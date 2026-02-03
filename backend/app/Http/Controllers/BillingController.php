<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    /**
     * Get current subscription status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = Subscription::where('user_id', $user->id)
            ->whereIn('status', ['active', 'trialing', 'past_due'])
            ->first();

        if (!$subscription) {
            return response()->json([
                'plan' => 'free',
                'status' => 'none',
                'is_active' => true,
                'features' => $this->getPlanFeatures('free'),
            ]);
        }

        return response()->json([
            ...$subscription->toApiResponse(),
            'features' => $this->getPlanFeatures($subscription->plan),
        ]);
    }

    /**
     * Get available plans.
     */
    public function plans(): JsonResponse
    {
        $plans = [
            [
                'id' => 'free',
                'name' => 'Free',
                'price' => 0,
                'interval' => 'month',
                'features' => $this->getPlanFeatures('free'),
            ],
            [
                'id' => 'pro',
                'name' => 'Pro',
                'price' => 19,
                'interval' => 'month',
                'stripe_price_id' => config('services.stripe.prices.pro'),
                'features' => $this->getPlanFeatures('pro'),
            ],
            [
                'id' => 'team',
                'name' => 'Team',
                'price' => 49,
                'interval' => 'month',
                'stripe_price_id' => config('services.stripe.prices.team'),
                'features' => $this->getPlanFeatures('team'),
            ],
            [
                'id' => 'enterprise',
                'name' => 'Enterprise',
                'price' => 199,
                'interval' => 'month',
                'stripe_price_id' => config('services.stripe.prices.enterprise'),
                'features' => $this->getPlanFeatures('enterprise'),
            ],
        ];

        return response()->json(['data' => $plans]);
    }

    /**
     * Create checkout session.
     */
    public function createCheckout(Request $request): JsonResponse
    {
        if (!config('services.stripe.secret')) {
            return response()->json([
                'message' => 'Billing is not configured',
                'code' => 'BILLING_NOT_CONFIGURED',
            ], 503);
        }

        $validated = $request->validate([
            'plan' => 'required|in:pro,team,enterprise',
        ]);

        $user = $request->user();
        $priceId = config("services.stripe.prices.{$validated['plan']}");

        if (!$priceId) {
            return response()->json([
                'message' => 'Invalid plan',
                'code' => 'INVALID_PLAN',
            ], 400);
        }

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            // Create or get customer
            $customerId = $user->stripe_customer_id;
            if (!$customerId) {
                $customer = \Stripe\Customer::create([
                    'email' => $user->email,
                    'name' => $user->name,
                    'metadata' => ['user_id' => $user->id],
                ]);
                $customerId = $customer->id;
                $user->update(['stripe_customer_id' => $customerId]);
            }

            $session = \Stripe\Checkout\Session::create([
                'customer' => $customerId,
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => $priceId,
                    'quantity' => 1,
                ]],
                'success_url' => config('app.frontend_url') . '/billing/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url') . '/billing/cancel',
                'metadata' => [
                    'user_id' => $user->id,
                    'plan' => $validated['plan'],
                ],
            ]);

            return response()->json(['checkout_url' => $session->url]);
        } catch (\Exception $e) {
            Log::error('Stripe checkout error', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to create checkout session',
                'code' => 'CHECKOUT_FAILED',
            ], 500);
        }
    }

    /**
     * Create customer portal session.
     */
    public function portal(Request $request): JsonResponse
    {
        if (!config('services.stripe.secret')) {
            return response()->json([
                'message' => 'Billing is not configured',
                'code' => 'BILLING_NOT_CONFIGURED',
            ], 503);
        }

        $user = $request->user();

        if (!$user->stripe_customer_id) {
            return response()->json([
                'message' => 'No billing account found',
                'code' => 'NO_CUSTOMER',
            ], 400);
        }

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $session = \Stripe\BillingPortal\Session::create([
                'customer' => $user->stripe_customer_id,
                'return_url' => config('app.frontend_url') . '/settings/billing',
            ]);

            return response()->json(['portal_url' => $session->url]);
        } catch (\Exception $e) {
            Log::error('Stripe portal error', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Failed to create portal session',
                'code' => 'PORTAL_FAILED',
            ], 500);
        }
    }

    /**
     * Handle Stripe webhook.
     */
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        if (!$webhookSecret) {
            return response()->json(['error' => 'Webhook not configured'], 400);
        }

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );

            switch ($event->type) {
                case 'customer.subscription.created':
                case 'customer.subscription.updated':
                    $this->handleSubscriptionUpdate($event->data->object);
                    break;

                case 'customer.subscription.deleted':
                    $this->handleSubscriptionDeleted($event->data->object);
                    break;

                case 'invoice.payment_failed':
                    $this->handlePaymentFailed($event->data->object);
                    break;
            }

            return response()->json(['received' => true]);
        } catch (\Exception $e) {
            Log::error('Webhook error', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Handle subscription create/update.
     */
    private function handleSubscriptionUpdate($stripeSubscription): void
    {
        $customerId = $stripeSubscription->customer;

        // Find user by customer ID
        $user = \App\Models\User::where('stripe_customer_id', $customerId)->first();
        if (!$user) {
            Log::warning('User not found for subscription', ['customer_id' => $customerId]);
            return;
        }

        // Map Stripe status
        $status = match ($stripeSubscription->status) {
            'active' => 'active',
            'trialing' => 'trialing',
            'past_due' => 'past_due',
            'canceled', 'unpaid' => 'canceled',
            default => 'active',
        };

        // Determine plan from price
        $plan = $this->getPlanFromPrice($stripeSubscription->items->data[0]->price->id ?? '');

        Subscription::updateOrCreate(
            ['stripe_subscription_id' => $stripeSubscription->id],
            [
                'user_id' => $user->id,
                'stripe_price_id' => $stripeSubscription->items->data[0]->price->id ?? '',
                'plan' => $plan,
                'status' => $status,
                'trial_ends_at' => $stripeSubscription->trial_end 
                    ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->trial_end) 
                    : null,
                'current_period_start' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_start),
                'current_period_end' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
                'canceled_at' => $stripeSubscription->canceled_at 
                    ? \Carbon\Carbon::createFromTimestamp($stripeSubscription->canceled_at) 
                    : null,
            ]
        );

        // Update user tier
        $user->update([
            'tier' => $plan,
            'tier_expires_at' => \Carbon\Carbon::createFromTimestamp($stripeSubscription->current_period_end),
        ]);

        Log::info('Subscription updated', [
            'user_id' => $user->id,
            'plan' => $plan,
            'status' => $status,
        ]);
    }

    /**
     * Handle subscription deleted.
     */
    private function handleSubscriptionDeleted($stripeSubscription): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $stripeSubscription->id)->first();
        
        if ($subscription) {
            $subscription->update([
                'status' => 'canceled',
                'canceled_at' => now(),
            ]);

            // Downgrade user to free
            $subscription->user->update([
                'tier' => 'free',
                'tier_expires_at' => null,
            ]);

            Log::info('Subscription canceled', ['subscription_id' => $subscription->id]);
        }
    }

    /**
     * Handle failed payment.
     */
    private function handlePaymentFailed($invoice): void
    {
        $subscription = Subscription::where('stripe_subscription_id', $invoice->subscription)->first();
        
        if ($subscription) {
            $subscription->update(['status' => 'past_due']);
            Log::warning('Payment failed', ['subscription_id' => $subscription->id]);
        }
    }

    /**
     * Get plan from Stripe price ID.
     */
    private function getPlanFromPrice(string $priceId): string
    {
        return match ($priceId) {
            config('services.stripe.prices.pro') => 'pro',
            config('services.stripe.prices.team') => 'team',
            config('services.stripe.prices.enterprise') => 'enterprise',
            default => 'free',
        };
    }

    /**
     * Get features for a plan.
     */
    private function getPlanFeatures(string $plan): array
    {
        return match ($plan) {
            'free' => [
                'max_prds' => 3,
                'max_collaborators' => 1,
                'ai_messages_per_day' => 10,
                'export_formats' => ['markdown'],
                'version_history' => false,
                'team_features' => false,
            ],
            'pro' => [
                'max_prds' => 50,
                'max_collaborators' => 5,
                'ai_messages_per_day' => 100,
                'export_formats' => ['markdown', 'html', 'pdf'],
                'version_history' => true,
                'team_features' => false,
            ],
            'team' => [
                'max_prds' => 200,
                'max_collaborators' => 20,
                'ai_messages_per_day' => 500,
                'export_formats' => ['markdown', 'html', 'pdf'],
                'version_history' => true,
                'team_features' => true,
            ],
            'enterprise' => [
                'max_prds' => -1, // unlimited
                'max_collaborators' => -1,
                'ai_messages_per_day' => -1,
                'export_formats' => ['markdown', 'html', 'pdf'],
                'version_history' => true,
                'team_features' => true,
            ],
            default => [],
        };
    }
}

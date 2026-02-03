<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test user can get billing status.
     */
    public function test_user_can_get_billing_status(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/billing/status');

        $response->assertStatus(200)
            ->assertJsonPath('plan', 'free')
            ->assertJsonPath('is_active', true)
            ->assertJsonStructure(['features']);
    }

    /**
     * Test user with subscription shows correct status.
     */
    public function test_subscription_shows_correct_status(): void
    {
        Subscription::create([
            'user_id' => $this->user->id,
            'stripe_subscription_id' => 'sub_test123',
            'stripe_price_id' => 'price_pro',
            'plan' => 'pro',
            'status' => 'active',
            'current_period_end' => now()->addMonth(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/billing/status');

        $response->assertStatus(200)
            ->assertJsonPath('plan', 'pro')
            ->assertJsonPath('status', 'active')
            ->assertJsonPath('is_active', true);
    }

    /**
     * Test user can get plans.
     */
    public function test_user_can_get_plans(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/billing/plans');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('data.0.id', 'free')
            ->assertJsonPath('data.1.id', 'pro');
    }

    /**
     * Test checkout requires Stripe config.
     */
    public function test_checkout_requires_stripe_config(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/billing/checkout', [
                'plan' => 'pro',
            ]);

        // Stripe not configured in tests
        $response->assertStatus(503)
            ->assertJsonPath('code', 'BILLING_NOT_CONFIGURED');
    }

    /**
     * Test portal requires customer.
     */
    public function test_portal_requires_customer(): void
    {
        // User without stripe_customer_id
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/billing/portal');

        // Either not configured or no customer
        $response->assertStatus(503);
    }

    /**
     * Test free plan features.
     */
    public function test_free_plan_features(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/billing/status');

        $response->assertStatus(200)
            ->assertJsonPath('features.max_prds', 3)
            ->assertJsonPath('features.team_features', false);
    }
}

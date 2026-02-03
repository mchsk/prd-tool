<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Redirect to Google OAuth.
     */
    public function redirectToGoogle(Request $request): RedirectResponse
    {
        // Generate state for CSRF protection
        $state = Str::random(40);
        session(['oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => config('services.google.redirect'),
            'scope' => implode(' ', [
                'openid',
                'email',
                'profile',
                'https://www.googleapis.com/auth/drive.file'
            ]),
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent', // Force refresh token
            'state' => $state,
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . $query);
    }

    /**
     * Handle Google OAuth callback.
     */
    public function handleGoogleCallback(Request $request): RedirectResponse
    {
        // Verify state
        if ($request->state !== session('oauth_state')) {
            Log::warning('OAuth state mismatch', [
                'expected' => session('oauth_state'),
                'actual' => $request->state,
            ]);
            return redirect(config('app.frontend_url') . '/login?error=invalid_state');
        }

        session()->forget('oauth_state');

        // Handle error from Google
        if ($request->has('error')) {
            Log::warning('OAuth error from Google', [
                'error' => $request->error,
                'error_description' => $request->error_description,
            ]);
            return redirect(config('app.frontend_url') . '/login?error=' . $request->error);
        }

        // Exchange code for tokens
        try {
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $request->code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.google.redirect'),
            ]);

            if (!$tokenResponse->successful()) {
                Log::error('Failed to exchange OAuth code', [
                    'status' => $tokenResponse->status(),
                    'body' => $tokenResponse->body(),
                ]);
                return redirect(config('app.frontend_url') . '/login?error=token_exchange_failed');
            }

            $tokens = $tokenResponse->json();

            // Get user info
            $userInfoResponse = Http::withToken($tokens['access_token'])
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if (!$userInfoResponse->successful()) {
                Log::error('Failed to get user info', [
                    'status' => $userInfoResponse->status(),
                ]);
                return redirect(config('app.frontend_url') . '/login?error=user_info_failed');
            }

            $googleUser = $userInfoResponse->json();

            // Find or create user
            $user = User::updateOrCreate(
                ['google_id' => $googleUser['id']],
                [
                    'name' => $googleUser['name'],
                    'email' => $googleUser['email'],
                    'avatar_url' => $googleUser['picture'] ?? null,
                    'google_access_token' => $tokens['access_token'],
                    'google_refresh_token' => $tokens['refresh_token'] ?? null,
                    'google_token_expires_at' => now()->addSeconds($tokens['expires_in']),
                ]
            );

            // Create session-based auth token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User authenticated', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            // Redirect to frontend with token
            return redirect(config('app.frontend_url') . '/?token=' . $token);

        } catch (\Exception $e) {
            Log::error('OAuth callback failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect(config('app.frontend_url') . '/login?error=auth_failed');
        }
    }

    /**
     * Get current authenticated user.
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated',
                'code' => 'UNAUTHENTICATED',
            ], 401);
        }

        return response()->json($user->toApiResponse());
    }

    /**
     * Logout the user.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user) {
            // Revoke current token if it exists
            $token = $user->currentAccessToken();
            if ($token) {
                $token->delete();
            }

            Log::info('User logged out', ['user_id' => $user->id]);
        }

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Refresh Google access token.
     */
    public function refreshGoogleToken(User $user): bool
    {
        if (!$user->google_refresh_token) {
            Log::warning('No refresh token available', ['user_id' => $user->id]);
            return false;
        }

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $user->google_refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                Log::error('Token refresh failed', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                ]);
                return false;
            }

            $tokens = $response->json();

            $user->update([
                'google_access_token' => $tokens['access_token'],
                'google_token_expires_at' => now()->addSeconds($tokens['expires_in']),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Token refresh exception', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}

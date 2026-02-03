<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test unauthenticated user cannot access /api/user.
     */
    public function test_unauthenticated_user_cannot_access_user_endpoint(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can access /api/user.
     */
    public function test_authenticated_user_can_access_user_endpoint(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'name',
                'email',
                'avatar_url',
                'preferred_language',
                'tier',
            ])
            ->assertJson([
                'id' => $user->id,
                'email' => $user->email,
            ]);
    }

    /**
     * Test logout endpoint revokes token.
     */
    public function test_logout_endpoint_works(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully']);
    }

    /**
     * Test Google OAuth redirect.
     */
    public function test_google_oauth_redirect(): void
    {
        $response = $this->get('/auth/google');

        $response->assertRedirect();
        $this->assertStringContainsString(
            'accounts.google.com/o/oauth2',
            $response->headers->get('Location')
        );
    }

    /**
     * Test OAuth callback with missing state returns error.
     */
    public function test_oauth_callback_requires_valid_state(): void
    {
        // Set a session state
        session(['oauth_state' => 'valid_state']);

        $response = $this->get('/auth/google/callback?state=invalid_state&code=test');

        $response->assertRedirect();
        $this->assertStringContainsString('error=invalid_state', $response->headers->get('Location'));
    }
}

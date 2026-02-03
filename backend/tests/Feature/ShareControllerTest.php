<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\PrdCollaborator;
use App\Models\PrdShareLink;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Prd $prd;
    private FileStorageService $fileStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->prd = Prd::factory()->create(['user_id' => $this->user->id]);
        $this->fileStorage = app(FileStorageService::class);
        $this->fileStorage->createPrd($this->user->id, $this->prd->id, '# Test PRD');
    }

    protected function tearDown(): void
    {
        $storagePath = storage_path('prds');
        if (is_dir($storagePath)) {
            $this->deleteDirectory($storagePath);
        }
        parent::tearDown();
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    // ============================================
    // COLLABORATORS
    // ============================================

    /**
     * Test owner can add collaborator.
     */
    public function test_owner_can_add_collaborator(): void
    {
        $collaborator = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/collaborators", [
                'email' => $collaborator->email,
                'role' => 'editor',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('role', 'editor');

        $this->assertDatabaseHas('prd_collaborators', [
            'prd_id' => $this->prd->id,
            'user_id' => $collaborator->id,
            'role' => 'editor',
        ]);
    }

    /**
     * Test cannot add non-existent user.
     */
    public function test_cannot_add_nonexistent_user(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/collaborators", [
                'email' => 'notexist@example.com',
                'role' => 'viewer',
            ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', 'USER_NOT_FOUND');
    }

    /**
     * Test cannot add self as collaborator.
     */
    public function test_cannot_add_self_as_collaborator(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/collaborators", [
                'email' => $this->user->email,
                'role' => 'editor',
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('code', 'SELF_INVITE');
    }

    /**
     * Test owner can remove collaborator.
     */
    public function test_owner_can_remove_collaborator(): void
    {
        $collaborator = User::factory()->create();
        $collab = PrdCollaborator::create([
            'prd_id' => $this->prd->id,
            'user_id' => $collaborator->id,
            'role' => 'viewer',
            'invited_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/prds/{$this->prd->id}/collaborators/{$collab->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('prd_collaborators', ['id' => $collab->id]);
    }

    // ============================================
    // SHARE LINKS
    // ============================================

    /**
     * Test owner can create share link.
     */
    public function test_owner_can_create_share_link(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/share-links", [
                'access_level' => 'view',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['id', 'token', 'url', 'access_level']);

        $this->assertDatabaseHas('prd_share_links', [
            'prd_id' => $this->prd->id,
            'access_level' => 'view',
        ]);
    }

    /**
     * Test share link with password.
     */
    public function test_share_link_with_password(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/share-links", [
                'password' => 'secret123',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('has_password', true);
    }

    /**
     * Test share link with expiration.
     */
    public function test_share_link_with_expiration(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/share-links", [
                'expires_in_days' => 7,
            ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('expires_at'));
    }

    /**
     * Test owner can revoke share link.
     */
    public function test_owner_can_revoke_share_link(): void
    {
        $link = PrdShareLink::create([
            'prd_id' => $this->prd->id,
            'token' => PrdShareLink::generateToken(),
            'access_level' => 'view',
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/prds/{$this->prd->id}/share-links/{$link->id}");

        $response->assertStatus(200);
        $this->assertFalse($link->fresh()->is_active);
    }

    // ============================================
    // PUBLIC ACCESS
    // ============================================

    /**
     * Test public can access shared PRD.
     */
    public function test_public_can_access_shared_prd(): void
    {
        $link = PrdShareLink::create([
            'prd_id' => $this->prd->id,
            'token' => PrdShareLink::generateToken(),
            'access_level' => 'view',
            'created_by' => $this->user->id,
        ]);

        $response = $this->postJson("/api/share/{$link->token}");

        $response->assertStatus(200)
            ->assertJsonPath('prd.id', $this->prd->id)
            ->assertJsonStructure(['prd', 'content', 'access_level']);
    }

    /**
     * Test password-protected link requires password.
     */
    public function test_password_protected_link_requires_password(): void
    {
        $link = PrdShareLink::create([
            'prd_id' => $this->prd->id,
            'token' => PrdShareLink::generateToken(),
            'access_level' => 'view',
            'created_by' => $this->user->id,
        ]);
        $link->setPassword('secret123');
        $link->save();

        $response = $this->postJson("/api/share/{$link->token}");

        $response->assertStatus(401)
            ->assertJsonPath('code', 'PASSWORD_REQUIRED');

        // With correct password
        $response = $this->postJson("/api/share/{$link->token}", [
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
    }

    /**
     * Test expired link is rejected.
     */
    public function test_expired_link_is_rejected(): void
    {
        $link = PrdShareLink::create([
            'prd_id' => $this->prd->id,
            'token' => PrdShareLink::generateToken(),
            'access_level' => 'view',
            'created_by' => $this->user->id,
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->postJson("/api/share/{$link->token}");

        $response->assertStatus(404)
            ->assertJsonPath('code', 'INVALID_LINK');
    }

    /**
     * Test revoked link is rejected.
     */
    public function test_revoked_link_is_rejected(): void
    {
        $link = PrdShareLink::create([
            'prd_id' => $this->prd->id,
            'token' => PrdShareLink::generateToken(),
            'access_level' => 'view',
            'created_by' => $this->user->id,
            'is_active' => false,
        ]);

        $response = $this->postJson("/api/share/{$link->token}");

        $response->assertStatus(404)
            ->assertJsonPath('code', 'INVALID_LINK');
    }
}

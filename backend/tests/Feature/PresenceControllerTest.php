<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\PrdCollaborator;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PresenceControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $collaborator;
    private Prd $prd;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create();
        $this->collaborator = User::factory()->create();
        $this->prd = Prd::factory()->create(['user_id' => $this->owner->id]);

        PrdCollaborator::create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->collaborator->id,
            'role' => 'editor',
            'invited_by' => $this->owner->id,
        ]);

        $fileStorage = app(FileStorageService::class);
        $fileStorage->createPrd($this->owner->id, $this->prd->id, '# Test');
    }

    protected function tearDown(): void
    {
        Cache::flush();
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

    /**
     * Test owner can get presence.
     */
    public function test_owner_can_get_presence(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/presence");

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    /**
     * Test user can update presence.
     */
    public function test_user_can_update_presence(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/presence", [
                'cursor_position' => 150,
                'is_typing' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('status', 'ok');
    }

    /**
     * Test presence shows active users.
     */
    public function test_presence_shows_active_users(): void
    {
        // Owner updates presence
        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/presence", [
                'cursor_position' => 100,
            ]);

        // Collaborator updates presence
        $this->actingAs($this->collaborator, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/presence", [
                'cursor_position' => 200,
            ]);

        // Check presence list
        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/presence");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test user can leave presence.
     */
    public function test_user_can_leave_presence(): void
    {
        // First join
        $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/presence", [
                'cursor_position' => 100,
            ]);

        // Then leave
        $response = $this->actingAs($this->owner, 'sanctum')
            ->deleteJson("/api/prds/{$this->prd->id}/presence");

        $response->assertStatus(200);
    }

    /**
     * Test non-collaborator cannot access presence.
     */
    public function test_non_collaborator_cannot_access_presence(): void
    {
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/presence");

        $response->assertStatus(404);
    }
}

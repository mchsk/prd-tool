<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\PrdVersion;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersionControllerTest extends TestCase
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
        $this->fileStorage->createPrd($this->user->id, $this->prd->id, '# Initial Content');
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

    /**
     * Test user can list versions.
     */
    public function test_user_can_list_versions(): void
    {
        PrdVersion::factory()->create([
            'prd_id' => $this->prd->id,
            'version_number' => 1,
        ]);
        PrdVersion::factory()->create([
            'prd_id' => $this->prd->id,
            'version_number' => 2,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/versions");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test user can create a version snapshot.
     */
    public function test_user_can_create_version(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/versions", [
                'summary' => 'First snapshot',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('version_number', 1)
            ->assertJsonPath('change_summary', 'First snapshot');
    }

    /**
     * Test cannot create version without changes.
     */
    public function test_cannot_create_version_without_changes(): void
    {
        // Create first version
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/versions");

        // Try to create another without changes
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/versions");

        $response->assertStatus(400)
            ->assertJsonPath('code', 'NO_CHANGES');
    }

    /**
     * Test user can view version with content.
     */
    public function test_user_can_view_version_with_content(): void
    {
        $version = PrdVersion::factory()->create([
            'prd_id' => $this->prd->id,
            'content' => '# Version Content',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/versions/{$version->id}");

        $response->assertStatus(200)
            ->assertJsonPath('content', '# Version Content');
    }

    /**
     * Test user can restore a version.
     */
    public function test_user_can_restore_version(): void
    {
        $version = PrdVersion::factory()->create([
            'prd_id' => $this->prd->id,
            'version_number' => 1,
            'content' => '# Old Content',
            'title' => $this->prd->title,
        ]);

        // Update current content
        $this->fileStorage->writePrd($this->user->id, $this->prd->id, '# New Content');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/versions/{$version->id}/restore");

        $response->assertStatus(200)
            ->assertJsonPath('message', 'Version restored');

        // Verify content was restored
        $currentContent = $this->fileStorage->readPrd($this->user->id, $this->prd->id);
        $this->assertEquals('# Old Content', $currentContent);
    }

    /**
     * Test user can compare versions.
     */
    public function test_user_can_compare_versions(): void
    {
        $v1 = PrdVersion::factory()->create([
            'prd_id' => $this->prd->id,
            'version_number' => 1,
            'content' => '# Version 1',
        ]);
        $v2 = PrdVersion::factory()->create([
            'prd_id' => $this->prd->id,
            'version_number' => 2,
            'content' => '# Version 2',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/versions/compare", [
                'from_version' => (string) $v1->id,
                'to_version' => (string) $v2->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('from.version_number', 1)
            ->assertJsonPath('to.version_number', 2)
            ->assertJsonPath('from.content', '# Version 1')
            ->assertJsonPath('to.content', '# Version 2');
    }

    /**
     * Test user cannot access other users' versions.
     */
    public function test_user_cannot_access_others_versions(): void
    {
        $otherUser = User::factory()->create();
        $otherPrd = Prd::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$otherPrd->id}/versions");

        $response->assertStatus(404);
    }
}

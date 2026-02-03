<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrdControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private FileStorageService $fileStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->fileStorage = app(FileStorageService::class);
    }

    protected function tearDown(): void
    {
        // Clean up any created files
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
     * Test user can list their PRDs.
     */
    public function test_user_can_list_their_prds(): void
    {
        // Create PRDs for the user
        Prd::factory()->count(3)->create(['user_id' => $this->user->id]);
        
        // Create PRDs for another user (should not appear)
        $otherUser = User::factory()->create();
        Prd::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/prds');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'status', 'created_at', 'updated_at'],
                ],
            ]);
    }

    /**
     * Test user can create a PRD.
     */
    public function test_user_can_create_prd(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/prds', [
                'title' => 'My New PRD',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'My New PRD')
            ->assertJsonPath('status', 'draft');

        $this->assertDatabaseHas('prds', [
            'user_id' => $this->user->id,
            'title' => 'My New PRD',
        ]);

        // Verify file was created
        $prdId = $response->json('id');
        $this->assertTrue(
            $this->fileStorage->exists($this->user->id, $prdId)
        );
    }

    /**
     * Test user can create PRD with default title.
     */
    public function test_prd_created_with_default_title(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/prds', []);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'Untitled PRD');
    }

    /**
     * Test user can view their PRD.
     */
    public function test_user_can_view_their_prd(): void
    {
        $prd = Prd::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$prd->id}");

        $response->assertStatus(200)
            ->assertJsonPath('id', $prd->id)
            ->assertJsonPath('title', $prd->title);
    }

    /**
     * Test user cannot view another user's PRD.
     */
    public function test_user_cannot_view_other_users_prd(): void
    {
        $otherUser = User::factory()->create();
        $prd = Prd::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$prd->id}");

        $response->assertStatus(404);
    }

    /**
     * Test user can update their PRD.
     */
    public function test_user_can_update_prd(): void
    {
        $prd = Prd::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/prds/{$prd->id}", [
                'title' => 'Updated Title',
                'status' => 'active',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('title', 'Updated Title')
            ->assertJsonPath('status', 'active');
    }

    /**
     * Test user can delete their PRD.
     */
    public function test_user_can_delete_prd(): void
    {
        $prd = Prd::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/prds/{$prd->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'PRD deleted']);

        $this->assertSoftDeleted('prds', ['id' => $prd->id]);
    }

    /**
     * Test user can update PRD content.
     */
    public function test_user_can_update_prd_content(): void
    {
        // Create PRD with file
        $prd = Prd::factory()->create(['user_id' => $this->user->id]);
        $this->fileStorage->createPrd($this->user->id, $prd->id, '');

        $content = "# My PRD\n\nThis is the content.";

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/prds/{$prd->id}/content", [
                'content' => $content,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'estimated_tokens', 'updated_at']);

        // Verify file content
        $savedContent = $this->fileStorage->readPrd($this->user->id, $prd->id);
        $this->assertEquals($content, $savedContent);
    }

    /**
     * Test user can get PRD content.
     */
    public function test_user_can_get_prd_content(): void
    {
        $prd = Prd::factory()->create(['user_id' => $this->user->id]);
        $content = "# Test PRD Content";
        $this->fileStorage->createPrd($this->user->id, $prd->id, $content);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$prd->id}/content");

        $response->assertStatus(200)
            ->assertJsonPath('content', $content);
    }

    /**
     * Test PRD list can be filtered by status.
     */
    public function test_prds_can_be_filtered_by_status(): void
    {
        Prd::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'draft',
        ]);
        Prd::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/prds?status=active');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}

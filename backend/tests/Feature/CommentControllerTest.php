<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\PrdCollaborator;
use App\Models\PrdComment;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
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

        // Add collaborator
        PrdCollaborator::create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->collaborator->id,
            'role' => 'editor',
            'invited_by' => $this->owner->id,
        ]);

        // Create PRD file
        $fileStorage = app(FileStorageService::class);
        $fileStorage->createPrd($this->owner->id, $this->prd->id, '# Test PRD');
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
     * Test owner can list comments.
     */
    public function test_owner_can_list_comments(): void
    {
        PrdComment::factory()->count(3)->create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test collaborator can list comments.
     */
    public function test_collaborator_can_list_comments(): void
    {
        PrdComment::factory()->count(2)->create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->collaborator, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test user can create comment.
     */
    public function test_user_can_create_comment(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/comments", [
                'content' => 'This is a test comment',
                'line_number' => 5,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('content', 'This is a test comment')
            ->assertJsonPath('line_number', 5);
    }

    /**
     * Test user can reply to comment.
     */
    public function test_user_can_reply_to_comment(): void
    {
        $parent = PrdComment::factory()->create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->collaborator, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/comments", [
                'content' => 'This is a reply',
                'parent_id' => $parent->id,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('parent_id', (string) $parent->id);
    }

    /**
     * Test author can edit their own comment.
     */
    public function test_author_can_edit_own_comment(): void
    {
        $comment = PrdComment::factory()->create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->owner->id,
            'content' => 'Original content',
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/prds/{$this->prd->id}/comments/{$comment->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('content', 'Updated content');
    }

    /**
     * Test user cannot edit others' comments.
     */
    public function test_user_cannot_edit_others_comments(): void
    {
        $comment = PrdComment::factory()->create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->collaborator, 'sanctum')
            ->putJson("/api/prds/{$this->prd->id}/comments/{$comment->id}", [
                'content' => 'Trying to edit',
            ]);

        $response->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN');
    }

    /**
     * Test owner can resolve comment.
     */
    public function test_owner_can_resolve_comment(): void
    {
        $comment = PrdComment::factory()->create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->collaborator->id,
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->putJson("/api/prds/{$this->prd->id}/comments/{$comment->id}", [
                'is_resolved' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('is_resolved', true);
    }

    /**
     * Test author can delete their comment.
     */
    public function test_author_can_delete_own_comment(): void
    {
        $comment = PrdComment::factory()->create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->collaborator->id,
        ]);

        $response = $this->actingAs($this->collaborator, 'sanctum')
            ->deleteJson("/api/prds/{$this->prd->id}/comments/{$comment->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('prd_comments', ['id' => $comment->id]);
    }

    /**
     * Test PRD owner can delete any comment.
     */
    public function test_prd_owner_can_delete_any_comment(): void
    {
        $comment = PrdComment::factory()->create([
            'prd_id' => $this->prd->id,
            'user_id' => $this->collaborator->id,
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->deleteJson("/api/prds/{$this->prd->id}/comments/{$comment->id}");

        $response->assertStatus(200);
    }

    /**
     * Test non-collaborator cannot access comments.
     */
    public function test_non_collaborator_cannot_access_comments(): void
    {
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/comments");

        $response->assertStatus(404);
    }
}

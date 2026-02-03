<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Prd;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
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
        
        // Create PRD file
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

    /**
     * Test user can get messages for their PRD.
     */
    public function test_user_can_get_messages(): void
    {
        // Create some messages
        Message::factory()->count(3)->create([
            'prd_id' => $this->prd->id,
            'role' => 'user',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/messages");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'role', 'content', 'created_at'],
                ],
            ]);
    }

    /**
     * Test user cannot get messages for another user's PRD.
     */
    public function test_user_cannot_get_messages_for_other_users_prd(): void
    {
        $otherUser = User::factory()->create();
        $otherPrd = Prd::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$otherPrd->id}/messages");

        $response->assertStatus(404);
    }

    /**
     * Test messages are ordered by created_at.
     */
    public function test_messages_are_ordered_by_created_at(): void
    {
        $older = Message::factory()->create([
            'prd_id' => $this->prd->id,
            'created_at' => now()->subMinutes(10),
        ]);

        $newer = Message::factory()->create([
            'prd_id' => $this->prd->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/messages");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals($older->id, $data[0]['id']);
        $this->assertEquals($newer->id, $data[1]['id']);
    }

    /**
     * Test apply update requires message with suggestion.
     */
    public function test_apply_update_requires_suggestion(): void
    {
        $message = Message::factory()->create([
            'prd_id' => $this->prd->id,
            'prd_update_suggestion' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/messages/{$message->id}/apply");

        $response->assertStatus(400)
            ->assertJsonPath('code', 'NO_UPDATE');
    }

    /**
     * Test apply update works with valid suggestion.
     */
    public function test_apply_update_works(): void
    {
        $message = Message::factory()->create([
            'prd_id' => $this->prd->id,
            'prd_update_suggestion' => '## New Section\n\nThis is new content.',
            'update_applied' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/messages/{$message->id}/apply");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Update applied successfully']);

        // Verify message is marked as applied
        $this->assertTrue($message->fresh()->update_applied);

        // Verify PRD content was updated
        $content = $this->fileStorage->readPrd($this->user->id, $this->prd->id);
        $this->assertStringContainsString('New Section', $content);
    }
}

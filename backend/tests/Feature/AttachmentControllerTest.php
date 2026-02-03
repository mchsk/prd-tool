<?php

namespace Tests\Feature;

use App\Models\DraftAttachment;
use App\Models\Prd;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Prd $prd;
    private FileStorageService $fileStorage;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        
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
     * Test user can list attachments.
     */
    public function test_user_can_list_attachments(): void
    {
        DraftAttachment::factory()->count(3)->create([
            'prd_id' => $this->prd->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/attachments");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test user can upload text file.
     */
    public function test_user_can_upload_text_file(): void
    {
        $file = UploadedFile::fake()->createWithContent('test.txt', 'Hello World');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('original_filename', 'test.txt')
            ->assertJsonPath('status', 'ready');
    }

    /**
     * Test unsupported file type is rejected.
     */
    public function test_unsupported_file_type_rejected(): void
    {
        $file = UploadedFile::fake()->create('test.exe', 100, 'application/octet-stream');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('code', 'UNSUPPORTED_FILE_TYPE');
    }

    /**
     * Test user can get attachment with extracted text.
     */
    public function test_user_can_get_attachment_with_text(): void
    {
        $attachment = DraftAttachment::factory()->create([
            'prd_id' => $this->prd->id,
            'extracted_text' => 'Extracted content here',
            'status' => 'ready',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('extracted_text', 'Extracted content here');
    }

    /**
     * Test user can delete attachment.
     */
    public function test_user_can_delete_attachment(): void
    {
        $attachment = DraftAttachment::factory()->create([
            'prd_id' => $this->prd->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/prds/{$this->prd->id}/attachments/{$attachment->id}");

        $response->assertStatus(200)
            ->assertJson(['message' => 'Attachment deleted']);

        $this->assertDatabaseMissing('draft_attachments', ['id' => $attachment->id]);
    }

    /**
     * Test user cannot access other user's attachments.
     */
    public function test_user_cannot_access_other_users_attachments(): void
    {
        $otherUser = User::factory()->create();
        $otherPrd = Prd::factory()->create(['user_id' => $otherUser->id]);
        $attachment = DraftAttachment::factory()->create([
            'prd_id' => $otherPrd->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$otherPrd->id}/attachments/{$attachment->id}");

        $response->assertStatus(404);
    }
}

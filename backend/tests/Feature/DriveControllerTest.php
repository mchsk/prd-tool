<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriveControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Prd $prd;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->prd = Prd::factory()->create(['user_id' => $this->user->id]);

        $fileStorage = app(FileStorageService::class);
        $fileStorage->createPrd($this->user->id, $this->prd->id, '# Test PRD');
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
     * Test user can get drive status.
     */
    public function test_user_can_get_drive_status(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/drive/status');

        $response->assertStatus(200)
            ->assertJsonStructure(['available', 'connected', 'picker_api_key']);
    }

    /**
     * Test picker token requires google connection.
     */
    public function test_picker_token_requires_google_connection(): void
    {
        // User without Google token
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/drive/picker-token');

        // Should fail since Drive is not configured in tests
        $response->assertStatus(503)
            ->assertJsonPath('code', 'DRIVE_NOT_CONFIGURED');
    }

    /**
     * Test import requires drive configuration.
     */
    public function test_import_requires_drive_configuration(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/drive/import", [
                'file_id' => 'some-google-file-id',
            ]);

        // Should fail since Drive is not configured in tests
        $response->assertStatus(503)
            ->assertJsonPath('code', 'DRIVE_NOT_CONFIGURED');
    }

    /**
     * Test list files requires drive configuration.
     */
    public function test_list_files_requires_drive_configuration(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/drive/files');

        // Should fail since Drive is not configured in tests
        $response->assertStatus(503)
            ->assertJsonPath('code', 'DRIVE_NOT_CONFIGURED');
    }
}

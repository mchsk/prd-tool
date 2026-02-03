<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Prd $prd;
    private FileStorageService $fileStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->prd = Prd::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test PRD Document',
        ]);
        $this->fileStorage = app(FileStorageService::class);
        $this->fileStorage->createPrd(
            $this->user->id,
            $this->prd->id,
            "# Test PRD\n\nThis is **bold** text.\n\n## Features\n\n- Feature 1\n- Feature 2"
        );
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
     * Test user can get export formats.
     */
    public function test_user_can_get_export_formats(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/export/formats");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.format', 'markdown')
            ->assertJsonPath('data.1.format', 'html');
    }

    /**
     * Test user can export as markdown.
     */
    public function test_user_can_export_markdown(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/prds/{$this->prd->id}/export/markdown");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/markdown; charset=UTF-8');

        $this->assertStringContainsString('# Test PRD', $response->getContent());
        $this->assertStringContainsString('Feature 1', $response->getContent());
    }

    /**
     * Test user can export as HTML.
     */
    public function test_user_can_export_html(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/prds/{$this->prd->id}/export/html");

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');

        $content = $response->getContent();
        $this->assertStringContainsString('<html', $content);
        $this->assertStringContainsString('<strong>bold</strong>', $content);
        $this->assertStringContainsString('Feature 1', $content);
    }

    /**
     * Test PDF export returns unavailable when not configured.
     */
    public function test_pdf_export_unavailable_without_puppeteer(): void
    {
        // PDF requires Puppeteer which is not configured in tests
        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/prds/{$this->prd->id}/export/pdf");

        $response->assertStatus(503)
            ->assertJsonPath('code', 'PDF_UNAVAILABLE');
    }

    /**
     * Test user cannot export other users' PRD.
     */
    public function test_user_cannot_export_others_prd(): void
    {
        $otherUser = User::factory()->create();
        $otherPrd = Prd::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/prds/{$otherPrd->id}/export/markdown");

        $response->assertStatus(404);
    }

    /**
     * Test markdown export has correct filename.
     */
    public function test_markdown_export_has_correct_filename(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/prds/{$this->prd->id}/export/markdown");

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('.md', $disposition);
    }
}

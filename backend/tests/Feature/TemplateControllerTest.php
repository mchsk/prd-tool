<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
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
     * Test user can list templates.
     */
    public function test_user_can_list_templates(): void
    {
        // Create user's own templates
        Template::factory()->count(2)->create(['user_id' => $this->user->id]);
        // Create public template
        Template::factory()->public()->create();
        // Create system template
        Template::factory()->system()->create();
        // Create other user's private template (should not be visible)
        Template::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/templates');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data'); // 2 own + 1 public + 1 system
    }

    /**
     * Test user can filter by category.
     */
    public function test_user_can_filter_by_category(): void
    {
        Template::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'software',
        ]);
        Template::factory()->create([
            'user_id' => $this->user->id,
            'category' => 'mobile',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/templates?category=software');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'software');
    }

    /**
     * Test user can create template.
     */
    public function test_user_can_create_template(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/templates', [
                'name' => 'My Template',
                'description' => 'A test template',
                'content' => '# Template\n\n## Section',
                'category' => 'software',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'My Template')
            ->assertJsonPath('is_owner', true);
    }

    /**
     * Test user can update their template.
     */
    public function test_user_can_update_template(): void
    {
        $template = Template::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/templates/{$template->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Name');
    }

    /**
     * Test user cannot update others' templates.
     */
    public function test_user_cannot_update_others_templates(): void
    {
        $otherTemplate = Template::factory()->public()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/templates/{$otherTemplate->id}", [
                'name' => 'Trying to update',
            ]);

        $response->assertStatus(404);
    }

    /**
     * Test user can delete their template.
     */
    public function test_user_can_delete_template(): void
    {
        $template = Template::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/templates/{$template->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }

    /**
     * Test user can create PRD from template.
     */
    public function test_user_can_create_prd_from_template(): void
    {
        $template = Template::factory()->create([
            'user_id' => $this->user->id,
            'content' => '# Template Content',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/templates/{$template->id}/create-prd", [
                'title' => 'My New PRD',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('title', 'My New PRD')
            ->assertJsonPath('created_from_template_id', (string) $template->id);

        // Template usage should increment
        $this->assertEquals(1, $template->fresh()->usage_count);
    }

    /**
     * Test user can get categories.
     */
    public function test_user_can_get_categories(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/templates/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'name']]]);
    }
}

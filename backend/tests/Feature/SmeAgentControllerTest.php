<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\SmeAgent;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmeAgentControllerTest extends TestCase
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
        $fileStorage->createPrd($this->user->id, $this->prd->id, '# Test');
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
     * Test user can list agents.
     */
    public function test_user_can_list_agents(): void
    {
        SmeAgent::factory()->count(2)->create(['user_id' => $this->user->id]);
        SmeAgent::factory()->system()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/agents');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test user can create agent.
     */
    public function test_user_can_create_agent(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/agents', [
                'name' => 'Security Expert',
                'description' => 'Expert in application security',
                'system_prompt' => 'You are a security expert...',
                'category' => 'security',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'Security Expert')
            ->assertJsonPath('category', 'security');
    }

    /**
     * Test user can update own agent.
     */
    public function test_user_can_update_own_agent(): void
    {
        $agent = SmeAgent::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/agents/{$agent->id}", [
                'name' => 'Updated Agent',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Agent');
    }

    /**
     * Test user cannot update others agents.
     */
    public function test_user_cannot_update_others_agents(): void
    {
        $otherUser = User::factory()->create();
        $agent = SmeAgent::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/agents/{$agent->id}", [
                'name' => 'Hacked',
            ]);

        $response->assertStatus(404);
    }

    /**
     * Test user can delete own agent.
     */
    public function test_user_can_delete_own_agent(): void
    {
        $agent = SmeAgent::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/agents/{$agent->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('sme_agents', ['id' => $agent->id]);
    }

    /**
     * Test user can assign agents to PRD.
     */
    public function test_user_can_assign_agents_to_prd(): void
    {
        $agent = SmeAgent::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/agents", [
                'agents' => [
                    ['id' => (string) $agent->id, 'priority' => 1],
                ],
            ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('prd_sme_agents', [
            'prd_id' => $this->prd->id,
            'sme_agent_id' => $agent->id,
            'priority' => 1,
        ]);
    }

    /**
     * Test user can get assigned agents.
     */
    public function test_user_can_get_assigned_agents(): void
    {
        $agent = SmeAgent::factory()->create(['user_id' => $this->user->id]);
        
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/agents", [
                'agents' => [['id' => (string) $agent->id]],
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/agents");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test user can get combined prompt.
     */
    public function test_user_can_get_combined_prompt(): void
    {
        $agent = SmeAgent::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Expert',
            'system_prompt' => 'You are a test expert.',
        ]);
        
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/agents", [
                'agents' => [['id' => (string) $agent->id]],
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/agents/prompt");

        $response->assertStatus(200)
            ->assertJsonStructure(['prompt']);
        
        $this->assertStringContainsString('Test Expert', $response->json('prompt'));
    }

    /**
     * Test user can get categories.
     */
    public function test_user_can_get_categories(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/agents/categories');

        $response->assertStatus(200)
            ->assertJsonStructure(['data']);
    }
}

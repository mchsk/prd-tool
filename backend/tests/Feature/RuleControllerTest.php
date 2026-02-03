<?php

namespace Tests\Feature;

use App\Models\Prd;
use App\Models\Rule;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RuleControllerTest extends TestCase
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
     * Test user can list their rules.
     */
    public function test_user_can_list_rules(): void
    {
        Rule::factory()->count(3)->create(['user_id' => $this->user->id]);
        Rule::factory()->count(2)->create(); // Other user's rules

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/rules');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test user can create a rule.
     */
    public function test_user_can_create_rule(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/rules', [
                'name' => 'My Test Rule',
                'content' => 'Always follow accessibility guidelines.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'My Test Rule');

        $this->assertDatabaseHas('rules', [
            'user_id' => $this->user->id,
            'name' => 'My Test Rule',
        ]);
    }

    /**
     * Test user can update their rule.
     */
    public function test_user_can_update_rule(): void
    {
        $rule = Rule::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/rules/{$rule->id}", [
                'name' => 'Updated Rule Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Rule Name');
    }

    /**
     * Test user can delete their rule.
     */
    public function test_user_can_delete_rule(): void
    {
        $rule = Rule::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/rules/{$rule->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('rules', ['id' => $rule->id]);
    }

    /**
     * Test user cannot access other users' rules.
     */
    public function test_user_cannot_access_others_rules(): void
    {
        $otherRule = Rule::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/rules/{$otherRule->id}");

        $response->assertStatus(404);
    }

    /**
     * Test user can assign rules to PRD.
     */
    public function test_user_can_assign_rules_to_prd(): void
    {
        $rules = Rule::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/rules", [
                'rules' => [
                    ['id' => (string) $rules[0]->id, 'priority' => 0],
                    ['id' => (string) $rules[1]->id, 'priority' => 1],
                ],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('prd_rules', [
            'prd_id' => $this->prd->id,
            'rule_id' => $rules[0]->id,
            'priority' => 0,
        ]);
    }

    /**
     * Test user can get assigned rules.
     */
    public function test_user_can_get_assigned_rules(): void
    {
        $rule = Rule::factory()->create(['user_id' => $this->user->id]);

        // Assign using the assign endpoint to use proper sync
        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/rules", [
                'rules' => [
                    ['id' => (string) $rule->id, 'priority' => 0],
                ],
            ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/prds/{$this->prd->id}/rules");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (string) $rule->id);
    }

    /**
     * Test cannot assign other users' rules.
     */
    public function test_cannot_assign_others_rules(): void
    {
        $otherRule = Rule::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/prds/{$this->prd->id}/rules", [
                'rules' => [
                    ['id' => (string) $otherRule->id, 'priority' => 0],
                ],
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('code', 'INVALID_RULES');
    }

    /**
     * Test rule content limit is enforced.
     */
    public function test_rule_content_limit_enforced(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/rules', [
                'name' => 'Test Rule',
                'content' => str_repeat('a', 60000), // Exceeds 50KB
            ]);

        $response->assertStatus(422);
    }
}

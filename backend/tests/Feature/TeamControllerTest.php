<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test user can list their teams.
     */
    public function test_user_can_list_teams(): void
    {
        Team::factory()->count(2)->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/teams');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test user can create a team.
     */
    public function test_user_can_create_team(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/teams', [
                'name' => 'My Team',
                'description' => 'A test team',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('name', 'My Team')
            ->assertJsonPath('is_owner', true);
    }

    /**
     * Test user can update their team.
     */
    public function test_user_can_update_team(): void
    {
        $team = Team::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/teams/{$team->id}", [
                'name' => 'Updated Team Name',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Updated Team Name');
    }

    /**
     * Test user can delete their team.
     */
    public function test_user_can_delete_team(): void
    {
        $team = Team::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/teams/{$team->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('teams', ['id' => $team->id]);
    }

    /**
     * Test owner can add members.
     */
    public function test_owner_can_add_member(): void
    {
        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $newMember = User::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/teams/{$team->id}/members", [
                'email' => $newMember->email,
                'role' => 'member',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('role', 'member');
    }

    /**
     * Test cannot add non-existent user.
     */
    public function test_cannot_add_nonexistent_user(): void
    {
        $team = Team::factory()->create(['owner_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/teams/{$team->id}/members", [
                'email' => 'nobody@example.com',
            ]);

        $response->assertStatus(404)
            ->assertJsonPath('code', 'USER_NOT_FOUND');
    }

    /**
     * Test owner can remove members.
     */
    public function test_owner_can_remove_member(): void
    {
        $team = Team::factory()->create(['owner_id' => $this->user->id]);
        $member = User::factory()->create();
        $teamMember = TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member->id,
            'role' => 'member',
            'invited_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/teams/{$team->id}/members/{$teamMember->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('team_members', ['id' => $teamMember->id]);
    }

    /**
     * Test team member can access team.
     */
    public function test_member_can_access_team(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $this->user->id,
            'role' => 'member',
            'invited_by' => $owner->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/teams/{$team->id}");

        $response->assertStatus(200);
    }

    /**
     * Test non-member cannot access team.
     */
    public function test_non_member_cannot_access_team(): void
    {
        $owner = User::factory()->create();
        $team = Team::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/teams/{$team->id}");

        $response->assertStatus(404);
    }

    /**
     * Test team capacity limit.
     */
    public function test_team_capacity_limit(): void
    {
        $team = Team::factory()->create([
            'owner_id' => $this->user->id,
            'max_members' => 1,
        ]);
        
        // Add first member (at capacity)
        $member1 = User::factory()->create();
        TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $member1->id,
            'role' => 'member',
            'invited_by' => $this->user->id,
        ]);

        // Try to add second member
        $member2 = User::factory()->create();
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/teams/{$team->id}/members", [
                'email' => $member2->email,
            ]);

        $response->assertStatus(400)
            ->assertJsonPath('code', 'TEAM_FULL');
    }
}

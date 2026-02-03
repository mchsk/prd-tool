<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    /**
     * List teams the user belongs to.
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Teams user owns
        $ownedTeams = Team::where('owner_id', $userId)
            ->with('owner')
            ->get();

        // Teams user is a member of
        $memberTeamIds = TeamMember::where('user_id', $userId)
            ->pluck('team_id');

        $memberTeams = Team::whereIn('id', $memberTeamIds)
            ->where('owner_id', '!=', $userId)
            ->with('owner')
            ->get();

        $allTeams = $ownedTeams->merge($memberTeams)->map(function (Team $t) use ($userId) {
            $response = $t->toApiResponse();
            $response['is_owner'] = (string) $t->owner_id === (string) $userId;
            return $response;
        });

        return response()->json(['data' => $allTeams]);
    }

    /**
     * Create a new team.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $team = Team::create([
            'name' => $validated['name'],
            'slug' => Team::generateSlug($validated['name']),
            'owner_id' => $request->user()->id,
            'description' => $validated['description'] ?? null,
        ]);

        Log::info('Team created', ['team_id' => $team->id]);

        $response = $team->toApiResponse();
        $response['is_owner'] = true;

        return response()->json($response, 201);
    }

    /**
     * Get a specific team.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $team = $this->findAccessibleTeam($request, $id);

        $response = $team->toApiResponse();
        $response['is_owner'] = (string) $team->owner_id === (string) $request->user()->id;

        return response()->json($response);
    }

    /**
     * Update a team.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $team = $this->findOwnedTeam($request, $id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if (isset($validated['name']) && $validated['name'] !== $team->name) {
            $validated['slug'] = Team::generateSlug($validated['name']);
        }

        $team->update($validated);

        return response()->json($team->fresh()->toApiResponse());
    }

    /**
     * Delete a team.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $team = $this->findOwnedTeam($request, $id);

        $team->delete();

        Log::info('Team deleted', ['team_id' => $id]);

        return response()->json(['message' => 'Team deleted']);
    }

    /**
     * List team members.
     */
    public function members(Request $request, string $id): JsonResponse
    {
        $team = $this->findAccessibleTeam($request, $id);

        $members = TeamMember::where('team_id', $team->id)
            ->with(['user', 'inviter'])
            ->get()
            ->map(fn (TeamMember $m) => $m->toApiResponse());

        return response()->json(['data' => $members]);
    }

    /**
     * Add a member to the team.
     */
    public function addMember(Request $request, string $id): JsonResponse
    {
        $team = $this->findOwnedOrAdminTeam($request, $id);

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'sometimes|in:admin,member',
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found. They must sign up first.',
                'code' => 'USER_NOT_FOUND',
            ], 404);
        }

        // Check if already a member
        $existing = TeamMember::where('team_id', $team->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'User is already a team member',
                'code' => 'ALREADY_MEMBER',
            ], 400);
        }

        // Check capacity
        if (!$team->hasCapacity()) {
            return response()->json([
                'message' => 'Team has reached maximum member limit',
                'code' => 'TEAM_FULL',
            ], 400);
        }

        $member = TeamMember::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'role' => $validated['role'] ?? 'member',
            'invited_by' => $request->user()->id,
        ]);

        $member->load(['user', 'inviter']);

        Log::info('Team member added', [
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        return response()->json($member->toApiResponse(), 201);
    }

    /**
     * Remove a member from the team.
     */
    public function removeMember(Request $request, string $id, string $memberId): JsonResponse
    {
        $team = $this->findOwnedOrAdminTeam($request, $id);

        $member = TeamMember::where('id', $memberId)
            ->where('team_id', $team->id)
            ->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        // Cannot remove the owner
        if ((string) $member->user_id === (string) $team->owner_id) {
            return response()->json([
                'message' => 'Cannot remove team owner',
                'code' => 'CANNOT_REMOVE_OWNER',
            ], 400);
        }

        $member->delete();

        Log::info('Team member removed', [
            'team_id' => $team->id,
            'member_id' => $memberId,
        ]);

        return response()->json(['message' => 'Member removed']);
    }

    /**
     * Update a member's role.
     */
    public function updateMember(Request $request, string $id, string $memberId): JsonResponse
    {
        $team = $this->findOwnedTeam($request, $id);

        $member = TeamMember::where('id', $memberId)
            ->where('team_id', $team->id)
            ->first();

        if (!$member) {
            return response()->json([
                'message' => 'Member not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,member',
        ]);

        $member->update(['role' => $validated['role']]);

        return response()->json($member->fresh()->load(['user', 'inviter'])->toApiResponse());
    }

    /**
     * Find a team accessible by the user.
     */
    private function findAccessibleTeam(Request $request, string $id): Team
    {
        $userId = $request->user()->id;

        $team = Team::where('id', $id)->first();

        if (!$team) {
            abort(404);
        }

        // Owner has access
        if ((string) $team->owner_id === (string) $userId) {
            return $team;
        }

        // Members have access
        $isMember = TeamMember::where('team_id', $id)
            ->where('user_id', $userId)
            ->exists();

        if ($isMember) {
            return $team;
        }

        abort(404);
    }

    /**
     * Find a team owned by the user.
     */
    private function findOwnedTeam(Request $request, string $id): Team
    {
        $team = Team::where('id', $id)
            ->where('owner_id', $request->user()->id)
            ->first();

        if (!$team) {
            abort(404);
        }

        return $team;
    }

    /**
     * Find a team owned by user or where user is admin.
     */
    private function findOwnedOrAdminTeam(Request $request, string $id): Team
    {
        $userId = $request->user()->id;

        $team = Team::where('id', $id)->first();

        if (!$team) {
            abort(404);
        }

        // Owner has access
        if ((string) $team->owner_id === (string) $userId) {
            return $team;
        }

        // Admin members have access
        $isAdmin = TeamMember::where('team_id', $id)
            ->where('user_id', $userId)
            ->where('role', 'admin')
            ->exists();

        if ($isAdmin) {
            return $team;
        }

        abort(403);
    }
}

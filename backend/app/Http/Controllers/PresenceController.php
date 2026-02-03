<?php

namespace App\Http\Controllers;

use App\Models\Prd;
use App\Models\PrdCollaborator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PresenceController extends Controller
{
    private const PRESENCE_TTL = 30; // seconds

    /**
     * Get active users in a PRD.
     */
    public function index(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);
        $userId = $request->user()->id;

        // Get all presence data for this PRD
        $presenceKey = "prd:{$prd->id}:presence";
        $presenceData = Cache::get($presenceKey, []);

        // Filter out stale entries and format response
        $activeUsers = [];
        $now = now()->timestamp;

        foreach ($presenceData as $id => $data) {
            if (($now - $data['updated_at']) < self::PRESENCE_TTL) {
                $activeUsers[] = [
                    'user_id' => $id,
                    'name' => $data['name'],
                    'avatar_url' => $data['avatar_url'],
                    'cursor_position' => $data['cursor_position'] ?? null,
                    'selection' => $data['selection'] ?? null,
                    'is_typing' => $data['is_typing'] ?? false,
                    'last_seen' => $data['updated_at'],
                ];
            }
        }

        return response()->json(['data' => $activeUsers]);
    }

    /**
     * Update current user's presence.
     */
    public function update(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);
        $user = $request->user();

        $validated = $request->validate([
            'cursor_position' => 'nullable|integer|min:0',
            'selection' => 'nullable|array',
            'selection.start' => 'required_with:selection|integer|min:0',
            'selection.end' => 'required_with:selection|integer|min:0',
            'is_typing' => 'nullable|boolean',
        ]);

        $presenceKey = "prd:{$prd->id}:presence";
        $presenceData = Cache::get($presenceKey, []);

        $presenceData[(string) $user->id] = [
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
            'cursor_position' => $validated['cursor_position'] ?? null,
            'selection' => $validated['selection'] ?? null,
            'is_typing' => $validated['is_typing'] ?? false,
            'updated_at' => now()->timestamp,
        ];

        // Clean up stale entries
        $now = now()->timestamp;
        $presenceData = array_filter($presenceData, function ($data) use ($now) {
            return ($now - $data['updated_at']) < self::PRESENCE_TTL * 2;
        });

        Cache::put($presenceKey, $presenceData, self::PRESENCE_TTL * 2);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Remove current user's presence (leaving).
     */
    public function leave(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);
        $userId = (string) $request->user()->id;

        $presenceKey = "prd:{$prd->id}:presence";
        $presenceData = Cache::get($presenceKey, []);

        unset($presenceData[$userId]);

        Cache::put($presenceKey, $presenceData, self::PRESENCE_TTL * 2);

        return response()->json(['status' => 'ok']);
    }

    /**
     * Find a PRD accessible by the user.
     */
    private function findAccessiblePrd(Request $request, string $id): Prd
    {
        $userId = $request->user()->id;

        $prd = Prd::where('id', $id)->first();

        if (!$prd) {
            abort(404);
        }

        // Owner has access
        if ((string) $prd->user_id === (string) $userId) {
            return $prd;
        }

        // Collaborators have access
        $isCollaborator = PrdCollaborator::where('prd_id', $id)
            ->where('user_id', $userId)
            ->exists();

        if ($isCollaborator) {
            return $prd;
        }

        abort(404);
    }
}

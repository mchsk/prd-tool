<?php

namespace App\Http\Controllers;

use App\Models\Prd;
use App\Models\PrdCollaborator;
use App\Models\PrdShareLink;
use App\Models\User;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShareController extends Controller
{
    public function __construct(
        private FileStorageService $fileStorage,
    ) {}

    // ============================================
    // COLLABORATORS
    // ============================================

    /**
     * List collaborators for a PRD.
     */
    public function listCollaborators(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findOwnedPrd($request, $prdId);

        $collaborators = PrdCollaborator::where('prd_id', $prd->id)
            ->with(['user', 'inviter'])
            ->get()
            ->map(fn (PrdCollaborator $c) => $c->toApiResponse());

        return response()->json(['data' => $collaborators]);
    }

    /**
     * Add a collaborator by email.
     */
    public function addCollaborator(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findOwnedPrd($request, $prdId);

        $validated = $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:viewer,editor',
        ]);

        // Find user by email
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found. They must sign up first.',
                'code' => 'USER_NOT_FOUND',
            ], 404);
        }

        // Can't add yourself
        if ((string) $user->id === (string) $request->user()->id) {
            return response()->json([
                'message' => 'Cannot add yourself as collaborator',
                'code' => 'SELF_INVITE',
            ], 400);
        }

        // Check if already a collaborator
        $existing = PrdCollaborator::where('prd_id', $prd->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            // Update role if different
            if ($existing->role !== $validated['role']) {
                $existing->update(['role' => $validated['role']]);
                return response()->json($existing->fresh()->load(['user', 'inviter'])->toApiResponse());
            }

            return response()->json([
                'message' => 'User is already a collaborator',
                'code' => 'ALREADY_COLLABORATOR',
            ], 400);
        }

        $collaborator = PrdCollaborator::create([
            'prd_id' => $prd->id,
            'user_id' => $user->id,
            'role' => $validated['role'],
            'invited_by' => $request->user()->id,
        ]);

        $collaborator->load(['user', 'inviter']);

        Log::info('Collaborator added', [
            'prd_id' => $prd->id,
            'user_id' => $user->id,
            'role' => $validated['role'],
        ]);

        return response()->json($collaborator->toApiResponse(), 201);
    }

    /**
     * Remove a collaborator.
     */
    public function removeCollaborator(Request $request, string $prdId, string $collaboratorId): JsonResponse
    {
        $prd = $this->findOwnedPrd($request, $prdId);

        $collaborator = PrdCollaborator::where('id', $collaboratorId)
            ->where('prd_id', $prd->id)
            ->first();

        if (!$collaborator) {
            return response()->json([
                'message' => 'Collaborator not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $collaborator->delete();

        Log::info('Collaborator removed', [
            'prd_id' => $prd->id,
            'collaborator_id' => $collaboratorId,
        ]);

        return response()->json(['message' => 'Collaborator removed']);
    }

    // ============================================
    // SHARE LINKS
    // ============================================

    /**
     * List share links for a PRD.
     */
    public function listShareLinks(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findOwnedPrd($request, $prdId);

        $links = PrdShareLink::where('prd_id', $prd->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (PrdShareLink $l) => $l->toApiResponse());

        return response()->json(['data' => $links]);
    }

    /**
     * Create a share link.
     */
    public function createShareLink(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findOwnedPrd($request, $prdId);

        $validated = $request->validate([
            'access_level' => 'sometimes|in:view,comment',
            'password' => 'nullable|string|min:4',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ]);

        $link = new PrdShareLink();
        $link->prd_id = $prd->id;
        $link->token = PrdShareLink::generateToken();
        $link->access_level = $validated['access_level'] ?? 'view';
        $link->created_by = $request->user()->id;
        
        if (!empty($validated['password'])) {
            $link->setPassword($validated['password']);
        }

        if (!empty($validated['expires_in_days'])) {
            $link->expires_at = now()->addDays($validated['expires_in_days']);
        }

        $link->save();

        Log::info('Share link created', [
            'prd_id' => $prd->id,
            'link_id' => $link->id,
        ]);

        return response()->json($link->toApiResponse(), 201);
    }

    /**
     * Revoke a share link.
     */
    public function revokeShareLink(Request $request, string $prdId, string $linkId): JsonResponse
    {
        $prd = $this->findOwnedPrd($request, $prdId);

        $link = PrdShareLink::where('id', $linkId)
            ->where('prd_id', $prd->id)
            ->first();

        if (!$link) {
            return response()->json([
                'message' => 'Share link not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        $link->update(['is_active' => false]);

        Log::info('Share link revoked', ['link_id' => $linkId]);

        return response()->json(['message' => 'Share link revoked']);
    }

    // ============================================
    // PUBLIC ACCESS
    // ============================================

    /**
     * Access a shared PRD via link.
     */
    public function accessSharedPrd(Request $request, string $token): JsonResponse
    {
        $link = PrdShareLink::where('token', $token)
            ->with('prd')
            ->first();

        if (!$link || !$link->isValid()) {
            return response()->json([
                'message' => 'Share link not found or expired',
                'code' => 'INVALID_LINK',
            ], 404);
        }

        // Check password if required
        if ($link->password_hash) {
            $password = $request->input('password');
            if (!$password || !$link->verifyPassword($password)) {
                return response()->json([
                    'message' => 'Password required',
                    'code' => 'PASSWORD_REQUIRED',
                    'requires_password' => true,
                ], 401);
            }
        }

        $prd = $link->prd;
        $content = $this->fileStorage->readPrd($prd->user_id, $prd->id);

        return response()->json([
            'prd' => [
                'id' => $prd->id,
                'title' => $prd->title,
                'updated_at' => $prd->updated_at?->toIso8601String(),
            ],
            'content' => $content,
            'access_level' => $link->access_level,
        ]);
    }

    /**
     * Find a PRD owned by the authenticated user.
     */
    private function findOwnedPrd(Request $request, string $id): Prd
    {
        $prd = Prd::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$prd) {
            abort(404);
        }

        return $prd;
    }
}

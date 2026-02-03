<?php

namespace App\Http\Controllers;

use App\Models\Prd;
use App\Models\PrdCollaborator;
use App\Models\PrdComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    /**
     * List comments for a PRD.
     */
    public function index(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);

        $query = PrdComment::where('prd_id', $prd->id)
            ->whereNull('parent_id') // Only top-level comments
            ->with('user')
            ->orderByDesc('created_at');

        // Filter by resolved status
        if ($request->has('resolved')) {
            $query->where('is_resolved', $request->boolean('resolved'));
        }

        $comments = $query->get()->map(fn (PrdComment $c) => $c->toApiResponse());

        return response()->json(['data' => $comments]);
    }

    /**
     * Get a comment with its replies.
     */
    public function show(Request $request, string $prdId, string $commentId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);

        $comment = PrdComment::where('id', $commentId)
            ->where('prd_id', $prd->id)
            ->with(['user', 'replies.user'])
            ->first();

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            ...$comment->toApiResponse(),
            'replies' => $comment->replies->map(fn ($r) => $r->toApiResponse()),
        ]);
    }

    /**
     * Create a new comment.
     */
    public function store(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'line_number' => 'nullable|integer|min:1',
            'anchor_text' => 'nullable|string|max:500',
            'parent_id' => 'nullable|uuid|exists:prd_comments,id',
            'author_name' => 'nullable|string|max:100', // For anonymous comments
        ]);

        // If replying, verify parent belongs to same PRD
        if (!empty($validated['parent_id'])) {
            $parent = PrdComment::find($validated['parent_id']);
            if (!$parent || $parent->prd_id !== $prd->id) {
                return response()->json([
                    'message' => 'Invalid parent comment',
                    'code' => 'INVALID_PARENT',
                ], 400);
            }
        }

        $comment = PrdComment::create([
            'prd_id' => $prd->id,
            'user_id' => $request->user()?->id,
            'author_name' => $request->user() ? null : ($validated['author_name'] ?? 'Anonymous'),
            'content' => $validated['content'],
            'line_number' => $validated['line_number'] ?? null,
            'anchor_text' => $validated['anchor_text'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        $comment->load('user');

        Log::info('Comment created', [
            'prd_id' => $prd->id,
            'comment_id' => $comment->id,
        ]);

        return response()->json($comment->toApiResponse(), 201);
    }

    /**
     * Update a comment.
     */
    public function update(Request $request, string $prdId, string $commentId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);

        $comment = PrdComment::where('id', $commentId)
            ->where('prd_id', $prd->id)
            ->first();

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        // Only author can edit content
        if ($request->has('content')) {
            if ((string) $comment->user_id !== (string) $request->user()?->id) {
                return response()->json([
                    'message' => 'You can only edit your own comments',
                    'code' => 'FORBIDDEN',
                ], 403);
            }
        }

        $validated = $request->validate([
            'content' => 'sometimes|required|string|max:5000',
            'is_resolved' => 'sometimes|boolean',
        ]);

        $comment->update($validated);

        return response()->json($comment->fresh()->load('user')->toApiResponse());
    }

    /**
     * Delete a comment.
     */
    public function destroy(Request $request, string $prdId, string $commentId): JsonResponse
    {
        $prd = $this->findAccessiblePrd($request, $prdId);

        $comment = PrdComment::where('id', $commentId)
            ->where('prd_id', $prd->id)
            ->first();

        if (!$comment) {
            return response()->json([
                'message' => 'Comment not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        // Only author or PRD owner can delete
        $canDelete = (string) $comment->user_id === (string) $request->user()?->id
            || (string) $prd->user_id === (string) $request->user()?->id;

        if (!$canDelete) {
            return response()->json([
                'message' => 'You cannot delete this comment',
                'code' => 'FORBIDDEN',
            ], 403);
        }

        $comment->delete();

        Log::info('Comment deleted', ['comment_id' => $commentId]);

        return response()->json(['message' => 'Comment deleted']);
    }

    /**
     * Find a PRD that the user can access (owner or collaborator).
     */
    private function findAccessiblePrd(Request $request, string $id): Prd
    {
        $userId = $request->user()?->id;

        // Check if owner
        $prd = Prd::where('id', $id)->first();

        if (!$prd) {
            abort(404);
        }

        // Owner always has access
        if ((string) $prd->user_id === (string) $userId) {
            return $prd;
        }

        // Check if collaborator
        if ($userId) {
            $isCollaborator = PrdCollaborator::where('prd_id', $id)
                ->where('user_id', $userId)
                ->exists();

            if ($isCollaborator) {
                return $prd;
            }
        }

        abort(404);
    }
}

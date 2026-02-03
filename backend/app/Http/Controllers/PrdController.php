<?php

namespace App\Http\Controllers;

use App\Models\Prd;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PrdController extends Controller
{
    public function __construct(
        private FileStorageService $fileStorage,
    ) {}

    /**
     * List all PRDs for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Prd::forUser($request->user()->id)
            ->orderByDesc('updated_at');

        // Filter by status if provided
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $prds = $query->get()->map(fn (Prd $prd) => $prd->toApiResponse());

        return response()->json(['data' => $prds]);
    }

    /**
     * Create a new PRD.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'template_id' => 'nullable|uuid',
        ]);

        DB::beginTransaction();
        try {
            $prd = new Prd();
            $prd->id = Str::uuid();
            $prd->user_id = $request->user()->id;
            $prd->title = $validated['title'] ?? 'Untitled PRD';
            $prd->file_path = "storage/prds/{$prd->user_id}/{$prd->id}.md";
            $prd->status = 'draft';

            // Get initial content (empty or from template)
            $initialContent = '';
            if (!empty($validated['template_id'])) {
                // Template support will be added in Phase 10
                $prd->created_from_template_id = $validated['template_id'];
            }

            // Create file first
            $this->fileStorage->createPrd(
                $prd->user_id,
                $prd->id,
                $initialContent
            );

            $prd->save();

            // Update user's last PRD
            $request->user()->update(['last_prd_id' => $prd->id]);

            DB::commit();

            Log::info('PRD created', [
                'prd_id' => $prd->id,
                'user_id' => $prd->user_id,
            ]);

            return response()->json($prd->toApiResponse(), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PRD creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get a specific PRD.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $prd = $this->findUserPrd($request, $id);

        // Update user's last PRD
        $request->user()->update(['last_prd_id' => $prd->id]);

        return response()->json($prd->toApiResponse());
    }

    /**
     * Update a PRD.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $prd = $this->findUserPrd($request, $id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:draft,active,archived',
        ]);

        $prd->update($validated);

        Log::info('PRD updated', [
            'prd_id' => $prd->id,
            'changes' => $validated,
        ]);

        return response()->json($prd->toApiResponse());
    }

    /**
     * Delete a PRD (soft delete).
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $prd = $this->findUserPrd($request, $id);

        $prd->delete();

        Log::info('PRD deleted', [
            'prd_id' => $prd->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json(['message' => 'PRD deleted']);
    }

    /**
     * Get PRD content.
     */
    public function getContent(Request $request, string $id): JsonResponse
    {
        $prd = $this->findUserPrd($request, $id);

        $content = $this->fileStorage->readPrd($prd->user_id, $prd->id);

        return response()->json([
            'content' => $content,
            'estimated_tokens' => $prd->estimated_tokens,
        ]);
    }

    /**
     * Update PRD content.
     */
    public function updateContent(Request $request, string $id): JsonResponse
    {
        $prd = $this->findUserPrd($request, $id);

        $validated = $request->validate([
            'content' => 'required|string|max:2097152', // 2MB limit
        ]);

        $this->fileStorage->writePrd($prd->user_id, $prd->id, $validated['content']);

        // Update estimated tokens (rough: ~4 chars per token)
        $prd->update([
            'estimated_tokens' => (int) ceil(strlen($validated['content']) / 4),
        ]);

        $prd->touch();

        return response()->json([
            'message' => 'Content updated',
            'estimated_tokens' => $prd->estimated_tokens,
            'updated_at' => $prd->updated_at->toIso8601String(),
        ]);
    }

    /**
     * Find a PRD belonging to the authenticated user.
     */
    private function findUserPrd(Request $request, string $id): Prd
    {
        $prd = Prd::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$prd) {
            abort(404); // 404 not 403 to prevent enumeration
        }

        return $prd;
    }
}

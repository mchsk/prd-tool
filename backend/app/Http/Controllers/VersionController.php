<?php

namespace App\Http\Controllers;

use App\Models\Prd;
use App\Models\PrdVersion;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VersionController extends Controller
{
    public function __construct(
        private FileStorageService $fileStorage,
    ) {}

    /**
     * List versions for a PRD.
     */
    public function index(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $versions = PrdVersion::where('prd_id', $prd->id)
            ->with('creator')
            ->orderByDesc('version_number')
            ->get()
            ->map(fn (PrdVersion $v) => $v->toApiResponse());

        return response()->json(['data' => $versions]);
    }

    /**
     * Get a specific version with content.
     */
    public function show(Request $request, string $prdId, string $versionId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $version = PrdVersion::where('id', $versionId)
            ->where('prd_id', $prd->id)
            ->with('creator')
            ->first();

        if (!$version) {
            return response()->json([
                'message' => 'Version not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            ...$version->toApiResponse(),
            'content' => $version->content,
        ]);
    }

    /**
     * Create a snapshot (manual version save).
     */
    public function store(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $validated = $request->validate([
            'summary' => 'nullable|string|max:255',
        ]);

        $content = $this->fileStorage->readPrd($prd->user_id, $prd->id);

        // Check if content changed since last version
        $lastVersion = PrdVersion::where('prd_id', $prd->id)
            ->orderByDesc('version_number')
            ->first();

        if ($lastVersion && md5($content) === $lastVersion->content_hash) {
            return response()->json([
                'message' => 'No changes to save',
                'code' => 'NO_CHANGES',
            ], 400);
        }

        $version = PrdVersion::createFromContent(
            $prd,
            $content,
            $request->user(),
            'manual',
            $validated['summary'] ?? null
        );

        Log::info('Manual version created', [
            'prd_id' => $prd->id,
            'version_number' => $version->version_number,
        ]);

        return response()->json($version->toApiResponse(), 201);
    }

    /**
     * Restore a previous version.
     */
    public function restore(Request $request, string $prdId, string $versionId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $version = PrdVersion::where('id', $versionId)
            ->where('prd_id', $prd->id)
            ->first();

        if (!$version) {
            return response()->json([
                'message' => 'Version not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        // Save current state as a new version before restoring
        $currentContent = $this->fileStorage->readPrd($prd->user_id, $prd->id);
        PrdVersion::createFromContent(
            $prd,
            $currentContent,
            $request->user(),
            'manual',
            'Auto-saved before restore to v' . $version->version_number
        );

        // Write restored content
        $this->fileStorage->writePrd($prd->user_id, $prd->id, $version->content);

        // Update PRD title if it changed
        if ($prd->title !== $version->title) {
            $prd->update(['title' => $version->title]);
        }

        // Create a new version for the restore
        $newVersion = PrdVersion::createFromContent(
            $prd->fresh(),
            $version->content,
            $request->user(),
            'manual',
            'Restored from v' . $version->version_number
        );

        Log::info('Version restored', [
            'prd_id' => $prd->id,
            'restored_from' => $version->version_number,
            'new_version' => $newVersion->version_number,
        ]);

        return response()->json([
            'message' => 'Version restored',
            'version' => $newVersion->toApiResponse(),
        ]);
    }

    /**
     * Compare two versions.
     */
    public function compare(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $validated = $request->validate([
            'from_version' => 'required|uuid',
            'to_version' => 'required|uuid',
        ]);

        $fromVersion = PrdVersion::where('id', $validated['from_version'])
            ->where('prd_id', $prd->id)
            ->first();

        $toVersion = PrdVersion::where('id', $validated['to_version'])
            ->where('prd_id', $prd->id)
            ->first();

        if (!$fromVersion || !$toVersion) {
            return response()->json([
                'message' => 'One or both versions not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            'from' => [
                'id' => $fromVersion->id,
                'version_number' => $fromVersion->version_number,
                'content' => $fromVersion->content,
                'created_at' => $fromVersion->created_at?->toIso8601String(),
            ],
            'to' => [
                'id' => $toVersion->id,
                'version_number' => $toVersion->version_number,
                'content' => $toVersion->content,
                'created_at' => $toVersion->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Find a PRD owned by the authenticated user.
     */
    private function findUserPrd(Request $request, string $id): Prd
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

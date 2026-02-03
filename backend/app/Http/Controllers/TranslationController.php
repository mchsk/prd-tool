<?php

namespace App\Http\Controllers;

use App\Models\Prd;
use App\Models\PrdCollaborator;
use App\Services\FileStorageService;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TranslationController extends Controller
{
    public function __construct(
        private TranslationService $translation,
        private FileStorageService $fileStorage
    ) {}

    /**
     * Get translation status and available languages.
     */
    public function status(): JsonResponse
    {
        return response()->json([
            'available' => $this->translation->isAvailable(),
            'languages' => $this->translation->getLanguages(),
        ]);
    }

    /**
     * Translate a PRD to target language.
     */
    public function translatePrd(Request $request, string $prdId): JsonResponse
    {
        // Check authorization first
        $prd = $this->findAccessiblePrd($request, $prdId);

        if (!$this->translation->isAvailable()) {
            return response()->json([
                'message' => 'Translation is not configured',
                'code' => 'TRANSLATION_NOT_CONFIGURED',
            ], 503);
        }

        $validated = $request->validate([
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2',
            'create_copy' => 'sometimes|boolean',
        ]);

        $content = $this->fileStorage->readPrd($prd->user_id, $prd->id);

        $translatedContent = $this->translation->translateMarkdown(
            $content,
            $validated['target_language'],
            $validated['source_language'] ?? null
        );

        if (!$translatedContent) {
            return response()->json([
                'message' => 'Translation failed',
                'code' => 'TRANSLATION_FAILED',
            ], 500);
        }

        $createCopy = $validated['create_copy'] ?? false;

        if ($createCopy) {
            // Create a new PRD with translated content
            $newPrdId = \Illuminate\Support\Str::uuid();
            $userId = $request->user()->id;

            $this->fileStorage->createPrd($userId, $newPrdId, $translatedContent);

            $langName = collect($this->translation->getLanguages())
                ->firstWhere('code', strtoupper($validated['target_language']))['name'] ?? $validated['target_language'];

            $newPrd = Prd::create([
                'id' => $newPrdId,
                'user_id' => $userId,
                'title' => "{$prd->title} ({$langName})",
                'file_path' => "prds/{$userId}/{$newPrdId}.md",
                'status' => 'draft',
            ]);

            Log::info('PRD translated as copy', [
                'source_prd' => $prd->id,
                'new_prd' => $newPrd->id,
                'target_lang' => $validated['target_language'],
            ]);

            return response()->json([
                'prd' => $newPrd->toApiResponse(),
                'message' => 'Translation created as new PRD',
            ], 201);
        } else {
            // Update the existing PRD
            $this->fileStorage->writePrd($prd->user_id, $prd->id, $translatedContent);

            Log::info('PRD translated in place', [
                'prd' => $prd->id,
                'target_lang' => $validated['target_language'],
            ]);

            return response()->json([
                'message' => 'PRD translated successfully',
                'content' => $translatedContent,
            ]);
        }
    }

    /**
     * Translate arbitrary text.
     */
    public function translateText(Request $request): JsonResponse
    {
        if (!$this->translation->isAvailable()) {
            return response()->json([
                'message' => 'Translation is not configured',
                'code' => 'TRANSLATION_NOT_CONFIGURED',
            ], 503);
        }

        $validated = $request->validate([
            'text' => 'required|string|max:10000',
            'target_language' => 'required|string|size:2',
            'source_language' => 'nullable|string|size:2',
        ]);

        $translated = $this->translation->translate(
            $validated['text'],
            $validated['target_language'],
            $validated['source_language'] ?? null
        );

        if (!$translated) {
            return response()->json([
                'message' => 'Translation failed',
                'code' => 'TRANSLATION_FAILED',
            ], 500);
        }

        return response()->json(['translated' => $translated]);
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

        // Collaborators with edit access
        $collaborator = PrdCollaborator::where('prd_id', $id)
            ->where('user_id', $userId)
            ->where('role', 'editor')
            ->first();

        if ($collaborator) {
            return $prd;
        }

        abort(404);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\DraftAttachment;
use App\Models\Prd;
use App\Services\FileExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function __construct(
        private FileExtractionService $fileExtraction,
    ) {}

    /**
     * List attachments for a PRD.
     */
    public function index(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $attachments = DraftAttachment::where('prd_id', $prd->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (DraftAttachment $a) => $a->toApiResponse());

        return response()->json(['data' => $attachments]);
    }

    /**
     * Upload a new attachment.
     */
    public function store(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $mimeType = $file->getMimeType();

        if (!$this->fileExtraction->isSupported($mimeType)) {
            return response()->json([
                'message' => 'Unsupported file type',
                'code' => 'UNSUPPORTED_FILE_TYPE',
            ], 400);
        }

        // Generate unique filename
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();

        // Store file
        $path = $file->storeAs("attachments/{$prd->user_id}/{$prd->id}", $filename);

        if (!$path) {
            return response()->json([
                'message' => 'Failed to store file',
                'code' => 'STORAGE_ERROR',
            ], 500);
        }

        // Create attachment record
        $attachment = DraftAttachment::create([
            'prd_id' => $prd->id,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $mimeType,
            'size_bytes' => $file->getSize(),
            'status' => 'processing',
        ]);

        // Extract text in same request (could be moved to queue for large files)
        try {
            $extractedText = $this->fileExtraction->extractText($file);
            
            $attachment->update([
                'extracted_text' => $extractedText,
                'status' => 'ready',
            ]);

            Log::info('Attachment processed', [
                'attachment_id' => $attachment->id,
                'text_length' => strlen($extractedText),
            ]);

        } catch (\Exception $e) {
            $attachment->update([
                'status' => 'failed',
                'error_message' => 'Failed to extract text: ' . $e->getMessage(),
            ]);

            Log::error('Attachment processing failed', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json($attachment->fresh()->toApiResponse(), 201);
    }

    /**
     * Get attachment details with extracted text.
     */
    public function show(Request $request, string $prdId, string $attachmentId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $attachment = DraftAttachment::where('id', $attachmentId)
            ->where('prd_id', $prd->id)
            ->first();

        if (!$attachment) {
            return response()->json([
                'message' => 'Attachment not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        return response()->json([
            ...$attachment->toApiResponse(),
            'extracted_text' => $attachment->extracted_text,
        ]);
    }

    /**
     * Delete an attachment.
     */
    public function destroy(Request $request, string $prdId, string $attachmentId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);

        $attachment = DraftAttachment::where('id', $attachmentId)
            ->where('prd_id', $prd->id)
            ->first();

        if (!$attachment) {
            return response()->json([
                'message' => 'Attachment not found',
                'code' => 'NOT_FOUND',
            ], 404);
        }

        // Delete file
        $filePath = "attachments/{$prd->user_id}/{$prd->id}/{$attachment->filename}";
        Storage::delete($filePath);

        // Delete record
        $attachment->delete();

        Log::info('Attachment deleted', ['attachment_id' => $attachmentId]);

        return response()->json(['message' => 'Attachment deleted']);
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
            abort(404);
        }

        return $prd;
    }
}

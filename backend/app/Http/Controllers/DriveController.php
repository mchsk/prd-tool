<?php

namespace App\Http\Controllers;

use App\Exceptions\DriveException;
use App\Models\DraftAttachment;
use App\Models\Prd;
use App\Services\FileExtractionService;
use App\Services\GoogleDriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DriveController extends Controller
{
    public function __construct(
        private GoogleDriveService $driveService,
        private FileExtractionService $fileExtraction,
    ) {}

    /**
     * Check if Drive integration is available.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'available' => $this->driveService->isAvailable(),
            'connected' => !empty($user->google_access_token),
            'picker_api_key' => config('services.google.picker_api_key') ? true : false,
        ]);
    }

    /**
     * Get Picker token for frontend.
     */
    public function pickerToken(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$this->driveService->isAvailable()) {
            return response()->json([
                'message' => 'Google Drive integration is not configured.',
                'code' => 'DRIVE_NOT_CONFIGURED',
            ], 503);
        }

        if (!$user->google_access_token) {
            return response()->json([
                'message' => 'Please connect your Google account first.',
                'code' => 'NOT_CONNECTED',
            ], 400);
        }

        try {
            $tokens = $this->driveService->getPickerToken($user);

            return response()->json($tokens);
        } catch (DriveException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'DRIVE_ERROR',
            ], 400);
        }
    }

    /**
     * Import a file from Google Drive as attachment.
     */
    public function import(Request $request, string $prdId): JsonResponse
    {
        $prd = $this->findUserPrd($request, $prdId);
        $user = $request->user();

        $validated = $request->validate([
            'file_id' => 'required|string',
        ]);

        if (!$this->driveService->isAvailable()) {
            return response()->json([
                'message' => 'Google Drive integration is not configured.',
                'code' => 'DRIVE_NOT_CONFIGURED',
            ], 503);
        }

        try {
            // Download file from Drive
            $downloadedFile = $this->driveService->downloadFile($user, $validated['file_id']);

            // Check if file type is supported
            if (!$this->fileExtraction->isSupported($downloadedFile->mimeType)) {
                return response()->json([
                    'message' => 'This file type is not supported for text extraction.',
                    'code' => 'UNSUPPORTED_FILE_TYPE',
                ], 400);
            }

            // Extract text
            $extractedText = $this->fileExtraction->extractText(
                $downloadedFile->content,
                $downloadedFile->mimeType
            );

            // Save attachment
            $filename = Str::uuid() . '.' . pathinfo($downloadedFile->filename, PATHINFO_EXTENSION);
            $attachment = DraftAttachment::create([
                'prd_id' => $prd->id,
                'filename' => $filename,
                'original_filename' => $downloadedFile->filename,
                'mime_type' => $downloadedFile->mimeType,
                'size_bytes' => $downloadedFile->size,
                'extracted_text' => $extractedText,
                'status' => 'ready',
            ]);

            Log::info('File imported from Drive', [
                'prd_id' => $prd->id,
                'file_id' => $validated['file_id'],
                'filename' => $downloadedFile->filename,
            ]);

            return response()->json($attachment->toApiResponse(), 201);
        } catch (DriveException $e) {
            Log::error('Drive import failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'DRIVE_ERROR',
            ], 400);
        }
    }

    /**
     * List files in user's Drive.
     */
    public function listFiles(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = $request->input('q', '');

        if (!$this->driveService->isAvailable()) {
            return response()->json([
                'message' => 'Google Drive integration is not configured.',
                'code' => 'DRIVE_NOT_CONFIGURED',
            ], 503);
        }

        try {
            $files = $this->driveService->listFiles($user, $query);

            return response()->json(['data' => $files]);
        } catch (DriveException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'code' => 'DRIVE_ERROR',
            ], 400);
        }
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

<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\PrdController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| PRD Tool API - All routes are prefixed with /api
|
*/

// ============================================
// HEALTH CHECK (No Auth)
// ============================================
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// ============================================
// AUTHENTICATED ROUTES
// ============================================
Route::middleware(['auth:sanctum'])->group(function () {
    // ----- User -----
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ----- PRDs -----
    Route::get('/prds', [PrdController::class, 'index']);
    Route::post('/prds', [PrdController::class, 'store']);
    Route::get('/prds/{id}', [PrdController::class, 'show']);
    Route::put('/prds/{id}', [PrdController::class, 'update']);
    Route::delete('/prds/{id}', [PrdController::class, 'destroy']);
    Route::get('/prds/{id}/content', [PrdController::class, 'getContent']);
    Route::put('/prds/{id}/content', [PrdController::class, 'updateContent']);

    // ----- Chat -----
    Route::get('/prds/{prdId}/messages', [ChatController::class, 'index']);
    Route::post('/prds/{prdId}/messages', [ChatController::class, 'store']);
    Route::post('/prds/{prdId}/messages/{messageId}/apply', [ChatController::class, 'applyUpdate']);

    // ----- Attachments -----
    Route::get('/prds/{prdId}/attachments', [AttachmentController::class, 'index']);
    Route::post('/prds/{prdId}/attachments', [AttachmentController::class, 'store']);
    Route::get('/prds/{prdId}/attachments/{attachmentId}', [AttachmentController::class, 'show']);
    Route::delete('/prds/{prdId}/attachments/{attachmentId}', [AttachmentController::class, 'destroy']);
});

<?php

use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DriveController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PrdController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\RuleController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\VersionController;
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

    // ----- Sharing: Collaborators -----
    Route::get('/prds/{prdId}/collaborators', [ShareController::class, 'listCollaborators']);
    Route::post('/prds/{prdId}/collaborators', [ShareController::class, 'addCollaborator']);
    Route::delete('/prds/{prdId}/collaborators/{collaboratorId}', [ShareController::class, 'removeCollaborator']);

    // ----- Sharing: Share Links -----
    Route::get('/prds/{prdId}/share-links', [ShareController::class, 'listShareLinks']);
    Route::post('/prds/{prdId}/share-links', [ShareController::class, 'createShareLink']);
    Route::delete('/prds/{prdId}/share-links/{linkId}', [ShareController::class, 'revokeShareLink']);

    // ----- Comments -----
    Route::get('/prds/{prdId}/comments', [CommentController::class, 'index']);
    Route::get('/prds/{prdId}/comments/{commentId}', [CommentController::class, 'show']);
    Route::post('/prds/{prdId}/comments', [CommentController::class, 'store']);
    Route::put('/prds/{prdId}/comments/{commentId}', [CommentController::class, 'update']);
    Route::delete('/prds/{prdId}/comments/{commentId}', [CommentController::class, 'destroy']);

    // ----- Rules -----
    Route::get('/rules', [RuleController::class, 'index']);
    Route::post('/rules', [RuleController::class, 'store']);
    Route::get('/rules/{id}', [RuleController::class, 'show']);
    Route::put('/rules/{id}', [RuleController::class, 'update']);
    Route::delete('/rules/{id}', [RuleController::class, 'destroy']);

    // ----- PRD Rule Assignments -----
    Route::get('/prds/{prdId}/rules', [RuleController::class, 'assigned']);
    Route::post('/prds/{prdId}/rules', [RuleController::class, 'assign']);

    // ----- Export -----
    Route::get('/prds/{prdId}/export/formats', [ExportController::class, 'formats']);
    Route::get('/prds/{prdId}/export/markdown', [ExportController::class, 'markdown']);
    Route::get('/prds/{prdId}/export/html', [ExportController::class, 'html']);
    Route::get('/prds/{prdId}/export/pdf', [ExportController::class, 'pdf']);

    // ----- Google Drive -----
    Route::get('/drive/status', [DriveController::class, 'status']);
    Route::get('/drive/picker-token', [DriveController::class, 'pickerToken']);
    Route::get('/drive/files', [DriveController::class, 'listFiles']);
    Route::post('/prds/{prdId}/drive/import', [DriveController::class, 'import']);

    // ----- Templates -----
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::get('/templates/categories', [TemplateController::class, 'categories']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::get('/templates/{id}', [TemplateController::class, 'show']);
    Route::put('/templates/{id}', [TemplateController::class, 'update']);
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy']);
    Route::post('/templates/{id}/create-prd', [TemplateController::class, 'createPrd']);

    // ----- Version History -----
    Route::get('/prds/{prdId}/versions', [VersionController::class, 'index']);
    Route::post('/prds/{prdId}/versions', [VersionController::class, 'store']);
    Route::get('/prds/{prdId}/versions/{versionId}', [VersionController::class, 'show']);
    Route::post('/prds/{prdId}/versions/{versionId}/restore', [VersionController::class, 'restore']);
    Route::post('/prds/{prdId}/versions/compare', [VersionController::class, 'compare']);

    // ----- Collaboration: Presence -----
    Route::get('/prds/{prdId}/presence', [PresenceController::class, 'index']);
    Route::post('/prds/{prdId}/presence', [PresenceController::class, 'update']);
    Route::delete('/prds/{prdId}/presence', [PresenceController::class, 'leave']);

    // ----- Teams -----
    Route::get('/teams', [TeamController::class, 'index']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::get('/teams/{id}', [TeamController::class, 'show']);
    Route::put('/teams/{id}', [TeamController::class, 'update']);
    Route::delete('/teams/{id}', [TeamController::class, 'destroy']);
    Route::get('/teams/{id}/members', [TeamController::class, 'members']);
    Route::post('/teams/{id}/members', [TeamController::class, 'addMember']);
    Route::put('/teams/{id}/members/{memberId}', [TeamController::class, 'updateMember']);
    Route::delete('/teams/{id}/members/{memberId}', [TeamController::class, 'removeMember']);

    // ----- Billing -----
    Route::get('/billing/status', [BillingController::class, 'status']);
    Route::get('/billing/plans', [BillingController::class, 'plans']);
    Route::post('/billing/checkout', [BillingController::class, 'createCheckout']);
    Route::post('/billing/portal', [BillingController::class, 'portal']);
});

// ============================================
// WEBHOOKS (No Auth)
// ============================================
Route::post('/webhooks/stripe', [BillingController::class, 'webhook']);

// ============================================
// PUBLIC SHARE ACCESS (No Auth)
// ============================================
Route::post('/share/{token}', [ShareController::class, 'accessSharedPrd']);

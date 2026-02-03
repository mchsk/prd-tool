<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| OAuth callbacks must be web routes for session handling.
|
*/

// OAuth routes
Route::get('/auth/google', [AuthController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

// Catch-all for SPA (when deployed together)
Route::get('/{any}', function () {
    // In production, this would serve the React app
    return response()->json([
        'message' => 'PRD Tool API',
        'version' => config('app.version'),
    ]);
})->where('any', '^(?!api|auth).*$');

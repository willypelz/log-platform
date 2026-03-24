<?php

use Willypelz\LogPlatform\Http\Controllers\LogPlatformController;
use Willypelz\LogPlatform\Http\Controllers\Api\FilesController;
use Willypelz\LogPlatform\Http\Controllers\Api\StreamController;
use Illuminate\Support\Facades\Route;

// UI Route (Web)
Route::get('/log-platform', [LogPlatformController::class, 'index'])
    ->name('log-platform.index')
    ->middleware(config('log-platform.security.ui_middleware', ['web']));

// Apply middleware from config
$middleware = config('log-platform.security.middleware', ['api']);

Route::prefix('log-platform/api')
    ->middleware($middleware)
    ->group(function () {
        // File listing & management
        Route::get('/files',             [FilesController::class, 'index']);
        Route::get('/files/{filename}',  [FilesController::class, 'show']);
        Route::post('/files/download',   [FilesController::class, 'download']);
        Route::delete('/files/delete',   [FilesController::class, 'delete']);

        // Log content endpoints
        Route::get('/logs',               [FilesController::class, 'logs']);      // parsed + filtered entries
        Route::get('/contents',           [FilesController::class, 'contents']);  // raw paginated lines

        // Real-time tail (file-based SSE)
        Route::get('/stream',            [StreamController::class, 'stream']);

        // Hosts (returns configured log paths, no DB)
        Route::get('/hosts', function () {
            $hostManager = app(\Willypelz\LogPlatform\Services\HostManager::class);
            return response()->json($hostManager->all());
        });
    });


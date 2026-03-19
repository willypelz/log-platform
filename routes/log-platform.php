<?php

use Willypelz\LogPlatform\Http\Controllers\Api\LogsController;
use Willypelz\LogPlatform\Http\Controllers\Api\StreamController;
use Willypelz\LogPlatform\Http\Controllers\Api\MetricsController;
use Willypelz\LogPlatform\Http\Controllers\Api\AlertsController;
use Willypelz\LogPlatform\Http\Controllers\Api\FilesController;
use Illuminate\Support\Facades\Route;

// Apply middleware from config
$middleware = config('log-platform.security.middleware', ['web', 'auth']);

Route::prefix('log-platform/api')
    ->middleware($middleware)
    ->group(function () {
        // Logs
        Route::get('/logs', [LogsController::class, 'index']);
        Route::get('/logs/{id}', [LogsController::class, 'show']);
        Route::post('/logs/{id}/share', [LogsController::class, 'shareLink']);
        Route::get('/requests/{requestId}/logs', [LogsController::class, 'byRequestId']);
        Route::get('/fingerprints/{fingerprint}/logs', [LogsController::class, 'byFingerprint']);

        // Real-time streaming
        Route::get('/logs/stream', [StreamController::class, 'stream']);

        Route::get('/files', [FilesController::class, 'index']);
        Route::get('/files/{filename}', [FilesController::class, 'show']);
        Route::post('/files/download', [FilesController::class, 'download']);
        Route::delete('/files/delete', [FilesController::class, 'delete']);

        // Metrics
        Route::get('/metrics/overview', [MetricsController::class, 'overview']);
        Route::get('/metrics/timeseries', [MetricsController::class, 'timeseries']);

        // Alerts
        Route::get('/alerts/rules', [AlertsController::class, 'index']);
        Route::post('/alerts/rules', [AlertsController::class, 'store']);
        Route::patch('/alerts/rules/{rule}', [AlertsController::class, 'update']);
        Route::delete('/alerts/rules/{rule}', [AlertsController::class, 'destroy']);

        // Hosts (multi-host support)
        Route::get('/hosts', function () {
            $hostManager = app(\Willypelz\LogPlatform\Services\HostManager::class);
            return response()->json($hostManager->all());
        });
    });


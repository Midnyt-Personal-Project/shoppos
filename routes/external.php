<?php

// routes/external.php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\SyncController;

// External API endpoints for peer-to-peer sync
Route::post('/sync/receive', [SyncController::class, 'receive']);
Route::get('/sync/export', [SyncController::class, 'export']);

// Health check endpoint
Route::get('/health', fn () => response()->json(['status' => 'ok']));
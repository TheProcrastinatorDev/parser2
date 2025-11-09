<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ParserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);

// Parser API endpoints
Route::prefix('parsers')->group(function () {
    Route::get('/', [ParserController::class, 'index']);
    Route::get('/{name}', [ParserController::class, 'show']);
    Route::post('/parse', [ParserController::class, 'parse']);
    Route::post('/batch', [ParserController::class, 'batch']);
});

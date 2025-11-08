<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\ParserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/parsers', [ParserController::class, 'index']);
    Route::get('/parsers/{parser}', [ParserController::class, 'show']);
    Route::post('/parsers/parse', [ParserController::class, 'parse']);
    Route::get('/parsers/{parser}/types', [ParserController::class, 'types']);
});
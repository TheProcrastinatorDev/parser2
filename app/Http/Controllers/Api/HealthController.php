<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class HealthController extends Controller
{
    /**
     * Health check endpoint
     *
     * @OA\Get(
     *     path="/api/health",
     *     operationId="healthCheck",
     *     tags={"System"},
     *     summary="Check API health status",
     *     description="Returns the current health status of the API",
     *
     *     @OA\Response(
     *         response=200,
     *         description="API is healthy",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(property="timestamp", type="string", format="date-time", example="2025-01-01T12:00:00Z"),
     *             @OA\Property(property="version", type="string", example="1.0.0")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }
}

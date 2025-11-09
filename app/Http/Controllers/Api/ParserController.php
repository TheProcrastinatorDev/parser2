<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DTOs\Parser\ParseRequestDTO;
use App\Exceptions\ParserNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\BatchParseRequest;
use App\Http\Requests\ParseRequest;
use App\Http\Resources\ParserCollection;
use App\Http\Resources\ParserDetailsResource;
use App\Http\Resources\ParseResultResource;
use App\Http\Resources\ParserListResource;
use App\Services\Parser\ParserManager;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Http\JsonResponse;

class ParserController extends Controller
{
    public function __construct(
        private readonly ParserManager $parserManager,
        private readonly RateLimiter $rateLimiter,
    ) {}

    /**
     * Execute a parse request.
     *
     * @OA\Post(
     *     path="/parsers/parse",
     *     summary="Execute a parse request",
     *     description="Parse content from a source using the specified parser type",
     *     tags={"Parsers"},
     *
     * @OA\RequestBody(
     *         required=true,
     *
     * @OA\JsonContent(
     *             required={"source", "type"},
     *
     * @OA\Property(property="source", type="string", example="https://reddit.com/r/programming.json", description="Source URL or identifier to parse"),
     * @OA\Property(property="type", type="string", enum={"feeds", "reddit", "single_page", "telegram", "medium", "bing", "multi", "craigslist"}, example="reddit", description="Parser type to use"),
     * @OA\Property(property="keywords", type="array", @OA\Items(type="string"), example={"php", "laravel"}, description="Optional keywords for search-based parsers"),
     * @OA\Property(property="options", type="object", description="Parser-specific options"),
     * @OA\Property(property="limit", type="integer", example=10, description="Maximum number of items to return"),
     * @OA\Property(property="offset", type="integer", example=0, description="Offset for pagination"),
     * @OA\Property(property="filters", type="object", description="Additional filters")
     *         )
     *     ),
     *
     * @OA\Response(
     *         response=200,
     *         description="Parse result",
     *
     * @OA\JsonContent(
     *
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="items", type="array", @OA\Items(type="object")),
     * @OA\Property(property="metadata", type="object"),
     * @OA\Property(property="total", type="integer"),
     * @OA\Property(property="next_offset", type="integer")
     *             )
     *         )
     *     ),
     *
     * @OA\Response(response=422, description="Validation error"),
     * @OA\Response(response=429, description="Rate limit exceeded")
     * )
     */
    public function parse(ParseRequest $request): ParseResultResource|JsonResponse
    {
        $parserType = $request->validated('type');

        // Get rate limits from config
        $perMinute = config("parser.parsers.{$parserType}.rate_limit_per_minute", config('parser.rate_limit_per_minute', 60));
        $perHour = config("parser.parsers.{$parserType}.rate_limit_per_hour", config('parser.rate_limit_per_hour', 1000));

        // Check rate limit
        if (! $this->rateLimiter->attempt($parserType, $perMinute, $perHour)) {
            return response()->json([
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => $this->rateLimiter->availableIn($parserType),
            ], 429);
        }

        // Create parse request DTO
        $parseRequest = ParseRequestDTO::fromArray($request->validated());

        // Get parser and execute
        $parser = $this->parserManager->get($parserType);
        $result = $parser->parse($parseRequest);

        return new ParseResultResource($result);
    }

    /**
     * List all available parsers.
     *
     * @OA\Get(
     *     path="/parsers",
     *     summary="List available parsers",
     *     description="Get a list of all available parser types and their descriptions",
     *     tags={"Parsers"},
     *
     * @OA\Response(
     *         response=200,
     *         description="List of available parsers",
     *
     * @OA\JsonContent(
     *
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="array",
     *
     * @OA\Items(
     *
     * @OA\Property(property="name", type="string", example="reddit"),
     * @OA\Property(property="description", type="string", example="Parser for reddit sources")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): ParserCollection
    {
        $parsers = [];
        foreach ($this->parserManager->names() as $name) {
            $parsers[] = $this->parserManager->getDetails($name);
        }

        return new ParserCollection(
            ParserListResource::collection(collect($parsers))
        );
    }

    /**
     * Get parser details by name.
     *
     * @OA\Get(
     *     path="/parsers/{name}",
     *     summary="Get parser details",
     *     description="Get detailed information about a specific parser",
     *     tags={"Parsers"},
     *
     * @OA\Parameter(
     *         name="name",
     *         in="path",
     *         required=true,
     *         description="Parser name",
     *
     * @OA\Schema(type="string", enum={"feeds", "reddit", "single_page", "telegram", "medium", "bing", "multi", "craigslist"})
     *     ),
     *
     * @OA\Response(
     *         response=200,
     *         description="Parser details",
     *
     * @OA\JsonContent(
     *
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="name", type="string", example="reddit"),
     * @OA\Property(property="description", type="string", example="Parser for reddit sources"),
     * @OA\Property(property="config", type="object")
     *             )
     *         )
     *     ),
     *
     * @OA\Response(response=404, description="Parser not found")
     * )
     */
    public function show(string $name): ParserDetailsResource|JsonResponse
    {
        try {
            $details = $this->parserManager->getDetails($name);

            return new ParserDetailsResource($details);
        } catch (ParserNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error' => "Parser '{$name}' not found",
            ], 404);
        }
    }

    /**
     * Execute batch parse requests.
     *
     * @OA\Post(
     *     path="/parsers/batch",
     *     summary="Execute batch parse requests",
     *     description="Parse multiple sources in a single batch request (max 100)",
     *     tags={"Parsers"},
     *
     * @OA\RequestBody(
     *         required=true,
     *
     * @OA\JsonContent(
     *             required={"requests"},
     *
     * @OA\Property(property="requests", type="array",
     *
     * @OA\Items(
     *
     * @OA\Property(property="source", type="string", example="https://example.com"),
     * @OA\Property(property="type", type="string", example="single_page"),
     * @OA\Property(property="keywords", type="array", @OA\Items(type="string")),
     * @OA\Property(property="options", type="object"),
     * @OA\Property(property="limit", type="integer"),
     * @OA\Property(property="offset", type="integer"),
     * @OA\Property(property="filters", type="object")
     *                 )
     *             )
     *         )
     *     ),
     *
     * @OA\Response(
     *         response=200,
     *         description="Batch parse results",
     *
     * @OA\JsonContent(
     *
     * @OA\Property(property="success", type="boolean", example=true),
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="results", type="array", @OA\Items(type="object")),
     * @OA\Property(property="summary", type="object",
     * @OA\Property(property="total", type="integer", example=10),
     * @OA\Property(property="successful", type="integer", example=8),
     * @OA\Property(property="failed", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *
     * @OA\Response(response=422, description="Validation error")
     * )
     */
    public function batch(BatchParseRequest $request): JsonResponse
    {
        $requests = $request->validated('requests');
        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($requests as $requestData) {
            $parserType = $requestData['type'];

            // Get rate limits from config
            $perMinute = config("parser.parsers.{$parserType}.rate_limit_per_minute", config('parser.rate_limit_per_minute', 60));
            $perHour = config("parser.parsers.{$parserType}.rate_limit_per_hour", config('parser.rate_limit_per_hour', 1000));

            // Check rate limit for each request
            if (! $this->rateLimiter->attempt($parserType, $perMinute, $perHour)) {
                $results[] = [
                    'success' => false,
                    'error' => 'Rate limit exceeded for this parser',
                    'data' => null,
                ];
                $failed++;

                continue;
            }

            // Create parse request DTO
            $parseRequest = ParseRequestDTO::fromArray($requestData);

            // Get parser and execute
            try {
                $parser = $this->parserManager->get($parserType);
                $result = $parser->parse($parseRequest);

                $results[] = [
                    'success' => $result->success,
                    'data' => $result->success ? [
                        'items' => $result->items,
                        'metadata' => $result->metadata,
                        'total' => $result->total,
                        'next_offset' => $result->nextOffset,
                    ] : null,
                    'error' => $result->error,
                ];

                if ($result->success) {
                    $successful++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $results[] = [
                    'success' => false,
                    'data' => null,
                    'error' => $e->getMessage(),
                ];
                $failed++;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'summary' => [
                    'total' => count($requests),
                    'successful' => $successful,
                    'failed' => $failed,
                ],
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Parser\ParseRequestDTO;
use App\Http\Controllers\Controller;
use App\Services\Parser\ParserManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Parsers",
 *     description="Parser management and execution endpoints"
 * )
 */
class ParserController extends Controller
{
    public function __construct(
        private readonly ParserManager $parserManager,
    ) {}

    /**
     * @OA\Get(
     *     path="/api/parsers",
     *     summary="List all available parsers",
     *     tags={"Parsers"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of available parsers",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="parsers",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="feeds"),
     *                     @OA\Property(property="supported_types", type="array", @OA\Items(type="string"))
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $parsers = [];
        foreach ($this->parserManager->list() as $name => $parser) {
            $parsers[] = [
                'name' => $name,
                'supported_types' => $parser->getSupportedTypes(),
            ];
        }

        return response()->json(['parsers' => $parsers]);
    }

    /**
     * @OA\Get(
     *     path="/api/parsers/{parser}",
     *     summary="Get parser details",
     *     tags={"Parsers"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="parser",
     *         in="path",
     *         required=true,
     *         description="Parser name",
     *         @OA\Schema(type="string", example="feeds")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Parser details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="supported_types", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="config", type="object")
     *         )
     *     ),
     *     @OA\Response(response=404, description="Parser not found")
     * )
     */
    public function show(string $parser): JsonResponse
    {
        try {
            $parserInstance = $this->parserManager->get($parser);

            return response()->json([
                'name' => $parserInstance->getName(),
                'supported_types' => $parserInstance->getSupportedTypes(),
                'config' => $parserInstance->getConfig(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/parsers/parse",
     *     summary="Execute parsing operation",
     *     tags={"Parsers"},
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"parser", "source", "type"},
     *             @OA\Property(property="parser", type="string", example="feeds"),
     *             @OA\Property(property="source", type="string", example="https://example.com/feed.xml"),
     *             @OA\Property(property="type", type="string", example="rss"),
     *             @OA\Property(property="keywords", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="options", type="object"),
     *             @OA\Property(property="limit", type="integer"),
     *             @OA\Property(property="offset", type="integer"),
     *             @OA\Property(property="filters", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Parse result",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="metadata", type="object"),
     *             @OA\Property(property="error", type="string", nullable=true),
     *             @OA\Property(property="total", type="integer", nullable=true),
     *             @OA\Property(property="nextOffset", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=404, description="Parser not found")
     * )
     */
    public function parse(Request $request): JsonResponse
    {
        $request->validate([
            'parser' => 'required|string',
            'source' => 'required|url',
            'type' => 'required|string',
            'keywords' => 'sometimes|array',
            'options' => 'sometimes|array',
            'limit' => 'sometimes|integer|min:1',
            'offset' => 'sometimes|integer|min:0',
            'filters' => 'sometimes|array',
        ]);

        try {
            $parser = $this->parserManager->get($request->input('parser'));
            $parseRequest = ParseRequestDTO::fromArray($request->all());
            $result = $parser->parse($parseRequest);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'items' => [],
                'metadata' => [],
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/parsers/{parser}/types",
     *     summary="Get supported types for a parser",
     *     tags={"Parsers"},
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="parser",
     *         in="path",
     *         required=true,
     *         description="Parser name",
     *         @OA\Schema(type="string", example="feeds")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Supported types",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="types",
     *                 type="array",
     *                 @OA\Items(type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=404, description="Parser not found")
     * )
     */
    public function types(string $parser): JsonResponse
    {
        try {
            $parserInstance = $this->parserManager->get($parser);

            return response()->json([
                'types' => $parserInstance->getSupportedTypes(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}

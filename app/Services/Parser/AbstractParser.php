<?php

namespace App\Services\Parser;

use App\DTOs\Parser\ParseRequestDTO;
use App\DTOs\Parser\ParseResultDTO;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\RateLimiter;
use Exception;

abstract class AbstractParser
{
    protected string $name;
    protected array $supportedTypes = [];

    public function __construct(
        protected HttpClient $httpClient,
        protected ContentExtractor $contentExtractor,
        protected RateLimiter $rateLimiter,
    ) {}

    public function parse(ParseRequestDTO $request): ParseResultDTO
    {
        try {
            // Validate request
            $this->validateRequest($request);

            // Check rate limit
            if (!$this->rateLimiter->check()) {
                return new ParseResultDTO(
                    success: false,
                    items: [],
                    metadata: [
                        'parser' => $this->name,
                        'type' => $request->type,
                        'source' => $request->source,
                    ],
                    error: 'Rate limit exceeded',
                );
            }

            // Parse items
            $items = $this->parseInternal($request);

            // Apply pagination
            if ($request->offset !== null) {
                $items = array_slice($items, $request->offset);
            }
            if ($request->limit !== null) {
                $items = array_slice($items, 0, $request->limit);
            }

            // Process items through pipeline
            $processedItems = $this->processItems($items, $request);

            return new ParseResultDTO(
                success: true,
                items: $processedItems,
                metadata: [
                    'parser' => $this->name,
                    'type' => $request->type,
                    'source' => $request->source,
                    'execution_time' => 0, // TODO: Add timing
                ],
                total: count($items),
                nextOffset: $request->offset !== null && $request->limit !== null
                    ? $request->offset + $request->limit
                    : null,
            );
        } catch (Exception $e) {
            return new ParseResultDTO(
                success: false,
                items: [],
                metadata: [
                    'parser' => $this->name,
                    'type' => $request->type,
                    'source' => $request->source,
                ],
                error: $e->getMessage(),
            );
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSupportedTypes(): array
    {
        return $this->supportedTypes;
    }

    public function getConfig(): array
    {
        return config("parser.{$this->name}", []);
    }

    protected function validateRequest(ParseRequestDTO $request): void
    {
        if (!in_array($request->type, $this->supportedTypes)) {
            throw new Exception("Unsupported type: {$request->type}");
        }
    }

    protected function processItems(array $items, ParseRequestDTO $request): array
    {
        // Override in subclasses for custom processing
        return $items;
    }

    abstract protected function parseInternal(ParseRequestDTO $request): array;
}

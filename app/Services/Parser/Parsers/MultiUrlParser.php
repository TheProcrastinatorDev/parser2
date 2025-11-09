<?php

declare(strict_types=1);

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\DTOs\Parser\ParseResultDTO;
use App\Services\Parser\AbstractParser;

class MultiUrlParser extends AbstractParser
{
    public function __construct(
        private readonly SinglePageParser $singlePageParser,
    ) {}

    /**
     * Parse multiple URLs and aggregate results.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function doParse(ParseRequestDTO $request): array
    {
        // Parse URLs from source
        $urls = $this->parseUrls($request->source);

        if (empty($urls)) {
            return [];
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($urls as $url) {
            // Skip invalid URLs
            if (! $this->isValidUrl($url)) {
                continue;
            }

            // Create parse request for single URL
            $singleRequest = new ParseRequestDTO(
                source: $url,
                type: 'single_page',
                keywords: $request->keywords,
                options: $request->options,
                limit: $request->limit,
                offset: $request->offset,
                filters: $request->filters,
            );

            // Parse the URL
            $parseResult = $this->singlePageParser->parse($singleRequest);

            // Extract first item from result (single page returns array with one item)
            $item = $parseResult->items[0] ?? [];

            // Add metadata
            $item['source_url'] = $url;
            $item['parse_success'] = $parseResult->success;

            if (! $parseResult->success) {
                $item['parse_error'] = $parseResult->error;
                $failureCount++;
            } else {
                $successCount++;
            }

            $results[] = $item;
        }

        // Store metadata for parent class
        $this->parseMetadata = [
            'total_urls' => count($results),
            'successful' => $successCount,
            'failed' => $failureCount,
        ];

        return $results;
    }

    /**
     * Parse URLs from various formats.
     *
     * @return array<int, string>
     */
    private function parseUrls(string $source): array
    {
        // Try JSON array first
        $decoded = json_decode($source, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try comma-separated
        if (str_contains($source, ',')) {
            return array_map('trim', explode(',', $source));
        }

        // Try newline-separated
        if (str_contains($source, "\n")) {
            $urls = array_map('trim', explode("\n", $source));

            return array_filter($urls, fn ($url) => ! empty($url));
        }

        // Single URL or invalid format
        return [];
    }

    /**
     * Validate URL format.
     */
    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Override parse to handle invalid source format.
     */
    public function parse(ParseRequestDTO $request): ParseResultDTO
    {
        // Check if source can be parsed
        $urls = $this->parseUrls($request->source);

        if ($urls === [] && ! empty($request->source)) {
            // Source provided but couldn't be parsed
            $decoded = json_decode($request->source, true);
            if (json_last_error() !== JSON_ERROR_NONE && json_last_error() !== 0) {
                return new ParseResultDTO(
                    success: false,
                    items: [],
                    error: 'Invalid source format: Expected JSON array, comma-separated, or newline-separated URLs',
                );
            }
        }

        // Call parent parse method
        return parent::parse($request);
    }

    /**
     * Temporary storage for metadata to pass to parent.
     *
     * @var array<string, mixed>
     */
    private array $parseMetadata = [];

    /**
     * Override buildMetadata to include custom metadata.
     */
    protected function buildMetadata(ParseRequestDTO $request): array
    {
        return array_merge(
            parent::buildMetadata($request),
            $this->parseMetadata
        );
    }

    /**
     * Get parser name.
     */
    protected function getName(): string
    {
        return 'multi';
    }
}

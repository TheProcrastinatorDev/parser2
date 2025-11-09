<?php

declare(strict_types=1);

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\HttpClient;
use Exception;

class RedditParser extends AbstractParser
{
    public function __construct(
        private readonly HttpClient $httpClient,
    ) {}

    /**
     * Parse Reddit JSON content.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    protected function doParse(ParseRequestDTO $request): array
    {
        // Fetch Reddit JSON
        $content = $this->httpClient->get($request->source);

        // Parse JSON
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse Reddit JSON: '.json_last_error_msg());
        }

        if (! isset($data['data']['children'])) {
            throw new Exception('Invalid Reddit JSON: missing data.children array');
        }

        // Store 'after' token for pagination
        if (isset($data['data']['after'])) {
            $this->afterToken = $data['data']['after'];
        }

        $items = [];

        foreach ($data['data']['children'] as $child) {
            if (! isset($child['data'])) {
                continue;
            }

            $post = $child['data'];

            $item = [
                'title' => $post['title'] ?? '',
                'url' => $post['url'] ?? '',
                'permalink' => 'https://reddit.com'.($post['permalink'] ?? ''),
                'description' => $post['selftext'] ?? '',
                'author' => $post['author'] ?? null,
                'score' => $post['score'] ?? 0,
                'upvote_ratio' => $post['upvote_ratio'] ?? null,
                'gilded' => $post['gilded'] ?? 0,
                'num_comments' => $post['num_comments'] ?? 0,
                'created_at' => isset($post['created_utc']) ? date('Y-m-d H:i:s', (int) $post['created_utc']) : null,
                'type' => $this->determinePostType($post),
                'nsfw' => $post['over_18'] ?? false,
                'spoiler' => $post['spoiler'] ?? false,
            ];

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Determine the type of Reddit post.
     */
    private function determinePostType(array $post): string
    {
        // Self post (text)
        if (! empty($post['is_self'])) {
            return 'text';
        }

        $url = $post['url'] ?? '';

        // Video post
        if (str_contains($url, 'v.redd.it') || str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'video';
        }

        // Image post
        if (
            str_contains($url, 'i.redd.it')
            || str_contains($url, 'i.imgur.com')
            || preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $url)
        ) {
            return 'image';
        }

        // External link
        return 'link';
    }

    /**
     * Build metadata with Reddit-specific info.
     *
     * @return array<string, mixed>
     */
    protected function buildMetadata(ParseRequestDTO $request): array
    {
        $metadata = parent::buildMetadata($request);

        // Add 'after' token for pagination
        if (isset($this->afterToken)) {
            $metadata['after'] = $this->afterToken;
        }

        return $metadata;
    }

    /**
     * Get parser name.
     */
    protected function getName(): string
    {
        return 'reddit';
    }

    /**
     * Reddit 'after' token for pagination.
     */
    private ?string $afterToken = null;
}

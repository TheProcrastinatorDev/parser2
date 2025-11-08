<?php

namespace App\Services\Parser\Parsers;

use App\DTOs\Parser\ParseRequestDTO;
use App\Services\Parser\AbstractParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\RateLimiter;
use Exception;

class RedditParser extends AbstractParser
{
    protected string $name = 'reddit';
    protected array $supportedTypes = ['subreddit', 'user', 'post'];

    protected function parseInternal(ParseRequestDTO $request): array
    {
        $url = $this->buildRedditUrl($request);
        $response = $this->httpClient->get($url, [
            'timeout' => $request->options['timeout'] ?? 30,
        ]);

        if (!$response->successful()) {
            throw new Exception("Failed to fetch Reddit data: HTTP {$response->status()}");
        }

        $data = json_decode($response->body(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response: ' . json_last_error_msg());
        }

        return $this->parseRedditData($data);
    }

    private function buildRedditUrl(ParseRequestDTO $request): string
    {
        $url = $request->source;

        // Ensure .json suffix
        if (!str_ends_with($url, '.json')) {
            $url = rtrim($url, '/') . '.json';
        }

        // Add pagination if specified
        if (isset($request->options['after'])) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . 'after=' . urlencode($request->options['after']);
        }

        return $url;
    }

    private function parseRedditData(array $data): array
    {
        $items = [];

        // Handle listing response (array with 'data' key)
        if (isset($data['data']['children'])) {
            foreach ($data['data']['children'] as $child) {
                $items[] = $this->parseRedditPost($child['data']);
            }
        }
        // Handle single post response (array of listings)
        elseif (is_array($data) && isset($data[0]['data']['children'])) {
            foreach ($data[0]['data']['children'] as $child) {
                $items[] = $this->parseRedditPost($child['data']);
            }
        }

        return $items;
    }

    private function parseRedditPost(array $post): array
    {
        $postType = $this->determinePostType($post);

        return [
            'title' => $post['title'] ?? '',
            'url' => $this->buildPostUrl($post),
            'content' => $post['selftext'] ?? '',
            'author' => $post['author'] ?? '',
            'published_at' => isset($post['created_utc']) ? date('c', $post['created_utc']) : null,
            'tags' => [],
            'images' => $this->extractImages($post),
            'metadata' => [
                'post_type' => $postType,
                'subreddit' => $post['subreddit'] ?? '',
                'score' => $post['score'] ?? 0,
                'upvote_ratio' => $post['upvote_ratio'] ?? 0,
                'num_comments' => $post['num_comments'] ?? 0,
                'gilded' => $post['gilded'] ?? 0,
                'nsfw' => $post['over_18'] ?? false,
                'spoiler' => $post['spoiler'] ?? false,
            ],
        ];
    }

    private function determinePostType(array $post): string
    {
        if (isset($post['post_hint'])) {
            if (str_contains($post['post_hint'], 'image')) {
                return 'image';
            }
            if (str_contains($post['post_hint'], 'video')) {
                return 'video';
            }
            if ($post['post_hint'] === 'self') {
                return 'text';
            }
        }

        // Fallback: check URL
        $url = $post['url'] ?? '';
        if (str_contains($url, 'i.redd.it') || str_contains($url, 'imgur.com')) {
            return 'image';
        }
        if (str_contains($url, 'v.redd.it') || str_contains($url, 'youtube.com')) {
            return 'video';
        }

        return 'link';
    }

    private function buildPostUrl(array $post): string
    {
        if (isset($post['permalink'])) {
            return 'https://www.reddit.com' . $post['permalink'];
        }

        return $post['url'] ?? '';
    }

    private function extractImages(array $post): array
    {
        $images = [];

        // Check for preview images
        if (isset($post['preview']['images'][0]['source']['url'])) {
            $images[] = $post['preview']['images'][0]['source']['url'];
        }

        // Check for thumbnail
        if (isset($post['thumbnail']) && $post['thumbnail'] !== 'self' && $post['thumbnail'] !== 'default') {
            $images[] = $post['thumbnail'];
        }

        // Check URL if it's an image
        $url = $post['url'] ?? '';
        if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $url)) {
            $images[] = $url;
        }

        return array_unique($images);
    }
}

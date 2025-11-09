<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Parser Settings
    |--------------------------------------------------------------------------
    |
    | These settings apply to all parsers unless overridden by parser-specific
    | configuration.
    |
    */

    'defaults' => [
        'timeout' => env('PARSER_TIMEOUT', 30), // seconds
        'max_retries' => env('PARSER_MAX_RETRIES', 3),
        'rate_limit' => [
            'per_minute' => 60,
            'per_hour' => 1000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Parser-Specific Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for each individual parser. Supports:
    | - enabled: Whether the parser is active
    | - rate_limit: Parser-specific rate limits
    | - options: Parser-specific options
    | - test_urls: URLs for testing (used in fixtures)
    |
    */

    'parsers' => [
        'feeds' => [
            'enabled' => true,
            'rate_limit' => [
                'per_minute' => 60,
                'per_hour' => 1000,
            ],
            'options' => [
                'max_items' => 100,
                'extract_images' => true,
            ],
            'test_urls' => [
                'https://hnrss.org/frontpage',
                'https://www.reddit.com/.rss',
            ],
        ],

        'reddit' => [
            'enabled' => true,
            'rate_limit' => [
                'per_minute' => 60,
                'per_hour' => 1000,
            ],
            'options' => [
                'include_nsfw' => false,
                'include_spoilers' => true,
            ],
            'test_urls' => [
                'https://www.reddit.com/r/programming.json',
                'https://www.reddit.com/r/laravel.json',
            ],
        ],

        'single_page' => [
            'enabled' => true,
            'rate_limit' => [
                'per_minute' => 30,
                'per_hour' => 500,
            ],
            'options' => [
                'clean_html' => true,
                'extract_images' => true,
                'fix_relative_urls' => true,
            ],
            'test_urls' => [
                'https://example.com',
            ],
        ],

        'telegram' => [
            'enabled' => true,
            'rate_limit' => [
                'per_minute' => 30,
                'per_hour' => 500,
            ],
            'options' => [
                'normalize_urls' => true,
                'extract_media' => true,
            ],
            'test_urls' => [
                'https://t.me/s/durov',
                'https://t.me/s/telegram',
            ],
        ],

        'medium' => [
            'enabled' => true,
            'rate_limit' => [
                'per_minute' => 60,
                'per_hour' => 1000,
            ],
            'options' => [
                'estimate_read_time' => true,
                'extract_author' => true,
            ],
            'test_urls' => [
                'https://medium.com/feed/@username',
            ],
        ],

        'bing_search' => [
            'enabled' => true,
            'rate_limit' => [
                'per_minute' => 20,
                'per_hour' => 200,
            ],
            'options' => [
                'search_type' => 'web', // web, news, images
                'safe_search' => true,
            ],
            'test_urls' => [
                'https://www.bing.com/search?q=laravel',
            ],
        ],

        'multi_url' => [
            'enabled' => true,
            'rate_limit' => [
                'per_minute' => 20,
                'per_hour' => 300,
            ],
            'options' => [
                'deduplicate' => true,
                'fix_relative_urls' => true,
                'fail_gracefully' => true,
            ],
            'test_urls' => [],
        ],

        'craigslist' => [
            'enabled' => true,
            'rate_limit' => [
                'per_minute' => 1, // Very conservative to avoid IP bans
                'per_hour' => 30,
            ],
            'options' => [
                'items_per_page' => 120,
                'extract_coordinates' => true,
                'extract_images' => true,
            ],
            'test_urls' => [
                'https://sfbay.craigslist.org/search/sss',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the HTTP client used by parsers.
    |
    */

    'http' => [
        'timeout' => env('PARSER_HTTP_TIMEOUT', 30),
        'connect_timeout' => env('PARSER_HTTP_CONNECT_TIMEOUT', 10),
        'retry' => [
            'max_attempts' => 3,
            'delay' => 1000, // milliseconds
            'multiplier' => 2, // exponential backoff multiplier
            'retryable_status_codes' => [429, 500, 502, 503, 504],
        ],
        'user_agents' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        ],
        'headers' => [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
            'Accept-Encoding' => 'gzip, deflate',
            'DNT' => '1',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Extraction Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for content extraction from HTML.
    |
    */

    'extraction' => [
        'remove_tags' => ['script', 'style', 'iframe', 'noscript'],
        'remove_attributes' => ['onclick', 'onload', 'onerror'],
        'image_selectors' => [
            'meta[property="og:image"]',
            'meta[name="twitter:image"]',
            'img[src]',
        ],
        'encoding' => 'UTF-8',
    ],
];

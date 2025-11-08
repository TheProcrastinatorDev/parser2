<?php

declare(strict_types=1);

return [
    'http' => [
        'timeout' => env('PARSER_HTTP_TIMEOUT', 20),
        'connect_timeout' => env('PARSER_HTTP_CONNECT_TIMEOUT', 5),
        'user_agents' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
        ],
        'retry' => [
            'max_attempts' => env('PARSER_HTTP_MAX_ATTEMPTS', 3),
            'delay_seconds' => env('PARSER_HTTP_RETRY_DELAY', 1),
            'backoff_factor' => env('PARSER_HTTP_BACKOFF_FACTOR', 2),
            'retry_statuses' => [429, 500, 502, 503, 504],
        ],
        'headers' => [
            'Accept-Language' => 'en-US,en;q=0.9',
        ],
    ],

    'rate_limits' => [
        'default' => [
            'requests_per_minute' => env('PARSER_RATE_LIMIT_RPM', 30),
            'requests_per_hour' => env('PARSER_RATE_LIMIT_RPH', 300),
        ],
        'feeds' => [
            'requests_per_minute' => 60,
            'requests_per_hour' => 600,
        ],
        'reddit' => [
            'requests_per_minute' => 30,
            'requests_per_hour' => 120,
        ],
        'single_page' => [
            'requests_per_minute' => 45,
            'requests_per_hour' => 300,
        ],
        'telegram' => [
            'requests_per_minute' => 15,
            'requests_per_hour' => 100,
        ],
        'medium' => [
            'requests_per_minute' => 25,
            'requests_per_hour' => 200,
        ],
        'bing_search' => [
            'requests_per_minute' => 20,
            'requests_per_hour' => 150,
        ],
        'multi_url' => [
            'requests_per_minute' => 10,
            'requests_per_hour' => 80,
        ],
        'craigslist' => [
            'requests_per_minute' => 5,
            'requests_per_hour' => 60,
        ],
    ],

    'parsers' => [
        'feeds' => [
            'class' => 'App\\Services\\Parser\\Parsers\\FeedsParser',
            'enabled' => true,
            'priority' => 1,
            'description' => 'Generic feed parser supporting RSS, Atom, and JSON feeds.',
            'supported_types' => ['rss', 'atom', 'json', 'google-news', 'bing-news'],
            'capabilities' => [
                'images' => true,
                'enclosures' => true,
                'pagination' => true,
            ],
        ],
        'reddit' => [
            'class' => 'App\\Services\\Parser\\Parsers\\RedditParser',
            'enabled' => true,
            'priority' => 1,
            'description' => 'Reddit JSON API parser for subreddits, users, and posts.',
            'supported_types' => ['subreddit', 'user', 'post'],
            'capabilities' => [
                'pagination' => true,
                'media' => true,
                'metadata' => ['score', 'upvote_ratio', 'gildings'],
            ],
        ],
        'single_page' => [
            'class' => 'App\\Services\\Parser\\Parsers\\SinglePageParser',
            'enabled' => true,
            'priority' => 1,
            'description' => 'Single page HTML parser with CSS, XPath, regex, and auto content extraction.',
            'supported_types' => ['auto', 'css', 'xpath', 'regex'],
            'capabilities' => [
                'images' => true,
                'metadata' => true,
                'lazy_images' => true,
            ],
        ],
        'telegram' => [
            'class' => 'App\\Services\\Parser\\Parsers\\TelegramParser',
            'enabled' => true,
            'priority' => 2,
            'description' => 'Public Telegram channel scraping without API keys.',
            'supported_types' => ['channel'],
            'capabilities' => [
                'media' => true,
                'views' => true,
            ],
        ],
        'medium' => [
            'class' => 'App\\Services\\Parser\\Parsers\\MediumParser',
            'enabled' => true,
            'priority' => 2,
            'description' => 'Medium RSS feed parser for users and publications.',
            'supported_types' => ['user', 'publication', 'topic'],
            'capabilities' => [
                'read_time' => true,
                'author' => true,
            ],
        ],
        'bing_search' => [
            'class' => 'App\\Services\\Parser\\Parsers\\BingSearchParser',
            'enabled' => true,
            'priority' => 2,
            'description' => 'Bing search scraping for web, news, and images.',
            'supported_types' => ['web', 'news', 'images'],
            'capabilities' => [
                'pagination' => true,
                'images' => true,
            ],
        ],
        'multi_url' => [
            'class' => 'App\\Services\\Parser\\Parsers\\MultiUrlParser',
            'enabled' => true,
            'priority' => 3,
            'description' => 'Discovers and processes multiple URLs using CSS, XPath, regex, or static lists.',
            'supported_types' => ['css', 'xpath', 'regex', 'list'],
            'capabilities' => [
                'deduplication' => true,
                'delegates_to' => ['single_page'],
            ],
        ],
        'craigslist' => [
            'class' => 'App\\Services\\Parser\\Parsers\\CraigslistParser',
            'enabled' => false,
            'priority' => 3,
            'description' => 'Craigslist search and listing scraper with anti-blocking considerations.',
            'supported_types' => ['search', 'listing'],
            'capabilities' => [
                'rate_limit' => 'strict',
                'geo_coordinates' => true,
            ],
        ],
    ],
];

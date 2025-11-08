<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Parser Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for all parsers in the system. Each parser can have:
    | - enabled: Whether the parser is active
    | - rate_limit: Requests per time window
    | - timeout: HTTP request timeout in seconds
    | - user_agents: Custom user agents for rotation
    |
    */

    'feeds' => [
        'enabled' => true,
        'rate_limit' => [
            'requests' => 60,
            'window' => 60, // per minute
        ],
        'timeout' => 30,
        'supported_types' => ['rss', 'atom', 'json', 'google_news', 'bing_news'],
    ],

    'reddit' => [
        'enabled' => true,
        'rate_limit' => [
            'requests' => 30,
            'window' => 60,
        ],
        'timeout' => 30,
        'supported_types' => ['subreddit', 'user', 'post'],
    ],

    'single_page' => [
        'enabled' => true,
        'rate_limit' => [
            'requests' => 100,
            'window' => 60,
        ],
        'timeout' => 30,
        'supported_types' => ['auto', 'css', 'xpath', 'regex'],
    ],

    'telegram' => [
        'enabled' => true,
        'rate_limit' => [
            'requests' => 20,
            'window' => 60,
        ],
        'timeout' => 30,
        'supported_types' => ['channel'],
    ],

    'medium' => [
        'enabled' => true,
        'rate_limit' => [
            'requests' => 40,
            'window' => 60,
        ],
        'timeout' => 30,
        'supported_types' => ['user', 'publication', 'tag'],
    ],

    'bing_search' => [
        'enabled' => true,
        'rate_limit' => [
            'requests' => 10,
            'window' => 60,
        ],
        'timeout' => 30,
        'supported_types' => ['web', 'news', 'images'],
    ],

    'multi_url' => [
        'enabled' => true,
        'rate_limit' => [
            'requests' => 50,
            'window' => 60,
        ],
        'timeout' => 30,
        'supported_types' => ['xpath', 'css', 'regex', 'list'],
    ],

    'craigslist' => [
        'enabled' => true,
        'rate_limit' => [
            'requests' => 1,
            'window' => 1, // 1 per second to avoid IP blocking
        ],
        'timeout' => 30,
        'supported_types' => ['search', 'listing'],
    ],
];

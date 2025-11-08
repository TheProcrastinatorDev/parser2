<?php

namespace App\Providers;

use App\Services\Parser\ParserManager;
use App\Services\Parser\Parsers\FeedsParser;
use App\Services\Parser\Parsers\RedditParser;
use App\Services\Parser\Parsers\SinglePageParser;
use App\Services\Parser\Support\ContentExtractor;
use App\Services\Parser\Support\HttpClient;
use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\ServiceProvider;

class ParserServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ParserManager::class, function ($app) {
            $manager = new ParserManager();

            // Register P1 parsers
            if (config('parser.feeds.enabled', true)) {
                $manager->register('feeds', new FeedsParser(
                    $app->make(HttpClient::class),
                    $app->make(ContentExtractor::class),
                    new RateLimiter('feeds', config('parser.feeds.rate_limit.requests', 60), config('parser.feeds.rate_limit.window', 60))
                ));
            }

            if (config('parser.reddit.enabled', true)) {
                $manager->register('reddit', new RedditParser(
                    $app->make(HttpClient::class),
                    $app->make(ContentExtractor::class),
                    new RateLimiter('reddit', config('parser.reddit.rate_limit.requests', 30), config('parser.reddit.rate_limit.window', 60))
                ));
            }

            if (config('parser.single_page.enabled', true)) {
                $manager->register('single_page', new SinglePageParser(
                    $app->make(HttpClient::class),
                    $app->make(ContentExtractor::class),
                    new RateLimiter('single_page', config('parser.single_page.rate_limit.requests', 100), config('parser.single_page.rate_limit.window', 60))
                ));
            }

            // TODO: Register P2 and P3 parsers when implemented
            // - TelegramParser
            // - MediumParser
            // - BingSearchParser
            // - MultiUrlParser
            // - CraigslistParser

            return $manager;
        });
    }

    public function boot(): void
    {
        //
    }
}

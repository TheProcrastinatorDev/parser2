<?php

namespace App\Providers;

use App\Services\Parser\ParserManager;
use App\Services\Parser\Parsers\BingSearchParser;
use App\Services\Parser\Parsers\CraigslistParser;
use App\Services\Parser\Parsers\FeedsParser;
use App\Services\Parser\Parsers\MediumParser;
use App\Services\Parser\Parsers\MultiUrlParser;
use App\Services\Parser\Parsers\RedditParser;
use App\Services\Parser\Parsers\SinglePageParser;
use App\Services\Parser\Parsers\TelegramParser;
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

            // Register P2 parsers
            if (config('parser.telegram.enabled', true)) {
                $manager->register('telegram', new TelegramParser(
                    $app->make(HttpClient::class),
                    $app->make(ContentExtractor::class),
                    new RateLimiter('telegram', config('parser.telegram.rate_limit.requests', 20), config('parser.telegram.rate_limit.window', 60))
                ));
            }

            if (config('parser.medium.enabled', true)) {
                $manager->register('medium', new MediumParser(
                    $app->make(HttpClient::class),
                    $app->make(ContentExtractor::class),
                    new RateLimiter('medium', config('parser.medium.rate_limit.requests', 40), config('parser.medium.rate_limit.window', 60))
                ));
            }

            if (config('parser.bing_search.enabled', true)) {
                $manager->register('bing_search', new BingSearchParser(
                    $app->make(HttpClient::class),
                    $app->make(ContentExtractor::class),
                    new RateLimiter('bing_search', config('parser.bing_search.rate_limit.requests', 10), config('parser.bing_search.rate_limit.window', 60))
                ));
            }

            // Register P3 parsers
            if (config('parser.multi_url.enabled', true)) {
                $manager->register('multi_url', new MultiUrlParser(
                    $app->make(HttpClient::class),
                    $app->make(ContentExtractor::class),
                    new RateLimiter('multi_url', config('parser.multi_url.rate_limit.requests', 50), config('parser.multi_url.rate_limit.window', 60))
                ));
            }

            if (config('parser.craigslist.enabled', true)) {
                $manager->register('craigslist', new CraigslistParser(
                    $app->make(HttpClient::class),
                    $app->make(ContentExtractor::class),
                    new RateLimiter('craigslist', config('parser.craigslist.rate_limit.requests', 1), config('parser.craigslist.rate_limit.window', 1))
                ));
            }

            return $manager;
        });
    }

    public function boot(): void
    {
        //
    }
}

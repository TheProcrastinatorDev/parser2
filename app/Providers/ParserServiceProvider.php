<?php

declare(strict_types=1);

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
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register singletons for shared services
        $this->app->singleton(HttpClient::class);
        $this->app->singleton(ContentExtractor::class);
        $this->app->singleton(RateLimiter::class);
        $this->app->singleton(ParserManager::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register all parsers with the ParserManager
        $parserManager = $this->app->make(ParserManager::class);

        // P1 Parsers
        $parserManager->register('feeds', $this->app->make(FeedsParser::class));
        $parserManager->register('reddit', $this->app->make(RedditParser::class));
        $parserManager->register('single_page', $this->app->make(SinglePageParser::class));

        // P2 Parsers
        $parserManager->register('telegram', $this->app->make(TelegramParser::class));
        $parserManager->register('medium', $this->app->make(MediumParser::class));
        $parserManager->register('bing', $this->app->make(BingSearchParser::class));

        // P3 Parsers
        $parserManager->register('multi', $this->app->make(MultiUrlParser::class));
        $parserManager->register('craigslist', $this->app->make(CraigslistParser::class));
    }
}

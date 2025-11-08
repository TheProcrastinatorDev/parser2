<?php

namespace App\Services\Parser\Support;

use Illuminate\Support\Facades\Cache;

class RateLimiter
{
    public function __construct(
        private string $parserName,
        private int $maxRequests,
        private int $windowSeconds,
    ) {}

    public function check(): bool
    {
        $key = $this->getCacheKey();
        $current = Cache::get($key, 0);

        if ($current >= $this->maxRequests) {
            return false;
        }

        Cache::put($key, $current + 1, $this->windowSeconds);
        return true;
    }

    public function getCurrentCount(): int
    {
        $key = $this->getCacheKey();
        return Cache::get($key, 0);
    }

    public function getRemaining(): int
    {
        return max(0, $this->maxRequests - $this->getCurrentCount());
    }

    private function getCacheKey(): string
    {
        return "rate_limit:parser:{$this->parserName}";
    }
}

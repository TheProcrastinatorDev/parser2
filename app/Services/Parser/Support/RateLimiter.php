<?php

declare(strict_types=1);

namespace App\Services\Parser\Support;

use Illuminate\Support\Facades\Cache;

class RateLimiter
{
    /**
     * Attempt to execute a rate-limited operation.
     */
    public function attempt(string $key, int $perMinute, int $perHour): bool
    {
        $minuteKey = $this->getKey($key, 'minute');
        $hourKey = $this->getKey($key, 'hour');

        // Check minute limit
        $minuteHits = Cache::get($minuteKey, 0);
        if ($minuteHits >= $perMinute) {
            return false;
        }

        // Check hour limit
        $hourHits = Cache::get($hourKey, 0);
        if ($hourHits >= $perHour) {
            return false;
        }

        // Increment counters
        Cache::add($minuteKey, 0, 60); // TTL: 60 seconds
        Cache::increment($minuteKey);

        Cache::add($hourKey, 0, 3600); // TTL: 3600 seconds (1 hour)
        Cache::increment($hourKey);

        return true;
    }

    /**
     * Get the number of remaining attempts.
     */
    public function remaining(string $key, int $perMinute, int $perHour): int
    {
        $minuteKey = $this->getKey($key, 'minute');
        $hourKey = $this->getKey($key, 'hour');

        $minuteHits = Cache::get($minuteKey, 0);
        $hourHits = Cache::get($hourKey, 0);

        $minuteRemaining = max(0, $perMinute - $minuteHits);
        $hourRemaining = max(0, $perHour - $hourHits);

        // Return the most restrictive limit
        return min($minuteRemaining, $hourRemaining);
    }

    /**
     * Get the number of seconds until the rate limit resets.
     */
    public function availableIn(string $key): int
    {
        $minuteKey = $this->getKey($key, 'minute');

        // If the key doesn't exist, it's already reset
        if (! Cache::has($minuteKey)) {
            return 0;
        }

        // Return TTL of the minute key
        // Note: Laravel's Cache doesn't expose TTL directly,
        // so we approximate based on 60-second window
        return 60;
    }

    /**
     * Clear rate limit for a specific key.
     */
    public function clear(string $key): void
    {
        $minuteKey = $this->getKey($key, 'minute');
        $hourKey = $this->getKey($key, 'hour');

        Cache::forget($minuteKey);
        Cache::forget($hourKey);
    }

    /**
     * Get the number of hits for a specific key.
     */
    public function hits(string $key): int
    {
        $minuteKey = $this->getKey($key, 'minute');

        return Cache::get($minuteKey, 0);
    }

    /**
     * Build cache key for rate limiting.
     */
    private function getKey(string $key, string $period): string
    {
        return "rate_limit:{$key}:{$period}";
    }
}

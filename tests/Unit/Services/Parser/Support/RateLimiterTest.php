<?php

use App\Services\Parser\Support\RateLimiter;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('allows request when under limit', function () {
    $limiter = new RateLimiter('test-parser', 10, 60); // 10 per minute

    expect($limiter->check())->toBeTrue();
});

it('blocks request when limit exceeded', function () {
    $limiter = new RateLimiter('test-parser', 2, 60); // 2 per minute

    expect($limiter->check())->toBeTrue(); // First request
    expect($limiter->check())->toBeTrue(); // Second request
    expect($limiter->check())->toBeFalse(); // Third request should be blocked
});

it('tracks requests per parser separately', function () {
    $limiter1 = new RateLimiter('parser1', 2, 60);
    $limiter2 = new RateLimiter('parser2', 2, 60);

    expect($limiter1->check())->toBeTrue();
    expect($limiter1->check())->toBeTrue();
    expect($limiter1->check())->toBeFalse(); // Parser1 blocked

    expect($limiter2->check())->toBeTrue(); // Parser2 still allowed
    expect($limiter2->check())->toBeTrue();
    expect($limiter2->check())->toBeFalse(); // Parser2 now blocked
});

it('resets after time window', function () {
    $limiter = new RateLimiter('test-parser', 1, 1); // 1 per second

    expect($limiter->check())->toBeTrue();
    expect($limiter->check())->toBeFalse();

    sleep(2); // Wait for window to reset

    expect($limiter->check())->toBeTrue(); // Should be allowed again
});

it('increments counter on check', function () {
    $limiter = new RateLimiter('test-parser', 5, 60);

    $limiter->check();
    $limiter->check();
    $limiter->check();

    expect($limiter->getCurrentCount())->toBe(3);
});

it('returns remaining requests', function () {
    $limiter = new RateLimiter('test-parser', 5, 60);

    $limiter->check();
    $limiter->check();

    expect($limiter->getRemaining())->toBe(3);
});

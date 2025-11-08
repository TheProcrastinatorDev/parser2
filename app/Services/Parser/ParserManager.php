<?php

declare(strict_types=1);

namespace App\Services\Parser;

use Closure;
use App\Services\Parser\Exceptions\ParserAlreadyRegisteredException;
use App\Services\Parser\Exceptions\ParserNotFoundException;

class ParserManager
{
    /**
     * @var array<string, Closure():object>
     */
    private array $factories = [];

    /**
     * @param  callable():object  $factory
     */
    public function register(string $key, callable $factory): void
    {
        $normalisedKey = $this->normaliseKey($key);

        if ($this->has($normalisedKey)) {
            throw ParserAlreadyRegisteredException::forParser($key);
        }

        $this->factories[$normalisedKey] = Closure::fromCallable($factory);
    }

    public function registerClass(string $key, string $className): void
    {
        $this->register($key, static fn (): object => app($className));
    }

    public function get(string $key): object
    {
        $normalisedKey = $this->normaliseKey($key);

        if (! $this->has($normalisedKey)) {
            throw ParserNotFoundException::forParser($key);
        }

        return ($this->factories[$normalisedKey])();
    }

    public function has(string $key): bool
    {
        return array_key_exists($this->normaliseKey($key), $this->factories);
    }

    /**
     * @return string[]
     */
    public function keys(): array
    {
        return array_keys($this->factories);
    }

    /**
     * @return array<string, Closure():object>
     */
    public function all(): array
    {
        return $this->factories;
    }

    private function normaliseKey(string $key): string
    {
        return strtolower(trim($key));
    }
}

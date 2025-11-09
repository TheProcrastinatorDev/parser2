<?php

declare(strict_types=1);

namespace App\Services\Parser;

use App\Exceptions\ParserNotFoundException;
use Exception;

class ParserManager
{
    /**
     * Registered parsers.
     *
     * @var array<string, AbstractParser>
     */
    private array $parsers = [];

    /**
     * Register a parser.
     *
     * @throws Exception
     */
    public function register(string $name, AbstractParser $parser): void
    {
        if ($this->has($name)) {
            throw new Exception("Parser \"{$name}\" is already registered");
        }

        $this->parsers[$name] = $parser;
    }

    /**
     * Get a parser by name.
     *
     * @throws ParserNotFoundException
     */
    public function get(string $name): AbstractParser
    {
        if (! $this->has($name)) {
            throw new ParserNotFoundException($name);
        }

        return $this->parsers[$name];
    }

    /**
     * Get all registered parsers.
     *
     * @return array<string, AbstractParser>
     */
    public function all(): array
    {
        return $this->parsers;
    }

    /**
     * Get all parser names.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_keys($this->parsers);
    }

    /**
     * Check if a parser is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->parsers[$name]);
    }

    /**
     * Get the count of registered parsers.
     */
    public function count(): int
    {
        return count($this->parsers);
    }

    /**
     * Remove a registered parser.
     */
    public function remove(string $name): void
    {
        unset($this->parsers[$name]);
    }

    /**
     * Get details about a specific parser.
     *
     * @return array<string, mixed>
     *
     * @throws ParserNotFoundException
     */
    public function getDetails(string $name): array
    {
        $parser = $this->get($name);

        return [
            'name' => $name,
            'class' => get_class($parser),
        ];
    }

    /**
     * Get details about all registered parsers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllDetails(): array
    {
        $details = [];

        foreach ($this->parsers as $name => $parser) {
            $details[] = [
                'name' => $name,
                'class' => get_class($parser),
            ];
        }

        return $details;
    }
}

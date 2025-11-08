<?php

namespace App\Services\Parser;

use Exception;

class ParserManager
{
    private array $parsers = [];

    public function register(string $name, AbstractParser $parser): void
    {
        if (isset($this->parsers[$name])) {
            throw new Exception("Parser already registered: {$name}");
        }

        $this->parsers[$name] = $parser;
    }

    public function get(string $name): AbstractParser
    {
        if (!isset($this->parsers[$name])) {
            throw new Exception("Parser not found: {$name}");
        }

        return $this->parsers[$name];
    }

    public function list(): array
    {
        return $this->parsers;
    }

    public function has(string $name): bool
    {
        return isset($this->parsers[$name]);
    }
}

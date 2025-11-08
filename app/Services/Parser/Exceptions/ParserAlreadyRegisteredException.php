<?php

declare(strict_types=1);

namespace App\Services\Parser\Exceptions;

use RuntimeException;

final class ParserAlreadyRegisteredException extends RuntimeException
{
    public static function forParser(string $parser): self
    {
        return new self(sprintf('Parser [%s] is already registered.', $parser));
    }
}

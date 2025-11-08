<?php

declare(strict_types=1);

namespace App\Services\Parser\Exceptions;

use RuntimeException;

final class ParserNotFoundException extends RuntimeException
{
    public static function forParser(string $parser): self
    {
        return new self(sprintf('Parser [%s] is not registered.', $parser));
    }
}

<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class ParserNotFoundException extends Exception
{
    public function __construct(string $parserName)
    {
        parent::__construct("Parser \"{$parserName}\" not found");
    }
}

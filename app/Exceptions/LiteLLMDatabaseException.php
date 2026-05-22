<?php

namespace App\Exceptions;

use Exception;

class LiteLLMDatabaseException extends Exception
{
    public static function fromResponse(string $response): self
    {
        return new self($response);
    }
}
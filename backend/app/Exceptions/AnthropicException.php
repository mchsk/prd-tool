<?php

namespace App\Exceptions;

use Exception;

class AnthropicException extends Exception
{
    private ?string $anthropicError;

    public function __construct(
        string $message = 'Anthropic API error',
        int $code = 0,
        ?string $anthropicError = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->anthropicError = $anthropicError;
    }

    public function getAnthropicError(): ?string
    {
        return $this->anthropicError;
    }
}

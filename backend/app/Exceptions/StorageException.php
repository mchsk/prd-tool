<?php

namespace App\Exceptions;

use Exception;

class StorageException extends Exception
{
    /**
     * Create a new StorageException.
     */
    public function __construct(string $message = 'Storage operation failed', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

<?php

declare(strict_types=1);

namespace SimoneBianco\SimpleStorageClient\Exceptions;

/**
 * Exception thrown when authentication fails (HTTP 401).
 */
class UnauthorizedException extends SimpleStorageException
{
    public function __construct()
    {
        parent::__construct(
            message: 'Invalid or missing API key',
            code: 401
        );
    }
}

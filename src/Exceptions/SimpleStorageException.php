<?php

declare(strict_types=1);

namespace SimoneBianco\SimpleStorageClient\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Base exception thrown when a Simple Storage operation fails.
 *
 * This is the base class for all Simple Storage exceptions. Specific exception
 * types extend this class to provide more granular exception handling:
 *
 * - ConnectionFailedException: Network/connection issues (should NOT be silently caught)
 * - NotFoundException: Resource not found (HTTP 404)
 * - FileGoneException: Resource deleted (HTTP 410)
 * - UnauthorizedException: Authentication failed (HTTP 401)
 *
 * @see ConnectionFailedException
 * @see NotFoundException
 * @see FileGoneException
 * @see UnauthorizedException
 */
class SimpleStorageException extends Exception
{
    protected ?Response $response = null;

    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message = 'Simple Storage operation failed',
        int $code = 0,
        ?Exception $previous = null,
        ?Response $response = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * Get the HTTP response if available.
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Check if this exception represents a connection failure.
     */
    public function isConnectionError(): bool
    {
        return $this instanceof ConnectionFailedException;
    }

    /**
     * Check if this exception represents a "not found" error.
     */
    public function isNotFound(): bool
    {
        return $this instanceof NotFoundException;
    }

    /**
     * Check if this exception represents a "file gone/deleted" error.
     */
    public function isFileGone(): bool
    {
        return $this instanceof FileGoneException;
    }

    /**
     * Check if this exception represents an authentication error.
     */
    public function isUnauthorized(): bool
    {
        return $this instanceof UnauthorizedException;
    }

    /**
     * Create exception from HTTP response.
     */
    public static function fromResponse(Response $response, string $context = ''): self
    {
        $body = $response->json();
        $error = $body['error'] ?? 'Unknown error';
        $message = $context ? "{$context}: {$error}" : $error;

        return new self(
            message: $message,
            code: $response->status(),
            response: $response
        );
    }

    /**
     * Create a connection exception.
     *
     * @deprecated Use new ConnectionFailedException($url, $previous) instead
     */
    public static function connectionFailed(string $url, ?Exception $previous = null): ConnectionFailedException
    {
        return new ConnectionFailedException($url, $previous);
    }

    /**
     * Create an authentication exception.
     *
     * @deprecated Use new UnauthorizedException() instead
     */
    public static function unauthorized(): UnauthorizedException
    {
        return new UnauthorizedException();
    }

    /**
     * Create a not found exception.
     *
     * @deprecated Use new NotFoundException($jobId) instead
     */
    public static function notFound(string $jobId): NotFoundException
    {
        return new NotFoundException($jobId);
    }

    /**
     * Create a file deleted exception.
     *
     * @deprecated Use new FileGoneException($jobId) instead
     */
    public static function fileDeleted(string $jobId): FileGoneException
    {
        return new FileGoneException($jobId);
    }

    /**
     * Create a file read exception.
     */
    public static function fileNotReadable(string $path): self
    {
        return new self(
            message: "File not readable: {$path}",
            code: 0
        );
    }

    /**
     * Create a file write exception.
     */
    public static function fileNotWritable(string $path): self
    {
        return new self(
            message: "Cannot write to path: {$path}",
            code: 0
        );
    }
}

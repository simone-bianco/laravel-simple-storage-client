<?php

declare(strict_types=1);

namespace Dolphin\SimpleStorage\Exceptions;

use Exception;
use Illuminate\Http\Client\Response;

/**
 * Exception thrown when a Simple Storage operation fails.
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
     */
    public static function connectionFailed(string $url, ?Exception $previous = null): self
    {
        return new self(
            message: "Failed to connect to Simple Storage Server at {$url}",
            code: 0,
            previous: $previous
        );
    }

    /**
     * Create an authentication exception.
     */
    public static function unauthorized(): self
    {
        return new self(
            message: 'Invalid or missing API key',
            code: 401
        );
    }

    /**
     * Create a not found exception.
     */
    public static function notFound(string $jobId): self
    {
        return new self(
            message: "Job not found: {$jobId}",
            code: 404
        );
    }

    /**
     * Create a file deleted exception.
     */
    public static function fileDeleted(string $jobId): self
    {
        return new self(
            message: "File already deleted: {$jobId}",
            code: 410
        );
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

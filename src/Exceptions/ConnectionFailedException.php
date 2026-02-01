<?php

declare(strict_types=1);

namespace SimoneBianco\SimpleStorageClient\Exceptions;

use Exception;

/**
 * Exception thrown when a connection to the storage server fails.
 *
 * This is a distinct exception type that should NOT be silently caught
 * in methods like exists(), as it indicates a network-level issue.
 */
class ConnectionFailedException extends SimpleStorageException
{
    protected string $targetUrl;

    public function __construct(
        string $url,
        ?Exception $previous = null
    ) {
        $this->targetUrl = $url;
        parent::__construct(
            message: "Failed to connect to Simple Storage Server at {$url}",
            code: 0,
            previous: $previous
        );
    }

    /**
     * Get the URL that was unreachable.
     */
    public function getTargetUrl(): string
    {
        return $this->targetUrl;
    }
}

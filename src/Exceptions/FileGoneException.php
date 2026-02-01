<?php

declare(strict_types=1);

namespace SimoneBianco\SimpleStorageClient\Exceptions;

/**
 * Exception thrown when a file has been deleted (HTTP 410 Gone).
 *
 * This indicates the file existed at some point but has since been removed.
 */
class FileGoneException extends SimpleStorageException
{
    protected string $jobId;

    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
        parent::__construct(
            message: "File already deleted: {$jobId}",
            code: 410
        );
    }

    /**
     * Get the job ID of the deleted file.
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}

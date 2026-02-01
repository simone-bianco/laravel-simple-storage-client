<?php

declare(strict_types=1);

namespace SimoneBianco\SimpleStorageClient\Exceptions;

/**
 * Exception thrown when a requested resource (job/file) is not found.
 *
 * This exception can be safely caught in exists() methods as it indicates
 * the file genuinely doesn't exist, rather than a system error.
 */
class NotFoundException extends SimpleStorageException
{
    protected string $jobId;

    public function __construct(string $jobId)
    {
        $this->jobId = $jobId;
        parent::__construct(
            message: "Job not found: {$jobId}",
            code: 404
        );
    }

    /**
     * Get the job ID that was not found.
     */
    public function getJobId(): string
    {
        return $this->jobId;
    }
}

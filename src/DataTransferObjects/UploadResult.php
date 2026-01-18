<?php

declare(strict_types=1);

namespace Dolphin\SimpleStorage\DataTransferObjects;

/**
 * Represents the result of an upload operation.
 */
final readonly class UploadResult
{
    public function __construct(
        public string $status,
        public string $jobId,
        public int $fileSize,
        public string $downloadUrl,
    ) {}

    /**
     * Create from API response array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? 'unknown',
            jobId: $data['job_id'] ?? '',
            fileSize: (int) ($data['file_size'] ?? 0),
            downloadUrl: $data['download_url'] ?? '',
        );
    }

    /**
     * Check if the upload was successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'uploaded';
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'job_id' => $this->jobId,
            'file_size' => $this->fileSize,
            'download_url' => $this->downloadUrl,
        ];
    }
}

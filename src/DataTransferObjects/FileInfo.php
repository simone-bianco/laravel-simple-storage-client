<?php

declare(strict_types=1);

namespace Dolphin\SimpleStorage\DataTransferObjects;

use Carbon\Carbon;

/**
 * Represents information about a stored file.
 */
final readonly class FileInfo
{
    public function __construct(
        public string $jobId,
        public int $fileSize,
        public Carbon $uploadedAt,
        public ?Carbon $downloadedAt,
        public bool $deleted,
    ) {}

    /**
     * Create from API response array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            jobId: $data['job_id'] ?? '',
            fileSize: (int) ($data['file_size'] ?? 0),
            uploadedAt: Carbon::parse($data['uploaded_at'] ?? now()),
            downloadedAt: isset($data['downloaded_at']) ? Carbon::parse($data['downloaded_at']) : null,
            deleted: (bool) ($data['deleted'] ?? false),
        );
    }

    /**
     * Check if the file is available for download.
     */
    public function isAvailable(): bool
    {
        return !$this->deleted;
    }

    /**
     * Check if the file has been downloaded.
     */
    public function hasBeenDownloaded(): bool
    {
        return $this->downloadedAt !== null;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'job_id' => $this->jobId,
            'file_size' => $this->fileSize,
            'uploaded_at' => $this->uploadedAt->toISOString(),
            'downloaded_at' => $this->downloadedAt?->toISOString(),
            'deleted' => $this->deleted,
        ];
    }
}

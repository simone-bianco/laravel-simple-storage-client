<?php

declare(strict_types=1);

namespace SimoneBianco\SimpleStorageClient\Contracts;

use SimoneBianco\SimpleStorageClient\DataTransferObjects\FileInfo;
use SimoneBianco\SimpleStorageClient\DataTransferObjects\HealthStatus;
use SimoneBianco\SimpleStorageClient\DataTransferObjects\UploadResult;
use SimoneBianco\SimpleStorageClient\Exceptions\ConnectionFailedException;
use SimoneBianco\SimpleStorageClient\Exceptions\SimpleStorageException;
use SimoneBianco\SimpleStorageClient\Exceptions\UnauthorizedException;
use Illuminate\Support\Collection;

/**
 * Contract for Simple Storage operations.
 *
 * This interface defines all operations available for interacting with
 * the Dolphin Simple Storage Server.
 */
interface SimpleStorageInterface
{
    /**
     * Check if the storage server is healthy and reachable.
     *
     * @return HealthStatus The health status of the server
     * @throws SimpleStorageException If the request fails
     */
    public function health(): HealthStatus;

    /**
     * Check if the storage server is reachable.
     *
     * @return bool True if the server is healthy, false otherwise
     */
    public function isHealthy(): bool;

    /**
     * Upload a ZIP file to the storage server.
     *
     * @param string $jobId Unique job identifier
     * @param string $filePath Absolute path to the ZIP file
     * @return UploadResult The upload result with download URL
     * @throws SimpleStorageException If the upload fails
     */
    public function upload(string $jobId, string $filePath): UploadResult;

    /**
     * Upload raw binary content to the storage server.
     *
     * @param string $jobId Unique job identifier
     * @param string $content Raw binary content
     * @return UploadResult The upload result with download URL
     * @throws SimpleStorageException If the upload fails
     */
    public function uploadContent(string $jobId, string $content): UploadResult;

    /**
     * Download a ZIP file from the storage server.
     *
     * @param string $jobId The job identifier
     * @param bool $keep If true, don't delete the file after download
     * @return string The raw binary content of the ZIP file
     * @throws SimpleStorageException If the download fails
     */
    public function download(string $jobId, bool $keep = false): string;

    /**
     * Download a ZIP file and save it to disk.
     *
     * @param string $jobId The job identifier
     * @param string $absoluteDestinationPath Where to save the file (absolute path)
     * @param bool $keep If true, don't delete the file after download
     * @return string The full path to the saved file
     * @throws SimpleStorageException If the download fails
     */
    public function downloadTo(string $jobId, string $absoluteDestinationPath, bool $keep = false): string;

    /**
     * Delete a file from the storage server.
     *
     * @param string $jobId The job identifier
     * @return bool True if deletion was successful
     * @throws SimpleStorageException If the deletion fails
     */
    public function delete(string $jobId): bool;

    /**
     * Check if a file exists on the storage server.
     *
     * Note: This method will throw exceptions for connection failures
     * and authentication errors. It only returns false when the file
     * genuinely doesn't exist.
     *
     * @param string $jobId The job identifier
     * @return bool True if the file exists and is not deleted
     * @throws ConnectionFailedException If the server is unreachable
     * @throws UnauthorizedException If authentication fails
     * @throws SimpleStorageException For other unexpected errors
     */
    public function exists(string $jobId): bool;

    /**
     * List all files stored on the server.
     *
     * @return Collection<int, FileInfo> Collection of file information
     * @throws SimpleStorageException If the request fails
     */
    public function list(): Collection;

    /**
     * Trigger cleanup of old files on the server.
     *
     * @return array The cleanup result
     * @throws SimpleStorageException If the request fails
     */
    public function cleanup(): array;
}

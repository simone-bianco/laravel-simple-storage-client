<?php

declare(strict_types=1);

namespace SimoneBianco\SimpleStorageClient\Facades;

use SimoneBianco\SimpleStorageClient\Contracts\SimpleStorageInterface;
use SimoneBianco\SimpleStorageClient\DataTransferObjects\FileInfo;
use SimoneBianco\SimpleStorageClient\DataTransferObjects\HealthStatus;
use SimoneBianco\SimpleStorageClient\DataTransferObjects\UploadResult;
use SimoneBianco\SimpleStorageClient\Exceptions\ConnectionFailedException;
use SimoneBianco\SimpleStorageClient\Exceptions\SimpleStorageException;
use SimoneBianco\SimpleStorageClient\Exceptions\UnauthorizedException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for Simple Storage Client.
 *
 * @method static HealthStatus health() Check the health status of the storage server @throws ConnectionFailedException @throws SimpleStorageException
 * @method static bool isHealthy() Check if the storage server is reachable
 * @method static UploadResult upload(string $jobId, string $filePath) Upload a ZIP file @throws ConnectionFailedException @throws SimpleStorageException
 * @method static UploadResult uploadContent(string $jobId, string $content) Upload raw binary content @throws ConnectionFailedException @throws SimpleStorageException
 * @method static string download(string $jobId, bool $keep = false) Download a ZIP file as string @throws ConnectionFailedException @throws SimpleStorageException
 * @method static string downloadTo(string $jobId, string $destinationPath, bool $keep = false) Download and save to disk @throws ConnectionFailedException @throws SimpleStorageException
 * @method static bool delete(string $jobId) Delete a file @throws ConnectionFailedException @throws SimpleStorageException
 * @method static bool exists(string $jobId) Check if a file exists @throws ConnectionFailedException @throws UnauthorizedException @throws SimpleStorageException
 * @method static Collection<int, FileInfo> list() List all stored files @throws ConnectionFailedException @throws SimpleStorageException
 * @method static string getBaseUrl() Get the configured base URL
 * @method static SimpleStorageInterface setBaseUrl(string $baseUrl) Set a new base URL
 * @method static SimpleStorageInterface setApiKey(string $apiKey) Set a new API key
 *
 * @see \SimoneBianco\SimpleStorageClient\SimpleStorageClient
 * @see \SimoneBianco\SimpleStorageClient\Contracts\SimpleStorageInterface
 *
 * @mixin \SimoneBianco\SimpleStorageClient\SimpleStorageClient
 */
class SimpleStorage extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SimpleStorageInterface::class;
    }
}

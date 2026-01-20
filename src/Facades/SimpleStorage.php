<?php

declare(strict_types=1);

namespace Dolphin\SimpleStorage\Facades;

use Dolphin\SimpleStorage\Contracts\SimpleStorageInterface;
use Dolphin\SimpleStorage\DataTransferObjects\FileInfo;
use Dolphin\SimpleStorage\DataTransferObjects\HealthStatus;
use Dolphin\SimpleStorage\DataTransferObjects\UploadResult;
use Dolphin\SimpleStorage\Exceptions\SimpleStorageException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Facade for Simple Storage Client.
 *
 * @method static HealthStatus health() Check the health status of the storage server @throws SimpleStorageException
 * @method static bool isHealthy() Check if the storage server is reachable
 * @method static UploadResult upload(string $jobId, string $filePath) Upload a ZIP file @throws SimpleStorageException
 * @method static UploadResult uploadContent(string $jobId, string $content) Upload raw binary content @throws SimpleStorageException
 * @method static string download(string $jobId, bool $keep = false) Download a ZIP file as string @throws SimpleStorageException
 * @method static string downloadTo(string $jobId, string $destinationPath, bool $keep = false) Download and save to disk @throws SimpleStorageException
 * @method static bool delete(string $jobId) Delete a file @throws SimpleStorageException
 * @method static bool exists(string $jobId) Check if a file exists
 * @method static Collection<int, FileInfo> list() List all stored files @throws SimpleStorageException
 * @method static string getBaseUrl() Get the configured base URL
 * @method static SimpleStorageInterface setBaseUrl(string $baseUrl) Set a new base URL
 * @method static SimpleStorageInterface setApiKey(string $apiKey) Set a new API key
 *
 * @see \Dolphin\SimpleStorage\SimpleStorageClient
 * @see \Dolphin\SimpleStorage\Contracts\SimpleStorageInterface
 *
 * @mixin \Dolphin\SimpleStorage\SimpleStorageClient
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

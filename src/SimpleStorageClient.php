<?php

declare(strict_types=1);

namespace SimoneBianco\SimpleStorageClient;

use SimoneBianco\SimpleStorageClient\Contracts\SimpleStorageInterface;
use SimoneBianco\SimpleStorageClient\DataTransferObjects\FileInfo;
use SimoneBianco\SimpleStorageClient\DataTransferObjects\HealthStatus;
use SimoneBianco\SimpleStorageClient\DataTransferObjects\UploadResult;
use SimoneBianco\SimpleStorageClient\Exceptions\ConnectionFailedException;
use SimoneBianco\SimpleStorageClient\Exceptions\FileGoneException;
use SimoneBianco\SimpleStorageClient\Exceptions\NotFoundException;
use SimoneBianco\SimpleStorageClient\Exceptions\SimpleStorageException;
use SimoneBianco\SimpleStorageClient\Exceptions\UnauthorizedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Simple Storage Client Service.
 *
 * Provides a clean interface for interacting with the Dolphin Simple Storage Server.
 * Follows Single Responsibility Principle - only handles storage operations.
 */
class SimpleStorageClient implements SimpleStorageInterface
{
    protected string $baseUrl;
    protected string $apiKey;
    protected int $timeout;
    protected int $connectTimeout;
    protected int $retryTimes;
    protected int $retrySleepMs;
    protected bool $verifySsl;

    /**
     * Create a new Simple Storage Client instance.
     */
    public function __construct(array $config = [])
    {
        $this->baseUrl = rtrim($config['base_url'] ?? config('simple-storage.base_url', 'http://localhost:5000'), '/');
        $this->apiKey = $config['api_key'] ?? config('simple-storage.api_key', '');
        $this->timeout = $config['timeout'] ?? config('simple-storage.timeout', 120);
        $this->connectTimeout = $config['connect_timeout'] ?? config('simple-storage.connect_timeout', 10);
        $this->retryTimes = $config['retry']['times'] ?? config('simple-storage.retry.times', 3);
        $this->retrySleepMs = $config['retry']['sleep_ms'] ?? config('simple-storage.retry.sleep_ms', 500);
        $this->verifySsl = $config['verify_ssl'] ?? config('simple-storage.verify_ssl', true);
    }

    /**
     * Create a configured HTTP client.
     */
    protected function httpClient(bool $withAuth = true): PendingRequest
    {
        $client = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->retry($this->retryTimes, $this->retrySleepMs)
            ->withOptions(['verify' => $this->verifySsl])
            ->acceptJson();

        if ($withAuth && $this->apiKey) {
            $client->withToken($this->apiKey);
        }

        return $client;
    }

    /**
     * Handle API response and throw exceptions for errors.
     *
     * @throws SimpleStorageException
     */
    protected function handleResponse(Response $response, string $context = ''): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        match ($response->status()) {
            401 => throw new UnauthorizedException(),
            default => throw SimpleStorageException::fromResponse($response, $context),
        };
    }

    /**
     * {@inheritDoc}
     *
     * @throws SimpleStorageException
     */
    public function health(): HealthStatus
    {
        try {
            $response = $this->httpClient(withAuth: false)->get('/health');

            return HealthStatus::fromArray($this->handleResponse($response, 'Health check failed'));
        } catch (ConnectionException $e) {
            throw new ConnectionFailedException($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function isHealthy(): bool
    {
        try {
            return $this->health()->isHealthy();
        } catch (SimpleStorageException) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws SimpleStorageException
     */
    public function upload(string $jobId, string $filePath): UploadResult
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw SimpleStorageException::fileNotReadable($filePath);
        }

        try {
            $response = $this->httpClient()
                ->attach('file', file_get_contents($filePath), basename($filePath))
                ->post('/upload', ['job_id' => $jobId]);

            return UploadResult::fromArray($this->handleResponse($response, 'Upload failed'));
        } catch (ConnectionException $e) {
            throw new ConnectionFailedException($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws SimpleStorageException
     */
    public function uploadContent(string $jobId, string $content): UploadResult
    {
        try {
            $response = $this->httpClient()
                ->withHeaders([
                    'X-Job-Id' => $jobId,
                    'Content-Type' => 'application/octet-stream',
                ])
                ->withBody($content, 'application/octet-stream')
                ->post('/upload');

            return UploadResult::fromArray($this->handleResponse($response, 'Upload failed'));
        } catch (ConnectionException $e) {
            throw new ConnectionFailedException($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws SimpleStorageException
     */
    public function download(string $jobId, bool $keep = false): string
    {
        try {
            $url = "/download/{$jobId}" . ($keep ? '?keep=true' : '');
            $response = $this->httpClient()->get($url);

            if ($response->successful()) {
                return $response->body();
            }

            match ($response->status()) {
                404 => throw new NotFoundException($jobId),
                410 => throw new FileGoneException($jobId),
                default => throw SimpleStorageException::fromResponse($response, 'Download failed'),
            };
        } catch (ConnectionException $e) {
            throw new ConnectionFailedException($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws SimpleStorageException
     */
    public function downloadTo(string $jobId, string $absoluteDestinationPath, bool $keep = false): string
    {
        $directory = dirname($absoluteDestinationPath);

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw SimpleStorageException::fileNotWritable($absoluteDestinationPath);
        }

        $content = $this->download($jobId, $keep);

        if (file_put_contents($absoluteDestinationPath, $content) === false) {
            throw SimpleStorageException::fileNotWritable($absoluteDestinationPath);
        }

        return $absoluteDestinationPath;
    }

    /**
     * {@inheritDoc}
     *
     * @throws SimpleStorageException
     */
    public function delete(string $jobId): bool
    {
        try {
            $response = $this->httpClient()->delete("/delete/{$jobId}");

            if ($response->successful()) {
                return true;
            }

            if ($response->status() === 404) {
                throw new NotFoundException($jobId);
            }

            throw SimpleStorageException::fromResponse($response, 'Delete failed');
        } catch (ConnectionException $e) {
            throw new ConnectionFailedException($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
     *
     * Note: This method uses a lightweight HEAD request to check existence.
     * It only catches NotFoundException and FileGoneException.
     * Connection errors (ConnectionFailedException) will propagate.
     *
     * @throws ConnectionFailedException If the server is unreachable
     * @throws UnauthorizedException If authentication fails
     * @throws SimpleStorageException For other unexpected errors
     */
    public function exists(string $jobId): bool
    {
        try {
            $response = $this->httpClient()->get("/check/{$jobId}");

            if ($response->successful()) {
                return $response->json('status') === 'exists';
            }

            if ($response->status() === 404) {
                return false;
            }

            $this->handleResponse($response, 'Existence check failed');

            return false;
        } catch (ConnectionException $e) {
            throw new ConnectionFailedException($this->baseUrl, $e);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            if ($e->response->status() === 404) {
                return false;
            }
            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws SimpleStorageException
     */
    public function list(): Collection
    {
        try {
            $response = $this->httpClient()->get('/list');
            $data = $this->handleResponse($response, 'List failed');

            return collect($data['files'] ?? [])
                ->map(fn (array $file) => FileInfo::fromArray($file));
        } catch (ConnectionException $e) {
            throw new ConnectionFailedException($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws SimpleStorageException
     */
    public function cleanup(): array
    {
        try {
            $response = $this->httpClient()->post('/cleanup');

            return $this->handleResponse($response, 'Cleanup failed');
        } catch (ConnectionException $e) {
            throw new ConnectionFailedException($this->baseUrl, $e);
        }
    }

    /**
     * Get the configured base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Set a new base URL (useful for testing).
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');

        return $this;
    }

    /**
     * Set a new API key (useful for testing).
     */
    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }
}

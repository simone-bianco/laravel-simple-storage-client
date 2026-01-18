<?php

declare(strict_types=1);

namespace Dolphin\SimpleStorage;

use Dolphin\SimpleStorage\Contracts\SimpleStorageInterface;
use Dolphin\SimpleStorage\DataTransferObjects\FileInfo;
use Dolphin\SimpleStorage\DataTransferObjects\HealthStatus;
use Dolphin\SimpleStorage\DataTransferObjects\UploadResult;
use Dolphin\SimpleStorage\Exceptions\SimpleStorageException;
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
     */
    protected function handleResponse(Response $response, string $context = ''): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        match ($response->status()) {
            401 => throw SimpleStorageException::unauthorized(),
            404 => throw SimpleStorageException::fromResponse($response, $context),
            410 => throw SimpleStorageException::fromResponse($response, $context),
            default => throw SimpleStorageException::fromResponse($response, $context),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function health(): HealthStatus
    {
        try {
            $response = $this->httpClient(withAuth: false)->get('/health');

            return HealthStatus::fromArray($this->handleResponse($response, 'Health check failed'));
        } catch (ConnectionException $e) {
            throw SimpleStorageException::connectionFailed($this->baseUrl, $e);
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
            throw SimpleStorageException::connectionFailed($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
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
            throw SimpleStorageException::connectionFailed($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
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
                404 => throw SimpleStorageException::notFound($jobId),
                410 => throw SimpleStorageException::fileDeleted($jobId),
                default => throw SimpleStorageException::fromResponse($response, 'Download failed'),
            };
        } catch (ConnectionException $e) {
            throw SimpleStorageException::connectionFailed($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function downloadTo(string $jobId, string $destinationPath, bool $keep = false): string
    {
        $directory = dirname($destinationPath);

        if (!is_dir($directory) && !mkdir($directory, 0755, true)) {
            throw SimpleStorageException::fileNotWritable($destinationPath);
        }

        $content = $this->download($jobId, $keep);

        if (file_put_contents($destinationPath, $content) === false) {
            throw SimpleStorageException::fileNotWritable($destinationPath);
        }

        return $destinationPath;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $jobId): bool
    {
        try {
            $response = $this->httpClient()->delete("/delete/{$jobId}");

            if ($response->successful()) {
                return true;
            }

            if ($response->status() === 404) {
                throw SimpleStorageException::notFound($jobId);
            }

            throw SimpleStorageException::fromResponse($response, 'Delete failed');
        } catch (ConnectionException $e) {
            throw SimpleStorageException::connectionFailed($this->baseUrl, $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function exists(string $jobId): bool
    {
        try {
            $files = $this->list();

            return $files->contains(fn (FileInfo $file) => $file->jobId === $jobId && $file->isAvailable());
        } catch (SimpleStorageException) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function list(): Collection
    {
        try {
            $response = $this->httpClient()->get('/list');
            $data = $this->handleResponse($response, 'List failed');

            return collect($data['files'] ?? [])
                ->map(fn (array $file) => FileInfo::fromArray($file));
        } catch (ConnectionException $e) {
            throw SimpleStorageException::connectionFailed($this->baseUrl, $e);
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

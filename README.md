# Laravel Simple Storage Client

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dolphin/simple-storage-client.svg?style=flat-square)](https://packagist.org/packages/dolphin/simple-storage-client)
[![Total Downloads](https://img.shields.io/packagist/dt/dolphin/simple-storage-client.svg?style=flat-square)](https://packagist.org/packages/dolphin/simple-storage-client)
[![License](https://img.shields.io/packagist/l/dolphin/simple-storage-client.svg?style=flat-square)](https://packagist.org/packages/dolphin/simple-storage-client)

A plug-and-play Laravel package for interacting with the **Dolphin Simple Storage Server**. Upload, download, and manage ZIP files with a clean, fluent API.

## Features

- 🚀 **Plug & Play** - Install, configure, and start using immediately
- 🎭 **Facade Support** - Use `SimpleStorage::upload()` anywhere in your app
- 📦 **DTOs** - Clean data transfer objects for type-safe responses
- 🔄 **Auto-Retry** - Configurable retry logic for failed requests
- 🔐 **Secure** - Bearer token authentication
- 💉 **Dependency Injection** - Interface-based design for easy testing
- ✅ **SOLID Principles** - Clean, maintainable, extensible code

## Requirements

- PHP 8.1+
- Laravel 10.x or 11.x

## Installation

### Step 1: Install via Composer

```bash
composer require dolphin/simple-storage-client
```

### Step 2: Publish Configuration (Optional)

```bash
php artisan vendor:publish --tag=simple-storage-config
```

### Step 3: Configure Environment

Add these variables to your `.env` file:

```env
SIMPLE_STORAGE_URL=http://localhost:5000
SIMPLE_STORAGE_API_KEY=your-api-key-here
```

**That's it!** You're ready to use the client.

---

## Quick Start

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

// Upload a file
$result = SimpleStorage::upload('job-123', '/path/to/file.zip');
echo $result->downloadUrl; // /download/job-123

// Download a file
$content = SimpleStorage::download('job-123');
file_put_contents('/path/to/output.zip', $content);

// Or download directly to disk
SimpleStorage::downloadTo('job-123', '/path/to/output.zip');

// Delete a file
SimpleStorage::delete('job-123');

// Check health
if (SimpleStorage::isHealthy()) {
    echo 'Server is up!';
}
```

---

## Configuration

All configuration options are available in `config/simple-storage.php`:

| Option            | Environment Variable             | Default                 | Description                  |
| ----------------- | -------------------------------- | ----------------------- | ---------------------------- |
| `base_url`        | `SIMPLE_STORAGE_URL`             | `http://localhost:5000` | Storage server URL           |
| `api_key`         | `SIMPLE_STORAGE_API_KEY`         | `''`                    | API key for authentication   |
| `timeout`         | `SIMPLE_STORAGE_TIMEOUT`         | `120`                   | Request timeout (seconds)    |
| `connect_timeout` | `SIMPLE_STORAGE_CONNECT_TIMEOUT` | `10`                    | Connection timeout (seconds) |
| `retry.times`     | `SIMPLE_STORAGE_RETRY_TIMES`     | `3`                     | Number of retry attempts     |
| `retry.sleep_ms`  | `SIMPLE_STORAGE_RETRY_SLEEP_MS`  | `500`                   | Delay between retries (ms)   |
| `verify_ssl`      | `SIMPLE_STORAGE_VERIFY_SSL`      | `true`                  | Verify SSL certificates      |

### Example Configuration

```php
// config/simple-storage.php

return [
    'base_url' => env('SIMPLE_STORAGE_URL', 'http://localhost:5000'),
    'api_key' => env('SIMPLE_STORAGE_API_KEY', ''),
    'timeout' => env('SIMPLE_STORAGE_TIMEOUT', 120),
    'connect_timeout' => env('SIMPLE_STORAGE_CONNECT_TIMEOUT', 10),
    'retry' => [
        'times' => env('SIMPLE_STORAGE_RETRY_TIMES', 3),
        'sleep_ms' => env('SIMPLE_STORAGE_RETRY_SLEEP_MS', 500),
    ],
    'verify_ssl' => env('SIMPLE_STORAGE_VERIFY_SSL', true),
];
```

---

## Storage Driver Usage

You can use the Simple Storage Server as a standard Laravel Storage disk.

### 1. Configure `filesystems.php`

Add the following to your `config/filesystems.php` disks array:

```php
'disks' => [
    // ...
    'simple' => [
        'driver' => 'simple-storage',
        // Optional: override global config
        // 'base_url' => env('SIMPLE_STORAGE_URL'),
        // 'api_key' => env('SIMPLE_STORAGE_API_KEY'),
    ],
],
```

### 2. Use the Storage Facade

Now you can use standard Storage methods:

```php
use Illuminate\Support\Facades\Storage;

// Upload file
Storage::disk('simple')->put('job-123', 'file content');

// Check existence
if (Storage::disk('simple')->exists('job-123')) {
    // ...
}

// Get content
$content = Storage::disk('simple')->get('job-123');

// Delete
Storage::disk('simple')->delete('job-123');

// Get file size
$size = Storage::disk('simple')->size('job-123');

// Get last modified timestamp
$time = Storage::disk('simple')->lastModified('job-123');
```

> **Note:** Directory operations (`makeDirectory`, `deleteDirectory`) are not fully supported as the storage is flat, but `deleteDirectory` will attempt to delete all files matching the prefix.

---

## API Reference

### Health Check

#### `health(): HealthStatus`

Get detailed health status of the storage server.

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

$status = SimpleStorage::health();

echo $status->status;    // "ok"
echo $status->service;   // "dolphin-storage-server"
echo $status->timestamp; // "2024-01-18T15:30:00.123456"
```

#### `isHealthy(): bool`

Quick check if the server is reachable and healthy.

```php
if (SimpleStorage::isHealthy()) {
    // Server is up
}
```

---

### Upload Operations

#### `upload(string $jobId, string $filePath): UploadResult`

Upload a ZIP file from disk.

**Parameters:**

- `$jobId` - Unique identifier for this job
- `$filePath` - Absolute path to the ZIP file

**Returns:** `UploadResult` DTO

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

$result = SimpleStorage::upload('job-123', storage_path('app/results.zip'));

if ($result->isSuccessful()) {
    echo "Uploaded {$result->fileSize} bytes";
    echo "Download URL: {$result->downloadUrl}";
}
```

#### `uploadContent(string $jobId, string $content): UploadResult`

Upload raw binary content directly.

**Parameters:**

- `$jobId` - Unique identifier for this job
- `$content` - Raw binary content

**Returns:** `UploadResult` DTO

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

// Create a ZIP in memory
$zip = new ZipArchive();
$tempFile = tempnam(sys_get_temp_dir(), 'zip');
$zip->open($tempFile, ZipArchive::CREATE);
$zip->addFromString('data.json', json_encode(['key' => 'value']));
$zip->close();

$result = SimpleStorage::uploadContent('job-456', file_get_contents($tempFile));
unlink($tempFile);
```

---

### Download Operations

#### `download(string $jobId, bool $keep = false): string`

Download file content as a string.

**Parameters:**

- `$jobId` - The job identifier
- `$keep` - If `true`, don't delete the file after download (default: `false`)

**Returns:** Raw binary content

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

// Download and auto-delete from server
$content = SimpleStorage::download('job-123');

// Download but keep on server
$content = SimpleStorage::download('job-123', keep: true);

file_put_contents('/path/to/output.zip', $content);
```

#### `downloadTo(string $jobId, string $destinationPath, bool $keep = false): string`

Download file directly to disk.

**Parameters:**

- `$jobId` - The job identifier
- `$destinationPath` - Where to save the file
- `$keep` - If `true`, don't delete the file after download

**Returns:** Full path to the saved file

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

$savedPath = SimpleStorage::downloadTo(
    'job-123',
    storage_path('app/downloads/result.zip')
);

echo "Saved to: {$savedPath}";
```

---

### Delete Operation

#### `delete(string $jobId): bool`

Manually delete a file from the server.

**Parameters:**

- `$jobId` - The job identifier

**Returns:** `true` if deletion was successful

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

try {
    SimpleStorage::delete('job-123');
    echo 'File deleted successfully';
} catch (SimpleStorageException $e) {
    echo "Delete failed: {$e->getMessage()}";
}
```

---

### File Existence Check

#### `exists(string $jobId): bool`

Check if a file exists on the server.

**Parameters:**

- `$jobId` - The job identifier

**Returns:** `true` if the file exists and is not deleted

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

if (SimpleStorage::exists('job-123')) {
    $content = SimpleStorage::download('job-123');
}
```

---

### List Files

#### `list(): Collection<FileInfo>`

Get a collection of all stored files.

**Returns:** `Collection` of `FileInfo` DTOs

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

$files = SimpleStorage::list();

foreach ($files as $file) {
    echo "Job: {$file->jobId}\n";
    echo "Size: {$file->fileSize} bytes\n";
    echo "Uploaded: {$file->uploadedAt->format('Y-m-d H:i:s')}\n";
    echo "Downloaded: " . ($file->downloadedAt?->format('Y-m-d H:i:s') ?? 'Never') . "\n";
    echo "Deleted: " . ($file->deleted ? 'Yes' : 'No') . "\n";
    echo "---\n";
}

// Filter available files
$availableFiles = $files->filter(fn ($file) => $file->isAvailable());

// Get files that were downloaded
$downloadedFiles = $files->filter(fn ($file) => $file->hasBeenDownloaded());
```

---

## Data Transfer Objects (DTOs)

### HealthStatus

```php
final readonly class HealthStatus
{
    public string $status;    // "ok" or error status
    public string $service;   // "dolphin-storage-server"
    public string $timestamp; // ISO 8601 timestamp

    public function isHealthy(): bool;
    public function toArray(): array;
}
```

### UploadResult

```php
final readonly class UploadResult
{
    public string $status;      // "uploaded"
    public string $jobId;       // The job identifier
    public int $fileSize;       // File size in bytes
    public string $downloadUrl; // Relative download URL

    public function isSuccessful(): bool;
    public function toArray(): array;
}
```

### FileInfo

```php
final readonly class FileInfo
{
    public string $jobId;
    public int $fileSize;
    public Carbon $uploadedAt;
    public ?Carbon $downloadedAt;
    public bool $deleted;

    public function isAvailable(): bool;
    public function hasBeenDownloaded(): bool;
    public function toArray(): array;
}
```

---

## Error Handling

All operations throw `SimpleStorageException` on failure:

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;
use Dolphin\SimpleStorage\Exceptions\SimpleStorageException;

try {
    $content = SimpleStorage::download('non-existent-job');
} catch (SimpleStorageException $e) {
    echo "Error: {$e->getMessage()}";
    echo "HTTP Status: {$e->getCode()}";

    // Access the HTTP response if available
    if ($response = $e->getResponse()) {
        echo "Response body: " . $response->body();
    }
}
```

### Exception Types

| Exception         | HTTP Code | Description                       |
| ----------------- | --------- | --------------------------------- |
| Connection failed | 0         | Cannot reach the server           |
| Unauthorized      | 401       | Invalid or missing API key        |
| Not found         | 404       | Job ID not found                  |
| File deleted      | 410       | File was already deleted          |
| File not readable | 0         | Cannot read local file for upload |
| File not writable | 0         | Cannot write to destination path  |

---

## Dependency Injection

You can inject the interface instead of using the Facade:

```php
use Dolphin\SimpleStorage\Contracts\SimpleStorageInterface;

class PdfProcessorService
{
    public function __construct(
        private SimpleStorageInterface $storage
    ) {}

    public function storeResult(string $jobId, string $zipPath): void
    {
        $result = $this->storage->upload($jobId, $zipPath);

        if (!$result->isSuccessful()) {
            throw new \RuntimeException('Upload failed');
        }
    }
}
```

---

## Testing with Mock

For testing, you can easily mock the interface:

```php
use Dolphin\SimpleStorage\Contracts\SimpleStorageInterface;
use Dolphin\SimpleStorage\DataTransferObjects\UploadResult;

it('stores processing results', function () {
    $mockStorage = Mockery::mock(SimpleStorageInterface::class);

    $mockStorage->shouldReceive('upload')
        ->with('job-123', '/path/to/file.zip')
        ->andReturn(new UploadResult(
            status: 'uploaded',
            jobId: 'job-123',
            fileSize: 12345,
            downloadUrl: '/download/job-123'
        ));

    app()->instance(SimpleStorageInterface::class, $mockStorage);

    // Your test code here
});
```

---

## Using Without Facade

If you prefer not to use Facades:

```php
use Dolphin\SimpleStorage\SimpleStorageClient;

// Using dependency injection
public function __construct(
    private SimpleStorageClient $client
) {}

// Or manual instantiation with custom config
$client = new SimpleStorageClient([
    'base_url' => 'https://custom-storage.example.com',
    'api_key' => 'custom-key',
    'timeout' => 60,
]);

$result = $client->upload('job-123', '/path/to/file.zip');
```

---

## Advanced Usage

### Custom Client Configuration at Runtime

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

// Change the base URL temporarily
SimpleStorage::setBaseUrl('https://backup-storage.example.com');

// Change the API key temporarily
SimpleStorage::setApiKey('different-key');
```

### Working with Callbacks

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

// Upload and handle result
$result = SimpleStorage::upload('job-123', $zipPath);

if ($result->isSuccessful()) {
    // Notify external service
    Http::post('https://callback.example.com/webhook', [
        'job_id' => $result->jobId,
        'download_url' => config('simple-storage.base_url') . $result->downloadUrl,
    ]);
}
```

### Batch Operations

```php
use Dolphin\SimpleStorage\Facades\SimpleStorage;

$jobIds = ['job-1', 'job-2', 'job-3'];

// Download all files
$results = collect($jobIds)->mapWithKeys(function ($jobId) {
    try {
        return [$jobId => SimpleStorage::download($jobId, keep: true)];
    } catch (SimpleStorageException $e) {
        return [$jobId => null];
    }
});

// Delete all files
foreach ($jobIds as $jobId) {
    try {
        SimpleStorage::delete($jobId);
    } catch (SimpleStorageException) {
        // Log or ignore
    }
}
```

---

## Server API Endpoints Reference

The Simple Storage Server exposes these endpoints:

| Endpoint                       | Method | Auth | Description                  |
| ------------------------------ | ------ | ---- | ---------------------------- |
| `/health`                      | GET    | ❌   | Health check                 |
| `/upload`                      | POST   | ✅   | Upload ZIP file              |
| `/download/{job_id}`           | GET    | ✅   | Download ZIP file            |
| `/download/{job_id}?keep=true` | GET    | ✅   | Download without auto-delete |
| `/delete/{job_id}`             | DELETE | ✅   | Delete a file                |
| `/list`                        | GET    | ✅   | List all files               |

---

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- Built with [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools)

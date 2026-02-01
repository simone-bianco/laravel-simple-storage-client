<?php

declare(strict_types=1);

namespace SimoneBianco\SimpleStorageClient;

use SimoneBianco\SimpleStorageClient\Contracts\SimpleStorageInterface;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

/**
 * Service Provider for Simple Storage Client.
 *
 * Registers the Simple Storage client service and facade with Laravel.
 * Uses Spatie's PackageServiceProvider for clean, maintainable package registration.
 */
class SimpleStorageServiceProvider extends PackageServiceProvider
{
    /**
     * Configure the package.
     */
    public function configurePackage(Package $package): void
    {
        $package
            ->name('simple-storage')
            ->hasConfigFile();
    }

    /**
     * Register any package services.
     */
    public function packageRegistered(): void
    {
        // Bind the interface to the concrete implementation (Dependency Inversion Principle)
        $this->app->singleton(SimpleStorageInterface::class, function ($app) {
            return new SimpleStorageClient([
                'base_url' => config('simple-storage.base_url'),
                'api_key' => config('simple-storage.api_key'),
                'timeout' => config('simple-storage.timeout'),
                'connect_timeout' => config('simple-storage.connect_timeout'),
                'retry' => [
                    'times' => config('simple-storage.retry.times'),
                    'sleep_ms' => config('simple-storage.retry.sleep_ms'),
                ],
                'verify_ssl' => config('simple-storage.verify_ssl'),
            ]);
        });

        // Alias for convenience
        $this->app->alias(SimpleStorageInterface::class, 'simple-storage');
        $this->app->alias(SimpleStorageInterface::class, SimpleStorageClient::class);
    }

    /**
     * Bootstrap any package services.
     */
    public function packageBooted(): void
    {
        Storage::extend('simple-storage', function ($app, $config) {
            $client = new SimpleStorageClient([
                'base_url' => $config['base_url'] ?? config('simple-storage.base_url'),
                'api_key' => $config['api_key'] ?? config('simple-storage.api_key'),
                'timeout' => $config['timeout'] ?? config('simple-storage.timeout'),
                'connect_timeout' => $config['connect_timeout'] ?? config('simple-storage.connect_timeout'),
                'retry' => [
                    'times' => $config['retry']['times'] ?? config('simple-storage.retry.times'),
                    'sleep_ms' => $config['retry']['sleep_ms'] ?? config('simple-storage.retry.sleep_ms'),
                ],
                'verify_ssl' => $config['verify_ssl'] ?? config('simple-storage.verify_ssl'),
            ]);

            $adapter = new SimpleStorageAdapter($client);

            return new Filesystem($adapter, $config);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            SimpleStorageInterface::class,
            SimpleStorageClient::class,
            'simple-storage',
        ];
    }
}

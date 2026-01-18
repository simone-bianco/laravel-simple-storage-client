<?php

declare(strict_types=1);

namespace Dolphin\SimpleStorage\DataTransferObjects;

/**
 * Represents the health status of the Simple Storage Server.
 */
final readonly class HealthStatus
{
    public function __construct(
        public string $status,
        public string $service,
        public string $timestamp,
    ) {}

    /**
     * Create from API response array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? 'unknown',
            service: $data['service'] ?? 'unknown',
            timestamp: $data['timestamp'] ?? now()->toISOString(),
        );
    }

    /**
     * Check if the server is healthy.
     */
    public function isHealthy(): bool
    {
        return $this->status === 'ok';
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'service' => $this->service,
            'timestamp' => $this->timestamp,
        ];
    }
}

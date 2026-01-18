<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Simple Storage Server Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL of your Dolphin Simple Storage Server instance.
    | Do not include a trailing slash.
    |
    | Example: https://storage.yourdomain.com
    |
    */
    'base_url' => env('SIMPLE_STORAGE_URL', 'http://localhost:5000'),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | The API key used to authenticate with the Simple Storage Server.
    | This should be kept secret and never committed to version control.
    |
    */
    'api_key' => env('SIMPLE_STORAGE_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Timeout
    |--------------------------------------------------------------------------
    |
    | The timeout in seconds for HTTP requests to the storage server.
    | Increase this value for large file uploads.
    |
    */
    'timeout' => env('SIMPLE_STORAGE_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Connection Timeout
    |--------------------------------------------------------------------------
    |
    | The connection timeout in seconds for establishing a connection
    | to the storage server.
    |
    */
    'connect_timeout' => env('SIMPLE_STORAGE_CONNECT_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic retries for failed requests.
    |
    */
    'retry' => [
        'times' => env('SIMPLE_STORAGE_RETRY_TIMES', 3),
        'sleep_ms' => env('SIMPLE_STORAGE_RETRY_SLEEP_MS', 500),
    ],

    /*
    |--------------------------------------------------------------------------
    | Verify SSL
    |--------------------------------------------------------------------------
    |
    | Whether to verify the SSL certificate of the storage server.
    | Should be true in production, can be false for local development.
    |
    */
    'verify_ssl' => env('SIMPLE_STORAGE_VERIFY_SSL', true),
];

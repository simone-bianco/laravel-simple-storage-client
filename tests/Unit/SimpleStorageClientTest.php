<?php

namespace SimoneBianco\SimpleStorageClient\Tests\Unit;

use SimoneBianco\SimpleStorageClient\SimpleStorageClient;
use SimoneBianco\SimpleStorageClient\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class SimpleStorageClientTest extends TestCase
{
    public function test_exists_calls_correct_endpoint()
    {
        Http::fake([
            '*/check/job-123' => Http::response(['status' => 'exists'], 200),
        ]);

        $client = new SimpleStorageClient(['base_url' => 'http://test.com']);
        
        $this->assertTrue($client->exists('job-123'));
        
        Http::assertSent(function ($request) {
            return $request->url() === 'http://test.com/check/job-123' &&
                   $request->method() === 'GET';
        });
    }

    public function test_cleanup_calls_correct_endpoint()
    {
        Http::fake([
            '*/cleanup' => Http::response(['deleted_count' => 5], 200),
        ]);

        $client = new SimpleStorageClient(['base_url' => 'http://test.com']);
        
        $result = $client->cleanup();
        
        $this->assertEquals(['deleted_count' => 5], $result);
        
        Http::assertSent(function ($request) {
            return $request->url() === 'http://test.com/cleanup' &&
                   $request->method() === 'POST';
        });
    }
}

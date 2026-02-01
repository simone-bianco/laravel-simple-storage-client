<?php

namespace SimoneBianco\SimpleStorageClient\Tests\Integration;

use SimoneBianco\SimpleStorageClient\SimpleStorageClient;
use SimoneBianco\SimpleStorageClient\Tests\TestCase;

class LiveServerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Skip if not running integration tests specifically requested? 
        // We will just run this file explicitly.
    }

    public function test_live_lifecycle()
    {
        $config = [
            'base_url' => 'http://135.181.87.5:5000/api',
            'api_key' => 'vvqfvkjio2349JMCQNjf9j9i2gMBKSNVJAqf22',
            'verify_ssl' => false,
            'timeout' => 10,
        ];
        
        $client = new SimpleStorageClient($config);
        
        echo "\n\n--- Live Server Integration Test ---\n";
        
        // 1. Health
        $isHealthy = $client->isHealthy();
        $this->assertTrue($isHealthy, 'Server should be healthy');
        echo "1. Health: OK\n";
        
        $jobId = 'test-pest-' . uniqid();
        $content = "Pest Integration Test " . date('Y-m-d H:i:s');
        
        // 2. Upload
        $result = $client->uploadContent($jobId, $content);
        $this->assertNotNull($result->downloadUrl);
        echo "2. Upload: OK ({$result->fileSize} bytes)\n";
        
        // 3. Exists
        $exists = $client->exists($jobId);
        $this->assertTrue($exists, 'File should exist after upload');
        echo "3. Exists: OK\n";
        
        // 4. Download
        $downloaded = $client->download($jobId, true);
        $this->assertEquals($content, $downloaded, 'Downloaded content should match');
        echo "4. Download: OK\n";
        
        // 5. Delete
        try {
            $deleted = $client->delete($jobId);
            $this->assertTrue($deleted, 'Delete should succeed');
            echo "5. Delete: OK\n";
        } catch (\Exception $e) {
            echo "5. Delete FAILED: " . $e->getMessage() . "\n";
            throw $e;
        }
        
        // 6. Check gone
        try {
            $existsAfter = $client->exists($jobId);
            if ($existsAfter) {
                echo "6. Exists (after delete): FAILED (Still exists)\n";
            } else {
                echo "6. Exists (after delete): OK\n";
            }
            $this->assertFalse($existsAfter, 'File should not exist after delete');
        } catch (\Exception $e) {
             echo "6. Check Gone FAILED: " . $e->getMessage() . "\n";
             throw $e;
        }
        
        // 7. Cleanup
        try {
            $client->cleanup();
            echo "7. Cleanup: OK\n";
        } catch (\Exception $e) {
            $this->fail("Cleanup failed: " . $e->getMessage());
        }
        
        echo "--- End Test ---\n";
    }
}

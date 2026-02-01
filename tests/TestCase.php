<?php

namespace SimoneBianco\SimpleStorageClient\Tests;

use SimoneBianco\SimpleStorageClient\SimpleStorageServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            SimpleStorageServiceProvider::class,
        ];
    }
}

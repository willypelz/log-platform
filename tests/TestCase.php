<?php

namespace Willypelz\LogPlatform\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Willypelz\LogPlatform\LogPlatformServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LogPlatformServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}


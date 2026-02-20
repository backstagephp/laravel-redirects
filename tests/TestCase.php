<?php

namespace Backstage\Redirects\Laravel\Tests;

use Backstage\Redirects\Laravel\RedirectServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Backstage\\Redirects\\Laravel\\Database\\Factories\\' . class_basename($modelName) . 'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            RedirectServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        // Use the default Orchestra Testbench database setup
        // This respects DB_CONNECTION and DB_DATABASE from phpunit.xml
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}

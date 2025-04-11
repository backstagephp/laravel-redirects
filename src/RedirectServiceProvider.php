<?php

namespace Backstage\Redirects\Laravel;

use Illuminate\Routing\Router;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class RedirectServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-redirects')
            ->hasConfigFile()
            ->hasMigration('create_redirects_table');
    }

    public function packageBooted()
    {
        /** @var \Illuminate\Routing\Router $router */
        $kernel = $this->app->make(Router::class);

        foreach (config('redirects.middleware') as $middleware) {
            $kernel->pushMiddlewareToGroup('web', $middleware);
        }
    }
}

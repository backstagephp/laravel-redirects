<?php

namespace Backstage\Redirects\Laravel;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Backstage\Redirects\Laravel\Http\Middleware;

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

        $kernel->pushMiddlewareToGroup('web', Middleware\BackstageRedirects::class);
    }
}

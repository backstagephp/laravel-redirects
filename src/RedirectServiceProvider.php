<?php

namespace Backstage\Redirects\Laravel;

use Backstage\Redirects\Laravel\Events\UrlHasChanged;
use Backstage\Redirects\Laravel\Listeners\RedirectOldUrlToNewUrl;
use Backstage\Redirects\Laravel\Models\Redirect;
use Backstage\Redirects\Laravel\Observers\RedirectObserver;
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

        $this->app['events']->listen(
            UrlHasChanged::class,
            RedirectOldUrlToNewUrl::class
        );

        $modelClass = config('redirects.model', Redirect::class);
        $modelClass::observe(RedirectObserver::class);
    }
}

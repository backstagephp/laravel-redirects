<?php

namespace Backstage\Redirects\Laravel;

use Backstage\Redirects\Laravel\Events\UrlHasChanged;
use Backstage\Redirects\Laravel\Listeners\RedirectOldUrlToNewUrl;
use Backstage\Redirects\Laravel\Models\Redirect;
use Backstage\Redirects\Laravel\Observers\RedirectObserver;
use Illuminate\Contracts\Http\Kernel;
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
        // TODO: Once Laravel 10 support is dropped, consider using the new
        // bootstrap/app.php middleware configuration approach instead.
        // See: https://laravel.com/docs/11.x/middleware#registering-middleware
        /** @var \Illuminate\Foundation\Http\Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        foreach (config('redirects.middleware', []) as $middleware) {
            $kernel->appendMiddlewareToGroup('web', $middleware);
        }

        $this->app['events']->listen(
            UrlHasChanged::class,
            RedirectOldUrlToNewUrl::class
        );

        $modelClass = config('redirects.model', Redirect::class);
        $modelClass::observe(RedirectObserver::class);
    }
}

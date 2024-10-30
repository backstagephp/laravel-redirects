<?php

namespace Vormkracht10\Redirects;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vormkracht10\Redirects\Commands\RedirectsCommand;

class RedirectsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-redirects')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_redirects_table');
    }
}

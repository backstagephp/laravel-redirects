<?php

namespace Vormkracht10\Redirects;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vormkracht10\Redirects\Commands\RedirectsCommand;

class RedirectsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class RedirectsServiceProvider a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-redirects')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_redirects_table')
            ->hasCommand(RedirectsCommand::class);
    }
}

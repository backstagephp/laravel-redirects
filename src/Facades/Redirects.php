<?php

namespace Backstage\Redirects\Laravel\Facades;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection forSite(?Model $site = null)
 *
 * @see \Backstage\Redirects\Laravel\Redirects
 */
class Redirects extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Backstage\Redirects\Laravel\Redirects::class;
    }
}

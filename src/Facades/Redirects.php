<?php

namespace Vormkracht10\Redirects\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Vormkracht10\Redirects\Redirects
 */
class Redirects extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Vormkracht10\Redirects\Redirects::class;
    }
}

<?php

namespace Backstage\Redirects\Laravel\Http\Middleware;

use Backstage\Redirects\Laravel\Http\Middleware\Concerns\SkipMethod;
use Backstage\Redirects\Laravel\Http\Middleware\HttpRedirects;
use Backstage\Redirects\Laravel\Http\Middleware\StrictRedirects;
use Backstage\Redirects\Laravel\Http\Middleware\WildRedirects;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Pipeline;

class BackstageRedirects
{
    use SkipMethod;

    /**
     * Handle an incoming request.
     */
    public function handleNonPost(Request $request, Closure $next)
    {
        /**
         * @var \Illuminate\Http\Request $request
         */
        $request = Pipeline::through(config('redirects.middlware', [
            new StrictRedirects,
            new HttpRedirects,
            new WildRedirects
        ]))
            ->send($request)
            ->thenReturn();
            
        return $next($request);
    }
}

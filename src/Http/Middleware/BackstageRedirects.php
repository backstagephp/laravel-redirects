<?php

namespace Backstage\Redirects\Laravel\Http\Middleware;

use Backstage\Redirects\Laravel\Http\Middleware\Concerns\SkipMethod;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Pipeline;
use Backstage\Redirects\Laravel\Http\Middleware\HttpRedirects;
use Backstage\Redirects\Laravel\Http\Middleware\WildRedirects;
use Backstage\Redirects\Laravel\Http\Middleware\StrictRedirects;

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
        $response = Pipeline::through(config('redirects.middlware', [
            new StrictRedirects,
            new HttpRedirects,
            new WildRedirects,
        ]))
            ->send($request)
            ->thenReturn();

        if ($response instanceof RedirectResponse) {
            return $response;
        }

        return $next($request);
    }
}

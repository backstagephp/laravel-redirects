<?php

namespace Backstage\Redirects\Laravel\Http\Middleware;

use Backstage\Redirects\Laravel\Http\Middleware\Concerns\SkipMethod;
use Closure;
use Illuminate\Http\RedirectResponse;
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

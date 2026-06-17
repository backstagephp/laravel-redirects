<?php

namespace Backstage\Redirects\Laravel\Http\Middleware;

use Backstage\Redirects\Laravel\Facades\Redirects;
use Backstage\Redirects\Laravel\Http\Middleware\Concerns\SkipMethod;
use Backstage\Redirects\Laravel\Models\Redirect;
use Closure;
use Illuminate\Http\Request;

class WildRedirects
{
    use SkipMethod;

    public function handleNonPost(Request $request, Closure $next)
    {
        // Get current site
        $currentSite = $request->site();

        /**
         * @var Redirect|null $checker
         */
        $checker = Redirects::forSite($currentSite)
            ->firstWhere(function (Redirect $redirect) use ($request) {
                return str($request->fullUrl())->contains($redirect->source);
            });

        if (! $checker) {
            return $next($request);
        }

        return $checker->redirect($request);
    }
}

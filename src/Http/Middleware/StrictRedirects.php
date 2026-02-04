<?php

namespace Backstage\Redirects\Laravel\Http\Middleware;

use Backstage\Redirects\Laravel\Http\Middleware\Concerns\SkipMethod;
use Backstage\Redirects\Laravel\Models\Redirect;
use Closure;
use Illuminate\Http\Request;

class StrictRedirects
{
    use SkipMethod;

    public function handleNonPost(Request $request, Closure $next)
    {
        $currentUrl = str($request->url())
            ->replace(['http://', 'https://'], '')
            ->replace(['www.'], '')
            ->rtrim('/')
            ->toString();

        $redirect = Redirect::query()
            ->whereRaw("TRIM(TRAILING '/' FROM REPLACE(REPLACE(source, 'https://', ''), 'http://', '')) = ?", [$currentUrl])
            ->first();

        if (! $redirect) {
            return $next($request);
        }

        return $redirect->redirect($request);
    }
}

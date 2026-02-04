<?php

namespace Backstage\Redirects\Laravel\Http\Middleware;

use Backstage\Redirects\Laravel\Http\Middleware\Concerns\SkipMethod;
use Backstage\Redirects\Laravel\Models\Redirect;
use Closure;
use Illuminate\Http\Request;

class HttpRedirects
{
    use SkipMethod;

    public function handleNonPost(Request $request, Closure $next)
    {
        $currentUrl = str($request->fullUrl())
            ->replace(['http://', 'https://'], '')
            ->replace(['www.'], '')
            ->rtrim('/')
            ->toString();

        $currentPath = str($request->getRequestUri())
            ->rtrim('/')
            ->toString();

        // Match relative paths against path only, full URLs against full URL
        $redirect = Redirect::query()
            ->where(function ($query) use ($currentUrl, $currentPath) {
                $query->where(function ($q) use ($currentPath) {
                    $q->whereRaw("source LIKE '/%'")
                        ->whereRaw("TRIM(TRAILING '/' FROM source) = ?", [$currentPath]);
                })
                    ->orWhere(function ($q) use ($currentUrl) {
                        $q->whereRaw("source NOT LIKE '/%'")
                            ->whereRaw("TRIM(TRAILING '/' FROM REPLACE(REPLACE(source, 'https://', ''), 'http://', '')) = ?", [$currentUrl]);
                    });
            })
            ->first();

        if (! $redirect) {
            return $next($request);
        }

        return $redirect->redirect($request);
    }
}

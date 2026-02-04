<?php

namespace Backstage\Redirects\Laravel\Http\Middleware;

use Backstage\Redirects\Laravel\Http\Middleware\Concerns\SkipMethod;
use Backstage\Redirects\Laravel\Models\Redirect;
use Closure;
use Illuminate\Http\Request;

class WildRedirects
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

        // Wild redirects match path prefixes (not substrings)
        // /test matches /test, /test/, /test/foo but NOT /testcase or /testing
        $redirect = Redirect::query()
            ->where(function ($query) use ($currentUrl, $currentPath) {
                // Match relative paths against path only (prefix match)
                $query->where(function ($q) use ($currentPath) {
                    $q->whereRaw("source LIKE '/%'")
                        ->where(function ($q2) use ($currentPath) {
                            // Match exact path or path with subpaths
                            $q2->whereRaw("? = TRIM(TRAILING '/' FROM source)", [$currentPath])
                                ->orWhereRaw("? LIKE CONCAT(TRIM(TRAILING '/' FROM source), '/%')", [$currentPath]);
                        })
                        ->whereRaw("? NOT LIKE CONCAT(TRIM(TRAILING '/' FROM destination), '%')", [$currentPath]);
                })
                // Match full URLs against full URL (prefix match)
                ->orWhere(function ($q) use ($currentUrl) {
                    $q->whereRaw("source NOT LIKE '/%'")
                        ->where(function ($q2) use ($currentUrl) {
                            $q2->whereRaw("? = TRIM(TRAILING '/' FROM REPLACE(REPLACE(source, 'https://', ''), 'http://', ''))", [$currentUrl])
                                ->orWhereRaw("? LIKE CONCAT(TRIM(TRAILING '/' FROM REPLACE(REPLACE(source, 'https://', ''), 'http://', '')), '/%')", [$currentUrl]);
                        })
                        ->whereRaw("? NOT LIKE CONCAT(TRIM(TRAILING '/' FROM REPLACE(REPLACE(destination, 'https://', ''), 'http://', '')), '%')", [$currentUrl]);
                });
            })
            ->first();

        if (! $redirect) {
            return $next($request);
        }

        return $redirect->redirect($request);
    }
}

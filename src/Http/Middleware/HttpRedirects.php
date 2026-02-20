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
        /**
         * @var \Backstage\Redirects\Laravel\Models\Redirect|null $checker
         */
        $checker = Redirect::all()
            ->firstWhere(function (Redirect $redirect) use ($request) {
                $requestUrl = str($request->fullUrl())
                    ->replace(['http://', 'https://'], '')
                    ->replace(['www.'], '');

                $requestPath = $request->path();
                $requestPathWithSlash = '/' . ltrim($requestPath, '/');

                $redirectSource = str($redirect->source)
                    ->replace(['http://', 'https://'], '')
                    ->replace(['www.'], '');

                // Match full URL or just the path (using contains for flexible matching)
                return $requestUrl->contains($redirectSource)
                    || str($requestPath)->contains($redirect->source)
                    || str($requestPathWithSlash)->contains($redirect->source);
            });

        if (! $checker) {
            return $next($request);
        }

        return $checker->redirect($request);
    }
}

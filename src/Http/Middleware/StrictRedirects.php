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
        $requestUrl = str($request->url())
            ->replace(['http://', 'https://'], '')
            ->replace(['www.'], '');

        $requestPath = $request->path();
        $requestPathWithSlash = '/'.ltrim($requestPath, '/');

        $modelClass = config('redirects.model', Redirect::class);

        // Get current site
        $currentSite = $request->site();

        /**
         * @var Redirect|null $checker
         */
        $checker = $modelClass::query()
            ->when($currentSite, fn ($query) => $query->where(function ($q) use ($currentSite) {
                $q->where('site_id', $currentSite->ulid)->orWhereNull('site_id');
            }))
            ->get()
            ->first(function (Redirect $redirect) use ($requestUrl, $requestPath, $requestPathWithSlash) {
                $redirectSource = str($redirect->source)
                    ->replace(['http://', 'https://'], '')
                    ->replace(['www.'], '');

                // Match full URL or just the path
                return $requestUrl->exactly($redirectSource)
                    || $requestPath === $redirect->source
                    || $requestPathWithSlash === $redirect->source;
            });

        if (! $checker) {
            return $next($request);
        }

        return $checker->redirect($request);
    }
}

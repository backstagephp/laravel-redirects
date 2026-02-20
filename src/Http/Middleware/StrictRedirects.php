<?php

namespace Backstage\Redirects\Laravel\Http\Middleware;

use Backstage\Redirects\Laravel\Http\Middleware\Concerns\SkipMethod;
use Backstage\Redirects\Laravel\Models\Redirect;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StrictRedirects
{
    use SkipMethod;

    public function handleNonPost(Request $request, Closure $next)
    {
        $requestUrl = str($request->url())
            ->replace(['http://', 'https://'], '')
            ->replace(['www.'], '');

        $requestPath = $request->path();
        $requestPathWithSlash = '/' . ltrim($requestPath, '/');

        $modelClass = config('redirects.model', Redirect::class);

        Log::info('StrictRedirects: Checking for redirect', [
            'path' => $requestPath,
            'url' => (string) $requestUrl,
        ]);

        /**
         * @var \Backstage\Redirects\Laravel\Models\Redirect|null $checker
         */
        $checker = $modelClass::query()
            ->get()
            ->first(function (Redirect $redirect) use ($requestUrl, $requestPath, $requestPathWithSlash) {
                $redirectSource = str($redirect->source)
                    ->replace(['http://', 'https://'], '')
                    ->replace(['www.'], '');

                // Match full URL or just the path
                $matches = $requestUrl->exactly($redirectSource)
                    || $requestPath === $redirect->source
                    || $requestPathWithSlash === $redirect->source;

                if ($matches) {
                    Log::info('StrictRedirects: Match found', [
                        'redirect_id' => $redirect->ulid,
                        'source' => $redirect->source,
                        'destination' => $redirect->destination,
                    ]);
                }

                return $matches;
            });

        if (! $checker) {
            Log::info('StrictRedirects: No redirect found, continuing');
            return $next($request);
        }

        Log::info('StrictRedirects: Redirecting', [
            'from' => $request->url(),
            'to' => $checker->destination,
        ]);

        return $checker->redirect($request);
    }
}

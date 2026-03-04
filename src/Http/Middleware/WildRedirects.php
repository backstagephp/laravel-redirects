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
        // Get current site
        $currentSite = $request->site();

        /**
         * @var \Backstage\Redirects\Laravel\Models\Redirect|null $checker
         */
        $checker = Redirect::query()
            ->when($currentSite, fn ($query) => $query->where(function ($q) use ($currentSite) {
                $q->where('site_id', $currentSite->ulid)->orWhereNull('site_id');
            }))
            ->get()
            ->firstWhere(function (Redirect $redirect) use ($request) {
                return str($request->fullUrl())->contains($redirect->source);
            });

        if (! $checker) {
            return $next($request);
        }

        return $checker->redirect($request);
    }
}

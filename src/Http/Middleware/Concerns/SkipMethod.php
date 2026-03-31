<?php

namespace Backstage\Redirects\Laravel\Http\Middleware\Concerns;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

trait SkipMethod
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response|RedirectResponse)  $next
     * @return Response|RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return $next($request);
        }

        return $this->handleNonPost($request, $next);
    }

    /**
     * Handle non-POST requests.
     */
    abstract protected function handleNonPost(Request $request, Closure $next);
}

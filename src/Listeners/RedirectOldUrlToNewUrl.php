<?php

namespace Backstage\Redirects\Laravel\Listeners;

use Backstage\Redirects\Laravel\Events\UrlHasChanged;
use Backstage\Redirects\Laravel\Models\Redirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Keeps the redirects of a site flat when a URL moves.
 *
 * The new URL serves the page now, so no redirect may start there: one left behind
 * by an earlier rename would send visitors away from the page, to a URL that no
 * longer exists. Redirects that pointed at the old URL are pointed at the new one,
 * so every old URL stays one hop from the page instead of growing a chain. Then the
 * old URL gets its own redirect, or the one it already had is reused.
 */
class RedirectOldUrlToNewUrl
{
    public function handle(UrlHasChanged $event): void
    {
        if (Redirect::sameUrl($event->oldUrl, $event->newUrl)) {
            return;
        }

        DB::transaction(function () use ($event): void {
            $redirects = $this->redirectsOfSite($event->siteId);

            $redirects
                ->filter(fn (Redirect $redirect): bool => $redirect->startsAt($event->newUrl))
                ->each(fn (Redirect $redirect) => $redirect->delete());

            $remaining = $redirects->reject(fn (Redirect $redirect): bool => $redirect->startsAt($event->newUrl));

            $remaining
                ->filter(fn (Redirect $redirect): bool => $redirect->pointsAt($event->oldUrl))
                ->each(fn (Redirect $redirect) => $redirect->pointAt($event->newUrl));

            $existing = $remaining->first(fn (Redirect $redirect): bool => $redirect->startsAt($event->oldUrl));

            if ($existing) {
                $existing->update(['destination' => $event->newUrl, 'code' => $event->code]);

                return;
            }

            $this->createRedirect($event);
        });
    }

    private function redirectsOfSite(?string $siteId): Collection
    {
        return config('redirects.model', Redirect::class)::query()
            ->when(
                $siteId,
                fn (Builder $query): Builder => $query->where('site_id', $siteId),
                fn (Builder $query): Builder => $query->whereNull('site_id'),
            )
            ->get();
    }

    private function createRedirect(UrlHasChanged $event): void
    {
        $modelClass = config('redirects.model', Redirect::class);

        $data = [
            'source' => $event->oldUrl,
            'destination' => $event->newUrl,
            'code' => $event->code,
        ];

        if ($event->siteId) {
            $data['site_id'] = $event->siteId;
        }

        Log::info('RedirectOldUrlToNewUrl: Creating redirect', [
            'model_class' => $modelClass,
            'data' => $data,
        ]);

        $redirect = $modelClass::create($data);

        Log::info('RedirectOldUrlToNewUrl: Redirect created', [
            'redirect_id' => $redirect->ulid,
            'site_id' => $redirect->site_id,
        ]);
    }
}

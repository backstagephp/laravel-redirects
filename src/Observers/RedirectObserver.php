<?php

namespace Backstage\Redirects\Laravel\Observers;

use Backstage\Redirects\Laravel\Models\Redirect;

class RedirectObserver
{
    /**
     * Handle the Redirect "saved" event.
     *
     * Removes any existing redirects that would cause an infinite loop with the saved redirect.
     * The newly saved redirect takes precedence over conflicting older ones.
     */
    public function saved(Redirect $redirect): void
    {
        $this->removeConflictingRedirects($redirect);
    }

    /**
     * Find and remove all redirects that would create an infinite loop.
     *
     * A conflict occurs when following redirects would eventually lead back to the source.
     * Example: If we save A → B, and there's an existing B → A, that creates A → B → A (loop).
     * More complex: A → B, B → C, C → A would also be a loop.
     */
    protected function removeConflictingRedirects(Redirect $redirect): void
    {
        $visited = collect([$redirect->source]);
        $currentDestination = $redirect->destination;

        while (true) {
            $conflicting = $this->findRedirectBySource($redirect, $currentDestination);

            if (! $conflicting) {
                break;
            }

            if ($visited->contains($conflicting->destination)) {
                $conflicting->delete();

                break;
            }

            $visited->push($conflicting->source);
            $currentDestination = $conflicting->destination;
        }
    }

    protected function findRedirectBySource(Redirect $redirect, string $source): ?Redirect
    {
        return $redirect->newQuery()
            ->where('source', $source)
            ->where($redirect->getKeyName(), '!=', $redirect->getKey())
            ->first();
    }
}

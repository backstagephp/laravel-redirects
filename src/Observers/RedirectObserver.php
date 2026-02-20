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
        $visited = collect([$this->normalizeSource($redirect->source)]);
        $currentDestination = $redirect->destination;

        while (true) {
            $conflicting = $this->findRedirectBySource($redirect, $currentDestination);

            if (! $conflicting) {
                break;
            }

            $normalizedConflictingDestination = $this->normalizeSource($conflicting->destination);

            if ($visited->contains($normalizedConflictingDestination)) {
                $conflicting->delete();

                break;
            }

            $visited->push($this->normalizeSource($conflicting->source));
            $currentDestination = $conflicting->destination;
        }
    }

    /**
     * Find a redirect by its source, scoped to the same site if applicable.
     *
     * Uses normalized source matching to handle inconsistencies like:
     * - Leading slashes: "/page" vs "page"
     * - Trailing slashes: "/page/" vs "/page"
     */
    protected function findRedirectBySource(Redirect $redirect, string $source): ?Redirect
    {
        $normalizedSource = $this->normalizeSource($source);

        $query = $redirect->newQuery()
            ->where($redirect->getKeyName(), '!=', $redirect->getKey());

        // Scope to the same site if site_id is set
        if ($redirect->site_id) {
            $query->where('site_id', $redirect->site_id);
        } else {
            $query->whereNull('site_id');
        }

        // Find redirects where the normalized source matches
        return $query->get()->first(function (Redirect $r) use ($normalizedSource) {
            return $this->normalizeSource($r->source) === $normalizedSource;
        });
    }

    /**
     * Normalize a source URL for consistent comparison.
     *
     * This handles common inconsistencies:
     * - Ensures leading slash
     * - Removes trailing slash (unless it's just "/")
     * - Removes protocol and www prefix
     * - Converts to lowercase for case-insensitive matching
     */
    protected function normalizeSource(string $source): string
    {
        $normalized = $source;

        // Remove protocol and www
        $normalized = preg_replace('#^https?://(www\.)?#i', '', $normalized);

        // Remove domain if present (everything before first single slash after potential domain)
        if (preg_match('#^[^/]+\.[^/]+(/.*)?$#', $normalized, $matches)) {
            $normalized = $matches[1] ?? '/';
        }

        // Ensure leading slash
        $normalized = '/' . ltrim($normalized, '/');

        // Remove trailing slash (unless it's just "/")
        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
        }

        // Lowercase for case-insensitive matching (unless configured otherwise)
        if (! config('redirects.case_sensitive', false)) {
            $normalized = strtolower($normalized);
        }

        return $normalized;
    }
}

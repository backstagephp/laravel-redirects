<?php

namespace Backstage\Redirects\Laravel;

use Backstage\Redirects\Laravel\Models\Redirect;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Redirects
{
    public array $redirects = [];

    public function forSite(?Model $site = null): Collection
    {
        $key = $site?->getKey() ?? '*';

        return $this->redirects[$key] ??= $this->resolveRedirects($site);
    }

    protected function resolveRedirects(?Model $site = null): Collection
    {
        $modelClass = config('redirects.model', Redirect::class);

        return $modelClass::query()
            ->when($site, fn ($query) => $query->where(function ($q) use ($site) {
                $q->where('site_id', $site->getKey())->orWhereNull('site_id');
            }))
            ->get();
    }
}

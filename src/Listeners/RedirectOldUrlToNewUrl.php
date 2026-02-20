<?php

namespace Backstage\Redirects\Laravel\Listeners;

use Backstage\Redirects\Laravel\Events\UrlHasChanged;
use Backstage\Redirects\Laravel\Models\Redirect;
use Illuminate\Support\Facades\Log;

class RedirectOldUrlToNewUrl
{
    public function handle(UrlHasChanged $event): void
    {
        $modelClass = config('redirects.model', Redirect::class);

        $data = [
            'source' => $event->oldUrl,
            'destination' => $event->newUrl,
            'code' => $event->code,
        ];

        // If siteId is provided in the event, add it to the data
        // This bypasses the model's boot method which relies on Filament::getTenant()
        if ($event->siteId) {
            $data['site_id'] = $event->siteId;
        }

        \Log::info('RedirectOldUrlToNewUrl: Creating redirect', [
            'model_class' => $modelClass,
            'data' => $data,
        ]);

        $redirect = $modelClass::create($data);

        \Log::info('RedirectOldUrlToNewUrl: Redirect created', [
            'redirect_id' => $redirect->ulid,
            'site_id' => $redirect->site_id,
        ]);
    }
}

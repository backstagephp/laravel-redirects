<?php

namespace Backstage\Redirects\Laravel\Models;

use Backstage\Redirects\Laravel\Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Redirect as RedirectFacade;

class Redirect extends Model
{
    use HasFactory;
    use HasUlids;

    protected $primaryKey = 'ulid';

    protected $fillable = [
        'source',
        'destination',
        'code',
        'site_id',
    ];

    protected static function newFactory()
    {
        return RedirectFactory::new();
    }

    public function startsAt(string $url): bool
    {
        return static::sameUrl($this->source, $url);
    }

    public function pointsAt(string $url): bool
    {
        return static::sameUrl($this->destination, $url);
    }

    public function pointAt(string $url): void
    {
        $this->update([
            'destination' => parse_url($this->destination, PHP_URL_HOST) ? $url : static::pathOf($url),
        ]);
    }

    public static function sameUrl(string $first, string $second): bool
    {
        [$firstHost, $firstPath] = static::comparable($first);
        [$secondHost, $secondPath] = static::comparable($second);

        return $firstPath === $secondPath
            && ($firstHost === null || $secondHost === null || $firstHost === $secondHost);
    }

    protected static function comparable(string $url): array
    {
        $host = parse_url(trim($url), PHP_URL_HOST);
        $path = '/' . trim(static::pathOf($url), '/');

        return [
            $host ? preg_replace('/^www\./', '', strtolower($host)) : null,
            config('redirects.case_sensitive', false) ? $path : strtolower($path),
        ];
    }

    protected static function pathOf(string $url): string
    {
        $parts = parse_url(trim($url)) ?: [];
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return '/' . ltrim($parts['path'] ?? '', '/') . $query;
    }

    public function redirect(Request $request): ?RedirectResponse
    {
        $this->increment('hits');

        $destination = $this->destination;

        if ($request->query()) {
            $destination .= (str($destination)->contains('?') ? '&' : '?') . Arr::query($request->query());
        }

        return RedirectFacade::to($destination, $this->code, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ])->withInput($request->input());
    }
}

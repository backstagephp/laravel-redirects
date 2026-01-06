<?php

namespace Backstage\Redirects\Laravel\Models;

use Backstage\Redirects\Laravel\Database\Factories\RedirectFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class Redirect extends Model
{
    use HasFactory;
    use HasUlids;

    protected $primaryKey = 'ulid';

    protected $fillable = [
        'source',
        'destination',
        'code',
    ];

    protected static function newFactory()
    {
        return RedirectFactory::new();
    }

    public function redirect(Request $request): ?RedirectResponse
    {
        $this->increment('hits');

        $destination = $this->destination;

        if ($request->query()) {
            $destination .= (str($destination)->contains('?') ? '&' : '?').Arr::query($request->query());
        }

        return redirect($destination, $this->code)
            ->with('input', $request->input());
    }
}

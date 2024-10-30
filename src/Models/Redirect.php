<?php

namespace Vormkracht10\Redirects\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Redirect extends Model
{
    use HasFactory, HasUlids;

    protected $primaryKey = 'ulid';
}

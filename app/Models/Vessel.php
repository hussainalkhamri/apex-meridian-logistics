<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vessel extends Model
{
    protected $fillable = [
        'name',
        'type',
        'capacity',
        'flag',
        'year',
        'status',
        'route',
    ];

    protected $casts = [
        'year' => 'integer',
    ];
}

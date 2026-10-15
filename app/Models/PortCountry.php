<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortCountry extends Model
{
    protected $fillable = [
        'name',
        'name_ar',
        'code',
        'flag',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}

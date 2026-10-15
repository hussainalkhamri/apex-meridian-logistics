<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'vessel',
        'voyage',
        'carrier',
        'service_name',
        'origin',
        'origin_code',
        'destination',
        'destination_code',
        'etd',
        'eta',
        'transit',
        'cutoff',
        'status',
        'is_published',
        'source_name',
        'source_url',
        'source_reference',
        'source_checked_at',
        'expires_at',
        'booking_url',
        'notes',
    ];

    protected $casts = [
        'etd' => 'date',
        'eta' => 'date',
        'cutoff' => 'date',
        'source_checked_at' => 'date',
        'expires_at' => 'date',
        'is_published' => 'boolean',
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEvent extends Model
{
    protected $fillable = [
        'shipment_track_id',
        'event_date',
        'location',
        'description',
        'status',
        'source_name',
        'source_type',
    ];

    public function track(): BelongsTo
    {
        return $this->belongsTo(ShipmentTrack::class, 'shipment_track_id');
    }
}

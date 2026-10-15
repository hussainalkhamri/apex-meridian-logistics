<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class ShippingRate extends Model
{
    use HasUuids;

    protected $fillable = [
        'shipping_type',
        'service_type',
        'incoterm',
        'container_type',
        'origin_country',
        'origin_port',
        'destination_country',
        'destination_port',
        'shipping_company',
        'transit_time',
        'currency',
        'price',
        'additional_fee',
        'valid_from',
        'valid_to',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'additional_fee' => 'decimal:2',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'transit_time' => 'integer',
    ];

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'فعال' : 'غير فعال';
    }
}

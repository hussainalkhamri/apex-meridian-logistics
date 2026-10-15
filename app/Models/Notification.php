<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Notification extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'message',
        'booking_id',
        'read',
        'target',
        'sound',
    ];

    protected $casts = [
        'read' => 'boolean',
    ];

    public function reads(): HasMany
    {
        return $this->hasMany(NotificationRead::class);
    }
}

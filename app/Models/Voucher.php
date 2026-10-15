<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'vouchers';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'type',
        'booking_id',
        'invoice_id',
        'agent_id',
        'amount',
        'paid_before',
        'paid_after',
        'balance_after',
        'payment_method',
        'payee_payer',
        'description',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'double',
        'paid_before' => 'double',
        'paid_after' => 'double',
        'balance_after' => 'double',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'booking_id')->withoutGlobalScope('archived_visibility');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function scopeAgentPaymentsForApprovedAccounting(Builder $query): Builder
    {
        return $query
            ->where('type', 'payment')
            ->whereNotNull('agent_id')
            ->where(function (Builder $query) {
                $query
                    ->whereHas('booking', fn (Builder $bookingQuery) => $bookingQuery
                        ->withoutGlobalScope('archived_visibility')
                        ->approvedForAccounting())
                    ->orWhere(function (Builder $query) {
                        $query
                            ->whereNull('booking_id')
                            ->whereHas('agent.bookings', fn (Builder $bookingQuery) => $bookingQuery
                                ->withoutGlobalScope('archived_visibility')
                                ->approvedForAccounting()
                                ->where('agent_purchase_amount', '>', 0));
                    });
            });
    }

    public function getTypeName()
    {
        return $this->type === 'receipt' ? 'سند قبض' : 'سند صرف';
    }
}

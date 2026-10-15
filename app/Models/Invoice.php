<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'booking_id',
        'invoice_type',
        'amount',
        'currency',
        'description',
        'status',
        'issued_at',
        'due_at',
    ];

    protected $casts = [
        'amount' => 'double',
        'issued_at' => 'datetime',
        'due_at' => 'datetime',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class)->withoutGlobalScope('archived_visibility');
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function getPaidAmountAttribute(): float
    {
        if ($this->relationLoaded('vouchers')) {
            $paid = (float) $this->vouchers
                ->where('type', 'receipt')
                ->sum('amount');

            if ($this->booking_id) {
                $paid += (float) Voucher::where('type', 'receipt')
                    ->whereNull('invoice_id')
                    ->where('booking_id', $this->booking_id)
                    ->sum('amount');
            }

            if ($paid <= 0 && $this->status === 'مدفوعة') {
                return (float) $this->amount;
            }

            return $paid;
        }

        $paid = (float) Voucher::where('type', 'receipt')
            ->where(function ($query) {
                $query->where('invoice_id', $this->id);

                if ($this->booking_id) {
                    $query->orWhere(function ($fallbackQuery) {
                        $fallbackQuery
                            ->whereNull('invoice_id')
                            ->where('booking_id', $this->booking_id);
                    });
                }
            })
            ->sum('amount');

        if ($paid <= 0 && $this->status === 'مدفوعة') {
            return (float) $this->amount;
        }

        return $paid;
    }

    public function getRemainingAmountAttribute(): float
    {
        return max(((float) $this->amount) - $this->paid_amount, 0);
    }

    public function effectiveCurrency(): string
    {
        return Booking::normalizePricingCurrency(
            $this->currency ?: $this->booking?->pricingCurrency() ?: 'USD'
        );
    }

    public function currencySymbol(): string
    {
        return Booking::pricingCurrencySymbolFor($this->effectiveCurrency());
    }

    public function formatMoney(float|int|null $amount, int $decimals = 2): string
    {
        return $this->currencySymbol() . ' ' . number_format((float) $amount, $decimals);
    }
}

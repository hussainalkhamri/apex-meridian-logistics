<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'action_type',
        'table_name',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'record_id' => 'string',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function logAction(
        string $actionType,
        string $tableName,
        string $recordId,
        array $oldValues = [],
        array $newValues = []
    ): void {
        $userId = auth('web')->check() ? auth('web')->id() : null;

        static::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()->ip(),
        ]);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('action_type', 'like', "%{$term}%")
                ->orWhere('table_name', 'like', "%{$term}%")
                ->orWhere('record_id', 'like', "%{$term}%")
                ->orWhereHas('user', fn ($uq) => $uq->where('full_name', 'like', "%{$term}%"));
        });
    }

    public static function actionLabelFor(string $actionType): string
    {
        $translationKey = match ($actionType) {
            'CREATE' => 'audit_action_create',
            'UPDATE' => 'audit_action_update',
            'DELETE' => 'audit_action_delete',
            'LOGIN' => 'audit_action_login',
            'LOGIN_FAILED' => 'audit_action_login_failed',
            'LOGOUT' => 'audit_action_logout',
            'CUSTOMER_LOGIN' => 'audit_action_customer_login',
            'CUSTOMER_LOGOUT' => 'audit_action_customer_logout',
            'CUSTOMER_LOGIN_FAIL' => 'audit_action_customer_login_fail',
            'IMPORT' => 'audit_action_import',
            'PRICING' => 'audit_action_pricing',
            'PRICING_UPDATE' => 'audit_action_pricing_update',
            'SEND_QUOTE' => 'audit_action_send_quote',
            'APPROVE' => 'audit_action_approve',
            'REJECT' => 'audit_action_reject',
            'CARRIER_BOOKING' => 'audit_action_carrier_booking',
            'INVOICE_CREATE' => 'audit_action_invoice_create',
            'INVOICE_UPDATE' => 'audit_action_invoice_update',
            'VOUCHER_CREATE' => 'audit_action_voucher_create',
            'CUSTOMS_UPDATE' => 'audit_action_customs_update',
            'CUSTOMER_CREATE_BOOKING' => 'audit_action_customer_create_booking',
            'CUSTOMER_ACCEPT_QUOTE' => 'audit_action_customer_accept_quote',
            'CUSTOMER_REJECT_QUOTE' => 'audit_action_customer_reject_quote',
            'DATA_BACKUP_EXPORT' => 'audit_action_data_backup_export',
            'DATA_BACKUP_IMPORT' => 'audit_action_data_backup_import',
            default => null,
        };

        return $translationKey ? __("messages.{$translationKey}") : $actionType;
    }

    public static function actionColorFor(string $actionType): string
    {
        return match ($actionType) {
            'CREATE', 'APPROVE', 'CARRIER_BOOKING', 'VOUCHER_CREATE' => 'bg-success/15 text-success border-success/25',
            'UPDATE', 'PRICING', 'PRICING_UPDATE', 'SEND_QUOTE', 'INVOICE_UPDATE', 'CUSTOMS_UPDATE' => 'bg-accent/15 text-accent-foreground border-accent/20',
            'DELETE', 'REJECT', 'CUSTOMER_REJECT_QUOTE' => 'bg-destructive/15 text-destructive border-destructive/20',
            'LOGIN', 'CUSTOMER_LOGIN', 'CUSTOMER_CREATE_BOOKING', 'CUSTOMER_ACCEPT_QUOTE' => 'bg-primary/15 text-primary border-primary/20',
            'IMPORT', 'INVOICE_CREATE', 'DATA_BACKUP_EXPORT', 'DATA_BACKUP_IMPORT' => 'bg-warning/20 text-warning-foreground border-warning/30',
            'LOGIN_FAILED', 'CUSTOMER_LOGIN_FAIL' => 'bg-destructive/15 text-destructive border-destructive/20',
            'LOGOUT', 'CUSTOMER_LOGOUT' => 'bg-secondary text-foreground border-border',
            default => 'bg-secondary text-foreground border-border',
        };
    }

    public function getActionLabelAttribute(): string
    {
        return self::actionLabelFor($this->action_type);
    }

    public function getActionColorAttribute(): string
    {
        return self::actionColorFor($this->action_type);
    }
}

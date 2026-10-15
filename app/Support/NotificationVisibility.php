<?php

namespace App\Support;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class NotificationVisibility
{
    private const TARGET_PERMISSIONS = [
        'general' => 'view_dashboard',
        'orders' => 'manage_bookings',
        'pricing' => 'manage_pricing',
        'sales' => 'manage_sales',
        'tracking' => 'manage_carriers',
        'customs' => 'manage_customs',
        'accounts' => 'manage_accounts',
        'customers' => 'manage_customers',
        'ports' => 'manage_ports',
        'agents' => 'manage_agents',
        'users' => 'manage_users',
        'roles' => 'manage_roles',
        'audit' => 'view_audit_logs',
        'insights' => 'manage_insights',
    ];

    public static function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        $targets = self::allowedTargetsFor($user);

        if (empty($targets)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $targetQuery) use ($targets) {
            $targetQuery->whereIn('target', $targets);

            if (in_array('general', $targets, true)) {
                $targetQuery->orWhereNull('target')->orWhere('target', '');
            }
        });
    }

    public static function scopeUnreadFor(Builder $query, ?User $user): Builder
    {
        self::scopeVisibleTo($query, $user);

        if (! $user) {
            return $query;
        }

        return $query->whereDoesntHave('reads', function (Builder $readQuery) use ($user) {
            $readQuery->where('user_id', $user->id);
        });
    }

    public static function canView(?User $user, Notification $notification): bool
    {
        if (! $user) {
            return false;
        }

        $target = $notification->target ?: 'general';

        return in_array($target, self::allowedTargetsFor($user), true);
    }

    public static function allowedTargetsFor(User $user): array
    {
        if ($user->isSuperAdmin()) {
            return array_keys(self::TARGET_PERMISSIONS);
        }

        return array_keys(array_filter(
            self::TARGET_PERMISSIONS,
            fn (string $permission) => $user->hasPermission($permission)
        ));
    }
}

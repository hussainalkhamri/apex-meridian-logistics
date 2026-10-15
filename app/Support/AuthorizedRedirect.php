<?php

namespace App\Support;

use App\Models\User;

class AuthorizedRedirect
{
    /**
     * Ordered by the main dashboard navigation.
     */
    private const PERMISSION_ROUTES = [
        'view_dashboard' => 'dashboard',
        'manage_bookings' => 'orders.index',
        'manage_pricing' => 'pricing.index',
        'manage_shipping_rates' => 'shipping-rates.index',
        'view_shipping_rates_operations' => 'shipping-rates.operations',
        'manage_sales' => 'sales.index',
        'manage_carriers' => 'bookings.index',
        'manage_customs' => 'customs.index',
        'manage_accounts' => 'accounts.index',
        'manage_customers' => 'customers.index',
        'manage_ports' => 'ports.index',
        'manage_agents' => 'agents.index',
        'manage_users' => 'users.index',
        'manage_roles' => 'roles.index',
        'view_audit_logs' => 'audit-logs.index',
        'manage_insights' => 'insights.index',
        'manage_backups' => 'backups.index',
    ];

    public static function routeNameFor(User $user): ?string
    {
        if ($user->isSuperAdmin()) {
            return 'dashboard';
        }

        foreach (self::PERMISSION_ROUTES as $permission => $routeName) {
            if ($user->hasPermission($permission)) {
                return $routeName;
            }
        }

        return null;
    }

    public static function urlFor(User $user): string
    {
        $routeName = self::routeNameFor($user);

        return $routeName ? route($routeName) : route('home');
    }
}

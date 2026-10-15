<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        // ─── Create Roles ─────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin'], [
            'permissions' => array_keys(Role::$allPermissions),
        ]);

        $opsManager = Role::firstOrCreate(['name' => 'ops_manager'], [
            'permissions' => [
                'manage_bookings', 'manage_pricing', 'manage_sales',
                'manage_carriers', 'manage_accounts', 'view_dashboard',
                'manage_ports', 'manage_agents', 'manage_customs',
                'view_reports', 'view_audit_logs',
            ],
        ]);

        $pricing = Role::firstOrCreate(['name' => 'pricing'], [
            'permissions' => ['manage_bookings', 'manage_pricing', 'view_dashboard'],
        ]);

        $sales = Role::firstOrCreate(['name' => 'sales'], [
            'permissions' => ['manage_bookings', 'manage_sales', 'view_dashboard'],
        ]);

        $accounts = Role::firstOrCreate(['name' => 'accounts'], [
            'permissions' => ['manage_accounts', 'view_dashboard', 'view_reports'],
        ]);

        Role::firstOrCreate(['name' => 'viewer'], [
            'permissions' => ['view_dashboard'],
        ]);

        // ─── Create Default Admin User ────────────────────────────
        User::firstOrCreate(['username' => 'admin'], [
            'full_name'  => 'مدير النظام',
            'phone'      => '+966500000000',
            'password'   => bcrypt('admin1234'),
            'role_id'    => $superAdmin->id,
            'is_active'  => true,
        ]);

        // ─── Demo Users ───────────────────────────────────────────
        User::firstOrCreate(['username' => 'pricing_user'], [
            'full_name' => 'موظف التسعير',
            'password'  => bcrypt('password'),
            'role_id'   => $pricing->id,
            'is_active' => true,
        ]);

        User::firstOrCreate(['username' => 'sales_user'], [
            'full_name' => 'موظف المبيعات',
            'password'  => bcrypt('password'),
            'role_id'   => $sales->id,
            'is_active' => true,
        ]);

        User::firstOrCreate(['username' => 'accounts_user'], [
            'full_name' => 'المحاسب',
            'password'  => bcrypt('password'),
            'role_id'   => $accounts->id,
            'is_active' => true,
        ]);
    }
}

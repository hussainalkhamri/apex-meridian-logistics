<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')->get(['id', 'name', 'permissions']);

        foreach ($roles as $role) {
            $permissions = json_decode($role->permissions ?: '[]', true) ?: [];
            $updated = $permissions;

            if (in_array('manage_users', $permissions, true)) {
                $updated[] = 'manage_ports';
                $updated[] = 'manage_agents';
            }

            if ($role->name === 'ops_manager') {
                $updated[] = 'manage_ports';
                $updated[] = 'manage_agents';
                $updated[] = 'manage_customs';
            }

            if ($role->name === 'super_admin') {
                $updated[] = 'manage_ports';
                $updated[] = 'manage_agents';
                $updated[] = 'manage_customs';
            }

            $updated = array_values(array_unique($updated));

            if ($updated !== $permissions) {
                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($updated)]);
            }
        }
    }

    public function down(): void
    {
        // Permissions may have been changed intentionally after deployment; keep them intact.
    }
};

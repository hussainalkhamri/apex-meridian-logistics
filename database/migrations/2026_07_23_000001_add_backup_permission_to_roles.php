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

            if ($role->name !== 'super_admin' && ! in_array('manage_users', $permissions, true)) {
                continue;
            }

            $updated = array_values(array_unique([...$permissions, 'manage_backups']));

            if ($updated !== $permissions) {
                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($updated)]);
            }
        }
    }

    public function down(): void
    {
        // Keep manually assigned permissions untouched.
    }
};

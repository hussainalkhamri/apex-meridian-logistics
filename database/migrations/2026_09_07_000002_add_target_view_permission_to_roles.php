<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')
            ->whereIn('name', ['super_admin', 'ops_manager'])
            ->get(['id', 'permissions']);

        foreach ($roles as $role) {
            $permissions = json_decode($role->permissions ?: '[]', true) ?: [];
            $updated = array_values(array_unique([...$permissions, 'view_targets']));

            if ($updated !== $permissions) {
                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($updated)]);
            }
        }
    }

    public function down(): void
    {
        // Keep manually assigned permissions intact.
    }
};

<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiagnosticAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostic_pin_is_read_from_configuration(): void
    {
        config([
            'app.env' => 'production',
            'diagnostic.pin' => 'hostinger-secure-pin',
            'diagnostic.allow_pin_access' => true,
        ]);

        $this->get('/diagnose')
            ->assertStatus(200)
            ->assertSee('لوحة تشخيص النظام');

        $this->get('/diagnose?pin=wrong-pin')
            ->assertStatus(200)
            ->assertSee('رمز الدخول غير صحيح');

        $this->get('/diagnose?pin=hostinger-secure-pin')
            ->assertStatus(200)
            ->assertSee('لوحة تشخيص وتأمين العمليات');
    }

    public function test_diagnostic_dashboard_is_not_unlocked_without_configured_pin(): void
    {
        config([
            'app.env' => 'production',
            'diagnostic.pin' => null,
            'diagnostic.allow_pin_access' => true,
        ]);

        $this->get('/diagnose?pin=shipmate2026')
            ->assertStatus(200)
            ->assertSee('رمز الدخول غير صحيح')
            ->assertDontSee('لوحة تشخيص وتأمين العمليات');
    }

    public function test_diagnostic_pin_access_is_disabled_unless_explicitly_allowed(): void
    {
        config([
            'app.env' => 'production',
            'diagnostic.pin' => 'hostinger-secure-pin',
            'diagnostic.allow_pin_access' => false,
        ]);

        $this->get('/diagnose?pin=hostinger-secure-pin')
            ->assertStatus(200)
            ->assertSee('رمز الدخول غير صحيح')
            ->assertDontSee('لوحة تشخيص وتأمين العمليات');
    }

    public function test_super_admin_can_open_diagnostic_dashboard_without_pin(): void
    {
        config([
            'app.env' => 'production',
            'diagnostic.pin' => null,
            'diagnostic.allow_pin_access' => false,
        ]);

        $role = Role::create([
            'name' => 'super_admin',
            'permissions' => [],
        ]);
        $admin = User::create([
            'role_id' => $role->id,
            'full_name' => 'Security Admin',
            'username' => 'security-admin',
            'phone' => '+967700900900',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/diagnose')
            ->assertStatus(200)
            ->assertSee('لوحة تشخيص وتأمين العمليات');
    }
}

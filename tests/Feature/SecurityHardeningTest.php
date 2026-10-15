<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_diagnostic_dashboard_requires_pin_even_in_local_environment(): void
    {
        config([
            'app.env' => 'local',
            'diagnostic.pin' => null,
        ]);

        $this->get(route('diagnose.index'))
            ->assertOk()
            ->assertSee('لوحة تشخيص النظام')
            ->assertDontSee('لوحة تشخيص وتأمين العمليات');
    }

    public function test_language_switch_does_not_redirect_to_external_hosts(): void
    {
        $this->get(route('language.switch', [
            'locale' => 'ar',
            'redirect_to' => 'https://evil.example/phishing',
        ]))->assertRedirect(route('home'));
    }

    public function test_private_local_storage_serving_route_is_not_registered(): void
    {
        $this->assertFalse(Route::has('storage.local'));
        $this->assertFalse(Route::has('storage.local.upload'));
    }

    public function test_public_responses_include_baseline_security_headers(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_staff_passwords_must_be_at_least_eight_characters(): void
    {
        $role = Role::create([
            'name' => 'super_admin',
            'permissions' => [],
        ]);
        $admin = User::create([
            'role_id' => $role->id,
            'full_name' => 'Password Policy Admin',
            'username' => 'password-policy-admin',
            'phone' => '+967700901901',
            'password' => 'strong-password',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'full_name' => 'Weak Password User',
                'username' => 'weakpassuser',
                'phone' => '+967700902902',
                'password' => '1234567',
                'role_id' => $role->id,
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_attachment_download_rejects_path_traversal_and_unregistered_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bookings/attachments/allowed.pdf', 'allowed');
        Storage::disk('public')->put('bookings/attachments/not-listed.pdf', 'not-listed');

        $role = Role::create([
            'name' => 'operations_staff',
            'permissions' => ['manage_bookings'],
        ]);
        $staff = User::create([
            'role_id' => $role->id,
            'full_name' => 'Attachment Security Staff',
            'username' => 'attachment-security-staff',
            'phone' => '+967700903903',
            'password' => 'strong-password',
            'is_active' => true,
        ]);
        $booking = Booking::create([
            'id' => 'BK-ATTACHMENT-SECURITY',
            'customer_company' => 'Attachment Security Customer',
            'customer_contact_name' => 'Attachment Contact',
            'customer_phone' => '+967700904904',
            'customer_email' => 'attachment-security@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Attachment security cargo',
            'weight_kg' => 100,
            'volume_cbm' => 1,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'attachments' => json_encode(['bookings/attachments/allowed.pdf']),
        ]);

        $this->actingAs($staff)
            ->get(route('orders.attachments.show', [
                'booking' => $booking,
                'path' => '../.env',
            ]))
            ->assertNotFound();

        $this->actingAs($staff)
            ->get(route('orders.attachments.show', [
                'booking' => $booking,
                'path' => 'bookings/attachments/not-listed.pdf',
            ]))
            ->assertNotFound();
    }

    public function test_quote_documents_are_not_public_and_are_visible_to_owner_or_authorized_staff(): void
    {
        $customer = Customer::create([
            'company' => 'Secure Quote Customer',
            'contact_name' => 'Secure Contact',
            'phone' => '+967700123123',
            'email' => 'secure-quote@example.com',
            'password' => 'password',
            'portal_enabled' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-SECURE-QUOTE',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Secure quote cargo',
            'weight_kg' => 1000,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 900,
            'priced_at' => now(),
        ]);

        $this->get(route('quote.show', $booking))->assertForbidden();

        $this->actingAs($customer, 'customer')
            ->get(route('quote.show', $booking))
            ->assertOk()
            ->assertSee('BK-SECURE-QUOTE');

        auth('customer')->logout();

        $role = Role::create([
            'name' => 'secure_quote_staff',
            'permissions' => ['manage_sales'],
        ]);
        $staff = User::create([
            'role_id' => $role->id,
            'full_name' => 'Secure Quote Staff',
            'username' => 'secure-quote-staff',
            'phone' => '+967700456456',
            'password' => 'password',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('quote.show', $booking))
            ->assertOk()
            ->assertSee('BK-SECURE-QUOTE');
    }
}

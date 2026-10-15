<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_view_and_update_profile_with_avatar(): void
    {
        Storage::fake('public');

        $customer = $this->makeCustomer();
        $avatar = UploadedFile::fake()->image('avatar.jpg', 120, 120);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.profile'))
            ->assertOk()
            ->assertSee('الملف الشخصي')
            ->assertSee($customer->company);

        $response = $this->actingAs($customer, 'customer')
            ->put(route('customer.profile.update'), [
                'company' => 'Updated Portal Company',
                'contact_name' => 'Updated Contact',
                'phone' => '+967777888999',
                'email' => 'updated-portal@example.com',
                'avatar' => $avatar,
            ]);

        $response->assertRedirect(route('customer.profile'));
        $response->assertSessionHasNoErrors();

        $customer->refresh();

        $this->assertSame('Updated Portal Company', $customer->company);
        $this->assertSame('Updated Contact', $customer->contact_name);
        $this->assertSame('+967777888999', $customer->phone);
        $this->assertSame('updated-portal@example.com', $customer->email);
        $this->assertNotNull($customer->avatar_path);
        Storage::disk('public')->assertExists($customer->avatar_path);
    }

    public function test_customer_can_change_password_only_with_current_password(): void
    {
        $customer = $this->makeCustomer();

        $this->actingAs($customer, 'customer')
            ->from(route('customer.profile'))
            ->put(route('customer.profile.password'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('customer.profile'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password', $customer->fresh()->password));

        $this->actingAs($customer, 'customer')
            ->put(route('customer.profile.password'), [
                'current_password' => 'old-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('customer.profile'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('new-password-123', $customer->fresh()->password));
    }

    public function test_customer_can_delete_avatar(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('customers/avatars/avatar.jpg', 'fake image content');

        $customer = $this->makeCustomer([
            'avatar_path' => 'customers/avatars/avatar.jpg',
        ]);

        $this->actingAs($customer, 'customer')
            ->delete(route('customer.profile.avatar.delete'))
            ->assertRedirect(route('customer.profile'))
            ->assertSessionHasNoErrors();

        $this->assertNull($customer->fresh()->avatar_path);
        Storage::disk('public')->assertMissing('customers/avatars/avatar.jpg');
    }

    private function makeCustomer(array $overrides = []): Customer
    {
        return Customer::create(array_merge([
            'company' => 'Portal Customer',
            'contact_name' => 'Portal Contact',
            'phone' => '+967700111222',
            'email' => 'portal-customer@example.com',
            'password' => 'old-password',
            'portal_enabled' => true,
        ], $overrides));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_store_rejects_duplicate_company_email_and_phone(): void
    {
        $this->actingAs($this->customerManager());

        Customer::create([
            'company' => 'Acme Logistics',
            'contact_name' => 'Ahmed',
            'phone' => '+967 777123456',
            'email' => 'ops@example.com',
            'portal_enabled' => true,
        ]);

        $response = $this->from(route('customers.index'))
            ->post(route('customers.store'), [
                'company' => '  acme  logistics  ',
                'contact_name' => 'Ali',
                'phone_country_code' => '+967',
                'phone' => '777 123 456',
                'email' => 'OPS@example.com',
            ]);

        $response->assertRedirect(route('customers.index'));
        $response->assertSessionHasErrors(['company', 'phone', 'email']);
        $this->assertDatabaseCount('customers', 1);
    }

    public function test_customer_update_allows_own_values_and_rejects_other_customer_duplicates(): void
    {
        $this->actingAs($this->customerManager());

        $first = Customer::create([
            'company' => 'First Client',
            'contact_name' => 'First Contact',
            'phone' => '+967 700000001',
            'email' => 'first@example.com',
            'portal_enabled' => true,
        ]);

        $second = Customer::create([
            'company' => 'Second Client',
            'contact_name' => 'Second Contact',
            'phone' => '+967 700000002',
            'email' => 'second@example.com',
            'portal_enabled' => true,
        ]);

        $this->from(route('customers.index'))
            ->put(route('customers.update', $first), [
                'customer_form_id' => $first->id,
                'company' => 'First Client',
                'contact_name' => 'Updated Contact',
                'phone_country_code' => '+967',
                'phone' => '+967 700000001',
                'email' => 'first@example.com',
            ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasNoErrors();

        $this->from(route('customers.index'))
            ->put(route('customers.update', $second), [
                'customer_form_id' => $second->id,
                'company' => 'First Client',
                'contact_name' => 'Second Contact',
                'phone_country_code' => '+967',
                'phone' => '+967 700000001',
                'email' => 'first@example.com',
            ])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHasErrors(['company', 'phone', 'email']);
    }

    private function customerManager(): User
    {
        $role = Role::create([
            'name' => 'customer_manager',
            'permissions' => ['manage_customers'],
        ]);

        return User::create([
            'role_id' => $role->id,
            'full_name' => 'Customer Manager',
            'username' => 'customer-manager',
            'phone' => '+967700000000',
            'password' => 'password',
            'is_active' => true,
        ]);
    }
}

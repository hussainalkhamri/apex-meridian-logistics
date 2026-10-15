<?php

namespace Tests\Feature;

use App\Models\Port;
use App\Models\Role;
use App\Models\ShippingRate;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingRateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_countries_from_ports_table_for_form(): void
    {
        Port::create([
            'name' => 'ميناء غزة',
            'country' => 'فلسطين',
            'country_code' => 'PS',
            'country_flag' => '🇵🇸',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $role = Role::create([
            'name' => 'pricing',
            'permissions' => ['manage_shipping_rates'],
        ]);

        $user = User::create([
            'role_id' => $role->id,
            'full_name' => 'Pricing User',
            'username' => 'pricing_user_two',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('shipping-rates.create'));

        $response->assertOk();
        $response->assertSee('فلسطين');
        $response->assertSee('name="incoterm"', false);
        $response->assertSee('data-country-code="PS"', false);
    }

    public function test_operations_page_renders_modal_trigger_with_data_attributes(): void
    {
        $role = Role::create([
            'name' => 'shipping_rates_operations',
            'permissions' => ['view_shipping_rates_operations'],
        ]);

        $user = User::create([
            'role_id' => $role->id,
            'full_name' => 'Pricing User',
            'username' => 'pricing_user_three',
            'password' => 'password123',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'shipping_type' => 'بحري',
            'service_type' => 'FCL',
            'container_type' => '20GP',
            'origin_country' => 'السعودية',
            'origin_port' => 'جدة',
            'destination_country' => 'مصر',
            'destination_port' => 'الإسكندرية',
            'shipping_company' => 'MSC',
            'transit_time' => 12,
            'currency' => 'USD',
            'price' => 1200,
            'additional_fee' => 150,
            'valid_from' => '2026-08-01',
            'valid_to' => '2026-12-31',
            'notes' => 'اختبار',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('shipping-rates.operations'));

        $response->assertOk();
        $response->assertSee('عرض التفاصيل');
        $response->assertSee('data-shipping-company=', false);
    }

    public function test_shipping_rate_pages_are_available_with_manage_shipping_rates_permission(): void
    {
        $role = Role::create([
            'name' => 'shipping_rates',
            'permissions' => ['manage_shipping_rates'],
        ]);

        $user = User::create([
            'role_id' => $role->id,
            'full_name' => 'Shipping Rates User',
            'username' => 'shipping_rates_user',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('shipping-rates.index'));

        $response->assertOk();
    }

    public function test_customer_portal_shipping_rates_page_shows_active_rates_only(): void
    {
        $customer = Customer::create([
            'company' => 'Customer Shipping Rates',
            'contact_name' => 'Customer User',
            'phone' => '777000333',
            'email' => 'rates-customer@example.com',
            'password' => 'password123',
            'portal_enabled' => true,
        ]);

        ShippingRate::create([
            'shipping_type' => 'بحري',
            'service_type' => 'FCL',
            'container_type' => '20GP',
            'origin_country' => 'الإمارات',
            'origin_port' => 'Jebel Ali',
            'destination_country' => 'اليمن',
            'destination_port' => 'Aden',
            'shipping_company' => 'Visible Line',
            'transit_time' => 10,
            'currency' => 'USD',
            'price' => 950,
            'additional_fee' => 75,
            'valid_from' => '2026-08-01',
            'valid_to' => '2026-12-31',
            'notes' => 'سعر ظاهر للعميل',
            'is_active' => true,
        ]);

        ShippingRate::create([
            'shipping_type' => 'بحري',
            'service_type' => 'FCL',
            'container_type' => '40HQ',
            'origin_country' => 'الصين',
            'origin_port' => 'Shanghai',
            'destination_country' => 'اليمن',
            'destination_port' => 'Hodeidah',
            'shipping_company' => 'Hidden Line',
            'transit_time' => 22,
            'currency' => 'USD',
            'price' => 1800,
            'additional_fee' => 100,
            'valid_from' => '2026-08-01',
            'valid_to' => '2026-12-31',
            'notes' => 'سعر غير فعال',
            'is_active' => false,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->get(route('customer.shipping-rates'));

        $response->assertOk();
        $response->assertSee('أسعار الشحن');
        $response->assertSee('Visible Line');
        $response->assertSee('Jebel Ali');
        $response->assertSee('1,025.00 USD');
        $response->assertDontSee('Hidden Line');
    }

    public function test_customer_can_start_booking_from_shipping_rate_with_route_prefilled(): void
    {
        $customer = Customer::create([
            'company' => 'Prefill Customer',
            'contact_name' => 'Prefill User',
            'phone' => '777000334',
            'email' => 'prefill-customer@example.com',
            'password' => 'password123',
            'portal_enabled' => true,
        ]);

        $rate = ShippingRate::create([
            'shipping_type' => 'بحري',
            'service_type' => 'FCL',
            'incoterm' => 'EXW',
            'container_type' => '20GP',
            'origin_country' => 'الإمارات',
            'origin_port' => 'Jebel Ali',
            'destination_country' => 'اليمن',
            'destination_port' => 'Aden',
            'shipping_company' => 'Prefill Line',
            'transit_time' => 10,
            'currency' => 'USD',
            'price' => 950,
            'additional_fee' => 75,
            'valid_from' => '2026-08-01',
            'valid_to' => '2026-12-31',
            'notes' => 'سعر لفتح الطلب',
            'is_active' => true,
        ]);

        $ratesResponse = $this->actingAs($customer, 'customer')
            ->get(route('customer.shipping-rates'));

        $ratesResponse->assertOk();
        $ratesResponse->assertSee(route('customer.shipments.create', ['shipping_rate_id' => $rate->id]), false);

        $createResponse = $this->actingAs($customer, 'customer')
            ->get(route('customer.shipments.create', ['shipping_rate_id' => $rate->id]));

        $createResponse->assertOk();
        $createResponse->assertSee('تم تجهيز النموذج من مسار السعر');
        $createResponse->assertSee('Jebel Ali');
        $createResponse->assertSee('Aden');
        $createResponse->assertSee('"source":"shipping_rate"', false);
        $createResponse->assertSee('"route_mode":"EXW"', false);
        $createResponse->assertSee('"pol":"Jebel Ali"', false);
        $createResponse->assertSee('"pod":"Aden"', false);
        $createResponse->assertSee('"containerType":"20GP"', false);
    }

    public function test_it_can_create_a_shipping_rate(): void
    {
        $role = Role::create([
            'name' => 'pricing',
            'permissions' => ['manage_shipping_rates'],
        ]);

        $user = User::create([
            'role_id' => $role->id,
            'full_name' => 'Pricing User',
            'username' => 'pricing_user',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->post(route('shipping-rates.store'), [
            'shipping_type' => 'بحري',
            'service_type' => 'FCL',
            'incoterm' => 'EXW',
            'container_type' => '20GP',
            'origin_country' => 'السعودية',
            'origin_port' => 'جدة',
            'destination_country' => 'مصر',
            'destination_port' => 'الإسكندرية',
            'shipping_company' => 'MSC',
            'transit_time' => 12,
            'currency' => 'USD',
            'price' => 1200,
            'additional_fee' => 150,
            'valid_from' => '2026-08-01',
            'valid_to' => '2026-12-31',
            'notes' => 'اختبار',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('shipping-rates.index'));
        $this->assertDatabaseHas('shipping_rates', [
            'shipping_company' => 'MSC',
            'origin_port' => 'جدة',
            'incoterm' => 'EXW',
        ]);
    }
}

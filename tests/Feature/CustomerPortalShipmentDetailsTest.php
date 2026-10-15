<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPortalShipmentDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_shipments_page_renders_nested_cargo_details(): void
    {
        $customer = Customer::create([
            'company' => 'Nested Cargo Customer',
            'contact_name' => 'Nested Contact',
            'phone' => '+967700222333',
            'email' => 'nested-cargo@example.com',
            'password' => 'password',
            'portal_enabled' => true,
        ]);

        Booking::create([
            'id' => 'BK-NESTED-CARGO',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'AIR',
            'goods_description' => 'Air cargo with package rows',
            'weight_kg' => 30,
            'volume_cbm' => 0.24,
            'pol' => 'SAH',
            'pod' => 'DXB',
            'status' => 'new',
            'cargo_details' => [
                'service_type' => 'air_freight',
                'packages' => [
                    [
                        'quantity' => 2,
                        'package_type' => 'Carton',
                        'length_cm' => 60,
                        'width_cm' => 40,
                        'height_cm' => 50,
                        'weight_kg' => 15,
                    ],
                ],
                'dangerous_goods' => [
                    'is_dangerous' => true,
                    'un_number' => 'UN1234',
                    'iata_class' => '3',
                ],
                'trade' => [
                    'incoterm' => 'EXW',
                    'commercial_value' => 1200,
                    'currency' => 'USD',
                ],
            ],
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.shipments.index'))
            ->assertOk()
            ->assertSee('BK-NESTED-CARGO')
            ->assertSee('2x Carton')
            ->assertSee('UN1234')
            ->assertSee('EXW');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoorToDoorVolumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_volume_uses_dimensions_when_available_and_manual_input_otherwise(): void
    {
        $this->withoutMiddleware();

        foreach (['complete', 'incomplete', 'none'] as $packageScenario) {
            $payload = $this->doorPayload();
            $payload['volume_cbm'] = '3.2567';

            if ($packageScenario === 'incomplete') {
                $payload['packages'][0]['height_cm'] = '';
            } elseif ($packageScenario === 'none') {
                $payload['packages'] = [];
            }

            $this->post(route('booking.store'), $payload)
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('orders.index'));

            $booking = Booking::latest('id')->firstOrFail();
            $this->assertVolume($booking, $packageScenario === 'complete' ? 2.4 : 3.2567);
        }
    }

    public function test_editing_door_to_door_dimensions_recalculates_volume_and_allows_manual_volume_when_cleared(): void
    {
        $this->withoutMiddleware();
        $payload = $this->doorPayload();

        $this->post(route('booking.store'), $payload)->assertSessionHasNoErrors();
        $booking = Booking::firstOrFail();
        $this->assertVolume($booking, 2.4);

        $payload['volume_cbm'] = '7.1256';
        $payload['packages'][0]['height_cm'] = 100;
        $this->put(route('orders.update', $booking->id), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.index'));

        $this->assertVolume($booking->refresh(), 4);
        $payload['packages'] = [];
        $this->put(route('orders.update', $booking->id), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.index'));
        $this->assertVolume($booking->refresh(), 7.1256);
        $this->get(route('orders.edit', $booking->id))
            ->assertOk()
            ->assertSee('id="volume_cbm_door" value="7.1256"', false);

        $payload['volume_cbm'] = '';
        $this->put(route('orders.update', $booking->id), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('orders.index'));
        $this->assertVolume($booking->refresh(), 0);
    }

    private function assertVolume(Booking $booking, float $expected): void
    {
        $this->assertEquals($expected, $booking->volume_cbm);
        $this->assertEquals($expected, data_get($booking->cargo_details, 'volume_cbm'));
        $this->assertEquals($expected, data_get($booking->cargo_details, 'cargo.volume_cbm'));
        $this->assertEquals($expected, $booking->doorToDoorPricing()['volume_cbm']);
    }

    public function test_customer_portal_keeps_manual_volume_without_complete_dimensions(): void
    {
        $customer = Customer::create([
            'company' => 'Door Portal Volume',
            'contact_name' => 'Portal Contact',
            'phone' => '770123457',
            'email' => 'door-portal-volume@example.com',
            'password' => 'test-password',
            'portal_enabled' => true,
        ]);
        $this->actingAs($customer, 'customer');

        foreach (['incomplete', 'none', 'blank'] as $scenario) {
            $payload = $this->doorPayload();
            $payload['weightKg'] = $payload['gross_weight_kg'];
            $payload['volumeCbm'] = $scenario === 'blank' ? '' : '3.2567';
            $payload['goodsDescription'] = 'Door portal ' . $scenario;
            if ($scenario === 'incomplete') {
                $payload['packages'][0]['height_cm'] = '';
            } else {
                $payload['packages'] = [];
            }

            $this->post(route('customer.shipments.store'), $payload)
                ->assertSessionHasNoErrors()
                ->assertRedirect();
            $booking = Booking::where('goods_description', $payload['goodsDescription'])->firstOrFail();
            $this->assertVolume($booking, $scenario === 'blank' ? 0 : 3.2567);
        }
    }

    public function test_package_totals_match_for_centimeters_and_meters_in_staff_and_customer_orders(): void
    {
        $customer = Customer::create([
            'company' => 'Package Totals Customer',
            'contact_name' => 'Package Contact',
            'phone' => '770123458',
            'email' => 'package-totals@example.com',
            'password' => 'test-password',
            'portal_enabled' => true,
        ]);
        $this->actingAs($customer, 'customer')->withoutMiddleware();

        foreach (['staff', 'customer'] as $entryPoint) {
            foreach (['cm', 'm'] as $unit) {
                $payload = $this->doorPayload();
                $payload['goodsDescription'] = "Package totals {$entryPoint} {$unit}";
                $payload['package_dimension_unit'] = $unit;
                $payload['gross_weight_kg'] = '';
                $payload['weightKg'] = '';
                $payload['volume_cbm'] = 999;
                $payload['volumeCbm'] = 999;
                $payload['packages'][] = [
                    'quantity' => 2,
                    'package_type' => 'carton',
                    'length_cm' => 50,
                    'width_cm' => 40,
                    'height_cm' => 30,
                    'weight_kg' => 7.5,
                ];
                if ($unit === 'm') {
                    foreach ($payload['packages'] as &$package) {
                        foreach (['length_cm', 'width_cm', 'height_cm'] as $dimension) {
                            $package[$dimension] /= 100;
                        }
                    }
                    unset($package);
                }

                $this->post(route($entryPoint === 'staff' ? 'booking.store' : 'customer.shipments.store'), $payload)
                    ->assertSessionHasNoErrors()
                    ->assertRedirect();
                $booking = Booking::where('goods_description', $payload['goodsDescription'])->firstOrFail();
                $this->assertVolume($booking, 2.52);
                $this->assertEquals(135, $booking->weight_kg);
                $this->assertEquals(135, data_get($booking->cargo_details, 'cargo.gross_weight_kg'));
                $this->assertEquals(7, data_get($booking->cargo_details, 'cargo.packages_count'));
                $this->assertEquals(100, data_get($booking->cargo_details, 'packages.0.length_cm'));
                $this->assertSame($unit, data_get($booking->cargo_details, 'package_dimension_unit'));

                if ($entryPoint === 'staff') {
                    $displayLength = $unit === 'm' ? 1 : 100;
                    $this->get(route('orders.edit', $booking->id))
                        ->assertOk()
                        ->assertSee('name="packages[0][length_cm]" value="' . $displayLength . '"', false);
                    $payload['packages'][0]['quantity'] = 10;
                    $this->put(route('orders.update', $booking->id), $payload)
                        ->assertSessionHasNoErrors()
                        ->assertRedirect(route('orders.index'));
                    $this->assertVolume($booking->refresh(), 4.92);
                    $this->assertEquals(255, $booking->weight_kg);
                }
            }
        }
    }

    public function test_customer_door_order_keeps_manual_weight_when_only_dimensions_are_entered(): void
    {
        $customer = Customer::create([
            'company' => 'Manual Weight Customer',
            'contact_name' => 'Weight Contact',
            'phone' => '770123459',
            'email' => 'manual-weight@example.com',
            'password' => 'test-password',
            'portal_enabled' => true,
        ]);
        $payload = $this->doorPayload();
        $payload['weightKg'] = 78.5;
        $payload['packages'][0]['weight_kg'] = '';

        $this->actingAs($customer, 'customer')->post(route('customer.shipments.store'), $payload)
            ->assertSessionHasNoErrors()
            ->assertRedirect();
        $booking = Booking::where('customer_id', $customer->id)->firstOrFail();
        $this->assertVolume($booking, 2.4);
        $this->assertEquals(78.5, $booking->weight_kg);
    }

    private function doorPayload(): array
    {
        return [
            'shipping_category' => 'door_to_door',
            'route_mode' => 'DOOR_TO_DOOR',
            'shipmentType' => 'DOOR_TO_DOOR',
            'main_transport_mode' => 'SEA',
            'company' => 'Door Volume Customer',
            'contactName' => 'Door Contact',
            'phone' => '770123456',
            'email' => 'door-volume@example.com',
            'goodsDescription' => 'Door shipment cargo',
            'gross_weight_kg' => 120,
            'origin_country' => 'China',
            'origin_city' => 'Yiwu',
            'pickup_address' => 'Factory Warehouse',
            'destination_country' => 'Yemen',
            'destination_city' => 'Aden',
            'delivery_address' => 'Customer Warehouse',
            'packages' => [[
                'quantity' => 5,
                'package_type' => 'carton',
                'length_cm' => 100,
                'width_cm' => 80,
                'height_cm' => 60,
                'weight_kg' => 24,
            ]],
        ];
    }
}

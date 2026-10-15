<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentItemPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndUsersSeeder::class);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
    }

    public function test_hs_code_is_saved_for_every_supported_shipment_type_and_items_are_saved_for_itemized_types(): void
    {
        $shipmentTypes = ['FCL', 'LCL', 'LAND', 'Bulk', 'RoRo', 'AIR'];

        foreach ($shipmentTypes as $index => $shipmentType) {
            $payload = [
                'company' => "HS Code Customer {$index}",
                'contactName' => 'Cargo Contact',
                'phone' => '77000000' . $index,
                'email' => "hs-code-{$index}@example.com",
                'goodsDescription' => "Cargo {$shipmentType}",
                'weightKg' => 1200,
                'pol' => 'Shanghai',
                'pod' => 'Aden',
                'shipmentType' => $shipmentType,
                'hs_code' => '8429.51.' . $index,
                'cargo_items' => [
                    [
                        'name' => 'Main machinery',
                        'hs_code' => '8429.51.' . $index,
                        'quantity' => 2,
                        'unit' => $shipmentType === 'RoRo' ? 'vehicle' : 'piece',
                        'weight_kg' => 600,
                        'volume_cbm' => 4.5,
                    ],
                    [
                        'name' => 'Spare parts',
                        'hs_code' => '8431.49.' . $index,
                        'quantity' => 3,
                        'unit' => 'piece',
                        'weight_kg' => 80,
                        'volume_cbm' => 0.8,
                    ],
                ],
            ];

            if ($shipmentType === 'Bulk') {
                $payload += [
                    'bulk_pieces' => 2,
                    'bulk_piece_weight' => 600,
                    'bulk_length' => 6,
                    'bulk_width' => 2,
                    'bulk_height' => 2,
                ];
            }

            $this->withoutMiddleware()
                ->post(route('booking.store'), $payload)
                ->assertRedirect();

            $booking = Booking::where('customer_email', $payload['email'])->firstOrFail();

            $this->assertSame($payload['hs_code'], $booking->hsCode());
            $this->assertSame($payload['hs_code'], data_get($booking->cargo_details, 'hs_code'));

            if (in_array($shipmentType, ['AIR', 'Bulk', 'RoRo'], true)) {
                $this->assertCount(2, $booking->cargoItems());
                $this->assertSame('Spare parts', $booking->cargoItems()[1]['name']);
                $this->assertSame('8431.49.' . $index, $booking->cargoItems()[1]['hs_code']);
            }
        }
    }

    public function test_itemized_pricing_drives_sale_purchase_profit_quote_and_invoice_totals(): void
    {
        $agent = Agent::create([
            'name' => 'Item Pricing Agent',
            'type' => 'local',
            'phone' => '+967700123456',
            'email' => 'item-pricing-agent@example.com',
        ]);

        $booking = Booking::create([
            'id' => 'BK-ITEM-PRICING',
            'customer_company' => 'Item Pricing Customer',
            'customer_contact_name' => 'Pricing Contact',
            'customer_phone' => '+967700999888',
            'customer_email' => 'item-pricing@example.com',
            'shipment_type' => 'Bulk',
            'goods_description' => 'Machinery and spare parts',
            'weight_kg' => 1500,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
            'route_details' => [
                'category' => 'sea',
                'mode' => 'FOB',
            ],
            'cargo_details' => [
                'service_type' => 'bulk',
                'hs_code' => '8429.51',
                'cargo_items' => [
                    ['name' => 'Machinery', 'hs_code' => '8429.51', 'quantity' => 2, 'unit' => 'piece'],
                    ['name' => 'Spare parts', 'hs_code' => '8431.49', 'quantity' => 3, 'unit' => 'piece'],
                ],
            ],
        ]);

        $this->get(route('pricing.index'))
            ->assertOk()
            ->assertSee('HS CODE Number')
            ->assertSee('إضافة صنف جديد')
            ->assertSee('Machinery');

        $response = $this->withoutMiddleware()->post(route('pricing.store', $booking), [
            'transitDays' => 12,
            'validityDate' => now()->addDays(10)->toDateString(),
            'agent_id' => $agent->id,
            'itemPricingLines' => [
                [
                    'name' => 'Machinery',
                    'hs_code' => '8429.51',
                    'quantity' => 2,
                    'unit' => 'piece',
                    'sale_unit_price' => 150,
                    'purchase_unit_price' => 100,
                ],
                [
                    'name' => 'Spare parts',
                    'hs_code' => '8431.49',
                    'quantity' => 3,
                    'unit' => 'piece',
                    'sale_unit_price' => 50,
                    'purchase_unit_price' => 30,
                ],
            ],
            'customPricingCharges' => [
                ['label' => 'Handling', 'amount' => 25],
            ],
            'notes' => 'Itemized sale and purchase pricing',
        ]);

        $response->assertRedirect(route('pricing.index'));
        $response->assertSessionHasNoErrors();

        $booking->refresh();

        $this->assertSame('priced', $booking->status);
        $this->assertCount(2, $booking->itemPricingLines());
        $this->assertEquals(450.0, $booking->itemPricingTotal());
        $this->assertEquals(475.0, $booking->pricingTotal());
        $this->assertEquals(290.0, $booking->agent_purchase_amount);
        $this->assertEquals(5.0, $booking->agent_purchase_quantity);
        $this->assertEquals(185.0, $booking->profit_amount);

        $this->get(route('quote.show', $booking))
            ->assertOk()
            ->assertSee('Machinery')
            ->assertSee('8429.51')
            ->assertSee('Spare parts')
            ->assertSee('8431.49');

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertOk()
            ->assertSee('Machinery')
            ->assertSee('8429.51')
            ->assertSee('Spare parts')
            ->assertSee('8431.49');

        $invoice = Invoice::create([
            'id' => 'INV-ITEM-PRICING',
            'booking_id' => $booking->id,
            'invoice_type' => 'freight',
            'amount' => $booking->pricingTotal(),
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->view('invoice-document', compact('invoice', 'booking'))
            ->assertSee('Machinery')
            ->assertSee('8429.51')
            ->assertSee('Spare parts')
            ->assertSee('8431.49')
            ->assertSee('$ 475.00')
            ->assertDontSee('$ 100.00')
            ->assertDontSee('$ 30.00');
    }

    public function test_invoice_document_shows_the_pricing_unit_for_air_and_door_to_door_shipments(): void
    {
        app()->setLocale('ar');

        $airBooking = Booking::create([
            'id' => 'BK-INVOICE-AIR-UNIT',
            'customer_company' => 'Air Invoice Customer',
            'customer_contact_name' => 'Air Contact',
            'customer_phone' => '+967700000101',
            'customer_email' => 'air-invoice-unit@example.com',
            'shipment_type' => 'AIR',
            'goods_description' => 'Air cargo',
            'weight_kg' => 800,
            'volume_cbm' => 12,
            'pol' => 'Sana\'a Airport',
            'pod' => 'Dubai Airport',
            'status' => 'sent',
            'ocean_freight' => 960,
            'origin_charges' => 120,
            'dthc' => 60,
            'war_risk_surcharge' => 0,
            'other_charges' => 0,
            'cargo_details' => [
                'air_pricing' => [
                    'basis' => 'cbm',
                    'volume_cbm' => 12,
                    'unit_prices' => [
                        'air_freight' => 80,
                        'origin_charges' => 10,
                        'dthc' => 5,
                    ],
                ],
            ],
            'route_details' => ['category' => 'air'],
            'priced_at' => now(),
        ]);

        $airInvoice = Invoice::create([
            'id' => 'INV-AIR-UNIT',
            'booking_id' => $airBooking->id,
            'amount' => 1140,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->view('invoice-document', ['invoice' => $airInvoice, 'booking' => $airBooking])
            ->assertSee('12', false)
            ->assertSee('CBM')
            ->assertSee('80.00');

        $doorBooking = Booking::create([
            'id' => 'BK-INVOICE-TON-UNIT',
            'customer_company' => 'Door Invoice Customer',
            'customer_contact_name' => 'Door Contact',
            'customer_phone' => '+967700000102',
            'customer_email' => 'door-invoice-unit@example.com',
            'shipment_type' => 'DOOR_TO_DOOR',
            'goods_description' => 'Door cargo',
            'weight_kg' => 1500,
            'volume_cbm' => 2.5,
            'pol' => 'Factory',
            'pod' => 'Warehouse',
            'status' => 'sent',
            'ocean_freight' => 675,
            'origin_charges' => 0,
            'dthc' => 0,
            'war_risk_surcharge' => 0,
            'other_charges' => 0,
            'cargo_details' => [
                'service_type' => 'door_to_door',
                'door_to_door_pricing' => [
                    'gross_weight_kg' => 1500,
                    'tons' => 1.5,
                    'unit_price_per_ton' => 450,
                    'total' => 675,
                ],
            ],
            'route_details' => ['category' => 'door_to_door', 'mode' => 'DOOR_TO_DOOR'],
            'priced_at' => now(),
        ]);

        $doorInvoice = Invoice::create([
            'id' => 'INV-TON-UNIT',
            'booking_id' => $doorBooking->id,
            'amount' => 675,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->view('invoice-document', ['invoice' => $doorInvoice, 'booking' => $doorBooking])
            ->assertSee('1.5', false)
            ->assertSee('طن')
            ->assertSee('450.00');
    }
}

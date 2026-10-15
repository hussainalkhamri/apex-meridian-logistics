<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomsDeclarationCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customs_declaration_customer_endpoint_creates_customer(): void
    {
        $response = $this->withoutMiddleware()->postJson(route('customs.declaration.customer'), [
            'company' => 'Customs Client Co',
            'contact_name' => 'Customs Contact',
            'phone' => '+967700123456',
            'email' => 'customs-client@example.com',
            'notes' => 'Created from customs declaration step.',
        ]);

        $response->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('customer.company', 'Customs Client Co')
            ->assertJsonPath('customer.contact_name', 'Customs Contact');

        $this->assertDatabaseHas('customers', [
            'company' => 'Customs Client Co',
            'contact_name' => 'Customs Contact',
            'phone' => '+967700123456',
            'email' => 'customs-client@example.com',
        ]);
    }

    public function test_customs_declaration_customer_endpoint_updates_selected_customer(): void
    {
        $customer = Customer::create([
            'company' => 'Old Company',
            'contact_name' => 'Old Contact',
            'phone' => '+967700000000',
            'email' => 'old@example.com',
            'notes' => 'Old notes',
        ]);

        $response = $this->withoutMiddleware()->postJson(route('customs.declaration.customer'), [
            'customer_id' => $customer->id,
            'company' => 'Updated Company',
            'contact_name' => 'Updated Contact',
            'phone' => '+967711111111',
            'email' => 'updated@example.com',
            'notes' => 'Updated from customs declaration.',
        ]);

        $response->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('customer.id', $customer->id)
            ->assertJsonPath('customer.company', 'Updated Company');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'company' => 'Updated Company',
            'contact_name' => 'Updated Contact',
            'phone' => '+967711111111',
            'email' => 'updated@example.com',
        ]);
    }

    public function test_customs_declaration_review_confirmation_creates_clearance_booking(): void
    {
        $response = $this->withoutMiddleware()->postJson(route('customs.declaration.store'), [
            'declaration_type' => 'import',
            'file_name' => 'customs-file',
            'serial_no' => 'SER-100',
            'shipment_no' => 'SHIP-100',
            'importer_code' => 'IMP-100',
            'customer' => 'Review Client',
            'declarant_code' => 'DEC-100',
            'declarant_name' => 'Declarant Name',
            'customs_office_code' => '0309',
            'declaration_no' => 'DEC-NO-100',
            'bayan_no' => 'BAYAN-100',
            'customs_center' => 'Aden Customs',
            'country' => 'Yemen',
            'destination' => 'Aden',
            'goods_type' => 'Electronics',
            'driver' => 'Driver Name',
            'truck' => 'TRK-100',
            'arrival_date' => '2026-08-15',
            'note' => 'Created from review confirmation.',
            'customer_company' => 'Review Client Co',
            'customer_contact_name' => 'Review Contact',
            'customer_phone' => '+967733333333',
            'customer_email' => 'review-client@example.com',
            'customer_notes' => 'Customer from review step.',
            'items' => [
                [
                    'name' => 'Mobile phones',
                    'brand' => 'Brand A',
                    'origin' => 'China',
                    'count' => 10,
                    'type' => 'Carton',
                    'packages' => 2,
                    'price_unit' => 'Piece',
                    'package_weight_kg' => 25,
                    'net_weight_kg' => 50,
                    'value_usd' => 1200,
                    'price' => 120,
                    'reference' => 'REF-100',
                    'hs' => '8517.13',
                ],
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('saved', true)
            ->assertJsonPath('booking.customs_status', 'pending')
            ->assertJsonPath('customer.company', 'Review Client Co');

        $bookingId = $response->json('booking.id');
        $booking = Booking::findOrFail($bookingId);

        $this->assertSame('CUSTOMS', $booking->shipment_type);
        $this->assertSame('approved', $booking->status);
        $this->assertSame('pending', $booking->customs_status);
        $this->assertSame('SHIP-100', $booking->customs_reference);
        $this->assertSame('DEC-NO-100', $booking->customs_declaration_no);
        $this->assertSame('BAYAN-100', $booking->customs_bayan_no);
        $this->assertSame('Aden Customs', $booking->pol);
        $this->assertSame('Aden', $booking->pod);
        $this->assertSame('customs_clearance', $booking->cargo_details['service_type']);
        $this->assertEquals(2.0, $booking->cargo_details['totals']['packages']);
        $this->assertEquals(50.0, $booking->cargo_details['totals']['net_weight_kg']);
        $this->assertSame('CUSTOMS', $booking->route_details['mode']);

        $this->assertDatabaseHas('customers', [
            'company' => 'Review Client Co',
            'contact_name' => 'Review Contact',
            'phone' => '+967733333333',
            'email' => 'review-client@example.com',
        ]);
    }
}

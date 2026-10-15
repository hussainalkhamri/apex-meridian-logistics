<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use App\Support\BookingQuoteMessage;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteDocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndUsersSeeder::class);
        $this->actingAs(User::where('username', 'admin')->firstOrFail());
    }

    public function test_quote_print_uses_raw_route_and_container_labels_when_translation_is_missing(): void
    {
        $booking = Booking::create([
            'id' => 'BK-QUOTE-LABELS',
            'customer_company' => 'Quote Customer',
            'customer_contact_name' => 'Quote Contact',
            'customer_phone' => '+967700000001',
            'customer_email' => 'quote-labels@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 2,
            'goods_description' => 'Quote label goods',
            'weight_kg' => 1200,
            'volume_cbm' => 10,
            'pol' => 'Custom POL Port',
            'pod' => 'Custom POD Port',
            'status' => 'sent',
            'ocean_freight' => 1000,
            'origin_charges' => 100,
            'dthc' => 50,
            'war_risk_surcharge' => 25,
            'other_charges' => 25,
            'destination_port_change_fee' => 40,
            'other_penalties' => 20,
            'transit_days' => 15,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
        ]);

        $this->get(route('quote.show', $booking))
            ->assertStatus(200)
            ->assertSee('Custom POL Port')
            ->assertSee('Custom POD Port')
            ->assertSee('2x40HC')
            ->assertSee('رسوم تغيير ميناء الوجهة')
            ->assertSee('غرامات أخرى')
            ->assertDontSee('messages.Custom POL Port', false)
            ->assertDontSee('messages.Custom POD Port', false)
            ->assertDontSee('messages.40HC', false);

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertStatus(200)
            ->assertSee('Custom POL Port')
            ->assertSee('Custom POD Port')
            ->assertSee('2x40HC')
            ->assertSee('رسوم تغيير ميناء الوجهة')
            ->assertSee('غرامات أخرى')
            ->assertDontSee('messages.Custom POL Port', false)
            ->assertDontSee('messages.Custom POD Port', false)
            ->assertDontSee('messages.40HC', false);
    }

    public function test_quote_message_uses_booking_data_and_active_locale(): void
    {
        $booking = Booking::create([
            'id' => 'BK-QUOTE-MESSAGE-AR',
            'customer_company' => 'Main Customer Company',
            'customer_contact_name' => 'Operations Contact Person',
            'customer_phone' => '+966500000001',
            'customer_email' => 'customer@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HQ',
            'container_qty' => 1,
            'goods_description' => 'Machinery',
            'weight_kg' => 16000,
            'volume_cbm' => 82,
            'pol' => 'Mundra',
            'pod' => 'Salalah',
            'status' => 'sent',
            'ocean_freight' => 3000,
            'origin_charges' => 200,
            'dthc' => 300,
            'war_risk_surcharge' => 150,
            'other_charges' => 100,
            'transit_days' => 2,
            'validity_date' => now()->addDays(5),
            'priced_at' => now(),
            'cargo_details' => [
                'shipping_line' => 'MLO',
                'free_time' => '5 days',
                'tos' => 'FOB',
            ],
        ]);

        $ar = BookingQuoteMessage::forBooking($booking, 'ar');
        $this->assertStringStartsWith("الطلب: BK-QUOTE-MESSAGE-AR\nنجم الشمال لاين للشحن والخدمات اللوجستية\nعرض سعر شحن", $ar);
        $this->assertStringContainsString('1- ميناء التحميل: Mundra', $ar);
        $this->assertStringContainsString('2- مكان التفريغ: Salalah', $ar);
        $this->assertStringContainsString('3- السعر: $ 3,750.00', $ar);
        $this->assertStringContainsString('4- مدة النقل: 2 يوم', $ar);
        $this->assertStringContainsString('5- الفترة المجانية: 5 days', $ar);
        $this->assertStringContainsString('6- شركة الشحن: MLO', $ar);
        $this->assertStringContainsString('7- TOS: FOB', $ar);
        $this->assertStringContainsString('8- اسم العميل: Main Customer Company', $ar);
        $this->assertStringNotContainsString('ETD:', $ar);
        $this->assertStringNotContainsString('—', $ar);
        $this->assertStringNotContainsString('Operations Contact Person', $ar);
        $this->assertStringContainsString('وصف البضاعة: Machinery', $ar);
        $this->assertStringContainsString('تفاصيل الحاويات: 1x40HQ', $ar);
        $this->assertStringContainsString('عدد الحاويات: 1 حاوية', $ar);
        $this->assertStringContainsString('الحجم: 82 CBM', $ar);
        $this->assertStringContainsString('الوزن الإجمالي: 16,000 كجم', $ar);
        $this->assertStringContainsString("ملاحظة:\n• العرض خاضع لتوافر المساحة والحاويات في وقت الحجز.", $ar);
        $this->assertStringNotContainsString('تحية طيبة', $ar);

        $en = BookingQuoteMessage::forBooking($booking, 'en');
        $this->assertStringStartsWith("Order: BK-QUOTE-MESSAGE-AR\nLodeStar Line for Shipping and Logistics\nFreight Quotation", $en);
        $this->assertStringContainsString('1- Port of Loading: Mundra', $en);
        $this->assertStringContainsString('2- Place of Discharge: Salalah', $en);
        $this->assertStringContainsString('3- Rate: $ 3,750.00', $en);
        $this->assertStringContainsString('4- Transit Time: 2 days', $en);
        $this->assertStringContainsString('5- Free Time: 5 days', $en);
        $this->assertStringContainsString('6- Shipping Line: MLO', $en);
        $this->assertStringContainsString('7- TOS: FOB', $en);
        $this->assertStringContainsString('8- Customer Name: Main Customer Company', $en);
        $this->assertStringNotContainsString('ETD:', $en);
        $this->assertStringNotContainsString('—', $en);
        $this->assertStringNotContainsString('Operations Contact Person', $en);
        $this->assertStringContainsString('Goods Description: Machinery', $en);
        $this->assertStringContainsString('Container Details: 1x40HQ', $en);
        $this->assertStringContainsString('Container Quantity: 1 container', $en);
        $this->assertStringContainsString('Volume: 82 CBM', $en);
        $this->assertStringContainsString('Gross Weight: 16,000 KG', $en);
        $this->assertStringContainsString("Note:\n• Offer is subject to availability of space and containers at the time of booking.", $en);
        $this->assertStringNotContainsString('Dear ', $en);
    }

    public function test_quote_message_uses_cbm_as_the_quantity_for_lcl_shipments(): void
    {
        $booking = Booking::create([
            'id' => 'BK-QUOTE-MESSAGE-LCL',
            'customer_company' => 'LCL Customer',
            'customer_contact_name' => 'LCL Contact',
            'customer_phone' => '+967700000014',
            'customer_email' => 'lcl-message@example.com',
            'shipment_type' => 'LCL',
            'goods_description' => 'Furniture',
            'weight_kg' => 850,
            'volume_cbm' => 12.5,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'status' => 'priced',
            'ocean_freight' => 900,
            'origin_charges' => 100,
            'transit_days' => 6,
            'validity_date' => now()->addDays(7),
            'priced_at' => now(),
        ]);

        $ar = BookingQuoteMessage::forBooking($booking, 'ar');
        $this->assertStringContainsString('الكمية: 12.5 CBM', $ar);
        $this->assertSame(1, substr_count($ar, '12.5 CBM'));

        $en = BookingQuoteMessage::forBooking($booking, 'en');
        $this->assertStringContainsString('Quantity: 12.5 CBM', $en);
        $this->assertSame(1, substr_count($en, '12.5 CBM'));

        $this->get(route('sales.index'))
            ->assertOk()
            ->assertSee('quote_message_ar', false)
            ->assertSee('الكمية: 12.5 CBM');
    }

    public function test_quote_print_uses_multi_container_summary(): void
    {
        $booking = Booking::create([
            'id' => 'BK-QUOTE-MULTI-FCL',
            'customer_company' => 'Quote Multi Customer',
            'customer_contact_name' => 'Quote Multi Contact',
            'customer_phone' => '+967700000011',
            'customer_email' => 'quote-multi@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40GP',
            'container_qty' => 3,
            'cargo_details' => [
                'service_type' => 'fcl',
                'containers' => [
                    ['type' => '40GP', 'qty' => 3],
                    ['type' => '20GP', 'qty' => 2],
                ],
                'container_summary' => '3x40GP + 2x20GP',
                'container_total_qty' => 5,
            ],
            'goods_description' => 'Mixed quote cargo',
            'weight_kg' => 1200,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 1000,
            'origin_charges' => 100,
            'dthc' => 50,
            'war_risk_surcharge' => 25,
            'other_charges' => 25,
            'transit_days' => 15,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
        ]);

        $this->get(route('quote.show', $booking))
            ->assertStatus(200)
            ->assertSee('3x40GP + 2x20GP')
            ->assertDontSee('3x 40GP');

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertStatus(200)
            ->assertSee('3x40GP + 2x20GP')
            ->assertDontSee('3x 40GP');
    }

    public function test_air_quote_print_uses_package_summary_instead_of_lcl(): void
    {
        $booking = Booking::create([
            'id' => 'BK-QUOTE-AIR-PACKAGES',
            'customer_company' => 'Air Package Customer',
            'customer_contact_name' => 'Air Contact',
            'customer_phone' => '+967700000012',
            'customer_email' => 'air-packages@example.com',
            'shipment_type' => 'AIR',
            'goods_description' => 'Air package cargo',
            'weight_kg' => 120,
            'volume_cbm' => 1.2,
            'pol' => 'SAH',
            'pod' => 'DXB',
            'status' => 'sent',
            'ocean_freight' => 600,
            'origin_charges' => 120,
            'dthc' => 80,
            'transit_days' => 3,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
            'route_details' => [
                'category' => 'air',
                'mode' => 'AIR',
            ],
            'cargo_details' => [
                'service_type' => 'air_freight',
                'packages' => [
                    ['quantity' => 2, 'package_type' => 'carton'],
                    ['quantity' => 3, 'package_type' => 'pallet'],
                ],
                'cargo' => [
                    'package_type' => 'carton',
                    'pieces_count' => 5,
                    'volume_cbm' => 1.2,
                    'chargeable_weight_kg' => 200,
                ],
                'air_pricing' => [
                    'basis' => 'cbm',
                    'volume_cbm' => 1.2,
                    'unit_prices' => [
                        'air_freight' => 500,
                        'origin_charges' => 100,
                        'dthc' => 66.67,
                    ],
                ],
            ],
        ]);

        $this->get(route('quote.show', $booking))
            ->assertOk()
            ->assertSee('2x carton + 3x pallet')
            ->assertDontSee('LCL');

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertOk()
            ->assertSee('2x carton + 3x pallet')
            ->assertDontSee('LCL');
    }

    public function test_roro_quote_print_uses_vehicle_summary_instead_of_lcl(): void
    {
        $booking = Booking::create([
            'id' => 'BK-QUOTE-RORO-VEHICLE',
            'customer_company' => 'RoRo Vehicle Customer',
            'customer_contact_name' => 'RoRo Contact',
            'customer_phone' => '+967700000013',
            'customer_email' => 'roro-quote@example.com',
            'shipment_type' => 'RoRo',
            'goods_description' => 'Vehicle shipment',
            'weight_kg' => 2200,
            'volume_cbm' => 12,
            'pol' => 'Jeddah Islamic Port',
            'pod' => 'Aden Port',
            'status' => 'sent',
            'ocean_freight' => 900,
            'origin_charges' => 150,
            'dthc' => 120,
            'transit_days' => 7,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
            'cargo_details' => [
                'service_type' => 'roro',
                'vehicle_type' => 'SUV',
                'manufacturer' => 'Toyota',
                'model' => 'Land Cruiser',
                'year' => 2024,
                'weight' => 2200,
                'length' => 4.9,
                'width' => 1.9,
                'height' => 1.8,
            ],
        ]);

        $this->get(route('quote.show', $booking))
            ->assertOk()
            ->assertSee('RoRo - SUV Toyota Land Cruiser 2024')
            ->assertDontSee('LCL');

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertOk()
            ->assertSee('RoRo - SUV Toyota Land Cruiser 2024')
            ->assertDontSee('LCL');
    }

    public function test_bulk_quote_print_uses_bulk_summary_instead_of_lcl(): void
    {
        $booking = Booking::create([
            'id' => 'BK-QUOTE-BULK-CARGO',
            'customer_company' => 'Bulk Quote Customer',
            'customer_contact_name' => 'Bulk Contact',
            'customer_phone' => '+967700000014',
            'customer_email' => 'bulk-quote@example.com',
            'shipment_type' => 'Bulk',
            'goods_description' => 'Steel coils shipment',
            'weight_kg' => 34.5,
            'volume_cbm' => 13.5,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 750,
            'origin_charges' => 100,
            'dthc' => 80,
            'transit_days' => 14,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
            'cargo_details' => [
                'cargo_name' => 'Steel Coils',
                'pieces' => 3,
                'piece_weight' => 11.5,
                'total_weight' => 34.5,
                'gross_weight_kg' => 34.5,
                'length' => 1.5,
                'width' => 1.5,
                'height' => 2,
                'dimension_unit' => 'cm',
                'piece_cbm' => 4.5,
                'total_cbm' => 13.5,
                'volume_cbm' => 13.5,
                'packaging_type' => 'Bundle',
            ],
        ]);

        $this->get(route('quote.show', $booking))
            ->assertOk()
            ->assertSee('Bulk - Steel Coils - 3x Bundle - 13.5 CBM')
            ->assertDontSee('LCL');

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertOk()
            ->assertSee('Bulk - Steel Coils - 3x Bundle - 13.5 CBM')
            ->assertDontSee('LCL');
    }

    public function test_quote_print_shows_itemized_fcl_container_breakdown_for_mixed_fcl(): void
    {
        $booking = Booking::create([
            'id' => 'BK-QUOTE-MIXED-FCL-PRICING',
            'customer_company' => 'Mixed Pricing Customer',
            'customer_contact_name' => 'Mixed Pricing Contact',
            'customer_phone' => '+967700000041',
            'customer_email' => 'mixed-pricing@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40GP',
            'container_qty' => 2,
            'cargo_details' => [
                'service_type' => 'fcl',
                'containers' => [
                    ['type' => '40GP', 'qty' => 2],
                    ['type' => '20GP', 'qty' => 3],
                ],
                'container_summary' => '2x40GP + 3x20GP',
                'container_total_qty' => 5,
            ],
            'goods_description' => 'Mixed pricing cargo',
            'weight_kg' => 2000,
            'volume_cbm' => 12,
            'pol' => 'Qingdao',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 800,
            'origin_charges' => 200,
            'dthc' => 100,
            'war_risk_surcharge' => 50,
            'other_charges' => 50,
            'transit_days' => 18,
            'validity_date' => now()->addDays(12),
            'priced_at' => now(),
        ]);

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertStatus(200)
            ->assertSee('2x40GP')
            ->assertSee('3x20GP')
            ->assertSee('$ 480.00')
            ->assertSee('$ 720.00')
            ->assertSee('$ 1,200.00');
    }

    public function test_exw_charges_appear_in_quote_and_invoice_documents_only_when_present(): void
    {
        app()->setLocale('ar');

        $booking = Booking::create([
            'id' => 'BK-QUOTE-EXW-CHARGES',
            'customer_company' => 'EXW Document Customer',
            'customer_contact_name' => 'EXW Document Contact',
            'customer_phone' => '+967700000051',
            'customer_email' => 'exw-document@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'EXW document cargo',
            'weight_kg' => 1200,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 1000,
            'origin_charges' => 100,
            'exw_charges' => 75,
            'dthc' => 50,
            'war_risk_surcharge' => 25,
            'other_charges' => 25,
            'route_details' => [
                'category' => 'sea',
                'mode' => 'EXW',
            ],
            'transit_days' => 15,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
        ]);

        $this->get(route('quote.show', $booking))
            ->assertOk()
            ->assertSee('أجور EXW')
            ->assertSee('$ 75.00');

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertOk()
            ->assertSee('أجور EXW')
            ->assertSee('$ 75.00');

        $invoice = Invoice::create([
            'id' => 'INV-EXW-CHARGES',
            'booking_id' => $booking->id,
            'amount' => $booking->pricingTotal(),
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->view('invoice-document', ['invoice' => $invoice, 'booking' => $booking])
            ->assertSee('أجور EXW')
            ->assertSee('$ 75.00');

        $fobBooking = Booking::create([
            'id' => 'BK-QUOTE-FOB-NO-EXW',
            'customer_company' => 'FOB Document Customer',
            'customer_contact_name' => 'FOB Document Contact',
            'customer_phone' => '+967700000052',
            'customer_email' => 'fob-document@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20GP',
            'container_qty' => 1,
            'goods_description' => 'FOB document cargo',
            'weight_kg' => 800,
            'volume_cbm' => 8,
            'pol' => 'Ningbo',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 600,
            'origin_charges' => 80,
            'exw_charges' => 75,
            'dthc' => 40,
            'war_risk_surcharge' => 10,
            'other_charges' => 20,
            'route_details' => [
                'category' => 'sea',
                'mode' => 'FOB',
            ],
            'transit_days' => 12,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
        ]);

        $this->get(route('quote.show', $fobBooking))
            ->assertOk()
            ->assertDontSee('أجور EXW');

        $fobInvoice = Invoice::create([
            'id' => 'INV-FOB-NO-EXW',
            'booking_id' => $fobBooking->id,
            'amount' => $fobBooking->pricingTotal(),
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->view('invoice-document', ['invoice' => $fobInvoice, 'booking' => $fobBooking])
            ->assertDontSee('أجور EXW');
    }

    public function test_custom_pricing_charge_lines_appear_in_quote_and_invoice_documents(): void
    {
        app()->setLocale('ar');

        $booking = Booking::create([
            'id' => 'BK-QUOTE-CUSTOM-CHARGES',
            'customer_company' => 'Custom Charge Customer',
            'customer_contact_name' => 'Custom Charge Contact',
            'customer_phone' => '+967700000053',
            'customer_email' => 'custom-charge@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Custom charge document cargo',
            'weight_kg' => 1200,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 1000,
            'origin_charges' => 100,
            'dthc' => 50,
            'war_risk_surcharge' => 25,
            'other_charges' => 25,
            'cargo_details' => [
                'custom_pricing_charges' => [
                    ['label' => 'رسوم فحص خاصة', 'amount' => 120],
                    ['label' => 'خدمة تغليف إضافية', 'amount' => 35.5],
                ],
            ],
            'transit_days' => 15,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
        ]);

        $this->assertSame(1355.5, $booking->pricingTotal());

        $this->get(route('quote.show', $booking))
            ->assertOk()
            ->assertSee('رسوم فحص خاصة')
            ->assertSee('خدمة تغليف إضافية')
            ->assertSee('$ 120.00')
            ->assertSee('$ 35.50');

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertOk()
            ->assertSee('رسوم فحص خاصة')
            ->assertSee('خدمة تغليف إضافية')
            ->assertSee('$ 120.00')
            ->assertSee('$ 35.50');

        $invoice = Invoice::create([
            'id' => 'INV-CUSTOM-CHARGES',
            'booking_id' => $booking->id,
            'amount' => $booking->pricingTotal(),
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->view('invoice-document', ['invoice' => $invoice, 'booking' => $booking])
            ->assertSee('رسوم فحص خاصة')
            ->assertSee('خدمة تغليف إضافية')
            ->assertSee('$ 120.00')
            ->assertSee('$ 35.50');
    }

    public function test_door_to_door_quote_uses_special_pricing_layout_and_labels(): void
    {
        app()->setLocale('ar');

        $booking = Booking::create([
            'id' => 'BK-QUOTE-DOOR',
            'customer_company' => 'Door Quote Customer',
            'customer_contact_name' => 'Door Contact',
            'customer_phone' => '+967700000021',
            'customer_email' => 'door-quote@example.com',
            'shipment_type' => 'DOOR_TO_DOOR',
            'goods_description' => 'Door-to-door cargo',
            'weight_kg' => 1500,
            'volume_cbm' => 2.5,
            'pol' => 'Factory Warehouse',
            'pod' => 'Customer Warehouse',
            'status' => 'sent',
            'ocean_freight' => 675,
            'origin_charges' => 0,
            'dthc' => 0,
            'war_risk_surcharge' => 0,
            'other_charges' => 0,
            'transit_days' => 6,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
            'cargo_details' => [
                'service_type' => 'door_to_door',
                'door_to_door_pricing' => [
                    'gross_weight_kg' => 1500,
                    'tons' => 1.5,
                    'unit_price_per_ton' => 450,
                    'total' => 675,
                ],
            ],
            'route_details' => [
                'category' => 'door_to_door',
                'mode' => 'DOOR_TO_DOOR',
            ],
        ]);

        $this->get(route('quote.show', $booking))
            ->assertStatus(200)
            ->assertSee('عرض سعر شحن باب إلى باب')
            ->assertSee('سعر الطن الواحد')
            ->assertSee('الوزن الإجمالي')
            ->assertSee('خدمة النقل من الباب إلى الباب')
            ->assertDontSee('خدمة النقل البري من الباب إلى الباب')
            ->assertSee('وتشمل الأسعار الرسوم الجمركية')
            ->assertDontSee('ولا تشمل الأسعار أي رسوم جمركية')
            ->assertDontSee('الاستلام والتسليم')
            ->assertDontSee('الغرامات')
            ->assertDontSee('ما لم يُنص صراحةً على خلاف ذلك في عرض السعر')
            ->assertDontSee('أي تغيير في العنوان')
            ->assertDontSee('تغليف وتحميل البضاعة')
            ->assertSee('675');

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertStatus(200)
            ->assertSee('عرض سعر شحن باب إلى باب')
            ->assertSee('سعر الطن الواحد')
            ->assertSee('الوزن الإجمالي')
            ->assertSee('خدمة النقل من الباب إلى الباب')
            ->assertDontSee('خدمة النقل البري من الباب إلى الباب')
            ->assertSee('وتشمل الأسعار الرسوم الجمركية')
            ->assertDontSee('ولا تشمل الأسعار أي رسوم جمركية')
            ->assertDontSee('الاستلام والتسليم')
            ->assertDontSee('الغرامات')
            ->assertDontSee('ما لم يُنص صراحةً على خلاف ذلك في عرض السعر')
            ->assertDontSee('أي تغيير في العنوان')
            ->assertDontSee('تغليف وتحميل البضاعة');
    }

    public function test_door_to_door_quote_and_invoice_use_selected_cbm_pricing(): void
    {
        app()->setLocale('ar');

        $booking = Booking::create([
            'id' => 'BK-QUOTE-DOOR-CBM',
            'customer_company' => 'Door CBM Quote Customer',
            'customer_contact_name' => 'Door CBM Contact',
            'customer_phone' => '+967700000022',
            'customer_email' => 'door-cbm-quote@example.com',
            'shipment_type' => 'DOOR_TO_DOOR',
            'goods_description' => 'Door-to-door CBM cargo',
            'weight_kg' => 1500,
            'volume_cbm' => 3.25,
            'pol' => 'Factory Warehouse',
            'pod' => 'Customer Warehouse',
            'status' => 'sent',
            'ocean_freight' => 585,
            'origin_charges' => 0,
            'dthc' => 0,
            'war_risk_surcharge' => 0,
            'other_charges' => 0,
            'transit_days' => 6,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
            'cargo_details' => [
                'service_type' => 'door_to_door',
                'door_to_door_pricing' => [
                    'basis' => 'cbm',
                    'quantity' => 3.25,
                    'unit_price' => 180,
                    'gross_weight_kg' => 1500,
                    'tons' => 1.5,
                    'volume_cbm' => 3.25,
                    'unit_price_per_cbm' => 180,
                    'total' => 585,
                ],
            ],
            'route_details' => [
                'category' => 'door_to_door',
                'mode' => 'DOOR_TO_DOOR',
            ],
        ]);

        $this->get(route('quote.show', $booking))
            ->assertOk()
            ->assertSee('سعر الـ CBM الواحد')
            ->assertSee('الحجم بالـ CBM')
            ->assertSee('3.25', false);

        $this->get(route('quote.show', ['id' => $booking->id, 'template' => 'modern']))
            ->assertOk()
            ->assertSee('سعر الـ CBM الواحد')
            ->assertSee('الحجم بالـ CBM')
            ->assertSee('3.25', false);

        $invoice = Invoice::create([
            'id' => 'INV-QUOTE-DOOR-CBM',
            'booking_id' => $booking->id,
            'amount' => 585,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->view('invoice-document', ['invoice' => $invoice, 'booking' => $booking])
            ->assertSee('3.25', false)
            ->assertSee('CBM')
            ->assertSee('180.00');
    }

    public function test_sales_queue_shows_door_to_door_pricing_summary_with_ton_rate(): void
    {
        app()->setLocale('ar');
        $this->seed(RolesAndUsersSeeder::class);

        $booking = Booking::create([
            'id' => 'BK-SALES-DOOR',
            'customer_company' => 'Sales Door Customer',
            'customer_contact_name' => 'Sales Contact',
            'customer_phone' => '+967700000031',
            'customer_email' => 'sales-door@example.com',
            'shipment_type' => 'DOOR_TO_DOOR',
            'goods_description' => 'Door-to-door sales cargo',
            'weight_kg' => 2500,
            'volume_cbm' => 3.5,
            'pol' => 'Jeddah',
            'pod' => 'Sanaa',
            'status' => 'sent',
            'ocean_freight' => 1250,
            'origin_charges' => 0,
            'dthc' => 0,
            'war_risk_surcharge' => 0,
            'other_charges' => 0,
            'transit_days' => 5,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
            'cargo_details' => [
                'service_type' => 'door_to_door',
                'door_to_door_pricing' => [
                    'gross_weight_kg' => 2500,
                    'tons' => 2.5,
                    'unit_price_per_ton' => 500,
                    'total' => 1250,
                ],
            ],
            'route_details' => [
                'category' => 'door_to_door',
                'mode' => 'DOOR_TO_DOOR',
            ],
        ]);

        $salesUser = User::where('username', 'sales_user')->firstOrFail();

        $message = BookingQuoteMessage::forBooking($booking, 'ar');
        $this->assertStringContainsString('الكمية: 2.5 طن', $message);

        $this->actingAs($salesUser)
            ->get(route('sales.index'))
            ->assertStatus(200)
            ->assertSee('تفصيل سعر الشحن من الباب إلى الباب')
            ->assertSee('سعر الطن الواحد')
            ->assertSee('الوزن بالطن');
    }

    public function test_invoice_document_uses_booking_route_mode_in_description(): void
    {
        app()->setLocale('en');

        $booking = Booking::create([
            'id' => 'BK-INVOICE-ROUTE',
            'customer_company' => 'Invoice Customer',
            'customer_contact_name' => 'Invoice Contact',
            'customer_phone' => '+967700000002',
            'customer_email' => 'invoice-route@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Invoice goods',
            'weight_kg' => 500,
            'volume_cbm' => 5,
            'pol' => 'Aden',
            'pod' => 'Jebel Ali',
            'status' => 'sent',
            'ocean_freight' => 800,
            'origin_charges' => 50,
            'dthc' => 30,
            'war_risk_surcharge' => 10,
            'other_charges' => 20,
            'route_details' => [
                'mode' => 'FOB',
                'origin' => ['country' => 'Yemen', 'city' => null, 'port' => 'Aden'],
                'destination' => ['country' => 'UAE', 'city' => null, 'port' => 'Jebel Ali'],
            ],
            'priced_at' => now(),
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-ROUTE-MODE',
            'booking_id' => $booking->id,
            'amount' => 900,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->view('invoice-document', ['invoice' => $invoice, 'booking' => $booking])
            ->assertSee('FOB plus sea freight rate')
            ->assertDontSee('EXW plus sea freight rate');
    }

    public function test_invoice_document_footer_and_direction_follow_arabic_locale(): void
    {
        app()->setLocale('ar');

        $booking = Booking::create([
            'id' => 'BK-INVOICE-AR',
            'customer_company' => 'عميل الفاتورة',
            'customer_contact_name' => 'مسؤول الفاتورة',
            'customer_phone' => '+967700000003',
            'customer_email' => 'invoice-ar@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'بضائع الفاتورة',
            'weight_kg' => 500,
            'volume_cbm' => 5,
            'pol' => 'عدن',
            'pod' => 'جبل علي',
            'status' => 'sent',
            'ocean_freight' => 800,
            'origin_charges' => 50,
            'dthc' => 30,
            'war_risk_surcharge' => 10,
            'other_charges' => 20,
            'priced_at' => now(),
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-AR-DOC',
            'booking_id' => $booking->id,
            'amount' => 900,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
        ]);

        $this->view('invoice-document', ['invoice' => $invoice, 'booking' => $booking])
            ->assertSee('dir="rtl"', false)
            ->assertSee('<p class="text-right"><bdi dir="ltr">02 23750681 - 01553818989</bdi></p>', false)
            ->assertSeeText('فاتورة شحن')
            ->assertSeeText('لا تترددوا في التواصل معنا إذا كان لديكم أي أسئلة أو استفسارات.')
            ->assertSeeText('هذه الفاتورة إلكترونية ولا تحتاج إلى ختم أو توقيع.')
            ->assertSeeText('يرجى تسوية المبلغ المستحق في أقرب وقت لتجنب أي غرامات أرضيات أو تأخير قد يحدث.')
            ->assertDontSeeText('Please do not hesitate to contact us if you have any questions or concerns')
            ->assertDontSeeText('This invoice is electronic and don\'t need to stamp or signatory')
            ->assertDontSeeText('please settle the payment asap to avoid any demurrage may occur');
    }
}

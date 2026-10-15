<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Agent;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Port;
use App\Models\PortCountry;
use App\Models\Role;
use App\Models\Schedule;
use App\Models\ShipmentEvent;
use App\Models\ShipmentTrack;
use App\Models\ShippingRate;
use App\Models\SystemInsight;
use App\Models\User;
use App\Models\Voucher;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ManagementSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Run seeders to set up roles and default users
        $this->seed(RolesAndUsersSeeder::class);
    }

    /**
     * Test public pages render.
     */
    public function test_public_pages_render_correctly(): void
    {
        $this->get('/')->assertStatus(200);
        $this->get('/tracking')->assertStatus(200);
        $this->get('/our-ports')->assertStatus(200);
        $this->get('/our-agents')->assertStatus(200);
        $this->get('/contact')->assertStatus(200);
        $this->get('/login')->assertStatus(200);
    }

    public function test_public_tracking_does_not_show_random_shipments_without_valid_query(): void
    {
        $booking = Booking::create([
            'id' => 'BK-PUBLIC-TRACK',
            'customer_company' => 'Private Tracking Customer',
            'customer_contact_name' => 'Private Contact',
            'customer_phone' => '+967700111222',
            'customer_email' => 'private-track@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Private tracking goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'booked',
            'carrier_booking_ref' => 'CARRIER-PUBLIC-TRACK',
        ]);

        ShipmentTrack::create([
            'id' => 'BHRL-PUBLIC-TRACK',
            'booking_id' => $booking->id,
            'status' => 'In transit',
            'progress' => 45,
        ]);

        $this->get(route('tracking'))
            ->assertStatus(200)
            ->assertDontSee('BHRL-PUBLIC-TRACK')
            ->assertDontSee('Private Tracking Customer');

        $this->get(route('tracking', ['q' => 'WRONG-TRACKING-NO']))
            ->assertStatus(200)
            ->assertSee(__('messages.no_results'))
            ->assertDontSee('BHRL-PUBLIC-TRACK')
            ->assertDontSee('Private Tracking Customer');

        $this->get(route('tracking', ['q' => 'CARRIER-PUBLIC-TRACK']))
            ->assertStatus(200)
            ->assertSee('BHRL-PUBLIC-TRACK');
    }

    public function test_public_home_quote_entry_requires_customer_account_and_uses_full_form(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('طلب شحنة من حسابك')
            ->assertSee('إنشاء حساب عميل')
            ->assertDontSee('instant-booking', false)
            ->assertDontSee('phone_country_code', false)
            ->assertDontSee('data-instant-country-select', false)
            ->assertDontSee('data-instant-port-select', false);

        $response = $this->post('/instant-booking', [
            'company' => 'Public Instant Customer',
            'contactName' => 'Public Contact',
            'phone' => '+967700100100',
            'email' => 'public-instant@example.com',
            'shipping_category' => 'sea',
            'route_mode' => 'EXW',
            'origin_country' => 'China',
            'origin_city' => 'Yiwu',
            'pol' => 'Shanghai',
            'destination_country' => 'Yemen',
            'destination_city' => 'Aden',
            'pod' => 'Aden',
            'shipmentType' => 'FCL',
            'containerType' => '40HC',
            'containerQty' => 2,
            'goodsDescription' => 'Public instant booking goods',
            'weightKg' => 2500,
            'volumeCbm' => 18,
            'documents' => 'Invoice, Packing List',
            'notes' => 'Created from public home form',
        ]);

        $response->assertRedirect(route('customer.login'));

        $this->assertDatabaseMissing('customers', [
            'email' => 'public-instant@example.com',
        ]);

        $this->assertDatabaseMissing('bookings', [
            'customer_email' => 'public-instant@example.com',
        ]);

        $customer = Customer::create([
            'company' => 'Portal Quote Customer',
            'contact_name' => 'Portal Quote Contact',
            'phone' => '+967700300300',
            'email' => 'portal-instant@example.com',
            'password' => 'portal-password',
            'portal_enabled' => true,
        ]);

        $this->actingAs($customer, 'customer')
            ->post('/instant-booking', [
                'email' => 'portal-instant@example.com',
            ])
            ->assertRedirect(route('customer.shipments.create'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('bookings', [
            'customer_email' => 'portal-instant@example.com',
        ]);
    }

    public function test_staff_can_create_fcl_booking_with_multiple_container_types(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->post('/booking', [
            'shipping_category' => 'sea',
            'route_mode' => 'FOB',
            'company' => 'Mixed FCL Customer',
            'contactName' => 'Mixed FCL Contact',
            'phone' => '+967700123125',
            'email' => 'mixed-fcl@example.com',
            'origin_country' => 'China',
            'destination_country' => 'Yemen',
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'shipmentType' => 'FCL',
            'containerType' => '40GP',
            'containerQty' => 3,
            'containers' => [
                ['type' => '40GP', 'qty' => 3],
                ['type' => '20GP', 'qty' => 5],
            ],
            'goodsDescription' => 'Mixed container cargo',
            'weightKg' => 2500,
            'volumeCbm' => 18,
        ]);

        $booking = Booking::where('customer_email', 'mixed-fcl@example.com')->firstOrFail();

        $response->assertRedirect(route('orders.index'));
        $this->assertSame('FCL', $booking->shipment_type);
        $this->assertSame('40GP', $booking->container_type);
        $this->assertSame(3, $booking->container_qty);
        $this->assertSame([
            ['type' => '40GP', 'qty' => 3],
            ['type' => '20GP', 'qty' => 5],
        ], data_get($booking->cargo_details, 'containers'));
        $this->assertSame(8, data_get($booking->cargo_details, 'container_total_qty'));
        $this->assertSame('3x40GP + 5x20GP', data_get($booking->cargo_details, 'container_summary'));
        $this->assertSame(8, $booking->containerTotalQuantity());
        $this->assertSame('FCL 3x40GP + 5x20GP', $booking->shipmentDisplay());

        $this->getJson(route('orders.show', $booking->id))
            ->assertOk()
            ->assertJsonPath('cargo.cargoDetails.container_summary', '3x40GP + 5x20GP')
            ->assertJsonPath('cargo.containerSummary', '3x40GP + 5x20GP')
            ->assertJsonPath('cargo.containerTotalQty', 8);

        $this->actingAs($user)
            ->get(route('pricing.index'))
            ->assertOk()
            ->assertSee('FCL 3x40GP + 5x20GP');
    }

    public function test_quote_request_requires_customer_account(): void
    {
        $quoteData = [
            'company' => 'Guest Quote Customer',
            'contact_name' => 'Guest Contact',
            'phone_country_code' => '+967',
            'phone' => '0712345678',
            'email' => 'guest-quote@example.com',
            'origin' => 'Shanghai',
            'destination' => 'Aden',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Guest quote cargo',
            'weight_kg' => 1000,
        ];

        $this->get(route('quote.index'))
            ->assertRedirect(route('customer.login'));

        $this->post(route('quote.calculate'), $quoteData)
            ->assertRedirect(route('customer.login'));

        $this->post(route('quote.submit'), $quoteData)
            ->assertRedirect(route('customer.login'));

        $this->assertDatabaseMissing('bookings', [
            'customer_email' => 'guest-quote@example.com',
        ]);
    }

    public function test_customer_quote_entry_uses_full_customer_shipment_request(): void
    {
        $customer = Customer::create([
            'company' => 'Quote Public Customer',
            'contact_name' => 'Quote Contact',
            'phone' => '+967712345678',
            'email' => 'quote-public@example.com',
            'password' => 'secret-password',
            'portal_enabled' => true,
        ]);

        $this->actingAs($customer, 'customer');

        $this->get(route('quote.index'))
            ->assertRedirect(route('customer.shipments.create'))
            ->assertSessionHas('success');

        $this->post(route('quote.calculate'))
            ->assertRedirect(route('customer.shipments.create'));

        $this->post(route('quote.submit'))
            ->assertRedirect(route('customer.shipments.create'));

        $this->assertDatabaseMissing('bookings', [
            'customer_email' => 'quote-public@example.com',
        ]);
    }

    public function test_public_home_instant_booking_does_not_create_land_booking_for_guests(): void
    {
        $response = $this->post('/instant-booking', [
            'company' => 'Land Instant Customer',
            'contactName' => 'Land Contact',
            'phone_country_code' => '+966',
            'phone' => '0555555555',
            'email' => 'land-instant@example.com',
            'shipping_category' => 'land',
            'pol' => 'Riyadh',
            'pod' => 'Aden',
            'goodsDescription' => 'Land cargo',
            'weightKg' => 1200,
        ]);

        $response->assertRedirect(route('customer.login'));

        $this->assertDatabaseMissing('bookings', [
            'customer_company' => 'Land Instant Customer',
        ]);
    }

    public function test_customer_portal_registers_customer_creates_booking_and_scopes_access(): void
    {
        $response = $this->post('/customer/register', [
            'company' => 'Portal Customer',
            'contact_name' => 'Portal Contact',
            'phone' => '+967700200200',
            'email' => 'portal@example.com',
            'password' => 'portal-password',
            'password_confirmation' => 'portal-password',
        ]);

        $response->assertRedirect(route('customer.pending'));
        $this->assertGuest('customer');

        $customer = Customer::where('email', 'portal@example.com')->firstOrFail();
        $this->assertFalse((bool) $customer->portal_enabled);
        $this->assertDatabaseHas('notifications', [
            'message' => 'طلب اعتماد حساب عميل جديد: Portal Customer',
            'target' => 'general',
            'read' => false,
            'booking_id' => null,
        ]);

        $customer->forceFill(['portal_enabled' => true])->save();
        $this->actingAs($customer->fresh(), 'customer');

        $response = $this->post('/customer/shipments', [
            'shipping_category' => 'sea',
            'route_mode' => 'EXW',
            'origin_country' => 'China',
            'origin_city' => 'Yiwu',
            'pol' => 'Shanghai',
            'destination_country' => 'Yemen',
            'destination_city' => 'Aden',
            'pod' => 'Aden',
            'shipmentType' => 'FCL',
            'containerType' => '40HC',
            'containerQty' => 2,
            'containers' => [
                ['type' => '40HC', 'qty' => 2],
                ['type' => '20GP', 'qty' => 3],
            ],
            'goodsDescription' => 'Portal customer goods',
            'weightKg' => 900,
            'volumeCbm' => 12,
        ]);

        $booking = Booking::where('customer_id', $customer->id)->firstOrFail();
        $response->assertRedirect(route('customer.shipments.show', $booking));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'customer_id' => $customer->id,
            'customer_company' => 'Portal Customer',
            'status' => 'new',
        ]);
        $this->assertSame('40HC', $booking->container_type);
        $this->assertSame(2, $booking->container_qty);
        $this->assertSame([
            ['type' => '40HC', 'qty' => 2],
            ['type' => '20GP', 'qty' => 3],
        ], data_get($booking->cargo_details, 'containers'));
        $this->assertSame(5, data_get($booking->cargo_details, 'container_total_qty'));
        $this->assertSame('2x40HC + 3x20GP', data_get($booking->cargo_details, 'container_summary'));
        $this->assertSame(5, $booking->containerTotalQuantity());
        $this->assertSame('FCL 2x40HC + 3x20GP', $booking->shipmentDisplay());

        $this->actingAs(User::where('username', 'admin')->first())
            ->get(route('pricing.index'))
            ->assertOk()
            ->assertSee('FCL 2x40HC + 3x20GP');

        $sameEmailUnlinkedBooking = Booking::create([
            'id' => 'BK-2026-EMAIL1',
            'customer_id' => null,
            'customer_company' => 'Unlinked Same Email',
            'customer_contact_name' => 'Unlinked Contact',
            'customer_phone' => '+967733000000',
            'customer_email' => $customer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Unlinked goods',
            'weight_kg' => 800,
            'volume_cbm' => 8,
            'pol' => 'Qingdao',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 900,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.dashboard'))
            ->assertStatus(200)
            ->assertSee($booking->id)
            ->assertDontSee($sameEmailUnlinkedBooking->id);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.shipments.show', $sameEmailUnlinkedBooking))
            ->assertNotFound();

        $otherCustomer = Customer::create([
            'company' => 'Other Customer',
            'contact_name' => 'Other Contact',
            'phone' => '+967711000000',
            'email' => 'other-customer@example.com',
            'password' => 'other-password',
            'portal_enabled' => true,
        ]);

        $otherBooking = Booking::create([
            'id' => 'BK-2026-OTHER1',
            'customer_id' => $otherCustomer->id,
            'customer_company' => $otherCustomer->company,
            'customer_contact_name' => $otherCustomer->contact_name,
            'customer_phone' => $otherCustomer->phone,
            'customer_email' => $otherCustomer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Other goods',
            'weight_kg' => 1000,
            'volume_cbm' => 10,
            'pol' => 'Ningbo',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 1000,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.shipments.show', $otherBooking))
            ->assertNotFound();
    }

    public function test_customer_portal_customer_can_create_air_shipment_with_package_metrics(): void
    {
        $customer = Customer::create([
            'company' => 'Air Portal Customer',
            'contact_name' => 'Air Contact',
            'phone' => '+967700300300',
            'email' => 'air-portal@example.com',
            'password' => 'air-password',
            'portal_enabled' => true,
        ]);

        $response = $this->actingAs($customer, 'customer')->post(route('customer.shipments.store'), [
            'shipping_category' => 'air',
            'origin_country' => 'Yemen',
            'origin_country_code' => 'YE',
            'origin_city' => 'Sanaa',
            'origin_airport' => 'SAH',
            'origin_airport_name' => 'Sanaa International Airport',
            'destination_country' => 'United Arab Emirates',
            'destination_country_code' => 'AE',
            'destination_city' => 'Dubai',
            'destination_airport' => 'DXB',
            'destination_airport_name' => 'Dubai International Airport',
            'goodsDescription' => 'Air portal cargo',
            'weightKg' => 120,
            'volumeCbm' => 1.2,
            'packages' => [
                [
                    'quantity' => 2,
                    'package_type' => 'carton',
                    'length_cm' => 100,
                    'width_cm' => 80,
                    'height_cm' => 70,
                    'weight_kg' => 50,
                ],
                [
                    'quantity' => 3,
                    'package_type' => 'carton',
                    'length_cm' => 50,
                    'width_cm' => 40,
                    'height_cm' => 30,
                    'weight_kg' => 20,
                ],
            ],
            'air_service_level' => 'standard',
            'ready_date' => '2026-08-15',
            'cargo_type' => 'general',
            'hs_code' => '850440',
            'cargo_items' => [
                [
                    'name' => 'Power supplies',
                    'hs_code' => '850440',
                    'quantity' => 2,
                    'unit' => 'piece',
                    'weight_kg' => 100,
                    'volume_cbm' => 0.8,
                ],
                [
                    'name' => 'Control boards',
                    'hs_code' => '853710',
                    'quantity' => 3,
                    'unit' => 'piece',
                    'weight_kg' => 60,
                    'volume_cbm' => 0.5,
                ],
            ],
            'country_of_origin' => 'China',
            'incoterm' => 'EXW',
            'commercial_value' => 5000,
            'currency' => 'USD',
            'customs_clearance_destination' => '1',
            'insurance_required' => '1',
        ]);

        $booking = Booking::where('customer_id', $customer->id)->firstOrFail();
        $response->assertRedirect(route('customer.shipments.show', $booking));

        $this->assertSame('AIR', $booking->shipment_type);
        $this->assertSame('SAH', $booking->pol);
        $this->assertSame('DXB', $booking->pod);
        $this->assertSame('air', data_get($booking->route_details, 'category'));
        $this->assertSame('airport', data_get($booking->route_details, 'origin.type'));
        $this->assertSame('SAH', data_get($booking->route_details, 'origin.airport_code'));
        $this->assertSame('DXB', data_get($booking->route_details, 'destination.airport_code'));
        $this->assertTrue((bool) data_get($booking->route_details, 'services.customs_requested'));
        $this->assertTrue((bool) data_get($booking->route_details, 'services.cargo_insurance_requested'));
        $this->assertSame(5, data_get($booking->cargo_details, 'cargo.pieces_count'));
        $this->assertEquals(160.0, data_get($booking->cargo_details, 'cargo.gross_weight_kg'));
        $this->assertEquals(1.3, data_get($booking->cargo_details, 'cargo.volume_cbm'));
        $this->assertEquals(216.67, data_get($booking->cargo_details, 'cargo.volumetric_weight_kg'));
        $this->assertEquals(216.67, data_get($booking->cargo_details, 'cargo.chargeable_weight_kg'));
        $this->assertSame('850440', $booking->hsCode());
        $this->assertCount(2, $booking->cargoItems());
        $this->assertSame('Control boards', $booking->cargoItems()[1]['name']);
        $this->assertSame('853710', $booking->cargoItems()[1]['hs_code']);
        $this->assertSame('EXW', data_get($booking->cargo_details, 'trade.incoterm'));

        $this->assertDatabaseHas('notifications', [
            'booking_id' => $booking->id,
            'target' => 'pricing',
            'read' => false,
        ]);
        $this->assertDatabaseHas('notifications', [
            'booking_id' => $booking->id,
            'target' => 'customs',
            'read' => false,
        ]);
    }

    public function test_customer_portal_dangerous_goods_booking_requires_supporting_file(): void
    {
        $customer = Customer::create([
            'company' => 'DG Portal Customer',
            'contact_name' => 'DG Portal Contact',
            'phone' => '+967700303303',
            'email' => 'dg-portal@example.com',
            'password' => 'dg-password',
            'portal_enabled' => true,
        ]);

        $response = $this->actingAs($customer, 'customer')
            ->from(route('customer.shipments.create'))
            ->post(route('customer.shipments.store'), [
                'shipping_category' => 'sea',
                'route_mode' => 'FOB',
                'origin_country' => 'China',
                'destination_country' => 'Yemen',
                'pol' => 'Shanghai',
                'pod' => 'Aden',
                'shipmentType' => 'FCL',
                'containerType' => '40GP',
                'containerQty' => 1,
                'goodsDescription' => 'DG customer cargo',
                'weightKg' => 500,
                'cargo_type' => 'dangerous',
                'dangerous' => '1',
            ]);

        $response->assertRedirect(route('customer.shipments.create'));
        $response->assertSessionHasErrors('attachments');

        $this->assertDatabaseMissing('bookings', [
            'customer_id' => $customer->id,
            'goods_description' => 'DG customer cargo',
        ]);
    }

    public function test_customer_portal_customer_can_create_door_to_door_shipment(): void
    {
        $customer = Customer::create([
            'company' => 'Door Portal Customer',
            'contact_name' => 'Door Contact',
            'phone' => '+967700310310',
            'email' => 'door-portal@example.com',
            'password' => 'door-password',
            'portal_enabled' => true,
        ]);

        $response = $this->actingAs($customer, 'customer')->post(route('customer.shipments.store'), [
            'shipping_category' => 'door_to_door',
            'origin_country' => 'China',
            'origin_country_code' => 'CN',
            'origin_city' => 'Guangzhou',
            'origin_postal_code' => '510000',
            'pickup_address' => 'Tianhe District Warehouse',
            'pickup_contact_name' => 'Supplier Contact',
            'pickup_phone_country_code' => '+86',
            'pickup_phone' => '13800138000',
            'destination_country' => 'Yemen',
            'destination_country_code' => 'YE',
            'destination_city' => 'Aden',
            'destination_postal_code' => '00000',
            'delivery_address' => 'Aden Customer Warehouse',
            'delivery_contact_name' => 'Receiver Contact',
            'delivery_phone_country_code' => '+967',
            'delivery_phone' => '700111222',
            'main_transport_mode' => 'AIR',
            'goodsDescription' => 'Door delivery cargo',
            'weightKg' => 75,
            'volumeCbm' => 0.6,
            'packages' => [
                [
                    'quantity' => 4,
                    'package_type' => 'box',
                    'length_cm' => 60,
                    'width_cm' => 40,
                    'height_cm' => 50,
                    'weight_kg' => 18.75,
                ],
            ],
            'pickup_ready_date' => '2026-08-20',
            'pickup_time_from' => '09:00',
            'pickup_time_to' => '12:00',
            'preferred_pickup_time' => 'morning',
            'cargo_type' => 'general',
            'incoterm' => 'DAP',
            'commercial_value' => 1800,
            'currency' => 'USD',
            'import_customs_clearance' => '1',
            'insurance_required' => '1',
            'delivery_notes' => 'Call before delivery',
        ]);

        $booking = Booking::where('customer_id', $customer->id)->firstOrFail();
        $response->assertRedirect(route('customer.shipments.show', $booking));

        $this->assertSame('DOOR_TO_DOOR', $booking->shipment_type);
        $this->assertSame('Tianhe District Warehouse', $booking->pol);
        $this->assertSame('Aden Customer Warehouse', $booking->pod);
        $this->assertSame('door_to_door', data_get($booking->route_details, 'category'));
        $this->assertSame('DOOR_TO_DOOR', data_get($booking->route_details, 'mode'));
        $this->assertSame('AIR', data_get($booking->route_details, 'main_transport_mode'));
        $this->assertSame('address', data_get($booking->route_details, 'origin.type'));
        $this->assertSame('address', data_get($booking->route_details, 'destination.type'));
        $this->assertSame('Tianhe District Warehouse', data_get($booking->route_details, 'origin.address'));
        $this->assertSame('Aden Customer Warehouse', data_get($booking->route_details, 'destination.address'));
        $this->assertTrue((bool) data_get($booking->route_details, 'services.customs_requested'));
        $this->assertTrue((bool) data_get($booking->route_details, 'services.door_delivery_requested'));
        $this->assertSame('door_to_door', data_get($booking->cargo_details, 'service_type'));
        $this->assertSame(4, data_get($booking->cargo_details, 'cargo.packages_count'));
        $this->assertEquals(75.0, data_get($booking->cargo_details, 'cargo.gross_weight_kg'));
        $this->assertEquals(0.48, $booking->volume_cbm);
        $this->assertEquals(0.48, data_get($booking->cargo_details, 'cargo.volume_cbm'));
        $this->assertSame('DAP', data_get($booking->cargo_details, 'trade.incoterm'));

        $this->assertDatabaseHas('notifications', [
            'booking_id' => $booking->id,
            'target' => 'customs',
            'read' => false,
        ]);
    }

    public function test_pricing_calculates_door_to_door_total_from_gross_weight_in_tons(): void
    {
        $user = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Test Door Agent',
            'type' => 'local',
            'phone' => '+967700123456',
            'email' => 'door-agent@example.com',
        ]);

        $booking = Booking::create([
            'id' => 'BK-DOOR-PRICING',
            'customer_company' => 'Door Pricing Customer',
            'customer_contact_name' => 'Pricing Contact',
            'customer_phone' => '+967700999888',
            'customer_email' => 'door-pricing@example.com',
            'shipment_type' => 'DOOR_TO_DOOR',
            'goods_description' => 'Door to door custom cargo',
            'weight_kg' => 1500,
            'volume_cbm' => 2.5,
            'pol' => 'Factory Warehouse',
            'pod' => 'Customer Warehouse',
            'status' => 'new',
            'route_details' => [
                'category' => 'door_to_door',
                'mode' => 'DOOR_TO_DOOR',
            ],
            'cargo_details' => [
                'service_type' => 'door_to_door',
            ],
        ]);

        $response = $this->actingAs($user)->post(route('pricing.store', $booking), [
            'transitDays' => 6,
            'validityDate' => '2026-09-15',
            'agent_id' => $agent->id,
            'agentPurchaseUnitPrice' => 220,
            'agentPurchaseQuantity' => 1,
            'doorToDoorPricingBasis' => 'ton',
            'doorToDoorUnitPrice' => 450,
            'notes' => 'Door to door custom price',
            'agentPurchaseNotes' => 'Agent purchase note',
        ]);

        $response->assertRedirect(route('pricing.index'));
        $booking->refresh();

        $this->assertSame('priced', $booking->status);
        $this->assertEquals(675.0, $booking->ocean_freight);
        $this->assertEquals(675.0, $booking->pricingTotal());
        $this->assertEquals(220.0, $booking->agent_purchase_amount);
    }

    public function test_pricing_calculates_door_to_door_total_from_selected_cbm_basis(): void
    {
        $user = User::where('username', 'admin')->firstOrFail();
        $agent = Agent::create([
            'name' => 'Test Door CBM Agent',
            'type' => 'local',
            'phone' => '+967700123457',
            'email' => 'door-cbm-agent@example.com',
        ]);

        $booking = Booking::create([
            'id' => 'BK-DOOR-CBM-PRICING',
            'customer_company' => 'Door CBM Customer',
            'customer_contact_name' => 'CBM Contact',
            'customer_phone' => '+967700999887',
            'customer_email' => 'door-cbm-pricing@example.com',
            'shipment_type' => 'DOOR_TO_DOOR',
            'goods_description' => 'Door to door CBM cargo',
            'weight_kg' => 1500,
            'volume_cbm' => 3.25,
            'pol' => 'Factory Warehouse',
            'pod' => 'Customer Warehouse',
            'status' => 'new',
            'route_details' => [
                'category' => 'door_to_door',
                'mode' => 'DOOR_TO_DOOR',
            ],
            'cargo_details' => [
                'service_type' => 'door_to_door',
            ],
        ]);

        $this->actingAs($user)->post(route('pricing.store', $booking), [
            'transitDays' => 6,
            'validityDate' => '2026-09-15',
            'agent_id' => $agent->id,
            'agentPurchaseUnitPrice' => 80,
            'agentPurchaseQuantity' => 3.25,
            'doorToDoorPricingBasis' => 'cbm',
            'doorToDoorUnitPrice' => 180,
        ])->assertRedirect(route('pricing.index'));

        $booking->refresh();

        $this->assertEquals(585.0, $booking->ocean_freight);
        $this->assertEquals(585.0, $booking->pricingTotal());
        $this->assertSame('cbm', data_get($booking->cargo_details, 'door_to_door_pricing.basis'));
        $this->assertEquals(3.25, data_get($booking->cargo_details, 'door_to_door_pricing.quantity'));
        $this->assertEquals(180.0, data_get($booking->cargo_details, 'door_to_door_pricing.unit_price'));
        $this->assertSame('cbm', $booking->pricingBasis());
        $this->assertEquals(3.25, $booking->pricingQuantity());
    }

    public function test_staff_can_create_lcl_booking_and_cbm_is_calculated_from_dimensions(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->post('/booking', [
            'shipping_category' => 'sea',
            'route_mode' => 'FOB',
            'company' => 'LCL Dimensions Customer',
            'contactName' => 'LCL Contact',
            'phone' => '+967700123123',
            'email' => 'lcl-dimensions@example.com',
            'origin_country' => 'China',
            'destination_country' => 'Yemen',
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'shipmentType' => 'LCL',
            'goodsDescription' => 'LCL cargo with dimensions',
            'weightKg' => 850,
            'lcl_length_m' => 2.5,
            'lcl_width_m' => 1.2,
            'lcl_height_m' => 1.1,
        ]);

        $booking = Booking::where('customer_email', 'lcl-dimensions@example.com')->firstOrFail();

        $response->assertRedirect(route('orders.index'));
        $this->assertSame('LCL', $booking->shipment_type);
        $this->assertEquals(4, $booking->volume_cbm);
        $this->assertEquals(2.5, data_get($booking->cargo_details, 'dimensions.length_m'));
        $this->assertEquals(1.2, data_get($booking->cargo_details, 'dimensions.width_m'));
        $this->assertEquals(1.1, data_get($booking->cargo_details, 'dimensions.height_m'));
        $this->assertEquals(4, data_get($booking->cargo_details, 'volume_cbm'));
    }

    public function test_staff_can_create_bulk_booking_and_cbm_is_calculated_from_dimensions(): void
    {
        $user = User::where('username', 'admin')->first();

        $response = $this->actingAs($user)->post('/booking', [
            'shipping_category' => 'sea',
            'route_mode' => 'FOB',
            'company' => 'Bulk Dimensions Customer',
            'contactName' => 'Bulk Contact',
            'phone' => '+967700123124',
            'email' => 'bulk-dimensions@example.com',
            'origin_country' => 'China',
            'destination_country' => 'Yemen',
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'shipmentType' => 'Bulk',
            'goodsDescription' => 'Bulk cargo with dimensions',
            'weightKg' => 999,
            'bulk_pieces' => 3,
            'bulk_piece_weight' => 11.5,
            'bulk_length' => 1.5,
            'bulk_width' => 1.5,
            'bulk_height' => 2,
        ]);

        $booking = Booking::where('customer_email', 'bulk-dimensions@example.com')->firstOrFail();

        $response->assertRedirect(route('orders.index'));
        $this->assertSame('Bulk', $booking->shipment_type);
        $this->assertEquals(34.5, $booking->weight_kg);
        $this->assertEquals(13.5, $booking->volume_cbm);
        $this->assertEquals(3, data_get($booking->cargo_details, 'pieces'));
        $this->assertEquals(11.5, data_get($booking->cargo_details, 'piece_weight'));
        $this->assertEquals(34.5, data_get($booking->cargo_details, 'total_weight'));
        $this->assertEquals(34.5, data_get($booking->cargo_details, 'gross_weight_kg'));
        $this->assertEquals(1.5, data_get($booking->cargo_details, 'length'));
        $this->assertEquals(1.5, data_get($booking->cargo_details, 'width'));
        $this->assertEquals(2, data_get($booking->cargo_details, 'height'));
        $this->assertSame('cm', data_get($booking->cargo_details, 'dimension_unit'));
        $this->assertEquals(4.5, data_get($booking->cargo_details, 'cbm'));
        $this->assertEquals(4.5, data_get($booking->cargo_details, 'piece_cbm'));
        $this->assertEquals(13.5, data_get($booking->cargo_details, 'total_cbm'));
        $this->assertEquals(13.5, data_get($booking->cargo_details, 'volume_cbm'));
    }

    public function test_customer_operational_pages_are_scoped_to_customer_bookings(): void
    {
        $customer = Customer::create([
            'company' => 'Pages Customer',
            'contact_name' => 'Pages Contact',
            'phone' => '+967700400400',
            'email' => 'pages-customer@example.com',
            'password' => 'pages-password',
            'portal_enabled' => true,
        ]);

        $otherCustomer = Customer::create([
            'company' => 'Pages Other Customer',
            'contact_name' => 'Pages Other Contact',
            'phone' => '+967700500500',
            'email' => 'pages-other@example.com',
            'password' => 'pages-other-password',
            'portal_enabled' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-CUST-PAGES-1',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Customer pages goods',
            'weight_kg' => 1200,
            'volume_cbm' => 15,
            'documents' => 'Commercial invoice and packing list',
            'attachments' => json_encode(['bookings/attachments/customer-pages.pdf']),
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'route_details' => ['services' => ['customs_requested' => true]],
            'status' => 'sent',
            'ocean_freight' => 1400,
            'origin_charges' => 150,
            'customs_status' => 'documents_review',
            'customs_reference' => 'CUS-PAGES-1',
            'customs_documents' => ['customs/customer-pages-bayan.pdf'],
        ]);

        $otherBooking = Booking::create([
            'id' => 'BK-CUST-PAGES-OTHER',
            'customer_id' => $otherCustomer->id,
            'customer_company' => $otherCustomer->company,
            'customer_contact_name' => $otherCustomer->contact_name,
            'customer_phone' => $otherCustomer->phone,
            'customer_email' => $otherCustomer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Other customer pages goods',
            'weight_kg' => 1000,
            'volume_cbm' => 12,
            'documents' => 'Other commercial invoice',
            'pol' => 'Ningbo',
            'pod' => 'Aden',
            'route_details' => ['services' => ['customs_requested' => true]],
            'status' => 'sent',
            'ocean_freight' => 900,
            'customs_status' => 'documents_review',
            'customs_reference' => 'CUS-PAGES-OTHER',
        ]);

        Invoice::create([
            'id' => 'INV-CUST-PAGES-1',
            'booking_id' => $booking->id,
            'amount' => 1550,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        Invoice::create([
            'id' => 'INV-CUST-PAGES-OTHER',
            'booking_id' => $otherBooking->id,
            'amount' => 900,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        foreach ([
            route('customer.shipments.index'),
            route('customer.offers'),
            route('customer.documents'),
            route('customer.invoices'),
            route('customer.customs'),
        ] as $url) {
            $this->actingAs($customer, 'customer')
                ->get($url)
                ->assertStatus(200)
                ->assertSee($booking->id)
                ->assertDontSee($otherBooking->id)
                ->assertDontSee('INV-CUST-PAGES-OTHER');
        }
    }

    public function test_customer_tracking_page_uses_system_tracking_and_scopes_results(): void
    {
        $customer = Customer::create([
            'company' => 'Tracking Customer',
            'contact_name' => 'Tracking Contact',
            'phone' => '+967700700700',
            'email' => 'tracking-customer@example.com',
            'password' => 'tracking-password',
            'portal_enabled' => true,
        ]);

        $otherCustomer = Customer::create([
            'company' => 'Other Tracking Customer',
            'contact_name' => 'Other Tracking Contact',
            'phone' => '+967700800800',
            'email' => 'other-tracking-customer@example.com',
            'password' => 'other-tracking-password',
            'portal_enabled' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-CUST-TRACK-1',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Tracking goods',
            'weight_kg' => 1500,
            'volume_cbm' => 18,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'booked',
            'carrier_booking_ref' => 'CARRIER-CUST-TRACK',
        ]);

        $otherBooking = Booking::create([
            'id' => 'BK-CUST-TRACK-OTHER',
            'customer_id' => $otherCustomer->id,
            'customer_company' => $otherCustomer->company,
            'customer_contact_name' => $otherCustomer->contact_name,
            'customer_phone' => $otherCustomer->phone,
            'customer_email' => $otherCustomer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Other tracking goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Ningbo',
            'pod' => 'Aden',
            'status' => 'booked',
            'carrier_booking_ref' => 'CARRIER-OTHER-TRACK',
        ]);

        $track = ShipmentTrack::create([
            'id' => 'BHRL-CUST-TRACK-1',
            'booking_id' => $booking->id,
            'status' => 'تم الحجز',
            'progress' => 35,
            'etd' => now()->addDays(2),
            'eta' => now()->addDays(20),
        ]);

        ShipmentEvent::create([
            'shipment_track_id' => $track->id,
            'event_date' => now()->format('Y-m-d H:i'),
            'location' => 'Shanghai',
            'description' => 'Customer tracking event',
            'status' => 'تم الحجز',
        ]);

        ShipmentTrack::create([
            'id' => 'BHRL-CUST-TRACK-OTHER',
            'booking_id' => $otherBooking->id,
            'status' => 'تم الحجز',
            'progress' => 15,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.tracking'))
            ->assertStatus(200)
            ->assertSee($track->id)
            ->assertSee('Customer tracking event')
            ->assertDontSee('BHRL-CUST-TRACK-OTHER');

        $this->actingAs($customer, 'customer')
            ->get(route('customer.tracking', ['q' => 'CARRIER-CUST-TRACK']))
            ->assertStatus(200)
            ->assertSee($track->id)
            ->assertDontSee('BHRL-CUST-TRACK-OTHER');

        $this->actingAs($customer, 'customer')
            ->get(route('customer.tracking', ['q' => 'BHRL-CUST-TRACK-OTHER']))
            ->assertStatus(200)
            ->assertDontSee($otherBooking->id)
            ->assertDontSee('CARRIER-OTHER-TRACK')
            ->assertSee('لم نعثر على نتائج مطابقة');
    }

    public function test_customer_quote_decision_returns_to_sales_without_exposing_internal_costs(): void
    {
        $customer = Customer::create([
            'company' => 'Decision Customer',
            'contact_name' => 'Decision Contact',
            'phone' => '+967700300300',
            'email' => 'decision@example.com',
            'password' => 'decision-password',
            'portal_enabled' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-2026-DEC01',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Decision goods',
            'weight_kg' => 1500,
            'volume_cbm' => 14,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'priced',
            'ocean_freight' => 1000,
            'origin_charges' => 200,
            'dthc' => 100,
            'war_risk_surcharge' => 50,
            'other_charges' => 25,
            'agent_purchase_amount' => 4321,
            'profit_amount' => 999,
            'transit_days' => 21,
            'validity_date' => now()->addDays(10),
        ]);
        $customerQuoteTotal = $booking->formatPricingMoney($booking->pricingTotal());

        $salesUser = User::where('username', 'sales_user')->firstOrFail();

        $this->actingAs($salesUser)
            ->get(route('sales.index'))
            ->assertStatus(200)
            ->assertSee($booking->id)
            ->assertSee('بانتظار المبيعات');

        $this->actingAs($customer, 'customer')
            ->get(route('customer.order-confirmation'))
            ->assertStatus(200)
            ->assertSee($booking->id)
            ->assertSee($customerQuoteTotal)
            ->assertSee(route('customer.shipments.accept', $booking), false)
            ->assertSee(route('customer.shipments.reject', $booking), false);

        $this->actingAs($salesUser)
            ->post(route('sales.send', $booking))
            ->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('notifications', [
            'booking_id' => $booking->id,
            'target' => 'customer',
            'read' => false,
        ]);

        $otherCustomer = Customer::create([
            'company' => 'Other Decision Customer',
            'contact_name' => 'Other Contact',
            'phone' => '+967700300301',
            'email' => 'other-decision@example.com',
            'password' => 'decision-password',
            'portal_enabled' => true,
        ]);

        $otherBooking = Booking::create([
            'id' => 'BK-2026-DEC02',
            'customer_id' => $otherCustomer->id,
            'customer_company' => $otherCustomer->company,
            'customer_contact_name' => $otherCustomer->contact_name,
            'customer_phone' => $otherCustomer->phone,
            'customer_email' => $otherCustomer->email,
            'shipment_type' => 'FCL',
            'container_type' => '20GP',
            'container_qty' => 1,
            'goods_description' => 'Other decision goods',
            'weight_kg' => 900,
            'volume_cbm' => 9,
            'pol' => 'Ningbo',
            'pod' => 'Aden',
            'status' => 'sent',
            'ocean_freight' => 777,
            'origin_charges' => 100,
            'dthc' => 50,
            'war_risk_surcharge' => 20,
            'other_charges' => 10,
            'transit_days' => 18,
            'validity_date' => now()->addDays(10),
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.shipments.show', $booking))
            ->assertStatus(200)
            ->assertSee('عرض السعر الرسمي')
            ->assertSee($customerQuoteTotal)
            ->assertDontSee('4321')
            ->assertDontSee('الربح');

        $this->actingAs($customer, 'customer')
            ->get(route('customer.order-confirmation'))
            ->assertStatus(200)
            ->assertSee($booking->id)
            ->assertSee($customerQuoteTotal)
            ->assertSee(route('customer.shipments.accept', $booking), false)
            ->assertSee(route('customer.shipments.reject', $booking), false)
            ->assertDontSee($otherBooking->id)
            ->assertDontSee('4321')
            ->assertDontSee('999');

        $this->actingAs($customer, 'customer')
            ->post(route('customer.shipments.reject', $booking), [
                'note' => 'السعر يحتاج مراجعة من فضلكم.',
                'return_to' => 'order-confirmation',
            ])
            ->assertRedirect(route('customer.order-confirmation'));

        $booking->refresh();
        $booking->update([
            'status' => 'sent',
            'customer_decision' => null,
            'customer_decision_note' => null,
            'customer_decided_at' => null,
        ]);

        $this->actingAs($customer, 'customer')
            ->post(route('customer.shipments.accept', $booking), [
                'note' => 'موافق، يرجى المتابعة.',
            ])
            ->assertRedirect(route('customer.shipments.show', $booking));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'customer_approved',
            'customer_decision' => 'accepted',
            'customer_decision_note' => 'موافق، يرجى المتابعة.',
        ]);

        $this->assertDatabaseHas('notifications', [
            'booking_id' => $booking->id,
            'target' => 'sales',
            'read' => false,
        ]);

        $this->actingAs($salesUser)
            ->get(route('sales.index'))
            ->assertStatus(200)
            ->assertSee('وافق العميل')
            ->assertSee('موافق، يرجى المتابعة.');
    }

    public function test_sales_approval_does_not_duplicate_invoice_or_tracking_when_retried(): void
    {
        $salesUser = User::where('username', 'sales_user')->firstOrFail();

        $booking = Booking::create([
            'id' => 'BK-SALES-IDEMPOTENT',
            'customer_company' => 'Idempotent Sales Customer',
            'customer_contact_name' => 'Sales Contact',
            'customer_phone' => '+967700444444',
            'customer_email' => 'sales-idempotent@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Sales approval goods',
            'weight_kg' => 1400,
            'volume_cbm' => 14,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'customer_approved',
            'ocean_freight' => 1200,
            'origin_charges' => 100,
            'dthc' => 50,
            'war_risk_surcharge' => 25,
            'other_charges' => 25,
        ]);

        $payload = [
            'shipper' => 'Idempotent Shipper',
            'consignee' => 'Idempotent Consignee',
            'notify_party' => 'Idempotent Notify',
            'note' => 'Approved once only',
        ];

        $this->actingAs($salesUser)
            ->post(route('sales.approve', $booking), $payload)
            ->assertRedirect(route('sales.index'));

        $this->actingAs($salesUser)
            ->post(route('sales.approve', $booking), $payload)
            ->assertRedirect(route('sales.index'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'approved',
        ]);

        $this->assertSame(1, Invoice::where('booking_id', $booking->id)
            ->where(function ($query) {
                $query->where('invoice_type', 'freight')
                    ->orWhereNull('invoice_type');
            })
            ->count());
        $this->assertSame(1, ShipmentTrack::where('booking_id', $booking->id)->count());

        $track = ShipmentTrack::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame(1, ShipmentEvent::where('shipment_track_id', $track->id)->count());
        $this->assertSame(1, Notification::where('booking_id', $booking->id)->where('target', 'accounts')->count());
        $this->assertSame(1, Notification::where('booking_id', $booking->id)->where('target', 'tracking')->count());
        $this->assertSame(1, Notification::where('booking_id', $booking->id)->where('target', 'customs')->count());
    }

    /**
     * Test login functionality.
     */
    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'admin1234',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        
        // Assert audit log was created for login
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'LOGIN',
            'table_name' => 'users',
        ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'username' => 'admin',
            'password' => 'wrong_password',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'LOGIN_FAILED',
            'table_name' => 'users',
            'record_id' => 'admin',
        ]);
    }

    public function test_staff_logout_is_written_to_audit_log(): void
    {
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($admin)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();

        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'LOGOUT',
            'table_name' => 'users',
            'record_id' => (string) $admin->id,
        ]);
    }

    public function test_customer_login_logout_and_failed_attempts_are_written_to_audit_log(): void
    {
        $customer = Customer::create([
            'company' => 'Audit Customer',
            'contact_name' => 'Audit Contact',
            'phone' => '+967700909090',
            'email' => 'customer-audit@example.com',
            'password' => 'customer-password',
            'portal_enabled' => true,
        ]);

        $this->post('/customer/login', [
            'email' => $customer->email,
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('customer');
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'CUSTOMER_LOGIN_FAIL',
            'table_name' => 'customers',
            'record_id' => $customer->email,
        ]);

        $this->post('/customer/login', [
            'email' => $customer->email,
            'password' => 'customer-password',
        ])->assertRedirect('/customer/dashboard');

        $this->assertAuthenticated('customer');
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'CUSTOMER_LOGIN',
            'table_name' => 'customers',
            'record_id' => (string) $customer->id,
        ]);

        $this->post('/customer/logout')->assertRedirect('/customer/login');

        $this->assertGuest('customer');
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'CUSTOMER_LOGOUT',
            'table_name' => 'customers',
            'record_id' => (string) $customer->id,
        ]);
    }

    /**
     * Test dashboard access.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::where('username', 'admin')->first();
        
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertStatus(200);
    }

    public function test_dashboard_employee_operations_can_be_filtered_by_period(): void
    {
        $user = User::where('username', 'admin')->first();

        $todayLog = AuditLog::create([
            'user_id' => $user->id,
            'action_type' => 'UPDATE',
            'table_name' => 'bookings',
            'record_id' => 'BK-DASH-TODAY',
            'ip_address' => '127.0.0.1',
        ]);
        $todayLog->forceFill([
            'created_at' => now(),
            'updated_at' => now(),
        ])->save();

        $oldLog = AuditLog::create([
            'user_id' => $user->id,
            'action_type' => 'CREATE',
            'table_name' => 'bookings',
            'record_id' => 'BK-DASH-OLD',
            'ip_address' => '127.0.0.1',
        ]);
        $oldLog->forceFill([
            'created_at' => now()->subMonths(2),
            'updated_at' => now()->subMonths(2),
        ])->save();

        $this->actingAs($user)
            ->get('/dashboard?employee_operations_period=day')
            ->assertOk()
            ->assertViewHas('employeeOperationsPeriod', 'day')
            ->assertViewHas('employeeOperations', function ($operations) use ($user) {
                $row = collect($operations)->first(fn (array $row) => $row['user']?->id === $user->id);

                $this->assertNotNull($row);
                $this->assertSame(1, $row['total']);
                $this->assertSame(1, $row['actions']->get('UPDATE'));
                $this->assertNull($row['actions']->get('CREATE'));

                return true;
            });

        $this->actingAs($user)
            ->get('/dashboard?employee_operations_period=all')
            ->assertOk()
            ->assertViewHas('employeeOperationsPeriod', 'all')
            ->assertViewHas('employeeOperations', function ($operations) use ($user) {
                $row = collect($operations)->first(fn (array $row) => $row['user']?->id === $user->id);

                $this->assertNotNull($row);
                $this->assertSame(2, $row['total']);
                $this->assertSame(1, $row['actions']->get('UPDATE'));
                $this->assertSame(1, $row['actions']->get('CREATE'));

                return true;
            });
    }

    public function test_dashboard_financial_summary_uses_invoices_and_vouchers_not_stale_profit(): void
    {
        $user = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Accounting Agent',
            'type' => 'external',
            'country' => 'CN',
        ]);
        $booking = Booking::create([
            'id' => 'BK-ACCOUNTING-001',
            'customer_company' => 'Accounting Customer',
            'customer_contact_name' => 'Accounting Contact',
            'customer_phone' => '+967700000001',
            'customer_email' => 'accounting-customer@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Accounting test goods',
            'weight_kg' => 1000,
            'volume_cbm' => 20,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'approved',
            'ocean_freight' => 700,
            'origin_charges' => 100,
            'dthc' => 100,
            'war_risk_surcharge' => 50,
            'other_charges' => 50,
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 300,
            'agent_purchase_quantity' => 1,
            'profit_amount' => 999,
            'priced_at' => now(),
        ]);
        $invoice = Invoice::create([
            'id' => 'INV-ACCOUNTING-001',
            'booking_id' => $booking->id,
            'invoice_type' => 'freight',
            'amount' => 1200,
            'currency' => 'USD',
            'status' => 'مدفوعة جزئياً',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);
        Voucher::create([
            'id' => 'REC-ACCOUNTING-001',
            'type' => 'receipt',
            'booking_id' => $booking->id,
            'invoice_id' => $invoice->id,
            'amount' => 800,
            'paid_before' => 0,
            'paid_after' => 800,
            'balance_after' => 400,
            'payment_method' => 'cash',
            'payee_payer' => 'Accounting Customer',
            'description' => 'Receipt for dashboard accounting test',
            'date' => now(),
        ]);
        Voucher::create([
            'id' => 'PAY-ACCOUNTING-001',
            'type' => 'payment',
            'booking_id' => $booking->id,
            'agent_id' => $agent->id,
            'amount' => 100,
            'paid_before' => 0,
            'paid_after' => 100,
            'balance_after' => 200,
            'payment_method' => 'cash',
            'payee_payer' => 'Accounting Agent',
            'description' => 'Agent payment for dashboard accounting test',
            'date' => now(),
        ]);
        $customsBooking = Booking::create([
            'id' => 'BK-ACCOUNTING-CUSTOMS',
            'customer_company' => 'Customs Accounting Customer',
            'customer_contact_name' => 'Customs Contact',
            'customer_phone' => '+967700000002',
            'customer_email' => 'customs-accounting@example.com',
            'shipment_type' => 'CUSTOMS',
            'goods_description' => 'Customs accounting test goods',
            'weight_kg' => 100,
            'volume_cbm' => 1,
            'pol' => 'Aden',
            'pod' => 'Aden',
            'status' => 'approved',
            'cargo_details' => [
                'customs_accounting' => [
                    'government_fees' => 500,
                    'service_fee' => 120,
                    'other_fee' => 30,
                    'last_invoiced_at' => now()->toDateString(),
                ],
            ],
        ]);
        Invoice::create([
            'id' => 'INV-ACCOUNTING-CUSTOMS',
            'booking_id' => $customsBooking->id,
            'invoice_type' => 'customs',
            'amount' => 650,
            'currency' => 'USD',
            'status' => 'ØºÙŠØ± Ù…Ø¯ÙÙˆØ¹Ø©',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('financialSummary', function (array $summary) {
            $this->assertSame(1850.0, (float) $summary['sales']);
            $this->assertSame(800.0, (float) $summary['purchases']);
            $this->assertSame(1050.0, (float) $summary['profit']);
            $this->assertSame(0.0, (float) $summary['loss']);
            $this->assertSame(1050.0, (float) $summary['net']);
            $this->assertSame(800.0, (float) $summary['collected']);
            $this->assertSame(100.0, (float) $summary['paid']);
            $this->assertSame(700.0, (float) $summary['cash_net']);
            $this->assertSame(500.0, (float) $summary['customs_government_fees']);
            $this->assertSame(200.0, (float) $summary['unpaid_purchases']);

            return true;
        });
    }

    public function test_dashboard_financial_summary_includes_archived_bookings_for_accounting(): void
    {
        $user = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Archived Accounting Agent',
            'type' => 'external',
            'country' => 'CN',
        ]);
        $booking = Booking::create([
            'id' => 'BK-ACCOUNTING-ARCHIVED',
            'customer_company' => 'Archived Accounting Customer',
            'customer_contact_name' => 'Archived Contact',
            'customer_phone' => '+967700000003',
            'customer_email' => 'archived-accounting@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Archived accounting goods',
            'weight_kg' => 1000,
            'volume_cbm' => 20,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'approved',
            'is_archived' => true,
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 400,
            'agent_purchase_quantity' => 1,
            'priced_at' => now(),
        ]);

        Invoice::create([
            'id' => 'INV-ACCOUNTING-ARCHIVED',
            'booking_id' => $booking->id,
            'invoice_type' => 'freight',
            'amount' => 1000,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('financialSummary', function (array $summary) {
            $this->assertSame(1000.0, (float) $summary['sales']);
            $this->assertSame(400.0, (float) $summary['purchases']);
            $this->assertSame(600.0, (float) $summary['profit']);
            $this->assertSame(0.0, (float) $summary['loss']);
            $this->assertSame(600.0, (float) $summary['net']);
            $this->assertSame(400.0, (float) $summary['agent_purchases']);

            return true;
        });
    }

    public function test_dashboard_financial_summary_ignores_uninvoiced_priced_purchases(): void
    {
        $user = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Uninvoiced Pricing Agent',
            'type' => 'external',
            'country' => 'CN',
        ]);

        Booking::create([
            'id' => 'BK-ACCOUNTING-UNINVOICED',
            'customer_company' => 'Uninvoiced Pricing Customer',
            'customer_contact_name' => 'Pricing Contact',
            'customer_phone' => '+967700000005',
            'customer_email' => 'uninvoiced-pricing@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Uninvoiced pricing goods',
            'weight_kg' => 1000,
            'volume_cbm' => 20,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'approved',
            'ocean_freight' => 650,
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 500,
            'agent_purchase_quantity' => 1,
            'priced_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('financialSummary', function (array $summary) {
            $this->assertSame(0.0, (float) $summary['sales']);
            $this->assertSame(0.0, (float) $summary['purchases']);
            $this->assertSame(0.0, (float) $summary['profit']);
            $this->assertSame(0.0, (float) $summary['loss']);
            $this->assertSame(0.0, (float) $summary['net']);
            $this->assertSame(0.0, (float) $summary['agent_purchases']);

            return true;
        });
    }

    public function test_dashboard_customs_government_fees_sum_multiple_customs_invoice_lines(): void
    {
        $user = User::where('username', 'admin')->first();
        $booking = Booking::create([
            'id' => 'BK-ACCOUNTING-CUSTOMS-MULTI',
            'customer_company' => 'Multiple Customs Customer',
            'customer_contact_name' => 'Customs Contact',
            'customer_phone' => '+967700000004',
            'customer_email' => 'customs-multiple@example.com',
            'shipment_type' => 'CUSTOMS',
            'goods_description' => 'Multiple customs invoice goods',
            'weight_kg' => 100,
            'volume_cbm' => 1,
            'pol' => 'Aden Customs',
            'pod' => 'Aden',
            'status' => 'approved',
            'customs_status' => 'duties',
            'customs_reference' => 'CUS-MULTI-001',
        ]);

        $this->actingAs($user)
            ->post('/accounts/invoices', [
                'booking_id' => $booking->id,
                'invoice_type' => 'customs',
                'customs_government_fees' => 300,
                'customs_service_fee' => 50,
                'customs_other_fee' => 0,
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(7)->toDateString(),
                'status' => 'غير مدفوعة',
            ])
            ->assertRedirect(route('accounts.index', ['accounts_tab' => 'customs']));

        $this->actingAs($user)
            ->post('/accounts/invoices', [
                'booking_id' => $booking->id,
                'invoice_type' => 'customs',
                'customs_government_fees' => 200,
                'customs_service_fee' => 30,
                'customs_other_fee' => 20,
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(7)->toDateString(),
                'status' => 'غير مدفوعة',
            ])
            ->assertRedirect(route('accounts.index', ['accounts_tab' => 'customs']));

        $booking = Booking::withoutGlobalScope('archived_visibility')->findOrFail($booking->id);
        $customsInvoiceLines = data_get($booking->cargo_details, 'customs_accounting.invoices');

        $this->assertCount(2, $customsInvoiceLines);
        $this->assertSame(500.0, (float) collect($customsInvoiceLines)->sum('government_fees'));
        $this->assertSame(600.0, (float) $booking->customs_fees);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('financialSummary', function (array $summary) {
            $this->assertSame(600.0, (float) $summary['sales']);
            $this->assertSame(500.0, (float) $summary['purchases']);
            $this->assertSame(100.0, (float) $summary['profit']);
            $this->assertSame(0.0, (float) $summary['loss']);
            $this->assertSame(100.0, (float) $summary['net']);
            $this->assertSame(500.0, (float) $summary['customs_government_fees']);

            return true;
        });
    }

    public function test_unauthenticated_user_is_redirected_from_dashboard(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    /**
     * Test User Management.
     */
    public function test_non_admin_cannot_access_user_management(): void
    {
        $pricingUser = User::where('username', 'pricing_user')->first();

        $response = $this->actingAs($pricingUser)->get('/users');
        $response->assertRedirect('/dashboard');
    }

    public function test_user_without_dashboard_permission_is_sent_to_first_allowed_page(): void
    {
        $role = Role::create([
            'name' => 'carrier_only',
            'permissions' => ['manage_carriers'],
        ]);

        User::create([
            'full_name' => 'موظف حجوزات الناقلين',
            'username' => 'carrier_only_user',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'carrier_only_user',
            'password' => 'password',
        ]);

        $response->assertRedirect('/bookings');
        $this->assertAuthenticated();
    }

    public function test_direct_dashboard_access_without_permission_redirects_to_allowed_page(): void
    {
        $role = Role::create([
            'name' => 'pricing_without_dashboard',
            'permissions' => ['manage_pricing'],
        ]);

        $user = User::create([
            'full_name' => 'موظف تسعير بدون لوحة تحكم',
            'username' => 'pricing_no_dashboard',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/pricing');
        $response->assertSessionHas('error');
    }

    public function test_notification_bell_only_shows_notifications_for_allowed_pages(): void
    {
        $pricingUser = User::where('username', 'pricing_user')->first();

        Notification::create([
            'id' => 'N-PRICING-VISIBLE',
            'message' => 'Pricing visible notice',
            'read' => false,
            'target' => 'pricing',
        ]);

        Notification::create([
            'id' => 'N-ACCOUNTS-HIDDEN',
            'message' => 'Accounts hidden notice',
            'read' => false,
            'target' => 'accounts',
        ]);

        $response = $this->actingAs($pricingUser)->get('/pricing');

        $response->assertStatus(200);
        $response->assertSee('Pricing visible notice');
        $response->assertDontSee('Accounts hidden notice');
    }

    public function test_staff_notification_center_filters_by_visibility_and_read_status(): void
    {
        $pricingUser = User::where('username', 'pricing_user')->first();

        $unread = Notification::create([
            'id' => 'N-CENTER-PRICING-UNREAD',
            'message' => 'Staff notification center unread',
            'read' => false,
            'target' => 'pricing',
        ]);

        $read = Notification::create([
            'id' => 'N-CENTER-PRICING-READ',
            'message' => 'Staff notification center read',
            'read' => false,
            'target' => 'pricing',
        ]);

        Notification::create([
            'id' => 'N-CENTER-ACCOUNTS-HIDDEN',
            'message' => 'Staff notification center hidden',
            'read' => false,
            'target' => 'accounts',
        ]);

        DB::table('notification_reads')->insert([
            'notification_id' => $read->id,
            'user_id' => $pricingUser->id,
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($pricingUser)
            ->get('/notifications')
            ->assertStatus(200)
            ->assertSee('sidebar-panel', false)
            ->assertSee('id="notification-dropdown" class="hidden absolute right-0', false)
            ->assertSee('data-notification-row="'.$unread->id.'"', false)
            ->assertSee('data-notification-row="'.$read->id.'"', false)
            ->assertSee($unread->message)
            ->assertSee($read->message)
            ->assertDontSee('Staff notification center hidden');

        $this->actingAs($pricingUser)
            ->get('/notifications?status=unread')
            ->assertStatus(200)
            ->assertSee('data-notification-row="'.$unread->id.'"', false)
            ->assertDontSee('data-notification-row="'.$read->id.'"', false)
            ->assertSee($unread->message)
            ->assertDontSee($read->message);

        $this->actingAs($pricingUser)
            ->get('/notifications?status=read')
            ->assertStatus(200)
            ->assertSee('data-notification-row="'.$read->id.'"', false)
            ->assertDontSee('data-notification-row="'.$unread->id.'"', false)
            ->assertSee($read->message);
    }

    public function test_customer_notification_center_only_shows_customer_order_notifications(): void
    {
        $customer = Customer::create([
            'company' => 'Notification Center Customer',
            'contact_name' => 'Notification Contact',
            'phone' => '+967700707070',
            'email' => 'notification-center@example.com',
            'password' => 'password',
            'portal_enabled' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-CUSTOMER-NOTIF-CENTER',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Customer notification center goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'sent',
        ]);

        $unread = Notification::create([
            'id' => 'N-CUSTOMER-CENTER-UNREAD',
            'message' => 'Customer center unread notification',
            'booking_id' => $booking->id,
            'read' => false,
            'target' => 'customer',
        ]);

        $read = Notification::create([
            'id' => 'N-CUSTOMER-CENTER-READ',
            'message' => 'Customer center read notification',
            'booking_id' => $booking->id,
            'read' => false,
            'target' => 'customer',
        ]);

        Notification::create([
            'id' => 'N-CUSTOMER-CENTER-HIDDEN',
            'message' => 'Customer center hidden staff notification',
            'booking_id' => $booking->id,
            'read' => false,
            'target' => 'pricing',
        ]);

        DB::table('customer_notification_reads')->insert([
            'notification_id' => $read->id,
            'customer_id' => $customer->id,
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($customer, 'customer')
            ->get('/customer/notifications')
            ->assertStatus(200)
            ->assertSee($unread->message)
            ->assertSee($read->message)
            ->assertDontSee('Customer center hidden staff notification');

        $this->actingAs($customer, 'customer')
            ->get('/customer/notifications?status=unread')
            ->assertStatus(200)
            ->assertSee($unread->message)
            ->assertDontSee($read->message);

        $this->actingAs($customer, 'customer')
            ->get('/customer/notifications?status=read')
            ->assertStatus(200)
            ->assertSee($read->message);
    }

    public function test_pricing_page_highlights_unpriced_requests_even_without_unread_notification(): void
    {
        $pricingUser = User::where('username', 'pricing_user')->first();

        $booking = Booking::create([
            'id' => 'BK-UNPRICED-ALERT',
            'customer_company' => 'Unpriced Alert Customer',
            'customer_contact_name' => 'Unpriced Contact',
            'customer_phone' => '+967700555001',
            'customer_email' => 'unpriced-alert@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Unpriced alert goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        $this->actingAs($pricingUser)
            ->get(route('pricing.index'))
            ->assertStatus(200)
            ->assertSee($booking->id)
            ->assertSee('تنبيهات التسعير')
            ->assertSee('غير مسعر');
    }

    public function test_mark_all_notifications_only_reads_allowed_targets(): void
    {
        $pricingUser = User::where('username', 'pricing_user')->first();

        $pricingNotification = Notification::create([
            'id' => 'N-PRICING-READ',
            'message' => 'Pricing read notice',
            'read' => false,
            'target' => 'pricing',
        ]);

        $accountsNotification = Notification::create([
            'id' => 'N-ACCOUNTS-UNREAD',
            'message' => 'Accounts unread notice',
            'read' => false,
            'target' => 'accounts',
        ]);

        $response = $this->actingAs($pricingUser)->post('/notifications/read');

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('notification_reads', [
            'notification_id' => $pricingNotification->id,
            'user_id' => $pricingUser->id,
        ]);
        $this->assertDatabaseMissing('notification_reads', [
            'notification_id' => $accountsNotification->id,
            'user_id' => $pricingUser->id,
        ]);
    }

    public function test_staff_notifications_exclude_customer_portal_targets_even_for_super_admin(): void
    {
        $admin = User::where('username', 'admin')->first();

        $staffNotification = Notification::create([
            'id' => 'N-ADMIN-GENERAL-VISIBLE',
            'message' => 'Admin visible staff notice',
            'read' => false,
            'target' => 'general',
        ]);

        $customerNotification = Notification::create([
            'id' => 'N-CUSTOMER-PORTAL-HIDDEN-FROM-STAFF',
            'message' => 'Customer portal notice hidden from staff',
            'read' => false,
            'target' => 'customer',
        ]);

        $this->actingAs($admin)
            ->get('/notifications')
            ->assertStatus(200)
            ->assertSee($staffNotification->message)
            ->assertDontSee($customerNotification->message)
            ->assertDontSee('data-notification-row="'.$customerNotification->id.'"', false);

        $this->actingAs($admin)
            ->post('/notifications/read')
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notification_reads', [
            'notification_id' => $staffNotification->id,
            'user_id' => $admin->id,
        ]);
        $this->assertDatabaseMissing('notification_reads', [
            'notification_id' => $customerNotification->id,
            'user_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post("/notifications/{$customerNotification->id}/read")
            ->assertForbidden();
    }

    public function test_mark_all_notifications_only_hides_for_current_user(): void
    {
        $role = Role::where('name', 'pricing')->first();
        $firstUser = User::where('username', 'pricing_user')->first();
        $secondUser = User::create([
            'full_name' => 'Second Pricing User',
            'username' => 'pricing_user_two',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        Notification::create([
            'id' => 'N-PRICING-PER-USER',
            'message' => 'Pricing per user notice',
            'read' => false,
            'target' => 'pricing',
        ]);

        $this->actingAs($firstUser)
            ->post('/notifications/read')
            ->assertOk();

        $this->actingAs($firstUser)
            ->get('/pricing')
            ->assertStatus(200)
            ->assertDontSee('Pricing per user notice');

        $this->actingAs($secondUser)
            ->get('/pricing')
            ->assertStatus(200)
            ->assertSee('Pricing per user notice');
    }

    public function test_page_notifications_are_marked_read_only_for_current_user_and_target(): void
    {
        $role = Role::where('name', 'pricing')->first();
        $firstUser = User::where('username', 'pricing_user')->first();
        $secondUser = User::create([
            'full_name' => 'Second Pricing Page User',
            'username' => 'pricing_page_user_two',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $pricingNotification = Notification::create([
            'id' => 'N-PRICING-PAGE-READ',
            'message' => 'Pricing page read notice',
            'booking_id' => 'BK-PAGE-READ',
            'read' => false,
            'target' => 'pricing',
        ]);

        $accountsNotification = Notification::create([
            'id' => 'N-ACCOUNTS-PAGE-READ',
            'message' => 'Accounts page must stay unread',
            'booking_id' => 'BK-PAGE-READ',
            'read' => false,
            'target' => 'accounts',
        ]);

        $this->actingAs($firstUser)
            ->postJson('/notifications/page-read', [
                'target' => 'pricing',
                'notification_ids' => [
                    $pricingNotification->id,
                    $accountsNotification->id,
                ],
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'marked' => 1,
            ]);

        $this->assertFalse($pricingNotification->fresh()->read);
        $this->assertDatabaseHas('notification_reads', [
            'notification_id' => $pricingNotification->id,
            'user_id' => $firstUser->id,
        ]);
        $this->assertDatabaseMissing('notification_reads', [
            'notification_id' => $pricingNotification->id,
            'user_id' => $secondUser->id,
        ]);
        $this->assertDatabaseMissing('notification_reads', [
            'notification_id' => $accountsNotification->id,
            'user_id' => $firstUser->id,
        ]);

        $this->actingAs($firstUser)
            ->get('/pricing')
            ->assertStatus(200)
            ->assertDontSee('Pricing page read notice');

        $this->actingAs($secondUser)
            ->get('/pricing')
            ->assertStatus(200)
            ->assertSee('Pricing page read notice');
    }

    public function test_user_cannot_mark_hidden_notification_as_read(): void
    {
        $pricingUser = User::where('username', 'pricing_user')->first();

        $accountsNotification = Notification::create([
            'id' => 'N-ACCOUNTS-FORBIDDEN',
            'message' => 'Accounts forbidden notice',
            'read' => false,
            'target' => 'accounts',
        ]);

        $this->actingAs($pricingUser)
            ->post("/notifications/{$accountsNotification->id}/read")
            ->assertForbidden();

        $this->assertFalse($accountsNotification->fresh()->read);
        $this->assertDatabaseMissing('notification_reads', [
            'notification_id' => $accountsNotification->id,
            'user_id' => $pricingUser->id,
        ]);
    }

    public function test_accounts_can_issue_agent_payment_voucher_against_purchase_balance(): void
    {
        $admin = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Agent Payable Test',
            'phone' => '+967700000001',
            'email' => 'agent-payable@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);

        $booking = Booking::create([
            'id' => 'BK-AGENT-PAY-1',
            'customer_company' => 'Agent Customer',
            'customer_contact_name' => 'Agent Contact',
            'customer_phone' => '+967711111111',
            'customer_email' => 'agent-customer@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Agent purchase goods',
            'weight_kg' => 1200,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'priced',
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 600,
            'agent_purchase_quantity' => 1,
            'ocean_freight' => 800,
            'origin_charges' => 100,
            'dthc' => 50,
            'war_risk_surcharge' => 25,
            'other_charges' => 25,
        ]);

        $this->actingAs($admin)
            ->get('/accounts')
            ->assertStatus(200)
            ->assertDontSee('BK-AGENT-PAY-1')
            ->assertDontSee('600.00');

        $this->actingAs($admin)
            ->from('/accounts')
            ->post('/accounts/vouchers', [
                'type' => 'payment',
                'agent_id' => $agent->id,
                'booking_id' => 'BK-AGENT-PAY-1',
                'amount' => 250,
                'payment_method' => 'Bank Transfer',
                'payee_payer' => $agent->name,
                'description' => 'Agent payable before sales approval',
                'date' => now()->toDateString(),
            ])
            ->assertRedirect('/accounts')
            ->assertSessionHasErrors('booking_id');

        $booking->update(['status' => 'approved']);

        $this->actingAs($admin)
            ->get('/accounts')
            ->assertStatus(200)
            ->assertSee('Agent Payable Test')
            ->assertSee('600.00')
            ->assertSee('openAgentPaymentVoucherModal', false)
            ->assertSee('v-agent-select', false)
            ->assertSee('سجل سندات القبض الخاصة بالعملاء')
            ->assertSee('سندات الصرف الخاصة بالوكلاء')
            ->assertDontSee('سند صرف جديد')
            ->assertDontSee('openAgentPaymentVoucherFromButton', false);

        $this->actingAs($admin)
            ->post('/accounts/vouchers', [
                'type' => 'payment',
                'agent_id' => $agent->id,
                'booking_id' => 'BK-AGENT-PAY-1',
                'amount' => 250,
                'payment_method' => 'Bank Transfer',
                'payee_payer' => $agent->name,
                'description' => 'Agent payable settlement',
                'date' => now()->toDateString(),
            ])
            ->assertRedirect('/accounts');

        $this->assertDatabaseHas('vouchers', [
            'type' => 'payment',
            'agent_id' => $agent->id,
            'booking_id' => 'BK-AGENT-PAY-1',
            'amount' => 250,
            'paid_before' => 0,
            'paid_after' => 250,
            'balance_after' => 350,
        ]);

        $voucher = Voucher::where('type', 'payment')
            ->where('agent_id', $agent->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get('/accounts')
            ->assertStatus(200)
            ->assertSee("accounts/vouchers/{$voucher->id}", false)
            ->assertSee('openAgentPaymentVoucherEditFromButton', false);

        $this->actingAs($admin)
            ->put("/accounts/vouchers/{$voucher->id}", [
                'type' => 'payment',
                'agent_id' => $agent->id,
                'booking_id' => 'BK-AGENT-PAY-1',
                'amount' => 300,
                'payment_method' => 'Bank Transfer',
                'payee_payer' => $agent->name,
                'description' => 'Updated agent payable settlement',
                'date' => now()->toDateString(),
            ])
            ->assertRedirect('/accounts');

        $this->assertDatabaseHas('vouchers', [
            'id' => $voucher->id,
            'type' => 'payment',
            'agent_id' => $agent->id,
            'booking_id' => 'BK-AGENT-PAY-1',
            'amount' => 300,
            'paid_before' => 0,
            'paid_after' => 300,
            'balance_after' => 300,
            'description' => 'Updated agent payable settlement',
        ]);

        $this->actingAs($admin)
            ->from('/accounts')
            ->post('/accounts/vouchers', [
                'type' => 'payment',
                'agent_id' => $agent->id,
                'amount' => 400,
                'payment_method' => 'Bank Transfer',
                'payee_payer' => $agent->name,
                'description' => 'Overpay agent balance',
                'date' => now()->toDateString(),
            ])
            ->assertRedirect('/accounts')
            ->assertSessionHasErrors('amount');

        $this->actingAs($admin)
            ->from('/accounts')
            ->post('/accounts/vouchers', [
                'type' => 'payment',
                'amount' => 100,
                'payment_method' => 'Bank Transfer',
                'payee_payer' => 'General payment without agent',
                'description' => 'Should not create general payment voucher',
                'date' => now()->toDateString(),
            ])
            ->assertRedirect('/accounts')
            ->assertSessionHasErrors('agent_id');
    }

    public function test_accounts_can_issue_customer_receipt_voucher_against_invoice_balance(): void
    {
        $admin = User::where('username', 'admin')->first();
        $customer = Customer::create([
            'company' => 'Receipt Customer',
            'contact_name' => 'Receipt Contact',
            'phone' => '+967700600600',
            'email' => 'receipt-customer@example.com',
            'password' => 'receipt-password',
            'portal_enabled' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-RECEIPT-1',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Receipt goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'approved',
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-RECEIPT-1',
            'booking_id' => $booking->id,
            'amount' => 1500,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($admin)
            ->post('/accounts/vouchers', [
                'type' => 'receipt',
                'invoice_id' => $invoice->id,
                'amount' => 1000,
                'payment_method' => 'Cash',
                'payee_payer' => $customer->company,
                'description' => 'Partial receipt against invoice',
                'date' => now()->toDateString(),
            ])
            ->assertRedirect('/accounts');

        $this->assertDatabaseHas('vouchers', [
            'type' => 'receipt',
            'booking_id' => $booking->id,
            'invoice_id' => $invoice->id,
            'amount' => 1000,
            'paid_before' => 0,
            'paid_after' => 1000,
            'balance_after' => 500,
        ]);
    }

    public function test_accounts_can_invoice_customs_files_and_include_them_in_customer_statement(): void
    {
        $admin = User::where('username', 'admin')->first();
        $customer = Customer::create([
            'company' => 'Customs Account Customer',
            'contact_name' => 'Customs Account Contact',
            'phone' => '+967700777888',
            'email' => 'customs-account@example.com',
        ]);

        $booking = Booking::create([
            'id' => 'BK-CUSTOMS-ACC-1',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'CUSTOMS',
            'goods_description' => 'Customs account goods',
            'weight_kg' => 100,
            'volume_cbm' => 0,
            'pol' => 'Aden Customs',
            'pod' => 'Aden',
            'status' => 'approved',
            'customs_status' => 'duties',
            'customs_reference' => 'CUS-ACC-1',
            'customs_fees' => 500,
        ]);

        $this->actingAs($admin)
            ->get('/accounts')
            ->assertStatus(200)
            ->assertSee('حسابات الجمارك')
            ->assertSee('BK-CUSTOMS-ACC-1')
            ->assertSee('CUS-ACC-1');

        $this->actingAs($admin)
            ->post('/accounts/invoices', [
                'booking_id' => $booking->id,
                'invoice_type' => 'customs',
                'amount' => 0,
                'customs_government_fees' => 500,
                'customs_service_fee' => 120,
                'customs_other_fee' => 30,
                'description' => 'Customs duties and clearance service',
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(7)->toDateString(),
                'status' => 'غير مدفوعة',
            ])
            ->assertRedirect(route('accounts.index', ['accounts_tab' => 'customs']));

        $invoice = Invoice::where('booking_id', $booking->id)
            ->where('invoice_type', 'customs')
            ->firstOrFail();

        $this->assertSame(650.0, (float) $invoice->amount);
        $this->assertSame('Customs duties and clearance service', $invoice->description);

        $booking->refresh();
        $this->assertSame(650.0, (float) $booking->customs_fees);
        $this->assertSame(120.0, (float) data_get($booking->cargo_details, 'customs_accounting.service_fee'));
        $this->assertSame($invoice->id, data_get($booking->cargo_details, 'customs_accounting.last_invoice_id'));

        $this->actingAs($admin)
            ->get('/accounts?accounts_tab=customs')
            ->assertStatus(200)
            ->assertSee("/accounts/{$invoice->id}/invoice", false)
            ->assertSee('printer', false);

        $this->actingAs($admin)
            ->get("/accounts/{$invoice->id}/invoice")
            ->assertStatus(200)
            ->assertSee('فاتورة تخليص جمركي')
            ->assertSee('خدمات التخليص الجمركي')
            ->assertSee('Customs duties and clearance service');

        $this->actingAs($admin)
            ->get('/accounts/statements/print?account_type=customer&account_id=' . $customer->id . '&statement_type=detailed&statement_scope=customs')
            ->assertStatus(200)
            ->assertSee('Customs Account Customer')
            ->assertSee('فاتورة جمارك')
            ->assertSee($invoice->id)
            ->assertSee('Customs duties and clearance service');

        $this->actingAs($admin)
            ->get('/accounts/statements/print?account_type=customer&account_id=' . $customer->id . '&statement_type=detailed&statement_scope=freight')
            ->assertStatus(200)
            ->assertDontSee($invoice->id);
    }

    public function test_accounts_can_print_customer_and_agent_account_statements(): void
    {
        $admin = User::where('username', 'admin')->first();
        $customer = Customer::create([
            'company' => 'Statement Customer',
            'contact_name' => 'Statement Contact',
            'phone' => '+967700000100',
            'email' => 'statement-customer@example.com',
        ]);
        $agent = Agent::create([
            'name' => 'Statement Agent',
            'phone' => '+967700000200',
            'email' => 'statement-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);

        $booking = Booking::create([
            'id' => 'BK-STMT-1',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Statement goods',
            'weight_kg' => 1200,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'approved',
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 600,
            'agent_purchase_quantity' => 1,
            'ocean_freight' => 900,
            'origin_charges' => 100,
            'dthc' => 0,
            'war_risk_surcharge' => 0,
            'other_charges' => 0,
            'priced_at' => now(),
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-STMT-1',
            'booking_id' => $booking->id,
            'amount' => 1000,
            'currency' => 'USD',
            'status' => 'مدفوعة جزئياً',
            'issued_at' => now(),
            'due_at' => now()->addDays(14),
        ]);

        Voucher::create([
            'id' => 'REC-STMT-1',
            'type' => 'receipt',
            'booking_id' => $booking->id,
            'invoice_id' => $invoice->id,
            'amount' => 300,
            'paid_before' => 0,
            'paid_after' => 300,
            'balance_after' => 700,
            'payment_method' => 'Cash',
            'payee_payer' => $customer->company,
            'description' => 'Customer receipt against statement invoice',
            'date' => now()->toDateString(),
        ]);

        Voucher::create([
            'id' => 'PAY-STMT-1',
            'type' => 'payment',
            'booking_id' => $booking->id,
            'agent_id' => $agent->id,
            'amount' => 150,
            'paid_before' => 0,
            'paid_after' => 150,
            'balance_after' => 450,
            'payment_method' => 'Cash',
            'payee_payer' => $agent->name,
            'description' => 'Agent payment against statement booking',
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($admin)
            ->get('/accounts')
            ->assertStatus(200)
            ->assertSee('إصدار كشف حساب')
            ->assertSee('statement-account-type', false);

        $this->actingAs($admin)
            ->get('/accounts/statements/print?account_type=customer&account_id=' . $customer->id . '&statement_type=detailed')
            ->assertStatus(200)
            ->assertSee('Statement Customer')
            ->assertSee('INV-STMT-1')
            ->assertSee('REC-STMT-1')
            ->assertSee('مدين $ 700.00');

        $this->actingAs($admin)
            ->get('/accounts/statements/print?account_type=agent&account_id=' . $agent->id . '&statement_type=summary')
            ->assertStatus(200)
            ->assertSee('Statement Agent')
            ->assertSee('كشف حساب إجمالي')
            ->assertSee('دائن $ 450.00');
    }

    public function test_pricing_calculates_agent_purchase_total_from_unit_price_and_quantity(): void
    {
        $admin = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Pricing Quantity Agent',
            'phone' => '+967700000300',
            'email' => 'pricing-quantity-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);
        $booking = Booking::create([
            'id' => 'BK-PRICE-QTY-1',
            'customer_company' => 'Pricing Quantity Customer',
            'customer_contact_name' => 'Pricing Contact',
            'customer_phone' => '+967711111333',
            'customer_email' => 'pricing-quantity@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 3,
            'goods_description' => 'Pricing quantity goods',
            'weight_kg' => 1800,
            'volume_cbm' => 18,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->post("/pricing/{$booking->id}/price", [
                'oceanFreight' => 900,
                'originCharges' => 100,
                'dthc' => 50,
                'warRiskSurcharge' => 25,
                'otherCharges' => 25,
                'destinationPortChangeFee' => 10,
                'otherPenalties' => 15,
                'transitDays' => 28,
                'validityDate' => now()->addDays(14)->toDateString(),
                'agent_id' => $agent->id,
                'agentPurchaseUnitPrice' => 200,
                'agentPurchaseQuantity' => 3,
                'agentPurchaseNotes' => 'Unit purchase price calculation',
                'notes' => 'Sale price calculation',
                'pricedBy' => 'قسم التسعير',
            ])
            ->assertRedirect('/pricing');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'agent_id' => $agent->id,
            'ocean_freight' => 2700,
            'origin_charges' => 300,
            'dthc' => 150,
            'war_risk_surcharge' => 75,
            'other_charges' => 75,
            'destination_port_change_fee' => 30,
            'other_penalties' => 45,
            'agent_purchase_amount' => 600,
            'agent_purchase_quantity' => 3,
            'profit_amount' => 2775,
            'status' => 'priced',
        ]);

        $this->assertDatabaseHas('notifications', [
            'booking_id' => $booking->id,
            'target' => 'sales',
            'read' => false,
        ]);

        $this->assertDatabaseHas('notifications', [
            'booking_id' => $booking->id,
            'target' => 'customer',
            'read' => false,
        ]);
    }

    public function test_pricing_saves_custom_charge_lines_and_adds_them_to_sale_total(): void
    {
        $admin = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Custom Pricing Agent',
            'phone' => '+967700000301',
            'email' => 'custom-pricing-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);
        $booking = Booking::create([
            'id' => 'BK-PRICE-CUSTOM-LINES',
            'customer_company' => 'Custom Pricing Customer',
            'customer_contact_name' => 'Pricing Contact',
            'customer_phone' => '+967711111337',
            'customer_email' => 'custom-pricing@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Custom pricing goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->post(route('pricing.store', $booking), [
                'oceanFreight' => 900,
                'originCharges' => 100,
                'dthc' => 50,
                'warRiskSurcharge' => 25,
                'otherCharges' => 25,
                'destinationPortChangeFee' => 10,
                'otherPenalties' => 15,
                'customPricingCharges' => [
                    ['label' => 'رسوم فحص خاصة', 'amount' => 120],
                    ['label' => 'خدمة تغليف إضافية', 'amount' => 35.5],
                    ['label' => '', 'amount' => 999],
                ],
                'transitDays' => 28,
                'validityDate' => now()->addDays(14)->toDateString(),
                'agent_id' => $agent->id,
                'agentPurchaseUnitPrice' => 200,
                'agentPurchaseQuantity' => 1,
                'agentPurchaseNotes' => 'Custom lines purchase',
                'notes' => 'Custom lines sale price',
                'pricedBy' => 'قسم التسعير',
            ])
            ->assertRedirect('/pricing');

        $booking->refresh();

        $this->assertEquals([
            ['label' => 'رسوم فحص خاصة', 'amount' => 120.0],
            ['label' => 'خدمة تغليف إضافية', 'amount' => 35.5],
        ], $booking->customPricingCharges());
        $this->assertSame(155.5, $booking->customPricingChargesTotal());
        $this->assertSame(1280.5, $booking->pricingTotal());
        $this->assertSame(1080.5, (float) $booking->profit_amount);
        $this->assertEquals([
            ['label' => 'رسوم فحص خاصة', 'amount' => 120.0],
            ['label' => 'خدمة تغليف إضافية', 'amount' => 35.5],
        ], data_get($booking->cargo_details, 'custom_pricing_charges'));
    }

    public function test_pricing_page_allows_updating_recent_priced_offer(): void
    {
        $admin = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Recent Edit Pricing Agent',
            'phone' => '+967700000305',
            'email' => 'recent-edit-pricing-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);
        $booking = Booking::create([
            'id' => 'BK-PRICE-RECENT-EDIT',
            'customer_company' => 'Recent Edit Customer',
            'customer_contact_name' => 'Recent Contact',
            'customer_phone' => '+967711111338',
            'customer_email' => 'recent-edit@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Recent priced goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'priced',
            'ocean_freight' => 900,
            'origin_charges' => 100,
            'dthc' => 50,
            'war_risk_surcharge' => 25,
            'other_charges' => 25,
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 200,
            'agent_purchase_quantity' => 1,
            'profit_amount' => 900,
            'transit_days' => 20,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('pricing.index'))
            ->assertStatus(200)
            ->assertSee('BK-PRICE-RECENT-EDIT')
            ->assertSee('تعديل السعر');

        $this->actingAs($admin)
            ->post(route('pricing.store', $booking), [
                'pricingEditMode' => 1,
                'oceanFreight' => 1100,
                'originCharges' => 150,
                'dthc' => 70,
                'warRiskSurcharge' => 20,
                'otherCharges' => 30,
                'destinationPortChangeFee' => 5,
                'otherPenalties' => 10,
                'customPricingCharges' => [
                    ['label' => 'رسوم تعديل خاصة', 'amount' => 80.5],
                ],
                'transitDays' => 18,
                'validityDate' => now()->addDays(12)->toDateString(),
                'agent_id' => $agent->id,
                'agentPurchaseUnitPrice' => 400,
                'agentPurchaseQuantity' => 1,
                'agentPurchaseNotes' => 'Updated purchase cost',
                'notes' => 'Updated sale quote',
                'pricedBy' => 'قسم التسعير',
            ])
            ->assertRedirect('/pricing');

        $booking->refresh();

        $this->assertSame('priced', $booking->status);
        $this->assertSame(1465.5, $booking->pricingTotal());
        $this->assertSame(400.0, (float) $booking->agent_purchase_amount);
        $this->assertSame(1065.5, (float) $booking->profit_amount);
        $this->assertSame(18, (int) $booking->transit_days);
        $this->assertEquals([
            ['label' => 'رسوم تعديل خاصة', 'amount' => 80.5],
        ], $booking->customPricingCharges());
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'PRICING_UPDATE',
            'table_name' => 'bookings',
            'record_id' => $booking->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'booking_id' => $booking->id,
            'target' => 'sales',
            'read' => false,
        ]);
    }

    public function test_pricing_update_mode_does_not_reopen_locked_operational_offers(): void
    {
        $admin = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Locked Pricing Agent',
            'phone' => '+967700000306',
            'email' => 'locked-pricing-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);
        $booking = Booking::create([
            'id' => 'BK-PRICE-LOCKED-EDIT',
            'customer_company' => 'Locked Edit Customer',
            'customer_contact_name' => 'Locked Contact',
            'customer_phone' => '+967711111339',
            'customer_email' => 'locked-edit@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20GP',
            'container_qty' => 1,
            'goods_description' => 'Locked priced goods',
            'weight_kg' => 900,
            'volume_cbm' => 9,
            'pol' => 'Ningbo',
            'pod' => 'Aden',
            'status' => 'approved',
            'ocean_freight' => 600,
            'origin_charges' => 100,
            'dthc' => 40,
            'war_risk_surcharge' => 10,
            'other_charges' => 20,
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 300,
            'agent_purchase_quantity' => 1,
            'profit_amount' => 470,
            'transit_days' => 15,
            'validity_date' => now()->addDays(10),
            'priced_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('pricing.store', $booking), [
                'pricingEditMode' => 1,
                'oceanFreight' => 999,
                'originCharges' => 100,
                'dthc' => 40,
                'warRiskSurcharge' => 10,
                'otherCharges' => 20,
                'transitDays' => 15,
                'validityDate' => now()->addDays(10)->toDateString(),
                'agent_id' => $agent->id,
                'agentPurchaseUnitPrice' => 300,
                'agentPurchaseQuantity' => 1,
            ])
            ->assertRedirect('/pricing')
            ->assertSessionHas('error');

        $booking->refresh();

        $this->assertSame('approved', $booking->status);
        $this->assertSame(600.0, (float) $booking->ocean_freight);
        $this->assertSame(770.0, $booking->pricingTotal());
    }

    public function test_exw_sea_pricing_saves_optional_exw_charges_in_total(): void
    {
        $admin = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'EXW Pricing Agent',
            'phone' => '+967700000302',
            'email' => 'exw-pricing-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);
        $booking = Booking::create([
            'id' => 'BK-PRICE-EXW-1',
            'customer_company' => 'EXW Pricing Customer',
            'customer_contact_name' => 'EXW Contact',
            'customer_phone' => '+967711111334',
            'customer_email' => 'exw-pricing@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 2,
            'goods_description' => 'EXW pricing goods',
            'weight_kg' => 1800,
            'volume_cbm' => 18,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
            'route_details' => [
                'category' => 'sea',
                'mode' => 'EXW',
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('pricing.store', $booking), [
                'oceanFreight' => 900,
                'originCharges' => 100,
                'exwCharges' => 75,
                'dthc' => 50,
                'warRiskSurcharge' => 25,
                'otherCharges' => 25,
                'destinationPortChangeFee' => 10,
                'otherPenalties' => 15,
                'transitDays' => 28,
                'validityDate' => now()->addDays(14)->toDateString(),
                'agent_id' => $agent->id,
                'agentPurchaseUnitPrice' => 200,
                'agentPurchaseQuantity' => 2,
                'agentPurchaseNotes' => 'EXW unit purchase',
                'notes' => 'EXW sale price',
                'pricedBy' => 'قسم التسعير',
            ])
            ->assertRedirect('/pricing');

        $booking->refresh();

        $this->assertSame(150.0, (float) $booking->exw_charges);
        $this->assertSame(2400.0, $booking->pricingTotal());
        $this->assertSame(2000.0, (float) $booking->profit_amount);
        $this->assertTrue($booking->shouldShowExwCharges());
    }

    public function test_non_exw_sea_pricing_ignores_exw_charges_even_if_submitted(): void
    {
        $admin = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'FOB Pricing Agent',
            'phone' => '+967700000303',
            'email' => 'fob-pricing-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);
        $booking = Booking::create([
            'id' => 'BK-PRICE-FOB-1',
            'customer_company' => 'FOB Pricing Customer',
            'customer_contact_name' => 'FOB Contact',
            'customer_phone' => '+967711111335',
            'customer_email' => 'fob-pricing@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20GP',
            'container_qty' => 1,
            'goods_description' => 'FOB pricing goods',
            'weight_kg' => 900,
            'volume_cbm' => 9,
            'pol' => 'Ningbo',
            'pod' => 'Aden',
            'status' => 'new',
            'route_details' => [
                'category' => 'sea',
                'mode' => 'FOB',
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('pricing.store', $booking), [
                'oceanFreight' => 500,
                'originCharges' => 100,
                'exwCharges' => 999,
                'dthc' => 50,
                'warRiskSurcharge' => 25,
                'otherCharges' => 25,
                'transitDays' => 18,
                'validityDate' => now()->addDays(10)->toDateString(),
                'agent_id' => $agent->id,
                'agentPurchaseUnitPrice' => 100,
                'agentPurchaseQuantity' => 1,
                'pricedBy' => 'قسم التسعير',
            ])
            ->assertRedirect('/pricing');

        $booking->refresh();

        $this->assertSame(0.0, (float) $booking->exw_charges);
        $this->assertSame(700.0, $booking->pricingTotal());
        $this->assertFalse($booking->shouldShowExwCharges());
    }

    public function test_exw_charges_for_mixed_fcl_pricing_use_total_container_quantity(): void
    {
        app()->setLocale('ar');

        $admin = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Mixed EXW Pricing Agent',
            'phone' => '+967700000304',
            'email' => 'mixed-exw-pricing-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);
        $booking = Booking::create([
            'id' => 'BK-PRICE-MIXED-EXW',
            'customer_company' => 'Mixed EXW Customer',
            'customer_contact_name' => 'Mixed EXW Contact',
            'customer_phone' => '+967711111336',
            'customer_email' => 'mixed-exw@example.com',
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
            'goods_description' => 'Mixed EXW pricing goods',
            'weight_kg' => 2400,
            'volume_cbm' => 24,
            'pol' => 'Qingdao',
            'pod' => 'Aden',
            'status' => 'new',
            'route_details' => [
                'category' => 'sea',
                'mode' => 'EXW',
            ],
        ]);

        $this->actingAs($admin)
            ->post(route('pricing.store', $booking), [
                'fclPricingLines' => [
                    ['type' => '40GP', 'qty' => 2, 'unit_price' => 600],
                    ['type' => '20GP', 'qty' => 3, 'unit_price' => 400],
                ],
                'agentPurchaseLines' => [
                    ['type' => '40GP', 'qty' => 2, 'unit_price' => 300],
                    ['type' => '20GP', 'qty' => 3, 'unit_price' => 200],
                ],
                'originCharges' => 20,
                'exwCharges' => 15,
                'dthc' => 10,
                'warRiskSurcharge' => 5,
                'otherCharges' => 0,
                'destinationPortChangeFee' => 0,
                'otherPenalties' => 0,
                'transitDays' => 21,
                'validityDate' => now()->addDays(12)->toDateString(),
                'agent_id' => $agent->id,
                'agentPurchaseUnitPrice' => 0,
                'agentPurchaseQuantity' => 5,
                'pricedBy' => 'قسم التسعير',
            ])
            ->assertRedirect('/pricing');

        $booking->refresh();
        $exwLine = collect(data_get($booking->cargo_details, 'fcl_standard_charges', []))
            ->firstWhere('label', 'أجور EXW');

        $this->assertSame(75.0, (float) $booking->exw_charges);
        $this->assertSame(2650.0, $booking->pricingTotal());
        $this->assertEquals(15.0, (float) ($exwLine['unit_price'] ?? 0));
        $this->assertEquals(75.0, (float) ($exwLine['amount'] ?? 0));
    }

    public function test_accounts_invoice_edit_preserves_destination_change_and_penalty_charges(): void
    {
        $admin = User::where('username', 'admin')->first();
        $booking = Booking::create([
            'id' => 'BK-INVOICE-EXTRA-CHARGES',
            'customer_company' => 'Invoice Extra Customer',
            'customer_contact_name' => 'Invoice Contact',
            'customer_phone' => '+967711111445',
            'customer_email' => 'invoice-extra@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Invoice extra charges goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'approved',
            'ocean_freight' => 1000,
            'origin_charges' => 100,
            'dthc' => 50,
            'war_risk_surcharge' => 25,
            'other_charges' => 25,
            'destination_port_change_fee' => 40,
            'other_penalties' => 20,
        ]);
        $invoice = Invoice::create([
            'id' => 'INV-EXTRA-CHARGES',
            'booking_id' => $booking->id,
            'invoice_type' => 'freight',
            'amount' => $booking->pricingTotal(),
            'status' => 'غير مدفوعة',
            'issued_at' => now(),
            'due_at' => now()->addDays(10),
        ]);

        $this->actingAs($admin)
            ->put('/accounts/invoices/' . $invoice->id, [
                'amount' => 1287,
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(15)->toDateString(),
                'status' => 'مدفوعة جزئياً',
                'ocean_freight' => 1000,
                'origin_charges' => 100,
                'dthc' => 50,
                'war_risk_surcharge' => 25,
                'other_charges' => 25,
                'destination_port_change_fee' => 55,
                'other_penalties' => 32,
                'pol' => 'Ningbo',
                'pod' => 'Hodeidah',
                'custom_charge_desc' => null,
                'custom_charge_amount' => 0,
            ])
            ->assertRedirect('/accounts');

        $booking->refresh();
        $invoice->refresh();

        $this->assertSame(55.0, (float) $booking->destination_port_change_fee);
        $this->assertSame(32.0, (float) $booking->other_penalties);
        $this->assertSame(1287.0, (float) $invoice->amount);
        $this->assertSame(1287.0, $booking->pricingTotal());
    }

    public function test_pricing_submission_does_not_duplicate_notifications_after_request_moves_forward(): void
    {
        $admin = User::where('username', 'admin')->first();
        $agent = Agent::create([
            'name' => 'Pricing Idempotent Agent',
            'phone' => '+967700000301',
            'email' => 'pricing-idempotent-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);
        $booking = Booking::create([
            'id' => 'BK-PRICE-IDEMPOTENT',
            'customer_company' => 'Pricing Idempotent Customer',
            'customer_contact_name' => 'Pricing Contact',
            'customer_phone' => '+967711111334',
            'customer_email' => 'pricing-idempotent@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Pricing idempotent goods',
            'weight_kg' => 1800,
            'volume_cbm' => 18,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);
        $payload = [
            'oceanFreight' => 900,
            'originCharges' => 100,
            'dthc' => 50,
            'warRiskSurcharge' => 25,
            'otherCharges' => 25,
            'transitDays' => 28,
            'validityDate' => now()->addDays(14)->toDateString(),
            'agent_id' => $agent->id,
            'agentPurchaseUnitPrice' => 200,
            'agentPurchaseQuantity' => 1,
            'agentPurchaseNotes' => 'First pricing only',
            'notes' => 'Pricing retry guard',
            'pricedBy' => 'Pricing Desk',
        ];

        $this->actingAs($admin)
            ->post(route('pricing.store', $booking), $payload)
            ->assertRedirect(route('pricing.index'));

        $this->actingAs($admin)
            ->post(route('pricing.store', $booking), $payload)
            ->assertRedirect(route('pricing.index'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'priced',
            'ocean_freight' => 900,
        ]);
        $this->assertSame(1, Notification::where('booking_id', $booking->id)->where('target', 'sales')->count());
        $this->assertSame(1, Notification::where('booking_id', $booking->id)->where('target', 'customer')->count());
    }

    public function test_carrier_booking_vessel_can_be_typed_and_reused_as_option(): void
    {
        $admin = User::where('username', 'admin')->first();

        Booking::create([
            'id' => 'BK-CARRIER-PREV',
            'customer_company' => 'Previous Carrier Customer',
            'customer_contact_name' => 'Previous Contact',
            'customer_phone' => '+967700777001',
            'customer_email' => 'previous-carrier@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Previous carrier goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'booked',
            'carrier_vessel' => 'MV Customer Reusable',
            'carrier_voyage' => 'CR-001E',
            'carrier_booking_ref' => 'CR-REF-001',
            'carrier_etd' => '2026-08-01',
            'carrier_eta' => '2026-08-20',
        ]);

        $booking = Booking::create([
            'id' => 'BK-CARRIER-MANUAL',
            'customer_company' => 'Manual Carrier Customer',
            'customer_contact_name' => 'Manual Contact',
            'customer_phone' => '+967700777002',
            'customer_email' => 'manual-carrier@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20GP',
            'container_qty' => 1,
            'goods_description' => 'Manual carrier goods',
            'weight_kg' => 1000,
            'volume_cbm' => 10,
            'pol' => 'Ningbo',
            'pod' => 'Hodeidah',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->get('/bookings')
            ->assertStatus(200)
            ->assertSee('data-carrier-vessel-picker', false)
            ->assertSee('data-carrier-vessel-name="MV Customer Reusable"', false);

        $this->actingAs($admin)
            ->post("/bookings/{$booking->id}/confirm", [
                'vessel' => 'MV Manual Phoenix',
                'voyage' => 'MPX-2026E',
                'bookingRef' => 'MPX-REF-2026',
                'etd' => '2026-08-10',
                'eta' => '2026-08-28',
                'bookedBy' => 'قسم الحجوزات الملاحية',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'booked',
            'carrier_vessel' => 'MV Manual Phoenix',
            'carrier_voyage' => 'MPX-2026E',
            'carrier_booking_ref' => 'MPX-REF-2026',
        ]);

        $track = ShipmentTrack::where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame(15, $track->progress);
        $this->assertSame(1, ShipmentEvent::where('shipment_track_id', $track->id)->count());

        $this->actingAs($admin)
            ->get('/bookings')
            ->assertStatus(200)
            ->assertSee('data-carrier-vessel-name="MV Manual Phoenix"', false);
    }

    public function test_staff_can_manage_verified_schedules_and_public_page_only_shows_published_data(): void
    {
        $admin = User::where('username', 'admin')->first();

        Schedule::create([
            'vessel' => 'Draft Vessel',
            'voyage' => 'DR-001',
            'carrier' => 'Draft Carrier',
            'origin' => 'Shanghai',
            'destination' => 'Aden',
            'etd' => '2026-09-01',
            'eta' => '2026-09-20',
            'transit' => '19 days',
            'cutoff' => '2026-08-29',
            'status' => 'draft',
            'is_published' => false,
            'source_name' => 'Unverified agent note',
        ]);

        Schedule::create([
            'vessel' => 'Expired Vessel',
            'voyage' => 'EX-001',
            'carrier' => 'Expired Carrier',
            'origin' => 'Shanghai',
            'destination' => 'Aden',
            'etd' => '2026-09-01',
            'eta' => '2026-09-20',
            'transit' => '19 days',
            'cutoff' => '2026-08-29',
            'status' => 'published',
            'is_published' => true,
            'source_name' => 'Expired official schedule',
            'source_reference' => 'EXPIRED-001',
            'source_checked_at' => '2026-07-20',
            'expires_at' => '2026-08-01',
        ]);

        $this->actingAs($admin)
            ->get(route('schedules.manage'))
            ->assertOk()
            ->assertSee('Draft Vessel');

        $activeExpiry = now()->addDays(30)->toDateString();

        $this->actingAs($admin)
            ->post(route('schedules.store'), [
                'vessel' => 'Trusted Vessel',
                'voyage' => 'TV-001E',
                'carrier' => 'Trusted Carrier',
                'service_name' => 'Asia Gulf Express',
                'origin' => 'Shanghai',
                'origin_code' => 'cnsha',
                'destination' => 'Aden',
                'destination_code' => 'yeade',
                'etd' => '2026-09-01',
                'eta' => '2026-09-16',
                'cutoff' => '2026-08-29',
                'status' => 'published',
                'is_published' => '1',
                'source_name' => 'Carrier official schedule',
                'source_url' => 'https://example.com/trusted-schedule',
                'source_reference' => 'REF-001',
                'source_checked_at' => '2026-08-10',
                'expires_at' => $activeExpiry,
                'booking_url' => 'https://example.com/booking',
                'notes' => 'Verified from carrier source',
            ])
            ->assertRedirect(route('schedules.manage'));

        $schedule = Schedule::where('vessel', 'Trusted Vessel')->firstOrFail();

        $this->assertTrue((bool) $schedule->is_published);
        $this->assertSame('CNSHA', $schedule->origin_code);
        $this->assertSame('YEADE', $schedule->destination_code);
        $this->assertStringContainsString('15', $schedule->transit);
        $this->assertSame($activeExpiry, $schedule->expires_at->toDateString());

        $this->get(route('schedules'))
            ->assertOk()
            ->assertSee('Trusted Vessel')
            ->assertSee('Trusted Carrier')
            ->assertSee('Carrier official schedule')
            ->assertSee($activeExpiry)
            ->assertDontSee('Expired Vessel')
            ->assertDontSee('Draft Vessel');

        $this->actingAs($admin)
            ->put(route('schedules.update', $schedule), [
                'vessel' => 'Trusted Vessel',
                'voyage' => 'TV-001E',
                'carrier' => 'Trusted Carrier',
                'service_name' => 'Asia Gulf Express',
                'origin' => 'Shanghai',
                'origin_code' => 'CNSHA',
                'destination' => 'Aden',
                'destination_code' => 'YEADE',
                'etd' => '2026-09-01',
                'eta' => '2026-09-16',
                'cutoff' => '2026-08-29',
                'status' => 'published',
                'is_published' => '0',
                'source_name' => 'Carrier official schedule',
                'source_url' => 'https://example.com/trusted-schedule',
                'source_reference' => 'REF-001',
                'source_checked_at' => '2026-08-10',
                'expires_at' => '2026-09-30',
                'booking_url' => 'https://example.com/booking',
                'notes' => 'Temporarily hidden',
            ])
            ->assertRedirect(route('schedules.manage'));

        $schedule->refresh();
        $this->assertFalse((bool) $schedule->is_published);
        $this->assertSame('draft', $schedule->status);

        $this->get(route('schedules'))
            ->assertOk()
            ->assertDontSee('Trusted Vessel');

        $this->actingAs($admin)
            ->delete(route('schedules.destroy', $schedule))
            ->assertRedirect(route('schedules.manage'));

        $this->assertDatabaseMissing('schedules', [
            'id' => $schedule->id,
        ]);
    }

    public function test_role_permissions_include_ports_agents_and_customs(): void
    {
        $admin = User::where('username', 'admin')->first();

        $this->actingAs($admin)
            ->get('/roles')
            ->assertStatus(200)
            ->assertSee('إدارة الموانئ')
            ->assertSee('إدارة الوكلاء')
            ->assertSee('التخليص الجمركي');
    }

    public function test_customs_permission_can_manage_clearance_files(): void
    {
        $role = Role::create([
            'name' => 'customs_only',
            'permissions' => ['manage_customs'],
        ]);

        $user = User::create([
            'full_name' => 'موظف التخليص',
            'username' => 'customs_user',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-CUSTOMS-1',
            'customer_company' => 'Customs Customer',
            'customer_contact_name' => 'Customs Contact',
            'customer_phone' => '+967711111111',
            'customer_email' => 'customs@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Auto parts',
            'weight_kg' => 1200,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get('/customs-clearance')
            ->assertStatus(200)
            ->assertSee($booking->id);

        $response = $this->actingAs($user)->put("/customs-clearance/{$booking->id}", [
            'customs_status' => 'cleared',
            'customs_reference' => 'CUS-REF-1',
            'customs_declaration_no' => 'DEC-1',
            'customs_bayan_no' => 'BAYAN-1',
            'customs_fees' => 150,
            'customs_documents' => ['invoice', 'packing_list'],
            'customs_notes' => 'Cleared successfully',
        ]);

        $response->assertRedirect('/customs-clearance');
        $booking->refresh();

        $this->assertSame('cleared', $booking->customs_status);
        $this->assertSame('CUS-REF-1', $booking->customs_reference);
        $this->assertSame(['invoice', 'packing_list'], $booking->customs_documents);
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'CUSTOMS_UPDATE',
            'record_id' => $booking->id,
        ]);
    }

    public function test_admin_can_manage_users(): void
    {
        $admin = User::where('username', 'admin')->first();
        $role = Role::where('name', 'pricing')->first();

        // 1. Index
        $response = $this->actingAs($admin)->get('/users');
        $response->assertStatus(200);

        // 2. Store (Create)
        $createResponse = $this->actingAs($admin)->post('/users', [
            'full_name' => 'موظف تجريبي جديد',
            'username' => 'test_worker',
            'phone' => '+966555555555',
            'password' => 'secure123',
            'role_id' => $role->id,
            'is_active' => '1',
        ]);
        
        $createResponse->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['username' => 'test_worker']);

        $newWorker = User::where('username', 'test_worker')->first();

        // 3. Update
        $updateResponse = $this->actingAs($admin)->put("/users/{$newWorker->id}", [
            'full_name' => 'موظف تجريبي معدل',
            'username' => 'test_worker_updated',
            'phone' => '+966555555555',
            'role_id' => $role->id,
            'is_active' => '1',
        ]);
        
        $updateResponse->assertRedirect('/users');
        $this->assertDatabaseHas('users', ['username' => 'test_worker_updated']);

        $passwordResponse = $this->actingAs($admin)->patch("/users/{$newWorker->id}/password", [
            'password' => 'new-secure-123',
            'password_confirmation' => 'new-secure-123',
        ]);

        $passwordResponse->assertRedirect('/users');
        $this->assertTrue(Hash::check('new-secure-123', $newWorker->fresh()->password));
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'UPDATE',
            'table_name' => 'users',
            'record_id' => (string) $newWorker->id,
        ]);

        // 4. Toggle active status
        $toggleResponse = $this->actingAs($admin)->patch("/users/{$newWorker->id}/toggle");
        $toggleResponse->assertRedirect();
        
        $newWorker->refresh();
        $this->assertFalse($newWorker->is_active);

        // 5. Delete (Destroy)
        $deleteResponse = $this->actingAs($admin)->delete("/users/{$newWorker->id}");
        $deleteResponse->assertRedirect('/users');
        $this->assertDatabaseMissing('users', ['id' => $newWorker->id]);
    }

    public function test_user_creation_validation_feedback_is_visible(): void
    {
        $admin = User::where('username', 'admin')->first();
        $role = Role::where('name', 'pricing')->first();

        $response = $this->from('/users')
            ->actingAs($admin)
            ->post('/users', [
                'full_name' => 'Validation Worker',
                'username' => 'admin',
                'phone' => '+967700123123',
                'password' => '123',
                'role_id' => $role->id,
                'is_active' => '1',
            ]);

        $response->assertRedirect('/users');
        $response->assertSessionHasErrors(['username', 'password']);

        $this->followingRedirects()
            ->actingAs($admin)
            ->from('/users')
            ->post('/users', [
                'full_name' => 'Validation Worker',
                'username' => 'admin',
                'phone' => '+967700123123',
                'password' => '123',
                'role_id' => $role->id,
                'is_active' => '1',
            ])
            ->assertOk()
            ->assertSee('لم يتم حفظ بيانات المستخدم')
            ->assertSee('const shouldReopenUserModal = true', false)
            ->assertSee('Validation Worker');
    }

    /**
     * Test Roles Management.
     */
    public function test_admin_can_manage_roles(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Index
        $this->actingAs($admin)->get('/roles')->assertStatus(200);

        // 2. Store (Create)
        $response = $this->actingAs($admin)->post('/roles', [
            'name' => 'new_custom_role',
            'permissions' => ['manage_bookings', 'view_dashboard'],
        ]);
        $response->assertRedirect('/roles');
        $this->assertDatabaseHas('roles', ['name' => 'new_custom_role']);

        $role = Role::where('name', 'new_custom_role')->first();

        // 3. Update
        $updateResponse = $this->actingAs($admin)->put("/roles/{$role->id}", [
            'name' => 'updated_custom_role',
            'permissions' => ['view_dashboard'],
        ]);
        $updateResponse->assertRedirect('/roles');
        $this->assertDatabaseHas('roles', ['name' => 'updated_custom_role']);

        // 4. Destroy
        $deleteResponse = $this->actingAs($admin)->delete("/roles/{$role->id}");
        $deleteResponse->assertRedirect('/roles');
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_sidebar_navigation_updates_from_selected_role_permissions(): void
    {
        $role = Role::create([
            'name' => 'role_menu_controller',
            'permissions' => ['manage_roles', 'view_shipping_rates_operations'],
        ]);

        $user = User::create([
            'full_name' => 'مدير صلاحيات محدود',
            'username' => 'role_menu_controller',
            'password' => 'password',
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('roles.index'));

        $response->assertOk();
        $response->assertSee('href="' . route('roles.index') . '"', false);
        $response->assertSee('href="' . route('shipping-rates.operations') . '"', false);
        $response->assertDontSee('href="' . route('shipping-rates.index') . '"', false);
        $response->assertDontSee('href="' . route('dashboard') . '"', false);
        $response->assertDontSee('href="' . route('pricing.index') . '"', false);

        $role->update([
            'permissions' => ['manage_roles', 'manage_shipping_rates'],
        ]);

        $response = $this->actingAs($user->fresh())->get(route('roles.index'));

        $response->assertOk();
        $response->assertSee('href="' . route('roles.index') . '"', false);
        $response->assertSee('href="' . route('shipping-rates.index') . '"', false);
        $response->assertDontSee('href="' . route('shipping-rates.operations') . '"', false);
    }

    public function test_role_permissions_are_saved_as_known_unique_permissions(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->post('/roles', [
            'name' => 'unique_permissions_role',
            'permissions' => ['manage_roles', 'manage_roles', 'view_dashboard'],
        ]);

        $response->assertRedirect('/roles');

        $role = Role::where('name', 'unique_permissions_role')->firstOrFail();

        $this->assertSame(['manage_roles', 'view_dashboard'], $role->permissions);
    }

    /**
     * Test Audit Logs.
     */
    public function test_admin_can_view_audit_logs(): void
    {
        $admin = User::where('username', 'admin')->first();

        $response = $this->actingAs($admin)->get('/audit-logs');
        $response->assertStatus(200);
    }

    /**
     * Test System Insights.
     */
    public function test_admin_can_manage_insights(): void
    {
        $admin = User::where('username', 'admin')->first();

        // 1. Index
        $this->actingAs($admin)->get('/insights')->assertStatus(200);

        // 2. Generate Insights
        $generateResponse = $this->actingAs($admin)->post('/insights/generate');
        $generateResponse->assertRedirect();
        
        $this->assertNotEmpty(SystemInsight::all());

        $insight = SystemInsight::first();

        // 3. Mark Read
        $markReadResponse = $this->actingAs($admin)->post("/insights/{$insight->id}/mark-read");
        $markReadResponse->assertRedirect();
        $insight->refresh();
        $this->assertTrue($insight->is_read);

        // 4. Mark All Read
        $markAllReadResponse = $this->actingAs($admin)->post('/insights/mark-all-read');
        $markAllReadResponse->assertRedirect();

        // 5. Destroy
        $deleteResponse = $this->actingAs($admin)->delete("/insights/{$insight->id}");
        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('system_insights', ['id' => $insight->id]);
    }

    public function test_system_insights_are_deduplicated_and_critical_notifications_follow_permissions(): void
    {
        $admin = User::where('username', 'admin')->first();
        $salesUser = User::where('username', 'sales_user')->first();
        $pricingUser = User::where('username', 'pricing_user')->first();

        foreach (range(1, 6) as $index) {
            Booking::create([
                'id' => "BK-REJECTED-INSIGHT-{$index}",
                'customer_company' => 'Rejected Insight Customer',
                'customer_contact_name' => 'Rejected Contact',
                'customer_phone' => '+967700600600',
                'customer_email' => "rejected-insight-{$index}@example.com",
                'shipment_type' => 'FCL',
                'container_type' => '40HC',
                'container_qty' => 1,
                'goods_description' => 'Rejected insight goods',
                'weight_kg' => 1200,
                'volume_cbm' => 12,
                'pol' => 'Shanghai',
                'pod' => 'Aden',
                'status' => 'rejected',
            ]);
        }

        $this->actingAs($admin)->post('/insights/generate')->assertRedirect();

        $insight = SystemInsight::where('source_key', 'rejected_bookings')->first();

        $this->assertNotNull($insight);
        $this->assertSame('CRITICAL', $insight->level);
        $this->assertSame('sales', $insight->target);
        $this->assertSame('/sales', $insight->action_url);
        $this->assertNotNull($insight->notification_id);

        $notification = Notification::find($insight->notification_id);
        $this->assertNotNull($notification);
        $this->assertSame('sales', $notification->target);

        $this->actingAs($admin)->post('/insights/generate')->assertRedirect();

        $this->assertSame(1, SystemInsight::where('source_key', 'rejected_bookings')->count());
        $this->assertSame(1, Notification::where('target', 'sales')->count());

        $this->actingAs($salesUser)
            ->get('/sales')
            ->assertStatus(200)
            ->assertSee($notification->message);

        $this->actingAs($pricingUser)
            ->get('/pricing')
            ->assertStatus(200)
            ->assertDontSee($notification->message);
    }

    public function test_insights_console_command_generates_current_insights(): void
    {
        $this->artisan('insights:generate --silent')->assertExitCode(0);

        $this->assertDatabaseHas('system_insights', [
            'source_key' => 'system_normal',
            'target' => 'general',
            'action_url' => '/dashboard',
        ]);
    }

    public function test_dashboard_request_auto_generates_insights_without_cron(): void
    {
        $admin = User::where('username', 'admin')->first();
        cache()->forget('system_insights:auto_generate');

        SystemInsight::query()->delete();

        $this->actingAs($admin)->get('/dashboard')->assertStatus(200);

        $this->assertDatabaseHas('system_insights', [
            'source_key' => 'system_normal',
            'target' => 'general',
            'action_url' => '/dashboard',
        ]);
    }

    public function test_admin_can_export_and_import_system_backup(): void
    {
        $admin = User::where('username', 'admin')->first();

        $booking = Booking::create([
            'id' => 'BK-BACKUP-1',
            'customer_company' => 'Backup Customer',
            'customer_contact_name' => 'Backup Contact',
            'customer_phone' => '+967700900900',
            'customer_email' => 'backup@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Backup goods',
            'weight_kg' => 1500,
            'volume_cbm' => 15,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        $customer = Customer::create([
            'company' => 'Backup Portal Customer',
            'contact_name' => 'Portal Contact',
            'phone' => '+967700911911',
            'email' => 'portal-backup@example.com',
        ]);

        $notification = Notification::create([
            'id' => 'N-BACKUP-1',
            'message' => 'Customer portal notification backup test',
            'booking_id' => $booking->id,
            'target' => 'customer',
            'read' => false,
        ]);

        DB::table('customer_notification_reads')->insert([
            'notification_id' => $notification->id,
            'customer_id' => $customer->id,
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $port = Port::create([
            'name' => 'Backup Aden Port',
            'code' => 'ADN-BK',
            'country' => 'Yemen',
            'country_code' => 'YE',
            'country_flag' => '🇾🇪',
            'is_active' => true,
            'sort_order' => 50,
        ]);

        $portCountry = PortCountry::create([
            'name' => 'Backup Test Country',
            'name_ar' => 'دولة النسخ الاحتياطي',
            'code' => 'BT',
            'flag' => '🏁',
            'is_active' => true,
            'sort_order' => 55,
        ]);

        $airport = Airport::create([
            'iata_code' => 'BKP',
            'name' => 'Backup Test Airport',
            'city' => 'Backup City',
            'country' => 'Yemen',
            'country_code' => 'YE',
            'is_active' => true,
            'sort_order' => 60,
        ]);

        $shippingRate = ShippingRate::create([
            'shipping_type' => 'sea',
            'service_type' => 'FCL',
            'container_type' => '40HC',
            'origin_country' => 'China',
            'origin_port' => 'Shanghai',
            'destination_country' => 'Yemen',
            'destination_port' => 'Aden',
            'shipping_company' => 'Backup Carrier',
            'transit_time' => 21,
            'currency' => 'USD',
            'price' => 1450,
            'additional_fee' => 50,
            'valid_from' => '2026-08-01',
            'valid_to' => '2026-09-01',
            'notes' => 'Backup rate',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/backups')
            ->assertStatus(200)
            ->assertSee('النسخ الاحتياطي والاستعادة')
            ->assertSee('تنزيل نسخة احتياطية الآن');

        $export = $this->actingAs($admin)
            ->get('/backups/export')
            ->assertStatus(200)
            ->assertHeader('content-type', 'application/json; charset=UTF-8');

        $backupJson = $export->getContent();
        $this->assertStringContainsString('"type": "shipmate-data-backup"', $backupJson);
        $this->assertStringContainsString($booking->id, $backupJson);
        $this->assertStringContainsString('"ports"', $backupJson);
        $this->assertStringContainsString('"port_countries"', $backupJson);
        $this->assertStringContainsString('"airports"', $backupJson);
        $this->assertStringContainsString('"shipping_rates"', $backupJson);
        $this->assertStringContainsString('Backup Test Country', $backupJson);
        $this->assertStringContainsString('Backup Test Airport', $backupJson);
        $this->assertStringContainsString('Backup Carrier', $backupJson);

        $this->actingAs($admin)
            ->post('/backups/preview', [
                'import_mode' => 'merge',
                'backup_file' => UploadedFile::fake()->createWithContent('backup-preview.json', $backupJson),
            ])
            ->assertRedirect(route('backups.index'))
            ->assertSessionHas('backup_preview', function (array $preview) {
                $bookingPreview = collect($preview['tables'])->firstWhere('table', 'bookings');

                return $preview['mode'] === 'merge'
                    && $bookingPreview
                    && $bookingPreview['incoming'] >= 1
                    && $bookingPreview['action'] === 'merge';
            });

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'customer_company' => 'Backup Customer',
        ]);

        $booking->delete();
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        DB::table('customer_notification_reads')->where('notification_id', $notification->id)->delete();
        $this->assertDatabaseMissing('customer_notification_reads', ['notification_id' => $notification->id]);
        $port->delete();
        $portCountry->delete();
        $airport->delete();
        $shippingRate->delete();

        $this->actingAs($admin)
            ->post('/backups/import', [
                'import_mode' => 'merge',
                'backup_file' => UploadedFile::fake()->createWithContent('backup.json', $backupJson),
            ])
            ->assertRedirect(route('backups.index'));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'customer_company' => 'Backup Customer',
        ]);
        $this->assertDatabaseHas('customer_notification_reads', [
            'notification_id' => $notification->id,
            'customer_id' => $customer->id,
        ]);
        $this->assertDatabaseHas('ports', [
            'name' => 'Backup Aden Port',
            'code' => 'ADN-BK',
        ]);
        $this->assertDatabaseHas('port_countries', [
            'code' => 'BT',
            'name' => 'Backup Test Country',
        ]);
        $this->assertDatabaseHas('airports', [
            'iata_code' => 'BKP',
            'name' => 'Backup Test Airport',
        ]);
        $this->assertDatabaseHas('shipping_rates', [
            'id' => $shippingRate->id,
            'shipping_company' => 'Backup Carrier',
        ]);

        $this->actingAs($admin)
            ->post('/backups/import', [
                'import_mode' => 'replace',
                'backup_file' => UploadedFile::fake()->createWithContent('backup.json', $backupJson),
            ])
            ->assertSessionHasErrors('replace_confirmation');

        $protectedPort = Port::create([
            'name' => 'Protected Local Port',
            'code' => 'KEEP-PORT',
            'country' => 'Yemen',
            'country_code' => 'YE',
            'country_flag' => '🇾🇪',
            'is_active' => true,
            'sort_order' => 70,
        ]);

        $protectedAirport = Airport::create([
            'iata_code' => 'KPP',
            'name' => 'Protected Local Airport',
            'city' => 'Keep City',
            'country' => 'Yemen',
            'country_code' => 'YE',
            'is_active' => true,
            'sort_order' => 70,
        ]);

        $protectedPortCountry = PortCountry::create([
            'name' => 'Protected Local Country',
            'name_ar' => 'دولة محمية',
            'code' => 'PC',
            'flag' => '🏳',
            'is_active' => true,
            'sort_order' => 70,
        ]);

        $this->actingAs($admin)
            ->post('/backups/import', [
                'import_mode' => 'replace',
                'replace_confirmation' => 'استبدال البيانات',
                'preserve_foundation_data' => '1',
                'backup_file' => UploadedFile::fake()->createWithContent('backup.json', $backupJson),
            ])
            ->assertRedirect(route('backups.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'username' => 'admin',
        ]);
        $this->assertDatabaseHas('ports', [
            'id' => $protectedPort->id,
            'code' => 'KEEP-PORT',
        ]);
        $this->assertDatabaseHas('port_countries', [
            'id' => $protectedPortCountry->id,
            'code' => 'PC',
        ]);
        $this->assertDatabaseHas('airports', [
            'id' => $protectedAirport->id,
            'iata_code' => 'KPP',
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'customer_company' => 'Backup Customer',
        ]);
    }

    public function test_backup_import_upload_failure_shows_arabic_error_message(): void
    {
        $admin = User::where('username', 'admin')->first();
        $path = tempnam(sys_get_temp_dir(), 'backup-upload-failure-');
        file_put_contents($path, '{"meta":{"type":"shipmate-data-backup"},"tables":[]}');

        try {
            $failedUpload = new UploadedFile($path, 'large-backup.json', 'application/json', UPLOAD_ERR_INI_SIZE, true);

            $response = $this->actingAs($admin)
                ->post('/backups/import', [
                    'import_mode' => 'replace',
                    'replace_confirmation' => 'استبدال البيانات',
                    'backup_file' => $failedUpload,
                ])
                ->assertSessionHasErrors('backup_file');

            $error = $response->baseResponse->getSession()->get('errors')->first('backup_file');
            $this->assertStringContainsString('حد الرفع في PHP', $error);
            $this->assertStringContainsString('upload_max_filesize', $error);
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function test_backup_merge_updates_system_insights_by_source_key(): void
    {
        $admin = User::where('username', 'admin')->first();

        $existingInsight = SystemInsight::create([
            'source_key' => 'active_shipments',
            'title' => 'Old title',
            'content' => 'Old content',
            'level' => 'INFO',
            'target' => 'general',
            'is_read' => false,
        ]);

        $importedInsightId = '00000000-0000-4000-8000-000000000123';
        $backupJson = json_encode([
            'meta' => [
                'type' => 'shipmate-data-backup',
                'format_version' => 1,
            ],
            'tables' => [
                'system_insights' => [
                    [
                        'id' => $importedInsightId,
                        'source_key' => 'active_shipments',
                        'title' => 'Updated title',
                        'content' => 'Updated content from backup',
                        'level' => 'WARNING',
                        'target' => 'operations',
                        'action_url' => '/shipments',
                        'action_label' => 'Open shipments',
                        'notification_id' => null,
                        'last_seen_at' => now()->toDateTimeString(),
                        'is_read' => 0,
                        'created_at' => now()->toDateTimeString(),
                        'updated_at' => now()->toDateTimeString(),
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $this->actingAs($admin)
            ->post('/backups/import', [
                'import_mode' => 'merge',
                'backup_file' => UploadedFile::fake()->createWithContent('backup.json', $backupJson),
            ])
            ->assertRedirect(route('backups.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('system_insights', [
            'id' => $existingInsight->id,
            'source_key' => 'active_shipments',
            'title' => 'Updated title',
            'level' => 'WARNING',
            'target' => 'operations',
        ]);
        $this->assertDatabaseMissing('system_insights', [
            'id' => $importedInsightId,
        ]);
        $this->assertSame(1, SystemInsight::where('source_key', 'active_shipments')->count());
    }

    /**
     * Test role-based access for department desks.
     */
    public function test_department_access_permissions(): void
    {
        $pricingUser = User::where('username', 'pricing_user')->first();
        $salesUser = User::where('username', 'sales_user')->first();
        $accountsUser = User::where('username', 'accounts_user')->first();

        // 1. Pricing user accessing pricing desk (allowed) vs other desks (denied)
        $this->actingAs($pricingUser)->get('/pricing')->assertStatus(200);
        $this->actingAs($pricingUser)->get('/sales')->assertRedirect('/dashboard');
        $this->actingAs($pricingUser)->get('/bookings')->assertRedirect('/dashboard');
        $this->actingAs($pricingUser)->get('/accounts')->assertRedirect('/dashboard');

        // 2. Sales user accessing sales desk (allowed) vs other desks (denied)
        $this->actingAs($salesUser)->get('/sales')->assertStatus(200);
        $this->actingAs($salesUser)->get('/pricing')->assertRedirect('/dashboard');
        $this->actingAs($salesUser)->get('/bookings')->assertRedirect('/dashboard');
        $this->actingAs($salesUser)->get('/accounts')->assertRedirect('/dashboard');

        // 3. Accounts user accessing accounts desk (allowed) vs other desks (denied)
        $this->actingAs($accountsUser)->get('/accounts')->assertStatus(200);
        $this->actingAs($accountsUser)->get('/pricing')->assertRedirect('/dashboard');
        $this->actingAs($accountsUser)->get('/sales')->assertRedirect('/dashboard');
        $this->actingAs($accountsUser)->get('/bookings')->assertRedirect('/dashboard');
    }

    public function test_only_super_admin_can_edit_or_delete_customer_invoices_and_receipts(): void
    {
        $booking = Booking::create([
            'id' => 'BK-INVOICE-DELETE-1',
            'customer_company' => 'Delete Invoice Customer',
            'customer_contact_name' => 'Delete Contact',
            'customer_phone' => '+966500111222',
            'customer_email' => 'delete@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Invoice deletion test goods',
            'weight_kg' => 1200,
            'volume_cbm' => 12,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'booked',
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-DELETE-001',
            'booking_id' => $booking->id,
            'amount' => 1500,
            'status' => 'غير مدفوعة',
            'issued_at' => now()->subDay(),
            'due_at' => now()->addDays(10),
        ]);

        $deleteInvoice = Invoice::create([
            'id' => 'INV-DELETE-002',
            'booking_id' => $booking->id,
            'amount' => 700,
            'status' => 'غير مدفوعة',
            'issued_at' => now()->subDay(),
            'due_at' => now()->addDays(10),
        ]);

        $voucher = Voucher::create([
            'id' => 'REC-LOCK-001',
            'type' => 'receipt',
            'booking_id' => $booking->id,
            'invoice_id' => $invoice->id,
            'amount' => 300,
            'paid_before' => 0,
            'paid_after' => 300,
            'balance_after' => 1200,
            'payment_method' => 'Cash',
            'payee_payer' => 'Delete Invoice Customer',
            'description' => 'Receipt edit lock test',
            'date' => now()->toDateString(),
        ]);

        $agent = Agent::create([
            'name' => 'Locked Agent Payments',
            'phone' => '+967700000099',
            'email' => 'locked-agent@example.com',
            'type' => 'external',
            'region' => 'Asia',
            'country' => 'China',
        ]);

        Booking::create([
            'id' => 'BK-AGENT-PAY-LOCK',
            'customer_company' => 'Agent Lock Customer',
            'customer_contact_name' => 'Agent Lock Contact',
            'customer_phone' => '+967700000098',
            'customer_email' => 'agent-lock@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20GP',
            'container_qty' => 1,
            'goods_description' => 'Agent payment lock goods',
            'weight_kg' => 900,
            'volume_cbm' => 9,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'approved',
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 800,
            'agent_purchase_quantity' => 1,
        ]);

        $agentPaymentVoucher = Voucher::create([
            'id' => 'PAY-LOCK-001',
            'type' => 'payment',
            'booking_id' => 'BK-AGENT-PAY-LOCK',
            'invoice_id' => null,
            'agent_id' => $agent->id,
            'amount' => 250,
            'paid_before' => 0,
            'paid_after' => 250,
            'balance_after' => 550,
            'payment_method' => 'Bank Transfer',
            'payee_payer' => $agent->name,
            'description' => 'Agent payment edit lock test',
            'date' => now()->toDateString(),
        ]);

        $customsInvoice = Invoice::create([
            'id' => 'INV-CUSTOMS-LOCK-001',
            'booking_id' => $booking->id,
            'invoice_type' => 'customs',
            'amount' => 400,
            'status' => 'غير مدفوعة',
            'issued_at' => now()->subDay(),
            'due_at' => now()->addDays(10),
        ]);

        $superAdmin = User::where('username', 'admin')->first();
        $accountsUser = User::where('username', 'accounts_user')->first();

        $this->actingAs($accountsUser)
            ->get('/accounts')
            ->assertStatus(200)
            ->assertDontSee('edit-invoice-' . $invoice->id, false)
            ->assertDontSee('delete-invoice-' . $invoice->id, false)
            ->assertDontSee('edit-receipt-voucher-' . $voucher->id, false)
            ->assertDontSee('delete-receipt-voucher-' . $voucher->id, false)
            ->assertDontSee('edit-agent-voucher-latest-' . $agentPaymentVoucher->id, false)
            ->assertDontSee('edit-agent-voucher-' . $agentPaymentVoucher->id, false);

        $this->actingAs($accountsUser)
            ->post('/accounts/' . $invoice->id . '/status', [
                'status' => 'مدفوعة',
            ])
            ->assertForbidden();

        $this->assertSame('غير مدفوعة', $invoice->fresh()->status);

        $this->actingAs($accountsUser)
            ->put('/accounts/invoices/' . $invoice->id, [
                'amount' => 1600,
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(15)->toDateString(),
                'status' => 'مدفوعة جزئياً',
                'ocean_freight' => 1000,
                'origin_charges' => 200,
                'dthc' => 100,
                'war_risk_surcharge' => 50,
                'other_charges' => 250,
                'pol' => 'Ningbo',
                'pod' => 'Hodeidah',
                'custom_charge_desc' => null,
                'custom_charge_amount' => 0,
            ])
            ->assertForbidden();

        $this->assertSame(1500.0, (float) $invoice->fresh()->amount);
        $this->assertSame('Shanghai', $booking->fresh()->pol);

        $this->actingAs($accountsUser)
            ->put('/accounts/vouchers/' . $voucher->id, [
                'type' => 'receipt',
                'invoice_id' => $invoice->id,
                'amount' => 350,
                'payment_method' => 'Cash',
                'payee_payer' => 'Delete Invoice Customer',
                'description' => 'Unauthorized receipt update',
                'date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertSame(300.0, (float) $voucher->fresh()->amount);

        $this->actingAs($accountsUser)
            ->delete('/accounts/vouchers/' . $voucher->id)
            ->assertForbidden();

        $this->assertDatabaseHas('vouchers', ['id' => $voucher->id]);

        $this->actingAs($accountsUser)
            ->put('/accounts/vouchers/' . $agentPaymentVoucher->id, [
                'type' => 'payment',
                'agent_id' => $agent->id,
                'booking_id' => 'BK-AGENT-PAY-LOCK',
                'amount' => 300,
                'payment_method' => 'Bank Transfer',
                'payee_payer' => $agent->name,
                'description' => 'Unauthorized agent payment update',
                'date' => now()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertSame(250.0, (float) $agentPaymentVoucher->fresh()->amount);

        $this->actingAs($accountsUser)
            ->put('/accounts/invoices/' . $customsInvoice->id, [
                'amount' => 450,
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(15)->toDateString(),
                'status' => 'مدفوعة جزئياً',
                'ocean_freight' => 1000,
                'origin_charges' => 200,
                'dthc' => 100,
                'war_risk_surcharge' => 50,
                'other_charges' => 250,
                'pol' => 'Ningbo',
                'pod' => 'Hodeidah',
                'custom_charge_desc' => null,
                'custom_charge_amount' => 0,
            ])
            ->assertForbidden();

        $this->assertSame(400.0, (float) $customsInvoice->fresh()->amount);

        $this->actingAs($accountsUser)
            ->delete('/accounts/invoices/' . $customsInvoice->id)
            ->assertForbidden();

        $this->assertDatabaseHas('invoices', ['id' => $customsInvoice->id]);

        $this->actingAs($accountsUser)
            ->delete('/accounts/invoices/' . $deleteInvoice->id)
            ->assertForbidden();

        $this->assertDatabaseHas('invoices', ['id' => $deleteInvoice->id]);

        $this->actingAs($superAdmin)
            ->get('/accounts')
            ->assertStatus(200)
            ->assertSee('edit-invoice-' . $invoice->id, false)
            ->assertSee('delete-invoice-' . $invoice->id, false)
            ->assertSee('edit-receipt-voucher-' . $voucher->id, false)
            ->assertSee('delete-receipt-voucher-' . $voucher->id, false)
            ->assertSee('edit-agent-voucher-latest-' . $agentPaymentVoucher->id, false)
            ->assertSee('edit-agent-voucher-' . $agentPaymentVoucher->id, false);

        $this->actingAs($superAdmin)
            ->delete('/accounts/vouchers/' . $voucher->id)
            ->assertRedirect('/accounts');

        $this->assertDatabaseMissing('vouchers', ['id' => $voucher->id]);

        $this->actingAs($superAdmin)
            ->delete('/accounts/invoices/' . $deleteInvoice->id)
            ->assertRedirect('/accounts');

        $this->assertDatabaseMissing('invoices', ['id' => $deleteInvoice->id]);
    }
}

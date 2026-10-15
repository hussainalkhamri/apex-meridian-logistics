<?php

namespace Tests\Feature;

use App\Models\Airport;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Port;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BookingNoteAndAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_form_renders_country_filtered_port_selects(): void
    {
        Port::create([
            'name' => 'Shanghai',
            'code' => 'CNSHA',
            'country' => 'China',
            'country_code' => 'CN',
            'country_flag' => '🇨🇳',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Port::create([
            'name' => 'Aden',
            'code' => 'YEADE',
            'country' => 'Yemen',
            'country_code' => 'YE',
            'country_flag' => '🇾🇪',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Airport::create([
            'iata_code' => 'SAH',
            'name' => 'Sanaa International Airport',
            'city' => 'Sanaa',
            'country' => 'Yemen',
            'country_code' => 'YE',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Airport::create([
            'iata_code' => 'KWI',
            'name' => 'Kuwait International Airport',
            'city' => 'Kuwait City',
            'country' => 'Kuwait',
            'country_code' => 'KW',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $response = $this->withoutMiddleware()->get(route('booking.create'));

        $response->assertOk();
        $response->assertSee('data-country-select', false);
        $response->assertSee('data-custom-port-input', false);
        $response->assertSee('data-airport-target="origin_airport_air"', false);
        $response->assertSee('data-airport-filter-hint', false);
        $response->assertSee('<select name="origin_airport" id="origin_airport_air"', false);
        $response->assertSee('managedPorts', false);
        $response->assertSee('managedAirports', false);
        $response->assertSee('Shanghai');
        $response->assertSee('Aden');
        $response->assertSee('SAH');
        $response->assertSee('Kuwait');
        $response->assertSee('KWI');
    }

    public function test_booking_store_saves_notes_and_attachment_paths(): void
    {
        Storage::fake('public');

        $response = $this->withoutMiddleware()->post(route('booking.store'), [
            'company' => 'Test Company',
            'contactName' => 'Test Contact',
            'phone' => '123456',
            'email' => 'test@example.com',
            'goodsDescription' => 'Electronics',
            'weightKg' => '10',
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'shipmentType' => 'FCL',
            'notes' => 'Fragile cargo with special handling',
            'documents' => 'Invoice, Packing List',
            'attachments' => [
                UploadedFile::fake()->image('photo.jpg'),
                UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'customer_company' => 'Test Company',
            'notes' => 'Fragile cargo with special handling',
            'documents' => 'Invoice, Packing List',
        ]);

        $booking = Booking::latest()->first();
        $this->assertNotNull($booking->attachments);
        $this->assertIsArray(json_decode($booking->attachments, true));
        $this->assertCount(2, json_decode($booking->attachments, true));
    }

    public function test_dangerous_goods_booking_requires_supporting_file(): void
    {
        $response = $this->withSession([])
            ->from(route('booking.create'))
            ->withoutMiddleware()
            ->post(route('booking.store'), [
            'company' => 'DG Company',
            'contactName' => 'DG Contact',
            'phone' => '123456',
            'email' => 'dg@example.com',
            'goodsDescription' => 'Dangerous cargo',
            'weightKg' => '10',
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'shipmentType' => 'FCL',
            'cargo_type' => 'dangerous',
            'dangerous' => '1',
        ]);

        $response->assertSessionHasErrors('attachments');

        $this->assertDatabaseMissing('bookings', [
            'customer_company' => 'DG Company',
        ]);
    }

    public function test_booking_edit_form_shows_existing_attachments(): void
    {
        $booking = Booking::create([
            'id' => 'BK-FILES-EDIT',
            'customer_company' => 'Files Company',
            'customer_contact_name' => 'Files Contact',
            'customer_phone' => '123456',
            'customer_email' => 'files@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Files goods',
            'weight_kg' => 10,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'attachments' => json_encode([
                'bookings/attachments/invoice.pdf',
                'bookings/attachments/packing-list.jpg',
            ]),
        ]);

        $response = $this->withoutMiddleware()->get(route('orders.edit', $booking->id));

        $response->assertOk();
        $response->assertSee('invoice.pdf');
        $response->assertSee('packing-list.jpg');
        $response->assertSee(route('bookings.attachments.show', [
            'booking' => $booking,
            'path' => 'bookings/attachments/invoice.pdf',
        ]), false);
        $response->assertDontSee('/storage/bookings/attachments/invoice.pdf', false);
        $response->assertSee('name="remove_attachments[]"', false);
    }

    public function test_booking_attachment_download_route_serves_file_for_staff_and_customer(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bookings/attachments/customer-copy.pdf', 'file-body');

        $customer = Customer::create([
            'company' => 'Attachment Customer',
            'contact_name' => 'Attachment Contact',
            'phone' => '777000111',
            'email' => 'attachment-customer@example.com',
            'password' => 'secret',
            'portal_enabled' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-FILES-DOWNLOAD',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_phone' => $customer->phone,
            'customer_email' => $customer->email,
            'shipment_type' => 'FCL',
            'goods_description' => 'Files goods',
            'weight_kg' => 10,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'attachments' => json_encode(['bookings/attachments/customer-copy.pdf']),
        ]);

        $role = Role::create(['name' => 'attachment_staff', 'permissions' => ['manage_bookings']]);
        $staff = User::create([
            'role_id' => $role->id,
            'full_name' => 'Attachment Staff',
            'username' => 'attachment_staff',
            'phone' => '700000001',
            'password' => 'secret',
            'is_active' => true,
        ]);

        $staffUrl = route('orders.attachments.show', [
            'booking' => $booking,
            'path' => 'bookings/attachments/customer-copy.pdf',
        ]);
        $customerUrl = route('bookings.attachments.show', [
            'booking' => $booking,
            'path' => 'bookings/attachments/customer-copy.pdf',
        ]);

        $this->actingAs($staff)->get($staffUrl)->assertOk();
        $this->actingAs($customer, 'customer')->get($customerUrl)->assertOk();
    }

    public function test_booking_attachment_download_route_blocks_other_customers(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bookings/attachments/private.pdf', 'file-body');

        $owner = Customer::create([
            'company' => 'Owner Customer',
            'contact_name' => 'Owner Contact',
            'phone' => '777000222',
            'email' => 'owner@example.com',
            'password' => 'secret',
            'portal_enabled' => true,
        ]);
        $other = Customer::create([
            'company' => 'Other Customer',
            'contact_name' => 'Other Contact',
            'phone' => '777000333',
            'email' => 'other@example.com',
            'password' => 'secret',
            'portal_enabled' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-FILES-PRIVATE',
            'customer_id' => $owner->id,
            'customer_company' => $owner->company,
            'customer_contact_name' => $owner->contact_name,
            'customer_phone' => $owner->phone,
            'customer_email' => $owner->email,
            'shipment_type' => 'FCL',
            'goods_description' => 'Private files',
            'weight_kg' => 10,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'attachments' => json_encode(['bookings/attachments/private.pdf']),
        ]);

        $this->actingAs($other, 'customer')
            ->get(route('bookings.attachments.show', [
                'booking' => $booking,
                'path' => 'bookings/attachments/private.pdf',
            ]))
            ->assertForbidden();
    }

    public function test_booking_attachment_download_route_blocks_staff_without_operational_permission(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('bookings/attachments/staff-private.pdf', 'file-body');

        $booking = Booking::create([
            'id' => 'BK-FILES-STAFF-PERM',
            'customer_company' => 'Permission Customer',
            'customer_contact_name' => 'Permission Contact',
            'customer_phone' => '777000444',
            'customer_email' => 'permission@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Permission files',
            'weight_kg' => 10,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'attachments' => json_encode(['bookings/attachments/staff-private.pdf']),
        ]);

        $role = Role::create([
            'name' => 'attachment_viewer_only',
            'permissions' => ['view_dashboard'],
        ]);
        $staff = User::create([
            'role_id' => $role->id,
            'full_name' => 'Viewer Only',
            'username' => 'viewer-only',
            'phone' => '700000009',
            'password' => 'secret',
            'is_active' => true,
        ]);

        $this->actingAs($staff)
            ->get(route('orders.attachments.show', [
                'booking' => $booking,
                'path' => 'bookings/attachments/staff-private.pdf',
            ]))
            ->assertForbidden();
    }

    public function test_booking_update_can_add_and_remove_attachments(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('bookings/attachments/remove-me.pdf', 'remove');
        Storage::disk('public')->put('bookings/attachments/keep-me.pdf', 'keep');

        $booking = Booking::create([
            'id' => 'BK-FILES-UPD',
            'customer_company' => 'Files Company',
            'customer_contact_name' => 'Files Contact',
            'customer_phone' => '123456',
            'customer_email' => 'files-update@example.com',
            'shipment_type' => 'FCL',
            'goods_description' => 'Old goods',
            'weight_kg' => 10,
            'volume_cbm' => 1,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'attachments' => json_encode([
                'bookings/attachments/remove-me.pdf',
                'bookings/attachments/keep-me.pdf',
            ]),
        ]);

        $response = $this->withoutMiddleware()->put(route('orders.update', $booking->id), [
            'shipping_category' => 'sea',
            'route_mode' => 'FOB',
            'company' => 'Files Company Updated',
            'contactName' => 'Files Contact',
            'phone' => '123456',
            'email' => 'files-update@example.com',
            'origin_country' => 'United Arab Emirates',
            'destination_country' => 'Yemen',
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'shipmentType' => 'FCL',
            'containerType' => '40GP',
            'containerQty' => 1,
            'goodsDescription' => 'Updated goods',
            'weightKg' => 25,
            'volumeCbm' => 2,
            'documents' => 'Invoice',
            'remove_attachments' => ['bookings/attachments/remove-me.pdf'],
            'attachments' => [
                UploadedFile::fake()->create('new-file.pdf', 100, 'application/pdf'),
            ],
        ]);

        $response->assertRedirect(route('orders.index'));

        $booking->refresh();
        $attachments = json_decode($booking->attachments, true);

        $this->assertCount(2, $attachments);
        $this->assertNotContains('bookings/attachments/remove-me.pdf', $attachments);
        $this->assertContains('bookings/attachments/keep-me.pdf', $attachments);
        $this->assertTrue(collect($attachments)->contains(fn ($path) => str_starts_with($path, 'bookings/attachments/') && $path !== 'bookings/attachments/keep-me.pdf'));

        Storage::disk('public')->assertMissing('bookings/attachments/remove-me.pdf');
        Storage::disk('public')->assertExists('bookings/attachments/keep-me.pdf');
        Storage::disk('public')->assertExists(collect($attachments)->first(fn ($path) => $path !== 'bookings/attachments/keep-me.pdf'));
    }

    public function test_booking_store_saves_manual_ports_in_port_management(): void
    {
        $response = $this->withoutMiddleware()->post(route('booking.store'), [
            'company' => 'Port Company',
            'contactName' => 'Port Contact',
            'phone' => '123456',
            'email' => 'port@example.com',
            'goodsDescription' => 'Spare parts',
            'weightKg' => '5',
            'pol' => 'Manual Port One',
            'pod' => 'Manual Port Two',
            'shipmentType' => 'LCL',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('ports', [
            'name' => 'Manual Port One',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('ports', [
            'name' => 'Manual Port Two',
            'is_active' => true,
        ]);
    }

    public function test_booking_store_saves_exw_route_details(): void
    {
        $response = $this->withoutMiddleware()->post(route('booking.store'), [
            'company' => 'Route Company',
            'contactName' => 'Route Contact',
            'phone' => '987654',
            'email' => 'route@example.com',
            'goodsDescription' => 'Machinery',
            'weightKg' => '25',
            'volumeCbm' => '4.5',
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'shipmentType' => 'LCL',
            'route_mode' => 'EXW',
            'origin_country' => 'China',
            'origin_city' => 'Yiwu',
            'destination_country' => 'Yemen',
            'destination_city' => 'Sanaa',
        ]);

        $response->assertRedirect();

        $booking = Booking::latest()->first();
        $this->assertSame('Shanghai', $booking->pol);
        $this->assertSame('Aden', $booking->pod);
        $this->assertSame('EXW', $booking->route_details['mode']);
        $this->assertSame('China', $booking->route_details['origin']['country']);
        $this->assertSame('Yiwu', $booking->route_details['origin']['city']);
        $this->assertSame('Yemen', $booking->route_details['destination']['country']);
        $this->assertSame('Sanaa', $booking->route_details['destination']['city']);
    }

    public function test_booking_store_saves_fob_country_route_and_port_metadata(): void
    {
        $response = $this->withoutMiddleware()->post(route('booking.store'), [
            'company' => 'FOB Route Company',
            'contactName' => 'FOB Contact',
            'phone' => '555555',
            'email' => 'fob@example.com',
            'goodsDescription' => 'Food products',
            'weightKg' => '40',
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'shipmentType' => 'FCL',
            'route_mode' => 'FOB',
            'origin_country' => 'China',
            'destination_country' => 'Yemen',
        ]);

        $response->assertRedirect();

        $booking = Booking::latest()->first();
        $this->assertSame('FOB', $booking->route_details['mode']);
        $this->assertSame('China', $booking->route_details['origin']['country']);
        $this->assertSame('Shanghai', $booking->route_details['origin']['port']);
        $this->assertSame('Yemen', $booking->route_details['destination']['country']);
        $this->assertSame('Aden', $booking->route_details['destination']['port']);

        $this->assertDatabaseHas('ports', [
            'name' => 'Shanghai',
            'country' => 'China',
            'country_code' => 'CN',
        ]);
        $this->assertDatabaseHas('ports', [
            'name' => 'Aden',
            'country' => 'Yemen',
            'country_code' => 'YE',
        ]);
    }

    public function test_booking_store_saves_air_freight_as_pricing_order(): void
    {
        Storage::fake('public');

        $response = $this->withoutMiddleware()->post(route('booking.store'), [
            'shipping_category' => 'air',
            'route_mode' => 'AIR',
            'company' => 'Air Freight Company',
            'contactName' => 'Air Contact',
            'phone' => '333333',
            'email' => 'air@example.com',
            'goodsDescription' => 'Urgent samples',
            'gross_weight_kg' => '130',
            'origin_country' => 'YE',
            'origin_city' => 'Sanaa',
            'origin_airport' => 'SAH',
            'origin_airport_name' => 'Sanaa International Airport',
            'destination_country' => 'AE',
            'destination_city' => 'Dubai',
            'destination_airport' => 'DXB',
            'destination_airport_name' => 'Dubai International Airport',
            'shipmentType' => 'AIR',
            'air_service_level' => 'express',
            'ready_date' => '2026-08-15',
            'direct_flight_preferred' => '1',
            'insurance_required' => '1',
            'customs_clearance_destination' => '1',
            'incoterm' => 'EXW',
            'cargo_type' => 'dangerous',
            'hs_code' => '8517.62',
            'country_of_origin' => 'Yemen',
            'package_type' => 'carton',
            'stackable' => '1',
            'declared_value' => '5000',
            'commercial_value' => '5200',
            'currency' => 'USD',
            'dangerous_un_number' => 'UN3481',
            'dangerous_iata_class' => '9',
            'dangerous_packing_group' => 'II',
            'dangerous_proper_shipping_name' => 'Lithium ion batteries packed with equipment',
            'dangerous_msds_available' => '1',
            'attachments' => [
                UploadedFile::fake()->create('msds.pdf', 100, 'application/pdf'),
            ],
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
                    'weight_kg' => 10,
                ],
            ],
        ]);

        $response->assertRedirect(route('orders.index'));

        $booking = Booking::latest()->first();
        $this->assertSame('new', $booking->status);
        $this->assertSame('AIR', $booking->shipment_type);
        $this->assertSame('SAH', $booking->pol);
        $this->assertSame('DXB', $booking->pod);
        $this->assertTrue($booking->dangerous);
        $this->assertSame(130.0, $booking->weight_kg);
        $this->assertSame(1.3, $booking->volume_cbm);
        $this->assertSame('AIR', $booking->route_details['mode']);
        $this->assertSame('air', $booking->route_details['category']);
        $this->assertSame('airport', $booking->route_details['origin']['type']);
        $this->assertSame('SAH', $booking->route_details['origin']['airport']);
        $this->assertSame('Sanaa International Airport', $booking->route_details['origin']['airport_name']);
        $this->assertSame('Sanaa (SAH)', $booking->route_details['origin']['display_name']);
        $this->assertSame('YE', $booking->route_details['origin']['country_code']);
        $this->assertSame('DXB', $booking->route_details['destination']['airport']);
        $this->assertSame('Dubai International Airport', $booking->route_details['destination']['airport_name']);
        $this->assertSame('AE', $booking->route_details['destination']['country_code']);
        $this->assertSame('EXW', $booking->route_details['incoterm']);
        $this->assertSame('air_freight', $booking->cargo_details['service_type']);
        $this->assertSame('express', $booking->cargo_details['service']['service_level']);
        $this->assertSame('2026-08-15', $booking->cargo_details['service']['ready_date']);
        $this->assertTrue($booking->cargo_details['service']['direct_flight_preferred']);
        $this->assertTrue($booking->cargo_details['service']['insurance_required']);
        $this->assertSame(5, $booking->cargo_details['pieces_count']);
        $this->assertSame(1.3, $booking->cargo_details['volume_cbm']);
        $this->assertSame(216.67, $booking->cargo_details['volumetric_weight_kg']);
        $this->assertSame(216.67, $booking->cargo_details['chargeable_weight_kg']);
        $this->assertSame('8517.62', $booking->cargo_details['cargo']['hs_code']);
        $this->assertSame('Yemen', $booking->cargo_details['cargo']['country_of_origin']);
        $this->assertSame('carton', $booking->cargo_details['cargo']['package_type']);
        $this->assertTrue($booking->cargo_details['cargo']['stackable']);
        $this->assertEquals(5000.0, $booking->cargo_details['declared_value']);
        $this->assertSame('EXW', $booking->cargo_details['trade']['incoterm']);
        $this->assertEquals(5200.0, $booking->cargo_details['trade']['commercial_value']);
        $this->assertSame(6000, $booking->cargo_details['volumetric_divisor']);
        $this->assertTrue($booking->cargo_details['dangerous_goods']['is_dangerous']);
        $this->assertSame('IATA', $booking->cargo_details['dangerous_goods']['transport_regulation']);
        $this->assertSame('UN3481', $booking->cargo_details['dangerous_goods']['un_number']);
        $this->assertSame('9', $booking->cargo_details['dangerous_goods']['iata_class']);
        $this->assertTrue($booking->cargo_details['dangerous_goods']['msds_available']);
        $this->assertCount(1, json_decode($booking->attachments, true));

        $this->assertDatabaseMissing('ports', [
            'name' => 'SAH',
        ]);
        $this->assertDatabaseHas('airports', [
            'iata_code' => 'SAH',
            'country' => 'Yemen',
            'country_code' => 'YE',
        ]);
        $this->assertDatabaseHas('airports', [
            'iata_code' => 'DXB',
            'country' => 'United Arab Emirates',
            'country_code' => 'AE',
        ]);
    }

    public function test_booking_store_saves_door_to_door_request_without_creating_ports(): void
    {
        $response = $this->withoutMiddleware()->post(route('booking.store'), [
            'shipping_category' => 'door_to_door',
            'route_mode' => 'DOOR_TO_DOOR',
            'company' => 'Door Service Company',
            'contactName' => 'Door Contact',
            'phone' => '444444',
            'email' => 'door@example.com',
            'goodsDescription' => 'Home appliances',
            'origin_country' => 'China',
            'origin_city' => 'Yiwu',
            'origin_postal_code' => '322000',
            'pickup_address' => 'Factory Zone 2, Yiwu',
            'pickup_contact_name' => 'Factory Contact',
            'pickup_phone_country_code' => '+86',
            'pickup_phone' => '+8613812345678',
            'destination_country' => 'Yemen',
            'destination_city' => 'Aden',
            'destination_postal_code' => '124',
            'delivery_address' => 'Customer warehouse, Aden',
            'delivery_contact_name' => 'Warehouse Contact',
            'delivery_phone_country_code' => '+967',
            'delivery_phone' => '+967700000000',
            'shipmentType' => 'DOOR_TO_DOOR',
            'main_transport_mode' => 'SEA',
            'incoterm' => 'DAP',
            'cargo_type' => 'fragile',
            'hs_code' => '8509.40',
            'country_of_origin' => 'China',
            'declared_value' => '3000',
            'commercial_value' => '3200',
            'currency' => 'USD',
            'packages' => [
                [
                    'quantity' => 5,
                    'package_type' => 'carton',
                    'length_cm' => 100,
                    'width_cm' => 80,
                    'height_cm' => 60,
                    'weight_kg' => 24,
                ],
            ],
            'pickup_ready_date' => '2026-08-20',
            'preferred_pickup_time' => 'morning',
            'pickup_time_from' => '09:00',
            'pickup_time_to' => '12:00',
            'delivery_required_date' => '2026-09-10',
            'insurance_required' => '1',
            'export_customs_clearance' => '1',
            'delivery_notes' => 'Call before final delivery',
        ]);

        $response->assertRedirect(route('orders.index'));

        $booking = Booking::latest()->first();
        $this->assertSame('new', $booking->status);
        $this->assertSame('DOOR_TO_DOOR', $booking->shipment_type);
        $this->assertSame('Factory Zone 2, Yiwu', $booking->pol);
        $this->assertSame('Customer warehouse, Aden', $booking->pod);
        $this->assertSame(120.0, $booking->weight_kg);
        $this->assertSame(2.4, $booking->volume_cbm);
        $this->assertSame('DOOR_TO_DOOR', $booking->route_details['mode']);
        $this->assertSame('door_to_door', $booking->route_details['category']);
        $this->assertSame('SEA', $booking->route_details['main_transport_mode']);
        $this->assertSame('DAP', $booking->route_details['incoterm']);
        $this->assertSame('address', $booking->route_details['origin']['type']);
        $this->assertSame('Factory Zone 2, Yiwu', $booking->route_details['origin']['address']);
        $this->assertSame('CN', $booking->route_details['origin']['country_code']);
        $this->assertSame('322000', $booking->route_details['origin']['postal_code']);
        $this->assertSame('Factory Contact', $booking->route_details['origin']['contact_name']);
        $this->assertSame('+86', $booking->route_details['origin']['phone_country_code']);
        $this->assertSame('Customer warehouse, Aden', $booking->route_details['destination']['address']);
        $this->assertSame('YE', $booking->route_details['destination']['country_code']);
        $this->assertSame('Warehouse Contact', $booking->route_details['destination']['contact_name']);
        $this->assertSame('door_to_door', $booking->cargo_details['service_type']);
        $this->assertSame('fragile', $booking->cargo_details['cargo_type']);
        $this->assertSame('8509.40', $booking->cargo_details['cargo']['hs_code']);
        $this->assertSame('China', $booking->cargo_details['cargo']['country_of_origin']);
        $this->assertSame(5, $booking->cargo_details['packages_count']);
        $this->assertEquals(120.0, $booking->cargo_details['gross_weight_kg']);
        $this->assertSame(2.4, $booking->cargo_details['volume_cbm']);
        $this->assertSame('morning', $booking->cargo_details['service']['preferred_pickup_time']);
        $this->assertSame('09:00', $booking->cargo_details['service']['pickup_time_from']);
        $this->assertSame('2026-09-10', $booking->cargo_details['service']['delivery_required_date']);
        $this->assertTrue($booking->cargo_details['service']['insurance_required']);
        $this->assertTrue($booking->cargo_details['service']['export_customs_clearance']);
        $this->assertSame('DAP', $booking->cargo_details['trade']['incoterm']);
        $this->assertEquals(3200.0, $booking->cargo_details['trade']['commercial_value']);
        $this->assertFalse($booking->cargo_details['dangerous_goods']['is_dangerous']);
        $this->assertSame('IMDG', $booking->cargo_details['dangerous_goods']['transport_regulation']);

        $this->assertDatabaseMissing('ports', [
            'name' => 'Factory Zone 2, Yiwu',
        ]);
    }

    public function test_booking_store_saves_bulk_details_when_selected(): void
    {
        $response = $this->withoutMiddleware()->post(route('booking.store'), [
            'company' => 'Bulk Company',
            'contactName' => 'Bulk Contact',
            'phone' => '111111',
            'email' => 'bulk@example.com',
            'goodsDescription' => 'Steel coils',
            'weightKg' => '1200',
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'shipmentType' => 'Bulk',
            'bulk_cargo_name' => 'Steel Coil',
            'bulk_pieces' => '10',
            'bulk_piece_weight' => '120',
            'bulk_total_weight' => '1200',
            'bulk_length' => '6',
            'bulk_width' => '2',
            'bulk_height' => '2',
            'bulk_cbm' => '24',
            'bulk_packaging' => 'Bundle',
            'bulk_lifting' => 'Crane',
            'bulk_stackable' => '1',
        ]);

        $response->assertRedirect();

        $booking = Booking::latest()->first();
        $this->assertSame('Bulk', $booking->shipment_type);
        $this->assertSame('Steel Coil', $booking->cargo_details['cargo_name']);
        $this->assertSame('Bundle', $booking->cargo_details['packaging_type']);
        $this->assertSame('Crane', $booking->cargo_details['lifting_method']);
        $this->assertSame(true, $booking->cargo_details['stackable']);
    }

    public function test_booking_store_saves_roro_details_when_selected(): void
    {
        $response = $this->withoutMiddleware()->post(route('booking.store'), [
            'company' => 'RoRo Company',
            'contactName' => 'RoRo Contact',
            'phone' => '222222',
            'email' => 'roro@example.com',
            'goodsDescription' => 'Vehicle',
            'weightKg' => '1800',
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'shipmentType' => 'RoRo',
            'roro_vehicle_type' => 'Car',
            'roro_manufacturer' => 'Toyota',
            'roro_model' => 'Corolla',
            'roro_year' => '2020',
            'roro_color' => 'White',
            'roro_vin' => 'VIN12345',
            'roro_chassis_number' => 'CHS12345',
            'roro_engine_number' => 'ENG12345',
            'roro_plate_number' => 'PLATE123',
            'roro_length' => '4.5',
            'roro_width' => '1.8',
            'roro_height' => '1.5',
            'roro_weight' => '1500',
            'roro_running' => '1',
            'roro_tow_required' => '0',
            'roro_fuel_level' => 'Half',
            'roro_has_key' => '1',
            'roro_has_items' => '0',
            'roro_value' => '12000',
        ]);

        $response->assertRedirect();

        $booking = Booking::latest()->first();
        $this->assertSame('RoRo', $booking->shipment_type);
        $this->assertSame('Car', $booking->cargo_details['vehicle_type']);
        $this->assertSame('Toyota', $booking->cargo_details['manufacturer']);
        $this->assertSame('VIN12345', $booking->cargo_details['vin']);
        $this->assertSame('Half', $booking->cargo_details['fuel_level']);
        $this->assertSame(true, $booking->cargo_details['has_key']);
    }

    public function test_booking_details_page_shows_cargo_details_for_bulk_and_roro(): void
    {
        $booking = Booking::create([
            'id' => 'BK-DETAILS-1',
            'customer_company' => 'Details Company',
            'customer_contact_name' => 'Details Contact',
            'customer_phone' => '123456',
            'customer_email' => 'details@example.com',
            'shipment_type' => 'Bulk',
            'goods_description' => 'Steel coils',
            'weight_kg' => 1200,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'cargo_details' => [
                'cargo_name' => 'Steel Coil',
                'pieces' => '10',
                'piece_weight' => '120',
                'total_weight' => '1200',
                'packaging_type' => 'Bundle',
                'lifting_method' => 'Crane',
                'stackable' => true,
            ],
        ]);

        $response = $this->withoutMiddleware()->get(route('orders.show', $booking->id));

        $response->assertOk();
        $response->assertSee('Steel Coil');
        $response->assertSee('Bundle');
        $response->assertSee('Crane');
        $response->assertSee('1200');
    }

    public function test_booking_json_includes_cargo_details_for_modal_rendering(): void
    {
        $booking = Booking::create([
            'id' => 'BK-DETAILS-2',
            'customer_company' => 'Modal Company',
            'customer_contact_name' => 'Modal Contact',
            'customer_phone' => '123456',
            'customer_email' => 'modal@example.com',
            'shipment_type' => 'RoRo',
            'goods_description' => 'Vehicle',
            'weight_kg' => 1800,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'cargo_details' => [
                'vehicle_type' => 'Car',
                'manufacturer' => 'Toyota',
                'vin' => 'VIN12345',
                'fuel_level' => 'Half',
                'has_key' => true,
            ],
        ]);

        $response = $this->withHeader('Accept', 'application/json')->withoutMiddleware()->get(route('orders.show', $booking->id));

        $response->assertOk();
        $response->assertJsonPath('cargo.cargoDetails.vehicle_type', 'Car');
        $response->assertJsonPath('cargo.cargoDetails.manufacturer', 'Toyota');
        $response->assertJsonPath('cargo.cargoDetails.fuel_level', 'Half');
    }

    public function test_edit_form_prefills_cargo_details_for_bulk_bookings(): void
    {
        $booking = Booking::create([
            'id' => 'BK-DETAILS-3',
            'customer_company' => 'Edit Company',
            'customer_contact_name' => 'Edit Contact',
            'customer_phone' => '123456',
            'customer_email' => 'edit@example.com',
            'shipment_type' => 'Bulk',
            'goods_description' => 'Steel coils',
            'weight_kg' => 1200,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'cargo_details' => [
                'cargo_name' => 'Steel Coil',
                'packaging_type' => 'Bundle',
                'lifting_method' => 'Crane',
                'stackable' => true,
            ],
        ]);

        $response = $this->withoutMiddleware()->get(route('orders.edit', $booking->id));

        $response->assertOk();
        $response->assertSee('value="Steel Coil"', false);
        $response->assertSee('value="Bundle"', false);
        $response->assertSee('value="Crane"', false);
    }
}

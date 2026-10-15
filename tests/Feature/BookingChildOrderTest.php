<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Agent;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Role;
use App\Models\ShipmentTrack;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingChildOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_opens_the_booking_form_with_parent_details_when_adding_a_child_order(): void
    {
        $parent = Booking::create([
            'id' => 'BK-TEST-1',
            'customer_company' => 'Acme Logistics',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'ahmed@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 2,
            'goods_description' => 'Electronics',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Cairo',
            'pod' => 'Jeddah',
            'status' => 'new',
        ]);

        $this->withoutMiddleware();
        $response = $this->get(route('booking.create', ['parent_id' => $parent->id]));

        $response->assertOk();
        $response->assertViewHas('parentBooking', function ($booking) use ($parent) {
            return $booking->id === $parent->id;
        });
    }

    public function test_it_can_open_the_edit_form_for_a_booking_without_parent_context(): void
    {
        $booking = Booking::create([
            'id' => 'BK-TEST-2',
            'customer_company' => 'Acme Logistics',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'ahmed@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 2,
            'goods_description' => 'Electronics',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Cairo',
            'pod' => 'Jeddah',
            'status' => 'new',
        ]);

        $this->withoutMiddleware();
        $response = $this->get(route('orders.edit', $booking->id));

        $response->assertOk();
        $response->assertViewHas('booking', function ($viewBooking) use ($booking) {
            return $viewBooking->id === $booking->id;
        });
    }

    public function test_it_shows_an_edit_link_for_child_orders_in_the_orders_page(): void
    {
        $parent = Booking::create([
            'id' => 'BK-TEST-3',
            'customer_company' => 'Acme Logistics',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'ahmed@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 2,
            'goods_description' => 'Electronics',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Cairo',
            'pod' => 'Jeddah',
            'status' => 'new',
        ]);

        $child = Booking::create([
            'id' => 'BK-TEST-4',
            'parent_booking_id' => $parent->id,
            'customer_company' => 'Acme Logistics',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'ahmed@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 2,
            'goods_description' => 'Electronics',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Cairo',
            'pod' => 'Jeddah',
            'status' => 'new',
        ]);

        $this->withoutMiddleware();
        $response = $this->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee(route('orders.edit', $child->id));
    }

    public function test_it_uses_the_detail_modal_for_child_orders_in_the_orders_page(): void
    {
        $parent = Booking::create([
            'id' => 'BK-TEST-5',
            'customer_company' => 'Acme Logistics',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'ahmed@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 2,
            'goods_description' => 'Electronics',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Cairo',
            'pod' => 'Jeddah',
            'status' => 'new',
        ]);

        $child = Booking::create([
            'id' => 'BK-TEST-6',
            'parent_booking_id' => $parent->id,
            'customer_company' => 'Acme Logistics',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'ahmed@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 2,
            'goods_description' => 'Electronics',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Cairo',
            'pod' => 'Jeddah',
            'status' => 'new',
        ]);

        $this->withoutMiddleware();
        $response = $this->get(route('orders.index'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, "openDetailModal('{$child->id}')")
                || str_contains($content, "openDetailModal(&#039;{$child->id}&#039;)"),
            'Child order should open the shared detail modal.'
        );
    }

    public function test_orders_page_flags_only_the_child_order_when_child_needs_repricing(): void
    {
        $parent = Booking::create([
            'id' => 'BK-REPRICE-PARENT',
            'customer_company' => 'Acme Logistics',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'ahmed-parent@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 2,
            'goods_description' => 'Electronics',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'route_details' => ['mode' => 'FOB'],
            'status' => 'priced',
        ]);

        $child = Booking::create([
            'id' => 'BK-REPRICE-CHILD',
            'parent_booking_id' => $parent->id,
            'customer_company' => 'Acme Logistics',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'ahmed-child@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 1,
            'goods_description' => 'Electronics',
            'weight_kg' => 600,
            'volume_cbm' => 15,
            'pol' => 'Ningbo',
            'pod' => 'Aden',
            'status' => 'needs_pricing',
        ]);

        $this->withoutMiddleware();
        $response = $this->get(route('orders.index'));

        $response->assertOk();
        $response->assertSee('FOB');
        $response->assertDontSee('data-repricing-badge-for="' . $parent->id . '"', false);
        $response->assertSee('data-repricing-badge-for="' . $child->id . '"', false);
    }

    public function test_it_can_archive_and_restore_an_order_from_the_orders_list(): void
    {
        $adminRole = Role::create([
            'name' => 'super_admin',
            'permissions' => ['manage_bookings'],
        ]);
        $admin = User::create([
            'full_name' => 'Test Admin Archive',
            'username' => 'test_admin_archive',
            'phone' => '+967700000001',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-ARCHIVE-1',
            'customer_company' => 'Archive Customer',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'archive@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Archive test',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        $this->withoutMiddleware();
        $response = $this->actingAs($admin)->post(route('orders.archive', $booking->id));
        $response->assertRedirect(route('orders.index'));
        $booking->refresh();
        $this->assertTrue((bool) $booking->is_archived);

        $list = $this->actingAs($admin)->get(route('orders.index'));
        $list->assertOk();
        $list->assertDontSee('Archive Customer');

        $restore = $this->actingAs($admin)->post(route('orders.restore', $booking->id));
        $restore->assertRedirect(route('orders.index'));
        $booking->refresh();
        $this->assertFalse((bool) $booking->is_archived);

        $restoredList = $this->actingAs($admin)->get(route('orders.index'));
        $restoredList->assertOk();
        $restoredList->assertSee('Archive Customer');
    }

    public function test_it_can_delete_a_completed_parent_order_with_related_trial_records(): void
    {
        $adminRole = Role::create([
            'name' => 'super_admin',
            'permissions' => ['manage_bookings'],
        ]);
        $admin = User::create([
            'full_name' => 'Test Admin',
            'username' => 'test_admin_delete',
            'phone' => '+967700000000',
            'password' => 'password',
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $parent = Booking::create([
            'id' => 'BK-TEST-DELETE-1',
            'customer_company' => 'Trial Customer',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'trial@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Trial goods',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'booked',
        ]);

        $child = Booking::create([
            'id' => 'BK-TEST-DELETE-2',
            'parent_booking_id' => $parent->id,
            'customer_company' => 'Trial Customer',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'trial@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 1,
            'goods_description' => 'Trial child goods',
            'weight_kg' => 800,
            'volume_cbm' => 20,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'booked',
        ]);

        $invoice = Invoice::create([
            'id' => 'INV-TEST-DELETE-1',
            'booking_id' => $parent->id,
            'amount' => 1500,
            'currency' => 'USD',
            'status' => 'unpaid',
            'issued_at' => now(),
            'due_at' => now()->addWeek(),
        ]);

        $childInvoice = Invoice::create([
            'id' => 'INV-TEST-DELETE-2',
            'booking_id' => $child->id,
            'amount' => 700,
            'currency' => 'USD',
            'status' => 'unpaid',
            'issued_at' => now(),
            'due_at' => now()->addWeek(),
        ]);

        Voucher::create([
            'id' => 'REC-TEST-DELETE-1',
            'type' => 'receipt',
            'booking_id' => $parent->id,
            'invoice_id' => $invoice->id,
            'amount' => 200,
            'payment_method' => 'cash',
            'payee_payer' => 'Trial Customer',
            'description' => 'Trial receipt',
            'date' => now()->toDateString(),
        ]);

        Voucher::create([
            'id' => 'REC-TEST-DELETE-2',
            'type' => 'receipt',
            'booking_id' => $child->id,
            'invoice_id' => $childInvoice->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'payee_payer' => 'Trial Customer',
            'description' => 'Trial child receipt',
            'date' => now()->toDateString(),
        ]);

        ShipmentTrack::create([
            'id' => 'TRK-TEST-DELETE-1',
            'booking_id' => $parent->id,
            'status' => 'delivered',
            'progress' => 100,
            'etd' => now()->toDateString(),
            'eta' => now()->addWeek()->toDateString(),
        ]);

        Notification::create([
            'id' => 'N-TEST-DELETE-1',
            'booking_id' => $parent->id,
            'message' => 'Trial booking completed',
            'target' => 'tracking',
        ]);

        $this->withoutMiddleware();
        $response = $this->actingAs($admin)->delete(route('orders.destroy', $parent->id));

        $response->assertRedirect(route('orders.index'));
        $this->assertDatabaseMissing('bookings', ['id' => $parent->id]);
        $this->assertDatabaseMissing('bookings', ['id' => $child->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseMissing('invoices', ['id' => $childInvoice->id]);
        $this->assertDatabaseMissing('vouchers', ['id' => 'REC-TEST-DELETE-1']);
        $this->assertDatabaseMissing('vouchers', ['id' => 'REC-TEST-DELETE-2']);
        $this->assertDatabaseMissing('shipment_tracks', ['id' => 'TRK-TEST-DELETE-1']);
        $this->assertDatabaseMissing('notifications', ['id' => 'N-TEST-DELETE-1']);
        $this->assertDatabaseHas('audit_logs', [
            'action_type' => 'DELETE',
            'table_name' => 'bookings',
            'record_id' => $parent->id,
        ]);
    }

    public function test_non_super_admin_cannot_delete_orders_even_with_manage_bookings_permission(): void
    {
        $staffRole = Role::create([
            'name' => 'ops_manager',
            'permissions' => ['manage_bookings'],
        ]);
        $staff = User::create([
            'full_name' => 'Test Operations Manager',
            'username' => 'test_ops_no_delete',
            'phone' => '+967700000000',
            'password' => 'password',
            'role_id' => $staffRole->id,
            'is_active' => true,
        ]);

        $booking = Booking::create([
            'id' => 'BK-TEST-NO-DELETE-1',
            'customer_company' => 'Protected Customer',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'protected@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Protected goods',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'booked',
        ]);

        $this->withoutMiddleware();
        $response = $this->actingAs($staff)->delete(route('orders.destroy', $booking->id));

        $response->assertRedirect(route('orders.index'));
        $response->assertSessionHas('error', __('messages.orders_delete_forbidden'));
        $this->assertDatabaseHas('bookings', ['id' => $booking->id]);
        $this->assertDatabaseMissing('audit_logs', [
            'action_type' => 'DELETE',
            'table_name' => 'bookings',
            'record_id' => $booking->id,
        ]);
    }

    public function test_it_accepts_multiple_land_shipments_for_the_same_parent_quote_request(): void
    {
        $this->withoutMiddleware();

        $response = $this->post(route('booking.store'), [
            'shipping_category' => 'land',
            'route_mode' => 'LAND',
            'company' => 'Land Hub Co',
            'contactName' => 'Saleh',
            'phone' => '771234567',
            'phone_country_code' => '+967',
            'email' => 'land@example.com',
            'goodsDescription' => 'Machinery',
            'weightKg' => 2500,
            'volumeCbm' => 12,
            'land_carton_count' => 44,
            'shipmentType' => 'LAND',
            'pol' => 'Sanaa',
            'pod' => 'Aden',
            'land_shipments' => [
                ['qty' => 2, 'from_city' => 'Sanaa', 'to_city' => 'Aden'],
                ['qty' => 1, 'from_city' => 'Taiz', 'to_city' => 'Aden'],
            ],
        ]);

        $response->assertRedirect(route('orders.index'));
        $booking = Booking::query()->where('customer_email', 'land@example.com')->firstOrFail();
        $this->assertSame('LAND', $booking->shipment_type);
        $this->assertSame('Sanaa', $booking->pol);
        $this->assertSame('Aden', $booking->pod);
        $this->assertCount(2, $booking->cargo_details['land_shipments'] ?? []);
        $this->assertSame(3, collect($booking->cargo_details['land_shipments'])->sum('qty'));
        $this->assertSame(44, $booking->cargo_details['land_carton_count'] ?? null);
        $this->assertStringContainsString('2xSanaa → Aden', $booking->shipmentDisplay());
    }

    public function test_land_booking_pricing_uses_route_lines_instead_of_sea_charges(): void
    {
        $this->withoutMiddleware();

        $agent = Agent::create([
            'name' => 'Local Land Carrier',
            'type' => 'local',
            'country' => 'Yemen',
        ]);

        $booking = Booking::create([
            'id' => 'BK-LAND-PRICE-1',
            'customer_company' => 'Land Hub Co',
            'customer_contact_name' => 'Saleh',
            'customer_phone' => '771234567',
            'customer_email' => 'land-price@example.com',
            'shipment_type' => 'LAND',
            'goods_description' => 'Machinery pallets',
            'weight_kg' => 2500,
            'volume_cbm' => 12,
            'pol' => 'Sanaa',
            'pod' => 'Aden',
            'status' => 'new',
            'route_details' => [
                'category' => 'land',
                'mode' => 'LAND',
            ],
            'cargo_details' => [
                'service_type' => 'land',
                'land_shipments' => [
                    ['qty' => 2, 'from_city' => 'Sanaa', 'to_city' => 'Aden'],
                    ['qty' => 1, 'from_city' => 'Taiz', 'to_city' => 'Aden'],
                ],
            ],
        ]);

        $response = $this->post(route('pricing.store', $booking->id), [
            'transitDays' => 2,
            'validityDate' => '2026-08-20',
            'agent_id' => $agent->id,
            'agentPurchaseUnitPrice' => 150,
            'agentPurchaseQuantity' => 2.5,
            'landPricingLines' => [
                ['qty' => 1.5, 'truck_qty' => 2, 'from_city' => 'Sanaa', 'to_city' => 'Aden', 'unit_price' => 300, 'plate_number' => 'TRK-101'],
                ['qty' => 1, 'truck_qty' => 1, 'from_city' => 'Taiz', 'to_city' => 'Aden', 'unit_price' => 350],
            ],
        ]);

        $response->assertRedirect(route('pricing.index'));

        $booking->refresh();

        $this->assertSame('priced', $booking->status);
        $this->assertEquals(800, $booking->pricingTotal());
        $this->assertEquals(800, $booking->ocean_freight);
        $this->assertEquals(0, $booking->origin_charges);
        $this->assertEquals(0, $booking->dthc);
        $this->assertSame('USD', $booking->agent_purchase_currency);
        $this->assertSame('USD', $booking->cargo_details['pricing_currency'] ?? null);
        $this->assertCount(2, $booking->cargo_details['land_pricing_lines'] ?? []);
        $this->assertSame('ton', $booking->cargo_details['land_pricing_basis'] ?? null);
        $this->assertEquals(2.5, $booking->landPricingQuantity());
        $this->assertEquals(2, $booking->cargo_details['land_pricing_lines'][0]['truck_qty'] ?? null);

        $quoteViewerRole = Role::create([
            'name' => 'quote_viewer',
            'permissions' => ['manage_pricing'],
        ]);
        $quoteViewer = User::create([
            'role_id' => $quoteViewerRole->id,
            'full_name' => 'Quote Viewer',
            'username' => 'quote-viewer',
            'phone' => '+967700123450',
            'password' => 'password',
            'is_active' => true,
        ]);

        $quote = $this->actingAs($quoteViewer)->get(route('quote.show', $booking->id));

        $quote->assertOk();
        $quote->assertSee('نقل بري: Sanaa');
        $quote->assertSee('TRK-101');
        $quote->assertSee('$ 300.00');
        $quote->assertSee('سعر الطن الواحد');
        $quote->assertSee('الوزن بالطن');
        $quote->assertSee('تفصيل التكاليف والرسوم المعتمدة');
        $quote->assertSee('بالدولار الأمريكي');
        $quote->assertSee('عدد الطبليات');
        $quote->assertSee('12');
        $quote->assertDontSee('عدد الطرود');
        $quote->assertSee('2.5');
        $quote->assertDontSee('عدد الطبليات/القواطر');
        $quote->assertSee('ولا تشمل تخاليص الصادر وخرج الطريق');
        $quote->assertSee('20-08-2026');
        $quote->assertSee('مكتب النقل المحلي في اليمن');
        $quote->assertDontSee('Ocean Freight');
        $quote->assertDontSee('التأمين البحري');
        $quote->assertDontSee('Bill of Lading Terms');
    }

    public function test_it_returns_a_rendered_details_page_for_html_requests(): void
    {
        $booking = Booking::create([
            'id' => 'BK-TEST-7',
            'customer_company' => 'Acme Logistics',
            'customer_contact_name' => 'Ahmed',
            'customer_phone' => '771234567',
            'customer_email' => 'ahmed@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20DC',
            'container_qty' => 2,
            'goods_description' => 'Electronics',
            'weight_kg' => 1200,
            'volume_cbm' => 30,
            'pol' => 'Cairo',
            'pod' => 'Jeddah',
            'status' => 'new',
        ]);

        $this->withoutMiddleware();
        $response = $this->withHeaders(['Accept' => 'text/html'])->get(route('orders.show', $booking->id));

        $response->assertOk();
        $response->assertSee('تفاصيل الطلب');
    }
}

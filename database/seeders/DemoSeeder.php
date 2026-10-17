<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Schedule;
use App\Models\ShipmentEvent;
use App\Models\ShipmentTrack;
use App\Models\ShippingRate;
use App\Models\Vessel;
use App\Models\Voucher;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    /**
     * Seed synthetic, company-neutral demonstration data.
     */
    public function run(): void
    {
        // 1. Roles, Staff Users & Reference Catalogs
        $this->call([
            RolesAndUsersSeeder::class,
            GlobalPortsSeeder::class,
            AirportSeeder::class,
        ]);

        // 2. Active Vessels
        $vessels = [
            ['name' => 'Meridian Pioneer', 'type' => 'Ultra Large Container', 'capacity' => '24,000 TEU', 'flag' => '🇸🇬', 'year' => 2024, 'status' => 'قيد الإبحار', 'route' => 'Shanghai → Aden'],
            ['name' => 'Meridian Horizon', 'type' => 'Neo-Panamax', 'capacity' => '15,000 TEU', 'flag' => '🇩🇰', 'year' => 2022, 'status' => 'في الميناء', 'route' => 'Jebel Ali → Alexandria'],
            ['name' => 'Pacific Navigator', 'type' => 'Post-Panamax', 'capacity' => '11,000 TEU', 'flag' => '🇳🇱', 'year' => 2021, 'status' => 'قيد الإبحار', 'route' => 'Singapore → Rotterdam'],
            ['name' => 'Atlas Carrier', 'type' => 'Feeder Vessel', 'capacity' => '4,500 TEU', 'flag' => '🇬🇧', 'year' => 2020, 'status' => 'في الميناء', 'route' => 'Jeddah → Port Said'],
        ];
        foreach ($vessels as $v) {
            Vessel::create($v);
        }

        // 3. Active Published Schedules with Relative Expiry
        $schedules = [
            [
                'vessel' => 'Meridian Pioneer',
                'voyage' => 'MP-2026W',
                'carrier' => 'Global Ocean Lines',
                'service_name' => 'Asia Gulf Express',
                'origin' => 'Shanghai',
                'origin_code' => 'CNSHA',
                'destination' => 'Aden',
                'destination_code' => 'YEADE',
                'etd' => Carbon::today()->addDays(5)->toDateString(),
                'eta' => Carbon::today()->addDays(23)->toDateString(),
                'transit' => '18 days',
                'cutoff' => Carbon::today()->addDays(3)->toDateString(),
                'status' => 'published',
                'is_published' => true,
                'source_name' => 'Carrier Official EDI',
                'source_reference' => 'SCH-2026-01',
                'source_checked_at' => Carbon::today()->subDays(2)->toDateString(),
                'expires_at' => Carbon::today()->addDays(45)->toDateString(),
            ],
            [
                'vessel' => 'Meridian Horizon',
                'voyage' => 'MH-401E',
                'carrier' => 'Alliance Ocean Line',
                'service_name' => 'Red Sea Express',
                'origin' => 'Jebel Ali',
                'origin_code' => 'AEJEA',
                'destination' => 'Alexandria',
                'destination_code' => 'EGALY',
                'etd' => Carbon::today()->addDays(8)->toDateString(),
                'eta' => Carbon::today()->addDays(18)->toDateString(),
                'transit' => '10 days',
                'cutoff' => Carbon::today()->addDays(6)->toDateString(),
                'status' => 'published',
                'is_published' => true,
                'source_name' => 'Terminal Schedule Direct',
                'source_reference' => 'SCH-2026-02',
                'source_checked_at' => Carbon::today()->subDays(1)->toDateString(),
                'expires_at' => Carbon::today()->addDays(30)->toDateString(),
            ],
            [
                'vessel' => 'Pacific Navigator',
                'voyage' => 'PN-108W',
                'carrier' => 'Global Ocean Lines',
                'service_name' => 'Far East Europe',
                'origin' => 'Singapore',
                'origin_code' => 'SGSIN',
                'destination' => 'Rotterdam',
                'destination_code' => 'NLRTM',
                'etd' => Carbon::today()->addDays(12)->toDateString(),
                'eta' => Carbon::today()->addDays(34)->toDateString(),
                'transit' => '22 days',
                'cutoff' => Carbon::today()->addDays(9)->toDateString(),
                'status' => 'published',
                'is_published' => true,
                'source_name' => 'Global Maritime Network',
                'source_reference' => 'SCH-2026-03',
                'source_checked_at' => Carbon::today()->toDateString(),
                'expires_at' => Carbon::today()->addDays(60)->toDateString(),
            ],
        ];
        foreach ($schedules as $s) {
            Schedule::create($s);
        }

        // 4. Master Shipping Tariffs
        $tariffs = [
            ['shipping_type' => 'sea', 'service_type' => 'FCL', 'incoterm' => 'FOB', 'container_type' => '40HC', 'origin_country' => 'China', 'origin_port' => 'Shanghai', 'destination_country' => 'Yemen', 'destination_port' => 'Aden', 'shipping_company' => 'Global Ocean Lines', 'transit_time' => 18, 'currency' => 'USD', 'price' => 2450, 'additional_fee' => 150, 'notes' => 'Synthetic demo rate.'],
            ['shipping_type' => 'sea', 'service_type' => 'FCL', 'incoterm' => 'FOB', 'container_type' => '20GP', 'origin_country' => 'China', 'origin_port' => 'Ningbo', 'destination_country' => 'Egypt', 'destination_port' => 'Alexandria', 'shipping_company' => 'Alliance Ocean Line', 'transit_time' => 22, 'currency' => 'USD', 'price' => 1650, 'additional_fee' => 120, 'notes' => 'Synthetic demo rate.'],
            ['shipping_type' => 'sea', 'service_type' => 'FCL', 'incoterm' => 'CIF', 'container_type' => '40HC', 'origin_country' => 'United Arab Emirates', 'origin_port' => 'Jebel Ali', 'destination_country' => 'Yemen', 'destination_port' => 'Aden', 'shipping_company' => 'Global Ocean Lines', 'transit_time' => 8, 'currency' => 'USD', 'price' => 1800, 'additional_fee' => 100, 'notes' => 'Synthetic demo rate.'],
            ['shipping_type' => 'sea', 'service_type' => 'FCL', 'incoterm' => 'FOB', 'container_type' => '40HC', 'origin_country' => 'China', 'origin_port' => 'Qingdao', 'destination_country' => 'Saudi Arabia', 'destination_port' => 'Jeddah', 'shipping_company' => 'Global Freight Star', 'transit_time' => 20, 'currency' => 'USD', 'price' => 2100, 'additional_fee' => 180, 'notes' => 'Synthetic demo rate.'],
        ];
        foreach ($tariffs as $t) {
            ShippingRate::create(array_merge($t, [
                'valid_from' => Carbon::today()->subDays(15),
                'valid_to' => Carbon::today()->addDays(60),
                'is_active' => true,
            ]));
        }

        // 5. Verified Demo Customer Shipper Account
        $customer = Customer::create([
            'company' => 'Meridian Global Trading Ltd',
            'contact_name' => 'Alexander Vance',
            'email' => 'customer@example.com',
            'phone' => '+15550192834',
            'password' => Hash::make('password'),
            'portal_enabled' => true,
        ]);

        // 6. Realistic Customer Shipments in Multiple Stages

        // Shipment 1: Priced Quote (Awaiting Customer Approval)
        $b1 = Booking::create([
            'id' => 'BK-2026-8801',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 2,
            'goods_description' => 'Solar inverters & commercial batteries',
            'weight_kg' => 18400,
            'volume_cbm' => 54,
            'dangerous' => false,
            'documents' => 'Commercial Invoice + Packing List + Certificate of Origin',
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'priced',
            'ocean_freight' => 2450,
            'origin_charges' => 180,
            'dthc' => 250,
            'validity_date' => Carbon::today()->addDays(5),
            'transit_days' => 18,
            'priced_at' => Carbon::now()->subHours(4),
            'route_details' => ['mode' => 'FOB', 'services' => ['customs_requested' => true]],
            'created_at' => Carbon::now()->subDay(),
        ]);

        // Shipment 2: Active In-Transit Shipment with Live Ocean Tracking
        $b2 = Booking::create([
            'id' => 'BK-2026-8802',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Industrial water pumps & filtration valves',
            'weight_kg' => 14200,
            'volume_cbm' => 38,
            'dangerous' => false,
            'pol' => 'Jebel Ali',
            'pod' => 'Aden',
            'status' => 'approved',
            'ocean_freight' => 1800,
            'origin_charges' => 120,
            'transit_days' => 8,
            'priced_at' => Carbon::now()->subDays(6),
            'customs_status' => 'documents_review',
            'customs_reference' => 'CUS-2026-0089',
            'customs_declaration_no' => 'DEC-2026-99214',
            'carrier_vessel' => 'Meridian Pioneer',
            'carrier_voyage' => 'MP-2026W',
            'carrier_booking_ref' => 'GLO-88201',
            'container_seal_no' => 'SEAL-99824',
            'route_details' => ['mode' => 'CIF', 'services' => ['customs_requested' => true]],
            'created_at' => Carbon::now()->subDays(7),
        ]);

        // Tracking for Shipment 2
        $track2 = ShipmentTrack::create([
            'id' => 'TR-2026-8802',
            'booking_id' => $b2->id,
            'status' => 'In transit',
            'progress' => 55,
            'etd' => Carbon::today()->subDays(4),
            'eta' => Carbon::today()->addDays(4),
        ]);
        $track2->events()->create([
            'status' => 'Vessel departed',
            'location' => 'Jebel Ali, UAE',
            'description' => 'Container loaded aboard Meridian Pioneer (Voyage MP-2026W)',
            'event_date' => Carbon::now()->subDays(3)->format('Y-m-d H:i'),
        ]);
        $track2->events()->create([
            'status' => 'In transit',
            'location' => 'Gulf of Oman / Arabian Sea',
            'description' => 'Vessel en route at speed of 18.2 knots',
            'event_date' => Carbon::now()->subDay()->format('Y-m-d H:i'),
        ]);

        // Invoice for Shipment 2 (Unpaid)
        Invoice::create([
            'id' => 'INV-2026-002',
            'booking_id' => $b2->id,
            'amount' => 1920,
            'currency' => 'USD',
            'status' => 'غير مدفوعة',
            'issued_at' => Carbon::now()->subDays(3),
            'due_at' => Carbon::today()->addDays(14),
        ]);

        // Shipment 3: Completed & Delivered Shipment
        $b3 = Booking::create([
            'id' => 'BK-2026-8803',
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name,
            'customer_email' => $customer->email,
            'customer_phone' => $customer->phone,
            'shipment_type' => 'FCL',
            'container_type' => '20GP',
            'container_qty' => 1,
            'goods_description' => 'Textile fabrics & synthetic yarn',
            'weight_kg' => 9800,
            'volume_cbm' => 24,
            'pol' => 'Ningbo',
            'pod' => 'Alexandria',
            'status' => 'delivered',
            'ocean_freight' => 1650,
            'origin_charges' => 100,
            'dthc' => 200,
            'created_at' => Carbon::now()->subDays(30),
        ]);

        $inv3 = Invoice::create([
            'id' => 'INV-2026-001',
            'booking_id' => $b3->id,
            'amount' => 1950,
            'currency' => 'USD',
            'status' => 'مدفوعة',
            'issued_at' => Carbon::now()->subDays(28),
            'due_at' => Carbon::now()->subDays(14),
        ]);

        Voucher::create([
            'id' => 'V-2026-001',
            'invoice_id' => $inv3->id,
            'booking_id' => $b3->id,
            'type' => 'receipt',
            'amount' => 1950,
            'date' => Carbon::now()->subDays(20),
            'payee_payer' => $customer->company,
            'payment_method' => 'Wire Transfer',
            'description' => 'Full payment settlement for INV-2026-001',
        ]);

        // 7. System Notifications
        Notification::create([
            'id' => 'notif-demo-1',
            'booking_id' => $b1->id,
            'target' => 'customer',
            'message' => 'عرض السعر جاهز للاعتماد / Quotation BK-2026-8801 is ready for your review',
            'read' => false,
            'created_at' => Carbon::now()->subHours(2),
        ]);
        Notification::create([
            'id' => 'notif-demo-2',
            'booking_id' => $b2->id,
            'target' => 'customer',
            'message' => 'تحديث تتبع الشحنة / Shipment BK-2026-8802 tracking updated: In Transit',
            'read' => false,
            'created_at' => Carbon::now()->subDay(),
        ]);
    }
}

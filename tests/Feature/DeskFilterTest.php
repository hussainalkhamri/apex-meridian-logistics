<?php

namespace Tests\Feature;

use App\Models\Booking;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeskFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndUsersSeeder::class);
    }

    public function test_pricing_page_search_and_transport_filters_work(): void
    {
        $pricingUser = \App\Models\User::where('username', 'pricing_user')->firstOrFail();

        Booking::create([
            'id' => 'BK-PRICING-AIR-MATCH',
            'customer_company' => 'Pricing Air Match',
            'customer_contact_name' => 'Air Contact',
            'customer_phone' => '+967700000101',
            'customer_email' => 'pricing-air-match@example.com',
            'shipment_type' => 'AIR',
            'goods_description' => 'Air cargo match',
            'weight_kg' => 100,
            'pol' => 'Sanaa',
            'pod' => 'Dubai',
            'status' => 'new',
        ]);

        Booking::create([
            'id' => 'BK-PRICING-SEA-OTHER',
            'customer_company' => 'Pricing Sea Other',
            'customer_contact_name' => 'Sea Contact',
            'customer_phone' => '+967700000102',
            'customer_email' => 'pricing-sea-other@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Sea cargo other',
            'weight_kg' => 100,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        $this->actingAs($pricingUser)
            ->get('/pricing?q=Pricing%20Air%20Match')
            ->assertOk()
            ->assertSee('BK-PRICING-AIR-MATCH')
            ->assertDontSee('BK-PRICING-SEA-OTHER');

        $this->actingAs($pricingUser)
            ->get('/pricing?q=BK-PRICING-AIR-MATCH')
            ->assertOk()
            ->assertSee('Pricing Air Match')
            ->assertDontSee('Pricing Sea Other');

        $this->actingAs($pricingUser)
            ->get('/pricing?transport_mode=air')
            ->assertOk()
            ->assertSee('BK-PRICING-AIR-MATCH')
            ->assertDontSee('BK-PRICING-SEA-OTHER');
    }

    public function test_pricing_page_filters_sea_shipment_type(): void
    {
        $pricingUser = \App\Models\User::where('username', 'pricing_user')->firstOrFail();

        Booking::create([
            'id' => 'BK-PRICING-BULK-MATCH',
            'customer_company' => 'Pricing Bulk Match',
            'customer_contact_name' => 'Bulk Contact',
            'customer_phone' => '+967700000105',
            'customer_email' => 'pricing-bulk-match@example.com',
            'shipment_type' => 'Bulk',
            'goods_description' => 'Bulk cargo',
            'weight_kg' => 100,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        Booking::create([
            'id' => 'BK-PRICING-FCL-OTHER',
            'customer_company' => 'Pricing FCL Other',
            'customer_contact_name' => 'FCL Contact',
            'customer_phone' => '+967700000106',
            'customer_email' => 'pricing-fcl-other@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '20GP',
            'container_qty' => 1,
            'goods_description' => 'FCL cargo',
            'weight_kg' => 100,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'new',
        ]);

        $this->actingAs($pricingUser)
            ->get('/pricing?transport_mode=sea&sea_type=Bulk')
            ->assertOk()
            ->assertSee('BK-PRICING-BULK-MATCH')
            ->assertDontSee('BK-PRICING-FCL-OTHER');
    }

    public function test_sales_page_search_transport_and_status_filters_work(): void
    {
        $salesUser = \App\Models\User::where('username', 'sales_user')->firstOrFail();

        Booking::create([
            'id' => 'BK-SALES-AIR-MATCH',
            'customer_company' => 'Sales Air Match',
            'customer_contact_name' => 'Air Sales Contact',
            'customer_phone' => '+967700000103',
            'customer_email' => 'sales-air-match@example.com',
            'shipment_type' => 'AIR',
            'goods_description' => 'Sales air cargo',
            'weight_kg' => 100,
            'pol' => 'Sanaa',
            'pod' => 'Dubai',
            'status' => 'sent',
        ]);

        Booking::create([
            'id' => 'BK-SALES-LAND-OTHER',
            'customer_company' => 'Sales Land Other',
            'customer_contact_name' => 'Land Sales Contact',
            'customer_phone' => '+967700000104',
            'customer_email' => 'sales-land-other@example.com',
            'shipment_type' => 'LAND',
            'goods_description' => 'Sales land cargo',
            'weight_kg' => 100,
            'pol' => 'Sanaa',
            'pod' => 'Riyadh',
            'status' => 'sent',
        ]);

        Booking::create([
            'id' => 'BK-SALES-APPROVED',
            'customer_company' => 'Sales Approved Customer',
            'customer_contact_name' => 'Approved Contact',
            'customer_phone' => '+967700000107',
            'customer_email' => 'sales-approved@example.com',
            'shipment_type' => 'AIR',
            'goods_description' => 'Approved cargo',
            'weight_kg' => 100,
            'pol' => 'Sanaa',
            'pod' => 'Dubai',
            'status' => 'approved',
        ]);

        $this->actingAs($salesUser)
            ->get('/sales?q=Sales%20Air%20Match&transport_mode=air')
            ->assertOk()
            ->assertSee('BK-SALES-AIR-MATCH')
            ->assertDontSee('BK-SALES-LAND-OTHER');

        $this->actingAs($salesUser)
            ->get('/sales?q=BK-SALES-AIR-MATCH')
            ->assertOk()
            ->assertSee('Sales Air Match')
            ->assertDontSee('Sales Land Other');

        $this->actingAs($salesUser)
            ->get('/sales?status=approved')
            ->assertOk()
            ->assertSee('BK-SALES-APPROVED')
            ->assertDontSee('BK-SALES-AIR-MATCH');
    }
}

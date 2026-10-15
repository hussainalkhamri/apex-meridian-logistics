<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentOrdersTest extends TestCase
{
    use RefreshDatabase;

    public function test_agents_page_only_lists_sales_approved_orders_with_agent_purchase_price(): void
    {
        $agent = Agent::create([
            'name' => 'Approved Agent',
            'type' => 'external',
        ]);

        $baseBooking = [
            'customer_company' => 'Test Customer',
            'customer_contact_name' => 'Customer Contact',
            'customer_phone' => '+967700000000',
            'customer_email' => 'customer@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Test goods',
            'weight_kg' => 1000,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 1250,
            'agent_purchase_currency' => 'USD',
        ];

        Booking::create($baseBooking + [
            'id' => 'BK-APPROVED-AGENT',
            'status' => 'approved',
            'decided_at' => now(),
            'ocean_freight' => 2500,
        ]);
        Booking::create($baseBooking + [
            'id' => 'BK-NOT-APPROVED-AGENT',
            'status' => 'priced',
        ]);

        $response = $this->withoutMiddleware()->get(route('agents.index', ['tab' => 'orders']));

        $response->assertOk()
            ->assertSee('Approved Agent')
            ->assertSee('BK-APPROVED-AGENT')
            ->assertSee('1,250.00')
            ->assertDontSee('BK-NOT-APPROVED-AGENT');
    }

    public function test_agent_orders_export_uses_current_search_filter(): void
    {
        $agent = Agent::create([
            'name' => 'Export Agent',
            'type' => 'local',
        ]);

        Booking::create([
            'id' => 'BK-EXPORT-AGENT',
            'customer_company' => 'Export Customer',
            'customer_contact_name' => 'Customer Contact',
            'customer_phone' => '+967700000001',
            'customer_email' => 'export@example.com',
            'shipment_type' => 'LCL',
            'goods_description' => 'Export goods',
            'weight_kg' => 500,
            'volume_cbm' => 4,
            'pol' => 'Aden',
            'pod' => 'Jeddah',
            'status' => 'approved',
            'agent_id' => $agent->id,
            'agent_purchase_amount' => 800,
            'decided_at' => now(),
        ]);

        $response = $this->withoutMiddleware()->get(route('agents.orders.export', ['search' => 'Export Agent']));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}

<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentForwardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_agent_email_forward_returns_a_mailto_link(): void
    {
        app()->setLocale('en');
        $agent = Agent::create([
            'name' => 'Test Agent',
            'email' => 'rates@agent.example',
            'phone' => '+967777777777',
            'type' => 'external',
        ]);

        $booking = Booking::create([
            'id' => 'BK-TEST-AGENT',
            'customer_company' => 'Test Customer',
            'customer_contact_name' => 'Customer Contact',
            'customer_phone' => '+967711111111',
            'customer_email' => 'customer@example.com',
            'shipment_type' => 'FCL',
            'container_type' => '40HC',
            'container_qty' => 1,
            'goods_description' => 'Auto parts',
            'weight_kg' => 1200,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'pricing',
        ]);

        $response = $this->withoutMiddleware()->postJson(route('bookings.sendToAgent'), [
            'booking_id' => $booking->id,
            'agent_id' => $agent->id,
            'method' => 'email',
            'message' => 'Please price this shipment.',
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('email', 'rates@agent.example')
            ->assertJson(fn ($json) => $json->whereType('mailto_url', 'string')->etc());

        $mailtoUrl = $response->json('mailto_url');
        $this->assertStringStartsWith('mailto:rates%40agent.example', $mailtoUrl);
        $this->assertStringContainsString('subject=Forwarded%20Booking%20Request', $mailtoUrl);
        $this->assertStringContainsString('Please%20price%20this%20shipment', $mailtoUrl);
        $this->assertStringContainsString('BK-TEST-AGENT', rawurldecode($mailtoUrl));
    }

    public function test_agent_email_forward_uses_multi_container_summary(): void
    {
        app()->setLocale('en');

        $agent = Agent::create([
            'name' => 'Multi Container Agent',
            'email' => 'multi-rates@agent.example',
            'phone' => '+967777777778',
            'type' => 'external',
        ]);

        $booking = Booking::create([
            'id' => 'BK-TEST-AGENT-MULTI',
            'customer_company' => 'Multi Container Customer',
            'customer_contact_name' => 'Customer Contact',
            'customer_phone' => '+967711111112',
            'customer_email' => 'multi-customer@example.com',
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
            'goods_description' => 'Mixed FCL cargo',
            'weight_kg' => 1200,
            'volume_cbm' => 10,
            'pol' => 'Shanghai',
            'pod' => 'Aden',
            'status' => 'pricing',
        ]);

        $response = $this->withoutMiddleware()->postJson(route('bookings.sendToAgent'), [
            'booking_id' => $booking->id,
            'agent_id' => $agent->id,
            'method' => 'email',
            'message' => 'Please price this shipment.',
        ]);

        $response->assertOk();

        $message = rawurldecode($response->json('mailto_url'));
        $this->assertStringContainsString('Cargo: FCL 3x40GP + 2x20GP', $message);
        $this->assertStringNotContainsString('Cargo: FCL 3x 40GP', $message);
    }
}

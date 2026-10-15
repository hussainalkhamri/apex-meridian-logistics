<?php

namespace Tests\Feature;

use App\Http\Middleware\AutoGenerateSystemInsights;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MultiAgentForwardingTest extends TestCase
{
    use RefreshDatabase;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AutoGenerateSystemInsights::class);
        $role = Role::create(['name' => 'forwarding', 'permissions' => ['manage_pricing', 'manage_bookings']]);
        $this->actingAs(User::create([
            'username' => 'forwarding', 'full_name' => 'Forwarding user',
            'password' => 'password', 'is_active' => true, 'role_id' => $role->id,
        ]));
        $this->booking = Booking::create([
            'id' => 'BK-MULTI-AGENTS', 'customer_company' => 'Private Customer',
            'customer_contact_name' => 'Private Contact', 'customer_phone' => '+967711111111',
            'customer_email' => 'private@example.com', 'shipment_type' => 'FCL',
            'container_type' => '40HC', 'container_qty' => 1, 'goods_description' => 'Auto parts',
            'weight_kg' => 1200, 'volume_cbm' => 10, 'pol' => 'Shanghai', 'pod' => 'Aden',
            'status' => 'needs_pricing',
        ]);
    }

    private function agent(array $overrides = []): Agent
    {
        $number = Agent::count() + 1;

        return Agent::create($overrides + [
            'name' => 'Agent '.$number, 'email' => 'rates+'.$number.'@agent.example',
            'phone' => '+967 700 000 00'.$number, 'type' => 'external',
        ]);
    }

    private function forward(string $method, array $data)
    {
        return $this->postJson(route('bookings.sendToAgent', ['locale' => 'en']), $data + [
            'booking_id' => $this->booking->id, 'method' => $method,
        ]);
    }

    public function test_whatsapp_links_keep_selection_order_and_use_one_sanitized_saved_booking_message(): void
    {
        $first = $this->agent(['type' => 'local']);
        $second = $this->agent();
        $before = $this->booking->fresh()->getRawOriginal();
        $agentsBefore = Agent::all()->toArray();
        $response = $this->forward('whatsapp', [
            'agent_ids' => [$second->id, $first->id],
            'phone' => '15555555555', 'email' => 'untrusted@example.com',
            'message' => 'Please quote this shipment.',
        ])->assertOk()->assertJsonPath('sent_count', 2)->assertJsonPath('failed_agents', [])
            ->assertJsonMissingPath('whatsapp_url');

        $urls = $response->json('whatsapp_urls');
        $this->assertCount(2, $urls);
        $this->assertStringStartsWith('https://wa.me/967700000002?text=', $urls[0]);
        $this->assertStringStartsWith('https://wa.me/967700000001?text=', $urls[1]);
        parse_str(parse_url($urls[0], PHP_URL_QUERY), $firstQuery);
        parse_str(parse_url($urls[1], PHP_URL_QUERY), $secondQuery);
        $this->assertSame($firstQuery['text'], $secondQuery['text']);
        $this->assertStringContainsString('Route: Shanghai → Aden', $firstQuery['text']);
        $this->assertStringContainsString('[CONFIDENTIAL]', $firstQuery['text']);
        $this->assertStringContainsString('[EMAIL]', $firstQuery['text']);
        $this->assertStringContainsString('[PHONE]', $firstQuery['text']);
        foreach (['Private Customer', 'Private Contact', 'private@example.com', '967711111111'] as $private) {
            $this->assertStringNotContainsString($private, $firstQuery['text']);
        }
        $this->assertSame($before, $this->booking->fresh()->getRawOriginal());
        $this->assertSame($agentsBefore, Agent::all()->toArray());
    }

    public function test_single_agent_whatsapp_supports_both_old_and_new_request_and_response_fields(): void
    {
        $agent = $this->agent();
        foreach ([['agent_id' => $agent->id], ['agent_ids' => [$agent->id]]] as $data) {
            $response = $this->forward('whatsapp', $data)->assertOk()
                ->assertJsonPath('sent_count', 1)->assertJsonCount(1, 'whatsapp_urls');
            $this->assertSame($response->json('whatsapp_url'), $response->json('whatsapp_urls.0'));
        }
    }

    public function test_whatsapp_skips_missing_phones_without_losing_the_remaining_order(): void
    {
        $first = $this->agent();
        $missing = $this->agent(['phone' => null]);
        $last = $this->agent();
        $response = $this->forward('whatsapp', ['agent_ids' => [$last->id, $missing->id, $first->id]])
            ->assertOk()->assertJsonPath('sent_count', 2)
            ->assertJsonPath('failed_agents.0.id', $missing->id)
            ->assertJsonPath('failed_agents.0.reason', 'missing_phone');
        $this->assertStringStartsWith('https://wa.me/967700000003?', $response->json('whatsapp_urls.0'));
        $this->assertStringStartsWith('https://wa.me/967700000001?', $response->json('whatsapp_urls.1'));
    }

    public function test_whatsapp_with_no_usable_phones_returns_422_and_no_links(): void
    {
        $first = $this->agent(['phone' => 'not a phone']);
        $second = $this->agent(['phone' => null]);
        $this->forward('whatsapp', ['agent_ids' => [$first->id, $second->id]])
            ->assertUnprocessable()->assertJsonPath('status', 'error')
            ->assertJsonPath('whatsapp_urls', [])->assertJsonPath('sent_count', 0)
            ->assertJsonCount(2, 'failed_agents')->assertJsonMissingPath('whatsapp_url')
            ->assertJsonPath('message', 'None of the selected agents have a valid phone number.');
        $this->assertSame('needs_pricing', $this->booking->fresh()->status);
    }

    public function test_multiple_agents_get_one_bcc_draft_with_encoded_addresses_subject_and_body(): void
    {
        $first = $this->agent(['email' => 'rates+special&team@agent.example']);
        $second = $this->agent();
        $before = $this->booking->fresh()->getRawOriginal();
        $response = $this->forward('email', [
            'agent_ids' => [$second->id, $first->id],
            'message' => 'Please quote A&B / طلب تسعير',
            'email' => 'untrusted@example.com',
        ])->assertOk()->assertJsonPath('email', $second->email)
            ->assertJsonPath('emails', [$second->email, $first->email])
            ->assertJsonPath('sent_count', 2)->assertJsonPath('failed_agents', []);

        $url = $response->json('mailto_url');
        $this->assertStringStartsWith('mailto:?bcc='.rawurlencode($second->email).','.rawurlencode($first->email).'&subject=', $url);
        $this->assertStringContainsString('%2Bspecial%26team%40agent.example', $url);
        parse_str(explode('?', $url, 2)[1], $query);
        $this->assertSame($second->email.','.$first->email, $query['bcc']);
        $this->assertArrayNotHasKey('to', $query);
        $this->assertArrayNotHasKey('cc', $query);
        $this->assertSame('Forwarded Booking Request', $query['subject']);
        $this->assertStringContainsString('Please quote A&B / طلب تسعير', $query['body']);
        $this->assertStringNotContainsString('private@example.com', $query['body']);
        $this->assertStringNotContainsString('untrusted@example.com', $url);
        $this->assertSame($before, $this->booking->fresh()->getRawOriginal());
    }

    public function test_single_agent_email_keeps_the_legacy_mailto_format(): void
    {
        $agent = $this->agent();
        foreach ([['agent_id' => $agent->id], ['agent_ids' => [$agent->id]]] as $data) {
            $response = $this->forward('email', $data)->assertOk()
                ->assertJsonPath('emails', [$agent->email])->assertJsonPath('sent_count', 1);
            $this->assertStringStartsWith('mailto:'.rawurlencode($agent->email).'?subject=', $response->json('mailto_url'));
            $this->assertStringNotContainsString('bcc=', $response->json('mailto_url'));
        }
    }

    public function test_email_skips_missing_addresses_and_keeps_bcc_for_a_multi_agent_selection(): void
    {
        $valid = $this->agent();
        $missing = $this->agent(['email' => null]);
        $response = $this->forward('email', ['agent_ids' => [$missing->id, $valid->id]])
            ->assertOk()->assertJsonPath('sent_count', 1)->assertJsonPath('emails', [$valid->email])
            ->assertJsonPath('failed_agents.0.id', $missing->id)
            ->assertJsonPath('failed_agents.0.reason', 'missing_email');
        $this->assertStringStartsWith('mailto:?bcc='.rawurlencode($valid->email).'&', $response->json('mailto_url'));
    }

    public function test_email_with_no_addresses_returns_422_without_a_draft(): void
    {
        $first = $this->agent(['email' => null]);
        $second = $this->agent(['email' => '']);
        $this->forward('email', ['agent_ids' => [$first->id, $second->id]])
            ->assertUnprocessable()->assertJsonPath('sent_count', 0)->assertJsonPath('emails', [])
            ->assertJsonCount(2, 'failed_agents')->assertJsonMissingPath('mailto_url')
            ->assertJsonPath('message', 'None of the selected agents have a valid email address.');
    }

    public static function invalidSelections(): array
    {
        return [
            'empty' => [[]], 'null' => [null], 'not array' => ['1'],
            'duplicate' => [[1, 1]], 'mixed duplicate' => [[1, '1']],
            'missing' => [[99999]], 'noninteger' => [['not-an-id']],
            'fraction' => [[1.5]], 'nested' => [[[1]]],
        ];
    }

    #[DataProvider('invalidSelections')]
    public function test_invalid_agent_arrays_are_rejected_for_both_methods(mixed $ids): void
    {
        $this->agent();
        foreach (['whatsapp', 'email'] as $method) {
            $this->forward($method, ['agent_ids' => $ids])->assertUnprocessable();
        }
        $this->assertSame('needs_pricing', $this->booking->fresh()->status);
    }

    public function test_sanitizing_a_prefilled_message_still_hides_customer_contact_details(): void
    {
        $first = $this->agent();
        $second = $this->agent();
        $message = "Order number: {$this->booking->id}\nClient: Private Customer\nContact Name: Private Contact\nPhone: +967711111111\nEmail: private@example.com";
        $response = $this->forward('email', ['agent_ids' => [$first->id, $second->id], 'message' => $message])->assertOk();
        parse_str(explode('?', $response->json('mailto_url'), 2)[1], $query);
        $this->assertSame(1, substr_count($query['body'], 'Order number:'));
        foreach (['Private Customer', 'Private Contact', 'private@example.com', '967711111111'] as $private) {
            $this->assertStringNotContainsString($private, $query['body']);
        }
    }

    public function test_forwarding_still_requires_authentication(): void
    {
        auth('web')->logout();
        $this->post(route('bookings.sendToAgent'), [
            'booking_id' => $this->booking->id, 'agent_ids' => [1], 'method' => 'email',
        ])->assertRedirect(route('login'));
    }

    public function test_pricing_renders_the_translated_send_button_and_multi_selection_hint(): void
    {
        foreach (['ar' => ['إرسال', 'اختر وكيلاً واحداً أو أكثر'], 'en' => ['Send', 'Select one or more agents']] as $locale => [$send, $hint]) {
            $response = $this->get('/pricing?locale='.$locale)->assertOk()->assertSeeText($hint);
            $response->assertSee('<span>'.$send.'</span>', false);
            $response->assertSee('id="agent-forward-whatsapp" disabled', false);
        }
    }
}

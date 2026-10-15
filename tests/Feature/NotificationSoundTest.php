<?php

namespace Tests\Feature;

use App\Http\Middleware\AutoGenerateSystemInsights;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\NotificationRead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationSoundTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(AutoGenerateSystemInsights::class);
    }

    private function staff(array $permissions): User
    {
        $role = Role::create(['name' => 'sound-role-'.Role::count(), 'permissions' => $permissions]);

        return User::create([
            'full_name' => 'Notification Tester', 'username' => 'sound-user-'.User::count(),
            'password' => 'password', 'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    private function customer(): Customer
    {
        return Customer::create([
            'company' => 'Notification Customer', 'contact_name' => 'Test Contact',
            'phone' => '770000001', 'email' => 'sound-'.Customer::count().'@example.com',
            'password' => 'password', 'portal_enabled' => true,
        ]);
    }

    private function booking(array $attributes = []): Booking
    {
        return Booking::create($attributes + [
            'id' => 'BK-SOUND-'.Booking::withoutGlobalScopes()->count(),
            'customer_company' => 'Test', 'customer_contact_name' => 'Test',
            'customer_phone' => '770000001', 'customer_email' => 'test@example.com',
            'shipment_type' => 'FCL', 'container_type' => '40HC', 'container_qty' => 1,
            'goods_description' => 'Cargo', 'weight_kg' => 1000, 'volume_cbm' => 5,
            'pol' => 'Shanghai', 'pod' => 'Aden', 'status' => 'new',
        ]);
    }

    private function notification(string $target, ?Booking $booking = null, string $sound = 'request'): Notification
    {
        return Notification::create([
            'id' => 'N-SOUND-'.Notification::count(), 'message' => 'Notification '.Notification::count(),
            'target' => $target, 'booking_id' => $booking?->id, 'sound' => $sound,
        ]);
    }

    public function test_feeds_require_the_correct_authenticated_guard(): void
    {
        $this->get('/notifications/feed')->assertRedirect('/login');
        $this->get('/customer/notifications/feed')->assertRedirect('/customer/login');

        $this->actingAs($this->customer(), 'customer');
        $this->get('/notifications/feed')->assertRedirect('/login');
    }

    public function test_staff_feed_respects_permissions_and_read_status_and_counts_the_live_queue(): void
    {
        $user = $this->staff(['manage_pricing']);
        $new = $this->booking();
        $reprice = $this->booking(['status' => 'needs_pricing']);
        $this->booking(['status' => 'new', 'is_archived' => true]);
        $this->booking(['status' => 'priced']);
        $visible = $this->notification('pricing', $new);
        $read = $this->notification('pricing', $reprice);
        $this->notification('accounts', $new);
        $this->notification('customer', $new, 'pricing');
        NotificationRead::create(['notification_id' => $read->id, 'user_id' => $user->id, 'read_at' => now()]);

        $this->actingAs($user)->getJson('/notifications/feed')
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('unread_count', 1)->assertJsonPath('notifications.0.id', $visible->id)
            ->assertJsonPath('notifications.0.sound', 'request')
            ->assertJsonPath('pending_pricing.count', 2)
            ->assertJsonPath('pending_pricing.interval_ms', 300000);

        $this->post('/notifications/read')->assertOk();
        $this->getJson('/notifications/feed')->assertJsonPath('unread_count', 0)->assertJsonPath('pending_pricing.count', 2);
        $new->update(['status' => 'priced']);
        $reprice->update(['is_archived' => true]);
        $this->getJson('/notifications/feed')->assertJsonPath('pending_pricing.count', 0);

        $this->actingAs($this->staff(['manage_accounts']))->getJson('/notifications/feed')
            ->assertOk()->assertJsonPath('pending_pricing', null)
            ->assertJsonPath('notifications.0.target', 'accounts');
    }

    public function test_customer_feed_never_exposes_other_customers_or_staff_notifications(): void
    {
        $customer = $this->customer();
        $own = $this->booking(['customer_id' => $customer->id]);
        $other = $this->booking(['customer_id' => $this->customer()->id]);
        $visible = $this->notification('customer', $own, 'pricing');
        $read = $this->notification('customer', $own);
        $this->notification('customer', $other, 'pricing');
        $this->notification('pricing', $own);
        DB::table('customer_notification_reads')->insert([
            'notification_id' => $read->id, 'customer_id' => $customer->id,
            'read_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Both sessions may coexist; the customer endpoint must use its guard.
        $this->actingAs($this->staff(['manage_pricing']), 'web')->actingAs($customer, 'customer');
        $this->getJson('/customer/notifications/feed')->assertOk()
            ->assertJsonCount(1, 'notifications')->assertJsonPath('notifications.0.id', $visible->id)
            ->assertJsonPath('notifications.0.sound', 'pricing')->assertJsonPath('pending_pricing', null);

        $customer->update(['portal_enabled' => false]);
        $this->getJson('/customer/notifications/feed')->assertRedirect('/customer/pending-approval');
    }

    public function test_successful_staff_and_customer_submissions_use_the_request_tone(): void
    {
        $payload = [
            'company' => 'New customer', 'contactName' => 'Test', 'phone' => '770000001',
            'email' => 'new@example.com', 'goodsDescription' => 'Test cargo', 'weightKg' => 1000,
            'pol' => 'Shanghai', 'pod' => 'Aden', 'shipmentType' => 'FCL',
            'containerType' => '40HC', 'containerQty' => 1,
            'shipping_category' => 'sea', 'route_mode' => 'FOB',
            'origin_country' => 'China', 'destination_country' => 'Yemen',
        ];
        $this->actingAs($this->staff(['manage_bookings']))->post('/booking', $payload)
            ->assertSessionHasNoErrors()->assertRedirect('/orders')
            ->assertSessionHas('notification_feedback.sounds', ['request']);
        $this->assertSame(['request'], Notification::pluck('sound')->unique()->values()->all());
        $staffBooking = Booking::firstOrFail();
        $this->assertDatabaseHas('notifications', ['booking_id' => $staffBooking->id, 'target' => 'customer', 'sound' => 'request']);
        $this->get('/orders')->assertOk()->assertSee('sounds/request.mp3', false)->assertSee('notification-config', false);

        $customer = $this->customer();
        $this->actingAs($customer, 'customer')->post('/customer/shipments', $payload)
            ->assertSessionHasNoErrors()->assertRedirect()
            ->assertSessionHas('notification_feedback.sounds', ['request']);
        $booking = Booking::where('customer_id', $customer->id)->firstOrFail();
        $this->assertDatabaseHas('notifications', ['booking_id' => $booking->id, 'target' => 'pricing', 'sound' => 'request']);
        $this->get('/customer/dashboard')->assertOk()->assertSee('sounds/request.mp3', false);
    }

    public function test_pricing_uses_the_pricing_tone_for_sender_sales_and_customer_and_stops_pending_reminders(): void
    {
        $user = $this->staff(['manage_pricing']);
        $booking = $this->booking(['customer_id' => $this->customer()->id]);
        $agent = Agent::create(['name' => 'Pricing agent', 'type' => 'local', 'phone' => '770000002', 'email' => 'agent@example.com']);
        $this->actingAs($user)->post('/pricing/'.$booking->id.'/price', [
            'oceanFreight' => 900, 'originCharges' => 100, 'dthc' => 50,
            'warRiskSurcharge' => 0, 'otherCharges' => 0, 'transitDays' => 20,
            'validityDate' => now()->addDays(14)->toDateString(), 'agent_id' => $agent->id,
            'agentPurchaseUnitPrice' => 500, 'agentPurchaseQuantity' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect('/pricing')
            ->assertSessionHas('notification_feedback.sounds', ['pricing']);

        foreach (['sales', 'customer'] as $target) {
            $this->assertDatabaseHas('notifications', ['booking_id' => $booking->id, 'target' => $target, 'sound' => 'pricing']);
        }
        // A background poll must not consume the sender's flash before redirect.
        $this->getJson('/notifications/feed')->assertOk()->assertJsonPath('pending_pricing.count', 0);
        $this->get('/pricing')->assertOk()->assertSee('sounds/pricing.mp3', false);
        $this->assertNull(session('notification_feedback'));
    }

    public function test_invalid_submission_does_not_emit_success_sound(): void
    {
        $this->actingAs($this->staff(['manage_bookings']))->post('/booking', [])
            ->assertSessionHasErrors()->assertSessionMissing('notification_feedback');
        $this->assertDatabaseCount('notifications', 0);
    }
}

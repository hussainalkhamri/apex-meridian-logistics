<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\ShipmentTrack;
use App\Models\ShippingRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomerPortalRefinementTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pages_render_in_both_directions_with_operational_data(): void
    {
        $this->withoutExceptionHandling();
        $customer = Customer::create([
            'company' => 'شركة الاختبار للشحن', 'contact_name' => 'عميل تجريبي',
            'email' => 'portal-preview@example.test', 'phone' => '+967700000000',
            'password' => 'test-password', 'portal_enabled' => true,
        ]);
        $booking = Booking::create([
            'id' => 'BK-PORTAL-PREVIEW', 'customer_id' => $customer->id,
            'customer_company' => $customer->company, 'customer_email' => $customer->email,
            'customer_contact_name' => $customer->contact_name, 'customer_phone' => $customer->phone,
            'shipment_type' => 'FCL', 'container_type' => '40HC', 'container_qty' => 2,
            'goods_description' => 'Test cargo / بضائع اختبار', 'weight_kg' => 1200,
            'volume_cbm' => 18, 'pol' => 'Jebel Ali', 'pod' => 'Aden',
            'status' => 'priced', 'ocean_freight' => 1800, 'origin_charges' => 120,
            'pricing_currency' => 'USD', 'validity_date' => now()->addDays(2),
            'transit_days' => 8, 'priced_at' => now(), 'documents' => 'Commercial invoice / فاتورة تجارية',
            'customs_status' => 'documents_review', 'customs_reference' => 'CUS-2026-TEST',
            'customs_declaration_no' => 'DEC-TEST-001', 'customs_fees' => 75,
            'customs_notes' => 'Test case / حالة اختبار',
            'route_details' => ['mode' => 'FOB', 'services' => ['customs_requested' => true]],
        ]);
        $track = ShipmentTrack::create([
            'id' => 'TR-PORTAL-PREVIEW', 'booking_id' => $booking->id,
            'status' => 'In transit', 'progress' => 40, 'etd' => now()->subDays(2), 'eta' => now()->addDays(6),
        ]);
        $track->events()->create(['status' => 'Departed', 'location' => 'Jebel Ali', 'description' => 'Vessel departed', 'event_date' => now()->subDay()->format('Y-m-d H:i')]);
        Invoice::create(['id' => 'INV-PORTAL-PREVIEW', 'booking_id' => $booking->id, 'amount' => 1920, 'currency' => 'USD', 'status' => 'غير مدفوعة', 'issued_at' => now()->subDays(5), 'due_at' => now()->subDay()]);
        Notification::create(['id' => 'NT-PORTAL-PREVIEW', 'booking_id' => $booking->id, 'target' => 'customer', 'message' => 'عرض السعر جاهز للمراجعة / Quote ready for review']);
        ShippingRate::create(['shipping_type' => 'sea', 'service_type' => 'FCL', 'incoterm' => 'FOB', 'container_type' => '40HC', 'origin_country' => 'United Arab Emirates', 'origin_port' => 'Jebel Ali', 'destination_country' => 'Yemen', 'destination_port' => 'Aden', 'shipping_company' => 'Preview Carrier', 'transit_time' => 8, 'currency' => 'USD', 'price' => 1800, 'additional_fee' => 120, 'valid_from' => now()->subDay(), 'valid_to' => now()->addDays(15), 'is_active' => true, 'notes' => 'Synthetic rate for interface testing only. Subject to space and equipment availability.']);

        $this->actingAs($customer, 'customer');
        $paths = ['/customer/dashboard', '/customer/shipments', '/customer/shipments/create', '/customer/shipments/'.$booking->id, '/customer/tracking', '/customer/offers', '/customer/order-confirmation', '/customer/shipping-rates', '/customer/documents', '/customer/invoices', '/customer/customs', '/customer/notifications', '/customer/profile'];
        foreach (['ar' => 'rtl', 'en' => 'ltr'] as $locale => $direction) {
            foreach ($paths as $path) {
                $response = $this->get($path.'?locale='.$locale);
                $response->assertOk()->assertSee('dir="'.$direction.'"', false)->assertSee('customer-content')->assertDontSee('portal.attention');
                $this->exportPreview($path, $locale, $response->getContent());
            }
        }
    }

    public function test_empty_portal_and_public_auth_pages_render(): void
    {
        $customer = Customer::create(['company' => 'Empty Account', 'email' => 'empty@example.test', 'password' => 'test-password', 'portal_enabled' => true]);
        $this->actingAs($customer, 'customer')->get('/customer/dashboard')->assertOk()->assertSee(__('portal.no_action'));
        auth('customer')->logout();
        foreach (['ar', 'en'] as $locale) {
            foreach (['/customer/login', '/customer/register', '/customer/pending-approval'] as $path) {
                $response = $this->get($path.'?locale='.$locale);
                $response->assertOk();
                $this->exportPreview($path, $locale, $response->getContent());
            }
        }
    }

    private function exportPreview(string $path, string $locale, string $html): void
    {
        if (!getenv('PORTAL_PREVIEW_EXPORT')) return;
        $directory = storage_path('app/portal-preview/'.$locale);
        if (!is_dir($directory)) mkdir($directory, 0777, true);
        $origin = rtrim(URL::to('/'), '/');
        $html = str_replace($origin, 'http://127.0.0.1:8012', $html);
        // This is a synthetic read-only preview, never a session in the operational DB.
        $html = str_replace('</body>', '<script>window.addEventListener("submit",function(e){if(!e.defaultPrevented && e.target.method.toLowerCase()!=="get"){e.preventDefault();alert("Read-only preview: no data was submitted.")}})</script></body>', $html);
        file_put_contents($directory.'/'.rawurlencode($path).'.html', $html);
    }
}

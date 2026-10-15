<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_show_the_new_artwork_and_separate_cooperation_logos(): void
    {
        foreach (['ar', 'en'] as $locale) {
            $home = $this->get('/?locale=' . $locale)->assertOk();
            $home->assertSeeText(config('company.name.' . $locale))
                ->assertSee('images/brand/logistics.jpg', false)
                ->assertSee('company-partners-title', false)
                ->assertDontSee('images/hero-ship.jpg', false)
                ->assertDontSee('images/logo1.png', false);

            foreach (config('company.partners') as $partner) {
                $home->assertSee($partner['logo'], false);
                $this->assertFileExists(public_path($partner['logo']));
            }

            $contact = $this->get('/contact?locale=' . $locale)->assertOk();
            foreach ([...config('company.phones'), config('company.landline')] as $phone) {
                $contact->assertSee('tel:' . $phone['tel'], false)->assertSeeText($phone['display']);
            }
            foreach (config('company.emails') as $email) {
                $contact->assertSee('mailto:' . $email, false);
            }
            $contact->assertDontSee('info@ibrahimalhadi.com')->assertDontSee('776722211');
        }
    }

    public function test_quotes_invoices_and_both_voucher_types_use_the_same_identity(): void
    {
        $booking = new Booking([
            'id' => 'BK-BRAND-PREVIEW', 'customer_company' => 'Example customer',
            'customer_contact_name' => 'Example contact', 'shipment_type' => 'FCL',
            'container_type' => '40HC', 'container_qty' => 1,
            'pol' => 'Alexandria', 'pod' => 'Aden', 'goods_description' => 'General cargo',
            'weight_kg' => 1200, 'volume_cbm' => 10, 'ocean_freight' => 1000,
            'status' => 'sent', 'priced_at' => now(),
        ]);
        $invoice = new Invoice([
            'id' => 'INV-BRAND-PREVIEW', 'amount' => 1000, 'currency' => 'USD',
            'issued_at' => now(), 'due_at' => now()->addDays(7),
        ]);
        $invoice->setRelation('vouchers', collect());
        $invoice->setRelation('booking', $booking);
        $voucher = new Voucher([
            'id' => 'V-BRAND-PREVIEW', 'type' => 'receipt', 'date' => now(),
            'amount' => 1000, 'payee_payer' => 'Example customer',
            'payment_method' => 'Cash', 'description' => 'Freight payment',
        ]);
        $voucher->setRelation('invoice', null);
        $voucher->setRelation('booking', $booking);

        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);
            foreach (['quote-document', 'quote-document-modern', 'invoice-document', 'voucher-document'] as $template) {
                $html = view($template, compact('booking', 'invoice', 'voucher'))->render();
                $this->assertDocumentIdentity($html, $locale);
            }
            $voucher->type = 'payment';
            $this->assertDocumentIdentity(view('voucher-document', compact('voucher'))->render(), $locale);
            $voucher->type = 'receipt';
        }
    }

    private function assertDocumentIdentity(string $html, string $locale): void
    {
        $this->assertStringContainsString(config('company.name.' . $locale), $html);
        $this->assertStringContainsString(config('company.letterhead_logo'), $html);
        $this->assertStringNotContainsString('700123456789003', $html);
        $this->assertStringNotContainsString('776722211', $html);
        $this->assertStringNotContainsString('images/logo1.png', $html);
        foreach (config('company.emails') as $email) {
            $this->assertStringContainsString($email, $html);
        }
        preg_match('/<header class="company-letterhead">(.*?)<\/header>/s', $html, $header);
        $this->assertNotEmpty($header);
        foreach (config('company.partners') as $partner) {
            $this->assertStringContainsString($partner['logo'], $html);
            $this->assertStringNotContainsString($partner['logo'], $header[1]);
        }
    }
}

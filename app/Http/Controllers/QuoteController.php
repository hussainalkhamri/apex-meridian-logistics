<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        return redirect()
            ->route('customer.shipments.create', $request->only(['repeat_id']))
            ->with('success', 'استخدم نموذج طلب الشحنة الكامل لإرسال طلب التسعير.');
    }

    public function calculate(Request $request)
    {
        return redirect()
            ->route('customer.shipments.create')
            ->with('success', 'تم توحيد طلب السعر مع نموذج طلب الشحنة الكامل.');
    }

    public function submit(Request $request)
    {
        return redirect()
            ->route('customer.shipments.create')
            ->with('success', 'أرسل الطلب من نموذج الشحنة الكامل حتى تصل كل البيانات لقسم التسعير.');
    }

    public function submitUnderDevelopment()
    {
        return redirect()->route('quote.index');
    }

    public function show(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        abort_unless($this->canViewQuoteDocument($booking), 403);

        if (! $booking->ocean_freight) {
            abort(404, 'هذا الطلب لم يتم تسعيره بعد.');
        }

        if ($request->query('template') === 'modern') {
            return view('quote-document-modern', compact('booking'));
        }

        return view('quote-document', compact('booking'));
    }

    public function draftMbl($id)
    {
        $booking = Booking::findOrFail($id);
        abort_unless($this->canViewQuoteDocument($booking), 403);

        return view('draft-mbl', compact('booking'));
    }

    private function canViewQuoteDocument(Booking $booking): bool
    {
        $user = auth('web')->user();

        if ($user) {
            return collect([
                'manage_bookings',
                'manage_pricing',
                'manage_sales',
                'manage_carriers',
                'manage_customs',
                'manage_accounts',
            ])->contains(fn (string $permission) => $user->hasPermission($permission));
        }

        $customer = auth('customer')->user();

        return $customer !== null && (int) $booking->customer_id === (int) $customer->id;
    }

}

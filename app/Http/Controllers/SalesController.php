<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\ShipmentEvent;
use App\Models\ShipmentTrack;
use App\Support\BookingFilters;
use App\Support\SystemId;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $filters = BookingFilters::fromRequest($request);

        $queueQuery = Booking::whereIn('status', ['priced', 'sent', 'customer_approved', 'customer_rejected']);
        BookingFilters::apply($queueQuery, $filters);
        $queue = $queueQuery->latest()->get();

        $closedQuery = Booking::where('status', 'approved');
        BookingFilters::apply($closedQuery, $filters);
        $closed = $closedQuery->latest()->get();

        $rejectedQuery = Booking::where('status', 'rejected');
        BookingFilters::apply($rejectedQuery, $filters);
        $rejected = $rejectedQuery->latest()->get();

        $filteredBookingsQuery = Booking::query();
        BookingFilters::apply($filteredBookingsQuery, $filters);

        $totalRevenue = 0;
        foreach ($closed as $booking) {
            $totalRevenue += $booking->pricingTotal();
        }

        $totalBookings = $filteredBookingsQuery->count();
        $conversion = $totalBookings > 0 ? round(($closed->count() / $totalBookings) * 100) : 0;

        return view('sales', compact('queue', 'closed', 'rejected', 'totalRevenue', 'conversion', 'filters'));
    }

    public function send($id)
    {
        $booking = Booking::findOrFail($id);
        $old = $booking->only(['status', 'customer_decision', 'customer_decision_note', 'customer_decided_at']);
        $booking->update([
            'status' => 'sent',
            'customer_decision' => null,
            'customer_decision_note' => null,
            'customer_decided_at' => null,
        ]);

        AuditLog::logAction('SEND_QUOTE', 'bookings', $booking->id, $old, [
            'status' => $booking->status,
        ]);

        $this->createNotification([
            'message' => "تم إرسال عرض السعر للعميل ({$id})",
            'sound' => 'pricing',
            'booking_id' => $id,
            'read' => false,
            'target' => 'general',
        ]);

        $this->createNotification([
            'message' => "وصل عرض السعر الرسمي للطلب {$id}. يرجى المراجعة والموافقة أو الرفض.",
            'sound' => 'pricing',
            'booking_id' => $id,
            'read' => false,
            'target' => 'customer',
        ]);

        return redirect()->route('sales.index')->with('success', "تم إرسال العرض للعميل {$id}");
    }

    public function approve(Request $request, $id)
    {
        $data = $request->validate([
            'shipper' => 'required|string',
            'consignee' => 'required|string',
            'notify_party' => 'required|string',
        ]);

        $result = DB::transaction(function () use ($id, $request, $data) {
            $booking = Booking::whereKey($id)->lockForUpdate()->firstOrFail();
            $now = Carbon::now();

            if (in_array($booking->status, ['approved', 'booked'], true)) {
                $invoice = $this->ensureFreightInvoice($booking, $now);
                $track = $this->ensureShipmentTrack($booking, $now);
                $this->ensureInitialShipmentEvent($track, $booking, $now);

                return [
                    'state' => 'already_approved',
                    'invoice_id' => $invoice->id,
                    'tracking_id' => $track->id,
                ];
            }

            if (! in_array($booking->status, ['priced', 'sent', 'customer_approved'], true)) {
                return ['state' => 'invalid_status'];
            }

            $old = $booking->only(['status', 'sales_notes', 'shipper', 'consignee', 'notify_party']);
            $booking->update([
                'status' => 'approved',
                'sales_notes' => $request->input('note'),
                'shipper' => $data['shipper'],
                'consignee' => $data['consignee'],
                'notify_party' => $data['notify_party'],
                'decided_at' => $now,
            ]);

            $invoice = $this->ensureFreightInvoice($booking, $now);
            $track = $this->ensureShipmentTrack($booking, $now);
            $this->ensureInitialShipmentEvent($track, $booking, $now);

            AuditLog::logAction('APPROVE', 'bookings', $booking->id, $old, [
                'status' => $booking->status,
                'invoice_id' => $invoice->id,
                'tracking_id' => $track->id,
                'invoice_amount' => $booking->pricingTotal(),
            ]);

            $this->createNotification([
                'message' => "تم اعتماد الطلب {$id} وتوليد الفاتورة والملف اللوجستي للعميل",
                'booking_id' => $id,
                'read' => false,
                'target' => 'accounts',
            ]);

            $this->createNotification([
                'message' => "طلب {$id} جاهز لحجز الناقل وإدخال بيانات السفينة",
                'booking_id' => $id,
                'read' => false,
                'target' => 'tracking',
            ]);

            $this->createNotification([
                'message' => "الطلب {$id} جاهز لمتابعة ملف التخليص الجمركي والمستندات",
                'booking_id' => $id,
                'read' => false,
                'target' => 'customs',
            ]);

            return [
                'state' => 'approved',
                'invoice_id' => $invoice->id,
                'tracking_id' => $track->id,
            ];
        }, 3);

        if ($result['state'] === 'already_approved') {
            return redirect()->route('sales.index')->with('success', "الطلب {$id} معتمد مسبقاً ولن يتم إنشاء فاتورة أو تتبع مكرر.");
        }

        if ($result['state'] === 'invalid_status') {
            return redirect()->route('sales.index')->with('error', "لا يمكن اعتماد الطلب {$id} في حالته الحالية.");
        }

        return redirect()->route('sales.index')->with('success', "تم اعتماد الطلب {$id} وتوليد الفاتورة والملف اللوجستي بنجاح");
    }

    public function reject(Request $request, $id)
    {
        $booking = Booking::findOrFail($id);
        $old = $booking->only(['status', 'sales_notes']);
        $reason = $request->input('note');

        $booking->update([
            'status' => 'needs_pricing',
            'sales_notes' => $reason,
            'decided_at' => Carbon::now(),
        ]);

        AuditLog::logAction('REJECT', 'bookings', $booking->id, $old, [
            'status' => $booking->status,
            'reason' => $reason,
        ]);

        $this->createNotification([
            'message' => "المبيعات رفضت الطلب {$id}. السبب: " . ($reason ?: 'لم يتم تحديد سبب') . " - يرجى تعديل السعر وإعادة الإحالة.",
            'booking_id' => $id,
            'read' => false,
            'target' => 'pricing',
        ]);

        $this->createNotification([
            'message' => "تم رفض الطلب {$id} من المبيعات وأحيل لإعادة التسعير",
            'booking_id' => $id,
            'read' => false,
            'target' => 'general',
        ]);

        return redirect()->route('sales.index')->with('error', "تم رفض الطلب {$id} وإحالته لقسم التسعير");
    }

    private function ensureFreightInvoice(Booking $booking, Carbon $now): Invoice
    {
        $invoice = Invoice::query()
            ->where('booking_id', $booking->id)
            ->where(function ($query) {
                $query->where('invoice_type', 'freight')
                    ->orWhereNull('invoice_type');
            })
            ->first();

        if ($invoice) {
            return $invoice;
        }

        return Invoice::create([
            'id' => SystemId::make(Invoice::class, 'INV'),
            'booking_id' => $booking->id,
            'invoice_type' => 'freight',
            'amount' => $booking->pricingTotal(),
            'currency' => $booking->pricingCurrency(),
            'status' => 'غير مدفوعة',
            'issued_at' => $now,
            'due_at' => $now->copy()->addDays(14),
        ]);
    }

    private function ensureShipmentTrack(Booking $booking, Carbon $now): ShipmentTrack
    {
        $track = ShipmentTrack::where('booking_id', $booking->id)->first();

        if (! $track) {
            return ShipmentTrack::create([
                'id' => SystemId::make(ShipmentTrack::class, 'BHRL'),
                'booking_id' => $booking->id,
                'status' => 'تم الحجز',
                'progress' => 10,
            ]);
        }

        $track->fill([
            'status' => $track->status ?: 'تم الحجز',
            'progress' => max((int) $track->progress, 10),
        ])->save();

        return $track;
    }

    private function ensureInitialShipmentEvent(ShipmentTrack $track, Booking $booking, Carbon $now): ShipmentEvent
    {
        $event = ShipmentEvent::firstOrCreate(
            [
                'shipment_track_id' => $track->id,
                'description' => 'تم استلام وتأكيد طلب الشحن وإصدار الفاتورة',
            ],
            [
                'event_date' => $now->format('Y-m-d H:i'),
                'location' => $booking->pol,
                'status' => 'تم الحجز',
            ]
        );

        $event->forceFill([
            'source_name' => $event->source_name ?: (auth('web')->user()?->full_name ?? 'Sales Desk'),
            'source_type' => $event->source_type ?: 'sales',
        ])->save();

        return $event;
    }
}

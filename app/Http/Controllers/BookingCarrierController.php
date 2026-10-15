<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\AuditLog;
use App\Models\ShipmentTrack;
use App\Models\ShipmentEvent;
use App\Models\Notification;
use App\Support\BookingDetailPayload;
use App\Support\CarrierVesselOptions;
use App\Support\SystemId;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BookingCarrierController extends Controller
{
    public function index()
    {
        $queue = Booking::where('status', 'approved')->latest()->get();
        $booked = Booking::where('status', 'booked')->latest()->get();
        $carrierVesselOptions = CarrierVesselOptions::all();

        return view('bookings', compact('queue', 'booked', 'carrierVesselOptions'));
    }

    public function show($id)
    {
        $booking = Booking::with(['agent', 'invoices', 'shipmentTrack'])
            ->whereIn('status', ['approved', 'booked'])
            ->findOrFail($id);

        return response()->json(BookingDetailPayload::fromBooking($booking));
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'vessel' => 'required',
            'voyage' => 'required',
            'bookingRef' => 'required',
            'etd' => 'required|date',
            'eta' => 'required|date',
            'shipper' => 'nullable|string',
            'consignee' => 'nullable|string',
            'notify_party' => 'nullable|string',
            'bill_type' => 'nullable|string|max:255',
            'type_of_move' => 'nullable|string|max:255',
            'job_no' => 'nullable|string|max:255',
            'overseas_agent' => 'nullable|string|max:255',
            'place_of_delivery' => 'nullable|string|max:255',
            'quantity_packages' => 'nullable|string|max:255',
            'measurement_cbm' => 'nullable|numeric',
            'container_seal_no' => 'nullable|string',
            'custom_notes' => 'nullable|string',
        ]);

        $booking = Booking::whereIn('status', ['approved', 'booked'])->findOrFail($id);
        $wasBooked = $booking->status === 'booked';
        $old = $booking->only([
            'status',
            'carrier_vessel',
            'carrier_voyage',
            'carrier_booking_ref',
            'carrier_etd',
            'carrier_eta',
            'shipper',
            'consignee',
            'notify_party',
            'bill_type',
            'type_of_move',
            'job_no',
            'overseas_agent',
            'place_of_delivery',
            'quantity_packages',
            'measurement_cbm',
            'container_seal_no',
            'custom_notes',
        ]);
        $vessel = $request->input('vessel');
        $voyage = $request->input('voyage');
        $bookingRef = $request->input('bookingRef');
        $etd = $request->input('etd');
        $eta = $request->input('eta');
        $shippingUpdates = $this->shippingUpdates($request, $booking);

        $booking->update(array_merge([
            'status' => 'booked',
            'carrier_vessel' => $vessel,
            'carrier_voyage' => $voyage,
            'carrier_booking_ref' => $bookingRef,
            'carrier_etd' => $etd,
            'carrier_eta' => $eta,
            'carrier_booked_by' => $request->input('bookedBy') ?: 'قسم التسعير',
            'carrier_booked_at' => $booking->carrier_booked_at ?: Carbon::now(),
        ], $shippingUpdates));

        AuditLog::logAction('CARRIER_BOOKING', 'bookings', $booking->id, $old, [
            'status' => $booking->status,
            'carrier_vessel' => $booking->carrier_vessel,
            'carrier_voyage' => $booking->carrier_voyage,
            'carrier_booking_ref' => $booking->carrier_booking_ref,
        ]);

        $track = ShipmentTrack::where('booking_id', $booking->id)->first();

        if (! $track) {
            $track = ShipmentTrack::create([
                'id' => SystemId::make(ShipmentTrack::class, 'BHRL'),
                'booking_id' => $booking->id,
                'status' => 'تم الحجز',
                'progress' => 10,
            ]);
        }

        if ($track) {
            $track->update([
                'etd' => $etd,
                'eta' => $eta,
                'status' => 'تم الحجز',
                'progress' => 15
            ]);

            // Add Event if it doesn't already exist for this vessel (to avoid duplicates when editing)
            $exists = ShipmentEvent::where('shipment_track_id', $track->id)
                ->where('description', 'LIKE', '%تم حجز الحاوية%')
                ->exists();

            if (!$exists) {
                $event = ShipmentEvent::create([
                    'shipment_track_id' => $track->id,
                    'event_date' => Carbon::now()->format('Y-m-d H:i'),
                    'location' => $booking->pol,
                    'description' => "تم حجز الحاوية وتأكيد موعد الإبحار على السفينة {$vessel} (رحلة {$voyage}) رقم حجز الناقل {$bookingRef}",
                    'status' => 'تم الحجز'
                ]);

                $event->forceFill([
                    'source_name' => auth('web')->user()?->full_name ?? 'Carrier Booking Desk',
                    'source_type' => 'carrier_booking',
                ])->save();
            }
        }

        if (! $wasBooked) {
            $this->createNotification([
                'message' => "تم تأكيد حجز الطلب {$id} مع الخط الملاحي",
                'booking_id' => $id,
                'read' => false,
                'target' => 'tracking'
            ]);
        }

        return redirect()->back()->with('success', "تم حفظ تفاصيل العمليات بنجاح للطلب {$id}");
    }

    private function shippingUpdates(Request $request, Booking $booking): array
    {
        $updates = [];

        foreach ([
            'shipper',
            'consignee',
            'notify_party',
            'job_no',
            'overseas_agent',
            'place_of_delivery',
            'quantity_packages',
            'container_seal_no',
            'custom_notes',
        ] as $field) {
            if ($request->exists($field)) {
                $updates[$field] = $request->input($field);
            }
        }

        $updates['bill_type'] = $request->filled('bill_type')
            ? $request->input('bill_type')
            : ($booking->bill_type ?: 'TELEX RELEASE');

        $updates['type_of_move'] = $request->filled('type_of_move')
            ? $request->input('type_of_move')
            : ($booking->type_of_move ?: 'CY-CY');

        if ($request->exists('measurement_cbm')) {
            $updates['measurement_cbm'] = $request->filled('measurement_cbm')
                ? (float) $request->input('measurement_cbm')
                : null;
        }

        return $updates;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Port;
use App\Models\ShippingRate;
use App\Models\ShipmentTrack;
use App\Support\BookingStatus;
use App\Support\PortCatalog;
use App\Support\SystemId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class CustomerPortalController extends Controller
{
    private const DEFAULT_AIR_VOLUMETRIC_DIVISOR = 6000;

    public function dashboard()
    {
        $customer = $this->customer();
        $bookings = $this->customerBookings($customer)
            ->with(['shipmentTrack.events', 'invoices'])
            ->latest()
            ->get();
        $notifications = $this->customerUnreadNotifications($customer)->take(8)->get();
        $invoices = $bookings->flatMap->invoices;
        $openQuotes = $bookings->whereIn('status', ['priced', 'sent'])->take(4)->values();
        $documents = $bookings
            ->filter(fn (Booking $booking) => filled($booking->documents) || filled($booking->attachments))
            ->take(5)
            ->values();
        $activeCustoms = $bookings
            ->filter(fn (Booking $booking) => $this->hasCustomsActivity($booking))
            ->values();

        $stats = [
            'total' => $bookings->count(),
            'open_quotes' => $bookings->whereIn('status', ['priced', 'sent'])->count(),
            'in_progress' => $bookings->whereIn('status', ['customer_approved', 'approved', 'booked'])->count(),
            'customs' => $activeCustoms->count(),
            'documents' => $documents->count(),
            'invoices' => $invoices->count(),
            'unpaid_invoices' => $invoices->filter(fn ($invoice) => $invoice->status !== 'مدفوعة')->count(),
            'balance_due' => $invoices
                ->filter(fn ($invoice) => $invoice->status !== 'مدفوعة')
                ->sum('amount'),
            'balance_due_label' => Booking::formatMoneyTotalsByCurrency(
                $invoices->filter(fn ($invoice) => $invoice->status !== 'مدفوعة'),
                fn (Invoice $invoice) => $invoice->amount,
                fn (Invoice $invoice) => $invoice->effectiveCurrency()
            ),
        ];

        $activeCustoms = $activeCustoms->take(5)->values();

        return view('customer.dashboard', compact(
            'customer',
            'bookings',
            'notifications',
            'stats',
            'openQuotes',
            'documents',
            'activeCustoms',
            'invoices',
        ));
    }

    public function shipments(Request $request)
    {
        $customer = $this->customer();
        $allBookings = $this->customerBookings($customer)
            ->with(['shipmentTrack.events', 'invoices'])
            ->latest()
            ->get();
        $notifications = $this->customerUnreadNotifications($customer)->take(8)->get();
        $hasFilterQuery = $request->has('q')
            || $request->has('status')
            || $request->has('type')
            || $request->has('date_from')
            || $request->has('date_to');

        if ($request->boolean('reset')) {
            $request->session()->forget('customer_shipments_filters');
        }

        $filters = array_merge([
            'q' => '',
            'status' => 'all',
            'type' => 'all',
            'date_from' => '',
            'date_to' => '',
        ], $request->boolean('reset') ? [] : $request->session()->get('customer_shipments_filters', []));

        if ($hasFilterQuery) {
            $filters = [
                'q' => trim((string) $request->query('q', '')),
                'status' => (string) $request->query('status', 'all'),
                'type' => (string) $request->query('type', 'all'),
                'date_from' => (string) $request->query('date_from', ''),
                'date_to' => (string) $request->query('date_to', ''),
            ];

            $request->session()->put('customer_shipments_filters', $filters);
        }

        $statusFilter = $filters['status'];
        $shipmentTypeFilter = $filters['type'];
        $dateFrom = $filters['date_from'];
        $dateTo = $filters['date_to'];
        $search = trim((string) $filters['q']);
        $statusGroups = BookingStatus::GROUPS;
        $statusOptions = BookingStatus::optionGroups();
        $statusLabels = $this->bookingStatusLabels();
        $typeOptions = $allBookings
            ->pluck('shipment_type')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        foreach ($statusLabels as $key => $statusLabel) {
            $statusOptions[$key] = $statusLabel['label'];
        }

        if (! array_key_exists($statusFilter, $statusOptions)) {
            $statusFilter = 'all';
            $filters['status'] = 'all';
        }

        if ($shipmentTypeFilter !== 'all' && ! $typeOptions->contains($shipmentTypeFilter)) {
            $shipmentTypeFilter = 'all';
            $filters['type'] = 'all';
        }

        $bookings = $allBookings
            ->when($search !== '', function ($collection) use ($search) {
                $needle = strtolower($search);

                return $collection->filter(function (Booking $booking) use ($needle) {
                    $haystack = strtolower(implode(' ', array_filter([
                        $booking->id,
                        $booking->pol,
                        $booking->pod,
                        $booking->shipment_type,
                        $booking->container_type,
                        $booking->carrier_booking_ref,
                        $booking->goods_description,
                    ])));

                    return str_contains($haystack, $needle);
                });
            })
            ->when($statusFilter !== 'all', function ($collection) use ($statusFilter, $statusGroups) {
                if ($statusFilter === 'customs') {
                    return $collection->filter(fn (Booking $booking) => $this->hasCustomsActivity($booking));
                }

                if (isset($statusGroups[$statusFilter])) {
                    return $collection->whereIn('status', $statusGroups[$statusFilter]);
                }

                return $collection->where('status', $statusFilter);
            })
            ->when($shipmentTypeFilter !== 'all', fn ($collection) => $collection->where('shipment_type', $shipmentTypeFilter))
            ->when($dateFrom !== '', function ($collection) use ($dateFrom) {
                return $collection->filter(fn (Booking $booking) => $booking->created_at && $booking->created_at->toDateString() >= $dateFrom);
            })
            ->when($dateTo !== '', function ($collection) use ($dateTo) {
                return $collection->filter(fn (Booking $booking) => $booking->created_at && $booking->created_at->toDateString() <= $dateTo);
            })
            ->values();

        $stats = [
            'total' => $allBookings->count(),
            'waiting' => $allBookings->whereIn('status', ['new', 'needs_pricing', 'priced'])->count(),
            'offers' => $allBookings->whereIn('status', ['priced', 'sent'])->count(),
            'active' => $allBookings->whereIn('status', ['customer_approved', 'approved', 'booked'])->count(),
            'customs' => $allBookings->filter(fn (Booking $booking) => $this->hasCustomsActivity($booking))->count(),
        ];

        $totalFiltered = $bookings->count();
        $page = min(max(1, $request->integer('page', 1)), max(1, (int) ceil($totalFiltered / 15)));
        $bookings = new \Illuminate\Pagination\LengthAwarePaginator(
            $bookings->slice(($page - 1) * 15, 15)->values(),
            $totalFiltered,
            15,
            $page,
            ['path' => $request->url(), 'query' => $request->except('page', 'reset')]
        );

        return view('customer.shipments.index', [
            'customer' => $customer,
            'bookings' => $bookings,
            'notifications' => $notifications,
            'stats' => $stats,
            'statusLabels' => $statusLabels,
            'statusOptions' => $statusOptions,
            'typeOptions' => $typeOptions,
            'filters' => $filters,
        ]);
    }

    public function tracking(Request $request)
    {
        $customer = $this->customer();
        $q = trim((string) $request->query('q', ''));
        $notifications = $this->customerUnreadNotifications($customer)->take(8)->get();
        $trackingQuery = $this->customerTrackingQuery($customer)
            ->with(['booking', 'events']);

        $tracks = (clone $trackingQuery)
            ->latest()
            ->get();

        $found = null;

        if ($q !== '') {
            $found = (clone $trackingQuery)
                ->where(function (Builder $query) use ($q) {
                    $query->where('id', $q)
                        ->orWhere('booking_id', $q)
                        ->orWhereHas('booking', function (Builder $bookingQuery) use ($q) {
                            $bookingQuery
                                ->where('id', $q)
                                ->orWhere('carrier_booking_ref', 'LIKE', "%{$q}%");
                        });
                })
                ->first();
        }

        if (! $found && $q === '') {
            $found = $tracks->first();
        }

        $stats = [
            'total' => $tracks->count(),
            'active' => $tracks->where('progress', '<', 100)->count(),
            'delivered' => $tracks->filter(fn (ShipmentTrack $track) => $track->progress >= 100 || $track->status === 'تم التسليم')->count(),
            'average_progress' => $tracks->count() ? round($tracks->avg('progress')) : 0,
        ];

        return view('customer.tracking', compact('customer', 'notifications', 'tracks', 'found', 'q', 'stats'));
    }

    public function shippingRates(Request $request)
    {
        $customer = $this->customer();
        $query = ShippingRate::query()
            ->where('is_active', true)
            ->latest('updated_at');

        if ($request->filled('search')) {
            $term = trim((string) $request->query('search'));
            $query->where(function (Builder $searchQuery) use ($term) {
                $searchQuery
                    ->where('shipping_company', 'like', "%{$term}%")
                    ->orWhere('origin_country', 'like', "%{$term}%")
                    ->orWhere('origin_port', 'like', "%{$term}%")
                    ->orWhere('destination_country', 'like', "%{$term}%")
                    ->orWhere('destination_port', 'like', "%{$term}%")
                    ->orWhere('incoterm', 'like', "%{$term}%")
                    ->orWhere('container_type', 'like', "%{$term}%")
                    ->orWhere('service_type', 'like', "%{$term}%")
                    ->orWhere('shipping_type', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        foreach (['shipping_type', 'service_type', 'incoterm', 'container_type', 'origin_country', 'origin_port', 'destination_country', 'destination_port', 'shipping_company'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->query($field));
            }
        }

        $shippingRates = $query->paginate(12)->withQueryString();
        $activeRatesQuery = ShippingRate::query()->where('is_active', true);

        $filtersData = [
            'shipping_types' => (clone $activeRatesQuery)->distinct()->pluck('shipping_type')->filter()->sort()->values(),
            'service_types' => (clone $activeRatesQuery)->distinct()->pluck('service_type')->filter()->sort()->values(),
            'incoterms' => (clone $activeRatesQuery)->distinct()->pluck('incoterm')->filter()->sort()->values(),
            'container_types' => (clone $activeRatesQuery)->distinct()->pluck('container_type')->filter()->sort()->values(),
            'origin_countries' => (clone $activeRatesQuery)->distinct()->pluck('origin_country')->filter()->sort()->values(),
            'origin_ports' => (clone $activeRatesQuery)->distinct()->pluck('origin_port')->filter()->sort()->values(),
            'destination_countries' => (clone $activeRatesQuery)->distinct()->pluck('destination_country')->filter()->sort()->values(),
            'destination_ports' => (clone $activeRatesQuery)->distinct()->pluck('destination_port')->filter()->sort()->values(),
            'shipping_companies' => (clone $activeRatesQuery)->distinct()->pluck('shipping_company')->filter()->sort()->values(),
        ];

        $lowestRate = (clone $activeRatesQuery)->orderBy('price')->first();
        $routesCount = (clone $activeRatesQuery)
            ->get(['origin_port', 'destination_port'])
            ->map(fn (ShippingRate $rate) => $rate->origin_port . '|' . $rate->destination_port)
            ->unique()
            ->count();

        $stats = [
            'total' => (clone $activeRatesQuery)->count(),
            'routes' => $routesCount,
            'companies' => (clone $activeRatesQuery)->distinct()->count('shipping_company'),
            'lowest_price_label' => $lowestRate
                ? number_format((float) $lowestRate->price, 2) . ' ' . $lowestRate->currency
                : '—',
        ];

        return view('customer.shipping-rates', [
            'customer' => $customer,
            'shippingRates' => $shippingRates,
            'filtersData' => $filtersData,
            'stats' => $stats,
        ]);
    }

    public function offers()
    {
        $customer = $this->customer();
        $bookings = $this->customerBookings($customer)
            ->with(['invoices'])
            ->latest()
            ->get()
            ->filter(fn (Booking $booking) => $booking->pricingTotal() > 0 || in_array($booking->status, [
                'sent',
                'customer_approved',
                'customer_rejected',
                'approved',
                'booked',
            ], true))
            ->values();
        $notifications = $this->customerUnreadNotifications($customer)->take(8)->get();

        $stats = [
            'total' => $bookings->count(),
            'pending' => $bookings->whereIn('status', ['priced', 'sent'])->count(),
            'accepted' => $bookings->whereIn('status', ['customer_approved', 'approved', 'booked'])->count(),
            'rejected' => $bookings->where('status', 'customer_rejected')->count(),
            'value' => $bookings->sum(fn (Booking $booking) => $booking->pricingTotal()),
            'value_label' => Booking::formatMoneyTotalsByCurrency($bookings, fn (Booking $booking) => $booking->pricingTotal()),
        ];

        return view('customer.offers', [
            'customer' => $customer,
            'bookings' => $bookings,
            'notifications' => $notifications,
            'stats' => $stats,
            'statusLabels' => $this->bookingStatusLabels(),
        ]);
    }

    public function orderConfirmation()
    {
        $customer = $this->customer();
        $bookings = $this->customerBookings($customer)
            ->with(['invoices'])
            ->whereIn('status', [
                'priced',
                'sent',
                'customer_approved',
                'customer_rejected',
                'approved',
                'booked',
            ])
            ->latest('priced_at')
            ->latest()
            ->get()
            ->filter(fn (Booking $booking) => $booking->pricingTotal() > 0)
            ->values();

        $pending = $bookings->whereIn('status', ['priced', 'sent'])->values();
        $decided = $bookings->whereNotIn('status', ['priced', 'sent'])->values();
        $notifications = $this->customerUnreadNotifications($customer)->take(8)->get();

        $stats = [
            'total' => $bookings->count(),
            'pending' => $pending->count(),
            'accepted' => $bookings->whereIn('status', ['customer_approved', 'approved', 'booked'])->count(),
            'rejected' => $bookings->where('status', 'customer_rejected')->count(),
            'value' => $bookings->sum(fn (Booking $booking) => $booking->pricingTotal()),
            'value_label' => Booking::formatMoneyTotalsByCurrency($bookings, fn (Booking $booking) => $booking->pricingTotal()),
        ];

        return view('customer.order-confirmation', [
            'customer' => $customer,
            'bookings' => $bookings,
            'pending' => $pending,
            'decided' => $decided,
            'notifications' => $notifications,
            'stats' => $stats,
            'statusLabels' => $this->bookingStatusLabels(),
        ]);
    }

    public function documents()
    {
        $customer = $this->customer();
        $documentRows = $this->customerBookings($customer)
            ->with(['shipmentTrack'])
            ->latest()
            ->get()
            ->filter(fn (Booking $booking) => filled($booking->documents)
                || filled($booking->attachments)
                || filled($booking->customs_documents))
            ->map(fn (Booking $booking) => [
                'booking' => $booking,
                'attachments' => $this->bookingAttachments($booking),
                'customs_documents' => $this->bookingCustomsDocuments($booking),
            ])
            ->values();
        $notifications = $this->customerUnreadNotifications($customer)->take(8)->get();

        $stats = [
            'files' => $documentRows->count(),
            'attachments' => $documentRows->sum(fn (array $row) => count($row['attachments'])),
            'customs_documents' => $documentRows->sum(fn (array $row) => count($row['customs_documents'])),
            'with_notes' => $documentRows->filter(fn (array $row) => filled($row['booking']->documents))->count(),
        ];

        return view('customer.documents', [
            'customer' => $customer,
            'documentRows' => $documentRows,
            'notifications' => $notifications,
            'stats' => $stats,
            'statusLabels' => $this->bookingStatusLabels(),
        ]);
    }

    public function invoices()
    {
        $customer = $this->customer();
        $bookingIds = $this->customerBookings($customer)->pluck('id');
        $invoices = Invoice::query()
            ->with(['booking', 'vouchers'])
            ->whereIn('booking_id', $bookingIds)
            ->latest('issued_at')
            ->latest()
            ->get();
        $notifications = $this->customerUnreadNotifications($customer)->take(8)->get();

        $stats = [
            'total' => $invoices->count(),
            'amount' => $invoices->sum('amount'),
            'paid' => $invoices->sum(fn (Invoice $invoice) => $invoice->paid_amount),
            'remaining' => $invoices->sum(fn (Invoice $invoice) => $invoice->remaining_amount),
            'amount_label' => Booking::formatMoneyTotalsByCurrency(
                $invoices,
                fn (Invoice $invoice) => $invoice->amount,
                fn (Invoice $invoice) => $invoice->effectiveCurrency()
            ),
            'paid_label' => Booking::formatMoneyTotalsByCurrency(
                $invoices,
                fn (Invoice $invoice) => $invoice->paid_amount,
                fn (Invoice $invoice) => $invoice->effectiveCurrency()
            ),
            'remaining_label' => Booking::formatMoneyTotalsByCurrency(
                $invoices,
                fn (Invoice $invoice) => $invoice->remaining_amount,
                fn (Invoice $invoice) => $invoice->effectiveCurrency()
            ),
            'overdue' => $invoices->filter(fn (Invoice $invoice) => $invoice->due_at
                && $invoice->due_at->isPast()
                && $invoice->remaining_amount > 0)->count(),
        ];

        return view('customer.invoices', [
            'customer' => $customer,
            'invoices' => $invoices,
            'notifications' => $notifications,
            'stats' => $stats,
            'statusLabels' => $this->bookingStatusLabels(),
        ]);
    }

    public function customs()
    {
        $customer = $this->customer();
        $bookings = $this->customerBookings($customer)
            ->with(['shipmentTrack', 'invoices'])
            ->latest()
            ->get()
            ->filter(fn (Booking $booking) => $this->hasCustomsActivity($booking))
            ->values();
        $notifications = $this->customerUnreadNotifications($customer)->take(8)->get();

        $stats = [
            'total' => $bookings->count(),
            'pending' => $bookings->whereIn('customs_status', [null, 'pending', 'documents_review'])->count(),
            'inspection' => $bookings->where('customs_status', 'inspection')->count(),
            'cleared' => $bookings->where('customs_status', 'cleared')->count(),
            'fees' => $bookings->sum('customs_fees'),
            'fees_label' => Booking::formatMoneyTotalsByCurrency($bookings, fn (Booking $booking) => $booking->customs_fees ?? 0),
        ];

        return view('customer.customs', [
            'customer' => $customer,
            'bookings' => $bookings,
            'notifications' => $notifications,
            'stats' => $stats,
            'statusLabels' => $this->bookingStatusLabels(),
            'customsLabels' => $this->customsStatusLabels(),
        ]);
    }

    public function notifications(Request $request)
    {
        $customer = $this->customer();
        $status = $request->query('status', 'all');
        $status = in_array($status, ['all', 'unread', 'read'], true) ? $status : 'all';
        $search = trim((string) $request->query('q', ''));

        $query = $this->customerNotifications($customer);

        if ($status === 'unread') {
            $query->whereNotIn('id', $this->customerReadNotificationIdsQuery($customer));
        } elseif ($status === 'read') {
            $query->whereIn('id', $this->customerReadNotificationIdsQuery($customer));
        }

        if ($search !== '') {
            $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery
                    ->where('message', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('booking_id', 'like', "%{$search}%");
            });
        }

        $notifications = $query->latest()->paginate(30)->withQueryString();
        $readIds = DB::table('customer_notification_reads')
            ->where('customer_id', $customer->id)
            ->whereIn('notification_id', $notifications->pluck('id'))
            ->pluck('notification_id')
            ->all();

        $statusCounts = [
            'all' => $this->customerNotifications($customer)->count(),
            'unread' => $this->customerNotifications($customer)
                ->whereNotIn('id', $this->customerReadNotificationIdsQuery($customer))
                ->count(),
            'read' => $this->customerNotifications($customer)
                ->whereIn('id', $this->customerReadNotificationIdsQuery($customer))
                ->count(),
        ];

        return view('customer.notifications', [
            'customer' => $customer,
            'notifications' => $notifications,
            'readIds' => $readIds,
            'status' => $status,
            'search' => $search,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function profile()
    {
        $customer = $this->customer();

        return view('customer.profile', compact('customer'));
    }

    public function updateProfile(Request $request)
    {
        $customer = $this->customer();

        $data = $request->validate([
            'company' => ['required', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'company.required' => 'اسم الشركة مطلوب.',
            'email.required' => 'البريد الإلكتروني مطلوب لأنه يستخدم لتسجيل الدخول.',
            'email.email' => 'صيغة البريد الإلكتروني غير صحيحة.',
            'email.unique' => 'هذا البريد مستخدم من عميل آخر.',
            'avatar.image' => 'الملف يجب أن يكون صورة.',
            'avatar.mimes' => 'الصورة يجب أن تكون JPG أو PNG أو WEBP.',
            'avatar.max' => 'حجم الصورة يجب ألا يتجاوز 2MB.',
        ]);

        $oldValues = $customer->only(['company', 'contact_name', 'phone', 'email', 'avatar_path']);
        $oldAvatarPath = $customer->avatar_path;
        $profileData = [
            'company' => $data['company'],
            'contact_name' => $data['contact_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
        ];

        if ($request->hasFile('avatar')) {
            $profileData['avatar_path'] = $request->file('avatar')->store('customers/avatars', 'public');
        }

        $customer->update($profileData);

        if (($profileData['avatar_path'] ?? null) && $oldAvatarPath && $oldAvatarPath !== $profileData['avatar_path']) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        AuditLog::logAction('CUSTOMER_PROFILE_UPDATE', 'customers', (string) $customer->id, $oldValues, [
            'company' => $customer->company,
            'contact_name' => $customer->contact_name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'avatar_changed' => isset($profileData['avatar_path']),
        ]);

        return redirect()
            ->route('customer.profile')
            ->with('success', 'تم تحديث الملف الشخصي بنجاح.');
    }

    public function updatePassword(Request $request)
    {
        $customer = $this->customer();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'current_password.required' => 'كلمة السر الحالية مطلوبة.',
            'password.required' => 'كلمة السر الجديدة مطلوبة.',
            'password.confirmed' => 'تأكيد كلمة السر غير مطابق.',
        ]);

        if (! Hash::check($data['current_password'], (string) $customer->password)) {
            return back()->withErrors([
                'current_password' => 'كلمة السر الحالية غير صحيحة.',
            ])->onlyInput();
        }

        $customer->update([
            'password' => $data['password'],
        ]);

        AuditLog::logAction('CUSTOMER_PROFILE_PASSWORD_UPDATE', 'customers', (string) $customer->id, [], [
            'password_changed' => true,
        ]);

        return redirect()
            ->route('customer.profile')
            ->with('success', 'تم تغيير كلمة السر بنجاح.');
    }

    public function deleteAvatar()
    {
        $customer = $this->customer();
        $oldAvatarPath = $customer->avatar_path;

        if ($oldAvatarPath) {
            Storage::disk('public')->delete($oldAvatarPath);

            $customer->update([
                'avatar_path' => null,
            ]);

            AuditLog::logAction('CUSTOMER_PROFILE_AVATAR_DELETE', 'customers', (string) $customer->id, [
                'avatar_path' => $oldAvatarPath,
            ], [
                'avatar_path' => null,
            ]);
        }

        return redirect()
            ->route('customer.profile')
            ->with('success', 'تم حذف الصورة الشخصية.');
    }

    public function create(Request $request)
    {
        $customer = $this->customer();
        $repeatBooking = null;
        $shippingRate = null;
        $shippingRateSeed = null;

        if ($request->filled('repeat_id')) {
            $repeatBooking = $this->findCustomerBooking($customer, (string) $request->query('repeat_id'))->first();
        }

        if (! $repeatBooking && $request->filled('shipping_rate_id')) {
            $shippingRate = ShippingRate::query()
                ->where('is_active', true)
                ->find($request->query('shipping_rate_id'));
            $shippingRateSeed = $shippingRate ? $this->customerShippingRateSeed($shippingRate) : null;
        }

        [$portCountries, $portsForBooking] = $this->portSelectorData();
        [$airportCountriesForBooking, $airportsForBooking] = $this->airportSelectorData($portCountries);

        return view('customer.create', compact(
            'portCountries',
            'portsForBooking',
            'airportCountriesForBooking',
            'airportsForBooking',
            'repeatBooking',
            'shippingRate',
            'shippingRateSeed',
        ));
    }

    public function store(Request $request)
    {
        $customer = $this->customer();
        $shippingCategory = $this->normalizeShippingCategory($request->input('shipping_category', 'sea'));
        $routeMode = match ($shippingCategory) {
            'sea' => strtoupper((string) $request->input('route_mode')),
            'land' => 'LAND',
            'air' => 'AIR',
            'door_to_door' => 'DOOR_TO_DOOR',
            default => null,
        };
        $shipmentType = match ($shippingCategory) {
            'land' => 'LAND',
            'air' => 'AIR',
            'door_to_door' => 'DOOR_TO_DOOR',
            default => $request->input('shipmentType'),
        };

        $pol = match ($shippingCategory) {
            'air' => $this->airportCode($request->input('origin_airport')),
            'door_to_door' => trim((string) $request->input('pickup_address')),
            default => $request->input('pol'),
        };
        $pod = match ($shippingCategory) {
            'air' => $this->airportCode($request->input('destination_airport')),
            'door_to_door' => trim((string) $request->input('delivery_address')),
            default => $request->input('pod'),
        };

        $request->merge([
            'shipping_category' => $shippingCategory,
            'route_mode' => $routeMode,
            'shipmentType' => $shipmentType,
            'pol' => $pol,
            'pod' => $pod,
        ]);

        if ($shipmentType === 'DOOR_TO_DOOR' && ! $request->filled('weightKg')) {
            $metrics = $this->packageMetrics($this->extractPackages($request), $request);
            if ($metrics['gross_weight_kg'] > 0) {
                $request->merge(['weightKg' => $metrics['gross_weight_kg']]);
            }
        }

        $this->validateCustomerBookingRequest($request, [
            'shipping_category' => 'required|in:sea,land,air,door_to_door',
            'pol' => 'required|string|max:255',
            'pod' => 'required|string|max:255',
            'route_mode' => 'nullable|required_if:shipping_category,sea|in:FOB,EXW,LAND,AIR,DOOR_TO_DOOR',
            'origin_country' => 'nullable|required_unless:shipping_category,land|string|max:255',
            'destination_country' => 'nullable|required_unless:shipping_category,land|string|max:255',
            'origin_city' => 'nullable|required_if:route_mode,EXW,AIR,DOOR_TO_DOOR|string|max:255',
            'destination_city' => 'nullable|required_if:route_mode,EXW,AIR,DOOR_TO_DOOR|string|max:255',
            'shipmentType' => 'nullable|required_if:shipping_category,sea|in:FCL,LCL,Bulk,RoRo,LAND,AIR,DOOR_TO_DOOR',
            'containerType' => 'required_if:shipmentType,FCL|nullable|string|max:50',
            'containerQty' => 'required_if:shipmentType,FCL|nullable|integer|min:1',
            'containers' => 'nullable|array',
            'containers.*.type' => 'nullable|string|max:50',
            'containers.*.qty' => 'nullable|integer|min:1',
            'reefer_temperature' => 'nullable|string|max:50',
            'bulk_cargo_name' => 'nullable|string|max:255',
            'bulk_pieces' => 'nullable|required_if:shipmentType,Bulk|integer|min:1',
            'bulk_piece_weight' => 'nullable|required_if:shipmentType,Bulk|numeric|min:0.01',
            'bulk_total_weight' => 'nullable|numeric|min:0',
            'bulk_length' => 'nullable|required_if:shipmentType,Bulk|numeric|min:0.01',
            'bulk_width' => 'nullable|required_if:shipmentType,Bulk|numeric|min:0.01',
            'bulk_height' => 'nullable|required_if:shipmentType,Bulk|numeric|min:0.01',
            'bulk_cbm' => 'nullable|numeric|min:0',
            'bulk_packaging' => 'nullable|string|max:100',
            'bulk_lifting' => 'nullable|string|max:100',
            'bulk_stackable' => 'nullable|boolean',
            'roro_vehicle_type' => 'nullable|string|max:255',
            'roro_manufacturer' => 'nullable|string|max:255',
            'roro_model' => 'nullable|string|max:255',
            'roro_year' => 'nullable|integer|min:1900|max:2100',
            'roro_color' => 'nullable|string|max:100',
            'roro_vin' => 'nullable|string|max:100',
            'roro_chassis_number' => 'nullable|string|max:100',
            'roro_engine_number' => 'nullable|string|max:100',
            'roro_plate_number' => 'nullable|string|max:100',
            'roro_length' => 'nullable|numeric|min:0',
            'roro_width' => 'nullable|numeric|min:0',
            'roro_height' => 'nullable|numeric|min:0',
            'roro_weight' => 'nullable|numeric|min:0',
            'roro_fuel_level' => 'nullable|string|max:100',
            'roro_value' => 'nullable|numeric|min:0',
            'roro_running' => 'nullable|boolean',
            'roro_tow_required' => 'nullable|boolean',
            'roro_has_key' => 'nullable|boolean',
            'roro_has_items' => 'nullable|boolean',
            'goodsDescription' => 'required|string|max:1000',
            'weightKg' => 'required|numeric|min:0.01',
            'volumeCbm' => 'nullable|numeric|min:0',
            'package_dimension_unit' => 'nullable|in:cm,m',
            'land_carton_count' => 'nullable|integer|min:0',
            'documents' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array|max:6',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'notes' => 'nullable|string|max:2000',
            'dangerous' => 'nullable|boolean',
            'origin_country_code' => 'nullable|string|size:2',
            'destination_country_code' => 'nullable|string|size:2',
            'origin_airport' => 'nullable|required_if:shipping_category,air|string|max:10',
            'destination_airport' => 'nullable|required_if:shipping_category,air|string|max:10',
            'origin_airport_name' => 'nullable|string|max:255',
            'destination_airport_name' => 'nullable|string|max:255',
            'air_service_level' => 'nullable|in:standard,express,economy',
            'ready_date' => 'nullable|date',
            'air_volumetric_divisor' => 'nullable|integer|min:1',
            'origin_postal_code' => 'nullable|string|max:40',
            'destination_postal_code' => 'nullable|string|max:40',
            'pickup_address' => 'nullable|required_if:shipping_category,door_to_door|string|max:500',
            'pickup_contact_name' => 'nullable|string|max:255',
            'pickup_phone_country_code' => 'nullable|string|max:10',
            'pickup_phone' => 'nullable|string|max:50',
            'delivery_address' => 'nullable|required_if:shipping_category,door_to_door|string|max:500',
            'delivery_contact_name' => 'nullable|string|max:255',
            'delivery_phone_country_code' => 'nullable|string|max:10',
            'delivery_phone' => 'nullable|string|max:50',
            'main_transport_mode' => 'nullable|required_if:shipping_category,door_to_door|in:AUTO,AIR,SEA,LAND,MULTIMODAL',
            'pickup_ready_date' => 'nullable|date',
            'pickup_time_from' => 'nullable|date_format:H:i',
            'pickup_time_to' => 'nullable|date_format:H:i',
            'delivery_required_date' => 'nullable|date',
            'preferred_pickup_time' => 'nullable|in:morning,afternoon,evening,anytime',
            'delivery_notes' => 'nullable|string|max:1000',
            'incoterm' => 'nullable|string|max:20',
            'cargo_type' => 'nullable|in:general,dangerous,perishable,pharmaceutical,valuable,fragile,live_animals,temperature_controlled,oversized',
            'hs_code' => 'nullable|string|max:40',
            'cargo_items' => 'nullable|array|max:50',
            'cargo_items.*.name' => 'required_with:cargo_items|string|max:255',
            'cargo_items.*.hs_code' => 'nullable|string|max:40',
            'cargo_items.*.quantity' => 'required_with:cargo_items|numeric|min:0.01',
            'cargo_items.*.unit' => 'nullable|in:piece,vehicle,cbm,kg,ton,container',
            'cargo_items.*.weight_kg' => 'nullable|numeric|min:0',
            'cargo_items.*.volume_cbm' => 'nullable|numeric|min:0',
            'country_of_origin' => 'nullable|string|max:255',
            'net_weight_kg' => 'nullable|numeric|min:0',
            'package_type' => 'nullable|string|max:80',
            'declared_value' => 'nullable|numeric|min:0',
            'commercial_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'packages_count' => 'nullable|integer|min:1',
            'packages' => 'nullable|array',
            'packages.*.quantity' => 'nullable|integer|min:1',
            'packages.*.package_type' => 'nullable|string|max:80',
            'packages.*.length_cm' => 'nullable|numeric|min:0',
            'packages.*.width_cm' => 'nullable|numeric|min:0',
            'packages.*.height_cm' => 'nullable|numeric|min:0',
            'packages.*.weight_kg' => 'nullable|numeric|min:0',
            'direct_flight_preferred' => 'nullable|boolean',
            'insurance_required' => 'nullable|boolean',
            'customs_clearance_origin' => 'nullable|boolean',
            'customs_clearance_destination' => 'nullable|boolean',
            'export_customs_clearance' => 'nullable|boolean',
            'import_customs_clearance' => 'nullable|boolean',
            'pickup_required' => 'nullable|boolean',
            'delivery_required' => 'nullable|boolean',
            'dangerous_transport_regulation' => 'nullable|in:IATA,IMDG,ADR,MULTIMODAL',
            'dangerous_un_number' => 'nullable|string|max:40',
            'dangerous_iata_class' => 'nullable|string|max:40',
            'dangerous_imdg_class' => 'nullable|string|max:40',
            'dangerous_adr_class' => 'nullable|string|max:40',
            'dangerous_packing_group' => 'nullable|string|max:40',
            'dangerous_proper_shipping_name' => 'nullable|string|max:255',
            'dangerous_msds_available' => 'nullable|boolean',
            'customs_requested' => 'nullable|boolean',
            'door_delivery_requested' => 'nullable|boolean',
            'cargo_insurance_requested' => 'nullable|boolean',
        ]);

        $this->rememberCustomerRoutePorts($request);

        $bookingId = $this->makeBookingId();
        $serviceOptions = [
            'customs_requested' => $this->customerRequestedCustoms($request),
            'door_delivery_requested' => $request->boolean('door_delivery_requested') || $request->boolean('delivery_required') || $shippingCategory === 'door_to_door',
            'cargo_insurance_requested' => $request->boolean('cargo_insurance_requested') || $request->boolean('insurance_required'),
            'source' => 'customer_portal',
        ];
        $requestedServiceLabels = collect([
            $serviceOptions['customs_requested'] ? 'تخليص جمركي' : null,
            $serviceOptions['door_delivery_requested'] ? 'تسليم إلى الباب' : null,
            $serviceOptions['cargo_insurance_requested'] ? 'تأمين على الشحنة' : null,
        ])->filter()->values();
        $customerNotes = trim((string) $request->input('notes'));

        if ($requestedServiceLabels->isNotEmpty()) {
            $servicesNote = 'خدمات إضافية مطلوبة من العميل: ' . $requestedServiceLabels->implode('، ');
            $customerNotes = $customerNotes ? $customerNotes . PHP_EOL . $servicesNote : $servicesNote;
        }

        $routeDetails = $this->buildCustomerRouteDetails($request, $serviceOptions);
        $cargoDetails = $this->buildCustomerCargoDetails($request);
        $primaryContainer = $this->primaryCustomerFclContainer($cargoDetails, $request);

        $uploadedAttachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $uploadedAttachments[] = $file->store('bookings/attachments', 'public');
                }
            }
        }

        $booking = Booking::create([
            'id' => $bookingId,
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => $customer->contact_name ?: $customer->company,
            'customer_phone' => $customer->phone ?: '—',
            'customer_email' => $customer->email ?: '—',
            'shipment_type' => $shipmentType,
            'container_type' => $shipmentType === 'FCL' ? $primaryContainer['type'] : null,
            'container_qty' => $shipmentType === 'FCL' ? $primaryContainer['qty'] : null,
            'goods_description' => $request->input('goodsDescription'),
            'notes' => $customerNotes,
            'weight_kg' => $this->customerBookingGrossWeight($request, $cargoDetails),
            'volume_cbm' => $this->customerBookingVolumeCbm($request, $cargoDetails),
            'dangerous' => $this->customerIsDangerousCargo($request),
            'documents' => $request->input('documents'),
            'attachments' => ! empty($uploadedAttachments) ? json_encode($uploadedAttachments) : null,
            'cargo_details' => ! empty($cargoDetails) ? $cargoDetails : null,
            'pol' => $request->input('pol'),
            'pod' => $request->input('pod'),
            'route_details' => $routeDetails,
            'customs_status' => 'pending',
            'customs_notes' => $serviceOptions['customs_requested'] ? 'طلب العميل إضافة خدمة التخليص الجمركي إلى هذا الملف.' : null,
            'status' => 'new',
        ]);

        AuditLog::logAction('CUSTOMER_CREATE_BOOKING', 'bookings', $booking->id, [], [
            'source' => 'customer_portal',
            'customer_id' => $customer->id,
            'pol' => $booking->pol,
            'pod' => $booking->pod,
        ]);

        $this->createNotification([
            'message' => "طلب عميل جديد {$bookingId} من {$booking->customer_company} بانتظار التسعير",
            'booking_id' => $bookingId,
            'read' => false,
            'target' => 'pricing',
        ]);

        $this->createNotification([
            'message' => "طلب عميل جديد {$bookingId} تمت إضافته إلى سجل الطلبات",
            'booking_id' => $bookingId,
            'read' => false,
            'target' => 'orders',
        ]);

        if ($serviceOptions['customs_requested']) {
            $this->createNotification([
                'message' => "العميل طلب تخليص جمركي للطلب {$bookingId}",
                'booking_id' => $bookingId,
                'read' => false,
                'target' => 'customs',
            ]);
        }

        return redirect()
            ->route('customer.shipments.show', $booking)
            ->with('success', "تم إنشاء طلب الشحن بنجاح. رقم الطلب: {$bookingId}");
    }

    public function show(Booking $booking)
    {
        $customer = $this->customer();
        $booking = $this->findCustomerBooking($customer, $booking->id)
            ->with(['shipmentTrack.events', 'invoices'])
            ->firstOrFail();
        $notifications = $this->customerUnreadNotifications($customer)
            ->where('booking_id', $booking->id)
            ->get();
        $activityLogs = AuditLog::with('user')
            ->where('table_name', 'bookings')
            ->where('record_id', $booking->id)
            ->latest()
            ->take(12)
            ->get();

        return view('customer.show', compact('customer', 'booking', 'notifications', 'activityLogs'));
    }

    public function acceptQuote(Request $request, Booking $booking)
    {
        return $this->decideQuote($request, $booking, 'accepted');
    }

    public function rejectQuote(Request $request, Booking $booking)
    {
        return $this->decideQuote($request, $booking, 'rejected');
    }

    public function markNotificationsRead(Request $request)
    {
        $customer = $this->customer();
        $data = $request->validate([
            'notification_ids' => ['nullable', 'array'],
            'notification_ids.*' => ['string', 'max:100', 'distinct'],
            'booking_id' => ['nullable', 'string', 'max:100'],
        ]);

        $query = $this->customerUnreadNotifications($customer);

        if (! empty($data['notification_ids'])) {
            $query->whereIn('id', $data['notification_ids']);
        }

        if (! empty($data['booking_id'])) {
            $query->where('booking_id', $data['booking_id']);
        }

        $rows = $query->pluck('id')->map(fn (string $notificationId) => [
            'notification_id' => $notificationId,
            'customer_id' => $customer->id,
            'read_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ])->all();

        if ($rows) {
            DB::table('customer_notification_reads')->insertOrIgnore($rows);
        }

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : back()->with('success', 'تم تعليم إشعارات العميل كمقروءة.');
    }

    private function decideQuote(Request $request, Booking $booking, string $decision)
    {
        $customer = $this->customer();
        $booking = $this->findCustomerBooking($customer, $booking->id)->firstOrFail();

        if (! in_array($booking->status, ['priced', 'sent'], true)) {
            return back()->with('error', 'لا يمكن اتخاذ قرار على هذا العرض في حالته الحالية.');
        }

        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
            'return_to' => ['nullable', 'string', 'in:order-confirmation'],
        ]);

        $old = $booking->only(['status', 'customer_decision', 'customer_decision_note', 'customer_decided_at']);
        $status = $decision === 'accepted' ? 'customer_approved' : 'customer_rejected';
        $label = $decision === 'accepted' ? 'وافق' : 'رفض';
        $note = trim((string) ($data['note'] ?? ''));

        $booking->update([
            'status' => $status,
            'customer_decision' => $decision,
            'customer_decision_note' => $note ?: null,
            'customer_decided_at' => Carbon::now(),
        ]);

        AuditLog::logAction(
            $decision === 'accepted' ? 'CUSTOMER_ACCEPT_QUOTE' : 'CUSTOMER_REJECT_QUOTE',
            'bookings',
            $booking->id,
            $old,
            [
                'status' => $booking->status,
                'customer_id' => $customer->id,
                'note' => $note,
            ]
        );

        $this->createNotification([
            'message' => "العميل {$label} على عرض السعر للطلب {$booking->id}" . ($note ? ". ملاحظة العميل: {$note}" : ''),
            'booking_id' => $booking->id,
            'read' => false,
            'target' => 'sales',
        ]);

        $this->markBookingCustomerNotificationsRead($customer, $booking);

        $redirect = ($data['return_to'] ?? null) === 'order-confirmation'
            ? redirect()->route('customer.order-confirmation')
            : redirect()->route('customer.shipments.show', $booking);

        return $redirect
            ->with('success', $decision === 'accepted'
                ? 'تم إرسال موافقتك إلى قسم المبيعات لمتابعة الاعتماد النهائي.'
                : 'تم إرسال رفضك وملاحظتك إلى قسم المبيعات للمراجعة.');
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }

    private function customerBookings(Customer $customer): Builder
    {
        return Booking::query()
            ->where('customer_id', $customer->id);
    }

    private function findCustomerBooking(Customer $customer, string $bookingId): Builder
    {
        return $this->customerBookings($customer)->whereKey($bookingId);
    }

    private function customerShippingRateSeed(ShippingRate $shippingRate): array
    {
        $category = $this->customerShippingRateCategory($shippingRate);
        $shipmentType = $category === 'land'
            ? 'LAND'
            : $this->customerShippingRateShipmentType($shippingRate);
        $containerType = $shipmentType === 'FCL' ? $shippingRate->container_type : null;

        return [
            'source' => 'shipping_rate',
            'shipping_rate_id' => $shippingRate->id,
            'shipping_category' => $category,
            'route_mode' => $category === 'land' ? 'LAND' : ($shippingRate->incoterm ?: 'FOB'),
            'origin_country' => $shippingRate->origin_country,
            'destination_country' => $shippingRate->destination_country,
            'origin_city' => null,
            'destination_city' => null,
            'pol' => $shippingRate->origin_port,
            'pod' => $shippingRate->destination_port,
            'shipmentType' => $shipmentType,
            'containerType' => $containerType,
            'containerQty' => $containerType ? 1 : null,
            'containers' => $containerType ? [[
                'type' => $containerType,
                'qty' => 1,
            ]] : [],
        ];
    }

    private function customerShippingRateCategory(ShippingRate $shippingRate): string
    {
        $type = mb_strtolower(trim((string) $shippingRate->shipping_type));

        return str_contains($type, 'بري') || str_contains($type, 'land')
            ? 'land'
            : 'sea';
    }

    private function customerShippingRateShipmentType(ShippingRate $shippingRate): string
    {
        $serviceType = strtoupper(trim((string) $shippingRate->service_type));

        return match ($serviceType) {
            'LCL' => 'LCL',
            'BULK' => 'Bulk',
            'RORO', 'RO-RO' => 'RoRo',
            default => 'FCL',
        };
    }

    private function customerTrackingQuery(Customer $customer): Builder
    {
        return ShipmentTrack::query()
            ->whereHas('booking', fn (Builder $query) => $query->where('customer_id', $customer->id));
    }

    private function customerUnreadNotifications(Customer $customer): Builder
    {
        return $this->customerNotifications($customer)
            ->whereNotIn('id', $this->customerReadNotificationIdsQuery($customer))
            ->latest();
    }

    private function customerNotifications(Customer $customer): Builder
    {
        return Notification::query()
            ->where('target', 'customer')
            ->whereIn('booking_id', $this->customerBookings($customer)->select('id'));
    }

    private function customerReadNotificationIdsQuery(Customer $customer)
    {
        return DB::table('customer_notification_reads')
            ->select('notification_id')
            ->where('customer_id', $customer->id);
    }

    private function markBookingCustomerNotificationsRead(Customer $customer, Booking $booking): void
    {
        $rows = $this->customerUnreadNotifications($customer)
            ->where('booking_id', $booking->id)
            ->pluck('id')
            ->map(fn (string $notificationId) => [
                'notification_id' => $notificationId,
                'customer_id' => $customer->id,
                'read_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($rows) {
            DB::table('customer_notification_reads')->insertOrIgnore($rows);
        }
    }

    private function bookingStatusLabels(): array
    {
        return BookingStatus::labels();
    }

    private function customsStatusLabels(): array
    {
        return BookingStatus::customsLabels();
    }

    private function hasCustomsActivity(Booking $booking): bool
    {
        $services = (array) data_get($booking->route_details, 'services', []);

        return (bool) ($services['customs_requested'] ?? false)
            || filled($booking->customs_reference)
            || filled($booking->customs_declaration_no)
            || filled($booking->customs_bayan_no)
            || filled($booking->customs_documents)
            || filled($booking->customs_notes);
    }

    private function bookingAttachments(Booking $booking): array
    {
        if (blank($booking->attachments)) {
            return [];
        }

        $attachments = is_array($booking->attachments)
            ? $booking->attachments
            : json_decode((string) $booking->attachments, true);

        return is_array($attachments) ? array_values($attachments) : [];
    }

    private function bookingCustomsDocuments(Booking $booking): array
    {
        if (blank($booking->customs_documents)) {
            return [];
        }

        $documents = is_array($booking->customs_documents)
            ? $booking->customs_documents
            : json_decode((string) $booking->customs_documents, true);

        return is_array($documents) ? array_values($documents) : [];
    }

    private function normalizeShippingCategory(mixed $category): string
    {
        $category = strtolower(str_replace('-', '_', trim((string) $category)));

        return match ($category) {
            'land' => 'land',
            'air' => 'air',
            'door', 'contracting', 'door_to_door' => 'door_to_door',
            default => 'sea',
        };
    }

    private function cleanString(mixed $value): string
    {
        return trim((string) $value);
    }

    private function airportCode(mixed $value): string
    {
        return strtoupper(preg_replace('/[^A-Z0-9]/', '', $this->cleanString($value)) ?? '');
    }

    private function normalizeMainTransportMode(mixed $mode): string
    {
        $mode = strtoupper($this->cleanString($mode));

        return in_array($mode, ['AUTO', 'AIR', 'SEA', 'LAND', 'MULTIMODAL'], true) ? $mode : 'AUTO';
    }

    private function customerRequestedCustoms(Request $request): bool
    {
        return $request->boolean('customs_requested')
            || $request->boolean('customs_clearance_origin')
            || $request->boolean('customs_clearance_destination')
            || $request->boolean('export_customs_clearance')
            || $request->boolean('import_customs_clearance');
    }

    private function countryMeta(?string $countryName, ?string $countryCode = null): array
    {
        $countryName = $this->cleanString($countryName);
        $countryCode = strtoupper($this->cleanString($countryCode));

        if ($countryCode === '' && preg_match('/^[A-Za-z]{2}$/', $countryName)) {
            $countryCode = strtoupper($countryName);
            $countryName = '';
        }

        $resolved = PortCatalog::resolveCountry($countryName, $countryCode);

        return [
            'country' => $resolved['country'] ?: ($countryName ?: null),
            'country_code' => $resolved['country_code'],
        ];
    }

    private function routeDisplayName(string $city, string $codeOrAddress): string
    {
        $city = $this->cleanString($city);
        $codeOrAddress = $this->cleanString($codeOrAddress);

        if ($city !== '' && $codeOrAddress !== '') {
            return "{$city} ({$codeOrAddress})";
        }

        return $codeOrAddress ?: $city;
    }

    private function airportSelectorData($fallbackCountries): array
    {
        $airports = Schema::hasTable('airports')
            ? Airport::where('is_active', true)
                ->orderBy('country')
                ->orderBy('city')
                ->orderBy('iata_code')
                ->get()
            : collect();

        if ($airports->isEmpty()) {
            $airports = collect(config('airports.airports', []));
        }

        $airportsForBooking = $airports
            ->map(function ($airport) {
                $code = $this->airportCode(data_get($airport, 'iata_code'));
                $country = $this->countryMeta(data_get($airport, 'country'), data_get($airport, 'country_code'));

                return [
                    'iata_code' => $code,
                    'code' => $code,
                    'name' => (string) data_get($airport, 'name', $code),
                    'city' => (string) data_get($airport, 'city', ''),
                    'country' => $country['country'],
                    'country_code' => $country['country_code'],
                ];
            })
            ->filter(fn (array $airport) => $airport['iata_code'] !== '')
            ->values();

        $airportCountries = $airportsForBooking
            ->map(fn (array $airport) => [
                'name' => $airport['country'],
                'code' => $airport['country_code'],
            ])
            ->filter(fn (array $country) => filled($country['name']))
            ->unique(fn (array $country) => ($country['code'] ?: $country['name']))
            ->sortBy('name')
            ->values();

        if ($airportCountries->isEmpty()) {
            $airportCountries = collect($fallbackCountries)->values();
        }

        return [$airportCountries, $airportsForBooking];
    }

    private function airportByCode(?string $code): ?array
    {
        $code = $this->airportCode($code);
        if ($code === '') {
            return null;
        }

        $airport = Schema::hasTable('airports')
            ? Airport::where('iata_code', $code)->first()
            : null;

        if ($airport) {
            return [
                'iata_code' => $airport->iata_code,
                'name' => $airport->name,
                'city' => $airport->city,
                'country' => $airport->country,
                'country_code' => $airport->country_code,
            ];
        }

        return collect(config('airports.airports', []))
            ->first(fn (array $item) => $this->airportCode($item['iata_code'] ?? null) === $code);
    }

    private function numericInput(Request $request, string $field): float
    {
        return is_numeric($request->input($field)) ? (float) $request->input($field) : 0.0;
    }

    private function integerInput(Request $request, string $field): int
    {
        return is_numeric($request->input($field)) ? (int) $request->input($field) : 0;
    }

    private function extractPackages(Request $request): array
    {
        $packages = [];
        $isDoor = $request->input('shipmentType') === 'DOOR_TO_DOOR';
        $dimensionFactor = $isDoor && $request->input('package_dimension_unit') === 'm' ? 100 : 1;

        foreach ((array) $request->input('packages', []) as $package) {
            if (! is_array($package)) {
                continue;
            }

            $quantity = max(1, (int) ($package['quantity'] ?? 1));
            $type = $this->cleanString($package['package_type'] ?? $request->input('package_type'));
            $length = (float) ($package['length_cm'] ?? 0) * $dimensionFactor;
            $width = (float) ($package['width_cm'] ?? 0) * $dimensionFactor;
            $height = (float) ($package['height_cm'] ?? 0) * $dimensionFactor;
            $weight = (float) ($package['weight_kg'] ?? 0);

            if ($type === '' && $length <= 0 && $width <= 0 && $height <= 0 && $weight <= 0) {
                continue;
            }

            $packages[] = [
                'quantity' => $quantity,
                'package_type' => $type ?: null,
                'length_cm' => $isDoor ? round(max(0, $length), 2) : $length,
                'width_cm' => $isDoor ? round(max(0, $width), 2) : $width,
                'height_cm' => $isDoor ? round(max(0, $height), 2) : $height,
                'weight_kg' => $isDoor ? round(max(0, $weight), 2) : $weight,
            ];
        }

        return $packages;
    }

    private function airVolumetricDivisor(Request $request): int
    {
        $requestDivisor = $this->integerInput($request, 'air_volumetric_divisor');

        if ($requestDivisor > 0) {
            return $requestDivisor;
        }

        return max(1, (int) config('shipping.air.volumetric_divisor', self::DEFAULT_AIR_VOLUMETRIC_DIVISOR));
    }

    private function packageMetrics(array $packages, Request $request, bool $air = false): array
    {
        $pieces = 0;
        $grossWeight = 0.0;
        $volumeCbm = 0.0;

        foreach ($packages as $package) {
            $quantity = max(1, (int) ($package['quantity'] ?? 1));
            $pieces += $quantity;
            $grossWeight += ((float) ($package['weight_kg'] ?? 0)) * $quantity;
            $volumeCbm += (
                max(0, (float) ($package['length_cm'] ?? 0))
                * max(0, (float) ($package['width_cm'] ?? 0))
                * max(0, (float) ($package['height_cm'] ?? 0))
                / 1000000
            ) * $quantity;
        }

        if ($packages === []) {
            $pieces = $this->integerInput($request, 'packages_count')
                ?: $this->integerInput($request, 'containerQty')
                ?: 1;
            $grossWeight = $this->numericInput($request, 'weightKg');
            $volumeCbm = $this->numericInput($request, 'volumeCbm');
        }

        if (! $air && $volumeCbm <= 0) {
            $volumeCbm = $this->numericInput($request, 'volumeCbm');
        }

        if (! $air && $grossWeight <= 0) {
            $grossWeight = $this->numericInput($request, 'weightKg');
        }

        $volumetricDivisor = $air ? $this->airVolumetricDivisor($request) : null;
        $volumetricWeight = $air && $volumeCbm > 0 && $volumetricDivisor
            ? ($volumeCbm * 1000000) / $volumetricDivisor
            : 0;

        return [
            'pieces_count' => $pieces,
            'gross_weight_kg' => round($grossWeight, 2),
            'volume_cbm' => round($volumeCbm, $air ? 3 : 4),
            'volumetric_weight_kg' => round($volumetricWeight, 2),
            'chargeable_weight_kg' => $air ? round(max($grossWeight, $volumetricWeight), 2) : null,
            'volumetric_divisor' => $volumetricDivisor,
        ];
    }

    private function validateCustomerBookingRequest(Request $request, array $rules): void
    {
        $validator = validator($request->all(), $rules);

        $validator->after(function ($validator) use ($request) {
            if (! $this->customerIsDangerousCargo($request)) {
                return;
            }

            if ($this->requestHasValidAttachments($request)) {
                return;
            }

            $validator->errors()->add('attachments', __('messages.booking_dg_attachment_required'));
        });

        $validator->validate();
    }

    private function customerIsDangerousCargo(Request $request, ?string $cargoType = null): bool
    {
        $cargoType = strtolower($this->cleanString($cargoType ?? $request->input('cargo_type')));
        if ($cargoType === 'dangerous' || $request->boolean('dangerous')) {
            return true;
        }

        $dangerousGoods = $request->input('dangerous_goods', []);
        if (is_array($dangerousGoods) && filter_var(data_get($dangerousGoods, 'is_dangerous'), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        return collect([
            'dangerous_un_number',
            'dangerous_iata_class',
            'dangerous_imdg_class',
            'dangerous_adr_class',
            'dangerous_packing_group',
            'dangerous_proper_shipping_name',
            'dangerous_msds_available',
        ])->contains(fn (string $key) => $request->filled($key));
    }

    private function requestHasValidAttachments(Request $request): bool
    {
        $files = $request->file('attachments', []);
        if ($files instanceof \Illuminate\Http\UploadedFile) {
            $files = [$files];
        }

        foreach ((array) $files as $file) {
            if ($file && $file->isValid()) {
                return true;
            }
        }

        return false;
    }

    private function customerDangerousGoodsDetails(Request $request): array
    {
        $isDangerous = $this->customerIsDangerousCargo($request);

        return [
            'is_dangerous' => $isDangerous,
            'transport_regulation' => $request->input('dangerous_transport_regulation'),
            'un_number' => $request->input('dangerous_un_number'),
            'iata_class' => $request->input('dangerous_iata_class'),
            'imdg_class' => $request->input('dangerous_imdg_class'),
            'adr_class' => $request->input('dangerous_adr_class'),
            'packing_group' => $request->input('dangerous_packing_group'),
            'proper_shipping_name' => $request->input('dangerous_proper_shipping_name'),
            'msds_available' => $request->boolean('dangerous_msds_available'),
        ];
    }

    private function customerCargoBaseDetails(Request $request): array
    {
        $currency = strtoupper($this->cleanString($request->input('currency', 'USD'))) ?: 'USD';
        $cargoType = $request->input('cargo_type') ?: ($this->customerIsDangerousCargo($request) ? 'dangerous' : 'general');
        if ($cargoType !== 'dangerous' && $this->customerIsDangerousCargo($request, $cargoType)) {
            $cargoType = 'dangerous';
        }

        $commercialValue = $this->numericInput($request, 'commercial_value') ?: $this->numericInput($request, 'declared_value');

        return [
            'cargo_type' => $cargoType,
            'hs_code' => $request->input('hs_code'),
            'country_of_origin' => $request->input('country_of_origin'),
            'package_type' => $request->input('package_type'),
            'net_weight_kg' => $request->filled('net_weight_kg') ? (float) $request->input('net_weight_kg') : null,
            'stackable' => ! $request->has('stackable') || $request->boolean('stackable'),
            'declared_value' => $this->numericInput($request, 'declared_value'),
            'currency' => $currency,
            'trade' => [
                'incoterm' => $request->input('incoterm'),
                'commercial_value' => $commercialValue,
                'currency' => $currency,
            ],
            'dangerous_goods' => $this->customerDangerousGoodsDetails($request),
        ];
    }

    private function buildCustomerCargoDetails(Request $request): array
    {
        $shipmentType = (string) $request->input('shipmentType', 'FCL');
        $base = $this->customerCargoBaseDetails($request);
        $cargoItems = $this->extractCustomerCargoItems($request);
        $base['cargo_items'] = $cargoItems;

        if (blank($base['hs_code']) && $cargoItems !== []) {
            $base['hs_code'] = $cargoItems[0]['hs_code'] ?? null;
        }

        if ($shipmentType === 'AIR') {
            $packages = $this->extractPackages($request);
            $metrics = $this->packageMetrics($packages, $request, true);
            $service = [
                'service_level' => $request->input('air_service_level', 'standard'),
                'ready_date' => $request->input('ready_date'),
                'direct_flight_preferred' => $request->boolean('direct_flight_preferred'),
                'insurance_required' => $request->boolean('insurance_required'),
                'customs_clearance_origin' => $request->boolean('customs_clearance_origin'),
                'customs_clearance_destination' => $request->boolean('customs_clearance_destination'),
                'pickup_required' => $request->boolean('pickup_required'),
                'delivery_required' => $request->boolean('delivery_required'),
            ];
            $cargo = [
                'goods_description' => $request->input('goodsDescription'),
                'cargo_type' => $base['cargo_type'],
                'hs_code' => $base['hs_code'],
                'country_of_origin' => $base['country_of_origin'],
                'pieces_count' => $metrics['pieces_count'],
                'package_type' => $base['package_type'],
                'gross_weight_kg' => $metrics['gross_weight_kg'],
                'net_weight_kg' => $base['net_weight_kg'],
                'volume_cbm' => $metrics['volume_cbm'],
                'volumetric_weight_kg' => $metrics['volumetric_weight_kg'],
                'chargeable_weight_kg' => $metrics['chargeable_weight_kg'],
                'stackable' => $base['stackable'],
                'declared_value' => $base['declared_value'],
                'currency' => $base['currency'],
            ];

            return array_merge($base, $metrics, [
                'cargo' => $cargo,
                'packages' => $packages,
                'service' => $service,
                'service_details' => $service,
                'trade' => $base['trade'],
                'service_type' => 'air_freight',
                'service_level' => $service['service_level'],
                'ready_date' => $service['ready_date'],
            ]);
        }

        if ($shipmentType === 'DOOR_TO_DOOR') {
            $packages = $this->extractPackages($request);
            $metrics = $this->packageMetrics($packages, $request);
            $service = [
                'pickup_ready_date' => $request->input('pickup_ready_date'),
                'pickup_time_from' => $request->input('pickup_time_from'),
                'pickup_time_to' => $request->input('pickup_time_to'),
                'delivery_required_date' => $request->input('delivery_required_date'),
                'preferred_pickup_time' => $request->input('preferred_pickup_time', 'anytime'),
                'insurance_required' => $request->boolean('insurance_required'),
                'export_customs_clearance' => $request->boolean('export_customs_clearance'),
                'import_customs_clearance' => $request->boolean('import_customs_clearance'),
                'delivery_notes' => $request->input('delivery_notes'),
            ];
            $cargo = [
                'goods_description' => $request->input('goodsDescription'),
                'cargo_type' => $base['cargo_type'],
                'hs_code' => $base['hs_code'],
                'country_of_origin' => $base['country_of_origin'],
                'packages_count' => $metrics['pieces_count'],
                'package_type' => $base['package_type'],
                'gross_weight_kg' => $metrics['gross_weight_kg'],
                'net_weight_kg' => $base['net_weight_kg'],
                'volume_cbm' => $metrics['volume_cbm'],
                'stackable' => $base['stackable'],
                'declared_value' => $base['declared_value'],
                'currency' => $base['currency'],
            ];

            return array_merge($base, $metrics, [
                'cargo' => $cargo,
                'packages' => $packages,
                'service' => $service,
                'service_details' => $service,
                'trade' => $base['trade'],
                'service_type' => 'door_to_door',
                'package_dimension_unit' => $request->input('package_dimension_unit') ?: 'cm',
                'main_transport_mode' => $this->normalizeMainTransportMode($request->input('main_transport_mode')),
                'pickup_ready_date' => $service['pickup_ready_date'],
                'preferred_pickup_time' => $service['preferred_pickup_time'],
                'pickup_contact' => [
                    'name' => $request->input('pickup_contact_name'),
                    'phone_country_code' => $request->input('pickup_phone_country_code'),
                    'phone' => $request->input('pickup_phone'),
                ],
                'delivery_contact' => [
                    'name' => $request->input('delivery_contact_name'),
                    'phone_country_code' => $request->input('delivery_phone_country_code'),
                    'phone' => $request->input('delivery_phone'),
                ],
                'delivery_notes' => $service['delivery_notes'],
            ]);
        }

        if ($shipmentType === 'LAND') {
            $cartonCount = $this->integerInput($request, 'land_carton_count');

            return array_filter(array_merge($base, [
                'service_type' => 'land',
                'land_carton_count' => $cartonCount > 0 ? $cartonCount : null,
            ]), fn ($value) => $value !== null && $value !== '' && $value !== []);
        }

        if ($shipmentType === 'LCL') {
            return array_merge($base, [
                'service_type' => 'lcl',
                'volume_cbm' => $this->numericInput($request, 'volumeCbm'),
            ]);
        }

        if ($shipmentType === 'Bulk') {
            $pieces = max(1, $this->integerInput($request, 'bulk_pieces') ?: 1);
            $pieceWeight = $this->numericInput($request, 'bulk_piece_weight');
            $totalWeight = $pieceWeight > 0
                ? round($pieceWeight * $pieces, 2)
                : $this->numericInput($request, 'bulk_total_weight');
            $length = $this->numericInput($request, 'bulk_length');
            $width = $this->numericInput($request, 'bulk_width');
            $height = $this->numericInput($request, 'bulk_height');
            $rawPieceCbm = $length > 0 && $width > 0 && $height > 0
                ? $height * $width * $length
                : $this->numericInput($request, 'bulk_cbm');
            $pieceCbm = round($rawPieceCbm, 1);
            $totalCbm = round($rawPieceCbm * $pieces, 1);

            return array_merge($base, [
                'service_type' => 'bulk',
                'cargo_name' => $request->input('bulk_cargo_name'),
                'pieces' => $pieces,
                'piece_weight' => $pieceWeight,
                'total_weight' => $totalWeight,
                'gross_weight_kg' => $totalWeight,
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'dimension_unit' => 'cm',
                'cbm' => $pieceCbm,
                'piece_cbm' => $pieceCbm,
                'total_cbm' => $totalCbm,
                'volume_cbm' => $totalCbm,
                'packaging_type' => $request->input('bulk_packaging'),
                'lifting_method' => $request->input('bulk_lifting'),
                'stackable' => $request->has('bulk_stackable'),
            ]);
        }

        if ($shipmentType === 'RoRo') {
            return array_merge($base, [
                'service_type' => 'roro',
                'vehicle_type' => $request->input('roro_vehicle_type'),
                'manufacturer' => $request->input('roro_manufacturer'),
                'model' => $request->input('roro_model'),
                'year' => $request->input('roro_year'),
                'color' => $request->input('roro_color'),
                'vin' => $request->input('roro_vin'),
                'chassis_number' => $request->input('roro_chassis_number'),
                'engine_number' => $request->input('roro_engine_number'),
                'plate_number' => $request->input('roro_plate_number'),
                'length' => $request->input('roro_length'),
                'width' => $request->input('roro_width'),
                'height' => $request->input('roro_height'),
                'weight' => $request->input('roro_weight'),
                'running' => $request->has('roro_running'),
                'tow_required' => $request->has('roro_tow_required'),
                'fuel_level' => $request->input('roro_fuel_level'),
                'has_key' => $request->has('roro_has_key'),
                'has_items' => $request->has('roro_has_items'),
                'value' => $request->input('roro_value'),
            ]);
        }

        if ($shipmentType === 'FCL') {
            $containers = $this->extractCustomerFclContainers($request);
            $totalQty = collect($containers)->sum('qty');
            $hasReefer = collect($containers)
                ->contains(fn (array $container) => in_array($container['type'], ['Reefer 20', 'Reefer 40'], true));

            return array_filter(array_merge($base, [
                'service_type' => 'fcl',
                'containers' => $containers,
                'container_summary' => $this->customerFclContainerSummary($containers),
                'container_total_qty' => $totalQty,
                'reefer_temperature' => $hasReefer ? $request->input('reefer_temperature') : null,
            ]), fn ($value) => $value !== null && $value !== '' && $value !== []);
        }

        return [];
    }

    private function extractCustomerCargoItems(Request $request): array
    {
        $rows = $request->input('cargo_items', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->map(function ($row) {
                if (! is_array($row)) {
                    return null;
                }

                $name = $this->cleanString($row['name'] ?? null);
                $quantity = max(0, (float) ($row['quantity'] ?? 0));

                if ($name === '' || $quantity <= 0) {
                    return null;
                }

                $unit = strtolower($this->cleanString($row['unit'] ?? 'piece'));
                if (! in_array($unit, ['piece', 'vehicle', 'cbm', 'kg', 'ton', 'container'], true)) {
                    $unit = 'piece';
                }

                return array_filter([
                    'name' => mb_substr($name, 0, 255),
                    'hs_code' => mb_substr($this->cleanString($row['hs_code'] ?? null), 0, 40),
                    'quantity' => round($quantity, 4),
                    'unit' => $unit,
                    'weight_kg' => isset($row['weight_kg']) && $row['weight_kg'] !== '' ? round(max(0, (float) $row['weight_kg']), 2) : null,
                    'volume_cbm' => isset($row['volume_cbm']) && $row['volume_cbm'] !== '' ? round(max(0, (float) $row['volume_cbm']), 4) : null,
                ], fn ($value) => $value !== null && $value !== '');
            })
            ->filter()
            ->values()
            ->all();
    }

    private function extractCustomerFclContainers(Request $request): array
    {
        $rows = $request->input('containers', []);

        if (! is_array($rows) || $rows === []) {
            $rows = [[
                'type' => $request->input('containerType'),
                'qty' => $request->input('containerQty'),
            ]];
        }

        $containers = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $type = trim((string) ($row['type'] ?? ''));
            $qty = max(1, (int) ($row['qty'] ?? 1));

            if ($type === '') {
                continue;
            }

            if (isset($containers[$type])) {
                $containers[$type]['qty'] += $qty;
                continue;
            }

            $containers[$type] = [
                'type' => $type,
                'qty' => $qty,
            ];
        }

        return array_values($containers);
    }

    private function customerFclContainerSummary(array $containers): string
    {
        return collect($containers)
            ->map(fn (array $container) => "{$container['qty']}x{$container['type']}")
            ->implode(' + ');
    }

    private function primaryCustomerFclContainer(array $cargoDetails, Request $request): array
    {
        $containers = $cargoDetails['containers'] ?? [];

        if (is_array($containers) && isset($containers[0]) && is_array($containers[0])) {
            return [
                'type' => (string) ($containers[0]['type'] ?? $request->input('containerType')),
                'qty' => (int) ($containers[0]['qty'] ?? $request->input('containerQty', 1)),
            ];
        }

        return [
            'type' => (string) $request->input('containerType'),
            'qty' => (int) $request->input('containerQty', 1),
        ];
    }

    private function buildCustomerRouteDetails(Request $request, array $serviceOptions): array
    {
        $category = $request->input('shipping_category', 'sea');
        $routeMode = $request->input('route_mode');
        $originMeta = $this->countryMeta($request->input('origin_country'), $request->input('origin_country_code'));
        $destinationMeta = $this->countryMeta($request->input('destination_country'), $request->input('destination_country_code'));
        $originCity = $request->input('origin_city');
        $destinationCity = $request->input('destination_city');
        $pol = $request->input('pol');
        $pod = $request->input('pod');
        $incoterm = $request->input('incoterm');

        if ($category === 'air') {
            $originAirport = $this->airportCode($request->input('origin_airport', $pol));
            $destinationAirport = $this->airportCode($request->input('destination_airport', $pod));
            $originRecord = $this->airportByCode($originAirport);
            $destinationRecord = $this->airportByCode($destinationAirport);
            $originAirportName = $request->input('origin_airport_name') ?: data_get($originRecord, 'name');
            $destinationAirportName = $request->input('destination_airport_name') ?: data_get($destinationRecord, 'name');
            $originCity = $originCity ?: data_get($originRecord, 'city');
            $destinationCity = $destinationCity ?: data_get($destinationRecord, 'city');

            if (blank($originMeta['country_code']) && $originRecord) {
                $originMeta = $this->countryMeta(data_get($originRecord, 'country'), data_get($originRecord, 'country_code'));
            }

            if (blank($destinationMeta['country_code']) && $destinationRecord) {
                $destinationMeta = $this->countryMeta(data_get($destinationRecord, 'country'), data_get($destinationRecord, 'country_code'));
            }

            return [
                'category' => 'air',
                'mode' => 'AIR',
                'service' => 'air_freight',
                'services' => $serviceOptions,
                'incoterm' => $incoterm ?: null,
                'origin' => [
                    'type' => 'airport',
                    'country' => $originMeta['country'],
                    'country_code' => $originMeta['country_code'],
                    'city' => $originCity,
                    'code' => $originAirport,
                    'airport_code' => $originAirport,
                    'airport' => $originAirport,
                    'airport_name' => $originAirportName ?: null,
                    'address' => null,
                    'port' => null,
                    'display_name' => $this->routeDisplayName((string) $originCity, $originAirport),
                ],
                'destination' => [
                    'type' => 'airport',
                    'country' => $destinationMeta['country'],
                    'country_code' => $destinationMeta['country_code'],
                    'city' => $destinationCity,
                    'code' => $destinationAirport,
                    'airport_code' => $destinationAirport,
                    'airport' => $destinationAirport,
                    'airport_name' => $destinationAirportName ?: null,
                    'address' => null,
                    'port' => null,
                    'display_name' => $this->routeDisplayName((string) $destinationCity, $destinationAirport),
                ],
            ];
        }

        if ($category === 'door_to_door') {
            $pickupAddress = $this->cleanString($request->input('pickup_address', $pol));
            $deliveryAddress = $this->cleanString($request->input('delivery_address', $pod));

            return [
                'category' => 'door_to_door',
                'mode' => 'DOOR_TO_DOOR',
                'service' => 'door_to_door',
                'services' => $serviceOptions,
                'main_transport_mode' => $this->normalizeMainTransportMode($request->input('main_transport_mode')),
                'incoterm' => $incoterm ?: null,
                'origin' => [
                    'type' => 'address',
                    'country' => $originMeta['country'],
                    'country_code' => $originMeta['country_code'],
                    'city' => $originCity,
                    'postal_code' => $request->input('origin_postal_code'),
                    'airport' => null,
                    'port' => null,
                    'address' => $pickupAddress,
                    'contact_name' => $request->input('pickup_contact_name'),
                    'phone_country_code' => $request->input('pickup_phone_country_code'),
                    'phone' => $request->input('pickup_phone'),
                    'display_name' => $this->routeDisplayName((string) $originCity, $pickupAddress),
                ],
                'destination' => [
                    'type' => 'address',
                    'country' => $destinationMeta['country'],
                    'country_code' => $destinationMeta['country_code'],
                    'city' => $destinationCity,
                    'postal_code' => $request->input('destination_postal_code'),
                    'airport' => null,
                    'port' => null,
                    'address' => $deliveryAddress,
                    'contact_name' => $request->input('delivery_contact_name'),
                    'phone_country_code' => $request->input('delivery_phone_country_code'),
                    'phone' => $request->input('delivery_phone'),
                    'display_name' => $this->routeDisplayName((string) $destinationCity, $deliveryAddress),
                ],
            ];
        }

        if ($category === 'land') {
            return [
                'category' => 'land',
                'mode' => 'LAND',
                'services' => $serviceOptions,
                'origin' => [
                    'type' => 'city',
                    'country' => null,
                    'city' => $pol,
                    'port' => $pol,
                ],
                'destination' => [
                    'type' => 'city',
                    'country' => null,
                    'city' => $pod,
                    'port' => $pod,
                ],
            ];
        }

        return [
            'category' => 'sea',
            'mode' => $routeMode,
            'services' => $serviceOptions,
            'incoterm' => $incoterm ?: ($routeMode ?: null),
            'origin' => [
                'type' => 'port',
                'country' => $originMeta['country'],
                'country_code' => $originMeta['country_code'],
                'city' => $routeMode === 'EXW' ? $originCity : null,
                'port' => $pol,
            ],
            'destination' => [
                'type' => 'port',
                'country' => $destinationMeta['country'],
                'country_code' => $destinationMeta['country_code'],
                'city' => $routeMode === 'EXW' ? $destinationCity : null,
                'port' => $pod,
            ],
        ];
    }

    private function customerBookingGrossWeight(Request $request, array $cargoDetails): float
    {
        return (float) (data_get($cargoDetails, 'cargo.gross_weight_kg')
            ?? data_get($cargoDetails, 'gross_weight_kg')
            ?? $request->input('weightKg')
            ?? 0);
    }

    private function customerBookingVolumeCbm(Request $request, array $cargoDetails): float
    {
        return (float) (data_get($cargoDetails, 'cargo.volume_cbm')
            ?? data_get($cargoDetails, 'volume_cbm')
            ?? $request->input('volumeCbm')
            ?? 0);
    }

    private function makeBookingId(): string
    {
        return SystemId::makeSequential(Booking::class, 'BK');
    }

    private function portSelectorData(): array
    {
        $ports = Port::where('is_active', true)
            ->orderBy('country')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($ports->isEmpty()) {
            $ports = collect(config('ports.ports', []))->map(fn (array $portData, int $index) => (object) [
                'name' => $portData['name'] ?? '',
                'code' => $portData['code'] ?? null,
                'country' => $portData['country'] ?? null,
                'country_code' => $portData['country_code'] ?? null,
                'country_flag' => $portData['country_flag'] ?? null,
                'sort_order' => $index,
            ]);
        }

        $portCountries = $ports
            ->map(fn ($port) => PortCatalog::countryOptionFromPort($port))
            ->filter()
            ->unique('name')
            ->sortBy('name')
            ->values();

        $portsForBooking = $ports->map(function ($port) {
            $country = PortCatalog::resolveCountry($port->country, $port->country_code, $port->country_flag);

            return [
                'name' => $port->name,
                'code' => $port->code,
                'country' => $country['country'],
                'country_code' => $country['country_code'],
                'country_flag' => $country['country_flag'],
            ];
        })->values();

        return [$portCountries, $portsForBooking];
    }

    private function rememberCustomerRoutePorts(Request $request): void
    {
        if ($request->input('shipping_category') !== 'sea') {
            return;
        }

        foreach ([
            ['name' => trim((string) $request->input('pol')), 'country' => $request->input('origin_country')],
            ['name' => trim((string) $request->input('pod')), 'country' => $request->input('destination_country')],
        ] as $portData) {
            if ($portData['name'] === '') {
                continue;
            }

            $country = PortCatalog::resolveCountry($portData['country']);
            $port = Port::where('name', $portData['name'])
                ->when(filled($country['country']), function ($query) use ($country) {
                    $query->where(function ($countryQuery) use ($country) {
                        $countryQuery->where('country', $country['country'])
                            ->orWhereNull('country')
                            ->orWhere('country', '');
                    });
                })
                ->first();

            if (! $port) {
                $port = new Port(['name' => $portData['name']]);
            }

            if (! $port->exists) {
                $port->sort_order = Port::count();
                $port->is_active = true;
            }

            if (blank($port->country) && filled($country['country'])) {
                $port->country = $country['country'];
                $port->country_code = $country['country_code'];
                $port->country_flag = $country['country_flag'];
            }

            $port->save();
        }
    }
}

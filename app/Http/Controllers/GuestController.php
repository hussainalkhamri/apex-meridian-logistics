<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Agent;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Port;
use App\Models\ShipmentTrack;
use App\Models\Schedule;
use App\Models\Vessel;
use App\Support\PortCatalog;
use App\Support\SystemId;
use Illuminate\Http\Request;

class GuestController extends Controller
{
    public function home()
    {
        [$portCountries, $portsForBooking] = $this->publicPortSelectorData();
        $pricedBookings = Booking::whereNotNull('priced_at')->get(['created_at', 'priced_at']);
        $averageResponseHours = $pricedBookings->count()
            ? round($pricedBookings->avg(fn (Booking $booking) => max($booking->created_at->diffInMinutes($booking->priced_at), 1)) / 60, 1)
            : null;
        $trustStats = [
            [
                'value' => number_format(Booking::count()),
                'label' => 'طلبات شحن مسجلة',
            ],
            [
                'value' => number_format(Customer::count()),
                'label' => 'عملاء وشركات',
            ],
            [
                'value' => number_format(Port::where('is_active', true)->count()),
                'label' => 'ميناء نشط',
            ],
            [
                'value' => $averageResponseHours ? $averageResponseHours . 'h' : '<24h',
                'label' => 'متوسط زمن التسعير',
            ],
        ];

        return view('home', compact('portCountries', 'portsForBooking', 'trustStats'));
    }

    public function storeInstantBooking(Request $request)
    {
        $shippingCategory = $request->input('shipping_category', 'sea');

        $request->merge([
            'shipping_category' => $shippingCategory,
            'route_mode' => $shippingCategory === 'sea' ? strtoupper((string) $request->input('route_mode')) : null,
        ]);

        $request->validate([
            'shipping_category' => 'required|in:sea,land',
            'company' => 'required|string|max:255',
            'contactName' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'phone_country_code' => 'nullable|string|max:8',
            'email' => 'required|email|max:255',
            'pol' => 'required|string|max:255',
            'pod' => 'required|string|max:255',
            'route_mode' => 'nullable|required_if:shipping_category,sea|in:FOB,EXW',
            'origin_country' => 'nullable|required_if:shipping_category,sea|string|max:255',
            'destination_country' => 'nullable|required_if:shipping_category,sea|string|max:255',
            'origin_city' => 'nullable|required_if:route_mode,EXW|string|max:255',
            'destination_city' => 'nullable|required_if:route_mode,EXW|string|max:255',
            'shipmentType' => 'nullable|required_if:shipping_category,sea|in:FCL,LCL',
            'containerType' => 'required_if:shipmentType,FCL|nullable|string|max:50',
            'containerQty' => 'required_if:shipmentType,FCL|nullable|integer|min:1',
            'goodsDescription' => 'required|string|max:1000',
            'weightKg' => 'required|numeric|min:0.01',
            'volumeCbm' => 'nullable|numeric|min:0',
            'documents' => 'nullable|string|max:1000',
            'attachments' => 'nullable|array|max:6',
            'attachments.*' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
            'notes' => 'nullable|string|max:2000',
            'dangerous' => 'nullable|boolean',
        ]);

        $phone = $this->composePublicPhoneNumber(
            $request->input('phone'),
            $request->input('phone_country_code')
        );

        $portalCustomer = auth('customer')->user();

        if ($portalCustomer instanceof Customer) {
            $customer = $portalCustomer;
            $customer->fill([
                'company' => $request->input('company') ?: $customer->company,
                'contact_name' => $request->input('contactName') ?: $customer->contact_name,
                'phone' => $phone ?: $customer->phone,
                'email' => $request->input('email') ?: $customer->email,
            ])->save();
        } else {
            $customer = Customer::firstOrCreate(
                [
                    'company' => $request->input('company'),
                    'email' => $request->input('email'),
                    'phone' => $phone,
                ],
                [
                    'contact_name' => $request->input('contactName'),
                    'notes' => 'تم إنشاؤه من طلب شحن فوري عبر الصفحة الرئيسية.',
                ]
            );
        }

        $bookingId = $this->makePublicBookingId();
        $shippingCategory = $request->input('shipping_category');
        $shipmentType = $shippingCategory === 'land' ? 'LAND' : $request->input('shipmentType');
        $routeMode = $request->input('route_mode');
        $routeDetails = null;

        if ($shippingCategory === 'sea' && in_array($routeMode, ['FOB', 'EXW'], true)) {
            $routeDetails = [
                'mode' => $routeMode,
                'origin' => [
                    'country' => $request->input('origin_country'),
                    'city' => $routeMode === 'EXW' ? $request->input('origin_city') : null,
                    'port' => $request->input('pol'),
                ],
                'destination' => [
                    'country' => $request->input('destination_country'),
                    'city' => $routeMode === 'EXW' ? $request->input('destination_city') : null,
                    'port' => $request->input('pod'),
                ],
            ];
        }

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
            'customer_company' => $request->input('company'),
            'customer_contact_name' => $request->input('contactName'),
            'customer_phone' => $phone,
            'customer_email' => $request->input('email'),
            'shipment_type' => $shipmentType,
            'container_type' => $shipmentType === 'FCL' ? $request->input('containerType') : null,
            'container_qty' => $shipmentType === 'FCL' ? (int) $request->input('containerQty') : null,
            'goods_description' => $request->input('goodsDescription'),
            'notes' => $request->input('notes'),
            'weight_kg' => (float) $request->input('weightKg'),
            'volume_cbm' => (float) $request->input('volumeCbm', 0),
            'dangerous' => (bool) $request->boolean('dangerous'),
            'documents' => $request->input('documents'),
            'attachments' => !empty($uploadedAttachments) ? json_encode($uploadedAttachments) : null,
            'pol' => $request->input('pol'),
            'pod' => $request->input('pod'),
            'route_details' => $routeDetails,
            'status' => 'new',
        ]);

        AuditLog::logAction('CREATE', 'bookings', $booking->id, [], [
            'source' => 'public_home',
            'customer_company' => $booking->customer_company,
            'pol' => $booking->pol,
            'pod' => $booking->pod,
            'status' => $booking->status,
        ]);

        $this->createNotification([
            'message' => "طلب شحن فوري جديد {$bookingId} من {$booking->customer_company} بانتظار التسعير",
            'booking_id' => $bookingId,
            'read' => false,
            'target' => 'pricing',
        ]);

        $this->createNotification([
            'message' => "طلب شحن فوري جديد {$bookingId} تمت إضافته إلى سجل الطلبات",
            'booking_id' => $bookingId,
            'read' => false,
            'target' => 'orders',
        ]);

        $redirectUrl = $portalCustomer instanceof Customer
            ? route('customer.shipments.show', $booking)
            : route('customer.register');

        return redirect($redirectUrl)
            ->with('success', "تم استلام طلب الشحن بنجاح. رقم الطلب: {$bookingId}")
            ->with('instant_booking_id', $bookingId);
    }

    public function redirectInstantBooking()
    {
        return redirect()
            ->route('customer.shipments.create')
            ->with('success', 'تم توحيد طلبات الشحن مع نموذج بوابة العميل الكامل.');
    }

    public function tracking(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $found = null;

        if ($q !== '') {
            $found = ShipmentTrack::with([
                'booking',
                'events' => fn ($query) => $query->orderBy('event_date'),
            ])
                ->where(function ($query) use ($q) {
                    $query->where('id', $q)
                        ->orWhere('booking_id', $q)
                        ->orWhereHas('booking', function ($bookingQuery) use ($q) {
                            $bookingQuery->where('carrier_booking_ref', 'LIKE', "%{$q}%");
                        });
                })
                ->first();
        }

        return view('tracking', compact('found', 'q'));
    }

    public function schedules(Request $request)
    {
        $origin = $request->query('origin', 'all');
        $destination = $request->query('destination', 'all');
        $search = $request->query('search', '');
        $dateFrom = $request->query('date_from', '');

        $publicScheduleQuery = Schedule::query()
            ->where('is_published', true)
            ->whereNotIn('status', ['draft', 'cancelled'])
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', today());
            });

        $query = (clone $publicScheduleQuery);

        if ($origin !== 'all') {
            $query->where('origin', $origin);
        }
        if ($destination !== 'all') {
            $query->where('destination', $destination);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('vessel', 'LIKE', "%{$search}%")
                  ->orWhere('voyage', 'LIKE', "%{$search}%")
                  ->orWhere('carrier', 'LIKE', "%{$search}%")
                  ->orWhere('service_name', 'LIKE', "%{$search}%")
                  ->orWhere('source_reference', 'LIKE', "%{$search}%");
            });
        }
        if ($dateFrom) {
            $query->whereDate('etd', '>=', $dateFrom);
        }

        $schedules = $query->orderBy('etd')->get();
        $ports = (clone $publicScheduleQuery)
            ->get(['origin', 'destination'])
            ->flatMap(fn (Schedule $schedule) => [$schedule->origin, $schedule->destination])
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('schedules', compact('schedules', 'ports', 'origin', 'destination', 'search', 'dateFrom'));
    }

    public function fleet()
    {
        $vessels = Vessel::all();
        return view('fleet', compact('vessels'));
    }

    public function ports(Request $request)
    {
        $ports = $this->publicPortsCollection();
        $query = trim((string) $request->query('q', ''));
        $selectedCountry = trim((string) $request->query('country', 'all'));
        $countryCounts = $ports->countBy(fn ($port) => (string) ($port->country ?? ''));

        $countries = $ports
            ->map(fn ($port) => [
                'name' => (string) ($port->country ?? ''),
                'code' => (string) ($port->country_code ?? ''),
                'flag' => (string) ($port->country_flag ?? ''),
                'count' => $countryCounts[(string) ($port->country ?? '')] ?? 0,
            ])
            ->filter(fn (array $country) => $country['name'] !== '')
            ->unique('name')
            ->sortBy('name')
            ->values();

        $filteredPorts = $ports
            ->when($selectedCountry !== '' && $selectedCountry !== 'all', fn ($items) => $items->where('country', $selectedCountry))
            ->when($query !== '', function ($items) use ($query) {
                $needle = mb_strtolower($query);

                return $items->filter(function ($port) use ($needle) {
                    return str_contains(mb_strtolower((string) $port->name), $needle)
                        || str_contains(mb_strtolower((string) $port->code), $needle)
                        || str_contains(mb_strtolower((string) $port->country), $needle);
                });
            })
            ->values();

        $featuredCountries = $ports
            ->groupBy('country')
            ->map(function ($items, $country) {
                $first = $items->first();

                return [
                    'country' => $country ?: 'Global',
                    'country_code' => (string) ($first->country_code ?? ''),
                    'country_flag' => (string) ($first->country_flag ?? ''),
                    'count' => $items->count(),
                    'ports' => $items->take(4)->values(),
                ];
            })
            ->sortByDesc('count')
            ->take(8)
            ->values();

        $portGroups = $filteredPorts
            ->groupBy('country')
            ->sortKeys()
            ->map(fn ($items) => $items->values());

        $stats = [
            'ports' => $ports->count(),
            'countries' => $countries->count(),
            'filtered' => $filteredPorts->count(),
            'featured' => $featuredCountries->count(),
        ];

        return view('public.ports', compact('ports', 'filteredPorts', 'portGroups', 'countries', 'featuredCountries', 'stats', 'query', 'selectedCountry'));
    }

    public function agents()
    {
        $agents = Agent::orderBy('type')
            ->orderBy('country')
            ->orderBy('region')
            ->orderBy('name')
            ->get();

        return view('agents', compact('agents'));
    }

    public function contact()
    {
        $isArabic = app()->getLocale() === 'ar';
        $contactCards = [];
        foreach ([...config('company.phones'), config('company.landline')] as $phone) {
            $contactCards[] = [
                'icon' => 'phone-call',
                'label' => $isArabic ? 'اتصال مباشر' : 'Call us',
                'value' => $phone['display'],
                'href' => 'tel:' . $phone['tel'],
                'hint' => '',
                'dir' => 'ltr',
            ];
        }
        foreach (config('company.emails') as $email) {
            $contactCards[] = [
                'icon' => 'mail',
                'label' => $isArabic ? 'البريد الإلكتروني' : 'Email',
                'value' => $email,
                'href' => 'mailto:' . $email,
                'hint' => '',
                'dir' => 'ltr',
            ];
        }
        $contactCards[] = [
            'icon' => 'map-pin',
            'label' => $isArabic ? 'العنوان' : 'Address',
            'value' => config('company.address.' . app()->getLocale()),
            'hint' => '',
            'dir' => $isArabic ? 'rtl' : 'ltr',
        ];

        return view('public.contact', compact('contactCards'));
    }

    private function makePublicBookingId(): string
    {
        return SystemId::makeSequential(Booking::class, 'BK');
    }

    private function publicPortSelectorData(): array
    {
        $ports = Port::where('is_active', true)
            ->orderBy('country')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($ports->isNotEmpty()) {
            $portCountries = $ports
                ->map(fn (Port $port) => PortCatalog::countryOptionFromPort($port))
                ->filter()
                ->unique('name')
                ->sortBy('name')
                ->values();

            $portsForBooking = $ports->map(function (Port $port) {
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

        $portsForBooking = collect(config('ports.ports', []))
            ->map(function (array $portData) {
                $country = PortCatalog::resolveCountry(
                    $portData['country'] ?? null,
                    $portData['country_code'] ?? null,
                    $portData['country_flag'] ?? null
                );

                return [
                    'name' => (string) ($portData['name'] ?? ''),
                    'code' => $portData['code'] ?? null,
                    'country' => $country['country'],
                    'country_code' => $country['country_code'],
                    'country_flag' => $country['country_flag'],
                ];
            })
            ->filter(fn (array $port) => $port['name'] !== '' && filled($port['country']))
            ->values();

        $portCountries = $portsForBooking
            ->map(fn (array $port) => [
                'name' => $port['country'],
                'code' => $port['country_code'],
                'flag' => $port['country_flag'],
            ])
            ->unique('name')
            ->sortBy('name')
            ->values();

        return [$portCountries, $portsForBooking];
    }

    private function publicPortsCollection()
    {
        $ports = Port::where('is_active', true)
            ->orderBy('country')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($ports->isNotEmpty()) {
            return $ports;
        }

        return collect(config('ports.ports', []))
            ->map(function (array $portData) {
                $country = PortCatalog::resolveCountry(
                    $portData['country'] ?? null,
                    $portData['country_code'] ?? null,
                    $portData['country_flag'] ?? null
                );

                return (object) [
                    'name' => (string) ($portData['name'] ?? ''),
                    'code' => (string) ($portData['code'] ?? ''),
                    'country' => (string) $country['country'],
                    'country_code' => (string) $country['country_code'],
                    'country_flag' => (string) $country['country_flag'],
                    'is_active' => true,
                ];
            })
            ->filter(fn ($port) => $port->name !== '' && $port->country !== '')
            ->sortBy([
                ['country', 'asc'],
                ['name', 'asc'],
            ])
            ->values();
    }

    private function composePublicPhoneNumber(?string $phone, ?string $countryCode): string
    {
        $phone = trim((string) $phone);
        $countryCode = trim((string) $countryCode);

        if ($phone === '' || str_starts_with($phone, '+')) {
            return $phone;
        }

        if (str_starts_with($phone, '00')) {
            return '+' . ltrim(substr($phone, 2), '+');
        }

        if ($countryCode === '') {
            return $phone;
        }

        $dialCode = '+' . ltrim($countryCode, '+');
        $localDigits = preg_replace('/\D+/', '', $phone) ?? '';

        return $dialCode . ltrim($localDigits, '0');
    }
}

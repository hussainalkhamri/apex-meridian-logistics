@extends('layouts.customer')

@section('title', __('طلب شحنة جديدة'))

@section('content')
@php
    $customer = auth('customer')->user();
    $repeatBooking = $repeatBooking ?? null;
    $shippingRate = $shippingRate ?? null;
    $shippingRateSeed = $shippingRateSeed ?? null;
    $repeatCategory = match ($repeatBooking?->shipment_type) {
        'LAND' => 'land',
        'AIR' => 'air',
        'DOOR_TO_DOOR' => 'contracting',
        default => 'sea',
    };
    $activeTab = old('shipping_category', $repeatBooking ? $repeatCategory : data_get($shippingRateSeed, 'shipping_category', 'sea'));
    $routeMode = old('route_mode', data_get($repeatBooking?->route_details, 'mode', data_get($shippingRateSeed, 'route_mode', 'FOB')));
    $isExw = $routeMode === 'EXW';
    $repeatBookingPayload = null;

    if ($repeatBooking) {
        $repeatBookingPayload = [
            'shipping_category' => $repeatCategory,
            'route_mode' => data_get($repeatBooking->route_details, 'mode', 'FOB'),
            'origin_country' => data_get($repeatBooking->route_details, 'origin.country'),
            'destination_country' => data_get($repeatBooking->route_details, 'destination.country'),
            'origin_city' => data_get($repeatBooking->route_details, 'origin.city'),
            'destination_city' => data_get($repeatBooking->route_details, 'destination.city'),
            'pol' => $repeatBooking->pol,
            'pod' => $repeatBooking->pod,
            'shipmentType' => $repeatBooking->shipment_type === 'LAND' ? 'LAND' : $repeatBooking->shipment_type,
            'containerType' => $repeatBooking->container_type,
            'containerQty' => $repeatBooking->container_qty,
            'containers' => data_get($repeatBooking->cargo_details, 'containers'),
            'goodsDescription' => $repeatBooking->goods_description,
            'weightKg' => $repeatBooking->weight_kg,
            'volumeCbm' => $repeatBooking->volume_cbm,
            'landCartonCount' => data_get($repeatBooking->cargo_details, 'land_carton_count'),
            'documents' => $repeatBooking->documents,
            'notes' => $repeatBooking->notes,
            'dangerous' => (bool) $repeatBooking->dangerous,
            'customs_requested' => (bool) data_get($repeatBooking->route_details, 'services.customs_requested'),
            'door_delivery_requested' => (bool) data_get($repeatBooking->route_details, 'services.door_delivery_requested'),
            'cargo_insurance_requested' => (bool) data_get($repeatBooking->route_details, 'services.cargo_insurance_requested'),
        ];
    } elseif ($shippingRateSeed) {
        $repeatBookingPayload = $shippingRateSeed;
    }
    $lockedCustomerFields = [
        ['label' => __('اسم الشركة'), 'value' => $customer?->company],
        ['label' => __('الشخص المسؤول'), 'value' => $customer?->contact_name ?: $customer?->company],
        ['label' => __('رقم الهاتف'), 'value' => $customer?->phone],
        ['label' => __('البريد الإلكتروني'), 'value' => $customer?->email],
    ];
@endphp

<div>
    <section class="portal-intro rounded-xl bg-hero text-white shadow-card">
        <span class="inline-flex items-center rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-bold text-white">
            Customer Booking Request
        </span>
        <h1 class="mt-3 text-2xl font-bold">{{ __('messages.request_new_shipment') }}</h1>
        <p class="mt-3 max-w-2xl text-sm leading-8 text-white/80">
            {{ __('portal.review_hint') }} {{ __('portal.required') }}
        </p>
        <div class="mt-4 flex flex-wrap justify-start gap-2" aria-label="{{ __('portal.method') }}">
            @foreach([
                'sea' => ['label' => __('البحري'), 'icon' => 'ship'],
                'land' => ['label' => __('البري'), 'icon' => 'truck'],
                'air' => ['label' => __('الجوي'), 'icon' => 'plane'],
                'customs' => ['label' => __('التخليص الجمركي'), 'icon' => 'file-check'],
                'contracting' => ['label' => 'Door to Door', 'icon' => 'package-check'],
            ] as $tab => $item)
                <button type="button" data-customer-tab="{{ $tab }}" aria-controls="customer-tab-{{ $tab }}" aria-pressed="{{ $activeTab === $tab ? 'true' : 'false' }}" class="customer-tab-btn inline-flex items-center gap-2 rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                    <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4"></i>
                    {{ $item['label'] }}
                </button>
            @endforeach
        </div>
    </section>

    <section class="py-6">
        <div class="mx-auto max-w-5xl overflow-hidden rounded-2xl border border-border bg-card shadow-elegant">
            @if($repeatBooking)
                <div class="flex items-start gap-3 border-b border-accent/20 bg-accent/10 p-4 text-sm">
                    <i data-lucide="copy-plus" class="mt-0.5 h-5 w-5 shrink-0 text-accent"></i>
                    <div>
                        <div class="font-black">تم تجهيز النموذج من الطلب {{ $repeatBooking->id }}</div>
                        <div class="mt-1 text-xs leading-6 text-muted-foreground">{{ __('راجع البيانات ثم أرسلها كطلب جديد. المرفقات القديمة لا يتم نسخها تلقائيًا.') }}</div>
                    </div>
                </div>
            @elseif($shippingRate)
                <div class="flex items-start gap-3 border-b border-accent/20 bg-accent/10 p-4 text-sm">
                    <i data-lucide="route" class="mt-0.5 h-5 w-5 shrink-0 text-accent"></i>
                    <div>
                        <div class="font-black">تم تجهيز النموذج من مسار السعر {{ $shippingRate->origin_port }} ← {{ $shippingRate->destination_port }}</div>
                        <div class="mt-1 text-xs leading-6 text-muted-foreground">{{ __('راجع الدولة والميناء ونوع الشحنة، ثم أكمل تفاصيل البضاعة وأرسل الطلب للتسعير.') }}</div>
                    </div>
                </div>
            @endif

            @if($errors->any())
                <div class="border-b border-destructive/20 bg-destructive/10 p-4 text-sm text-destructive">
                    <div class="font-black">{{ __('راجع بيانات الطلب') }}</div>
                    <ul class="mt-2 list-disc space-y-1 pr-5 text-xs">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="customer-tab-sea" class="customer-tab-content p-6">
                <form data-review-request data-dirty-check action="{{ route('customer.shipments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <input type="hidden" name="shipping_category" value="sea">

                    <x-customer-lock-card :fields="$lockedCustomerFields" />

                    <div class="space-y-4">
                        <h2 class="flex items-center gap-2 border-b border-border pb-2 text-lg font-bold text-primary">
                            <i data-lucide="anchor" class="h-5 w-5 text-accent"></i>
                            <span>{{ __('بيانات المسار') }}</span>
                        </h2>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label for="customer-route-mode" class="text-xs font-bold text-foreground">{{ __('شرط الشحن *') }}</label>
                                <select name="route_mode" id="customer-route-mode" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    <option value="FOB" @selected($routeMode === 'FOB')>FOB</option>
                                    <option value="EXW" @selected($routeMode === 'EXW')>EXW</option>
                                </select>
                            </div>
                        </div>

                        <div data-customer-route-panel="FOB" class="{{ $isExw ? 'hidden' : 'grid' }} gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label for="customer-fob-origin-country" class="text-xs font-bold text-foreground">{{ __('دولة الشحن *') }}</label>
                                <select name="origin_country" id="customer-fob-origin-country" data-customer-country-select data-port-target="customer-fob-pol" @disabled($isExw) class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    <option value="">{{ __('اختر الدولة') }}</option>
                                    @foreach($portCountries as $country)
                                        <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}" @selected(old('origin_country') === $country['name'])>{{ $country['name'] }}</option>
                                    @endforeach
                                </select>
                                <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                                    <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                                    <span></span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label for="customer-fob-pol" class="text-xs font-bold text-foreground">{{ __('ميناء الشحن (POL) *') }}</label>
                                <select id="customer-fob-pol" data-customer-port-select class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    <option value="">{{ __('اختر الدولة أولاً') }}</option>
                                </select>
                                <input type="hidden" name="pol" value="{{ old('pol') }}" data-customer-port-value @disabled($isExw)>
                                <input type="text" value="{{ old('pol') }}" data-customer-custom-port-input disabled placeholder="{{ __('اكتب اسم الميناء') }}" class="hidden h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                <p data-customer-custom-port-hint class="hidden text-[11px] text-muted-foreground">{{ __('إذا لم يظهر الميناء، اختر إدخال يدوي واكتبه هنا.') }}</p>
                            </div>
                            <div class="space-y-2">
                                <label for="customer-fob-destination-country" class="text-xs font-bold text-foreground">{{ __('دولة الوصول *') }}</label>
                                <select name="destination_country" id="customer-fob-destination-country" data-customer-country-select data-port-target="customer-fob-pod" @disabled($isExw) class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    <option value="">{{ __('اختر الدولة') }}</option>
                                    @foreach($portCountries as $country)
                                        <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}" @selected(old('destination_country') === $country['name'])>{{ $country['name'] }}</option>
                                    @endforeach
                                </select>
                                <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                                    <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                                    <span></span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label for="customer-fob-pod" class="text-xs font-bold text-foreground">{{ __('ميناء الوصول (POD) *') }}</label>
                                <select id="customer-fob-pod" data-customer-port-select class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    <option value="">{{ __('اختر الدولة أولاً') }}</option>
                                </select>
                                <input type="hidden" name="pod" value="{{ old('pod') }}" data-customer-port-value @disabled($isExw)>
                                <input type="text" value="{{ old('pod') }}" data-customer-custom-port-input disabled placeholder="{{ __('اكتب اسم الميناء') }}" class="hidden h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                <p data-customer-custom-port-hint class="hidden text-[11px] text-muted-foreground">{{ __('إذا لم يظهر الميناء، اختر إدخال يدوي واكتبه هنا.') }}</p>
                            </div>
                        </div>

                        <div data-customer-route-panel="EXW" class="{{ $isExw ? 'grid' : 'hidden' }} gap-4 md:grid-cols-2">
                            <div class="rounded-lg border border-border/70 bg-secondary/10 p-3 md:col-span-2">
                                <h3 class="mb-3 text-sm font-bold text-foreground">{{ __('الانطلاق') }}</h3>
                                <div class="grid gap-4 md:grid-cols-3">
                                    <div class="space-y-2">
                                        <label for="customer-exw-origin-country" class="text-xs font-bold text-foreground">{{ __('دولة *') }}</label>
                                        <select name="origin_country" id="customer-exw-origin-country" data-customer-country-select data-port-target="customer-exw-pol" @disabled(! $isExw) class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                            <option value="">{{ __('اختر الدولة') }}</option>
                                            @foreach($portCountries as $country)
                                                <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}" @selected(old('origin_country') === $country['name'])>{{ $country['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                                            <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                                            <span></span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label for="customer-exw-origin-city" class="text-xs font-bold text-foreground">{{ __('مدينة *') }}</label>
                                        <input type="text" name="origin_city" id="customer-exw-origin-city" value="{{ old('origin_city') }}" @disabled(! $isExw) class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div class="space-y-2">
                                        <label for="customer-exw-pol" class="text-xs font-bold text-foreground">{{ __('ميناء الشحن (POL) *') }}</label>
                                        <select id="customer-exw-pol" data-customer-port-select class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                            <option value="">{{ __('اختر الدولة أولاً') }}</option>
                                        </select>
                                        <input type="hidden" name="pol" value="{{ old('pol') }}" data-customer-port-value @disabled(! $isExw)>
                                        <input type="text" value="{{ old('pol') }}" data-customer-custom-port-input disabled placeholder="{{ __('اكتب اسم الميناء') }}" class="hidden h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-lg border border-border/70 bg-secondary/10 p-3 md:col-span-2">
                                <h3 class="mb-3 text-sm font-bold text-foreground">{{ __('الوصول') }}</h3>
                                <div class="grid gap-4 md:grid-cols-3">
                                    <div class="space-y-2">
                                        <label for="customer-exw-destination-country" class="text-xs font-bold text-foreground">{{ __('دولة *') }}</label>
                                        <select name="destination_country" id="customer-exw-destination-country" data-customer-country-select data-port-target="customer-exw-pod" @disabled(! $isExw) class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                            <option value="">{{ __('اختر الدولة') }}</option>
                                            @foreach($portCountries as $country)
                                                <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}" @selected(old('destination_country') === $country['name'])>{{ $country['name'] }}</option>
                                            @endforeach
                                        </select>
                                        <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                                            <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                                            <span></span>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <label for="customer-exw-destination-city" class="text-xs font-bold text-foreground">{{ __('مدينة *') }}</label>
                                        <input type="text" name="destination_city" id="customer-exw-destination-city" value="{{ old('destination_city') }}" @disabled(! $isExw) class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    </div>
                                    <div class="space-y-2">
                                        <label for="customer-exw-pod" class="text-xs font-bold text-foreground">{{ __('ميناء الوصول (POD) *') }}</label>
                                        <select id="customer-exw-pod" data-customer-port-select class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                            <option value="">{{ __('اختر الدولة أولاً') }}</option>
                                        </select>
                                        <input type="hidden" name="pod" value="{{ old('pod') }}" data-customer-port-value @disabled(! $isExw)>
                                        <input type="text" value="{{ old('pod') }}" data-customer-custom-port-input disabled placeholder="{{ __('اكتب اسم الميناء') }}" class="hidden h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('customer.partials.shipment-cargo-fields', ['mode' => 'sea'])

                    <div class="portal-form-actions flex flex-wrap justify-end gap-3 border-t border-border pt-4">
                        <a href="{{ route('customer.dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-border bg-card px-6 text-sm font-bold hover:bg-secondary/40">{{ __('إلغاء') }}</a>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground shadow hover:opacity-95">
                            <i data-lucide="send" class="h-4 w-4"></i>
                            <span>{{ __('portal.review') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <div id="customer-tab-land" class="customer-tab-content hidden p-6">
                <form data-review-request data-dirty-check action="{{ route('customer.shipments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <input type="hidden" name="shipping_category" value="land">
                    <input type="hidden" name="shipmentType" value="LAND">

                    <x-customer-lock-card :fields="$lockedCustomerFields" />

                    <div class="space-y-4">
                        <h2 class="flex items-center gap-2 border-b border-border pb-2 text-lg font-bold text-primary">
                            <i data-lucide="truck" class="h-5 w-5 text-accent"></i>
                            <span>{{ __('تفاصيل الشحنة البرية') }}</span>
                        </h2>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="space-y-2">
                                <label for="land-pol" class="text-xs font-bold text-foreground">{{ __('نقطة الانطلاق *') }}</label>
                                <input type="text" name="pol" id="land-pol" value="{{ old('pol') }}" required placeholder="{{ __('مثال: الرياض') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                            </div>
                            <div class="space-y-2">
                                <label for="land-pod" class="text-xs font-bold text-foreground">{{ __('نقطة الوصول *') }}</label>
                                <input type="text" name="pod" id="land-pod" value="{{ old('pod') }}" required placeholder="{{ __('مثال: عدن') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                            </div>
                        </div>
                    </div>

                    @include('customer.partials.shipment-cargo-fields', ['mode' => 'land'])

                    <div class="portal-form-actions flex flex-wrap justify-end gap-3 border-t border-border pt-4">
                        <a href="{{ route('customer.dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-border bg-card px-6 text-sm font-bold hover:bg-secondary/40">{{ __('إلغاء') }}</a>
                        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground shadow hover:opacity-95">
                            <i data-lucide="send" class="h-4 w-4"></i>
                            {{ __('portal.review') }}
                        </button>
                    </div>
                </form>
            </div>

            <div id="customer-tab-air" class="customer-tab-content hidden p-6">
                @include('customer.partials.service-shipment-form', [
                    'lockedCustomerFields' => $lockedCustomerFields,
                    'service' => [
                        'tab' => 'air',
                        'category' => 'air',
                        'route_mode' => 'AIR',
                        'shipment_type' => 'AIR',
                        'icon' => 'plane',
                        'title' => __('تفاصيل الشحن الجوي'),
                    ],
                ])
            </div>

            <div id="customer-tab-customs" class="customer-tab-content hidden p-6">
                <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/80 bg-secondary/15 px-4 py-16 text-center">
                    <div class="mb-4 grid h-16 w-16 place-items-center rounded-2xl bg-accent/10 text-accent">
                        <i data-lucide="file-check" class="h-8 w-8"></i>
                    </div>
                    <h3 class="text-xl font-bold text-foreground">{{ __('التخليص الجمركي مرتبط بالشحنة') }}</h3>
                    <p class="mt-2 max-w-md text-sm leading-7 text-muted-foreground">{{ __('للطلبات المسموحة، فعّل خيار التخليص داخل نموذج الشحن البحري أو الجوي أو Door to Door، وسيصل تنبيه مباشر لقسم التخليص.') }}</p>
                </div>
            </div>

            <div id="customer-tab-contracting" class="customer-tab-content hidden p-6">
                @include('customer.partials.service-shipment-form', [
                    'lockedCustomerFields' => $lockedCustomerFields,
                    'service' => [
                        'tab' => 'door',
                        'category' => 'door_to_door',
                        'route_mode' => 'DOOR_TO_DOOR',
                        'shipment_type' => 'DOOR_TO_DOOR',
                        'icon' => 'package-check',
                        'title' => __('تفاصيل Door to Door'),
                    ],
                ])
            </div>
        </div>
    </section>
</div>
@endsection

@section('scripts')
@include('customer.partials.request-review')
<script>
    const customerManagedPorts = @json($portsForBooking);
    const customerManagedAirports = @json($airportsForBooking ?? []);
    const CUSTOMER_CUSTOM_PORT_VALUE = '__custom_port__';
    const initialCustomerTab = @json($activeTab);
    const repeatBookingSeed = @json($repeatBookingPayload);
    const customerCountryDropdowns = new WeakMap();

    function customerFlagImageUrl(countryCode) {
        return countryCode ? `/flags/${countryCode.toLowerCase()}.svg` : '';
    }

    function customerFlagEmoji(countryCode) {
        const code = (countryCode || '').trim().toUpperCase();
        if (!/^[A-Z]{2}$/.test(code)) return '';

        return Array.from(code)
            .map(char => String.fromCodePoint(127397 + char.charCodeAt(0)))
            .join('');
    }

    function customerPortLabel(port) {
        const flag = customerFlagEmoji(port.country_code);
        const code = port.code ? ` - ${port.code}` : '';

        return `${flag ? `${flag} ` : ''}${port.name}${code}`;
    }

    function decorateCustomerCountryOptions(countrySelect) {
        Array.from(countrySelect.options).forEach(option => {
            if (!option.value || option.dataset.flagDecorated === '1') return;

            const flag = customerFlagEmoji(option.dataset.countryCode);
            if (flag) {
                option.textContent = `${flag} ${option.textContent.trim()}`;
            }

            option.dataset.flagDecorated = '1';
        });
    }

    function syncCustomerCountryDropdown(countrySelect) {
        const dropdown = customerCountryDropdowns.get(countrySelect);
        if (!dropdown) return;

        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        const countryCode = selectedOption?.dataset.countryCode || '';
        const label = countrySelect.value || dropdown.placeholder;

        dropdown.button.disabled = countrySelect.disabled;
        dropdown.button.classList.toggle('opacity-60', countrySelect.disabled);
        dropdown.button.classList.toggle('cursor-not-allowed', countrySelect.disabled);
        dropdown.current.textContent = label;

        if (countryCode) {
            dropdown.flag.src = customerFlagImageUrl(countryCode);
            dropdown.flag.alt = `${countrySelect.value} flag`;
            dropdown.flag.classList.remove('hidden');
        } else {
            dropdown.flag.removeAttribute('src');
            dropdown.flag.classList.add('hidden');
        }
    }

    function closeCustomerCountryDropdown(countrySelect) {
        const dropdown = customerCountryDropdowns.get(countrySelect);
        dropdown?.list.classList.add('hidden');
    }

    function renderCustomerCountryDropdownList(countrySelect) {
        const dropdown = customerCountryDropdowns.get(countrySelect);
        if (!dropdown) return;

        dropdown.list.innerHTML = '';

        Array.from(countrySelect.options)
            .filter(option => option.value)
            .forEach(option => {
                const countryCode = option.dataset.countryCode || '';
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'flex min-h-10 w-full items-center gap-2 px-3 py-2 text-right text-sm hover:bg-secondary/60';

                if (countryCode) {
                    const image = document.createElement('img');
                    image.src = customerFlagImageUrl(countryCode);
                    image.alt = '';
                    image.loading = 'lazy';
                    image.className = 'h-4 w-6 shrink-0 rounded-[2px] border border-border object-cover';
                    image.addEventListener('error', () => image.remove());
                    item.appendChild(image);
                }

                const label = document.createElement('span');
                label.className = 'min-w-0 flex-1 truncate';
                label.textContent = option.value;
                item.appendChild(label);

                const code = document.createElement('span');
                code.className = 'text-[10px] font-bold text-muted-foreground';
                code.textContent = countryCode;
                item.appendChild(code);

                item.addEventListener('mousedown', event => {
                    event.preventDefault();
                    countrySelect.value = option.value;
                    closeCustomerCountryDropdown(countrySelect);
                    countrySelect.dispatchEvent(new Event('change', { bubbles: true }));
                });

                dropdown.list.appendChild(item);
            });
    }

    function initCustomerCountryDropdown(countrySelect) {
        if (countrySelect.dataset.customerCountryDropdownReady === '1') return;
        countrySelect.dataset.customerCountryDropdownReady = '1';

        const wrapper = document.createElement('div');
        wrapper.className = 'relative';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'flex h-10 w-full items-center gap-2 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent';

        const flag = document.createElement('img');
        flag.alt = '';
        flag.className = 'hidden h-4 w-6 shrink-0 rounded-[2px] border border-border object-cover';
        flag.addEventListener('error', () => flag.classList.add('hidden'));

        const current = document.createElement('span');
        current.className = 'min-w-0 flex-1 truncate text-right';

        const icon = document.createElement('span');
        icon.className = 'text-xs text-muted-foreground';
        icon.textContent = '⌄';

        const list = document.createElement('div');
        list.className = 'hidden absolute z-50 mt-1 w-full overflow-y-auto overscroll-contain rounded-lg border border-border bg-card shadow-elegant';
        list.style.maxHeight = '22.5rem';

        button.appendChild(flag);
        button.appendChild(current);
        button.appendChild(icon);
        wrapper.appendChild(button);
        wrapper.appendChild(list);

        countrySelect.classList.add('hidden');
        countrySelect.insertAdjacentElement('afterend', wrapper);

        customerCountryDropdowns.set(countrySelect, {
            wrapper,
            button,
            current,
            flag,
            list,
            placeholder: countrySelect.options[0]?.textContent?.trim() || 'اختر الدولة',
        });

        button.addEventListener('click', event => {
            event.stopPropagation();
            if (countrySelect.disabled) return;

            document.querySelectorAll('[data-customer-country-select]').forEach(otherSelect => {
                if (otherSelect !== countrySelect) closeCustomerCountryDropdown(otherSelect);
            });

            renderCustomerCountryDropdownList(countrySelect);
            list.classList.toggle('hidden');
        });

        countrySelect.addEventListener('change', () => syncCustomerCountryDropdown(countrySelect));
        syncCustomerCountryDropdown(countrySelect);
    }

    function updateCustomerCountryFlagPreview(countrySelect) {
        const preview = countrySelect.closest('.space-y-2')?.querySelector('[data-country-flag-preview]');
        if (!preview) return;

        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        const countryCode = selectedOption?.dataset.countryCode || '';
        const hiddenCountryCode = document.querySelector(`[data-country-code-for="${countrySelect.id}"]`);
        const image = preview.querySelector('img');
        const text = preview.querySelector('span');
        const flag = customerFlagEmoji(countryCode);

        if (hiddenCountryCode) {
            hiddenCountryCode.value = countryCode;
        }

        if (!countryCode) {
            preview.classList.add('hidden');
            preview.classList.remove('flex');
            image?.removeAttribute('src');
            if (text) text.textContent = '';
            return;
        }

        if (image) {
            image.classList.remove('hidden');
            image.src = customerFlagImageUrl(countryCode);
            image.alt = `${countrySelect.value} flag`;
            image.onerror = () => image.classList.add('hidden');
        }

        if (text) {
            text.textContent = `${flag ? `${flag} ` : ''}${countrySelect.value}`;
        }

        preview.classList.remove('hidden');
        preview.classList.add('flex');
    }

    function customerPortControls(select) {
        const wrapper = select.closest('.space-y-2');

        return {
            valueInput: wrapper?.querySelector('[data-customer-port-value]') || null,
            customInput: wrapper?.querySelector('[data-customer-custom-port-input]') || null,
            customHint: wrapper?.querySelector('[data-customer-custom-port-hint]') || null,
        };
    }

    function syncCustomerPortValue(select) {
        const { valueInput, customInput, customHint } = customerPortControls(select);
        const isCustom = select.value === CUSTOMER_CUSTOM_PORT_VALUE;
        const disabled = select.disabled || select.closest('[data-customer-route-panel]')?.classList.contains('hidden');

        if (valueInput) {
            valueInput.disabled = disabled;
            valueInput.value = isCustom ? (customInput?.value || '').trim() : select.value;
        }

        if (customInput) {
            customInput.classList.toggle('hidden', !isCustom);
            customInput.disabled = disabled || !isCustom;
            customInput.required = !disabled && isCustom;
        }

        if (customHint) {
            customHint.classList.toggle('hidden', !isCustom);
        }
    }

    function customerAirportCode(value) {
        return String(value || '').trim().toUpperCase().replace(/[^A-Z0-9]/g, '');
    }

    function customerAirportLabel(airport) {
        const code = customerAirportCode(airport.iata_code || airport.code);
        const city = airport.city ? ` - ${airport.city}` : '';
        const name = airport.name ? ` - ${airport.name}` : '';

        return `${code}${city}${name}`;
    }

    function findCustomerAirport(code) {
        const normalized = customerAirportCode(code);

        return customerManagedAirports.find(airport => customerAirportCode(airport.iata_code || airport.code) === normalized) || null;
    }

    function fillCustomerAirportFields(select) {
        const airport = findCustomerAirport(select.value);
        const side = select.dataset.airportSide;
        const nameInput = document.getElementById(`customer-${side}-airport-name`);
        const cityInput = document.getElementById(`customer-${side}-city-air`);

        if (nameInput) nameInput.value = airport?.name || '';
        if (cityInput && airport?.city && !cityInput.value) cityInput.value = airport.city;
    }

    function renderCustomerAirports(countrySelect) {
        const airportSelect = document.getElementById(countrySelect.dataset.customerAirportTarget || '');
        if (!airportSelect) return;

        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        const selectedCountry = countrySelect.value;
        const selectedCountryCode = selectedOption?.dataset.countryCode || '';
        const previous = customerAirportCode(airportSelect.value || airportSelect.dataset.selectedAirport);
        const airports = selectedCountry
            ? customerManagedAirports.filter(airport => {
                const code = String(airport.country_code || '').toUpperCase();
                if (selectedCountryCode && code) return code === selectedCountryCode;
                return String(airport.country || '').toLowerCase() === selectedCountry.toLowerCase();
            })
            : [];

        airportSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = selectedCountry ? 'اختر رمز المطار' : 'اختر الدولة أولاً';
        airportSelect.appendChild(placeholder);

        airports.forEach(airport => {
            const code = customerAirportCode(airport.iata_code || airport.code);
            if (!code) return;

            const option = document.createElement('option');
            option.value = code;
            option.textContent = customerAirportLabel(airport);
            airportSelect.appendChild(option);
        });

        if (previous && airports.some(airport => customerAirportCode(airport.iata_code || airport.code) === previous)) {
            airportSelect.value = previous;
        }

        airportSelect.disabled = !selectedCountry;
        airportSelect.dataset.selectedAirport = airportSelect.value || '';
        fillCustomerAirportFields(airportSelect);

        const hint = airportSelect.closest('.space-y-2')?.querySelector('[data-customer-airport-hint]');
        if (hint) {
            hint.textContent = selectedCountry
                ? (airports.length ? `تظهر ${airports.length} مطارات خاصة بـ ${selectedCountry}.` : 'لا توجد مطارات محفوظة لهذه الدولة.')
                : 'تظهر رموز المطارات الخاصة بالدولة المختارة فقط.';
        }
    }

    function renderCustomerPorts(countrySelect) {
        initCustomerCountryDropdown(countrySelect);
        decorateCustomerCountryOptions(countrySelect);
        updateCustomerCountryFlagPreview(countrySelect);
        syncCustomerCountryDropdown(countrySelect);

        if (countrySelect.dataset.customerAirportTarget) {
            renderCustomerAirports(countrySelect);
            return;
        }

        const portSelect = document.getElementById(countrySelect.dataset.portTarget || '');
        if (!portSelect) return;

        const { valueInput, customInput } = customerPortControls(portSelect);
        const previous = valueInput?.value || portSelect.value;
        const ports = customerManagedPorts.filter(port => port.country === countrySelect.value);
        portSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = countrySelect.value ? 'اختر الميناء' : 'اختر الدولة أولاً';
        portSelect.appendChild(placeholder);

        ports.forEach(port => {
            const option = document.createElement('option');
            option.value = port.name;
            option.textContent = customerPortLabel(port);
            portSelect.appendChild(option);
        });

        if (countrySelect.value) {
            const custom = document.createElement('option');
            custom.value = CUSTOMER_CUSTOM_PORT_VALUE;
            custom.textContent = 'ميناء آخر / إدخال يدوي';
            portSelect.appendChild(custom);
        }

        if (ports.some(port => port.name === previous)) {
            portSelect.value = previous;
        } else if (previous) {
            portSelect.value = CUSTOMER_CUSTOM_PORT_VALUE;
            if (customInput && !customInput.value) customInput.value = previous;
        }

        portSelect.disabled = !countrySelect.value || countrySelect.disabled;
        syncCustomerPortValue(portSelect);
    }

    function setCustomerRouteMode(mode) {
        document.querySelectorAll('[data-customer-route-panel]').forEach(panel => {
            const isActive = panel.dataset.customerRoutePanel === mode;
            panel.classList.toggle('hidden', !isActive);
            panel.classList.toggle('grid', isActive);
            panel.querySelectorAll('input, select, textarea').forEach(field => {
                field.disabled = !isActive;
            });
        });

        document.querySelectorAll('[data-customer-country-select]').forEach(renderCustomerPorts);
        document.querySelectorAll('[data-customer-country-select]').forEach(syncCustomerCountryDropdown);
    }

    function setCustomerTab(tab) {
        document.querySelectorAll('.customer-tab-content').forEach(content => content.classList.add('hidden'));
        document.getElementById(`customer-tab-${tab}`)?.classList.remove('hidden');
        document.querySelectorAll('.customer-tab-btn').forEach(button => {
            const active = button.dataset.customerTab === tab;
            button.setAttribute('aria-pressed', String(active));
            button.classList.toggle('bg-white/20', active);
            button.classList.toggle('bg-white/10', !active);
            button.classList.toggle('text-accent', active);
        });
    }

    function customerFclRows() {
        return Array.from(document.querySelectorAll('[data-customer-fcl-row]'));
    }

    function reindexCustomerFclRows() {
        customerFclRows().forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(field => {
                field.name = field.name.replace(/containers\[\d+\]/, `containers[${index}]`);
            });
        });
    }

    function syncCustomerFclPrimaryFields() {
        const firstRow = customerFclRows()[0];
        const typeInput = document.getElementById('customer-container-type');
        const qtyInput = document.getElementById('customer-container-qty');
        const type = firstRow?.querySelector('[data-customer-fcl-container-type]')?.value || typeInput?.value || '40HC';
        const qty = firstRow?.querySelector('[data-customer-fcl-container-qty]')?.value || qtyInput?.value || 1;

        if (typeInput) typeInput.value = type;
        if (qtyInput) qtyInput.value = Math.max(1, Number(qty) || 1);
    }

    function bindCustomerFclRow(row) {
        if (!row || row.dataset.customerFclReady === '1') return;
        row.dataset.customerFclReady = '1';

        row.querySelectorAll('[data-customer-fcl-container-type], [data-customer-fcl-container-qty]').forEach(field => {
            field.addEventListener('input', syncShipmentTypeFields);
            field.addEventListener('change', syncShipmentTypeFields);
        });

        row.querySelector('[data-customer-remove-fcl-row]')?.addEventListener('click', () => {
            if (customerFclRows().length <= 1) {
                return;
            }

            row.remove();
            reindexCustomerFclRows();
            syncShipmentTypeFields();
            window.lucide?.createIcons();
        });
    }

    function addCustomerFclRow(container = {}) {
        const wrapper = document.querySelector('[data-customer-fcl-rows]');
        const template = document.getElementById('customer-fcl-row-template');
        if (!wrapper || !template) return;

        const html = template.innerHTML.replaceAll('__INDEX__', String(customerFclRows().length));
        const shell = document.createElement('div');
        shell.innerHTML = html.trim();
        const row = shell.firstElementChild;
        if (!row) return;

        const typeInput = row.querySelector('[data-customer-fcl-container-type]');
        const qtyInput = row.querySelector('[data-customer-fcl-container-qty]');
        if (typeInput && container.type) {
            ensureCustomerSelectOption(typeInput, container.type);
            typeInput.value = container.type;
        }
        if (qtyInput && container.qty) qtyInput.value = Math.max(1, Number(container.qty) || 1);

        wrapper.appendChild(row);
        bindCustomerFclRow(row);
        reindexCustomerFclRows();
        syncShipmentTypeFields();
        window.lucide?.createIcons();
    }

    function setCustomerFclRows(containers = [], fallbackType = '', fallbackQty = 1) {
        const wrapper = document.querySelector('[data-customer-fcl-rows]');
        if (!wrapper) return;

        const normalized = Array.isArray(containers) && containers.length
            ? containers
            : [{ type: fallbackType || '40HC', qty: fallbackQty || 1 }];

        wrapper.innerHTML = '';
        normalized.forEach(container => addCustomerFclRow(container));
        syncShipmentTypeFields();
    }

    function initCustomerFclRows() {
        customerFclRows().forEach(bindCustomerFclRow);
        document.querySelector('[data-customer-add-fcl-row]')?.addEventListener('click', () => addCustomerFclRow());
        syncCustomerFclPrimaryFields();
    }

    function syncShipmentTypeFields() {
        const type = document.getElementById('customer-shipment-type')?.value || 'FCL';
        const isFcl = type === 'FCL';
        const isReefer = customerFclRows().some(row => {
            const containerType = row.querySelector('[data-customer-fcl-container-type]')?.value || '';

            return ['Reefer 20', 'Reefer 40'].includes(containerType);
        });

        syncCustomerFclPrimaryFields();

        document.querySelectorAll('[data-customer-fcl-field]').forEach(field => {
            field.classList.toggle('hidden', !isFcl);
            field.querySelectorAll('input, select').forEach(input => {
                input.disabled = !isFcl;
                input.required = isFcl && input.dataset.requiredWhenFcl === '1';
            });
        });

        document.querySelectorAll('[data-customer-cargo-panel]').forEach(panel => {
            const isActive = panel.dataset.customerCargoPanel === type || (type === 'Flat Container' && panel.dataset.customerCargoPanel === 'RoRo');
            panel.classList.toggle('hidden', !isActive);
            panel.querySelectorAll('input, select, textarea').forEach(input => {
                input.disabled = !isActive;
            });
        });

        document.querySelectorAll('[data-customer-cargo-items]').forEach(editor => {
            const isActive = type === 'Bulk' || type === 'RoRo';
            editor.classList.toggle('hidden', !isActive);
            editor.querySelectorAll('input, select').forEach(input => {
                input.disabled = !isActive;
            });
        });

        document.querySelectorAll('[data-customer-reefer-temperature]').forEach(field => {
            const isActive = isFcl && isReefer;
            field.classList.toggle('hidden', !isActive);
            field.querySelectorAll('input').forEach(input => {
                input.disabled = !isActive;
            });
        });
    }

    function setRepeatedField(name, value) {
        if (value === null || value === undefined || value === '') return;

        document.querySelectorAll(`[name="${name}"]`).forEach(field => {
            if (field.type === 'checkbox') {
                field.checked = Boolean(value);
                return;
            }

            if (field.tagName === 'SELECT') {
                ensureCustomerSelectOption(field, value);
            }

            field.value = value;
            field.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    function ensureCustomerSelectOption(select, value, label = null) {
        if (!select || value === null || value === undefined || value === '') return;

        const stringValue = String(value);
        const exists = Array.from(select.options).some(option => option.value === stringValue);
        if (!exists) {
            select.appendChild(new Option(label || stringValue, stringValue));
        }
    }

    function applyRepeatBookingSeed() {
        if (!repeatBookingSeed) return;

        const targetTab = repeatBookingSeed.shipping_category === 'land' ? 'land' : 'sea';
        setCustomerTab(targetTab);

        if (targetTab === 'sea') {
            setRepeatedField('route_mode', repeatBookingSeed.route_mode || 'FOB');
            setCustomerRouteMode(repeatBookingSeed.route_mode || 'FOB');
            setRepeatedField('origin_country', repeatBookingSeed.origin_country);
            setRepeatedField('destination_country', repeatBookingSeed.destination_country);
            setRepeatedField('origin_city', repeatBookingSeed.origin_city);
            setRepeatedField('destination_city', repeatBookingSeed.destination_city);

            document.querySelectorAll('[data-customer-country-select]').forEach(renderCustomerPorts);
        }

        setRepeatedField('pol', repeatBookingSeed.pol);
        setRepeatedField('pod', repeatBookingSeed.pod);

        document.querySelectorAll('[data-customer-port-select]').forEach(select => {
            const { valueInput, customInput } = customerPortControls(select);
            const repeatedValue = valueInput?.name === 'pol' ? repeatBookingSeed.pol : repeatBookingSeed.pod;

            if (!repeatedValue) return;

            const hasPortOption = Array.from(select.options).some(option => option.value === repeatedValue);
            if (hasPortOption) {
                select.value = repeatedValue;
            } else {
                select.value = CUSTOMER_CUSTOM_PORT_VALUE;
                if (customInput) customInput.value = repeatedValue;
            }

            syncCustomerPortValue(select);
        });

        setRepeatedField('shipmentType', repeatBookingSeed.shipmentType);
        setRepeatedField('containerType', repeatBookingSeed.containerType);
        setRepeatedField('containerQty', repeatBookingSeed.containerQty);
        setCustomerFclRows(repeatBookingSeed.containers || [], repeatBookingSeed.containerType, repeatBookingSeed.containerQty);
        setRepeatedField('goodsDescription', repeatBookingSeed.goodsDescription);
        setRepeatedField('weightKg', repeatBookingSeed.weightKg);
        setRepeatedField('volumeCbm', repeatBookingSeed.volumeCbm);
        setRepeatedField('land_carton_count', repeatBookingSeed.landCartonCount);
        setRepeatedField('documents', repeatBookingSeed.documents);
        setRepeatedField('notes', repeatBookingSeed.notes);
        setRepeatedField('dangerous', repeatBookingSeed.dangerous);
        setRepeatedField('customs_requested', repeatBookingSeed.customs_requested);
        setRepeatedField('door_delivery_requested', repeatBookingSeed.door_delivery_requested);
        setRepeatedField('cargo_insurance_requested', repeatBookingSeed.cargo_insurance_requested);
        syncShipmentTypeFields();
    }

    function reindexCustomerPackageRows(tbody) {
        tbody.querySelectorAll('[data-customer-package-row]').forEach((row, index) => {
            row.querySelectorAll('[name]').forEach(field => {
                field.name = field.name.replace(/packages\[\d+\]/, `packages[${index}]`);
            });
        });
    }

    function initCustomerPackageButtons() {
        document.querySelectorAll('[data-customer-add-package-row]').forEach(button => {
            if (button.dataset.customerPackageReady === '1') return;
            button.dataset.customerPackageReady = '1';

            button.addEventListener('click', () => {
                const tbody = document.getElementById(button.dataset.target || '');
                const source = tbody?.querySelector('[data-customer-package-row]');
                if (!tbody || !source) return;

                const clone = source.cloneNode(true);
                clone.querySelectorAll('input, select').forEach(field => {
                    field.value = '';
                    field.removeAttribute('id');
                });
                tbody.appendChild(clone);
                reindexCustomerPackageRows(tbody);
            });
        });
    }

    function syncCustomerDangerousPanel(toggle) {
        const tab = toggle.dataset.customerDangerousToggle;
        const panel = document.querySelector(`[data-customer-dangerous-panel="${tab}"]`);
        if (!panel) return;

        panel.classList.toggle('hidden', !toggle.checked);
        panel.querySelectorAll('input, select').forEach(field => {
            field.disabled = !toggle.checked;
        });

        const cargoType = document.querySelector(`[data-customer-cargo-type="${tab}"]`);
        if (toggle.checked && cargoType) cargoType.value = 'dangerous';
    }

    function initCustomerDangerousPanels() {
        document.querySelectorAll('[data-customer-dangerous-toggle]').forEach(toggle => {
            if (toggle.dataset.customerDangerousReady === '1') return;
            toggle.dataset.customerDangerousReady = '1';
            toggle.addEventListener('change', () => syncCustomerDangerousPanel(toggle));
            syncCustomerDangerousPanel(toggle);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-customer-tab]').forEach(button => {
            button.addEventListener('click', () => setCustomerTab(button.dataset.customerTab));
        });

        document.querySelectorAll('[data-customer-country-select]').forEach(select => {
            initCustomerCountryDropdown(select);
            select.addEventListener('change', () => renderCustomerPorts(select));
            renderCustomerPorts(select);
        });

        document.querySelectorAll('[data-customer-port-select]').forEach(select => {
            select.addEventListener('change', () => syncCustomerPortValue(select));
            customerPortControls(select).customInput?.addEventListener('input', () => syncCustomerPortValue(select));
            syncCustomerPortValue(select);
        });

        document.querySelectorAll('[data-customer-airport-select]').forEach(select => {
            select.addEventListener('change', () => fillCustomerAirportFields(select));
            fillCustomerAirportFields(select);
        });

        document.getElementById('customer-route-mode')?.addEventListener('change', event => setCustomerRouteMode(event.target.value));
        document.getElementById('customer-shipment-type')?.addEventListener('change', syncShipmentTypeFields);
        document.getElementById('customer-container-type')?.addEventListener('change', syncShipmentTypeFields);
        initCustomerFclRows();
        initCustomerPackageButtons();
        initCustomerDangerousPanels();

        setCustomerRouteMode(document.getElementById('customer-route-mode')?.value || 'FOB');
        syncShipmentTypeFields();
        const normalizedInitialTab = initialCustomerTab === 'door_to_door' ? 'contracting' : initialCustomerTab;
        setCustomerTab(['sea', 'land', 'air', 'customs', 'contracting'].includes(normalizedInitialTab) ? normalizedInitialTab : 'sea');
        applyRepeatBookingSeed();

        document.addEventListener('click', () => {
            document.querySelectorAll('[data-customer-country-select]').forEach(closeCustomerCountryDropdown);
        });
    });
</script>
@include('partials.door-to-door-volume-script')
@endsection

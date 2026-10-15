@php
    $tab = $service['tab'];
    $isAir = $tab === 'air';
    $category = $service['category'];
    $routeMode = $service['route_mode'];
    $shipmentType = $service['shipment_type'];
    $countryOptions = $isAir ? ($airportCountriesForBooking ?? collect()) : ($portCountries ?? collect());
    $cargoTypes = [
        'general' => 'بضائع عامة',
        'dangerous' => 'بضائع خطرة',
        'perishable' => 'قابلة للتلف',
        'pharmaceutical' => 'أدوية ومستلزمات طبية',
        'valuable' => 'عالية القيمة',
        'fragile' => 'قابلة للكسر',
        'temperature_controlled' => 'تحكم حراري',
        'oversized' => 'أحجام خاصة',
    ];
    $incoterms = ['EXW', 'FOB', 'FCA', 'CIF', 'CFR', 'DAP', 'DDP', 'CPT', 'CIP'];
    $packageTypes = ['carton' => 'Carton', 'pallet' => 'Pallet', 'crate' => 'Crate', 'box' => 'Box', 'bag' => 'Bag', 'drum' => 'Drum', 'bundle' => 'Bundle'];
@endphp

<form @if($tab === 'door') data-door-volume-form @endif data-review-request data-dirty-check action="{{ route('customer.shipments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    <input type="hidden" name="shipping_category" value="{{ $category }}">
    <input type="hidden" name="route_mode" value="{{ $routeMode }}">
    <input type="hidden" name="shipmentType" value="{{ $shipmentType }}">

    <x-customer-lock-card :fields="$lockedCustomerFields" />

    <div class="space-y-4">
        <h2 class="flex items-center gap-2 border-b border-border pb-2 text-lg font-bold text-primary">
            <i data-lucide="{{ $service['icon'] }}" class="h-5 w-5 text-accent"></i>
            <span>{{ $service['title'] }}</span>
        </h2>

        @if($isAir)
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('دولة الانطلاق *') }}</label>
                    <select name="origin_country" id="customer-air-origin-country" required data-customer-country-select data-customer-airport-target="customer-origin-airport" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">{{ __('اختر الدولة') }}</option>
                        @foreach($countryOptions as $country)
                            <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}">{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="origin_country_code" data-country-code-for="customer-air-origin-country">
                    <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                        <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                        <span></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('مدينة الانطلاق *') }}</label>
                    <input type="text" name="origin_city" id="customer-origin-city-air" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('رمز مطار الانطلاق *') }}</label>
                    <select name="origin_airport" id="customer-origin-airport" required data-customer-airport-select data-airport-side="origin" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm font-mono uppercase focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                        <option value="">{{ __('اختر الدولة أولاً') }}</option>
                    </select>
                    <input type="hidden" name="origin_airport_name" id="customer-origin-airport-name">
                    <p class="text-[11px] text-muted-foreground" data-customer-airport-hint>{{ __('تظهر رموز المطارات الخاصة بالدولة المختارة فقط.') }}</p>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('دولة الوصول *') }}</label>
                    <select name="destination_country" id="customer-air-destination-country" required data-customer-country-select data-customer-airport-target="customer-destination-airport" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">{{ __('اختر الدولة') }}</option>
                        @foreach($countryOptions as $country)
                            <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}">{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="destination_country_code" data-country-code-for="customer-air-destination-country">
                    <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                        <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                        <span></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('مدينة الوصول *') }}</label>
                    <input type="text" name="destination_city" id="customer-destination-city-air" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('رمز مطار الوصول *') }}</label>
                    <select name="destination_airport" id="customer-destination-airport" required data-customer-airport-select data-airport-side="destination" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm font-mono uppercase focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                        <option value="">{{ __('اختر الدولة أولاً') }}</option>
                    </select>
                    <input type="hidden" name="destination_airport_name" id="customer-destination-airport-name">
                    <p class="text-[11px] text-muted-foreground" data-customer-airport-hint>{{ __('تظهر رموز المطارات الخاصة بالدولة المختارة فقط.') }}</p>
                </div>
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('دولة الاستلام *') }}</label>
                    <select name="origin_country" id="customer-door-origin-country" required data-customer-country-select class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">{{ __('اختر الدولة') }}</option>
                        @foreach($countryOptions as $country)
                            <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}">{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="origin_country_code" data-country-code-for="customer-door-origin-country">
                    <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                        <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                        <span></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('مدينة الاستلام *') }}</label>
                    <input type="text" name="origin_city" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('الرمز البريدي للاستلام') }}</label>
                    <input type="text" name="origin_postal_code" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('وسيلة النقل الرئيسية *') }}</label>
                    <select name="main_transport_mode" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="AUTO">{{ __('تلقائي') }}</option>
                        <option value="AIR">{{ __('جوي') }}</option>
                        <option value="SEA">{{ __('بحري') }}</option>
                        <option value="LAND">{{ __('بري') }}</option>
                        <option value="MULTIMODAL">{{ __('متعدد الوسائط') }}</option>
                    </select>
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label class="text-xs font-bold text-foreground">{{ __('عنوان الاستلام *') }}</label>
                    <input type="text" name="pickup_address" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('مسؤول الاستلام') }}</label>
                    <input type="text" name="pickup_contact_name" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('هاتف الاستلام') }}</label>
                    <div class="grid grid-cols-[5.5rem_1fr] gap-2">
                        <input type="text" name="pickup_phone_country_code" value="+967" dir="ltr" class="h-10 rounded-lg border border-border bg-background px-2 text-center text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <input type="text" name="pickup_phone" dir="ltr" class="h-10 rounded-lg border border-border bg-background px-3 text-right text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('دولة التسليم *') }}</label>
                    <select name="destination_country" id="customer-door-destination-country" required data-customer-country-select class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">{{ __('اختر الدولة') }}</option>
                        @foreach($countryOptions as $country)
                            <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}">{{ $country['name'] }}</option>
                        @endforeach
                    </select>
                    <input type="hidden" name="destination_country_code" data-country-code-for="customer-door-destination-country">
                    <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                        <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                        <span></span>
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('مدينة التسليم *') }}</label>
                    <input type="text" name="destination_city" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('الرمز البريدي للتسليم') }}</label>
                    <input type="text" name="destination_postal_code" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('تاريخ جاهزية الاستلام') }}</label>
                    <input type="date" name="pickup_ready_date" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2 md:col-span-2">
                    <label class="text-xs font-bold text-foreground">{{ __('عنوان التسليم *') }}</label>
                    <input type="text" name="delivery_address" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('مسؤول التسليم') }}</label>
                    <input type="text" name="delivery_contact_name" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="space-y-2">
                    <label class="text-xs font-bold text-foreground">{{ __('هاتف التسليم') }}</label>
                    <div class="grid grid-cols-[5.5rem_1fr] gap-2">
                        <input type="text" name="delivery_phone_country_code" value="+967" dir="ltr" class="h-10 rounded-lg border border-border bg-background px-2 text-center text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <input type="text" name="delivery_phone" dir="ltr" class="h-10 rounded-lg border border-border bg-background px-3 text-right text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                </div>
            </div>
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">Incoterm</label>
            <select name="incoterm" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                <option value="">{{ __('غير محدد') }}</option>
                @foreach($incoterms as $incoterm)
                    <option value="{{ $incoterm }}">{{ $incoterm }}</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">{{ __('نوع البضاعة') }}</label>
            <select name="cargo_type" data-customer-cargo-type="{{ $tab }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                @foreach($cargoTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">HS Code</label>
            <input type="text" name="hs_code" dir="ltr" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        @if($tab === 'air')
            @include('partials.cargo-items-editor', [
                'editorId' => 'customer-air-cargo-items-editor',
                'cargoItems' => data_get($repeatBooking?->cargo_details, 'cargo_items', []),
                'defaultUnit' => 'cbm',
                'class' => 'md:col-span-2',
            ])
        @endif
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">{{ __('بلد منشأ البضاعة') }}</label>
            <input type="text" name="country_of_origin" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">{{ __('القيمة التجارية') }}</label>
            <input type="number" name="commercial_value" step="0.01" min="0" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">{{ __('العملة') }}</label>
            <select name="currency" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                <option value="USD">USD</option>
                <option value="SAR">SAR</option>
                <option value="AED">AED</option>
                <option value="YER">YER</option>
            </select>
        </div>
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">{{ __('الوزن الإجمالي KG *') }}</label>
            <input type="number" name="weightKg" step="0.01" min="0.01" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">{{ __('الحجم CBM') }}</label>
            <input type="number" name="volumeCbm" step="{{ $isAir ? '0.001' : '0.0001' }}" min="0" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        @if($isAir)
            <div class="space-y-2">
                <label class="text-xs font-bold text-foreground">{{ __('مستوى الخدمة') }}</label>
                <select name="air_service_level" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="standard">Standard</option>
                    <option value="express">Express</option>
                    <option value="economy">Economy</option>
                </select>
            </div>
            <div class="space-y-2">
                <label class="text-xs font-bold text-foreground">{{ __('تاريخ الجاهزية') }}</label>
                <input type="date" name="ready_date" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            </div>
        @endif
    </div>

    <div class="space-y-3">
        <div class="flex items-center justify-between gap-3">
            <div>
                <div class="text-sm font-black text-foreground">{{ __('تفاصيل الطرود') }}</div>
                <p class="text-[11px] text-muted-foreground">{{ __('يحسب النظام الوزن والحجم من الأبعاد عند تعبئتها.') }}</p>
            </div>
            <button type="button" data-customer-add-package-row data-target="customer-packages-{{ $tab }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border bg-card px-3 text-xs font-bold hover:bg-secondary/50">
                <i data-lucide="plus" class="h-4 w-4"></i>
                {{ __('إضافة طرد') }}
            </button>
        </div>
        @if(! $isAir)
            @include('partials.package-dimension-unit-select', ['selectedUnit' => old('package_dimension_unit', 'cm')])
        @endif
        <div class="overflow-x-auto rounded-lg border border-border">
            <table class="w-full min-w-[760px] text-sm">
                <thead class="bg-secondary/30 text-xs text-muted-foreground">
                    <tr>
                        <th class="px-2 py-2 text-right">{{ __('الكمية') }}</th>
                        <th class="px-2 py-2 text-right">{{ __('نوع التغليف') }}</th>
                        <th data-package-length-label class="px-2 py-2 text-right">{{ __('messages.booking_package_length_label') }}</th>
                        <th data-package-width-label class="px-2 py-2 text-right">{{ __('messages.booking_package_width_label') }}</th>
                        <th data-package-height-label class="px-2 py-2 text-right">{{ __('messages.booking_package_height_label') }}</th>
                        <th class="px-2 py-2 text-right">{{ __('messages.booking_package_weight_label') }}</th>
                    </tr>
                </thead>
                <tbody id="customer-packages-{{ $tab }}" class="divide-y divide-border/60">
                    <tr data-customer-package-row>
                        <td class="p-2"><input type="number" min="1" name="packages[0][quantity]" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm"></td>
                        <td class="p-2">
                            <select name="packages[0][package_type]" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm">
                                <option value="">{{ __('اختر') }}</option>
                                @foreach($packageTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="p-2"><input type="number" step="0.01" min="0" name="packages[0][length_cm]" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm"></td>
                        <td class="p-2"><input type="number" step="0.01" min="0" name="packages[0][width_cm]" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm"></td>
                        <td class="p-2"><input type="number" step="0.01" min="0" name="packages[0][height_cm]" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm"></td>
                        <td class="p-2"><input type="number" step="0.01" min="0" name="packages[0][weight_kg]" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-3 rounded-xl border border-border/70 bg-secondary/10 p-3 md:grid-cols-3">
        @foreach(($isAir ? [
            'insurance_required' => 'تأمين على الشحنة',
            'customs_clearance_origin' => 'تخليص في بلد الانطلاق',
            'customs_clearance_destination' => 'تخليص في بلد الوصول',
            'pickup_required' => 'استلام من العنوان',
            'delivery_required' => 'تسليم إلى العنوان',
            'direct_flight_preferred' => 'أفضل رحلة مباشرة',
        ] : [
            'insurance_required' => 'تأمين على الشحنة',
            'export_customs_clearance' => 'تخليص تصدير',
            'import_customs_clearance' => 'تخليص استيراد',
        ]) as $flag => $label)
            <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                <input type="checkbox" name="{{ $flag }}" value="1" class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent">
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>

    <div class="flex items-center justify-between rounded-xl border border-border/80 bg-secondary/20 p-4">
        <div>
            <label class="block cursor-pointer text-sm font-bold text-foreground">{{ __('بضائع خطرة') }}</label>
            <span class="text-xs text-muted-foreground">{{ __('عند التفعيل تظهر بيانات UN / Class / MSDS.') }}</span>
        </div>
        <input type="checkbox" name="dangerous" value="1" data-customer-dangerous-toggle="{{ $tab }}" class="h-5 w-5 rounded border-border bg-background text-accent focus:ring-accent">
    </div>

    <div data-customer-dangerous-panel="{{ $tab }}" class="hidden grid gap-4 rounded-xl border border-border/70 bg-secondary/10 p-4 md:grid-cols-2">
        <input type="text" name="dangerous_un_number" placeholder="UN Number" class="h-10 rounded-lg border border-border bg-background px-3 text-sm">
        <input type="text" name="dangerous_iata_class" placeholder="IATA Class" class="h-10 rounded-lg border border-border bg-background px-3 text-sm">
        <input type="text" name="dangerous_packing_group" placeholder="Packing Group" class="h-10 rounded-lg border border-border bg-background px-3 text-sm">
        <input type="text" name="dangerous_proper_shipping_name" placeholder="Proper Shipping Name" class="h-10 rounded-lg border border-border bg-background px-3 text-sm">
        <label class="flex items-center gap-2 text-sm font-bold text-foreground md:col-span-2">
            <input type="checkbox" name="dangerous_msds_available" value="1" class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent">
            <span>{{ __('MSDS متوفر') }}</span>
        </label>
    </div>

    <div class="space-y-2">
        <label class="text-xs font-bold text-foreground">{{ __('وصف البضاعة *') }}</label>
        <textarea name="goodsDescription" required rows="3" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
    </div>

    @unless($isAir)
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">{{ __('ملاحظات التسليم') }}</label>
            <textarea name="delivery_notes" rows="2" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
        </div>
    @endunless

    <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">{{ __('المستندات المطلوبة') }}</label>
            <input type="text" name="documents" placeholder="Invoice, Packing List, MSDS" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <div class="space-y-2">
            <label class="text-xs font-bold text-foreground">{{ __('المرفقات') }}</label>
            <input type="file" name="attachments[]" multiple accept="image/*,.pdf" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            <p class="text-[11px] font-bold text-warning">{{ __('messages.booking_dg_attachment_hint') }}</p>
            @if(isset($errors) && $errors->has('attachments'))
                <p class="text-xs font-bold text-destructive">{{ $errors->first('attachments') }}</p>
            @endif
        </div>
    </div>

    <div class="space-y-2">
        <label class="text-xs font-bold text-foreground">{{ __('ملاحظات إضافية') }}</label>
        <textarea name="notes" rows="3" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
    </div>

    <div class="portal-form-actions flex flex-wrap justify-end gap-3 border-t border-border pt-4">
        <a href="{{ route('customer.dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-border bg-card px-6 text-sm font-bold hover:bg-secondary/40">{{ __('إلغاء') }}</a>
        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground shadow hover:opacity-95">
            <i data-lucide="send" class="h-4 w-4"></i>
            <span>{{ __('portal.review') }}</span>
        </button>
    </div>
</form>

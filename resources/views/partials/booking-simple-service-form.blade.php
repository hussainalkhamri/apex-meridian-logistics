@php
    $tab = $service['tab'];
    $category = $service['category'];
    $routeMode = $service['route_mode'];
    $shipmentType = $service['shipment_type'];
    $icon = $service['icon'];
    $title = $service['title'];
    $routeTitle = $service['route_title'];
    $sourceBooking = $booking ?? $parentBooking;
    $details = $sourceBooking?->route_details ?? [];
    $cargoDetails = $sourceBooking?->cargo_details ?? [];
    $packageRows = old('packages', data_get($cargoDetails, 'packages') ?? []);
    $packageRows = array_values(is_array($packageRows) ? $packageRows : []);
    $packageDimensionUnit = $tab === 'door'
        ? old('package_dimension_unit', data_get($cargoDetails, 'package_dimension_unit', 'cm'))
        : 'cm';
    if ($packageDimensionUnit === 'm' && ! session()->hasOldInput('packages')) {
        foreach ($packageRows as &$package) {
            foreach (['length_cm', 'width_cm', 'height_cm'] as $dimension) {
                if (isset($package[$dimension])) {
                    $package[$dimension] = (float) $package[$dimension] / 100;
                }
            }
        }
        unset($package);
    }
    while (count($packageRows) < 1) {
        $packageRows[] = [];
    }
    $incoterms = ['EXW', 'FOB', 'FCA', 'CIF', 'CFR', 'DAP', 'DDP', 'CPT', 'CIP'];
    $countryOptions = $tab === 'air' ? ($airportCountriesForBooking ?? $portCountries) : $portCountries;
    $selectedOriginAirport = old('origin_airport', data_get($details, 'origin.airport') ?? $sourceBooking?->pol ?? '');
    $selectedDestinationAirport = old('destination_airport', data_get($details, 'destination.airport') ?? $sourceBooking?->pod ?? '');
    $mainTransportModes = [
        'AUTO' => __('messages.booking_transport_mode_auto'),
        'AIR' => __('messages.booking_transport_mode_air'),
        'SEA' => __('messages.booking_transport_mode_sea'),
        'LAND' => __('messages.booking_transport_mode_land'),
        'MULTIMODAL' => __('messages.booking_transport_mode_multimodal'),
    ];
    $selectedMainTransportMode = old('main_transport_mode', data_get($details, 'main_transport_mode') ?? data_get($cargoDetails, 'main_transport_mode') ?? 'AUTO');
    $cargoTypes = [
        'general' => __('messages.booking_cargo_type_general'),
        'dangerous' => __('messages.booking_cargo_type_dangerous'),
        'perishable' => __('messages.booking_cargo_type_perishable'),
        'pharmaceutical' => __('messages.booking_cargo_type_pharmaceutical'),
        'valuable' => __('messages.booking_cargo_type_valuable'),
        'fragile' => __('messages.booking_cargo_type_fragile'),
        'live_animals' => __('messages.booking_cargo_type_live_animals'),
        'temperature_controlled' => __('messages.booking_cargo_type_temperature_controlled'),
        'oversized' => __('messages.booking_cargo_type_oversized'),
    ];
    $packageTypes = ['carton', 'pallet', 'crate', 'box', 'bag', 'drum', 'bundle', 'loose'];
@endphp

<form @if($tab === 'door') data-door-volume-form @endif action="@isset($booking) {{ route('orders.update', $booking->id) }} @else {{ route('booking.store') }} @endisset" method="POST" enctype="multipart/form-data" class="space-y-6">
    @csrf
    @isset($booking) @method('PUT') @endisset
    <input type="hidden" name="shipping_category" value="{{ $category }}" />
    <input type="hidden" name="route_mode" value="{{ $routeMode }}" />
    <input type="hidden" name="shipmentType" value="{{ $shipmentType }}" />
    @if($parentBooking)
        <input type="hidden" name="parent_booking_id" value="{{ $parentBooking->id }}" />
        <div class="rounded-lg border border-accent/30 bg-accent/10 p-3 text-sm text-accent-foreground">
            <div class="font-bold">{{ __('messages.booking_parent_booking_note', ['id' => $parentBooking->id]) }}</div>
            <div class="mt-1 text-xs text-muted-foreground">{{ __('messages.booking_parent_booking_hint') }}</div>
        </div>
    @endif

    <h2 class="flex items-center gap-2 border-b pb-2 text-lg font-bold text-primary">
        <i data-lucide="{{ $icon }}" class="h-5 w-5 text-accent"></i>
        <span>{{ $title }}</span>
    </h2>

    <div class="space-y-4">
        <h3 class="flex items-center gap-2 text-sm font-bold text-foreground">
            <i data-lucide="users" class="h-4 w-4 text-accent"></i>
            <span>{{ __('messages.booking_client_data') }}</span>
        </h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2 md:col-span-2">
                <label for="customer_id_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_select_customer') }}</label>
                <select name="customer_id" id="customer_id_{{ $tab }}" onchange="populateCustomerFields('{{ $tab }}')" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('messages.booking_manual_entry') }}</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->company }} @if($customer->contact_name) - {{ $customer->contact_name }} @endif</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2">
                <label for="company_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_company_label') }}</label>
                <input type="text" name="company" id="company_{{ $tab }}" required value="{{ old('company', $sourceBooking?->customer_company ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            <div class="space-y-2">
                <label for="contactName_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_contact_label') }}</label>
                <input type="text" name="contactName" id="contactName_{{ $tab }}" required value="{{ old('contactName', $sourceBooking?->customer_contact_name ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            <div class="space-y-2">
                <label for="phone_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_phone_label') }}</label>
                <input type="hidden" name="phone_country_code" value="+967" />
                <input type="tel" name="phone" id="phone_{{ $tab }}" dir="ltr" required value="{{ old('phone', $sourceBooking?->customer_phone ?? '') }}" placeholder="7xx xxx xxx" class="intl-phone-input h-10 w-full rounded-lg border border-border bg-background px-3 text-right text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                <p class="phone-error hidden text-[11px] font-semibold text-destructive">{{ __('messages.booking_phone_invalid') }}</p>
                <p class="text-[11px] text-muted-foreground">{{ __('messages.booking_phone_hint') }}</p>
            </div>
            <div class="space-y-2">
                <label for="email_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_email_label') }}</label>
                <input type="email" name="email" id="email_{{ $tab }}" dir="ltr" required value="{{ old('email', $sourceBooking?->customer_email ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-right text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
        </div>
    </div>

    <div class="space-y-4">
        <h3 class="flex items-center gap-2 text-sm font-bold text-foreground">
            <i data-lucide="route" class="h-4 w-4 text-accent"></i>
            <span>{{ $routeTitle }}</span>
        </h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <label for="origin_country_{{ $tab }}" class="text-xs font-bold text-foreground">{{ $service['origin_country_label'] }}</label>
                <select name="origin_country" id="origin_country_{{ $tab }}" required data-country-select @if($tab === 'air') data-airport-target="origin_airport_air" @endif class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('messages.booking_choose_country') }}</option>
                    @foreach($countryOptions as $country)
                        <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}" @selected(old('origin_country', data_get($details, 'origin.country') ?? '') === $country['name'])>{{ $country['name'] }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="origin_country_code" value="{{ old('origin_country_code', data_get($details, 'origin.country_code') ?? '') }}" data-country-code-for="origin_country_{{ $tab }}" />
                <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                    <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                    <span></span>
                </div>
            </div>
            <div class="space-y-2">
                <label for="origin_city_{{ $tab }}" class="text-xs font-bold text-foreground">{{ $service['origin_city_label'] }}</label>
                <input type="text" name="origin_city" id="origin_city_{{ $tab }}" required value="{{ old('origin_city', data_get($details, 'origin.city') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            @if($tab === 'door')
                <div class="space-y-2">
                    <label for="origin_postal_code_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_origin_postal_code_label') }}</label>
                    <input type="text" name="origin_postal_code" id="origin_postal_code_door" value="{{ old('origin_postal_code', data_get($details, 'origin.postal_code') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
            @endif

            @if($tab === 'air')
                <div class="space-y-2">
                    <label for="origin_airport_air" class="text-xs font-bold text-foreground">{{ __('messages.booking_origin_airport_code_label') }}</label>
                    <select name="origin_airport" id="origin_airport_air" required data-airport-code-input data-airport-side="origin" data-selected-airport="{{ $selectedOriginAirport }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm font-mono uppercase focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                        <option value="">{{ __('messages.booking_choose_country_first') }}</option>
                    </select>
                    <p class="text-[11px] text-muted-foreground" data-airport-filter-hint>{{ __('messages.booking_choose_country_first_airport_hint') }}</p>
                </div>
                <div class="space-y-2">
                    <label for="origin_airport_name_air" class="text-xs font-bold text-foreground">{{ __('messages.booking_origin_airport_name_label') }}</label>
                    <input type="text" name="origin_airport_name" id="origin_airport_name_air" value="{{ old('origin_airport_name', data_get($details, 'origin.airport_name') ?? '') }}" placeholder="{{ $service['pol_placeholder'] }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
            @else
                <div class="space-y-2 md:col-span-2">
                    <label for="pickup_address_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_pickup_address_label') }}</label>
                    <input type="text" name="pickup_address" id="pickup_address_door" required value="{{ old('pickup_address', data_get($details, 'origin.address') ?? $sourceBooking?->pol ?? '') }}" placeholder="{{ $service['pol_placeholder'] }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="pickup_contact_name_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_pickup_contact_name_label') }}</label>
                    <input type="text" name="pickup_contact_name" id="pickup_contact_name_door" value="{{ old('pickup_contact_name', data_get($details, 'origin.contact_name') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="pickup_phone_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_pickup_phone_label') }}</label>
                    <div class="grid grid-cols-[5.5rem_1fr] gap-2">
                        <input type="text" name="pickup_phone_country_code" dir="ltr" value="{{ old('pickup_phone_country_code', data_get($details, 'origin.phone_country_code') ?? '+967') }}" class="h-10 rounded-lg border border-border bg-background px-2 text-center text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                        <input type="text" name="pickup_phone" id="pickup_phone_door" dir="ltr" value="{{ old('pickup_phone', data_get($details, 'origin.phone') ?? '') }}" class="h-10 rounded-lg border border-border bg-background px-3 text-right text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                    </div>
                </div>
            @endif

            <div class="space-y-2">
                <label for="destination_country_{{ $tab }}" class="text-xs font-bold text-foreground">{{ $service['destination_country_label'] }}</label>
                <select name="destination_country" id="destination_country_{{ $tab }}" required data-country-select @if($tab === 'air') data-airport-target="destination_airport_air" @endif class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('messages.booking_choose_country') }}</option>
                    @foreach($countryOptions as $country)
                        <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}" @selected(old('destination_country', data_get($details, 'destination.country') ?? '') === $country['name'])>{{ $country['name'] }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="destination_country_code" value="{{ old('destination_country_code', data_get($details, 'destination.country_code') ?? '') }}" data-country-code-for="destination_country_{{ $tab }}" />
                <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                    <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                    <span></span>
                </div>
            </div>
            <div class="space-y-2">
                <label for="destination_city_{{ $tab }}" class="text-xs font-bold text-foreground">{{ $service['destination_city_label'] }}</label>
                <input type="text" name="destination_city" id="destination_city_{{ $tab }}" required value="{{ old('destination_city', data_get($details, 'destination.city') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            @if($tab === 'door')
                <div class="space-y-2">
                    <label for="destination_postal_code_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_destination_postal_code_label') }}</label>
                    <input type="text" name="destination_postal_code" id="destination_postal_code_door" value="{{ old('destination_postal_code', data_get($details, 'destination.postal_code') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
            @endif

            @if($tab === 'air')
                <div class="space-y-2">
                    <label for="destination_airport_air" class="text-xs font-bold text-foreground">{{ __('messages.booking_destination_airport_code_label') }}</label>
                    <select name="destination_airport" id="destination_airport_air" required data-airport-code-input data-airport-side="destination" data-selected-airport="{{ $selectedDestinationAirport }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm font-mono uppercase focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                        <option value="">{{ __('messages.booking_choose_country_first') }}</option>
                    </select>
                    <p class="text-[11px] text-muted-foreground" data-airport-filter-hint>{{ __('messages.booking_choose_country_first_airport_hint') }}</p>
                </div>
                <div class="space-y-2">
                    <label for="destination_airport_name_air" class="text-xs font-bold text-foreground">{{ __('messages.booking_destination_airport_name_label') }}</label>
                    <input type="text" name="destination_airport_name" id="destination_airport_name_air" value="{{ old('destination_airport_name', data_get($details, 'destination.airport_name') ?? '') }}" placeholder="{{ $service['pod_placeholder'] }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
            @else
                <div class="space-y-2 md:col-span-2">
                    <label for="delivery_address_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_delivery_address_label') }}</label>
                    <input type="text" name="delivery_address" id="delivery_address_door" required value="{{ old('delivery_address', data_get($details, 'destination.address') ?? $sourceBooking?->pod ?? '') }}" placeholder="{{ $service['pod_placeholder'] }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="delivery_contact_name_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_delivery_contact_name_label') }}</label>
                    <input type="text" name="delivery_contact_name" id="delivery_contact_name_door" value="{{ old('delivery_contact_name', data_get($details, 'destination.contact_name') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="delivery_phone_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_delivery_phone_label') }}</label>
                    <div class="grid grid-cols-[5.5rem_1fr] gap-2">
                        <input type="text" name="delivery_phone_country_code" dir="ltr" value="{{ old('delivery_phone_country_code', data_get($details, 'destination.phone_country_code') ?? '+967') }}" class="h-10 rounded-lg border border-border bg-background px-2 text-center text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                        <input type="text" name="delivery_phone" id="delivery_phone_door" dir="ltr" value="{{ old('delivery_phone', data_get($details, 'destination.phone') ?? '') }}" class="h-10 rounded-lg border border-border bg-background px-3 text-right text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                    </div>
                </div>
            @endif

            <div class="space-y-2">
                <label for="incoterm_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_incoterm_label') }}</label>
                <select name="incoterm" id="incoterm_{{ $tab }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('messages.booking_choose') }}</option>
                    @foreach($incoterms as $incoterm)
                        <option value="{{ $incoterm }}" @selected(old('incoterm', data_get($details, 'incoterm') ?? '') === $incoterm)>{{ $incoterm }}</option>
                    @endforeach
                </select>
            </div>
            @if($tab === 'door')
                <div class="space-y-2">
                    <label for="main_transport_mode_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_main_transport_mode_label') }}</label>
                    <select name="main_transport_mode" id="main_transport_mode_door" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        @foreach($mainTransportModes as $mode => $label)
                            <option value="{{ $mode }}" @selected($selectedMainTransportMode === $mode)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
    </div>

    <div class="space-y-4">
        <h3 class="flex items-center gap-2 text-sm font-bold text-foreground">
            <i data-lucide="box" class="h-4 w-4 text-accent"></i>
            <span>{{ __('messages.booking_cargo_details') }}</span>
        </h3>
        <div class="grid gap-4 md:grid-cols-2">
            <div class="space-y-2">
                <label for="cargo_type_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_cargo_type_label') }}</label>
                <select name="cargo_type" id="cargo_type_{{ $tab }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    @foreach($cargoTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('cargo_type', data_get($cargoDetails, 'cargo_type') ?? 'general') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2">
                <label for="hs_code_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_hs_code_label') }}</label>
                <input type="text" name="hs_code" id="hs_code_{{ $tab }}" value="{{ old('hs_code', data_get($cargoDetails, 'hs_code') ?? data_get($cargoDetails, 'cargo.hs_code') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            @if($tab === 'air')
                @include('partials.cargo-items-editor', [
                    'editorId' => 'air-cargo-items-editor',
                    'cargoItems' => data_get($cargoDetails, 'cargo_items', []),
                    'defaultUnit' => 'cbm',
                    'class' => 'md:col-span-2',
                ])
            @endif
            <div class="space-y-2">
                <label for="country_of_origin_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_country_of_origin_label') }}</label>
                <input type="text" name="country_of_origin" id="country_of_origin_{{ $tab }}" value="{{ old('country_of_origin', data_get($cargoDetails, 'country_of_origin') ?? data_get($cargoDetails, 'cargo.country_of_origin') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            <div class="space-y-2">
                <label for="package_type_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_package_type_label') }}</label>
                <select name="package_type" id="package_type_{{ $tab }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('messages.booking_choose') }}</option>
                    @foreach($packageTypes as $packageType)
                        <option value="{{ $packageType }}" @selected(old('package_type', data_get($cargoDetails, 'package_type') ?? data_get($cargoDetails, 'cargo.package_type') ?? '') === $packageType)>{{ ucfirst($packageType) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="space-y-2">
                <label for="gross_weight_kg_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_gross_weight_label') }}</label>
                <input type="number" step="0.01" name="gross_weight_kg" id="gross_weight_kg_{{ $tab }}" required value="{{ old('gross_weight_kg', data_get($cargoDetails, 'gross_weight_kg') ?? $sourceBooking?->weight_kg ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            <div class="space-y-2">
                <label for="net_weight_kg_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_net_weight_label') }}</label>
                <input type="number" step="0.01" name="net_weight_kg" id="net_weight_kg_{{ $tab }}" value="{{ old('net_weight_kg', data_get($cargoDetails, 'net_weight_kg') ?? data_get($cargoDetails, 'cargo.net_weight_kg') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            <div class="space-y-2">
                <label for="volume_cbm_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_volume_label') }}</label>
                <input type="number" step="0.0001" name="volume_cbm" id="volume_cbm_{{ $tab }}" value="{{ old('volume_cbm', data_get($cargoDetails, 'volume_cbm') ?? $sourceBooking?->volume_cbm ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            <div class="space-y-2">
                <label for="declared_value_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_declared_value_label') }}</label>
                <input type="number" step="0.01" name="declared_value" id="declared_value_{{ $tab }}" value="{{ old('declared_value', data_get($cargoDetails, 'declared_value') ?: '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            <div class="space-y-2">
                <label for="commercial_value_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_commercial_value_label') }}</label>
                <input type="number" step="0.01" name="commercial_value" id="commercial_value_{{ $tab }}" value="{{ old('commercial_value', data_get($cargoDetails, 'commercial_value') ?: data_get($cargoDetails, 'trade.commercial_value') ?: '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            <div class="space-y-2">
                <label for="currency_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_currency_label') }}</label>
                <input type="text" name="currency" id="currency_{{ $tab }}" value="{{ old('currency', data_get($cargoDetails, 'currency') ?? 'USD') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm uppercase focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>
            <div class="flex items-center gap-2 rounded-xl border border-border/70 bg-secondary/10 p-3">
                <input type="checkbox" name="stackable" id="stackable_{{ $tab }}" value="1" @checked(old('stackable', data_get($cargoDetails, 'stackable') ?? data_get($cargoDetails, 'cargo.stackable') ?? true)) class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent" />
                <label for="stackable_{{ $tab }}" class="text-sm font-bold text-foreground">{{ __('messages.booking_stackable_label') }}</label>
            </div>

            @if($tab === 'air')
                <div class="space-y-2">
                    <label for="pieces_count_air" class="text-xs font-bold text-foreground">{{ __('messages.booking_pieces_count_label') }}</label>
                    <input type="number" min="1" name="pieces_count" id="pieces_count_air" value="{{ old('pieces_count', data_get($cargoDetails, 'pieces_count') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="air_service_level_air" class="text-xs font-bold text-foreground">{{ __('messages.booking_air_service_level_label') }}</label>
                    <select name="air_service_level" id="air_service_level_air" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="standard" @selected(old('air_service_level', data_get($cargoDetails, 'service_level') ?? 'standard') === 'standard')>{{ __('messages.booking_air_service_standard') }}</option>
                        <option value="express" @selected(old('air_service_level', data_get($cargoDetails, 'service_level') ?? '') === 'express')>{{ __('messages.booking_air_service_express') }}</option>
                        <option value="economy" @selected(old('air_service_level', data_get($cargoDetails, 'service_level') ?? '') === 'economy')>{{ __('messages.booking_air_service_economy') }}</option>
                    </select>
                </div>
                <div class="space-y-2">
                    <label for="ready_date_air" class="text-xs font-bold text-foreground">{{ __('messages.booking_ready_date_label') }}</label>
                    <input type="date" name="ready_date" id="ready_date_air" value="{{ old('ready_date', data_get($cargoDetails, 'ready_date') ?? data_get($cargoDetails, 'service.ready_date') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="grid gap-3 rounded-xl border border-border/70 bg-secondary/10 p-3 md:col-span-2 sm:grid-cols-2">
                    @foreach([
                        'direct_flight_preferred' => __('messages.booking_direct_flight_preferred_label'),
                        'insurance_required' => __('messages.booking_insurance_required_label'),
                        'customs_clearance_origin' => __('messages.booking_customs_clearance_origin_label'),
                        'customs_clearance_destination' => __('messages.booking_customs_clearance_destination_label'),
                        'pickup_required' => __('messages.booking_pickup_required_label'),
                        'delivery_required' => __('messages.booking_delivery_required_label'),
                    ] as $flag => $label)
                        <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                            <input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, data_get($cargoDetails, 'service.' . $flag) ?? data_get($cargoDetails, 'service_details.' . $flag) ?? false)) class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent" />
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            @else
                <div class="space-y-2">
                    <label for="packages_count_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_packages_count_label') }}</label>
                    <input type="number" min="1" name="packages_count" id="packages_count_door" value="{{ old('packages_count', data_get($cargoDetails, 'packages_count') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="pickup_ready_date_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_pickup_ready_date_label') }}</label>
                    <input type="date" name="pickup_ready_date" id="pickup_ready_date_door" value="{{ old('pickup_ready_date', data_get($cargoDetails, 'pickup_ready_date') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="pickup_time_from_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_pickup_time_from_label') }}</label>
                    <input type="time" name="pickup_time_from" id="pickup_time_from_door" value="{{ old('pickup_time_from', data_get($cargoDetails, 'service.pickup_time_from') ?? data_get($cargoDetails, 'service_details.pickup_time_from') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="pickup_time_to_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_pickup_time_to_label') }}</label>
                    <input type="time" name="pickup_time_to" id="pickup_time_to_door" value="{{ old('pickup_time_to', data_get($cargoDetails, 'service.pickup_time_to') ?? data_get($cargoDetails, 'service_details.pickup_time_to') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="delivery_required_date_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_delivery_required_date_label') }}</label>
                    <input type="date" name="delivery_required_date" id="delivery_required_date_door" value="{{ old('delivery_required_date', data_get($cargoDetails, 'service.delivery_required_date') ?? data_get($cargoDetails, 'service_details.delivery_required_date') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-2">
                    <label for="preferred_pickup_time_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_preferred_pickup_time_label') }}</label>
                    <select name="preferred_pickup_time" id="preferred_pickup_time_door" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="anytime" @selected(old('preferred_pickup_time', data_get($cargoDetails, 'preferred_pickup_time') ?? 'anytime') === 'anytime')>{{ __('messages.booking_pickup_time_anytime') }}</option>
                        <option value="morning" @selected(old('preferred_pickup_time', data_get($cargoDetails, 'preferred_pickup_time') ?? '') === 'morning')>{{ __('messages.booking_pickup_time_morning') }}</option>
                        <option value="afternoon" @selected(old('preferred_pickup_time', data_get($cargoDetails, 'preferred_pickup_time') ?? '') === 'afternoon')>{{ __('messages.booking_pickup_time_afternoon') }}</option>
                        <option value="evening" @selected(old('preferred_pickup_time', data_get($cargoDetails, 'preferred_pickup_time') ?? '') === 'evening')>{{ __('messages.booking_pickup_time_evening') }}</option>
                    </select>
                </div>
                <div class="grid gap-3 rounded-xl border border-border/70 bg-secondary/10 p-3 md:col-span-2 sm:grid-cols-3">
                    @foreach([
                        'insurance_required' => __('messages.booking_insurance_required_label'),
                        'export_customs_clearance' => __('messages.booking_export_customs_clearance_label'),
                        'import_customs_clearance' => __('messages.booking_import_customs_clearance_label'),
                    ] as $flag => $label)
                        <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                            <input type="checkbox" name="{{ $flag }}" value="1" @checked(old($flag, data_get($cargoDetails, 'service.' . $flag) ?? data_get($cargoDetails, 'service_details.' . $flag) ?? false)) class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent" />
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="space-y-2 md:col-span-2">
                <label for="goodsDescription_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_goods_description_label') }}</label>
                <textarea name="goodsDescription" id="goodsDescription_{{ $tab }}" required rows="3" placeholder="{{ __('messages.booking_goods_description_example') }}" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">{{ old('goodsDescription', $sourceBooking?->goods_description ?? '') }}</textarea>
            </div>

            <div class="space-y-3 md:col-span-2">
                <div class="text-xs font-bold text-foreground">{{ __('messages.booking_package_details_label') }}</div>
                @if($tab === 'door')
                    @include('partials.package-dimension-unit-select', ['selectedUnit' => $packageDimensionUnit])
                @endif
                <div class="overflow-x-auto rounded-lg border border-border">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead class="bg-secondary/30 text-xs text-muted-foreground">
                            <tr>
                                <th class="px-2 py-2 text-right">{{ __('messages.booking_package_quantity_label') }}</th>
                                <th class="px-2 py-2 text-right">{{ __('messages.booking_package_type_label') }}</th>
                                <th data-package-length-label class="px-2 py-2 text-right">{{ __('messages.booking_package_length' . ($packageDimensionUnit === 'm' ? '_m' : '') . '_label') }}</th>
                                <th data-package-width-label class="px-2 py-2 text-right">{{ __('messages.booking_package_width' . ($packageDimensionUnit === 'm' ? '_m' : '') . '_label') }}</th>
                                <th data-package-height-label class="px-2 py-2 text-right">{{ __('messages.booking_package_height' . ($packageDimensionUnit === 'm' ? '_m' : '') . '_label') }}</th>
                                <th class="px-2 py-2 text-right">{{ __('messages.booking_package_weight_label') }}</th>
                            </tr>
                        </thead>
                        <tbody id="packages-body-{{ $tab }}" class="divide-y divide-border/60">
                            @foreach($packageRows as $index => $package)
                                <tr data-package-row>
                                    <td class="p-2"><input type="number" min="1" name="packages[{{ $index }}][quantity]" value="{{ data_get($package, 'quantity') }}" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm" /></td>
                                    <td class="p-2"><input type="text" name="packages[{{ $index }}][package_type]" value="{{ data_get($package, 'package_type') }}" placeholder="carton" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm" /></td>
                                    <td class="p-2"><input type="number" step="0.01" name="packages[{{ $index }}][length_cm]" value="{{ data_get($package, 'length_cm') }}" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm" /></td>
                                    <td class="p-2"><input type="number" step="0.01" name="packages[{{ $index }}][width_cm]" value="{{ data_get($package, 'width_cm') }}" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm" /></td>
                                    <td class="p-2"><input type="number" step="0.01" name="packages[{{ $index }}][height_cm]" value="{{ data_get($package, 'height_cm') }}" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm" /></td>
                                    <td class="p-2"><input type="number" step="0.01" name="packages[{{ $index }}][weight_kg]" value="{{ data_get($package, 'weight_kg') }}" class="h-9 w-full rounded-md border border-border bg-background px-2 text-sm" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="button" data-add-package-row data-target="packages-body-{{ $tab }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border bg-card px-3 text-xs font-bold text-foreground transition hover:bg-secondary/50">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>{{ __('messages.booking_add_package_row') }}</span>
                </button>
                <p class="text-[11px] text-muted-foreground">{{ __('messages.booking_package_calc_hint') }}</p>
            </div>

            <div class="flex items-center justify-between rounded-xl border border-border/80 bg-secondary/20 p-4 md:col-span-2">
                <div>
                    <label for="dangerous_{{ $tab }}" class="block cursor-pointer text-sm font-bold text-foreground">{{ __('messages.booking_dangerous_label') }}</label>
                    <span class="text-xs text-muted-foreground">{{ __('messages.booking_dangerous_hint') }}</span>
                </div>
                <input type="checkbox" name="dangerous" id="dangerous_{{ $tab }}" value="1" @checked(old('dangerous', $sourceBooking?->dangerous ?? false)) class="h-5 w-5 rounded border-border bg-background text-accent focus:ring-accent" />
            </div>

            <div data-dangerous-goods-panel="{{ $tab }}" class="hidden space-y-3 rounded-xl border border-border/70 bg-secondary/10 p-4 md:col-span-2">
                <div class="text-xs font-bold text-foreground">{{ __('messages.booking_dangerous_goods_details_label') }}</div>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label for="dangerous_transport_regulation_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_dangerous_transport_regulation_label') }}</label>
                        <select name="dangerous_transport_regulation" id="dangerous_transport_regulation_{{ $tab }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                            <option value="">{{ __('messages.booking_auto_by_route') }}</option>
                            @foreach(['IATA', 'IMDG', 'ADR', 'MULTIMODAL'] as $regulation)
                                <option value="{{ $regulation }}" @selected(old('dangerous_transport_regulation', data_get($cargoDetails, 'dangerous_goods.transport_regulation') ?? '') === $regulation)>{{ $regulation }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="dangerous_un_number_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_dangerous_un_number_label') }}</label>
                        <input type="text" name="dangerous_un_number" id="dangerous_un_number_{{ $tab }}" value="{{ old('dangerous_un_number', data_get($cargoDetails, 'dangerous_goods.un_number') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm" />
                    </div>
                    <div class="space-y-2">
                        <label for="dangerous_iata_class_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_dangerous_iata_class_label') }}</label>
                        <input type="text" name="dangerous_iata_class" id="dangerous_iata_class_{{ $tab }}" value="{{ old('dangerous_iata_class', data_get($cargoDetails, 'dangerous_goods.iata_class') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm" />
                    </div>
                    <div class="space-y-2">
                        <label for="dangerous_imdg_class_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_dangerous_imdg_class_label') }}</label>
                        <input type="text" name="dangerous_imdg_class" id="dangerous_imdg_class_{{ $tab }}" value="{{ old('dangerous_imdg_class', data_get($cargoDetails, 'dangerous_goods.imdg_class') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm" />
                    </div>
                    <div class="space-y-2">
                        <label for="dangerous_adr_class_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_dangerous_adr_class_label') }}</label>
                        <input type="text" name="dangerous_adr_class" id="dangerous_adr_class_{{ $tab }}" value="{{ old('dangerous_adr_class', data_get($cargoDetails, 'dangerous_goods.adr_class') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm" />
                    </div>
                    <div class="space-y-2">
                        <label for="dangerous_packing_group_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_dangerous_packing_group_label') }}</label>
                        <input type="text" name="dangerous_packing_group" id="dangerous_packing_group_{{ $tab }}" value="{{ old('dangerous_packing_group', data_get($cargoDetails, 'dangerous_goods.packing_group') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm" />
                    </div>
                    <div class="space-y-2">
                        <label for="dangerous_proper_shipping_name_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_dangerous_proper_shipping_name_label') }}</label>
                        <input type="text" name="dangerous_proper_shipping_name" id="dangerous_proper_shipping_name_{{ $tab }}" value="{{ old('dangerous_proper_shipping_name', data_get($cargoDetails, 'dangerous_goods.proper_shipping_name') ?? '') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm" />
                    </div>
                    <label class="flex items-center gap-2 text-sm font-bold text-foreground md:col-span-2">
                        <input type="checkbox" name="dangerous_msds_available" id="dangerous_msds_available_{{ $tab }}" value="1" @checked(old('dangerous_msds_available', data_get($cargoDetails, 'dangerous_goods.msds_available') ?? false)) class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent" />
                        <span>{{ __('messages.booking_dangerous_msds_available_label') }}</span>
                    </label>
                </div>
            </div>

            @if($tab === 'door')
                <div class="space-y-2 md:col-span-2">
                    <label for="delivery_notes_door" class="text-xs font-bold text-foreground">{{ __('messages.booking_delivery_notes_label') }}</label>
                    <textarea name="delivery_notes" id="delivery_notes_door" rows="2" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">{{ old('delivery_notes', data_get($cargoDetails, 'delivery_notes') ?? '') }}</textarea>
                </div>
            @endif

            <div class="space-y-2 md:col-span-2">
                <label for="notes_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_additional_notes') }}</label>
                <textarea name="notes" id="notes_{{ $tab }}" rows="3" placeholder="{{ __('messages.booking_additional_notes_example') }}" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">{{ old('notes', $sourceBooking?->notes ?? '') }}</textarea>
            </div>

            <div class="space-y-2 md:col-span-2">
                <label for="documents_{{ $tab }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_documents_label') }}</label>
                <input type="text" name="documents" id="documents_{{ $tab }}" value="{{ old('documents', $sourceBooking?->documents ?? '') }}" placeholder="Invoice, Packing List, MSDS ..." class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>

            <div class="md:col-span-2">
                @include('partials.booking-attachments-field', [
                    'inputId' => 'attachments_' . $tab,
                    'currentBooking' => $booking ?? null,
                ])
            </div>
        </div>
    </div>

    <div class="flex flex-wrap justify-end gap-3 border-t pt-4">
        <a href="{{ $parentBooking ? route('orders.index') : route('dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-border bg-card px-6 text-sm font-bold hover:bg-secondary/40">{{ __('messages.bookings_cancel') }}</a>
        <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground shadow hover:opacity-95">
            <i data-lucide="send" class="h-4 w-4"></i>
            <span>{{ __('messages.booking_send_for_pricing') }}</span>
        </button>
    </div>
</form>

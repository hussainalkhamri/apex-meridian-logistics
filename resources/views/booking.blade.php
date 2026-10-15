@extends('layouts.app')

@section('title', __('messages.booking_page_title'))

@section('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@26.9.2/build/css/intlTelInput.css">
<style>
    .iti {
        width: 100%;
        direction: ltr;
    }

    .iti input[type="tel"] {
        direction: ltr;
        text-align: right;
    }

    .iti__country-container {
        direction: ltr;
    }

    .iti__country-list {
        z-index: 60;
        color: hsl(var(--foreground));
        background: hsl(var(--background));
        border-color: hsl(var(--border));
        font-family: inherit;
        text-align: right;
    }

    .iti__search-input {
        height: 2.5rem;
        border-color: hsl(var(--border));
        background: hsl(var(--background));
        color: hsl(var(--foreground));
        font-family: inherit;
        text-align: right;
    }

    .iti__country {
        direction: ltr;
    }
</style>
@endsection

@section('content')
@php
    $booking = $booking ?? null;
    $parentBooking = $parentBooking ?? null;
@endphp
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs text-white border border-white/10">
                @isset($booking) {{ __('messages.booking_edit_request') }} @else {{ __('messages.booking_request') }} @endisset
            </span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">
                @isset($booking) {{ __('messages.booking_edit_request_with_id', ['id' => $booking->id]) }} @else {{ __('messages.booking_new_request') }} @endisset
            </h1>
            <div class="mt-6 flex flex-wrap justify-start gap-3">  
                <button type="button" data-tab="sea" class="tab-btn rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition duration-150 ease-in-out hover:bg-white/20">{{ __('messages.booking_tab_sea') }}</button>
                <button type="button" data-tab="land" class="tab-btn rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition duration-150 ease-in-out hover:bg-white/20">{{ __('messages.booking_tab_land') }}</button>
                <button type="button" data-tab="air" class="tab-btn rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition duration-150 ease-in-out hover:bg-white/20">{{ __('messages.booking_tab_air') }}</button>
                <button type="button" data-tab="customs" class="tab-btn rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition duration-150 ease-in-out hover:bg-white/20">{{ __('messages.booking_tab_customs') }}</button>
                <button type="button" data-tab="contracting" class="tab-btn rounded-full border border-white/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white transition duration-150 ease-in-out hover:bg-white/20">{{ __('messages.booking_tab_contracting') }}</button>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-10">
        @if(isset($errors) && $errors->any())
            <div class="mx-auto mb-5 max-w-4xl rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-foreground">
                <div class="flex items-center gap-2 font-black text-destructive">
                    <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                    <span>{{ __('messages.booking_validation_error_title') }}</span>
                </div>
                <ul class="mt-3 list-disc space-y-1 pr-5 text-xs text-foreground/85">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="max-w-4xl mx-auto rounded-2xl border border-border bg-card shadow-elegant overflow-hidden">

            <!-- Tab Contents -->
            <div id="tab-sea" class="tab-content p-6 space-y-6">
                <form action="@isset($booking) {{ route('orders.update', $booking->id) }} @else {{ route('booking.store') }} @endisset" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @isset($booking) @method('PUT') @endisset
                    <input type="hidden" name="shipping_category" value="sea" />
                    @if($parentBooking)
                        <input type="hidden" name="parent_booking_id" value="{{ $parentBooking->id }}" />
                        <div class="rounded-lg border border-accent/30 bg-accent/10 p-3 text-sm text-accent-foreground">
                            <div class="font-bold">{{ __('messages.booking_parent_booking_note', ['id' => $parentBooking->id]) }}</div>
                            <div class="mt-1 text-xs text-muted-foreground">{{ __('messages.booking_parent_booking_hint') }}</div>
                        </div>
                    @endif

                    <!-- Client Details -->
                <div class="space-y-4">
                        <h2 class="flex items-center gap-2 text-lg font-bold text-primary border-b pb-2">
                        <i data-lucide="users" class="h-5 w-5 text-accent"></i>
                        <span>{{ __('messages.booking_client_data') }}</span>
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <label for="customer_id_sea" class="text-xs font-bold text-foreground">{{ __('messages.booking_select_customer') }}</label>
                            <select name="customer_id" id="customer_id_sea" onchange="populateCustomerFields('sea')" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="">{{ __('messages.booking_manual_entry') }}</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->company }} @if($customer->contact_name) - {{ $customer->contact_name }} @endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label for="company" class="text-xs font-bold text-foreground">{{ __('messages.booking_company_label') }}</label>
                            <input type="text" name="company" id="company" required value="{{ old('company', $parentBooking?->customer_company ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                        </div>
                        <div class="space-y-2">
                            <label for="contactName" class="text-xs font-bold text-foreground">{{ __('messages.booking_contact_label') }}</label>
                            <input type="text" name="contactName" id="contactName" required value="{{ old('contactName', $parentBooking?->customer_contact_name ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                        </div>
                        <div class="space-y-2">
                            <label for="phone" class="text-xs font-bold text-foreground">{{ __('messages.booking_phone_label') }}</label>
                            <input type="hidden" name="phone_country_code" value="+967" />
                            <input type="tel" name="phone" id="phone" dir="ltr" required value="{{ old('phone', $parentBooking?->customer_phone ?? '') }}" placeholder="7xx xxx xxx" class="intl-phone-input w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent text-right" />
                            <p class="phone-error hidden text-[11px] font-semibold text-destructive">{{ __('messages.booking_phone_invalid') }}</p>
                            <p class="text-[11px] text-muted-foreground">{{ __('messages.booking_phone_hint') }}</p>
                        </div>
                        <div class="space-y-2">
                            <label for="email" class="text-xs font-bold text-foreground">{{ __('messages.booking_email_label') }}</label>
                            <input type="email" name="email" id="email" dir="ltr" required value="{{ old('email', $parentBooking?->customer_email ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent text-right" />
                        </div>
                    </div>
                </div>

                <!-- Route -->
                <div class="space-y-4">
                    <h2 class="flex items-center gap-2 text-lg font-bold text-primary border-b pb-2">
                            <i data-lucide="anchor" class="h-5 w-5 text-accent"></i>
                            <span>{{ __('messages.booking_route_title') }}</span>
                        </h2>
                     <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="route_mode" class="text-xs font-bold text-foreground">{{ __('messages.booking_route_mode_label') }}</label>
                            <select name="route_mode" id="route_mode" required class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="FOB" {{ old('route_mode', $parentBooking && data_get($parentBooking->route_details, 'mode') === 'FOB' ? 'FOB' : 'FOB') == 'FOB' ? 'selected' : '' }}>FOB</option>
                                <option value="EXW" {{ old('route_mode', $parentBooking && data_get($parentBooking->route_details, 'mode') === 'EXW' ? 'EXW' : '') == 'EXW' ? 'selected' : '' }}>EXW</option>
                            </select>
                        </div>
                    </div>

                    <div data-route-panel="FOB" class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="fob_origin_country" class="text-xs font-bold text-foreground">{{ __('messages.booking_origin_country_label') }}</label>
                            <select name="origin_country" id="fob_origin_country" required data-country-select data-port-target="fob_pol" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="">{{ __('messages.booking_choose_country') }}</option>
                                @foreach($portCountries as $country)
                                    <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}">{{ $country['name'] }}</option>
                                @endforeach
                            </select>
                            <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                                <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                                <span></span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="fob_pol" class="text-xs font-bold text-foreground">{{ __('messages.booking_pol_label') }}</label>
                            <select id="fob_pol" required data-port-select data-port-name="pol" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="">{{ __('messages.booking_choose_country_first') }}</option>
                            </select>
                            <input type="hidden" name="pol" data-port-value value="{{ old('pol', data_get($parentBooking?->route_details, 'origin.port') ?? '') }}">
                            <input type="text" data-custom-port-input disabled placeholder="{{ __('messages.booking_port_name_placeholder') }}" class="hidden w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                            <p data-custom-port-hint class="hidden text-[11px] text-muted-foreground">{{ __('messages.booking_port_saved_hint') }}</p>
                        </div>
                        <div class="space-y-2">
                            <label for="fob_destination_country" class="text-xs font-bold text-foreground">{{ __('messages.booking_destination_country_label') }}</label>
                            <select name="destination_country" id="fob_destination_country" required data-country-select data-port-target="fob_pod" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="">{{ __('messages.booking_choose_country') }}</option>
                                @foreach($portCountries as $country)
                                    <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}">{{ $country['name'] }}</option>
                                @endforeach
                            </select>
                            <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                                <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                                <span></span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label for="fob_pod" class="text-xs font-bold text-foreground">{{ __('messages.booking_pod_label') }}</label>
                            <select id="fob_pod" required data-port-select data-port-name="pod" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="">{{ __('messages.booking_choose_country_first') }}</option>
                            </select>
                            <input type="hidden" name="pod" data-port-value value="{{ old('pod', data_get($parentBooking?->route_details, 'destination.port') ?? '') }}">
                            <input type="text" data-custom-port-input disabled placeholder="{{ __('messages.booking_port_name_placeholder') }}" class="hidden w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                            <p data-custom-port-hint class="hidden text-[11px] text-muted-foreground">{{ __('messages.booking_port_saved_hint') }}</p>
                        </div>
                    </div>

                    <div data-route-panel="EXW" class="hidden gap-4 md:grid-cols-2">
                        <div class="md:col-span-2 rounded-lg border border-border/70 bg-secondary/10 p-3">
                            <h3 class="mb-3 text-sm font-bold text-foreground">{{ __('messages.booking_exw_departure') }}</h3>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="space-y-2">
                                    <label for="exw_origin_country" class="text-xs font-bold text-foreground">{{ __('messages.booking_origin_country_label') }}</label>
                                    <select name="origin_country" id="exw_origin_country" required disabled data-country-select data-port-target="exw_pol" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">{{ __('messages.booking_choose_country') }}</option>
                                        @foreach($portCountries as $country)
                                            <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}">{{ $country['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                                        <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                                        <span></span>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label for="exw_origin_city" class="text-xs font-bold text-foreground">{{ __('messages.booking_city_label') }}</label>
                                    <input type="text" name="origin_city" id="exw_origin_city" required disabled class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="exw_pol" class="text-xs font-bold text-foreground">{{ __('messages.booking_pol_label') }}</label>
                                    <select id="exw_pol" required disabled data-port-select data-port-name="pol" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">{{ __('messages.booking_choose_country_first') }}</option>
                                    </select>
                                    <input type="hidden" name="pol" data-port-value disabled>
                                    <input type="text" data-custom-port-input disabled placeholder="{{ __('messages.booking_port_name_placeholder') }}" class="hidden w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                    <p data-custom-port-hint class="hidden text-[11px] text-muted-foreground">{{ __('messages.booking_port_saved_hint') }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-lg border border-border/70 bg-secondary/10 p-3">
                            <h3 class="mb-3 text-sm font-bold text-foreground">{{ __('messages.booking_exw_arrival') }}</h3>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="space-y-2">
                                    <label for="exw_destination_country" class="text-xs font-bold text-foreground">{{ __('messages.booking_destination_country_label') }}</label>
                                    <select name="destination_country" id="exw_destination_country" required disabled data-country-select data-port-target="exw_pod" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">{{ __('messages.booking_choose_country') }}</option>
                                        @foreach($portCountries as $country)
                                            <option value="{{ $country['name'] }}" data-country-code="{{ $country['code'] }}">{{ $country['name'] }}</option>
                                        @endforeach
                                    </select>
                                    <div data-country-flag-preview class="hidden items-center gap-2 text-[11px] font-semibold text-muted-foreground">
                                        <img alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                                        <span></span>
                                    </div>
                                </div>
                                <div class="space-y-2">
                                    <label for="exw_destination_city" class="text-xs font-bold text-foreground">{{ __('messages.booking_city_label') }}</label>
                                    <input type="text" name="destination_city" id="exw_destination_city" required disabled class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="exw_pod" class="text-xs font-bold text-foreground">{{ __('messages.booking_pod_label') }}</label>
                                    <select id="exw_pod" required disabled data-port-select data-port-name="pod" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">{{ __('messages.booking_choose_country_first') }}</option>
                                    </select>
                                    <input type="hidden" name="pod" data-port-value disabled>
                                    <input type="text" data-custom-port-input disabled placeholder="{{ __('messages.booking_port_name_placeholder') }}" class="hidden w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                    <p data-custom-port-hint class="hidden text-[11px] text-muted-foreground">{{ __('messages.booking_port_saved_hint') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <datalist id="ports_list">
                    @foreach($portCountries as $country)
                        <option value="{{ $country['name'] }}"></option>
                    @endforeach
                </datalist>

                <!-- Cargo Details -->
                <div class="space-y-4">
                        <h2 class="flex items-center gap-2 text-lg font-bold text-primary border-b pb-2">
                        <i data-lucide="box" class="h-5 w-5 text-accent"></i>
                        <span>{{ __('messages.booking_cargo_details') }}</span>
                    </h2>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="shipmentType" class="text-xs font-bold text-foreground">{{ __('messages.booking_shipment_type_label') }}</label>
                            <select name="shipmentType" id="shipmentType" required class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="FCL">{{ __('messages.booking_shipment_fcl') }}</option>
                                <option value="LCL">{{ __('messages.booking_shipment_lcl') }}</option>
                                <option value="Bulk">Bulk</option>
                                <option value="RoRo">RoRo</option>
                                <option value="Flat Container">Flat Container</option>
                            </select>
                        </div>
                        
                        <!-- FCL Options Container -->
                        <?php
                            $containerTypes = ['20GP', '40GP', '40HC', '45GP', '45HC', 'Reefer 20', 'Reefer 40', 'OT 20', 'OT 40'];
                            $fclRows = old('containers', data_get($booking?->cargo_details, 'containers'));
                            if (empty($fclRows)) {
                                $fclRows = [[
                                    'type' => old('containerType', $booking?->container_type ?? '40HC'),
                                    'qty' => old('containerQty', $booking?->container_qty ?? 1),
                                ]];
                            }
                        ?>
                        <div id="fcl-options" class="space-y-3 rounded-xl border border-border/80 bg-secondary/10 p-4 md:col-span-2">
                            <input type="hidden" name="containerType" id="containerType" value="{{ old('containerType', data_get($fclRows, '0.type', '40HC')) }}">
                            <input type="hidden" name="containerQty" id="containerQty" value="{{ old('containerQty', data_get($fclRows, '0.qty', 1)) }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-bold text-foreground">{{ __('messages.booking_fcl_containers_title') }}</h3>
                                    <p class="text-[11px] font-semibold text-muted-foreground">{{ __('messages.booking_fcl_containers_hint') }}</p>
                                </div>
                                <button type="button" id="add-fcl-container-row" class="inline-flex h-9 items-center gap-2 rounded-lg bg-accent px-3 text-xs font-bold text-accent-foreground shadow-sm hover:opacity-95">
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    <span>{{ __('messages.booking_add_container_row') }}</span>
                                </button>
                            </div>
                            <div id="fcl-container-rows" class="space-y-2">
                                @foreach($fclRows as $index => $row)
                                    <div class="fcl-container-row grid gap-3 rounded-lg border border-border/70 bg-background/80 p-3 sm:grid-cols-[minmax(0,1fr)_140px_40px]">
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-foreground">{{ __('messages.booking_container_type_label') }}</label>
                                            <select name="containers[{{ $index }}][type]" data-fcl-container-type class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                                @foreach($containerTypes as $type)
                                                    <option value="{{ $type }}" @selected(data_get($row, 'type') === $type)>{{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-foreground">{{ __('messages.booking_container_qty_label') }}</label>
                                            <input type="number" name="containers[{{ $index }}][qty]" data-fcl-container-qty value="{{ data_get($row, 'qty', 1) }}" min="1" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                        </div>
                                        <div class="flex items-end">
                                            <button type="button" data-remove-fcl-container class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-background text-muted-foreground hover:border-destructive/40 hover:text-destructive" title="{{ __('messages.booking_remove_container_row') }}">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <template id="fcl-container-row-template">
                                <div class="fcl-container-row grid gap-3 rounded-lg border border-border/70 bg-background/80 p-3 sm:grid-cols-[minmax(0,1fr)_140px_40px]">
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-foreground">{{ __('messages.booking_container_type_label') }}</label>
                                        <select name="containers[__INDEX__][type]" data-fcl-container-type class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                            @foreach($containerTypes as $type)
                                                <option value="{{ $type }}">{{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="text-xs font-bold text-foreground">{{ __('messages.booking_container_qty_label') }}</label>
                                        <input type="number" name="containers[__INDEX__][qty]" data-fcl-container-qty value="1" min="1" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                    </div>
                                    <div class="flex items-end">
                                        <button type="button" data-remove-fcl-container class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-background text-muted-foreground hover:border-destructive/40 hover:text-destructive" title="{{ __('messages.booking_remove_container_row') }}">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </template>
                            <div id="reefer-temperature-wrapper" class="hidden space-y-2 md:col-span-2">
                                <label for="reefer_temperature" class="text-xs font-bold text-foreground">{{ __('messages.booking_reefer_temperature_label') }}</label>
                                <input type="text" name="reefer_temperature" id="reefer_temperature" value="{{ old('reefer_temperature', data_get($booking?->cargo_details, 'reefer_temperature') ?? '') }}" placeholder="{{ __('messages.booking_reefer_example') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                            </div>
                        </div>

                        <div id="flat-container-options" class="hidden md:col-span-2 space-y-4 rounded-xl border border-border/80 bg-secondary/10 p-4">
                            <h3 class="text-sm font-bold text-foreground">Flat Container cargo details</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2"><label for="flat_cargo_name" class="text-xs font-bold text-foreground">Cargo description</label><input type="text" name="flat_cargo_name" id="flat_cargo_name" value="{{ old('flat_cargo_name', data_get($booking?->cargo_details, 'cargo_name') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm" /></div>
                                <div class="space-y-2"><label for="flat_pieces" class="text-xs font-bold text-foreground">Number of pieces</label><input type="number" name="flat_pieces" id="flat_pieces" value="{{ old('flat_pieces', data_get($booking?->cargo_details, 'pieces') ?? '') }}" min="1" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm" /></div>
                                <div class="space-y-2"><label for="flat_piece_weight" class="text-xs font-bold text-foreground">Weight per piece (kg)</label><input type="number" step="0.01" name="flat_piece_weight" id="flat_piece_weight" value="{{ old('flat_piece_weight', data_get($booking?->cargo_details, 'piece_weight') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm" /></div>
                                <div class="space-y-2"><label for="flat_total_weight" class="text-xs font-bold text-foreground">Total weight (kg)</label><input type="number" step="0.01" name="flat_total_weight" id="flat_total_weight" value="{{ old('flat_total_weight', data_get($booking?->cargo_details, 'total_weight') ?? '') }}" readonly class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm bg-secondary/30" /></div>
                                <div class="space-y-2"><label for="flat_length" class="text-xs font-bold text-foreground">Length (m)</label><input type="number" step="0.01" name="flat_length" id="flat_length" value="{{ old('flat_length', data_get($booking?->cargo_details, 'length') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm" /></div>
                                <div class="space-y-2"><label for="flat_width" class="text-xs font-bold text-foreground">Width (m)</label><input type="number" step="0.01" name="flat_width" id="flat_width" value="{{ old('flat_width', data_get($booking?->cargo_details, 'width') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm" /></div>
                                <div class="space-y-2"><label for="flat_height" class="text-xs font-bold text-foreground">Height (m)</label><input type="number" step="0.01" name="flat_height" id="flat_height" value="{{ old('flat_height', data_get($booking?->cargo_details, 'height') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm" /></div>
                                <div class="space-y-2"><label for="flat_cbm" class="text-xs font-bold text-foreground">Total CBM</label><input type="number" step="0.1" name="flat_cbm" id="flat_cbm" value="{{ old('flat_cbm', data_get($booking?->cargo_details, 'total_cbm') ?? '') }}" readonly class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm bg-secondary/30" /></div>
                                <div class="space-y-2"><label for="flat_packaging" class="text-xs font-bold text-foreground">Packaging</label><input type="text" name="flat_packaging" id="flat_packaging" value="{{ old('flat_packaging', data_get($booking?->cargo_details, 'packaging_type') ?? '') }}" placeholder="Pallet, bundle, crate..." class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm" /></div>
                                <div class="space-y-2"><label for="flat_lifting" class="text-xs font-bold text-foreground">Lifting method</label><input type="text" name="flat_lifting" id="flat_lifting" value="{{ old('flat_lifting', data_get($booking?->cargo_details, 'lifting_method') ?? '') }}" placeholder="Forklift or crane" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm" /></div>
                                <label class="flex items-center gap-2 text-sm font-bold text-foreground md:col-span-2"><input type="checkbox" name="flat_stackable" id="flat_stackable" @checked(old('flat_stackable', data_get($booking?->cargo_details, 'stackable', false))) class="h-4 w-4 rounded border-border bg-background text-accent" /><span>Stackable</span></label>
                            </div>
                        </div>

                        <div class="space-y-2">
                            <label for="weightKg" class="text-xs font-bold text-foreground">{{ __('messages.booking_total_weight_label') }}</label>
                            <input type="number" name="weightKg" id="weightKg" required class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                        </div>
                        <div class="space-y-2">
                            <label for="volumeCbm" class="text-xs font-bold text-foreground">{{ __('messages.booking_volume_label') }}</label>
                            <input type="number" step="0.1" min="0" name="volumeCbm" id="volumeCbm" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                        </div>

                        <div class="space-y-2">
                            <label for="hs_code_sea" class="text-xs font-bold text-foreground">{{ __('messages.booking_hs_code_label') }}</label>
                            <input type="text" name="hs_code" id="hs_code_sea" value="{{ old('hs_code', data_get($booking?->cargo_details, 'hs_code') ?? '') }}" placeholder="مثال: 8703.23" dir="ltr" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                        </div>

                        <div id="lcl-options" class="hidden md:col-span-2 space-y-3 rounded-xl border border-border/80 bg-secondary/10 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <h3 class="text-sm font-bold text-foreground">{{ __('messages.booking_lcl_dimensions_title') }}</h3>
                                <span class="rounded-full bg-accent/10 px-2.5 py-1 text-[11px] font-bold text-accent">CBM</span>
                            </div>
                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="space-y-2">
                                    <label for="lcl_length_m" class="text-xs font-bold text-foreground">{{ __('messages.booking_lcl_length_m_label') }}</label>
                                    <input type="number" step="0.01" min="0" name="lcl_length_m" id="lcl_length_m" value="{{ old('lcl_length_m', data_get($booking?->cargo_details, 'dimensions.length_m') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="lcl_width_m" class="text-xs font-bold text-foreground">{{ __('messages.booking_lcl_width_m_label') }}</label>
                                    <input type="number" step="0.01" min="0" name="lcl_width_m" id="lcl_width_m" value="{{ old('lcl_width_m', data_get($booking?->cargo_details, 'dimensions.width_m') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="lcl_height_m" class="text-xs font-bold text-foreground">{{ __('messages.booking_lcl_height_m_label') }}</label>
                                    <input type="number" step="0.01" min="0" name="lcl_height_m" id="lcl_height_m" value="{{ old('lcl_height_m', data_get($booking?->cargo_details, 'dimensions.height_m') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                            </div>
                            <p class="text-[11px] font-semibold text-muted-foreground">{{ __('messages.booking_lcl_dimensions_hint') }}</p>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label for="goodsDescription" class="text-xs font-bold text-foreground">{{ __('messages.booking_goods_description_label') }}</label>
                            <textarea name="goodsDescription" id="goodsDescription" required rows="3" placeholder="{{ __('messages.booking_goods_description_example') }}" class="w-full p-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                        </div>

                        <!-- Dangerous switch -->
                        <div class="md:col-span-2 flex items-center justify-between rounded-xl border border-border/80 p-4 bg-secondary/20">
                            <div>
                                <label for="dangerous" class="text-sm font-bold text-foreground block cursor-pointer">{{ __('messages.booking_dangerous_label') }}</label>
                                <span class="text-xs text-muted-foreground">{{ __('messages.booking_dangerous_hint') }}</span>
                            </div>
                            <input type="checkbox" name="dangerous" id="dangerous" value="1" class="h-5 w-5 rounded border-border bg-background text-accent focus:ring-accent" />
                        </div>

                        <div id="bulk-options" class="hidden md:col-span-2 space-y-4 rounded-xl border border-border/80 bg-secondary/10 p-4">
                            <h3 class="text-sm font-bold text-foreground">{{ __('messages.booking_bulk_details') }}</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="bulk_cargo_name" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_cargo_name') }}</label>
                                    <input type="text" name="bulk_cargo_name" id="bulk_cargo_name" value="{{ old('bulk_cargo_name', data_get($booking?->cargo_details, 'cargo_name') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="bulk_pieces" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_pieces') }}</label>
                                    <input type="number" name="bulk_pieces" id="bulk_pieces" value="{{ old('bulk_pieces', data_get($booking?->cargo_details, 'pieces') ?? '') }}" min="1" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="bulk_piece_weight" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_piece_weight') }}</label>
                                    <input type="number" step="0.01" name="bulk_piece_weight" id="bulk_piece_weight" value="{{ old('bulk_piece_weight', data_get($booking?->cargo_details, 'piece_weight') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="bulk_total_weight" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_total_weight') }}</label>
                                    <input type="number" step="0.01" min="0" name="bulk_total_weight" id="bulk_total_weight" value="{{ old('bulk_total_weight', data_get($booking?->cargo_details, 'total_weight') ?? data_get($booking?->cargo_details, 'gross_weight_kg') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent bg-secondary/30" readonly />
                                </div>
                                <div class="space-y-2">
                                    <label for="bulk_length" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_length') }}</label>
                                    <input type="number" step="0.01" name="bulk_length" id="bulk_length" value="{{ old('bulk_length', data_get($booking?->cargo_details, 'length') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="bulk_width" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_width') }}</label>
                                    <input type="number" step="0.01" name="bulk_width" id="bulk_width" value="{{ old('bulk_width', data_get($booking?->cargo_details, 'width') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="bulk_height" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_height') }}</label>
                                    <input type="number" step="0.01" name="bulk_height" id="bulk_height" value="{{ old('bulk_height', data_get($booking?->cargo_details, 'height') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="bulk_cbm" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_piece_cbm') }}</label>
                                    <input type="number" step="0.1" min="0" name="bulk_cbm" id="bulk_cbm" value="{{ old('bulk_cbm', data_get($booking?->cargo_details, 'piece_cbm') ?? data_get($booking?->cargo_details, 'cbm') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent bg-secondary/30" readonly />
                                </div>
                                <p class="text-[11px] font-semibold text-muted-foreground md:col-span-2">{{ __('messages.booking_bulk_dimensions_hint') }}</p>
                                <div class="space-y-2">
                                    <label for="bulk_packaging" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_packaging') }}</label>
                                    <select name="bulk_packaging" id="bulk_packaging" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">{{ __('messages.booking_choose') }}</option>
                                        <option value="Pallet" {{ old('bulk_packaging', data_get($booking?->cargo_details, 'packaging_type') ?? '') === 'Pallet' ? 'selected' : '' }}>Pallet</option>
                                        <option value="Bundle" {{ old('bulk_packaging', data_get($booking?->cargo_details, 'packaging_type') ?? '') === 'Bundle' ? 'selected' : '' }}>Bundle</option>
                                        <option value="Crate" {{ old('bulk_packaging', data_get($booking?->cargo_details, 'packaging_type') ?? '') === 'Crate' ? 'selected' : '' }}>Crate</option>
                                        <option value="Box" {{ old('bulk_packaging', data_get($booking?->cargo_details, 'packaging_type') ?? '') === 'Box' ? 'selected' : '' }}>Box</option>
                                        <option value="Bag" {{ old('bulk_packaging', data_get($booking?->cargo_details, 'packaging_type') ?? '') === 'Bag' ? 'selected' : '' }}>Bag</option>
                                        <option value="Drum" {{ old('bulk_packaging', data_get($booking?->cargo_details, 'packaging_type') ?? '') === 'Drum' ? 'selected' : '' }}>Drum</option>
                                        <option value="Loose" {{ old('bulk_packaging', data_get($booking?->cargo_details, 'packaging_type') ?? '') === 'Loose' ? 'selected' : '' }}>Loose</option>
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label for="bulk_lifting" class="text-xs font-bold text-foreground">{{ __('messages.booking_bulk_lifting') }}</label>
                                    <select name="bulk_lifting" id="bulk_lifting" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">{{ __('messages.booking_choose') }}</option>
                                        <option value="Forklift" {{ old('bulk_lifting', data_get($booking?->cargo_details, 'lifting_method') ?? '') === 'Forklift' ? 'selected' : '' }}>Forklift</option>
                                        <option value="Crane" {{ old('bulk_lifting', data_get($booking?->cargo_details, 'lifting_method') ?? '') === 'Crane' ? 'selected' : '' }}>Crane</option>
                                        <option value="Manual" {{ old('bulk_lifting', data_get($booking?->cargo_details, 'lifting_method') ?? '') === 'Manual' ? 'selected' : '' }}>Manual</option>
                                    </select>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                                        <input type="checkbox" name="bulk_stackable" id="bulk_stackable" @checked(old('bulk_stackable', data_get($booking?->cargo_details, 'stackable', false))) class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent" />
                                        <span>{{ __('messages.booking_bulk_stackable') }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div id="roro-options" class="hidden md:col-span-2 space-y-4 rounded-xl border border-border/80 bg-secondary/10 p-4">
                            <h3 class="text-sm font-bold text-foreground">{{ __('messages.booking_roro_details') }}</h3>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="roro_vehicle_type" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_vehicle_type') }}</label>
                                    <input type="text" name="roro_vehicle_type" id="roro_vehicle_type" value="{{ old('roro_vehicle_type', data_get($booking?->cargo_details, 'vehicle_type') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_manufacturer" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_manufacturer') }}</label>
                                    <input type="text" name="roro_manufacturer" id="roro_manufacturer" value="{{ old('roro_manufacturer', data_get($booking?->cargo_details, 'manufacturer') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_model" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_model') }}</label>
                                    <input type="text" name="roro_model" id="roro_model" value="{{ old('roro_model', data_get($booking?->cargo_details, 'model') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_year" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_year') }}</label>
                                    <input type="number" name="roro_year" id="roro_year" value="{{ old('roro_year', data_get($booking?->cargo_details, 'year') ?? '') }}" min="1900" max="2100" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_color" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_color') }}</label>
                                    <input type="text" name="roro_color" id="roro_color" value="{{ old('roro_color', data_get($booking?->cargo_details, 'color') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_vin" class="text-xs font-bold text-foreground">VIN</label>
                                    <input type="text" name="roro_vin" id="roro_vin" value="{{ old('roro_vin', data_get($booking?->cargo_details, 'vin') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_chassis_number" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_chassis_number') }}</label>
                                    <input type="text" name="roro_chassis_number" id="roro_chassis_number" value="{{ old('roro_chassis_number', data_get($booking?->cargo_details, 'chassis_number') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_engine_number" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_engine_number') }}</label>
                                    <input type="text" name="roro_engine_number" id="roro_engine_number" value="{{ old('roro_engine_number', data_get($booking?->cargo_details, 'engine_number') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_plate_number" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_plate_number') }}</label>
                                    <input type="text" name="roro_plate_number" id="roro_plate_number" value="{{ old('roro_plate_number', data_get($booking?->cargo_details, 'plate_number') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_length" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_length') }}</label>
                                    <input type="number" step="0.01" name="roro_length" id="roro_length" value="{{ old('roro_length', data_get($booking?->cargo_details, 'length') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_width" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_width') }}</label>
                                    <input type="number" step="0.01" name="roro_width" id="roro_width" value="{{ old('roro_width', data_get($booking?->cargo_details, 'width') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_height" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_height') }}</label>
                                    <input type="number" step="0.01" name="roro_height" id="roro_height" value="{{ old('roro_height', data_get($booking?->cargo_details, 'height') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_weight" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_weight') }}</label>
                                    <input type="number" step="0.01" name="roro_weight" id="roro_weight" value="{{ old('roro_weight', data_get($booking?->cargo_details, 'weight') ?? '') }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_fuel_level" class="text-xs font-bold text-foreground">{{ __('messages.booking_roro_fuel_level') }}</label>
                                    <select name="roro_fuel_level" id="roro_fuel_level" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">{{ __('messages.booking_choose') }}</option>
                                        <option value="Quarter" {{ old('roro_fuel_level', data_get($booking?->cargo_details, 'fuel_level') ?? '') === 'Quarter' ? 'selected' : '' }}>{{ __('messages.booking_fuel_quarter') }}</option>
                                        <option value="Half" {{ old('roro_fuel_level', data_get($booking?->cargo_details, 'fuel_level') ?? '') === 'Half' ? 'selected' : '' }}>{{ __('messages.booking_fuel_half') }}</option>
                                        <option value="Empty" {{ old('roro_fuel_level', data_get($booking?->cargo_details, 'fuel_level') ?? '') === 'Empty' ? 'selected' : '' }}>{{ __('messages.booking_fuel_empty') }}</option>
                                    </select>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                                        <input type="checkbox" name="roro_running" id="roro_running" @checked(old('roro_running', data_get($booking?->cargo_details, 'running', false))) class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent" />
                                        <span>{{ __('messages.booking_roro_running') }}</span>
                                    </label>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                                        <input type="checkbox" name="roro_tow_required" id="roro_tow_required" @checked(old('roro_tow_required', data_get($booking?->cargo_details, 'tow_required', false))) class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent" />
                                        <span>Tow Required</span>
                                    </label>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                                        <input type="checkbox" name="roro_has_key" id="roro_has_key" @checked(old('roro_has_key', data_get($booking?->cargo_details, 'has_key', false))) class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent" />
                                        <span>{{ __('messages.booking_roro_has_key') }}</span>
                                    </label>
                                </div>
                                <div class="space-y-2 md:col-span-2">
                                    <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                                        <input type="checkbox" name="roro_has_items" id="roro_has_items" @checked(old('roro_has_items', data_get($booking?->cargo_details, 'has_items', false))) class="h-4 w-4 rounded border-border bg-background text-accent focus:ring-accent" />
                                        <span>{{ __('messages.booking_roro_has_items') }}</span>
                                    </label>
                                </div>
                                <div class="space-y-2">
                                    <label for="roro_value" class="text-xs font-bold text-foreground">{{ __('messages.booking_detail_value') }}</label>
                                    <input type="number" step="0.01" name="roro_value" id="roro_value" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                            </div>
                        </div>

                        @include('partials.cargo-items-editor', [
                            'editorId' => 'sea-cargo-items-editor',
                            'cargoItems' => data_get($booking?->cargo_details, 'cargo_items', []),
                            'defaultUnit' => old('shipmentType', $booking?->shipment_type) === 'RoRo' ? 'vehicle' : 'piece',
                            'class' => 'hidden md:col-span-2',
                        ])

                        <div class="md:col-span-2 space-y-2">
                            <label for="notes" class="text-xs font-bold text-foreground">{{ __('messages.booking_additional_notes') }}</label>
                            <textarea name="notes" id="notes" rows="3" placeholder="{{ __('messages.booking_additional_notes_example') }}" class="w-full p-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label for="documents" class="text-xs font-bold text-foreground">{{ __('messages.booking_documents_label') }}</label>
                            <input type="text" name="documents" id="documents" placeholder="Invoice, Packing List, MSDS ..." class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                        </div>

                        <div class="md:col-span-2">
                            @include('partials.booking-attachments-field', [
                                'inputId' => 'attachments',
                                'currentBooking' => $booking ?? null,
                            ])
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap justify-end gap-3 pt-4 border-t">
                    <a href="{{ $parentBooking ? route('orders.index') : route('dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-border bg-card px-6 text-sm font-bold hover:bg-secondary/40">{{ __('messages.bookings_cancel') }}</a>
                    <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground shadow hover:opacity-95">
                        <i data-lucide="send" class="ml-2 h-4 w-4"></i>
                        <span>{{ __('messages.booking_send_for_pricing') }}</span>
                    </button>
                </div>
                </form>
            </div>

            <div id="tab-air" class="tab-content hidden p-6 space-y-6">
                @include('partials.booking-simple-service-form', [
                    'service' => [
                        'tab' => 'air',
                        'category' => 'air',
                        'route_mode' => 'AIR',
                        'shipment_type' => 'AIR',
                        'icon' => 'plane',
                        'title' => __('messages.booking_air_details'),
                        'route_title' => __('messages.booking_air_route_title'),
                        'origin_country_label' => __('messages.booking_air_origin_country_label'),
                        'origin_city_label' => __('messages.booking_air_origin_city_label'),
                        'pol_label' => __('messages.booking_air_origin_airport_label'),
                        'pol_placeholder' => __('messages.booking_air_origin_airport_placeholder'),
                        'destination_country_label' => __('messages.booking_air_destination_country_label'),
                        'destination_city_label' => __('messages.booking_air_destination_city_label'),
                        'pod_label' => __('messages.booking_air_destination_airport_label'),
                        'pod_placeholder' => __('messages.booking_air_destination_airport_placeholder'),
                    ],
                ])
            </div>

            <div id="tab-land" class="tab-content hidden p-6 space-y-6">
                <form action="@isset($booking) {{ route('orders.update', $booking->id) }} @else {{ route('booking.store') }} @endisset" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    @isset($booking) @method('PUT') @endisset
                    <input type="hidden" name="shipping_category" value="land" />
                    <input type="hidden" name="shipmentType" value="LAND" />

                    <h2 class="flex items-center gap-2 text-lg font-bold text-primary border-b pb-2">
                        <i data-lucide="truck" class="h-5 w-5 text-accent"></i>
                        <span>{{ __('messages.booking_land_details') }}</span>
                    </h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="md:col-span-2 rounded-2xl border border-border/80 bg-secondary/10 p-4">
                            <div class="mb-4 flex items-center justify-between gap-3 border-b border-border/80 pb-3">
                                <div>
                                    <h3 class="text-sm font-bold text-foreground">بيانات العميل</h3>
                                    <p class="text-[11px] text-muted-foreground">معلومات الاتصال الأساسية للطلب البري</p>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-border bg-background px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-muted-foreground">Client</span>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2 md:col-span-2">
                                    <label for="customer_id_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_select_customer') }}</label>
                                    <select name="customer_id" id="customer_id_land" onchange="populateCustomerFields('land')" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">{{ __('messages.booking_manual_entry') }}</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->company }} @if($customer->contact_name) - {{ $customer->contact_name }} @endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-2">
                                    <label for="company_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_company_label') }}</label>
                                    <input type="text" name="company" id="company_land" required class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="contactName_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_contact_label') }}</label>
                                    <input type="text" name="contactName" id="contactName_land" required class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="phone_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_phone_label') }}</label>
                                    <input type="hidden" name="phone_country_code" value="+967" />
                                    <input type="tel" name="phone" id="phone_land" dir="ltr" required placeholder="7xx xxx xxx" class="intl-phone-input w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent text-right" />
                                    <p class="phone-error hidden text-[11px] font-semibold text-destructive">{{ __('messages.booking_phone_invalid') }}</p>
                                    <p class="text-[11px] text-muted-foreground">{{ __('messages.booking_phone_hint') }}</p>
                                </div>
                                <div class="space-y-2">
                                    <label for="email_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_email_label') }}</label>
                                    <input type="email" name="email" id="email_land" dir="ltr" required class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent text-right" />
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-2xl border border-border/80 bg-white/5 p-4 shadow-sm">
                            <div class="mb-4 flex items-center justify-between gap-3 border-b border-border/80 pb-3">
                                <div>
                                    <h3 class="text-sm font-bold text-foreground">مسار الشحن البري</h3>
                                    <p class="text-[11px] text-muted-foreground">حدد نقطة الانطلاق والوصول داخل نفس الطلب</p>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-accent/30 bg-accent/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-accent">Route</span>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="pol_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_land_origin_label') }}</label>
                                    <input list="ports_list" type="text" name="pol" id="pol_land" placeholder="اختر الدولة" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="pod_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_land_destination_label') }}</label>
                                    <input list="ports_list" type="text" name="pod" id="pod_land" placeholder="اختر الدولة" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2 rounded-2xl border border-border/80 bg-secondary/10 p-4">
                            <div class="mb-4 flex items-center justify-between gap-3 border-b border-border/80 pb-3">
                                <div>
                                    <h3 class="text-sm font-bold text-foreground">تفاصيل البضاعة</h3>
                                    <p class="text-[11px] text-muted-foreground">وزن، حجم، ووصف الشحنة</p>
                                </div>
                                <span class="inline-flex items-center rounded-full border border-border bg-background px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-muted-foreground">Cargo</span>
                            </div>
                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-2">
                                    <label for="weightKg_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_total_weight_label') }}</label>
                                    <input type="number" name="weightKg" id="weightKg_land" required class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="volumeCbm_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_land_volume_label') }}</label>
                                    <input type="number" step="0.01" name="volumeCbm" id="volumeCbm_land" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>
                                <div class="space-y-2">
                                    <label for="landCartonCount_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_land_carton_count_label') }}</label>
                                    <input type="number" name="land_carton_count" id="landCartonCount_land" value="{{ old('land_carton_count', data_get($booking ?? null, 'cargo_details.land_carton_count')) }}" min="0" step="1" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                </div>

                                <div class="md:col-span-2 space-y-2">
                                    <label for="goodsDescription_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_goods_description_label') }}</label>
                                    <textarea name="goodsDescription" id="goodsDescription_land" required rows="3" class="w-full p-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="md:col-span-2 space-y-3 rounded-xl border border-border/80 bg-secondary/10 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h3 class="text-sm font-bold text-foreground">الشحنات البرية</h3>
                                    <p class="text-[11px] font-semibold text-muted-foreground">أضف أكثر من مسار بري في نفس الطلب الرئيسي، مع عدد الشاحنات والمدينة المصدر والمدينة المقصد.</p>
                                </div>
                                <button type="button" id="add-land-shipment-row" class="inline-flex h-9 items-center gap-2 rounded-lg bg-accent px-3 text-xs font-bold text-accent-foreground shadow-sm hover:opacity-95">
                                    <i data-lucide="plus" class="h-4 w-4"></i>
                                    <span>إضافة مسار آخر</span>
                                </button>
                            </div>

                            @php
                                $currentBooking = $booking ?? null;
                                $landShipments = old('land_shipments', data_get($currentBooking?->cargo_details, 'land_shipments'));
                                if (empty($landShipments)) {
                                    $landShipments = [[
                                        'qty' => old('land_truck_qty', 1),
                                        'from_city' => old('land_from_city', data_get($currentBooking?->route_details, 'origin.city', $currentBooking?->pol ?? '')),
                                        'to_city' => old('land_to_city', data_get($currentBooking?->route_details, 'destination.city', $currentBooking?->pod ?? '')),
                                    ]];
                                }
                            @endphp

                            <div id="land-shipment-rows" class="space-y-3">
                                @foreach($landShipments as $index => $row)
                                    <div class="land-shipment-row rounded-2xl border border-border/80 bg-background/80 p-3 shadow-sm">
                                        <div class="mb-3 flex items-center justify-between gap-2">
                                            <span class="inline-flex items-center rounded-full border border-accent/30 bg-accent/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-accent">مسار {{ $index + 1 }}</span>
                                            <button type="button" data-remove-land-shipment class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-muted-foreground hover:border-destructive/40 hover:text-destructive" title="حذف المسار">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                        <div class="grid gap-3 sm:grid-cols-[140px_minmax(0,1fr)_minmax(0,1fr)]">
                                            <div class="space-y-2">
                                                <label class="text-xs font-bold text-foreground">عدد الشاحنات</label>
                                                <input type="number" name="land_shipments[{{ $index }}][qty]" data-land-shipment-qty value="{{ data_get($row, 'qty', data_get($row, 'truck_qty', 1)) }}" min="1" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-xs font-bold text-foreground">من المدينة</label>
                                                <input type="text" name="land_shipments[{{ $index }}][from_city]" data-land-shipment-from value="{{ data_get($row, 'from_city', data_get($row, 'origin_city', '')) }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                            </div>
                                            <div class="space-y-2">
                                                <label class="text-xs font-bold text-foreground">إلى المدينة</label>
                                                <input type="text" name="land_shipments[{{ $index }}][to_city]" data-land-shipment-to value="{{ data_get($row, 'to_city', data_get($row, 'destination_city', '')) }}" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <template id="land-shipment-row-template">
                                <div class="land-shipment-row rounded-2xl border border-border/80 bg-background/80 p-3 shadow-sm">
                                    <div class="mb-3 flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center rounded-full border border-accent/30 bg-accent/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-accent">مسار __INDEX_PLUS_1__</span>
                                        <button type="button" data-remove-land-shipment class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-background text-muted-foreground hover:border-destructive/40 hover:text-destructive" title="حذف المسار">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                    <div class="grid gap-3 sm:grid-cols-[140px_minmax(0,1fr)_minmax(0,1fr)]">
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-foreground">عدد الشاحنات</label>
                                            <input type="number" name="land_shipments[__INDEX__][qty]" data-land-shipment-qty value="1" min="1" class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-foreground">من المدينة</label>
                                            <input type="text" name="land_shipments[__INDEX__][from_city]" data-land-shipment-from class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                        </div>
                                        <div class="space-y-2">
                                            <label class="text-xs font-bold text-foreground">إلى المدينة</label>
                                            <input type="text" name="land_shipments[__INDEX__][to_city]" data-land-shipment-to class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label for="notes_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_additional_notes') }}</label>
                            <textarea name="notes" id="notes_land" rows="3" placeholder="{{ __('messages.booking_additional_notes_example') }}" class="w-full p-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                        </div>

                        <div class="md:col-span-2 space-y-2">
                            <label for="documents_land" class="text-xs font-bold text-foreground">{{ __('messages.booking_documents_label') }}</label>
                            <input type="text" name="documents" id="documents_land" placeholder="Invoice, Packing List ..." class="w-full h-10 px-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
                        </div>

                        <div class="md:col-span-2">
                            @include('partials.booking-attachments-field', [
                                'inputId' => 'attachments_land',
                                'currentBooking' => $booking ?? null,
                            ])
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3 pt-4 border-t">
                        <a href="{{ $parentBooking ? route('orders.index') : route('dashboard') }}" class="inline-flex h-11 items-center justify-center rounded-lg border border-border bg-card px-6 text-sm font-bold hover:bg-secondary/40">{{ __('messages.bookings_cancel') }}</a>
                        <button type="submit" class="inline-flex h-11 items-center justify-center rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground shadow hover:opacity-95">{{ __('messages.booking_send_for_pricing') }}</button>
                    </div>
                </form>
            </div>

            <div id="tab-customs" class="tab-content hidden p-6 space-y-6">
                <div class="flex flex-col items-center justify-center py-16 px-4 text-center border border-dashed border-border/80 rounded-2xl bg-secondary/15">
                    <div class="grid h-16 w-16 place-items-center rounded-2xl bg-accent/10 text-accent mb-4">
                        <i data-lucide="file-check" class="h-8 w-8"></i>
                    </div>
                    <h3 class="text-xl font-bold text-foreground">{{ __('messages.booking_feature_in_progress') }}</h3>
                    <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                        {{ __('messages.booking_air_feature_in_progress') }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground font-semibold">
                        This section is currently under development.
                    </p>
                </div>
            </div>

            <div id="tab-contracting" class="tab-content hidden p-6 space-y-6">
                @include('partials.booking-simple-service-form', [
                    'service' => [
                        'tab' => 'door',
                        'category' => 'door_to_door',
                        'route_mode' => 'DOOR_TO_DOOR',
                        'shipment_type' => 'DOOR_TO_DOOR',
                        'icon' => 'package-check',
                        'title' => __('messages.booking_door_details'),
                        'route_title' => __('messages.booking_door_route_title'),
                        'origin_country_label' => __('messages.booking_pickup_country_label'),
                        'origin_city_label' => __('messages.booking_pickup_city_label'),
                        'pol_label' => __('messages.booking_pickup_address_label'),
                        'pol_placeholder' => __('messages.booking_pickup_address_placeholder'),
                        'destination_country_label' => __('messages.booking_delivery_country_label'),
                        'destination_city_label' => __('messages.booking_delivery_city_label'),
                        'pod_label' => __('messages.booking_delivery_address_label'),
                        'pod_placeholder' => __('messages.booking_delivery_address_placeholder'),
                    ],
                ])
            </div>
        </div>
    </section>
</div>

<!-- Agent forward modal (shown when a booking is in pricing stage) -->
@isset($booking)
<div id="agent-forward-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40" style="display:none;">
    <div class="w-full max-w-lg rounded-lg bg-card p-6">
        <h3 class="text-lg font-bold mb-3">{{ __('messages.agent_forward_title') }}</h3>

        <div class="mb-4">
            <label class="text-sm font-bold">{{ __('messages.agent_type_label') }}</label>
            <div class="mt-2 flex flex-wrap gap-3">
                <button type="button" id="agent-type-local" class="px-4 py-2 rounded border">{{ __('messages.agent_type_local') }}</button>
                <button type="button" id="agent-type-external" class="px-4 py-2 rounded border">{{ __('messages.agent_type_external') }}</button>
            </div>
        </div>

        <div class="mb-4">
            <label class="text-sm font-bold">{{ __('messages.agent_agents_label') }}</label>
            <div id="agents-list" class="mt-2 space-y-2 max-h-48 overflow-auto"></div>
        </div>

        <div class="mb-4">
            <label class="text-sm font-bold">{{ __('messages.agent_message_optional') }}</label>
            <textarea id="agent-forward-message" rows="3" class="w-full p-2 border rounded"></textarea>
        </div>

        <div class="flex flex-wrap justify-end gap-3">
            <button type="button" id="agent-forward-cancel" class="px-4 py-2 rounded border">{{ __('messages.agent_forward_cancel') }}</button>
            <button type="button" id="agent-forward-whatsapp" class="px-4 py-2 rounded bg-green-600 text-white">{{ __('messages.agent_forward_whatsapp') }}</button>
            <button type="button" id="agent-forward-email" class="px-4 py-2 rounded bg-blue-600 text-white">{{ __('messages.agent_forward_email') }}</button>
        </div>
    </div>
</div>

<script>
    window.bookingId = @json($booking->id);
    window.bookingStatus = @json($booking->status);
</script>
@endisset
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@26.9.2/build/js/intlTelInputWithUtils.min.js"></script>
<script>
    const intlPhoneInstances = new Map();
    const managedPorts = @json($portsForBooking);
    const managedAirports = @json($airportsForBooking ?? []);
    const countrySearchInputs = new WeakMap();
    const airportSearchInputs = new WeakMap();
    const airportMessages = {
        chooseCountryFirst: @json(__('messages.booking_choose_country_first')),
        chooseAirport: @json(__('messages.booking_choose_airport')),
        noAirportsForCountry: @json(__('messages.booking_no_airports_for_country')),
        chooseCountryFirstHint: @json(__('messages.booking_choose_country_first_airport_hint')),
        filteredHint: @json(__('messages.booking_airports_filtered_hint')),
        searchPlaceholder: @json(__('messages.booking_airport_search_placeholder')),
        noResults: @json(__('messages.booking_no_airports_found')),
        savedCode: @json(__('messages.booking_saved_airport_code')),
    };

    function normalizePhoneDigits(value) {
        const easternDigits = '٠١٢٣٤٥٦٧٨٩';
        const persianDigits = '۰۱۲۳۴۵۶۷۸۹';

        return String(value || '').replace(/[٠-٩۰-۹]/g, digit => {
            const easternIndex = easternDigits.indexOf(digit);
            if (easternIndex >= 0) return String(easternIndex);
            const persianIndex = persianDigits.indexOf(digit);
            return persianIndex >= 0 ? String(persianIndex) : digit;
        });
    }

    function getIntlCountryData(iti) {
        if (!iti) return null;
        if (typeof iti.getSelectedCountryData === 'function') return iti.getSelectedCountryData();
        if (typeof iti.getSelectedCountry === 'function') return iti.getSelectedCountry();
        return null;
    }

    function getPhoneCountryCode(iti) {
        const country = getIntlCountryData(iti);
        return country?.dialCode ? `+${country.dialCode}` : '+967';
    }

    function getPhoneCountryHiddenInput(input) {
        return input.closest('.space-y-2')?.querySelector('input[name="phone_country_code"]') || null;
    }

    function getPhoneErrorElement(input) {
        return input.closest('.space-y-2')?.querySelector('.phone-error') || null;
    }

    function syncPhoneCountryCode(input) {
        const iti = intlPhoneInstances.get(input);
        const hiddenInput = getPhoneCountryHiddenInput(input);
        if (hiddenInput) hiddenInput.value = getPhoneCountryCode(iti);
    }

    function clearPhoneError(input) {
        const error = getPhoneErrorElement(input);
        if (error) error.classList.add('hidden');
        input.removeAttribute('aria-invalid');
        input.classList.remove('border-destructive', 'focus:ring-destructive');
    }

    function showPhoneError(input, message = "{{ __('messages.booking_phone_invalid') }}") {
        const error = getPhoneErrorElement(input);
        if (error) {
            error.textContent = message;
            error.classList.remove('hidden');
        }
        input.setAttribute('aria-invalid', 'true');
        input.classList.add('border-destructive', 'focus:ring-destructive');
        input.focus();
    }

    function detectStoredInternationalPhone(value) {
        const normalized = normalizePhoneDigits(value).trim();
        const compact = normalized.replace(/[^\d+]/g, '');

        if (!compact) return '';
        if (compact.startsWith('+')) return compact;
        if (compact.startsWith('00')) return `+${compact.replace(/^00+/, '')}`;

        if (window.intlTelInput?.getAllCountries) {
            const digits = compact.replace(/\D/g, '');
            const dialCodes = window.intlTelInput.getAllCountries()
                .map(country => country.dialCode)
                .filter(Boolean)
                .sort((a, b) => b.length - a.length);
            const matchedCode = dialCodes.find(code => digits.startsWith(code) && digits.length >= code.length + 6);
            if (matchedCode) return `+${digits}`;
        }

        return normalized;
    }

    function extractLocalPhoneNumber(input, iti) {
        const dialCode = getPhoneCountryCode(iti).replace('+', '');
        let value = normalizePhoneDigits(input.value).trim();

        if (value.startsWith('+') || value.startsWith('00')) {
            value = value.replace(/^00/, '+').replace(/[^\d+]/g, '');
            if (value.startsWith(`+${dialCode}`)) {
                return value.slice(dialCode.length + 1).replace(/\D/g, '');
            }
        }

        const digits = value.replace(/\D/g, '');
        return digits.startsWith(dialCode) ? digits.slice(dialCode.length) : digits;
    }

    function setPhoneFieldValue(input, value) {
        const iti = intlPhoneInstances.get(input);
        const normalized = detectStoredInternationalPhone(value);

        clearPhoneError(input);

        if (!iti) {
            input.value = normalized;
            return;
        }

        if (!normalized) {
            input.value = '';
            if (typeof iti.setSelectedCountry === 'function') iti.setSelectedCountry('ye');
            else if (typeof iti.setCountry === 'function') iti.setCountry('ye');
            syncPhoneCountryCode(input);
            return;
        }

        if (normalized.startsWith('+')) {
            iti.setNumber(normalized);
        } else {
            input.value = normalized;
        }

        syncPhoneCountryCode(input);
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    async function validateAndPreparePhone(form) {
        const phoneInput = form.querySelector('.intl-phone-input[name="phone"]');
        if (!phoneInput) return true;

        const iti = intlPhoneInstances.get(phoneInput);
        if (!iti) {
            showPhoneError(phoneInput, "{{ __('messages.booking_phone_init_failed') }}");
            return false;
        }

        try {
            if (iti.promise) await iti.promise;
        } catch (error) {
            showPhoneError(phoneInput, "{{ __('messages.booking_phone_verify_failed') }}");
            return false;
        }

        syncPhoneCountryCode(phoneInput);

        if (!phoneInput.value.trim()) {
            showPhoneError(phoneInput, "{{ __('messages.booking_phone_required') }}");
            return false;
        }

        if (!iti.isValidNumber()) {
            showPhoneError(phoneInput);
            return false;
        }

        phoneInput.value = extractLocalPhoneNumber(phoneInput, iti);
        return true;
    }

    function initIntlPhoneInputs() {
        if (!window.intlTelInput) return;

        document.querySelectorAll('.intl-phone-input[name="phone"]').forEach(input => {
            if (input.dataset.intlPhoneReady === '1') return;

            const iti = window.intlTelInput(input, {
                initialCountry: 'ye',
                separateDialCode: true,
                showFlags: true,
                countrySearch: true,
                strictMode: true,
                formatAsYouType: true,
                numberDisplayFormat: window.intlTelInput.NUMBER_FORMAT?.NATIONAL || 'NATIONAL',
                countryNameLocale: 'ar',
                    uiTranslations: {
                    searchPlaceholder: "{{ __('messages.booking_country_search_placeholder') }}",
                    noResultsFound: "{{ __('messages.booking_no_results_found') }}",
                },
            });

            input.dataset.intlPhoneReady = '1';
            intlPhoneInstances.set(input, iti);
            syncPhoneCountryCode(input);

            const syncAndClear = () => {
                syncPhoneCountryCode(input);
                clearPhoneError(input);
            };

            input.addEventListener('countrychange', syncAndClear);
            input.addEventListener('input', syncAndClear);
            input.addEventListener('blur', () => syncPhoneCountryCode(input));
        });

        document.querySelectorAll('form').forEach(form => {
            if (form.dataset.intlPhoneSubmitReady === '1') return;
            if (!form.querySelector('.intl-phone-input[name="phone"]')) return;

            form.dataset.intlPhoneSubmitReady = '1';
            form.addEventListener('submit', async event => {
                if (form.dataset.intlPhoneSubmitApproved === '1') {
                    delete form.dataset.intlPhoneSubmitApproved;
                    return;
                }

                event.preventDefault();

                if (await validateAndPreparePhone(form)) {
                    form.dataset.intlPhoneSubmitApproved = '1';
                    if (form.requestSubmit) {
                        form.requestSubmit(event.submitter || undefined);
                    } else {
                        HTMLFormElement.prototype.submit.call(form);
                    }
                }
            });
        });
    }

    function flagImageUrl(countryCode, width = 40) {
        return countryCode ? `/flags/${countryCode.toLowerCase()}.svg` : '';
    }

    function portOptionLabel(port) {
        const code = port.code ? ` - ${port.code}` : '';
        return `${port.name}${code}`;
    }

    const CUSTOM_PORT_VALUE = '__custom_port__';

    function getPortControls(portSelect) {
        const wrapper = portSelect.closest('.space-y-2');
        return {
            valueInput: wrapper?.querySelector('[data-port-value]') || null,
            customInput: wrapper?.querySelector('[data-custom-port-input]') || null,
            customHint: wrapper?.querySelector('[data-custom-port-hint]') || null,
        };
    }

    function syncPortValue(portSelect) {
        const { valueInput, customInput, customHint } = getPortControls(portSelect);
        const panel = portSelect.closest('[data-route-panel]');
        const panelInactive = panel && panel.classList.contains('hidden');
        const isCustom = portSelect.value === CUSTOM_PORT_VALUE;

        if (valueInput) {
            valueInput.disabled = panelInactive;
            valueInput.value = isCustom ? (customInput?.value || '').trim() : portSelect.value;
        }

        if (customInput) {
            customInput.disabled = panelInactive || !isCustom;
            customInput.required = !panelInactive && isCustom;
            customInput.classList.toggle('hidden', !isCustom);
        }

        if (customHint) {
            customHint.classList.toggle('hidden', !isCustom);
        }
    }

    function countryOptionText(option) {
        return (option?.textContent || '').trim();
    }

    function syncCountrySearchInput(select) {
        const input = countrySearchInputs.get(select);
        if (!input) return;

        const selected = select.options[select.selectedIndex];
        input.value = select.value ? countryOptionText(selected) : '';
        input.disabled = select.disabled;
        input.required = select.dataset.wasRequired === '1' && !select.disabled;
    }

    function ensureCountryOption(select, countryName) {
        const value = String(countryName || '').trim();
        if (!value) return;

        const exists = Array.from(select.options).some(option => option.value === value);
        if (exists) return;

        const option = document.createElement('option');
        option.value = value;
        option.textContent = value;
        select.appendChild(option);
    }

    function setCountrySelectValue(selectId, countryName) {
        const select = document.getElementById(selectId);
        if (!select) return;

        const previousValue = select.value;
        ensureCountryOption(select, countryName);
        select.value = countryName || '';
        syncCountrySearchInput(select);
        if (previousValue !== select.value) {
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function normalizeAirportCode(value) {
        return String(value || '').trim().toUpperCase().replace(/\s+/g, '');
    }

    function findManagedAirport(code) {
        const normalized = normalizeAirportCode(code);
        if (!normalized) return null;

        return managedAirports.find(airport => String(airport.iata_code || airport.code || '').toUpperCase() === normalized) || null;
    }

    function airportOptionLabel(airport) {
        const code = normalizeAirportCode(airport.iata_code || airport.code);
        const city = airport.city ? ` - ${airport.city}` : '';
        const name = airport.name ? ` - ${airport.name}` : '';

        return `${code}${city}${name}`;
    }

    function airportMatchesCountry(airport, countryName, countryCode) {
        const airportCountryCode = String(airport.country_code || '').toUpperCase();
        const selectedCountryCode = String(countryCode || '').toUpperCase();

        if (selectedCountryCode && airportCountryCode) {
            return airportCountryCode === selectedCountryCode;
        }

        return String(airport.country || '').toLowerCase() === String(countryName || '').toLowerCase();
    }

    function getAirportFilterHint(select) {
        return select.closest('.space-y-2')?.querySelector('[data-airport-filter-hint]') || null;
    }

    function setAirportFilterHint(select, message) {
        const hint = getAirportFilterHint(select);
        if (hint) hint.textContent = message;
    }

    function clearAirportReferenceFields(input) {
        if (input.tagName === 'SELECT') {
            input.dataset.selectedAirport = '';
        }
        const side = input.dataset.airportSide;
        const airportName = document.getElementById(`${side}_airport_name_air`);
        if (airportName) airportName.value = '';
        syncAirportSearchInput(input);
    }

    function airportOptionText(option) {
        return (option?.textContent || '').trim();
    }

    function syncAirportSearchInput(select) {
        const input = airportSearchInputs.get(select);
        if (!input) return;

        const selected = select.options[select.selectedIndex];
        input.value = select.value ? airportOptionText(selected) : '';
        input.disabled = select.disabled;
        input.required = select.dataset.wasRequired === '1' && !select.disabled;
        input.placeholder = select.disabled ? airportMessages.chooseCountryFirst : airportMessages.searchPlaceholder;
    }

    function initSearchableAirportSelect(select) {
        if (select.dataset.searchableAirportReady === '1') return;
        select.dataset.searchableAirportReady = '1';
        select.dataset.wasRequired = select.required ? '1' : '0';
        select.required = false;

        const wrapper = document.createElement('div');
        wrapper.className = 'relative';

        const input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.dir = 'ltr';
        input.className = select.className;
        input.setAttribute('aria-label', airportMessages.searchPlaceholder);

        const list = document.createElement('div');
        list.className = 'hidden absolute z-50 mt-1 w-full overflow-y-auto overscroll-contain rounded-lg border border-border bg-card shadow-elegant';
        list.style.maxHeight = '18rem';
        list.style.setProperty('-webkit-overflow-scrolling', 'touch');
        list.style.setProperty('touch-action', 'pan-y');

        wrapper.appendChild(input);
        wrapper.appendChild(list);
        select.classList.add('hidden');
        select.insertAdjacentElement('afterend', wrapper);
        airportSearchInputs.set(select, input);

        function optionMatches(option, query) {
            const label = airportOptionText(option).toLowerCase();
            const value = String(option.value || '').toLowerCase();
            return label.includes(query) || value.includes(query);
        }

        function chooseOption(option) {
            select.value = option.value;
            syncAirportSearchInput(select);
            list.classList.add('hidden');
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function renderList(query = '') {
            if (select.disabled) return;

            const normalizedQuery = query.trim().toLowerCase();
            const options = Array.from(select.options)
                .filter(option => option.value)
                .filter(option => optionMatches(option, normalizedQuery));

            list.innerHTML = '';

            if (options.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'px-3 py-2 text-sm text-muted-foreground';
                empty.textContent = airportMessages.noResults;
                list.appendChild(empty);
                list.classList.remove('hidden');
                return;
            }

            options.forEach(option => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm transition hover:bg-accent/10';
                button.dir = 'ltr';

                const code = document.createElement('span');
                code.className = 'shrink-0 font-mono font-bold text-accent';
                code.textContent = option.value;

                const meta = document.createElement('span');
                meta.className = 'min-w-0 truncate text-muted-foreground';
                meta.textContent = airportOptionText(option).replace(new RegExp(`^${option.value}\\s*-?\\s*`), '');

                button.appendChild(code);
                button.appendChild(meta);
                button.addEventListener('mousedown', event => event.preventDefault());
                button.addEventListener('click', () => chooseOption(option));
                list.appendChild(button);
            });

            list.classList.remove('hidden');
        }

        input.addEventListener('focus', () => renderList(input.value));
        input.addEventListener('input', () => renderList(input.value));
        input.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                list.classList.add('hidden');
                input.blur();
                return;
            }

            if (event.key === 'Enter') {
                const query = input.value.trim().toLowerCase();
                const first = Array.from(select.options)
                    .filter(option => option.value)
                    .find(option => optionMatches(option, query));

                if (first) {
                    event.preventDefault();
                    chooseOption(first);
                }
            }
        });
        input.addEventListener('blur', () => {
            setTimeout(() => {
                const query = input.value.trim().toLowerCase();
                const exact = Array.from(select.options)
                    .filter(option => option.value)
                    .find(option => option.value.toLowerCase() === query || airportOptionText(option).toLowerCase() === query);

                if (exact) {
                    chooseOption(exact);
                } else if (!query) {
                    select.value = '';
                    select.dispatchEvent(new Event('change', { bubbles: true }));
                    syncAirportSearchInput(select);
                } else {
                    syncAirportSearchInput(select);
                }

                list.classList.add('hidden');
            }, 120);
        });

        select.addEventListener('change', () => syncAirportSearchInput(select));
        syncAirportSearchInput(select);
    }

    function renderAirportsForCountry(countrySelect) {
        const airportSelect = document.getElementById(countrySelect.dataset.airportTarget || '');
        if (!airportSelect) return;

        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        const selectedCountry = countrySelect.value;
        const selectedCountryCode = selectedOption?.dataset.countryCode || '';
        const countryKey = selectedCountryCode || selectedCountry;
        const previousAirport = normalizeAirportCode(airportSelect.value || airportSelect.dataset.selectedAirport);
        const hasRendered = Object.prototype.hasOwnProperty.call(airportSelect.dataset, 'renderedCountryKey');
        const countryChanged = hasRendered && airportSelect.dataset.renderedCountryKey !== countryKey;
        const airports = selectedCountry
            ? managedAirports.filter(airport => airportMatchesCountry(airport, selectedCountry, selectedCountryCode))
            : [];

        airportSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = selectedCountry ? airportMessages.chooseAirport : airportMessages.chooseCountryFirst;
        airportSelect.appendChild(placeholder);

        airports.forEach(airport => {
            const code = normalizeAirportCode(airport.iata_code || airport.code);
            if (!code) return;

            const option = document.createElement('option');
            option.value = code;
            option.textContent = airportOptionLabel(airport);
            option.dataset.city = airport.city || '';
            option.dataset.name = airport.name || '';
            option.dataset.country = airport.country || '';
            option.dataset.countryCode = airport.country_code || '';
            airportSelect.appendChild(option);
        });

        const hasPreviousAirport = previousAirport && airports.some(airport => normalizeAirportCode(airport.iata_code || airport.code) === previousAirport);

        if (hasPreviousAirport) {
            airportSelect.value = previousAirport;
        } else if (previousAirport && !countryChanged) {
            const savedOption = document.createElement('option');
            savedOption.value = previousAirport;
            savedOption.textContent = `${previousAirport} - ${airportMessages.savedCode}`;
            airportSelect.appendChild(savedOption);
            airportSelect.value = previousAirport;
        } else {
            airportSelect.value = '';
        }
        airportSelect.dataset.selectedAirport = airportSelect.value || '';

        airportSelect.disabled = !selectedCountry;
        airportSelect.dataset.renderedCountryKey = countryKey;

        if (!selectedCountry) {
            setAirportFilterHint(airportSelect, airportMessages.chooseCountryFirstHint);
        } else if (airports.length === 0) {
            placeholder.textContent = airportMessages.noAirportsForCountry;
            setAirportFilterHint(airportSelect, airportMessages.noAirportsForCountry);
        } else {
            setAirportFilterHint(airportSelect, airportMessages.filteredHint.replace(':country', selectedCountry));
        }

        if (airportSelect.value) {
            fillAirportReferenceFields(airportSelect);
        } else {
            clearAirportReferenceFields(airportSelect);
        }

        syncAirportSearchInput(airportSelect);
    }

    function fillAirportReferenceFields(input) {
        const code = normalizeAirportCode(input.value);
        if (input.tagName !== 'SELECT') {
            input.value = code;
        } else {
            input.dataset.selectedAirport = code;
        }

        const airport = findManagedAirport(code);
        if (!airport) {
            syncAirportSearchInput(input);
            return;
        }

        const side = input.dataset.airportSide;
        const airportName = document.getElementById(`${side}_airport_name_air`);
        const city = document.getElementById(`${side}_city_air`);
        const country = document.getElementById(`${side}_country_air`);

        if (airportName) airportName.value = airport.name || '';
        if (city && airport.city) city.value = airport.city;
        if (country && airport.country) {
            setCountrySelectValue(country.id, airport.country);
            const hiddenCountryCode = document.querySelector(`[data-country-code-for="${country.id}"]`);
            if (hiddenCountryCode && airport.country_code) hiddenCountryCode.value = airport.country_code;
        }

        syncAirportSearchInput(input);
    }

    function initAirportReferenceInputs() {
        document.querySelectorAll('[data-airport-code-input]').forEach(input => {
            if (input.dataset.airportReferenceReady === '1') return;
            input.dataset.airportReferenceReady = '1';
            if (input.tagName === 'SELECT') {
                initSearchableAirportSelect(input);
            } else {
                input.addEventListener('input', () => {
                    input.value = String(input.value || '').toUpperCase();
                });
            }
            input.addEventListener('change', () => fillAirportReferenceFields(input));
            input.addEventListener('blur', () => fillAirportReferenceFields(input));
            if (input.value) fillAirportReferenceFields(input);
        });
    }

    function setPortSelectValue(selectId, portName) {
        const portSelect = document.getElementById(selectId);
        if (!portSelect) return;

        const value = String(portName || '').trim();
        const { valueInput, customInput } = getPortControls(portSelect);
        const hasOption = Array.from(portSelect.options).some(option => option.value === value);

        if (value && hasOption) {
            portSelect.value = value;
        } else if (value) {
            portSelect.value = CUSTOM_PORT_VALUE;
            if (customInput) customInput.value = value;
        } else {
            portSelect.value = '';
        }

        if (valueInput) valueInput.value = value;
        syncPortValue(portSelect);
    }

    function syncCountryCodeHidden(countrySelect, countryCode = null) {
        const hiddenInput = document.querySelector(`[data-country-code-for="${countrySelect.id}"]`);
        if (!hiddenInput) return;

        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        hiddenInput.value = countryCode ?? selectedOption?.dataset.countryCode ?? '';
    }

    function initSearchableCountrySelect(select) {
        if (select.dataset.searchableCountryReady === '1') return;
        select.dataset.searchableCountryReady = '1';
        select.dataset.wasRequired = select.required ? '1' : '0';
        select.required = false;

        const wrapper = document.createElement('div');
        wrapper.className = 'relative';

        const input = document.createElement('input');
        input.type = 'text';
        input.autocomplete = 'off';
        input.placeholder = select.options[0]?.textContent?.trim() || "{{ __('messages.booking_choose_country') }}";
        input.className = select.className;
        input.setAttribute('aria-label', input.placeholder);

        const list = document.createElement('div');
        list.className = 'hidden absolute z-50 mt-1 w-full overflow-y-auto overscroll-contain rounded-lg border border-border bg-card shadow-elegant';
        list.style.maxHeight = '22.5rem';
        list.style.setProperty('-webkit-overflow-scrolling', 'touch');
        list.style.setProperty('touch-action', 'pan-y');

        wrapper.appendChild(input);
        wrapper.appendChild(list);
        select.classList.add('hidden');
        select.insertAdjacentElement('afterend', wrapper);
        countrySearchInputs.set(select, input);

        function optionMatches(option, query) {
            const label = countryOptionText(option).toLowerCase();
            const value = String(option.value || '').toLowerCase();
            return label.includes(query) || value.includes(query);
        }

        function dispatchCountryChange(syncInput = true) {
            if (!syncInput) {
                select.dataset.skipSearchInputSync = '1';
            }

            select.dispatchEvent(new Event('change', { bubbles: true }));

            if (!syncInput) {
                delete select.dataset.skipSearchInputSync;
            }
        }

        function chooseOption(option) {
            select.value = option.value;
            syncCountrySearchInput(select);
            list.classList.add('hidden');
            dispatchCountryChange();
        }

        function renderList(query = '') {
            const normalizedQuery = query.trim().toLowerCase();
            const options = Array.from(select.options)
                .filter(option => option.value)
                .filter(option => !normalizedQuery || optionMatches(option, normalizedQuery));

            list.innerHTML = '';

            if (options.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'px-3 py-2 text-xs text-muted-foreground';
                empty.textContent = "{{ __('messages.booking_no_matching_country') }}";
                list.appendChild(empty);
            }

            options.forEach(option => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'flex h-9 w-full items-center gap-2 px-3 text-right text-sm hover:bg-secondary/60';

                const countryCode = option.dataset.countryCode || '';
                if (countryCode) {
                    const flag = document.createElement('img');
                    flag.src = flagImageUrl(countryCode, 24);
                    flag.alt = '';
                    flag.className = 'h-4 w-6 shrink-0 rounded-[2px] border border-border object-cover';
                    flag.loading = 'lazy';
                    flag.addEventListener('error', () => flag.remove());
                    button.appendChild(flag);
                }

                const label = document.createElement('span');
                label.className = 'min-w-0 flex-1 truncate';
                label.textContent = countryOptionText(option);
                button.appendChild(label);

                button.addEventListener('mousedown', event => {
                    event.preventDefault();
                    chooseOption(option);
                });
                list.appendChild(button);
            });

            list.classList.remove('hidden');
        }

        list.addEventListener('wheel', event => {
            const canScroll = list.scrollHeight > list.clientHeight;
            if (!canScroll) return;

            const atTop = list.scrollTop <= 0;
            const atBottom = Math.ceil(list.scrollTop + list.clientHeight) >= list.scrollHeight;

            if ((event.deltaY < 0 && atTop) || (event.deltaY > 0 && atBottom)) {
                return;
            }

            event.stopPropagation();
        }, { passive: true });

        input.addEventListener('focus', () => renderList(input.value));
        input.addEventListener('input', () => {
            const query = input.value.trim().toLowerCase();
            const selected = select.options[select.selectedIndex];
            const selectedLabel = select.value && selected ? countryOptionText(selected).toLowerCase() : '';

            if (!query || query !== selectedLabel) {
                select.value = '';
                dispatchCountryChange(false);
            }

            renderList(input.value);
        });

        input.addEventListener('blur', () => {
            setTimeout(() => {
                const query = input.value.trim().toLowerCase();
                const options = Array.from(select.options).filter(option => option.value);
                const exact = options.find(option => countryOptionText(option).toLowerCase() === query || option.value.toLowerCase() === query);

                if (exact) {
                    select.value = exact.value;
                    dispatchCountryChange();
                    syncCountrySearchInput(select);
                } else if (!query) {
                    select.value = '';
                    dispatchCountryChange();
                    syncCountrySearchInput(select);
                }

                list.classList.add('hidden');
            }, 120);
        });

        select.addEventListener('change', () => {
            if (select.dataset.skipSearchInputSync === '1') return;
            syncCountrySearchInput(select);
        });
        syncCountrySearchInput(select);
    }

    function updateCountryFlagPreview(countrySelect) {
        const selectedOption = countrySelect.options[countrySelect.selectedIndex];
        const countryCode = selectedOption?.dataset.countryCode || '';
        syncCountryCodeHidden(countrySelect, countryCode);
        const preview = countrySelect.closest('.space-y-2')?.querySelector('[data-country-flag-preview]');
        if (!preview) return;
        const image = preview.querySelector('img');
        const text = preview.querySelector('span');

        if (!countryCode) {
            preview.classList.add('hidden');
            preview.classList.remove('flex');
            image?.removeAttribute('src');
            if (text) text.textContent = '';
            return;
        }

        if (image) {
            image.src = flagImageUrl(countryCode, 40);
            image.alt = `${countrySelect.value} flag`;
            image.onerror = () => {
                preview.classList.add('hidden');
                preview.classList.remove('flex');
            };
        }
        if (text) text.textContent = countrySelect.value;
        preview.classList.remove('hidden');
        preview.classList.add('flex');
    }

    function normalizeCountryName(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/\s*[-–—]\s*.*/g, '')
            .replace(/\(.*?\)/g, '')
            .replace(/[^a-z0-9\s]/g, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function countryNamesMatch(a, b) {
        const left = normalizeCountryName(a);
        const right = normalizeCountryName(b);

        if (!left || !right) return false;
        if (left === right) return true;
        if (left.includes(right) || right.includes(left)) return true;

        const leftParts = left.split(' ');
        const rightParts = right.split(' ');
        return leftParts.some(part => part && rightParts.includes(part));
    }

    function renderPortsForCountry(countrySelect) {
        updateCountryFlagPreview(countrySelect);

        const portSelect = document.getElementById(countrySelect.dataset.portTarget || '');
        if (!portSelect) return;

        const selectedCountry = countrySelect.value;
        const previousPort = portSelect.value;
        const { valueInput, customInput } = getPortControls(portSelect);
        const panel = portSelect.closest('[data-route-panel]');
        const panelInactive = panel && panel.classList.contains('hidden');
        const ports = managedPorts.filter(port => countryNamesMatch(port.country, selectedCountry));

        portSelect.innerHTML = '';

        const placeholder = document.createElement('option');
        placeholder.value = '';
        placeholder.textContent = selectedCountry ? "{{ __('messages.booking_choose_port') }}" : "{{ __('messages.booking_choose_country_first') }}";
        portSelect.appendChild(placeholder);

        ports.forEach(port => {
            const option = document.createElement('option');
            option.value = port.name;
            option.textContent = portOptionLabel(port);
            portSelect.appendChild(option);
        });

        if (selectedCountry) {
            const customOption = document.createElement('option');
            customOption.value = CUSTOM_PORT_VALUE;
            customOption.textContent = "{{ __('messages.booking_custom_port_manual') }}";
            portSelect.appendChild(customOption);
        }

        const validPreviousPort = ports.some(port => port.name === previousPort)
            ? previousPort
            : (previousPort === CUSTOM_PORT_VALUE ? CUSTOM_PORT_VALUE : '');

        if (validPreviousPort) {
            portSelect.value = validPreviousPort;
        } else if (valueInput?.value && !ports.some(port => port.name === valueInput.value)) {
            portSelect.value = CUSTOM_PORT_VALUE;
            if (customInput && !customInput.value) customInput.value = valueInput.value;
        } else {
            portSelect.value = '';
        }

        portSelect.disabled = panelInactive || !selectedCountry;

        if (selectedCountry && ports.length === 0) {
            placeholder.textContent = "{{ __('messages.booking_no_ports_for_country') }}";
        }

        syncPortValue(portSelect);
    }

    function refreshCountryPortSelectors() {
        document.querySelectorAll('[data-country-select]').forEach(select => {
            syncCountrySearchInput(select);
            renderPortsForCountry(select);
            renderAirportsForCountry(select);
        });
    }

    function initCountryPortSelectors() {
        document.querySelectorAll('[data-country-select]').forEach(select => {
            initSearchableCountrySelect(select);
            if (select.dataset.countryPortReady === '1') return;
            select.dataset.countryPortReady = '1';
            select.addEventListener('change', () => {
                renderPortsForCountry(select);
                renderAirportsForCountry(select);
            });
            renderPortsForCountry(select);
            renderAirportsForCountry(select);
        });

        document.querySelectorAll('[data-port-select]').forEach(select => {
            if (select.dataset.portSelectReady === '1') return;
            select.dataset.portSelectReady = '1';
            select.addEventListener('change', () => syncPortValue(select));
            const { customInput } = getPortControls(select);
            customInput?.addEventListener('input', () => syncPortValue(select));
            syncPortValue(select);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        initIntlPhoneInputs();
        initCountryPortSelectors();
        initAirportReferenceInputs();

        function renumberPackageRows(tbody) {
            tbody.querySelectorAll('[data-package-row]').forEach((row, index) => {
                row.querySelectorAll('input, select').forEach(input => {
                    input.name = input.name.replace(/packages\[\d+\]/, `packages[${index}]`);
                });
            });
        }

        document.querySelectorAll('[data-add-package-row]').forEach(button => {
            button.addEventListener('click', () => {
                const tbody = document.getElementById(button.dataset.target || '');
                const sourceRow = tbody?.querySelector('[data-package-row]');
                if (!tbody || !sourceRow) return;

                const clone = sourceRow.cloneNode(true);
                clone.querySelectorAll('input').forEach(input => {
                    input.value = '';
                });
                tbody.appendChild(clone);
                renumberPackageRows(tbody);
                window.lucide?.createIcons();
            });
        });

        function syncDangerousGoodsPanel(tab) {
            const cargoType = document.getElementById(`cargo_type_${tab}`);
            const dangerous = document.getElementById(`dangerous_${tab}`);
            const panel = document.querySelector(`[data-dangerous-goods-panel="${tab}"]`);
            if (!panel) return;

            const show = cargoType?.value === 'dangerous' || !!dangerous?.checked;
            panel.classList.toggle('hidden', !show);
        }
        window.syncDangerousGoodsPanel = syncDangerousGoodsPanel;

        ['air', 'door'].forEach(tab => {
            document.getElementById(`cargo_type_${tab}`)?.addEventListener('change', () => syncDangerousGoodsPanel(tab));
            document.getElementById(`dangerous_${tab}`)?.addEventListener('change', () => syncDangerousGoodsPanel(tab));
            syncDangerousGoodsPanel(tab);
        });

        const typeSelect = document.getElementById('shipmentType');
        const fclOptions = document.getElementById('fcl-options');
        const lclOptions = document.getElementById('lcl-options');
        const bulkOptions = document.getElementById('bulk-options');
        const roroOptions = document.getElementById('roro-options');
        const flatContainerOptions = document.getElementById('flat-container-options');
        const weightKgInput = document.getElementById('weightKg');
        const volumeCbmInput = document.getElementById('volumeCbm');
        const lclLengthInput = document.getElementById('lcl_length_m');
        const lclWidthInput = document.getElementById('lcl_width_m');
        const lclHeightInput = document.getElementById('lcl_height_m');
        const bulkLengthInput = document.getElementById('bulk_length');
        const bulkWidthInput = document.getElementById('bulk_width');
        const bulkHeightInput = document.getElementById('bulk_height');
        const bulkCbmInput = document.getElementById('bulk_cbm');
        const bulkPiecesInput = document.getElementById('bulk_pieces');
        const bulkPieceWeightInput = document.getElementById('bulk_piece_weight');
        const bulkTotalWeightInput = document.getElementById('bulk_total_weight');
        const flatLengthInput = document.getElementById('flat_length');
        const flatWidthInput = document.getElementById('flat_width');
        const flatHeightInput = document.getElementById('flat_height');
        const flatCbmInput = document.getElementById('flat_cbm');
        const flatPiecesInput = document.getElementById('flat_pieces');
        const flatPieceWeightInput = document.getElementById('flat_piece_weight');
        const flatTotalWeightInput = document.getElementById('flat_total_weight');
        const containerTypeInput = document.getElementById('containerType');
        const containerQtyInput = document.getElementById('containerQty');
        const fclContainerRows = document.getElementById('fcl-container-rows');
        const fclContainerTemplate = document.getElementById('fcl-container-row-template');
        const addFclContainerRowButton = document.getElementById('add-fcl-container-row');
        const reeferTemperatureWrapper = document.getElementById('reefer-temperature-wrapper');
        let fclContainerIndex = fclContainerRows?.querySelectorAll('.fcl-container-row').length || 0;

        function numberFromInput(input) {
            const value = parseFloat(input?.value || '');
            return Number.isFinite(value) ? value : 0;
        }

        function roundedCbmFromDimensions(lengthInput, widthInput, heightInput) {
            const length = numberFromInput(lengthInput);
            const width = numberFromInput(widthInput);
            const height = numberFromInput(heightInput);

            return length > 0 && width > 0 && height > 0
                ? Math.ceil(length * width * height).toString()
                : '';
        }

        function decimalCbmFromDimensions(lengthInput, widthInput, heightInput) {
            const length = numberFromInput(lengthInput);
            const width = numberFromInput(widthInput);
            const height = numberFromInput(heightInput);

            return length > 0 && width > 0 && height > 0
                ? (height * width * length).toFixed(1)
                : '';
        }

        function formatCalculatedWeight(value) {
            if (!Number.isFinite(value) || value <= 0) return '';

            return Number.isInteger(value) ? value.toString() : value.toFixed(2).replace(/0+$/, '').replace(/\.$/, '');
        }

        function calculateLclCbm() {
            if (!volumeCbmInput || typeSelect?.value !== 'LCL') return;

            volumeCbmInput.value = roundedCbmFromDimensions(lclLengthInput, lclWidthInput, lclHeightInput);
        }
        window.calculateLclCbm = calculateLclCbm;

        function calculateBulkCbm() {
            if (typeSelect?.value !== 'Bulk') return;

            const length = numberFromInput(bulkLengthInput);
            const width = numberFromInput(bulkWidthInput);
            const height = numberFromInput(bulkHeightInput);
            const pieces = Math.max(1, Math.floor(numberFromInput(bulkPiecesInput)));
            const rawPieceCbm = length > 0 && width > 0 && height > 0
                ? height * width * length
                : 0;
            const pieceCbm = rawPieceCbm > 0 ? rawPieceCbm.toFixed(1) : '';
            const totalCbm = rawPieceCbm > 0 ? (rawPieceCbm * pieces).toFixed(1) : '';
            if (bulkCbmInput) {
                bulkCbmInput.value = pieceCbm;
            }
            if (volumeCbmInput) {
                volumeCbmInput.value = totalCbm;
            }
        }
        window.calculateBulkCbm = calculateBulkCbm;

        function calculateBulkWeight() {
            if (typeSelect?.value !== 'Bulk') return;

            const pieces = Math.max(1, Math.floor(numberFromInput(bulkPiecesInput)));
            const pieceWeight = numberFromInput(bulkPieceWeightInput);
            const totalWeight = pieceWeight > 0 ? formatCalculatedWeight(pieceWeight * pieces) : '';

            if (bulkTotalWeightInput) {
                bulkTotalWeightInput.value = totalWeight;
            }
            if (weightKgInput) {
                weightKgInput.value = totalWeight;
            }
        }
        window.calculateBulkWeight = calculateBulkWeight;

        function calculateFlatContainer() {
            if (typeSelect?.value !== 'Flat Container') return;
            const pieces = Math.max(1, Math.floor(numberFromInput(flatPiecesInput)));
            const pieceWeight = numberFromInput(flatPieceWeightInput);
            const pieceCbm = numberFromInput(flatLengthInput) * numberFromInput(flatWidthInput) * numberFromInput(flatHeightInput);
            if (flatTotalWeightInput) flatTotalWeightInput.value = pieceWeight > 0 ? formatCalculatedWeight(pieceWeight * pieces) : '';
            if (flatCbmInput) flatCbmInput.value = pieceCbm > 0 ? (pieceCbm * pieces).toFixed(1) : '';
            if (weightKgInput) weightKgInput.value = flatTotalWeightInput?.value || '';
            if (volumeCbmInput) volumeCbmInput.value = flatCbmInput?.value || '';
        }

        function fclRows() {
            return Array.from(fclContainerRows?.querySelectorAll('.fcl-container-row') || []);
        }

        function syncFclContainers() {
            const rows = fclRows();
            const firstRow = rows[0];
            const firstType = firstRow?.querySelector('[data-fcl-container-type]')?.value || '';
            const firstQty = firstRow?.querySelector('[data-fcl-container-qty]')?.value || '1';

            if (containerTypeInput) {
                containerTypeInput.value = firstType;
            }
            if (containerQtyInput) {
                containerQtyInput.value = firstQty;
            }

            rows.forEach(row => {
                const removeButton = row.querySelector('[data-remove-fcl-container]');
                if (removeButton) {
                    removeButton.classList.toggle('invisible', rows.length === 1);
                }
            });
        }

        function bindFclContainerRow(row) {
            row.querySelector('[data-fcl-container-type]')?.addEventListener('change', () => {
                syncFclContainers();
                toggleCargoPanels(typeSelect?.value || 'FCL');
            });
            row.querySelector('[data-fcl-container-qty]')?.addEventListener('input', syncFclContainers);
            row.querySelector('[data-remove-fcl-container]')?.addEventListener('click', () => {
                if (fclRows().length <= 1) return;
                row.remove();
                syncFclContainers();
                toggleCargoPanels(typeSelect?.value || 'FCL');
            });
        }

        function addFclContainerRow(container = {}) {
            if (!fclContainerRows || !fclContainerTemplate) return;

            const index = (fclContainerIndex++).toString();
            const html = fclContainerTemplate.innerHTML.replaceAll('__INDEX__', index);
            fclContainerRows.insertAdjacentHTML('beforeend', html);

            const row = fclContainerRows.lastElementChild;
            if (!row) return;

            const typeSelect = row.querySelector('[data-fcl-container-type]');
            const qtyInput = row.querySelector('[data-fcl-container-qty]');
            if (typeSelect && container.type && Array.from(typeSelect.options).some(option => option.value === container.type)) {
                typeSelect.value = container.type;
            }
            if (qtyInput && container.qty) {
                qtyInput.value = container.qty;
            }

            bindFclContainerRow(row);
            syncFclContainers();
            toggleCargoPanels(document.getElementById('shipmentType')?.value || 'FCL');
            window.lucide?.createIcons();
        }

        function setFclContainerRows(containers = [], fallbackType = '', fallbackQty = 1) {
            if (!fclContainerRows || !fclContainerTemplate) return;

            const normalized = Array.isArray(containers) && containers.length
                ? containers
                : [{ type: fallbackType, qty: fallbackQty }];

            fclContainerRows.innerHTML = '';
            normalized.forEach(container => addFclContainerRow(container));
            syncFclContainers();
        }

        window.setFclContainerRows = setFclContainerRows;
        
        function toggleCargoPanels(selectedType) {
            if (fclOptions) {
                fclOptions.classList.toggle('hidden', selectedType !== 'FCL');
                fclRows().forEach(row => {
                    row.querySelectorAll('select, input').forEach(input => {
                        input.required = selectedType === 'FCL';
                    });
                });
            }
            if (lclOptions) {
                lclOptions.classList.toggle('hidden', selectedType !== 'LCL');
                lclOptions.querySelectorAll('input').forEach(input => {
                    input.disabled = selectedType !== 'LCL';
                    input.required = selectedType === 'LCL';
                });
            }
            if (bulkOptions) {
                bulkOptions.classList.toggle('hidden', selectedType !== 'Bulk');
                [bulkPiecesInput, bulkPieceWeightInput, bulkLengthInput, bulkWidthInput, bulkHeightInput].forEach(input => {
                    if (!input) return;
                    input.required = selectedType === 'Bulk';
                });
            }
            if (roroOptions) {
                roroOptions.classList.toggle('hidden', !['RoRo', 'Flat Container'].includes(selectedType));
            }
            const seaCargoItemsEditor = document.getElementById('sea-cargo-items-editor');
            if (seaCargoItemsEditor) {
                const itemizedCargo = selectedType === 'Bulk' || selectedType === 'RoRo';
                seaCargoItemsEditor.classList.toggle('hidden', !itemizedCargo);
                seaCargoItemsEditor.querySelectorAll('input, select').forEach(input => {
                    input.disabled = !itemizedCargo;
                });
            }
            if (flatContainerOptions) {
                flatContainerOptions.classList.add('hidden');
                flatContainerOptions.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
            }
            if (volumeCbmInput) {
                const calculatedVolume = selectedType === 'LCL' || selectedType === 'Bulk';
                volumeCbmInput.readOnly = calculatedVolume;
                volumeCbmInput.classList.toggle('bg-secondary/30', calculatedVolume);
            }
            if (weightKgInput) {
                weightKgInput.readOnly = selectedType === 'Bulk';
                weightKgInput.classList.toggle('bg-secondary/30', selectedType === 'Bulk');
            }
            if (reeferTemperatureWrapper) {
                const isReefer = fclRows().some(row => {
                    const value = row.querySelector('[data-fcl-container-type]')?.value;
                    return value === 'Reefer 20' || value === 'Reefer 40';
                });
                reeferTemperatureWrapper.classList.toggle('hidden', !isReefer || selectedType !== 'FCL');
            }

            calculateLclCbm();
            calculateBulkCbm();
            calculateBulkWeight();
            calculateFlatContainer();
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', (e) => {
                toggleCargoPanels(e.target.value);
            });
            toggleCargoPanels(typeSelect.value || 'FCL');
        }

        fclRows().forEach(bindFclContainerRow);
        syncFclContainers();
        addFclContainerRowButton?.addEventListener('click', () => addFclContainerRow());

        const landShipmentRows = document.getElementById('land-shipment-rows');
        const landShipmentTemplate = document.getElementById('land-shipment-row-template');
        const addLandShipmentRowButton = document.getElementById('add-land-shipment-row');
        let landShipmentIndex = landShipmentRows?.querySelectorAll('.land-shipment-row').length || 0;

        function landShipmentRowsList() {
            return Array.from(landShipmentRows?.querySelectorAll('.land-shipment-row') || []);
        }

        function syncLandShipments() {
            const rows = landShipmentRowsList();
            rows.forEach(row => {
                const removeButton = row.querySelector('[data-remove-land-shipment]');
                if (removeButton) {
                    removeButton.classList.toggle('invisible', rows.length === 1);
                }
            });
        }

        function bindLandShipmentRow(row) {
            row.querySelector('[data-land-shipment-qty]')?.addEventListener('input', syncLandShipments);
            row.querySelector('[data-land-shipment-from]')?.addEventListener('input', syncLandShipments);
            row.querySelector('[data-land-shipment-to]')?.addEventListener('input', syncLandShipments);
            row.querySelector('[data-remove-land-shipment]')?.addEventListener('click', () => {
                if (landShipmentRowsList().length <= 1) return;
                row.remove();
                syncLandShipments();
            });
        }

        function addLandShipmentRow(shipment = {}) {
            if (!landShipmentRows || !landShipmentTemplate) return;

            const index = (landShipmentIndex++).toString();
            const html = landShipmentTemplate.innerHTML.replaceAll('__INDEX__', index);
            landShipmentRows.insertAdjacentHTML('beforeend', html);

            const row = landShipmentRows.lastElementChild;
            if (!row) return;

            const qtyInput = row.querySelector('[data-land-shipment-qty]');
            const fromInput = row.querySelector('[data-land-shipment-from]');
            const toInput = row.querySelector('[data-land-shipment-to]');

            if (qtyInput && shipment.qty) qtyInput.value = shipment.qty;
            if (fromInput && shipment.from_city) fromInput.value = shipment.from_city;
            if (toInput && shipment.to_city) toInput.value = shipment.to_city;

            bindLandShipmentRow(row);
            syncLandShipments();
            window.lucide?.createIcons();
        }

        function setLandShipmentRows(shipments = [], fallbackFrom = '', fallbackTo = '', fallbackQty = 1) {
            if (!landShipmentRows || !landShipmentTemplate) return;

            const normalized = Array.isArray(shipments) && shipments.length
                ? shipments
                : [{ qty: fallbackQty, from_city: fallbackFrom, to_city: fallbackTo }];

            landShipmentRows.innerHTML = '';
            normalized.forEach(shipment => addLandShipmentRow(shipment));
            syncLandShipments();
        }

        window.setLandShipmentRows = setLandShipmentRows;
        landShipmentRowsList().forEach(bindLandShipmentRow);
        syncLandShipments();
        addLandShipmentRowButton?.addEventListener('click', () => addLandShipmentRow());

        [lclLengthInput, lclWidthInput, lclHeightInput].forEach(input => {
            input?.addEventListener('input', calculateLclCbm);
        });
        [bulkPiecesInput, bulkLengthInput, bulkWidthInput, bulkHeightInput].forEach(input => {
            input?.addEventListener('input', calculateBulkCbm);
        });
        [bulkPiecesInput, bulkPieceWeightInput].forEach(input => {
            input?.addEventListener('input', calculateBulkWeight);
        });
        const routeModeSelect = document.getElementById('route_mode');
        const routePanels = document.querySelectorAll('[data-route-panel]');

        function setRouteMode(mode) {
            routePanels.forEach(panel => {
                const isActive = panel.dataset.routePanel === mode;
                panel.classList.toggle('hidden', !isActive);
                panel.classList.toggle('grid', isActive);
                panel.querySelectorAll('input, select, textarea').forEach(field => {
                    field.disabled = !isActive;
                });
            });
            refreshCountryPortSelectors();
        }

        routeModeSelect?.addEventListener('change', (e) => setRouteMode(e.target.value));
        setRouteMode(routeModeSelect?.value || 'FOB');
        window.setRouteMode = setRouteMode;

        // Tabs logic
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');

        function setActiveTab(name) {
            tabContents.forEach(c => c.classList.add('hidden'));
            const target = document.getElementById('tab-' + name);
            if (target) target.classList.remove('hidden');
            tabButtons.forEach(b => {
                if (b.dataset.tab === name) {
                    b.classList.add('bg-white/20', 'text-accent', 'shadow-sm');
                    b.classList.remove('bg-white/10', 'text-white');
                } else {
                    b.classList.remove('bg-white/20', 'text-accent', 'shadow-sm');
                    b.classList.add('bg-white/10', 'text-white');
                }
            });
        }

        tabButtons.forEach(b => b.addEventListener('click', () => setActiveTab(b.dataset.tab)));
        // default to sea tab
        setActiveTab('sea');
        window.setActiveTab = setActiveTab;
    });

    const customersData = @json($customersData ?? []);

    function populateCustomerFields(tab) {
        const select = document.getElementById(`customer_id_${tab}`);
        const customerId = select ? select.value : null;
        const fields = {
            company: document.getElementById(`company${tab === 'sea' ? '' : `_${tab}`}`),
            contactName: document.getElementById(`contactName${tab === 'sea' ? '' : `_${tab}`}`),
            phone: document.getElementById(`phone${tab === 'sea' ? '' : `_${tab}`}`),
            email: document.getElementById(`email${tab === 'sea' ? '' : `_${tab}`}`),
        };

        if (!customerId) {
            Object.values(fields).forEach(field => {
                if (field) field.value = '';
            });
            if (fields.phone) setPhoneFieldValue(fields.phone, '');
            return;
        }

        const customer = customersData.find(c => String(c.id) === String(customerId));
        if (!customer) return;

        if (fields.company) fields.company.value = customer.company || '';
        if (fields.contactName) fields.contactName.value = customer.contact_name || '';
        if (fields.phone) setPhoneFieldValue(fields.phone, customer.phone || '');
        if (fields.email) fields.email.value = customer.email || '';
    }

    // --- Agent forwarding logic ---
    (function () {
        const modal = document.getElementById('agent-forward-modal');
        if (!modal) return;

        const listEl = document.getElementById('agents-list');
        const btnLocal = document.getElementById('agent-type-local');
        const btnExternal = document.getElementById('agent-type-external');
        const btnCancel = document.getElementById('agent-forward-cancel');
        const btnWhats = document.getElementById('agent-forward-whatsapp');
        const btnEmail = document.getElementById('agent-forward-email');
        const messageEl = document.getElementById('agent-forward-message');

        let selectedAgentId = null;

        function openModal() {
            modal.classList.remove('hidden');
            modal.style.display = 'flex';
            fetchAgents('local');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.style.display = 'none';
            selectedAgentId = null;
            listEl.innerHTML = '';
            messageEl.value = '';
        }

        function fetchAgents(type) {
            listEl.innerHTML = `<div class="text-sm text-muted-foreground">{{ __('messages.agent_loading') }}</div>`;
            fetch(`{{ route('ajax.agents') }}?type=${type}`)
                .then(r => r.json())
                .then(data => {
                    listEl.innerHTML = '';
                    data.forEach(a => {
                        const item = document.createElement('div');
                        item.className = 'p-2 border rounded flex items-center justify-between cursor-pointer';
                        item.innerHTML = `<div><div class="font-bold">${a.name}</div><div class="text-xs">${a.email || ''} ${a.phone ? ' - ' + a.phone : ''}</div></div>` +
                            `<input name="agent_id" type="radio" value="${a.id}" />`;
                        item.querySelector('input').addEventListener('change', () => selectedAgentId = a.id);
                        listEl.appendChild(item);
                    });
                    if (!data.length) listEl.innerHTML = `<div class="text-sm text-muted-foreground">{{ __('messages.agent_no_agents_for_type') }}</div>`;
                }).catch(() => {
                    listEl.innerHTML = `<div class="text-sm text-red-500">{{ __('messages.agent_error_fetch_agents') }}</div>`;
                });
        }

        btnLocal?.addEventListener('click', () => fetchAgents('local'));
        btnExternal?.addEventListener('click', () => fetchAgents('external'));
        btnCancel?.addEventListener('click', closeModal);

        function send(method) {
            if (!selectedAgentId) {
                alert("{{ __('messages.agent_choose_first') }}");
                return;
            }

            const payload = {
                booking_id: window.bookingId,
                agent_id: selectedAgentId,
                method: method,
                message: messageEl.value || ''
            };

            fetch(`{{ route('bookings.sendToAgent') }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(payload)
            }).then(r => r.json()).then(resp => {
                if (resp.status === 'ok') {
                    if (method === 'whatsapp' && resp.whatsapp_url) {
                        window.open(resp.whatsapp_url, '_blank');
                    }
                    if (method === 'email' && resp.mailto_url) {
                        window.location.href = resp.mailto_url;
                    }
                    closeModal();
                    // show pricing button (sales) after forwarding
                    document.querySelectorAll('.show-after-forward').forEach(el => el.classList.remove('hidden'));
                    document.querySelectorAll('.send-to-pricing').forEach(el => el.classList.add('hidden'));
                    alert("{{ __('messages.agent_sent_success') }}");
                } else {
                    alert(resp.message || "{{ __('messages.agent_error') }}");
                }
            }).catch(() => alert("{{ __('messages.agent_send_failed') }}"));
        }

        btnWhats?.addEventListener('click', () => send('whatsapp'));
        btnEmail?.addEventListener('click', () => send('email'));

        // If booking is in pricing stage, hide pricing buttons and show forward UI trigger
        if (window.bookingStatus === 'pricing') {
            document.querySelectorAll('.send-to-pricing').forEach(el => el.classList.add('hidden'));
            // create a floating button to open modal
            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.textContent = "{{ __('messages.agent_choose_agent') }}";
            trigger.className = 'fixed bottom-6 left-6 z-40 inline-flex h-11 items-center justify-center rounded-lg bg-amber-500 px-4 text-sm font-bold text-white shadow';
            trigger.addEventListener('click', openModal);
            document.body.appendChild(trigger);
        }
    })();

    @isset($booking)
    document.addEventListener('DOMContentLoaded', () => {
        const booking = @json($booking);
        
        // Pre-fill fields
        const companyInput = document.getElementById('company');
        if (companyInput) companyInput.value = booking.customer_company || '';
        
        const contactNameInput = document.getElementById('contactName');
        if (contactNameInput) contactNameInput.value = booking.customer_contact_name || '';
        
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.value = booking.customer_phone || '';
            // trigger custom validation and prefix checks if present
            setTimeout(() => {
                if (typeof setPhoneFieldValue === 'function') {
                    setPhoneFieldValue(phoneInput, booking.customer_phone || '');
                }
            }, 100);
        }
        
        const emailInput = document.getElementById('email');
        if (emailInput) emailInput.value = booking.customer_email || '';
        
        const goodsDescInput = document.getElementById('goodsDescription');
        if (goodsDescInput) goodsDescInput.value = booking.goods_description || '';
        
        const weightInput = document.getElementById('weightKg');
        if (weightInput) weightInput.value = booking.weight_kg || '';
        
        const volumeInput = document.getElementById('volumeCbm');
        if (volumeInput) volumeInput.value = booking.volume_cbm || '';
        
        const notesInput = document.getElementById('notes');
        if (notesInput) notesInput.value = booking.notes || '';
        
        const docsInput = document.getElementById('documents');
        if (docsInput) docsInput.value = booking.documents || '';
        
        const polInput = document.getElementById('pol');
        if (polInput) polInput.value = booking.pol || '';
        
        const podInput = document.getElementById('pod');
        if (podInput) podInput.value = booking.pod || '';

        // Selects
        if (booking.customer_id) {
            const customerSelect = document.getElementById('customer_id_sea');
            if (customerSelect) customerSelect.value = booking.customer_id;
        }

        const shipmentTypeSelect = document.getElementById('shipmentType');
        if (shipmentTypeSelect) {
            shipmentTypeSelect.value = booking.shipment_type;
            shipmentTypeSelect.dispatchEvent(new Event('change'));
        }

        if (booking.container_type) {
            const containerTypeSelect = document.getElementById('containerType');
            if (containerTypeSelect) {
                containerTypeSelect.value = booking.container_type;
                containerTypeSelect.dispatchEvent(new Event('change'));
            }
        }

        if (booking.container_qty) {
            const containerQtyInput = document.getElementById('containerQty');
            if (containerQtyInput) containerQtyInput.value = booking.container_qty;
        }

        const cargoDetails = booking.cargo_details || {};
        const routeDetails = booking.route_details || {};

        if (typeof window.setFclContainerRows === 'function') {
            window.setFclContainerRows(cargoDetails.containers || [], booking.container_type || '', booking.container_qty || 1);
        }

        const landRouteFrom = routeDetails?.origin?.city || booking.pol || '';
        const landRouteTo = routeDetails?.destination?.city || booking.pod || '';
        if (typeof window.setLandShipmentRows === 'function') {
            window.setLandShipmentRows(cargoDetails.land_shipments || [], landRouteFrom, landRouteTo, 1);
        }

        const bulkCargoNameInput = document.getElementById('bulk_cargo_name');
        if (bulkCargoNameInput) bulkCargoNameInput.value = cargoDetails.cargo_name || '';

        const bulkPiecesInput = document.getElementById('bulk_pieces');
        if (bulkPiecesInput) bulkPiecesInput.value = cargoDetails.pieces || '';

        const bulkPieceWeightInput = document.getElementById('bulk_piece_weight');
        if (bulkPieceWeightInput) bulkPieceWeightInput.value = cargoDetails.piece_weight || '';

        const bulkTotalWeightInput = document.getElementById('bulk_total_weight');
        if (bulkTotalWeightInput) bulkTotalWeightInput.value = cargoDetails.total_weight || '';

        const bulkLengthInput = document.getElementById('bulk_length');
        if (bulkLengthInput) bulkLengthInput.value = cargoDetails.length || '';

        const bulkWidthInput = document.getElementById('bulk_width');
        if (bulkWidthInput) bulkWidthInput.value = cargoDetails.width || '';

        const bulkHeightInput = document.getElementById('bulk_height');
        if (bulkHeightInput) bulkHeightInput.value = cargoDetails.height || '';

        const bulkCbmInput = document.getElementById('bulk_cbm');
        if (bulkCbmInput) bulkCbmInput.value = cargoDetails.piece_cbm || cargoDetails.cbm || '';

        if (typeof window.calculateBulkCbm === 'function') {
            window.calculateBulkCbm();
        }
        if (typeof window.calculateBulkWeight === 'function') {
            window.calculateBulkWeight();
        }

        const bulkPackagingSelect = document.getElementById('bulk_packaging');
        if (bulkPackagingSelect) bulkPackagingSelect.value = cargoDetails.packaging_type || '';

        const bulkLiftingSelect = document.getElementById('bulk_lifting');
        if (bulkLiftingSelect) bulkLiftingSelect.value = cargoDetails.lifting_method || '';

        const bulkStackableCheckbox = document.getElementById('bulk_stackable');
        if (bulkStackableCheckbox) bulkStackableCheckbox.checked = !!cargoDetails.stackable;

        const roroVehicleTypeInput = document.getElementById('roro_vehicle_type');
        if (roroVehicleTypeInput) roroVehicleTypeInput.value = cargoDetails.vehicle_type || '';

        const roroManufacturerInput = document.getElementById('roro_manufacturer');
        if (roroManufacturerInput) roroManufacturerInput.value = cargoDetails.manufacturer || '';

        const roroModelInput = document.getElementById('roro_model');
        if (roroModelInput) roroModelInput.value = cargoDetails.model || '';

        const roroYearInput = document.getElementById('roro_year');
        if (roroYearInput) roroYearInput.value = cargoDetails.year || '';

        const roroColorInput = document.getElementById('roro_color');
        if (roroColorInput) roroColorInput.value = cargoDetails.color || '';

        const roroVinInput = document.getElementById('roro_vin');
        if (roroVinInput) roroVinInput.value = cargoDetails.vin || '';

        const roroChassisInput = document.getElementById('roro_chassis_number');
        if (roroChassisInput) roroChassisInput.value = cargoDetails.chassis_number || '';

        const roroEngineInput = document.getElementById('roro_engine_number');
        if (roroEngineInput) roroEngineInput.value = cargoDetails.engine_number || '';

        const roroPlateInput = document.getElementById('roro_plate_number');
        if (roroPlateInput) roroPlateInput.value = cargoDetails.plate_number || '';

        const roroLengthInput = document.getElementById('roro_length');
        if (roroLengthInput) roroLengthInput.value = cargoDetails.length || '';

        const roroWidthInput = document.getElementById('roro_width');
        if (roroWidthInput) roroWidthInput.value = cargoDetails.width || '';

        const roroHeightInput = document.getElementById('roro_height');
        if (roroHeightInput) roroHeightInput.value = cargoDetails.height || '';

        const roroWeightInput = document.getElementById('roro_weight');
        if (roroWeightInput) roroWeightInput.value = cargoDetails.weight || '';

        const roroFuelLevelSelect = document.getElementById('roro_fuel_level');
        if (roroFuelLevelSelect) roroFuelLevelSelect.value = cargoDetails.fuel_level || '';

        const roroRunningCheckbox = document.getElementById('roro_running');
        if (roroRunningCheckbox) roroRunningCheckbox.checked = !!cargoDetails.running;

        const roroTowRequiredCheckbox = document.getElementById('roro_tow_required');
        if (roroTowRequiredCheckbox) roroTowRequiredCheckbox.checked = !!cargoDetails.tow_required;

        const roroHasKeyCheckbox = document.getElementById('roro_has_key');
        if (roroHasKeyCheckbox) roroHasKeyCheckbox.checked = !!cargoDetails.has_key;

        const roroHasItemsCheckbox = document.getElementById('roro_has_items');
        if (roroHasItemsCheckbox) roroHasItemsCheckbox.checked = !!cargoDetails.has_items;

        const reeferTemperatureInput = document.getElementById('reefer_temperature');
        if (reeferTemperatureInput) reeferTemperatureInput.value = cargoDetails.reefer_temperature || '';

        if (routeDetails) {
            const routeMode = routeDetails.mode || 'FOB';
            const routeModeSelect = document.getElementById('route_mode');
            if (routeModeSelect) {
                routeModeSelect.value = routeMode;
                routeModeSelect.dispatchEvent(new Event('change'));
            }

            if (routeMode === 'EXW') {
                setCountrySelectValue('exw_origin_country', routeDetails.origin?.country || '');
                const originCityInput = document.getElementById('exw_origin_city');
                if (originCityInput) originCityInput.value = routeDetails.origin?.city || '';

                setCountrySelectValue('exw_destination_country', routeDetails.destination?.country || '');
                const destCityInput = document.getElementById('exw_destination_city');
                if (destCityInput) destCityInput.value = routeDetails.destination?.city || '';

                setPortSelectValue('exw_pol', routeDetails.origin?.port || booking.pol || '');
                setPortSelectValue('exw_pod', routeDetails.destination?.port || booking.pod || '');
            } else {
                setCountrySelectValue('fob_origin_country', routeDetails.origin?.country || '');
                setCountrySelectValue('fob_destination_country', routeDetails.destination?.country || '');
                setPortSelectValue('fob_pol', routeDetails.origin?.port || booking.pol || '');
                setPortSelectValue('fob_pod', routeDetails.destination?.port || booking.pod || '');
            }
        }

        // Checkbox dangerous
        const dangerousCheckbox = document.getElementById('dangerous');
        if (dangerousCheckbox) {
            dangerousCheckbox.checked = !!booking.dangerous;
        }

        // Land freight pre-filling
        const companyLand = document.getElementById('company_land');
        if (companyLand) {
            companyLand.value = booking.customer_company || '';
            
            const contactNameLand = document.getElementById('contactName_land');
            if (contactNameLand) contactNameLand.value = booking.customer_contact_name || '';
            
            const phoneLand = document.getElementById('phone_land');
            if (phoneLand) {
                phoneLand.value = booking.customer_phone || '';
                setTimeout(() => {
                    if (typeof setPhoneFieldValue === 'function') {
                        setPhoneFieldValue(phoneLand, booking.customer_phone || '');
                    }
                }, 100);
            }
            
            const emailLand = document.getElementById('email_land');
            if (emailLand) emailLand.value = booking.customer_email || '';
            
            const goodsDescLand = document.getElementById('goodsDescription_land');
            if (goodsDescLand) goodsDescLand.value = booking.goods_description || '';
            
            const weightLand = document.getElementById('weightKg_land');
            if (weightLand) weightLand.value = booking.weight_kg || '';
            
            const volumeLand = document.getElementById('volumeCbm_land');
            if (volumeLand) volumeLand.value = booking.volume_cbm || '';

            const landCartonCount = document.getElementById('landCartonCount_land');
            if (landCartonCount) landCartonCount.value = cargoDetails.land_carton_count || '';
            
            const notesLand = document.getElementById('notes_land');
            if (notesLand) notesLand.value = booking.notes || '';
            
            const docsLand = document.getElementById('documents_land');
            if (docsLand) docsLand.value = booking.documents || '';
            
            const polLand = document.getElementById('pol_land');
            if (polLand) polLand.value = booking.pol || '';
            
            const podLand = document.getElementById('pod_land');
            if (podLand) podLand.value = booking.pod || '';

            if (booking.customer_id) {
                const customerSelectLand = document.getElementById('customer_id_land');
                if (customerSelectLand) customerSelectLand.value = booking.customer_id;
            }

            if (routeDetails) {
                const routeModeSelectLand = document.getElementById('route_mode_land');
                if (routeModeSelectLand) {
                    routeModeSelectLand.value = routeDetails.mode || 'FOB';
                    routeModeSelectLand.dispatchEvent(new Event('change'));
                }

                if (routeDetails.origin) {
                    const originCountryLand = document.getElementById('origin_country_land');
                    if (originCountryLand) originCountryLand.value = routeDetails.origin.country || '';
                    
                    const originCityLand = document.getElementById('origin_city_land');
                    if (originCityLand) originCityLand.value = routeDetails.origin.city || '';
                }

                if (routeDetails.destination) {
                    const destCountryLand = document.getElementById('destination_country_land');
                    if (destCountryLand) destCountryLand.value = routeDetails.destination.country || '';

                    const destCityLand = document.getElementById('destination_city_land');
                    if (destCityLand) destCityLand.value = routeDetails.destination.city || '';
                }
            }

            const dangerousCheckboxLand = document.getElementById('dangerous_land');
            if (dangerousCheckboxLand) {
                dangerousCheckboxLand.checked = !!booking.dangerous;
            }
        }

        function fillSimpleServiceTab(tab) {
            const company = document.getElementById(`company_${tab}`);
            if (!company) return;

            company.value = booking.customer_company || '';

            const contactName = document.getElementById(`contactName_${tab}`);
            if (contactName) contactName.value = booking.customer_contact_name || '';

            const phone = document.getElementById(`phone_${tab}`);
            if (phone) {
                phone.value = booking.customer_phone || '';
                setTimeout(() => {
                    if (typeof setPhoneFieldValue === 'function') {
                        setPhoneFieldValue(phone, booking.customer_phone || '');
                    }
                }, 100);
            }

            const email = document.getElementById(`email_${tab}`);
            if (email) email.value = booking.customer_email || '';

            const goodsDescription = document.getElementById(`goodsDescription_${tab}`);
            if (goodsDescription) goodsDescription.value = booking.goods_description || '';

            const weight = document.getElementById(`gross_weight_kg_${tab}`);
            if (weight) weight.value = cargoDetails.gross_weight_kg || booking.weight_kg || '';

            const volume = document.getElementById(`volume_cbm_${tab}`);
            if (volume) volume.value = cargoDetails.volume_cbm || booking.volume_cbm || '';

            const notes = document.getElementById(`notes_${tab}`);
            if (notes) notes.value = booking.notes || '';

            const documents = document.getElementById(`documents_${tab}`);
            if (documents) documents.value = booking.documents || '';

            const customerSelect = document.getElementById(`customer_id_${tab}`);
            if (customerSelect && booking.customer_id) customerSelect.value = booking.customer_id;

            const origin = routeDetails?.origin || {};
            const destination = routeDetails?.destination || {};
            setCountrySelectValue(`origin_country_${tab}`, origin.country || '');
            setCountrySelectValue(`destination_country_${tab}`, destination.country || '');

            const originCity = document.getElementById(`origin_city_${tab}`);
            if (originCity) originCity.value = origin.city || '';

            const originPostalCode = document.getElementById('origin_postal_code_door');
            if (tab === 'door' && originPostalCode) originPostalCode.value = origin.postal_code || '';

            const destinationCity = document.getElementById(`destination_city_${tab}`);
            if (destinationCity) destinationCity.value = destination.city || '';

            const destinationPostalCode = document.getElementById('destination_postal_code_door');
            if (tab === 'door' && destinationPostalCode) destinationPostalCode.value = destination.postal_code || '';

            const incoterm = document.getElementById(`incoterm_${tab}`);
            if (incoterm) incoterm.value = routeDetails?.incoterm || '';

            const cargoType = document.getElementById(`cargo_type_${tab}`);
            if (cargoType) cargoType.value = cargoDetails.cargo_type || (booking.dangerous ? 'dangerous' : 'general');

            const declaredValue = document.getElementById(`declared_value_${tab}`);
            if (declaredValue) declaredValue.value = cargoDetails.declared_value || cargoDetails.cargo?.declared_value || '';

            const commercialValue = document.getElementById(`commercial_value_${tab}`);
            if (commercialValue) commercialValue.value = cargoDetails.commercial_value || cargoDetails.trade?.commercial_value || '';

            const currency = document.getElementById(`currency_${tab}`);
            if (currency) currency.value = cargoDetails.currency || cargoDetails.trade?.currency || 'USD';

            const hsCode = document.getElementById(`hs_code_${tab}`);
            if (hsCode) hsCode.value = cargoDetails.hs_code || cargoDetails.cargo?.hs_code || '';

            const countryOfOrigin = document.getElementById(`country_of_origin_${tab}`);
            if (countryOfOrigin) countryOfOrigin.value = cargoDetails.country_of_origin || cargoDetails.cargo?.country_of_origin || '';

            const packageType = document.getElementById(`package_type_${tab}`);
            if (packageType) packageType.value = cargoDetails.package_type || cargoDetails.cargo?.package_type || '';

            const netWeight = document.getElementById(`net_weight_kg_${tab}`);
            if (netWeight) netWeight.value = cargoDetails.net_weight_kg || cargoDetails.cargo?.net_weight_kg || '';

            const stackable = document.getElementById(`stackable_${tab}`);
            if (stackable) stackable.checked = cargoDetails.stackable ?? cargoDetails.cargo?.stackable ?? true;

            const dangerous = document.getElementById(`dangerous_${tab}`);
            if (dangerous) dangerous.checked = !!booking.dangerous;

            const serviceDetails = cargoDetails.service || cargoDetails.service_details || {};

            if (tab === 'air') {
                const originAirport = document.getElementById('origin_airport_air');
                if (originAirport) originAirport.value = origin.airport || booking.pol || '';
                if (originAirport) fillAirportReferenceFields(originAirport);

                const originAirportName = document.getElementById('origin_airport_name_air');
                if (originAirportName) originAirportName.value = origin.airport_name || originAirportName.value || '';

                const destinationAirport = document.getElementById('destination_airport_air');
                if (destinationAirport) destinationAirport.value = destination.airport || booking.pod || '';
                if (destinationAirport) fillAirportReferenceFields(destinationAirport);

                const destinationAirportName = document.getElementById('destination_airport_name_air');
                if (destinationAirportName) destinationAirportName.value = destination.airport_name || destinationAirportName.value || '';

                const pieces = document.getElementById('pieces_count_air');
                if (pieces) pieces.value = cargoDetails.pieces_count || '';

                const serviceLevel = document.getElementById('air_service_level_air');
                if (serviceLevel) serviceLevel.value = cargoDetails.service_level || 'standard';

                const readyDate = document.getElementById('ready_date_air');
                if (readyDate) readyDate.value = cargoDetails.ready_date || serviceDetails.ready_date || '';

                [
                    'direct_flight_preferred',
                    'insurance_required',
                    'customs_clearance_origin',
                    'customs_clearance_destination',
                    'pickup_required',
                    'delivery_required',
                ].forEach(flag => {
                    const input = document.querySelector(`#tab-air input[name="${flag}"]`);
                    if (input) input.checked = !!serviceDetails[flag];
                });
            }

            if (tab === 'door') {
                const pickupAddress = document.getElementById('pickup_address_door');
                if (pickupAddress) pickupAddress.value = origin.address || booking.pol || '';

                const pickupContactName = document.getElementById('pickup_contact_name_door');
                if (pickupContactName) pickupContactName.value = origin.contact_name || cargoDetails.pickup_contact?.name || '';

                const pickupPhone = document.getElementById('pickup_phone_door');
                if (pickupPhone) pickupPhone.value = origin.phone || cargoDetails.pickup_contact?.phone || '';

                const pickupPhoneCountryCode = document.querySelector('#tab-contracting input[name="pickup_phone_country_code"]');
                if (pickupPhoneCountryCode) pickupPhoneCountryCode.value = origin.phone_country_code || cargoDetails.pickup_contact?.phone_country_code || '+967';

                const deliveryAddress = document.getElementById('delivery_address_door');
                if (deliveryAddress) deliveryAddress.value = destination.address || booking.pod || '';

                const deliveryContactName = document.getElementById('delivery_contact_name_door');
                if (deliveryContactName) deliveryContactName.value = destination.contact_name || cargoDetails.delivery_contact?.name || '';

                const deliveryPhone = document.getElementById('delivery_phone_door');
                if (deliveryPhone) deliveryPhone.value = destination.phone || cargoDetails.delivery_contact?.phone || '';

                const deliveryPhoneCountryCode = document.querySelector('#tab-contracting input[name="delivery_phone_country_code"]');
                if (deliveryPhoneCountryCode) deliveryPhoneCountryCode.value = destination.phone_country_code || cargoDetails.delivery_contact?.phone_country_code || '+967';

                const mainTransportMode = document.getElementById('main_transport_mode_door');
                if (mainTransportMode) mainTransportMode.value = routeDetails?.main_transport_mode || cargoDetails.main_transport_mode || 'AUTO';

                const packages = document.getElementById('packages_count_door');
                if (packages) packages.value = cargoDetails.packages_count || '';

                const pickupReadyDate = document.getElementById('pickup_ready_date_door');
                if (pickupReadyDate) pickupReadyDate.value = cargoDetails.pickup_ready_date || serviceDetails.pickup_ready_date || '';

                const pickupTimeFrom = document.getElementById('pickup_time_from_door');
                if (pickupTimeFrom) pickupTimeFrom.value = serviceDetails.pickup_time_from || '';

                const pickupTimeTo = document.getElementById('pickup_time_to_door');
                if (pickupTimeTo) pickupTimeTo.value = serviceDetails.pickup_time_to || '';

                const deliveryRequiredDate = document.getElementById('delivery_required_date_door');
                if (deliveryRequiredDate) deliveryRequiredDate.value = serviceDetails.delivery_required_date || '';

                const preferredPickupTime = document.getElementById('preferred_pickup_time_door');
                if (preferredPickupTime) preferredPickupTime.value = cargoDetails.preferred_pickup_time || serviceDetails.preferred_pickup_time || 'anytime';

                const deliveryNotes = document.getElementById('delivery_notes_door');
                if (deliveryNotes) deliveryNotes.value = cargoDetails.delivery_notes || serviceDetails.delivery_notes || '';

                [
                    'insurance_required',
                    'export_customs_clearance',
                    'import_customs_clearance',
                ].forEach(flag => {
                    const input = document.querySelector(`#tab-contracting input[name="${flag}"]`);
                    if (input) input.checked = !!serviceDetails[flag];
                });
            }

            const dangerousGoods = cargoDetails.dangerous_goods || {};
            const dgFields = {
                dangerous_transport_regulation: dangerousGoods.transport_regulation || '',
                dangerous_un_number: dangerousGoods.un_number || '',
                dangerous_iata_class: dangerousGoods.iata_class || '',
                dangerous_imdg_class: dangerousGoods.imdg_class || '',
                dangerous_adr_class: dangerousGoods.adr_class || '',
                dangerous_packing_group: dangerousGoods.packing_group || '',
                dangerous_proper_shipping_name: dangerousGoods.proper_shipping_name || '',
            };

            Object.entries(dgFields).forEach(([field, value]) => {
                const input = document.getElementById(`${field}_${tab}`);
                if (input) input.value = value;
            });

            const msdsAvailable = document.querySelector(`input[name="dangerous_msds_available"]#dangerous_msds_available_${tab}`);
            if (msdsAvailable) msdsAvailable.checked = dangerousGoods.msds_available ?? false;

            window.syncDangerousGoodsPanel?.(tab);
        }

        fillSimpleServiceTab('air');
        fillSimpleServiceTab('door');

        // Set active tab and disable others to lock category
        const routeMode = routeDetails?.mode || '';
        const routeCategory = routeDetails?.category || '';
        let shipmentCat = 'sea';
        if (booking.shipment_type === 'LAND' || routeMode === 'LAND') {
            shipmentCat = 'land';
        } else if (booking.shipment_type === 'AIR' || routeMode === 'AIR') {
            shipmentCat = 'air';
        } else if (booking.shipment_type === 'DOOR_TO_DOOR' || routeMode === 'DOOR_TO_DOOR' || routeCategory === 'door_to_door') {
            shipmentCat = 'contracting';
        }
        if (typeof window.setActiveTab === 'function') {
            window.setActiveTab(shipmentCat);
        }
        document.querySelectorAll('.tab-btn').forEach(btn => {
            if (btn.dataset.tab !== shipmentCat) {
                btn.style.pointerEvents = 'none';
                btn.style.opacity = '0.4';
            }
        });
        
        // Change submit buttons text
        document.querySelectorAll('button[type="submit"]').forEach(btn => {
            const span = btn.querySelector('span');
            if (span) span.textContent = "{{ __('messages.agent_save_changes') }}";
        });
    });
    @endisset
</script>
@include('partials.door-to-door-volume-script')
@endsection

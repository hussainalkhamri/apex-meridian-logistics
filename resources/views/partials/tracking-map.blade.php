@php
    $mapMode = \App\Support\TrackingMapData::mode($found->booking);
    $mapIcon = ['SEA' => 'ship', 'AIR' => 'plane', 'LAND' => 'truck', 'MULTIMODAL' => 'route'][$mapMode];
    $mapBooking = $found->booking;
    $canReadMapDocuments = auth('customer')->check() && (int) auth('customer')->id() === (int) $mapBooking->customer_id;
    if (auth('web')->check()) {
        $canReadMapDocuments = collect(['manage_bookings', 'manage_pricing', 'manage_sales', 'manage_carriers', 'manage_customs', 'manage_accounts'])
            ->contains(fn ($permission) => auth('web')->user()->hasPermission($permission));
    }
    $mapAttachments = is_array($mapBooking->attachments) ? $mapBooking->attachments : (json_decode($mapBooking->attachments ?: '[]', true) ?: ($mapBooking->attachments ? [$mapBooking->attachments] : []));
    $mapAttachments = is_array($mapAttachments) ? array_values(array_filter($mapAttachments, 'is_string')) : [];
    $mapSettings = [
        'url' => route(request()->routeIs('customer.*') ? 'customer.tracking.map' : 'tracking.map', $found->id),
        'tileUrl' => config('tracking.tile_url'),
        'tileAttribution' => config('tracking.tile_attribution'),
        'labels' => __('tracking_map'),
    ];
@endphp
<dialog id="tracking-map-dialog" class="tracking-map-dialog" aria-labelledby="tracking-map-title" data-tracking-map>
    <script type="application/json" data-tracking-map-settings>{!! \Illuminate\Support\Js::encode($mapSettings) !!}</script>
    <header class="tracking-map-header">
        <div class="flex min-w-0 items-center gap-3">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-accent/15 text-accent"><i data-lucide="{{ $mapIcon }}" class="h-5 w-5"></i></span>
            <div class="min-w-0">
                <h2 id="tracking-map-title" class="text-base font-black">{{ __('tracking_map.title') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground" dir="ltr">{{ $found->id }}</p>
            </div>
        </div>
        <button type="button" class="tracking-map-icon-button" data-map-close title="{{ __('tracking_map.close') }}" aria-label="{{ __('tracking_map.close') }}"><i data-lucide="x" class="h-5 w-5"></i></button>
    </header>
    <div class="tracking-map-tabs" role="tablist" aria-label="{{ __('tracking_map.title') }}">
        <button type="button" id="tracking-map-tab" role="tab" aria-selected="true" aria-controls="tracking-map-panel" data-map-tab="map"><i data-lucide="map" class="h-4 w-4"></i>{{ __('tracking_map.map') }}</button>
        <button type="button" id="tracking-details-tab" role="tab" aria-selected="false" aria-controls="tracking-details-panel" tabindex="-1" data-map-tab="details"><i data-lucide="files" class="h-4 w-4"></i>{{ __('tracking_map.details') }}</button>
    </div>
    <div id="tracking-map-panel" class="tracking-map-body" role="tabpanel" aria-labelledby="tracking-map-tab">
        <div class="tracking-map-stage">
            <div class="tracking-map-canvas" data-map-canvas aria-label="{{ __('tracking_map.map') }}"></div>
            <span class="tracking-map-basemap-label" data-map-basemap hidden>{{ __('tracking_map.simple_map') }}</span>
            <div class="tracking-map-tools">
                <button type="button" class="tracking-map-icon-button" data-map-fit title="{{ __('tracking_map.fit') }}" aria-label="{{ __('tracking_map.fit') }}"><i data-lucide="scan" class="h-5 w-5"></i></button>
                <button type="button" class="tracking-map-icon-button" data-map-locate title="{{ __('tracking_map.locate') }}" aria-label="{{ __('tracking_map.locate') }}" disabled><i data-lucide="locate-fixed" class="h-5 w-5"></i></button>
            </div>
            <div class="tracking-map-feedback" data-map-feedback role="status" aria-live="polite">{{ __('tracking_map.loading') }}</div>
        </div>
        <aside class="tracking-map-summary">
            <span class="inline-flex items-center gap-2 text-xs font-black text-warning"><i data-lucide="clock-3" class="h-4 w-4"></i>{{ __('tracking_map.estimated') }}</span>
            <p class="mt-3 text-sm font-bold" data-map-state>{{ __('tracking_map.loading') }}</p>
            <p class="mt-2 text-xs leading-6 text-muted-foreground">{{ __('tracking_map.estimate_notice') }}</p>
            <div class="tracking-map-progress mt-4"><span data-map-progress></span></div>
            <div class="mt-2 flex justify-between text-xs"><span>{{ __('tracking_map.time_progress') }}</span><strong data-map-percent>—</strong></div>
            <dl class="tracking-map-facts">
                <div><dt>{{ __('tracking_map.transport') }}</dt><dd>{{ __('tracking_map.modes.'.$mapMode) }}</dd></div>
                <div><dt>{{ __('tracking_map.origin') }}</dt><dd data-map-origin>{{ $mapBooking->pol }}</dd></div>
                <div><dt>{{ __('tracking_map.destination') }}</dt><dd data-map-destination>{{ $mapBooking->pod }}</dd></div>
                <div><dt>ETD</dt><dd data-map-etd>—</dd></div>
                <div><dt>ETA</dt><dd data-map-eta>—</dd></div>
                <div><dt>{{ __('tracking_map.duration') }}</dt><dd data-map-duration>—</dd></div>
                <div><dt>{{ __('tracking_map.remaining') }}</dt><dd data-map-remaining>—</dd></div>
                <div><dt>{{ __('tracking_map.distance') }}</dt><dd data-map-distance>—</dd></div>
            </dl>
            <p class="text-xs leading-6 text-muted-foreground" data-map-route-note></p>
            <p class="mt-2 text-xs leading-6 text-muted-foreground" data-map-calculated></p>
            <button type="button" class="mt-4 inline-flex h-9 items-center gap-2 rounded-lg border border-border px-3 text-xs font-bold" data-map-refresh><i data-lucide="refresh-cw" class="h-4 w-4"></i>{{ __('tracking_map.refresh') }}</button>
            <p class="mt-4 text-[10px] leading-5 text-muted-foreground">{{ __('tracking_map.geodata') }}: <a href="https://unece.org/trade/cefact/unlocode-code-list-country-and-territory" target="_blank" rel="noopener">UN/LOCODE</a> · <a href="https://ourairports.com/data/" target="_blank" rel="noopener">OurAirports</a> · <a href="https://www.geonames.org/" target="_blank" rel="noopener">GeoNames</a></p>
        </aside>
    </div>
    <section id="tracking-details-panel" class="tracking-map-details" role="tabpanel" aria-labelledby="tracking-details-tab" hidden>
        <h3 class="text-base font-black">{{ __('tracking_map.carrier_details') }}</h3>
        <dl class="tracking-map-facts tracking-map-carrier-facts">
            @foreach([
                __('tracking_map.vehicle') => $mapBooking->carrier_vessel,
                __('messages.voyage') => $mapBooking->carrier_voyage,
                __('messages.carrier_booking_label') => $mapBooking->carrier_booking_ref,
                'ETD' => ($mapBooking->carrier_etd ?? $found->etd)?->toDateString(),
                'ETA' => ($mapBooking->carrier_eta ?? $found->eta)?->toDateString(),
                __('tracking_map.carrier_updated') => $mapBooking->carrier_booked_at?->format('Y-m-d H:i'),
                __('messages.type') => $mapBooking->shipmentDisplay(),
            ] as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value ?: '—' }}</dd></div>
            @endforeach
        </dl>
        @if($canReadMapDocuments)
            <dl class="tracking-map-facts tracking-map-carrier-facts">
                @foreach([
                    'shipper' => 'bookings_shipper_label',
                    'consignee' => 'bookings_consignee_label',
                    'notify_party' => 'bookings_notify_party_label',
                    'bill_type' => 'bookings_bill_type_label',
                    'type_of_move' => 'bookings_type_of_move_label',
                    'place_of_delivery' => 'bookings_place_of_delivery_label',
                    'quantity_packages' => 'bookings_quantity_packages_label',
                    'measurement_cbm' => 'bookings_measurement_cbm_label',
                    'container_seal_no' => 'bookings_container_seal_label',
                ] as $field => $label)
                    @if(is_scalar($mapBooking->$field) && (string) $mapBooking->$field !== '')
                        <div><dt>{{ __('messages.'.$label) }}</dt><dd class="whitespace-pre-line">{{ $mapBooking->$field }}</dd></div>
                    @endif
                @endforeach
            </dl>
        @endif
        <h3 class="mt-6 text-base font-black">{{ __('tracking_map.documents') }}</h3>
        @if($canReadMapDocuments)
            @if(is_string($mapBooking->documents) && $mapBooking->documents !== '')
                <p class="mt-3 whitespace-pre-line text-sm leading-7 text-muted-foreground">{{ $mapBooking->documents }}</p>
            @endif
            <div class="mt-3 divide-y divide-border">
                @forelse($mapAttachments as $path)
                    <a class="flex min-w-0 items-center gap-3 py-3 text-sm font-bold text-accent" href="{{ route('bookings.attachments.show', ['booking' => $mapBooking->id, 'path' => $path]) }}" target="_blank" rel="noopener">
                        <i data-lucide="file" class="h-5 w-5 shrink-0"></i><span class="min-w-0 break-all">{{ basename($path) }}</span><i data-lucide="external-link" class="h-4 w-4 shrink-0"></i>
                    </a>
                @empty
                    <p class="py-4 text-sm text-muted-foreground">{{ __('tracking_map.no_documents') }}</p>
                @endforelse
            </div>
        @else
            <p class="mt-3 text-sm leading-7 text-muted-foreground">{{ __('tracking_map.documents_private') }}</p>
            <a href="{{ route('customer.login') }}" class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-accent"><i data-lucide="log-in" class="h-4 w-4"></i>{{ __('messages.login') }}</a>
        @endif
    </section>
</dialog>

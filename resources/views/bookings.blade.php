@extends('layouts.app')

@section('title', __('messages.bookings_page_title'))

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs text-white border border-white/10">{{ __('messages.bookings_hero_badge') }}</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">{{ __('messages.bookings_hero_heading') }}</h1>
            <p class="mt-2 max-w-2xl text-white/80">
                {{ __('messages.bookings_hero_description') }}
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('pricing.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground shadow hover:opacity-95">
                    <span>{{ __('messages.bookings_back_to_pricing') }}</span>
                </a>
                <a href="{{ route('schedules') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-white/30 bg-white/10 px-5 text-sm font-bold text-white hover:bg-white/20">
                    <span>{{ __('messages.bookings_ship_schedules') }}</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Queue -->
    <section class="container mx-auto px-4 py-10">
        <div class="mb-4 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
                <h2 class="flex items-center gap-2 text-xl font-bold">
                <i data-lucide="clipboard-list" class="h-5 w-5 text-accent"></i>
                <span>{{ __('messages.bookings_queue_heading') }}</span>
            </h2>
            <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold">{{ $queue->count() }} {{ __('messages.bookings_requests') }}</span>
        </div>

        @if($queue->isEmpty())
            <div class="rounded-xl border border-dashed border-border bg-card py-10 text-center text-muted-foreground text-xs">
                {{ __('messages.bookings_no_approved_waiting') }}
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($queue as $b)
                    <div data-booking-id="{{ $b->id }}" class="rounded-xl border border-border bg-card p-5 shadow-card">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-mono text-xs text-muted-foreground">{{ $b->id }}</div>
                                <h3 class="font-bold text-card-foreground text-sm">{{ $b->customer_company }}</h3>
                                <p class="text-xs text-muted-foreground mt-0.5">{{ __('messages.bookings_approved_at') }}: {{ $b->decided_at ? $b->decided_at->toDateString() : '—' }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-success/20 text-success-foreground border border-success/30 px-2.5 py-0.5 text-xs font-bold">
                                {{ __('messages.bookings_approved_label') }}
                            </span>
                        </div>

                        <div class="my-3 flex flex-wrap items-center gap-2 rounded-lg bg-secondary/40 px-3 py-2 text-xs">
                            <i data-lucide="anchor" class="h-4 w-4 text-accent"></i>
                            <span class="font-semibold">{{ $b->pol }} &rarr; {{ $b->pod }}</span>
                            <span class="text-muted-foreground">•</span>
                            <span>{{ $b->shipmentDisplay() }}</span>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2 border-t pt-3">
                            <button onclick="openCarrierFormModal('{{ $b->id }}', '{{ $b->pol }}', '{{ $b->pod }}')" class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground shadow hover:opacity-95">
                                <i data-lucide="ship" class="ml-1 h-4 w-4"></i>
                                <span>{{ __('messages.bookings_confirm_carrier') }}</span>
                            </button>
                            <button onclick="openAgentForwardModal('{{ $b->id }}')" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold text-foreground hover:bg-secondary/40">
                                <i data-lucide="user-plus" class="ml-1 h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.bookings_add_agent_send_sales') }}</span>
                            </button>
                            <button onclick="openDetailModal('{{ $b->id }}')" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">
                                <span>{{ __('messages.bookings_view') }}</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Confirmed Bookings Table -->
    @if($booked->isNotEmpty())
        <section class="container mx-auto px-4 pb-12">
                <h2 class="mb-4 flex items-center gap-2 text-xl font-bold">
                <i data-lucide="check-circle-2" class="h-5 w-5 text-success"></i>
                <span>{{ __('messages.bookings_confirmed_bookings_heading') }}</span>
            </h2>
            <div class="grid gap-3 md:hidden">
                @foreach($booked as $b)
                    <article data-booking-id="{{ $b->id }}" class="rounded-xl border border-border bg-card p-4 shadow-card" onclick="openDetailModal('{{ $b->id }}')">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-mono text-xs font-bold text-muted-foreground">{{ $b->id }}</div>
                                <h4 class="mt-1 truncate text-sm font-black text-card-foreground">{{ $b->customer_company }}</h4>
                                <p class="mt-1 text-xs font-semibold text-muted-foreground">{{ $b->carrier_vessel ?: '—' }} / {{ $b->carrier_voyage ?: '—' }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1" onclick="event.stopPropagation()">
                                <button onclick="editCarrierBooking('{{ $b->id }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-secondary/35 text-muted-foreground transition-all hover:bg-secondary hover:text-foreground" title="{{ __('messages.bookings_edit') }}" aria-label="{{ __('messages.bookings_edit') }}">
                                    <i data-lucide="edit" class="h-4 w-4"></i>
                                </button>
                                <button onclick="openShareCarrierBookingModal('{{ $b->id }}', '{{ addslashes($b->customer_company) }}', '{{ addslashes($b->carrier_vessel) }}', '{{ addslashes($b->carrier_voyage) }}', '{{ addslashes($b->carrier_booking_ref) }}', '{{ addslashes($b->shipper) }}', '{{ addslashes($b->consignee) }}', '{{ addslashes($b->notify_party) }}', '{{ addslashes($b->custom_notes) }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border bg-secondary/35 text-muted-foreground transition-all hover:bg-secondary hover:text-foreground" title="{{ __('messages.bookings_send') }}" aria-label="{{ __('messages.bookings_send') }}">
                                    <i data-lucide="share-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">{{ __('messages.bookings_table_carrier_ref') }}</div>
                                <div class="mt-1 truncate font-mono font-black text-primary">{{ $b->carrier_booking_ref ?: '—' }}</div>
                            </div>
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">{{ __('messages.bookings_table_etd') }}</div>
                                <div class="mt-1 font-black">{{ $b->carrier_etd ? $b->carrier_etd->toDateString() : '—' }}</div>
                            </div>
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">{{ __('messages.bookings_table_eta') }}</div>
                                <div class="mt-1 font-black">{{ $b->carrier_eta ? $b->carrier_eta->toDateString() : '—' }}</div>
                            </div>
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">{{ __('messages.bookings_table_vessel_voyage') }}</div>
                                <div class="mt-1 truncate font-black">{{ $b->carrier_voyage ?: '—' }}</div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
            <div class="hidden overflow-x-auto rounded-xl border border-border bg-card shadow-card md:block">
                <table class="w-full text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">{{ __('messages.bookings_table_order_no') }}</th>
                            <th class="px-4 py-3">{{ __('messages.bookings_table_customer') }}</th>
                            <th class="px-4 py-3">{{ __('messages.bookings_table_vessel_voyage') }}</th>
                            <th class="px-4 py-3">{{ __('messages.bookings_table_carrier_ref') }}</th>
                            <th class="px-4 py-3">{{ __('messages.bookings_table_etd') }}</th>
                            <th class="px-4 py-3">{{ __('messages.bookings_table_eta') }}</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($booked as $b)
                            <tr data-booking-id="{{ $b->id }}" class="border-t border-border/40 hover:bg-secondary/30 transition-colors cursor-pointer" onclick="openDetailModal('{{ $b->id }}')">
                                <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $b->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $b->customer_company }}</td>
                                <td class="px-4 py-3">{{ $b->carrier_vessel }} / {{ $b->carrier_voyage }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-primary">{{ $b->carrier_booking_ref }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ $b->carrier_etd ? $b->carrier_etd->toDateString() : '—' }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ $b->carrier_eta ? $b->carrier_eta->toDateString() : '—' }}</td>
                                <td class="px-4 py-3 text-left" onclick="event.stopPropagation()">
                                    <div class="flex items-center gap-1.5 justify-end">
                                        <button onclick="editCarrierBooking('{{ $b->id }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition-all hover:bg-secondary hover:text-foreground" title="{{ __('messages.bookings_edit') }}" aria-label="{{ __('messages.bookings_edit') }}">
                                            <i data-lucide="edit" class="h-4 w-4"></i>
                                        </button>
                                        <button onclick="openShareCarrierBookingModal('{{ $b->id }}', '{{ addslashes($b->customer_company) }}', '{{ addslashes($b->carrier_vessel) }}', '{{ addslashes($b->carrier_voyage) }}', '{{ addslashes($b->carrier_booking_ref) }}', '{{ addslashes($b->shipper) }}', '{{ addslashes($b->consignee) }}', '{{ addslashes($b->notify_party) }}', '{{ addslashes($b->custom_notes) }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground transition-all hover:bg-secondary hover:text-foreground" title="{{ __('messages.bookings_send') }}" aria-label="{{ __('messages.bookings_send') }}">
                                            <i data-lucide="share-2" class="h-4 w-4"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>

<!-- Carrier Form Modal Dialog -->
<div id="carrier-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-xl bg-card rounded-2xl border border-border shadow-elegant overflow-hidden max-h-[90vh] flex flex-col" onclick="event.stopPropagation()">
        <!-- Header -->
        <div class="flex items-center justify-between border-b p-4 bg-secondary/20">
            <h3 class="font-extrabold text-base text-primary">{{ __('messages.bookings_update_vessel_docs') }}</h3>
            <button onclick="closeCarrierFormModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center transition-all">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        
        <!-- Content Form -->
        <form id="carrier-form" action="" method="POST" class="p-6 space-y-4 text-xs overflow-y-auto">
            @csrf
            <div class="rounded-lg bg-secondary/40 p-3 border mb-1">
                <strong>{{ __('messages.bookings_pol_label') }}</strong> <span id="car-pol">POL</span> | 
                <strong>{{ __('messages.bookings_pod_label') }}</strong> <span id="car-pod">POD</span>
            </div>
            
            @include('partials.carrier-vessel-picker')
            
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1">
                    <label for="voyage" class="font-bold text-foreground">{{ __('messages.bookings_voyage_label') }}</label>
                    <input type="text" name="voyage" id="voyage" required placeholder="{{ __('messages.bookings_voyage_example') }}" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="bookingRef" class="font-bold text-foreground">{{ __('messages.bookings_booking_ref_label') }}</label>
                    <input type="text" name="bookingRef" id="bookingRef" required placeholder="{{ __('messages.bookings_booking_ref_example') }}" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="etd" class="font-bold text-foreground">{{ __('messages.bookings_etd_label') }}</label>
                    <input type="date" name="etd" id="etd" required class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="eta" class="font-bold text-foreground">{{ __('messages.bookings_eta_label') }}</label>
                    <input type="date" name="eta" id="eta" required class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
            </div>

            <div class="space-y-1">
                <label for="bookedBy" class="font-bold text-foreground">{{ __('messages.bookings_booked_by_label') }}</label>
                <input type="text" name="bookedBy" id="bookedBy" value="{{ __('messages.bookings_booked_by_default') }}" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
            </div>

            <div class="border-t pt-3">
                <h4 class="font-bold text-primary mb-2">{{ __('messages.bookings_mbl_details') }}</h4>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1 sm:col-span-2">
                    <label for="shipper" class="font-bold text-foreground">{{ __('messages.bookings_shipper_label') }}</label>
                    <textarea name="shipper" id="shipper" rows="2" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="consignee" class="font-bold text-foreground">{{ __('messages.bookings_consignee_label') }}</label>
                    <textarea name="consignee" id="consignee" rows="2" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="notify_party" class="font-bold text-foreground">{{ __('messages.bookings_notify_party_label') }}</label>
                    <textarea name="notify_party" id="notify_party" rows="2" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1">
                    <label for="bill_type" class="font-bold text-foreground">{{ __('messages.bookings_bill_type_label') }}</label>
                    <select name="bill_type" id="bill_type" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="TELEX RELEASE">TELEX RELEASE</option>
                        <option value="ORIGINAL B/L">ORIGINAL B/L</option>
                        <option value="WAYBILL">WAYBILL</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label for="type_of_move" class="font-bold text-foreground">{{ __('messages.bookings_type_of_move_label') }}</label>
                    <input type="text" name="type_of_move" id="type_of_move" value="CY-CY" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="job_no" class="font-bold text-foreground">{{ __('messages.bookings_job_no_label') }}</label>
                    <input type="text" name="job_no" id="job_no" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="overseas_agent" class="font-bold text-foreground">{{ __('messages.bookings_overseas_agent_label') }}</label>
                    <input type="text" name="overseas_agent" id="overseas_agent" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="place_of_delivery" class="font-bold text-foreground">{{ __('messages.bookings_place_of_delivery_label') }}</label>
                    <input type="text" name="place_of_delivery" id="place_of_delivery" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="quantity_packages" class="font-bold text-foreground">{{ __('messages.bookings_quantity_packages_label') }}</label>
                    <input type="text" name="quantity_packages" id="quantity_packages" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="measurement_cbm" class="font-bold text-foreground">{{ __('messages.bookings_measurement_cbm_label') }}</label>
                    <input type="number" step="0.01" name="measurement_cbm" id="measurement_cbm" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="container_seal_no" class="font-bold text-foreground">{{ __('messages.bookings_container_seal_label') }}</label>
                    <textarea name="container_seal_no" id="container_seal_no" rows="2" placeholder="{{ __('messages.bookings_container_seal_example') }}" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="custom_notes" class="font-bold text-foreground">{{ __('messages.bookings_custom_notes_label') }}</label>
                    <textarea name="custom_notes" id="custom_notes" rows="2" class="w-full p-2 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button" onclick="closeCarrierFormModal()" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">{{ __('messages.bookings_cancel') }}</button>
                <button type="submit" class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-accent px-5 text-xs font-bold text-accent-foreground shadow hover:opacity-95">{{ __('messages.bookings_confirm_save_shipment') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- SHARE CARRIER BOOKING DETAILS MODAL -->
<div id="share-carrier-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-card rounded-2xl border border-border shadow-elegant overflow-hidden flex flex-col text-right">
        <!-- Header -->
        <div class="flex items-center justify-between border-b p-4 bg-secondary/20">
            <h3 class="font-extrabold text-base text-primary">{{ __('messages.bookings_share_modal_title') }}</h3>
            <button onclick="closeShareCarrierBookingModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center transition-all">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        
        <!-- Content -->
        <div class="p-6 space-y-4 text-xs">
            <p class="text-muted-foreground">{{ __('messages.bookings_share_review_text') }}</p>
            
            <textarea id="share-carrier-textarea" rows="10" readonly class="w-full p-3 border border-border bg-secondary/35 rounded-lg text-xs font-mono focus:outline-none leading-relaxed"></textarea>
            
            <div class="flex flex-wrap items-center justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="closeShareCarrierBookingModal()" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold text-foreground transition-all hover:bg-secondary/40">{{ __('messages.bookings_cancel') }}</button>
                <button type="button" onclick="copyCarrierDetailsText()" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg border border-border bg-card px-4 text-xs font-bold text-foreground transition-all hover:bg-secondary/40">
                    <i data-lucide="copy" class="h-4 w-4"></i>
                    <span>{{ __('messages.bookings_copy_text') }}</span>
                </button>
                <button type="button" onclick="sendCarrierDetailsWhatsApp()" class="inline-flex h-9 items-center justify-center gap-1.5 rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground shadow transition-all hover:opacity-95">
                    <i data-lucide="send" class="h-4 w-4"></i>
                    <span>{{ __('messages.bookings_send_whatsapp') }}</span>
                </button>
            </div>
        </div>
    </div>
</div>

@include('partials.booking-detail-modal')
@endsection

@section('scripts')
<script>
    const carrierModal = document.getElementById('carrier-modal');
    const carrierForm = document.getElementById('carrier-form');
    const carrierBookingDetailUrl = id => `/bookings/${encodeURIComponent(id)}`;
    window.bookingDetailEndpoint = carrierBookingDetailUrl;

    @include('partials.carrier-vessel-picker-script')

    function openCarrierFormModal(id, pol, pod, currentData = null) {
        if (!carrierModal) return;
        carrierModal.classList.remove('hidden');
        document.getElementById('car-pol').innerText = pol;
        document.getElementById('car-pod').innerText = pod;

        // Set action URL
        carrierForm.action = `/bookings/${id}/confirm`;

        // Get inputs
        const etdInput = document.getElementById('etd');
        const etaInput = document.getElementById('eta');
        const vesselInput = document.getElementById('vessel');
        const voyageInput = document.getElementById('voyage');
        const bookingRefInput = document.getElementById('bookingRef');
        const bookedByInput = document.getElementById('bookedBy');

        const shipperInput = document.getElementById('shipper');
        const consigneeInput = document.getElementById('consignee');
        const notifyPartyInput = document.getElementById('notify_party');
        const billTypeInput = document.getElementById('bill_type');
        const typeOfMoveInput = document.getElementById('type_of_move');
        const jobNoInput = document.getElementById('job_no');
        const overseasAgentInput = document.getElementById('overseas_agent');
        const placeOfDeliveryInput = document.getElementById('place_of_delivery');
        const qtyPackagesInput = document.getElementById('quantity_packages');
        const measCbmInput = document.getElementById('measurement_cbm');
        const sealNoInput = document.getElementById('container_seal_no');
        const customNotesInput = document.getElementById('custom_notes');

        if (currentData) {
            vesselInput.value = currentData.vessel || '';
            voyageInput.value = currentData.voyage || '';
            bookingRefInput.value = currentData.bookingRef || '';
            etdInput.value = currentData.etd || '';
            etaInput.value = currentData.eta || '';
            
            shipperInput.value = currentData.shipper || '';
            consigneeInput.value = currentData.consignee || '';
            notifyPartyInput.value = currentData.notify_party || '';
            billTypeInput.value = currentData.bill_type || 'TELEX RELEASE';
            typeOfMoveInput.value = currentData.type_of_move || 'CY-CY';
            jobNoInput.value = currentData.job_no || '';
            overseasAgentInput.value = currentData.overseas_agent || '';
            placeOfDeliveryInput.value = currentData.place_of_delivery || '';
            qtyPackagesInput.value = currentData.quantity_packages || '';
            measCbmInput.value = currentData.measurement_cbm || '';
            sealNoInput.value = currentData.container_seal_no || '';
            customNotesInput.value = currentData.custom_notes || '';
        } else {
            // Pre-fill Shipper, Consignee, Notify Party from Quote details via Ajax
            fetch(carrierBookingDetailUrl(id), {
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(b => {
                    shipperInput.value = b.shipper || '';
                    consigneeInput.value = b.consignee || '';
                    notifyPartyInput.value = b.notifyParty || '';
                    measCbmInput.value = b.cargo.volumeCbm || '';
                    qtyPackagesInput.value = b.quantityPackages || b.cargo.containerSummary || (b.cargo.containerQty ? `${b.cargo.containerQty} x ${b.cargo.containerType}` : 'LCL');
                }).catch(() => {});

            vesselInput.value = '';
            bookedByInput.value = "{{ __('messages.bookings_booked_by_default') }}";

            const now = new Date();
            now.setDate(now.getDate() + 5);
            etdInput.value = now.toISOString().split('T')[0];

            const etaDate = new Date();
            etaDate.setDate(etaDate.getDate() + 25);
            etaInput.value = etaDate.toISOString().split('T')[0];

            voyageInput.value = 'VYG-0' + Math.floor(Math.random() * 90 + 10);
            bookingRefInput.value = 'BHLU-' + Math.floor(Math.random() * 9000000 + 1000000);
            
            billTypeInput.value = 'TELEX RELEASE';
            typeOfMoveInput.value = 'CY-CY';
            jobNoInput.value = '';
            overseasAgentInput.value = '';
            placeOfDeliveryInput.value = '';
            sealNoInput.value = '';
            customNotesInput.value = '';
        }

        if (window.refreshCarrierVesselPicker) window.refreshCarrierVesselPicker();
    }

    function editCarrierBooking(id) {
        fetch(carrierBookingDetailUrl(id), {
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                openCarrierFormModal(
                    data.id, 
                    data.route.pol, 
                    data.route.pod, 
                    {
                        vessel: data.carrier?.vessel || '',
                        voyage: data.carrier?.voyage || '',
                        bookingRef: data.carrier?.bookingRef || '',
                        etd: data.carrier?.etd || '',
                        eta: data.carrier?.eta || '',
                        shipper: data.shipper || '',
                        consignee: data.consignee || '',
                        notify_party: data.notifyParty || '',
                        bill_type: data.billType || 'TELEX RELEASE',
                        type_of_move: data.typeOfMove || 'CY-CY',
                        job_no: data.jobNo || '',
                        overseas_agent: data.overseasAgent || '',
                        place_of_delivery: data.placeOfDelivery || '',
                        quantity_packages: data.quantityPackages || '',
                        measurement_cbm: data.measurementCbm || '',
                        container_seal_no: data.containerSealNo || '',
                        custom_notes: data.customNotes || ''
                    }
                );
            })
            .catch(() => {
                alert('تعذر تحميل بيانات الحجز للتعديل. حاول تحديث الصفحة ثم أعد المحاولة.');
            });
    }

    function closeCarrierFormModal() {
        if (window.closeCarrierVesselPickerMenu) window.closeCarrierVesselPickerMenu();
        if (carrierModal) carrierModal.classList.add('hidden');
    }

    function openShareCarrierBookingModal(id, company, vessel, voyage, ref, shipper, consignee, notify, notes) {
        const modal = document.getElementById('share-carrier-modal');
        const textarea = document.getElementById('share-carrier-textarea');
        
        let text = "{{ __('messages.bookings_share_header') }}\n";
        text += "{{ __('messages.bookings_share_separator') }}\n";
        text += "{{ __('messages.bookings_share_order') }}: " + id + "\n";
        text += "{{ __('messages.bookings_share_customer') }}: " + company + "\n";
        text += "{{ __('messages.bookings_share_vessel_voyage') }}: " + (vessel || '—') + " / " + (voyage || '—') + "\n";
        text += "{{ __('messages.bookings_share_booking_ref') }}: " + (ref || '—') + "\n\n";

        text += "{{ __('messages.bookings_share_shipper_label') }}:\n";
        text += (shipper || '—') + "\n\n";

        text += "{{ __('messages.bookings_share_consignee_label') }}:\n";
        text += (consignee || '—') + "\n\n";

        text += "{{ __('messages.bookings_share_notify_label') }}:\n";
        text += (notify || '—') + "\n\n";

        text += "{{ __('messages.bookings_share_notes_label') }}:\n";
        text += (notes || '—') + "\n\n";

        text += "{{ __('messages.bookings_share_footer') }}";
        
        textarea.value = text;
        
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function closeShareCarrierBookingModal() {
        const modal = document.getElementById('share-carrier-modal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }

    function copyCarrierDetailsText() {
        const textarea = document.getElementById('share-carrier-textarea');
        textarea.select();
        document.execCommand('copy');
        alert('{{ __('messages.bookings_copy_success') }}');
    }

    function sendCarrierDetailsWhatsApp() {
        const textarea = document.getElementById('share-carrier-textarea');
        const encodedText = encodeURIComponent(textarea.value);
        window.open(`https://api.whatsapp.com/send?text=${encodedText}`, '_blank');
    }
</script>
@endsection

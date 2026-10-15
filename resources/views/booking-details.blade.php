@extends('layouts.app')

@section('title', __('messages.booking_details_page_title'))

@section('content')
<div class="container mx-auto px-4 py-10">
    <div class="mx-auto max-w-4xl rounded-2xl border border-border bg-card shadow-elegant overflow-hidden">
        <div class="border-b bg-secondary/20 p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div class="text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('messages.booking_details_title') }}</div>
                    <h1 class="mt-1 text-2xl font-extrabold text-primary">{{ $booking->id }}</h1>
                </div>
                <a href="{{ route('orders.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-border bg-background px-4 text-sm font-bold text-foreground hover:bg-secondary/40">
                    <i data-lucide="arrow-right" class="ml-2 h-4 w-4"></i> {{ __('messages.booking_details_back_to_orders') }}
                </a>
            </div>
        </div>

        <div class="space-y-4 p-6 text-sm">
            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border/60 bg-secondary/20 p-4">
                    <div class="mb-3 border-b pb-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('messages.booking_details_customer_info') }}</div>
                    <div class="space-y-2">
                        <div><span class="text-muted-foreground">{{ __('messages.booking_details_customer_label') }}</span> <span class="font-bold text-foreground">{{ $resolvedCustomer['company'] ?? '—' }}</span></div>
                        <div><span class="text-muted-foreground">{{ __('messages.booking_details_contact_label') }}</span> <span class="font-bold text-foreground">{{ $resolvedCustomer['contactName'] ?? '—' }}</span></div>
                        <div dir="ltr"><span class="text-muted-foreground">{{ __('messages.booking_details_phone_label') }}</span> <span class="font-bold text-foreground">{{ $resolvedCustomer['phone'] ?? '—' }}</span></div>
                        <div dir="ltr"><span class="text-muted-foreground">{{ __('messages.booking_details_email_label') }}</span> <span class="font-bold text-foreground">{{ $resolvedCustomer['email'] ?? '—' }}</span></div>
                    </div>
                </div>

                <div class="rounded-xl border border-border/60 bg-secondary/20 p-4">
                    <div class="mb-3 border-b pb-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('messages.booking_details_route') }}</div>
                    <div class="space-y-2">
                        <div class="flex items-center gap-2 font-bold text-foreground">
                            <span>{{ $resolvedRoute['pol'] ?? '—' }}</span>
                            <i data-lucide="arrow-left" class="h-4 w-4 text-accent"></i>
                            <span>{{ $resolvedRoute['pod'] ?? '—' }}</span>
                        </div>
                        @if(!empty($resolvedRoute['details']['mode']))
                            <div class="text-xs text-muted-foreground">{{ __('messages.booking_details_shipping_terms') }} <span class="font-semibold text-foreground">{{ $resolvedRoute['details']['mode'] }}</span></div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="rounded-xl border border-border/60 p-4">
                <div class="mb-3 border-b pb-2 text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ __('messages.booking_details_cargo_info') }}</div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <?php $containerDisplay = $booking->containerSummary() ?: data_get($resolvedCargo, 'cargoDetails.container_summary') ?: ($resolvedCargo['containerType'] ? $resolvedCargo['containerQty'] . 'x ' . $resolvedCargo['containerType'] : '—'); ?>
                    <div><span class="block text-muted-foreground">{{ __('messages.booking_details_type_label') }}</span><span class="mt-1 block font-bold text-foreground">{{ $resolvedCargo['shipmentType'] ?? '—' }}</span></div>
                    <div><span class="block text-muted-foreground">{{ __('messages.booking_details_container_label') }}</span><span class="mt-1 block font-bold text-foreground">{{ $containerDisplay }}</span></div>
                    <div><span class="block text-muted-foreground">DG</span><span class="mt-1 block font-bold text-foreground">{{ !empty($resolvedCargo['dangerous']) ? __('messages.booking_details_yes') : __('messages.booking_details_no') }}</span></div>
                    <div><span class="block text-muted-foreground">{{ __('messages.booking_details_weight_label') }}</span><span class="mt-1 block font-bold text-foreground">{{ is_numeric($resolvedCargo['weightKg'] ?? null) ? number_format((float) $resolvedCargo['weightKg'], 0) . ' ' . __('messages.kg') : '—' }}</span></div>
                    <div><span class="block text-muted-foreground">{{ __('messages.booking_details_volume_label') }}</span><span class="mt-1 block font-bold text-foreground">{{ is_numeric($resolvedCargo['volumeCbm'] ?? null) ? number_format((float) $resolvedCargo['volumeCbm'], 0) . ' CBM' : '—' }}</span></div>
                    <div><span class="block text-muted-foreground">{{ __('messages.booking_details_status_label') }}</span><span class="mt-1 block font-bold text-foreground">{{ $booking->status ?? '—' }}</span></div>
                </div>
                <div class="mt-4 space-y-2">
                    <div><span class="text-muted-foreground">{{ __('messages.booking_details_description_label') }}:</span> <p class="mt-1 rounded border border-border/40 bg-secondary/20 p-2 text-foreground">{{ $resolvedCargo['goodsDescription'] ?? '—' }}</p></div>
                    <div><span class="text-muted-foreground">{{ __('messages.booking_details_notes_label') }}:</span> <p class="mt-1 rounded border border-border/40 bg-secondary/20 p-2 text-foreground">{{ $resolvedCargo['notes'] ?? '—' }}</p></div>
                </div>

                @php
                    $cargoDetails = $resolvedCargo['cargoDetails'] ?? [];
                    $shipmentType = $resolvedCargo['shipmentType'] ?? '—';
                    $labelMap = [
                        'cargo_name' => __('messages.booking_detail_cargo_name'),
                        'pieces' => __('messages.booking_detail_pieces'),
                        'piece_weight' => __('messages.booking_detail_piece_weight'),
                        'total_weight' => __('messages.booking_detail_total_weight'),
                        'length' => __('messages.booking_detail_length'),
                        'width' => __('messages.booking_detail_width'),
                        'height' => __('messages.booking_detail_height'),
                        'cbm' => __('messages.booking_detail_cbm'),
                        'packaging_type' => __('messages.booking_detail_packaging_type'),
                        'lifting_method' => __('messages.booking_detail_lifting_method'),
                        'stackable' => __('messages.booking_detail_stackable'),
                        'vehicle_type' => __('messages.booking_detail_vehicle_type'),
                        'manufacturer' => __('messages.booking_detail_manufacturer'),
                        'model' => __('messages.booking_detail_model'),
                        'year' => __('messages.booking_detail_year'),
                        'color' => __('messages.booking_detail_color'),
                        'vin' => __('messages.booking_detail_vin'),
                        'chassis_number' => __('messages.booking_detail_chassis_number'),
                        'engine_number' => __('messages.booking_detail_engine_number'),
                        'plate_number' => __('messages.booking_detail_plate_number'),
                        'weight' => __('messages.booking_detail_weight'),
                        'running' => __('messages.booking_detail_running'),
                        'tow_required' => __('messages.booking_detail_tow_required'),
                        'fuel_level' => __('messages.booking_detail_fuel_level'),
                        'has_key' => __('messages.booking_detail_has_key'),
                        'has_items' => __('messages.booking_detail_has_items'),
                        'value' => __('messages.booking_detail_value'),
                        'reefer_temperature' => __('messages.booking_detail_reefer_temperature'),
                        'container_summary' => __('messages.booking_details_container_label'),
                        'container_total_qty' => __('messages.booking_container_qty_label'),
                    ];
                    $shipmentTitle = match ($shipmentType) {
                        'Bulk' => __('messages.booking_detail_bulk_title'),
                        'RoRo' => __('messages.booking_detail_roro_title'),
                        'FCL', 'LCL' => __('messages.booking_detail_additional_title'),
                        default => __('messages.booking_detail_extra_title'),
                    };
                @endphp

                @if(!empty($cargoDetails))
                    <div class="mt-5 rounded-xl border border-border/60 bg-gradient-to-br from-secondary/20 to-background p-4">
                        <div class="mb-3 flex items-center justify-between border-b pb-2">
                            <div class="text-xs font-bold uppercase tracking-wider text-muted-foreground">{{ $shipmentTitle }}</div>
                            <span class="rounded-full border border-accent/20 bg-accent/10 px-3 py-1 text-[11px] font-semibold text-accent">{{ $shipmentType }}</span>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            @foreach($cargoDetails as $key => $value)
                                @php
                                    if ($value === null || $value === '') {
                                        continue;
                                    }

                                    if ($key === 'containers') {
                                        continue;
                                    }

                                    $label = $labelMap[$key] ?? str_replace('_', ' ', $key);
                                    $displayValue = is_bool($value) ? ($value ? __('messages.booking_details_yes') : __('messages.booking_details_no')) : $value;
                                    if (is_array($displayValue)) {
                                        $displayValue = json_encode($displayValue, JSON_UNESCAPED_UNICODE);
                                    }
                                @endphp
                                <div class="rounded-lg border border-border/40 bg-background/70 p-3">
                                    <span class="block text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">{{ $label }}</span>
                                    <span class="mt-1 block font-bold text-foreground">{{ $displayValue }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

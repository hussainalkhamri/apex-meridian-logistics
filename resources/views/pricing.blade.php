@extends('layouts.app')

@section('title', __('messages.pricing_page_title'))

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs text-white border border-white/10">{{ __('messages.pricing_hero_badge') }}</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">{{ __('messages.pricing_hero_heading') }}</h1>
            <p class="mt-2 max-w-2xl text-white/80">{{ __('messages.pricing_hero_description') }}</p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('bookings.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground shadow hover:opacity-95">
                    <span>{{ __('messages.bookings_page_title') }} ←</span>
                </a>
                <a href="{{ route('orders.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-white/30 bg-white/10 px-5 text-sm font-bold text-white hover:bg-white/20">
                    <span>{{ __('messages.orders_page_title') }}</span>
                </a>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-10">
        @include('partials.booking-filters', [
            'filterAction' => route('pricing.index'),
            'filterId' => 'pricing-filter-form',
            'filters' => $filters,
        ])

        @php
            $pricingPayloadFor = static function (\App\Models\Booking $booking, bool $isEdit = false): array {
                $cargoDetails = is_array($booking->cargo_details) ? $booking->cargo_details : [];
                $routeDetails = is_array($booking->route_details) ? $booking->route_details : [];
                $isAir = strtoupper((string) $booking->shipment_type) === 'AIR'
                    || strtolower((string) data_get($routeDetails, 'category')) === 'air';
                $isBulk = strtoupper((string) $booking->shipment_type) === 'BULK';
                $isRoRo = in_array(strtoupper((string) $booking->shipment_type), ['RORO', 'RO-RO', 'FLAT CONTAINER'], true);
                $weightKg = (float) ($booking->weight_kg ?? data_get($cargoDetails, 'cargo.gross_weight_kg') ?? data_get($cargoDetails, 'gross_weight_kg') ?? 0);
                $volumeCbm = (float) ($booking->volume_cbm ?? data_get($cargoDetails, 'cargo.volume_cbm') ?? 0);
                $containerRows = data_get($cargoDetails, 'containers');
                $containerRows = is_array($containerRows) && $containerRows !== []
                    ? $containerRows
                    : [[
                        'type' => $booking->container_type,
                        'qty' => $booking->container_qty ?: 1,
                    ]];
                $fclPricingRows = $booking->fclPricingLines();
                $fclPayloadRows = $isEdit && $fclPricingRows !== [] ? $fclPricingRows : $containerRows;
                $agentPurchaseRows = data_get($cargoDetails, 'agent_purchase_lines', []);
                $agentPurchaseRows = $isEdit && is_array($agentPurchaseRows) && $agentPurchaseRows !== []
                    ? $agentPurchaseRows
                    : $fclPayloadRows;
                $containerQuantity = (float) ($booking->containerTotalQuantity() ?: 1);
                $pricingQuantity = (float) ($booking->agent_purchase_quantity ?: $containerQuantity);

                if ($isAir && $volumeCbm > 0) {
                    $pricingQuantity = $volumeCbm;
                }

                $standardDivisor = $pricingQuantity > 0 ? $pricingQuantity : 1;
                $standardUnit = static fn ($value): float => round((float) ($value ?? 0) / $standardDivisor, 2);
                $fclDivisor = collect($fclPayloadRows)->sum(fn ($row) => (float) data_get($row, 'qty', data_get($row, 'quantity', 0)));
                $fclDivisor = $fclDivisor > 0 ? $fclDivisor : $standardDivisor;
                $fclUnit = static fn ($value): float => round((float) ($value ?? 0) / $fclDivisor, 2);
                $doorToDoorPricing = $booking->doorToDoorPricing();
                $agentPurchaseQuantity = (float) ($booking->agent_purchase_quantity ?: $pricingQuantity);
                $agentPurchaseUnitPrice = $agentPurchaseQuantity > 0
                    ? round((float) ($booking->agent_purchase_amount ?? 0) / $agentPurchaseQuantity, 2)
                    : (float) ($booking->agent_purchase_amount ?? 0);

                return [
                    'id' => $booking->id,
                    'pol' => $booking->pol,
                    'pod' => $booking->pod,
                    'cargo' => $booking->shipmentDisplay(),
                    'quantity' => $pricingQuantity > 0 ? $pricingQuantity : 1,
                    'isEdit' => $isEdit,
                    'status' => $booking->status,
                    'isLand' => $booking->isLandShipment(),
                    'isDoorToDoor' => $booking->isDoorToDoorShipment(),
                    'isExwSea' => $booking->isExwSeaShipment(),
                    'isAir' => $isAir,
                    'isBulk' => $isBulk,
                    'isRoRo' => $isRoRo,
                    'hsCode' => $booking->hsCode(),
                    'weightKg' => $weightKg,
                    'volumeCbm' => $volumeCbm,
                    'cargoItems' => $booking->cargoItems(),
                    'itemPricingRows' => $isEdit ? $booking->itemPricingLines() : [],
                    'landShipments' => $isEdit ? $booking->landPricingLines() : $booking->landShipmentLines(),
                    'fclContainerRows' => $fclPayloadRows,
                    'agentPurchaseRows' => $agentPurchaseRows,
                    'pricing' => [
                        'oceanFreight' => count($fclPayloadRows) > 1 ? 0 : $standardUnit($booking->ocean_freight),
                        'originCharges' => count($fclPayloadRows) > 1 ? $fclUnit($booking->origin_charges) : $standardUnit($booking->origin_charges),
                        'exwCharges' => count($fclPayloadRows) > 1 ? $fclUnit($booking->exw_charges) : $standardUnit($booking->exw_charges),
                        'dthc' => count($fclPayloadRows) > 1 ? $fclUnit($booking->dthc) : $standardUnit($booking->dthc),
                        'warRiskSurcharge' => count($fclPayloadRows) > 1 ? $fclUnit($booking->war_risk_surcharge) : $standardUnit($booking->war_risk_surcharge),
                        'otherCharges' => count($fclPayloadRows) > 1 ? $fclUnit($booking->other_charges) : $standardUnit($booking->other_charges),
                        'destinationPortChangeFee' => count($fclPayloadRows) > 1 ? $fclUnit($booking->destination_port_change_fee) : $standardUnit($booking->destination_port_change_fee),
                        'otherPenalties' => count($fclPayloadRows) > 1 ? $fclUnit($booking->other_penalties) : $standardUnit($booking->other_penalties),
                        'transitDays' => $booking->transit_days,
                        'validityDate' => $booking->validity_date?->toDateString(),
                        'agentId' => $booking->agent_id,
                        'agentPurchaseUnitPrice' => $agentPurchaseUnitPrice,
                        'agentPurchaseQuantity' => $agentPurchaseQuantity > 0 ? $agentPurchaseQuantity : $pricingQuantity,
                        'agentPurchaseNotes' => $booking->agent_purchase_notes,
                        'notes' => $booking->pricing_notes,
                        'doorToDoorPricingBasis' => $doorToDoorPricing['basis'],
                        'doorToDoorQuantity' => $doorToDoorPricing['quantity'],
                        'doorToDoorUnitPrice' => $doorToDoorPricing['unit_price'],
                        'customPricingCharges' => $booking->customPricingCharges(),
                    ],
                ];
            };
        @endphp

        <!-- Queue Header -->
        <div class="mb-4 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 text-xl font-bold">
                <i data-lucide="calculator" class="h-5 w-5 text-accent"></i>
                <span>{{ __('messages.pricing_queue_heading') }}</span>
            </h2>
            <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold">{{ $queue->count() }} {{ __('messages.pricing_requests_pending') }}</span>
        </div>

        @if($queue->isNotEmpty())
            <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-accent/25 bg-accent/10 p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-accent text-accent-foreground">
                            <i data-lucide="bell-ring" class="h-4 w-4"></i>
                        </span>
                        <div class="text-2xl font-black text-accent">{{ $queue->count() }}</div>
                    </div>
                    <div class="mt-3 text-sm font-black text-foreground">{{ __('messages.pricing_alerts') }}</div>
                    <div class="mt-1 text-[11px] leading-5 text-muted-foreground">{{ __('messages.pricing_alerts_desc') }}</div>
                </div>

                <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-primary/10 text-primary">
                            <i data-lucide="file-warning" class="h-4 w-4"></i>
                        </span>
                        <div class="text-2xl font-black text-primary">{{ $unpricedCount }}</div>
                    </div>
                    <div class="mt-3 text-sm font-black text-foreground">{{ __('messages.pricing_unpriced') }}</div>
                    <div class="mt-1 text-[11px] leading-5 text-muted-foreground">{{ __('messages.pricing_unpriced_desc') }}</div>
                </div>

                <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-warning/15 text-warning-foreground">
                            <i data-lucide="refresh-cw" class="h-4 w-4"></i>
                        </span>
                        <div class="text-2xl font-black text-warning-foreground">{{ $repricingCount }}</div>
                    </div>
                    <div class="mt-3 text-sm font-black text-foreground">{{ __('messages.pricing_repricing') }}</div>
                    <div class="mt-1 text-[11px] leading-5 text-muted-foreground">{{ __('messages.pricing_repricing_desc') }}</div>
                </div>

                <div class="rounded-xl border border-border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <span class="grid h-9 w-9 place-items-center rounded-lg bg-secondary text-muted-foreground">
                            <i data-lucide="clock-3" class="h-4 w-4"></i>
                        </span>
                        <div class="max-w-[9rem] truncate font-mono text-sm font-black text-foreground" dir="ltr">{{ $oldestWaiting?->id ?: '—' }}</div>
                    </div>
                    <div class="mt-3 text-sm font-black text-foreground">{{ __('messages.pricing_oldest') }}</div>
                    <div class="mt-1 text-[11px] leading-5 text-muted-foreground">{{ $oldestWaiting ? $oldestWaiting->created_at->diffForHumans() : __('messages.pricing_no_waiting') }}</div>
                </div>
            </div>
        @endif

        <!-- Queue Cards -->
        @if($queue->isEmpty())
            <div class="rounded-xl border border-dashed border-border bg-card py-10 text-center text-muted-foreground text-xs">
                {{ __('messages.pricing_no_queue') }}
            </div>
        @else
            <div class="grid gap-4 md:grid-cols-2">
                @foreach($queue as $b)
                    @php
                        $note = $notificationMap[$b->id] ?? null;
                        $cargoLabel = $b->shipmentDisplay();
                        $pricingPayload = $pricingPayloadFor($b);
                    @endphp
                    <div id="booking-card-{{ $b->id }}" data-booking-id="{{ $b->id }}" class="rounded-xl border border-border bg-card p-5 shadow-card transition hover:shadow-glow">
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full border {{ $b->status === 'needs_pricing' ? 'border-warning/30 bg-warning/15 text-warning-foreground' : 'border-accent/25 bg-accent/10 text-accent' }} px-2.5 py-1 text-[11px] font-black">
                                <i data-lucide="{{ $b->status === 'needs_pricing' ? 'refresh-cw' : 'bell-ring' }}" class="h-3.5 w-3.5"></i>
                                {{ $b->status === 'needs_pricing' ? __('messages.pricing_reprice_needed') : __('messages.pricing_unpriced') }}
                            </span>
                            <span class="text-[11px] font-bold text-muted-foreground">{{ $b->created_at->diffForHumans() }}</span>
                        </div>
                        @if($note)
                                <div class="mb-3 rounded-md border border-accent/20 bg-accent/10 p-3 text-sm text-accent">
                                <strong>{{ __('messages.pricing_note_from_sales') }}</strong>
                                <div class="mt-1">{{ $note->message }}</div>
                                <div class="mt-2 text-xs">
                                    <form action="{{ route('notifications.markRead', $note->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-accent px-3 py-1 text-xs font-semibold text-accent-foreground">{{ __('messages.pricing_mark_read') }}</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="mb-3 rounded-md border border-border bg-secondary/35 p-3 text-xs leading-6 text-muted-foreground">
                                <span class="font-black text-foreground">{{ __('messages.pricing_alert_label') }}</span>
                                {{ __('messages.pricing_alert_text') }}
                            </div>
                        @endif
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-xs text-muted-foreground">{{ $b->id }}</span>
                                <h3 class="font-bold text-card-foreground">{{ $b->customer_company }}</h3>
                                <p class="text-xs text-muted-foreground mt-0.5">{{ $b->customer_contact_name }} • {{ $b->created_at->toDateString() }}</p>
                            </div>
                            @if($b->dangerous)
                                <span class="inline-flex items-center gap-1 rounded-full bg-destructive/15 border border-destructive/20 px-2 py-0.5 text-xs font-bold text-destructive">
                                    <i data-lucide="alert-triangle" class="h-3 w-3"></i> DG
                                </span>
                            @endif
                        </div>

                        <div class="my-3 flex flex-wrap items-center gap-2 rounded-lg bg-secondary/40 px-3 py-2 text-xs">
                            <i data-lucide="anchor" class="h-4 w-4 text-accent"></i>
                            <span class="font-semibold">{{ $b->pol }} &rarr; {{ $b->pod }}</span>
                            @if(data_get($b->route_details, 'mode'))
                                <span class="inline-flex items-center rounded-full bg-accent/10 px-2 py-0.5 text-[10px] font-bold text-accent">{{ data_get($b->route_details, 'mode') }}</span>
                            @endif
                            <span class="text-muted-foreground">•</span>
                            <span>{{ $cargoLabel }}</span>
                        </div>

                        <p class="text-xs text-muted-foreground line-clamp-2 leading-relaxed">
                            {{ $b->goods_description }} — {{ number_format($b->weight_kg) }} {{ __('messages.kg') }} / {{ $b->volume_cbm }} CBM
                        </p>

                        <div class="mt-4 flex flex-wrap gap-2 border-t pt-3">
                            <button onclick="openPricingFormModal(@js($pricingPayload))" class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground shadow hover:opacity-95">
                                <i data-lucide="dollar-sign" class="ml-1 h-4 w-4"></i>
                                <span>{{ __('messages.pricing_price_order') }}</span>
                            </button>
                            <button onclick="openAgentForwardModal('{{ $b->id }}')" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">
                                <i data-lucide="send" class="ml-1 h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.agent_forward_send') }}</span>
                            </button>
                            <button onclick="openDetailModal('{{ $b->id }}')" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">
                                <span>{{ __('messages.bookings_view') }}</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Recent Priced Proposals -->
        @if($recent->isNotEmpty())
            <div class="mt-10">
                <h3 class="mb-3 flex items-center gap-2 font-bold">
                    <i data-lucide="clock" class="h-4 w-4 text-accent"></i>
                    <span>{{ __('messages.pricing_recent_heading') }}</span>
                </h3>
                <div class="grid gap-3 md:hidden">
                    @foreach($recent as $b)
                        @php
                            $badgeColors = [
                                'priced' => 'bg-secondary text-foreground',
                                'sent' => 'bg-accent/20 text-accent-foreground border-accent/20',
                                'approved' => 'bg-success/20 text-success-foreground border-success/30',
                                'booked' => 'bg-blue-600/20 text-blue-800 border-blue-600/30'
                            ];
                            $labels = [
                                'priced' => __('messages.orders_status_priced'),
                                'sent' => __('messages.orders_status_sent'),
                                'approved' => __('messages.orders_status_approved'),
                                'booked' => __('messages.orders_status_booked')
                            ];
                            $canEditPricing = in_array($b->status, ['priced', 'sent'], true);
                            $pricingPayload = $pricingPayloadFor($b, true);
                        @endphp
                        <article data-booking-id="{{ $b->id }}" class="rounded-xl border border-border bg-card p-4 shadow-card" onclick="openDetailModal('{{ $b->id }}')">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-mono text-xs font-bold text-muted-foreground">{{ $b->id }}</div>
                                    <h4 class="mt-1 truncate text-sm font-black text-card-foreground">{{ $b->customer_company }}</h4>
                                    <p class="mt-1 text-xs font-semibold text-muted-foreground">{{ $b->pol }} &rarr; {{ $b->pod }}</p>
                                </div>
                                <span class="shrink-0 rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $badgeColors[$b->status] ?? 'bg-secondary text-foreground border-border' }}">
                                    {{ $labels[$b->status] ?? $b->status }}
                                </span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div class="rounded-lg bg-secondary/35 p-2">
                                    <div class="text-muted-foreground">{{ __('messages.orders_table_total') }}</div>
                                    <div class="mt-1 font-black text-primary" dir="ltr">{{ $b->formatPricingMoney($b->pricingTotal()) }}</div>
                                </div>
                                <div class="rounded-lg bg-secondary/35 p-2">
                                    <div class="text-muted-foreground">{{ __('messages.pricing_agent_label') }}</div>
                                    <div class="mt-1 truncate font-black">{{ optional($b->agent)->name ?: '—' }}</div>
                                </div>
                                <div class="rounded-lg bg-secondary/35 p-2">
                                    <div class="text-muted-foreground">{{ __('messages.pricing_agent_purchase') }}</div>
                                    <div class="mt-1 font-black text-warning-foreground" dir="ltr">{{ $b->formatPricingMoney($b->agentPurchaseTotal()) }}</div>
                                </div>
                                <div class="rounded-lg bg-secondary/35 p-2">
                                    <div class="text-muted-foreground">{{ __('messages.pricing_profit') }}</div>
                                    <div class="mt-1 font-black {{ $b->profitAmount() >= 0 ? 'text-success' : 'text-destructive' }}" dir="ltr">{{ $b->formatPricingMoney($b->profitAmount()) }}</div>
                                </div>
                            </div>
                            @if($canEditPricing)
                                <div class="mt-3 border-t border-border/60 pt-3">
                                    <button type="button" onclick="event.stopPropagation(); openPricingFormModal(@js($pricingPayload))" class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-lg border border-accent/25 bg-accent/10 px-3 text-[11px] font-black text-accent transition hover:bg-accent/15">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                        <span>تعديل السعر</span>
                                    </button>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
                <div class="hidden overflow-x-auto rounded-xl border border-border bg-card shadow-card md:block">
                    <table class="w-full text-right text-sm">
                        <thead class="bg-secondary/40 text-xs text-muted-foreground">
                            <tr>
                                <th class="px-4 py-3">{{ __('messages.orders_table_order_no') }}</th>
                                <th class="px-4 py-3">{{ __('messages.orders_table_customer') }}</th>
                                <th class="px-4 py-3">{{ __('messages.orders_table_route') }}</th>
                                <th class="px-4 py-3">{{ __('messages.orders_table_total') }}</th>
                                <th class="px-4 py-3">{{ __('messages.pricing_agent_label') }}</th>
                                <th class="px-4 py-3">{{ __('messages.pricing_agent_purchase') }}</th>
                                <th class="px-4 py-3">{{ __('messages.pricing_profit') }}</th>
                                <th class="px-4 py-3">{{ __('messages.orders_table_status') }}</th>
                                <th class="px-4 py-3">إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recent as $b)
                                @php
                                    $canEditPricing = in_array($b->status, ['priced', 'sent'], true);
                                    $pricingPayload = $pricingPayloadFor($b, true);
                                @endphp
                                <tr data-booking-id="{{ $b->id }}" class="border-t border-border/40 hover:bg-secondary/30 transition-colors cursor-pointer" onclick="openDetailModal('{{ $b->id }}')">
                                    <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $b->id }}</td>
                                    <td class="px-4 py-3 font-medium">{{ $b->customer_company }}</td>
                                    <td class="px-4 py-3 text-muted-foreground">{{ $b->pol }} &rarr; {{ $b->pod }}</td>
                                    <td class="px-4 py-3 font-bold text-primary" dir="ltr">{{ $b->formatPricingMoney($b->pricingTotal()) }}</td>
                                    <td class="px-4 py-3 text-xs font-bold">{{ optional($b->agent)->name ?: '—' }}</td>
                                    <td class="px-4 py-3 font-bold text-warning-foreground" dir="ltr">{{ $b->formatPricingMoney($b->agentPurchaseTotal()) }}</td>
                                    <td class="px-4 py-3 font-bold {{ $b->profitAmount() >= 0 ? 'text-success' : 'text-destructive' }}" dir="ltr">{{ $b->formatPricingMoney($b->profitAmount()) }}</td>
                                    <td class="px-4 py-3">
                                        @php
                                            $badgeColors = [
                                                'priced' => 'bg-secondary text-foreground',
                                                'sent' => 'bg-accent/20 text-accent-foreground border-accent/20',
                                                'approved' => 'bg-success/20 text-success-foreground border-success/30',
                                                'booked' => 'bg-blue-600/20 text-blue-800 border-blue-600/30'
                                            ];
                                            $labels = [
                                                'priced' => __('messages.orders_status_priced'),
                                                'sent' => __('messages.orders_status_sent'),
                                                'approved' => __('messages.orders_status_approved'),
                                                'booked' => __('messages.orders_status_booked')
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold border {{ $badgeColors[$b->status] ?? '' }}">
                                            {{ $labels[$b->status] ?? $b->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($canEditPricing)
                                            <button type="button" onclick="event.stopPropagation(); openPricingFormModal(@js($pricingPayload))" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-accent/25 bg-accent/10 px-3 text-[11px] font-black text-accent transition hover:bg-accent/15">
                                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                                                <span>تعديل</span>
                                            </button>
                                        @else
                                            <span class="text-[11px] font-bold text-muted-foreground">مقفلة</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
</div>

<!-- Pricing Form Modal Dialog -->
<div id="pricing-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-3xl bg-card rounded-2xl border border-border shadow-elegant overflow-hidden max-h-[95vh] flex flex-col" onclick="event.stopPropagation()">
        <!-- Header -->
            <div class="flex items-center justify-between border-b p-4 bg-secondary/20">
            <div>
                <span id="price-booking-id" class="font-mono text-xs text-muted-foreground">BK-XXXXX</span>
                <h3 id="pricing-modal-title" class="font-extrabold text-lg text-primary">{{ __('messages.pricing_modal_title') }}</h3>
            </div>
            <button onclick="closePricingFormModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center transition-all">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        
        <!-- Content Form -->
        <form id="pricing-form" action="" method="POST" class="p-6 overflow-y-auto space-y-4 text-xs flex-1">
            @csrf
            <input type="hidden" name="pricingEditMode" id="pricingEditMode" value="0">
            <div class="rounded-lg bg-secondary/40 p-3 border mb-2">
                <strong>{{ __('messages.pricing_route_label') }}</strong> <span id="price-route">POL &rarr; POD</span><br>
                <strong>{{ __('messages.pricing_type_label') }}</strong> <span id="price-cargo">FCL 40HC</span><br>
                <span id="price-hs-code-wrap" class="hidden"><strong>HS CODE Number:</strong> <span id="price-hs-code" dir="ltr"></span></span>
            </div>

            <div id="item-pricing-section" class="hidden rounded-lg border border-accent/25 bg-accent/5 p-3">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2 font-bold text-primary">
                            <i data-lucide="boxes" class="h-4 w-4 text-accent"></i>
                            <span>تسعير أصناف الشحنة</span>
                        </div>
                        <p class="mt-1 text-[11px] text-muted-foreground">حدد سعر البيع والشراء لكل صنف؛ الإجماليات والربح تُحسب تلقائياً.</p>
                    </div>
                    <button type="button" onclick="addItemPricingRow()" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-accent/30 bg-accent/10 px-3 text-[11px] font-black text-accent transition hover:bg-accent/15">
                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                        <span>إضافة صنف جديد</span>
                    </button>
                </div>
                <div class="overflow-x-auto rounded-lg border border-border bg-background">
                    <table class="w-full min-w-[1040px] text-right text-[11px]">
                        <thead class="bg-secondary/50 text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2">الصنف</th>
                                <th class="px-3 py-2">HS CODE</th>
                                <th class="px-3 py-2">الكمية</th>
                                <th class="px-3 py-2">الوحدة</th>
                                <th class="px-3 py-2">سعر البيع</th>
                                <th class="px-3 py-2">سعر الشراء</th>
                                <th class="px-3 py-2">إجمالي البيع</th>
                                <th class="px-3 py-2">إجمالي الشراء</th>
                                <th class="w-12 px-2 py-2"></th>
                            </tr>
                        </thead>
                        <tbody id="item-pricing-rows" class="divide-y divide-border"></tbody>
                    </table>
                </div>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <div class="flex items-center justify-between rounded-lg bg-background px-3 py-2">
                        <span class="font-bold text-muted-foreground">إجمالي بيع الأصناف</span>
                        <span id="item-pricing-sale-total" class="font-black text-primary" dir="ltr">$ 0.00</span>
                    </div>
                    <div class="flex items-center justify-between rounded-lg bg-background px-3 py-2">
                        <span class="font-bold text-muted-foreground">إجمالي شراء الأصناف</span>
                        <span id="item-pricing-purchase-total" class="font-black text-foreground" dir="ltr">$ 0.00</span>
                    </div>
                </div>
            </div>

            <div id="land-pricing-section" class="hidden rounded-lg border border-accent/25 bg-accent/5 p-3">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2 font-bold text-primary">
                            <i data-lucide="truck" class="h-4 w-4 text-accent"></i>
                            <span>تسعير الشحن البري حسب المسار</span>
                        </div>
                        <p class="mt-1 text-[11px] text-muted-foreground">أدخل الوزن بالطن وسعر الطن لكل مسار، وسيحسب النظام الإجمالي والربح تلقائياً.</p>
                    </div>
                    <div class="rounded-lg bg-background px-3 py-2 text-left">
                        <div class="text-[10px] font-bold text-muted-foreground">إجمالي البيع البري</div>
                        <div id="land-sale-total" class="font-black text-primary" dir="ltr">{{ \App\Models\Booking::pricingCurrencySymbolFor('USD') }} 0.00</div>
                    </div>
                    <div class="rounded-lg bg-background px-3 py-2 text-left">
                        <div class="text-[10px] font-bold text-muted-foreground">وزن الشحنة</div>
                        <div id="land-weight-display" class="font-black text-primary" dir="ltr">0.00 كجم / 0 طن</div>
                    </div>
                </div>
                <div class="overflow-x-auto rounded-lg border border-border bg-background">
                    <table class="w-full min-w-[680px] text-right text-[11px]">
                        <thead class="bg-secondary/50 text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2">من</th>
                                <th class="px-3 py-2">إلى</th>
                                <th class="px-3 py-2">الوزن بالطن</th>
                                <th class="px-3 py-2">رقم اللوحة</th>
                                <th class="px-3 py-2">سعر الطن الواحد</th>
                                <th class="px-3 py-2">المجموع</th>
                            </tr>
                        </thead>
                        <tbody id="land-pricing-rows" class="divide-y divide-border"></tbody>
                    </table>
                </div>
            </div>

            <div id="door-to-door-pricing-section" class="hidden rounded-lg border border-primary/25 bg-primary/5 p-3">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2 font-bold text-primary">
                            <i data-lucide="package-check" class="h-4 w-4 text-primary"></i>
                            <span>تسعير من الباب إلى الباب</span>
                        </div>
                        <p id="door-to-door-pricing-formula" class="mt-1 text-[11px] text-muted-foreground">{{ __('messages.door_to_door_formula_ton') }}</p>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <div class="rounded-lg bg-background px-3 py-2 text-left">
                            <div class="text-[10px] font-bold text-muted-foreground">الوزن الإجمالي</div>
                            <div id="door-to-door-weight-display" class="font-black text-primary" dir="ltr">0.00 كجم</div>
                        </div>
                        <div class="rounded-lg bg-background px-3 py-2 text-left">
                            <div class="text-[10px] font-bold text-muted-foreground">{{ __('messages.door_to_door_cbm_label') }}</div>
                            <div id="door-to-door-volume-display" class="font-black text-primary" dir="ltr">0.00 CBM</div>
                        </div>
                        <div class="rounded-lg bg-background px-3 py-2 text-left">
                            <div class="text-[10px] font-bold text-muted-foreground">{{ __('messages.door_to_door_quantity_label') }}</div>
                            <div id="door-to-door-pricing-quantity-display" class="font-black text-primary" dir="ltr">0.00 طن</div>
                        </div>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="space-y-1">
                        <label for="doorToDoorPricingBasis" class="font-bold text-foreground">{{ __('messages.door_to_door_pricing_basis_label') }}</label>
                        <select name="doorToDoorPricingBasis" id="doorToDoorPricingBasis" onchange="syncDoorToDoorPurchaseQuantity(); updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                            <option value="ton">{{ __('messages.door_to_door_pricing_basis_ton') }}</option>
                            <option value="cbm">{{ __('messages.door_to_door_pricing_basis_cbm') }}</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="doorToDoorUnitPrice" id="door-to-door-unit-price-label" class="font-bold text-foreground">{{ __('messages.door_to_door_unit_price_label') }}</label>
                        <input type="number" name="doorToDoorUnitPrice" id="doorToDoorUnitPrice" min="0" step="0.01" oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr" />
                    </div>
                    <div class="space-y-1">
                        <label class="font-bold text-foreground">المجموع المحسوب</label>
                        <div id="door-to-door-fixed-total" class="flex h-9 items-center rounded-lg border border-border bg-background px-3 text-xs font-black text-primary" dir="ltr">$ 0.00</div>
                    </div>
                </div>
            </div>

            <div id="fcl-pricing-section" class="hidden rounded-lg border border-accent/25 bg-accent/5 p-3">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2 font-bold text-primary">
                            <i data-lucide="container" class="h-4 w-4 text-accent"></i>
                            <span>تسعير الحاويات المختلطة</span>
                        </div>
                        <p class="mt-1 text-[11px] text-muted-foreground">أدخل سعر الوحدة لكل نوع حاوية، وسيتم حساب المجموع النهائي تلقائياً.</p>
                    </div>
                    <div class="rounded-lg bg-background px-3 py-2 text-left">
                        <div class="text-[10px] font-bold text-muted-foreground">إجمالي سعر الحاويات</div>
                        <div id="fcl-sale-total" class="font-black text-primary" dir="ltr">$ 0.00</div>
                    </div>
                </div>
                <div class="overflow-x-auto rounded-lg border border-border bg-background">
                    <table class="w-full min-w-[520px] text-right text-[11px]">
                        <thead class="bg-secondary/50 text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2">نوع الحاوية</th>
                                <th class="px-3 py-2">الكمية</th>
                                <th class="px-3 py-2">سعر الوحدة</th>
                                <th class="px-3 py-2">المجموع</th>
                            </tr>
                        </thead>
                        <tbody id="fcl-pricing-rows" class="divide-y divide-border"></tbody>
                    </table>
                </div>
            </div>

            <div id="fcl-agent-purchase-section" class="hidden rounded-lg border border-warning/25 bg-warning/5 p-3">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2 font-bold text-warning-foreground">
                            <i data-lucide="shopping-cart" class="h-4 w-4 text-warning-foreground"></i>
                            <span>سعر شراء الحاويات المختلطة</span>
                        </div>
                        <p class="mt-1 text-[11px] text-muted-foreground">أدخل سعر شراء الوحدة لكل نوع حاوية بشكل منفصل، وسيتم جمع الإجمالي النهائي تلقائياً.</p>
                    </div>
                    <div class="rounded-lg bg-background px-3 py-2 text-left">
                        <div class="text-[10px] font-bold text-muted-foreground">إجمالي شراء الحاويات</div>
                        <div id="agent-purchase-lines-total" class="font-black text-warning-foreground" dir="ltr">$ 0.00</div>
                    </div>
                </div>
                <div class="overflow-x-auto rounded-lg border border-border bg-background">
                    <table class="w-full min-w-[520px] text-right text-[11px]">
                        <thead class="bg-secondary/50 text-muted-foreground">
                            <tr>
                                <th class="px-3 py-2">نوع الحاوية</th>
                                <th class="px-3 py-2">الكمية</th>
                                <th class="px-3 py-2">سعر شراء الوحدة</th>
                                <th class="px-3 py-2">المجموع</th>
                            </tr>
                        </thead>
                        <tbody id="agent-purchase-rows" class="divide-y divide-border"></tbody>
                    </table>
                </div>
            </div>
            
            <div class="grid gap-3 sm:grid-cols-2">
                <div class="space-y-1">
                    <label for="oceanFreight" id="oceanFreightLabel" class="font-bold text-foreground">{{ __('messages.pricing_ocean_freight_label') }}</label>
                    <input type="number" name="oceanFreight" id="oceanFreight" required step="0.01" min="0" data-standard-pricing-input oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="originCharges" class="font-bold text-foreground">{{ __('messages.pricing_origin_charges_label') }}</label>
                    <input type="number" name="originCharges" id="originCharges" required step="0.01" min="0" data-standard-pricing-input oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1 hidden" data-exw-charge-field>
                    <label for="exwCharges" class="font-bold text-foreground">{{ __('messages.pricing_exw_charges_label') }}</label>
                    <input type="number" name="exwCharges" id="exwCharges" value="0" step="0.01" min="0" data-standard-pricing-input data-standard-pricing-required="false" oninput="updateProfitPreview()" placeholder="0.00" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="dthc" class="font-bold text-foreground">{{ __('messages.pricing_dthc_label') }}</label>
                    <input type="number" name="dthc" id="dthc" required step="0.01" min="0" data-standard-pricing-input oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="warRiskSurcharge" class="font-bold text-foreground">{{ __('messages.pricing_war_risk_label') }}</label>
                    <input type="number" name="warRiskSurcharge" id="warRiskSurcharge" required step="0.01" min="0" data-standard-pricing-input oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="otherCharges" class="font-bold text-foreground">{{ __('messages.pricing_other_charges_label') }}</label>
                    <input type="number" name="otherCharges" id="otherCharges" required step="0.01" min="0" data-standard-pricing-input oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="destinationPortChangeFee" class="font-bold text-foreground">{{ __('messages.pricing_destination_port_change_fee_label') }}</label>
                    <input type="number" name="destinationPortChangeFee" id="destinationPortChangeFee" value="0" step="0.01" min="0" data-standard-pricing-input data-standard-pricing-required="false" oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="otherPenalties" class="font-bold text-foreground">{{ __('messages.pricing_other_penalties_label') }}</label>
                    <input type="number" name="otherPenalties" id="otherPenalties" value="0" step="0.01" min="0" data-standard-pricing-input data-standard-pricing-required="false" oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1">
                    <label for="transitDays" class="font-bold text-foreground">{{ __('messages.pricing_transit_days_label') }}</label>
                    <input type="number" name="transitDays" id="transitDays" required placeholder="{{ __('messages.pricing_transit_days_example') }}" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="validityDate" class="font-bold text-foreground">{{ __('messages.pricing_validity_date_label') }}</label>
                    <input type="date" name="validityDate" id="validityDate" required class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                </div>
                <div class="sm:col-span-2 rounded-lg border border-accent/20 bg-accent/5 p-3">
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <div class="flex items-center gap-2 font-bold text-primary">
                                <i data-lucide="list-plus" class="h-4 w-4 text-accent"></i>
                                <span>بنود إضافية في عرض السعر</span>
                            </div>
                            <p class="mt-1 text-[11px] text-muted-foreground">أضف أي بند خاص بهذا الطلب وسيظهر بنفس الاسم في عرض السعر والفاتورة ويضاف للإجمالي.</p>
                        </div>
                        <button type="button" onclick="addCustomPricingChargeRow()" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-accent/30 bg-accent/10 px-3 text-[11px] font-black text-accent transition hover:bg-accent/15">
                            <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                            <span>إضافة بند</span>
                        </button>
                    </div>
                    <div id="custom-pricing-charges-empty" class="rounded-lg border border-dashed border-border bg-background/70 px-3 py-3 text-center text-[11px] font-bold text-muted-foreground">
                        لا توجد بنود إضافية.
                    </div>
                    <div id="custom-pricing-charges-list" class="space-y-2"></div>
                    <div class="mt-3 flex items-center justify-between rounded-lg bg-background px-3 py-2 text-[11px]">
                        <span class="font-bold text-muted-foreground">إجمالي البنود الإضافية</span>
                        <span id="custom-pricing-charges-total" class="font-black text-primary" dir="ltr">$ 0.00</span>
                    </div>
                </div>
                <div class="sm:col-span-2 rounded-lg border border-border bg-secondary/25 p-3">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2 font-bold text-primary">
                            <i data-lucide="handshake" class="h-4 w-4 text-accent"></i>
                            <span>{{ __('messages.pricing_agent_purchase_header') }}</span>
                        </div>
                        <span class="text-[10px] font-bold text-muted-foreground">{{ __('messages.pricing_agent_purchase_hint') }}</span>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="space-y-1">
                            <label for="agent-id" class="font-bold text-foreground">{{ __('messages.pricing_agent_label') }}</label>
                            <select name="agent_id" id="agent-id" required class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                                <option value="">-- {{ __('messages.pricing_choose_agent') }} --</option>
                                @foreach($agents as $agent)
                                    <option value="{{ $agent->id }}">{{ $agent->name }} — {{ $agent->type === 'local' ? __('messages.agent_local_type') : __('messages.agent_external_type') }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-1" data-agent-standard-purchase-field>
                            <label for="agentPurchaseUnitPrice" id="agentPurchaseUnitPriceLabel" class="font-bold text-foreground">{{ __('messages.pricing_agent_purchase_unit_label') }}</label>
                            <input type="number" name="agentPurchaseUnitPrice" id="agentPurchaseUnitPrice" required step="0.01" min="0" oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                            <input type="hidden" name="agentPurchaseAmount" id="agentPurchaseAmount" value="0" />
                        </div>

                        <div class="space-y-1" data-agent-standard-purchase-field>
                            <label for="agentPurchaseQuantity" id="agentPurchaseQuantityLabel" class="font-bold text-foreground">{{ __('messages.pricing_agent_purchase_quantity_label') }}</label>
                            <input type="number" name="agentPurchaseQuantity" id="agentPurchaseQuantity" required step="0.0001" min="0.01" placeholder="{{ __('messages.pricing_agent_quantity_example') }}" oninput="updateProfitPreview()" class="w-full h-9 px-3 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent" />
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-4">
                        <div>
                            <div class="text-[10px] font-bold text-muted-foreground">{{ __('messages.pricing_sale_total_label') }}</div>
                            <div id="profit-sale-total" class="font-black text-primary" dir="ltr">{{ \App\Models\Booking::pricingCurrencySymbolFor('USD') }} 0.00</div>
                        </div>
                        <div>
                            <div id="profit-purchase-unit-label" class="text-[10px] font-bold text-muted-foreground">{{ __('messages.pricing_unit_price_label') }}</div>
                            <div id="profit-purchase-unit" class="font-black text-warning-foreground" dir="ltr">{{ \App\Models\Booking::pricingCurrencySymbolFor('USD') }} 0.00</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-muted-foreground">{{ __('messages.pricing_purchase_total_label') }}</div>
                            <div id="profit-purchase-total" class="font-black text-warning-foreground" dir="ltr">{{ \App\Models\Booking::pricingCurrencySymbolFor('USD') }} 0.00</div>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold text-muted-foreground">{{ __('messages.pricing_profit_label') }}</div>
                            <div id="profit-preview" class="font-black text-success" dir="ltr">{{ \App\Models\Booking::pricingCurrencySymbolFor('USD') }} 0.00</div>
                        </div>
                    </div>

                    <div class="mt-3 space-y-1">
                        <label for="agentPurchaseNotes" class="font-bold text-foreground">{{ __('messages.pricing_agent_purchase_notes_label') }}</label>
                        <textarea name="agentPurchaseNotes" id="agentPurchaseNotes" rows="2" class="w-full p-2.5 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                    </div>
                </div>
                <div class="space-y-1 sm:col-span-2">
                    <label for="notes" class="font-bold text-foreground">{{ __('messages.pricing_quote_notes_label') }}</label>
                    <textarea name="notes" id="notes" rows="2" class="w-full p-2.5 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 pt-4 border-t">
                <button type="button" onclick="closePricingFormModal()" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">{{ __('messages.bookings_cancel') }}</button>
                <button type="submit" id="pricing-submit-button" class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-accent px-5 text-xs font-bold text-accent-foreground shadow hover:opacity-95">{{ __('messages.pricing_confirm_and_forward') }}</button>
            </div>
        </form>
    </div>
</div>

@include('partials.booking-detail-modal')
@endsection

@section('scripts')
@include('partials.booking-filters-script')
<script>
    const pricingModal = document.getElementById('pricing-modal');
    const pricingForm = document.getElementById('pricing-form');
    let currentPricingMode = 'standard';
    let currentPricingIsExwSea = false;
    let customPricingChargeIndex = 0;
    let itemPricingIndex = 0;

    function pricingNumber(id) {
        return parseFloat(document.getElementById(id)?.value || 0) || 0;
    }

    function setPricingInputValue(id, value, fallback = '') {
        const input = document.getElementById(id);
        if (!input) return;

        input.value = value === null || value === undefined ? fallback : value;
    }

    function formatPricingMoney(value) {
        return '$ ' + (value || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    const doorToDoorPricingLabels = {
        ton: {
            unit: @js(__('messages.cargo_unit_ton')),
            unitPrice: @js(__('messages.door_to_door_unit_price_label')),
            purchaseUnit: @js(__('messages.door_to_door_unit_price_label')),
            purchaseQuantity: @js(__('messages.door_to_door_tons_label')),
            formula: @js(__('messages.door_to_door_formula_ton')),
        },
        cbm: {
            unit: @js(__('messages.cargo_unit_cbm')),
            unitPrice: @js(__('messages.door_to_door_unit_price_per_cbm_label')),
            purchaseUnit: @js(__('messages.door_to_door_unit_price_per_cbm_label')),
            purchaseQuantity: @js(__('messages.door_to_door_cbm_label')),
            formula: @js(__('messages.door_to_door_formula_cbm')),
        },
    };

    function selectedDoorToDoorPricingBasis() {
        return document.getElementById('doorToDoorPricingBasis')?.value === 'cbm' ? 'cbm' : 'ton';
    }

    function doorToDoorPricingQuantity(basis = selectedDoorToDoorPricingBasis()) {
        if (basis === 'cbm') {
            return Number(window.currentDoorToDoorVolumeCbm || 0);
        }

        return Number(window.currentDoorToDoorWeightKg || 0) / 1000;
    }

    function refreshDoorToDoorPricingUi() {
        const basis = selectedDoorToDoorPricingBasis();
        const labels = doorToDoorPricingLabels[basis];
        const grossWeightKg = Number(window.currentDoorToDoorWeightKg || 0);
        const volumeCbm = Number(window.currentDoorToDoorVolumeCbm || 0);
        const quantity = doorToDoorPricingQuantity(basis);
        const quantityText = quantity.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 4 });
        const unitPriceLabel = document.getElementById('door-to-door-unit-price-label');
        const formula = document.getElementById('door-to-door-pricing-formula');
        const weightDisplay = document.getElementById('door-to-door-weight-display');
        const volumeDisplay = document.getElementById('door-to-door-volume-display');
        const quantityDisplay = document.getElementById('door-to-door-pricing-quantity-display');

        if (unitPriceLabel) unitPriceLabel.textContent = labels.unitPrice;
        if (formula) formula.textContent = labels.formula;
        if (weightDisplay) weightDisplay.textContent = `${grossWeightKg.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} @js(__('messages.kg'))`;
        if (volumeDisplay) volumeDisplay.textContent = `${volumeCbm.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 })} CBM`;
        if (quantityDisplay) quantityDisplay.textContent = `${quantityText} ${labels.unit}`;

        if (currentPricingMode === 'door_to_door') {
            const purchaseUnitLabel = document.getElementById('agentPurchaseUnitPriceLabel');
            const purchaseQuantityLabel = document.getElementById('agentPurchaseQuantityLabel');
            const profitPurchaseUnitLabel = document.getElementById('profit-purchase-unit-label');

            if (purchaseUnitLabel) purchaseUnitLabel.textContent = labels.purchaseUnit;
            if (purchaseQuantityLabel) purchaseQuantityLabel.textContent = labels.purchaseQuantity;
            if (profitPurchaseUnitLabel) profitPurchaseUnitLabel.textContent = labels.purchaseUnit;
        }

        return { basis, quantity, labels };
    }

    function syncDoorToDoorPurchaseQuantity() {
        const { quantity } = refreshDoorToDoorPricingUi();
        const purchaseQuantity = document.getElementById('agentPurchaseQuantity');

        if (purchaseQuantity && quantity > 0) {
            purchaseQuantity.value = Number(quantity.toFixed(4)).toString();
        }
    }

    function escapePricingHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function syncCustomPricingChargesEmptyState() {
        const list = document.getElementById('custom-pricing-charges-list');
        const empty = document.getElementById('custom-pricing-charges-empty');

        if (!list || !empty) return;

        empty.classList.toggle('hidden', list.querySelectorAll('[data-custom-pricing-charge-row]').length > 0);
    }

    function customPricingChargesTotal() {
        let total = 0;

        document.querySelectorAll('[data-custom-pricing-charge-row]').forEach((row) => {
            total += parseFloat(row.querySelector('[data-custom-pricing-charge-amount]')?.value || 0) || 0;
        });

        const totalEl = document.getElementById('custom-pricing-charges-total');
        if (totalEl) totalEl.textContent = formatPricingMoney(total);

        syncCustomPricingChargesEmptyState();

        return total;
    }

    function addCustomPricingChargeRow(label = '', amount = '') {
        const list = document.getElementById('custom-pricing-charges-list');
        if (!list) return;

        const index = customPricingChargeIndex++;
        const row = document.createElement('div');
        row.className = 'grid gap-2 rounded-lg border border-border bg-background p-2 sm:grid-cols-[1fr_10rem_2.25rem]';
        row.dataset.customPricingChargeRow = 'true';
        row.innerHTML = `
            <input type="text" name="customPricingCharges[${index}][label]" value="${escapePricingHtml(label)}" required maxlength="120" placeholder="اسم البند الذي سيظهر للعميل" oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-3 text-xs focus:outline-none focus:ring-1 focus:ring-accent">
            <input type="number" name="customPricingCharges[${index}][amount]" value="${escapePricingHtml(amount)}" required min="0" step="0.01" placeholder="المبلغ" data-custom-pricing-charge-amount oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-3 text-xs focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
            <button type="button" onclick="removeCustomPricingChargeRow(this)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border text-muted-foreground transition hover:border-destructive/30 hover:bg-destructive/10 hover:text-destructive" title="حذف البند">
                <i data-lucide="trash-2" class="h-4 w-4"></i>
            </button>
        `;

        list.appendChild(row);

        if (window.lucide) {
            window.lucide.createIcons();
        }

        updateProfitPreview();
    }

    function removeCustomPricingChargeRow(button) {
        button.closest('[data-custom-pricing-charge-row]')?.remove();
        updateProfitPreview();
    }

    function clearCustomPricingChargeRows() {
        const list = document.getElementById('custom-pricing-charges-list');
        if (list) {
            list.innerHTML = '';
        }

        customPricingChargeIndex = 0;
        customPricingChargesTotal();
    }

    function itemPricingRows() {
        return Array.from(document.querySelectorAll('[data-item-pricing-row]'));
    }

    function itemPricingTotals() {
        let sale = 0;
        let purchase = 0;
        let quantity = 0;

        itemPricingRows().forEach(row => {
            const qty = parseFloat(row.querySelector('[data-item-pricing-qty]')?.value || 0) || 0;
            const saleUnit = parseFloat(row.querySelector('[data-item-pricing-sale-unit]')?.value || 0) || 0;
            const purchaseUnit = parseFloat(row.querySelector('[data-item-pricing-purchase-unit]')?.value || 0) || 0;
            const saleTotal = qty * saleUnit;
            const purchaseTotal = qty * purchaseUnit;

            quantity += qty;
            sale += saleTotal;
            purchase += purchaseTotal;
            if (row.querySelector('[data-item-pricing-sale-total]')) row.querySelector('[data-item-pricing-sale-total]').textContent = formatPricingMoney(saleTotal);
            if (row.querySelector('[data-item-pricing-purchase-total]')) row.querySelector('[data-item-pricing-purchase-total]').textContent = formatPricingMoney(purchaseTotal);
        });

        const saleEl = document.getElementById('item-pricing-sale-total');
        const purchaseEl = document.getElementById('item-pricing-purchase-total');
        if (saleEl) saleEl.textContent = formatPricingMoney(sale);
        if (purchaseEl) purchaseEl.textContent = formatPricingMoney(purchase);

        return { sale, purchase, quantity, purchaseUnit: quantity > 0 ? purchase / quantity : 0 };
    }

    function addItemPricingRow(item = {}) {
        const body = document.getElementById('item-pricing-rows');
        if (!body) return;

        const index = itemPricingIndex++;
        const unit = item.unit || 'piece';
        const unitOptions = [
            ['piece', 'قطعة'],
            ['vehicle', 'مركبة'],
            ['cbm', 'CBM'],
            ['kg', 'كجم'],
            ['ton', 'طن'],
            ['container', 'حاوية'],
        ].map(([value, label]) => `<option value="${value}" ${unit === value ? 'selected' : ''}>${label}</option>`).join('');
        const row = document.createElement('tr');
        row.dataset.itemPricingRow = 'true';
        row.innerHTML = `
            <td class="p-2"><input type="text" name="itemPricingLines[${index}][name]" value="${escapePricingHtml(item.name || '')}" required maxlength="255" placeholder="اسم الصنف" oninput="updateProfitPreview()" class="h-9 w-full rounded-md border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2"><input type="text" name="itemPricingLines[${index}][hs_code]" value="${escapePricingHtml(item.hs_code || '')}" maxlength="40" placeholder="HS CODE" dir="ltr" class="h-9 w-full rounded-md border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2"><input type="number" name="itemPricingLines[${index}][quantity]" value="${escapePricingHtml(item.quantity || item.qty || 1)}" required min="0.01" step="0.0001" data-item-pricing-qty oninput="updateProfitPreview()" dir="ltr" class="h-9 w-full rounded-md border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2"><select name="itemPricingLines[${index}][unit]" required onchange="updateProfitPreview()" class="h-9 w-full rounded-md border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent">${unitOptions}</select></td>
            <td class="p-2"><input type="number" name="itemPricingLines[${index}][sale_unit_price]" value="${escapePricingHtml(item.sale_unit_price ?? '')}" required min="0" step="0.01" data-item-pricing-sale-unit oninput="updateProfitPreview()" dir="ltr" class="h-9 w-full rounded-md border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2"><input type="number" name="itemPricingLines[${index}][purchase_unit_price]" value="${escapePricingHtml(item.purchase_unit_price ?? '')}" required min="0" step="0.01" data-item-pricing-purchase-unit oninput="updateProfitPreview()" dir="ltr" class="h-9 w-full rounded-md border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2 text-left font-black text-primary" data-item-pricing-sale-total dir="ltr">${formatPricingMoney(0)}</td>
            <td class="p-2 text-left font-black text-foreground" data-item-pricing-purchase-total dir="ltr">${formatPricingMoney(0)}</td>
            <td class="p-2 text-center"><button type="button" onclick="removeItemPricingRow(this)" class="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" title="حذف الصنف"><i data-lucide="trash-2" class="h-4 w-4"></i></button></td>
        `;
        body.appendChild(row);
        window.lucide?.createIcons();
        updateProfitPreview();
    }

    function removeItemPricingRow(button) {
        if (itemPricingRows().length <= 1) return;
        button.closest('[data-item-pricing-row]')?.remove();
        updateProfitPreview();
    }

    function renderItemPricingRows(items = []) {
        const body = document.getElementById('item-pricing-rows');
        if (!body) return;
        body.innerHTML = '';
        itemPricingIndex = 0;
        const rows = Array.isArray(items) && items.length ? items : [{ quantity: 1, unit: 'piece' }];
        rows.forEach(item => addItemPricingRow(item));
    }

    function setAgentStandardPurchaseVisibility(isVisible) {
        document.querySelectorAll('[data-agent-standard-purchase-field]').forEach(field => {
            field.classList.toggle('hidden', !isVisible);
            field.querySelectorAll('input').forEach(input => {
                input.disabled = !isVisible;
                input.required = isVisible;
            });
        });
    }

    function landPricingRowsTotal() {
        let total = 0;
        document.querySelectorAll('[data-land-pricing-row]').forEach((row) => {
            const qty = parseFloat(row.querySelector('[data-land-pricing-qty]')?.value || 0) || 0;
            const unitPrice = parseFloat(row.querySelector('[data-land-pricing-unit]')?.value || 0) || 0;
            const lineTotal = qty * unitPrice;
            total += lineTotal;

            const totalEl = row.querySelector('[data-land-pricing-total]');
            if (totalEl) totalEl.textContent = formatPricingMoney(lineTotal);
        });

        const landTotalEl = document.getElementById('land-sale-total');
        if (landTotalEl) landTotalEl.textContent = formatPricingMoney(total);

        return total;
    }

    function landPricingRowsQuantity() {
        let quantity = 0;
        document.querySelectorAll('[data-land-pricing-row]').forEach((row) => {
            quantity += parseFloat(row.querySelector('[data-land-pricing-qty]')?.value || 0) || 0;
        });

        return quantity;
    }

    function fclPricingRowsTotal() {
        let total = 0;
        document.querySelectorAll('[data-fcl-pricing-row]').forEach((row) => {
            const qty = parseFloat(row.querySelector('[data-fcl-pricing-qty]')?.value || 0) || 0;
            const unitPrice = parseFloat(row.querySelector('[data-fcl-pricing-unit]')?.value || 0) || 0;
            const lineTotal = qty * unitPrice;
            total += lineTotal;

            const totalEl = row.querySelector('[data-fcl-pricing-total]');
            if (totalEl) totalEl.textContent = formatPricingMoney(lineTotal);
        });

        const totalEl = document.getElementById('fcl-sale-total');
        if (totalEl) totalEl.textContent = formatPricingMoney(total);

        return total;
    }

    function agentPurchaseRowsTotal() {
        let total = 0;
        document.querySelectorAll('[data-agent-purchase-row]').forEach((row) => {
            const qty = parseFloat(row.querySelector('[data-agent-purchase-qty]')?.value || 0) || 0;
            const unitPrice = parseFloat(row.querySelector('[data-agent-purchase-unit]')?.value || 0) || 0;
            const lineTotal = qty * unitPrice;
            total += lineTotal;

            const totalEl = row.querySelector('[data-agent-purchase-total]');
            if (totalEl) totalEl.textContent = formatPricingMoney(lineTotal);
        });

        const totalEl = document.getElementById('agent-purchase-lines-total');
        if (totalEl) totalEl.textContent = formatPricingMoney(total);

        const purchaseAmountInput = document.getElementById('agentPurchaseAmount');
        if (purchaseAmountInput) purchaseAmountInput.value = total.toFixed(2);

        return total;
    }

    function renderFclPricingRows(containers = []) {
        const body = document.getElementById('fcl-pricing-rows');
        if (!body) return;

        const rows = Array.isArray(containers) && containers.length
            ? containers
            : [];

        body.innerHTML = rows.map((row, index) => {
            const qty = parseFloat(row.qty || row.quantity || 1) || 1;
            const type = row.type || row.container_type || `FCL-${index + 1}`;
            const unitPrice = row.unit_price ?? row.price ?? '';

            return `
                <tr data-fcl-pricing-row>
                    <td class="px-2 py-2">
                        <input type="hidden" name="fclPricingLines[${index}][type]" value="${escapePricingHtml(type)}">
                        <div class="flex h-9 items-center rounded-lg border border-border bg-card px-2 text-xs font-bold text-primary">${escapePricingHtml(type)}</div>
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="fclPricingLines[${index}][qty]" value="${qty}" min="0.01" step="0.01" required data-fcl-pricing-qty oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="fclPricingLines[${index}][unit_price]" value="${escapePricingHtml(unitPrice)}" min="0" step="0.01" required data-fcl-pricing-unit oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                    </td>
                    <td class="px-2 py-2 text-left font-black text-primary" data-fcl-pricing-total dir="ltr">${formatPricingMoney(0)}</td>
                </tr>
            `;
        }).join('');

        updateProfitPreview();
    }

    function renderAgentPurchaseRows(containers = []) {
        const body = document.getElementById('agent-purchase-rows');
        if (!body) return;

        const rows = Array.isArray(containers) && containers.length ? containers : [{ qty: 1, type: '40HC' }];

        body.innerHTML = rows.map((row, index) => {
            const qty = parseFloat(row.qty || row.quantity || 1) || 1;
            const type = row.type || row.container_type || `FCL-${index + 1}`;
            const unitPrice = row.unit_price ?? row.price ?? '';

            return `
                <tr data-agent-purchase-row>
                    <td class="px-2 py-2">
                        <input type="hidden" name="agentPurchaseLines[${index}][type]" value="${escapePricingHtml(type)}">
                        <div class="flex h-9 items-center rounded-lg border border-border bg-card px-2 text-xs font-bold text-warning-foreground">${escapePricingHtml(type)}</div>
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="agentPurchaseLines[${index}][qty]" value="${qty}" min="0.01" step="0.01" required data-agent-purchase-qty oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="agentPurchaseLines[${index}][unit_price]" value="${escapePricingHtml(unitPrice)}" min="0" step="0.01" required data-agent-purchase-unit oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                    </td>
                    <td class="px-2 py-2 text-left font-black text-warning-foreground" data-agent-purchase-total dir="ltr">${formatPricingMoney(0)}</td>
                </tr>
            `;
        }).join('');

        updateProfitPreview();
    }

    function renderLandPricingRows(shipments = [], pol = '', pod = '', grossWeightKg = 0) {
        const body = document.getElementById('land-pricing-rows');
        if (!body) return;

        const rows = Array.isArray(shipments) && shipments.length
            ? shipments
            : [{ qty: 1, from_city: pol, to_city: pod }];
        const totalTons = grossWeightKg > 0 ? grossWeightKg / 1000 : 0;
        const routeTotalQty = rows.reduce((sum, row) => {
            const routeQty = parseFloat(row.truck_qty || row.route_qty || row.qty || 0) || 0;
            return sum + routeQty;
        }, 0);
        const formatTonInput = (value) => {
            const number = parseFloat(value || 0) || 0;

            if (number <= 0) return '';

            return Number(number.toFixed(4)).toString();
        };

        body.innerHTML = rows.map((row, index) => {
            const routeQty = parseFloat(row.truck_qty || row.route_qty || row.qty || 1) || 1;
            const hasPricedTons = row.unit_price !== undefined || row.total !== undefined || row.tons !== undefined || row.weight_tons !== undefined;
            const tons = hasPricedTons
                ? (parseFloat(row.tons || row.weight_tons || row.qty || 0) || 0)
                : (totalTons > 0 && routeTotalQty > 0 ? (totalTons * routeQty / routeTotalQty) : routeQty);
            const fromCity = row.from_city || row.origin_city || pol || '';
            const toCity = row.to_city || row.destination_city || pod || '';
            const plateNumber = row.plate_number || '';
            const unitPrice = row.unit_price ?? row.price ?? '';

            return `
                <tr data-land-pricing-row>
                    <td class="px-2 py-2">
                        <input type="text" name="landPricingLines[${index}][from_city]" value="${escapePricingHtml(fromCity)}" required oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                    </td>
                    <td class="px-2 py-2">
                        <input type="text" name="landPricingLines[${index}][to_city]" value="${escapePricingHtml(toCity)}" required oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                    </td>
                    <td class="px-2 py-2">
                        <input type="hidden" name="landPricingLines[${index}][truck_qty]" value="${formatTonInput(routeQty)}">
                        <input type="number" name="landPricingLines[${index}][qty]" value="${formatTonInput(tons)}" min="0.01" step="0.0001" required data-land-pricing-qty oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                    </td>
                    <td class="px-2 py-2">
                        <input type="text" name="landPricingLines[${index}][plate_number]" value="${escapePricingHtml(plateNumber)}" placeholder="اختياري" class="h-9 w-full rounded-lg border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                    </td>
                    <td class="px-2 py-2">
                        <input type="number" name="landPricingLines[${index}][unit_price]" value="${escapePricingHtml(unitPrice)}" min="0" step="0.01" required data-land-pricing-unit oninput="updateProfitPreview()" class="h-9 w-full rounded-lg border border-border bg-card px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                    </td>
                    <td class="px-2 py-2 text-left font-black text-primary" data-land-pricing-total dir="ltr">${formatPricingMoney(0)}</td>
                </tr>
            `;
        }).join('');

        updateProfitPreview();
    }

    function setStandardPricingVisibility(isVisible) {
        document.querySelectorAll('[data-standard-pricing-input]').forEach((input) => {
            const shouldDisplay = isVisible || currentPricingMode === 'fcl';
            const canRequire = input.dataset.standardPricingRequired !== 'false';
            const shouldRequire = canRequire && isVisible && currentPricingMode !== 'fcl';

            input.required = shouldRequire;
            input.disabled = !shouldDisplay;
            input.closest('.space-y-1')?.classList.toggle('hidden', !shouldDisplay);
        });
    }

    function setExwChargesVisibility(isVisible) {
        const exwInput = document.getElementById('exwCharges');
        const exwField = document.querySelector('[data-exw-charge-field]');

        if (!exwInput || !exwField) return;

        const shouldShow = isVisible && ['standard', 'fcl'].includes(currentPricingMode);
        exwInput.disabled = !shouldShow;
        exwInput.required = false;
        exwField.classList.toggle('hidden', !shouldShow);

        if (!shouldShow) {
            exwInput.value = 0;
        }
    }

    function updateProfitPreview() {
        const saleUnitTotal = currentPricingMode === 'land'
            ? 0
            : pricingNumber('oceanFreight')
                + pricingNumber('originCharges')
                + pricingNumber('exwCharges')
                + pricingNumber('dthc')
                + pricingNumber('warRiskSurcharge')
                + pricingNumber('otherCharges')
                + pricingNumber('destinationPortChangeFee')
                + pricingNumber('otherPenalties');
        const purchaseUnitPrice = pricingNumber('agentPurchaseUnitPrice');
        const purchaseQuantity = pricingNumber('agentPurchaseQuantity');
        const customChargesTotal = customPricingChargesTotal();
        let saleTotal = 0;
        let purchaseTotal = 0;

        let effectivePurchaseUnitPrice = purchaseUnitPrice;

        if (currentPricingMode === 'itemized') {
            const totals = itemPricingTotals();
            saleTotal = totals.sale + customChargesTotal;
            purchaseTotal = totals.purchase;
            effectivePurchaseUnitPrice = totals.purchaseUnit;
        } else if (currentPricingMode === 'land') {
            saleTotal = landPricingRowsTotal() + customChargesTotal;
            purchaseTotal = purchaseUnitPrice * purchaseQuantity;
        } else if (currentPricingMode === 'door_to_door') {
            const { quantity } = refreshDoorToDoorPricingUi();
            const unitPrice = pricingNumber('doorToDoorUnitPrice');
            saleTotal = (quantity * unitPrice) + customChargesTotal;
            purchaseTotal = purchaseUnitPrice * purchaseQuantity;
            const doorTotalEl = document.getElementById('door-to-door-fixed-total');
            if (doorTotalEl) doorTotalEl.textContent = formatPricingMoney(quantity * unitPrice);
        } else if (currentPricingMode === 'fcl') {
            const mixedFclQty = Array.from(document.querySelectorAll('[data-fcl-pricing-row]')).reduce((sum, row) => {
                const qty = parseFloat(row.querySelector('[data-fcl-pricing-qty]')?.value || 0) || 0;
                return sum + qty;
            }, 0) || purchaseQuantity || 1;
            const standardFclCharges = (pricingNumber('oceanFreight') + pricingNumber('originCharges') + pricingNumber('exwCharges') + pricingNumber('dthc') + pricingNumber('warRiskSurcharge') + pricingNumber('otherCharges') + pricingNumber('destinationPortChangeFee') + pricingNumber('otherPenalties')) * mixedFclQty;
            saleTotal = fclPricingRowsTotal() + standardFclCharges + customChargesTotal;
            purchaseTotal = agentPurchaseRowsTotal();
        } else {
            saleTotal = (saleUnitTotal * purchaseQuantity) + customChargesTotal;
            purchaseTotal = purchaseUnitPrice * purchaseQuantity;
        }

        const profit = saleTotal - purchaseTotal;
        const profitEl = document.getElementById('profit-preview');
        const purchaseTotalInput = document.getElementById('agentPurchaseAmount');

        if (purchaseTotalInput) purchaseTotalInput.value = purchaseTotal.toFixed(2);

        document.getElementById('profit-sale-total').textContent = formatPricingMoney(saleTotal);
        document.getElementById('profit-purchase-unit').textContent = formatPricingMoney(effectivePurchaseUnitPrice);
        document.getElementById('profit-purchase-total').textContent = formatPricingMoney(purchaseTotal);
        profitEl.textContent = formatPricingMoney(profit);
        profitEl.classList.toggle('text-success', profit >= 0);
        profitEl.classList.toggle('text-destructive', profit < 0);
    }

    function openPricingFormModal(payloadOrId, pol = '', pod = '', cargo = '', containerQty = 1) {
        const payload = typeof payloadOrId === 'object'
            ? payloadOrId
            : { id: payloadOrId, pol, pod, cargo, quantity: containerQty, isLand: false, landShipments: [] };
        const id = payload.id;
        const routePol = payload.pol || '';
        const routePod = payload.pod || '';
        const cargoLabel = payload.cargo || '';
        const quantity = parseFloat(payload.quantity || 1) || 1;
        const isLand = Boolean(payload.isLand);
        const isDoorToDoor = Boolean(payload.isDoorToDoor);
        const isAir = Boolean(payload.isAir);
        const isBulk = Boolean(payload.isBulk);
        const isRoRo = Boolean(payload.isRoRo);
        const isItemized = isAir || isBulk || isRoRo;
        const isExwSea = Boolean(payload.isExwSea);
        const isFclMixed = Array.isArray(payload.fclContainerRows) && payload.fclContainerRows.length > 1;
        const grossWeightKg = Number(payload.weightKg || payload.weight_kg || 0);
        const isEdit = Boolean(payload.isEdit);
        const pricing = payload.pricing || {};

        if (!pricingModal) return;
        pricingModal.classList.remove('hidden');
        document.getElementById('price-booking-id').innerText = id;
        setPricingInputValue('pricingEditMode', isEdit ? 1 : 0);
        document.getElementById('pricing-modal-title').innerText = isEdit ? 'تعديل سعر العرض' : @js(__('messages.pricing_modal_title'));
        document.getElementById('pricing-submit-button').innerText = isEdit ? 'حفظ تعديل السعر' : @js(__('messages.pricing_confirm_and_forward'));
        // expose current pricing booking id for agent modal
        window.currentPricingBookingId = id;
        window.currentDoorToDoorWeightKg = grossWeightKg;
        window.currentDoorToDoorVolumeCbm = Number(payload.volumeCbm || 0);
        window.currentLandWeightKg = grossWeightKg;
        document.getElementById('price-route').innerText = `${routePol} → ${routePod}`;
        document.getElementById('price-cargo').innerText = cargoLabel;
        const hsCodeWrap = document.getElementById('price-hs-code-wrap');
        const hsCode = document.getElementById('price-hs-code');
        if (hsCode) hsCode.innerText = payload.hsCode || 'غير محدد';
        if (hsCodeWrap) hsCodeWrap.classList.toggle('hidden', !payload.hsCode);
        currentPricingMode = isLand ? 'land' : (isDoorToDoor ? 'door_to_door' : (isItemized ? 'itemized' : (isFclMixed ? 'fcl' : 'standard')));
        currentPricingIsExwSea = isExwSea;
        setPricingInputValue('doorToDoorPricingBasis', pricing.doorToDoorPricingBasis || 'ton');
        setStandardPricingVisibility(!isLand && !isDoorToDoor && !isFclMixed && !isItemized);
        setAgentStandardPurchaseVisibility(!isItemized);
        setExwChargesVisibility(currentPricingIsExwSea);
        const purchaseUnitLabel = document.getElementById('agentPurchaseUnitPriceLabel');
        const purchaseQuantityLabel = document.getElementById('agentPurchaseQuantityLabel');
        const profitPurchaseUnitLabel = document.getElementById('profit-purchase-unit-label');
        const doorToDoorUsesCbm = isDoorToDoor && selectedDoorToDoorPricingBasis() === 'cbm';
        const tonPricingMode = isLand || (isDoorToDoor && !doorToDoorUsesCbm);
        const cbmPricingMode = isAir || doorToDoorUsesCbm;
        if (purchaseUnitLabel) purchaseUnitLabel.textContent = tonPricingMode ? 'سعر شراء الطن' : (cbmPricingMode ? 'سعر شراء CBM' : @js(__('messages.pricing_agent_purchase_unit_label')));
        if (purchaseQuantityLabel) purchaseQuantityLabel.textContent = tonPricingMode ? 'وزن الشراء بالطن' : (cbmPricingMode ? 'كمية الشراء (CBM)' : @js(__('messages.pricing_agent_purchase_quantity_label')));
        if (profitPurchaseUnitLabel) profitPurchaseUnitLabel.textContent = tonPricingMode ? 'سعر شراء الطن' : (cbmPricingMode ? 'سعر شراء CBM' : @js(__('messages.pricing_unit_price_label')));
        const oceanFreightLabel = document.getElementById('oceanFreightLabel');
        if (oceanFreightLabel) oceanFreightLabel.textContent = isAir ? 'سعر الشحن الجوي لكل CBM' : @js(__('messages.pricing_ocean_freight_label'));

        const landSection = document.getElementById('land-pricing-section');
        const doorSection = document.getElementById('door-to-door-pricing-section');
        const itemSection = document.getElementById('item-pricing-section');
        const fclSection = document.getElementById('fcl-pricing-section');
        const fclPurchaseSection = document.getElementById('fcl-agent-purchase-section');
        if (landSection) landSection.classList.toggle('hidden', !isLand);
        if (doorSection) doorSection.classList.toggle('hidden', !isDoorToDoor);
        if (itemSection) {
            itemSection.classList.toggle('hidden', !isItemized);
            itemSection.querySelectorAll('input, select').forEach(input => {
                input.disabled = !isItemized;
            });
        }
        if (fclSection) fclSection.classList.toggle('hidden', !isFclMixed);
        if (fclPurchaseSection) fclPurchaseSection.classList.toggle('hidden', !isFclMixed);
        if (!isItemized) {
            const itemRows = document.getElementById('item-pricing-rows');
            if (itemRows) itemRows.innerHTML = '';
        }

        // Set action URL
        pricingForm.action = `/pricing/${id}/price`;

        // Prepopulate dates
        const dateInput = document.getElementById('validityDate');
        const futureDate = new Date();
        futureDate.setDate(futureDate.getDate() + (isLand ? 4 : 14));
        dateInput.value = isEdit && pricing.validityDate ? pricing.validityDate : futureDate.toISOString().split('T')[0];

        // Clear pricing fields by default (no default autofill prices)
        setPricingInputValue('oceanFreight', isEdit ? pricing.oceanFreight : '');
        setPricingInputValue('originCharges', isEdit ? pricing.originCharges : '');
        setPricingInputValue('exwCharges', isEdit ? pricing.exwCharges : 0, 0);
        setPricingInputValue('dthc', isEdit ? pricing.dthc : '');
        setPricingInputValue('warRiskSurcharge', isEdit ? pricing.warRiskSurcharge : '');
        setPricingInputValue('otherCharges', isEdit ? pricing.otherCharges : '');
        setPricingInputValue('destinationPortChangeFee', isEdit ? pricing.destinationPortChangeFee : 0, 0);
        setPricingInputValue('otherPenalties', isEdit ? pricing.otherPenalties : 0, 0);
        setPricingInputValue('transitDays', isEdit ? pricing.transitDays : '');
        setPricingInputValue('agent-id', isEdit ? pricing.agentId : '');
        setPricingInputValue('agentPurchaseUnitPrice', isEdit ? pricing.agentPurchaseUnitPrice : '');
        setPricingInputValue('agentPurchaseAmount', '');
        setPricingInputValue('agentPurchaseQuantity', isEdit ? pricing.agentPurchaseQuantity : quantity);
        setPricingInputValue('agentPurchaseNotes', isEdit ? pricing.agentPurchaseNotes : '');
        setPricingInputValue('notes', isEdit ? pricing.notes : '');
        setPricingInputValue('doorToDoorUnitPrice', isEdit ? pricing.doorToDoorUnitPrice : '');
        document.getElementById('door-to-door-fixed-total').textContent = formatPricingMoney(0);
        clearCustomPricingChargeRows();

        if (isEdit && Array.isArray(pricing.customPricingCharges)) {
            pricing.customPricingCharges.forEach((line) => addCustomPricingChargeRow(line.label || '', line.amount || ''));
        }

        if (isAir && !isItemized) {
            if (!isEdit) {
                document.getElementById('agentPurchaseQuantity').value = payload.volumeCbm > 0 ? Number(payload.volumeCbm.toFixed(4)).toString() : quantity;
            }
        }

        if (isItemized) {
            const itemRows = isEdit && Array.isArray(payload.itemPricingRows) && payload.itemPricingRows.length
                ? payload.itemPricingRows
                : (payload.cargoItems || []);
            renderItemPricingRows(itemRows);
            const landRows = document.getElementById('land-pricing-rows');
            if (landRows) landRows.innerHTML = '';
            const fclRows = document.getElementById('fcl-pricing-rows');
            if (fclRows) fclRows.innerHTML = '';
        } else if (isLand) {
            const landWeightEl = document.getElementById('land-weight-display');
            const landTons = grossWeightKg > 0 ? grossWeightKg / 1000 : 0;
            if (landWeightEl) landWeightEl.textContent = `${grossWeightKg.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} كجم / ${landTons.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 4 })} طن`;
            renderLandPricingRows(payload.landShipments || [], routePol, routePod, grossWeightKg);
            const landQty = landPricingRowsQuantity();
            if (!isEdit) {
                document.getElementById('agentPurchaseQuantity').value = landQty > 0 ? landQty : quantity;
            }
        } else if (isDoorToDoor) {
            const { quantity } = refreshDoorToDoorPricingUi();
            if (!isEdit) {
                document.getElementById('agentPurchaseQuantity').value = quantity > 0 ? Number(quantity.toFixed(4)).toString() : '';
            }
            const landRows = document.getElementById('land-pricing-rows');
            if (landRows) landRows.innerHTML = '';
        } else if (isFclMixed) {
            renderFclPricingRows(payload.fclContainerRows || []);
            renderAgentPurchaseRows((isEdit && Array.isArray(payload.agentPurchaseRows) && payload.agentPurchaseRows.length) ? payload.agentPurchaseRows : (payload.fclContainerRows || []));
            const totalFclQty = (payload.fclContainerRows || []).reduce((sum, row) => sum + (parseFloat(row.qty || row.quantity || 0) || 0), 0);
            if (!isEdit) {
                document.getElementById('agentPurchaseQuantity').value = totalFclQty > 0 ? totalFclQty : quantity;
                document.getElementById('agentPurchaseUnitPrice').value = '';
            }
        } else {
            const landRows = document.getElementById('land-pricing-rows');
            if (landRows) landRows.innerHTML = '';
            const fclRows = document.getElementById('fcl-pricing-rows');
            if (fclRows) fclRows.innerHTML = '';
        }

        updateProfitPreview();
    }

    function closePricingFormModal() {
        if (pricingModal) pricingModal.classList.add('hidden');
    }
</script>
@endsection

@extends('layouts.app')

@section('title', 'المبيعات — {{ config('company.name.ar') }}')

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-xs text-white border border-white/10">قسم المبيعات</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">إدارة الصفقات والعروض</h1>
            <p class="mt-2 max-w-2xl text-white/80">
                مراجعة العروض المُسعَّرة، إرسالها للعملاء، واتخاذ قرار الاعتماد أو الرفض وإتمام صفقات الحجز.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route('booking.create') }}" class="inline-flex h-10 items-center justify-center rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground shadow hover:opacity-95">
                    <span>طلب جديد</span>
                </a>
                <a href="{{ route('orders.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-white/30 bg-white/10 px-5 text-sm font-bold text-white hover:bg-white/20">
                    <span>جميع الطلبات</span>
                </a>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 pt-8">
        @include('partials.booking-filters', [
            'filterAction' => route('sales.index'),
            'filterId' => 'sales-filter-form',
            'filters' => $filters,
        ])
    </section>

    <!-- KPIs -->
    <section class="container mx-auto px-4 py-10">
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-accent/10 text-accent">
                        <i data-lucide="dollar-sign" class="h-5 w-5"></i>
                    </span>
                </div>
                <div class="mt-4 text-2xl font-extrabold text-card-foreground" dir="ltr">{{ \App\Models\Booking::formatMoneyTotalsByCurrency($closed, fn ($booking) => $booking->pricingTotal(), decimals: 0) }}</div>
                <div class="text-sm text-muted-foreground">إيرادات معتمدة</div>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-accent/10 text-accent">
                        <i data-lucide="award" class="h-5 w-5"></i>
                    </span>
                </div>
                <div class="mt-4 text-2xl font-extrabold text-card-foreground">{{ $closed->count() }}</div>
                <div class="text-sm text-muted-foreground">صفقات مغلقة</div>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-accent/10 text-accent">
                        <i data-lucide="users" class="h-5 w-5"></i>
                    </span>
                </div>
                <div class="mt-4 text-2xl font-extrabold text-card-foreground">{{ $queue->count() }}</div>
                <div class="text-sm text-muted-foreground">بانتظار اتخاذ القرار</div>
            </div>

            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-accent/10 text-accent">
                        <i data-lucide="target" class="h-5 w-5"></i>
                    </span>
                </div>
                <div class="mt-4 text-2xl font-extrabold text-card-foreground">{{ $conversion }}%</div>
                <div class="text-sm text-muted-foreground">معدل التحويل</div>
            </div>
        </div>
    </section>

    <!-- Queue Grid -->
    <section class="container mx-auto px-4 pb-10">
        <div class="mb-4 flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 text-xl font-bold">
                <i data-lucide="trending-up" class="h-5 w-5 text-accent"></i>
                <span>العروض الجاهزة للإرسال والاعتماد</span>
            </h2>
            <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-semibold">{{ $queue->count() }} عرض</span>
        </div>

        @if($queue->isEmpty())
            <div class="rounded-xl border border-dashed border-border bg-card py-10 text-center text-muted-foreground text-xs">
                لا توجد عروض جاهزة حالياً.
            </div>
        @else
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach($queue as $b)
                    @php
                        $salesStatusMap = [
                            'priced' => ['label' => 'بانتظار المبيعات', 'class' => 'bg-primary/20 text-primary-foreground border-primary/20'],
                            'sent' => ['label' => 'أُرسل للعميل', 'class' => 'bg-accent/20 text-accent-foreground border-accent/20'],
                            'customer_approved' => ['label' => 'وافق العميل', 'class' => 'bg-success/20 text-success-foreground border-success/30'],
                            'customer_rejected' => ['label' => 'رفض العميل', 'class' => 'bg-destructive/20 text-destructive border-destructive/30'],
                        ];
                        $salesStatus = $salesStatusMap[$b->status] ?? ['label' => $b->status, 'class' => 'bg-secondary text-foreground border-border'];
                        $cargoLabel = $b->shipmentDisplay();
                        $isLandBooking = $b->isLandShipment();
                        $isDoorToDoorBooking = $b->isDoorToDoorShipment();
                        $landPricingLines = $b->landPricingLines();
                        $itemPricingLines = $b->itemPricingLines();
                        $doorToDoorPricing = $isDoorToDoorBooking ? $b->doorToDoorPricing() : null;
                        $doorToDoorWeightKg = (float) ($doorToDoorPricing['gross_weight_kg'] ?? 0);
                        $doorToDoorPricingBasis = $doorToDoorPricing['basis'] ?? 'ton';
                        $doorToDoorQuantity = (float) ($doorToDoorPricing['quantity'] ?? 0);
                        $doorToDoorUnitPrice = (float) ($doorToDoorPricing['unit_price'] ?? 0);
                        $doorToDoorUnitPriceLabel = $doorToDoorPricingBasis === 'cbm'
                            ? __('messages.door_to_door_unit_price_per_cbm_label')
                            : __('messages.door_to_door_unit_price_label');
                        $doorToDoorQuantityLabel = $doorToDoorPricingBasis === 'cbm'
                            ? __('messages.door_to_door_cbm_label')
                            : __('messages.door_to_door_tons_label');
                    @endphp
                    <div data-booking-id="{{ $b->id }}" class="rounded-xl border border-border bg-card p-5 shadow-card">
                        <div class="flex items-start justify-between">
                            <div>
                                <div class="font-mono text-xs text-muted-foreground">{{ $b->id }}</div>
                                <h3 class="font-bold text-card-foreground text-sm">{{ $b->customer_company }}</h3>
                                <p class="text-xs text-muted-foreground mt-0.5" dir="ltr">{{ $b->customer_phone }} • {{ $b->customer_email }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold border {{ $salesStatus['class'] }}">
                                {{ $salesStatus['label'] }}
                            </span>
                        </div>

                        <div class="my-3 flex flex-wrap items-center gap-2 rounded-lg bg-secondary/40 px-3 py-2 text-xs">
                            <i data-lucide="anchor" class="h-4 w-4 text-accent"></i>
                            <span class="font-semibold">{{ $b->pol }} &rarr; {{ $b->pod }}</span>
                            <span class="text-muted-foreground">•</span>
                            <span>{{ $cargoLabel }}</span>
                        </div>

                        <!-- Pricing Table Summary -->
                        @if($isLandBooking && count($landPricingLines))
                            <div class="rounded-lg border border-border/60 p-3 text-[11px] leading-relaxed">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <span class="font-black text-foreground">تفصيل سعر النقل البري</span>
                                    <span class="font-bold text-muted-foreground">Transit: {{ $b->transit_days }} يوم</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[520px] text-right">
                                        <thead class="text-muted-foreground">
                                            <tr class="border-b border-border/60">
                                                <th class="py-1">المسار</th>
                                                <th class="py-1 text-center">الوزن بالطن</th>
                                                <th class="py-1 text-left">سعر الطن الواحد</th>
                                                <th class="py-1 text-left">المجموع</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($landPricingLines as $line)
                                                <tr class="border-b border-border/40">
                                                    <td class="py-1.5 font-semibold">{{ $line['from_city'] }} &rarr; {{ $line['to_city'] }}</td>
                                                    <td class="py-1.5 text-center font-semibold" dir="ltr">{{ rtrim(rtrim(number_format((float) $line['qty'], 2), '0'), '.') }} طن</td>
                                                    <td class="py-1.5 text-left font-semibold" dir="ltr">{{ $b->formatPricingMoney((float) $line['unit_price']) }}</td>
                                                    <td class="py-1.5 text-left font-black text-primary" dir="ltr">{{ $b->formatPricingMoney((float) $line['total']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 flex items-center justify-between border-t pt-2 font-bold text-xs">
                                    <span>الإجمالي المقترح</span>
                                    <span class="text-sm font-extrabold text-primary" dir="ltr">{{ $b->formatPricingMoney($b->pricingTotal()) }}</span>
                                </div>
                            </div>
                        @elseif(count($itemPricingLines))
                            <div class="rounded-lg border border-border/60 p-3 text-[11px] leading-relaxed">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <span class="font-black text-foreground">تفصيل أسعار أصناف الشحنة</span>
                                    <span class="font-bold text-muted-foreground">Transit: {{ $b->transit_days }} يوم</span>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full min-w-[620px] text-right">
                                        <thead class="text-muted-foreground">
                                            <tr class="border-b border-border/60">
                                                <th class="py-1">الصنف</th>
                                                <th class="py-1">HS CODE</th>
                                                <th class="py-1 text-center">الكمية</th>
                                                <th class="py-1 text-left">سعر البيع</th>
                                                <th class="py-1 text-left">المجموع</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($itemPricingLines as $line)
                                                <tr class="border-b border-border/40">
                                                    <td class="py-1.5 font-semibold">{{ $line['name'] }}</td>
                                                    <td class="py-1.5 font-mono" dir="ltr">{{ $line['hs_code'] ?: '—' }}</td>
                                                    <td class="py-1.5 text-center font-semibold" dir="ltr">{{ rtrim(rtrim(number_format((float) $line['quantity'], 2), '0'), '.') }} {{ \App\Models\Booking::cargoUnitLabel($line['unit'] ?? null) }}</td>
                                                    <td class="py-1.5 text-left font-semibold" dir="ltr">{{ $b->formatPricingMoney((float) $line['sale_unit_price']) }}</td>
                                                    <td class="py-1.5 text-left font-black text-primary" dir="ltr">{{ $b->formatPricingMoney((float) $line['sale_total']) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 flex items-center justify-between border-t pt-2 font-bold text-xs">
                                    <span>الإجمالي المقترح</span>
                                    <span class="text-sm font-extrabold text-primary" dir="ltr">{{ $b->formatPricingMoney($b->pricingTotal()) }}</span>
                                </div>
                            </div>
                        @elseif($isDoorToDoorBooking)
                            <div class="rounded-lg border border-border/60 p-3 text-[11px] leading-relaxed">
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <span class="font-black text-foreground">تفصيل سعر الشحن من الباب إلى الباب</span>
                                    <span class="font-bold text-muted-foreground">الوزن: {{ number_format((float) $doorToDoorWeightKg, 2) }} كجم</span>
                                </div>
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">{{ $doorToDoorUnitPriceLabel }}</span><span class="font-semibold" dir="ltr">{{ $b->formatPricingMoney($doorToDoorUnitPrice) }}</span></div>
                                    <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">{{ $doorToDoorQuantityLabel }}</span><span class="font-semibold" dir="ltr">{{ rtrim(rtrim(number_format($doorToDoorQuantity, 2), '0'), '.') }} {{ \App\Models\Booking::cargoUnitLabel($doorToDoorPricingBasis) }}</span></div>
                                    <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">الإجمالي</span><span class="font-semibold" dir="ltr">{{ $b->formatPricingMoney($b->pricingTotal()) }}</span></div>
                                    <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">مدة العبور</span><span class="font-semibold">{{ $b->transit_days }} يوم</span></div>
                                </div>
                            </div>
                        @else
                            <div class="grid gap-2 rounded-lg border border-border/60 p-3 text-[11px] leading-relaxed sm:grid-cols-2">
                                <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">Ocean Freight</span><span class="font-semibold" dir="ltr">{{ $b->formatPricingMoney($b->ocean_freight ?? 0) }}</span></div>
                                <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">DTHC</span><span class="font-semibold" dir="ltr">{{ $b->formatPricingMoney($b->dthc ?? 0) }}</span></div>
                                <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">Local Charges</span><span class="font-semibold" dir="ltr">{{ $b->formatPricingMoney($b->origin_charges ?? 0) }}</span></div>
                                @if($b->shouldShowExwCharges())
                                    <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">{{ __('messages.exw_charges_details') }}</span><span class="font-semibold" dir="ltr">{{ $b->formatPricingMoney($b->exw_charges ?? 0) }}</span></div>
                                @endif
                                <div class="flex justify-between border-b pb-1"><span class="text-muted-foreground">Transit</span><span class="font-semibold">{{ $b->transit_days }} يوم</span></div>
                                <div class="mt-1 flex items-center justify-between border-t pt-2 font-bold text-xs sm:col-span-2">
                                    <span>الإجمالي المقترح</span>
                                    <span class="text-sm font-extrabold text-primary" dir="ltr">{{ $b->formatPricingMoney($b->pricingTotal()) }}</span>
                                </div>
                            </div>
                        @endif

                        @if($b->customer_decision)
                            <div class="mt-3 rounded-lg border {{ $b->customer_decision === 'accepted' ? 'border-success/25 bg-success/10' : 'border-destructive/25 bg-destructive/10' }} p-3 text-[11px] leading-relaxed">
                                <div class="font-black">
                                    قرار العميل: {{ $b->customer_decision === 'accepted' ? 'موافقة على عرض السعر' : 'رفض عرض السعر' }}
                                    @if($b->customer_decided_at)
                                        <span class="font-normal text-muted-foreground">- {{ $b->customer_decided_at->format('Y-m-d H:i') }}</span>
                                    @endif
                                </div>
                                <div class="mt-1 text-muted-foreground">{{ $b->customer_decision_note ?: 'لا توجد ملاحظة من العميل.' }}</div>
                            </div>
                        @endif

                        @if($b->notes || $b->documents || $b->attachments)
                            <div class="mt-3 rounded-lg border border-dashed border-border/80 bg-secondary/20 p-3 text-[11px] leading-relaxed">
                                <div class="mb-1 font-bold text-foreground">ملاحظات ومرفقات</div>
                                @if($b->notes)
                                    <div class="mb-1 text-muted-foreground">{{ $b->notes }}</div>
                                @endif
                                @if($b->documents)
                                    <div class="mb-1 text-muted-foreground">الوثائق: {{ $b->documents }}</div>
                                @endif
                                @if($b->attachments)
                                    @php $attachmentList = json_decode($b->attachments, true) ?: []; @endphp
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        @foreach($attachmentList as $attachment)
                                            <a href="{{ route('bookings.attachments.show', ['booking' => $b, 'path' => ltrim(str_replace('\\', '/', $attachment), '/')]) }}" target="_blank" class="inline-flex items-center rounded-full border border-border bg-background px-2.5 py-1 text-[10px] font-semibold text-accent underline">
                                                {{ basename($attachment) }}
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Actions -->
                        <div class="mt-4 flex flex-wrap gap-2 border-t pt-3">
                            <button onclick="openDetailModal('{{ $b->id }}')" class="inline-flex h-8 items-center justify-center rounded-lg border border-border bg-card px-3 text-xs font-bold hover:bg-secondary/40">
                                <i data-lucide="eye" class="ml-1 h-3.5 w-3.5 text-muted-foreground"></i>
                                <span>تفاصيل</span>
                            </button>
                            <a href="{{ route('quote.show', ['id' => $b->id]) }}" target="_blank" class="inline-flex h-8 items-center justify-center rounded-lg border border-border bg-card px-3 text-xs font-bold hover:bg-secondary/40">
                                <i data-lucide="printer" class="ml-1 h-3.5 w-3.5 text-muted-foreground"></i>
                                <span>طباعة</span>
                            </a>
                            <button onclick="copyQuoteUrl('{{ $b->id }}')" class="inline-flex h-8 items-center justify-center rounded-lg border border-border bg-card px-3 text-xs font-bold hover:bg-secondary/40">
                                <i data-lucide="share-2" class="ml-1 h-3.5 w-3.5 text-muted-foreground"></i>
                                <span>مشاركة</span>
                            </button>
                            @if($b->status === 'priced')
                                <form id="send-form-{{ $b->id }}" action="{{ route('sales.send', ['id' => $b->id]) }}" method="POST" class="hidden">
                                    @csrf
                                </form>
                                <div class="flex flex-wrap items-center gap-2">
                                    <label class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-2 text-xs font-semibold text-foreground transition hover:border-accent hover:bg-accent/10">
                                        <input id="send-via-whatsapp-{{ $b->id }}" type="checkbox" class="h-4 w-4 rounded border-border text-success focus:ring-success" checked>
                                        <i data-lucide="message-circle" class="h-4 w-4 text-success"></i>
                                        واتساب
                                    </label>
                                    <label class="inline-flex items-center gap-2 rounded-full border border-border bg-card px-3 py-2 text-xs font-semibold text-foreground transition hover:border-primary hover:bg-primary/10">
                                        <input id="send-via-email-{{ $b->id }}" type="checkbox" class="h-4 w-4 rounded border-border text-primary focus:ring-primary">
                                        <i data-lucide="mail" class="h-4 w-4 text-primary"></i>
                                        بريد إلكتروني
                                    </label>
                                    <button type="button" data-booking='{{ json_encode([
                                        'id' => $b->id,
                                        'customer_company' => $b->customer_company,
                                        'customer_phone' => $b->customer_phone,
                                        'customer_email' => $b->customer_email,
                                        'pol' => $b->pol,
                                        'pod' => $b->pod,
                                        'shipment_type' => $b->shipment_type,
                                        'container_type' => $b->container_type,
                                        'container_qty' => $b->container_qty,
                                        'container_summary' => $b->containerSummary(),
                                        'container_total_qty' => $b->containerTotalQuantity(),
                                        'transit_days' => $b->transit_days,
                                        'carrier_etd' => $b->carrier_etd ? $b->carrier_etd->toDateString() : null,
                                        'pricing_total' => $b->pricingTotal(),
                                        'shipping_line' => data_get($b->cargo_details, 'shipping_line') ?: data_get($b->route_details, 'shipping_line') ?: $b->carrier_vessel ?: 'MLO',
                                        'free_time' => data_get($b->cargo_details, 'free_time') ?: data_get($b->route_details, 'free_time') ?: '5 days',
                                        'tos' => data_get($b->cargo_details, 'tos') ?: data_get($b->route_details, 'tos') ?: $b->bill_type ?: 'FOB',
                                        'cargo_details' => $b->cargo_details,
                                        'cargo_label' => $cargoLabel,
                                        'is_land' => $isLandBooking,
                                        'is_door_to_door' => $isDoorToDoorBooking,
                                        'land_pricing_lines' => $landPricingLines,
                                        'land_pricing_quantity' => $b->landPricingQuantity(),
                                        'door_to_door_pricing' => $doorToDoorPricing,
                                        'weight_kg' => $b->weight_kg,
                                        'volume_cbm' => $b->volume_cbm,
                                        'dangerous' => $b->dangerous,
                                        'goods_description' => $b->goods_description,
                                        'notes' => $b->notes,
                                        'attachments' => $b->attachments ? json_decode($b->attachments, true) : [],
                                        'ocean_freight' => $b->ocean_freight,
                                        'origin_charges' => $b->origin_charges,
                                        'exw_charges' => $b->exw_charges,
                                        'show_exw_charges' => $b->shouldShowExwCharges(),
                                        'dthc' => $b->dthc,
                                        'war_risk_surcharge' => $b->war_risk_surcharge,
                                        'other_charges' => $b->other_charges,
                                        'custom_pricing_charges' => $b->customPricingCharges(),
                                        'pricing_total' => $b->pricingTotal(),
                                        'pricing_currency' => $b->pricingCurrency(),
                                        'pricing_currency_symbol' => $b->pricingCurrencySymbol(),
                                        'route_details' => $b->route_details,
                                        'quote_message_ar' => \App\Support\BookingQuoteMessage::forBooking($b, 'ar'),
                                        'quote_message_en' => \App\Support\BookingQuoteMessage::forBooking($b, 'en'),
                                    ], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) }}' onclick="sendCustomer(this)" class="inline-flex h-8 items-center justify-center rounded-lg bg-secondary px-3 text-xs font-bold hover:bg-secondary/80 text-foreground border border-border/80">
                                        <i data-lucide="send" class="ml-1 h-3.5 w-3.5"></i>
                                        <span>إرسال للعميل</span>
                                    </button>
                                </div>
                            @endif
                            @if($b->status !== 'customer_rejected')
                                <button onclick="openDecisionModal('{{ $b->id }}', 'approve')" class="inline-flex h-8 items-center justify-center rounded-lg bg-success/20 text-success-foreground border border-success/30 px-3 text-xs font-bold hover:bg-success/30">
                                    <i data-lucide="check-circle-2" class="ml-1 h-3.5 w-3.5"></i>
                                    <span>{{ $b->status === 'customer_approved' ? 'اعتماد نهائي' : 'اعتماد' }}</span>
                                </button>
                            @endif
                            <button onclick="openDecisionModal('{{ $b->id }}', 'reject')" class="inline-flex h-8 items-center justify-center rounded-lg bg-destructive/20 text-destructive border border-destructive/30 px-3 text-xs font-bold hover:bg-destructive/30">
                                <i data-lucide="x-circle" class="ml-1 h-3.5 w-3.5"></i>
                                <span>{{ $b->status === 'customer_rejected' ? 'إرجاع للتسعير' : 'رفض' }}</span>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    <!-- Closed Deals Table -->
    @if($closed->isNotEmpty() || $rejected->isNotEmpty())
        <section class="container mx-auto px-4 pb-12">
            <h2 class="mb-4 text-xl font-bold">تاريخ الصفقات المغلّقة والمرفوضة</h2>
            <div class="grid gap-3 md:hidden">
                @foreach($closed->merge($rejected) as $b)
                    <article data-booking-id="{{ $b->id }}" class="rounded-xl border border-border bg-card p-4 shadow-card" onclick="openDetailModal('{{ $b->id }}')">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-mono text-xs font-bold text-muted-foreground">{{ $b->id }}</div>
                                <h4 class="mt-1 truncate text-sm font-black text-card-foreground">{{ $b->customer_company }}</h4>
                                <p class="mt-1 text-xs font-semibold text-muted-foreground">{{ $b->pol }} &rarr; {{ $b->pod }}</p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $b->status === 'approved' ? 'bg-success/20 text-success-foreground border-success/30' : 'bg-destructive/20 text-destructive border-destructive/30' }}">
                                {{ $b->status === 'approved' ? 'معتمد' : 'مرفوض' }}
                            </span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">{{ __('messages.orders_table_total') }}</div>
                                <div class="mt-1 font-black text-primary" dir="ltr">{{ $b->formatPricingMoney($b->pricingTotal()) }}</div>
                            </div>
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">{{ __('messages.orders_table_date') }}</div>
                                <div class="mt-1 font-black">{{ $b->decided_at ? $b->decided_at->toDateString() : '—' }}</div>
                            </div>
                        </div>
                        @if($b->sales_notes)
                            <p class="mt-3 rounded-lg bg-secondary/35 p-2 text-xs leading-6 text-muted-foreground">{{ $b->sales_notes }}</p>
                        @endif
                    </article>
                @endforeach
            </div>
            <div class="hidden overflow-x-auto rounded-xl border border-border bg-card shadow-card md:block">
                <table class="w-full text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3">رقم العرض</th>
                            <th class="px-4 py-3">العميل</th>
                            <th class="px-4 py-3">المسار</th>
                            <th class="px-4 py-3">الإجمالي</th>
                            <th class="px-4 py-3">ملاحظات القرار</th>
                            <th class="px-4 py-3">تاريخ الاعتماد</th>
                            <th class="px-4 py-3">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($closed->merge($rejected) as $b)
                            <tr data-booking-id="{{ $b->id }}" class="border-t border-border/40 hover:bg-secondary/30 transition-colors cursor-pointer" onclick="openDetailModal('{{ $b->id }}')">
                                <td class="px-4 py-3 font-mono text-xs font-semibold">{{ $b->id }}</td>
                                <td class="px-4 py-3 font-medium">{{ $b->customer_company }}</td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $b->pol }} &rarr; {{ $b->pod }}</td>
                                <td class="px-4 py-3 font-bold text-primary" dir="ltr">{{ $b->formatPricingMoney($b->pricingTotal()) }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ $b->sales_notes ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ $b->decided_at ? $b->decided_at->toDateString() : '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-bold border {{ $b->status === 'approved' ? 'bg-success/20 text-success-foreground border-success/30' : 'bg-destructive/20 text-destructive border-destructive/30' }}">
                                        {{ $b->status === 'approved' ? 'معتمد' : 'مرفوض' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>

<!-- Decision Dialog Modal -->
<div id="decision-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-md bg-card rounded-2xl border border-border shadow-elegant overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-4 bg-secondary/20">
            <h3 class="font-extrabold text-base text-primary" id="decision-title">اتخاذ القرار بشأن عرض السعر</h3>
            <button onclick="closeDecisionModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center transition-all">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="decision-form" action="" method="POST" class="p-6 space-y-4 text-xs">
            @csrf
            <p class="text-muted-foreground leading-relaxed" id="decision-desc">
                سيتم تحديث حالة طلب الشحن وتوليد الفاتورة والملف اللوجستي للعميل.
            </p>

            <div id="shipping-party-fields" class="space-y-3 hidden">
                <div class="border-t pt-3">
                    <h4 class="font-bold text-primary mb-2">معلومات الأطراف الشاحنة / Shipping Parties Details</h4>
                </div>
                <div class="space-y-1">
                    <label for="shipper" class="font-bold text-foreground block">المُرسِل / الشاحن (Shipper) *</label>
                    <span class="text-[10px] text-muted-foreground block">الاسم والعنوان الكامل للمصدر / Exporter full name & address</span>
                    <textarea name="shipper" id="shipper" rows="2" placeholder="اسم وعنوان الشاحن" class="w-full p-2.5 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1">
                    <label for="consignee" class="font-bold text-foreground block">المرسل إليه (Consignee) *</label>
                    <span class="text-[10px] text-muted-foreground block">الاسم، العنوان الكامل، رقم الهاتف، والبريد الإلكتروني للمستورد / Importer details & Tax No.</span>
                    <textarea name="consignee" id="consignee" rows="2" placeholder="اسم وعنوان المرسل إليه" class="w-full p-2.5 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
                <div class="space-y-1">
                    <label for="notify_party" class="font-bold text-foreground block">الطرف المخطر (Notify Party) *</label>
                    <span class="text-[10px] text-muted-foreground block">الجهة التي يتم إخطارها بوصول الشحنة / Arrival notification details</span>
                    <textarea name="notify_party" id="notify_party" rows="2" placeholder="اسم وعنوان الطرف المخطر" class="w-full p-2.5 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                </div>
            </div>

            <div class="space-y-1">
                <label for="note" class="font-bold text-foreground">ملاحظات ومرئيات إضافية</label>
                <textarea name="note" id="note" rows="3" placeholder="ملاحظات المبيعات أو شروط التسوية للملف..." class="w-full p-2.5 border border-border bg-background rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
            </div>
            
            <div class="flex justify-end gap-2 pt-2 border-t">
                <button type="button" onclick="closeDecisionModal()" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">إلغاء</button>
                <button type="submit" id="decision-submit-btn" class="inline-flex h-9 items-center justify-center rounded-lg px-5 text-xs font-bold text-white shadow">تأكيد القرار</button>
            </div>
        </form>
    </div>
</div>

@include('partials.booking-detail-modal')
@endsection

@section('scripts')
@include('partials.booking-filters-script')
<script>
    const decisionModal = document.getElementById('decision-modal');
    const decisionForm = document.getElementById('decision-form');
    const decisionTitle = document.getElementById('decision-title');
    const decisionDesc = document.getElementById('decision-desc');
    const decisionSubmitBtn = document.getElementById('decision-submit-btn');

    function openDecisionModal(id, type) {
        if (!decisionModal) return;
        decisionModal.classList.remove('hidden');

        const shippingFields = document.getElementById('shipping-party-fields');
        const shipperInput = document.getElementById('shipper');
        const consigneeInput = document.getElementById('consignee');
        const notifyPartyInput = document.getElementById('notify_party');

        if (type === 'approve') {
            decisionTitle.innerText = `اعتماد عرض السعر — ${id}`;
            decisionDesc.innerText = 'سيتم اعتماد عرض السعر للعميل، مما يقوم تلقائياً بإصدار فاتورة الحسابات وتوليد ملف تتبع الشحنة اللوجستي.';
            decisionSubmitBtn.innerText = 'تأكيد الاعتماد والتحويل';
            decisionSubmitBtn.className = 'inline-flex h-9 items-center justify-center rounded-lg bg-success text-success-foreground hover:bg-success/90 px-5 text-xs font-bold shadow';
            decisionForm.action = `/sales/${id}/approve`;

            if (shippingFields) shippingFields.classList.remove('hidden');
            if (shipperInput) shipperInput.required = true;
            if (consigneeInput) consigneeInput.required = true;
            if (notifyPartyInput) notifyPartyInput.required = true;
        } else {
            decisionTitle.innerText = `رفض عرض السعر — ${id}`;
            decisionDesc.innerText = 'سيتم إغلاق طلب الشحن وتسجيله كمرفوض في سجلات المبيعات. الرجاء كتابة سبب الرفض في الملاحظات.';
            decisionSubmitBtn.innerText = 'تأكيد الرفض والإغلاق';
            decisionSubmitBtn.className = 'inline-flex h-9 items-center justify-center rounded-lg bg-destructive text-destructive-foreground hover:bg-destructive/90 px-5 text-xs font-bold shadow';
            decisionForm.action = `/sales/${id}/reject`;

            if (shippingFields) shippingFields.classList.add('hidden');
            if (shipperInput) shipperInput.required = false;
            if (consigneeInput) consigneeInput.required = false;
            if (notifyPartyInput) notifyPartyInput.required = false;
        }
    }

    function closeDecisionModal() {
        if (decisionModal) decisionModal.classList.add('hidden');
    }

    function copyQuoteUrl(id) {
        const url = `${window.location.origin}/quote/${id}`;
        navigator.clipboard.writeText(url).then(() => {
            alert('تم نسخ رابط العرض بنجاح:\n' + url);
        });
    }

    function sanitizeMessage(text) {
        // Mask emails
        text = text.replace(/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g, '[EMAIL]');
        
        // Mask phone numbers (matches sequences of 7+ digits potentially with space/dash/plus)
        text = text.replace(/(\+?[0-9][\s\-\(\)0-9]{6,}[0-9])/g, '[PHONE]');
        
        // Mask name fields
        const patterns = [
            /(المسؤول:\s*)([^\n\r]+)/gu,
            /(الشخص المسؤول:\s*)([^\n\r]+)/gu,
            /(Contact Name:\s*)([^\n\r]+)/gi,
            /(Responsible:\s*)([^\n\r]+)/gi
        ];
        
        patterns.forEach(pattern => {
            text = text.replace(pattern, (match, label, name) => {
                return label + '[CONFIDENTIAL]';
            });
        });
        
        return text;
    }

    function buildFreightQuotationMessage(data, locale = 'en') {
        const isArabic = String(locale || 'en').toLowerCase().startsWith('ar');
        const preparedMessage = isArabic ? data.quote_message_ar : data.quote_message_en;
        if (typeof preparedMessage === 'string' && preparedMessage.trim() !== '') {
            return preparedMessage;
        }

        const orderId = data.id || '—';
        const customer = @json(config('company.name.en'));
        const pol = data.pol || '—';
        const pod = data.pod || '—';
        const rate = Number(data.pricing_total ?? 0);
        const symbol = data.pricing_currency_symbol || '$';
        const rateText = `${symbol} ${rate.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        const transitDays = Number(data.transit_days ?? 0);
        const transitText = Number.isFinite(transitDays) && transitDays > 0 ? `${transitDays} ${isArabic ? 'يوم' : 'days'}` : '—';
        const freeTime = data.free_time || '—';
        const shippingLine = data.shipping_line || 'MLO';
        const etd = data.carrier_etd || '—';
        const validityDate = data.validity_date || '—';
        const tos = data.tos || 'FOB';
        const shipmentType = data.shipment_type || 'FCL';
        const incoterm = (data.route_details && data.route_details.mode) || data.tos || 'FOB';

        if (isArabic) {
            return [
                `رقم الطلب: ${orderId}`,
                customer,
                'عرض سعر',
                '',
                `1- ميناء التحميل: ${pol}`,
                `2- مكان التفريغ: ${pod}`,
                `3- السعر: ${rateText}`,
                `4- مدة النقل: ${transitText}`,
                `5- صلاحية العرض: ${validityDate}`,
                `6- TOS: ${tos}`,
                `7- نوع الشحنة: ${shipmentType}`,
                `8- شرط الشحن: ${incoterm}`,
                '',
                'ملاحظة:',
                '• العرض خاضع لتوافر المساحة والحاويات في وقت الحجز.',
                '• السعر المقتبس يشمل رسوم War Risk الحالية؛ ومع ذلك فإن أي رسوم War Risk إضافية تفرضها شركة الشحن تكون على حساب العميل.',
            ].join('\n');
        }

        return [
            `Order: ${orderId}`,
            customer,
            'Freight Quotation',
            '',
            `1- Port of Loading: ${pol}`,
            `2- Place of Discharge: ${pod}`,
            `3- Rate: ${rateText}`,
            `4- Transit Time: ${transitText}`,
            `5- Validity Date: ${validityDate}`,
            `6- TOS: ${tos}`,
            `7- Shipment Type: ${shipmentType}`,
            `8- Incoterm: ${incoterm}`,
            '',
            'Note:',
            '• Offer is subject to availability of space and containers at the time of booking.',
            '• Price quoted includes current War Risk fees; however, any additional War Risk Surcharges imposed by the shipping line shall be for the account of the customer.',
        ].join('\n');
    }

    function sendCustomer(button) {
        const data = JSON.parse(button.getAttribute('data-booking'));
        const id = data.id;
        const locale = (document.documentElement.lang || 'en').toLowerCase();
        const whatsappCheckbox = document.getElementById(`send-via-whatsapp-${id}`);
        const emailCheckbox = document.getElementById(`send-via-email-${id}`);
        const sendWhatsApp = whatsappCheckbox ? whatsappCheckbox.checked : false;
        const sendEmail = emailCheckbox ? emailCheckbox.checked : false;

        if (!sendWhatsApp && !sendEmail) {
            alert(locale.startsWith('ar') ? 'الرجاء اختيار واتساب أو بريد إلكتروني أو كليهما قبل إرسال العرض.' : 'Please select WhatsApp, email, or both before sending the quote.');
            return;
        }

        const phoneNumber = data.customer_phone ? data.customer_phone.replace(/[^0-9]/g, '') : '';
        const hasPhone = Boolean(phoneNumber);
        const hasEmail = Boolean(data.customer_email);
        let opened = false;

        const bodyText = buildFreightQuotationMessage(data, locale);
        const sanitizedBodyText = sanitizeMessage(bodyText);
        const body = encodeURIComponent(sanitizedBodyText);
        const subject = encodeURIComponent(locale.startsWith('ar') ? 'عرض سعر للطلب ' + id : 'Freight Quotation for Order ' + id);

        if (sendWhatsApp) {
            if (hasPhone) {
                window.open(`https://wa.me/${phoneNumber}?text=${body}`, '_blank');
                opened = true;
            } else {
                alert(locale.startsWith('ar') ? 'رقم الهاتف غير متوفر لإرسال رسالة واتساب.' : 'Phone number is not available to send a WhatsApp message.');
            }
        }

        if (sendEmail) {
            if (hasEmail) {
                window.open(`mailto:${encodeURIComponent(data.customer_email)}?subject=${subject}&body=${body}`, '_blank');
                opened = true;
            } else {
                alert(locale.startsWith('ar') ? 'البريد الإلكتروني غير متوفر لإرسال رسالة إلكترونية.' : 'Email is not available to send an email message.');
            }
        }

        if (!opened) {
            return;
        }

        const form = document.getElementById(`send-form-${id}`);
        if (form) {
            form.submit();
        }
    }
</script>
@endsection

@php
    $invoices = $booking->relationLoaded('invoices') ? $booking->invoices : $booking->invoices()->get();
    $track = $booking->relationLoaded('shipmentTrack') ? $booking->shipmentTrack : $booking->shipmentTrack()->first();
    $events = $track && $track->relationLoaded('events') ? $track->events : ($track ? $track->events()->get() : collect());
    $hasCustomsActivity = (bool) data_get($booking->route_details, 'services.customs_requested')
        || filled($booking->customs_reference)
        || filled($booking->customs_declaration_no)
        || filled($booking->customs_bayan_no)
        || filled($booking->customs_documents)
        || filled($booking->customs_notes);
    $invoiceRemaining = $invoices->sum(fn ($invoice) => $invoice->remaining_amount);
    $pricingReady = $booking->pricingTotal() > 0;
    $customerDecided = filled($booking->customer_decision) || in_array($booking->status, ['customer_approved', 'customer_rejected', 'approved', 'booked'], true);
    $carrierReady = filled($booking->carrier_booking_ref) || filled($booking->carrier_booked_at) || $booking->status === 'booked';
    $trackingReady = (bool) $track;
    $trackingComplete = $track && ((int) $track->progress >= 100);
    $customsComplete = $booking->customs_status === 'cleared' || filled($booking->customs_cleared_at);
    $invoiceReady = $invoices->isNotEmpty();
    $invoiceComplete = $invoiceReady && $invoiceRemaining <= 0.01;
    $invoiceRemainingLabel = \App\Models\Booking::formatMoneyTotalsByCurrency(
        $invoices,
        fn ($invoice) => $invoice->remaining_amount,
        fn ($invoice) => $invoice->effectiveCurrency()
    );

    $steps = [
        [
            'label' => __('استلام الطلب'),
            'hint' => $booking->created_at?->format('Y-m-d H:i') ?: __('تم إنشاء الملف'),
            'icon' => 'inbox',
            'state' => 'done',
        ],
        [
            'label' => __('التسعير'),
            'hint' => $pricingReady ? ($booking->priced_at?->format('Y-m-d H:i') ?: __('تم إدخال العرض')) : __('بانتظار فريق التسعير'),
            'icon' => 'badge-dollar-sign',
            'state' => $pricingReady ? 'done' : 'current',
        ],
        [
            'label' => __('قرار العميل'),
            'hint' => $customerDecided ? ($booking->customer_decided_at?->format('Y-m-d H:i') ?: __('تم تسجيل القرار')) : __('الموافقة أو الرفض'),
            'icon' => 'clipboard-check',
            'state' => $customerDecided ? 'done' : ($pricingReady ? 'current' : 'pending'),
        ],
        [
            'label' => __('حجز الناقل'),
            'hint' => $carrierReady ? ($booking->carrier_booking_ref ?: __('تم الحجز')) : __('بعد اعتماد العرض'),
            'icon' => 'ship',
            'state' => $carrierReady ? 'done' : ($customerDecided && $booking->customer_decision !== 'rejected' ? 'current' : 'pending'),
        ],
        [
            'label' => __('الشحن والتتبع'),
            'hint' => $trackingReady ? (($track->status ?: __('قيد التتبع')) . ($track->progress !== null ? ' - ' . max(0, min(100, (int) $track->progress)) . '%' : '')) : __('يظهر بعد فتح التتبع'),
            'icon' => 'radar',
            'state' => $trackingComplete ? 'done' : ($trackingReady ? 'current' : 'pending'),
        ],
        [
            'label' => __('التخليص'),
            'hint' => $hasCustomsActivity ? __(\App\Support\BookingStatus::customsLabels()[$booking->customs_status] ?? 'قيد فتح الملف') : __('لا توجد متابعة جمركية مسجلة'),
            'icon' => 'shield-check',
            'state' => $customsComplete ? 'done' : ($hasCustomsActivity ? 'current' : 'pending'),
        ],
        [
            'label' => __('الفواتير'),
            'hint' => $invoiceReady ? ($invoiceComplete ? __('مدفوعة بالكامل') : __('متبقي ') . $invoiceRemainingLabel) : __('لم تصدر فاتورة بعد'),
            'icon' => 'receipt-text',
            'state' => $invoiceComplete ? 'done' : ($invoiceReady ? 'current' : 'pending'),
        ],
    ];

    $isRejected = in_array($booking->status, ['rejected', 'customer_rejected'], true) || $booking->customer_decision === 'rejected';
    $nextAction = $isRejected ? null : collect($steps)->firstWhere('state', 'current');
    $stateClasses = [
        'done' => [
            'dot' => 'bg-success text-white border-success',
            'line' => 'bg-success/45',
            'title' => 'text-foreground',
            'hint' => 'text-muted-foreground',
        ],
        'current' => [
            'dot' => 'bg-accent text-accent-foreground border-accent',
            'line' => 'bg-accent/40',
            'title' => 'text-accent',
            'hint' => 'text-foreground',
        ],
        'pending' => [
            'dot' => 'bg-secondary text-muted-foreground border-border',
            'line' => 'bg-border',
            'title' => 'text-muted-foreground',
            'hint' => 'text-muted-foreground',
        ],
    ];
@endphp

<section class="rounded-xl border border-border bg-card p-5 shadow-card">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-base font-black">{{ __('مسار الطلب') }}</h2>
            <p class="mt-1 text-xs text-muted-foreground">{{ __('نظرة واحدة على مرحلة الطلب وما يحتاج متابعة.') }}</p>
        </div>
        @if($nextAction)
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-accent/25 bg-accent/10 px-3 py-1 text-[11px] font-black text-accent">
                <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                {{ __('الإجراء التالي:') }} {{ $nextAction['label'] }}
            </span>
        @endif
    </div>

    <ol class="mt-5 grid gap-4 xl:grid-cols-7">
        @foreach($steps as $index => $step)
            @php $classes = $stateClasses[$step['state']]; @endphp
            <li class="relative min-w-0" @if($step['state'] === 'current' && !$isRejected) aria-current="step" @endif>
                @if(! $loop->last)
                    <div aria-hidden="true" class="absolute start-5 top-10 h-full w-px xl:start-7 xl:top-5 xl:h-0.5 xl:w-[calc(100%-1.75rem)] {{ $classes['line'] }}"></div>
                @endif
                <div class="relative flex gap-3 xl:block">
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full border {{ $classes['dot'] }}">
                        <i data-lucide="{{ $step['icon'] }}" class="h-4 w-4"></i>
                    </span>
                    <div class="min-w-0 xl:mt-3">
                        <div class="text-sm font-black {{ $classes['title'] }}">{{ $step['label'] }}</div>
                        <span class="sr-only">{{ __('portal.' . ($step['state'] === 'done' ? 'completed' : ($step['state'] === 'current' ? 'current' : 'upcoming'))) }}</span>
                        <div class="mt-1 text-[11px] leading-5 {{ $classes['hint'] }}">{{ $step['hint'] }}</div>
                    </div>
                </div>
            </li>
        @endforeach
    </ol>

    @if($events->isNotEmpty())
        <div class="mt-5 border-t border-border pt-4">
            <div class="mb-3 flex items-center justify-between gap-3">
                <h3 class="text-sm font-black">{{ __('آخر أحداث التتبع') }}</h3>
                <span class="rounded-full bg-secondary px-2.5 py-1 text-[11px] font-black">{{ $events->count() }} {{ __('حدث') }}</span>
            </div>
            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @foreach($events->sortByDesc('event_date')->take(3) as $event)
                    <div class="rounded-lg border border-border/70 bg-secondary/20 p-3 text-xs">
                        <div class="font-black text-foreground">{{ $event->status }}</div>
                        <div class="mt-1 text-muted-foreground">{{ $event->location ?: __('بدون موقع') }} - {{ $event->event_date ?: __('بدون تاريخ') }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</section>

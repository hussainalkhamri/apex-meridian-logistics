@extends('layouts.customer')

@section('title', __('messages.overview'))

@section('content')
@php
    $statusLabels = \App\Support\BookingStatus::labels();
    $latestBooking = $bookings->first();
    $outstandingInvoices = $invoices->filter(fn ($invoice) => $invoice->remaining_amount > 0);
    $metricCards = [
        ['icon' => 'ship', 'label' => __('portal.active'), 'value' => $stats['in_progress'], 'href' => route('customer.shipments.index', ['status' => 'active'])],
        ['icon' => 'badge-dollar-sign', 'label' => __('portal.quotes'), 'value' => $stats['open_quotes'], 'href' => route('customer.order-confirmation')],
        ['icon' => 'receipt-text', 'label' => __('portal.invoices'), 'value' => $outstandingInvoices->count(), 'href' => route('customer.invoices')],
        ['icon' => 'boxes', 'label' => __('portal.total'), 'value' => $stats['total'], 'href' => route('customer.shipments.index', ['reset' => 1])],
    ];
@endphp

<div class="space-y-5">
    <section class="portal-intro rounded-xl bg-hero text-white shadow-card">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1>{{ __('portal.welcome', ['name' => $customer->contact_name ?: $customer->company]) }}</h1>
                <p class="mt-1 text-sm leading-7 text-white/80">{{ __('portal.overview_hint') }}</p>
            </div>
            <a href="{{ route('customer.shipments.create') }}" class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-4 text-sm font-bold text-accent-foreground">
                <i data-lucide="plus" class="h-4 w-4"></i>{{ __('messages.new_request') }}
            </a>
        </div>
    </section>

    <section class="portal-metrics" aria-label="{{ __('messages.overview') }}">
        @foreach($metricCards as $card)
            <a href="{{ $card['href'] }}" class="portal-metric flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="text-xs text-muted-foreground">{{ $card['label'] }}</div>
                    <div class="portal-metric-number mt-2">{{ $card['value'] }}</div>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-secondary text-primary">
                    <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                </span>
            </a>
        @endforeach
    </section>

    <section class="rounded-xl border border-border bg-card p-5 shadow-card" aria-labelledby="requires-action">
        <div class="flex items-center gap-3">
            <i data-lucide="clipboard-check" class="h-5 w-5 text-accent"></i>
            <div>
                <h2 id="requires-action" class="text-base font-bold">{{ __('portal.attention') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('portal.attention_hint') }}</p>
            </div>
        </div>
        <div class="mt-4 divide-y divide-border">
            @if($stats['open_quotes'] > 0)
                <a href="{{ route('customer.order-confirmation') }}" class="flex items-center justify-between gap-3 rounded-lg py-3 text-sm hover:bg-secondary/30">
                    <span>{{ __('portal.quotes_action', ['count' => $stats['open_quotes']]) }}</span>
                    <i data-lucide="arrow-left" class="h-4 w-4 text-accent"></i>
                </a>
            @endif
            @if($outstandingInvoices->isNotEmpty())
                <a href="{{ route('customer.invoices') }}" class="flex flex-wrap items-center justify-between gap-3 rounded-lg py-3 text-sm hover:bg-secondary/30">
                    <span>{{ __('portal.invoices_action', ['count' => $outstandingInvoices->count()]) }}</span>
                    <span class="font-semibold text-destructive" dir="ltr">{{ \App\Models\Booking::formatMoneyTotalsByCurrency($outstandingInvoices, fn ($invoice) => $invoice->remaining_amount, fn ($invoice) => $invoice->effectiveCurrency()) }}</span>
                </a>
            @endif
            @if($stats['open_quotes'] === 0 && $outstandingInvoices->isEmpty())
                <p class="flex items-center gap-2 py-3 text-sm text-muted-foreground"><i data-lucide="check-circle-2" class="h-4 w-4 text-success"></i>{{ __('portal.no_action') }}</p>
            @endif
        </div>
    </section>

    @if($latestBooking)
        <section class="grid gap-4 rounded-xl border border-border bg-card p-5 shadow-card lg:grid-cols-[1fr_1.4fr_auto] lg:items-center">
            <div>
                <div class="text-xs text-muted-foreground">{{ __('portal.latest') }}</div>
                <div class="flex items-center gap-1">
                    <a href="{{ route('customer.shipments.show', $latestBooking) }}" class="font-mono text-sm font-semibold" dir="ltr">{{ $latestBooking->id }}</a>
                    @include('customer.partials.copy-reference', ['reference' => $latestBooking->id])
                </div>
                <div class="text-xs text-muted-foreground">{{ __('portal.updated') }}: {{ $latestBooking->updated_at?->format('Y-m-d H:i') }}</div>
            </div>
            @include('customer.partials.route', ['origin' => $latestBooking->pol, 'destination' => $latestBooking->pod, 'method' => $latestBooking->shipment_type])
            <div class="flex flex-wrap items-center gap-3">
                @include('customer.partials.status-badge', ['status' => $latestBooking->status])
                <a href="{{ route('customer.tracking', ['q' => $latestBooking->shipmentTrack?->id ?: $latestBooking->id]) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-border px-3 text-xs font-semibold hover:bg-secondary"><i data-lucide="radar" class="h-4 w-4"></i>{{ __('messages.tracking') }}</a>
            </div>
        </section>
    @endif

    <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
        <div class="rounded-xl border border-border bg-card p-5 shadow-card">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div>
                    <h2 class="text-base font-black">{{ __('الإشعارات الجديدة') }}</h2>
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('آخر تحديثات موجهة لحسابك.') }}</p>
                </div>
                @if($notifications->isNotEmpty())
                    <form action="{{ route('customer.notifications.read') }}" method="POST">
                        @csrf
                        @foreach($notifications as $notification)
                            <input type="hidden" name="notification_ids[]" value="{{ $notification->id }}">
                        @endforeach
                        <button class="inline-flex h-9 items-center justify-center rounded-lg border border-accent/30 px-3 text-xs font-black text-accent hover:bg-accent/10">{{ __('قراءة الكل') }}</button>
                    </form>
                @endif
            </div>

            <div class="space-y-3">
                @forelse($notifications as $notification)
                    <a href="{{ route('customer.shipments.show', $notification->booking_id) }}" class="block rounded-lg border border-border/70 bg-secondary/20 p-4 text-sm hover:bg-secondary/35">
                        <div class="flex items-start gap-3">
                            <i data-lucide="bell-ring" class="mt-0.5 h-4 w-4 shrink-0 text-accent"></i>
                            <div>
                                <div class="font-bold">{{ $notification->message }}</div>
                                <div class="mt-2 text-[11px] text-muted-foreground">{{ $notification->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="rounded-lg border border-dashed border-border p-8 text-center text-sm text-muted-foreground">{{ __('لا توجد إشعارات معلقة حاليا.') }}</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-3 border-b border-border p-5">
                <div>
                    <h2 class="text-base font-black">{{ __('عروض تنتظر قرارك') }}</h2>
                    <p class="mt-1 text-xs text-muted-foreground">{{ __('راجع الصفحة الكاملة لاتخاذ قرار الموافقة أو الرفض.') }}</p>
                </div>
                <a href="{{ route('customer.order-confirmation') }}" class="text-xs font-black text-accent hover:underline">{{ __('تأكيد الطلب') }}</a>
            </div>
            <div class="grid gap-3 p-4 md:hidden">
                @forelse($openQuotes as $booking)
                    <article class="rounded-xl border border-border bg-background p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-mono text-xs font-black text-accent" dir="ltr">{{ $booking->id }}</div>
                                <div class="mt-1 text-sm font-black">{{ $booking->pol }} → {{ $booking->pod }}</div>
                            </div>
                            <div class="shrink-0 font-black text-primary" dir="ltr">{{ $booking->formatPricingMoney($booking->pricingTotal()) }}</div>
                        </div>
                        <a href="{{ route('customer.order-confirmation') }}" class="mt-3 inline-flex h-9 w-full items-center justify-center rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">{{ __('مراجعة') }}</a>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">{{ __('لا توجد عروض تنتظر قرارك الآن.') }}</div>
                @endforelse
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[620px] text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">{{ __('الطلب') }}</th>
                            <th class="px-5 py-3">{{ __('المسار') }}</th>
                            <th class="px-5 py-3">{{ __('الإجمالي') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($openQuotes as $booking)
                            <tr class="border-t border-border/50 hover:bg-secondary/20">
                                <td class="px-5 py-4 font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->pol }}</div>
                                    <div class="text-xs text-muted-foreground">إلى {{ $booking->pod }}</div>
                                </td>
                                <td class="px-5 py-4 font-black text-primary" dir="ltr">{{ $booking->formatPricingMoney($booking->pricingTotal()) }}</td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('customer.order-confirmation') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">{{ __('مراجعة') }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-muted-foreground">{{ __('لا توجد عروض تنتظر قرارك الآن.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="rounded-xl border border-border bg-card shadow-card">
        <div class="flex flex-col gap-3 border-b border-border p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-black">{{ __('آخر طلبات الشحن') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('ملخص سريع، والتفاصيل الكاملة موجودة في صفحة شحناتي.') }}</p>
            </div>
            <a href="{{ route('customer.shipments.index') }}" class="text-xs font-black text-accent hover:underline">{{ __('فتح صفحة شحناتي') }}</a>
        </div>

        @if($bookings->isEmpty())
            <div class="p-12 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-xl bg-secondary text-primary">
                    <i data-lucide="package-plus" class="h-7 w-7"></i>
                </span>
                <h3 class="mt-4 text-lg font-black">{{ __('لا توجد طلبات بعد') }}</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-muted-foreground">{{ __('ابدأ بطلب شحنة جديدة، وسيظهر هنا ملخص الملف مع العرض والحالة والتتبع.') }}</p>
                <a href="{{ route('customer.shipments.create') }}" class="mt-5 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    {{ __('طلب شحنة جديدة') }}
                </a>
            </div>
        @else
            <div class="grid gap-3 p-4 md:hidden">
                @foreach($bookings->take(6) as $booking)
                    <article class="rounded-xl border border-border bg-background p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-mono text-xs font-black text-accent" dir="ltr">{{ $booking->id }}</div>
                                <div class="mt-1 text-sm font-black">{{ $booking->pol }} → {{ $booking->pod }}</div>
                                <div class="mt-1 text-[11px] text-muted-foreground">{{ optional($booking->created_at)->format('Y-m-d') }}</div>
                            </div>
                            @include('customer.partials.status-badge', ['status' => $booking->status, 'labels' => $statusLabels, 'size' => 'xs'])
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">{{ __('الشحنة') }}</div>
                                <div class="mt-1 font-black">{{ $booking->shipmentDisplay() }}</div>
                            </div>
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">{{ __('عرض السعر') }}</div>
                                <div class="mt-1 font-black text-primary" dir="ltr">{{ $booking->pricingTotal() > 0 ? $booking->formatPricingMoney($booking->pricingTotal()) : '—' }}</div>
                            </div>
                        </div>
                        <a href="{{ route('customer.shipments.show', $booking) }}" class="mt-3 inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">
                            {{ __('التفاصيل') }}
                            <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                        </a>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[860px] text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">{{ __('رقم الطلب') }}</th>
                            <th class="px-5 py-3">{{ __('المسار') }}</th>
                            <th class="px-5 py-3">{{ __('الشحنة') }}</th>
                            <th class="px-5 py-3">{{ __('عرض السعر') }}</th>
                            <th class="px-5 py-3">{{ __('الحالة') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings->take(6) as $booking)
                            <tr class="border-t border-border/50 hover:bg-secondary/20">
                                <td class="px-5 py-4">
                                    <div class="font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ optional($booking->created_at)->format('Y-m-d') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->pol }}</div>
                                    <div class="text-xs text-muted-foreground">إلى {{ $booking->pod }}</div>
                                </td>
                                <td class="px-5 py-4 text-muted-foreground">{{ $booking->shipmentDisplay() }}</td>
                                <td class="px-5 py-4 font-black text-primary" dir="ltr">{{ $booking->pricingTotal() > 0 ? $booking->formatPricingMoney($booking->pricingTotal()) : '—' }}</td>
                                <td class="px-5 py-4">
                                    @include('customer.partials.status-badge', ['status' => $booking->status, 'labels' => $statusLabels, 'size' => 'sm'])
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('customer.shipments.show', $booking) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">
                                        {{ __('التفاصيل') }}
                                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

    <section class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-3 border-b border-border p-5">
                <h2 class="text-base font-black">{{ __('الفواتير الحديثة') }}</h2>
                <a href="{{ route('customer.invoices') }}" class="text-xs font-black text-accent hover:underline">{{ __('كل الفواتير') }}</a>
            </div>
            <div class="divide-y divide-border/60">
                @forelse($invoices->take(4) as $invoice)
                    <a href="{{ route('customer.shipments.show', $invoice->booking_id) }}" class="flex items-center justify-between gap-3 p-4 text-sm hover:bg-secondary/20">
                        <div>
                            <div class="font-mono text-xs font-black" dir="ltr">{{ $invoice->id }}</div>
                            <div class="mt-1 text-[11px] text-muted-foreground">{{ $invoice->status }}</div>
                        </div>
                        <div class="font-black text-primary" dir="ltr">{{ $invoice->formatMoney($invoice->amount) }}</div>
                    </a>
                @empty
                    <div class="p-8 text-center text-sm text-muted-foreground">{{ __('لا توجد فواتير صادرة.') }}</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-3 border-b border-border p-5">
                <h2 class="text-base font-black">{{ __('المستندات') }}</h2>
                <a href="{{ route('customer.documents') }}" class="text-xs font-black text-accent hover:underline">{{ __('كل المستندات') }}</a>
            </div>
            <div class="divide-y divide-border/60">
                @forelse($documents->take(4) as $booking)
                    <a href="{{ route('customer.shipments.show', $booking) }}" class="block p-4 text-sm hover:bg-secondary/20">
                        <div class="font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</div>
                        <div class="mt-1 line-clamp-2 text-xs leading-6 text-muted-foreground">{{ $booking->documents ?: __('مرفقات محفوظة في ملف الطلب.') }}</div>
                    </a>
                @empty
                    <div class="p-8 text-center text-sm text-muted-foreground">{{ __('لا توجد مستندات محفوظة.') }}</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-border bg-card shadow-card">
            <div class="flex items-center justify-between gap-3 border-b border-border p-5">
                <h2 class="text-base font-black">{{ __('التخليص الجمركي') }}</h2>
                <a href="{{ route('customer.customs') }}" class="text-xs font-black text-accent hover:underline">{{ __('كل الملفات') }}</a>
            </div>
            <div class="divide-y divide-border/60">
                @forelse($activeCustoms->take(4) as $booking)
                    <a href="{{ route('customer.shipments.show', $booking) }}" class="flex items-center justify-between gap-3 p-4 text-sm hover:bg-secondary/20">
                        <div>
                            <div class="font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</div>
                            <div class="mt-1 text-[11px] text-muted-foreground">{{ $booking->customs_reference ?: __('بانتظار تحديث التخليص') }}</div>
                        </div>
                        <span class="rounded-full bg-secondary px-2 py-1 text-[11px] font-black">{{ __(\App\Support\BookingStatus::customsLabels()[$booking->customs_status ?: 'pending'] ?? $booking->customs_status) }}</span>
                    </a>
                @empty
                    <div class="p-8 text-center text-sm text-muted-foreground">{{ __('لا توجد ملفات تخليص مطلوبة حاليا.') }}</div>
                @endforelse
            </div>
        </div>
    </section>
</div>
@endsection

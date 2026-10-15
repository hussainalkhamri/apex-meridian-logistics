@extends('layouts.customer')

@section('title', __('شحناتي'))

@section('content')
@php
    $hasFilters = collect($filters)->contains(fn ($value) => filled($value) && $value !== 'all');
    $summaryCards = [
        ['label' => __('إجمالي الطلبات'), 'value' => $stats['total'], 'icon' => 'boxes'],
        ['label' => __('قيد التسعير'), 'value' => $stats['waiting'], 'icon' => 'timer'],
        ['label' => __('عروض تنتظر قرارك'), 'value' => $stats['offers'], 'icon' => 'badge-dollar-sign'],
        ['label' => __('قيد التنفيذ'), 'value' => $stats['active'], 'icon' => 'ship'],
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-border bg-card p-5 shadow-card">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-black">{{ __('شحناتي') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">
                    {{ __('portal.recent_hint') }}
                </p>
            </div>
            <a href="{{ route('customer.shipments.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground">
                <i data-lucide="package-plus" class="h-4 w-4"></i>
                {{ __('طلب شحنة جديدة') }}
            </a>
        </div>
    </section>

    <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        @foreach($summaryCards as $card)
            <article class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-secondary text-primary">
                        <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <div class="text-3xl font-black">{{ $card['value'] }}</div>
                </div>
                <div class="mt-3 text-sm font-black">{{ $card['label'] }}</div>
            </article>
        @endforeach
    </section>

    <section class="rounded-xl border border-border bg-card shadow-card">
        <div class="flex flex-col gap-2 border-b border-border p-5 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-lg font-black">{{ __('سجل الشحنات') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('تفاصيل الطلب، المسار، العرض، التتبع، والفواتير المرتبطة.') }}</p>
            </div>
            <span class="inline-flex h-8 w-fit items-center rounded-full bg-secondary px-3 text-xs font-black">
                {{ $bookings->total() }} / {{ $stats['total'] }} {{ __('ملف') }}
            </span>
        </div>

        <form data-filter-form action="{{ route('customer.shipments.index') }}" method="GET" class="grid gap-3 border-b border-border p-5 md:grid-cols-2 2xl:grid-cols-4">
            <label class="relative">
                <span class="sr-only">{{ __('بحث في الشحنات') }}</span>
                <i data-lucide="search" class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="{{ __('ابحث برقم الطلب، المسار، نوع الشحنة، أو رقم حجز الناقل') }}"
                    class="h-10 w-full rounded-lg border border-border bg-background px-10 text-sm focus:outline-none focus:ring-1 focus:ring-accent"
                >
            </label>
            <label>
                <span class="sr-only">{{ __('تصفية حسب الحالة') }}</span>
                <select name="status" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    @foreach($statusOptions as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['status'] ?? 'all') === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">{{ __('نوع الشحنة') }}</span>
                <select name="type" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="all" @selected(($filters['type'] ?? 'all') === 'all')>{{ __('كل الأنواع') }}</option>
                    @foreach($typeOptions as $type)
                        <option value="{{ $type }}" @selected(($filters['type'] ?? 'all') === $type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                <span class="sr-only">{{ __('من تاريخ') }}</span>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            </label>
            <label>
                <span class="sr-only">{{ __('إلى تاريخ') }}</span>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            </label>
            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-sm font-black text-primary-foreground hover:opacity-95">
                <i data-lucide="filter" class="h-4 w-4"></i>
                {{ __('تطبيق') }}
            </button>
            <a href="{{ route('customer.shipments.index', ['reset' => 1]) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-sm font-black hover:bg-secondary">
                <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                {{ __('مسح') }}
            </a>
        </form>

        @if($bookings->isEmpty())
            <div class="p-12 text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-xl bg-secondary text-primary">
                    <i data-lucide="package-plus" class="h-7 w-7"></i>
                </span>
                <h3 class="mt-4 text-lg font-black">{{ $hasFilters ? __('portal.no_matches') : __('portal.no_data') }}</h3>
                <p class="mx-auto mt-2 max-w-md text-sm leading-7 text-muted-foreground">
                    {{ $hasFilters ? __('portal.clear') : __('portal.recent_hint') }}
                </p>
                <a href="{{ $hasFilters ? route('customer.shipments.index', ['reset' => 1]) : route('customer.shipments.create') }}" class="mt-5 inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    {{ $hasFilters ? __('portal.clear') : __('messages.new_request') }}
                </a>
            </div>
        @else
            <div class="grid gap-3 p-4 md:hidden">
                @foreach($bookings as $booking)
                    @php $invoiceTotal = $booking->invoices->sum('amount'); @endphp
                    <article class="rounded-xl border border-border bg-card p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1"><span class="font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</span>@include('customer.partials.copy-reference', ['reference' => $booking->id])</div>
                                <div class="mt-1 text-sm font-black">{{ $booking->pol }} → {{ $booking->pod }}</div>
                                <div class="mt-1 text-[11px] text-muted-foreground">{{ optional($booking->created_at)->format('Y-m-d') }}</div>
                            </div>
                            @include('customer.partials.status-badge', ['status' => $booking->status, 'labels' => $statusLabels, 'size' => 'xs'])
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-secondary/30 p-2">
                                <div class="text-muted-foreground">{{ __('الشحنة') }}</div>
                                <div class="mt-1 font-black">{{ $booking->shipmentDisplay() }}</div>
                            </div>
                            <div class="rounded-lg bg-secondary/30 p-2">
                                <div class="text-muted-foreground">{{ __('العرض') }}</div>
                                <div class="mt-1 font-black text-primary" dir="ltr">{{ $booking->pricingTotal() > 0 ? $booking->formatPricingMoney($booking->pricingTotal()) : '—' }}</div>
                            </div>
                            <div class="rounded-lg bg-secondary/30 p-2">
                                <div class="text-muted-foreground">{{ __('التتبع') }}</div>
                                <div class="mt-1 font-black">{{ $booking->shipmentTrack?->progress !== null ? $booking->shipmentTrack->progress . '%' : __('لم يبدأ') }}</div>
                            </div>
                            <div class="rounded-lg bg-secondary/30 p-2">
                                <div class="text-muted-foreground">{{ __('الفواتير') }}</div>
                                <div class="mt-1 font-black" dir="ltr">{{ $invoiceTotal > 0 ? $booking->formatPricingMoney($invoiceTotal) : __('لا يوجد') }}</div>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button type="button" data-open-shipment-details data-shipment-details-target="shipment-details-{{ md5($booking->id) }}" data-shipment-details-title="{{ $booking->id }} · {{ $booking->pol }} → {{ $booking->pod }}" class="inline-flex h-9 w-10 items-center justify-center rounded-lg border border-border text-primary hover:bg-secondary" title="{{ __('عرض التفاصيل التي أدخلتها') }}" aria-label="{{ __('عرض التفاصيل التي أدخلتها') }}">
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </button>
                            <a href="{{ route('customer.shipments.show', $booking) }}" class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">
                                {{ __('التفاصيل') }}
                                <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                            </a>
                            <a href="{{ route('customer.shipments.create', ['repeat_id' => $booking->id]) }}" class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-lg border border-accent/30 px-3 text-xs font-black text-accent hover:bg-accent/10">
                                <i data-lucide="copy-plus" class="h-3.5 w-3.5"></i>
                                {{ __('تكرار') }}
                            </a>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full min-w-[980px] text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">{{ __('رقم الطلب') }}</th>
                            <th class="px-5 py-3">{{ __('المسار') }}</th>
                            <th class="px-5 py-3">{{ __('الشحنة') }}</th>
                            <th class="px-5 py-3">{{ __('عرض السعر') }}</th>
                            <th class="px-5 py-3">{{ __('التتبع') }}</th>
                            <th class="px-5 py-3">{{ __('الفواتير') }}</th>
                            <th class="px-5 py-3">{{ __('الحالة') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            @php $invoiceTotal = $booking->invoices->sum('amount'); @endphp
                             <tr class="border-t border-border/50 align-top hover:bg-secondary/20">
                                 <td class="px-5 py-4">
                                     <div class="flex items-center gap-1"><span class="font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</span>@include('customer.partials.copy-reference', ['reference' => $booking->id])</div>
                                     <div class="mt-1 text-[11px] text-muted-foreground">{{ optional($booking->created_at)->format('Y-m-d') }}</div>
                                     <div class="mt-1 text-[11px] text-muted-foreground">{{ __('portal.updated') }}: {{ $booking->updated_at?->format('Y-m-d H:i') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->pol }}</div>
                                    <div class="text-xs text-muted-foreground">إلى {{ $booking->pod }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ data_get($booking->route_details, 'mode', '—') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->shipment_type }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        {{ $booking->containerSummary() ?: __('بدون حاوية محددة') }}
                                    </div>
                                    <div class="mt-1 text-xs text-muted-foreground">{{ number_format($booking->weight_kg, 2) }} كجم / {{ number_format($booking->volume_cbm ?? 0, 2) }} CBM</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-black text-primary" dir="ltr">{{ $booking->pricingTotal() > 0 ? $booking->formatPricingMoney($booking->pricingTotal()) : '—' }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ __('الصلاحية:') }} {{ $booking->validity_date?->toDateString() ?: '—' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->shipmentTrack?->status ?: __('لم يبدأ') }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">{{ $booking->shipmentTrack ? $booking->shipmentTrack->progress . '%' : __('بعد الاعتماد والحجز') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->invoices->count() }} {{ __('فاتورة') }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground" dir="ltr">{{ $invoiceTotal > 0 ? $booking->formatPricingMoney($invoiceTotal) : __('لا يوجد مبلغ') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    @include('customer.partials.status-badge', ['status' => $booking->status, 'labels' => $statusLabels, 'size' => 'sm'])
                                </td>
                                <td class="px-5 py-4">
                                    <button type="button" data-open-shipment-details data-shipment-details-target="shipment-details-{{ md5($booking->id) }}" data-shipment-details-title="{{ $booking->id }} · {{ $booking->pol }} → {{ $booking->pod }}" class="mb-2 inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border text-primary hover:bg-secondary" title="{{ __('عرض التفاصيل التي أدخلتها') }}" aria-label="{{ __('عرض التفاصيل التي أدخلتها') }}">
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>
                                    <a href="{{ route('customer.shipments.show', $booking) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">
                                        {{ __('التفاصيل') }}
                                         <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                                     </a>
                                    <a href="{{ route('customer.shipments.create', ['repeat_id' => $booking->id]) }}" class="mt-2 inline-flex h-8 items-center justify-center gap-1.5 rounded-lg border border-accent/30 px-2.5 text-[11px] font-black text-accent hover:bg-accent/10">
                                        <i data-lucide="copy-plus" class="h-3.5 w-3.5"></i>
                                        {{ __('تكرار') }}
                                    </a>
                                 </td>
                             </tr>
                         @endforeach
                     </tbody>
                 </table>
             </div>
        @endif
        @if($bookings->hasPages())
            <div class="border-t border-border p-4">{{ $bookings->links() }}</div>
        @endif
    </section>

    @if($bookings->isNotEmpty())
        <div id="customer-shipment-details-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="customer-shipment-details-modal-title">
            <div class="w-full max-w-5xl overflow-hidden rounded-xl border border-border bg-card shadow-elegant" data-shipment-details-card>
                <div class="flex items-start justify-between gap-3 border-b border-border p-4">
                    <div>
                        <h2 id="customer-shipment-details-modal-title" class="text-base font-black">{{ __('التفاصيل التي أدخلتها') }}</h2>
                        <p id="customer-shipment-details-modal-subtitle" class="mt-1 text-xs text-muted-foreground"></p>
                    </div>
                    <button type="button" data-close-shipment-details class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-border hover:bg-secondary" aria-label="{{ __('إغلاق') }}">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>
                <div id="customer-shipment-details-modal-body" class="max-h-[calc(90vh-5rem)] overflow-y-auto p-4"></div>
            </div>
        </div>

        @foreach($bookings as $booking)
            <template id="shipment-details-{{ md5($booking->id) }}">
                @include('customer.partials.booking-entered-details', ['booking' => $booking, 'compact' => false])
            </template>
        @endforeach
    @endif
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('customer-shipment-details-modal');
        const body = document.getElementById('customer-shipment-details-modal-body');
        const subtitle = document.getElementById('customer-shipment-details-modal-subtitle');
        let modalTrigger;

        if (!modal || !body || !subtitle) return;

        function openShipmentDetails(button) {
            const template = document.getElementById(button.dataset.shipmentDetailsTarget);
            if (!template) return;
            modalTrigger = button;

            body.replaceChildren(template.content.cloneNode(true));
            subtitle.textContent = button.dataset.shipmentDetailsTitle || '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
            modal.querySelector('[data-close-shipment-details]').focus();

            if (window.lucide) {
                window.lucide.createIcons();
            }
        }

        function closeShipmentDetails() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            body.replaceChildren();
            subtitle.textContent = '';
            document.body.classList.remove('overflow-hidden');
            modalTrigger?.focus();
        }

        document.querySelectorAll('[data-open-shipment-details]').forEach((button) => {
            button.addEventListener('click', () => openShipmentDetails(button));
        });

        document.querySelectorAll('[data-close-shipment-details]').forEach((button) => {
            button.addEventListener('click', closeShipmentDetails);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeShipmentDetails();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeShipmentDetails();
            }
            if (event.key === 'Tab' && !modal.classList.contains('hidden')) {
                const controls = [...modal.querySelectorAll('button, a[href], input, select, textarea')].filter(el => el.getClientRects().length);
                if (event.shiftKey && document.activeElement === controls[0]) { event.preventDefault(); controls.at(-1).focus(); }
                else if (!event.shiftKey && document.activeElement === controls.at(-1)) { event.preventDefault(); controls[0].focus(); }
            }
        });
    });
</script>
@endsection

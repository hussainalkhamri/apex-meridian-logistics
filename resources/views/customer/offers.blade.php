@extends('layouts.customer')

@section('title', __('العروض والقرارات'))

@section('content')
@php
    $summaryCards = [
        ['label' => __('كل العروض'), 'value' => $stats['total'], 'icon' => 'files'],
        ['label' => __('تنتظر قرارك'), 'value' => $stats['pending'], 'icon' => 'hourglass'],
        ['label' => __('تمت الموافقة'), 'value' => $stats['accepted'], 'icon' => 'check-circle-2'],
        ['label' => __('إجمالي العروض'), 'value' => $stats['value_label'], 'icon' => 'badge-dollar-sign'],
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-border bg-card p-5 shadow-card">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-black">{{ __('العروض والقرارات') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">
                    {{ __('صفحة مخصصة لكل عروض السعر الصادرة من فريق المبيعات، مع حالة القرار والصلاحية وتفاصيل السعر.') }}
                </p>
            </div>
            <a href="{{ route('customer.shipments.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground">
                <i data-lucide="package-plus" class="h-4 w-4"></i>
                {{ __('طلب عرض جديد') }}
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
                    <div class="text-2xl font-black">{{ $card['value'] }}</div>
                </div>
                <div class="mt-3 text-sm font-black">{{ $card['label'] }}</div>
            </article>
        @endforeach
    </section>

    <section class="rounded-xl border border-border bg-card shadow-card">
        <div class="border-b border-border p-5">
            <h2 class="text-lg font-black">{{ __('سجل العروض') }}</h2>
            <p class="mt-1 text-xs text-muted-foreground">{{ __('راجع العرض من صفحة التفاصيل لاتخاذ قرار الموافقة أو الرفض.') }}</p>
        </div>

        @if($bookings->isEmpty())
            <div class="p-12 text-center text-sm text-muted-foreground">{{ __('لا توجد عروض سعر صادرة لحسابك حتى الآن.') }}</div>
        @else
            @include('customer.partials.list-search', ['tableId' => 'portal-offers'])
            <div class="overflow-x-auto">
                <table id="portal-offers" data-responsive-table class="w-full min-w-[960px] text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">{{ __('الطلب') }}</th>
                            <th class="px-5 py-3">{{ __('المسار') }}</th>
                            <th class="px-5 py-3">{{ __('تفاصيل السعر') }}</th>
                            <th class="px-5 py-3">{{ __('العبور والصلاحية') }}</th>
                            <th class="px-5 py-3">{{ __('قرار العميل') }}</th>
                            <th class="px-5 py-3">{{ __('الحالة') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            @php $status = $statusLabels[$booking->status] ?? ['label' => $booking->status, 'class' => 'bg-secondary text-foreground border-border']; @endphp
                            <tr class="border-t border-border/50 align-top hover:bg-secondary/20">
                                <td class="px-5 py-4">
                                    <div class="font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ optional($booking->priced_at)->format('Y-m-d H:i') ?: optional($booking->created_at)->format('Y-m-d') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->pol }}</div>
                                    <div class="text-xs text-muted-foreground">إلى {{ $booking->pod }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ $booking->shipment_type }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-lg font-black text-primary" dir="ltr">{{ $booking->formatPricingMoney($booking->pricingTotal()) }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">
                                        شحن: {{ $booking->formatPricingMoney($booking->ocean_freight ?? 0) }} / محلي: {{ $booking->formatPricingMoney($booking->origin_charges ?? 0) }}
                                        @if($booking->shouldShowExwCharges())
                                            / EXW: {{ $booking->formatPricingMoney($booking->exw_charges ?? 0) }}
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->transit_days ?: '—' }} {{ __('يوم') }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">{{ $booking->validity_date?->toDateString() ?: __('صلاحية غير محددة') }}</div>
                                    @include('customer.partials.quote-validity', ['booking' => $booking])
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">
                                        @if($booking->customer_decision === 'accepted')
                                            موافقة
                                        @elseif($booking->customer_decision === 'rejected')
                                            رفض
                                        @else
                                            {{ __('لم يتم اتخاذ قرار') }}
                                        @endif
                                    </div>
                                    <div class="mt-1 max-w-[220px] text-xs leading-6 text-muted-foreground">{{ $booking->customer_decision_note ?: '—' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black {{ $status['class'] }}">{{ __($status['label']) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('customer.shipments.show', $booking) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">
                                        {{ __('فتح العرض') }}
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
</div>
@endsection

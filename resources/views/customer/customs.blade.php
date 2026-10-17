@extends('layouts.customer')

@section('title', __('التخليص الجمركي') . ' — ' . __('messages.site_name'))

@section('content')
@php
    $summaryCards = [
        ['label' => __('ملفات التخليص'), 'value' => $stats['total'], 'icon' => 'shield-check'],
        ['label' => __('قيد المراجعة'), 'value' => $stats['pending'], 'icon' => 'clipboard-list'],
        ['label' => __('الفحص والمعاينة'), 'value' => $stats['inspection'], 'icon' => 'scan-search'],
        ['label' => __('تم التخليص'), 'value' => $stats['cleared'], 'icon' => 'check-check'],
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-border bg-card p-5 shadow-card">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-black">{{ __('التخليص الجمركي') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-7 text-muted-foreground">
                    {{ __('متابعة دقيقة لملفات التخليص المرتبطة بشحناتك: الحالة، المرجع، البيان، الرسوم، والملاحظات.') }}
                </p>
            </div>
            <a href="{{ route('customer.shipments.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground">
                <i data-lucide="plus" class="h-4 w-4"></i>
                {{ __('طلب تخليص مع شحنة') }}
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
                <h2 class="text-lg font-black">{{ __('ملفات التخليص') }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ __('كل ملف ظاهر هنا مرتبط بطلب شحن داخل النظام.') }}</p>
            </div>
            <span class="inline-flex h-8 w-fit items-center rounded-full bg-secondary px-3 text-xs font-black">رسوم مسجلة: {{ $stats['fees_label'] }}</span>
        </div>

        @if($bookings->isEmpty())
            <div class="p-12 text-center text-sm text-muted-foreground">{{ __('لا توجد ملفات تخليص مرتبطة بحسابك حاليا.') }}</div>
        @else
            @include('customer.partials.list-search', ['tableId' => 'portal-customs'])
            <div class="overflow-x-auto">
                <table id="portal-customs" data-responsive-table class="w-full min-w-[1040px] text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">{{ __('الطلب') }}</th>
                            <th class="px-5 py-3">{{ __('المسار') }}</th>
                            <th class="px-5 py-3">{{ __('حالة التخليص') }}</th>
                            <th class="px-5 py-3">{{ __('المرجع والبيان') }}</th>
                            <th class="px-5 py-3">{{ __('الرسوم') }}</th>
                            <th class="px-5 py-3">{{ __('ملاحظات') }}</th>
                            <th class="px-5 py-3">{{ __('حالة الطلب') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($bookings as $booking)
                            @php
                                $status = $statusLabels[$booking->status] ?? ['label' => $booking->status, 'class' => 'bg-secondary text-foreground border-border'];
                                $customsStatus = $booking->customs_status ?: 'pending';
                                $services = [
                                    data_get($booking->route_details, 'services.customs_requested') ? __('تخليص مطلوب من العميل') : null,
                                    data_get($booking->route_details, 'services.door_delivery_requested') ? __('تسليم إلى الباب') : null,
                                ];
                            @endphp
                            <tr class="border-t border-border/50 align-top hover:bg-secondary/20">
                                <td class="px-5 py-4">
                                    <div class="font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ optional($booking->created_at)->format('Y-m-d') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->pol }}</div>
                                    <div class="text-xs text-muted-foreground">إلى {{ $booking->pod }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ collect($services)->filter()->implode(' / ') ?: __('متابعة جمركية') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-black">{{ __($customsLabels[$customsStatus] ?? $customsStatus) }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">{{ __('portal.updated') }}: {{ $booking->updated_at?->format('Y-m-d H:i') }}</div>
                                    <div class="mt-1 text-xs text-muted-foreground">{{ __('تاريخ التخليص:') }} {{ $booking->customs_cleared_at?->format('Y-m-d') ?: '—' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="text-xs">{{ __('المرجع:') }} <span class="font-bold">{{ $booking->customs_reference ?: '—' }}</span></div>
                                    <div class="mt-1 text-xs">{{ __('البيان:') }} <span class="font-bold">{{ $booking->customs_declaration_no ?: '—' }}</span></div>
                                    <div class="mt-1 text-xs">{{ __('البوليصة/البايان:') }} <span class="font-bold">{{ $booking->customs_bayan_no ?: '—' }}</span></div>
                                </td>
                                <td class="px-5 py-4 font-black text-primary" dir="ltr">{{ $booking->formatPricingMoney($booking->customs_fees ?? 0) }}</td>
                                <td class="px-5 py-4">
                                    <div class="max-w-[260px] text-xs leading-6 text-muted-foreground">{{ $booking->customs_notes ?: __('لا توجد ملاحظات جمركية منشورة.') }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black {{ $status['class'] }}">{{ __($status['label']) }}</span>
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
</div>
@endsection

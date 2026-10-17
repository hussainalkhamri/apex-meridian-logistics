@extends('layouts.customer')

@section('title', __('تأكيد الطلب') . ' — ' . __('messages.site_name'))

@section('head')
<style>
    .customer-order-decision-form textarea[name="note"] {
        min-height: 5rem;
        height: auto;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.75rem;
        line-height: 1.5rem;
    }
</style>
@endsection

@section('content')
@php
    $summaryCards = [
        ['label' => __('كل العروض المستلمة'), 'value' => $stats['total'], 'icon' => 'files'],
        ['label' => __('تنتظر تأكيدك'), 'value' => $stats['pending'], 'icon' => 'hourglass'],
        ['label' => __('طلبات معتمدة'), 'value' => $stats['accepted'], 'icon' => 'check-circle-2'],
        ['label' => __('إجمالي قيمة العروض'), 'value' => $stats['value_label'], 'icon' => 'badge-dollar-sign'],
    ];
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-border bg-card p-5 shadow-card">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-accent/25 bg-accent/10 px-3 py-1 text-[11px] font-black text-accent">
                    <i data-lucide="clipboard-check" class="h-3.5 w-3.5"></i>
                    {{ __('قرار العميل') }}
                </div>
                <h1 class="mt-3 text-2xl font-black">{{ __('تأكيد الطلب') }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-7 text-muted-foreground">
                    {{ __('portal.attention_hint') }}
                </p>
            </div>
            <a href="{{ route('customer.shipments.create') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground">
                <i data-lucide="package-plus" class="h-4 w-4"></i>
                {{ __('طلب شحنة جديد') }}
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

    <section class="space-y-4">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="flex items-center gap-2 text-lg font-black">
                <i data-lucide="timer" class="h-5 w-5 text-accent"></i>
                <span>{{ __('طلبات تنتظر تأكيدك') }}</span>
            </h2>
            <span class="inline-flex w-fit rounded-full bg-secondary px-3 py-1 text-xs font-black">{{ $pending->count() }} {{ __('طلب') }}</span>
        </div>

        @if($pending->isEmpty())
            <div class="rounded-xl border border-dashed border-border bg-card p-10 text-center text-sm text-muted-foreground">
                {{ __('لا توجد عروض سعر تنتظر قرارك حالياً.') }}
            </div>
        @else
            <div class="grid gap-3 xl:grid-cols-2 2xl:grid-cols-3">
                @foreach($pending as $booking)
                    @php
                        $status = $statusLabels[$booking->status] ?? ['label' => $booking->status, 'class' => 'bg-secondary text-foreground border-border'];
                        $customPricingCharges = $booking->customPricingCharges();
                    @endphp
                    <article class="rounded-lg border border-border bg-card p-4 shadow-sm transition hover:border-accent/40 hover:shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-mono text-[11px] font-black text-muted-foreground" dir="ltr">{{ $booking->id }}</div>
                                <h3 class="mt-1 truncate text-sm font-black">{{ $booking->pol }} <span class="text-muted-foreground">→</span> {{ $booking->pod }}</h3>
                                <div class="mt-1 truncate text-[11px] text-muted-foreground">
                                    {{ $booking->shipmentDisplay() }}
                                </div>
                            </div>
                            <span class="inline-flex shrink-0 rounded-md border px-2 py-1 text-[10px] font-black {{ $status['class'] }}">{{ __($status['label']) }}</span>
                        </div>

                        <div class="mt-3 flex items-center justify-between rounded-md bg-secondary/45 px-3 py-2">
                            <span class="text-[11px] font-bold text-muted-foreground">{{ __('الإجمالي') }}</span>
                            <span class="text-lg font-black text-primary" dir="ltr">{{ $booking->formatPricingMoney($booking->pricingTotal()) }}</span>
                        </div>

                        <div class="mt-3 grid grid-cols-2 gap-x-4 gap-y-1.5 text-[11px]">
                            <div class="flex justify-between gap-2"><span class="text-muted-foreground">Ocean</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->ocean_freight ?? 0) }}</span></div>
                            <div class="flex justify-between gap-2"><span class="text-muted-foreground">Local</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->origin_charges ?? 0) }}</span></div>
                            @if($booking->shouldShowExwCharges())
                                <div class="flex justify-between gap-2"><span class="text-muted-foreground">EXW</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->exw_charges ?? 0) }}</span></div>
                            @endif
                            <div class="flex justify-between gap-2"><span class="text-muted-foreground">DTHC</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->dthc ?? 0) }}</span></div>
                            <div class="flex justify-between gap-2"><span class="text-muted-foreground">War Risk</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->war_risk_surcharge ?? 0) }}</span></div>
                            <div class="flex justify-between gap-2"><span class="text-muted-foreground">Other</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->other_charges ?? 0) }}</span></div>
                            @foreach($customPricingCharges as $customPricingCharge)
                                <div class="flex justify-between gap-2"><span class="text-muted-foreground">{{ $customPricingCharge['label'] }}</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($customPricingCharge['amount'] ?? 0) }}</span></div>
                            @endforeach
                            <div class="flex justify-between gap-2"><span class="text-muted-foreground">Transit</span><span class="font-bold">{{ $booking->transit_days ?: '—' }} {{ __('يوم') }}</span></div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-x-4 gap-y-1 border-t border-border/60 pt-2 text-[11px] text-muted-foreground">
                            <span>{{ __('الصلاحية:') }} {{ $booking->validity_date?->toDateString() ?: '—' }}</span>
                            <span>التسعير: {{ $booking->priced_at?->format('Y-m-d H:i') ?: '—' }}</span>
                        </div>

                        @if($booking->pricing_notes)
                            <div class="mt-2 line-clamp-2 text-[11px] leading-5 text-muted-foreground">{{ $booking->pricing_notes }}</div>
                        @endif

                        @include('customer.partials.quote-validity', ['booking' => $booking])
                        <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-border pt-3">
                            <a href="{{ route('customer.shipments.show', $booking) }}" title="{{ __('التفاصيل') }}" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md border border-border px-2.5 text-[11px] font-black hover:bg-secondary">
                                <i data-lucide="eye" class="h-3.5 w-3.5"></i>
                                {{ __('التفاصيل') }}
                            </a>
                            <a href="{{ route('quote.show', $booking->id) }}" target="_blank" title="{{ __('طباعة العرض') }}" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md border border-border px-2.5 text-[11px] font-black hover:bg-secondary">
                                <i data-lucide="printer" class="h-3.5 w-3.5"></i>
                                {{ __('طباعة العرض') }}
                            </a>
                        </div>

                        <div class="mt-3 border-t border-border pt-3" data-customer-decision-card>
                            <div class="grid gap-2 md:grid-cols-2">
                                <button type="button" data-show-customer-decision data-target="accept-decision-{{ md5($booking->id) }}" data-decision-modal-title="{{ __('ملاحظة الاعتماد') }}" data-decision-modal-subtitle="{{ $booking->id }} · {{ $booking->pol }} → {{ $booking->pod }}" class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-md border border-success/30 bg-success/10 px-3 text-[11px] font-black text-success hover:bg-success/15">
                                    <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                    {{ __('اعتماد الطلب') }}
                                </button>
                                <button type="button" data-show-customer-decision data-target="reject-decision-{{ md5($booking->id) }}" data-decision-modal-title="{{ __('سبب الرفض / الملاحظة') }}" data-decision-modal-subtitle="{{ $booking->id }} · {{ $booking->pol }} → {{ $booking->pod }}" class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-md border border-destructive/25 bg-destructive/10 px-3 text-[11px] font-black text-destructive hover:bg-destructive/15">
                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                    {{ __('رفض الطلب') }}
                                </button>
                            </div>

                            <div class="hidden">
                            <form id="accept-decision-{{ md5($booking->id) }}" data-customer-decision-panel action="{{ route('customer.shipments.accept', $booking) }}" method="POST" class="customer-order-decision-form hidden space-y-2 rounded-lg border border-success/25 bg-success/5 p-3">
                                @csrf
                                <input type="hidden" name="return_to" value="order-confirmation">
                                <label class="block text-xs font-black text-foreground">{{ __('ملاحظة الاعتماد') }}</label>
                                <textarea name="note" rows="1" class="h-8 w-full resize-none rounded-md border border-border bg-background px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-success" placeholder="{{ __('ملاحظة الاعتماد') }}"></textarea>
                                <button type="submit" class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-md border border-success/30 bg-success/10 px-3 text-[11px] font-black text-success hover:bg-success/15">
                                    <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                    {{ __('اعتماد الطلب') }}
                                </button>
                            </form>
                            <form id="reject-decision-{{ md5($booking->id) }}" data-customer-decision-panel action="{{ route('customer.shipments.reject', $booking) }}" method="POST" class="customer-order-decision-form hidden space-y-2 rounded-lg border border-destructive/25 bg-destructive/5 p-3">
                                @csrf
                                <input type="hidden" name="return_to" value="order-confirmation">
                                <label class="block text-xs font-black text-foreground">{{ __('سبب الرفض / الملاحظة') }}</label>
                                <textarea name="note" rows="1" class="h-8 w-full resize-none rounded-md border border-border bg-background px-2 py-1.5 text-[11px] focus:outline-none focus:ring-1 focus:ring-destructive" placeholder="{{ __('سبب الرفض') }}"></textarea>
                                <button type="submit" class="inline-flex h-8 w-full items-center justify-center gap-1.5 rounded-md border border-destructive/25 bg-destructive/10 px-3 text-[11px] font-black text-destructive hover:bg-destructive/15">
                                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                    {{ __('رفض الطلب') }}
                                </button>
                            </form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <div id="customer-order-decision-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="customer-order-decision-modal-title">
        <div class="w-full max-w-lg overflow-hidden rounded-xl border border-border bg-card shadow-elegant">
            <div class="flex items-start justify-between gap-3 border-b border-border p-4">
                <div>
                    <h2 id="customer-order-decision-modal-title" class="text-base font-black">{{ __('ملاحظة القرار') }}</h2>
                    <p id="customer-order-decision-modal-subtitle" class="mt-1 text-xs text-muted-foreground"></p>
                </div>
                <button type="button" data-close-customer-decision-modal class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-border hover:bg-secondary" aria-label="{{ __('إغلاق') }}">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <div id="customer-order-decision-modal-body" class="p-4"></div>
        </div>
    </div>

    <section class="rounded-xl border border-border bg-card shadow-card">
        <div class="border-b border-border p-5">
            <h2 class="text-lg font-black">{{ __('سجل قرارات الطلبات') }}</h2>
            <p class="mt-1 text-xs text-muted-foreground">{{ __('الطلبات التي تم اعتمادها أو رفضها من هذا الحساب.') }}</p>
        </div>

        @if($decided->isEmpty())
            <div class="p-10 text-center text-sm text-muted-foreground">{{ __('لا توجد قرارات سابقة لهذا الحساب.') }}</div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-5 py-3">{{ __('الطلب') }}</th>
                            <th class="px-5 py-3">{{ __('المسار') }}</th>
                            <th class="px-5 py-3">{{ __('الإجمالي') }}</th>
                            <th class="px-5 py-3">{{ __('قرار العميل') }}</th>
                            <th class="px-5 py-3">{{ __('الملاحظة') }}</th>
                            <th class="px-5 py-3">{{ __('الحالة') }}</th>
                            <th class="px-5 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($decided as $booking)
                            @php $status = $statusLabels[$booking->status] ?? ['label' => $booking->status, 'class' => 'bg-secondary text-foreground border-border']; @endphp
                            <tr class="border-t border-border/50 align-top hover:bg-secondary/20">
                                <td class="px-5 py-4">
                                    <div class="font-mono text-xs font-black" dir="ltr">{{ $booking->id }}</div>
                                    <div class="mt-1 text-[11px] text-muted-foreground">{{ $booking->customer_decided_at?->format('Y-m-d H:i') ?: '—' }}</div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-bold">{{ $booking->pol }}</div>
                                    <div class="text-xs text-muted-foreground">إلى {{ $booking->pod }}</div>
                                </td>
                                <td class="px-5 py-4 font-black text-primary" dir="ltr">{{ $booking->formatPricingMoney($booking->pricingTotal()) }}</td>
                                <td class="px-5 py-4 font-black">
                                    @if($booking->customer_decision === 'accepted' || in_array($booking->status, ['approved', 'booked'], true))
                                        موافقة
                                    @elseif($booking->customer_decision === 'rejected')
                                        رفض
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-5 py-4 max-w-[260px] text-xs leading-6 text-muted-foreground">{{ $booking->customer_decision_note ?: '—' }}</td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black {{ $status['class'] }}">{{ __($status['label']) }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <a href="{{ route('customer.shipments.show', $booking) }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border px-3 text-xs font-black hover:bg-secondary">
                                        {{ __('عرض') }}
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

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('customer-order-decision-modal');
        const modalTitle = document.getElementById('customer-order-decision-modal-title');
        const modalSubtitle = document.getElementById('customer-order-decision-modal-subtitle');
        const modalBody = document.getElementById('customer-order-decision-modal-body');

        if (!modal || !modalTitle || !modalSubtitle || !modalBody) return;

        function closeDecisionModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            modalBody.replaceChildren();
            modalTitle.textContent = 'ملاحظة القرار';
            modalSubtitle.textContent = '';
            document.body.classList.remove('overflow-hidden');
        }

        document.querySelectorAll('[data-show-customer-decision]').forEach((button) => {
            button.addEventListener('click', () => {
                const form = document.getElementById(button.dataset.target);

                if (!form) return;

                const modalForm = form.cloneNode(true);
                modalForm.removeAttribute('id');
                modalForm.classList.remove('hidden');

                modalTitle.textContent = button.dataset.decisionModalTitle || 'ملاحظة القرار';
                modalSubtitle.textContent = button.dataset.decisionModalSubtitle || '';
                modalBody.replaceChildren(modalForm);
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
                modalForm.querySelector('textarea[name="note"]')?.focus();
            });
        });

        document.querySelectorAll('[data-close-customer-decision-modal]').forEach((button) => {
            button.addEventListener('click', closeDecisionModal);
        });

        modal.addEventListener('click', (event) => {
            if (event.target === modal) {
                closeDecisionModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeDecisionModal();
            }
        });
    });
</script>
@endsection

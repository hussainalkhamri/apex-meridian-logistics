@extends('layouts.customer')

@section('title', __('تفاصيل الطلب ') . $booking->id)

@section('content')
@php
    $customsLabels = \App\Support\BookingStatus::customsLabels();
    $requestedServices = collect([
        data_get($booking->route_details, 'services.customs_requested') ? __('تخليص جمركي') : null,
        data_get($booking->route_details, 'services.door_delivery_requested') ? __('تسليم إلى الباب') : null,
        data_get($booking->route_details, 'services.cargo_insurance_requested') ? __('تأمين على الشحنة') : null,
    ])->filter()->values();
    $isLandBooking = $booking->isLandShipment();
    $landPricingLines = $isLandBooking ? $booking->landPricingLines() : [];
    $customPricingCharges = $booking->customPricingCharges();
@endphp

<div class="space-y-6">
    <section class="rounded-xl border border-border bg-card p-5 shadow-card">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="flex items-center gap-1"><span class="font-mono text-xs text-muted-foreground" dir="ltr">{{ $booking->id }}</span>@include('customer.partials.copy-reference', ['reference' => $booking->id])</div>
                <h1 class="mt-1 text-2xl font-black">{{ __('تفاصيل طلب الشحن') }}</h1>
            </div>
            @include('customer.partials.status-badge', ['status' => $booking->status, 'size' => 'md'])
        </div>
        <div class="mt-4 grid gap-5 border-t border-border pt-4 lg:grid-cols-2">
            @include('customer.partials.route', ['origin' => $booking->pol, 'destination' => $booking->pod, 'method' => $booking->shipment_type])
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs text-muted-foreground">{{ __('portal.method') }}</dt><dd class="mt-1 font-semibold">{{ $booking->shipmentDisplay() }}</dd></div>
                <div><dt class="text-xs text-muted-foreground">{{ __('portal.updated') }}</dt><dd class="mt-1" dir="ltr">{{ $booking->updated_at?->format('Y-m-d H:i') }}</dd></div>
                @if($booking->shipmentTrack?->eta)
                    <div><dt class="text-xs text-muted-foreground">{{ __('portal.eta') }}</dt><dd class="mt-1" dir="ltr">{{ $booking->shipmentTrack->eta->format('Y-m-d') }}</dd></div>
                @endif
            </dl>
        </div>
    </section>

    <nav class="portal-section-nav" aria-label="{{ __('portal.cargo') }}">
        <a href="#shipment-cargo">{{ __('portal.cargo') }}</a>
        <a href="#shipment-quote">{{ __('messages.offers_and_decisions') }}</a>
        <a href="#shipment-tracking">{{ __('messages.tracking') }}</a>
        <a href="#shipment-customs">{{ __('messages.customs_clearance') }}</a>
        <a href="#shipment-activity">{{ __('messages.event_log') }}</a>
    </nav>

    @include('customer.partials.shipment-timeline', ['booking' => $booking])

    @if($notifications->isNotEmpty())
        <section class="rounded-xl border border-accent/25 bg-accent/5 p-4">
            <div class="mb-2 flex items-center justify-between">
                <h2 class="text-sm font-black">{{ __('إشعارات هذا الطلب') }}</h2>
                <form action="{{ route('customer.notifications.read') }}" method="POST">
                    @csrf
                    <input type="hidden" name="booking_id" value="{{ $booking->id }}">
                    <button class="text-xs font-bold text-accent hover:underline">{{ __('تعليم كمقروء') }}</button>
                </form>
            </div>
            <div class="space-y-2">
                @foreach($notifications as $notification)
                    <div class="rounded-lg border border-border bg-card p-3 text-sm">
                        <div class="font-bold">{{ $notification->message }}</div>
                        <div class="mt-1 text-[11px] text-muted-foreground">{{ $notification->created_at->diffForHumans() }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="grid gap-4 lg:grid-cols-[1.1fr_0.9fr]">
        <div id="shipment-cargo" data-section class="rounded-xl border border-border bg-card p-5 shadow-card">
            <h2 class="mb-4 text-base font-black">{{ __('بيانات الشحنة') }}</h2>
            @include('customer.partials.booking-entered-details', ['booking' => $booking])
        </div>

        <div id="shipment-quote" data-section class="rounded-xl border border-border bg-card p-5 shadow-card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-black">{{ __('عرض السعر الرسمي') }}</h2>
                @if($booking->pricingTotal() > 0)
                    <a href="{{ route('quote.show', $booking->id) }}" target="_blank" class="text-xs font-bold text-accent hover:underline">{{ __('طباعة العرض') }}</a>
                @endif
            </div>

            @if($booking->pricingTotal() <= 0)
                <div class="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                    {{ __('لم يتم إصدار عرض السعر بعد. سيظهر هنا بعد إرساله من قسم المبيعات.') }}
                </div>
            @else
                @include('customer.partials.quote-validity', ['booking' => $booking])
                @if($isLandBooking && count($landPricingLines))
                    <div class="space-y-3 text-sm">
                        <div class="overflow-x-auto rounded-lg border border-border/70">
                            <table class="w-full min-w-[520px] text-right text-xs">
                                <thead class="bg-secondary/50 text-muted-foreground">
                                    <tr>
                                        <th class="px-3 py-2">{{ __('المسار') }}</th>
                                        <th class="px-3 py-2 text-center">{{ __('الوزن بالطن') }}</th>
                                        <th class="px-3 py-2 text-left">{{ __('سعر الطن الواحد') }}</th>
                                        <th class="px-3 py-2 text-left">{{ __('المجموع') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border/50">
                                    @foreach($landPricingLines as $line)
                                        <tr>
                                            <td class="px-3 py-2 font-bold">{{ $line['from_city'] }} &rarr; {{ $line['to_city'] }}</td>
                                            <td class="px-3 py-2 text-center font-bold" dir="ltr">{{ rtrim(rtrim(number_format((float) ($line['qty'] ?? 0), 2), '0'), '.') }} {{ __('طن') }}</td>
                                            <td class="px-3 py-2 text-left font-bold" dir="ltr">{{ $booking->formatPricingMoney((float) ($line['unit_price'] ?? 0)) }}</td>
                                            <td class="px-3 py-2 text-left font-black text-primary" dir="ltr">{{ $booking->formatPricingMoney((float) ($line['total'] ?? 0)) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if($customPricingCharges !== [])
                            <div class="space-y-2 border-t border-border/60 pt-2">
                                @foreach($customPricingCharges as $customPricingCharge)
                                    <div class="flex justify-between text-sm">
                                        <span class="font-bold text-muted-foreground">{{ $customPricingCharge['label'] }}</span>
                                        <span class="font-black text-primary" dir="ltr">{{ $booking->formatPricingMoney($customPricingCharge['amount'] ?? 0) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="flex justify-between pt-2 text-lg font-black text-primary"><span>{{ __('الإجمالي') }}</span><span dir="ltr">{{ $booking->formatPricingMoney($booking->pricingTotal()) }}</span></div>
                        <div class="grid gap-2 pt-2 text-xs text-muted-foreground sm:grid-cols-2">
                            <div>{{ __('مدة العبور:') }} {{ $booking->transit_days ?: '—' }} {{ __('يوم') }}</div>
                            <div>{{ __('الصلاحية:') }} {{ $booking->validity_date?->toDateString() ?: '—' }}</div>
                        </div>
                        @if($booking->pricing_notes)
                            <div class="rounded-lg bg-secondary/30 p-3 text-xs leading-6 text-muted-foreground">{{ $booking->pricing_notes }}</div>
                        @endif
                    </div>
                @else
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between border-b border-border/60 pb-2"><span>Ocean Freight</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->ocean_freight ?? 0) }}</span></div>
                    <div class="flex justify-between border-b border-border/60 pb-2"><span>Local Charges</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->origin_charges ?? 0) }}</span></div>
                    @if($booking->shouldShowExwCharges())
                        <div class="flex justify-between border-b border-border/60 pb-2"><span>{{ __('messages.exw_charges_details') }}</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->exw_charges ?? 0) }}</span></div>
                    @endif
                    <div class="flex justify-between border-b border-border/60 pb-2"><span>DTHC</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->dthc ?? 0) }}</span></div>
                    <div class="flex justify-between border-b border-border/60 pb-2"><span>War Risk</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->war_risk_surcharge ?? 0) }}</span></div>
                    <div class="flex justify-between border-b border-border/60 pb-2"><span>{{ __('messages.other_charges_details') }}</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->other_charges ?? 0) }}</span></div>
                    @if(($booking->destination_port_change_fee ?? 0) > 0)
                        <div class="flex justify-between border-b border-border/60 pb-2"><span>{{ __('messages.destination_port_change_fee_details') }}</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->destination_port_change_fee ?? 0) }}</span></div>
                    @endif
                    @if(($booking->other_penalties ?? 0) > 0)
                        <div class="flex justify-between border-b border-border/60 pb-2"><span>{{ __('messages.other_penalties_details') }}</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->other_penalties ?? 0) }}</span></div>
                    @endif
                    @foreach($customPricingCharges as $customPricingCharge)
                        <div class="flex justify-between border-b border-border/60 pb-2"><span>{{ $customPricingCharge['label'] }}</span><span class="font-bold" dir="ltr">{{ $booking->formatPricingMoney($customPricingCharge['amount'] ?? 0) }}</span></div>
                    @endforeach
                    <div class="flex justify-between pt-2 text-lg font-black text-primary"><span>{{ __('الإجمالي') }}</span><span dir="ltr">{{ $booking->formatPricingMoney($booking->pricingTotal()) }}</span></div>
                    <div class="grid gap-2 pt-2 text-xs text-muted-foreground sm:grid-cols-2">
                        <div>{{ __('مدة العبور:') }} {{ $booking->transit_days ?: '—' }} {{ __('يوم') }}</div>
                        <div>{{ __('الصلاحية:') }} {{ $booking->validity_date?->toDateString() ?: '—' }}</div>
                    </div>
                    @if($booking->pricing_notes)
                        <div class="rounded-lg bg-secondary/30 p-3 text-xs leading-6 text-muted-foreground">{{ $booking->pricing_notes }}</div>
                    @endif
                </div>
                @endif

                @if(in_array($booking->status, ['priced', 'sent'], true))
                    <div class="mt-5 border-t border-border pt-4" data-customer-quote-decision-card>
                        <div class="grid gap-2 md:grid-cols-2">
                            <button type="button" data-show-customer-quote-decision data-target="quote-accept-decision-{{ md5($booking->id) }}" class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg border border-success/30 bg-success/10 px-3 text-xs font-black text-success hover:bg-success/15">
                                <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                {{ __('موافقة على العرض') }}
                            </button>
                            <button type="button" data-show-customer-quote-decision data-target="quote-reject-decision-{{ md5($booking->id) }}" class="inline-flex h-9 w-full items-center justify-center gap-1.5 rounded-lg border border-destructive/25 bg-destructive/10 px-3 text-xs font-black text-destructive hover:bg-destructive/15">
                                <i data-lucide="x" class="h-3.5 w-3.5"></i>
                                {{ __('رفض العرض') }}
                            </button>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                        <form id="quote-accept-decision-{{ md5($booking->id) }}" data-customer-quote-decision-panel action="{{ route('customer.shipments.accept', $booking) }}" method="POST" class="hidden rounded-lg border border-success/25 bg-success/5 p-3">
                            @csrf
                            <label class="text-xs font-bold">{{ __('ملاحظة الموافقة') }}</label>
                            <textarea name="note" rows="2" class="mt-1 w-full rounded-lg border border-border bg-background p-2 text-xs focus:outline-none focus:ring-1 focus:ring-success" placeholder="{{ __('مثلاً: موافق، يرجى المتابعة...') }}"></textarea>
                            <button class="mt-2 inline-flex h-9 w-full items-center justify-center rounded-lg bg-success px-4 text-xs font-black text-white hover:opacity-95">
                                {{ __('موافقة على العرض') }}
                            </button>
                        </form>
                        <form id="quote-reject-decision-{{ md5($booking->id) }}" data-customer-quote-decision-panel action="{{ route('customer.shipments.reject', $booking) }}" method="POST" class="hidden rounded-lg border border-destructive/25 bg-destructive/5 p-3">
                            @csrf
                            <label class="text-xs font-bold">{{ __('سبب الرفض / الملاحظة') }}</label>
                            <textarea name="note" rows="2" class="mt-1 w-full rounded-lg border border-border bg-background p-2 text-xs focus:outline-none focus:ring-1 focus:ring-destructive" placeholder="{{ __('اكتب الملاحظة التي تريد إرسالها للمبيعات...') }}"></textarea>
                            <button class="mt-2 inline-flex h-9 w-full items-center justify-center rounded-lg bg-destructive px-4 text-xs font-black text-white hover:opacity-95">
                                {{ __('رفض العرض') }}
                            </button>
                        </form>
                        </div>
                    </div>
                @elseif($booking->customer_decision)
                    <div class="mt-4 rounded-lg border border-border bg-secondary/30 p-3 text-sm">
                        <div class="font-black">{{ __('قرارك:') }} {{ $booking->customer_decision === 'accepted' ? __('موافقة') : __('رفض') }}</div>
                        <div class="mt-1 text-xs text-muted-foreground">{{ $booking->customer_decision_note ?: __('لا توجد ملاحظة.') }}</div>
                    </div>
                @endif
            @endif
        </div>
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <div id="shipment-tracking" data-section class="rounded-xl border border-border bg-card p-5 shadow-card">
            <h2 class="mb-4 text-base font-black">{{ __('التتبع') }}</h2>
            @if(! $booking->shipmentTrack)
                <div class="rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
                    {{ __('يبدأ التتبع بعد اعتماد الطلب وحجز الناقل.') }}
                </div>
            @else
                <div class="mb-3 rounded-lg bg-secondary/30 p-3 text-sm">
                    <div class="font-mono text-xs text-muted-foreground">{{ $booking->shipmentTrack->id }}</div>
                    <div class="mt-1 font-black">{{ $booking->shipmentTrack->status }} - {{ $booking->shipmentTrack->progress }}%</div>
                </div>
                <div class="space-y-3">
                    @foreach($booking->shipmentTrack->events->sortByDesc('event_date') as $event)
                        <div class="border-s-2 border-accent ps-3 text-sm">
                            <div class="font-bold">{{ $event->status }}</div>
                            <div class="text-xs text-muted-foreground">{{ $event->location }} - {{ $event->event_date }}</div>
                            <div class="mt-1 text-xs text-muted-foreground">{{ $event->description }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div id="shipment-customs" data-section class="rounded-xl border border-border bg-card p-5 shadow-card">
            <h2 class="mb-4 text-base font-black">{{ __('التخليص الجمركي') }}</h2>
            <div class="grid gap-3 text-sm">
                <div class="rounded-lg bg-secondary/30 p-3">
                    <div class="text-xs text-muted-foreground">{{ __('الحالة') }}</div>
                    <div class="mt-1 font-black">{{ __($customsLabels[$booking->customs_status ?: 'pending'] ?? ($booking->customs_status ?: 'بانتظار فتح الملف')) }}</div>
                </div>
                <div class="rounded-lg bg-secondary/30 p-3">
                    <div class="text-xs text-muted-foreground">{{ __('المرجع / رقم البيان') }}</div>
                    <div class="mt-1 font-bold">{{ $booking->customs_reference ?: ($booking->customs_declaration_no ?: '—') }}</div>
                </div>
                <div class="rounded-lg bg-secondary/30 p-3">
                    <div class="text-xs text-muted-foreground">{{ __('رسوم التخليص') }}</div>
                    <div class="mt-1 font-bold" dir="ltr">{{ $booking->formatPricingMoney($booking->customs_fees ?? 0) }}</div>
                </div>
                @if($booking->customs_notes)
                    <div class="rounded-lg bg-secondary/30 p-3">
                        <div class="text-xs text-muted-foreground">{{ __('ملاحظات التخليص') }}</div>
                        <div class="mt-1 text-sm">{{ $booking->customs_notes }}</div>
                    </div>
                @endif
            </div>
        </div>
    </section>
    <div id="shipment-activity" data-section>@include('customer.partials.activity-feed', ['logs' => $activityLogs, 'customer' => $customer])</div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-show-customer-quote-decision]').forEach((button) => {
            button.addEventListener('click', () => {
                const card = button.closest('[data-customer-quote-decision-card]');
                const panel = document.getElementById(button.dataset.target);

                if (!card || !panel) return;

                card.querySelectorAll('[data-customer-quote-decision-panel]').forEach((item) => {
                    item.classList.add('hidden');
                });

                panel.classList.remove('hidden');
                panel.querySelector('textarea[name="note"]')?.focus();
            });
        });
    });
</script>
@endsection

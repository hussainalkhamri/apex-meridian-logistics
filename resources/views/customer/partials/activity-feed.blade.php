@php
    $logs = $logs ?? collect();
    $customer = $customer ?? null;
    $hiddenCustomerAuditKeys = [
        'source',
        'customer_id',
        'agent_id',
        'agent_name',
        'agent_purchase_unit_price',
        'agent_purchase_quantity',
        'agent_purchase_amount',
        'agent_purchase_currency',
        'agent_purchase_notes',
        'profit_amount',
    ];
    $moneyCustomerAuditKeys = [
        'sale_total',
        'sale_unit_total',
        'ocean_freight',
        'origin_charges',
        'exw_charges',
        'dthc',
        'war_risk_surcharge',
        'other_charges',
        'custom_charge_amount',
    ];
    $customerAuditFieldLabels = [
        'sale_total' => __('إجمالي العرض'),
        'sale_unit_total' => __('سعر الوحدة'),
        'ocean_freight' => __('الشحن البحري'),
        'origin_charges' => __('رسوم المنشأ'),
        'exw_charges' => __('أجور EXW'),
        'dthc' => 'DTHC',
        'war_risk_surcharge' => __('مخاطر الحرب'),
        'other_charges' => __('رسوم أخرى'),
        'custom_charge_amount' => __('رسوم إضافية'),
        'transit_days' => __('مدة العبور'),
        'validity_date' => __('صلاحية العرض'),
        'status' => __('الحالة'),
        'customer_decision' => __('قرار العميل'),
        'customer_decision_note' => __('ملاحظة العميل'),
        'carrier_booking_ref' => __('رقم حجز الناقل'),
        'carrier_etd' => __('تاريخ المغادرة'),
        'carrier_eta' => __('تاريخ الوصول'),
        'customs_status' => __('حالة التخليص'),
        'customs_reference' => __('مرجع التخليص'),
        'customs_declaration_no' => __('رقم البيان'),
    ];
    $auditBooking = $booking ?? null;
    $formatCustomerAuditValue = function ($key, $value) use ($moneyCustomerAuditKeys, $auditBooking) {
        $normalizedKey = str_replace([' ', '-'], '_', strtolower((string) $key));

        if ($value === null || $value === '') {
            return '—';
        }

        if ($normalizedKey === 'status' && is_scalar($value)) {
            return __(\App\Support\BookingStatus::resolve((string) $value)['label']);
        }

        if ($normalizedKey === 'customs_status' && is_scalar($value)) {
            return __(\App\Support\BookingStatus::customsLabels()[(string) $value] ?? (string) $value);
        }

        if (is_bool($value)) {
            return $value ? __('نعم') : __('لا');
        }

        if (is_numeric($value) && in_array($normalizedKey, $moneyCustomerAuditKeys, true)) {
            return $auditBooking instanceof \App\Models\Booking
                ? $auditBooking->formatPricingMoney((float) $value)
                : \App\Models\Booking::pricingCurrencySymbolFor('USD') . ' ' . number_format((float) $value, 2);
        }

        if (is_array($value) || is_object($value)) {
            return __('بيانات محدثة');
        }

        return (string) $value;
    };
@endphp

<section class="rounded-xl border border-border bg-card p-5 shadow-card">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-base font-black">{{ __('سجل النشاط') }}</h2>
            <p class="mt-1 text-xs text-muted-foreground">{{ __('آخر العمليات المسجلة على هذا الطلب.') }}</p>
        </div>
        <span class="rounded-full bg-secondary px-2.5 py-1 text-[11px] font-black">{{ $logs->count() }} {{ __('عملية') }}</span>
    </div>

    @if($logs->isEmpty())
        <div class="mt-4 rounded-lg border border-dashed border-border p-6 text-center text-sm text-muted-foreground">
            {{ __('لا توجد عمليات مسجلة على هذا الطلب حتى الآن.') }}
        </div>
    @else
        <div class="mt-4 space-y-3">
            @foreach($logs as $log)
                @php
                    $actor = $log->user?->full_name
                        ?: (str_starts_with($log->action_type, 'CUSTOMER_') ? ($customer?->company ?: __('العميل')) : __('النظام'));
                    $changes = collect($log->new_values ?? [])
                        ->reject(function ($value, $key) use ($hiddenCustomerAuditKeys) {
                            $normalizedKey = str_replace([' ', '-'], '_', strtolower((string) $key));

                            return in_array($normalizedKey, $hiddenCustomerAuditKeys, true)
                                || str_starts_with($normalizedKey, 'agent_')
                                || str_starts_with($normalizedKey, 'purchase_')
                                || str_starts_with($normalizedKey, 'profit_');
                        })
                        ->map(function ($value, $key) use ($customerAuditFieldLabels, $formatCustomerAuditValue) {
                            $normalizedKey = str_replace([' ', '-'], '_', strtolower((string) $key));

                            return [
                                'label' => $customerAuditFieldLabels[$normalizedKey] ?? str_replace('_', ' ', (string) $key),
                                'value' => $formatCustomerAuditValue($key, $value),
                            ];
                        })
                        ->take(4);
                @endphp
                <article class="rounded-lg border border-border/70 bg-secondary/15 p-3 text-sm">
                    <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                        <div>
                            <span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-black {{ $log->action_color }}">
                                {{ $log->action_label }}
                            </span>
                            <div class="mt-2 font-black">{{ $actor }}</div>
                            <div class="mt-1 text-[11px] text-muted-foreground">{{ $log->created_at?->format('Y-m-d H:i') }}</div>
                        </div>

                        @if($changes->isNotEmpty())
                            <div class="grid gap-1 text-[11px] text-muted-foreground md:min-w-64">
                                @foreach($changes as $change)
                                    @php $value = $change['value']; @endphp
                                    <div class="flex justify-between gap-3 rounded-md bg-card px-2 py-1">
                                        <span class="font-bold text-foreground">{{ $change['label'] }}</span>
                                        <span class="max-w-40 truncate" title="{{ $change['value'] }}">
                                            {{ is_scalar($value) ? $value : __('بيانات محدثة') }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</section>

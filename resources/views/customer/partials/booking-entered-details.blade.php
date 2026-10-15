@php
    $detailBooking = $booking ?? null;
    $compact = $compact ?? false;
    $cargoDetails = (array) ($detailBooking?->cargo_details ?? []);
    $routeDetails = (array) ($detailBooking?->route_details ?? []);
    $attachments = $detailBooking?->attachments;

    if (is_string($attachments)) {
        $decodedAttachments = json_decode($attachments, true);
        $attachments = json_last_error() === JSON_ERROR_NONE ? $decodedAttachments : [];
    }

    $attachments = is_array($attachments) ? $attachments : [];
    $hiddenCustomerDetailKeys = [
        'source',
        'customer_id',
        'sale_total',
        'sale_unit_total',
        'agent_id',
        'agent_name',
        'agent_purchase_unit_price',
        'agent_purchase_quantity',
        'agent_purchase_amount',
        'agent_purchase_currency',
        'agent_purchase_notes',
        'profit_amount',
    ];
    $cargoDetails = collect($cargoDetails)
        ->reject(function ($value, $key) use ($hiddenCustomerDetailKeys) {
            $normalizedKey = str_replace([' ', '-'], '_', strtolower((string) $key));

            return in_array($normalizedKey, $hiddenCustomerDetailKeys, true)
                || str_starts_with($normalizedKey, 'agent_')
                || str_starts_with($normalizedKey, 'purchase_')
                || str_starts_with($normalizedKey, 'profit_');
        })
        ->all();

    $requestedServices = collect([
        data_get($routeDetails, 'services.customs_requested') ? __('تخليص جمركي') : null,
        data_get($routeDetails, 'services.door_delivery_requested') ? __('تسليم إلى الباب') : null,
        data_get($routeDetails, 'services.cargo_insurance_requested') ? __('تأمين على الشحنة') : null,
    ])->filter()->values();
    $routeRows = collect([
        ['label' => __('شرط الشحن'), 'value' => data_get($routeDetails, 'mode')],
        ['label' => __('دولة الشحن'), 'value' => data_get($routeDetails, 'origin.country')],
        ['label' => __('مدينة الانطلاق'), 'value' => data_get($routeDetails, 'origin.city')],
        ['label' => __('ميناء الشحن'), 'value' => data_get($routeDetails, 'origin.port')],
        ['label' => __('دولة الوصول'), 'value' => data_get($routeDetails, 'destination.country')],
        ['label' => __('مدينة الوصول'), 'value' => data_get($routeDetails, 'destination.city')],
        ['label' => __('ميناء الوصول'), 'value' => data_get($routeDetails, 'destination.port')],
    ])->filter(fn ($item) => filled($item['value']))->values();

    $detailLabelMap = [
        'cargo_name' => __('اسم البضاعة'),
        'pieces' => __('عدد القطع'),
        'piece_weight' => __('وزن القطعة'),
        'total_weight' => __('إجمالي الوزن'),
        'length' => __('الطول'),
        'width' => __('العرض'),
        'height' => __('الارتفاع'),
        'cbm' => 'CBM',
        'packaging_type' => __('نوع التغليف'),
        'lifting_method' => __('طريقة الرفع'),
        'stackable' => __('يمكن الرص'),
        'vehicle_type' => __('نوع المركبة'),
        'manufacturer' => __('الشركة المصنعة'),
        'model' => __('الموديل'),
        'year' => __('سنة الصنع'),
        'color' => __('اللون'),
        'vin' => 'VIN',
        'chassis_number' => __('رقم الهيكل'),
        'engine_number' => __('رقم المحرك'),
        'plate_number' => __('رقم اللوحة'),
        'weight' => __('الوزن'),
        'running' => __('تمشي؟'),
        'tow_required' => 'Tow Required',
        'fuel_level' => __('كمية الوقود'),
        'has_key' => __('مفتاح'),
        'has_items' => __('أغراض داخل السيارة'),
        'value' => __('قيمة السيارة'),
        'reefer_temperature' => __('درجة البرودة'),
        'land_carton_count' => __('عدد الكراتين'),
        'service_type' => __('نوع الخدمة'),
        'container_summary' => __('ملخص الحاويات'),
        'container_total_qty' => __('إجمالي الحاويات'),
        'gross_weight_kg' => __('إجمالي الوزن'),
        'net_weight_kg' => __('صافي الوزن'),
        'volume_cbm' => __('الحجم CBM'),
        'volumetric_weight_kg' => __('الوزن الحجمي'),
        'chargeable_weight_kg' => __('الوزن المحاسبي'),
        'volumetric_divisor' => __('معامل الوزن الحجمي'),
        'cargo_type' => __('نوع البضاعة'),
        'hs_code' => 'HS Code',
        'country_of_origin' => __('بلد المنشأ'),
        'package_type' => __('نوع التغليف'),
        'declared_value' => __('القيمة المصرح بها'),
        'currency' => __('العملة'),
        'trade' => __('بيانات التجارة'),
        'dangerous_goods' => __('بيانات البضاعة الخطرة'),
        'cargo' => __('بيانات البضاعة'),
        'packages' => __('الطرود'),
        'service' => __('الخدمة'),
        'service_details' => __('تفاصيل الخدمة'),
        'main_transport_mode' => __('وسيلة النقل الرئيسية'),
        'pickup_ready_date' => __('تاريخ جاهزية الاستلام'),
        'preferred_pickup_time' => __('وقت الاستلام المفضل'),
        'pickup_contact' => __('مسؤول الاستلام'),
        'delivery_contact' => __('مسؤول التسليم'),
        'delivery_notes' => __('ملاحظات التسليم'),
    ];

    $nestedLabelMap = [
        'quantity' => __('الكمية'),
        'qty' => __('العدد'),
        'type' => __('النوع'),
        'package_type' => __('نوع التغليف'),
        'length_cm' => __('الطول سم'),
        'width_cm' => __('العرض سم'),
        'height_cm' => __('الارتفاع سم'),
        'weight_kg' => __('الوزن كجم'),
        'goods_description' => __('وصف البضاعة'),
        'cargo_type' => __('نوع البضاعة'),
        'hs_code' => 'HS Code',
        'country_of_origin' => __('بلد المنشأ'),
        'pieces_count' => __('عدد القطع'),
        'packages_count' => __('عدد الطرود'),
        'gross_weight_kg' => __('إجمالي الوزن'),
        'net_weight_kg' => __('صافي الوزن'),
        'volume_cbm' => __('الحجم CBM'),
        'volumetric_weight_kg' => __('الوزن الحجمي'),
        'chargeable_weight_kg' => __('الوزن المحاسبي'),
        'stackable' => __('يمكن الرص'),
        'declared_value' => __('القيمة المصرح بها'),
        'currency' => __('العملة'),
        'incoterm' => 'Incoterm',
        'commercial_value' => __('القيمة التجارية'),
        'is_dangerous' => __('بضاعة خطرة'),
        'transport_regulation' => __('نظام النقل'),
        'un_number' => 'UN Number',
        'iata_class' => 'IATA Class',
        'imdg_class' => 'IMDG Class',
        'adr_class' => 'ADR Class',
        'packing_group' => 'Packing Group',
        'proper_shipping_name' => __('اسم الشحن الصحيح'),
        'msds_available' => __('MSDS متوفر'),
        'service_level' => __('مستوى الخدمة'),
        'ready_date' => __('تاريخ الجاهزية'),
        'direct_flight_preferred' => __('رحلة مباشرة مفضلة'),
        'insurance_required' => __('مطلوب تأمين'),
        'customs_clearance_origin' => __('تخليص في بلد الشحن'),
        'customs_clearance_destination' => __('تخليص في بلد الوصول'),
        'pickup_required' => __('مطلوب استلام'),
        'delivery_required' => __('مطلوب تسليم'),
        'pickup_ready_date' => __('تاريخ جاهزية الاستلام'),
        'pickup_time_from' => __('وقت الاستلام من'),
        'pickup_time_to' => __('وقت الاستلام إلى'),
        'delivery_required_date' => __('تاريخ التسليم المطلوب'),
        'preferred_pickup_time' => __('وقت الاستلام المفضل'),
        'export_customs_clearance' => __('تخليص تصدير'),
        'import_customs_clearance' => __('تخليص استيراد'),
        'delivery_notes' => __('ملاحظات التسليم'),
        'name' => __('الاسم'),
        'phone_country_code' => __('رمز الدولة'),
        'phone' => __('الهاتف'),
    ];

    $isListArray = fn (array $items): bool => array_is_list($items);
    $isFilledDetail = function ($value): bool {
        if (is_array($value)) {
            return collect($value)->filter(fn ($item) => $item !== null && $item !== '' && $item !== [])->isNotEmpty();
        }

        return $value !== null && $value !== '';
    };
    $formatDetailValue = function ($value, ?string $key = null) use (&$formatDetailValue, $nestedLabelMap, $isListArray, $isFilledDetail) {
        if (is_bool($value)) {
            return $value ? __('نعم') : __('لا');
        }

        if (! is_array($value)) {
            return $value;
        }

        $value = collect($value)
            ->filter(fn ($item) => $isFilledDetail($item))
            ->all();

        if ($value === []) {
            return null;
        }

        if ($key === 'containers') {
            return collect($value)
                ->map(fn ($container) => is_array($container)
                    ? trim(($container['qty'] ?? $container['quantity'] ?? 1) . 'x' . ($container['type'] ?? ''))
                    : (string) $container)
                ->filter()
                ->implode(' + ');
        }

        if ($key === 'packages') {
            return collect($value)
                ->map(function ($package, $index) {
                    if (! is_array($package)) {
                        return (string) $package;
                    }

                    $quantity = $package['quantity'] ?? 1;
                    $type = $package['package_type'] ?? __('طرد');
                    $dimensions = collect([
                        $package['length_cm'] ?? null,
                        $package['width_cm'] ?? null,
                        $package['height_cm'] ?? null,
                    ])->filter(fn ($dimension) => $dimension !== null && $dimension !== '' && (float) $dimension > 0)->implode('x');
                    $weight = isset($package['weight_kg']) && (float) $package['weight_kg'] > 0
                        ? ' - ' . $package['weight_kg'] . __(' كجم')
                        : '';

                    return ($index + 1) . '. ' . $quantity . 'x ' . $type . ($dimensions ? ' - ' . $dimensions . __(' سم') : '') . $weight;
                })
                ->implode("\n");
        }

        if ($isListArray($value)) {
            return collect($value)
                ->map(fn ($item, $index) => is_array($item)
                    ? ($index + 1) . '. ' . $formatDetailValue($item)
                    : (string) $formatDetailValue($item))
                ->filter()
                ->implode("\n");
        }

        return collect($value)
            ->map(function ($item, $itemKey) use (&$formatDetailValue, $nestedLabelMap, $isFilledDetail) {
                if (! $isFilledDetail($item)) {
                    return null;
                }

                $label = $nestedLabelMap[$itemKey] ?? str_replace('_', ' ', (string) $itemKey);
                $formatted = $formatDetailValue($item, (string) $itemKey);

                return $formatted === null || $formatted === ''
                    ? null
                    : $label . ': ' . $formatted;
            })
            ->filter()
            ->implode("\n");
    };

    $shipmentTitle = match ($detailBooking?->shipment_type) {
        'Bulk' => __('تفاصيل الشحنة Bulk'),
        'RoRo' => __('تفاصيل الشحنة RoRo'),
        'FCL', 'LCL' => __('تفاصيل إضافية'),
        default => __('تفاصيل الشحنة الإضافية'),
    };
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <h3 class="text-sm font-black text-foreground">{{ __('التفاصيل التي أدخلتها') }}</h3>
        <span class="inline-flex rounded-full bg-secondary px-3 py-1 text-[11px] font-black text-muted-foreground">{{ data_get($routeDetails, 'mode', '—') }}</span>
    </div>

    <div class="grid gap-3 {{ $compact ? 'lg:grid-cols-4' : 'sm:grid-cols-2 lg:grid-cols-4' }} text-sm">
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('الشركة') }}</div>
            <div class="mt-1 font-bold">{{ $detailBooking?->customer_company ?: '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('الشخص المسؤول') }}</div>
            <div class="mt-1 font-bold">{{ $detailBooking?->customer_contact_name ?: '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('الهاتف') }}</div>
            <div class="mt-1 font-bold" dir="ltr">{{ $detailBooking?->customer_phone ?: '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('البريد') }}</div>
            <div class="mt-1 break-all font-bold" dir="ltr">{{ $detailBooking?->customer_email ?: '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('رقم الطلب') }}</div>
            <div class="mt-1 font-mono text-xs font-black" dir="ltr">{{ $detailBooking?->id }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('المسار') }}</div>
            <div class="mt-1 font-bold">{{ $detailBooking?->pol ?: '—' }} ← {{ $detailBooking?->pod ?: '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('نوع الشحنة') }}</div>
            <div class="mt-1 font-bold">{{ $detailBooking?->shipment_type ?: '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('الحاوية') }}</div>
            <div class="mt-1 font-bold">{{ $detailBooking?->containerSummary() ?: '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('الوزن') }}</div>
            <div class="mt-1 font-bold">{{ is_numeric($detailBooking?->weight_kg) ? number_format((float) $detailBooking->weight_kg, 2) . __(' كجم') : '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('الحجم') }}</div>
            <div class="mt-1 font-bold">{{ is_numeric($detailBooking?->volume_cbm) ? number_format((float) $detailBooking->volume_cbm, 2) . ' CBM' : '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('بضاعة خطرة DG') }}</div>
            <div class="mt-1 font-bold">{{ $detailBooking?->dangerous ? __('نعم') : __('لا') }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('المرفقات') }}</div>
            <div class="mt-1 font-bold">{{ count($attachments) ? count($attachments) . __(' ملف') : '—' }}</div>
        </div>
    </div>

    @if($routeRows->isNotEmpty())
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="mb-2 text-xs text-muted-foreground">{{ __('تفاصيل المسار') }}</div>
            <div class="grid gap-2 {{ $compact ? 'lg:grid-cols-4' : 'sm:grid-cols-2 lg:grid-cols-3' }}">
                @foreach($routeRows as $row)
                    <div class="rounded-lg border border-border/40 bg-card p-3">
                        <span class="block text-[11px] font-semibold text-muted-foreground">{{ $row['label'] }}</span>
                        <span class="mt-1 block font-bold text-foreground">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid gap-3 {{ $compact ? 'lg:grid-cols-2' : 'lg:grid-cols-2' }} text-sm">
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('وصف البضاعة') }}</div>
            <div class="mt-1 font-bold leading-7">{{ $detailBooking?->goods_description ?: '—' }}</div>
        </div>
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('المستندات / الملاحظات') }}</div>
            <div class="mt-1 leading-7">{{ $detailBooking?->documents ?: '—' }}</div>
            @if($detailBooking?->notes)
                <div class="mt-1 leading-7 text-muted-foreground">{{ $detailBooking->notes }}</div>
            @endif
        </div>
    </div>

    @if($requestedServices->isNotEmpty())
        <div class="rounded-lg bg-secondary/30 p-3">
            <div class="text-xs text-muted-foreground">{{ __('الخدمات الإضافية المطلوبة') }}</div>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach($requestedServices as $service)
                    <span class="rounded-full bg-accent/10 px-3 py-1 text-xs font-black text-accent">{{ $service }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @if(!empty($cargoDetails))
        <div class="rounded-lg border border-border/60 bg-background/70 p-3">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 border-b border-border/60 pb-2">
                <div class="text-xs font-bold uppercase text-muted-foreground">{{ $shipmentTitle }}</div>
                <span class="rounded-full border border-accent/20 bg-accent/10 px-3 py-1 text-[11px] font-semibold text-accent">{{ $detailBooking?->shipment_type }}</span>
            </div>
            <div class="grid gap-3 {{ $compact ? 'lg:grid-cols-4' : 'sm:grid-cols-2 lg:grid-cols-3' }}">
                @foreach($cargoDetails as $key => $value)
                    @php
                        if (! $isFilledDetail($value)) {
                            continue;
                        }

                        $label = $detailLabelMap[$key] ?? str_replace('_', ' ', $key);
                        $displayValue = $formatDetailValue($value, (string) $key);

                        if ($displayValue === null || $displayValue === '') {
                            continue;
                        }
                    @endphp
                    <div class="rounded-lg border border-border/40 bg-card p-3">
                        <span class="block text-[11px] font-semibold text-muted-foreground">{{ $label }}</span>
                        <span class="mt-1 block whitespace-pre-line break-words font-bold leading-7 text-foreground">{{ $displayValue }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

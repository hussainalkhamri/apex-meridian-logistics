@php
    $mode = $mode ?? 'sea';
    $suffix = $mode === 'sea' ? '' : '_' . $mode;
    $isSea = $mode === 'sea';
    $customerContainerTypes = ['20GP', '40HC', '45GP', '45HC', 'Reefer 20', 'Reefer 40', 'OT 20', 'OT 40'];
    $customerFclRows = old('containers');
    if (empty($customerFclRows)) {
        $customerFclRows = [[
            'type' => old('containerType', '40HC'),
            'qty' => old('containerQty', 1),
        ]];
    }
@endphp

<div class="space-y-4">
    <h2 class="flex items-center gap-2 border-b border-border pb-2 text-lg font-bold text-primary">
        <i data-lucide="box" class="h-5 w-5 text-accent"></i>
        <span>{{ __('تفاصيل البضاعة') }}</span>
    </h2>

    <div class="grid gap-4 md:grid-cols-2">
        @if($isSea)
            <div class="space-y-2">
                <label for="customer-shipment-type" class="text-xs font-bold text-foreground">{{ __('نوع الشحن *') }}</label>
                <select name="shipmentType" id="customer-shipment-type" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="FCL" @selected(old('shipmentType', 'FCL') === 'FCL')>{{ __('حاوية كاملة (FCL)') }}</option>
                    <option value="LCL" @selected(old('shipmentType') === 'LCL')>{{ __('شحن مشترك (LCL)') }}</option>
                    <option value="Bulk" @selected(old('shipmentType') === 'Bulk')>Bulk</option>
                    <option value="RoRo" @selected(old('shipmentType') === 'RoRo')>RoRo</option>
                    <option value="Flat Container" @selected(old('shipmentType') === 'Flat Container')>Flat Container</option>
                </select>
            </div>
            <div class="space-y-2">
                <label for="customer-sea-hs-code" class="text-xs font-bold text-foreground">HS CODE Number</label>
                <input type="text" name="hs_code" id="customer-sea-hs-code" value="{{ old('hs_code', data_get($repeatBooking?->cargo_details, 'hs_code')) }}" placeholder="{{ __('مثال: 8703.23') }}" dir="ltr" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            </div>

            <div data-customer-fcl-field class="space-y-3 rounded-xl border border-border/80 bg-secondary/10 p-4 md:col-span-2">
                <input type="hidden" name="containerType" id="customer-container-type" value="{{ old('containerType', data_get($customerFclRows, '0.type', '40HC')) }}">
                <input type="hidden" name="containerQty" id="customer-container-qty" value="{{ old('containerQty', data_get($customerFclRows, '0.qty', 1)) }}">

                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-foreground">{{ __('أنواع وكميات الحاويات') }}</h3>
                        <p class="text-[11px] font-semibold text-muted-foreground">{{ __('يمكن إضافة أكثر من نوع حاوية في نفس الطلب.') }}</p>
                    </div>
                    <button type="button" data-customer-add-fcl-row class="inline-flex h-9 items-center gap-2 rounded-lg bg-accent px-3 text-xs font-bold text-accent-foreground shadow-sm hover:opacity-95">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        <span>{{ __('إضافة حاوية') }}</span>
                    </button>
                </div>

                <div data-customer-fcl-rows class="space-y-2">
                    @foreach($customerFclRows as $index => $row)
                        <div data-customer-fcl-row class="grid gap-3 rounded-lg border border-border/70 bg-background/80 p-3 sm:grid-cols-[minmax(0,1fr)_140px_40px]">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-foreground">{{ __('نوع الحاوية') }}</label>
                                <select name="containers[{{ $index }}][type]" data-customer-fcl-container-type data-required-when-fcl="1" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                    @foreach($customerContainerTypes as $type)
                                        <option value="{{ $type }}" @selected(data_get($row, 'type') === $type)>{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-foreground">{{ __('الكمية') }}</label>
                                <input type="number" name="containers[{{ $index }}][qty]" data-customer-fcl-container-qty data-required-when-fcl="1" value="{{ data_get($row, 'qty', 1) }}" min="1" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                            </div>
                            <div class="flex items-end">
                                <button type="button" data-customer-remove-fcl-row class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-background text-muted-foreground hover:border-destructive/40 hover:text-destructive" title="{{ __('حذف الصف') }}">
                                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <template id="customer-fcl-row-template">
                    <div data-customer-fcl-row class="grid gap-3 rounded-lg border border-border/70 bg-background/80 p-3 sm:grid-cols-[minmax(0,1fr)_140px_40px]">
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-foreground">{{ __('نوع الحاوية') }}</label>
                            <select name="containers[__INDEX__][type]" data-customer-fcl-container-type data-required-when-fcl="1" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                @foreach($customerContainerTypes as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-foreground">{{ __('الكمية') }}</label>
                            <input type="number" name="containers[__INDEX__][qty]" data-customer-fcl-container-qty data-required-when-fcl="1" value="1" min="1" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        </div>
                        <div class="flex items-end">
                            <button type="button" data-customer-remove-fcl-row class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-background text-muted-foreground hover:border-destructive/40 hover:text-destructive" title="{{ __('حذف الصف') }}">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </template>

                <div data-customer-reefer-temperature class="hidden space-y-2">
                    <label for="customer-reefer-temperature" class="text-xs font-bold text-foreground">{{ __('درجة البرودة') }}</label>
                    <input type="text" name="reefer_temperature" id="customer-reefer-temperature" value="{{ old('reefer_temperature') }}" placeholder="{{ __('مثال: -18°C') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
            </div>

            <div data-customer-cargo-panel="Bulk" class="hidden space-y-4 rounded-xl border border-border/80 bg-secondary/10 p-4 md:col-span-2">
                <h3 class="text-sm font-bold text-foreground">{{ __('تفاصيل الشحنة Bulk') }}</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label for="customer-bulk-cargo-name" class="text-xs font-bold text-foreground">{{ __('اسم البضاعة') }}</label>
                        <input type="text" name="bulk_cargo_name" id="customer-bulk-cargo-name" value="{{ old('bulk_cargo_name') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-bulk-pieces" class="text-xs font-bold text-foreground">{{ __('عدد القطع') }}</label>
                        <input type="number" name="bulk_pieces" id="customer-bulk-pieces" value="{{ old('bulk_pieces') }}" min="1" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-bulk-piece-weight" class="text-xs font-bold text-foreground">{{ __('وزن القطعة') }}</label>
                        <input type="number" step="0.01" name="bulk_piece_weight" id="customer-bulk-piece-weight" value="{{ old('bulk_piece_weight') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-bulk-total-weight" class="text-xs font-bold text-foreground">{{ __('إجمالي الوزن') }}</label>
                        <input type="number" step="0.01" name="bulk_total_weight" id="customer-bulk-total-weight" value="{{ old('bulk_total_weight') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-bulk-length" class="text-xs font-bold text-foreground">{{ __('الطول') }}</label>
                        <input type="number" step="0.01" name="bulk_length" id="customer-bulk-length" value="{{ old('bulk_length') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-bulk-width" class="text-xs font-bold text-foreground">{{ __('العرض') }}</label>
                        <input type="number" step="0.01" name="bulk_width" id="customer-bulk-width" value="{{ old('bulk_width') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-bulk-height" class="text-xs font-bold text-foreground">{{ __('الارتفاع') }}</label>
                        <input type="number" step="0.01" name="bulk_height" id="customer-bulk-height" value="{{ old('bulk_height') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-bulk-cbm" class="text-xs font-bold text-foreground">CBM</label>
                        <input type="number" step="0.01" name="bulk_cbm" id="customer-bulk-cbm" value="{{ old('bulk_cbm') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-bulk-packaging" class="text-xs font-bold text-foreground">{{ __('نوع التغليف') }}</label>
                        <select name="bulk_packaging" id="customer-bulk-packaging" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                            <option value="">{{ __('اختر') }}</option>
                            @foreach(['Pallet', 'Bundle', 'Crate', 'Box', 'Bag', 'Drum', 'Loose'] as $packaging)
                                <option value="{{ $packaging }}" @selected(old('bulk_packaging') === $packaging)>{{ $packaging }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="customer-bulk-lifting" class="text-xs font-bold text-foreground">{{ __('طريقة الرفع') }}</label>
                        <select name="bulk_lifting" id="customer-bulk-lifting" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                            <option value="">{{ __('اختر') }}</option>
                            @foreach(['Forklift', 'Crane', 'Manual'] as $lifting)
                                <option value="{{ $lifting }}" @selected(old('bulk_lifting') === $lifting)>{{ $lifting }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="space-y-2 md:col-span-2">
                        <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                            <input type="checkbox" name="bulk_stackable" value="1" @checked(old('bulk_stackable')) class="h-4 w-4 rounded border-border accent-accent">
                            <span>{{ __('يمكن الرص (Stackable)') }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div data-customer-cargo-panel="Flat Container Legacy" class="hidden space-y-4 rounded-xl border border-border/80 bg-secondary/10 p-4 md:col-span-2">
                <h3 class="text-sm font-bold text-foreground">{{ __('تفاصيل Flat Container') }}</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2"><label for="customer-flat-cargo-name" class="text-xs font-bold text-foreground">{{ __('وصف الحمولة') }}</label><input type="text" name="flat_cargo_name" id="customer-flat-cargo-name" value="{{ old('flat_cargo_name') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm"></div>
                    <div class="space-y-2"><label for="customer-flat-pieces" class="text-xs font-bold text-foreground">{{ __('عدد القطع') }}</label><input type="number" name="flat_pieces" id="customer-flat-pieces" value="{{ old('flat_pieces') }}" min="1" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm"></div>
                    <div class="space-y-2"><label for="customer-flat-piece-weight" class="text-xs font-bold text-foreground">{{ __('وزن القطعة (كجم)') }}</label><input type="number" step="0.01" name="flat_piece_weight" id="customer-flat-piece-weight" value="{{ old('flat_piece_weight') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm"></div>
                    <div class="space-y-2"><label for="customer-flat-length" class="text-xs font-bold text-foreground">{{ __('الطول (م)') }}</label><input type="number" step="0.01" name="flat_length" id="customer-flat-length" value="{{ old('flat_length') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm"></div>
                    <div class="space-y-2"><label for="customer-flat-width" class="text-xs font-bold text-foreground">{{ __('العرض (م)') }}</label><input type="number" step="0.01" name="flat_width" id="customer-flat-width" value="{{ old('flat_width') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm"></div>
                    <div class="space-y-2"><label for="customer-flat-height" class="text-xs font-bold text-foreground">{{ __('الارتفاع (م)') }}</label><input type="number" step="0.01" name="flat_height" id="customer-flat-height" value="{{ old('flat_height') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm"></div>
                    <div class="space-y-2"><label for="customer-flat-packaging" class="text-xs font-bold text-foreground">{{ __('نوع التغليف') }}</label><input type="text" name="flat_packaging" id="customer-flat-packaging" value="{{ old('flat_packaging') }}" placeholder="Pallet, bundle, crate" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm"></div>
                    <div class="space-y-2"><label for="customer-flat-lifting" class="text-xs font-bold text-foreground">{{ __('طريقة الرفع') }}</label><input type="text" name="flat_lifting" id="customer-flat-lifting" value="{{ old('flat_lifting') }}" placeholder="Forklift or crane" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm"></div>
                    <label class="flex items-center gap-2 text-sm font-bold text-foreground md:col-span-2"><input type="checkbox" name="flat_stackable" value="1" @checked(old('flat_stackable')) class="h-4 w-4 rounded border-border accent-accent"><span>{{ __('قابلة للرص (Stackable)') }}</span></label>
                </div>
            </div>

            <div data-customer-cargo-panel="RoRo" class="hidden space-y-4 rounded-xl border border-border/80 bg-secondary/10 p-4 md:col-span-2">
                <h3 class="text-sm font-bold text-foreground">{{ __('تفاصيل الشحنة RoRo') }}</h3>
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-2">
                        <label for="customer-roro-vehicle-type" class="text-xs font-bold text-foreground">{{ __('نوع المركبة') }}</label>
                        <input type="text" name="roro_vehicle_type" id="customer-roro-vehicle-type" value="{{ old('roro_vehicle_type') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-manufacturer" class="text-xs font-bold text-foreground">{{ __('الشركة المصنعة') }}</label>
                        <input type="text" name="roro_manufacturer" id="customer-roro-manufacturer" value="{{ old('roro_manufacturer') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-model" class="text-xs font-bold text-foreground">{{ __('الموديل') }}</label>
                        <input type="text" name="roro_model" id="customer-roro-model" value="{{ old('roro_model') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-year" class="text-xs font-bold text-foreground">{{ __('سنة الصنع') }}</label>
                        <input type="number" name="roro_year" id="customer-roro-year" value="{{ old('roro_year') }}" min="1900" max="2100" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-color" class="text-xs font-bold text-foreground">{{ __('اللون') }}</label>
                        <input type="text" name="roro_color" id="customer-roro-color" value="{{ old('roro_color') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-vin" class="text-xs font-bold text-foreground">VIN</label>
                        <input type="text" name="roro_vin" id="customer-roro-vin" value="{{ old('roro_vin') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-chassis-number" class="text-xs font-bold text-foreground">{{ __('رقم الهيكل') }}</label>
                        <input type="text" name="roro_chassis_number" id="customer-roro-chassis-number" value="{{ old('roro_chassis_number') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-engine-number" class="text-xs font-bold text-foreground">{{ __('رقم المحرك') }}</label>
                        <input type="text" name="roro_engine_number" id="customer-roro-engine-number" value="{{ old('roro_engine_number') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-plate-number" class="text-xs font-bold text-foreground">{{ __('رقم اللوحة') }}</label>
                        <input type="text" name="roro_plate_number" id="customer-roro-plate-number" value="{{ old('roro_plate_number') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-length" class="text-xs font-bold text-foreground">{{ __('الطول') }}</label>
                        <input type="number" step="0.01" name="roro_length" id="customer-roro-length" value="{{ old('roro_length') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-width" class="text-xs font-bold text-foreground">{{ __('العرض') }}</label>
                        <input type="number" step="0.01" name="roro_width" id="customer-roro-width" value="{{ old('roro_width') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-height" class="text-xs font-bold text-foreground">{{ __('الارتفاع') }}</label>
                        <input type="number" step="0.01" name="roro_height" id="customer-roro-height" value="{{ old('roro_height') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-weight" class="text-xs font-bold text-foreground">{{ __('الوزن') }}</label>
                        <input type="number" step="0.01" name="roro_weight" id="customer-roro-weight" value="{{ old('roro_weight') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-fuel-level" class="text-xs font-bold text-foreground">{{ __('كمية الوقود') }}</label>
                        <select name="roro_fuel_level" id="customer-roro-fuel-level" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                            <option value="">{{ __('اختر') }}</option>
                            <option value="Quarter" @selected(old('roro_fuel_level') === 'Quarter')>{{ __('ربع') }}</option>
                            <option value="Half" @selected(old('roro_fuel_level') === 'Half')>{{ __('نصف') }}</option>
                            <option value="Empty" @selected(old('roro_fuel_level') === 'Empty')>{{ __('فارغ') }}</option>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label for="customer-roro-value" class="text-xs font-bold text-foreground">{{ __('قيمة السيارة') }}</label>
                        <input type="number" step="0.01" name="roro_value" id="customer-roro-value" value="{{ old('roro_value') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <div class="grid gap-3 md:col-span-2 md:grid-cols-2">
                        <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                            <input type="checkbox" name="roro_running" value="1" @checked(old('roro_running')) class="h-4 w-4 rounded border-border accent-accent">
                            <span>{{ __('تمشي؟') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                            <input type="checkbox" name="roro_tow_required" value="1" @checked(old('roro_tow_required')) class="h-4 w-4 rounded border-border accent-accent">
                            <span>Tow Required</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                            <input type="checkbox" name="roro_has_key" value="1" @checked(old('roro_has_key')) class="h-4 w-4 rounded border-border accent-accent">
                            <span>{{ __('هل يوجد مفتاح؟') }}</span>
                        </label>
                        <label class="flex items-center gap-2 text-sm font-bold text-foreground">
                            <input type="checkbox" name="roro_has_items" value="1" @checked(old('roro_has_items')) class="h-4 w-4 rounded border-border accent-accent">
                            <span>{{ __('هل يوجد أغراض داخل السيارة؟') }}</span>
                        </label>
                    </div>
                </div>
            </div>

            <div data-customer-cargo-items class="hidden md:col-span-2">
                @include('partials.cargo-items-editor', [
                    'editorId' => 'customer-sea-cargo-items-editor',
                    'cargoItems' => data_get($repeatBooking?->cargo_details, 'cargo_items', []),
                    'defaultUnit' => 'piece',
                ])
            </div>
        @endif

        <div class="space-y-2">
            <label for="weightKg{{ $suffix }}" class="text-xs font-bold text-foreground">{{ __('الوزن الإجمالي (كجم) *') }}</label>
            <input type="number" name="weightKg" id="weightKg{{ $suffix }}" value="{{ old('weightKg') }}" min="0.01" step="0.01" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        <div class="space-y-2">
            <label for="volumeCbm{{ $suffix }}" class="text-xs font-bold text-foreground">{{ $mode === 'land' ? __('messages.booking_land_volume_label') : 'الحجم (CBM)' }}</label>
            <input type="number" step="0.01" name="volumeCbm" id="volumeCbm{{ $suffix }}" value="{{ old('volumeCbm') }}" min="0" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
        </div>
        @if($mode === 'land')
            <div class="space-y-2">
                <label for="land_carton_count{{ $suffix }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_land_carton_count_label') }}</label>
                <input type="number" name="land_carton_count" id="land_carton_count{{ $suffix }}" value="{{ old('land_carton_count') }}" min="0" step="1" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            </div>
        @endif

        <div class="space-y-2 md:col-span-2">
            <label for="goodsDescription{{ $suffix }}" class="text-xs font-bold text-foreground">{{ __('وصف البضاعة *') }}</label>
            <textarea name="goodsDescription" id="goodsDescription{{ $suffix }}" required rows="3" placeholder="{{ __('مثال: قطع غيار سيارات، أجهزة إلكترونية، مواد غذائية...') }}" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">{{ old('goodsDescription') }}</textarea>
        </div>

        <div class="grid gap-3 md:col-span-2 md:grid-cols-2">
            <label class="flex items-start gap-3 rounded-xl border border-border bg-secondary/20 p-4 text-sm">
                <input type="checkbox" name="dangerous" value="1" @checked(old('dangerous')) class="mt-1 h-4 w-4 rounded border-border accent-accent">
                <span>
                    <span class="block font-black">{{ __('بضاعة خطرة (DG)') }}</span>
                    <span class="mt-1 block text-xs leading-6 text-muted-foreground">{{ __('يجب إرفاق MSDS وتطبيق إجراءات IMDG.') }}</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-xl border border-border bg-secondary/20 p-4 text-sm">
                <input type="checkbox" name="customs_requested" value="1" @checked(old('customs_requested')) class="mt-1 h-4 w-4 rounded border-border accent-accent">
                <span>
                    <span class="block font-black">{{ __('طلب تخليص جمركي') }}</span>
                    <span class="mt-1 block text-xs leading-6 text-muted-foreground">{{ __('يرسل تنبيهًا لقسم التخليص ويرتبط بنفس ملف الشحنة.') }}</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-xl border border-border bg-secondary/20 p-4 text-sm">
                <input type="checkbox" name="door_delivery_requested" value="1" @checked(old('door_delivery_requested')) class="mt-1 h-4 w-4 rounded border-border accent-accent">
                <span>
                    <span class="block font-black">{{ __('تسليم إلى الباب') }}</span>
                    <span class="mt-1 block text-xs leading-6 text-muted-foreground">{{ __('يظهر للفريق ضمن الخدمات المطلوبة في الملف.') }}</span>
                </span>
            </label>
            <label class="flex items-start gap-3 rounded-xl border border-border bg-secondary/20 p-4 text-sm">
                <input type="checkbox" name="cargo_insurance_requested" value="1" @checked(old('cargo_insurance_requested')) class="mt-1 h-4 w-4 rounded border-border accent-accent">
                <span>
                    <span class="block font-black">{{ __('طلب تأمين على الشحنة') }}</span>
                    <span class="mt-1 block text-xs leading-6 text-muted-foreground">{{ __('يتم تمريره للتسعير والمبيعات ضمن الملاحظات التشغيلية.') }}</span>
                </span>
            </label>
        </div>

        <div class="space-y-2 md:col-span-2">
            <label for="notes{{ $suffix }}" class="text-xs font-bold text-foreground">{{ __('ملاحظات إضافية') }}</label>
            <textarea name="notes" id="notes{{ $suffix }}" rows="3" placeholder="{{ __('مثال: بضائع هشة، معالجة خاصة، ملاحظات الاستلام...') }}" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">{{ old('notes') }}</textarea>
        </div>

        <div class="space-y-2 md:col-span-2">
            <label for="documents{{ $suffix }}" class="text-xs font-bold text-foreground">{{ __('المستندات المرفقة (وصف)') }}</label>
            <input type="text" name="documents" id="documents{{ $suffix }}" value="{{ old('documents') }}" placeholder="Invoice, Packing List, MSDS ..." class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
        </div>

        <div class="space-y-2 md:col-span-2">
            <label for="attachments{{ $suffix }}" class="text-xs font-bold text-foreground">{{ __('إرفاق صور أو ملفات PDF') }}</label>
            <input type="file" name="attachments[]" id="attachments{{ $suffix }}" multiple accept="image/*,.pdf" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            <p class="text-xs text-muted-foreground">{{ __('يمكنك رفع صور أو ملفات PDF من نوع JPG, PNG أو PDF. الحد الأقصى 6 ملفات.') }}</p>
            <p class="text-[11px] font-bold text-warning">{{ __('messages.booking_dg_attachment_hint') }}</p>
            @if(isset($errors) && $errors->has('attachments'))
                <p class="text-xs font-bold text-destructive">{{ $errors->first('attachments') }}</p>
            @endif
        </div>
    </div>
</div>

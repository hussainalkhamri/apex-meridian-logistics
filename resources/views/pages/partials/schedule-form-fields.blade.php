@php
    $schedule = $schedule ?? null;
    $isPublishedValue = (string) old('is_published', $schedule ? (int) $schedule->is_published : 1);
    $isPublished = in_array($isPublishedValue, ['1', 'true', 'on'], true);
    $inputClass = 'h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent';
    $labelClass = 'mb-1 block text-[11px] font-bold text-muted-foreground';
@endphp

<div class="grid gap-3 md:grid-cols-4">
    <label>
        <span class="{{ $labelClass }}">السفينة الناقلة *</span>
        <input name="vessel" value="{{ old('vessel', $schedule?->vessel) }}" required class="{{ $inputClass }} w-full" placeholder="MSC / Maersk / CMA CGM">
    </label>

    <label>
        <span class="{{ $labelClass }}">رقم الرحلة *</span>
        <input name="voyage" value="{{ old('voyage', $schedule?->voyage) }}" required class="{{ $inputClass }} w-full" placeholder="Voyage">
    </label>

    <label>
        <span class="{{ $labelClass }}">الناقل / الخط الملاحي</span>
        <input name="carrier" value="{{ old('carrier', $schedule?->carrier) }}" class="{{ $inputClass }} w-full" placeholder="Maersk Line">
    </label>

    <label>
        <span class="{{ $labelClass }}">اسم الخدمة</span>
        <input name="service_name" value="{{ old('service_name', $schedule?->service_name) }}" class="{{ $inputClass }} w-full" placeholder="Asia Gulf Express">
    </label>

    <label>
        <span class="{{ $labelClass }}">ميناء التحميل *</span>
        <input name="origin" value="{{ old('origin', $schedule?->origin) }}" list="schedule-port-options" required class="{{ $inputClass }} w-full" placeholder="Shanghai">
    </label>

    <label>
        <span class="{{ $labelClass }}">رمز ميناء التحميل</span>
        <input name="origin_code" value="{{ old('origin_code', $schedule?->origin_code) }}" class="{{ $inputClass }} w-full uppercase" placeholder="CNSHA">
    </label>

    <label>
        <span class="{{ $labelClass }}">ميناء التفريغ *</span>
        <input name="destination" value="{{ old('destination', $schedule?->destination) }}" list="schedule-port-options" required class="{{ $inputClass }} w-full" placeholder="Aden">
    </label>

    <label>
        <span class="{{ $labelClass }}">رمز ميناء التفريغ</span>
        <input name="destination_code" value="{{ old('destination_code', $schedule?->destination_code) }}" class="{{ $inputClass }} w-full uppercase" placeholder="YEADE">
    </label>

    <label>
        <span class="{{ $labelClass }}">تاريخ المغادرة ETD *</span>
        <input type="date" name="etd" value="{{ old('etd', optional($schedule?->etd)->format('Y-m-d')) }}" required class="{{ $inputClass }} w-full">
    </label>

    <label>
        <span class="{{ $labelClass }}">تاريخ الوصول ETA *</span>
        <input type="date" name="eta" value="{{ old('eta', optional($schedule?->eta)->format('Y-m-d')) }}" required class="{{ $inputClass }} w-full">
    </label>

    <label>
        <span class="{{ $labelClass }}">آخر موعد تحميل Cutoff</span>
        <input type="date" name="cutoff" value="{{ old('cutoff', optional($schedule?->cutoff)->format('Y-m-d')) }}" class="{{ $inputClass }} w-full">
    </label>

    <label>
        <span class="{{ $labelClass }}">مدة العبور</span>
        <input name="transit" value="{{ old('transit', $schedule?->transit) }}" class="{{ $inputClass }} w-full" placeholder="تحسب تلقائيًا إذا تركتها فارغة">
    </label>

    <label>
        <span class="{{ $labelClass }}">حالة الرحلة *</span>
        <select name="status" required class="{{ $inputClass }} w-full">
            @foreach($statuses as $statusValue => $statusLabel)
                <option value="{{ $statusValue }}" @selected(old('status', $schedule?->status ?? 'published') === $statusValue)>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </label>

    <label>
        <span class="{{ $labelClass }}">آخر تاريخ تحقق عند النشر</span>
        <input type="date" name="source_checked_at" value="{{ old('source_checked_at', optional($schedule?->source_checked_at)->format('Y-m-d')) }}" class="{{ $inputClass }} w-full">
    </label>

    <label>
        <span class="{{ $labelClass }}">تاريخ انتهاء عرض الرحلة</span>
        <input type="date" name="expires_at" value="{{ old('expires_at', optional($schedule?->expires_at)->format('Y-m-d')) }}" class="{{ $inputClass }} w-full">
    </label>

    <label class="flex items-end gap-2 rounded-lg border border-border bg-secondary/20 px-3 py-2">
        <input type="hidden" name="is_published" value="0">
        <input type="checkbox" name="is_published" value="1" @checked($isPublished) class="h-4 w-4 rounded border-border text-accent focus:ring-accent">
        <span class="text-xs font-bold text-foreground">منشور في صفحة الجداول العامة</span>
    </label>

    <label>
        <span class="{{ $labelClass }}">اسم المصدر الرسمي عند النشر</span>
        <input name="source_name" value="{{ old('source_name', $schedule?->source_name) }}" class="{{ $inputClass }} w-full" placeholder="موقع الناقل / ملف الوكيل">
    </label>

    <label class="md:col-span-2">
        <span class="{{ $labelClass }}">رابط المصدر أو اتركه إذا يوجد رقم مرجع</span>
        <input type="url" name="source_url" value="{{ old('source_url', $schedule?->source_url) }}" class="{{ $inputClass }} w-full ltr:text-left" placeholder="https://">
    </label>

    <label>
        <span class="{{ $labelClass }}">رقم المرجع / الملف عند عدم وجود رابط</span>
        <input name="source_reference" value="{{ old('source_reference', $schedule?->source_reference) }}" class="{{ $inputClass }} w-full" placeholder="PDF / Email / Ref">
    </label>

    <label>
        <span class="{{ $labelClass }}">رابط الحجز الخارجي</span>
        <input type="url" name="booking_url" value="{{ old('booking_url', $schedule?->booking_url) }}" class="{{ $inputClass }} w-full ltr:text-left" placeholder="https://">
    </label>

    <label class="md:col-span-4">
        <span class="{{ $labelClass }}">ملاحظات داخلية</span>
        <textarea name="notes" rows="3" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-accent" placeholder="أي ملاحظات تخص تحديث الرحلة أو المصدر">{{ old('notes', $schedule?->notes) }}</textarea>
    </label>
</div>

<div class="mt-4 flex justify-end">
    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-xs font-bold text-accent-foreground shadow hover:opacity-95">
        <i data-lucide="save" class="h-4 w-4"></i>
        {{ $submitLabel ?? 'حفظ جدول الرحلة' }}
    </button>
</div>

@extends('layouts.app')

@section('title', 'إدارة أسعار الشحن — النظام الجمركي')

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs">إدارة الأسعار</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">{{ $viewOnly ? 'عرض سعر الشحن' : ($shippingRate ? 'تعديل سعر الشحن' : 'إنشاء سعر شحن جديد') }}</h1>
            <p class="mt-2 max-w-2xl text-white/80">أدخل تفاصيل المسار والخدمة والحاوية مع التحقق من عدم التكرار.</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
        <div class="rounded-xl border border-border/60 bg-card p-6 shadow-card">
            @if($errors->any())
                <div class="mb-5 rounded-lg border border-destructive/20 bg-destructive/10 p-3 text-sm text-destructive">
                    <ul class="list-disc space-y-1 pr-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $shippingRate ? route('shipping-rates.update', $shippingRate) : route('shipping-rates.store') }}" class="space-y-6">
                @csrf
                @if($shippingRate)
                    @method('PUT')
                @endif

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">نوع الشحن <span class="text-destructive">*</span></label>
                        <select name="shipping_type" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                            @foreach(['بحري','جوي','بري'] as $type)
                                <option value="{{ $type }}" @selected(old('shipping_type', $shippingRate?->shipping_type) === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">نوع الخدمة <span class="text-destructive">*</span></label>
                        <select name="service_type" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                            @foreach(['FCL','LCL','RoRo','Bulk'] as $service)
                                <option value="{{ $service }}" @selected(old('service_type', $shippingRate?->service_type) === $service)>{{ $service }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">شرط الشحن <span class="text-destructive">*</span></label>
                        <select name="incoterm" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                            @foreach(['FOB','EXW'] as $incoterm)
                                <option value="{{ $incoterm }}" @selected(old('incoterm', $shippingRate?->incoterm ?? 'FOB') === $incoterm)>{{ $incoterm }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">نوع الحاوية <span class="text-destructive">*</span></label>
                        <input type="text" name="container_type" value="{{ old('container_type', $shippingRate?->container_type) }}" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">بلد الانطلاق <span class="text-destructive">*</span></label>
                        <select name="origin_country" id="origin-country" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                            <option value="">اختر الدولة</option>
                            @foreach($countries as $country)
                                <option value="{{ $country['name'] }}" data-name-ar="{{ $country['name_ar'] }}" data-code="{{ $country['code'] ?? '' }}" @selected(in_array(old('origin_country', $shippingRate?->origin_country), [$country['name'], $country['name_ar']], true))>{{ trim(($country['name_ar'] ?: $country['name']) . ' - ' . $country['name']) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">ميناء الانطلاق <span class="text-destructive">*</span></label>
                        <select name="origin_port" id="origin-port" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                            <option value="">اختر الميناء</option>
                            @foreach($ports as $port)
                                <option value="{{ $port->name }}" data-country="{{ $port->country }}" data-country-code="{{ $port->country_code }}" @selected(old('origin_port', $shippingRate?->origin_port) === $port->name)>{{ $port->name }} — {{ $port->country }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">بلد الوصول <span class="text-destructive">*</span></label>
                        <select name="destination_country" id="destination-country" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                            <option value="">اختر الدولة</option>
                            @foreach($countries as $country)
                                <option value="{{ $country['name'] }}" data-name-ar="{{ $country['name_ar'] }}" data-code="{{ $country['code'] ?? '' }}" @selected(in_array(old('destination_country', $shippingRate?->destination_country), [$country['name'], $country['name_ar']], true))>{{ trim(($country['name_ar'] ?: $country['name']) . ' - ' . $country['name']) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">ميناء الوصول <span class="text-destructive">*</span></label>
                        <select name="destination_port" id="destination-port" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                            <option value="">اختر الميناء</option>
                            @foreach($ports as $port)
                                <option value="{{ $port->name }}" data-country="{{ $port->country }}" data-country-code="{{ $port->country_code }}" @selected(old('destination_port', $shippingRate?->destination_port) === $port->name)>{{ $port->name }} — {{ $port->country }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">شركة الشحن <span class="text-destructive">*</span></label>
                        <input type="text" name="shipping_company" value="{{ old('shipping_company', $shippingRate?->shipping_company) }}" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">مدة النقل Transit Time</label>
                        <input type="number" min="1" name="transit_time" value="{{ old('transit_time', $shippingRate?->transit_time) }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">العملة <span class="text-destructive">*</span></label>
                        <input type="text" name="currency" value="{{ old('currency', $shippingRate?->currency) }}" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">سعر الشحن <span class="text-destructive">*</span></label>
                        <input type="number" step="0.01" min="0" name="price" value="{{ old('price', $shippingRate?->price) }}" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">رسوم إضافية</label>
                        <input type="number" step="0.01" min="0" name="additional_fee" value="{{ old('additional_fee', $shippingRate?->additional_fee) }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">تاريخ بداية السعر <span class="text-destructive">*</span></label>
                        <input type="date" name="valid_from" value="{{ old('valid_from', $shippingRate?->valid_from?->format('Y-m-d')) }}" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-semibold">تاريخ انتهاء السعر</label>
                        <input type="date" name="valid_to" value="{{ old('valid_to', $shippingRate?->valid_to?->format('Y-m-d')) }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm">
                    </div>
                    <div class="flex items-end">
                        <label class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-sm">
                            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $shippingRate?->is_active ?? true)) class="h-4 w-4 rounded border-border accent-accent">
                            <span>فعال</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-semibold">ملاحظات</label>
                    <textarea name="notes" rows="4" class="w-full rounded-lg border border-border bg-background px-3 py-2 text-sm">{{ old('notes', $shippingRate?->notes) }}</textarea>
                </div>

                <div class="flex flex-wrap justify-end gap-3 border-t border-border/40 pt-4">
                    <a href="{{ route('shipping-rates.index') }}" class="inline-flex h-10 items-center justify-center rounded-lg border border-border px-5 text-sm font-semibold hover:bg-secondary">إلغاء</a>
                    @if(!$viewOnly)
                        <button type="submit" name="save_and_new" value="1" class="inline-flex h-10 items-center justify-center rounded-lg border border-border px-5 text-sm font-semibold hover:bg-secondary">حفظ وإنشاء جديد</button>
                        <button type="submit" class="inline-flex h-10 items-center justify-center rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground hover:opacity-95">حفظ</button>
                    @endif
                </div>
            </form>
        </div>
    </section>
</div>
@endsection

@section('scripts')
<script>
    function normalizeCountryValue(value) {
        return String(value || '')
            .trim()
            .toLowerCase()
            .replace(/[أإآ]/g, 'ا')
            .replace(/ة/g, 'ه')
            .replace(/ى/g, 'ي')
            .replace(/\s*[-–—]\s*.*/g, '')
            .replace(/\(.*?\)/g, '')
            .replace(/[^\p{L}\p{N} ]/gu, ' ')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function countryCandidates(countrySelect) {
        const selectedOption = countrySelect?.selectedOptions?.[0];

        return [
            countrySelect?.value,
            selectedOption?.dataset?.nameAr,
            selectedOption?.dataset?.code,
            selectedOption?.textContent,
        ].filter(Boolean);
    }

    function portCountryCandidates(portOption) {
        return [
            portOption?.dataset?.country,
            portOption?.dataset?.countryCode,
            portOption?.textContent,
        ].filter(Boolean);
    }

    function countriesMatch(countrySelect, portOption) {
        const selectedCode = String(countrySelect?.selectedOptions?.[0]?.dataset?.code || '').trim().toUpperCase();
        const portCode = String(portOption?.dataset?.countryCode || '').trim().toUpperCase();

        if (selectedCode && portCode && selectedCode === portCode) {
            return true;
        }

        const selectedNames = countryCandidates(countrySelect).map(normalizeCountryValue).filter(Boolean);
        const portNames = portCountryCandidates(portOption).map(normalizeCountryValue).filter(Boolean);

        for (const left of selectedNames) {
            for (const right of portNames) {
                if (left === right || left.includes(right) || right.includes(left)) {
                    return true;
                }

                const leftParts = left.split(' ').filter(Boolean);
                const rightParts = right.split(' ').filter(Boolean);
                const shared = leftParts.filter(part => part.length > 2 && rightParts.includes(part));

                if (shared.length > 0) {
                    return true;
                }
            }
        }

        return false;
    }

    function originalPortOptions(portSelect) {
        if (!portSelect._shippingRatePortOptions) {
            portSelect._shippingRatePortOptions = Array.from(portSelect.querySelectorAll('option[data-country]'))
                .map((option) => option.cloneNode(true));
        }

        return portSelect._shippingRatePortOptions;
    }

    function filterPortOptions(countrySelectId, portSelectId) {
        const countrySelect = document.getElementById(countrySelectId);
        const portSelect = document.getElementById(portSelectId);

        if (!countrySelect || !portSelect) {
            return;
        }

        const selectedCountry = countrySelect.value;
        const currentValue = portSelect.value;
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = selectedCountry ? 'اختر الميناء' : 'اختر الدولة أولاً';

        const allOptions = originalPortOptions(portSelect);

        portSelect.innerHTML = '';
        portSelect.appendChild(defaultOption);

        const availableOptions = allOptions.filter((option) => {
            if (!selectedCountry) {
                return false;
            }

            return countriesMatch(countrySelect, option);
        });

        if (selectedCountry && availableOptions.length === 0) {
            defaultOption.textContent = 'لا توجد موانئ لهذه الدولة';
        }

        availableOptions.forEach((option) => {
            portSelect.appendChild(option.cloneNode(true));
        });

        if (selectedCountry && currentValue && Array.from(portSelect.options).some((option) => option.value === currentValue)) {
            portSelect.value = currentValue;
        } else {
            portSelect.value = '';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const originCountry = document.getElementById('origin-country');
        const destinationCountry = document.getElementById('destination-country');

        if (originCountry) {
            originCountry.addEventListener('change', function () {
                filterPortOptions('origin-country', 'origin-port');
            });
        }

        if (destinationCountry) {
            destinationCountry.addEventListener('change', function () {
                filterPortOptions('destination-country', 'destination-port');
            });
        }

        if (originCountry) {
            filterPortOptions('origin-country', 'origin-port');
        }
        if (destinationCountry) {
            filterPortOptions('destination-country', 'destination-port');
        }
    });
</script>
@endsection

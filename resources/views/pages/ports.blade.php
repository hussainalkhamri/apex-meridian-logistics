@extends('layouts.app')

@section('title', 'إدارة الموانئ — ' . config('company.name.ar'))

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs">إدارة العمليات</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">إدارة الموانئ العالمية</h1>
            <p class="mt-2 max-w-2xl text-white/80">أضف، عدّل أو أزل الموانئ مع ربط كل ميناء بدولته وعلمها، وتظهر تلقائياً داخل صفحة الطلب.</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
        @if(isset($errors) && $errors->any())
            <div class="mb-5 rounded-xl border border-destructive/20 bg-destructive/10 p-4 text-sm text-destructive">
                <ul class="list-disc space-y-1 pr-5">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6 flex flex-col gap-4 rounded-xl border border-border/60 bg-card p-5 shadow-card lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h2 class="text-lg font-extrabold text-foreground">قائمة الموانئ</h2>
                <p class="text-sm text-muted-foreground">اختر دولة لعرض موانئها، أو أضف ميناء جديد لاستخدامه في الطلبات.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form method="GET" action="{{ route('ports.index') }}" class="flex items-center gap-2">
                    <select name="country" onchange="this.form.submit()" class="h-10 min-w-52 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">كل الدول</option>
                        @foreach($dbCountries as $country)
                            <option value="{{ $country['name'] }}" @selected($selectedCountry === $country['name'])>
                                {{ $country['name'] }}
                            </option>
                        @endforeach
                    </select>
                    @if($selectedCountry)
                        <a href="{{ route('ports.index') }}" class="inline-flex h-10 items-center rounded-lg border border-border px-3 text-xs font-bold hover:bg-secondary/40">إظهار الكل</a>
                    @endif
                </form>
                <button type="button" onclick="openCountryModal()" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg border border-accent/25 bg-accent/10 px-5 text-sm font-bold text-accent hover:bg-accent/15">
                    <i data-lucide="flag" class="h-4 w-4"></i> دولة جديدة
                </button>
                <button type="button" onclick="openPortModal()" class="inline-flex h-10 shrink-0 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground hover:opacity-95">
                    <i data-lucide="plus" class="h-4 w-4"></i> ميناء جديد
                </button>
            </div>
        </div>

        <div class="mb-6 rounded-xl border border-border/60 bg-card p-5 shadow-card">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-foreground">الدول المضافة يدوياً</h2>
                    <p class="text-sm text-muted-foreground">أضف دولة مرة واحدة، ثم استخدمها مباشرة عند إضافة أو تعديل أي ميناء.</p>
                </div>
                <span class="inline-flex w-fit items-center rounded-full bg-secondary px-3 py-1 text-xs font-bold text-muted-foreground">{{ $managedCountries->count() }} دولة</span>
            </div>

            @if($managedCountries->isNotEmpty())
                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach($managedCountries as $country)
                        <article class="rounded-lg border border-border bg-background p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        @if($country->code)
                                            <img src="{{ asset('flags/' . strtolower($country->code) . '.svg') }}" alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover" loading="lazy" onerror="this.remove()">
                                        @endif
                                        @if($country->flag)
                                            <span class="text-base">{{ $country->flag }}</span>
                                        @endif
                                        <h3 class="truncate text-sm font-black text-foreground">{{ $country->name_ar ?: $country->name }}</h3>
                                    </div>
                                    <p class="mt-1 truncate text-xs text-muted-foreground">{{ $country->name }}{{ $country->code ? ' - ' . $country->code : '' }}</p>
                                    <span class="mt-2 inline-flex rounded-full border px-2 py-0.5 text-[11px] font-bold {{ $country->is_active ? 'border-success/25 bg-success/15 text-success' : 'border-destructive/20 bg-destructive/15 text-destructive' }}">
                                        {{ $country->is_active ? 'نشطة' : 'معطلة' }}
                                    </span>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <button type="button" onclick="openCountryModal({{ $country->id }}, {{ json_encode(['name' => $country->name, 'name_ar' => $country->name_ar, 'code' => $country->code, 'flag' => $country->flag, 'is_active' => $country->is_active], JSON_UNESCAPED_UNICODE) }})" class="flex h-8 w-8 items-center justify-center rounded-lg transition hover:bg-secondary" title="تعديل الدولة">
                                        <i data-lucide="pencil" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                    </button>
                                    <form action="{{ route('ports.countries.destroy', $country) }}" method="POST" onsubmit="return confirm('هل تريد حذف هذه الدولة من القائمة اليدوية؟ الموانئ الموجودة لن تُحذف.')" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-lg transition hover:bg-destructive/10" title="حذف الدولة">
                                            <i data-lucide="trash-2" class="h-3.5 w-3.5 text-destructive"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="mt-4 rounded-lg border border-dashed border-border bg-background px-4 py-5 text-center text-sm text-muted-foreground">
                    لا توجد دول مضافة يدوياً حتى الآن.
                </div>
            @endif
        </div>

        <div class="overflow-hidden rounded-xl border border-border/60 bg-card shadow-card">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-right">العلم</th>
                            <th class="px-4 py-3 text-right">اسم الميناء</th>
                            <th class="px-4 py-3 text-right">الكود</th>
                            <th class="px-4 py-3 text-right">الدولة</th>
                            <th class="px-4 py-3 text-right">الحالة</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($ports as $port)
                            <tr class="border-b border-border/40 transition-colors hover:bg-secondary/20">
                                <td class="px-4 py-3">
                                    @if($port->country_code)
                                        <img src="{{ asset('flags/' . strtolower($port->country_code) . '.svg') }}" alt="{{ $port->country }} flag" class="h-5 w-8 rounded-sm border border-border object-cover" loading="lazy" onerror="this.replaceWith(document.createTextNode('{{ $port->country_code }}'))">
                                    @else
                                        <span class="text-muted-foreground">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-semibold">{{ $port->name }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ $port->code ?: '—' }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    <span class="inline-flex items-center gap-2">
                                        @if($port->country_code)
                                            <img src="{{ asset('flags/' . strtolower($port->country_code) . '.svg') }}" alt="" class="h-3.5 w-5 rounded-[2px] border border-border object-cover" loading="lazy" onerror="this.remove()">
                                        @endif
                                        <span>{{ $port->country ?: '—' }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if($port->is_active)
                                        <span class="inline-flex items-center gap-1 rounded-full border border-success/25 bg-success/15 px-2.5 py-0.5 text-xs font-bold text-success">
                                            <span class="h-1.5 w-1.5 rounded-full bg-success"></span>نشط
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full border border-destructive/20 bg-destructive/15 px-2.5 py-0.5 text-xs font-bold text-destructive">
                                            <span class="h-1.5 w-1.5 rounded-full bg-destructive"></span>معطل
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button onclick="openPortModal({{ $port->id }}, {{ json_encode(['name' => $port->name, 'code' => $port->code, 'country' => $port->country, 'country_code' => $port->country_code, 'country_flag' => $port->country_flag, 'is_active' => $port->is_active], JSON_UNESCAPED_UNICODE) }})" class="flex h-8 w-8 items-center justify-center rounded-lg transition-all hover:bg-secondary" title="تعديل">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                        </button>
                                        <form action="{{ route('ports.destroy', $port) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا الميناء؟')" class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="flex h-8 w-8 items-center justify-center rounded-lg transition-all hover:bg-destructive/10" title="حذف">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5 text-destructive"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-12 text-center text-muted-foreground">
                                    <i data-lucide="anchor" class="mx-auto mb-3 h-10 w-10 opacity-30"></i>
                                    <p>لا يوجد موانئ مسجلة بعد</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<div id="port-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm flex">
    <div class="relative w-full max-w-xl rounded-2xl border border-border bg-card shadow-elegant" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-5">
            <h3 id="modal-title" class="text-lg font-extrabold">ميناء جديد</h3>
            <button onclick="closePortModal()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary/40">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="port-form" action="{{ route('ports.store') }}" method="POST" class="space-y-5 p-6">
            @csrf
            <div id="method-field"></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">اسم الميناء <span class="text-destructive">*</span></label>
                    <input type="text" name="name" id="inp-name" required class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">الكود</label>
                    <input type="text" name="code" id="inp-code" placeholder="مثال: CNSHA" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-semibold">الدولة</label>
                    <select name="country" id="inp-country" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">اختر الدولة</option>
                        @foreach($countryOptions as $country)
                            <option value="{{ $country['name'] }}" data-code="{{ $country['code'] }}" data-flag="{{ $country['flag'] }}">
                                {{ trim(($country['flag'] ? $country['flag'] . ' ' : '') . ($country['name_ar'] ?: $country['name']) . ' - ' . $country['name']) }}
                            </option>
                        @endforeach
                    </select>
                    <div id="country-flag-preview" class="mt-2 hidden items-center gap-2 text-xs font-semibold text-muted-foreground">
                        <img id="country-flag-preview-img" alt="" class="h-4 w-6 rounded-[2px] border border-border object-cover">
                        <span id="country-flag-preview-text"></span>
                    </div>
                    <input type="hidden" name="country_code" id="inp-country-code">
                    <input type="hidden" name="country_flag" id="inp-country-flag">
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" name="is_active" id="inp-active" value="1" checked class="h-4 w-4 rounded border-border accent-accent">
                        <span>الميناء نشط</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closePortModal()" class="h-10 rounded-lg border border-border px-5 text-sm font-semibold transition-all hover:bg-secondary">إلغاء</button>
                <button type="submit" class="h-10 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground hover:opacity-95">حفظ</button>
            </div>
        </form>
    </div>
</div>

<div id="country-modal" class="fixed inset-0 z-50 hidden items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm flex">
    <div class="relative w-full max-w-lg rounded-2xl border border-border bg-card shadow-elegant" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-5">
            <h3 id="country-modal-title" class="text-lg font-extrabold">دولة جديدة</h3>
            <button type="button" onclick="closeCountryModal()" class="flex h-8 w-8 items-center justify-center rounded-lg hover:bg-secondary/40">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="country-form" action="{{ route('ports.countries.store') }}" method="POST" class="space-y-5 p-6">
            @csrf
            <div id="country-method-field"></div>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">اسم الدولة بالإنجليزي <span class="text-destructive">*</span></label>
                    <input type="text" name="name" id="country-name" required placeholder="مثال: Brazil" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">اسم الدولة بالعربي</label>
                    <input type="text" name="name_ar" id="country-name-ar" placeholder="مثال: البرازيل" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">كود الدولة ISO</label>
                    <input type="text" name="code" id="country-code" maxlength="2" placeholder="BR" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm uppercase focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">العلم</label>
                    <input type="text" name="flag" id="country-flag" maxlength="16" placeholder="🇧🇷" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div class="md:col-span-2">
                    <label class="flex items-center gap-2 text-sm font-semibold">
                        <input type="checkbox" name="is_active" id="country-active" value="1" checked class="h-4 w-4 rounded border-border accent-accent">
                        <span>الدولة نشطة وتظهر في قوائم الموانئ</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closeCountryModal()" class="h-10 rounded-lg border border-border px-5 text-sm font-semibold transition-all hover:bg-secondary">إلغاء</button>
                <button type="submit" class="h-10 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground hover:opacity-95">حفظ الدولة</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const modal = document.getElementById('port-modal');
    const form = document.getElementById('port-form');
    const baseRoute = '{{ route('ports.store') }}';
    const countryModal = document.getElementById('country-modal');
    const countryForm = document.getElementById('country-form');
    const countryBaseRoute = '{{ route('ports.countries.store') }}';
    const countrySelect = document.getElementById('inp-country');
    const countryCodeInput = document.getElementById('inp-country-code');
    const countryFlagInput = document.getElementById('inp-country-flag');
    const countryFlagPreview = document.getElementById('country-flag-preview');
    const countryFlagPreviewImg = document.getElementById('country-flag-preview-img');
    const countryFlagPreviewText = document.getElementById('country-flag-preview-text');

    function flagImageUrl(countryCode, width = 40) {
        return countryCode ? `/flags/${countryCode.toLowerCase()}.svg` : '';
    }

    function syncCountryMeta() {
        const option = countrySelect.options[countrySelect.selectedIndex];
        countryCodeInput.value = option?.dataset.code || '';
        countryFlagInput.value = option?.dataset.flag || '';

        if (countryCodeInput.value) {
            countryFlagPreviewImg.src = flagImageUrl(countryCodeInput.value, 40);
            countryFlagPreviewImg.alt = `${countrySelect.value} flag`;
            countryFlagPreviewImg.onerror = () => {
                countryFlagPreview.classList.add('hidden');
                countryFlagPreview.classList.remove('flex');
            };
            countryFlagPreviewText.textContent = countrySelect.value;
            countryFlagPreview.classList.remove('hidden');
            countryFlagPreview.classList.add('flex');
        } else {
            countryFlagPreview.classList.add('hidden');
            countryFlagPreview.classList.remove('flex');
            countryFlagPreviewImg.removeAttribute('src');
            countryFlagPreviewText.textContent = '';
        }
    }

    function ensureCountryOption(data) {
        if (!data?.country) return;
        const exists = Array.from(countrySelect.options).some(option => option.value === data.country);
        if (exists) return;

        const option = document.createElement('option');
        option.value = data.country;
        option.dataset.code = data.country_code || '';
        option.dataset.flag = data.country_flag || '';
        option.textContent = data.country;
        countrySelect.appendChild(option);
    }

    function openPortModal(id = null, data = null) {
        document.getElementById('modal-title').textContent = id ? 'تعديل الميناء' : 'ميناء جديد';
        const methodField = document.getElementById('method-field');
        if (id) {
            form.action = `/ports/${id}`;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        } else {
            form.action = baseRoute;
            methodField.innerHTML = '';
        }

        if (data) {
            ensureCountryOption(data);
            document.getElementById('inp-name').value = data.name || '';
            document.getElementById('inp-code').value = data.code || '';
            countrySelect.value = data.country || '';
            document.getElementById('inp-active').checked = data.is_active !== false;
            syncCountryMeta();
            if (!countryCodeInput.value && data.country_code) countryCodeInput.value = data.country_code;
            if (!countryFlagInput.value && data.country_flag) countryFlagInput.value = data.country_flag;
        } else {
            form.reset();
            countryCodeInput.value = '';
            countryFlagInput.value = '';
            document.getElementById('inp-active').checked = true;
        }

        modal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closePortModal() {
        modal.classList.add('hidden');
        form.reset();
        countryCodeInput.value = '';
        countryFlagInput.value = '';
    }

    function openCountryModal(id = null, data = null) {
        document.getElementById('country-modal-title').textContent = id ? 'تعديل الدولة' : 'دولة جديدة';
        const methodField = document.getElementById('country-method-field');

        if (id) {
            countryForm.action = `/ports/countries/${id}`;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        } else {
            countryForm.action = countryBaseRoute;
            methodField.innerHTML = '';
        }

        if (data) {
            document.getElementById('country-name').value = data.name || '';
            document.getElementById('country-name-ar').value = data.name_ar || '';
            document.getElementById('country-code').value = data.code || '';
            document.getElementById('country-flag').value = data.flag || '';
            document.getElementById('country-active').checked = data.is_active !== false;
        } else {
            countryForm.reset();
            document.getElementById('country-active').checked = true;
        }

        countryModal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeCountryModal() {
        countryModal.classList.add('hidden');
        countryForm.reset();
    }

    countrySelect.addEventListener('change', syncCountryMeta);
    modal.addEventListener('click', closePortModal);
    countryModal.addEventListener('click', closeCountryModal);
</script>
@endsection

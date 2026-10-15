@extends('layouts.app')

@section('title', 'أسعار الشحن — النظام الجمركي')

@section('head')
<style>
    @keyframes shippingRateFade {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .shipping-rate-animate {
        animation: shippingRateFade 0.35s ease both;
    }
</style>
@endsection

@section('content')
@php
    $readOnly = $readOnly ?? false;
    $targetRoute = $readOnly ? route('shipping-rates.operations') : route('shipping-rates.index');
    $exportQuery = request()->except(['page', 'format']);
    $filterKeys = [
        'search',
        'shipping_type',
        'service_type',
        'incoterm',
        'container_type',
        'origin_country',
        'origin_port',
        'destination_country',
        'destination_port',
        'shipping_company',
        'status',
    ];
    $activeFiltersCount = collect($filterKeys)->filter(fn ($key) => filled(request($key)))->count();
    $pageRates = $shippingRates->getCollection();
    $pageActiveRates = $pageRates->where('is_active', true)->count();
    $pageRoutes = $pageRates
        ->map(fn ($rate) => trim(($rate->origin_port ?: '') . '|' . ($rate->destination_port ?: '')))
        ->filter(fn ($route) => $route !== '|')
        ->unique()
        ->count();
    $pageAveragePrice = $pageRates->avg(fn ($rate) => (float) $rate->price);
    $shippingTypeIcon = fn ($type) => match ((string) $type) {
        'جوي' => 'plane',
        'بري' => 'truck',
        default => 'ship',
    };
@endphp

<div>
    <section class="bg-hero py-10 text-white sm:py-12">
        <div class="container mx-auto px-4">
            <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-end">
                <div class="min-w-0">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-bold">
                        <i data-lucide="{{ $readOnly ? 'eye' : 'badge-dollar-sign' }}" class="h-4 w-4"></i>
                        {{ $readOnly ? 'إدارة العمليات' : 'مكتب التسعير' }}
                    </span>
                    <h1 class="mt-3 text-2xl font-extrabold leading-tight sm:text-3xl md:text-4xl">{{ $readOnly ? 'أسعار الشحن المعتمدة' : 'إدارة أسعار الشحن' }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-7 text-white/80 sm:text-base">
                        {{ $readOnly ? 'عرض الأسعار المتاحة للعمليات حسب الشركة والمسار ونوع الحاوية دون تعديل.' : 'لوحة عملية لإدارة أسعار الشحن، البحث في المسارات، استيراد القوائم، وتصدير النتائج حسب الفلاتر الحالية.' }}
                    </p>
                </div>

                @unless($readOnly)
                    <a href="{{ route('shipping-rates.create') }}" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-white px-5 text-sm font-extrabold text-primary shadow-lg transition hover:-translate-y-0.5 hover:bg-white/95 sm:w-auto">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        سعر شحن جديد
                    </a>
                @endunless
            </div>
        </div>
    </section>

    <section class="container mx-auto space-y-5 px-4 py-6 sm:py-8">
        <div id="shipping-rate-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/65 px-3 py-6 backdrop-blur-sm">
            <div class="shipping-rate-animate max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-2xl border border-border/60 bg-card p-4 shadow-[0_20px_70px_rgba(0,0,0,0.25)] sm:p-6">
                <div class="flex items-start justify-between gap-4 border-b border-border/40 pb-4">
                    <div class="min-w-0">
                        <div id="modal-id" class="text-[11px] font-bold uppercase tracking-[0.25em] text-muted-foreground"></div>
                        <h3 id="modal-title" class="mt-1 break-words text-xl font-extrabold text-foreground"></h3>
                    </div>
                    <button type="button" onclick="closeShippingRateModal()" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-border bg-background text-foreground transition hover:bg-secondary" title="إغلاق">
                        <i data-lucide="x" class="h-4 w-4"></i>
                    </button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-border/40 bg-secondary/20 p-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                            <i data-lucide="route" class="h-4 w-4 text-accent"></i>
                            الانطلاق
                        </div>
                        <div id="modal-origin" class="mt-2 break-words font-bold text-foreground"></div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-secondary/20 p-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-muted-foreground">
                            <i data-lucide="map-pin" class="h-4 w-4 text-accent"></i>
                            الوصول
                        </div>
                        <div id="modal-destination" class="mt-2 break-words font-bold text-foreground"></div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4">
                        <div class="text-xs font-bold text-muted-foreground">نوع الشحن</div>
                        <div id="modal-shipping-type" class="mt-2 font-semibold text-foreground"></div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4">
                        <div class="text-xs font-bold text-muted-foreground">شرط الشحن</div>
                        <div id="modal-incoterm" class="mt-2 font-extrabold text-foreground"></div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4">
                        <div class="text-xs font-bold text-muted-foreground">نوع الخدمة / الحاوية</div>
                        <div class="mt-2 font-semibold text-foreground">
                            <span id="modal-service-type"></span>
                            <span class="text-muted-foreground">/</span>
                            <span id="modal-container-type"></span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4">
                        <div class="text-xs font-bold text-muted-foreground">سعر الشحن</div>
                        <div id="modal-price" class="mt-2 font-extrabold text-accent"></div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4">
                        <div class="text-xs font-bold text-muted-foreground">رسوم إضافية</div>
                        <div id="modal-additional-fee" class="mt-2 font-semibold text-foreground"></div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4">
                        <div class="text-xs font-bold text-muted-foreground">الإجمالي</div>
                        <div id="modal-total" class="mt-2 font-extrabold text-foreground"></div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4">
                        <div class="text-xs font-bold text-muted-foreground">مدة العبور</div>
                        <div id="modal-transit" class="mt-2 font-semibold text-foreground"></div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4">
                        <div class="text-xs font-bold text-muted-foreground">صلاحية السعر</div>
                        <div class="mt-2 text-sm font-semibold text-foreground">
                            <span id="modal-valid-from"></span>
                            <span class="text-muted-foreground">إلى</span>
                            <span id="modal-valid-to"></span>
                        </div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4">
                        <div class="text-xs font-bold text-muted-foreground">الحالة</div>
                        <div id="modal-status" class="mt-2 inline-flex items-center rounded-full px-3 py-1 text-xs font-bold"></div>
                    </div>
                    <div class="rounded-xl border border-border/40 bg-background/60 p-4 sm:col-span-2">
                        <div class="text-xs font-bold text-muted-foreground">الملاحظات</div>
                        <div id="modal-notes" class="mt-2 whitespace-pre-line break-words text-sm leading-7 text-muted-foreground"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-border/60 bg-card p-4 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-muted-foreground">النتائج الحالية</span>
                    <i data-lucide="list-filter" class="h-5 w-5 text-accent"></i>
                </div>
                <div class="mt-3 text-2xl font-black text-foreground">{{ $shippingRates->total() }}</div>
                <div class="mt-1 text-xs text-muted-foreground">سعر مطابق للبحث والفلاتر</div>
            </div>
            <div class="rounded-xl border border-border/60 bg-card p-4 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-muted-foreground">الأسعار الفعالة</span>
                    <i data-lucide="badge-check" class="h-5 w-5 text-success"></i>
                </div>
                <div class="mt-3 text-2xl font-black text-foreground">{{ $pageActiveRates }}</div>
                <div class="mt-1 text-xs text-muted-foreground">ضمن الصفحة الحالية</div>
            </div>
            <div class="rounded-xl border border-border/60 bg-card p-4 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-muted-foreground">المسارات</span>
                    <i data-lucide="route" class="h-5 w-5 text-accent"></i>
                </div>
                <div class="mt-3 text-2xl font-black text-foreground">{{ $pageRoutes }}</div>
                <div class="mt-1 text-xs text-muted-foreground">مسار مختلف في هذه الصفحة</div>
            </div>
            <div class="rounded-xl border border-border/60 bg-card p-4 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <span class="text-sm font-bold text-muted-foreground">متوسط السعر</span>
                    <i data-lucide="chart-no-axes-combined" class="h-5 w-5 text-accent"></i>
                </div>
                <div class="mt-3 text-2xl font-black text-foreground">{{ $pageAveragePrice ? number_format($pageAveragePrice, 2) : '0.00' }}</div>
                <div class="mt-1 text-xs text-muted-foreground">للنتائج المعروضة حاليًا</div>
            </div>
        </div>

        <form method="GET" action="{{ $targetRoute }}" class="rounded-2xl border border-border/60 bg-card p-4 shadow-card sm:p-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <h2 class="text-lg font-extrabold text-foreground">البحث والفلترة</h2>
                    <p class="mt-1 text-sm text-muted-foreground">اكتب اسم شركة الشحن أو الميناء أو نوع الحاوية ثم طبّق الفلتر.</p>
                </div>
                @if($activeFiltersCount > 0)
                    <span class="inline-flex w-fit items-center gap-2 rounded-full border border-accent/25 bg-accent/10 px-3 py-1 text-xs font-bold text-accent">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        {{ $activeFiltersCount }} فلتر نشط
                    </span>
                @endif
            </div>

            <div class="mt-5 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <div class="md:col-span-2">
                    <label class="mb-1.5 block text-sm font-bold">بحث سريع</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="شركة الشحن، الميناء، الدولة، الحاوية..." class="h-11 w-full rounded-xl border border-border bg-background pr-10 pl-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">نوع الشحن</label>
                    <select name="shipping_type" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        @foreach($filtersData['shipping_types'] as $value)
                            <option value="{{ $value }}" @selected(request('shipping_type') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">نوع الخدمة</label>
                    <select name="service_type" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        @foreach($filtersData['service_types'] as $value)
                            <option value="{{ $value }}" @selected(request('service_type') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">شرط الشحن</label>
                    <select name="incoterm" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        @foreach(($filtersData['incoterms'] ?? collect(['FOB', 'EXW'])) as $value)
                            <option value="{{ $value }}" @selected(request('incoterm') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">نوع الحاوية</label>
                    <select name="container_type" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        @foreach($filtersData['container_types'] as $value)
                            <option value="{{ $value }}" @selected(request('container_type') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">بلد الانطلاق</label>
                    <select name="origin_country" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        @foreach($filtersData['origin_countries'] as $value)
                            <option value="{{ $value }}" @selected(request('origin_country') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">ميناء الانطلاق</label>
                    <select name="origin_port" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        @foreach($filtersData['origin_ports'] as $value)
                            <option value="{{ $value }}" @selected(request('origin_port') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">بلد الوصول</label>
                    <select name="destination_country" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        @foreach($filtersData['destination_countries'] as $value)
                            <option value="{{ $value }}" @selected(request('destination_country') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">ميناء الوصول</label>
                    <select name="destination_port" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        @foreach($filtersData['destination_ports'] as $value)
                            <option value="{{ $value }}" @selected(request('destination_port') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">شركة الشحن</label>
                    <select name="shipping_company" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        @foreach($filtersData['shipping_companies'] as $value)
                            <option value="{{ $value }}" @selected(request('shipping_company') === $value)>{{ $value }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-bold">الحالة</label>
                    <select name="status" class="h-11 w-full rounded-xl border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">الكل</option>
                        <option value="active" @selected(request('status') === 'active')>فعال</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>غير فعال</option>
                    </select>
                </div>
            </div>

            <div class="mt-5 flex flex-col gap-3 border-t border-border/40 pt-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-gradient-accent px-5 text-sm font-extrabold text-accent-foreground transition hover:opacity-95">
                        <i data-lucide="search-check" class="h-4 w-4"></i>
                        تطبيق البحث
                    </button>
                    <a href="{{ $targetRoute }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-border px-4 text-sm font-bold text-foreground transition hover:bg-secondary">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        مسح
                    </a>
                </div>

                @unless($readOnly)
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('shipping-rates.export', array_merge($exportQuery, ['format' => 'xlsx'])) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-border bg-background px-4 text-sm font-bold text-foreground transition hover:bg-secondary">
                            <i data-lucide="file-spreadsheet" class="h-4 w-4 text-success"></i>
                            Excel
                        </a>
                        <a href="{{ route('shipping-rates.export', array_merge($exportQuery, ['format' => 'pdf'])) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-border bg-background px-4 text-sm font-bold text-foreground transition hover:bg-secondary">
                            <i data-lucide="file-text" class="h-4 w-4 text-destructive"></i>
                            PDF
                        </a>
                    </div>
                @endunless
            </div>
        </form>

        @unless($readOnly)
            <div class="rounded-2xl border border-dashed border-border/70 bg-card p-4 shadow-card sm:p-5">
                <form method="POST" action="{{ route('shipping-rates.import') }}" enctype="multipart/form-data" class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    @csrf
                    <div class="min-w-0">
                        <h3 class="text-sm font-extrabold text-foreground">استيراد قائمة أسعار</h3>
                        <p class="mt-1 text-xs leading-6 text-muted-foreground">يدعم ملفات Excel وCSV مع منع تكرار نفس المسار والشركة والحاوية.</p>
                    </div>
                    <div class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center">
                        <label class="inline-flex h-10 cursor-pointer items-center justify-center gap-2 rounded-xl border border-border bg-background px-4 text-sm font-bold transition hover:bg-secondary">
                            <i data-lucide="upload" class="h-4 w-4"></i>
                            اختر ملف
                            <input id="shipping-rate-import-file" type="file" name="file" accept=".xlsx,.xls,.csv" class="sr-only">
                        </label>
                        <span id="shipping-rate-import-name" class="min-w-0 truncate text-xs text-muted-foreground">لم يتم اختيار ملف</span>
                        <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-accent/25 bg-accent/10 px-4 text-sm font-extrabold text-accent transition hover:bg-accent/15">
                            <i data-lucide="database-zap" class="h-4 w-4"></i>
                            استيراد
                        </button>
                    </div>
                </form>
            </div>
        @endunless

        <div class="hidden overflow-x-auto rounded-2xl border border-border/60 bg-card shadow-card lg:block">
            <table class="w-full min-w-[1180px] text-right text-sm">
                <thead class="bg-secondary/60 text-xs text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-extrabold">النوع</th>
                        <th class="px-4 py-3 font-extrabold">الشرط</th>
                        <th class="px-4 py-3 font-extrabold">المسار</th>
                        <th class="px-4 py-3 font-extrabold">شركة الشحن</th>
                        <th class="px-4 py-3 font-extrabold">الحاوية</th>
                        <th class="px-4 py-3 font-extrabold">السعر</th>
                        <th class="px-4 py-3 font-extrabold">الرسوم</th>
                        <th class="px-4 py-3 font-extrabold">المدة</th>
                        <th class="px-4 py-3 font-extrabold">الصلاحية</th>
                        <th class="px-4 py-3 font-extrabold">الحالة</th>
                        <th class="px-4 py-3 font-extrabold">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border/50">
                    @forelse($shippingRates as $rate)
                        @php
                            $additionalFee = (float) ($rate->additional_fee ?? 0);
                            $totalPrice = (float) $rate->price + $additionalFee;
                        @endphp
                        <tr class="transition hover:bg-secondary/30">
                            <td class="px-4 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-accent/20 bg-accent/10 text-accent">
                                        <i data-lucide="{{ $shippingTypeIcon($rate->shipping_type) }}" class="h-4 w-4"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="font-extrabold text-foreground">{{ $rate->shipping_type }}</div>
                                        <div class="text-xs text-muted-foreground">{{ $rate->service_type }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex rounded-full border border-accent/20 bg-accent/10 px-3 py-1 text-xs font-extrabold text-accent">{{ $rate->incoterm ?: 'FOB' }}</span>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="max-w-[260px] space-y-1">
                                    <div class="truncate font-bold text-foreground" title="{{ $rate->origin_country }} / {{ $rate->origin_port }}">{{ $rate->origin_country }} / {{ $rate->origin_port }}</div>
                                    <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                        <span class="h-px w-8 bg-border"></span>
                                        <i data-lucide="arrow-left" class="h-3.5 w-3.5"></i>
                                    </div>
                                    <div class="truncate font-bold text-foreground" title="{{ $rate->destination_country }} / {{ $rate->destination_port }}">{{ $rate->destination_country }} / {{ $rate->destination_port }}</div>
                                </div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="max-w-[170px] truncate font-bold text-foreground" title="{{ $rate->shipping_company }}">{{ $rate->shipping_company }}</div>
                                <div class="mt-1 text-xs text-muted-foreground">تحديث: {{ $rate->updated_at->format('Y-m-d') }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <span class="inline-flex max-w-[150px] truncate rounded-full border border-border/70 bg-background px-3 py-1 text-xs font-bold text-foreground" title="{{ $rate->container_type }}">{{ $rate->container_type }}</span>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-black text-accent">{{ number_format((float) $rate->price, 2) }}</div>
                                <div class="text-xs font-bold text-muted-foreground">{{ $rate->currency }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-bold text-foreground">{{ $additionalFee > 0 ? number_format($additionalFee, 2) : '—' }}</div>
                                <div class="text-xs text-muted-foreground">الإجمالي {{ number_format($totalPrice, 2) }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="font-bold text-foreground">{{ $rate->transit_time ?: '—' }}</div>
                                <div class="text-xs text-muted-foreground">يوم</div>
                            </td>
                            <td class="px-4 py-4 align-top text-xs">
                                <div class="font-bold text-foreground">{{ $rate->valid_from?->format('Y-m-d') ?? '—' }}</div>
                                <div class="text-muted-foreground">{{ $rate->valid_to?->format('Y-m-d') ?? 'مفتوح' }}</div>
                            </td>
                            <td class="px-4 py-4 align-top">
                                @if($rate->is_active)
                                    <span class="inline-flex rounded-full border border-success/25 bg-success/15 px-3 py-1 text-xs font-extrabold text-success">فعال</span>
                                @else
                                    <span class="inline-flex rounded-full border border-destructive/20 bg-destructive/15 px-3 py-1 text-xs font-extrabold text-destructive">غير فعال</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 align-top">
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-accent/20 bg-accent/10 text-accent transition hover:bg-accent/20"
                                        data-id="{{ $rate->id }}"
                                        data-shipping-company="{{ e($rate->shipping_company) }}"
                                        data-origin-country="{{ e($rate->origin_country) }}"
                                        data-origin-port="{{ e($rate->origin_port) }}"
                                        data-destination-country="{{ e($rate->destination_country) }}"
                                        data-destination-port="{{ e($rate->destination_port) }}"
                                        data-container-type="{{ e($rate->container_type) }}"
                                        data-service-type="{{ e($rate->service_type) }}"
                                        data-incoterm="{{ e($rate->incoterm ?: 'FOB') }}"
                                        data-shipping-type="{{ e($rate->shipping_type) }}"
                                        data-currency="{{ e($rate->currency) }}"
                                        data-price="{{ number_format((float) $rate->price, 2) }}"
                                        data-additional-fee="{{ $additionalFee > 0 ? number_format($additionalFee, 2) : '0.00' }}"
                                        data-total="{{ number_format($totalPrice, 2) }}"
                                        data-transit-time="{{ e($rate->transit_time ? $rate->transit_time . ' يوم' : '—') }}"
                                        data-valid-from="{{ e($rate->valid_from?->format('Y-m-d') ?? '—') }}"
                                        data-valid-to="{{ e($rate->valid_to?->format('Y-m-d') ?? 'مفتوح') }}"
                                        data-notes="{{ e($rate->notes ?? '') }}"
                                        data-status="{{ e($rate->is_active ? 'فعال' : 'غير فعال') }}"
                                        onclick="openShippingRateModal(this)"
                                        title="عرض التفاصيل"
                                    >
                                        <i data-lucide="eye" class="h-4 w-4"></i>
                                    </button>

                                    @unless($readOnly)
                                        <a href="{{ route('shipping-rates.edit', $rate) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-background text-foreground transition hover:bg-secondary" title="تعديل">
                                            <i data-lucide="pencil" class="h-4 w-4"></i>
                                        </a>
                                        <a href="{{ route('shipping-rates.copy', $rate) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-background text-foreground transition hover:bg-secondary" title="نسخ">
                                            <i data-lucide="copy" class="h-4 w-4"></i>
                                        </a>
                                        <form action="{{ route('shipping-rates.destroy', $rate) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا السعر؟')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-destructive/20 bg-background text-destructive transition hover:bg-destructive/10" title="حذف">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-muted-foreground">لا توجد أسعار مطابقة.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="space-y-3 lg:hidden">
            @forelse($shippingRates as $rate)
                @php
                    $additionalFee = (float) ($rate->additional_fee ?? 0);
                    $totalPrice = (float) $rate->price + $additionalFee;
                @endphp
                <article class="shipping-rate-animate rounded-2xl border border-border/60 bg-card p-4 shadow-card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full border border-accent/20 bg-accent/10 px-3 py-1 text-xs font-extrabold text-accent">
                                    <i data-lucide="{{ $shippingTypeIcon($rate->shipping_type) }}" class="h-3.5 w-3.5"></i>
                                    {{ $rate->shipping_type }}
                                </span>
                                <span class="rounded-full border border-border/70 bg-background px-3 py-1 text-xs font-bold text-muted-foreground">{{ $rate->service_type }}</span>
                                <span class="rounded-full border border-accent/20 bg-accent/10 px-3 py-1 text-xs font-extrabold text-accent">{{ $rate->incoterm ?: 'FOB' }}</span>
                            </div>
                            <h3 class="mt-3 break-words text-base font-extrabold text-foreground">{{ $rate->shipping_company }}</h3>
                        </div>
                        @if($rate->is_active)
                            <span class="shrink-0 rounded-full border border-success/25 bg-success/15 px-3 py-1 text-xs font-extrabold text-success">فعال</span>
                        @else
                            <span class="shrink-0 rounded-full border border-destructive/20 bg-destructive/15 px-3 py-1 text-xs font-extrabold text-destructive">غير فعال</span>
                        @endif
                    </div>

                    <div class="mt-4 space-y-3 rounded-xl border border-border/50 bg-secondary/15 p-3">
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-muted-foreground">من</div>
                            <div class="break-words font-bold text-foreground">{{ $rate->origin_country }} / {{ $rate->origin_port }}</div>
                        </div>
                        <div class="flex items-center gap-2 text-muted-foreground">
                            <span class="h-px flex-1 bg-border"></span>
                            <i data-lucide="arrow-left" class="h-4 w-4"></i>
                            <span class="h-px flex-1 bg-border"></span>
                        </div>
                        <div class="min-w-0">
                            <div class="text-xs font-bold text-muted-foreground">إلى</div>
                            <div class="break-words font-bold text-foreground">{{ $rate->destination_country }} / {{ $rate->destination_port }}</div>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                        <div class="rounded-xl border border-border/40 bg-background/60 p-3">
                            <div class="text-xs font-bold text-muted-foreground">الحاوية</div>
                            <div class="mt-1 break-words font-bold text-foreground">{{ $rate->container_type }}</div>
                        </div>
                        <div class="rounded-xl border border-border/40 bg-background/60 p-3">
                            <div class="text-xs font-bold text-muted-foreground">الشرط</div>
                            <div class="mt-1 font-extrabold text-foreground">{{ $rate->incoterm ?: 'FOB' }}</div>
                        </div>
                        <div class="rounded-xl border border-border/40 bg-background/60 p-3">
                            <div class="text-xs font-bold text-muted-foreground">المدة</div>
                            <div class="mt-1 font-bold text-foreground">{{ $rate->transit_time ?: '—' }} يوم</div>
                        </div>
                        <div class="rounded-xl border border-border/40 bg-background/60 p-3">
                            <div class="text-xs font-bold text-muted-foreground">سعر الشحن</div>
                            <div class="mt-1 break-words font-black text-accent">{{ number_format((float) $rate->price, 2) }} {{ $rate->currency }}</div>
                        </div>
                        <div class="rounded-xl border border-border/40 bg-background/60 p-3">
                            <div class="text-xs font-bold text-muted-foreground">الإجمالي</div>
                            <div class="mt-1 break-words font-black text-foreground">{{ number_format($totalPrice, 2) }} {{ $rate->currency }}</div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-border/40 pt-3">
                        <span class="text-xs text-muted-foreground">آخر تحديث: {{ $rate->updated_at->format('Y-m-d') }}</span>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-accent/20 bg-accent/10 text-accent transition hover:bg-accent/20"
                                data-id="{{ $rate->id }}"
                                data-shipping-company="{{ e($rate->shipping_company) }}"
                                data-origin-country="{{ e($rate->origin_country) }}"
                                data-origin-port="{{ e($rate->origin_port) }}"
                                data-destination-country="{{ e($rate->destination_country) }}"
                                data-destination-port="{{ e($rate->destination_port) }}"
                                data-container-type="{{ e($rate->container_type) }}"
                                data-service-type="{{ e($rate->service_type) }}"
                                data-incoterm="{{ e($rate->incoterm ?: 'FOB') }}"
                                data-shipping-type="{{ e($rate->shipping_type) }}"
                                data-currency="{{ e($rate->currency) }}"
                                data-price="{{ number_format((float) $rate->price, 2) }}"
                                data-additional-fee="{{ $additionalFee > 0 ? number_format($additionalFee, 2) : '0.00' }}"
                                data-total="{{ number_format($totalPrice, 2) }}"
                                data-transit-time="{{ e($rate->transit_time ? $rate->transit_time . ' يوم' : '—') }}"
                                data-valid-from="{{ e($rate->valid_from?->format('Y-m-d') ?? '—') }}"
                                data-valid-to="{{ e($rate->valid_to?->format('Y-m-d') ?? 'مفتوح') }}"
                                data-notes="{{ e($rate->notes ?? '') }}"
                                data-status="{{ e($rate->is_active ? 'فعال' : 'غير فعال') }}"
                                onclick="openShippingRateModal(this)"
                                title="عرض التفاصيل"
                            >
                                <i data-lucide="eye" class="h-4 w-4"></i>
                            </button>

                            @unless($readOnly)
                                <a href="{{ route('shipping-rates.edit', $rate) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-background text-foreground transition hover:bg-secondary" title="تعديل">
                                    <i data-lucide="pencil" class="h-4 w-4"></i>
                                </a>
                                <a href="{{ route('shipping-rates.copy', $rate) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-background text-foreground transition hover:bg-secondary" title="نسخ">
                                    <i data-lucide="copy" class="h-4 w-4"></i>
                                </a>
                                <form action="{{ route('shipping-rates.destroy', $rate) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من حذف هذا السعر؟')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-destructive/20 bg-background text-destructive transition hover:bg-destructive/10" title="حذف">
                                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                                    </button>
                                </form>
                            @endunless
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-border/60 bg-card p-10 text-center text-muted-foreground">لا توجد أسعار مطابقة.</div>
            @endforelse
        </div>

        @if($shippingRates->hasPages())
            <div class="rounded-2xl border border-border/60 bg-card p-3 shadow-card">
                {{ $shippingRates->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

@section('scripts')
<script>
    function setShippingRateModalText(id, value) {
        const element = document.getElementById(id);
        if (element) {
            element.textContent = value || '—';
        }
    }

    function openShippingRateModal(button) {
        const modal = document.getElementById('shipping-rate-modal');
        if (!modal || !button) {
            return;
        }

        const dataset = button.dataset || {};
        const currency = dataset.currency || '';

        setShippingRateModalText('modal-id', dataset.id ? `#${dataset.id}` : '');
        setShippingRateModalText('modal-title', dataset.shippingCompany || 'تفاصيل السعر');
        setShippingRateModalText('modal-shipping-type', dataset.shippingType || '—');
        setShippingRateModalText('modal-service-type', dataset.serviceType || '—');
        setShippingRateModalText('modal-incoterm', dataset.incoterm || 'FOB');
        setShippingRateModalText('modal-container-type', dataset.containerType || '—');
        setShippingRateModalText('modal-origin', `${dataset.originCountry || '—'} / ${dataset.originPort || '—'}`);
        setShippingRateModalText('modal-destination', `${dataset.destinationCountry || '—'} / ${dataset.destinationPort || '—'}`);
        setShippingRateModalText('modal-price', `${dataset.price || '0.00'} ${currency}`.trim());
        setShippingRateModalText('modal-additional-fee', `${dataset.additionalFee || '0.00'} ${currency}`.trim());
        setShippingRateModalText('modal-total', `${dataset.total || '0.00'} ${currency}`.trim());
        setShippingRateModalText('modal-transit', dataset.transitTime || '—');
        setShippingRateModalText('modal-valid-from', dataset.validFrom || '—');
        setShippingRateModalText('modal-valid-to', dataset.validTo || 'مفتوح');
        setShippingRateModalText('modal-notes', dataset.notes || 'لا توجد ملاحظات');

        const status = document.getElementById('modal-status');
        if (status) {
            status.textContent = dataset.status || '—';
            status.className = dataset.status === 'فعال'
                ? 'mt-2 inline-flex items-center rounded-full border border-success/25 bg-success/15 px-3 py-1 text-xs font-bold text-success'
                : 'mt-2 inline-flex items-center rounded-full border border-destructive/20 bg-destructive/15 px-3 py-1 text-xs font-bold text-destructive';
        }

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeShippingRateModal() {
        const modal = document.getElementById('shipping-rate-modal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('shipping-rate-modal');
        const importInput = document.getElementById('shipping-rate-import-file');
        const importName = document.getElementById('shipping-rate-import-name');

        if (modal) {
            modal.addEventListener('click', function (event) {
                if (event.target === modal) {
                    closeShippingRateModal();
                }
            });
        }

        if (importInput && importName) {
            importInput.addEventListener('change', function () {
                importName.textContent = importInput.files?.[0]?.name || 'لم يتم اختيار ملف';
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeShippingRateModal();
            }
        });
    });
</script>
@endsection

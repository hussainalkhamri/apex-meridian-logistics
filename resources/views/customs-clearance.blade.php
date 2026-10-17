@extends('layouts.app')

@section('title', 'التخليص والحاسبة الجمركية — ' . config('company.name.ar'))

@section('content')
@php
    $statusLabels = [
        'pending' => 'بانتظار البدء',
        'documents_review' => 'مراجعة المستندات',
        'inspection' => 'فحص جمركي',
        'duties' => 'احتساب الرسوم',
        'cleared' => 'تم التخليص',
        'on_hold' => 'موقوف',
    ];

    $statusClasses = [
        'pending' => 'bg-secondary text-foreground border-border',
        'documents_review' => 'bg-primary/15 text-primary border-primary/20',
        'inspection' => 'bg-warning/20 text-warning-foreground border-warning/30',
        'duties' => 'bg-accent/15 text-accent-foreground border-accent/20',
        'cleared' => 'bg-success/15 text-success border-success/25',
        'on_hold' => 'bg-destructive/15 text-destructive border-destructive/20',
    ];

    $documentLabels = [
        'invoice' => 'الفاتورة التجارية',
        'packing_list' => 'كشف التعبئة',
        'bill_of_lading' => 'بوليصة الشحن',
        'certificate_origin' => 'شهادة المنشأ',
        'authorization' => 'تفويض التخليص',
        'permit' => 'تصاريح خاصة',
    ];

    $customsCenters = [
        'ميناء الحديدة',
        'عفار',
        'ذمار',
        'الراهدة',
        'نهم',
        'شحن',
        'الوديعة',
        'ميتم',
        'الصليف',
        'رأس عيسى',
        'المكلا',
        'رقابة صنعاء',
        'حرض',
    ];

    $workflowSteps = [
        ['icon' => 'file-check-2', 'title' => 'استلام الملف', 'desc' => 'مطابقة الفاتورة، التعبئة، البوليصة والتفويض.'],
        ['icon' => 'scan-search', 'title' => 'الفحص والمراجعة', 'desc' => 'متابعة المنفذ، البيان، ورقم الإقرار الجمركي.'],
        ['icon' => 'calculator', 'title' => 'احتساب الرسوم', 'desc' => 'تقدير الرسوم حسب القيمة والبنود التعريفية.'],
        ['icon' => 'badge-check', 'title' => 'الإفراج', 'desc' => 'تحديث الحالة وتاريخ الإفراج بعد اكتمال الإجراءات.'],
    ];
@endphp

<div>
    <section class="bg-hero py-10 text-white">
        <div class="container mx-auto px-4">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-bold">
                        <i data-lucide="shield-check" class="h-3.5 w-3.5"></i>
                        Customs Desk
                    </span>
                    <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">التخليص والحاسبة الجمركية</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-white/80">
                        متابعة ملفات التخليص، تجهيز الإقرارات، مراجعة المستندات، وحساب الرسوم التقديرية من نفس شاشة العمليات.
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-3 text-xs sm:grid-cols-4 lg:min-w-[34rem]">
                    @foreach($workflowSteps as $step)
                        <div class="rounded-lg border border-white/15 bg-white/10 p-3">
                            <div class="mb-3 inline-grid h-8 w-8 place-items-center rounded-lg bg-white/15">
                                <i data-lucide="{{ $step['icon'] }}" class="h-4 w-4"></i>
                            </div>
                            <div class="font-black">{{ $step['title'] }}</div>
                            <p class="mt-1 leading-relaxed text-white/65">{{ $step['desc'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-2xl font-extrabold">{{ $stats['total'] }}</div>
                        <div class="text-xs text-muted-foreground">ملفات التخليص</div>
                    </div>
                    <i data-lucide="folders" class="h-5 w-5 text-accent"></i>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-2xl font-extrabold">{{ $stats['pending'] }}</div>
                        <div class="text-xs text-muted-foreground">بانتظار البدء</div>
                    </div>
                    <i data-lucide="hourglass" class="h-5 w-5 text-muted-foreground"></i>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-2xl font-extrabold">{{ $stats['in_progress'] }}</div>
                        <div class="text-xs text-muted-foreground">قيد المعالجة</div>
                    </div>
                    <i data-lucide="list-checks" class="h-5 w-5 text-primary"></i>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-2xl font-extrabold text-success">{{ $stats['cleared'] }}</div>
                        <div class="text-xs text-muted-foreground">تم التخليص</div>
                    </div>
                    <i data-lucide="badge-check" class="h-5 w-5 text-success"></i>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-2xl font-extrabold text-destructive">{{ $stats['on_hold'] }}</div>
                        <div class="text-xs text-muted-foreground">موقوف</div>
                    </div>
                    <i data-lucide="circle-pause" class="h-5 w-5 text-destructive"></i>
                </div>
            </div>
            <div class="rounded-xl border border-border bg-card p-5 shadow-card">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <div class="text-2xl font-extrabold" dir="ltr">${{ number_format($stats['fees'], 2) }}</div>
                        <div class="text-xs text-muted-foreground">رسوم مسجلة</div>
                    </div>
                    <i data-lucide="receipt-text" class="h-5 w-5 text-accent"></i>
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-border bg-card p-2 shadow-card">
            <div class="grid gap-2 md:grid-cols-3" role="tablist" aria-label="تبويبات التخليص الجمركي">
                <button id="customs-tab-button-files" type="button" role="tab" aria-selected="false" aria-controls="customs-tab-files" data-customs-tab="files" class="customs-tab-btn inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-transparent bg-secondary/35 px-4 text-sm font-black text-foreground transition hover:bg-secondary/60">
                    <i data-lucide="folder-kanban" class="h-4 w-4"></i>
                    <span>ملفات التخليص</span>
                </button>
                <button id="customs-tab-button-calculator" type="button" role="tab" aria-selected="true" aria-controls="customs-tab-calculator" data-customs-tab="calculator" class="customs-tab-btn inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-accent/60 bg-gradient-accent px-4 text-sm font-black text-accent-foreground shadow-glow transition">
                    <i data-lucide="calculator" class="h-4 w-4"></i>
                    <span>الحاسبة الجمركية</span>
                </button>
                <button id="customs-tab-button-declaration" type="button" role="tab" aria-selected="false" aria-controls="customs-tab-declaration" data-customs-tab="declaration" class="customs-tab-btn inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-transparent bg-secondary/35 px-4 text-sm font-black text-foreground transition hover:bg-secondary/60">
                    <i data-lucide="clipboard-list" class="h-4 w-4"></i>
                    <span>إقرار جمركي سريع</span>
                </button> 
            </div>
        </div>

        <div class="mt-4 space-y-6">
            <section id="customs-tab-declaration" data-customs-panel="declaration" role="tabpanel" aria-labelledby="customs-tab-button-declaration" class="hidden rounded-xl border border-border bg-card shadow-card">
                <div class="flex flex-col gap-4 border-b border-border/70 p-5 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-black">
                            <i data-lucide="clipboard-list" class="h-5 w-5 text-accent"></i>
                            <span id="declaration-request-title">إقرار جمركي سريع</span>
                        </h2>
                        <p id="declaration-request-description" class="mt-1 text-xs text-muted-foreground">طلب مختصر بنفس حقول الموقع المرجعي لإرسال بيانات التخليص بسرعة.</p>
                    </div>
                    <div class="grid gap-2 rounded-lg border border-border bg-secondary/35 p-1 text-xs font-bold sm:grid-cols-2" role="tablist" aria-label="نوع طلب الإقرار">
                        <button id="declaration-request-button-quick" type="button" role="tab" aria-selected="true" aria-controls="declaration-quick-panel" data-declaration-request="quick" class="declaration-request-btn inline-flex h-9 items-center justify-center gap-2 rounded-md bg-card px-4 text-foreground shadow-sm">
                            <i data-lucide="zap" class="h-3.5 w-3.5 text-accent"></i>
                            طلب سريع
                        </button>
                        <button id="declaration-request-button-detailed" type="button" role="tab" aria-selected="false" aria-controls="declaration-detailed-panel" data-declaration-request="detailed" class="declaration-request-btn inline-flex h-9 items-center justify-center gap-2 rounded-md px-4 text-muted-foreground">
                            <i data-lucide="list-checks" class="h-3.5 w-3.5 text-muted-foreground"></i>
                            طلب مفصل
                        </button>
                    </div>
                </div>

                <div class="space-y-6 p-5">
                    <div id="declaration-quick-panel" data-declaration-request-panel="quick" role="tabpanel" aria-labelledby="declaration-request-button-quick" class="grid gap-5 xl:grid-cols-[1.25fr_.75fr]">
                        <div class="space-y-5">
                            <div>
                                <h3 class="text-base font-black">طلب عرض سعر</h3>
                                <p class="mt-1 text-xs text-muted-foreground">أملأ النموذج وسنتواصل معك بأسرع وقت</p>
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label for="quick-declaration-name" class="text-xs font-bold">الاسم الكامل *</label>
                                    <input id="quick-declaration-name" type="text" placeholder="أدخل اسمك" class="quick-declaration-field h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="quick-declaration-company" class="text-xs font-bold">اسم الشركة</label>
                                    <input id="quick-declaration-company" type="text" placeholder="اسم شركتك (اختياري)" class="quick-declaration-field h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="quick-declaration-email" class="text-xs font-bold">البريد الإلكتروني *</label>
                                    <input id="quick-declaration-email" type="email" placeholder="example@email.com" class="quick-declaration-field h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="quick-declaration-phone" class="text-xs font-bold">رقم الجوال *</label>
                                    <input id="quick-declaration-phone" type="tel" placeholder="+966 5XX XXX XXXX" class="quick-declaration-field h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="quick-declaration-service" class="text-xs font-bold">نوع الخدمة *</label>
                                    <select id="quick-declaration-service" class="quick-declaration-field h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">اختر نوع الخدمة</option>
                                        <option value="الشحن البحري">الشحن البحري</option>
                                        <option value="الشحن الجوي">الشحن الجوي</option>
                                        <option value="الشحن البري">الشحن البري</option>
                                        <option value="التخليص الجمركي">التخليص الجمركي</option>
                                        <option value="خدمات متعددة">خدمات متعددة</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label for="quick-declaration-shipment-type" class="text-xs font-bold">نوع الشحنة</label>
                                    <select id="quick-declaration-shipment-type" class="quick-declaration-field h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">اختر نوع الشحنة</option>
                                        <option value="حاوية كاملة (FCL)">حاوية كاملة (FCL)</option>
                                        <option value="حاوية مجمعة (LCL)">حاوية مجمعة (LCL)</option>
                                        <option value="بضائع سائبة">بضائع سائبة</option>
                                        <option value="بضائع خاصة">بضائع خاصة</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label for="quick-declaration-origin" class="text-xs font-bold">من (المنشأ)</label>
                                    <input id="quick-declaration-origin" type="text" placeholder="مدينة/ميناء المنشأ" class="quick-declaration-field h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="quick-declaration-destination" class="text-xs font-bold">إلى (الوجهة)</label>
                                    <input id="quick-declaration-destination" type="text" placeholder="مدينة/ميناء الوجهة" class="quick-declaration-field h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5 md:col-span-2">
                                    <label for="quick-declaration-details" class="text-xs font-bold">تفاصيل إضافية</label>
                                    <textarea id="quick-declaration-details" rows="4" placeholder="اكتب تفاصيل الشحنة (الوزن، الأبعاد، نوع البضاعة، أي متطلبات خاصة...)" class="quick-declaration-field w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2 border-t border-border/70 pt-4">
                                <button type="button" id="prepare-quick-declaration" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground">
                                    <i data-lucide="send" class="h-4 w-4"></i>
                                    إرسال الطلب
                                </button>
                            </div>
                        </div>
                        <aside class="rounded-lg border border-border bg-secondary/25 p-5 text-xs leading-relaxed">
                            <h3 class="mb-4 font-black text-foreground">تواصل مباشر</h3>
                            <div class="space-y-3 text-muted-foreground">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="phone" class="h-4 w-4 text-accent"></i>
                                    <span>اتصل بنا</span>
                                    <strong class="text-foreground" dir="ltr">{{ config('company.landline.display') }} · {{ config('company.phones.0.display') }}</strong>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="message-circle" class="h-4 w-4 text-accent"></i>
                                    <span>واتساب</span>
                                    <strong class="text-foreground" dir="ltr">{{ config('company.phones.0.display') }}</strong>
                                </div>
                                <div class="flex items-center gap-2">
                                    <i data-lucide="mail" class="h-4 w-4 text-accent"></i>
                                    <span>البريد الإلكتروني</span>
                                    <strong class="text-foreground" dir="ltr">{{ config('company.email') }}</strong>
                                </div>
                            </div>
                            <div class="mt-5 rounded-lg border border-dashed border-border bg-background p-3">
                                <div class="font-black text-foreground">لماذا تختارنا؟</div>
                                <div class="mt-2 space-y-1 text-muted-foreground">
                                    <div>✓ عروض أسعار تنافسية</div>
                                    <div>✓ رد سريع خلال 24 ساعة</div>
                                    <div>✓ استشارات</div>
                                    <div>✓ فريق متخصص</div>
                                </div>
                            </div>
                            <pre id="quick-declaration-preview" class="sr-only"></pre>
                        </aside>
                    </div>

                    <div id="declaration-detailed-panel" data-declaration-request-panel="detailed" role="tabpanel" aria-labelledby="declaration-request-button-detailed" class="hidden space-y-5">
                        <div class="rounded-lg border border-border bg-secondary/20 px-4 py-5">
                            <div class="relative mx-auto max-w-3xl">
                                <div class="absolute left-[12.5%] right-[12.5%] top-4 h-1.5 overflow-hidden rounded-full border border-border/70 bg-secondary shadow-inner">
                                    <span id="declaration-progress-line" class="absolute inset-y-0 right-0 rounded-full bg-gradient-accent shadow-glow transition-all duration-300" style="width: 0%"></span>
                                </div>
                                <div class="relative grid grid-cols-4 gap-2 text-center text-[11px] font-bold">
                                    <div data-declaration-step-target="1" class="declaration-step-indicator">
                                        <span class="declaration-step-circle mx-auto grid h-8 w-8 place-items-center rounded-full border border-accent/60 bg-gradient-accent text-xs font-black text-accent-foreground shadow-glow ring-2 ring-background">1</span>
                                        <span class="declaration-step-label mt-2 block text-foreground">تفاصيل الشحنة</span>
                                    </div>
                                    <div data-declaration-step-target="2" class="declaration-step-indicator">
                                        <span class="declaration-step-circle mx-auto grid h-8 w-8 place-items-center rounded-full border border-border bg-background text-xs font-black text-muted-foreground ring-2 ring-background">2</span>
                                        <span class="declaration-step-label mt-2 block text-muted-foreground">المرفقات</span>
                                    </div>
                                    <div data-declaration-step-target="3" class="declaration-step-indicator">
                                        <span class="declaration-step-circle mx-auto grid h-8 w-8 place-items-center rounded-full border border-border bg-background text-xs font-black text-muted-foreground ring-2 ring-background">3</span>
                                        <span class="declaration-step-label mt-2 block text-muted-foreground">بيانات العميل</span>
                                    </div>
                                    <div data-declaration-step-target="4" class="declaration-step-indicator">
                                        <span class="declaration-step-circle mx-auto grid h-8 w-8 place-items-center rounded-full border border-border bg-background text-xs font-black text-muted-foreground ring-2 ring-background">4</span>
                                        <span class="declaration-step-label mt-2 block text-muted-foreground">المراجعة</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div data-declaration-step-panel="1" class="declaration-step-panel space-y-5">
                            <div>
                                <h3 class="text-base font-black">بيانات الإقرار الجمركي</h3>
                                <p class="mt-1 text-xs text-muted-foreground">أدخل بيانات الإقرار والأصناف بنفس ترتيب بيانات رسق، ثم حمّل ملف XML من خطوة المراجعة.</p>
                            </div>

                            <div class="grid gap-2 rounded-lg border border-border bg-secondary/35 p-1 text-xs font-bold sm:w-fit sm:grid-cols-2" role="tablist" aria-label="نوع الإقرار">
                                <button type="button" data-declaration-mode="import" class="declaration-mode-btn inline-flex h-9 items-center justify-center gap-2 rounded-md bg-card px-4 text-foreground shadow-sm">
                                    <i data-lucide="download" class="h-3.5 w-3.5 text-accent"></i>
                                    استيراد
                                </button>
                                <button type="button" data-declaration-mode="export" class="declaration-mode-btn inline-flex h-9 items-center justify-center gap-2 rounded-md px-4 text-muted-foreground">
                                    <i data-lucide="upload" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                    تصدير
                                </button>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label for="detailed-service-type" class="text-xs font-bold">نوع الخدمة</label>
                                    <select id="detailed-service-type" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="التخليص الجمركي" selected>التخليص الجمركي</option>
                                        <option value="الشحن البحري">الشحن البحري</option>
                                        <option value="الشحن الجوي">الشحن الجوي</option>
                                        <option value="الشحن البري">الشحن البري</option>
                                        <option value="خدمات متعددة">خدمات متعددة</option>
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label for="detailed-quote-customer" class="text-xs font-bold">اسم العميل</label>
                                    <input id="detailed-quote-customer" type="text" value="{{ auth()->user()->name ?? '' }}" placeholder="سيتم تعبئته تلقائياً" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-shipment-no" class="text-xs font-bold">رقم الإرسالية</label>
                                    <input id="declaration-shipment-no" type="text" placeholder="SHT-0001" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-customer" class="text-xs font-bold">اسم المستورد / المصدر</label>
                                    <input id="declaration-customer" type="text" placeholder="اسم المستورد أو المصدر" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-importer-code" class="text-xs font-bold">رمز المستورد</label>
                                    <input id="declaration-importer-code" type="text" placeholder="1" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-declarant-code" class="text-xs font-bold">رمز المصرح</label>
                                    <input id="declaration-declarant-code" type="text" placeholder="0165213" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-declarant-name" class="text-xs font-bold">اسم المصرح</label>
                                    <input id="declaration-declarant-name" type="text" placeholder="اسم المصرح الجمركي" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-customs-office-code" class="text-xs font-bold">كود المنفذ</label>
                                    <input id="declaration-customs-office-code" type="text" value="0309" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-no" class="text-xs font-bold">رقم الإقرار</label>
                                    <input id="declaration-no" type="text" placeholder="رقم الإقرار الجمركي" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-serial-no" class="text-xs font-bold">رقم المسلسل</label>
                                    <input id="declaration-serial-no" type="text" placeholder="رقم مسلسل البيان" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-bayan-no" class="text-xs font-bold">رقم البيان / المنصة</label>
                                    <input id="declaration-bayan-no" type="text" placeholder="رقم البيان" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-country" class="text-xs font-bold">الدولة</label>
                                    <input id="declaration-country" type="text" value="اليمن" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-destination" class="text-xs font-bold">جهة الوصول</label>
                                    <input id="declaration-destination" type="text" placeholder="المنفذ أو الجهة" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-goods-type" class="text-xs font-bold">نوع الصنف</label>
                                    <input id="declaration-goods-type" type="text" placeholder="مثال: مشتقات نفطية، مواد غذائية..." class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-driver" class="text-xs font-bold">السائق</label>
                                    <input id="declaration-driver" type="text" placeholder="اسم السائق" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-truck" class="text-xs font-bold">رقم القاطرة</label>
                                    <input id="declaration-truck" type="text" placeholder="رقم القاطرة / اللوحة" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="detailed-items-count" class="text-xs font-bold">عدد الأصناف</label>
                                    <input id="detailed-items-count" type="number" min="0" placeholder="عدد الأصناف" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="detailed-clearance-center" class="text-xs font-bold">مركز التخليص</label>
                                    <select id="detailed-clearance-center" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">اختر المركز</option>
                                        @foreach(['عفار', 'الحديدة', 'ذمار', 'الراهدة', 'نهم', 'شحن', 'الوديعة', 'ميتم', 'الصليف', 'رأس عيسى', 'المكلاء', 'رقابة صنعاء', 'حرض', 'أخرى'] as $center)
                                            <option value="{{ $center }}">{{ $center }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="space-y-1.5">
                                    <label for="detailed-arrival-date" class="text-xs font-bold">تاريخ وصول الشحنة إلى الجمارك</label>
                                    <input id="detailed-arrival-date" type="date" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="declaration-file-name" class="text-xs font-bold">اسم ملف XML</label>
                                    <input id="declaration-file-name" type="text" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="detailed-quote-note" class="text-xs font-bold">ملاحظة</label>
                                    <input id="detailed-quote-note" type="text" placeholder="اكتب ملاحظة..." class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h4 class="text-sm font-black">تفاصيل الأصناف</h4>
                                        <p class="mt-1 text-xs text-muted-foreground">أضف البند التعريفي، الوزن، القيمة، والمرجع لكل صنف.</p>
                                    </div>
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" id="add-declaration-row" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border bg-card px-3 text-xs font-bold hover:bg-secondary/40">
                                            <i data-lucide="plus" class="h-4 w-4 text-accent"></i>
                                            إضافة صنف
                                        </button>
                                        <button type="button" id="reset-declaration" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border bg-card px-3 text-xs font-bold hover:bg-secondary/40">
                                            <i data-lucide="rotate-ccw" class="h-4 w-4 text-muted-foreground"></i>
                                            تصفية
                                        </button>
                                    </div>
                                </div>

                                <div class="overflow-x-auto rounded-lg border border-border">
                                    <table class="w-full min-w-[84rem] text-xs">
                                        <thead class="bg-secondary/45 text-muted-foreground">
                                            <tr>
                                                <th class="px-3 py-2 text-right">اسم الصنف</th>
                                                <th class="px-3 py-2 text-right">الماركة</th>
                                                <th class="px-3 py-2 text-right">المنشأ</th>
                                                <th class="px-3 py-2 text-right">الكمية</th>
                                                <th class="px-3 py-2 text-right">نوع الطرد</th>
                                                <th class="px-3 py-2 text-right">عدد الطرود</th>
                                                <th class="px-3 py-2 text-right">وحدة السعر</th>
                                                <th class="px-3 py-2 text-right">وزن الطرد</th>
                                                <th class="px-3 py-2 text-right">الوزن الصافي</th>
                                                <th class="px-3 py-2 text-right">سعر الوحدة</th>
                                                <th class="px-3 py-2 text-right">القيمة</th>
                                                <th class="px-3 py-2 text-right">المرجع</th>
                                                <th class="px-3 py-2 text-right">HS</th>
                                                <th class="px-3 py-2 text-right"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="declaration-items-body"></tbody>
                                    </table>
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                    <div class="rounded-lg border border-border bg-secondary/30 p-3">
                                        <div class="text-xs text-muted-foreground">عدد الأصناف</div>
                                        <div id="declaration-total-items" class="mt-1 text-lg font-black">0</div>
                                    </div>
                                    <div class="rounded-lg border border-border bg-secondary/30 p-3">
                                        <div class="text-xs text-muted-foreground">إجمالي عدد الطرود</div>
                                        <div id="declaration-total-packages" class="mt-1 text-lg font-black">0</div>
                                    </div>
                                    <div class="rounded-lg border border-border bg-secondary/30 p-3">
                                        <div class="text-xs text-muted-foreground">الوزن الصافي</div>
                                        <div id="declaration-total-weight" class="mt-1 text-lg font-black">0 كجم</div>
                                    </div>
                                    <div class="rounded-lg border border-border bg-secondary/30 p-3">
                                        <div class="text-xs text-muted-foreground">القيمة الإجمالية</div>
                                        <div id="declaration-total-value" class="mt-1 text-lg font-black" dir="ltr">$0.00</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div data-declaration-step-panel="2" class="declaration-step-panel hidden space-y-5">
                            <div>
                                <h3 class="text-base font-black">المستندات والمرفقات</h3>
                                <p class="mt-1 text-xs text-muted-foreground">يمكنك رفع: الفاتورة التجارية، قائمة التعبئة، شهادة المنشأ، أو أي مستندات أخرى</p>
                            </div>
                            <label for="detailed-declaration-files" class="block cursor-pointer rounded-lg border border-dashed border-border bg-secondary/25 p-8 text-center text-xs hover:bg-secondary/40">
                                <i data-lucide="upload-cloud" class="mx-auto mb-3 h-8 w-8 text-accent"></i>
                                <span class="block font-black text-foreground">اسحب الملفات هنا أو انقر للاختيار</span>
                                <span class="mt-1 block text-muted-foreground">PDF, JPG, PNG (حد أقصى 10 ميجابايت لكل ملف)</span>
                                <span class="mt-4 inline-flex h-9 items-center justify-center rounded-lg bg-gradient-accent px-4 font-bold text-accent-foreground">اختر الملفات</span>
                                <input id="detailed-declaration-files" type="file" multiple class="sr-only" accept=".pdf,.jpg,.jpeg,.png">
                            </label>
                            <div id="detailed-file-list" class="rounded-lg bg-secondary/25 p-3 text-xs text-muted-foreground">لم يتم اختيار ملفات بعد.</div>
                        </div>

                        <div data-declaration-step-panel="3" class="declaration-step-panel hidden space-y-5">
                            <div>
                                <h3 class="text-base font-black">بيانات العميل</h3>
                                <p class="mt-1 text-xs text-muted-foreground">اختر عميلًا محفوظًا أو أدخل بيانات عميل جديد وسيحفظه النظام تلقائيًا عند اكتمال الاسم مع البريد أو الهاتف.</p>
                            </div>
                            <div class="grid gap-3 rounded-lg border border-border bg-secondary/20 p-4 md:grid-cols-[1fr_auto] md:items-end">
                                <div class="space-y-1.5">
                                    <label for="detailed-customer-id" class="text-xs font-bold">اختيار عميل موجود</label>
                                    <select id="detailed-customer-id" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                        <option value="">عميل جديد / إدخال يدوي</option>
                                        @foreach($customersForCustoms as $customer)
                                            <option value="{{ $customer['id'] }}">{{ $customer['company'] }} @if($customer['contact_name']) — {{ $customer['contact_name'] }} @endif</option>
                                        @endforeach
                                    </select>
                                </div>
                                <button type="button" id="save-detailed-customer" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">
                                    <i data-lucide="save" class="h-4 w-4 text-accent"></i>
                                    حفظ العميل الآن
                                </button>
                                <div id="detailed-customer-save-status" class="text-[11px] font-semibold text-muted-foreground md:col-span-2">سيتم حفظ العميل تلقائيًا عند إدخال الاسم مع البريد أو الهاتف.</div>
                            </div>
                            <div class="grid gap-3 md:grid-cols-2">
                                <div class="space-y-1.5">
                                    <label for="detailed-customer-name" class="text-xs font-bold">الاسم الكامل *</label>
                                    <input id="detailed-customer-name" type="text" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="detailed-customer-company" class="text-xs font-bold">اسم الشركة</label>
                                    <input id="detailed-customer-company" type="text" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="detailed-customer-email" class="text-xs font-bold">البريد الإلكتروني *</label>
                                    <input id="detailed-customer-email" type="email" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="detailed-customer-phone" class="text-xs font-bold">رقم الجوال *</label>
                                    <input id="detailed-customer-phone" type="tel" placeholder="+966 5XX XXX XXXX" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" dir="ltr">
                                </div>
                                <div class="space-y-1.5 md:col-span-2">
                                    <label for="detailed-customer-notes" class="text-xs font-bold">ملاحظات إضافية</label>
                                    <textarea id="detailed-customer-notes" rows="4" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
                                </div>
                            </div>
                        </div>

                        <div data-declaration-step-panel="4" class="declaration-step-panel hidden space-y-5">
                            <div>
                                <h3 class="text-base font-black">مراجعة الطلب</h3>
                                <p class="mt-1 text-xs text-muted-foreground">راجع البيانات قبل تأكيد وإرسال الطلب.</p>
                            </div>
                            <pre id="detailed-declaration-review" class="min-h-72 whitespace-pre-wrap rounded-lg border border-border bg-secondary/25 p-4 font-sans text-xs leading-relaxed text-foreground"></pre>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" id="download-declaration-excel" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground">
                                    <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                                    تحميل Excel
                                </button>
                                <button type="button" id="copy-declaration-summary" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">
                                    <i data-lucide="copy" class="h-4 w-4 text-muted-foreground"></i>
                                    نسخ الملخص
                                </button>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-border/70 pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <button type="button" id="declaration-prev-step" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                                السابق
                            </button>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" id="declaration-next-step" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-xs font-bold text-accent-foreground">
                                    التالي
                                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="customs-tab-calculator" data-customs-panel="calculator" role="tabpanel" aria-labelledby="customs-tab-button-calculator" class="rounded-xl border border-border bg-card shadow-card">
                <div class="border-b border-border/70 p-5">
                    <h2 class="flex items-center gap-2 text-lg font-black">
                        <i data-lucide="calculator" class="h-5 w-5 text-accent"></i>
                        الحاسبة الجمركية
                    </h2>
                    <p class="mt-1 text-xs text-muted-foreground">احتساب الرسوم بنفس طريقة رسق - مساعد الاحتساب الجمركي حسب القيمة بالدولار، البند التعريفي، عدد الطرود، والوزن.</p>
                </div>

                <div class="space-y-5 p-5">
                    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <div class="space-y-1.5">
                            <label for="calc-customs-exchange" class="text-xs font-bold">سعر الصرف الجمركي</label>
                            <input id="calc-customs-exchange" type="number" min="0" step="0.001" value="125.25" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        </div>
                        <div class="space-y-1.5">
                            <label for="calc-tax-exchange" class="text-xs font-bold">سعر صرف ST / VAT / PT</label>
                            <input id="calc-tax-exchange" type="number" min="0" step="0.001" value="554.607" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        </div>
                        <div class="space-y-1.5">
                            <label for="calc-os-fee" class="text-xs font-bold">رسوم OS</label>
                            <input id="calc-os-fee" type="number" min="0" step="1" value="498250" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        </div>
                        <div class="space-y-1.5">
                            <label for="calc-entry-port" class="text-xs font-bold">منفذ الدخول</label>
                            <select id="calc-entry-port" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                                @foreach($customsCenters as $center)
                                    <option value="{{ $center }}">{{ $center }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <datalist id="customs-hs-options">
                        <option value="دقيق القمح" data-hs="1101.00" data-rate="5"></option>
                        <option value="سكر" data-hs="1701.99" data-rate="10"></option>
                        <option value="أرز" data-hs="1006.30" data-rate="8"></option>
                        <option value="حليب بودرة" data-hs="0402.21" data-rate="15"></option>
                        <option value="27101939" data-hs="27101939" data-rate="10"></option>
                    </datalist>

                    <div class="overflow-x-auto rounded-lg border border-border">
                        <table class="w-full min-w-[46rem] text-xs">
                            <thead class="bg-secondary/45 text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-2 text-right">م</th>
                                    <th class="px-3 py-2 text-right">الوصف أو البند</th>
                                    <th class="px-3 py-2 text-right">البند التعريفي</th>
                                    <th class="px-3 py-2 text-right">الطرود</th>
                                    <th class="px-3 py-2 text-right">الوزن الكلي (كجم)</th>
                                    <th class="px-3 py-2 text-right">القيمة (دولار)</th>
                                    <th class="px-3 py-2 text-right">CV %</th>
                                    <th class="px-3 py-2 text-right"></th>
                                </tr>
                            </thead>
                            <tbody id="calculator-items-body"></tbody>
                        </table>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" id="add-calculator-row" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">
                            <i data-lucide="plus" class="h-4 w-4 text-accent"></i>
                            إضافة صنف
                        </button>
                        <button type="button" id="run-customs-calculation" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground">
                            <i data-lucide="calculator" class="h-4 w-4"></i>
                            حساب التكاليف
                        </button>
                        <button type="button" id="reset-calculator" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary/40">
                            <i data-lucide="rotate-ccw" class="h-4 w-4 text-muted-foreground"></i>
                            تصفية الكل
                        </button>
                    </div>

                    <div id="calculator-results" class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-border bg-secondary/30 p-4">
                            <div class="text-xs text-muted-foreground">قاعدة CV المحولة</div>
                            <div id="calc-total-value" class="mt-1 text-xl font-black" dir="ltr">0 ر.ي</div>
                        </div>
                        <div class="rounded-lg border border-border bg-secondary/30 p-4">
                            <div class="text-xs text-muted-foreground">إجمالي الرسوم</div>
                            <div id="calc-total-duty" class="mt-1 text-xl font-black text-primary" dir="ltr">0 ر.ي</div>
                        </div>
                        <div class="rounded-lg border border-border bg-secondary/30 p-4">
                            <div class="text-xs text-muted-foreground">الإجمالي الكلي</div>
                            <div id="calc-grand-total" class="mt-1 text-xl font-black text-success" dir="ltr">0 ر.ي</div>
                        </div>
                    </div>

                    <div id="calculator-breakdown" class="hidden overflow-x-auto rounded-lg border border-border">
                        <table class="w-full min-w-[52rem] text-xs">
                            <thead class="bg-secondary/45 text-muted-foreground">
                                <tr>
                                    <th class="px-3 py-2 text-right">الصنف</th>
                                    <th class="px-3 py-2 text-right">البند التعريفي</th>
                                    <th class="px-3 py-2 text-right">بند الرسوم</th>
                                    <th class="px-3 py-2 text-right">قاعدة الاحتساب</th>
                                    <th class="px-3 py-2 text-right">نسبة الترسيم</th>
                                    <th class="px-3 py-2 text-right">مبلغ الرسوم</th>
                                </tr>
                            </thead>
                            <tbody id="calculator-breakdown-body"></tbody>
                        </table>
                    </div>
                </div>
            </section>
            <section id="customs-tab-files" data-customs-panel="files" role="tabpanel" aria-labelledby="customs-tab-button-files" class="hidden rounded-xl border border-border bg-card shadow-card overflow-hidden">
            <div class="border-b border-border/70 p-5">
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="flex items-center gap-2 text-lg font-black">
                            <i data-lucide="folder-kanban" class="h-5 w-5 text-accent"></i>
                            ملفات التخليص
                        </h2>
                        <p class="mt-1 text-xs text-muted-foreground">بحث، فرز، وتحديث حالة الملف والمستندات والرسوم المسجلة.</p>
                    </div>
                    <button type="button" data-customs-tab-trigger="calculator" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border bg-secondary/40 px-3 text-xs font-bold hover:bg-secondary/70">
                        <i data-lucide="calculator" class="h-4 w-4 text-accent"></i>
                        فتح الحاسبة
                    </button>
                </div>

                <form action="{{ route('customs.index') }}" method="GET" class="grid gap-3 md:grid-cols-[1fr_220px_auto]">
                    <input type="hidden" name="customs_tab" value="files">
                    <div class="relative">
                        <i data-lucide="search" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                        <input type="text" name="q" value="{{ $q }}" placeholder="بحث برقم الطلب، العميل، البيان أو الرقم المرجعي..." class="h-10 w-full rounded-lg border border-border bg-background pr-9 pl-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    </div>
                    <select name="status" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="all" @selected($status === 'all')>جميع الحالات</option>
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        <span>تطبيق</span>
                    </button>
                </form>
            </div>

            <div class="grid gap-3 p-4 md:hidden">
                @forelse($clearanceBookings as $booking)
                    @php
                        $documents = $booking->customs_documents ?? [];
                        $statusKey = $booking->customs_status ?: 'pending';
                        $documentProgress = count($documentLabels) > 0 ? min(100, round((count($documents) / count($documentLabels)) * 100)) : 0;
                    @endphp
                    <article data-booking-id="{{ $booking->id }}" class="rounded-xl border border-border bg-card p-4 shadow-card">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="font-mono text-xs font-bold text-muted-foreground">{{ $booking->id }}</div>
                                <h4 class="mt-1 truncate text-sm font-black text-card-foreground">{{ $booking->customer_company }}</h4>
                                <p class="mt-1 text-xs font-semibold text-muted-foreground">{{ $booking->pol }} → {{ $booking->pod }}</p>
                            </div>
                            <span class="shrink-0 rounded-full border px-2.5 py-0.5 text-[11px] font-bold {{ $statusClasses[$statusKey] ?? $statusClasses['pending'] }}">
                                {{ $statusLabels[$statusKey] ?? $statusKey }}
                            </span>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">البيان / المرجع</div>
                                <div class="mt-1 truncate font-black">{{ $booking->customs_declaration_no ?: ($booking->customs_reference ?: $booking->customs_bayan_no ?: '—') }}</div>
                            </div>
                            <div class="rounded-lg bg-secondary/35 p-2">
                                <div class="text-muted-foreground">الرسوم</div>
                                <div class="mt-1 font-black text-primary" dir="ltr">${{ number_format($booking->customs_fees ?? 0, 2) }}</div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-2 rounded-lg bg-secondary/35 p-2">
                            <span class="inline-flex items-center rounded-full bg-card px-2.5 py-1 text-xs font-bold">{{ count($documents) }} / {{ count($documentLabels) }}</span>
                            <span class="h-1.5 flex-1 rounded-full bg-secondary">
                                <span class="block h-full rounded-full bg-accent" style="width: {{ $documentProgress }}%"></span>
                            </span>
                        </div>
                        <button type="button" onclick="openCustomsModal(@js([
                            'id' => $booking->id,
                            'customer' => $booking->customer_company,
                            'route' => $booking->pol . ' → ' . $booking->pod,
                            'customs_status' => $statusKey,
                            'customs_reference' => $booking->customs_reference,
                            'customs_declaration_no' => $booking->customs_declaration_no,
                            'customs_bayan_no' => $booking->customs_bayan_no,
                            'customs_fees' => $booking->customs_fees,
                            'customs_documents' => $documents,
                            'customs_cleared_at' => optional($booking->customs_cleared_at)->toDateString(),
                            'customs_notes' => $booking->customs_notes,
                        ]))" class="mt-3 inline-flex h-9 w-full items-center justify-center gap-2 rounded-lg bg-secondary px-3 text-xs font-bold hover:bg-secondary/70">
                            <i data-lucide="clipboard-edit" class="h-3.5 w-3.5"></i>
                            <span>تحديث الملف</span>
                        </button>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-border bg-card py-8 text-center text-xs text-muted-foreground">لا توجد طلبات تخليص مطابقة.</div>
                @endforelse
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-right">الطلب</th>
                            <th class="px-4 py-3 text-right">العميل</th>
                            <th class="px-4 py-3 text-right">المسار</th>
                            <th class="px-4 py-3 text-right">الحالة</th>
                            <th class="px-4 py-3 text-right">البيان / المرجع</th>
                            <th class="px-4 py-3 text-right">المستندات</th>
                            <th class="px-4 py-3 text-right">الرسوم</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($clearanceBookings as $booking)
                            @php
                                $documents = $booking->customs_documents ?? [];
                                $statusKey = $booking->customs_status ?: 'pending';
                                $documentProgress = count($documentLabels) > 0 ? min(100, round((count($documents) / count($documentLabels)) * 100)) : 0;
                            @endphp
                            <tr data-booking-id="{{ $booking->id }}" class="border-b border-border/40 hover:bg-secondary/20">
                                <td class="px-4 py-3 font-mono text-xs">{{ $booking->id }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-bold">{{ $booking->customer_company }}</div>
                                    <div class="text-[11px] text-muted-foreground">{{ $booking->customer_contact_name }}</div>
                                </td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $booking->pol }} → {{ $booking->pod }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $statusClasses[$statusKey] ?? $statusClasses['pending'] }}">
                                        {{ $statusLabels[$statusKey] ?? $statusKey }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    <div>{{ $booking->customs_declaration_no ?: '—' }}</div>
                                    <div>{{ $booking->customs_reference ?: $booking->customs_bayan_no ?: '' }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex min-w-24 items-center gap-2">
                                        <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-1 text-xs font-bold">{{ count($documents) }} / {{ count($documentLabels) }}</span>
                                        <span class="h-1.5 flex-1 rounded-full bg-secondary">
                                            <span class="block h-full rounded-full bg-accent" style="width: {{ $documentProgress }}%"></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-bold" dir="ltr">${{ number_format($booking->customs_fees ?? 0, 2) }}</td>
                                <td class="px-4 py-3 text-left">
                                    <button type="button" onclick="openCustomsModal(@js([
                                        'id' => $booking->id,
                                        'customer' => $booking->customer_company,
                                        'route' => $booking->pol . ' → ' . $booking->pod,
                                        'customs_status' => $statusKey,
                                        'customs_reference' => $booking->customs_reference,
                                        'customs_declaration_no' => $booking->customs_declaration_no,
                                        'customs_bayan_no' => $booking->customs_bayan_no,
                                        'customs_fees' => $booking->customs_fees,
                                        'customs_documents' => $documents,
                                        'customs_cleared_at' => optional($booking->customs_cleared_at)->toDateString(),
                                        'customs_notes' => $booking->customs_notes,
                                    ]))" class="inline-flex h-8 items-center justify-center gap-2 rounded-lg bg-secondary px-3 text-xs font-bold hover:bg-secondary/70">
                                        <i data-lucide="clipboard-edit" class="h-3.5 w-3.5"></i>
                                        <span>تحديث</span>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-muted-foreground">لا توجد طلبات تخليص مطابقة.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </section>
        </div>
    </section>
</div>

<div id="customs-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-3xl rounded-2xl border border-border bg-card shadow-elegant" onclick="event.stopPropagation()">
        <div class="flex items-start justify-between border-b p-5">
            <div>
                <h3 class="text-lg font-extrabold">تحديث ملف التخليص</h3>
                <p id="customs-modal-subtitle" class="mt-1 text-xs text-muted-foreground"></p>
            </div>
            <button type="button" onclick="closeCustomsModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>

        <form id="customs-form" method="POST" class="space-y-5 p-6">
            @csrf
            @method('PUT')

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-bold">حالة التخليص</label>
                    <select name="customs_status" id="customs-status" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        @foreach($statusLabels as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-bold">الرقم المرجعي</label>
                    <input type="text" name="customs_reference" id="customs-reference" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-bold">رقم البيان الجمركي</label>
                    <input type="text" name="customs_declaration_no" id="customs-declaration-no" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-bold">رقم البيان / المنصة</label>
                    <input type="text" name="customs_bayan_no" id="customs-bayan-no" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-bold">الرسوم الجمركية والتخليص</label>
                    <input type="number" step="0.01" min="0" name="customs_fees" id="customs-fees" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-bold">تاريخ الإفراج</label>
                    <input type="date" name="customs_cleared_at" id="customs-cleared-at" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-bold">المستندات المطلوبة</label>
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($documentLabels as $key => $label)
                        <label class="flex items-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-sm">
                            <input type="checkbox" name="customs_documents[]" value="{{ $key }}" class="customs-doc-check h-4 w-4 rounded border-border accent-accent">
                            <span>{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-bold">ملاحظات التخليص</label>
                <textarea name="customs_notes" id="customs-notes" rows="3" class="w-full rounded-lg border border-border bg-background p-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent"></textarea>
            </div>

            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closeCustomsModal()" class="h-10 rounded-lg border border-border px-5 text-sm font-semibold hover:bg-secondary transition-all">إلغاء</button>
                <button type="submit" class="h-10 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground hover:opacity-95">حفظ التخليص</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const customsModal = document.getElementById('customs-modal');
    const customsForm = document.getElementById('customs-form');

    function openCustomsModal(booking) {
        customsForm.action = `/customs-clearance/${booking.id}`;
        document.getElementById('customs-modal-subtitle').textContent = `${booking.id} · ${booking.customer || ''} · ${booking.route || ''}`;
        document.getElementById('customs-status').value = booking.customs_status || 'pending';
        document.getElementById('customs-reference').value = booking.customs_reference || '';
        document.getElementById('customs-declaration-no').value = booking.customs_declaration_no || '';
        document.getElementById('customs-bayan-no').value = booking.customs_bayan_no || '';
        document.getElementById('customs-fees').value = booking.customs_fees || 0;
        document.getElementById('customs-cleared-at').value = booking.customs_cleared_at || '';
        document.getElementById('customs-notes').value = booking.customs_notes || '';

        const documents = booking.customs_documents || [];
        document.querySelectorAll('.customs-doc-check').forEach(input => {
            input.checked = documents.includes(input.value);
        });

        customsModal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeCustomsModal() {
        customsModal.classList.add('hidden');
    }

    customsModal.addEventListener('click', closeCustomsModal);

    document.addEventListener('DOMContentLoaded', () => {
        const yerFormatter = new Intl.NumberFormat('en-US', {
            maximumFractionDigits: 0
        });
        const declarationMoneyFormatter = new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        const numberFormatter = new Intl.NumberFormat('en-US', {
            maximumFractionDigits: 2
        });
        const customsCustomers = @json($customersForCustoms ?? []);
        const hsSuggestions = [
            { description: 'دقيق القمح', hs: '1101.00', rate: 5 },
            { description: 'سكر', hs: '1701.99', rate: 10 },
            { description: 'أرز', hs: '1006.30', rate: 8 },
            { description: 'حليب بودرة', hs: '0402.21', rate: 15 },
            { description: '27101939', hs: '27101939', rate: 10 }
        ];
        let customsCalculationTimer = null;

        let declarationMode = 'import';
        let declarationRequestMode = 'quick';
        let declarationStep = 1;
        let declarationDraftTimer = null;
        let declarationCustomerSaveTimer = null;
        let declarationCustomerSaving = false;
        let declarationSubmitting = false;
        let isFillingDeclarationCustomer = false;
        let isRestoringDeclarationDraft = false;
        const declarationDraftKey = 'shipmate.customs.declaration.draft.{{ auth()->id() ?? 'guest' }}';
        const declarationDraftFieldIds = [
            'detailed-service-type',
            'detailed-quote-customer',
            'detailed-items-count',
            'detailed-clearance-center',
            'detailed-arrival-date',
            'detailed-quote-note',
            'detailed-customer-id',
            'detailed-customer-name',
            'detailed-customer-company',
            'detailed-customer-email',
            'detailed-customer-phone',
            'detailed-customer-notes',
            'declaration-shipment-no',
            'declaration-customer',
            'declaration-importer-code',
            'declaration-declarant-code',
            'declaration-declarant-name',
            'declaration-customs-office-code',
            'declaration-no',
            'declaration-serial-no',
            'declaration-bayan-no',
            'declaration-country',
            'declaration-destination',
            'declaration-goods-type',
            'declaration-driver',
            'declaration-truck',
            'declaration-file-name'
        ];
        const declarationDraftItemClasses = [
            'declaration-item-name',
            'declaration-brand',
            'declaration-origin',
            'declaration-count',
            'declaration-type',
            'declaration-package',
            'declaration-price-unit',
            'declaration-package-weight',
            'declaration-net-weight',
            'declaration-price',
            'declaration-value',
            'declaration-reference',
            'declaration-hs'
        ];

        function readNumber(input) {
            return Number.parseFloat(input?.value || '0') || 0;
        }

        function formatYer(value) {
            return `${yerFormatter.format(Math.round(Number(value) || 0))} ر.ي`;
        }

        function createCellInput(className, type = 'text', placeholder = '') {
            return `<input type="${type}" placeholder="${placeholder}" class="${className} h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent">`;
        }

        function declarationTimestamp() {
            const date = new Date();
            const pad = value => String(value).padStart(2, '0');

            return [
                date.getFullYear(),
                pad(date.getMonth() + 1),
                pad(date.getDate()),
                '_',
                pad(date.getHours()),
                pad(date.getMinutes()),
                pad(date.getSeconds()),
            ].join('');
        }

        function defaultDeclarationFileName() {
            return `إقرار_جمركي_${declarationTimestamp()}`;
        }

        function refreshIcons() {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        }

        function fieldValue(id, fallback = '-') {
            const element = document.getElementById(id);
            return element?.value?.trim() || fallback;
        }

        function declarationCustomerLabel(customer) {
            const company = customer?.company || '';
            const contact = customer?.contact_name || '';
            return contact && company && contact !== company ? `${company} — ${contact}` : (company || contact || `#${customer?.id || ''}`);
        }

        function findCustomsCustomer(customerId) {
            return customsCustomers.find(customer => String(customer.id) === String(customerId)) || null;
        }

        function setDeclarationCustomerStatus(message, type = 'muted') {
            const status = document.getElementById('detailed-customer-save-status');
            if (!status) return;

            status.textContent = message;
            status.classList.toggle('text-success', type === 'success');
            status.classList.toggle('text-destructive', type === 'error');
            status.classList.toggle('text-muted-foreground', type === 'muted' || type === 'saving');
        }

        function syncDeclarationCustomerDisplayFields() {
            const company = fieldValue('detailed-customer-company', '');
            const contact = fieldValue('detailed-customer-name', '');
            const displayName = company || contact;

            const quoteCustomer = document.getElementById('detailed-quote-customer');
            const declarationCustomer = document.getElementById('declaration-customer');

            if (quoteCustomer && displayName) quoteCustomer.value = displayName;
            if (declarationCustomer && displayName) declarationCustomer.value = displayName;
        }

        function fillDeclarationCustomer(customer) {
            if (!customer) return;

            isFillingDeclarationCustomer = true;
            const values = {
                'detailed-customer-name': customer.contact_name || '',
                'detailed-customer-company': customer.company || '',
                'detailed-customer-email': customer.email || '',
                'detailed-customer-phone': customer.phone || '',
                'detailed-customer-notes': customer.notes || '',
            };

            Object.entries(values).forEach(([id, value]) => {
                const input = document.getElementById(id);
                if (input) input.value = value;
            });

            syncDeclarationCustomerDisplayFields();
            setDeclarationCustomerStatus(`تم اختيار العميل: ${declarationCustomerLabel(customer)}`, 'success');
            isFillingDeclarationCustomer = false;
            scheduleDeclarationDraftSave();
            if (declarationStep === 4) renderDetailedDeclarationReview();
        }

        function upsertCustomsCustomerOption(customer) {
            if (!customer?.id) return;

            const index = customsCustomers.findIndex(item => String(item.id) === String(customer.id));
            if (index >= 0) {
                customsCustomers[index] = { ...customsCustomers[index], ...customer };
            } else {
                customsCustomers.push(customer);
            }

            const select = document.getElementById('detailed-customer-id');
            if (!select) return;

            let option = Array.from(select.options).find(item => String(item.value) === String(customer.id));
            if (!option) {
                option = document.createElement('option');
                option.value = customer.id;
                select.appendChild(option);
            }

            option.textContent = declarationCustomerLabel(customer);
            select.value = String(customer.id);
        }

        function declarationCustomerPayload() {
            const selectedCustomerId = document.getElementById('detailed-customer-id')?.value || '';

            return {
                customer_id: selectedCustomerId || null,
                contact_name: fieldValue('detailed-customer-name', ''),
                company: fieldValue('detailed-customer-company', ''),
                email: fieldValue('detailed-customer-email', ''),
                phone: fieldValue('detailed-customer-phone', ''),
                notes: fieldValue('detailed-customer-notes', ''),
            };
        }

        function canSaveDeclarationCustomer(payload) {
            return Boolean((payload.contact_name || payload.company) && (payload.email || payload.phone || payload.customer_id));
        }

        async function saveDeclarationCustomer(manual = false) {
            if (declarationCustomerSaving) return null;

            const payload = declarationCustomerPayload();
            syncDeclarationCustomerDisplayFields();

            if (!canSaveDeclarationCustomer(payload)) {
                if (manual) {
                    setDeclarationCustomerStatus('أدخل اسم العميل مع البريد أو الهاتف قبل الحفظ.', 'error');
                }
                return null;
            }

            declarationCustomerSaving = true;
            setDeclarationCustomerStatus('جار حفظ بيانات العميل...', 'saving');

            try {
                const response = await fetch('{{ route('customs.declaration.customer') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result.message || 'Unable to save customer');
                }

                upsertCustomsCustomerOption(result.customer);
                syncDeclarationCustomerDisplayFields();
                setDeclarationCustomerStatus(`تم حفظ العميل: ${declarationCustomerLabel(result.customer)}`, 'success');
                scheduleDeclarationDraftSave();
                return result.customer || null;
            } catch (error) {
                console.error(error);
                setDeclarationCustomerStatus('تعذر حفظ العميل. راجع البيانات وحاول مرة أخرى.', 'error');
                return null;
            } finally {
                declarationCustomerSaving = false;
            }
        }

        function scheduleDeclarationCustomerAutoSave() {
            if (isRestoringDeclarationDraft || isFillingDeclarationCustomer) return;

            window.clearTimeout(declarationCustomerSaveTimer);
            declarationCustomerSaveTimer = window.setTimeout(() => saveDeclarationCustomer(false), 700);
        }

        function saveDeclarationDraft() {
            if (isRestoringDeclarationDraft) return;

            const rows = Array.from(document.querySelectorAll('#declaration-items-body tr'));
            const fields = {};
            declarationDraftFieldIds.forEach(id => {
                const element = document.getElementById(id);
                if (element) fields[id] = element.value || '';
            });

            const items = rows.map(row => {
                const item = {};
                declarationDraftItemClasses.forEach(className => {
                    item[className] = row.querySelector(`.${className}`)?.value || '';
                });
                item.value_manual = row.querySelector('.declaration-value')?.dataset.manual === '1';

                return item;
            });

            try {
                localStorage.setItem(declarationDraftKey, JSON.stringify({
                    version: 1,
                    saved_at: new Date().toISOString(),
                    declaration_mode: declarationMode,
                    request_mode: declarationRequestMode,
                    step: declarationStep,
                    fields,
                    items,
                }));
            } catch (error) {
                console.warn('Unable to save customs declaration draft', error);
            }
        }

        function scheduleDeclarationDraftSave() {
            if (isRestoringDeclarationDraft) return;
            window.clearTimeout(declarationDraftTimer);
            declarationDraftTimer = window.setTimeout(saveDeclarationDraft, 150);
        }

        function clearDeclarationDraft() {
            try {
                localStorage.removeItem(declarationDraftKey);
            } catch (error) {
                console.warn('Unable to clear customs declaration draft', error);
            }
        }

        function restoreDeclarationDraft() {
            let draft = null;
            try {
                draft = JSON.parse(localStorage.getItem(declarationDraftKey) || 'null');
            } catch (error) {
                clearDeclarationDraft();
                return false;
            }

            if (!draft || typeof draft !== 'object') {
                return false;
            }

            isRestoringDeclarationDraft = true;

            declarationRequestMode = draft.request_mode === 'detailed' ? 'detailed' : 'quick';
            declarationStep = Math.min(4, Math.max(1, Number(draft.step) || 1));
            setDeclarationMode(draft.declaration_mode);

            Object.entries(draft.fields || {}).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) element.value = value || '';
            });

            const restoredCustomer = findCustomsCustomer(document.getElementById('detailed-customer-id')?.value || '');
            if (restoredCustomer) {
                setDeclarationCustomerStatus(`تم اختيار العميل: ${declarationCustomerLabel(restoredCustomer)}`, 'success');
            }

            const body = document.getElementById('declaration-items-body');
            if (body) {
                body.innerHTML = '';
                const items = Array.isArray(draft.items) ? draft.items : [];
                items.forEach(item => {
                    addDeclarationRow(item);
                    const lastRow = body.querySelector('tr:last-child');
                    const valueInput = lastRow?.querySelector('.declaration-value');
                    if (valueInput) valueInput.dataset.manual = item.value_manual ? '1' : '0';
                });
                if (!items.length) {
                    addDeclarationRow();
                }
            }

            updateDeclarationTotals();
            setDeclarationRequestMode(declarationRequestMode);
            setDeclarationStep(declarationStep);
            renderDetailedDeclarationReview();
            renderQuickDeclarationPreview();

            isRestoringDeclarationDraft = false;

            return true;
        }

        function getQuickDeclarationSummary() {
            return [
                'نوع الطلب: طلب تخليص سريع',
                `الاسم: ${fieldValue('quick-declaration-name')}`,
                `الشركة: ${fieldValue('quick-declaration-company')}`,
                `البريد: ${fieldValue('quick-declaration-email')}`,
                `الجوال: ${fieldValue('quick-declaration-phone')}`,
                `الخدمة: ${fieldValue('quick-declaration-service')}`,
                `نوع الشحنة: ${fieldValue('quick-declaration-shipment-type')}`,
                `من: ${fieldValue('quick-declaration-origin')}`,
                `إلى: ${fieldValue('quick-declaration-destination')}`,
                `التفاصيل: ${fieldValue('quick-declaration-details')}`
            ].join('\n');
        }

        function renderQuickDeclarationPreview() {
            const preview = document.getElementById('quick-declaration-preview');
            if (preview) {
                preview.textContent = getQuickDeclarationSummary();
            }
        }

        function setDeclarationRequestMode(mode) {
            declarationRequestMode = mode === 'detailed' ? 'detailed' : 'quick';
            const title = document.getElementById('declaration-request-title');
            const description = document.getElementById('declaration-request-description');

            if (title) {
                title.textContent = declarationRequestMode === 'detailed' ? 'إقرار جمركي مفصل' : 'إقرار جمركي سريع';
            }

            if (description) {
                description.textContent = declarationRequestMode === 'detailed'
                    ? 'طلب مفصل بخطوات مرتبة: تفاصيل الشحنة، المرفقات، بيانات العميل، ثم المراجعة.'
                    : 'طلب مختصر بنفس حقول الموقع المرجعي لإرسال بيانات التخليص بسرعة.';
            }

            document.querySelectorAll('[data-declaration-request]').forEach(button => {
                const isActive = button.dataset.declarationRequest === declarationRequestMode;
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                button.classList.toggle('bg-card', isActive);
                button.classList.toggle('text-foreground', isActive);
                button.classList.toggle('shadow-sm', isActive);
                button.classList.toggle('text-muted-foreground', !isActive);
            });

            document.querySelectorAll('[data-declaration-request-panel]').forEach(panel => {
                const isActive = panel.dataset.declarationRequestPanel === declarationRequestMode;
                panel.classList.toggle('hidden', !isActive);
                panel.toggleAttribute('hidden', !isActive);
            });

            if (declarationRequestMode === 'detailed') {
                setDeclarationStep(declarationStep);
            } else {
                renderQuickDeclarationPreview();
            }

            refreshIcons();
        }

        function setDeclarationMode(mode) {
            declarationMode = mode === 'export' ? 'export' : 'import';
            document.querySelectorAll('.declaration-mode-btn').forEach(item => {
                const active = item.dataset.declarationMode === declarationMode;
                item.classList.toggle('bg-card', active);
                item.classList.toggle('text-foreground', active);
                item.classList.toggle('shadow-sm', active);
                item.classList.toggle('text-muted-foreground', !active);
            });
        }

        function getDetailedFileNames() {
            const input = document.getElementById('detailed-declaration-files');
            return Array.from(input?.files || []).map(file => file.name);
        }

        function renderDetailedFileList() {
            const fileList = document.getElementById('detailed-file-list');
            const files = getDetailedFileNames();
            if (fileList) {
                fileList.textContent = files.length ? files.join('، ') : 'لم يتم اختيار ملفات بعد.';
            }
        }

        function getDetailedDeclarationSummary() {
            const files = getDetailedFileNames();
            const declarationItems = Array.from(document.querySelectorAll('#declaration-items-body tr')).map((row, index) => {
                return `الصنف ${index + 1}: ${row.querySelector('.declaration-item-name')?.value || '-'} | الماركة: ${row.querySelector('.declaration-brand')?.value || '-'} | HS: ${row.querySelector('.declaration-hs')?.value || '-'} | المنشأ: ${row.querySelector('.declaration-origin')?.value || '-'} | الطرود: ${row.querySelector('.declaration-package')?.value || '0'} | الوزن: ${row.querySelector('.declaration-net-weight')?.value || '0'} | القيمة: ${row.querySelector('.declaration-value')?.value || '0'}`;
            });

            return [
                'نوع الطلب: طلب تخليص مفصل',
                `نوع الإقرار: ${declarationMode === 'import' ? 'استيراد' : 'تصدير'}`,
                `اسم ملف XML: ${fieldValue('declaration-file-name')}`,
                `نوع الخدمة: ${fieldValue('detailed-service-type')}`,
                `اسم العميل: ${fieldValue('detailed-quote-customer')}`,
                `رقم الإرسالية: ${fieldValue('declaration-shipment-no')}`,
                `المستورد / المصدر: ${fieldValue('declaration-customer')}`,
                `رمز المستورد: ${fieldValue('declaration-importer-code')}`,
                `رمز المصرح: ${fieldValue('declaration-declarant-code')}`,
                `اسم المصرح: ${fieldValue('declaration-declarant-name')}`,
                `كود المنفذ: ${fieldValue('declaration-customs-office-code')}`,
                `رقم الإقرار: ${fieldValue('declaration-no')}`,
                `رقم المسلسل: ${fieldValue('declaration-serial-no')}`,
                `رقم البيان: ${fieldValue('declaration-bayan-no')}`,
                `الدولة: ${fieldValue('declaration-country')}`,
                `جهة الوصول: ${fieldValue('declaration-destination')}`,
                `نوع الصنف: ${fieldValue('declaration-goods-type')}`,
                `السائق: ${fieldValue('declaration-driver')}`,
                `القاطرة: ${fieldValue('declaration-truck')}`,
                `عدد الأصناف: ${document.getElementById('declaration-total-items')?.textContent || '0'}`,
                `إجمالي عدد الطرود: ${document.getElementById('declaration-total-packages')?.textContent || '0'}`,
                `مركز التخليص: ${fieldValue('detailed-clearance-center')}`,
                `تاريخ وصول الشحنة إلى الجمارك: ${fieldValue('detailed-arrival-date')}`,
                `ملاحظة: ${fieldValue('detailed-quote-note')}`,
                '',
                'تفاصيل الأصناف:',
                ...(declarationItems.length ? declarationItems : ['لا توجد أصناف مدخلة.']),
                '',
                `المرفقات: ${files.length ? files.join('، ') : '-'}`,
                '',
                'بيانات العميل:',
                `الاسم: ${fieldValue('detailed-customer-name')}`,
                `الشركة: ${fieldValue('detailed-customer-company')}`,
                `البريد: ${fieldValue('detailed-customer-email')}`,
                `الجوال: ${fieldValue('detailed-customer-phone')}`,
                `ملاحظات: ${fieldValue('detailed-customer-notes')}`
            ].join('\n');
        }

        function buildDeclarationXmlPayload() {
            return {
                declaration_type: declarationMode,
                file_name: fieldValue('declaration-file-name', defaultDeclarationFileName()),
                serial_no: fieldValue('declaration-serial-no', ''),
                shipment_no: fieldValue('declaration-shipment-no', ''),
                importer_code: fieldValue('declaration-importer-code', ''),
                customer: fieldValue('declaration-customer', fieldValue('detailed-quote-customer', '')),
                declarant_code: fieldValue('declaration-declarant-code', ''),
                declarant_name: fieldValue('declaration-declarant-name', ''),
                customs_office_code: fieldValue('declaration-customs-office-code', '0309'),
                declaration_no: fieldValue('declaration-no', ''),
                bayan_no: fieldValue('declaration-bayan-no', ''),
                customs_center: fieldValue('detailed-clearance-center', ''),
                country: fieldValue('declaration-country', 'اليمن'),
                destination: fieldValue('declaration-destination', ''),
                goods_type: fieldValue('declaration-goods-type', ''),
                driver: fieldValue('declaration-driver', ''),
                truck: fieldValue('declaration-truck', ''),
                arrival_date: fieldValue('detailed-arrival-date', ''),
                note: fieldValue('detailed-quote-note', ''),
                items: Array.from(document.querySelectorAll('#declaration-items-body tr')).map(row => ({
                    name: row.querySelector('.declaration-item-name')?.value || '',
                    brand: row.querySelector('.declaration-brand')?.value || '',
                    origin: row.querySelector('.declaration-origin')?.value || '',
                    count: readNumber(row.querySelector('.declaration-count')),
                    type: row.querySelector('.declaration-type')?.value || '',
                    packages: readNumber(row.querySelector('.declaration-package')),
                    price_unit: row.querySelector('.declaration-price-unit')?.value || '',
                    package_weight_kg: readNumber(row.querySelector('.declaration-package-weight')),
                    net_weight_kg: readNumber(row.querySelector('.declaration-net-weight')),
                    value_usd: readNumber(row.querySelector('.declaration-value')),
                    price: readNumber(row.querySelector('.declaration-price')),
                    reference: row.querySelector('.declaration-reference')?.value || '',
                    hs: row.querySelector('.declaration-hs')?.value || '',
                })),
            };
        }

        function appendDeclarationValue(formData, key, value) {
            formData.append(key, value ?? '');
        }

        function buildDeclarationStoreFormData() {
            const payload = buildDeclarationXmlPayload();
            const customerPayload = declarationCustomerPayload();
            const formData = new FormData();

            Object.entries(payload).forEach(([key, value]) => {
                if (key === 'items') {
                    value.forEach((item, index) => {
                        Object.entries(item).forEach(([itemKey, itemValue]) => {
                            appendDeclarationValue(formData, `items[${index}][${itemKey}]`, itemValue);
                        });
                    });

                    return;
                }

                appendDeclarationValue(formData, key, value);
            });

            appendDeclarationValue(formData, 'customer_id', customerPayload.customer_id || '');
            appendDeclarationValue(formData, 'customer_company', customerPayload.company || payload.customer || '');
            appendDeclarationValue(formData, 'customer_contact_name', customerPayload.contact_name || payload.customer || '');
            appendDeclarationValue(formData, 'customer_phone', customerPayload.phone || '');
            appendDeclarationValue(formData, 'customer_email', customerPayload.email || '');
            appendDeclarationValue(formData, 'customer_notes', customerPayload.notes || '');

            Array.from(document.getElementById('detailed-declaration-files')?.files || []).forEach(file => {
                formData.append('attachments[]', file);
            });

            return { formData, payload };
        }

        async function submitDetailedDeclaration() {
            if (declarationSubmitting) return;

            renderDetailedDeclarationReview();
            const { formData, payload } = buildDeclarationStoreFormData();

            if (!payload.items.length) {
                window.showGlobalToast?.('أضف صنفاً واحداً على الأقل قبل تأكيد الطلب.', 'error');
                return;
            }

            const nextButton = document.getElementById('declaration-next-step');
            declarationSubmitting = true;
            if (nextButton) {
                nextButton.disabled = true;
                nextButton.classList.add('opacity-70', 'cursor-wait');
            }

            if (canSaveDeclarationCustomer(declarationCustomerPayload())) {
                await saveDeclarationCustomer(false);
            }

            try {
                const response = await fetch('{{ route('customs.declaration.store') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: formData,
                });

                const result = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(result.message || 'Unable to submit declaration');
                }

                clearDeclarationDraft();
                window.showGlobalToast?.('تم حفظ ملف التخليص وربطه بالنظام.', 'success');
                window.location.assign(result.redirect_url || '{{ route('customs.index', ['customs_tab' => 'files']) }}');
            } catch (error) {
                console.error(error);
                window.showGlobalToast?.('تعذر حفظ ملف التخليص. راجع البيانات وحاول مرة أخرى.', 'error');
                declarationSubmitting = false;
                if (nextButton) {
                    nextButton.disabled = false;
                    nextButton.classList.remove('opacity-70', 'cursor-wait');
                }
            }
        }

        async function downloadDeclarationExcel() {
            renderDetailedDeclarationReview();
            const payload = buildDeclarationXmlPayload();

            if (!payload.items.length) {
                window.showGlobalToast?.('أضف صنفاً واحداً على الأقل قبل تحميل Excel.', 'error');
                return;
            }

            try {
                const response = await fetch('{{ route('customs.declaration.excel') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/vnd.ms-excel',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    throw new Error('Declaration Excel export failed');
                }

                const blob = await response.blob();
                const url = URL.createObjectURL(blob);
                const anchor = document.createElement('a');
                anchor.href = url;
                const baseName = payload.file_name.replace(/\.(xml|xls|xlsx)$/i, '');
                anchor.download = `${baseName}.xls`;
                document.body.appendChild(anchor);
                anchor.click();
                anchor.remove();
                URL.revokeObjectURL(url);
                window.showGlobalToast?.('تم تجهيز ملف Excel للإقرار الجمركي.', 'success');
            } catch (error) {
                console.error(error);
                window.showGlobalToast?.('تعذر تجهيز ملف Excel. راجع البيانات وحاول مرة أخرى.', 'error');
            }
        }

        function renderDetailedDeclarationReview() {
            const review = document.getElementById('detailed-declaration-review');
            if (review) {
                review.textContent = getDetailedDeclarationSummary();
            }
        }

        function setDeclarationStep(step) {
            declarationStep = Math.min(4, Math.max(1, Number(step) || 1));

            document.querySelectorAll('.declaration-step-panel').forEach(panel => {
                const isActive = Number(panel.dataset.declarationStepPanel) === declarationStep;
                panel.classList.toggle('hidden', !isActive);
                panel.toggleAttribute('hidden', !isActive);
            });

            document.querySelectorAll('.declaration-step-indicator').forEach(indicator => {
                const targetStep = Number(indicator.dataset.declarationStepTarget);
                const isActive = targetStep === declarationStep;
                const isDone = targetStep < declarationStep;
                const circle = indicator.querySelector('.declaration-step-circle');
                const label = indicator.querySelector('.declaration-step-label');

                indicator.setAttribute('aria-current', isActive ? 'step' : 'false');
                circle?.classList.toggle('bg-gradient-accent', isActive || isDone);
                circle?.classList.toggle('text-accent-foreground', isActive || isDone);
                circle?.classList.toggle('border-accent/60', isActive || isDone);
                circle?.classList.toggle('shadow-glow', isActive);
                circle?.classList.toggle('bg-background', !isActive && !isDone);
                circle?.classList.toggle('text-muted-foreground', !isActive && !isDone);
                circle?.classList.toggle('border-border', !isActive && !isDone);
                label?.classList.toggle('text-foreground', isActive || isDone);
                label?.classList.toggle('text-muted-foreground', !isActive && !isDone);
            });

            const progressLine = document.getElementById('declaration-progress-line');
            if (progressLine) {
                progressLine.style.width = `${((declarationStep - 1) / 3) * 100}%`;
            }

            const prevButton = document.getElementById('declaration-prev-step');
            if (prevButton) {
                prevButton.disabled = declarationStep === 1;
                prevButton.classList.toggle('opacity-50', declarationStep === 1);
                prevButton.classList.toggle('cursor-not-allowed', declarationStep === 1);
            }

            const nextButton = document.getElementById('declaration-next-step');
            if (nextButton) {
                nextButton.innerHTML = declarationStep === 4
                    ? '<span>تأكيد وإرسال الطلب</span><i data-lucide="check" class="h-4 w-4"></i>'
                    : `<span>${declarationStep === 3 ? 'مراجعة الطلب' : 'التالي'}</span><i data-lucide="arrow-left" class="h-4 w-4"></i>`;
            }

            document.getElementById('copy-declaration-summary')?.classList.toggle('hidden', declarationStep !== 4);

            if (declarationStep === 4) {
                renderDetailedDeclarationReview();
            }

            refreshIcons();
        }

        const customsTabButtons = Array.from(document.querySelectorAll('.customs-tab-btn'));
        const customsPanels = Array.from(document.querySelectorAll('[data-customs-panel]'));
        const activeTabClasses = ['border-accent/60', 'bg-gradient-accent', 'text-accent-foreground', 'shadow-glow'];
        const inactiveTabClasses = ['border-transparent', 'bg-secondary/35', 'text-foreground', 'hover:bg-secondary/60'];

        function toggleClasses(element, classes, force) {
            classes.forEach(className => element.classList.toggle(className, force));
        }

        function activateCustomsTab(tabName) {
            const selectedTab = customsPanels.some(panel => panel.dataset.customsPanel === tabName) ? tabName : 'calculator';

            customsTabButtons.forEach(button => {
                const isActive = button.dataset.customsTab === selectedTab;
                button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                button.tabIndex = isActive ? 0 : -1;
                toggleClasses(button, activeTabClasses, isActive);
                toggleClasses(button, inactiveTabClasses, !isActive);
            });

            customsPanels.forEach(panel => {
                const isActive = panel.dataset.customsPanel === selectedTab;
                panel.classList.toggle('hidden', !isActive);
                panel.toggleAttribute('hidden', !isActive);
            });

            refreshIcons();
        }

        customsTabButtons.forEach((button, index) => {
            button.addEventListener('click', () => activateCustomsTab(button.dataset.customsTab));
            button.addEventListener('keydown', event => {
                if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;

                event.preventDefault();
                const lastIndex = customsTabButtons.length - 1;
                let nextIndex = index;

                if (event.key === 'Home') nextIndex = 0;
                if (event.key === 'End') nextIndex = lastIndex;
                if (event.key === 'ArrowLeft') nextIndex = index === lastIndex ? 0 : index + 1;
                if (event.key === 'ArrowRight') nextIndex = index === 0 ? lastIndex : index - 1;

                customsTabButtons[nextIndex]?.focus();
                activateCustomsTab(customsTabButtons[nextIndex]?.dataset.customsTab);
            });
        });

        document.querySelectorAll('[data-customs-tab-trigger]').forEach(trigger => {
            trigger.addEventListener('click', () => {
                activateCustomsTab(trigger.dataset.customsTabTrigger);
                document.querySelector('[role="tablist"]')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        });

        const tabFromHash = {
            '#customs-tab-calculator': 'calculator',
            '#customs-tab-declaration': 'declaration',
            '#customs-tab-files': 'files'
        }[window.location.hash];
        const tabFromQuery = new URLSearchParams(window.location.search).get('customs_tab');
        activateCustomsTab(tabFromHash || tabFromQuery || 'calculator');

        function addDeclarationRow(data = {}) {
            const body = document.getElementById('declaration-items-body');
            if (!body) return;

            const row = document.createElement('tr');
            row.className = 'border-t border-border/50';
            row.innerHTML = `
                <td class="px-2 py-2">${createCellInput('declaration-item-name', 'text', 'اسم الصنف')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-brand', 'text', 'الماركة')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-origin', 'text', 'المنشأ')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-count', 'number')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-type', 'text')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-package', 'number')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-price-unit', 'text')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-package-weight', 'number')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-net-weight', 'number')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-price', 'number')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-value', 'number')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-reference', 'text')}</td>
                <td class="px-2 py-2">${createCellInput('declaration-hs', 'text', 'HS')}</td>
                <td class="px-2 py-2 text-left">
                    <button type="button" class="remove-declaration-row inline-grid h-8 w-8 place-items-center rounded-md text-destructive hover:bg-destructive/10" aria-label="حذف الصف">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                </td>
            `;

            Object.entries(data).forEach(([key, value]) => {
                const input = row.querySelector(`.${key}`);
                if (input) input.value = value;
            });
            const valueInput = row.querySelector('.declaration-value');
            if (valueInput && Object.prototype.hasOwnProperty.call(data, 'value_manual')) {
                valueInput.dataset.manual = data.value_manual ? '1' : '0';
            }

            body.appendChild(row);
            syncDeclarationRowValue(row);
            updateDeclarationTotals();
            refreshIcons();
        }

        function updateDeclarationTotals() {
            const rows = Array.from(document.querySelectorAll('#declaration-items-body tr'));
            const totals = rows.reduce((sum, row) => {
                sum.items += 1;
                sum.packages += readNumber(row.querySelector('.declaration-package'));
                sum.weight += readNumber(row.querySelector('.declaration-net-weight'));
                sum.value += readNumber(row.querySelector('.declaration-value'));
                return sum;
            }, { items: 0, packages: 0, weight: 0, value: 0 });

            const totalItems = document.getElementById('declaration-total-items');
            const totalPackages = document.getElementById('declaration-total-packages');
            const totalWeight = document.getElementById('declaration-total-weight');
            const totalValue = document.getElementById('declaration-total-value');
            if (totalItems) totalItems.textContent = numberFormatter.format(totals.items);
            if (totalPackages) totalPackages.textContent = numberFormatter.format(totals.packages);
            if (totalWeight) totalWeight.textContent = `${numberFormatter.format(totals.weight)} كجم`;
            if (totalValue) totalValue.textContent = declarationMoneyFormatter.format(totals.value);
        }

        function syncDeclarationRowValue(row) {
            const count = readNumber(row?.querySelector('.declaration-count'));
            const price = readNumber(row?.querySelector('.declaration-price'));
            const valueInput = row?.querySelector('.declaration-value');

            if (!valueInput || valueInput.dataset.manual === '1' || !count || !price) {
                return;
            }

            valueInput.value = (count * price).toFixed(2);
            valueInput.dataset.auto = '1';
        }

        function resetDeclaration() {
            clearDeclarationDraft();
            window.clearTimeout(declarationCustomerSaveTimer);
            [
                'declaration-shipment-no',
                'declaration-customer',
                'declaration-importer-code',
                'declaration-declarant-code',
                'declaration-declarant-name',
                'declaration-no',
                'declaration-serial-no',
                'declaration-bayan-no',
                'declaration-destination',
                'declaration-goods-type',
                'declaration-driver',
                'declaration-truck',
                'detailed-quote-customer',
                'detailed-items-count',
                'detailed-clearance-center',
                'detailed-arrival-date',
                'detailed-quote-note',
                'detailed-customer-id',
                'detailed-customer-name',
                'detailed-customer-company',
                'detailed-customer-email',
                'detailed-customer-phone',
                'detailed-customer-notes'
            ].forEach(id => {
                const input = document.getElementById(id);
                if (input) input.value = '';
            });
            const serviceType = document.getElementById('detailed-service-type');
            if (serviceType) serviceType.value = 'التخليص الجمركي';
            const customsOfficeCode = document.getElementById('declaration-customs-office-code');
            if (customsOfficeCode) customsOfficeCode.value = '0309';
            const country = document.getElementById('declaration-country');
            if (country) country.value = 'اليمن';
            const fileName = document.getElementById('declaration-file-name');
            if (fileName) fileName.value = defaultDeclarationFileName();
            const fileInput = document.getElementById('detailed-declaration-files');
            if (fileInput) fileInput.value = '';
            setDeclarationCustomerStatus('سيتم حفظ العميل تلقائيًا عند إدخال الاسم مع البريد أو الهاتف.', 'muted');
            renderDetailedFileList();
            const body = document.getElementById('declaration-items-body');
            if (body) body.innerHTML = '';
            addDeclarationRow();
            setDeclarationStep(1);
        }

        function getDeclarationSummary() {
            const lines = [
                `نوع الإقرار: ${declarationMode === 'import' ? 'استيراد' : 'تصدير'}`,
                `رقم الإرسالية: ${document.getElementById('declaration-shipment-no')?.value || '-'}`,
                `العميل: ${document.getElementById('declaration-customer')?.value || '-'}`,
                `رمز المستورد: ${document.getElementById('declaration-importer-code')?.value || '-'}`,
                `رمز المصرح: ${document.getElementById('declaration-declarant-code')?.value || '-'}`,
                `اسم المصرح: ${document.getElementById('declaration-declarant-name')?.value || '-'}`,
                `كود المنفذ: ${document.getElementById('declaration-customs-office-code')?.value || '0309'}`,
                `رقم الإقرار: ${document.getElementById('declaration-no')?.value || '-'}`,
                `رقم المسلسل: ${document.getElementById('declaration-serial-no')?.value || '-'}`,
                `رقم البيان: ${document.getElementById('declaration-bayan-no')?.value || '-'}`,
                `الدولة: ${document.getElementById('declaration-country')?.value || 'اليمن'}`,
                `جهة الوصول: ${document.getElementById('declaration-destination')?.value || '-'}`,
                `نوع الصنف: ${document.getElementById('declaration-goods-type')?.value || '-'}`,
                `السائق: ${document.getElementById('declaration-driver')?.value || '-'}`,
                `القاطرة: ${document.getElementById('declaration-truck')?.value || '-'}`,
                `عدد الأصناف: ${document.getElementById('declaration-total-items')?.textContent || '0'}`,
                `إجمالي عدد الطرود: ${document.getElementById('declaration-total-packages')?.textContent || '0'}`,
                `الوزن الصافي: ${document.getElementById('declaration-total-weight')?.textContent || '0'}`,
                `القيمة: ${document.getElementById('declaration-total-value')?.textContent || '0'}`
            ];

            document.querySelectorAll('#declaration-items-body tr').forEach((row, index) => {
                const name = row.querySelector('.declaration-item-name')?.value || '-';
                const brand = row.querySelector('.declaration-brand')?.value || '-';
                const hs = row.querySelector('.declaration-hs')?.value || '-';
                const value = row.querySelector('.declaration-value')?.value || '0';
                const priceUnit = row.querySelector('.declaration-price-unit')?.value || '-';
                const price = row.querySelector('.declaration-price')?.value || '0';
                const reference = row.querySelector('.declaration-reference')?.value || '-';
                lines.push(`الصنف ${index + 1}: ${name} | الماركة: ${brand} | HS: ${hs} | سعر الوحدة: ${price} | القيمة: ${value} | وحدة سعرية: ${priceUnit} | المرجع: ${reference}`);
            });

            return lines.join('\n');
        }

        function addCalculatorRow(data = {}) {
            const body = document.getElementById('calculator-items-body');
            if (!body) return;

            const row = document.createElement('tr');
            row.className = 'border-t border-border/50';
            row.innerHTML = `
                <td class="calc-row-index px-2 py-2 text-center font-mono text-muted-foreground"></td>
                <td class="px-2 py-2"><input list="customs-hs-options" class="calc-description h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" placeholder="مثال: دقيق، سكر"></td>
                <td class="px-2 py-2"><input class="calc-hs h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" placeholder="27101939"></td>
                <td class="px-2 py-2"><input type="number" min="0" class="calc-packages h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" value="0"></td>
                <td class="px-2 py-2"><input type="number" min="0" class="calc-weight h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" value="0"></td>
                <td class="px-2 py-2"><input type="number" min="0" class="calc-value h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" value="0"></td>
                <td class="px-2 py-2"><input type="number" min="0" class="calc-rate h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent" value="10"></td>
                <td class="px-2 py-2 text-left">
                    <button type="button" class="remove-calculator-row inline-grid h-8 w-8 place-items-center rounded-md text-destructive hover:bg-destructive/10" aria-label="حذف الصنف">
                        <i data-lucide="trash-2" class="h-4 w-4"></i>
                    </button>
                </td>
            `;

            Object.entries(data).forEach(([key, value]) => {
                const input = row.querySelector(`.calc-${key}`);
                if (input) input.value = value;
            });

            body.appendChild(row);
            refreshCalculatorRowIndexes();
            refreshIcons();
        }

        function refreshCalculatorRowIndexes() {
            document.querySelectorAll('#calculator-items-body tr').forEach((row, index) => {
                const cell = row.querySelector('.calc-row-index');
                if (cell) cell.textContent = index + 1;
            });
        }

        function buildCustomsCalculationPayload() {
            const rows = Array.from(document.querySelectorAll('#calculator-items-body tr'));

            return {
                customs_exchange_rate: readNumber(document.getElementById('calc-customs-exchange')) || 125.25,
                tax_exchange_rate: readNumber(document.getElementById('calc-tax-exchange')) || 554.607,
                other_services_fee: readNumber(document.getElementById('calc-os-fee')),
                items: rows.map(row => {
                    const descriptionInput = row.querySelector('.calc-description');

                    return {
                        description: descriptionInput?.value || '-',
                        hs: row.querySelector('.calc-hs')?.value || '-',
                        packages: readNumber(row.querySelector('.calc-packages')),
                        weight_kg: readNumber(row.querySelector('.calc-weight')),
                        value_usd: readNumber(row.querySelector('.calc-value')),
                        cv_rate: readNumber(row.querySelector('.calc-rate')),
                    };
                }),
            };
        }

        async function calculateCustoms() {
            const payload = buildCustomsCalculationPayload();

            try {
                const response = await fetch('{{ route('customs.calculate') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(payload),
                });

                if (!response.ok) {
                    throw new Error('Customs calculation failed');
                }

                renderCustomsCalculation(await response.json());
            } catch (error) {
                console.error(error);
            }
        }

        function renderCustomsCalculation(result) {
            const totals = result?.totals || {};
            document.getElementById('calc-total-value').textContent = formatYer(totals.customs_base || 0);
            document.getElementById('calc-total-duty').textContent = formatYer(totals.duties || 0);
            document.getElementById('calc-grand-total').textContent = formatYer(totals.grand_total || 0);

            const breakdown = document.getElementById('calculator-breakdown');
            const body = document.getElementById('calculator-breakdown-body');
            const details = result?.breakdown || [];
            if (body) {
                body.innerHTML = details.map(item => `
                    <tr class="border-t border-border/50">
                        <td class="px-3 py-2 font-mono">${item.item_number || '—'}</td>
                        <td class="px-3 py-2 font-mono">${item.hs || '-'}</td>
                        <td class="px-3 py-2 font-black">${item.code}</td>
                        <td class="px-3 py-2" dir="ltr">${item.base === null ? '—' : yerFormatter.format(item.base)}</td>
                        <td class="px-3 py-2">${item.rate === null ? '—' : `${numberFormatter.format(item.rate)}%`}</td>
                        <td class="px-3 py-2 font-bold" dir="ltr">${formatYer(item.amount)}</td>
                    </tr>
                `).join('');
            }
            breakdown?.classList.toggle('hidden', details.length === 0);
        }

        function resetCalculator() {
            document.getElementById('calc-customs-exchange').value = '125.25';
            document.getElementById('calc-tax-exchange').value = '554.607';
            document.getElementById('calc-os-fee').value = '498250';
            const body = document.getElementById('calculator-items-body');
            if (body) body.innerHTML = '';
            addCalculatorRow();
            calculateCustoms();
        }

        document.querySelectorAll('[data-declaration-request]').forEach(button => {
            button.addEventListener('click', () => {
                setDeclarationRequestMode(button.dataset.declarationRequest);
                scheduleDeclarationDraftSave();
            });
        });

        document.querySelectorAll('.quick-declaration-field').forEach(field => {
            field.addEventListener('input', renderQuickDeclarationPreview);
            field.addEventListener('change', renderQuickDeclarationPreview);
        });

        document.getElementById('prepare-quick-declaration')?.addEventListener('click', () => {
            renderQuickDeclarationPreview();
            window.showGlobalToast?.('تم إرسال الطلب السريع', 'success');
        });

        document.getElementById('detailed-quote-customer')?.addEventListener('input', event => {
            const customer = document.getElementById('declaration-customer');
            if (customer && !customer.value.trim()) {
                customer.value = event.target.value;
            }
        });

        document.getElementById('detailed-customer-id')?.addEventListener('change', event => {
            const customer = findCustomsCustomer(event.target.value);
            if (customer) {
                fillDeclarationCustomer(customer);
            } else {
                setDeclarationCustomerStatus('سيتم حفظ العميل تلقائيًا عند إدخال الاسم مع البريد أو الهاتف.', 'muted');
                scheduleDeclarationDraftSave();
            }
        });

        ['detailed-customer-name', 'detailed-customer-company', 'detailed-customer-email', 'detailed-customer-phone', 'detailed-customer-notes'].forEach(id => {
            const input = document.getElementById(id);
            input?.addEventListener('input', () => {
                syncDeclarationCustomerDisplayFields();
                scheduleDeclarationCustomerAutoSave();
            });
            input?.addEventListener('change', () => {
                syncDeclarationCustomerDisplayFields();
                scheduleDeclarationCustomerAutoSave();
            });
        });

        document.getElementById('save-detailed-customer')?.addEventListener('click', () => saveDeclarationCustomer(true));

        document.getElementById('declaration-prev-step')?.addEventListener('click', () => {
            if (declarationStep > 1) {
                setDeclarationStep(declarationStep - 1);
                scheduleDeclarationDraftSave();
            }
        });

        document.getElementById('declaration-next-step')?.addEventListener('click', () => {
            if (declarationStep < 4) {
                setDeclarationStep(declarationStep + 1);
                scheduleDeclarationDraftSave();
                return;
            }

            submitDetailedDeclaration();
            return;
            window.showGlobalToast?.('تم إرسال طلب الإقرار المفصل', 'success');
        });

        document.getElementById('download-declaration-excel')?.addEventListener('click', downloadDeclarationExcel);
        document.getElementById('copy-declaration-summary')?.addEventListener('click', async () => {
            renderDetailedDeclarationReview();
            const summary = getDetailedDeclarationSummary();
            try {
                await navigator.clipboard.writeText(summary);
                window.showGlobalToast?.('تم نسخ ملخص الإقرار.', 'success');
            } catch (error) {
                window.showGlobalToast?.('تعذر نسخ الملخص تلقائياً.', 'error');
            }
        });

        document.getElementById('detailed-declaration-files')?.addEventListener('change', () => {
            renderDetailedFileList();
            if (declarationStep === 4) renderDetailedDeclarationReview();
        });

        [
            ...declarationDraftFieldIds
        ].forEach(id => {
            const element = document.getElementById(id);
            const refreshReview = () => {
                scheduleDeclarationDraftSave();
                if (declarationStep === 4) renderDetailedDeclarationReview();
            };
            element?.addEventListener('input', refreshReview);
            element?.addEventListener('change', refreshReview);
        });

        document.querySelectorAll('.declaration-mode-btn').forEach(button => {
            button.addEventListener('click', () => {
                setDeclarationMode(button.dataset.declarationMode);
                scheduleDeclarationDraftSave();
            });
        });

        document.getElementById('add-declaration-row')?.addEventListener('click', () => {
            addDeclarationRow();
            scheduleDeclarationDraftSave();
        });
        document.getElementById('reset-declaration')?.addEventListener('click', resetDeclaration);
        document.getElementById('declaration-items-body')?.addEventListener('input', event => {
            const row = event.target.closest('tr');
            if (event.target.classList.contains('declaration-value')) {
                event.target.dataset.manual = event.target.value.trim() === '' ? '0' : '1';
            }
            if (event.target.classList.contains('declaration-count') || event.target.classList.contains('declaration-price')) {
                syncDeclarationRowValue(row);
            }
            updateDeclarationTotals();
            scheduleDeclarationDraftSave();
            if (declarationStep === 4) renderDetailedDeclarationReview();
        });
        document.getElementById('declaration-items-body')?.addEventListener('click', event => {
            const button = event.target.closest('.remove-declaration-row');
            if (!button) return;
            button.closest('tr')?.remove();
            if (document.querySelectorAll('#declaration-items-body tr').length === 0) {
                        addDeclarationRow();
                    }
                    updateDeclarationTotals();
                    scheduleDeclarationDraftSave();
                    if (declarationStep === 4) renderDetailedDeclarationReview();
                });

        document.getElementById('add-calculator-row')?.addEventListener('click', () => addCalculatorRow());
        document.getElementById('run-customs-calculation')?.addEventListener('click', calculateCustoms);
        document.getElementById('reset-calculator')?.addEventListener('click', resetCalculator);
        document.getElementById('calculator-items-body')?.addEventListener('input', event => {
            if (event.target.classList.contains('calc-description')) {
                const suggestion = hsSuggestions.find(item => item.description === event.target.value);
                if (suggestion) {
                    const row = event.target.closest('tr');
                    row.querySelector('.calc-hs').value = suggestion.hs;
                    row.querySelector('.calc-rate').value = suggestion.rate;
                }
            }
            window.clearTimeout(customsCalculationTimer);
            customsCalculationTimer = window.setTimeout(calculateCustoms, 250);
        });
        document.getElementById('calculator-items-body')?.addEventListener('click', event => {
            const button = event.target.closest('.remove-calculator-row');
            if (!button) return;
            button.closest('tr')?.remove();
            if (document.querySelectorAll('#calculator-items-body tr').length === 0) {
                addCalculatorRow();
            }
            refreshCalculatorRowIndexes();
            calculateCustoms();
        });
        ['calc-customs-exchange', 'calc-tax-exchange', 'calc-os-fee'].forEach(id => {
            document.getElementById(id)?.addEventListener('input', calculateCustoms);
            document.getElementById(id)?.addEventListener('change', calculateCustoms);
        });

        renderDetailedFileList();
        renderQuickDeclarationPreview();
        const initialDeclarationFileName = document.getElementById('declaration-file-name');
        const restoredDeclarationDraft = restoreDeclarationDraft();

        if (!restoredDeclarationDraft && initialDeclarationFileName && !initialDeclarationFileName.value.trim()) {
            initialDeclarationFileName.value = defaultDeclarationFileName();
        }

        if (!restoredDeclarationDraft) {
            setDeclarationRequestMode('quick');
            setDeclarationStep(1);
            addDeclarationRow();
            saveDeclarationDraft();
        }
        addCalculatorRow({
            description: '27101939',
            hs: '27101939',
            rate: 10,
            packages: 7000,
            weight: 105000,
            value: 208000
        });
        calculateCustoms();
    });
</script>
@endsection

@extends('layouts.app')

@section('title', __('messages.site_name') . ' - ' . __('messages.home_title'))

@section('head')
<meta name="description" content="{{ __('messages.home_meta_description') }}">
<meta property="og:title" content="{{ __('messages.site_name') }} - {{ __('messages.home_og_title') }}">
<meta property="og:description" content="{{ __('messages.home_og_description') }}">
@endsection

@section('content')
@php
    $isArabic = app()->getLocale() === 'ar';
    $copy = fn (string $ar, string $en): string => $isArabic ? $ar : $en;
    $customerPortalUrl = auth('customer')->check() ? route('customer.dashboard') : route('customer.login');
    $customerStartUrl = auth('customer')->check() ? route('customer.shipments.create') : route('customer.login');
    $customerRegisterUrl = auth('customer')->check() ? route('customer.dashboard') : route('customer.register');
    $statValues = collect($trustStats ?? [])->pluck('value')->values();

    $heroStats = [
        ['value' => $statValues->get(0, '+18M'), 'label' => $copy('طلب شحن مدار', 'managed shipment requests'), 'icon' => 'clipboard-check'],
        ['value' => $statValues->get(1, '420'), 'label' => $copy('عميل وشركة', 'customers and companies'), 'icon' => 'building-2'],
        ['value' => $statValues->get(2, '90+'), 'label' => $copy('ميناء ومسار', 'ports and lanes'), 'icon' => 'anchor'],
        ['value' => $statValues->get(3, '<24h'), 'label' => $copy('استجابة تسعير', 'pricing response'), 'icon' => 'timer'],
    ];

    $heroSlides = [
        [
            'icon' => 'route',
            'title' => $copy('شركة شحن وتخليص جمركي تدير رحلتك من المصنع إلى باب العميل.', 'Freight and customs clearance that manages your journey from factory to customer door.'),
            'desc' => $copy('نربط طلب الشحن، التسعير، المستندات، التخليص، الفواتير، والتتبع في مسار تشغيلي واحد واضح.', 'We connect shipment requests, pricing, documents, clearance, invoices, and tracking in one clear operations flow.'),
        ],
        [
            'icon' => 'truck',
            'title' => $copy('شحن بري مرن حتى باب المستلم.', 'Flexible road freight to the receiver door.'),
            'desc' => $copy('تنسيق استلام وتسليم داخلي، متابعة السائقين، وربط الرحلة البرية بنفس ملف الشحنة.', 'Pickup, inland delivery, driver follow-up, and road movement connected to the same shipment file.'),
        ],
        [
            'icon' => 'ship',
            'title' => $copy('شحن بحري للحاويات و LCL مع متابعة تشغيلية.', 'Ocean freight for containers and LCL with operational follow-up.'),
            'desc' => $copy('من ميناء التحميل إلى ميناء التفريغ، مع التسعير، الحجز الملاحي، والمستندات الرسمية.', 'From loading port to discharge port with pricing, carrier booking, and official documents.'),
        ],
        [
            'icon' => 'plane',
            'title' => $copy('شحن جوي سريع للبضائع الحساسة للوقت.', 'Fast air freight for time-sensitive cargo.'),
            'desc' => $copy('اختيار مطارات الانطلاق والوصول، حساب الوزن الحجمي، وربط الطلب بالتسعير والتتبع.', 'Airport routing, volumetric weight calculation, and request flow tied to pricing and tracking.'),
        ],
        [
            'icon' => 'boxes',
            'title' => $copy('جميع الخدمات اللوجستية في نظام واحد.', 'All logistics services in one system.'),
            'desc' => $copy('Door to Door، مستندات، تخليص جمركي، حسابات العملاء، وتنبيهات لحظية لفريق العمل والعميل.', 'Door to Door, documents, customs clearance, customer accounts, and live alerts for staff and customers.'),
        ],
    ];

    $services = [
        [
            'icon' => 'ship',
            'kicker' => 'FCL / LCL',
            'title' => __('messages.home_service_commercial_shipping_title'),
            'desc' => __('messages.home_service_commercial_shipping_desc'),
            'image' => asset('images/brand/ocean.jpg'),
            'href' => $customerStartUrl,
        ],
        [
            'icon' => 'badge-check',
            'kicker' => $copy('إفراج جمركي', 'Customs release'),
            'title' => __('messages.home_service_customs_title'),
            'desc' => __('messages.home_service_customs_desc'),
            'image' => asset('images/brand/customs.jpg'),
            'href' => $customerStartUrl,
        ],
        [
            'icon' => 'truck',
            'kicker' => $copy('تسليم محلي', 'Local delivery'),
            'title' => __('messages.home_service_land_transport_title'),
            'desc' => __('messages.home_service_land_transport_desc'),
            'image' => asset('images/brand/road.jpg'),
            'href' => $customerStartUrl,
        ],
        [
            'icon' => 'file-check-2',
            'kicker' => $copy('ملف موحد', 'Unified file'),
            'title' => __('messages.home_service_documents_title'),
            'desc' => __('messages.home_service_documents_desc'),
            'image' => asset('images/brand/global.jpg'),
            'href' => $customerPortalUrl,
        ],
        [
            'icon' => 'radar',
            'kicker' => $copy('رؤية مباشرة', 'Live visibility'),
            'title' => __('messages.home_service_tracking_title'),
            'desc' => __('messages.home_service_tracking_desc'),
            'image' => asset('images/brand/network.jpg'),
            'href' => route('tracking'),
        ],
        [
            'icon' => 'landmark',
            'kicker' => $copy('دورة مالية', 'Finance cycle'),
            'title' => __('messages.home_service_accounts_title'),
            'desc' => __('messages.home_service_accounts_desc'),
            'image' => asset('images/brand/freight.jpg'),
            'href' => $customerPortalUrl,
        ],
    ];

    $workflow = [
        ['step' => '01', 'icon' => 'user-plus', 'title' => __('messages.home_workflow_create_account_title'), 'desc' => __('messages.home_workflow_create_account_desc')],
        ['step' => '02', 'icon' => 'package-plus', 'title' => __('messages.home_workflow_submit_shipment_title'), 'desc' => __('messages.home_workflow_submit_shipment_desc')],
        ['step' => '03', 'icon' => 'receipt-text', 'title' => __('messages.home_workflow_receive_quote_title'), 'desc' => __('messages.home_workflow_receive_quote_desc')],
        ['step' => '04', 'icon' => 'route', 'title' => __('messages.home_workflow_follow_execution_title'), 'desc' => __('messages.home_workflow_follow_execution_desc')],
    ];

    $routeNodes = [
        ['code' => 'CN', 'label' => $copy('المورد', 'Supplier'), 'meta' => $copy('تجهيز البضاعة', 'Cargo ready')],
        ['code' => 'SEA', 'label' => $copy('الشحن', 'Freight'), 'meta' => 'FCL / LCL'],
        ['code' => 'YEM', 'label' => $copy('التخليص', 'Clearance'), 'meta' => $copy('مستندات ورسوم', 'Docs and duty')],
        ['code' => 'DOOR', 'label' => $copy('التسليم', 'Delivery'), 'meta' => $copy('حتى باب العميل', 'To customer door')],
    ];

    $operations = [
        ['icon' => 'clipboard-list', 'title' => __('messages.home_workflow_requests_title'), 'desc' => __('messages.home_workflow_requests_desc')],
        ['icon' => 'messages-square', 'title' => __('messages.home_workflow_alerts_title'), 'desc' => __('messages.home_workflow_alerts_desc')],
        ['icon' => 'shield-check', 'title' => __('messages.home_workflow_permissions_title'), 'desc' => __('messages.home_workflow_permissions_desc')],
        ['icon' => 'receipt-text', 'title' => __('messages.home_workflow_outputs_title'), 'desc' => __('messages.home_workflow_outputs_desc')],
    ];

    $coverage = [
        ['icon' => 'boxes', 'label' => $copy('استيراد وتصدير', 'Import and export')],
        ['icon' => 'ship', 'label' => $copy('شحن بحري', 'Ocean freight')],
        ['icon' => 'plane', 'label' => $copy('شحن جوي', 'Air freight')],
        ['icon' => 'truck', 'label' => $copy('نقل بري', 'Road freight')],
        ['icon' => 'badge-check', 'label' => $copy('تخليص جمركي', 'Customs clearance')],
        ['icon' => 'car', 'label' => $copy('شحن سيارات', 'Vehicle shipping')],
    ];

    $faqItems = [
        [
            'question' => $copy('هل يمكنني طلب شحنة بدون تواصل متكرر؟', 'Can I request a shipment without repeated follow-up?'),
            'answer' => $copy('نعم، تبدأ الطلب من بوابة العميل، ثم تتابع التسعير والاعتماد والتتبع والمستندات من نفس الحساب.', 'Yes. Start from the customer portal, then follow pricing, approval, tracking, and documents from the same account.'),
        ],
        [
            'question' => $copy('هل تدعمون الشحن البحري والتخليص معًا؟', 'Do you support ocean freight and customs clearance together?'),
            'answer' => $copy('نعم، صممنا المسار ليجمع الحجز، المستندات، التخليص، الفوترة، والتنبيهات في ملف واحد.', 'Yes. The workflow combines booking, documents, clearance, billing, and alerts in one file.'),
        ],
        [
            'question' => $copy('ماذا يميز الواجهة عن مواقع الشحن التقليدية؟', 'What makes this experience different from typical shipping sites?'),
            'answer' => $copy('الواجهة لا تعرض خدمات فقط، بل تقدم نقطة تشغيل مباشرة: تتبع، طلب، بوابة عميل، ومراحل واضحة من المصنع إلى التسليم.', 'It does not only list services. It gives a live operations entry point: tracking, requests, portal access, and clear stages from factory to delivery.'),
        ],
    ];
@endphp

<div class="home-page bg-background">
    <section class="home-hero relative isolate overflow-hidden text-white">
        <div class="absolute inset-0 -z-20 bg-[linear-gradient(110deg,rgba(21,32,67,1),rgba(38,55,107,1),rgba(89,34,69,1))]"></div>
        <div class="home-hero-grid absolute inset-0 -z-10"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-28 bg-gradient-to-t from-background to-transparent"></div>

        <div class="container mx-auto grid min-h-[78vh] gap-10 px-4 py-12 lg:grid-cols-[0.95fr_1.05fr] lg:items-center lg:py-16">
            <div class="home-reveal max-w-4xl">
                <span class="inline-flex max-w-full items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold backdrop-blur">
                    <i data-lucide="globe-2" class="h-4 w-4 text-accent"></i>
                    <span>{{ __('messages.home_hero_badge') }}</span>
                </span>

                <h1 class="mt-6 max-w-4xl text-balance text-4xl font-black leading-tight md:text-5xl">
                    {{ config('company.name.' . app()->getLocale()) }}
                </h1>

                <div class="home-hero-slider mt-5" data-home-hero-slider>
                    <div class="relative min-h-[16rem] overflow-hidden rounded-lg border border-white/20 bg-white/10 p-5 backdrop-blur sm:min-h-[14rem] md:min-h-[12.5rem]">
                        @foreach($heroSlides as $slideIndex => $slide)
                            <article class="home-hero-slide {{ $slideIndex === 0 ? 'is-active' : '' }}" data-home-hero-slide>
                                <div class="flex items-start gap-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-accent text-accent-foreground shadow-glow">
                                        <i data-lucide="{{ $slide['icon'] }}" class="h-5 w-5"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <h2 class="text-balance text-xl font-black leading-relaxed md:text-2xl">{{ $slide['title'] }}</h2>
                                        <p class="mt-3 max-w-2xl text-sm leading-8 text-white/80 md:text-base">{{ $slide['desc'] }}</p>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2" data-home-hero-dots>
                            @foreach($heroSlides as $slideIndex => $slide)
                                <button type="button" class="home-hero-dot {{ $slideIndex === 0 ? 'is-active' : '' }}" data-home-hero-dot aria-label="{{ $copy('عرض الشريحة', 'Show slide') }} {{ $slideIndex + 1 }}"></button>
                            @endforeach
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" class="home-hero-nav" data-home-hero-prev aria-label="{{ $copy('السابق', 'Previous') }}">
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                            </button>
                            <button type="button" class="home-hero-nav" data-home-hero-next aria-label="{{ $copy('التالي', 'Next') }}">
                                <i data-lucide="arrow-left" class="h-4 w-4"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $customerStartUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg bg-white px-6 py-3 text-sm font-black text-[#26376b] shadow-elegant transition hover:-translate-y-0.5 hover:bg-white/90">
                        <i data-lucide="plus-circle" class="h-4 w-4"></i>
                        {{ __('messages.home_cta_request_shipment') }}
                    </a>
                    <a href="{{ route('tracking') }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-6 py-3 text-sm font-bold text-white backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/20">
                        <i data-lucide="radar" class="h-4 w-4"></i>
                        {{ __('messages.home_cta_track_shipment') }}
                    </a>
                    <a href="{{ $customerPortalUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-6 py-3 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:bg-white/20">
                        <i data-lucide="user-round-check" class="h-4 w-4"></i>
                        {{ __('messages.home_cta_customer_portal') }}
                    </a>
                </div>

                <div class="mt-9 grid max-w-3xl grid-cols-2 gap-3 md:grid-cols-4">
                    @foreach($heroStats as $stat)
                        <div class="home-stat rounded-lg border border-white/20 bg-white/10 p-4 backdrop-blur">
                            <i data-lucide="{{ $stat['icon'] }}" class="h-5 w-5 text-accent"></i>
                            <div class="mt-3 text-2xl font-black md:text-3xl">{{ $stat['value'] }}</div>
                            <div class="mt-1 text-xs font-semibold leading-5 text-white/70">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="home-reveal home-reveal-delay relative">
                <div class="overflow-hidden rounded-xl border border-white/20 bg-white shadow-elegant">
                    <div>
                        <img src="{{ asset('images/brand/logistics.jpg') }}" alt="{{ $copy('نظام إدارة الشحن والخدمات اللوجستية', 'Freight & Logistics Management System') }}" class="block h-auto w-full" fetchpriority="high">
                        <form action="{{ route('tracking') }}" method="GET" class="grid gap-3 border-t border-border p-4 md:grid-cols-[1fr_auto]">
                            <label for="bl-input-live" class="sr-only">{{ __('messages.home_tracking_label') }}</label>
                            <div class="relative">
                                <i data-lucide="scan-search" class="absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-muted-foreground"></i>
                                <input id="bl-input-live" name="q" type="text" dir="ltr" placeholder="{{ __('messages.home_tracking_label') }}" class="h-12 w-full rounded-lg border border-border bg-background pr-11 pl-4 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-accent">
                            </div>
                            <button type="submit" class="inline-flex h-12 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-sm font-black text-primary-foreground transition hover:opacity-95">
                                <i data-lucide="search" class="h-4 w-4"></i>
                                {{ __('messages.home_track_now') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="border-y border-border bg-card">
        <div class="container mx-auto grid gap-3 px-4 py-5 text-sm font-bold text-muted-foreground md:grid-cols-4">
            @foreach([__('messages.home_feature_requests'), __('messages.home_feature_quotes'), __('messages.home_feature_customs'), __('messages.home_feature_documents')] as $label)
                <div class="flex min-h-10 items-center gap-2 rounded-lg bg-secondary/45 px-3">
                    <i data-lucide="check-circle-2" class="h-4 w-4 text-success"></i>
                    <span>{{ $label }}</span>
                </div>
            @endforeach
        </div>
    </section>

    <section class="container mx-auto px-4 py-16 md:py-20">
        <div class="grid gap-10 lg:grid-cols-[0.78fr_1.22fr] lg:items-end">
            <div>
                <span class="text-sm font-black uppercase tracking-widest text-accent">{{ __('messages.services') }}</span>
                <h2 class="mt-3 max-w-xl text-3xl font-black leading-tight md:text-4xl">{{ __('messages.home_services_heading') }}</h2>
                <p class="mt-4 max-w-xl text-sm leading-8 text-muted-foreground">{{ __('messages.home_services_intro') }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
                @foreach($coverage as $item)
                    <div class="coverage-chip rounded-lg border border-border bg-card px-4 py-3 shadow-card">
                        <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5 text-accent"></i>
                        <span>{{ $item['label'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-10 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            @foreach($services as $service)
                <a href="{{ $service['href'] }}" class="service-showcase group rounded-lg border border-border bg-card shadow-card transition duration-300 hover:-translate-y-1 hover:border-accent/40 hover:shadow-elegant">
                    <div class="overflow-hidden rounded-t-lg bg-white">
                        <img src="{{ $service['image'] }}" alt="{{ $service['title'] }}" loading="lazy" class="block h-auto w-full">
                    </div>
                    <div class="p-5">
                        <h3 class="text-lg font-black">{{ $service['title'] }}</h3>
                        <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ $service['desc'] }}</p>
                        <span class="mt-5 inline-flex items-center gap-2 text-sm font-black text-accent">
                            {{ $copy('ابدأ الخدمة', 'Start service') }}
                            <i data-lucide="arrow-up-left" class="h-4 w-4"></i>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>

    <section class="operations-band overflow-hidden border-y border-border bg-secondary/35 py-16 md:py-20">
        <div class="container mx-auto grid gap-10 px-4 lg:grid-cols-[1fr_0.9fr] lg:items-center">
            <div class="overflow-hidden rounded-lg bg-white shadow-elegant">
                <img src="{{ asset('images/brand/global.jpg') }}" alt="{{ $copy('نربط أعمالك بالعالم', 'Connecting your business to the world') }}" loading="lazy" class="block h-auto w-full">
            </div>

            <div>
                <span class="text-sm font-black uppercase tracking-widest text-accent">{{ __('messages.home_professional_workflow_title') }}</span>
                <h2 class="mt-3 text-3xl font-black leading-tight md:text-4xl">{{ __('messages.home_professional_workflow_subtitle') }}</h2>
                <div class="mt-7 space-y-3">
                    @foreach($operations as $item)
                        <div class="operation-row rounded-lg border border-border bg-card p-4 shadow-card">
                            <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-primary text-primary-foreground">
                                <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i>
                            </span>
                            <div>
                                <h3 class="font-black">{{ $item['title'] }}</h3>
                                <p class="mt-1 text-sm leading-7 text-muted-foreground">{{ $item['desc'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-16 md:py-20">
        <div class="mx-auto max-w-3xl text-center">
            <span class="text-sm font-black uppercase tracking-widest text-accent">{{ __('messages.home_customer_portal_section_title') }}</span>
            <h2 class="mt-3 text-3xl font-black leading-tight md:text-4xl">{{ __('messages.home_customer_portal_section_heading') }}</h2>
            <p class="mt-4 text-sm leading-8 text-muted-foreground">{{ __('messages.home_customer_portal_section_desc') }}</p>
        </div>

        <div class="relative mt-12">
            <div class="workflow-line hidden md:block"></div>
            <div class="grid gap-4 md:grid-cols-4">
                @foreach($workflow as $item)
                    <article class="workflow-card rounded-lg border border-border bg-card p-5 shadow-card">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-2xl font-black text-accent">{{ $item['step'] }}</span>
                            <span class="grid h-11 w-11 place-items-center rounded-lg bg-secondary text-primary">
                                <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i>
                            </span>
                        </div>
                        <h3 class="mt-5 text-lg font-black">{{ $item['title'] }}</h3>
                        <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ $item['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>

        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ $customerRegisterUrl }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground shadow-glow">
                <i data-lucide="user-plus" class="h-4 w-4"></i>
                {{ __('messages.home_create_customer_account') }}
            </a>
            <a href="{{ route('login') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-border bg-card px-5 text-sm font-bold hover:bg-secondary/50">
                <i data-lucide="id-card" class="h-4 w-4"></i>
                {{ __('messages.home_staff_login') }}
            </a>
        </div>
    </section>

    <section class="border-y border-border bg-card py-16 md:py-20">
        <div class="container mx-auto grid gap-10 px-4 lg:grid-cols-[0.85fr_1.15fr] lg:items-start">
            <div>
                <span class="text-sm font-black uppercase tracking-widest text-accent">{{ $copy('أسئلة العملاء', 'Customer questions') }}</span>
                <h2 class="mt-3 text-3xl font-black leading-tight md:text-4xl">{{ $copy('إجابات سريعة قبل أن تبدأ الشحنة.', 'Fast answers before the shipment starts.') }}</h2>
                <p class="mt-4 text-sm leading-8 text-muted-foreground">
                    {{ $copy('إجابات حول طلب الشحنة، واستخدام بوابة العميل، ومتابعة الشحنات.', 'Answers about shipment requests, customer portal access, and tracking.') }}
                </p>
            </div>
            <div class="space-y-3">
                @foreach($faqItems as $index => $item)
                    <details class="faq-item group rounded-lg border border-border bg-background p-5 shadow-card" {{ $index === 0 ? 'open' : '' }}>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-base font-black">
                            <span>{{ $item['question'] }}</span>
                            <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 text-accent transition group-open:rotate-180"></i>
                        </summary>
                        <p class="mt-3 text-sm leading-8 text-muted-foreground">{{ $item['answer'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    <section class="container mx-auto px-4 py-16 md:py-20">
        <div class="bottom-command relative overflow-hidden rounded-lg bg-hero p-6 text-white shadow-elegant md:p-10">
            <div class="relative grid gap-8 lg:grid-cols-[1fr_0.82fr] lg:items-center">
                <div>
                    <span class="inline-flex items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-3 py-1 text-xs font-black text-accent">
                        <i data-lucide="radio-tower" class="h-4 w-4"></i>
                        {{ $copy('جاهز للتشغيل', 'Ready for operations') }}
                    </span>
                    <h2 class="mt-4 text-3xl font-black leading-tight md:text-4xl">{{ __('messages.home_bottom_cta_title') }}</h2>
                    <p class="mt-4 max-w-2xl text-sm leading-8 text-white/80">{{ __('messages.home_bottom_cta_desc') }}</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <a href="{{ $customerStartUrl }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-lg bg-white px-6 text-sm font-black text-[#26376b] hover:bg-white/90">
                        <i data-lucide="rocket" class="h-4 w-4"></i>
                        {{ __('messages.home_bottom_cta_start_now') }}
                    </a>
                    <a href="{{ $customerStartUrl }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-white/30 bg-white/10 px-6 text-sm font-bold text-white hover:bg-white/20">
                        <i data-lucide="calculator" class="h-4 w-4"></i>
                        {{ __('messages.home_bottom_cta_get_quote') }}
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>


@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-home-hero-slider]').forEach((slider) => {
            const slides = Array.from(slider.querySelectorAll('[data-home-hero-slide]'));
            const dots = Array.from(slider.querySelectorAll('[data-home-hero-dot]'));
            const previousButton = slider.querySelector('[data-home-hero-prev]');
            const nextButton = slider.querySelector('[data-home-hero-next]');
            let activeIndex = Math.max(0, slides.findIndex((slide) => slide.classList.contains('is-active')));
            let timer = null;
            let touchStartX = 0;

            if (slides.length <= 1) {
                return;
            }

            const showSlide = (index) => {
                activeIndex = (index + slides.length) % slides.length;
                slides.forEach((slide, slideIndex) => {
                    slide.classList.toggle('is-active', slideIndex === activeIndex);
                });
                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('is-active', dotIndex === activeIndex);
                });
            };

            const restart = () => {
                window.clearInterval(timer);
                timer = window.setInterval(() => showSlide(activeIndex + 1), 5200);
            };

            previousButton?.addEventListener('click', () => {
                showSlide(activeIndex - 1);
                restart();
            });

            nextButton?.addEventListener('click', () => {
                showSlide(activeIndex + 1);
                restart();
            });

            dots.forEach((dot, dotIndex) => {
                dot.addEventListener('click', () => {
                    showSlide(dotIndex);
                    restart();
                });
            });

            slider.addEventListener('touchstart', (event) => {
                touchStartX = event.changedTouches[0]?.clientX || 0;
            }, { passive: true });

            slider.addEventListener('touchend', (event) => {
                const touchEndX = event.changedTouches[0]?.clientX || 0;
                const delta = touchEndX - touchStartX;

                if (Math.abs(delta) < 42) {
                    return;
                }

                showSlide(delta > 0 ? activeIndex - 1 : activeIndex + 1);
                restart();
            }, { passive: true });

            restart();
        });
    });
</script>
@endsection

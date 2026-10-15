@php
    $supportLandline = config('company.landline.display');
    $supportMobile = config('company.phones.0.display');
    $whatsAppNumber = config('company.whatsapp');
    $whatsAppText = rawurlencode('مرحباً، لدي حساب عميل قيد المراجعة وأرغب بتفعيل الحساب.');
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>الحساب قيد المراجعة — {{ __('messages.site_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset(config('company.icon')) }}">
    <link rel="apple-touch-icon" href="{{ asset(config('company.icon')) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="customer-portal min-h-screen bg-background text-foreground font-sans selection:bg-accent selection:text-accent-foreground">
    @include('customer.partials.ui-strings')
    <main class="pending-approval-page relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-hero"></div>
        <div class="pending-approval-wash absolute inset-0 -z-20"></div>
        <div class="pending-approval-grid absolute inset-0 -z-10"></div>

        <section class="container mx-auto flex min-h-screen max-w-6xl flex-col px-4 py-6">
            <header class="flex flex-wrap items-center justify-between gap-4">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3">
                    <img src="{{ asset(config('company.logo')) }}" alt="{{ __('messages.company_logo_alt') }}" class="h-11 w-24 rounded-xl border border-border bg-white object-contain p-1 shadow-sm">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-black sm:text-base">{{ __('messages.site_name') }}</div>
                        <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-muted-foreground">Customer Portal</div>
                    </div>
                </a>

                @include('customer.partials.auth-tools')
                <a href="{{ route('customer.login') }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card/80 px-4 text-xs font-black shadow-sm backdrop-blur hover:bg-secondary">
                    <i data-lucide="log-in" class="h-4 w-4 text-accent"></i>
                    {{ __('تسجيل الدخول') }}
                </a>
            </header>

            <div class="grid flex-1 items-center gap-8 py-10 lg:grid-cols-[1fr_0.82fr]">
                <section class="order-2 hidden lg:block">
                    <div class="pending-route-panel rounded-2xl border border-border/70 bg-card/80 p-6 shadow-card backdrop-blur">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-xs font-black text-accent">{{ __('مسار اعتماد الحساب') }}</div>
                                <h2 class="mt-2 text-2xl font-black">{{ __('مراجعة دقيقة قبل فتح البوابة') }}</h2>
                            </div>
                            <span class="grid h-12 w-12 place-items-center rounded-xl bg-primary text-primary-foreground shadow-sm">
                                <i data-lucide="shield-check" class="h-6 w-6"></i>
                            </span>
                        </div>

                        <div class="pending-route-track mt-8">
                            <div class="pending-route-line"></div>
                            @foreach([
                                ['icon' => 'send', 'title' => __('استلام الطلب'), 'text' => __('تم حفظ بيانات الحساب الجديد.')],
                                ['icon' => 'scan-search', 'title' => __('مراجعة الإدارة'), 'text' => __('فريق العملاء يطابق البيانات داخلياً.')],
                                ['icon' => 'badge-check', 'title' => __('التفعيل'), 'text' => __('بعد الاعتماد يصبح الدخول متاحاً فوراً.')],
                            ] as $step)
                                <div class="pending-route-step">
                                    <span class="pending-route-dot">
                                        <i data-lucide="{{ $step['icon'] }}" class="h-4 w-4"></i>
                                    </span>
                                    <div class="rounded-xl border border-border bg-background/80 p-4">
                                        <h3 class="text-sm font-black">{{ $step['title'] }}</h3>
                                        <p class="mt-1 text-xs leading-6 text-muted-foreground">{{ $step['text'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>

                <section class="order-1 mx-auto w-full max-w-xl lg:order-2">
                    <div class="pending-approval-card rounded-2xl border border-border/80 bg-card/95 p-5 shadow-elegant backdrop-blur-xl sm:p-8">
                        <div class="mx-auto pending-clock grid h-20 w-20 place-items-center rounded-full border border-accent/25 bg-accent/10 text-accent">
                            <i data-lucide="clock-3" class="h-9 w-9"></i>
                        </div>

                        <div class="mt-6 text-center">
                            <span class="inline-flex items-center gap-2 rounded-full border border-accent/20 bg-accent/10 px-3 py-1 text-[11px] font-black text-accent">
                                <i data-lucide="sparkles" class="h-3.5 w-3.5"></i>
                                {{ __('طلبك وصل بنجاح') }}
                            </span>
                            <h1 class="mt-4 text-3xl font-black sm:text-4xl">{{ __('الحساب قيد المراجعة') }}</h1>
                            <p class="mx-auto mt-4 max-w-md text-sm leading-8 text-muted-foreground">
                                {{ __('تم استلام طلب إنشاء الحساب. لن يتم فتح بوابة العميل حتى تعتمد إدارة العملاء الحساب وتفعله من النظام.') }}
                            </p>
                        </div>

                        @if($customer)
                            <div class="mt-6 grid gap-3 rounded-xl border border-border bg-secondary/30 p-4 text-sm sm:grid-cols-2">
                                <div>
                                    <div class="text-[11px] font-bold text-muted-foreground">{{ __('الشركة') }}</div>
                                    <div class="mt-1 font-black">{{ $customer->company }}</div>
                                </div>
                                <div>
                                    <div class="text-[11px] font-bold text-muted-foreground">{{ __('المسؤول') }}</div>
                                    <div class="mt-1 font-black">{{ $customer->contact_name ?: '—' }}</div>
                                </div>
                                <div class="sm:col-span-2">
                                    <div class="text-[11px] font-bold text-muted-foreground">{{ __('البريد الإلكتروني') }}</div>
                                    <div class="mt-1 font-black" dir="ltr">{{ $customer->email }}</div>
                                </div>
                            </div>
                        @endif

                        <div class="mt-6 rounded-xl border border-accent/20 bg-accent/10 p-5">
                            <h2 class="text-center text-base font-black">{{ __('للتواصل مع الدعم الفني وتفعيل الحساب') }}</h2>
                            <div class="mt-4 space-y-3">
                                <a href="tel:{{ config('company.landline.tel') }}" class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-3 text-sm transition hover:border-accent/40 hover:bg-background">
                                    <span class="flex items-center gap-2 font-bold text-muted-foreground">
                                        <i data-lucide="phone-call" class="h-4 w-4 text-accent"></i>
                                        {{ __('الهاتف الأرضي') }}
                                    </span>
                                    <span class="font-black" dir="ltr">{{ $supportLandline }}</span>
                                </a>
                                <a href="https://wa.me/{{ $whatsAppNumber }}?text={{ $whatsAppText }}" target="_blank" rel="noopener" class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card px-4 py-3 text-sm transition hover:border-accent/40 hover:bg-background">
                                    <span class="flex items-center gap-2 font-bold text-muted-foreground">
                                        <i data-lucide="smartphone" class="h-4 w-4 text-accent"></i>
                                        {{ __('الجوال / واتساب') }}
                                    </span>
                                    <span class="font-black" dir="ltr">{{ $supportMobile }}</span>
                                </a>
                            </div>
                        </div>

                        <div class="mt-6 grid gap-3 sm:grid-cols-2">
                            <a href="{{ route('home') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground shadow-glow hover:opacity-95">
                                <i data-lucide="home" class="h-4 w-4"></i>
                                {{ __('العودة للصفحة الرئيسية') }}
                            </a>
                            <a href="{{ route('customer.login') }}" class="inline-flex h-12 items-center justify-center gap-2 rounded-lg border border-border bg-background px-5 text-sm font-black hover:bg-secondary">
                                <i data-lucide="arrow-right" class="h-4 w-4 text-accent"></i>
                                {{ __('العودة لتسجيل الدخول') }}
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </section>
    </main>
</body>
</html>

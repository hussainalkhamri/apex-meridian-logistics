@php
    $accountType = $accountType ?? 'customer';
    $mode = $mode ?? 'login';
    $isCustomer = $accountType === 'customer';
    $isRegister = $mode === 'register';
    $title = $isCustomer
        ? ($isRegister ? __('messages.customer_register_title') : __('messages.customer_login_title'))
        : ($isRegister ? __('messages.staff_account_request_title') : __('messages.staff_login_title'));
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title }} — {{ __('messages.site_name') }}</title>
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
<body class="{{ $isCustomer ? 'customer-portal' : '' }} min-h-screen bg-background text-foreground font-sans selection:bg-accent selection:text-accent-foreground">
    @if($isCustomer) @include('customer.partials.ui-strings') @endif
    <main class="relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 -z-10 bg-gradient-hero"></div>
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(115deg,rgba(7,20,40,0.96),rgba(9,44,68,0.9),rgba(12,111,128,0.52))]"></div>

        <div class="container mx-auto grid min-h-screen gap-8 px-4 py-8 lg:grid-cols-[0.95fr_1.05fr] lg:items-center lg:py-10">
            <section class="hidden text-white lg:block">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-3">
                    <div>
                        <div class="text-2xl font-black tracking-tight">{{ __('messages.site_name') }}</div>
                    </div>
                </a>

                <div class="mt-16 max-w-xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-xs font-bold backdrop-blur">
                        <i data-lucide="shield-check" class="h-4 w-4 text-accent"></i>
                        {{ __('messages.unified_secure_portal') }}
                    </span>
                    <h1 class="mt-6 text-5xl font-black leading-tight">
                        {{ __('messages.unified_access_heading') }}
                    </h1>
                    <p class="mt-5 text-base leading-8 text-white/80">
                        {{ __('messages.auth_subheading') }}
                    </p>
                </div>

                <div class="mt-10 grid max-w-xl gap-3 sm:grid-cols-3">
                    @foreach([
                        ['icon' => 'user-round-check', 'label' => __('messages.clients')],
                        ['icon' => 'id-card', 'label' => __('messages.staff')],
                        ['icon' => 'lock-keyhole', 'label' => __('messages.secure_session')],
                    ] as $item)
                        <div class="rounded-xl border border-white/20 bg-white/10 p-4 backdrop-blur">
                            <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5 text-accent"></i>
                            <div class="mt-3 text-sm font-black">{{ $item['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="mx-auto w-full max-w-xl">
                <div class="rounded-2xl border border-white/20 bg-white/10 p-2 shadow-elegant backdrop-blur-xl">
                    <div class="rounded-xl bg-card p-5 shadow-card sm:p-7">
                        <div class="mb-7 flex items-center justify-between gap-3">
                            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-3 lg:hidden">
                                <div class="min-w-0">
                                    <div class="truncate text-base font-black">{{ __('messages.site_name') }}</div>
                                </div>
                            </a>
                            <a href="{{ route('home') }}" class="mr-auto inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border bg-background px-3 text-xs font-bold hover:bg-secondary/60">
                                <i data-lucide="arrow-right" class="h-4 w-4"></i>
                                {{ __('messages.home') }}
                            </a>
                        </div>

                        <div class="rounded-xl border border-border bg-secondary/40 p-1">
                            <div class="grid grid-cols-2 gap-1">
                                <a href="{{ route('customer.login') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg text-sm font-black transition {{ $isCustomer ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:bg-card/60 hover:text-foreground' }}">
                                    <i data-lucide="users-round" class="h-4 w-4"></i>
                                    {{ __('messages.customer') }}
                                </a>
                                <a href="{{ route('login') }}" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg text-sm font-black transition {{ ! $isCustomer ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:bg-card/60 hover:text-foreground' }}">
                                    <i data-lucide="id-card" class="h-4 w-4"></i>
                                    {{ __('messages.staff') }}
                                </a>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-border bg-background p-1">
                            <div class="grid grid-cols-2 gap-1">
                                <a href="{{ $isCustomer ? route('customer.login') : route('login') }}" class="inline-flex h-10 items-center justify-center rounded-lg text-xs font-black transition {{ ! $isRegister ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:bg-secondary' }}">
                                    {{ __('messages.login') }}
                                </a>
                                <a href="{{ $isCustomer ? route('customer.register') : route('login', ['mode' => 'register']) }}" class="inline-flex h-10 items-center justify-center rounded-lg text-xs font-black transition {{ $isRegister ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:bg-secondary' }}">
                                    {{ __('messages.create_account') }}
                                </a>
                            </div>
                        </div>

                        @if($isCustomer)
                            <div class="mt-4 flex items-center justify-between gap-3">
                                @include('customer.partials.auth-tools')
                            </div>
                        @endif
                        <div class="mt-5">
                            <h2 class="text-2xl font-black">{{ $title }}</h2>
                            <p class="mt-2 text-sm leading-7 text-muted-foreground">
                                @if($isCustomer && $isRegister)
                                    {{ __('messages.customer_register_prompt') }}
                                @elseif($isCustomer)
                                    {{ __('messages.customer_login_prompt') }}
                                @elseif($isRegister)
                                    {{ __('messages.staff_register_admin_note') }}
                                @else
                                    {{ __('messages.staff_login_prompt') }}
                                @endif
                            </p>
                        </div>

                        @if($errors->any())
                            <div role="alert" class="mt-5 flex items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                                <i data-lucide="alert-circle" class="mt-0.5 h-5 w-5 shrink-0"></i>
                                <div class="font-bold">{{ $errors->first() }}</div>
                            </div>
                        @endif

                        @if(session('success'))
                            <div class="mt-5 flex items-start gap-3 rounded-xl border border-success/30 bg-success/10 p-4 text-sm text-success">
                                <i data-lucide="check-circle-2" class="mt-0.5 h-5 w-5 shrink-0"></i>
                                <div class="font-bold">{{ session('success') }}</div>
                            </div>
                        @endif

                        @if($isCustomer && $isRegister)
                            <form action="{{ route('customer.register.submit') }}" method="POST" class="mt-6 space-y-4">
                                @csrf
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <label for="company" class="text-xs font-black">{{ __('messages.company_name_label') }} *</label>
                                        <input id="company" name="company" value="{{ old('company') }}" required autocomplete="organization" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label for="contact_name" class="text-xs font-black">{{ __('messages.contact_name_label') }} *</label>
                                        <input id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required autocomplete="name" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <label for="phone" class="text-xs font-black">{{ __('messages.phone_label') }} *</label>
                                        <input id="phone" name="phone" value="{{ old('phone') }}" required dir="ltr" autocomplete="tel" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                    <div class="space-y-1.5">
                                        <label for="email" class="text-xs font-black">{{ __('messages.email') }} *</label>
                                        <input id="email" type="email" name="email" value="{{ old('email') }}" required dir="ltr" autocomplete="email" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <label for="password" class="text-xs font-black">{{ __('messages.password') }} *</label>
                                        <div class="relative">
                                            <input id="password" type="password" name="password" required autocomplete="new-password" class="h-11 w-full rounded-lg border border-border bg-background ps-3 pe-12 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                            <button type="button" id="register-password-toggle" data-password-toggle="password" data-show-label="{{ __('messages.show_password') }}" data-hide-label="{{ __('messages.hide_password') }}" class="absolute end-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-secondary/60 hover:text-foreground focus:outline-none focus:ring-2 focus:ring-accent" aria-label="{{ __('messages.show_password') }}" title="{{ __('messages.show_password') }}" aria-pressed="false">
                                                <i data-password-eye data-lucide="eye" class="h-4.5 w-4.5"></i>
                                                <i data-password-eye-off data-lucide="eye-off" class="hidden h-4.5 w-4.5"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label for="password_confirmation" class="text-xs font-black">{{ __('messages.password_confirmation') }} *</label>
                                        <div class="relative">
                                            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" class="h-11 w-full rounded-lg border border-border bg-background ps-3 pe-12 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                            <button type="button" id="register-password-confirmation-toggle" data-password-toggle="password_confirmation" data-show-label="{{ __('messages.show_password') }}" data-hide-label="{{ __('messages.hide_password') }}" class="absolute end-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-secondary/60 hover:text-foreground focus:outline-none focus:ring-2 focus:ring-accent" aria-label="{{ __('messages.show_password') }}" title="{{ __('messages.show_password') }}" aria-pressed="false">
                                                <i data-password-eye data-lucide="eye" class="h-4.5 w-4.5"></i>
                                                <i data-password-eye-off data-lucide="eye-off" class="hidden h-4.5 w-4.5"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <button class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground shadow-glow hover:opacity-95">
                                    <i data-lucide="user-plus" class="h-4 w-4"></i>
                                    {{ __('messages.create_customer_account_btn') }}
                                </button>
                                <div class="flex items-start gap-2 rounded-lg border border-accent/20 bg-accent/10 p-3 text-xs font-bold leading-6 text-muted-foreground">
                                    <i data-lucide="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-accent"></i>
                                    {{ __('portal.approval_hint') }}
                                </div>
                            </form>
                        @elseif($isCustomer)
                            <form action="{{ route('customer.login.submit') }}" method="POST" class="mt-6 space-y-4">
                                @csrf
                                <div class="space-y-1.5">
                                    <label for="customer_email" class="text-xs font-black">{{ __('messages.email') }} *</label>
                                    <input id="customer_email" type="email" name="email" value="{{ old('email') }}" required dir="ltr" autocomplete="email" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="customer_password" class="text-xs font-black">{{ __('messages.password') }} *</label>
                                    <div class="relative">
                                        <input id="customer_password" type="password" name="password" required autocomplete="current-password" class="h-11 w-full rounded-lg border border-border bg-background ps-3 pe-12 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                        <button type="button" id="customer-password-toggle" data-password-toggle="customer_password" data-show-label="{{ __('messages.show_password') }}" data-hide-label="{{ __('messages.hide_password') }}" class="absolute end-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-secondary/60 hover:text-foreground focus:outline-none focus:ring-2 focus:ring-accent" aria-label="{{ __('messages.show_password') }}" title="{{ __('messages.show_password') }}" aria-pressed="false">
                                            <i data-password-eye data-lucide="eye" class="h-4.5 w-4.5"></i>
                                            <i data-password-eye-off data-lucide="eye-off" class="hidden h-4.5 w-4.5"></i>
                                        </button>
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 text-xs font-semibold text-muted-foreground">
                                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-border accent-accent">
                                    {{ __('messages.remember_me') }}
                                </label>
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground shadow-glow hover:opacity-95">
                                    <i data-lucide="log-in" class="h-4 w-4"></i>
                                    {{ __('messages.customer_portal_login_btn') }}
                                </button>
                            </form>
                        @elseif($isRegister)
                            <div class="mt-6 rounded-xl border border-accent/25 bg-accent/10 p-5">
                                <div class="flex gap-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-primary text-primary-foreground">
                                        <i data-lucide="shield-alert" class="h-5 w-5"></i>
                                    </span>
                                    <div>
                                        <h3 class="font-black">{{ __('messages.staff_register_admin_heading') }}</h3>
                                            <p class="mt-2 text-sm leading-7 text-muted-foreground">{{ __('messages.staff_register_admin_note') }}</p>
                                    </div>
                                </div>
                                    <a href="{{ route('login') }}" class="mt-5 inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-primary px-5 text-xs font-black text-primary-foreground">
                                        <i data-lucide="log-in" class="h-4 w-4"></i>
                                    {{ __('messages.back_to_staff_login') }}
                                    </a>
                            </div>
                        @else
                            <form action="{{ route('login') }}" method="POST" class="mt-6 space-y-4">
                                @csrf
                                <div class="space-y-1.5">
                                    <label for="username" class="text-xs font-black">{{ __('messages.username') }} *</label>
                                    <input id="username" name="username" value="{{ old('username') }}" required autocomplete="username" class="h-11 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div class="space-y-1.5">
                                    <label for="staff_password" class="text-xs font-black">{{ __('messages.password') }} *</label>
                                    <div class="relative">
                                        <input id="staff_password" type="password" name="password" required autocomplete="current-password" class="h-11 w-full rounded-lg border border-border bg-background ps-3 pe-12 text-sm focus:outline-none focus:ring-2 focus:ring-accent">
                                        <button type="button" id="password-toggle" data-password-toggle="staff_password" data-show-label="{{ __('messages.show_password') }}" data-hide-label="{{ __('messages.hide_password') }}" class="absolute end-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-secondary/60 hover:text-foreground focus:outline-none focus:ring-2 focus:ring-accent" aria-label="{{ __('messages.show_password') }}" title="{{ __('messages.show_password') }}" aria-pressed="false">
                                            <i data-password-eye data-lucide="eye" class="h-4.5 w-4.5"></i>
                                            <i data-password-eye-off data-lucide="eye-off" class="hidden h-4.5 w-4.5"></i>
                                        </button>
                                    </div>
                                </div>
                                <label class="flex items-center gap-2 text-xs font-semibold text-muted-foreground">
                                    <input type="checkbox" name="remember" class="h-4 w-4 rounded border-border accent-accent">
                                    {{ __('messages.remember_me') }}
                                </label>
                                <button type="submit" class="inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-black text-accent-foreground shadow-glow hover:opacity-95">
                                    <i data-lucide="id-card" class="h-4 w-4"></i>
                                    {{ __('messages.staff_panel_login_btn') }}
                                </button>
                            </form>
                        @endif

                        <div class="mt-7 grid gap-3 border-t border-border pt-5 text-xs text-muted-foreground sm:grid-cols-3">
                            <div class="flex items-center gap-2">
                                <i data-lucide="lock" class="h-4 w-4 text-success"></i>
                                {{ __('messages.secure_session') }}
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="bell" class="h-4 w-4 text-accent"></i>
                                {{ __('messages.live_notifications') }}
                            </div>
                            <div class="flex items-center gap-2">
                                <i data-lucide="file-text" class="h-4 w-4 text-warning"></i>
                                {{ __('messages.linked_files') }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordToggle);
                if (! input) {
                    return;
                }

                const willShow = input.type === 'password';
                const eye = button.querySelector('[data-password-eye]');
                const eyeOff = button.querySelector('[data-password-eye-off]');
                const label = willShow ? button.dataset.hideLabel : button.dataset.showLabel;

                input.type = willShow ? 'text' : 'password';
                eye?.classList.toggle('hidden', willShow);
                eyeOff?.classList.toggle('hidden', ! willShow);
                button.setAttribute('aria-pressed', willShow ? 'true' : 'false');
                button.setAttribute('aria-label', label);
                button.setAttribute('title', label);
                input.focus();
            });
        });
    </script>
</body>
</html>

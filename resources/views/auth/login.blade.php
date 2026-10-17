<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ __('messages.login') }} — {{ __('messages.site_name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset(config('company.icon')) }}">
    <link rel="apple-touch-icon" href="{{ asset(config('company.icon')) }}">
    
    <!-- Cairo Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark Mode pre-render script to prevent light flash -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-background text-foreground min-h-screen font-sans selection:bg-accent selection:text-accent-foreground">

    <div class="relative flex min-h-screen flex-col justify-center overflow-hidden lg:grid lg:grid-cols-12 lg:gap-0 lg:p-0">
        
        <!-- Left Side: Login Form (Col 1 to 5) -->
        <div class="relative z-10 flex flex-col justify-center bg-background/85 px-6 py-12 backdrop-blur-md sm:px-12 lg:col-span-5 lg:h-full lg:px-16 lg:bg-background lg:backdrop-blur-none shadow-2xl">
            
            <div class="mx-auto w-full max-w-md">
                
                <!-- Logo -->
                <div class="mb-8 flex items-center gap-3">
                    <img src="{{ asset(config('company.icon')) }}?v=3" alt="{{ __('messages.company_logo_alt') }}" class="h-12 w-12 rounded-xl bg-white object-contain p-1 border border-border shadow-sm">
                    <div class="leading-tight">
                        <div class="text-lg font-extrabold tracking-tight text-foreground">{{ __('messages.site_name') }}</div>
                        <div class="text-[10px] uppercase tracking-[0.2em] text-muted-foreground">{{ config('company.name.en') }}</div>
                    </div>
                </div>

                <!-- Titles -->
                <div class="mb-8">
                    <h1 class="text-2xl font-black text-foreground sm:text-3xl">{{ __('messages.login_title') }}</h1>
                    <p class="mt-2 text-sm text-muted-foreground">{{ __('messages.login_description') }}</p>
                </div>

                <!-- Session Alert / Errors -->
                @if ($errors->any())
                    <div class="mb-5 flex items-start gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-destructive">
                        <i data-lucide="alert-circle" class="h-5 w-5 shrink-0 mt-0.5"></i>
                        <div class="font-semibold">{{ $errors->first() }}</div>
                    </div>
                @endif

                <!-- Form -->
                <form action="{{ route('login') }}" method="POST" class="space-y-5">
                    @csrf

                    <!-- Username input -->
                    <div>
                        <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-foreground/80" for="username">{{ __('messages.username') }}</label>
                        <div class="relative">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                <i data-lucide="user" class="h-4.5 w-4.5"></i>
                            </span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                value="{{ old('username') }}"
                                placeholder="{{ __('messages.username') }}"
                                required
                                autocomplete="username"
                                class="w-full h-11 rounded-lg border border-border/80 bg-card pr-10 pl-4 text-sm text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-accent transition-all"
                            >
                        </div>
                    </div>

                    <!-- Password input -->
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider text-foreground/80" for="password">{{ __('messages.password') }}</label>
                        </div>
                        <div class="relative">
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">
                                <i data-lucide="lock" class="h-4.5 w-4.5"></i>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password"
                                class="w-full h-11 rounded-lg border border-border/80 bg-card pr-10 pl-12 text-sm text-foreground placeholder:text-muted-foreground/60 focus:outline-none focus:ring-2 focus:ring-accent transition-all"
                            >
                            <button
                                type="button"
                                id="password-toggle"
                                class="absolute left-3 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-secondary/60 hover:text-foreground focus:outline-none focus:ring-2 focus:ring-accent"
                                aria-label="{{ __('messages.show_password') }}"
                                title="{{ __('messages.show_password') }}"
                                aria-pressed="false"
                            >
                                <i data-password-eye data-lucide="eye" class="h-4.5 w-4.5"></i>
                                <i data-password-eye-off data-lucide="eye-off" class="hidden h-4.5 w-4.5"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Remember me -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="remember" name="remember" class="h-4 w-4 rounded border-border accent-accent cursor-pointer">
                            <span class="text-xs font-semibold text-muted-foreground">{{ __('messages.remember_me') }}</span>
                        </label>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="w-full h-11 rounded-lg bg-gradient-accent text-sm font-bold text-accent-foreground hover:opacity-95 shadow-glow transition-all flex items-center justify-center gap-2">
                        {{ __('messages.login') }}
                        <i data-lucide="log-in" class="h-4 w-4"></i>
                    </button>
                </form>

                <!-- Back Link -->
                <div class="mt-8 text-center border-t border-border/60 pt-6">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-muted-foreground hover:text-foreground transition-colors">
                        <i data-lucide="arrow-right" class="h-4 w-4"></i>
                        {{ __('messages.back_to_site') }}
                    </a>
                </div>

                <!-- Sandbox credentials warning (remove in real prod if needed, perfect for preview/admin roles check) -->
                <div class="mt-6 rounded-xl border border-dashed border-border/60 bg-secondary/30 p-4">
                    <div class="flex items-start gap-2.5">
                        <i data-lucide="info" class="h-4 w-4 text-accent shrink-0 mt-0.5"></i>
                        <div class="text-left">
                            <p class="text-[11px] font-bold text-foreground">{{ __('messages.demo_credentials') }} ({{ __('messages.general_manager') }}):</p>
                            <p class="mt-1 font-mono text-xs text-muted-foreground">{{ __('messages.username') }}: <span class="text-foreground font-bold">admin</span> / {{ __('messages.password') }}: <span class="text-foreground font-bold">admin1234</span></p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Right Side: Cover / Hero (Col 6 to 12) -->
        <div class="hidden lg:relative lg:col-span-7 lg:flex lg:flex-col lg:justify-between lg:h-full overflow-hidden bg-[#26376b]">
            <!-- Background Image -->
            <div class="absolute inset-0 bg-gradient-hero"></div>
            <!-- Ocean Gradient Overlay -->
            <div class="absolute inset-0 bg-gradient-to-t from-[#182448]/95 via-[#26376b]/80 to-transparent"></div>

            <div class="relative p-12 text-white">
                <span class="inline-flex items-center gap-1.5 rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs text-white backdrop-blur">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent animate-pulse"></span>
                    {{ __('messages.site_name') }} {{ __('messages.login_hero_badge') }}
                </span>
            </div>

            <!-- Bottom content -->
            <div class="relative p-12 text-white max-w-xl">
                <blockquote class="space-y-2">
                    <h2 class="text-3xl font-extrabold leading-tight text-white">
                        {{ __('messages.login_hero_title') }}
                        <br>{{ __('messages.login_hero_subtitle') }}
                    </h2>
                    <p class="text-sm text-white/80 leading-relaxed">
                        {{ __('messages.login_hero_description') }}
                    </p>
                </blockquote>
            </div>
        </div>

    </div>

    <!-- Initialize Lucide Icons & Pass Toggle Script -->
    <script>
        document.getElementById('password-toggle')?.addEventListener('click', function () {
            const pwd = document.getElementById('password');
            const eye = this.querySelector('[data-password-eye]');
            const eyeOff = this.querySelector('[data-password-eye-off]');
            const willShow = pwd.type === 'password';

            pwd.type = willShow ? 'text' : 'password';
            eye?.classList.toggle('hidden', willShow);
            eyeOff?.classList.toggle('hidden', !willShow);
            this.setAttribute('aria-pressed', willShow ? 'true' : 'false');
            this.setAttribute('aria-label', willShow ? @json(__('messages.hide_password')) : @json(__('messages.show_password')));
            this.setAttribute('title', willShow ? @json(__('messages.hide_password')) : @json(__('messages.show_password')));
            pwd.focus();
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.site_name') . ' — ' . __('messages.home_title'))</title>
    <link rel="icon" type="image/png" href="{{ asset(config('company.icon')) }}">
    <link rel="apple-touch-icon" href="{{ asset(config('company.icon')) }}">
    
    <!-- Cairo Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <!-- Vite assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Dark Mode pre-render script to prevent light flash -->
    <script>
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
    
    <style>
        .quote-paper {
            font-family: 'Cairo', sans-serif;
        }

        .notification-pulse-dot {
            display: inline-block;
            width: 0.62rem;
            height: 0.62rem;
            margin-inline-end: 0.45rem;
            border-radius: 9999px;
            background: hsl(var(--accent));
            box-shadow: 0 0 0 0 hsl(var(--accent) / 0.55);
            vertical-align: middle;
            animation: notificationPulse 1.25s ease-out infinite;
        }

        .has-page-notification {
            background: hsl(var(--accent) / 0.08) !important;
            box-shadow: inset 3px 0 0 hsl(var(--accent) / 0.8);
        }

        html[dir="rtl"] .has-page-notification {
            box-shadow: inset -3px 0 0 hsl(var(--accent) / 0.8);
        }

        @keyframes notificationPulse {
            70% {
                box-shadow: 0 0 0 0.45rem hsl(var(--accent) / 0);
            }
            100% {
                box-shadow: 0 0 0 0 hsl(var(--accent) / 0);
            }
        }
    </style>
    @yield('head')
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col overflow-x-clip font-sans selection:bg-accent selection:text-accent-foreground">
    <div id="global-toast-container" style="position:fixed; right:20px; bottom:20px; z-index:1000000; display:flex; flex-direction:column; gap:8px; pointer-events:none;"></div>

    <script>
        (function () {
            function showGlobalToast(message, type = 'info', timeout = 4000) {
                const container = document.getElementById('global-toast-container');
                if (!container) return;

                const toast = document.createElement('div');
                toast.style.pointerEvents = 'auto';
                toast.style.minWidth = '220px';
                toast.style.maxWidth = '320px';
                toast.style.padding = '10px 12px';
                toast.style.borderRadius = '8px';
                toast.style.boxShadow = '0 6px 18px rgba(0,0,0,0.12)';
                toast.style.color = '#fff';
                toast.style.fontSize = '13px';
                toast.style.lineHeight = '1.2';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(8px)';
                toast.style.transition = 'opacity 200ms ease, transform 200ms ease';
                if (type === 'success') toast.style.background = '#16a34a';
                else if (type === 'error') toast.style.background = '#dc2626';
                else toast.style.background = '#374151';
                toast.textContent = message;
                container.appendChild(toast);

                requestAnimationFrame(() => {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                });

                const hide = () => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(8px)';
                    setTimeout(() => { try { container.removeChild(toast); } catch (e) {} }, 220);
                };
                const timer = setTimeout(hide, timeout);
                toast.addEventListener('click', () => { clearTimeout(timer); hide(); });
            }

            window.showGlobalToast = showGlobalToast;
            window.alert = function (message) {
                window.showGlobalToast(String(message || ''), 'info');
                return undefined;
            };
        })();
    </script>
    
    @php
        $activeNotifications = collect();
        $staffUser = auth('web')->user();

        if ($staffUser) {
            $activeNotificationQuery = \App\Models\Notification::query();
            \App\Support\NotificationVisibility::scopeUnreadFor($activeNotificationQuery, $staffUser);
            $activeNotifications = $activeNotificationQuery->latest()->get();
        }

        $currentNotificationTarget = null;

        if (request()->routeIs('dashboard')) {
            $currentNotificationTarget = 'general';
        } elseif (request()->routeIs('orders.*')) {
            $currentNotificationTarget = 'orders';
        } elseif (request()->routeIs('pricing.*') || request()->routeIs('shipping-rates.*')) {
            $currentNotificationTarget = 'pricing';
        } elseif (request()->routeIs('sales.*')) {
            $currentNotificationTarget = 'sales';
        } elseif (request()->routeIs('bookings.*') || request()->routeIs('schedules.*')) {
            $currentNotificationTarget = 'tracking';
        } elseif (request()->routeIs('accounts.*')) {
            $currentNotificationTarget = 'accounts';
        } elseif (request()->routeIs('customs.*')) {
            $currentNotificationTarget = 'customs';
        } elseif (request()->routeIs('insights.*')) {
            $currentNotificationTarget = 'insights';
        }

        $pageNotifications = $currentNotificationTarget
            ? $activeNotifications->where('target', $currentNotificationTarget)->values()
            : collect();
        $pageNotificationIds = $pageNotifications->pluck('id')->values()->all();
        $pageNotificationBookingIds = $pageNotifications->pluck('booking_id')->filter()->unique()->values()->all();
    @endphp

    <script>
        window.__pageUnreadNotificationTarget = @json($currentNotificationTarget);
        window.__pageUnreadNotificationIds = @json($pageNotificationIds);
        window.__pageUnreadBookingIds = @json($pageNotificationBookingIds);
        window.__pageNotificationReadUrl = @json(route('notifications.markPageRead'));
    </script>
    @include('partials.notification-runtime', [
        'notificationIdentity' => $staffUser ? 'staff:' . $staffUser->id : 'guest',
        'notificationFeedUrl' => $staffUser ? route('notifications.feed') : null,
        'notificationSnapshot' => $staffUser ? \App\Support\NotificationFeed::staff($staffUser, $activeNotifications) : null,
    ])

    @php
        $unreadCount = $activeNotifications->count();
        $notificationTargetCounts = $activeNotifications->groupBy('target')->map->count();
        $targetBadges = [
            'orders' => $notificationTargetCounts['orders'] ?? 0,
            'pricing' => $notificationTargetCounts['pricing'] ?? 0,
            'sales' => $notificationTargetCounts['sales'] ?? 0,
            'accounts' => $notificationTargetCounts['accounts'] ?? 0,
            'tracking' => $notificationTargetCounts['tracking'] ?? 0,
            'customs' => $notificationTargetCounts['customs'] ?? 0,
            'general' => $notificationTargetCounts['general'] ?? 0,
        ];
        $currentLocale = app()->getLocale() ?? 'en';
        $toggleLocale = $currentLocale === 'ar' ? 'en' : 'ar';
        $toggleLabel = $currentLocale === 'ar' ? 'EN' : 'AR';
        $toggleTitle = $currentLocale === 'ar' ? __('messages.switch_to_english') : __('messages.switch_to_arabic');
        
        $isDashboardRoute = $staffUser && (
            request()->routeIs('dashboard') ||
            request()->routeIs('targets.*') ||
            request()->routeIs('users.*') ||
            request()->routeIs('roles.*') ||
            request()->routeIs('audit-logs.*') ||
            request()->routeIs('insights.*') ||
            request()->routeIs('backups.*') ||
            request()->routeIs('notifications.*') ||
            request()->routeIs('orders.*') ||
            request()->routeIs('pricing.*') ||
            request()->routeIs('shipping-rates.*') ||
            request()->routeIs('sales.*') ||
            request()->routeIs('bookings.*') ||
            request()->routeIs('schedules.*') ||
            request()->routeIs('customs.*') ||
            request()->routeIs('accounts.*') ||
            request()->routeIs('ports.*') ||
            request()->routeIs('agents.*') ||
            request()->routeIs('customers.*') ||
            request()->routeIs('booking.create')
        );
    @endphp

    @if($isDashboardRoute)
        <!-- ========================================== -->
        <!-- DASHBOARD SIDEBAR LAYOUT -->
        <!-- ========================================== -->
        <div class="flex min-h-screen bg-secondary/30 relative">
            
            <!-- Sidebar Panel (Sticky on desktop, slide-over on mobile) -->
            <aside id="sidebar-panel" class="hidden lg:flex flex-col w-72 max-w-[calc(100vw-2rem)] bg-card border-r border-border/80 h-screen lg:sticky top-0 z-40 shrink-0 shadow-sm">
                <!-- Sidebar Header -->
                <div class="flex h-16 items-center justify-between px-6 border-b border-border/60 bg-card/90">
                    <a href="{{ route('home') }}" class="flex items-center gap-2">
                        <div class="leading-tight">
                            <div class="text-sm font-extrabold tracking-tight text-foreground">{{ __('messages.site_name') }}</div>
                        </div>
                    </a>
                    <button onclick="toggleSidebar()" class="lg:hidden h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center">
                        <i data-lucide="x" class="h-5 w-5"></i>
                    </button>
                </div>

                <!-- User Quick Info -->
                <div class="p-4 border-b border-border/40">
                    <div class="flex items-center gap-3 rounded-xl bg-secondary/40 p-3">
                        <div class="grid h-10 w-10 place-items-center rounded-full bg-gradient-accent text-accent-foreground text-sm font-bold">
                            {{ mb_substr($staffUser->full_name, 0, 1) }}
                        </div>
                        <div class="leading-tight overflow-hidden">
                            <div class="text-xs font-bold text-foreground truncate">{{ $staffUser->full_name }}</div>
                            <div class="text-[10px] text-muted-foreground font-mono mt-0.5 truncate">{{ $staffUser->username }}</div>
                            @if($staffUser->role)
                                <div class="inline-block text-[9px] font-bold text-accent bg-accent/15 px-1.5 py-0.5 rounded mt-1">
                                    {{ $staffUser->role->getDisplayName() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar Nav Menu -->
                <nav class="flex-1 overflow-y-auto p-4 space-y-5 text-xs">
                    <!-- Dashboard Group -->
                    <div class="space-y-1">
                        @if($staffUser->hasPermission('view_dashboard'))
                            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-bold hover:bg-secondary transition-all {{ request()->routeIs('dashboard') ? 'bg-gradient-accent text-accent-foreground shadow-sm' : 'text-foreground/80' }}">
                                <i data-lucide="layout-dashboard" class="h-4 w-4"></i>
                                <span>{{ __('messages.dashboard') }}</span>
                                @if($targetBadges['general'] > 0)
                                    <span class="inline-flex h-5 min-w-[1.3rem] items-center justify-center rounded-full bg-accent text-[10px] font-bold text-accent-foreground px-2">{{ $targetBadges['general'] }}</span>
                                @endif
                            </a>
                        @endif

                        @if($staffUser->hasPermission('view_targets'))
                            <a href="{{ route('targets.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-bold hover:bg-secondary transition-all {{ request()->routeIs('targets.*') ? 'bg-gradient-accent text-accent-foreground shadow-sm' : 'text-foreground/80' }}">
                                <i data-lucide="target" class="h-4 w-4"></i>
                                <span>{{ __('messages.targets_title') }}</span>
                            </a>
                        @endif

                        <a href="{{ route('notifications.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-bold hover:bg-secondary transition-all {{ request()->routeIs('notifications.*') ? 'bg-gradient-accent text-accent-foreground shadow-sm' : 'text-foreground/80' }}">
                            <i data-lucide="bell" class="h-4 w-4"></i>
                            <span>{{ __('messages.notifications_center') }}</span>
                            @if($unreadCount > 0)
                                <span class="inline-flex h-5 min-w-[1.3rem] items-center justify-center rounded-full bg-accent text-[10px] font-bold text-accent-foreground px-2">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    </div>

                    <!-- Workflow Group -->
                    <div class="space-y-1.5">
                        <div class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-muted-foreground/85">{{ __('messages.operations') }}</div>
                        
                        @if($staffUser->hasPermission('manage_bookings'))
                            <a href="{{ route('orders.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('orders.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="file-text" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.orders_and_shipments') }}</span>
                                @if($targetBadges['orders'] > 0)
                                    <span class="inline-flex h-5 min-w-[1.3rem] items-center justify-center rounded-full bg-accent text-[10px] font-bold text-accent-foreground px-2">{{ $targetBadges['orders'] }}</span>
                                @endif
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_pricing'))
                            <a href="{{ route('pricing.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('pricing.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="dollar-sign" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.pricing_desk') }}</span>
                                @if($targetBadges['pricing'] > 0)
                                    <span class="inline-flex h-5 min-w-[1.3rem] items-center justify-center rounded-full bg-accent text-[10px] font-bold text-accent-foreground px-2">{{ $targetBadges['pricing'] }}</span>
                                @endif
                            </a>
                        @endif

                        @if($staffUser->hasPermission('view_shipping_rates_operations'))
                            <a href="{{ route('shipping-rates.operations') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('shipping-rates.operations') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="ship-wheel" class="h-4 w-4 text-muted-foreground"></i>
                                <span>أسعار الشحن</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_sales'))
                            <a href="{{ route('sales.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('sales.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="trending-up" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.sales_desk') }}</span>
                                @if($targetBadges['sales'] > 0)
                                    <span class="inline-flex h-5 min-w-[1.3rem] items-center justify-center rounded-full bg-accent text-[10px] font-bold text-accent-foreground px-2">{{ $targetBadges['sales'] }}</span>
                                @endif
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_carriers'))
                            <a href="{{ route('bookings.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('bookings.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="ship" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.carrier_bookings') }}</span>
                                @if($targetBadges['tracking'] > 0)
                                    <span class="inline-flex h-5 min-w-[1.3rem] items-center justify-center rounded-full bg-accent text-[10px] font-bold text-accent-foreground px-2">{{ $targetBadges['tracking'] }}</span>
                                @endif
                            </a>
                            <a href="{{ route('schedules.manage') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('schedules.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="calendar-days" class="h-4 w-4 text-muted-foreground"></i>
                                <span>إدارة جداول الرحلات</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_customs'))
                            <a href="{{ route('customs.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('customs.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="clipboard-check" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.customs_clearance') }}</span>
                                @if($targetBadges['customs'] > 0)
                                    <span class="inline-flex h-5 min-w-[1.3rem] items-center justify-center rounded-full bg-accent text-[10px] font-bold text-accent-foreground px-2">{{ $targetBadges['customs'] }}</span>
                                @endif
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_accounts'))
                            <a href="{{ route('accounts.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('accounts.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="credit-card" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.accounts_and_invoices') }}</span>
                                @if($targetBadges['accounts'] > 0)
                                    <span class="inline-flex h-5 min-w-[1.3rem] items-center justify-center rounded-full bg-accent text-[10px] font-bold text-accent-foreground px-2">{{ $targetBadges['accounts'] }}</span>
                                @endif
                            </a>
                        @endif
                    </div>

                    <!-- Administration Group -->
                    <div class="space-y-1.5">
                        <div class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-muted-foreground/85">{{ __('messages.system_management') }}</div>

                        @if($staffUser->hasPermission('manage_users'))
                            <a href="{{ route('users.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('users.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="user-round-cog" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.staff_and_users') }}</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_customers'))
                            <a href="{{ route('customers.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('customers.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="users" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.customers') }}</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_roles'))
                            <a href="{{ route('roles.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('roles.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="shield" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.roles_and_permissions') }}</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_ports'))
                            <a href="{{ route('ports.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('ports.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="anchor" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.manage_ports') }}</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_shipping_rates'))
                            <a href="{{ route('shipping-rates.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('shipping-rates.*') && !request()->routeIs('shipping-rates.operations') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="settings" class="h-4 w-4 text-muted-foreground"></i>
                                <span>إدارة أسعار الشحن</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_agents'))
                            <a href="{{ route('agents.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('agents.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="globe" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.manage_agents') }}</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('view_audit_logs'))
                            <a href="{{ route('audit-logs.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('audit-logs.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="activity" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.audit_logs') }}</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_insights'))
                            <a href="{{ route('insights.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('insights.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="brain" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.insights') }}</span>
                            </a>
                        @endif

                        @if($staffUser->hasPermission('manage_backups'))
                            <a href="{{ route('backups.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2.5 font-semibold hover:bg-secondary transition-all {{ request()->routeIs('backups.*') ? 'bg-secondary text-foreground font-bold border-r-2 border-accent' : 'text-foreground/80' }}">
                                <i data-lucide="database-backup" class="h-4 w-4 text-muted-foreground"></i>
                                <span>{{ __('messages.backups') }}</span>
                            </a>
                        @endif
                    </div>
                </nav>

                <!-- Sidebar Footer -->
                <div class="p-4 border-t border-border/60">
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full flex items-center justify-center gap-2 rounded-lg bg-destructive/10 text-destructive hover:bg-destructive/15 py-2.5 font-bold transition-all">
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                            <span>{{ __('messages.logout') }}</span>
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main Panel Frame -->
            <div class="flex-1 flex flex-col min-w-0">
                <!-- Top Header in Dashboard -->
                <header dir="ltr" class="flex h-16 items-center justify-between gap-3 border-b border-border/60 bg-card/90 backdrop-blur-xl px-3 sm:px-6 sticky top-0 z-30">
                    <div dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" class="flex min-w-0 items-center gap-3">
                        <button onclick="toggleSidebar()" class="lg:hidden text-foreground p-1 hover:bg-secondary rounded-lg">
                            <i data-lucide="menu" class="h-6 w-6"></i>
                        </button>
                        <h2 class="truncate text-sm font-bold text-foreground">
                            @yield('title')
                        </h2>
                    </div>

                    <div class="flex shrink-0 items-center gap-2 sm:gap-3 z-40 relative">
                        <form method="GET" action="{{ route('language.switch', ['locale' => $toggleLocale]) }}" class="inline-flex">
                            <input type="hidden" name="redirect_to" value="{{ request()->fullUrlWithoutQuery('locale') }}">
                            <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg border border-border/60 bg-card hover:bg-secondary px-3 text-xs font-bold text-foreground transition-all cursor-pointer no-underline" title="{{ $toggleTitle }}" style="text-decoration: none !important; position: relative; z-index: 40;">
                                <i data-lucide="languages" class="h-4 w-4"></i>
                                <span>{{ $currentLocale === 'ar' ? 'EN' : 'AR' }}</span>
                            </button>
                        </form>

                        <!-- Notifications Bell -->
                        @include('partials.notification-sound-button')
                        <div class="relative">
                            <button id="notification-bell-btn" class="relative grid h-9 w-9 place-items-center rounded-lg border border-border bg-card text-muted-foreground transition hover:text-foreground">
                                <i data-lucide="bell" class="h-4 w-4"></i>
                                    <span id="notification-badge" @if($unreadCount === 0) hidden @endif class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-accent text-[9px] font-bold text-accent-foreground animate-pulse">
                                        {{ $unreadCount }}
                                    </span>
                            </button>
                            
                            <!-- Dropdown -->
                            <div id="notification-dropdown" class="hidden absolute right-0 mt-2 w-80 max-w-[calc(100vw-1.5rem)] rounded-xl border border-border bg-card p-4 shadow-elegant z-50">
                                <div class="flex items-center justify-between border-b pb-2">
                                    <span class="font-bold text-sm">{{ __('messages.notifications') }}</span>
                                        <button id="mark-all-read-btn" @if($unreadCount === 0) hidden @endif class="text-xs text-accent font-semibold hover:underline">{{ __('messages.mark_all_read') }}</button>
                                </div>
                                <ul id="notification-list" class="mt-2 max-h-60 overflow-y-auto divide-y divide-border/60">
                                    @forelse($activeNotifications as $notif)
                                        <li class="py-2.5 hover:bg-secondary/20 rounded px-1 transition text-xs">
                                            <div class="font-medium text-foreground">{{ $notif->message }}</div>
                                            <div class="mt-1 text-[10px] text-muted-foreground">{{ $notif->created_at->diffForHumans() }}</div>
                                        </li>
                                    @empty
                                        <li class="py-6 text-center text-muted-foreground text-xs">{{ __('messages.no_notifications') }}</li>
                                    @endforelse
                                </ul>
                                <a href="{{ route('notifications.index') }}" class="mt-3 inline-flex h-8 w-full items-center justify-center gap-2 rounded-lg bg-secondary px-3 text-xs font-bold hover:bg-secondary/70">
                                    <i data-lucide="list" class="h-3.5 w-3.5"></i>
                                    {{ __('messages.view_all_notifications') }}
                                </a>
                            </div>
                        </div>

                        <!-- User Profile Circle/Name Shortcut -->
                        <div class="flex items-center gap-2 sm:pr-3">
                            <span class="text-xs text-muted-foreground hidden xl:block max-w-44 truncate font-bold">{{ __('messages.welcome') }}, {{ $staffUser->full_name }}</span>
                            <button type="button" class="theme-toggle-btn inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card text-foreground hover:bg-secondary transition-all" title="{{ __('messages.change_theme') }}">
                                <i data-lucide="sun" class="hidden dark:block h-4 w-4"></i>
                                <i data-lucide="moon" class="block dark:hidden h-4 w-4"></i>
                            </button>
                            <a href="{{ route('home') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-border bg-card px-3 text-xs font-semibold hover:bg-secondary transition-all text-foreground" title="{{ __('messages.marketing_site') }}">
                                <i data-lucide="globe" class="h-4 w-4"></i>
                            </a>
                        </div>
                    </div>
                </header>

                <!-- Page Content Area -->
                <main class="workspace-surfaces flex-1 p-4 sm:p-6">
                    @if(session('success'))
                        <div class="mb-4">
                            <div class="flex items-center gap-3 rounded-xl border border-success/30 bg-success/10 p-4 text-sm text-foreground">
                                <i data-lucide="check-circle-2" class="h-5 w-5 text-success"></i>
                                <div>{{ session('success') }}</div>
                            </div>
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="mb-4">
                            <div class="flex items-center gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-foreground">
                                <i data-lucide="x-circle" class="h-5 w-5 text-destructive"></i>
                                <div>{{ session('error') }}</div>
                            </div>
                        </div>
                    @endif

                    @yield('content')
                </main>
            </div>
            
            <!-- Mobile Sidebar Backdrop overlay -->
            <div id="sidebar-backdrop" onclick="toggleSidebar()" class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-30 lg:hidden"></div>
        </div>

    @else
        <!-- ========================================== -->
        <!-- PUBLIC MARKETING WEBSITE LAYOUT -->
        <!-- ========================================== -->
        
        <!-- Header -->
        <header class="sticky top-0 z-50 border-b border-border/60 bg-background/85 backdrop-blur-xl">
            <div class="container mx-auto flex h-16 min-w-0 items-center justify-between gap-3 px-4">
                <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2">
                    <div class="min-w-0 leading-tight">
                        <div class="truncate text-base font-extrabold tracking-tight text-foreground">{{ __('messages.site_name') }}</div>
                    </div>
                </a>
                
                <nav class="hidden items-center gap-1 lg:flex text-sm font-medium">
                    @php
                        $navItems = [
                            ['route' => 'home', 'label' => ($currentLocale === 'ar' ? 'بوابة النظام' : 'Portal')],
                            ['route' => 'tracking', 'label' => __('messages.tracking')],
                            ['route' => 'schedules', 'label' => __('messages.schedules')],
                        ];
                    @endphp
                    @foreach($navItems as $item)
                        @php
                            $isActive = request()->routeIs($item['route']);
                        @endphp
                        <a href="{{ route($item['route']) }}" class="rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-secondary hover:text-foreground {{ $isActive ? 'bg-secondary text-foreground font-bold' : 'text-foreground/80' }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    @if($staffUser)
                        <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-2 text-sm font-bold text-accent transition-colors hover:bg-secondary">{{ __('messages.dashboard') }} &larr;</a>
                    @endif
                </nav>
                
                <div class="hidden items-center gap-4 lg:flex">
                    <form method="GET" action="{{ route('language.switch', ['locale' => $toggleLocale]) }}" class="inline-flex">
                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrlWithoutQuery('locale') }}">
                        <button type="submit" class="inline-flex h-9 items-center gap-2 rounded-lg border border-border/60 bg-card hover:bg-secondary px-3 text-xs font-bold text-foreground transition-all cursor-pointer no-underline" title="{{ $toggleTitle }}" style="text-decoration: none !important;">
                            <i data-lucide="languages" class="h-4 w-4"></i>
                            <span>{{ $currentLocale === 'ar' ? 'EN' : 'AR' }}</span>
                        </button>
                    </form>

                    <button type="button" class="theme-toggle-btn inline-flex h-9 w-9 items-center justify-center rounded-lg border border-border bg-card text-foreground hover:bg-secondary transition-all" title="{{ __('messages.change_theme') }}">
                        <i data-lucide="sun" class="hidden dark:block h-4 w-4"></i>
                        <i data-lucide="moon" class="block dark:hidden h-4 w-4"></i>
                    </button>

                    <a href="{{ auth('customer')->check() ? route('customer.shipments.create') : route('customer.login') }}" class="inline-flex h-9 items-center justify-center rounded-lg bg-gradient-accent px-4 text-xs font-bold text-accent-foreground shadow-glow hover:opacity-95 transition-all">
                        {{ __('messages.instant_quote') }}
                    </a>

                    @if(auth('customer')->check())
                        <a href="{{ route('customer.dashboard') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-accent/40 bg-accent/10 px-4 text-xs font-bold text-accent hover:bg-accent/15 transition-all">
                            {{ __('messages.customer_portal') }}
                        </a>
                    @elseif(! $staffUser)
                        <a href="{{ route('customer.login') }}" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-xs font-bold hover:bg-secondary transition-all">
                            <i data-lucide="user-round-check" class="h-3.5 w-3.5"></i>
                            {{ __('messages.login_or_create_account') }}
                        </a>
                    @endif

                    @if($staffUser)
                        <!-- User Dropdown in top header (if logged in on public pages) -->
                        <div class="relative">
                            <button id="user-menu-btn" class="flex items-center gap-2 rounded-lg border border-border bg-card px-3 py-1.5 text-xs font-semibold hover:bg-secondary transition-all">
                                <div class="grid h-6 w-6 place-items-center rounded-full bg-gradient-accent text-accent-foreground text-[10px] font-bold">
                                    {{ mb_substr($staffUser->full_name, 0, 1) }}
                                </div>
                                <span class="hidden md:block">{{ $staffUser->full_name }}</span>
                                <i data-lucide="chevron-down" class="h-3 w-3"></i>
                            </button>
                            <div id="user-menu-dropdown" class="hidden absolute left-0 mt-2 w-52 rounded-xl border border-border bg-card p-2 shadow-elegant z-50">
                                <div class="border-b pb-2 mb-2 px-2">
                                    <div class="text-xs font-bold">{{ $staffUser->full_name }}</div>
                                    <div class="text-[10px] font-mono text-muted-foreground">{{ $staffUser->username }}</div>
                                </div>
                                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-xs hover:bg-secondary transition-all">
                                    <i data-lucide="layout-dashboard" class="h-3.5 w-3.5 text-muted-foreground"></i> {{ __('messages.admin_dashboard') }}
                                </a>
                                <div class="border-t mt-2 pt-2">
                                    <form action="{{ route('logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center gap-2 rounded-lg px-2 py-1.5 text-xs text-destructive hover:bg-destructive/10 transition-all">
                                            <i data-lucide="log-out" class="h-3.5 w-3.5"></i> {{ __('messages.logout') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Hamburger Button -->
                <button id="mobile-menu-btn" class="lg:hidden text-foreground hover:opacity-85" aria-label="{{ __('messages.mobile_menu') }}">
                    <i data-lucide="menu" id="menu-icon" class="h-6 w-6"></i>
                </button>
            </div>

            <!-- Mobile Drawer -->
            <div id="mobile-drawer" class="hidden border-t border-border bg-background lg:hidden">
                <div class="container mx-auto flex flex-col px-4 py-3 gap-1">
                    @foreach($navItems as $item)
                        <a href="{{ route($item['route']) }}" class="rounded-md px-3 py-2 text-sm font-medium text-foreground/80 hover:bg-secondary hover:text-foreground">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                    @if($staffUser)
                        <a href="{{ route('dashboard') }}" class="rounded-md px-3 py-2 text-sm font-bold text-accent">{{ __('messages.admin_dashboard') }}</a>
                    @endif
                    <a href="{{ auth('customer')->check() ? route('customer.shipments.create') : route('customer.login') }}" class="mt-2 inline-flex h-10 items-center justify-center rounded-lg bg-gradient-accent px-4 text-sm font-bold text-accent-foreground">
                        {{ __('messages.instant_quote') }}
                    </a>
                    @if(auth('customer')->check())
                        <a href="{{ route('customer.dashboard') }}" class="mt-2 inline-flex h-10 items-center justify-center rounded-lg border border-accent/40 bg-accent/10 px-4 text-sm font-bold text-accent">
                            {{ __('messages.customer_portal') }}
                        </a>
                    @elseif(! $staffUser)
                        <a href="{{ route('customer.login') }}" class="mt-2 inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card px-4 text-sm font-bold">
                            <i data-lucide="user-round-check" class="h-4 w-4"></i>
                            {{ __('messages.login_or_create_account') }}
                        </a>
                    @endif
                    <form method="GET" action="{{ route('language.switch', ['locale' => $toggleLocale]) }}" class="mt-2 inline-flex">
                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrlWithoutQuery('locale') }}">
                        <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border/60 bg-card hover:bg-secondary px-4 text-sm font-bold text-foreground transition-all cursor-pointer no-underline" style="text-decoration: none !important;">
                            <i data-lucide="languages" class="h-4 w-4"></i>
                            <span>{{ $currentLocale === 'ar' ? __('messages.switch_to_english') : __('messages.switch_to_arabic') }}</span>
                        </button>
                    </form>
                    <button type="button" class="theme-toggle-btn mt-2 inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-border bg-card text-foreground hover:bg-secondary transition-all">
                        <i data-lucide="sun" class="hidden dark:block h-4 w-4"></i>
                        <i data-lucide="moon" class="block dark:hidden h-4 w-4"></i>
                        <span>{{ __('messages.change_theme') }} ({{ __('messages.light') }} / {{ __('messages.dark') }})</span>
                    </button>
                </div>
            </div>
        </header>
        
        <!-- Main Content -->
        <main class="flex-1">
            @if(session('success'))
                <div class="container mx-auto px-4 mt-4">
                    <div class="flex items-center gap-3 rounded-xl border border-success/30 bg-success/10 p-4 text-sm text-foreground">
                        <i data-lucide="check-circle-2" class="h-5 w-5 text-success"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                </div>
            @endif
            
            @if(session('error'))
                <div class="container mx-auto px-4 mt-4">
                    <div class="flex items-center gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm text-foreground">
                        <i data-lucide="x-circle" class="h-5 w-5 text-destructive"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
        
        <!-- Enterprise System Footer -->
        <footer class="border-t border-border/80 bg-card/60 py-6 text-xs text-muted-foreground backdrop-blur-sm">
            <div class="container mx-auto flex flex-col items-center justify-between gap-3 px-4 sm:flex-row">
                <div class="flex items-center gap-2 font-bold text-foreground">
                    <span>{{ config('company.name.' . app()->getLocale()) }}</span>
                </div>
                <div>
                    &copy; {{ date('Y') }} {{ config('company.name.' . app()->getLocale()) }}. {{ __('messages.all_rights_reserved') ?? 'All rights reserved.' }}
                </div>
            </div>
        </footer>
    @endif
    
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            
            // Notification Dropdown Toggle
            const bellBtn = document.getElementById('notification-bell-btn');
            const dropdown = document.getElementById('notification-dropdown');
            const markAllBtn = document.getElementById('mark-all-read-btn');
            const badge = document.getElementById('notification-badge');
            const list = document.getElementById('notification-list');
            
            if (bellBtn && dropdown) {
                bellBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    dropdown.classList.toggle('hidden');
                });
                
                document.addEventListener('click', () => {
                    dropdown.classList.add('hidden');
                });
                
                dropdown.addEventListener('click', (e) => {
                    e.stopPropagation();
                });
            }
            
            if (markAllBtn) {
                markAllBtn.addEventListener('click', () => {
                    fetch('{{ route('notifications.markAllRead') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            if (badge) badge.hidden = true;
                            if (list) {
                                list.innerHTML = '<li class="py-6 text-center text-muted-foreground text-xs">{{ __('messages.no_new_notifications') }}</li>';
                            }
                            markAllBtn.hidden = true;
                            window.dispatchEvent(new Event('notifications:refresh'));
                        }
                    });
                });
            }

            const pageNotificationTarget = window.__pageUnreadNotificationTarget;
            const pageNotificationIds = Array.isArray(window.__pageUnreadNotificationIds)
                ? window.__pageUnreadNotificationIds
                : [];
            const pageBookingIds = Array.isArray(window.__pageUnreadBookingIds)
                ? window.__pageUnreadBookingIds
                : [];
            let pageNotificationsMarked = false;

            function escapeAttributeValue(value) {
                return String(value).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            }

            function addNotificationDot(element) {
                if (!element || element.dataset.notificationDotReady === '1') return;

                const anchor = element.tagName === 'TR' ? element.querySelector('td') : element;
                if (!anchor) return;

                const dot = document.createElement('span');
                dot.className = 'notification-pulse-dot';
                dot.setAttribute('aria-label', '{{ __('messages.new_notification') }}');
                dot.setAttribute('title', '{{ __('messages.new_notification') }}');
                anchor.prepend(dot);
                element.classList.add('has-page-notification');
                element.dataset.notificationDotReady = '1';
            }

            pageBookingIds.forEach((bookingId) => {
                document
                    .querySelectorAll(`[data-booking-id="${escapeAttributeValue(bookingId)}"]`)
                    .forEach(addNotificationDot);
            });

            function markPageNotificationsRead() {
                if (pageNotificationsMarked || !pageNotificationTarget || pageNotificationIds.length === 0) return;

                pageNotificationsMarked = true;
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const readUrl = window.__pageNotificationReadUrl;
                if (!csrfToken || !readUrl) return;

                const formData = new FormData();
                formData.append('_token', csrfToken);
                formData.append('target', pageNotificationTarget);
                pageNotificationIds.forEach((notificationId) => {
                    formData.append('notification_ids[]', notificationId);
                });

                if (navigator.sendBeacon && navigator.sendBeacon(readUrl, formData)) {
                    return;
                }

                fetch(readUrl, {
                    method: 'POST',
                    keepalive: true,
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        target: pageNotificationTarget,
                        notification_ids: pageNotificationIds,
                    }),
                }).catch(() => {});
            }

            window.addEventListener('pagehide', markPageNotificationsRead);
            window.addEventListener('beforeunload', markPageNotificationsRead);

            // User Dropdown in Public Header (if exists)
            const userMenuBtn = document.getElementById('user-menu-btn');
            const userMenuDropdown = document.getElementById('user-menu-dropdown');
            if (userMenuBtn && userMenuDropdown) {
                userMenuBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    userMenuDropdown.classList.toggle('hidden');
                    if (dropdown) dropdown.classList.add('hidden');
                });
                userMenuDropdown.addEventListener('click', (e) => e.stopPropagation());
            }
            document.addEventListener('click', () => {
                if (userMenuDropdown) userMenuDropdown.classList.add('hidden');
            });

            // Mobile Menu Drawer Toggle in Public Page
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileDrawer = document.getElementById('mobile-drawer');
            const menuIcon = document.getElementById('menu-icon');
            
            if (mobileMenuBtn && mobileDrawer) {
                mobileMenuBtn.addEventListener('click', () => {
                    mobileDrawer.classList.toggle('hidden');
                    const isHidden = mobileDrawer.classList.contains('hidden');
                    if (menuIcon) {
                        menuIcon.setAttribute('data-lucide', isHidden ? 'menu' : 'x');
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }
                });
            }
        });

        // Sidebar Panel Toggle function for Mobile
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar-panel');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            if (sidebar && backdrop) {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('flex');
                sidebar.classList.toggle('fixed');
                sidebar.classList.toggle('inset-y-0');
                sidebar.classList.toggle('right-0');
                backdrop.classList.toggle('hidden');
            }
        }
    </script>
    @yield('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('messages.customer_portal'))</title>
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
    @yield('head')
</head>
<body class="customer-portal min-h-screen bg-secondary/30 text-foreground font-sans selection:bg-accent selection:text-accent-foreground">
    <a href="#customer-content" class="portal-skip">{{ __('portal.skip') }}</a>
    @include('customer.partials.ui-strings')
    @php
        $customer = auth('customer')->user();
        $currentLocale = app()->getLocale();
        $toggleLocale = $currentLocale === 'ar' ? 'en' : 'ar';
        $toggleTitle = $currentLocale === 'ar' ? __('messages.switch_to_english') : __('messages.switch_to_arabic');
        $customerAvatarUrl = $customer?->avatar_path ? asset('storage/' . $customer->avatar_path) : null;
        $customerInitial = mb_substr($customer?->company ?: __('messages.company_initial'), 0, 1);
        $customerNotificationQuery = \App\Models\Notification::query()
            ->where('target', 'customer')
            ->whereIn('booking_id', \App\Models\Booking::query()
                ->where('customer_id', $customer?->id)
                ->select('id'))
            ->whereNotIn('id', function ($query) use ($customer) {
                $query->select('notification_id')
                    ->from('customer_notification_reads')
                    ->where('customer_id', $customer?->id);
            });
        $allCustomerNotifications = $customerNotificationQuery->latest()->get();
        $unreadCustomerCount = $allCustomerNotifications->count();
        $customerHeaderNotifications = $allCustomerNotifications->take(8);
        $customerNavGroups = [
            [
                'label' => __('messages.customer_center'),
                'items' => [
                    [
                        'label' => __('messages.overview'),
                        'icon' => 'layout-dashboard',
                        'href' => route('customer.dashboard'),
                        'active' => request()->routeIs('customer.dashboard'),
                        'badge' => $unreadCustomerCount,
                    ],
                    [
                        'label' => __('portal.profile'),
                        'icon' => 'user-round-cog',
                        'href' => route('customer.profile'),
                        'active' => request()->routeIs('customer.profile'),
                    ],
                    [
                        'label' => __('messages.my_shipments'),
                        'icon' => 'ship',
                        'href' => route('customer.shipments.index'),
                        'active' => request()->routeIs('customer.shipments.index') || request()->routeIs('customer.shipments.show'),
                    ],
                    [
                        'label' => __('messages.request_new_shipment'),
                        'icon' => 'package-plus',
                        'href' => route('customer.shipments.create'),
                        'active' => request()->routeIs('customer.shipments.create'),
                    ],
                    [
                        'label' => __('messages.shipment_tracking_nav'),
                        'icon' => 'radar',
                        'href' => route('customer.tracking'),
                        'active' => request()->routeIs('customer.tracking'),
                    ],
                    [
                        'label' => __('messages.notifications_center'),
                        'icon' => 'bell-ring',
                        'href' => route('customer.notifications.index'),
                        'active' => request()->routeIs('customer.notifications.index'),
                        'badge' => $unreadCustomerCount,
                    ],
                ],
            ],
            [
                'label' => __('messages.operations'),
                'items' => [
                    [
                        'label' => __('messages.order_confirmation'),
                        'icon' => 'clipboard-check',
                        'href' => route('customer.order-confirmation'),
                        'active' => request()->routeIs('customer.order-confirmation'),
                    ],
                    [
                        'label' => __('messages.offers_and_decisions'),
                        'icon' => 'badge-dollar-sign',
                        'href' => route('customer.offers'),
                        'active' => request()->routeIs('customer.offers'),
                    ],
                    [
                        'label' => __('messages.shipping_rates'),
                        'icon' => 'circle-dollar-sign',
                        'href' => route('customer.shipping-rates'),
                        'active' => request()->routeIs('customer.shipping-rates'),
                    ],
                    [
                        'label' => __('messages.documents'),
                        'icon' => 'folder-open',
                        'href' => route('customer.documents'),
                        'active' => request()->routeIs('customer.documents'),
                    ],
                    [
                        'label' => __('messages.invoices'),
                        'icon' => 'receipt-text',
                        'href' => route('customer.invoices'),
                        'active' => request()->routeIs('customer.invoices'),
                    ],
                    [
                        'label' => __('messages.customs_clearance'),
                        'icon' => 'shield-check',
                        'href' => route('customer.customs'),
                        'active' => request()->routeIs('customer.customs'),
                    ],
                ],
            ],
        ];
    @endphp

    <div class="relative flex min-h-screen bg-secondary/30">
        <aside id="customer-sidebar" aria-label="{{ __('portal.navigation') }}" class="portal-sidebar flex h-screen max-w-[calc(100vw-2rem)] shrink-0 flex-col bg-card shadow-sm lg:sticky lg:top-0 z-50">
            <div class="flex h-16 items-center justify-between border-b border-border/60 px-5">
                <a href="{{ route('customer.dashboard') }}" class="flex min-w-0 items-center gap-2">
                    <img src="{{ asset(config('company.logo')) }}" alt="{{ __('messages.company_logo_alt') }}" class="h-9 w-24 rounded-lg border border-border bg-white object-contain p-1 shadow-sm">
                    <div class="min-w-0 leading-tight">
                        <div class="truncate text-sm font-extrabold text-foreground">{{ __('messages.customer_portal') }}</div>
                        <div class="truncate text-[9px] uppercase tracking-[0.18em] text-muted-foreground">Customer Operations</div>
                    </div>
                </a>
                <button type="button" onclick="toggleCustomerSidebar()" class="grid h-8 w-8 place-items-center rounded-lg hover:bg-secondary/50 lg:hidden" aria-label="{{ __('messages.close_menu') }}">
                    <i data-lucide="x" class="h-5 w-5"></i>
                </button>
            </div>

            <div class="border-b border-border/50 p-4">
                <div class="flex items-center gap-3 rounded-xl bg-secondary/45 p-3">
                    @if($customerAvatarUrl)
                        <img src="{{ $customerAvatarUrl }}" alt="{{ __('portal.avatar') }}" class="h-11 w-11 shrink-0 rounded-full border border-border object-cover shadow-sm">
                    @else
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-gradient-accent text-sm font-black text-accent-foreground">
                            {{ $customerInitial }}
                        </div>
                    @endif
                    <div class="min-w-0 leading-tight">
                        <div class="truncate text-xs font-black">{{ $customer?->company }}</div>
                        <div class="mt-0.5 truncate text-[10px] text-muted-foreground">{{ $customer?->contact_name ?: $customer?->email }}</div>
                        <div class="mt-1 inline-flex rounded-md bg-accent/10 px-2 py-0.5 text-[9px] font-black text-accent">{{ __('messages.customer_account') }}</div>
                    </div>
                </div>
            </div>

            <nav aria-label="{{ __('portal.navigation') }}" class="flex-1 space-y-5 overflow-y-auto p-4 text-xs">
                @foreach($customerNavGroups as $group)
                    <div class="space-y-1.5">
                        <div class="px-3 text-[10px] font-extrabold uppercase tracking-wider text-muted-foreground/85">{{ $group['label'] }}</div>
                        @foreach($group['items'] as $item)
                            <a href="{{ $item['href'] }}" @if($item['active']) aria-current="page" @endif class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition-colors {{ $item['active'] ? 'bg-gradient-accent font-black text-accent-foreground shadow-sm' : 'font-semibold text-foreground/80 hover:bg-secondary' }}">
                                <i data-lucide="{{ $item['icon'] }}" class="h-4 w-4 {{ $item['active'] ? '' : 'text-muted-foreground' }}"></i>
                                <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                @if(($item['badge'] ?? 0) > 0)
                                    <span class="inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-accent px-1.5 text-[10px] font-black text-accent-foreground">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                @endforeach
            </nav>

            <div class="space-y-3 border-t border-border/60 p-4">
                <a href="{{ route('home') }}" class="flex items-center justify-center gap-2 rounded-lg border border-border bg-background py-2.5 text-xs font-bold hover:bg-secondary">
                    <i data-lucide="globe" class="h-4 w-4"></i>
                    {{ __('messages.main_site') }}
                </a>
                <form action="{{ route('customer.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg bg-destructive/10 py-2.5 text-xs font-black text-destructive transition hover:bg-destructive/15">
                        <i data-lucide="log-out" class="h-4 w-4"></i>
                        {{ __('messages.logout') }}
                    </button>
                </form>
            </div>
        </aside>

        <div id="customer-sidebar-backdrop" onclick="toggleCustomerSidebar()" hidden class="fixed inset-0 z-40 bg-black/40 lg:hidden"></div>

        <div id="customer-shell" class="flex min-w-0 flex-1 flex-col">
            <header class="portal-header sticky top-0 z-30 flex h-16 items-center justify-between gap-3 border-b border-border/60 px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-3">
                    <button id="customer-menu-toggle" type="button" onclick="toggleCustomerSidebar()" aria-controls="customer-sidebar" aria-expanded="false" class="grid h-10 w-10 shrink-0 place-items-center rounded-lg hover:bg-secondary lg:hidden" aria-label="{{ __('messages.open_menu') }}">
                        <i data-lucide="menu" class="h-5 w-5"></i>
                    </button>
                    <div class="min-w-0">
                        <div class="truncate text-sm font-black">@yield('title', __('messages.customer_portal'))</div>
                        <div class="hidden text-[10px] text-muted-foreground sm:block">{{ __('portal.overview_hint') }}</div>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <form method="GET" action="{{ route('language.switch', ['locale' => $toggleLocale]) }}" class="inline-flex">
                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrlWithoutQuery('locale') }}">
                        <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg border border-border bg-card px-3 text-xs font-black text-foreground transition hover:bg-secondary" title="{{ $toggleTitle }}">
                            <i data-lucide="languages" class="h-4 w-4"></i>
                            <span class="hidden sm:inline">{{ $currentLocale === 'ar' ? 'EN' : 'AR' }}</span>
                        </button>
                    </form>

                    @include('partials.notification-sound-button')
                    <div class="relative">
                        <button id="customer-notification-bell-btn" type="button" aria-label="{{ __('messages.notifications') }}" aria-controls="customer-notification-dropdown" aria-expanded="false" class="relative grid h-10 w-10 place-items-center rounded-lg border border-border bg-card text-muted-foreground transition hover:bg-secondary hover:text-foreground" title="{{ __('messages.notifications') }}">
                            <i data-lucide="bell" class="h-4 w-4"></i>
                                <span id="customer-notification-badge" @if($unreadCustomerCount === 0) hidden @endif class="absolute -top-1 -right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-accent px-1 text-[9px] font-black text-accent-foreground">
                                    {{ $unreadCustomerCount > 9 ? '9+' : $unreadCustomerCount }}
                                </span>
                        </button>

                        <div id="customer-notification-dropdown" class="absolute end-0 z-50 mt-2 hidden w-80 max-w-[calc(100vw-1.5rem)] rounded-xl border border-border bg-card p-4 shadow-card">
                            <div class="flex items-center justify-between gap-3 border-b border-border pb-2">
                                <div class="flex items-center gap-2 text-sm font-black">
                                    <i data-lucide="bell-ring" class="h-4 w-4 text-accent"></i>
                                    {{ __('messages.notifications') }}
                                </div>
                                    <button id="customer-mark-all-read-btn" @if($unreadCustomerCount === 0) hidden @endif data-url="{{ route('customer.notifications.read') }}" type="button" class="text-[11px] font-black text-accent hover:underline">{{ __('messages.mark_all_read') }}</button>
                            </div>
                            <ul id="customer-notification-list" class="mt-2 max-h-72 overflow-y-auto divide-y divide-border/60">
                                @forelse($customerHeaderNotifications as $notification)
                                    <li class="py-2.5">
                                        <a href="{{ route('customer.shipments.show', $notification->booking_id) }}" class="block rounded-lg px-2 py-1.5 text-xs transition hover:bg-secondary/40">
                                            <div class="font-bold leading-5 text-foreground">{{ $notification->message }}</div>
                                            <div class="mt-1 text-[10px] text-muted-foreground">{{ $notification->created_at->diffForHumans() }}</div>
                                        </a>
                                    </li>
                                @empty
                                    <li class="py-7 text-center text-xs text-muted-foreground">{{ __('messages.no_notifications') }}</li>
                                @endforelse
                            </ul>
                            <a href="{{ route('customer.notifications.index') }}" class="mt-3 inline-flex h-8 w-full items-center justify-center gap-2 rounded-lg bg-secondary px-3 text-xs font-bold hover:bg-secondary/70">
                                <i data-lucide="list" class="h-3.5 w-3.5"></i>
                                {{ __('messages.view_all_notifications') }}
                            </a>
                        </div>
                    </div>

                    <button type="button" aria-label="{{ __('messages.change_theme') }}" class="theme-toggle-btn inline-flex h-10 w-10 items-center justify-center rounded-lg border border-border bg-card text-foreground transition hover:bg-secondary" title="{{ __('messages.change_theme') }}">
                        <i data-lucide="sun" class="hidden h-4 w-4 dark:block"></i>
                        <i data-lucide="moon" class="block h-4 w-4 dark:hidden"></i>
                    </button>

                    <a href="{{ route('customer.profile') }}" class="hidden h-9 items-center justify-center gap-2 rounded-lg border border-border bg-card px-2 text-xs font-black text-foreground transition hover:bg-secondary sm:inline-flex" title="{{ __('portal.profile') }}">
                        @if($customerAvatarUrl)
                            <img src="{{ $customerAvatarUrl }}" alt="{{ __('portal.avatar') }}" class="h-6 w-6 rounded-full object-cover">
                        @else
                            <span class="grid h-6 w-6 place-items-center rounded-full bg-accent/10 text-[10px] text-accent">{{ $customerInitial }}</span>
                        @endif
                        <span class="hidden xl:inline">{{ __('portal.profile') }}</span>
                    </a>

                    <a href="{{ route('customer.shipments.create') }}" class="hidden h-9 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-4 text-xs font-black text-accent-foreground sm:inline-flex">
                        <i data-lucide="plus" class="h-4 w-4"></i>
                        {{ __('messages.new_request') }}
                    </a>
                </div>
            </header>

            <main id="customer-content" tabindex="-1" class="portal-content flex-1 p-4 sm:p-6">
                @if(session('success'))
                    <div role="status" class="mb-4 flex items-center gap-3 rounded-xl border border-success/30 bg-success/10 p-4 text-sm">
                        <i data-lucide="check-circle-2" class="h-5 w-5 text-success"></i>
                        <div>{{ session('success') }}</div>
                    </div>
                @endif

                @if(session('error'))
                    <div role="alert" class="mb-4 flex items-center gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm">
                        <i data-lucide="x-circle" class="h-5 w-5 text-destructive"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                @if($errors->any())
                    <div role="alert" class="mb-4 rounded-xl border border-destructive/30 bg-destructive/10 p-4 text-sm">
                        <p class="font-semibold">{{ __('portal.validation') }}</p>
                        <ul class="mt-2 list-disc space-y-1 ps-5">
                            @foreach($errors->messages() as $field => $messages)
                                <li><a data-error-field="{{ $field }}" class="underline">{{ $messages[0] }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    @include('partials.notification-runtime', [
        'notificationIdentity' => 'customer:' . $customer->id,
        'notificationFeedUrl' => route('customer.notifications.feed'),
        'notificationSnapshot' => \App\Support\NotificationFeed::customer($customer, $allCustomerNotifications),
    ])
    @yield('scripts')
</body>
</html>

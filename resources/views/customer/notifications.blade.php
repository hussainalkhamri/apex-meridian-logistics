@extends('layouts.customer')

@section('title', __('messages.notifications_center') . ' — ' . __('messages.site_name'))

@section('content')
<div>
    <section class="portal-intro rounded-xl bg-hero text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-bold">
                <i data-lucide="bell-ring" class="h-3.5 w-3.5"></i>
                {{ __('messages.notifications') }}
            </span>
            <h1 class="mt-3 text-2xl font-bold">{{ __('messages.notifications_center') }}</h1>
            <p class="mt-2 max-w-2xl text-white/80">{{ __('messages.customer_notifications_center_desc') }}</p>
        </div>
    </section>

    <section class="mx-auto py-5">
        <div class="mb-5 grid gap-4 md:grid-cols-3">
            @foreach([
                ['label' => __('messages.all_notifications'), 'value' => $statusCounts['all'], 'icon' => 'inbox', 'color' => 'text-primary bg-primary/10'],
                ['label' => __('messages.unread_notifications'), 'value' => $statusCounts['unread'], 'icon' => 'bell-dot', 'color' => 'text-accent bg-accent/10'],
                ['label' => __('messages.read_notifications'), 'value' => $statusCounts['read'], 'icon' => 'check-check', 'color' => 'text-success bg-success/10'],
            ] as $stat)
                <div class="flex items-center gap-3 rounded-xl border border-border bg-card p-4 shadow-card">
                    <span class="grid h-11 w-11 place-items-center rounded-lg {{ $stat['color'] }}">
                        <i data-lucide="{{ $stat['icon'] }}" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <div class="text-2xl font-extrabold">{{ $stat['value'] }}</div>
                        <div class="text-xs font-bold text-muted-foreground">{{ $stat['label'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="rounded-xl border border-border bg-card shadow-card">
            <form data-filter-form action="{{ route('customer.notifications.index') }}" method="GET" class="grid gap-3 border-b border-border p-4 md:grid-cols-[1fr_12rem_auto]">
                <div class="relative">
                    <i data-lucide="search" class="absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    <input type="search" name="q" aria-label="{{ __('messages.notification_search_placeholder') }}" value="{{ $search }}" placeholder="{{ __('messages.notification_search_placeholder') }}" class="h-10 w-full rounded-lg border border-border bg-background ps-9 pe-4 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <select name="status" aria-label="{{ __('messages.status') }}" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="all" @selected($status === 'all')>{{ __('messages.all_notifications') }}</option>
                    <option value="unread" @selected($status === 'unread')>{{ __('messages.unread_notifications') }}</option>
                    <option value="read" @selected($status === 'read')>{{ __('messages.read_notifications') }}</option>
                </select>
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-secondary px-4 text-sm font-bold hover:bg-secondary/70">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    {{ __('messages.filter') }}
                </button>
            </form>

            <div class="divide-y divide-border/60">
                @php $previousDay = null; @endphp
                @forelse($notifications as $notification)
                    @php
                        $isRead = in_array($notification->id, $readIds, true);
                        $dayGroup = $notification->created_at->isToday() ? 'today' : ($notification->created_at->isYesterday() ? 'yesterday' : 'earlier');
                    @endphp
                    @if($previousDay !== $dayGroup)
                        <h2 class="bg-secondary/30 px-4 py-2 text-xs font-semibold text-muted-foreground">{{ __('portal.'.$dayGroup) }}</h2>
                        @php $previousDay = $dayGroup; @endphp
                    @endif
                    <div class="grid gap-3 p-4 md:grid-cols-[1fr_auto] md:items-center {{ !$isRead ? 'border-s-2 border-s-accent bg-accent/5' : '' }}">
                        <a href="{{ $notification->booking_id ? route('customer.shipments.show', $notification->booking_id) : route('customer.notifications.index') }}" class="block rounded-lg p-1 transition hover:bg-secondary/25">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-bold {{ $isRead ? 'bg-success/15 text-success' : 'bg-accent/15 text-foreground' }}">
                                    {{ $isRead ? __('messages.read') : __('messages.unread') }}
                                </span>
                                @if($notification->booking_id)
                                    <span class="font-mono text-[11px] text-muted-foreground">{{ $notification->booking_id }}</span>
                                @endif
                            </div>
                            <div class="mt-2 text-sm font-bold leading-6">{{ $notification->message }}</div>
                            <div class="mt-1 text-[11px] text-muted-foreground">{{ $notification->created_at->diffForHumans() }}</div>
                        </a>

                        @unless($isRead)
                            <form action="{{ route('customer.notifications.read') }}" method="POST">
                                @csrf
                                <input type="hidden" name="notification_ids[]" value="{{ $notification->id }}">
                                <button type="submit" class="inline-flex h-8 items-center justify-center gap-2 rounded-lg bg-accent px-3 text-[11px] font-bold text-accent-foreground hover:opacity-90">
                                    <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                    {{ __('messages.mark_read') }}
                                </button>
                            </form>
                        @endunless
                    </div>
                @empty
                    <div class="px-4 py-12 text-center text-sm text-muted-foreground">
                        <i data-lucide="bell-off" class="mx-auto mb-3 h-10 w-10 opacity-30"></i>
                        {{ __('messages.no_matching_notifications') }}
                    </div>
                @endforelse
            </div>

            @if($notifications->hasPages())
                <div class="border-t border-border p-4">
                    {{ $notifications->links() }}
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

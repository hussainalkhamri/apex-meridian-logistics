@extends('layouts.app')

@section('title', __('messages.notifications_center') . ' — ' . __('messages.site_name'))

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs font-bold">
                <i data-lucide="bell-ring" class="h-3.5 w-3.5"></i>
                {{ __('messages.notifications') }}
            </span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">{{ __('messages.notifications_center') }}</h1>
            <p class="mt-2 max-w-2xl text-white/80">{{ __('messages.notifications_center_desc') }}</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
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
            <form action="{{ route('notifications.index') }}" method="GET" class="grid gap-3 border-b border-border p-4 lg:grid-cols-[1fr_12rem_12rem_auto]">
                <div class="relative">
                    <i data-lucide="search" class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"></i>
                    <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('messages.notification_search_placeholder') }}" class="h-10 w-full rounded-lg border border-border bg-background pr-9 pl-4 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <select name="status" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="all" @selected($status === 'all')>{{ __('messages.all_notifications') }}</option>
                    <option value="unread" @selected($status === 'unread')>{{ __('messages.unread_notifications') }}</option>
                    <option value="read" @selected($status === 'read')>{{ __('messages.read_notifications') }}</option>
                </select>
                <select name="target" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">{{ __('messages.all_targets') }}</option>
                    @foreach($allowedTargets as $allowedTarget)
                        <option value="{{ $allowedTarget }}" @selected($target === $allowedTarget)>
                            {{ __('messages.notification_target_' . $allowedTarget) }}
                        </option>
                    @endforeach
                </select>
                <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-secondary px-4 text-sm font-bold hover:bg-secondary/70">
                    <i data-lucide="filter" class="h-4 w-4"></i>
                    {{ __('messages.filter') }}
                </button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-right">{{ __('messages.notification_message') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.notification_target') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.notification_booking') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.notification_status') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('messages.notification_time') }}</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($notifications as $notification)
                            @php
                                $isRead = $notification->reads->isNotEmpty();
                                $targetKey = 'messages.notification_target_' . ($notification->target ?: 'general');
                            @endphp
                            <tr data-notification-row="{{ $notification->id }}" class="border-b border-border/50 hover:bg-secondary/20">
                                <td class="max-w-xl px-4 py-3">
                                    <div class="font-bold leading-6">{{ $notification->message }}</div>
                                    <div class="mt-1 text-[10px] font-mono text-muted-foreground">{{ $notification->id }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full border border-border bg-secondary/35 px-2 py-1 text-[11px] font-bold">
                                        {{ __($targetKey) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ $notification->booking_id ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-[11px] font-bold {{ $isRead ? 'bg-success/15 text-success' : 'bg-accent/15 text-accent-foreground' }}">
                                        {{ $isRead ? __('messages.read') : __('messages.unread') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ $notification->created_at->diffForHumans() }}
                                    <div class="mt-1 font-mono text-[10px]">{{ $notification->created_at->toDateTimeString() }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    @unless($isRead)
                                        <form action="{{ route('notifications.markRead', $notification) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="inline-flex h-8 items-center justify-center gap-2 rounded-lg bg-accent px-3 text-[11px] font-bold text-accent-foreground hover:opacity-90">
                                                <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                                {{ __('messages.mark_read') }}
                                            </button>
                                        </form>
                                    @endunless
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-12 text-center text-sm text-muted-foreground">
                                    <i data-lucide="bell-off" class="mx-auto mb-3 h-10 w-10 opacity-30"></i>
                                    {{ __('messages.no_matching_notifications') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
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

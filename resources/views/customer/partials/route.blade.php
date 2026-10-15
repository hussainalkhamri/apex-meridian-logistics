@php
    $routeIcon = match (strtoupper($method ?? '')) {
        'AIR' => 'plane',
        'LAND', 'DOOR_TO_DOOR' => 'truck',
        default => 'ship',
    };
@endphp
<div class="portal-route">
    <div class="min-w-0">
        <div class="text-xs text-muted-foreground">{{ __('portal.origin') }}</div>
        <div class="mt-1 text-sm font-semibold"><bdi>{{ $origin ?: '—' }}</bdi></div>
    </div>
    <div class="portal-route-line" aria-hidden="true"><i data-lucide="{{ $routeIcon }}"></i></div>
    <div class="min-w-0 text-end">
        <div class="text-xs text-muted-foreground">{{ __('portal.destination') }}</div>
        <div class="mt-1 text-sm font-semibold"><bdi>{{ $destination ?: '—' }}</bdi></div>
    </div>
</div>

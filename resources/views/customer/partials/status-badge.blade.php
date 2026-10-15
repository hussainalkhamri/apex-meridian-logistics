@php
    $currentStatus = (string) ($status ?? data_get($booking ?? null, 'status', ''));
    $labels = $labels ?? \App\Support\BookingStatus::labels();

    $badge = $labels[$currentStatus] ?? [
        'label' => $currentStatus !== '' ? $currentStatus : __('غير محدد'),
        'class' => 'bg-secondary text-foreground border-border',
    ];

    $sizeClasses = [
        'xs' => 'px-2 py-0.5 text-[10px]',
        'sm' => 'px-2.5 py-1 text-[11px]',
        'md' => 'px-3 py-1 text-xs',
    ];
@endphp

<span class="inline-flex w-fit items-center rounded-full border font-black {{ $sizeClasses[$size ?? 'sm'] ?? $sizeClasses['sm'] }} {{ $badge['class'] }}">
    {{ __($badge['label']) }}
</span>

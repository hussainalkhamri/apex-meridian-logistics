@if(in_array($booking->status, ['priced', 'sent'], true) && $booking->validity_date)
    @if($booking->validity_date->copy()->endOfDay()->isPast())
        <p class="mb-3 flex items-start gap-2 rounded-lg border border-warning/30 bg-warning/20 p-3 text-xs text-warning-foreground"><i data-lucide="clock-alert" class="h-4 w-4 shrink-0"></i>{{ __('portal.expired') }}</p>
    @elseif($booking->validity_date->lte(now()->addDays(3)))
        <p class="mb-3 flex items-start gap-2 rounded-lg border border-warning/30 bg-warning/20 p-3 text-xs text-warning-foreground"><i data-lucide="clock-3" class="h-4 w-4 shrink-0"></i>{{ __('portal.expiring') }}: {{ $booking->validity_date->format('Y-m-d') }}</p>
    @endif
@endif

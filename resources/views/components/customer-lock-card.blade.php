@props(['fields' => []])

<div class="space-y-4">
    <h2 class="flex items-center gap-2 border-b border-border pb-2 text-lg font-bold text-primary">
        <i data-lucide="users" class="h-5 w-5 text-accent"></i>
        <span>{{ __('messages.customer_lock_card_title') }}</span>
    </h2>
    <div class="rounded-xl border border-accent/20 bg-accent/5 p-4">
        <div class="mb-3 flex items-center justify-between gap-3">
            <div class="text-sm font-black text-foreground">{{ __('messages.customer_lock_card_desc') }}</div>
            <span class="inline-flex items-center gap-1 rounded-full bg-card px-3 py-1 text-[11px] font-black text-muted-foreground">
                <i data-lucide="lock" class="h-3.5 w-3.5"></i>
                {{ __('messages.customer_lock_card_limited_access') }}
            </span>
        </div>
        <div class="grid gap-3 md:grid-cols-2">
            @foreach($fields as $field)
                <div class="rounded-lg border border-border bg-card p-3">
                    <div class="text-[11px] font-bold text-muted-foreground">{{ $field['label'] }}</div>
                    <div class="mt-1 truncate text-sm font-black">{{ $field['value'] ?: '—' }}</div>
                </div>
            @endforeach
        </div>
    </div>
</div>

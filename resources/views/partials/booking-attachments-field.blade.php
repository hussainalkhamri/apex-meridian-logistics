@php
    $currentBooking = $currentBooking ?? ($booking ?? null);
    $inputId = $inputId ?? 'attachments';
    $currentAttachments = collect();

    if ($currentBooking && filled($currentBooking->attachments)) {
        $rawAttachments = $currentBooking->attachments;
        $decodedAttachments = is_array($rawAttachments)
            ? $rawAttachments
            : json_decode((string) $rawAttachments, true);

        if (! is_array($decodedAttachments)) {
            $decodedAttachments = [(string) $rawAttachments];
        }

        $currentAttachments = collect($decodedAttachments)
            ->filter(fn ($path) => is_string($path) && trim($path) !== '')
            ->map(fn ($path) => trim((string) $path))
            ->unique()
            ->values();
    }
@endphp

<div class="space-y-3">
    <div class="space-y-2">
        <label for="{{ $inputId }}" class="text-xs font-bold text-foreground">{{ __('messages.booking_attachments_label') }}</label>
        <input type="file" name="attachments[]" id="{{ $inputId }}" multiple accept="image/*,.pdf" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" />
        <p class="text-xs text-muted-foreground">{{ __('messages.booking_attachments_hint') }}</p>
        <p class="text-[11px] font-bold text-warning">{{ __('messages.booking_dg_attachment_hint') }}</p>
        @if(isset($errors))
            @if($errors->has('attachments'))
                <p class="text-xs font-bold text-destructive">{{ $errors->first('attachments') }}</p>
            @endif
            @foreach($errors->get('attachments.*') as $attachmentErrors)
                @foreach($attachmentErrors as $attachmentError)
                    <p class="text-xs font-bold text-destructive">{{ $attachmentError }}</p>
                @endforeach
            @endforeach
        @endif
    </div>

    @if($currentAttachments->isNotEmpty())
        <div class="rounded-xl border border-border/70 bg-secondary/15 p-3">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2 text-xs font-black text-foreground">
                    <i data-lucide="paperclip" class="h-4 w-4 text-accent"></i>
                    <span>{{ __('messages.booking_current_attachments') }}</span>
                </div>
                <span class="rounded-full bg-card px-2.5 py-1 text-[11px] font-bold text-muted-foreground">{{ $currentAttachments->count() }}</span>
            </div>

            <div class="space-y-2">
                @foreach($currentAttachments as $attachmentPath)
                    @php
                        $normalizedPath = str_replace('\\', '/', $attachmentPath);
                        $fileName = basename($normalizedPath);
                        $fileUrl = route('bookings.attachments.show', ['booking' => $currentBooking, 'path' => ltrim($normalizedPath, '/')]);
                    @endphp
                    <div class="flex flex-col gap-3 rounded-lg border border-border bg-card p-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener" class="flex min-w-0 items-center gap-3 text-sm font-bold text-foreground hover:text-accent">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-accent/10 text-accent">
                                <i data-lucide="file-text" class="h-4 w-4"></i>
                            </span>
                            <span class="min-w-0">
                                <span class="block truncate">{{ $fileName }}</span>
                                <span class="block truncate text-[11px] font-semibold text-muted-foreground">{{ __('messages.booking_open_attachment') }}</span>
                            </span>
                        </a>

                        <label class="inline-flex h-9 shrink-0 cursor-pointer items-center justify-center gap-2 rounded-lg border border-destructive/25 bg-destructive/5 px-3 text-xs font-black text-destructive transition hover:bg-destructive/10">
                            <input type="checkbox" name="remove_attachments[]" value="{{ $attachmentPath }}" class="h-4 w-4 rounded border-destructive/40 bg-background text-destructive focus:ring-destructive">
                            <span>{{ __('messages.booking_delete_attachment') }}</span>
                        </label>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>

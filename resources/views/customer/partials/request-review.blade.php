<dialog id="portal-request-review" class="portal-review" aria-labelledby="portal-review-title" aria-describedby="portal-review-hint">
    <header>
        <h2 id="portal-review-title" class="text-base font-bold">{{ __('portal.review') }}</h2>
        <button type="button" data-close-review class="portal-icon-button" aria-label="{{ __('portal.close') }}"><i data-lucide="x" class="h-5 w-5"></i></button>
    </header>
    <p id="portal-review-hint" class="portal-review-hint">{{ __('portal.review_hint') }}</p>
    <dl></dl>
    <footer>
        <button type="button" data-close-review class="inline-flex h-11 items-center justify-center rounded-lg border border-border px-4 text-sm font-semibold hover:bg-secondary">{{ __('portal.edit') }}</button>
        <button type="button" data-confirm-review class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground"><i data-lucide="send" class="h-4 w-4"></i>{{ __('portal.send') }}</button>
    </footer>
</dialog>

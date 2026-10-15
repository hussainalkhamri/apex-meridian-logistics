<div class="flex items-center justify-end gap-2">
    <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar', 'redirect_to' => request()->fullUrlWithoutQuery('locale')]) }}" class="inline-flex h-10 items-center gap-2 rounded-lg border border-border bg-card px-3 text-xs font-semibold text-foreground" aria-label="{{ app()->getLocale() === 'ar' ? __('messages.switch_to_english') : __('messages.switch_to_arabic') }}"><i data-lucide="languages" class="h-4 w-4"></i>{{ app()->getLocale() === 'ar' ? 'EN' : 'AR' }}</a>
    <button type="button" class="theme-toggle-btn portal-icon-button border border-border bg-card" aria-label="{{ __('messages.change_theme') }}"><i data-lucide="sun-moon" class="h-4 w-4"></i></button>
</div>

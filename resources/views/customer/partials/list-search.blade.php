<div class="portal-table-search">
    <label for="{{ $tableId }}-search" class="sr-only">{{ __('portal.search_list') }}</label>
    <input id="{{ $tableId }}-search" type="search" data-list-search="{{ $tableId }}" placeholder="{{ __('portal.search_list') }}" autocomplete="off">
    @if($unpaidFilter ?? false)
        <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" data-unpaid-filter="{{ $tableId }}" class="h-4 w-4 accent-accent">{{ __('portal.unpaid_only') }}</label>
    @endif
</div>
<p hidden data-list-empty="{{ $tableId }}" class="p-6 text-center text-sm text-muted-foreground" role="status">{{ __('portal.no_matches') }}</p>

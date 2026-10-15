<label class="inline-flex flex-wrap items-center gap-2 text-xs font-bold text-foreground">
    <span>{{ __('messages.booking_package_dimension_unit') }}</span>
    <select name="package_dimension_unit" class="h-9 rounded-md border border-border bg-background px-3 text-sm">
        @foreach(['cm', 'm'] as $unit)
            <option value="{{ $unit }}" @selected(($selectedUnit ?? 'cm') === $unit)
                data-length-label="{{ __('messages.booking_package_length' . ($unit === 'm' ? '_m' : '') . '_label') }}"
                data-width-label="{{ __('messages.booking_package_width' . ($unit === 'm' ? '_m' : '') . '_label') }}"
                data-height-label="{{ __('messages.booking_package_height' . ($unit === 'm' ? '_m' : '') . '_label') }}">
                {{ __('messages.booking_package_unit_' . $unit) }}
            </option>
        @endforeach
    </select>
</label>

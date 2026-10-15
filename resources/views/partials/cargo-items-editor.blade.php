@php
    $editorId = $editorId ?? 'cargo-items-editor';
    $cargoItemRows = old('cargo_items', $cargoItems ?? []);
    $cargoItemRows = is_array($cargoItemRows) && $cargoItemRows !== [] ? $cargoItemRows : [[]];
    $defaultUnit = $defaultUnit ?? 'piece';
    $unitOptions = [
        'piece' => 'قطعة',
        'vehicle' => 'مركبة',
        'cbm' => 'CBM',
        'kg' => 'كجم',
        'ton' => 'طن',
        'container' => 'حاوية',
    ];
@endphp

<div id="{{ $editorId }}" data-cargo-items-editor class="space-y-3 rounded-xl border border-accent/20 bg-accent/5 p-4 {{ $class ?? '' }}">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 text-sm font-bold text-foreground">
                <i data-lucide="boxes" class="h-4 w-4 text-accent"></i>
                <span>أصناف الشحنة و HS CODE</span>
            </div>
            <p class="mt-1 text-[11px] text-muted-foreground">أضف كل صنف بشكل مستقل ليتم تسعيره وإظهاره في عرض السعر والفاتورة.</p>
        </div>
        <button type="button" data-add-cargo-item class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-accent px-3 text-xs font-bold text-accent-foreground shadow-sm transition hover:opacity-95">
            <i data-lucide="plus" class="h-4 w-4"></i>
            <span>إضافة صنف جديد</span>
        </button>
    </div>

    <div class="overflow-x-auto rounded-lg border border-border bg-background">
        <table class="w-full min-w-[880px] text-right text-xs">
            <thead class="bg-secondary/50 text-muted-foreground">
                <tr>
                    <th class="px-3 py-2">اسم / وصف الصنف</th>
                    <th class="px-3 py-2">HS CODE Number</th>
                    <th class="px-3 py-2">الكمية</th>
                    <th class="px-3 py-2">الوحدة</th>
                    <th class="px-3 py-2">الوزن (كجم)</th>
                    <th class="px-3 py-2">الحجم (CBM)</th>
                    <th class="w-12 px-2 py-2"></th>
                </tr>
            </thead>
            <tbody data-cargo-items-rows class="divide-y divide-border">
                @foreach($cargoItemRows as $index => $item)
                    <tr data-cargo-item-row>
                        <td class="p-2"><input type="text" name="cargo_items[{{ $index }}][name]" value="{{ data_get($item, 'name') }}" required placeholder="اسم الصنف" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
                        <td class="p-2"><input type="text" name="cargo_items[{{ $index }}][hs_code]" value="{{ data_get($item, 'hs_code') }}" placeholder="مثال: 8703.23" dir="ltr" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
                        <td class="p-2"><input type="number" name="cargo_items[{{ $index }}][quantity]" value="{{ data_get($item, 'quantity', 1) }}" required min="0.01" step="0.01" dir="ltr" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
                        <td class="p-2">
                            <select name="cargo_items[{{ $index }}][unit]" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                                @foreach($unitOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(data_get($item, 'unit', $defaultUnit) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="p-2"><input type="number" name="cargo_items[{{ $index }}][weight_kg]" value="{{ data_get($item, 'weight_kg') }}" min="0" step="0.01" dir="ltr" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
                        <td class="p-2"><input type="number" name="cargo_items[{{ $index }}][volume_cbm]" value="{{ data_get($item, 'volume_cbm') }}" min="0" step="0.0001" dir="ltr" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
                        <td class="p-2 text-center">
                            <button type="button" data-remove-cargo-item class="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" title="حذف الصنف">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <template data-cargo-item-template>
        <tr data-cargo-item-row>
            <td class="p-2"><input type="text" name="cargo_items[__INDEX__][name]" required placeholder="اسم الصنف" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2"><input type="text" name="cargo_items[__INDEX__][hs_code]" placeholder="مثال: 8703.23" dir="ltr" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2"><input type="number" name="cargo_items[__INDEX__][quantity]" value="1" required min="0.01" step="0.01" dir="ltr" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2">
                <select name="cargo_items[__INDEX__][unit]" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent">
                    @foreach($unitOptions as $value => $label)
                        <option value="{{ $value }}" @selected($defaultUnit === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </td>
            <td class="p-2"><input type="number" name="cargo_items[__INDEX__][weight_kg]" min="0" step="0.01" dir="ltr" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2"><input type="number" name="cargo_items[__INDEX__][volume_cbm]" min="0" step="0.0001" dir="ltr" class="h-9 w-full rounded-md border border-border bg-background px-2 text-xs focus:outline-none focus:ring-1 focus:ring-accent"></td>
            <td class="p-2 text-center">
                <button type="button" data-remove-cargo-item class="inline-flex h-8 w-8 items-center justify-center rounded-md text-muted-foreground transition hover:bg-destructive/10 hover:text-destructive" title="حذف الصنف">
                    <i data-lucide="trash-2" class="h-4 w-4"></i>
                </button>
            </td>
        </tr>
    </template>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-cargo-items-editor]').forEach((editor, editorIndex) => {
                const rowsContainer = editor.querySelector('[data-cargo-items-rows]');
                const template = editor.querySelector('[data-cargo-item-template]');
                const addButton = editor.querySelector('[data-add-cargo-item]');
                let nextIndex = rowsContainer?.querySelectorAll('[data-cargo-item-row]').length || 0;

                const rows = () => Array.from(rowsContainer?.querySelectorAll('[data-cargo-item-row]') || []);
                const syncRemoveButtons = () => {
                    rows().forEach(row => {
                        row.querySelector('[data-remove-cargo-item]')?.classList.toggle('invisible', rows().length === 1);
                    });
                };
                const bindRow = row => {
                    row.querySelector('[data-remove-cargo-item]')?.addEventListener('click', () => {
                        if (rows().length <= 1) return;
                        row.remove();
                        syncRemoveButtons();
                    });
                };

                rows().forEach(bindRow);
                syncRemoveButtons();
                addButton?.addEventListener('click', () => {
                    if (!rowsContainer || !template) return;
                    const index = `${editorIndex}_${nextIndex++}`;
                    rowsContainer.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', index));
                    bindRow(rowsContainer.lastElementChild);
                    syncRemoveButtons();
                    window.lucide?.createIcons();
                });
            });
        });
    </script>
@endonce

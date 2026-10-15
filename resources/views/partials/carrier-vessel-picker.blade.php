@php
    $vesselOptions = collect($carrierVesselOptions ?? []);
@endphp

<div class="space-y-1">
    <label for="vessel" class="font-bold text-foreground">السفينة الناقلة</label>
    <div class="relative" data-carrier-vessel-picker>
        <input
            type="text"
            name="vessel"
            id="vessel"
            required
            placeholder="اكتب اسم السفينة أو اختر من القائمة"
            autocomplete="off"
            data-carrier-vessel-input
            class="w-full h-10 rounded-lg border border-border bg-background py-0 pr-10 pl-3 text-xs focus:outline-none focus:ring-1 focus:ring-accent"
        />
        <button
            type="button"
            data-carrier-vessel-toggle
            class="absolute right-1.5 top-1/2 grid h-7 w-7 -translate-y-1/2 place-items-center rounded-md text-muted-foreground hover:bg-secondary hover:text-foreground"
            aria-label="عرض قائمة السفن"
            aria-expanded="false"
        >
            <i data-lucide="chevron-down" class="h-4 w-4"></i>
        </button>

        <div data-carrier-vessel-menu class="hidden absolute left-0 right-0 top-full z-50 mt-1 max-h-56 overflow-y-auto rounded-xl border border-border bg-card py-1 text-right shadow-elegant">
            @foreach($vesselOptions as $v)
                <button
                    type="button"
                    data-carrier-vessel-option
                    data-carrier-vessel-name="{{ $v }}"
                    class="flex w-full items-center gap-2 px-3 py-2 text-right text-xs font-semibold text-foreground hover:bg-secondary/70 focus:bg-secondary/70 focus:outline-none"
                >
                    <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-accent/10 text-accent">
                        <i data-lucide="ship" class="h-3.5 w-3.5"></i>
                    </span>
                    <span class="truncate">{{ $v }}</span>
                </button>
            @endforeach
            <div data-carrier-vessel-empty class="{{ $vesselOptions->isEmpty() ? '' : 'hidden' }} px-3 py-3 text-center text-xs text-muted-foreground">
                لا توجد نتيجة مطابقة. اكتب الاسم يدوياً وسيتم حفظه بعد التأكيد.
            </div>
        </div>
    </div>
    <p class="text-[11px] text-muted-foreground">يمكنك كتابة سفينة جديدة، وبعد الحفظ ستظهر للاختيار في الطلبات القادمة.</p>
</div>

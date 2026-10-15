<div id="tab-customs" class="hidden mt-4 rounded-xl border border-border bg-card shadow-card overflow-hidden">
    <div class="p-6">
        <div class="mb-6 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <h2 class="flex items-center gap-2 text-lg font-bold text-primary">
                <i data-lucide="landmark" class="h-5 w-5 text-accent"></i>
                <span>حسابات الجمارك</span>
            </h2>
            <button type="button" onclick="openCustomsInvoiceModal()" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-primary px-4 text-xs font-bold text-primary-foreground shadow hover:opacity-90">
                <i data-lucide="file-plus-2" class="h-4 w-4"></i>
                <span>إصدار فاتورة جمارك</span>
            </button>
        </div>

        <div class="mb-6 grid gap-4 md:grid-cols-5">
            <div class="rounded-lg border border-border bg-secondary/30 p-4">
                <div class="text-xs font-bold text-muted-foreground">رسوم الجمارك المسجلة</div>
                <div class="mt-1 text-xl font-extrabold text-foreground" dir="ltr">${{ number_format($totalCustomsFees, 2) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-secondary/30 p-4">
                <div class="text-xs font-bold text-muted-foreground">المفوتر</div>
                <div class="mt-1 text-xl font-extrabold text-primary" dir="ltr">${{ number_format($totalCustomsInvoiced, 2) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-secondary/30 p-4">
                <div class="text-xs font-bold text-muted-foreground">المقبوض</div>
                <div class="mt-1 text-xl font-extrabold text-success" dir="ltr">${{ number_format($totalCustomsPaid, 2) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-secondary/30 p-4">
                <div class="text-xs font-bold text-muted-foreground">المتبقي على العملاء</div>
                <div class="mt-1 text-xl font-extrabold text-destructive" dir="ltr">${{ number_format($totalCustomsRemaining, 2) }}</div>
            </div>
            <div class="rounded-lg border border-border bg-secondary/30 p-4">
                <div class="text-xs font-bold text-muted-foreground">غير مفوتر</div>
                <div class="mt-1 text-xl font-extrabold text-warning" dir="ltr">${{ number_format($totalCustomsUnbilled, 2) }}</div>
            </div>
        </div>

        @if($customsAccounts->isEmpty())
            <div class="rounded-xl border border-dashed border-border bg-card py-10 text-center text-muted-foreground text-xs">
                لا توجد ملفات تخليص مرتبطة بالحسابات حالياً.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-right text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-3 py-3">الطلب</th>
                            <th class="px-3 py-3">العميل</th>
                            <th class="px-3 py-3">مرجع الجمارك</th>
                            <th class="px-3 py-3">المسار / المركز</th>
                            <th class="px-3 py-3">حالة التخليص</th>
                            <th class="px-3 py-3">رسوم مسجلة</th>
                            <th class="px-3 py-3">مفوتر</th>
                            <th class="px-3 py-3">مدفوع</th>
                            <th class="px-3 py-3">المتبقي</th>
                            <th class="px-3 py-3 text-left"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customsAccounts as $customsBooking)
                            @php
                                $customsSuggested = $customsBooking->customs_unbilled_total > 0
                                    ? $customsBooking->customs_unbilled_total
                                    : max((float) $customsBooking->customs_fees, 0);
                                $customsStatus = $customsBooking->customs_status ?: 'pending';
                                $customsStatusLabels = [
                                    'pending' => 'بانتظار المراجعة',
                                    'documents_review' => 'مراجعة المستندات',
                                    'inspection' => 'معاينة',
                                    'duties' => 'احتساب الرسوم',
                                    'cleared' => 'مخلصة',
                                    'on_hold' => 'معلقة',
                                ];
                                $invoiceState = $customsBooking->customs_invoice_total <= 0
                                    ? 'غير مفوتر'
                                    : ($customsBooking->customs_remaining_total > 0 ? 'مفوتر جزئياً/غير مدفوع' : 'مدفوع');
                            @endphp
                            <tr data-booking-id="{{ $customsBooking->id }}" class="border-b border-border/40 hover:bg-secondary/30 transition-colors">
                                <td class="px-3 py-3 font-mono text-xs font-semibold">{{ $customsBooking->id }}</td>
                                <td class="px-3 py-3">
                                    <div class="font-bold">{{ $customsBooking->customer_company }}</div>
                                    <div class="mt-0.5 text-[11px] text-muted-foreground">{{ $customsBooking->customer_contact_name ?: '—' }}</div>
                                </td>
                                <td class="px-3 py-3 font-mono text-xs text-muted-foreground">{{ $customsBooking->customs_reference ?: ($customsBooking->customs_bayan_no ?: '—') }}</td>
                                <td class="px-3 py-3 text-xs text-muted-foreground">{{ $customsBooking->pol }} &rarr; {{ $customsBooking->pod }}</td>
                                <td class="px-3 py-3">
                                    <div class="inline-flex items-center rounded-full bg-secondary px-2 py-1 text-[10px] font-bold text-foreground">
                                        {{ $customsStatusLabels[$customsStatus] ?? $customsStatus }}
                                    </div>
                                    <div class="mt-1 text-[10px] text-muted-foreground">{{ $invoiceState }}</div>
                                </td>
                                <td class="px-3 py-3 font-bold text-foreground" dir="ltr">${{ number_format($customsBooking->customs_fees ?? 0, 2) }}</td>
                                <td class="px-3 py-3 font-bold text-primary" dir="ltr">${{ number_format($customsBooking->customs_invoice_total, 2) }}</td>
                                <td class="px-3 py-3 font-bold text-success" dir="ltr">${{ number_format($customsBooking->customs_paid_total, 2) }}</td>
                                <td class="px-3 py-3">
                                    <div class="font-bold {{ $customsBooking->customs_remaining_total > 0 ? 'text-destructive' : 'text-success' }}" dir="ltr">${{ number_format($customsBooking->customs_remaining_total, 2) }}</div>
                                    @if($customsBooking->customs_unbilled_total > 0)
                                        <div class="mt-0.5 text-[10px] font-bold text-warning" dir="ltr">غير مفوتر ${{ number_format($customsBooking->customs_unbilled_total, 2) }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-left">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" onclick="openDetailModal('{{ $customsBooking->id }}')" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground" title="تفاصيل الطلب">
                                            <i data-lucide="eye" class="h-4 w-4"></i>
                                        </button>
                                        @if($customsBooking->latest_customs_invoice)
                                            <a href="{{ route('accounts.invoice', ['id' => $customsBooking->latest_customs_invoice->id]) }}" target="_blank" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-muted-foreground hover:bg-secondary hover:text-foreground" title="طباعة فاتورة تخليص جمركي رسمية">
                                                <i data-lucide="printer" class="h-4 w-4"></i>
                                            </a>
                                        @else
                                            <button type="button" disabled class="inline-flex h-8 w-8 cursor-not-allowed items-center justify-center rounded-lg text-muted-foreground/40" title="أصدر فاتورة جمارك أولاً">
                                                <i data-lucide="printer" class="h-4 w-4"></i>
                                            </button>
                                        @endif
                                        <button type="button"
                                            data-booking-id="{{ $customsBooking->id }}"
                                            data-customer="{{ $customsBooking->customer_company }}"
                                            data-reference="{{ $customsBooking->customs_reference ?: $customsBooking->customs_bayan_no }}"
                                            data-fees="{{ (float) $customsBooking->customs_fees }}"
                                            data-suggested="{{ (float) $customsSuggested }}"
                                            onclick="openCustomsInvoiceModalFromButton(this)"
                                            class="inline-flex h-8 items-center justify-center gap-1 rounded-lg bg-accent px-3 text-[11px] font-bold text-accent-foreground shadow hover:opacity-90">
                                            <i data-lucide="file-plus-2" class="h-3.5 w-3.5"></i>
                                            <span>فاتورة</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div id="customs-invoice-modal" class="hidden fixed inset-0 z-50 bg-background/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-2xl rounded-xl border border-border bg-card p-6 shadow-elegant">
        <h3 class="mb-4 flex items-center gap-2 text-lg font-bold text-primary">
            <i data-lucide="landmark" class="h-5 w-5 text-accent"></i>
            <span>إصدار فاتورة جمارك</span>
        </h3>

        <form action="{{ route('accounts.storeInvoice') }}" method="POST" class="space-y-4 text-xs">
            @csrf
            <input type="hidden" name="invoice_type" value="customs">
            <input type="hidden" name="amount" id="customs-invoice-amount" value="0">

            <div class="space-y-1">
                <label class="font-bold text-foreground">ملف التخليص / الطلب</label>
                <select name="booking_id" id="customs-invoice-booking" required onchange="syncCustomsInvoiceBooking()" class="h-10 w-full rounded-lg border border-border bg-background px-3 text-xs focus:outline-none">
                    <option value="">اختر ملف التخليص</option>
                    @foreach($customsAccounts as $customsBooking)
                        <option value="{{ $customsBooking->id }}"
                                data-customer="{{ $customsBooking->customer_company }}"
                                data-reference="{{ $customsBooking->customs_reference ?: $customsBooking->customs_bayan_no }}"
                                data-fees="{{ (float) $customsBooking->customs_fees }}"
                                data-suggested="{{ (float) ($customsBooking->customs_unbilled_total > 0 ? $customsBooking->customs_unbilled_total : max((float) $customsBooking->customs_fees, 0)) }}">
                            {{ $customsBooking->id }} — {{ $customsBooking->customer_company }} — {{ $customsBooking->customs_reference ?: 'بدون مرجع' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-1">
                    <label class="font-bold text-foreground">رسوم جمركية / مدفوعة نيابة عن العميل</label>
                    <input type="number" name="customs_government_fees" id="customs-government-fees" step="0.01" min="0" value="0" oninput="calculateCustomsInvoiceTotal()" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-foreground">أتعاب التخليص</label>
                    <input type="number" name="customs_service_fee" id="customs-service-fee" step="0.01" min="0" value="0" oninput="calculateCustomsInvoiceTotal()" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-foreground">رسوم أخرى</label>
                    <input type="number" name="customs_other_fee" id="customs-other-fee" step="0.01" min="0" value="0" oninput="calculateCustomsInvoiceTotal()" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                </div>
            </div>

            <div class="rounded-lg border border-border bg-secondary/30 p-3">
                <div class="text-[11px] font-bold text-muted-foreground">إجمالي فاتورة الجمارك</div>
                <div id="customs-invoice-total-display" class="mt-1 text-2xl font-black text-primary" dir="ltr">$0.00</div>
            </div>

            <div class="space-y-1">
                <label class="font-bold text-foreground">بيان الفاتورة</label>
                <textarea name="description" id="customs-invoice-description" rows="3" class="w-full rounded-lg border border-border bg-background p-3 focus:outline-none" placeholder="مثال: رسوم جمركية وأتعاب تخليص للطلب"></textarea>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="space-y-1">
                    <label class="font-bold text-foreground">تاريخ الإصدار</label>
                    <input type="date" name="issued_at" required value="{{ date('Y-m-d') }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-foreground">تاريخ الاستحقاق</label>
                    <input type="date" name="due_at" required value="{{ date('Y-m-d', strtotime('+7 days')) }}" class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                </div>
                <div class="space-y-1">
                    <label class="font-bold text-foreground">الحالة</label>
                    <select name="status" required class="h-10 w-full rounded-lg border border-border bg-background px-3 focus:outline-none">
                        <option value="غير مدفوعة">غير مدفوعة</option>
                        <option value="مدفوعة جزئياً">مدفوعة جزئياً</option>
                        <option value="مدفوعة">مدفوعة</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeCustomsInvoiceModal()" class="h-9 rounded-lg border border-border px-4 text-foreground transition-all hover:bg-secondary/40">إلغاء</button>
                <button type="submit" class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-primary px-4 font-bold text-primary-foreground shadow transition-all hover:opacity-90">
                    <i data-lucide="save" class="h-4 w-4"></i>
                    <span>حفظ وإضافة لحساب العميل</span>
                </button>
            </div>
        </form>
    </div>
</div>

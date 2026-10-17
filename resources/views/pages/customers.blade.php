@extends('layouts.app')

@section('title', 'إدارة العملاء — ' . config('company.name.ar'))

@section('head')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@26.9.2/build/css/intlTelInput.css">
<style>
    .iti {
        width: 100%;
        direction: ltr;
    }

    .iti input[type="tel"] {
        direction: ltr;
        text-align: right;
    }

    .iti__country-container {
        direction: ltr;
    }

    .iti__country-list {
        z-index: 70;
        color: hsl(var(--foreground));
        background: hsl(var(--background));
        border-color: hsl(var(--border));
        font-family: inherit;
        text-align: right;
    }

    .iti__search-input {
        height: 2.5rem;
        border-color: hsl(var(--border));
        background: hsl(var(--background));
        color: hsl(var(--foreground));
        font-family: inherit;
        text-align: right;
    }

    .iti__country {
        direction: ltr;
    }
</style>
@endsection

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs">إدارة النظام</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">إدارة العملاء</h1>
            <p class="mt-2 max-w-2xl text-white/80">أضف وحرر وعرض بيانات العملاء المرتبطين بالشحنات والعروض في النظام.</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
        @php
            $pendingPortalCustomers = $customers->where('portal_enabled', false);
        @endphp

        @if($pendingPortalCustomers->isNotEmpty())
            <div class="mb-5 rounded-xl border border-warning/25 bg-warning/10 p-4 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="flex items-center gap-2 text-sm font-black text-foreground">
                            <i data-lucide="user-check" class="h-4 w-4 text-warning"></i>
                            طلبات اعتماد حسابات العملاء
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $pendingPortalCustomers->count() }} حساب ينتظر اعتماد الدخول إلى بوابة العميل.
                        </p>
                    </div>
                </div>
                <div class="mt-4 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($pendingPortalCustomers->take(6) as $pendingCustomer)
                        <div class="flex items-center justify-between gap-3 rounded-lg border border-border bg-card px-3 py-2">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-black text-foreground">{{ $pendingCustomer->company }}</div>
                                <div class="truncate text-[11px] text-muted-foreground">{{ $pendingCustomer->email ?: $pendingCustomer->phone ?: 'بدون وسيلة تواصل' }}</div>
                            </div>
                            <form action="{{ route('customers.portal', $pendingCustomer) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="inline-flex h-8 items-center justify-center gap-1 rounded-lg bg-success px-3 text-[11px] font-black text-success-foreground hover:opacity-90">
                                    <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                    اعتماد
                                </button>
                            </form>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-border/60 bg-card shadow-card overflow-hidden">
            <div class="flex items-center justify-between border-b p-5">
                <div class="flex items-center gap-3">
                    <i data-lucide="users" class="h-5 w-5 text-muted-foreground"></i>
                    <div>
                        <div class="text-sm font-bold">قائمة العملاء</div>
                        <div class="text-xs text-muted-foreground">عرض العملاء المسجلين وربطهم بالطلبات.</div>
                    </div>
                </div>
                <button onclick="openCustomerModal()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground hover:opacity-95">
                    <i data-lucide="plus" class="h-4 w-4"></i> عميل جديد
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-right">الشركة</th>
                            <th class="px-4 py-3 text-right">المسؤول</th>
                            <th class="px-4 py-3 text-right">الهاتف</th>
                            <th class="px-4 py-3 text-right">البريد</th>
                            <th class="px-4 py-3 text-right">ملاحظات</th>
                            <th class="px-4 py-3 text-right">حالة البوابة</th>
                            <th class="px-4 py-3 text-right">تاريخ الإنشاء</th>
                            <th class="px-4 py-3 text-right"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customers as $customer)
                            <tr class="border-b border-border/40 hover:bg-secondary/20 transition-colors">
                                <td class="px-4 py-3 font-semibold">{{ $customer->company }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ $customer->contact_name ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground" dir="ltr">{{ $customer->phone ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ $customer->email ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ $customer->notes ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    @if($customer->portal_enabled)
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-success/25 bg-success/10 px-2.5 py-1 text-[11px] font-black text-success">
                                            <i data-lucide="badge-check" class="h-3.5 w-3.5"></i>
                                            معتمد
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full border border-warning/30 bg-warning/10 px-2.5 py-1 text-[11px] font-black text-warning">
                                            <i data-lucide="clock-3" class="h-3.5 w-3.5"></i>
                                            قيد المراجعة
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ $customer->created_at->toDateString() }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1 justify-end">
                                        <form action="{{ route('customers.portal', $customer->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="{{ $customer->portal_enabled ? 'h-8 w-8 hover:bg-secondary' : 'h-8 bg-success px-3 text-[11px] font-black text-success-foreground hover:opacity-90' }} inline-flex items-center justify-center gap-1 rounded-lg transition-all" title="{{ $customer->portal_enabled ? 'تعطيل بوابة العميل' : 'اعتماد بوابة العميل' }}">
                                                <i data-lucide="{{ $customer->portal_enabled ? 'user-x' : 'user-check' }}" class="h-3.5 w-3.5 {{ $customer->portal_enabled ? 'text-warning' : 'text-success-foreground' }}"></i>
                                                @unless($customer->portal_enabled)
                                                    <span>اعتماد</span>
                                                @endunless
                                            </button>
                                        </form>
                                        <button onclick="openCustomerModal({{ $customer->id }}, {{ json_encode(['company' => $customer->company, 'contact_name' => $customer->contact_name, 'phone' => $customer->phone, 'email' => $customer->email, 'notes' => $customer->notes]) }})" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-secondary transition-all" title="تعديل">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                        </button>
                                        <form action="{{ route('customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('حذف هذا العميل؟')" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-destructive/10 transition-all" title="حذف">
                                                <i data-lucide="trash-2" class="h-3.5 w-3.5 text-destructive"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-12 text-center text-muted-foreground">
                                    <i data-lucide="users" class="h-10 w-10 mx-auto mb-3 opacity-30"></i>
                                    <p>لا يوجد عملاء مسجلين</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

{{-- Add Customer Modal --}}
<div id="customer-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-lg bg-card rounded-2xl border border-border shadow-elegant" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-5">
            <h3 id="modal-title" class="font-extrabold text-lg">عميل جديد</h3>
            <button onclick="closeCustomerModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="customer-form" action="{{ route('customers.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <div id="method-field"></div>
            <input type="hidden" name="customer_form_id" id="customer-form-id" value="{{ old('customer_form_id') }}">

            @if($errors->any())
                <div class="rounded-lg border border-destructive/25 bg-destructive/10 p-3 text-xs font-semibold text-destructive" data-customer-error-summary>
                    يرجى مراجعة الحقول المشار إليها قبل حفظ العميل.
                </div>
            @endif

            <div data-customer-field="company">
                <label class="mb-1.5 block text-sm font-semibold">اسم الشركة <span class="text-destructive">*</span></label>
                <input type="text" name="company" id="customer-company" required value="{{ old('company') }}" placeholder="مثال: الشركة المتحدة" class="w-full h-10 border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 {{ $errors->has('company') ? 'border-destructive focus:ring-destructive' : 'border-border focus:ring-accent' }}">
                <p class="{{ $errors->has('company') ? '' : 'hidden' }} mt-1 text-[11px] font-semibold text-destructive" data-customer-error>{{ $errors->first('company') }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-semibold">المسؤول</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" placeholder="مثال: إبراهيم الهادي" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div data-customer-field="phone">
                    <label class="mb-1.5 block text-sm font-semibold">الهاتف</label>
                    <input type="hidden" name="phone_country_code" value="{{ old('phone_country_code', '+967') }}">
                    <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="7xx xxx xxx" dir="ltr" class="customer-phone-input w-full h-10 border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 {{ $errors->has('phone') ? 'border-destructive focus:ring-destructive' : 'border-border focus:ring-accent' }}">
                    <p class="customer-phone-error {{ $errors->has('phone') ? '' : 'hidden' }} mt-1 text-[11px] font-semibold text-destructive" data-customer-error>{{ $errors->first('phone') ?: 'رقم الهاتف غير صحيح للدولة المحددة.' }}</p>
                    <p class="mt-1 text-[11px] text-muted-foreground">اختر الدولة ثم اكتب الرقم المحلي. سيتم حفظ الرقم مع مفتاح الدولة.</p>
                </div>
            </div>

            <div data-customer-field="email">
                <label class="mb-1.5 block text-sm font-semibold">البريد الإلكتروني</label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="customer@example.com" class="w-full h-10 border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 {{ $errors->has('email') ? 'border-destructive focus:ring-destructive' : 'border-border focus:ring-accent' }}">
                <p class="{{ $errors->has('email') ? '' : 'hidden' }} mt-1 text-[11px] font-semibold text-destructive" data-customer-error>{{ $errors->first('email') }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold">ملاحظات</label>
                <textarea name="notes" rows="3" placeholder="ملاحظات إضافية عن العميل" class="w-full p-3 border border-border bg-background rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">{{ old('notes') }}</textarea>
            </div>

            <div class="flex gap-3 justify-end border-t pt-4">
                <button type="button" onclick="closeCustomerModal()" class="h-10 rounded-lg border border-border px-5 text-sm font-semibold hover:bg-secondary transition-all">إلغاء</button>
                <button type="submit" class="h-10 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground hover:opacity-95">حفظ العميل</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
@php
    $customerDuplicateRecords = $customers->map(function ($customer) {
        return [
            'id' => $customer->id,
            'company' => $customer->company,
            'phone' => $customer->phone,
            'email' => $customer->email,
        ];
    })->values();

    $customerOldInput = [
        'id' => old('customer_form_id'),
        'company' => old('company'),
        'contact_name' => old('contact_name'),
        'phone' => old('phone'),
        'email' => old('email'),
        'notes' => old('notes'),
    ];
@endphp
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@26.9.2/build/js/intlTelInputWithUtils.min.js"></script>
<script>
    const customerPhoneInstances = new Map();
    const existingCustomers = @json($customerDuplicateRecords);
    const shouldReopenCustomerModal = @json($errors->any());
    const customerOldInput = @json($customerOldInput);

    function getCustomerPhoneCountryData(iti) {
        if (!iti) return null;
        if (typeof iti.getSelectedCountryData === 'function') return iti.getSelectedCountryData();
        if (typeof iti.getSelectedCountry === 'function') return iti.getSelectedCountry();
        return null;
    }

    function getCustomerPhoneCountryCode(iti) {
        const country = getCustomerPhoneCountryData(iti);
        return country?.dialCode ? `+${country.dialCode}` : '+967';
    }

    function syncCustomerPhoneCountryCode(input) {
        const iti = customerPhoneInstances.get(input);
        const hiddenInput = input.closest('div')?.querySelector('input[name="phone_country_code"]');
        if (hiddenInput) hiddenInput.value = getCustomerPhoneCountryCode(iti);
    }

    function getCustomerFieldError(input) {
        return input.closest('[data-customer-field]')?.querySelector('[data-customer-error]') || null;
    }

    function clearCustomerFieldError(input) {
        const error = getCustomerFieldError(input);
        if (error) {
            error.textContent = '';
            error.classList.add('hidden');
        }
        input.removeAttribute('aria-invalid');
        input.classList.remove('border-destructive', 'focus:ring-destructive');
        input.classList.add('border-border', 'focus:ring-accent');
    }

    function showCustomerFieldError(input, message) {
        const error = getCustomerFieldError(input);
        if (error) {
            error.textContent = message;
            error.classList.remove('hidden');
        }
        input.setAttribute('aria-invalid', 'true');
        input.classList.remove('border-border', 'focus:ring-accent');
        input.classList.add('border-destructive', 'focus:ring-destructive');
    }

    function clearCustomerFormErrors() {
        customerForm.querySelector('[data-customer-error-summary]')?.classList.add('hidden');
        customerForm.querySelectorAll('[data-customer-field] input:not([type="hidden"]), [data-customer-field] textarea').forEach(clearCustomerFieldError);
    }

    function clearCustomerPhoneError(input) {
        clearCustomerFieldError(input);
    }

    function showCustomerPhoneError(input, message = 'رقم الهاتف غير صحيح للدولة المحددة.') {
        showCustomerFieldError(input, message);
        input.focus();
    }

    function initCustomerPhoneInput() {
        if (!window.intlTelInput) return;

        document.querySelectorAll('.customer-phone-input[name="phone"]').forEach(input => {
            if (input.dataset.customerPhoneReady === '1') return;

            const iti = window.intlTelInput(input, {
                initialCountry: 'ye',
                separateDialCode: true,
                showFlags: true,
                countrySearch: true,
                strictMode: true,
                formatAsYouType: true,
                numberDisplayFormat: window.intlTelInput.NUMBER_FORMAT?.NATIONAL || 'NATIONAL',
                countryNameLocale: 'ar',
                uiTranslations: {
                    searchPlaceholder: 'ابحث باسم الدولة أو مفتاح الاتصال',
                    noResultsFound: 'لا توجد نتائج',
                },
            });

            input.dataset.customerPhoneReady = '1';
            customerPhoneInstances.set(input, iti);
            syncCustomerPhoneCountryCode(input);

            const syncAndClear = () => {
                syncCustomerPhoneCountryCode(input);
                clearCustomerPhoneError(input);
            };

            input.addEventListener('countrychange', syncAndClear);
            input.addEventListener('input', syncAndClear);
            input.addEventListener('blur', () => syncCustomerPhoneCountryCode(input));
        });
    }

    async function validateAndPrepareCustomerPhone(form) {
        const phoneInput = form.querySelector('.customer-phone-input[name="phone"]');
        if (!phoneInput) return true;

        const iti = customerPhoneInstances.get(phoneInput);
        if (!iti) {
            showCustomerPhoneError(phoneInput, 'تعذر تهيئة حقل الهاتف. يرجى تحديث الصفحة والمحاولة مرة أخرى.');
            return false;
        }

        if (!phoneInput.value.trim()) {
            syncCustomerPhoneCountryCode(phoneInput);
            return true;
        }

        try {
            if (iti.promise) await iti.promise;
        } catch (error) {
            showCustomerPhoneError(phoneInput, 'تعذر تحميل بيانات التحقق من رقم الهاتف. يرجى المحاولة مرة أخرى.');
            return false;
        }

        syncCustomerPhoneCountryCode(phoneInput);

        if (!iti.isValidNumber()) {
            showCustomerPhoneError(phoneInput, 'رقم الهاتف غير صحيح للدولة المحددة.');
            return false;
        }

        phoneInput.value = iti.getNumber();
        return true;
    }

    function setCustomerPhoneValue(input, value) {
        const normalized = String(value || '').trim();
        const iti = customerPhoneInstances.get(input);

        if (iti && normalized.startsWith('+') && typeof iti.setNumber === 'function') {
            iti.setNumber(normalized);
        } else {
            input.value = normalized;
        }

        syncCustomerPhoneCountryCode(input);
    }

    function normalizeCustomerText(value) {
        return String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
    }

    function normalizeCustomerEmail(value) {
        return String(value || '').trim().toLowerCase();
    }

    function normalizeCustomerPhone(value) {
        const converted = String(value || '')
            .replace(/[٠-٩۰-۹]/g, digit => '٠١٢٣٤٥٦٧٨٩۰۱۲۳۴۵۶۷۸۹'.indexOf(digit) % 10);
        let digits = converted.replace(/\D+/g, '');

        if (digits.startsWith('00')) {
            digits = digits.slice(2);
        }

        return digits.replace(/^0+/, '');
    }

    function phonesLookEqual(phone, existingPhone) {
        if (!phone || !existingPhone) return false;
        if (phone === existingPhone) return true;

        const shorterLength = Math.min(phone.length, existingPhone.length);
        return shorterLength >= 7 && (phone.endsWith(existingPhone) || existingPhone.endsWith(phone));
    }

    function findDuplicateCustomer(field, value, currentId) {
        return existingCustomers.find(customer => {
            if (currentId && String(customer.id) === String(currentId)) return false;

            if (field === 'company') {
                return normalizeCustomerText(customer.company) === value;
            }

            if (field === 'email') {
                return normalizeCustomerEmail(customer.email) === value;
            }

            return phonesLookEqual(normalizeCustomerPhone(customer.phone), value);
        }) || null;
    }

    function validateCustomerDuplicates(form) {
        const currentId = form.querySelector('#customer-form-id')?.value || '';
        const fields = [
            {
                name: 'company',
                value: normalizeCustomerText(form.querySelector('[name="company"]')?.value),
                message: 'يوجد عميل مسجل بنفس اسم الشركة.',
            },
            {
                name: 'email',
                value: normalizeCustomerEmail(form.querySelector('[name="email"]')?.value),
                message: 'يوجد عميل مسجل بنفس البريد الإلكتروني.',
            },
            {
                name: 'phone',
                value: normalizeCustomerPhone(form.querySelector('[name="phone"]')?.value),
                message: 'يوجد عميل مسجل بنفس رقم الهاتف.',
            },
        ];
        let firstInvalidInput = null;

        fields.forEach(field => {
            if (!field.value) return;

            const input = form.querySelector(`[name="${field.name}"]`);
            if (!input) return;

            if (findDuplicateCustomer(field.name, field.value, currentId)) {
                showCustomerFieldError(input, field.message);
                firstInvalidInput ||= input;
            }
        });

        if (firstInvalidInput) {
            firstInvalidInput.focus();
            return false;
        }

        return true;
    }

    const customerModal = document.getElementById('customer-modal');
    const customerForm = document.getElementById('customer-form');

    function openCustomerModal(id = null, data = null, preserveErrors = false) {
        customerForm.reset();
        if (!preserveErrors) {
            clearCustomerFormErrors();
        }

        document.getElementById('customer-form-id').value = id || '';
        const phoneInput = customerForm.querySelector('.customer-phone-input[name="phone"]');
        const iti = phoneInput ? customerPhoneInstances.get(phoneInput) : null;
        if (phoneInput && iti) {
            if (typeof iti.setCountry === 'function') iti.setCountry('ye');
            else if (typeof iti.setSelectedCountry === 'function') iti.setSelectedCountry('ye');
            if (!preserveErrors) clearCustomerPhoneError(phoneInput);
            syncCustomerPhoneCountryCode(phoneInput);
        }

        document.getElementById('modal-title').textContent = id ? 'تعديل العميل' : 'عميل جديد';
        const methodField = document.getElementById('method-field');

        if (id) {
            customerForm.action = `/customers/${id}`;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        } else {
            customerForm.action = '{{ route('customers.store') }}';
            methodField.innerHTML = '';
        }

        const formData = data || {};
        document.getElementById('customer-company').value = formData.company || '';
        customerForm.querySelector('[name="contact_name"]').value = formData.contact_name || '';
        const phoneField = customerForm.querySelector('[name="phone"]');
        if (phoneField) {
            setCustomerPhoneValue(phoneField, formData.phone || '');
        }
        customerForm.querySelector('[name="email"]').value = formData.email || '';
        customerForm.querySelector('[name="notes"]').value = formData.notes || '';

        customerModal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeCustomerModal() {
        customerModal.classList.add('hidden');
    }

    initCustomerPhoneInput();

    if (customerForm && customerForm.dataset.customerPhoneSubmitReady !== '1') {
        customerForm.dataset.customerPhoneSubmitReady = '1';
        customerForm.addEventListener('submit', async event => {
            if (customerForm.dataset.customerPhoneSubmitApproved === '1') {
                delete customerForm.dataset.customerPhoneSubmitApproved;
                return;
            }

            event.preventDefault();
            clearCustomerFormErrors();

            if (await validateAndPrepareCustomerPhone(customerForm) && validateCustomerDuplicates(customerForm)) {
                customerForm.dataset.customerPhoneSubmitApproved = '1';
                if (customerForm.requestSubmit) {
                    customerForm.requestSubmit(event.submitter || undefined);
                } else {
                    HTMLFormElement.prototype.submit.call(customerForm);
                }
            }
        });
    }

    customerForm.querySelectorAll('[name="company"], [name="email"], [name="phone"]').forEach(input => {
        input.addEventListener('input', () => clearCustomerFieldError(input));
    });

    if (shouldReopenCustomerModal) {
        openCustomerModal(customerOldInput.id || null, customerOldInput, true);
    }

    customerModal.addEventListener('click', closeCustomerModal);
</script>
@endsection

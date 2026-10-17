@extends('layouts.app')

@section('title', 'إدارة الوكلاء — ' . config('company.name.ar'))

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
</style>
@endsection

@section('content')
<div>
    <section class="bg-hero py-14 text-white">
        <div class="container mx-auto px-4">
            <span class="inline-flex items-center rounded-full border border-white/20 bg-white/15 px-3 py-1 text-xs">إدارة النظام</span>
            <h1 class="mt-3 text-3xl font-extrabold md:text-4xl">إدارة الوكلاء</h1>
            <p class="mt-2 max-w-2xl text-white/80">أضف وكلاء محليين أو خارجيين، وعدّل بيانات التواصل والبلد والمنطقة من نفس الصفحة.</p>
        </div>
    </section>

    <section class="container mx-auto px-4 py-8">
        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-xl border border-border/60 bg-card p-5 shadow-card">
                <div class="text-2xl font-extrabold">{{ $agents->count() }}</div>
                <div class="text-sm text-muted-foreground">إجمالي الوكلاء</div>
            </div>
            <div class="rounded-xl border border-border/60 bg-card p-5 shadow-card">
                <div class="text-2xl font-extrabold">{{ $agents->where('type', 'local')->count() }}</div>
                <div class="text-sm text-muted-foreground">وكلاء محليين</div>
            </div>
            <div class="rounded-xl border border-border/60 bg-card p-5 shadow-card">
                <div class="text-2xl font-extrabold">{{ $agents->where('type', 'external')->count() }}</div>
                <div class="text-sm text-muted-foreground">وكلاء خارجيين</div>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-center gap-2 border-b border-border/60">
            <a href="{{ route('agents.index', ['tab' => 'agents']) }}" class="border-b-2 px-4 py-3 text-sm font-bold {{ $activeTab === 'agents' ? 'border-accent text-accent' : 'border-transparent text-muted-foreground hover:text-foreground' }}">
                إدارة الوكلاء
            </a>
            <a href="{{ route('agents.index', ['tab' => 'orders']) }}" class="border-b-2 px-4 py-3 text-sm font-bold {{ $activeTab === 'orders' ? 'border-accent text-accent' : 'border-transparent text-muted-foreground hover:text-foreground' }}">
                طلبات الوكلاء
            </a>
        </div>

        @if($activeTab === 'agents')
        <div class="mt-6 rounded-xl border border-border/60 bg-card shadow-card overflow-hidden">
            <div class="flex items-center justify-between border-b p-5">
                <div class="flex items-center gap-3">
                    <i data-lucide="globe" class="h-5 w-5 text-muted-foreground"></i>
                    <div>
                        <div class="text-sm font-bold">قائمة الوكلاء</div>
                    </div>
                </div>
                <button onclick="openAgentModal()" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground hover:opacity-95">
                    <i data-lucide="plus" class="h-4 w-4"></i>
                    <span>وكيل جديد</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-right">الاسم</th>
                            <th class="px-4 py-3 text-right">النوع</th>
                            <th class="px-4 py-3 text-right">المنطقة</th>
                            <th class="px-4 py-3 text-right">البلد</th>
                            <th class="px-4 py-3 text-right">الهاتف</th>
                            <th class="px-4 py-3 text-right">البريد</th>
                            <th class="px-4 py-3 text-right">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agents as $agent)
                            <tr class="border-b border-border/40 hover:bg-secondary/20 transition-colors">
                                <td class="px-4 py-3 font-semibold">{{ $agent->name }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $agent->type === 'local' ? 'bg-primary/10 text-primary' : 'bg-accent/10 text-accent' }}">
                                        {{ $agent->type === 'local' ? 'محلي' : 'خارجي' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ $agent->region ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ $agent->country ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground" dir="ltr">{{ $agent->phone ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm text-muted-foreground">{{ $agent->email ?: '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2 justify-end">
                                        <button type="button" onclick="openAgentModal(@js([
                                            'id' => $agent->id,
                                            'name' => $agent->name,
                                            'phone' => $agent->phone,
                                            'email' => $agent->email,
                                            'type' => $agent->type,
                                            'region' => $agent->region,
                                            'country' => $agent->country,
                                        ]))" class="h-8 w-8 flex items-center justify-center rounded-lg hover:bg-secondary transition-all" title="تعديل">
                                            <i data-lucide="pencil" class="h-3.5 w-3.5 text-muted-foreground"></i>
                                        </button>
                                        <form action="{{ route('agents.destroy', $agent->id) }}" method="POST" onsubmit="return confirm('حذف هذا الوكيل؟')">
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
                                <td colspan="7" class="py-12 text-center text-muted-foreground">
                                    <i data-lucide="globe" class="h-10 w-10 mx-auto mb-3 opacity-30"></i>
                                    <p>لا يوجد وكلاء مسجلون</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="mt-6 rounded-xl border border-border/60 bg-card shadow-card overflow-hidden">
            <div class="flex flex-col gap-4 border-b p-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-3">
                    <i data-lucide="clipboard-list" class="h-5 w-5 text-muted-foreground"></i>
                    <div>
                        <div class="text-sm font-bold">طلبات الوكلاء المعتمدة</div>
                        <div class="text-xs text-muted-foreground">تظهر الطلبات بعد اعتمادها من المبيعات فقط.</div>
                    </div>
                </div>
                <a href="{{ route('agents.orders.export', request()->except('orders_page')) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-gradient-accent px-5 text-sm font-bold text-accent-foreground hover:opacity-95">
                    <i data-lucide="file-spreadsheet" class="h-4 w-4"></i>
                    <span>تصدير Excel</span>
                </a>
            </div>

            <form method="GET" action="{{ route('agents.index') }}" class="grid gap-3 border-b p-5 md:grid-cols-2 lg:grid-cols-5">
                <input type="hidden" name="tab" value="orders">
                <input name="search" value="{{ request('search') }}" placeholder="البحث باسم الوكيل" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                <select name="agent_type" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                    <option value="">كل أنواع الوكلاء</option>
                    <option value="local" @selected(request('agent_type') === 'local')>محلي</option>
                    <option value="external" @selected(request('agent_type') === 'external')>خارجي</option>
                </select>
                <select name="route" class="h-10 rounded-lg border border-border bg-background px-3 text-sm focus:outline-none focus:ring-1 focus:ring-accent" onchange="this.form.pol.value=this.value.split('|')[0] || ''; this.form.pod.value=this.value.split('|')[1] || ''">
                    <option value="">كل مسارات الشحن</option>
                    @foreach($agentOrderRoutes as $route)
                        <option value="{{ $route->pol }}|{{ $route->pod }}" @selected(request('pol') === $route->pol && request('pod') === $route->pod)>{{ $route->pol }} → {{ $route->pod }}</option>
                    @endforeach
                </select>
                <input type="hidden" name="pol" value="{{ request('pol') }}">
                <input type="hidden" name="pod" value="{{ request('pod') }}">
                <div class="flex gap-2">
                    <input type="date" name="approved_from" value="{{ request('approved_from') }}" title="من تاريخ الاعتماد" class="h-10 min-w-0 flex-1 rounded-lg border border-border bg-background px-2 text-sm">
                    <input type="date" name="approved_to" value="{{ request('approved_to') }}" title="إلى تاريخ الاعتماد" class="h-10 min-w-0 flex-1 rounded-lg border border-border bg-background px-2 text-sm">
                </div>
                <button class="h-10 rounded-lg bg-secondary px-4 text-sm font-bold hover:bg-secondary/70">تطبيق الفلاتر</button>
            </form>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-secondary/40 text-xs text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 text-right">اسم الوكيل</th>
                            <th class="px-4 py-3 text-right">عدد الطلبات المؤكدة</th>
                            <th class="px-4 py-3 text-right">مسار الشحن</th>
                            <th class="px-4 py-3 text-right">سعر الشراء</th>
                            <th class="px-4 py-3 text-right">نوع الوكيل</th>
                            <th class="px-4 py-3 text-right">الطلب</th>
                            <th class="px-4 py-3 text-right">تاريخ الاعتماد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agentOrders as $booking)
                            <tr class="border-b border-border/40 hover:bg-secondary/20 transition-colors">
                                <td class="px-4 py-3 font-semibold">{{ $booking->agent?->name ?? '—' }}</td>
                                <td class="px-4 py-3 font-bold">{{ $booking->confirmed_orders_count }}</td>
                                <td class="px-4 py-3">{{ $booking->pol }} → {{ $booking->pod }}</td>
                                <td class="px-4 py-3" dir="ltr">{{ $booking->formatPricingMoney($booking->agent_purchase_amount) }}</td>
                                <td class="px-4 py-3">{{ $booking->agent?->type === 'local' ? 'محلي' : 'خارجي' }}</td>
                                <td class="px-4 py-3"><a class="font-semibold text-accent hover:underline" href="{{ route('orders.show', $booking->id) }}">{{ $booking->id }}</a></td>
                                <td class="px-4 py-3 text-muted-foreground">{{ $booking->decided_at?->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="py-12 text-center text-muted-foreground">لا توجد طلبات معتمدة مطابقة للفلاتر.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($agentOrders->hasPages())
                <div class="border-t p-4">{{ $agentOrders->links() }}</div>
            @endif
        </div>
        @endif
    </section>
</div>

<div id="agent-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="relative w-full max-w-2xl bg-card rounded-2xl border border-border shadow-elegant" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between border-b p-5">
            <h3 id="modal-title" class="font-extrabold text-lg">وكيل جديد</h3>
            <button onclick="closeAgentModal()" class="h-8 w-8 rounded-lg hover:bg-secondary/40 flex items-center justify-center">
                <i data-lucide="x" class="h-5 w-5"></i>
            </button>
        </div>
        <form id="agent-form" action="{{ route('agents.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" id="agent-method" value="PUT" disabled>

            <div>
                <label for="agent-name" class="mb-1.5 block text-sm font-semibold">الاسم الكامل <span class="text-destructive">*</span></label>
                <input type="text" name="name" id="agent-name" required placeholder="مثال: Al-Hadi Shipping" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="agent-phone" class="mb-1.5 block text-sm font-semibold">الهاتف</label>
                    <input type="hidden" name="phone_country_code" id="agent-phone-country-code" value="+967">
                    <input type="tel" name="phone" id="agent-phone" dir="ltr" placeholder="7xx xxx xxx" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent text-right">
                    <p class="mt-1 text-[11px] text-muted-foreground">اختر مفتاح الدولة من الحقل نفسه، أو ابحث باسم الدولة/المفتاح.</p>
                </div>
                <div>
                    <label for="agent-email" class="mb-1.5 block text-sm font-semibold">البريد الإلكتروني</label>
                    <input type="email" name="email" id="agent-email" placeholder="agent@example.com" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div>
                    <label for="agent-type" class="mb-1.5 block text-sm font-semibold">النوع <span class="text-destructive">*</span></label>
                    <select name="type" id="agent-type" required class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="local">محلي</option>
                        <option value="external">خارجي</option>
                    </select>
                </div>
                <div>
                    <label for="agent-region" class="mb-1.5 block text-sm font-semibold">المنطقة</label>
                    <input type="text" name="region" id="agent-region" placeholder="صنعاء / عدن" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                </div>
                <div>
                    <label for="agent-country" class="mb-1.5 block text-sm font-semibold">البلد</label>
                    <select name="country" id="agent-country" class="w-full h-10 border border-border bg-background px-3 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-accent">
                        <option value="">اختر البلد</option>
                        @foreach($countryOptions as $country)
                            <option value="{{ $country['name'] }}">{{ trim(($country['name_ar'] ?: $country['name']) . ' - ' . $country['name']) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex gap-3 justify-end border-t pt-4">
                <button type="button" onclick="closeAgentModal()" class="h-10 rounded-lg border border-border px-5 text-sm font-semibold hover:bg-secondary transition-all">إلغاء</button>
                <button type="submit" class="h-10 rounded-lg bg-gradient-accent px-6 text-sm font-bold text-accent-foreground hover:opacity-95">حفظ الوكيل</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/intl-tel-input@26.9.2/build/js/intlTelInputWithUtils.min.js"></script>
<script>
    const agentModal = document.getElementById('agent-modal');
    const agentForm = document.getElementById('agent-form');
    const agentMethod = document.getElementById('agent-method');
    const agentPhoneInput = document.getElementById('agent-phone');
    const agentPhoneCountryCode = document.getElementById('agent-phone-country-code');
    let agentPhoneIti = null;

    function getAgentPhoneCountryCode() {
        const country = agentPhoneIti?.getSelectedCountryData?.();
        return country?.dialCode ? `+${country.dialCode}` : '+967';
    }

    function syncAgentPhoneCountryCode() {
        agentPhoneCountryCode.value = getAgentPhoneCountryCode();
    }

    function setAgentPhoneValue(value) {
        const normalized = String(value || '').trim();

        if (!agentPhoneIti) {
            agentPhoneInput.value = normalized;
            return;
        }

        if (normalized.startsWith('+')) {
            agentPhoneIti.setNumber(normalized);
        } else {
            agentPhoneInput.value = normalized;
            agentPhoneIti.setCountry('ye');
        }

        syncAgentPhoneCountryCode();
    }

    function initAgentPhoneInput() {
        if (!window.intlTelInput || agentPhoneIti || !agentPhoneInput) return;

        agentPhoneIti = window.intlTelInput(agentPhoneInput, {
            initialCountry: 'ye',
            separateDialCode: true,
            showFlags: true,
            countrySearch: true,
            formatAsYouType: true,
            countryNameLocale: 'ar',
            uiTranslations: {
                searchPlaceholder: 'ابحث باسم الدولة أو مفتاح الاتصال',
                noResultsFound: 'لا توجد نتائج',
            },
        });

        agentPhoneInput.addEventListener('countrychange', syncAgentPhoneCountryCode);
        agentPhoneInput.addEventListener('input', syncAgentPhoneCountryCode);
        syncAgentPhoneCountryCode();
    }

    function openAgentModal(agent = null) {
        initAgentPhoneInput();
        agentForm.reset();

        if (agent) {
            document.getElementById('modal-title').textContent = 'تعديل بيانات الوكيل';
            agentForm.action = `/agents/${agent.id}`;
            agentMethod.disabled = false;

            document.getElementById('agent-name').value = agent.name || '';
            document.getElementById('agent-email').value = agent.email || '';
            document.getElementById('agent-type').value = agent.type || 'local';
            document.getElementById('agent-region').value = agent.region || '';
            document.getElementById('agent-country').value = agent.country || '';
            setAgentPhoneValue(agent.phone || '');
        } else {
            document.getElementById('modal-title').textContent = 'وكيل جديد';
            agentForm.action = '{{ route('agents.store') }}';
            agentMethod.disabled = true;
            setAgentPhoneValue('');
        }

        agentModal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeAgentModal() {
        agentModal.classList.add('hidden');
    }

    document.addEventListener('DOMContentLoaded', initAgentPhoneInput);
    agentForm.addEventListener('submit', syncAgentPhoneCountryCode);
    agentModal.addEventListener('click', closeAgentModal);
</script>
@endsection

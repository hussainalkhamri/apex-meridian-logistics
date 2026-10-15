@php
    $isCustomer = $accountType === 'customer';
    $accountTitle = $isCustomer ? __('messages.account_statement_customer_label') : __('messages.account_statement_agent_label');
    $accountName = $isCustomer ? $account->company : $account->name;
    $contactName = $isCustomer ? $account->contact_name : null;
    $accountPhone = $account->phone ?? null;
    $accountEmail = $account->email ?? null;
    $statementTitle = $statementType === 'detailed' ? __('messages.account_statement_detailed_title') : __('messages.account_statement_summary_title');
    $scopeLabels = [
        'all' => 'كل الحركات',
        'freight' => 'الشحن والفواتير العامة',
        'customs' => 'الجمارك فقط',
    ];
    $scopeLabel = $isCustomer ? ($scopeLabels[$statementScope ?? 'all'] ?? $scopeLabels['all']) : 'حساب الوكيل';
    $periodLabel = ($dateFrom ? $dateFrom->toDateString() : __('messages.account_statement_start_of_period')) . ' ' . __('messages.account_statement_to') . ' ' . ($dateTo ? $dateTo->toDateString() : __('messages.account_statement_today'));
    $formatMoney = fn ($value) => '$ ' . number_format(abs((float) $value), 2);
    $formatBalance = function ($value) use ($formatMoney) {
        $amount = (float) $value;

        if (abs($amount) < 0.01) {
            return __('messages.account_statement_balanced');
        }

        return ($amount > 0 ? __('messages.account_statement_debit_prefix') : __('messages.account_statement_credit_prefix')) . $formatMoney($amount);
    };
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $statementTitle }} — {{ $accountName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Inter:wght@400;650;850&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandSky: '#c6192b',
                        brandDark: '#26376b',
                    },
                    fontFamily: {
                        sans: ['Cairo', 'Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        @media print {
            @page { size: A4 portrait; margin: 6mm; }
            html, body { background: #fff !important; margin: 0; padding: 0; }
            body { font-size: 9px; line-height: 1.2; }
            .print-hidden { display: none !important; }
            .print-single-page { border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 auto !important; width: 100% !important; max-width: 100% !important; overflow: hidden !important; page-break-inside: avoid !important; }
            .statement-paper { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
    </style>
    @include('partials.company-document-style')
</head>
<body class="bg-slate-50 px-4 py-8 font-sans text-slate-800">
    <div class="print-hidden mx-auto mb-6 flex max-w-[200mm] justify-end gap-2">
        <button onclick="window.print()" class="inline-flex h-10 items-center justify-center rounded-lg bg-brandSky px-4 text-xs font-bold text-white shadow-sm hover:opacity-95">
            <i data-lucide="printer" class="ml-2 h-4 w-4"></i>
            <span>{{ __('messages.account_statement_print_button') }}</span>
        </button>
        <button onclick="window.close()" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">
            {{ __('messages.account_statement_close_button') }}
        </button>
    </div>

    <article class="statement-paper print-single-page mx-auto max-w-[200mm] rounded-2xl border border-slate-200 bg-white p-8 text-[12px] leading-relaxed shadow-lg md:p-10">
        @include('partials.company-letterhead')

        <section class="my-6 flex items-center justify-between border-b-2 border-brandSky pb-4">
            <div>
                <h2 class="text-2xl font-black text-brandSky">{{ $statementTitle }}</h2>
                <p class="mt-1 text-[10px] font-bold text-slate-400">{{ __('messages.account_statement_heading_detail', ['accountTitle' => $accountTitle]) }}</p>
            </div>
            <div class="text-left text-xs text-slate-500" dir="ltr">
                <p>No: <strong class="font-mono text-brandSky">{{ $statementNumber }}</strong></p>
                <p>Date: <strong class="font-mono text-slate-700">{{ now()->toDateString() }}</strong></p>
            </div>
        </section>

        <section class="grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs sm:grid-cols-2">
            <div>
                <div class="text-[10px] font-bold text-slate-400">{{ __('messages.account_statement_account_label') }}</div>
                <div class="mt-1 text-base font-black text-slate-900">{{ $accountName }}</div>
                @if($contactName)
                    <div class="mt-0.5 text-[10px] font-semibold text-slate-500">{{ $contactName }}</div>
                @endif
            </div>
            <div>
                <div class="text-[10px] font-bold text-slate-400">{{ __('messages.account_statement_period_label') }}</div>
                <div class="mt-1 font-bold text-slate-800">{{ $periodLabel }}</div>
                <div class="mt-0.5 text-[10px] font-bold text-brandSky">{{ $scopeLabel }}</div>
                <div class="mt-0.5 text-[10px] text-slate-500">{{ $accountPhone ?: __('messages.account_statement_no_phone') }} @if($accountEmail) · {{ $accountEmail }} @endif</div>
            </div>
        </section>

        <section class="mt-5 grid gap-3 sm:grid-cols-4">
            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-[10px] font-bold text-slate-400">{{ __('messages.account_statement_opening_balance') }}</div>
                <div class="mt-1 font-black text-slate-900" dir="ltr">{{ $formatBalance($statement['opening_balance']) }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-[10px] font-bold text-slate-400">{{ __('messages.account_statement_total_debit') }}</div>
                <div class="mt-1 font-black text-emerald-700" dir="ltr">{{ $formatMoney($statement['period_debit']) }}</div>
            </div>
            <div class="rounded-lg border border-slate-200 p-3">
                <div class="text-[10px] font-bold text-slate-400">{{ __('messages.account_statement_total_credit') }}</div>
                <div class="mt-1 font-black text-red-700" dir="ltr">{{ $formatMoney($statement['period_credit']) }}</div>
            </div>
            <div class="rounded-lg border border-brandSky/30 bg-brandSky/5 p-3">
                <div class="text-[10px] font-bold text-brandSky">{{ __('messages.account_statement_closing_balance') }}</div>
                <div class="mt-1 font-black text-brandDark" dir="ltr">{{ $formatBalance($statement['closing_balance']) }}</div>
            </div>
        </section>

        @if($statementType === 'detailed')
            <section class="mt-6">
                <table class="w-full border-collapse border border-slate-200 text-[10.5px]">
                    <thead class="bg-slate-100 text-slate-500">
                        <tr>
                            <th class="border border-slate-200 p-2">{{ __('messages.account_statement_date') }}</th>
                            <th class="border border-slate-200 p-2">{{ __('messages.account_statement_document') }}</th>
                            <th class="border border-slate-200 p-2">{{ __('messages.account_statement_request') }}</th>
                            <th class="border border-slate-200 p-2">{{ __('messages.account_statement_statement') }}</th>
                            <th class="border border-slate-200 p-2">{{ __('messages.account_statement_debit') }}</th>
                            <th class="border border-slate-200 p-2">{{ __('messages.account_statement_credit') }}</th>
                            <th class="border border-slate-200 p-2">{{ __('messages.account_statement_balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($statement['transactions'] as $transaction)
                            <tr>
                                <td class="border border-slate-200 p-2 font-mono text-slate-500" dir="ltr">{{ $transaction['date']->toDateString() }}</td>
                                <td class="border border-slate-200 p-2">
                                    <div class="font-bold text-slate-800">{{ $transaction['document_type'] }}</div>
                                    <div class="font-mono text-[9px] text-slate-500" dir="ltr">{{ $transaction['document'] }}</div>
                                </td>
                                <td class="border border-slate-200 p-2 font-mono text-slate-500" dir="ltr">{{ $transaction['booking_id'] ?: '—' }}</td>
                                <td class="border border-slate-200 p-2 text-slate-700">{{ $transaction['description'] }}</td>
                                <td class="border border-slate-200 p-2 font-bold text-emerald-700" dir="ltr">{{ $transaction['debit'] > 0 ? $formatMoney($transaction['debit']) : '—' }}</td>
                                <td class="border border-slate-200 p-2 font-bold text-red-700" dir="ltr">{{ $transaction['credit'] > 0 ? $formatMoney($transaction['credit']) : '—' }}</td>
                                <td class="border border-slate-200 p-2 font-black text-slate-900" dir="ltr">{{ $formatBalance($transaction['balance']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="border border-slate-200 p-6 text-center text-slate-400">{{ __('messages.account_statement_no_transactions') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </section>
        @else
            <section class="mt-6 rounded-xl border border-slate-200 p-5">
                <h3 class="text-sm font-black text-slate-900">{{ __('messages.account_statement_summary_heading') }}</h3>
                <div class="mt-4 grid gap-3 sm:grid-cols-3">
                    <div>
                        <div class="text-[10px] font-bold text-slate-400">{{ __('messages.account_statement_transaction_count') }}</div>
                        <div class="mt-1 text-xl font-black text-slate-900">{{ $statement['transaction_count'] }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400">{{ __('messages.account_statement_period_net') }}</div>
                        <div class="mt-1 text-xl font-black text-slate-900" dir="ltr">{{ $formatBalance($statement['period_debit'] - $statement['period_credit']) }}</div>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold text-slate-400">{{ __('messages.account_statement_final_balance') }}</div>
                        <div class="mt-1 text-xl font-black text-brandSky" dir="ltr">{{ $formatBalance($statement['closing_balance']) }}</div>
                    </div>
                </div>
            </section>
        @endif

        <footer class="mt-12 grid grid-cols-3 gap-4 border-t border-slate-100 pt-8 text-center">
            <div>
                <p class="font-bold text-slate-700">{{ __('messages.account_statement_accountant') }}</p>
                <div class="mt-2 h-14 w-full border-b border-dashed border-slate-200"></div>
            </div>
            <div>
                <p class="font-bold text-slate-700">{{ __('messages.account_statement_financial_manager') }}</p>
                <div class="mt-2 h-14 w-full border-b border-dashed border-slate-200"></div>
            </div>
            <div>
                <p class="font-bold text-slate-700">{{ __('messages.account_statement_stamp_signature') }}</p>
                <div class="mt-2 h-14 w-full border-b border-dashed border-slate-200"></div>
            </div>
        </footer>

        <div class="mt-8 text-center text-[9px] font-semibold tracking-wider text-slate-400">
            {{ __('messages.account_statement_footer') }}
        </div>
        @include('partials.company-document-footer')
    </article>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>

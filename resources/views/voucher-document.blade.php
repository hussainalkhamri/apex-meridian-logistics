<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $voucher->getTypeName() }} — {{ $voucher->id }}</title>
    
    <!-- Cairo Font for English/Arabic readability -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;800;900&family=Inter:wght@400;650;850&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons CDN -->
    <script src="https://cdn.jsdelivr.net/npm/lucide@latest"></script>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brandSky: '#c6192b', // Sky Blue Accent
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
            html, body { background: #fff !important; color: #000 !important; margin: 0; padding: 0; }
            body { font-size: 9px; line-height: 1.2; }
            .print-hidden { display: none !important; }
            .print-single-page { border: none !important; box-shadow: none !important; padding: 0 !important; margin: 0 auto !important; width: 100% !important; max-width: 100% !important; overflow: hidden !important; page-break-inside: avoid !important; }
            .voucher-paper { border: none !important; box-shadow: none !important; padding: 0 !important; }
        }
    </style>
    @include('partials.company-document-style')
</head>
<body class="bg-slate-50 text-slate-800 py-8 px-4 font-sans">
    
    <!-- Action bar -->
    <div class="max-w-[200mm] mx-auto mb-6 flex items-center justify-end gap-2 px-4 print-hidden">
            <button onclick="window.print()" class="inline-flex h-10 items-center justify-center rounded-lg bg-brandSky hover:opacity-95 text-white px-4 text-xs font-bold shadow-sm transition-all">
            <i data-lucide="printer" class="ml-2 h-4 w-4"></i>
            <span>{{ __('messages.print_voucher_btn') }}</span>
        </button>
        <button onclick="window.close()" class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-200 bg-white px-4 text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-sm transition-all">
            <span>{{ __('messages.close_page') }}</span>
        </button>
    </div>

    <!-- Official Document Sheet -->
    <article class="voucher-paper print-single-page mx-auto max-w-[200mm] bg-white p-8 md:p-10 text-[12px] leading-relaxed shadow-lg border border-slate-200 rounded-2xl print:max-w-none print:shadow-none print:border-none print:p-0">
        
        <!-- Header logo & Name Block -->
        @include('partials.company-letterhead')

        <!-- Document Identity -->
        <div class="my-6 flex items-center justify-between border-b-2 border-brandSky pb-4">
            <div>
                <h2 class="text-2xl font-black text-brandSky tracking-tight">{{ $voucher->getTypeName() }}</h2>
                <span class="text-[9px] uppercase tracking-widest font-black text-slate-400">
                    {{ $voucher->type === 'receipt' ? __('messages.voucher_type_receipt') : __('messages.voucher_type_payment') }}
                </span>
            </div>
            <div class="text-left" dir="ltr">
                <p class="text-xs text-slate-400">{{ __('messages.voucher_number_label') }}: <strong class="text-brandSky font-mono text-sm font-bold">{{ $voucher->id }}</strong></p>
                <p class="text-xs text-slate-400">{{ __('messages.date') }}: <strong class="text-slate-700 font-mono">{{ $voucher->date->toDateString() }}</strong></p>
            </div>
        </div>

        <!-- Voucher Details Table -->
        <table class="w-full border-collapse border border-slate-200 mt-6 text-xs text-slate-800">
            <tbody>
                <!-- Payee / Payer -->
                <tr>
                    <td class="w-32 bg-slate-50 p-3 font-bold border border-slate-200">
                        {{ $voucher->type === 'receipt' ? __('messages.voucher_received_from') : __('messages.voucher_paid_to') }}
                    </td>
                    <td class="p-3 border border-slate-200 font-semibold text-slate-900 text-sm">
                        {{ $voucher->payee_payer }}
                    </td>
                </tr>

                <!-- Amount -->
                <tr>
                    <td class="bg-slate-50 p-3 font-bold border border-slate-200">{{ __('messages.amount_in_words') }}</td>
                    <td class="p-3 border border-slate-200 font-black text-brandSky text-base" dir="ltr">
                        $ {{ number_format($voucher->amount, 2) }}
                    </td>
                </tr>

                @if($voucher->invoice)
                <tr>
                    <td class="bg-slate-50 p-3 font-bold border border-slate-200">{{ __('messages.invoice_number_label') }}</td>
                    <td class="p-3 border border-slate-200 font-mono font-bold text-slate-600">
                        {{ $voucher->invoice_id }}
                    </td>
                </tr>
                @endif

                @if($voucher->type === 'receipt' && $voucher->invoice)
                <tr>
                    <td class="bg-slate-50 p-3 font-bold border border-slate-200">{{ __('messages.invoice_total_label') }}</td>
                    <td class="p-3 border border-slate-200 font-bold text-slate-900" dir="ltr">
                        $ {{ number_format($voucher->invoice->amount, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="bg-slate-50 p-3 font-bold border border-slate-200">{{ __('messages.paid_before_voucher') }}</td>
                    <td class="p-3 border border-slate-200 font-bold text-slate-900" dir="ltr">
                        $ {{ number_format($voucher->paid_before, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="bg-slate-50 p-3 font-bold border border-slate-200">{{ __('messages.paid_after_voucher') }}</td>
                    <td class="p-3 border border-slate-200 font-bold text-emerald-700" dir="ltr">
                        $ {{ number_format($voucher->paid_after, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="bg-slate-50 p-3 font-bold border border-slate-200">{{ __('messages.remaining_on_invoice') }}</td>
                    <td class="p-3 border border-slate-200 font-black text-red-600" dir="ltr">
                        $ {{ number_format($voucher->balance_after, 2) }}
                    </td>
                </tr>
                @endif

                <!-- Payment Method -->
                <tr>
                    <td class="bg-slate-50 p-3 font-bold border border-slate-200">{{ __('messages.payment_method_label') }}</td>
                    <td class="p-3 border border-slate-200 font-semibold">
                        {{ $voucher->payment_method }}
                    </td>
                </tr>

                <!-- Booking Linked -->
                @if($voucher->booking_id)
                <tr>
                    <td class="bg-slate-50 p-3 font-bold border border-slate-200">{{ __('messages.booking_request_label') }}</td>
                    <td class="p-3 border border-slate-200 font-mono font-bold text-slate-600">
                        {{ $voucher->booking_id }}
                    </td>
                </tr>
                @endif

                <!-- Description / Purpose -->
                <tr>
                    <td class="bg-slate-50 p-3 font-bold border border-slate-200">{{ __('messages.voucher_description_label') }}</td>
                    <td class="p-3 border border-slate-200 leading-relaxed text-slate-700 font-medium">
                        {{ $voucher->description }}
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- Signatures & Stamp block -->
        <footer class="mt-14 grid grid-cols-3 gap-4 text-center border-t border-slate-100 pt-8">
            <div>
                <p class="font-bold text-slate-700">{{ __('messages.recipient') }}</p>
                <div class="h-16 w-full border-b border-dashed border-slate-200 mt-2"></div>
            </div>
            <div>
                <p class="font-bold text-slate-700">{{ __('messages.accountant') }}</p>
                <div class="h-16 w-full border-b border-dashed border-slate-200 mt-2"></div>
            </div>
            <div>
                <p class="font-bold text-slate-700">{{ __('messages.stamp_and_signature') }}</p>
                <div class="h-16 w-full border-b border-dashed border-slate-200 mt-2"></div>
            </div>
        </footer>

        <div class="mt-10 text-center text-[9px] text-slate-400 font-semibold tracking-wider">
            {{ __('messages.voucher_footer') }}
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

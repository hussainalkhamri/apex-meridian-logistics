<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: dejavusans, sans-serif;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.7;
        }

        .header {
            border-bottom: 3px solid #c6192b;
            padding-bottom: 14px;
            margin-bottom: 22px;
        }

        .brand {
            font-size: 18px;
            font-weight: bold;
        }

        .muted {
            color: #64748b;
        }

        .title {
            color: #c6192b;
            font-size: 26px;
            font-weight: bold;
            text-align: left;
        }

        .identity {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 18px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .table td {
            border: 1px solid #cbd5e1;
            padding: 10px;
            vertical-align: top;
        }

        .label {
            width: 28%;
            background: #f8fafc;
            font-weight: bold;
            color: #334155;
        }

        .amount {
            color: #c6192b;
            font-size: 15px;
            font-weight: bold;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .signatures {
            width: 100%;
            margin-top: 46px;
            border-collapse: collapse;
        }

        .signatures td {
            width: 33.333%;
            text-align: center;
            padding: 12px;
        }

        .line {
            border-bottom: 1px dashed #94a3b8;
            height: 40px;
            margin-top: 12px;
        }

        .footer {
            margin-top: 28px;
            text-align: center;
            color: #64748b;
            font-size: 10px;
        }
    </style>
@include('partials.company-document-style')
<style>.company-document-partners {display:block;text-align:center} .company-document-partners img {display:inline-block;vertical-align:middle;margin:0 10px}</style>
</head>
<body>
    <table class="header" width="100%">
        <tr>
            <td width="60%">
                <img src="{{ public_path(config('company.letterhead_logo')) }}" alt="{{ config('company.short_name.en') }}" style="width:190px;height:auto;margin-bottom:12px">
                <div class="brand">{{ __('messages.company_name') }}</div>
                <div class="muted">{{ __('messages.account_statement_company_tagline') }}</div>
                <div class="muted">{{ __('messages.account_statement_company_address') }}</div>
                <div class="muted ltr">{{ config('company.phones.0.display') }} - {{ config('company.phones.1.display') }}</div>
            </td>
            <td width="40%" class="title">{{ $voucher->getTypeName() }}</td>
        </tr>
    </table>

    <div class="identity">
        <strong>{{ __('messages.voucher_number_label') }}:</strong> <span class="ltr">{{ $voucher->id }}</span><br>
        <strong>{{ __('messages.date') }}:</strong> {{ $voucher->date->toDateString() }}<br>
        @if($booking)
            <strong>طلب الشحن:</strong> <span class="ltr">{{ $booking->id }}</span><br>
            <strong>العميل:</strong> {{ $booking->customer_company }}
        @endif
    </div>

    <table class="table">
        <tr>
            <td class="label">{{ $voucher->type === 'receipt' ? __('messages.voucher_received_from') : __('messages.voucher_paid_to') }}</td>
            <td>{{ $voucher->payee_payer }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.amount_in_words') }}</td>
            <td class="amount ltr">$ {{ number_format($voucher->amount, 2) }}</td>
        </tr>
        @if($voucher->invoice_id)
            <tr>
                <td class="label">{{ __('messages.invoice_number_label') }}</td>
                <td class="ltr">{{ $voucher->invoice_id }}</td>
            </tr>
        @endif
        @if($voucher->type === 'receipt' && $voucher->invoice)
            <tr>
                <td class="label">{{ __('messages.invoice_total_label') }}</td>
                <td class="ltr">$ {{ number_format($voucher->invoice->amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.paid_after_voucher') }}</td>
                <td class="ltr">$ {{ number_format($voucher->paid_after, 2) }}</td>
            </tr>
            <tr>
                <td class="label">{{ __('messages.remaining_on_invoice') }}</td>
                <td class="ltr">$ {{ number_format($voucher->balance_after, 2) }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">{{ __('messages.payment_method_label') }}</td>
            <td>{{ $voucher->payment_method }}</td>
        </tr>
        <tr>
            <td class="label">{{ __('messages.voucher_description_label') }}</td>
            <td>{{ $voucher->description }}</td>
        </tr>
    </table>

    <table class="signatures">
        <tr>
            <td>
                {{ __('messages.recipient') }}
                <div class="line"></div>
            </td>
            <td>
                {{ __('messages.accountant') }}
                <div class="line"></div>
            </td>
            <td>
                {{ __('messages.stamp_and_signature') }}
                <div class="line"></div>
            </td>
        </tr>
    </table>

    <div class="footer">
        {{ __('messages.voucher_footer') }}
    </div>
@include('partials.company-document-footer', ['pdfAssets' => true])
</body>
</html>

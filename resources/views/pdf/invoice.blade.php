<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: dejavusans, sans-serif;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.6;
        }

        .header {
            border-bottom: 3px solid #c6192b;
            padding-bottom: 14px;
            margin-bottom: 20px;
        }

        .brand {
            font-size: 18px;
            font-weight: bold;
            color: #0f172a;
        }

        .muted {
            color: #64748b;
        }

        .title {
            color: #c6192b;
            font-size: 26px;
            font-weight: bold;
            letter-spacing: 1px;
            text-align: left;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .grid td {
            vertical-align: top;
            padding: 4px 0;
        }

        .box {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 18px;
        }

        .items th,
        .items td {
            border: 1px solid #cbd5e1;
            padding: 9px 10px;
        }

        .items th {
            background: #edf0f8;
            color: #26376b;
            font-weight: bold;
        }

        .total-row td {
            background: #f8fafc;
            font-size: 13px;
            font-weight: bold;
        }

        .paid-row td {
            background: #ecfdf5;
            color: #047857;
            font-weight: bold;
        }

        .balance-row td {
            background: #fef2f2;
            color: #dc2626;
            font-weight: bold;
        }

        .ltr {
            direction: ltr;
            text-align: left;
        }

        .footer {
            margin-top: 28px;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
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
            <td width="55%">
                <img src="{{ public_path(config('company.letterhead_logo')) }}" alt="{{ config('company.short_name.en') }}" style="width:190px;height:auto;margin-bottom:12px">
                <div class="brand">{{ config('company.name.ar') }}</div>
                <div class="muted">{{ config('company.name.en') }}</div>
                <div class="muted">{{ config('company.address.ar') }}</div>
                <div class="muted ltr">{{ config('company.phones.0.display') }} - {{ config('company.phones.1.display') }}</div>
                <div class="muted">{{ config('company.email') }}</div>
            </td>
            <td width="45%" class="title">SHIPPING INVOICE</td>
        </tr>
    </table>

    <table class="grid">
        <tr>
            <td width="50%">
                <div class="box">
                    <strong>إلى / Bill To</strong><br>
                    {{ $booking?->customer_company ?: '—' }}<br>
                    {{ $booking?->customer_contact_name ?: '' }}<br>
                    {{ $booking?->customer_phone ?: '' }}<br>
                    {{ $booking?->customer_email ?: '' }}
                </div>
            </td>
            <td width="50%">
                <div class="box">
                    <strong>رقم الفاتورة:</strong> <span class="ltr">{{ $invoice->id }}</span><br>
                    <strong>رقم الطلب:</strong> <span class="ltr">{{ $invoice->booking_id }}</span><br>
                    <strong>تاريخ الإصدار:</strong> {{ $invoice->issued_at->toDateString() }}<br>
                    <strong>تاريخ الاستحقاق:</strong> {{ $invoice->due_at->toDateString() }}<br>
                    <strong>الحالة:</strong> {{ $invoice->status }}
                </div>
            </td>
        </tr>
    </table>

    <div class="box">
        <strong>بيانات الشحنة:</strong>
        {{ $booking?->pol ?: '—' }} إلى {{ $booking?->pod ?: '—' }}
        @if($booking?->carrier_booking_ref)
            - مرجع الناقل: <span class="ltr">{{ $booking->carrier_booking_ref }}</span>
        @endif
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>البيان</th>
                <th width="28%">المبلغ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>خدمات شحن وتخليص مرتبطة بطلب الشحن <span class="ltr">{{ $invoice->booking_id }}</span></td>
                <td class="ltr">$ {{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr class="total-row">
                <td>الإجمالي</td>
                <td class="ltr">$ {{ number_format($invoice->amount, 2) }}</td>
            </tr>
            <tr class="paid-row">
                <td>المدفوع</td>
                <td class="ltr">$ {{ number_format($invoice->paid_amount, 2) }}</td>
            </tr>
            <tr class="balance-row">
                <td>المتبقي</td>
                <td class="ltr">$ {{ number_format($invoice->remaining_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        هذا مستند إلكتروني رسمي صادر من نظام الحسابات لدى شركة {{ config('company.name.ar') }}.
        يرجى سداد المبلغ المستحق في موعده لتجنب أي غرامات أو تأخير تشغيلي.
    </div>
@include('partials.company-document-footer', ['pdfAssets' => true])
</body>
</html>

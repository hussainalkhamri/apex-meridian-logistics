<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تصدير أسعار الشحن</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: right; }
        th { background: #f3f4f6; }
    </style>
</head>
<body>
<h2>أسعار الشحن</h2>
<table>
    <thead>
        <tr>
            <th>نوع الشحن</th>
            <th>نوع الخدمة</th>
            <th>شرط الشحن</th>
            <th>نوع الحاوية</th>
            <th>بلد الانطلاق</th>
            <th>ميناء الانطلاق</th>
            <th>بلد الوصول</th>
            <th>ميناء الوصول</th>
            <th>شركة الشحن</th>
            <th>العملة</th>
            <th>السعر</th>
            <th>الحالة</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rates as $rate)
            <tr>
                <td>{{ $rate->shipping_type }}</td>
                <td>{{ $rate->service_type }}</td>
                <td>{{ $rate->incoterm ?: 'FOB' }}</td>
                <td>{{ $rate->container_type }}</td>
                <td>{{ $rate->origin_country }}</td>
                <td>{{ $rate->origin_port }}</td>
                <td>{{ $rate->destination_country }}</td>
                <td>{{ $rate->destination_port }}</td>
                <td>{{ $rate->shipping_company }}</td>
                <td>{{ $rate->currency }}</td>
                <td>{{ number_format((float)$rate->price, 2) }}</td>
                <td>{{ $rate->is_active ? 'فعال' : 'غير فعال' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
</body>
</html>

<?php

namespace App\Support;

class RasaqCustomsDeclarationExcel
{
    public function build(array $data): string
    {
        $items = $data['items'] ?? [];
        $totals = $this->totals($items);

        $html = [];
        $html[] = '<!doctype html>';
        $html[] = '<html lang="ar" dir="rtl">';
        $html[] = '<head>';
        $html[] = '<meta charset="UTF-8">';
        $html[] = '<style>';
        $html[] = 'body{font-family:Tahoma,Arial,sans-serif;direction:rtl;}';
        $html[] = 'table{border-collapse:collapse;width:100%;}';
        $html[] = 'th,td{border:1px solid #b7b7b7;padding:6px;text-align:center;mso-number-format:"\@";}';
        $html[] = 'th{background:#123d68;color:#fff;font-weight:bold;}';
        $html[] = '.title{font-size:18px;font-weight:bold;background:#123d68;color:#fff;text-align:center;}';
        $html[] = '.section{background:#eaf2f8;font-weight:bold;text-align:right;}';
        $html[] = '.total{background:#fff2cc;font-weight:bold;}';
        $html[] = '</style>';
        $html[] = '</head>';
        $html[] = '<body>';
        $html[] = '<table>';
        $html[] = '<tr><td class="title" colspan="13">إقرار جمركي</td></tr>';
        $html[] = '<tr><td class="section" colspan="13">البيانات العامة للبيان</td></tr>';
        $html[] = $this->row('نوع الإقرار', $data['declaration_type'] ?? 'import', 'اسم ملف XML', $data['file_name'] ?? '', 'رقم المسلسل', $data['serial_no'] ?? '');
        $html[] = $this->row('رقم الإرسالية', $data['shipment_no'] ?? '', 'رمز المستورد', $data['importer_code'] ?? '', 'اسم المستورد', $data['customer'] ?? '');
        $html[] = $this->row('رمز المصرح', $data['declarant_code'] ?? '', 'اسم المصرح', $data['declarant_name'] ?? '', 'كود المنفذ', $data['customs_office_code'] ?? '0309');
        $html[] = $this->row('رقم الإقرار', $data['declaration_no'] ?? '', 'رقم البيان', $data['bayan_no'] ?? '', 'الدولة', $data['country'] ?? 'اليمن');
        $html[] = $this->row('مركز التخليص', $data['customs_center'] ?? '', 'جهة الوصول', $data['destination'] ?? '', 'نوع الصنف', $data['goods_type'] ?? '');
        $html[] = $this->row('السائق', $data['driver'] ?? '', 'رقم القاطرة', $data['truck'] ?? '', 'تاريخ الوصول', $data['arrival_date'] ?? '');
        $html[] = '<tr><td class="section" colspan="13">بيانات الأصناف</td></tr>';
        $html[] = '<tr>';

        foreach ([
            'م',
            'اسم الصنف',
            'الماركة',
            'المنشأ',
            'الكمية',
            'نوع الطرد',
            'عدد الطرود',
            'وحدة السعر',
            'وزن الطرد',
            'الوزن الصافي',
            'سعر الوحدة',
            'القيمة',
            'HS',
        ] as $heading) {
            $html[] = '<th>' . $this->escape($heading) . '</th>';
        }

        $html[] = '</tr>';

        foreach ($items as $index => $item) {
            $html[] = '<tr>';
            foreach ([
                $index + 1,
                $item['name'] ?? '',
                $item['brand'] ?? '',
                $item['origin'] ?? '',
                $item['count'] ?? 0,
                $item['type'] ?? '',
                $item['packages'] ?? 0,
                $item['price_unit'] ?? '',
                $item['package_weight_kg'] ?? 0,
                $item['net_weight_kg'] ?? 0,
                $item['price'] ?? 0,
                $item['value_usd'] ?? 0,
                $item['hs'] ?? '',
            ] as $value) {
                $html[] = '<td>' . $this->escape((string) $value) . '</td>';
            }
            $html[] = '</tr>';
        }

        $html[] = '<tr class="total">';
        $html[] = '<td colspan="4">الإجمالي</td>';
        $html[] = '<td>' . $this->escape((string) $totals['quantity']) . '</td>';
        $html[] = '<td></td>';
        $html[] = '<td>' . $this->escape((string) $totals['packages']) . '</td>';
        $html[] = '<td></td>';
        $html[] = '<td></td>';
        $html[] = '<td>' . $this->escape((string) $totals['net_weight_kg']) . '</td>';
        $html[] = '<td></td>';
        $html[] = '<td>' . $this->escape((string) $totals['value_usd']) . '</td>';
        $html[] = '<td></td>';
        $html[] = '</tr>';
        $html[] = '<tr><td class="section" colspan="13">ملاحظة</td></tr>';
        $html[] = '<tr><td colspan="13">' . $this->escape((string) ($data['note'] ?? '')) . '</td></tr>';
        $html[] = '</table>';
        $html[] = '</body>';
        $html[] = '</html>';

        return implode("\n", $html);
    }

    private function row(string $label1, mixed $value1, string $label2, mixed $value2, string $label3, mixed $value3): string
    {
        return sprintf(
            '<tr><td class="section" colspan="2">%s</td><td colspan="2">%s</td><td class="section" colspan="2">%s</td><td colspan="2">%s</td><td class="section" colspan="2">%s</td><td colspan="3">%s</td></tr>',
            $this->escape($label1),
            $this->escape((string) $value1),
            $this->escape($label2),
            $this->escape((string) $value2),
            $this->escape($label3),
            $this->escape((string) $value3),
        );
    }

    private function totals(array $items): array
    {
        return collect($items)->reduce(fn (array $totals, array $item) => [
            'quantity' => $totals['quantity'] + (float) ($item['count'] ?? 0),
            'packages' => $totals['packages'] + (float) ($item['packages'] ?? 0),
            'net_weight_kg' => $totals['net_weight_kg'] + (float) ($item['net_weight_kg'] ?? 0),
            'value_usd' => $totals['value_usd'] + (float) ($item['value_usd'] ?? 0),
        ], [
            'quantity' => 0.0,
            'packages' => 0.0,
            'net_weight_kg' => 0.0,
            'value_usd' => 0.0,
        ]);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Port;
use App\Models\ShippingRate;
use App\Support\PortCatalog;
use Illuminate\Http\Request;
use Mpdf\Mpdf;
use ZipArchive;

class ShippingRateController extends Controller
{
    public function index(Request $request)
    {
        $query = ShippingRate::query()->latest('updated_at');

        if ($request->filled('search')) {
            $term = trim($request->string('search'));
            $query->where(function ($q) use ($term) {
                $q->where('shipping_company', 'like', "%{$term}%")
                    ->orWhere('origin_country', 'like', "%{$term}%")
                    ->orWhere('origin_port', 'like', "%{$term}%")
                    ->orWhere('destination_country', 'like', "%{$term}%")
                    ->orWhere('destination_port', 'like', "%{$term}%")
                    ->orWhere('incoterm', 'like', "%{$term}%")
                    ->orWhere('container_type', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        foreach (['shipping_type', 'service_type', 'incoterm', 'container_type', 'origin_country', 'origin_port', 'destination_country', 'destination_port', 'shipping_company'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $shippingRates = $query->paginate(20)->withQueryString();

        $filtersData = [
            'shipping_types' => ShippingRate::query()->distinct()->pluck('shipping_type')->filter()->sort()->values(),
            'service_types' => ShippingRate::query()->distinct()->pluck('service_type')->filter()->sort()->values(),
            'incoterms' => ShippingRate::query()->distinct()->pluck('incoterm')->filter()->sort()->values(),
            'container_types' => ShippingRate::query()->distinct()->pluck('container_type')->filter()->sort()->values(),
            'origin_countries' => ShippingRate::query()->distinct()->pluck('origin_country')->filter()->sort()->values(),
            'origin_ports' => ShippingRate::query()->distinct()->pluck('origin_port')->filter()->sort()->values(),
            'destination_countries' => ShippingRate::query()->distinct()->pluck('destination_country')->filter()->sort()->values(),
            'destination_ports' => ShippingRate::query()->distinct()->pluck('destination_port')->filter()->sort()->values(),
            'shipping_companies' => ShippingRate::query()->distinct()->pluck('shipping_company')->filter()->sort()->values(),
        ];

        $readOnly = false;

        return view('pages.shipping-rates.index', compact('shippingRates', 'filtersData', 'readOnly'));
    }

    public function operations(Request $request)
    {
        $query = ShippingRate::query()->latest('updated_at');

        if ($request->filled('search')) {
            $term = trim($request->string('search'));
            $query->where(function ($q) use ($term) {
                $q->where('shipping_company', 'like', "%{$term}%")
                    ->orWhere('origin_country', 'like', "%{$term}%")
                    ->orWhere('origin_port', 'like', "%{$term}%")
                    ->orWhere('destination_country', 'like', "%{$term}%")
                    ->orWhere('destination_port', 'like', "%{$term}%")
                    ->orWhere('incoterm', 'like', "%{$term}%")
                    ->orWhere('container_type', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        foreach (['shipping_type', 'service_type', 'incoterm', 'container_type', 'origin_country', 'origin_port', 'destination_country', 'destination_port', 'shipping_company'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $shippingRates = $query->paginate(20)->withQueryString();

        $filtersData = [
            'shipping_types' => ShippingRate::query()->distinct()->pluck('shipping_type')->filter()->sort()->values(),
            'service_types' => ShippingRate::query()->distinct()->pluck('service_type')->filter()->sort()->values(),
            'incoterms' => ShippingRate::query()->distinct()->pluck('incoterm')->filter()->sort()->values(),
            'container_types' => ShippingRate::query()->distinct()->pluck('container_type')->filter()->sort()->values(),
            'origin_countries' => ShippingRate::query()->distinct()->pluck('origin_country')->filter()->sort()->values(),
            'origin_ports' => ShippingRate::query()->distinct()->pluck('origin_port')->filter()->sort()->values(),
            'destination_countries' => ShippingRate::query()->distinct()->pluck('destination_country')->filter()->sort()->values(),
            'destination_ports' => ShippingRate::query()->distinct()->pluck('destination_port')->filter()->sort()->values(),
            'shipping_companies' => ShippingRate::query()->distinct()->pluck('shipping_company')->filter()->sort()->values(),
        ];

        $readOnly = true;

        return view('pages.shipping-rates.index', compact('shippingRates', 'filtersData', 'readOnly'));
    }

    public function create()
    {
        return $this->formView(null);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedData($request);

        if ($this->hasDuplicate($validated, null)) {
            return back()->withErrors(['duplicate' => 'يوجد سعر مشابه لهذا المسار ونوع الحاوية والشركة بالفعل.'])->withInput();
        }

        $shippingRate = ShippingRate::create($validated);

        AuditLog::logAction('CREATE', 'shipping_rates', (string) $shippingRate->id, [], $shippingRate->toArray());

        $redirectTarget = $request->boolean('save_and_new') ? route('shipping-rates.create') : route('shipping-rates.index');

        return redirect($redirectTarget)->with('success', 'تم حفظ سعر الشحن بنجاح');
    }

    public function show(ShippingRate $shippingRate)
    {
        return $this->formView($shippingRate, true);
    }

    public function edit(ShippingRate $shippingRate)
    {
        return $this->formView($shippingRate, false);
    }

    public function update(Request $request, ShippingRate $shippingRate)
    {
        $validated = $this->validatedData($request);

        if ($this->hasDuplicate($validated, $shippingRate)) {
            return back()->withErrors(['duplicate' => 'يوجد سعر مشابه لهذا المسار ونوع الحاوية والشركة بالفعل.'])->withInput();
        }

        $old = $shippingRate->only(array_keys($validated));
        $shippingRate->update($validated);

        AuditLog::logAction('UPDATE', 'shipping_rates', (string) $shippingRate->id, $old, $shippingRate->fresh()->toArray());

        return redirect()->route('shipping-rates.index')->with('success', 'تم تحديث سعر الشحن بنجاح');
    }

    public function copy(ShippingRate $shippingRate)
    {
        $newRate = $shippingRate->replicate();
        $newRate->forceFill([
            'valid_from' => now()->toDateString(),
            'valid_to' => null,
            'is_active' => true,
        ]);
        $newRate->save();

        AuditLog::logAction('CREATE', 'shipping_rates', (string) $newRate->id, [], $newRate->toArray());

        return redirect()->route('shipping-rates.edit', $newRate)->with('success', 'تم نسخ السعر وإنشاء نسخة جديدة بنجاح');
    }

    public function destroy(ShippingRate $shippingRate)
    {
        AuditLog::logAction('DELETE', 'shipping_rates', (string) $shippingRate->id, $shippingRate->toArray());
        $shippingRate->delete();

        return redirect()->route('shipping-rates.index')->with('success', 'تم حذف سعر الشحن بنجاح');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => ['required','file','mimes:xlsx,xls,csv']]);

        $path = $request->file('file')->store('imports');
        $fullPath = storage_path('app/' . $path);

        try {
            $rows = $this->readSimpleXlsx($fullPath);
        } catch (\Throwable $exception) {
            $rows = array_map('str_getcsv', file($fullPath));
        }

        $headers = array_map(fn ($value) => trim((string) $value), (array) ($rows[0] ?? []));
        $imported = 0;

        foreach (array_slice($rows, 1) as $row) {
            $payload = array_combine($headers, $row);
            if (!is_array($payload)) {
                continue;
            }

            $record = $this->normalizeImportedRow($payload);
            if ($record === null) {
                continue;
            }

            if ($this->hasDuplicate($record, null)) {
                continue;
            }

            ShippingRate::create($record);
            $imported++;
        }

        return redirect()->route('shipping-rates.index')->with('success', "تم استيراد {$imported} سعر بنجاح");
    }

    public function export(Request $request)
    {
        $query = ShippingRate::query()->latest('updated_at');
        $format = $request->input('format', 'xlsx');

        if ($request->filled('search')) {
            $term = trim($request->string('search'));
            $query->where(function ($q) use ($term) {
                $q->where('shipping_company', 'like', "%{$term}%")
                    ->orWhere('origin_country', 'like', "%{$term}%")
                    ->orWhere('origin_port', 'like', "%{$term}%")
                    ->orWhere('destination_country', 'like', "%{$term}%")
                    ->orWhere('destination_port', 'like', "%{$term}%")
                    ->orWhere('incoterm', 'like', "%{$term}%")
                    ->orWhere('container_type', 'like', "%{$term}%")
                    ->orWhere('notes', 'like', "%{$term}%");
            });
        }

        foreach (['shipping_type', 'service_type', 'incoterm', 'container_type', 'origin_country', 'origin_port', 'destination_country', 'destination_port', 'shipping_company'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $rates = $query->get();

        if ($format === 'pdf') {
            $pdf = new Mpdf(['tempDir' => storage_path('app/tmp')]);
            $pdf->WriteHTML(view('pages.shipping-rates.export-pdf', compact('rates'))->render());

            return response($pdf->Output('', 'S'))->header('Content-Type', 'application/pdf')->header('Content-Disposition', 'attachment; filename="shipping-rates.pdf"');
        }

        $headerRow = ['نوع الشحن', 'نوع الخدمة', 'شرط الشحن', 'نوع الحاوية', 'بلد الانطلاق', 'ميناء الانطلاق', 'بلد الوصول', 'ميناء الوصول', 'شركة الشحن', 'مدة النقل', 'العملة', 'السعر', 'رسوم إضافية', 'تاريخ بداية السعر', 'تاريخ انتهاء السعر', 'الحالة', 'ملاحظات'];
        $rows = $rates->map(function (ShippingRate $rate) {
            return [
                $rate->shipping_type,
                $rate->service_type,
                $rate->incoterm ?: 'FOB',
                $rate->container_type,
                $rate->origin_country,
                $rate->origin_port,
                $rate->destination_country,
                $rate->destination_port,
                $rate->shipping_company,
                $rate->transit_time,
                $rate->currency,
                number_format((float) $rate->price, 2),
                number_format((float) ($rate->additional_fee ?? 0), 2),
                $rate->valid_from?->format('Y-m-d'),
                $rate->valid_to?->format('Y-m-d'),
                $rate->is_active ? 'فعال' : 'غير فعال',
                $rate->notes,
            ];
        })->values()->toArray();

        $tmpPath = $this->buildSimpleXlsx(array_merge([$headerRow], $rows));

        return response()->download($tmpPath, 'shipping-rates.xlsx')->deleteFileAfterSend(true);
    }

    protected function buildSimpleXlsx(array $rows): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'shipping-rates');
        if ($tmpPath === false) {
            $tmpPath = storage_path('app/tmp/shipping-rates.xlsx');
        }
        @unlink($tmpPath);

        $zip = new ZipArchive();
        $zip->open($tmpPath, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', $this->excelContentTypesXml());
        $zip->addFromString('_rels/.rels', $this->excelRelationshipsXml());
        $zip->addFromString('docProps/app.xml', $this->excelAppXml());
        $zip->addFromString('docProps/core.xml', $this->excelCoreXml());
        $zip->addFromString('xl/workbook.xml', $this->excelWorkbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->excelWorkbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->excelStylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->excelSheetXml($rows));
        $zip->close();

        return $tmpPath;
    }

    protected function readSimpleXlsx(string $path): array
    {
        $zip = new ZipArchive();
        $open = $zip->open($path);
        if ($open !== true) {
            throw new \RuntimeException('Unable to read Excel file');
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $xml = simplexml_load_string($sheetXml);
        if ($xml === false) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $values = [];
            foreach ($row->c as $cell) {
                $type = (string) ($cell['t'] ?? '');
                if ($type === 'inlineStr') {
                    $values[] = trim((string) $cell->is->t);
                } else {
                    $values[] = trim((string) $cell->v);
                }
            }
            $rows[] = $values;
        }

        return $rows;
    }

    protected function excelContentTypesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
  <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
  <Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>
  <Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>
</Types>
XML;
    }

    protected function excelRelationshipsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>
  <Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>
</Relationships>
XML;
    }

    protected function excelAppXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties"
            xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">
  <Application>Microsoft Excel</Application>
</Properties>
XML;
    }

    protected function excelCoreXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties"
                  xmlns:dc="http://purl.org/dc/elements/1.1/"
                  xmlns:dcterms="http://purl.org/dc/terms/"
                  xmlns:dcmitype="http://purl.org/dc/dcmitype/"
                  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <dc:title>Shipping Rates</dc:title>
  <dc:creator>Shipmate</dc:creator>
  <cp:lastModifiedBy>Shipmate</cp:lastModifiedBy>
</cp:coreProperties>
XML;
    }

    protected function excelWorkbookXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"
          xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets>
    <sheet name="Sheet1" sheetId="1" r:id="rId1"/>
  </sheets>
</workbook>
XML;
    }

    protected function excelWorkbookRelsXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
</Relationships>
XML;
    }

    protected function excelStylesXml(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>
  <fills count="1"><fill><patternFill patternType="none"/></fill></fills>
  <borders count="1"><border/></borders>
  <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
  <cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>
  <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
</styleSheet>
XML;
    }

    protected function excelSheetXml(array $rows): string
    {
        $sheetData = [];
        foreach ($rows as $index => $cells) {
            $rowCells = [];
            foreach ($cells as $cell) {
                $value = (string) $cell;
                $isNumeric = is_numeric($value) && !str_contains($value, ':');
                $rowCells[] = $isNumeric
                    ? '<c><v>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</v></c>'
                    : '<c t="inlineStr"><is><t>' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '</t></is></c>';
            }
            $sheetData[] = '<row r="' . ($index + 1) . '">' . implode('', $rowCells) . '</row>';
        }

        return <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>
XML
        . implode('', $sheetData) .
        '</sheetData></worksheet>';
    }

    protected function formView(?ShippingRate $shippingRate = null, bool $viewOnly = false)
    {
        $ports = Port::query()->where('is_active', true)->orderBy('country')->orderBy('name')->get();

        $portCountries = $ports
            ->map(function (Port $port) {
                return [
                    'name' => $port->country ?: 'غير محدد',
                    'name_ar' => $port->country ?: 'غير محدد',
                    'code' => $port->country_code ?: '',
                    'flag' => $port->country_flag ?: '',
                ];
            })
            ->filter(fn (array $country) => !blank($country['name']))
            ->unique('name')
            ->values();

        $countries = PortCatalog::countries()
            ->merge($portCountries)
            ->unique(fn (array $country) => ($country['code'] ?? '') ?: $country['name'])
            ->sortBy('name')
            ->values();

        return view('pages.shipping-rates.form', compact('shippingRate', 'viewOnly', 'countries', 'ports'));
    }

    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'shipping_type' => ['required', 'string', 'max:50'],
            'service_type' => ['required', 'string', 'max:50'],
            'incoterm' => ['nullable', 'string', 'in:FOB,EXW'],
            'container_type' => ['required', 'string', 'max:50'],
            'origin_country' => ['required', 'string', 'max:100'],
            'origin_port' => ['required', 'string', 'max:100'],
            'destination_country' => ['required', 'string', 'max:100'],
            'destination_port' => ['required', 'string', 'max:100'],
            'shipping_company' => ['required', 'string', 'max:100'],
            'transit_time' => ['nullable', 'integer', 'min:1'],
            'currency' => ['required', 'string', 'max:10'],
            'price' => ['required', 'numeric', 'min:0'],
            'additional_fee' => ['nullable', 'numeric', 'min:0'],
            'valid_from' => ['required', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['incoterm'] = $validated['incoterm'] ?? 'FOB';
        $validated['price'] = (float) $validated['price'];
        $validated['additional_fee'] = isset($validated['additional_fee']) ? (float) $validated['additional_fee'] : null;
        $validated['transit_time'] = $validated['transit_time'] ?? null;

        return $validated;
    }

    protected function hasDuplicate(array $validated, ?ShippingRate $ignore = null): bool
    {
        $query = ShippingRate::query()
            ->where('origin_country', $validated['origin_country'])
            ->where('origin_port', $validated['origin_port'])
            ->where('destination_country', $validated['destination_country'])
            ->where('destination_port', $validated['destination_port'])
            ->where('incoterm', $validated['incoterm'] ?? 'FOB')
            ->where('container_type', $validated['container_type'])
            ->where('shipping_company', $validated['shipping_company']);

        if ($ignore) {
            $query->where('id', '!=', $ignore->id);
        }

        return $query->exists();
    }

    protected function normalizeImportedRow(array $payload): ?array
    {
        $mapping = [
            'shipping_type' => ['shipping_type', 'نوع الشحن', 'shipping type'],
            'service_type' => ['service_type', 'نوع الخدمة', 'service type'],
            'incoterm' => ['incoterm', 'شرط الشحن', 'shipping term', 'trade term'],
            'container_type' => ['container_type', 'نوع الحاوية', 'container type'],
            'origin_country' => ['origin_country', 'بلد الانطلاق', 'origin country'],
            'origin_port' => ['origin_port', 'ميناء الانطلاق', 'origin port'],
            'destination_country' => ['destination_country', 'بلد الوصول', 'destination country'],
            'destination_port' => ['destination_port', 'ميناء الوصول', 'destination port'],
            'shipping_company' => ['shipping_company', 'شركة الشحن', 'carrier', 'company'],
            'transit_time' => ['transit_time', 'مدة النقل', 'transit time'],
            'currency' => ['currency', 'العملة', 'currency'],
            'price' => ['price', 'السعر', 'rate'],
            'additional_fee' => ['additional_fee', 'رسوم إضافية', 'additional fee'],
            'valid_from' => ['valid_from', 'تاريخ بداية السعر', 'valid from'],
            'valid_to' => ['valid_to', 'تاريخ انتهاء السعر', 'valid to'],
            'notes' => ['notes', 'ملاحظات', 'notes'],
        ];

        $record = [];
        foreach ($mapping as $field => $candidates) {
            foreach ($candidates as $candidate) {
                $value = null;
                foreach ($payload as $key => $item) {
                    if (strtolower((string) $key) === strtolower((string) $candidate)) {
                        $value = $item;
                        break;
                    }
                }
                if ($value !== null) {
                    $record[$field] = $value;
                    break;
                }
            }
        }

        if (empty($record['shipping_type']) || empty($record['service_type']) || empty($record['origin_country']) || empty($record['origin_port']) || empty($record['destination_country']) || empty($record['destination_port']) || empty($record['shipping_company']) || empty($record['price'])) {
            return null;
        }

        return [
            'shipping_type' => (string) ($record['shipping_type'] ?? 'بحري'),
            'service_type' => (string) ($record['service_type'] ?? 'FCL'),
            'incoterm' => in_array(strtoupper((string) ($record['incoterm'] ?? 'FOB')), ['FOB', 'EXW'], true) ? strtoupper((string) ($record['incoterm'] ?? 'FOB')) : 'FOB',
            'container_type' => (string) ($record['container_type'] ?? '20GP'),
            'origin_country' => (string) ($record['origin_country'] ?? ''),
            'origin_port' => (string) ($record['origin_port'] ?? ''),
            'destination_country' => (string) ($record['destination_country'] ?? ''),
            'destination_port' => (string) ($record['destination_port'] ?? ''),
            'shipping_company' => (string) ($record['shipping_company'] ?? ''),
            'transit_time' => is_numeric($record['transit_time'] ?? null) ? (int) $record['transit_time'] : null,
            'currency' => (string) ($record['currency'] ?? 'USD'),
            'price' => (float) ($record['price'] ?? 0),
            'additional_fee' => !empty($record['additional_fee']) ? (float) $record['additional_fee'] : null,
            'valid_from' => !empty($record['valid_from']) ? $record['valid_from'] : now()->toDateString(),
            'valid_to' => !empty($record['valid_to']) ? $record['valid_to'] : null,
            'notes' => (string) ($record['notes'] ?? ''),
            'is_active' => true,
        ];
    }
}

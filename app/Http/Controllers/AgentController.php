<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Support\PortCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        $agents = Agent::orderBy('created_at', 'desc')->get();
        $agentOrders = $this->agentOrdersQuery($request)->paginate(20, ['*'], 'orders_page')->withQueryString();
        $agentOrderRoutes = Booking::query()
            ->where('status', 'approved')
            ->whereNotNull('agent_id')
            ->select(['pol', 'pod'])
            ->distinct()
            ->orderBy('pol')
            ->get();
        $countryOptions = PortCatalog::countries()
            ->map(fn (array $country) => [
                'name' => $country['name'],
                'name_ar' => $country['name_ar'],
                'code' => $country['code'],
            ])
            ->sortBy('name')
            ->values();

        $activeTab = $request->input('tab', 'agents') === 'orders' ? 'orders' : 'agents';

        return view('pages.agents', compact('agents', 'countryOptions', 'agentOrders', 'agentOrderRoutes', 'activeTab'));
    }

    public function exportOrders(Request $request)
    {
        $orders = $this->agentOrdersQuery($request)->get();
        $rows = [
            ['اسم الوكيل', 'عدد الطلبات المؤكدة', 'مسار الشحن', 'سعر الشراء', 'العملة', 'نوع الوكيل', 'رقم الطلب', 'تاريخ الاعتماد'],
        ];

        foreach ($orders as $booking) {
            $rows[] = [
                $booking->agent?->name ?? '—',
                $booking->confirmed_orders_count,
                trim("{$booking->pol} → {$booking->pod}"),
                number_format((float) $booking->agent_purchase_amount, 2),
                $booking->agent_purchase_currency ?: 'USD',
                $booking->agent?->type === 'local' ? 'محلي' : 'خارجي',
                $booking->id,
                $booking->decided_at?->format('Y-m-d H:i'),
            ];
        }

        $path = $this->buildSimpleXlsx($rows);

        return response()->download($path, 'agent-orders.xlsx')->deleteFileAfterSend(true);
    }

    private function agentOrdersQuery(Request $request)
    {
        $query = Booking::query()
            ->with('agent')
            ->where('bookings.status', 'approved')
            ->whereNotNull('bookings.agent_id')
            ->select('bookings.*')
            ->selectSub(
                Booking::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('agent_id', 'bookings.agent_id')
                    ->where('status', 'approved'),
                'confirmed_orders_count'
            )
            ->latest('decided_at');

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->whereHas('agent', fn ($agentQuery) => $agentQuery->where('name', 'like', "%{$term}%"));
        }

        if ($request->filled('agent_type')) {
            $query->whereHas('agent', fn ($agentQuery) => $agentQuery->where('type', $request->input('agent_type')));
        }

        foreach (['pol', 'pod'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('approved_from')) {
            $query->whereDate('decided_at', '>=', $request->input('approved_from'));
        }

        if ($request->filled('approved_to')) {
            $query->whereDate('decided_at', '<=', $request->input('approved_to'));
        }

        return $query;
    }

    private function buildSimpleXlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'agent-orders');
        if ($path === false) {
            abort(500, 'Unable to create export file');
        }
        @unlink($path);

        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Agent Orders" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $sheetRows = collect($rows)->map(function (array $cells, int $rowIndex) {
            $cellsXml = collect($cells)->map(function ($cell) {
                $value = htmlspecialchars((string) $cell, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                return is_numeric($cell) ? "<c><v>{$value}</v></c>" : "<c t=\"inlineStr\"><is><t>{$value}</t></is></c>";
            })->implode('');
            return '<row r="' . ($rowIndex + 1) . '">' . $cellsXml . '</row>';
        })->implode('');
        $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $sheetRows . '</sheetData></worksheet>');
        $zip->close();

        return $path;
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);
        $data['phone'] = $this->phoneWithCountryCode($data['phone'] ?? null, $data['phone_country_code'] ?? null);
        unset($data['phone_country_code']);

        $agent = Agent::create($data);

        AuditLog::logAction('CREATE', 'agents', (string) $agent->id, [], $agent->toArray());

        return redirect()->route('agents.index')->with('success', 'تم إضافة الوكيل بنجاح.');
    }

    public function update(Request $request, Agent $agent)
    {
        $data = $this->validatedData($request);
        $data['phone'] = $this->phoneWithCountryCode($data['phone'] ?? null, $data['phone_country_code'] ?? null);
        unset($data['phone_country_code']);

        $old = $agent->only(['name', 'phone', 'email', 'type', 'region', 'country']);
        $agent->update($data);

        AuditLog::logAction('UPDATE', 'agents', (string) $agent->id, $old, $data);

        return redirect()->route('agents.index')->with('success', 'تم تحديث بيانات الوكيل بنجاح.');
    }

    public function destroy(Agent $agent)
    {
        AuditLog::logAction('DELETE', 'agents', (string) $agent->id, $agent->toArray());
        $agent->delete();

        return redirect()->route('agents.index')->with('success', 'تم حذف الوكيل.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'phone_country_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'type' => 'required|in:local,external',
            'region' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);
    }

    private function phoneWithCountryCode(?string $phone, ?string $countryCode): ?string
    {
        $phone = trim((string) $phone);
        $countryCode = trim((string) $countryCode);

        if ($phone === '') {
            return null;
        }

        if (Str::startsWith($phone, '+') || $countryCode === '') {
            return $phone;
        }

        return trim($countryCode . ' ' . ltrim($phone, '0 '));
    }
}

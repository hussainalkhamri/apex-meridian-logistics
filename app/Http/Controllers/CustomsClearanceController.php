<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Customer;
use App\Support\RasaqCustomsCalculator;
use App\Support\RasaqCustomsDeclarationExcel;
use App\Support\RasaqCustomsDeclarationXml;
use App\Support\SystemId;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CustomsClearanceController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $q = trim((string) $request->query('q', ''));

        $query = Booking::with(['customer', 'agent'])
            ->where(function ($builder) {
                $builder->whereIn('status', ['approved', 'booked'])
                    ->orWhereNotNull('customs_reference')
                    ->orWhere('customs_status', '!=', 'pending');
            });

        if ($status !== 'all') {
            $query->where('customs_status', $status);
        }

        if ($q !== '') {
            $query->where(function ($builder) use ($q) {
                $builder->where('id', 'like', "%{$q}%")
                    ->orWhere('customer_company', 'like', "%{$q}%")
                    ->orWhere('pol', 'like', "%{$q}%")
                    ->orWhere('pod', 'like', "%{$q}%")
                    ->orWhere('customs_reference', 'like', "%{$q}%")
                    ->orWhere('customs_declaration_no', 'like', "%{$q}%")
                    ->orWhere('customs_bayan_no', 'like', "%{$q}%");
            });
        }

        $clearanceBookings = $query->latest()->get();
        $allClearanceBookings = Booking::where(function ($builder) {
            $builder->whereIn('status', ['approved', 'booked'])
                ->orWhereNotNull('customs_reference')
                ->orWhere('customs_status', '!=', 'pending');
        })->get();

        $stats = [
            'total' => $allClearanceBookings->count(),
            'pending' => $allClearanceBookings->where('customs_status', 'pending')->count(),
            'in_progress' => $allClearanceBookings->whereIn('customs_status', ['documents_review', 'inspection', 'duties'])->count(),
            'cleared' => $allClearanceBookings->where('customs_status', 'cleared')->count(),
            'on_hold' => $allClearanceBookings->where('customs_status', 'on_hold')->count(),
            'fees' => $allClearanceBookings->sum('customs_fees'),
        ];

        $customers = Customer::orderBy('company')->orderBy('contact_name')->get();
        $customersForCustoms = $customers->map(fn (Customer $customer) => [
            'id' => $customer->id,
            'company' => $customer->company,
            'contact_name' => $customer->contact_name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'notes' => $customer->notes,
        ])->values();

        return view('customs-clearance', compact('clearanceBookings', 'stats', 'status', 'q', 'customersForCustoms'));
    }

    public function calculate(Request $request, RasaqCustomsCalculator $calculator)
    {
        $data = $request->validate([
            'customs_exchange_rate' => 'nullable|numeric|min:0',
            'tax_exchange_rate' => 'nullable|numeric|min:0',
            'other_services_fee' => 'nullable|numeric|min:0',
            'items' => 'required|array|max:50',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.hs' => 'nullable|string|max:50',
            'items.*.packages' => 'nullable|numeric|min:0',
            'items.*.weight_kg' => 'nullable|numeric|min:0',
            'items.*.value_usd' => 'required|numeric|min:0',
            'items.*.cv_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        return response()->json($calculator->calculate($data));
    }

    public function exportDeclarationXml(Request $request, RasaqCustomsDeclarationXml $xmlBuilder)
    {
        $data = $this->validateDeclarationData($request);

        $xml = $xmlBuilder->build($data);
        $filename = $this->sanitizeDeclarationFilename($data['file_name'] ?? '', 'xml');

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="customs-declaration.xml"; filename*=UTF-8\'\'' . rawurlencode($filename),
        ]);
    }

    public function exportDeclarationExcel(Request $request, RasaqCustomsDeclarationExcel $excelBuilder)
    {
        $data = $this->validateDeclarationData($request);
        $excel = $excelBuilder->build($data);
        $filename = $this->sanitizeDeclarationFilename($data['file_name'] ?? '', 'xls');

        return response($excel, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="customs-declaration.xls"; filename*=UTF-8\'\'' . rawurlencode($filename),
        ]);
    }

    public function storeDeclaration(Request $request)
    {
        $data = $this->validateDeclarationData($request);
        $customerInput = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'customer_company' => 'nullable|string|max:255',
            'customer_contact_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'customer_notes' => 'nullable|string|max:2000',
            'attachments' => 'nullable|array|max:10',
            'attachments.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $customer = $this->saveDeclarationCustomerRecord([
            'customer_id' => $customerInput['customer_id'] ?? null,
            'company' => $this->firstFilled(
                $customerInput['customer_company'] ?? null,
                $data['customer'] ?? null,
                $customerInput['customer_contact_name'] ?? null,
                'Customs Declaration Customer'
            ),
            'contact_name' => $this->firstFilled(
                $customerInput['customer_contact_name'] ?? null,
                $data['customer'] ?? null
            ),
            'phone' => $customerInput['customer_phone'] ?? null,
            'email' => $customerInput['customer_email'] ?? null,
            'notes' => $customerInput['customer_notes'] ?? null,
        ], 'customs_declaration_submit');

        $uploadedAttachments = [];
        foreach ($request->file('attachments', []) as $file) {
            if ($file->isValid()) {
                $uploadedAttachments[] = $file->store('bookings/attachments', 'public');
            }
        }

        $items = collect($data['items'] ?? []);
        $totalPackages = $items->sum(fn ($item) => (float) ($item['packages'] ?? 0));
        if ($totalPackages <= 0) {
            $totalPackages = $items->sum(fn ($item) => (float) ($item['count'] ?? 0));
        }

        $totalWeight = $items->sum(fn ($item) => (float) ($item['net_weight_kg'] ?? 0));
        $totalValue = $items->sum(fn ($item) => (float) ($item['value_usd'] ?? 0));
        $itemNames = $items
            ->pluck('name')
            ->filter(fn ($value) => trim((string) $value) !== '')
            ->take(3)
            ->implode(', ');

        $goodsDescription = $this->firstFilled(
            $data['goods_type'] ?? null,
            $itemNames,
            'Customs declaration'
        );

        $bookingId = SystemId::makeSequential(Booking::class, 'BK');
        $customsReference = $this->firstFilled(
            $data['shipment_no'] ?? null,
            $data['declaration_no'] ?? null,
            'CST-' . now()->format('YmdHis')
        );
        $pol = $this->firstFilled($data['customs_center'] ?? null, $data['country'] ?? null, '-');
        $pod = $this->firstFilled($data['destination'] ?? null, $data['country'] ?? null, '-');

        $cargoDetails = [
            'service_type' => 'customs_clearance',
            'source' => 'customs_declaration_review',
            'declaration_type' => $data['declaration_type'],
            'file_name' => $data['file_name'] ?? null,
            'shipment_no' => $data['shipment_no'] ?? null,
            'serial_no' => $data['serial_no'] ?? null,
            'importer_code' => $data['importer_code'] ?? null,
            'declarant_code' => $data['declarant_code'] ?? null,
            'declarant_name' => $data['declarant_name'] ?? null,
            'customs_office_code' => $data['customs_office_code'] ?? null,
            'customs_center' => $data['customs_center'] ?? null,
            'country' => $data['country'] ?? null,
            'destination' => $data['destination'] ?? null,
            'goods_type' => $data['goods_type'] ?? null,
            'driver' => $data['driver'] ?? null,
            'truck' => $data['truck'] ?? null,
            'arrival_date' => ! empty($data['arrival_date'])
                ? Carbon::parse($data['arrival_date'])->toDateString()
                : null,
            'totals' => [
                'items' => $items->count(),
                'packages' => $totalPackages,
                'net_weight_kg' => $totalWeight,
                'value_usd' => $totalValue,
            ],
            'items' => $items->values()->all(),
            'attachments' => $uploadedAttachments,
        ];

        $routeDetails = [
            'category' => 'customs_clearance',
            'mode' => 'CUSTOMS',
            'service' => 'customs_declaration',
            'origin' => [
                'type' => 'customs_center',
                'country' => $data['country'] ?? null,
                'city' => null,
                'address' => null,
                'display_name' => $pol,
            ],
            'destination' => [
                'type' => 'customs_destination',
                'country' => $data['country'] ?? null,
                'city' => $data['destination'] ?? null,
                'address' => null,
                'display_name' => $pod,
            ],
        ];

        $booking = Booking::create([
            'id' => $bookingId,
            'customer_id' => $customer->id,
            'customer_company' => $customer->company,
            'customer_contact_name' => (string) ($customer->contact_name ?? ''),
            'customer_phone' => (string) ($customer->phone ?? ''),
            'customer_email' => (string) ($customer->email ?? ''),
            'shipment_type' => 'CUSTOMS',
            'container_type' => null,
            'container_qty' => null,
            'goods_description' => $goodsDescription,
            'notes' => $data['note'] ?? null,
            'weight_kg' => $totalWeight,
            'volume_cbm' => 0,
            'dangerous' => false,
            'documents' => ! empty($uploadedAttachments)
                ? collect($uploadedAttachments)->map(fn ($path) => basename($path))->implode(', ')
                : null,
            'attachments' => ! empty($uploadedAttachments) ? json_encode($uploadedAttachments) : null,
            'cargo_details' => $cargoDetails,
            'pol' => $pol,
            'pod' => $pod,
            'route_details' => $routeDetails,
            'status' => 'approved',
            'customs_status' => 'pending',
            'customs_reference' => $customsReference,
            'customs_declaration_no' => $data['declaration_no'] ?? null,
            'customs_bayan_no' => $data['bayan_no'] ?? null,
            'customs_fees' => 0,
            'customs_documents' => ! empty($uploadedAttachments) ? ['attachments'] : [],
            'customs_notes' => $data['note'] ?? null,
        ]);

        AuditLog::logAction('CUSTOMS_CREATE', 'bookings', $booking->id, [], [
            'source' => 'customs_declaration_review',
            'customer_id' => $customer->id,
            'customs_reference' => $booking->customs_reference,
            'customs_status' => $booking->customs_status,
        ]);

        $this->createNotification([
            'message' => "تم إنشاء ملف تخليص جمركي {$bookingId} من الإقرار وبانتظار المراجعة",
            'booking_id' => $bookingId,
            'read' => false,
            'target' => 'customs',
        ]);

        return response()->json([
            'saved' => true,
            'booking' => [
                'id' => $booking->id,
                'customs_reference' => $booking->customs_reference,
                'customs_status' => $booking->customs_status,
            ],
            'customer' => $this->declarationCustomerResponse($customer),
            'redirect_url' => route('customs.index', [
                'customs_tab' => 'files',
                'q' => $booking->id,
            ]),
        ]);
    }

    private function validateDeclarationData(Request $request): array
    {
        return $request->validate([
            'declaration_type' => 'required|in:import,export',
            'file_name' => 'nullable|string|max:160',
            'serial_no' => 'nullable|string|max:100',
            'shipment_no' => 'nullable|string|max:100',
            'importer_code' => 'nullable|string|max:100',
            'customer' => 'nullable|string|max:255',
            'declarant_code' => 'nullable|string|max:100',
            'declarant_name' => 'nullable|string|max:255',
            'customs_office_code' => 'nullable|string|max:100',
            'declaration_no' => 'nullable|string|max:100',
            'bayan_no' => 'nullable|string|max:100',
            'customs_center' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'destination' => 'nullable|string|max:255',
            'goods_type' => 'nullable|string|max:255',
            'driver' => 'nullable|string|max:255',
            'truck' => 'nullable|string|max:255',
            'arrival_date' => 'nullable|date',
            'note' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1|max:100',
            'items.*.name' => 'nullable|string|max:255',
            'items.*.brand' => 'nullable|string|max:255',
            'items.*.origin' => 'nullable|string|max:255',
            'items.*.count' => 'nullable|numeric|min:0',
            'items.*.type' => 'nullable|string|max:100',
            'items.*.packages' => 'nullable|numeric|min:0',
            'items.*.price_unit' => 'nullable|string|max:100',
            'items.*.package_weight_kg' => 'nullable|numeric|min:0',
            'items.*.net_weight_kg' => 'nullable|numeric|min:0',
            'items.*.value_usd' => 'nullable|numeric|min:0',
            'items.*.price' => 'nullable|numeric|min:0',
            'items.*.reference' => 'nullable|string|max:255',
            'items.*.hs' => 'nullable|string|max:100',
        ]);
    }

    private function sanitizeDeclarationFilename(?string $filename, string $extension): string
    {
        $filename = trim((string) $filename);

        if ($filename === '') {
            $filename = 'إقرار_جمركي_' . now()->format('Ymd_His');
        }

        $filename = preg_replace('/[\\\\\/:*?"<>|]+/u', '_', $filename) ?: 'customs_declaration';
        $filename = preg_replace('/\.(xml|xls|xlsx)$/i', '', $filename) ?: 'customs_declaration';

        return "{$filename}.{$extension}";
    }

    public function storeDeclarationCustomer(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|integer|exists:customers,id',
            'company' => 'nullable|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $company = trim((string) ($data['company'] ?? ''));
        $contactName = trim((string) ($data['contact_name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($company === '' && $contactName === '') {
            return response()->json([
                'message' => 'اسم العميل أو الشركة مطلوب للحفظ.',
                'errors' => ['contact_name' => ['اسم العميل أو الشركة مطلوب للحفظ.']],
            ], 422);
        }

        $customer = null;
        if (! empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
        }

        if (! $customer && $email !== '') {
            $customer = Customer::where('email', $email)->first();
        }

        if (! $customer && $phone !== '') {
            $customer = Customer::where('phone', $phone)->first();
        }

        $payload = [
            'company' => $company !== '' ? $company : $contactName,
            'contact_name' => $contactName !== '' ? $contactName : null,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'notes' => $notes !== '' ? $notes : null,
        ];

        if ($customer) {
            $old = $customer->only(['company', 'contact_name', 'phone', 'email', 'notes']);
            $customer->update($payload);

            AuditLog::logAction('UPDATE', 'customers', (string) $customer->id, $old, [
                ...$payload,
                'source' => 'customs_declaration_step',
            ]);
        } else {
            $customer = Customer::create([
                ...$payload,
                'portal_enabled' => true,
            ]);

            AuditLog::logAction('CREATE', 'customers', (string) $customer->id, [], [
                ...$customer->toArray(),
                'source' => 'customs_declaration_step',
            ]);
        }

        return response()->json([
            'saved' => true,
            'customer' => [
                'id' => $customer->id,
                'company' => $customer->company,
                'contact_name' => $customer->contact_name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'notes' => $customer->notes,
            ],
        ]);
    }

    private function saveDeclarationCustomerRecord(array $data, string $source): Customer
    {
        $company = trim((string) ($data['company'] ?? ''));
        $contactName = trim((string) ($data['contact_name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));

        if ($company === '' && $contactName === '') {
            throw ValidationException::withMessages([
                'contact_name' => 'Customer name or company is required.',
            ]);
        }

        $customer = null;
        if (! empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
        }

        if (! $customer && $email !== '') {
            $customer = Customer::where('email', $email)->first();
        }

        if (! $customer && $phone !== '') {
            $customer = Customer::where('phone', $phone)->first();
        }

        $payload = [
            'company' => $company !== '' ? $company : $contactName,
            'contact_name' => $contactName !== '' ? $contactName : null,
            'phone' => $phone !== '' ? $phone : null,
            'email' => $email !== '' ? $email : null,
            'notes' => $notes !== '' ? $notes : null,
        ];

        if ($customer) {
            $old = $customer->only(['company', 'contact_name', 'phone', 'email', 'notes']);
            $customer->update($payload);

            AuditLog::logAction('UPDATE', 'customers', (string) $customer->id, $old, [
                ...$payload,
                'source' => $source,
            ]);

            return $customer;
        }

        $customer = Customer::create([
            ...$payload,
            'portal_enabled' => true,
        ]);

        AuditLog::logAction('CREATE', 'customers', (string) $customer->id, [], [
            ...$customer->toArray(),
            'source' => $source,
        ]);

        return $customer;
    }

    private function declarationCustomerResponse(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'company' => $customer->company,
            'contact_name' => $customer->contact_name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'notes' => $customer->notes,
        ];
    }

    private function firstFilled(...$values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    public function update(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'customs_status' => 'required|in:pending,documents_review,inspection,duties,cleared,on_hold',
            'customs_reference' => 'nullable|string|max:255',
            'customs_declaration_no' => 'nullable|string|max:255',
            'customs_bayan_no' => 'nullable|string|max:255',
            'customs_fees' => 'nullable|numeric|min:0',
            'customs_documents' => 'nullable|array',
            'customs_documents.*' => 'string|max:100',
            'customs_cleared_at' => 'nullable|date',
            'customs_notes' => 'nullable|string',
        ]);

        $old = $booking->only([
            'customs_status',
            'customs_reference',
            'customs_declaration_no',
            'customs_bayan_no',
            'customs_fees',
            'customs_documents',
            'customs_cleared_at',
            'customs_notes',
        ]);

        $data['customs_documents'] = $data['customs_documents'] ?? [];
        $data['customs_fees'] = (float) ($data['customs_fees'] ?? 0);

        if ($data['customs_status'] === 'cleared' && empty($data['customs_cleared_at'])) {
            $data['customs_cleared_at'] = Carbon::today()->toDateString();
        }

        if ($data['customs_status'] !== 'cleared' && empty($data['customs_cleared_at'])) {
            $data['customs_cleared_at'] = null;
        }

        $booking->update($data);

        AuditLog::logAction('CUSTOMS_UPDATE', 'bookings', $booking->id, $old, $data);

        return redirect()
            ->route('customs.index')
            ->with('success', "تم تحديث ملف التخليص للطلب {$booking->id} بنجاح");
    }
}

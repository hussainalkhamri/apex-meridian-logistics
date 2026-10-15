<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Port;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ScheduleManagementController extends Controller
{
    private const STATUSES = [
        'draft' => 'مسودة',
        'published' => 'منشورة',
        'delayed' => 'متأخرة',
        'cancelled' => 'ملغية',
        'completed' => 'مكتملة',
    ];

    public function index(Request $request)
    {
        $status = $request->query('status', 'all');
        $published = $request->query('published', 'all');
        $search = trim((string) $request->query('q', ''));
        $dateFrom = $request->query('date_from');

        $query = Schedule::query();

        if (array_key_exists($status, self::STATUSES)) {
            $query->where('status', $status);
        }

        if ($published === 'yes') {
            $query->where('is_published', true);
        } elseif ($published === 'no') {
            $query->where('is_published', false);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('vessel', 'like', "%{$search}%")
                    ->orWhere('voyage', 'like', "%{$search}%")
                    ->orWhere('carrier', 'like', "%{$search}%")
                    ->orWhere('origin', 'like', "%{$search}%")
                    ->orWhere('destination', 'like', "%{$search}%")
                    ->orWhere('source_name', 'like', "%{$search}%")
                    ->orWhere('source_reference', 'like', "%{$search}%");
            });
        }

        if ($dateFrom) {
            $query->whereDate('etd', '>=', $dateFrom);
        }

        $schedules = $query->orderBy('etd')->paginate(25)->withQueryString();
        $stats = [
            'total' => Schedule::count(),
            'published' => Schedule::where('is_published', true)->count(),
            'draft' => Schedule::where('is_published', false)->count(),
            'upcoming' => Schedule::where('is_published', true)->whereDate('etd', '>=', today())->count(),
        ];
        $ports = Port::where('is_active', true)
            ->orderBy('country')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['name', 'code', 'country']);

        return view('pages.schedules-management', [
            'schedules' => $schedules,
            'stats' => $stats,
            'statuses' => self::STATUSES,
            'status' => $status,
            'published' => $published,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'ports' => $ports,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedScheduleData($request);
        $schedule = Schedule::create($data);

        AuditLog::logAction('SCHEDULE_CREATE', 'schedules', (string) $schedule->id, [], $schedule->toArray());

        return redirect()
            ->route('schedules.manage')
            ->with('success', 'تمت إضافة جدول الرحلة بنجاح.');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $old = $schedule->toArray();
        $data = $this->validatedScheduleData($request);
        $schedule->update($data);

        AuditLog::logAction('SCHEDULE_UPDATE', 'schedules', (string) $schedule->id, $old, $schedule->fresh()->toArray());

        return redirect()
            ->route('schedules.manage')
            ->with('success', 'تم تحديث جدول الرحلة بنجاح.');
    }

    public function destroy(Schedule $schedule)
    {
        $old = $schedule->toArray();
        $scheduleId = (string) $schedule->id;
        $schedule->delete();

        AuditLog::logAction('SCHEDULE_DELETE', 'schedules', $scheduleId, $old, []);

        return redirect()
            ->route('schedules.manage')
            ->with('success', 'تم حذف جدول الرحلة.');
    }

    private function validatedScheduleData(Request $request): array
    {
        $data = $request->validate([
            'vessel' => ['required', 'string', 'max:255'],
            'voyage' => ['required', 'string', 'max:255'],
            'carrier' => ['nullable', 'string', 'max:255'],
            'service_name' => ['nullable', 'string', 'max:255'],
            'origin' => ['required', 'string', 'max:255'],
            'origin_code' => ['nullable', 'string', 'max:20'],
            'destination' => ['required', 'string', 'max:255'],
            'destination_code' => ['nullable', 'string', 'max:20'],
            'etd' => ['required', 'date'],
            'eta' => ['required', 'date', 'after_or_equal:etd'],
            'transit' => ['nullable', 'string', 'max:100'],
            'cutoff' => ['nullable', 'date', 'before_or_equal:etd'],
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(self::STATUSES))],
            'is_published' => ['nullable', 'boolean'],
            'source_name' => ['nullable', 'required_if:is_published,1', 'string', 'max:255'],
            'source_url' => ['nullable', 'url', 'max:1000'],
            'source_reference' => ['nullable', 'string', 'max:255'],
            'source_checked_at' => ['nullable', 'required_if:is_published,1', 'date'],
            'expires_at' => ['nullable', 'date'],
            'booking_url' => ['nullable', 'url', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['is_published'] = $request->boolean('is_published');
        if ($data['is_published'] && blank($data['source_url'] ?? null) && blank($data['source_reference'] ?? null)) {
            throw ValidationException::withMessages([
                'source_reference' => 'عند نشر جدول رحلة يجب إدخال رابط المصدر أو رقم مرجع المستند.',
            ]);
        }

        if (filled($data['expires_at'] ?? null) && filled($data['source_checked_at'] ?? null)
            && Carbon::parse($data['expires_at'])->lt(Carbon::parse($data['source_checked_at']))) {
            throw ValidationException::withMessages([
                'expires_at' => 'تاريخ انتهاء عرض الرحلة يجب أن يكون بعد تاريخ التحقق من المصدر أو مساويًا له.',
            ]);
        }

        if (! $data['is_published']) {
            $data['status'] = 'draft';
        }

        $data['origin_code'] = $this->upperNullable($data['origin_code'] ?? null);
        $data['destination_code'] = $this->upperNullable($data['destination_code'] ?? null);

        if (blank($data['transit'] ?? null)) {
            $data['transit'] = $this->transitLabel($data['etd'], $data['eta']);
        }

        return $data;
    }

    private function transitLabel(string $etd, string $eta): string
    {
        $days = Carbon::parse($etd)->diffInDays(Carbon::parse($eta));

        return $days . ' يوم';
    }

    private function upperNullable(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : strtoupper($value);
    }
}

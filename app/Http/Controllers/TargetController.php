<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MonthlyTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class TargetController extends Controller
{
    public function index()
    {
        $month = Carbon::now()->startOfMonth();
        $target = $this->targetForMonth($month);

        $approvedCount = Booking::query()
            ->whereIn('status', Booking::ACCOUNTING_APPROVED_STATUSES)
            ->whereBetween('decided_at', [$month, $month->copy()->endOfMonth()])
            ->count();
        $percentage = $target->target > 0
            ? min(100, round(($approvedCount / $target->target) * 100, 1))
            : 0;
        $dailyApproved = Booking::query()
            ->whereIn('status', Booking::ACCOUNTING_APPROVED_STATUSES)
            ->whereBetween('decided_at', [$month, $month->copy()->endOfMonth()])
            ->get(['decided_at'])
            ->groupBy(fn (Booking $booking) => $booking->decided_at->day)
            ->map->count();
        $recentApproved = Booking::query()
            ->whereIn('status', Booking::ACCOUNTING_APPROVED_STATUSES)
            ->whereBetween('decided_at', [$month, $month->copy()->endOfMonth()])
            ->latest('decided_at')
            ->take(5)
            ->get(['id', 'customer_company', 'decided_at']);

        return view('targets.index', compact('target', 'approvedCount', 'percentage', 'month', 'dailyApproved', 'recentApproved'));
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->isManager(), 403);

        $data = $request->validate([
            'target' => ['required', 'integer', 'min:1', 'max:1000000'],
        ]);
        $month = Carbon::now()->startOfMonth();

        $target = $this->targetForMonth($month);
        $target->update([
            'target' => $data['target'],
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('targets.index')->with('success', __('messages.target_updated'));
    }

    private function targetForMonth(Carbon $month): MonthlyTarget
    {
        return MonthlyTarget::query()
            ->whereDate('month', $month->toDateString())
            ->first() ?? MonthlyTarget::create([
                'month' => $month->toDateString(),
                'target' => 50,
            ]);
    }
}

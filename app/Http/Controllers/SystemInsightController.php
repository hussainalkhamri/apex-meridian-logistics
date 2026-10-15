<?php

namespace App\Http\Controllers;

use App\Models\NotificationRead;
use App\Models\SystemInsight;
use App\Support\SystemInsightGenerator;
use Illuminate\Support\Facades\DB;

class SystemInsightController extends Controller
{
    public function index()
    {
        $insights = SystemInsight::latest('last_seen_at')->latest()->get();

        return view('pages.insights', compact('insights'));
    }

    public function generate(SystemInsightGenerator $generator)
    {
        $summary = $generator->generate();

        return back()->with('success', __('messages.insights_generated_summary', [
            'created' => $summary['created'],
            'updated' => $summary['updated'],
            'unchanged' => $summary['unchanged'],
        ]));
    }

    public function markRead(SystemInsight $insight)
    {
        $insight->update(['is_read' => true]);

        if ($insight->notification_id && request()->user()) {
            NotificationRead::firstOrCreate(
                [
                    'notification_id' => $insight->notification_id,
                    'user_id' => request()->user()->id,
                ],
                ['read_at' => now()]
            );
        }

        return back()->with('success', __('messages.insight_marked_read'));
    }

    public function markAllRead()
    {
        $notificationIds = SystemInsight::whereNotNull('notification_id')->pluck('notification_id');

        DB::transaction(function () use ($notificationIds) {
            SystemInsight::where('is_read', false)->update(['is_read' => true]);

            if ($notificationIds->isEmpty() || ! request()->user()) {
                return;
            }

            $now = now();
            $rows = $notificationIds->map(fn (string $notificationId) => [
                'notification_id' => $notificationId,
                'user_id' => request()->user()->id,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();

            DB::table('notification_reads')->insertOrIgnore($rows);
        });

        return back()->with('success', __('messages.insights_marked_all_read'));
    }

    public function destroy(SystemInsight $insight)
    {
        DB::transaction(function () use ($insight) {
            if ($insight->notification_id) {
                $insight->notification?->delete();
            }

            $insight->delete();
        });

        return back()->with('success', __('messages.insight_deleted'));
    }
}

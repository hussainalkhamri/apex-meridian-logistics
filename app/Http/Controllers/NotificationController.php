<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\NotificationRead;
use App\Support\NotificationVisibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $allowedTargets = NotificationVisibility::allowedTargetsFor($user);
        $status = $request->query('status', 'all');
        $status = in_array($status, ['all', 'unread', 'read'], true) ? $status : 'all';
        $target = $request->query('target');
        $search = trim((string) $request->query('q', ''));

        $query = Notification::query()
            ->with(['reads' => fn ($readQuery) => $readQuery->where('user_id', $user->id)]);

        if ($status === 'unread') {
            NotificationVisibility::scopeUnreadFor($query, $user);
        } else {
            NotificationVisibility::scopeVisibleTo($query, $user);

            if ($status === 'read') {
                $query->whereHas('reads', fn ($readQuery) => $readQuery->where('user_id', $user->id));
            }
        }

        if (is_string($target) && in_array($target, $allowedTargets, true)) {
            $query->where('target', $target);
        }

        if ($search !== '') {
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('message', 'like', "%{$search}%")
                    ->orWhere('id', 'like', "%{$search}%")
                    ->orWhere('booking_id', 'like', "%{$search}%");
            });
        }

        $notifications = $query->latest()->paginate(30)->withQueryString();
        $statusCounts = [
            'all' => tap(Notification::query(), fn ($countQuery) => NotificationVisibility::scopeVisibleTo($countQuery, $user))->count(),
            'unread' => tap(Notification::query(), fn ($countQuery) => NotificationVisibility::scopeUnreadFor($countQuery, $user))->count(),
            'read' => tap(Notification::query(), fn ($countQuery) => NotificationVisibility::scopeVisibleTo($countQuery, $user))
                ->whereHas('reads', fn ($readQuery) => $readQuery->where('user_id', $user->id))
                ->count(),
        ];

        return view('pages.notifications', compact(
            'notifications',
            'allowedTargets',
            'status',
            'target',
            'search',
            'statusCounts'
        ));
    }

    public function markRead(Request $request, Notification $notification)
    {
        abort_unless(NotificationVisibility::canView($request->user(), $notification), 403);

        NotificationRead::firstOrCreate(
            [
                'notification_id' => $notification->id,
                'user_id' => $request->user()->id,
            ],
            ['read_at' => now()]
        );

        return back()->with('success', 'تم وضع العلامة كمقروء');
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $query = Notification::query();
        NotificationVisibility::scopeUnreadFor($query, $user);

        $now = now();
        $rows = $query->pluck('id')->map(fn (string $notificationId) => [
            'notification_id' => $notificationId,
            'user_id' => $user->id,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (! empty($rows)) {
            DB::table('notification_reads')->insertOrIgnore($rows);
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function markPageRead(Request $request)
    {
        $data = $request->validate([
            'target' => ['required', 'string', 'max:50'],
            'notification_ids' => ['nullable', 'array'],
            'notification_ids.*' => ['string', 'max:100', 'distinct'],
        ]);

        $user = $request->user();
        $query = Notification::query()->where('target', $data['target']);
        NotificationVisibility::scopeUnreadFor($query, $user);

        if (! empty($data['notification_ids'])) {
            $query->whereIn('id', $data['notification_ids']);
        }

        $now = now();
        $rows = $query->pluck('id')->map(fn (string $notificationId) => [
            'notification_id' => $notificationId,
            'user_id' => $user->id,
            'read_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if (! empty($rows)) {
            DB::table('notification_reads')->insertOrIgnore($rows);
        }

        return response()->json([
            'success' => true,
            'marked' => count($rows),
        ]);
    }
}

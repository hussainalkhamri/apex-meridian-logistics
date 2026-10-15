<?php

namespace App\Support;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationFeed
{
    public static function staff(User $user, ?Collection $notifications = null): array
    {
        $notifications ??= NotificationVisibility::scopeUnreadFor(Notification::query(), $user)
            ->latest()->get();

        $pending = null;
        if ($user->hasPermission('manage_pricing')) {
            // Count the actual queue, independently of notification read status.
            // The Booking scope excludes archived requests.
            $count = Booking::whereIn('status', ['new', 'needs_pricing'])->count();
            $pending = [
                'count' => $count,
                'message' => __('notifications.pending_pricing', ['count' => $count]),
                'url' => route('pricing.index'),
                'interval_ms' => 5 * 60 * 1000,
            ];
        }

        return self::snapshot($notifications, false, $pending);
    }

    public static function customer(Customer $customer, ?Collection $notifications = null): array
    {
        $notifications ??= Notification::query()
            ->where('target', 'customer')
            ->whereIn('booking_id', Booking::where('customer_id', $customer->id)->select('id'))
            ->whereNotIn('id', function ($query) use ($customer) {
                $query->select('notification_id')->from('customer_notification_reads')
                    ->where('customer_id', $customer->id);
            })
            ->latest()->get();

        return self::snapshot($notifications, true, null);
    }

    private static function snapshot(Collection $notifications, bool $customer, ?array $pending): array
    {
        return [
            'unread_count' => $notifications->count(),
            'notifications' => $notifications->map(fn (Notification $notification) => [
                'id' => $notification->id,
                'booking_id' => $notification->booking_id,
                'target' => $notification->target,
                'message' => $notification->message,
                'sound' => in_array($notification->sound, ['request', 'pricing'], true) ? $notification->sound : null,
                'time' => $notification->created_at?->diffForHumans(),
                'url' => $customer
                    ? route('customer.shipments.show', $notification->booking_id)
                    : route('notifications.index', ['target' => $notification->target]),
            ])->values()->all(),
            'pending_pricing' => $pending,
        ];
    }
}

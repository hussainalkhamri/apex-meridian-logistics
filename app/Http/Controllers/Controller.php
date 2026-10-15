<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Support\SystemId;
use Illuminate\Support\Str;

abstract class Controller
{
    protected function createNotification(array $attributes): Notification
    {
        $attributes['id'] = SystemId::make(Notification::class, 'N');
        $attributes['sound'] ??= 'request';

        $notification = Notification::create($attributes);

        // A successful action may notify several departments. Acknowledge it once
        // for the sender, even when they cannot view the receiving department.
        $feedback = session('notification_feedback', [
            'id' => (string) Str::uuid(),
            'sounds' => [],
            'notification_ids' => [],
        ]);
        $feedback['sounds'] = array_values(array_unique([...$feedback['sounds'], $notification->sound]));
        $feedback['notification_ids'][] = $notification->id;
        session()->flash('notification_feedback', $feedback);

        return $notification;
    }
}

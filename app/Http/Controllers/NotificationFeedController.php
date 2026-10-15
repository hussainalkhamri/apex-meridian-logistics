<?php

namespace App\Http\Controllers;

use App\Support\NotificationFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationFeedController extends Controller
{
    public function staff(Request $request): JsonResponse
    {
        $request->session()->keep('notification_feedback');

        return response()->json(NotificationFeed::staff($request->user('web')))
            ->header('Cache-Control', 'private, no-store');
    }

    public function customer(Request $request): JsonResponse
    {
        $request->session()->keep('notification_feedback');

        return response()->json(NotificationFeed::customer($request->user('customer')))
            ->header('Cache-Control', 'private, no-store');
    }
}

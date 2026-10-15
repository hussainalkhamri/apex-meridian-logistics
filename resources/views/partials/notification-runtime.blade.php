@php
    $notificationConfig = [
        'identity' => $notificationIdentity ?? 'guest',
        'feed_url' => $notificationFeedUrl ?? null,
        'snapshot' => $notificationSnapshot ?? ['unread_count' => 0, 'notifications' => [], 'pending_pricing' => null],
        'feedback' => session()->pull('notification_feedback'),
        'sounds' => ['request' => asset('sounds/request.mp3'), 'pricing' => asset('sounds/pricing.mp3')],
        'strings' => __('notifications'),
    ];
@endphp
<script id="notification-config" type="application/json">{!! json_encode($notificationConfig, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>

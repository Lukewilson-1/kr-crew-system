<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email notification channel
    |--------------------------------------------------------------------------
    |
    | The NotificationService persists in-app notifications to
    | `system_notifications` and, when this channel is enabled, also sends an
    | email copy to the recipient's `users.email` address (and to the
    | operational alert recipients for alert-type notifications).
    |
    */

    'email' => [
        // Master switch: send an email copy for every persisted notification.
        'enabled' => (bool) env('NOTIFICATIONS_EMAIL_ENABLED', true),

        // From address/name override. Falls back to config('mail.from').
        'from_address' => env('MAIL_FROM_ADDRESS'),
        'from_name' => env('MAIL_FROM_NAME'),

        // Comma-separated recipients for alert-type emails (ops/security).
        'alert_recipients' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('ALERT_EMAIL_RECIPIENTS', ''))
        ))),
    ],

    /*
    |--------------------------------------------------------------------------
    | In-app notification retention
    |--------------------------------------------------------------------------
    |
    | unused for now; hook for future pruning of read notifications.
    |
    */

    'retention_days' => (int) env('NOTIFICATIONS_RETENTION_DAYS', 90),
];
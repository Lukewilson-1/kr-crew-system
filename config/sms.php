<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS channel for departure reminders (Phase 4)
    |--------------------------------------------------------------------------
    |
    | A provider stub is wired so the T-1hr booking path can call
    | App\Services\SmsService without knowing the gateway. When no gateway is
    | configured the default 'log' provider records what would have been sent,
    | which keeps the existing email path working with zero setup.
    |
    */

    // Master switch for the SMS channel.
    'enabled' => (bool) env('SMS_ENABLED', false),

    // 'log' (stub) or any real gateway added later (africastalking, twilio, ...).
    'provider' => env('SMS_PROVIDER', 'log'),

    // Sender id / from label shown by the gateway.
    'from' => env('SMS_FROM', 'KR-Crew'),

    // Gateway credentials (left empty while on the log stub).
    'username' => env('SMS_USERNAME', ''),
    'api_key' => env('SMS_API_KEY', ''),
    'api_secret' => env('SMS_API_SECRET', ''),
    'endpoint' => env('SMS_ENDPOINT', ''),
];
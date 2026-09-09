<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Break-Glass & Emergency Access
    |--------------------------------------------------------------------------
    |
    | Emergency authentication path used when normal login (local credentials
    | or future SSO) is unavailable. Credentials are stored in the environment
    | (.env) and rotated regularly by an authorised administrator via the
    | `break-glass:rotate` command.
    |
    | Every successful AND failed attempt is written to the audit_logs table.
    | Sessions created this way are short-lived (BREAK_GLASS_SESSION_HOURS)
    | and a justification note is mandatory (BREAK_GLASS_REQUIRE_JUSTIFICATION).
    |
    */

    'enabled' => (bool) env('BREAK_GLASS_ENABLED', false),

    'username' => env('BREAK_GLASS_USERNAME', ''),

    'password' => env('BREAK_GLASS_PASSWORD', ''),

    /*
    | Username of the application account the break-glass session logs in as.
    | Defaults to the built-in superadmin account that already has full access.
    */
    'login_as' => env('BREAK_GLASS_LOGIN_AS', 'superadmin'),

    /*
    | Maximum lifetime of a break-glass session in hours.
    | After this the session is force-expired and audit-logged.
    */
    'session_hours' => (int) env('BREAK_GLASS_SESSION_HOURS', 8),

    'require_justification' => (bool) env('BREAK_GLASS_REQUIRE_JUSTIFICATION', true),
];
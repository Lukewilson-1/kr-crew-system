<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maintenance mode credentials
    |--------------------------------------------------------------------------
    |
    | The only credential accepted while the site is offline — no superadmin or
    | HQ account can pass the maintenance gate. Two usernames map to the one
    | shared passphrase so staff have an email and a plain login form.
    |
    | Rotate the passphrase like the break-glass access path:
    |   php artisan maintenance:rotate [--update-env]
    |
    | Values are read from the environment file (.env).
    |
    */
    'username' => env('MAINTENANCE_USERNAME', 'site@maintenance.com'),
    'login' => env('MAINTENANCE_LOGIN', 'sitemaintenance'),
    'password' => env('MAINTENANCE_PASSWORD', 'n0!!Pass10'),

    /*
    | Username of the application account the maintenance sign-in operates as
    | once the maintenance credentials are accepted. Like break-glass, this
    | defaults to the built-in superadmin account.
    */
    'login_as' => env('MAINTENANCE_LOGIN_AS', 'superadmin'),

    /*
    | Timezone used to interpret scheduled maintenance end times. The control
    | page collects a naive `datetime-local` value, so it is anchored to the
    | operator's business timezone (Africa/Nairobi) rather than the server
    | clock. The stored payload keeps the explicit offset, and the summary
    | times are rendered in this same timezone with a clear suffix.
    */
    'timezone' => env('MAINTENANCE_TIMEZONE', 'Africa/Nairobi'),

    /*
    | Hard lockdown on activation. When true (default), activating maintenance
    | signs out EVERY session across the system (file + database session store,
    | and "remember me" tokens), deletes the activation browser's bypass cookie,
    | and sends the operator to the maintenance sign-in page. Nobody stays
    | logged in — the only way back in is the maintenance account at
    | /maintenance-login, exactly as requested for a hard lock-down mode.
    |
    | Note: lockdown is triggered by the control page activation. A bare
    | `php artisan down` (rare, CLI) does not clear sessions by itself.
    */
    'lockdown_on_activate' => env('MAINTENANCE_LOCKDOWN', true),
];
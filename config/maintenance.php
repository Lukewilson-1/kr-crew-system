<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maintenance mode credentials
    |--------------------------------------------------------------------------
    |
    | These credentials are used to secure the maintenance-mode bypass so the
    | value never changes at runtime. They are read from the environment file.
    |
    */
    'username' => env('MAINTENANCE_USERNAME', 'site@maintenance.com'),
    'login' => env('MAINTENANCE_LOGIN', 'sitemaintenance'),
    'password' => env('MAINTENANCE_PASSWORD', 'n0!!Pass10'),
];
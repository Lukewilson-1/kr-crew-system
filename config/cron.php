<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Cron webhook secret
    |--------------------------------------------------------------------------
    |
    | Shared shared-hosting hosts (e.g. Microsoft shared servers) do not expose
    | a real cron, so Laravel's scheduler cannot run. Instead, a free external
    | ping service (cron-job.org, etc.) hits a webhook URL that runs the
    | scheduled command directly. This token must be appended to that URL so
    | strangers cannot trigger it.
    |
    */
    'token' => env('CRON_TOKEN', 'change-me'),
];

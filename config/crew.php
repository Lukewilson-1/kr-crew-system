<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Crew status rules
    |--------------------------------------------------------------------------
    */

    // How many trailing days of the current month stay editable for past-day
    // status corrections. Day "today" and the previous (n-1) days are editable.
    'past_day_edit_window_days' => (int) env('CREW_PAST_DAY_EDIT_WINDOW_DAYS', 2),
];

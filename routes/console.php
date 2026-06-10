<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment('\"The best thing about a boolean is even if you are wrong, you are only off by a bit.\"');
})->describe('Display an inspiring quote');

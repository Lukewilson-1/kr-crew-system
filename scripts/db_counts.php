<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
$tables = ['crew_members','depots','designations','shift_templates','users','report_definitions'];
foreach($tables as $t){
    try{ echo $t . ': ' . DB::table($t)->count() . PHP_EOL; } catch (\Throwable $e) { echo $t . ': error (' . $e->getMessage() . ')' . PHP_EOL; }
}

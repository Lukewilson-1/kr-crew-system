<?php
require __DIR__ . '/../vendor/autoload.php';
$m = new App\CrewMember();
echo $m->getKeyName() . PHP_EOL;

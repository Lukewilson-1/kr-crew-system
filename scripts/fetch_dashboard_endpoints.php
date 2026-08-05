<?php
$base = 'http://127.0.0.1:8000';
$endpoints = [
    '/admin/meta',
    '/mysql/meta/depotMeta',
    '/mysql/users',
    '/mysql/crew-view',
    '/mysql/meta/reportMeta',
];
foreach ($endpoints as $ep) {
    $url = $base . $ep;
    echo "=== $ep ===\n";
    $c = @file_get_contents($url);
    if ($c === false) { echo "ERROR fetching $url\n\n"; continue; }
    $json = json_decode($c, true);
    if ($json === null) { echo $c . "\n\n"; continue; }
    echo json_encode($json, JSON_PRETTY_PRINT) . "\n\n";
}

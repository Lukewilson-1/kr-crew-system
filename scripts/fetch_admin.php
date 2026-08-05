<?php
$urls = ['http://127.0.0.1:8000/admin', 'http://localhost:8000/admin'];
foreach ($urls as $u) {
    $c = @file_get_contents($u);
    if ($c !== false) {
        echo "STATUS:OK\n";
        echo substr($c, 0, 4000);
        exit(0);
    }
}
echo "ERROR: unable to fetch\n";
exit(1);

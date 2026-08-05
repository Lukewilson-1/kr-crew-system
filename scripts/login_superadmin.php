<?php
function fetch($url, &$headers=null, &$cookies=null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    $header = substr($resp, 0, $info['header_size']);
    $body = substr($resp, $info['header_size']);
    if ($headers !== null) {
        $headers = $header;
    }
    if ($cookies !== null) {
        preg_match_all('/^Set-Cookie:\s*([^;]+)/mi', $header, $m);
        $cookies = [];
        foreach ($m[1] as $c) {
            $parts = explode('=', $c, 2);
            $cookies[$parts[0]] = $parts[1] ?? '';
        }
    }
    curl_close($ch);
    return $body;
}
$base = 'http://127.0.0.1:8000';
$home = fetch($base . '/admin', $headers, $cookies);
if ($home === false) { echo "ERROR: cannot fetch admin"; exit(1); }
// extract CSRF token
$csrf = null;
if (preg_match('/<meta name="csrf-token" content="([^"]+)"/i', $home, $m)) { $csrf = $m[1]; }
if (!$csrf) { echo "ERROR: CSRF token not found\n"; exit(1); }
// prepare login payload
$username = 'superadmin';
$password = 'superadmin123';
$payload = json_encode(['username' => $username, 'password' => $password]);
$ch = curl_init($base . '/mysql/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "X-CSRF-TOKEN: $csrf"]);
curl_setopt($ch, CURLOPT_HEADER, false);
if (is_array($cookies) && count($cookies) > 0) {
    $pairs = [];
    foreach ($cookies as $k => $v) {
        $pairs[] = "$k=$v";
    }
    curl_setopt($ch, CURLOPT_COOKIE, implode('; ', $pairs));
}
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if ($res === false) { echo "CURL ERROR: ".curl_error($ch); exit(1); }
curl_close($ch);
echo "HTTP:$code\n";
echo $res . PHP_EOL;

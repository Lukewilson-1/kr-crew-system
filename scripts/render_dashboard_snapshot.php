<?php
$base = 'http://127.0.0.1:8000';
function get($path){ $c = @file_get_contents($path); if($c===false) return null; return json_decode($c, true); }
$adminMeta = get($base . '/admin/meta') ?: [];
$crew = get($base . '/mysql/crew-view') ?: [];
$depots = $adminMeta['depotMeta'] ?? [];
$statuses = $adminMeta['statusMeta'] ?? [];
$reports = $adminMeta['reportMeta'] ?? [];
$users = $adminMeta['users'] ?? [];
$totalCrew = count($crew);
$byDepot = [];
$byStatus = [];
foreach ($crew as $c) {
    $depot = $c['depot'] ?? ($c['depot_code'] ?? 'Unknown');
    $status = $c['status'] ?? ($c['employment_status_code'] ?? ($c['current_status'] ?? 'UNK'));
    $byDepot[$depot] = ($byDepot[$depot] ?? 0) + 1;
    $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
}
// map status codes to labels
$statusLabels = [];
foreach ($statuses as $s) { $statusLabels[$s['id']] = $s['label']; }
$html = '<!doctype html><html><head><meta charset="utf-8"><title>Dashboard snapshot - Superadmin</title><style>body{font-family:Arial,Helvetica,sans-serif;padding:20px;background:#f6f7fb} .grid{display:flex;gap:12px;flex-wrap:wrap} .card{background:#fff;padding:16px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,.08);min-width:220px} h1{margin:0 0 12px 0} .list{margin:8px 0 0 0;padding:0;list-style:none} .list li{padding:6px 0;border-bottom:1px solid #f0f0f3} .muted{color:#666;font-size:13px}</style></head><body>';
$html .= '<h1>Admin Dashboard Snapshot (Superadmin)</h1>';
$html .= "<div class=\"grid\">";
$html .= "<div class=\"card\"><strong>Total crew</strong><div style=\"font-size:28px;margin-top:8px\">$totalCrew</div><div class=\"muted\">Total crew records</div></div>";
// depot cards
$depotCards = '';
foreach ($depots as $d) {
    $id = $d['id']; $label = $d['label'] ?? $id; $count = $byDepot[$id] ?? 0;
    $depotCards .= "<div class=\"card\"><strong>$label</strong><div style=\"font-size:22px;margin-top:8px\">$count</div><div class=\"muted\">Crew in depot</div></div>";
}
$html .= $depotCards;
// status breakdown
$statusHtml = '<div class="card"><strong>Status breakdown</strong><ul class="list">';
foreach ($byStatus as $code => $cnt) {
    $label = $statusLabels[$code] ?? $code;
    $statusHtml .= "<li><strong>$label</strong> <span style=\"float:right\">$cnt</span></li>";
}
$statusHtml .= '</ul></div>';
$html .= $statusHtml;
$html .= '</div>'; // grid
// reports list
$html .= '<div style="margin-top:18px"><div class="card" style="max-width:800px"><strong>Configured reports</strong><ul class="list">';
foreach ($reports as $r) { $html .= '<li><strong>'.htmlspecialchars($r['label'] ?? $r['id']).'</strong> — '.htmlspecialchars($r['description'] ?? '').'</li>'; }
$html .= '</ul></div></div>';
// users small list
$html .= '<div style="margin-top:18px"><div class="card" style="max-width:600px"><strong>Admin users</strong><ul class="list">';
foreach ($users as $u) { $html .= '<li>'.htmlspecialchars($u['username'] . ' — ' . ($u['name'] ?? '')).'</li>'; }
$html .= '</ul></div></div>';
$html .= '</body></html>';
file_put_contents(__DIR__.'/dashboard_snapshot.html', $html);
echo "Wrote scripts/dashboard_snapshot.html\n";

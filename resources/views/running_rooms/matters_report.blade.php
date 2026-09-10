<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Matters Arising Report – Kenya Railways</title>
<style>
  :root{
    --kr-maroon:#681828; --kr-gold:#F8C808; --dark:#1A1A2E;
    --ink:#22262b; --muted:#5b6169; --line:#d9dee7; --bg:#f4f6f9;
    --ok:#1B5E20; --err:#B71C1C; --warn:#E65100; --chip:#fff;
  }
  *{box-sizing:border-box}
  html,body{margin:0;padding:0}
  body{
    font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
    color:var(--ink); background:var(--bg); font-size:12px; line-height:1.5;
  }
  .rp-page{max-width:900px;margin:0 auto;padding:22px 20px 40px}

  /* ── Report letterhead ─────────────────────────────── */
  .rp-head{
    display:flex;align-items:center;gap:14px;padding:14px 18px;
    background:#fff;border:1px solid var(--line);border-radius:10px;
    margin-bottom:14px;
  }
  .rp-head .rp-h-logo{width:56px;height:56px;border-radius:8px;object-fit:contain;flex-shrink:0}
  .rp-head .rp-h-id{display:flex;align-items:center;gap:6px;color:var(--kr-maroon);font-weight:700;font-size:12px;letter-spacing:.04em}
  .rp-head .rp-h-title{font-size:22px;font-weight:800;color:var(--dark);letter-spacing:.01em}
  .rp-head .rp-h-sub{font-size:12px;color:var(--muted)}
  .rp-head .rp-h-bar{width:4px;align-self:stretch;border-radius:2px;background:var(--kr-gold)}
  .rp-head .rp-h-meta{margin-left:auto;text-align:right;font-size:11px;color:var(--muted);line-height:1.6}
  .rp-head .rp-h-meta b{color:var(--ink)}

  /* ── Action bar ────────────────────────────────────── */
  .rp-actions{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
  .rp-btn{
    display:inline-flex;align-items:center;gap:7px;padding:9px 16px;border-radius:8px;
    border:1px solid var(--line);background:#fff;color:var(--dark);font-weight:600;font-size:13px;
    cursor:pointer;text-decoration:none;
  }
  .rp-btn:hover{background:#eef1f5}
  .rp-btn.primary{background:var(--kr-maroon);border-color:var(--kr-maroon);color:#fff}
  .rp-btn.primary:hover{background:#7d2034}
  .rp-btn.ghost{background:transparent}

  /* ── Report options form ───────────────────────────── */
  .rp-options{
    background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:14px;
  }
  .rp-options .rp-opt-title{font-size:12px;font-weight:800;color:var(--dark);margin:0 0 10px;text-transform:uppercase;letter-spacing:.05em}
  .rp-options .rp-opt-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px;align-items:start}
  .rp-options label.rp-opt-label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:6px}
  .rp-options input[type=date],.rp-options select{
    width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:6px;font-size:12px;font-family:inherit;color:var(--ink);background:#fff;
  }
  .rp-rooms{display:grid;grid-template-columns:1fr 1fr;gap:6px 14px;max-height:150px;overflow:auto;padding:2px}
  .rp-rooms label{display:flex;align-items:center;gap:7px;font-size:12px;color:var(--ink);cursor:pointer;line-height:1.3}
  .rp-rooms .rp-opt-all{font-weight:700}
  .rp-rooms input[type=checkbox]{width:15px;height:15px;accent-color:var(--kr-maroon)}
  .rp-opt-foot{display:flex;align-items:center;gap:10px;margin-top:12px}
  .rp-opt-foot .rp-hint{font-size:11px;color:var(--muted)}

  /* ── Meta strip ────────────────────────────────────── */
  .rp-meta{
    display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;
    background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:14px;
  }
  .rp-meta .m-cell .m-k{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)}
  .rp-meta .m-cell .m-v{font-size:12px;font-weight:600;color:var(--dark);margin-top:2px;word-break:break-word}

  /* ── KPIs ──────────────────────────────────────────── */
  .rp-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
  .rp-kpi{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .rp-kpi .k-n{font-size:26px;font-weight:800;font-family:'Consolas','Courier New',monospace;line-height:1}
  .rp-kpi .k-l{font-size:11px;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:.05em}
  .rp-kpi.total .k-n{color:var(--dark)}
  .rp-kpi.open .k-n{color:var(--err)}
  .rp-kpi.resolved .k-n{color:var(--ok)}
  .rp-kpi.rate .k-n{color:var(--kr-maroon)}

  /* ── Sections ──────────────────────────────────────── */
  .rp-section{background:#fff;border:1px solid var(--line);border-radius:10px;padding:16px 18px;margin-bottom:14px}
  .rp-section h2{
    display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:15px;font-weight:800;
    color:var(--dark);letter-spacing:.02em;
  }
  .rp-section h2::before{content:'';width:4px;height:18px;border-radius:2px;background:var(--kr-gold)}
  .rp-sec-note{font-size:12px;color:var(--muted);margin:0 0 12px}

  /* ── Tables ────────────────────────────────────────── */
  table.rp-table{width:100%;border-collapse:collapse;font-size:12px}
  .rp-table th{
    text-align:left;padding:7px 9px;background:#f1f3f6;color:#3a3f46;
    font-size:11px;text-transform:uppercase;letter-spacing:.04em;border:1px solid var(--line);
  }
  .rp-table td{padding:7px 9px;border:1px solid var(--line);vertical-align:top}
  .rp-table tr:nth-child(even) td{background:#fafbfc}

  /* ── Matter cards ──────────────────────────────────── */
  .rp-matter{border:1px solid var(--line);border-radius:8px;padding:12px 14px;margin-bottom:12px;break-inside:auto}
  .rp-matter-head{display:flex;align-items:center;gap:10px;flex-wrap:wrap;break-inside:avoid;margin-bottom:6px}
  .rp-matter-id{font-family:'Consolas','Courier New',monospace;font-weight:700;font-size:12px;color:var(--kr-maroon)}
  .rp-matter-date{font-family:'Consolas','Courier New',monospace;font-size:11px;color:var(--muted)}
  .rp-badge{margin-left:auto;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;
    padding:3px 9px;border-radius:99px;border:1px solid var(--line)}
  .rp-badge.open{color:var(--err);border-color:var(--err);background:#fdecec}
  .rp-badge.resolved{color:var(--ok);border-color:var(--ok);background:#eaf6ec}
  .rp-matter-meta{font-size:12px;color:var(--muted);margin-bottom:6px;break-inside:avoid}
  .rp-matter-meta b{color:var(--ink)}
  .rp-desc{font-size:12px;color:var(--ink);margin:6px 0}
  .rp-desc :is(p,ul,ol){margin:.35em 0}
  .rp-evid-label{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:8px}
  .rp-evidence{display:flex;flex-wrap:wrap;gap:10px;margin-top:6px}
  .rp-fig{break-inside:avoid;border:1px solid var(--line);border-radius:6px;overflow:hidden;background:#fff;width:190px}
  .rp-fig img{width:100%;height:142px;object-fit:cover;display:block;background:#eef0f3}
  .rp-fig figcaption{
    font-size:10px;color:var(--muted);text-align:center;padding:4px 6px;
    border-top:1px solid var(--line);
  }

  /* ── Footer ────────────────────────────────────────── */
  .rp-foot{
    display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;
    font-size:11px;color:var(--muted);padding:4px 4px 0;
  }
  .rp-no-print{display:inline-flex}

  /* ── Print ─────────────────────────────────────────── */
  @media print{
    @page{size:A4 portrait;margin:10mm 11mm}
    html,body{background:#fff !important}
    body{font-size:12px}
    .rp-page{max-width:none;padding:0}
    *{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important}
    .rp-no-print{display:none !important}
    .rp-head{display:flex;border:1.2px solid #333;border-radius:4px;print-color-adjust:exact}
    .rp-kpis,.rp-meta,.rp-section{box-shadow:none !important;border:1px solid #888;border-radius:4px}
    .rp-kpis{grid-template-columns:repeat(4,1fr)}
    .rp-kpi{border:1px solid #999;border-radius:4px;padding:8px 10px}
    .rp-kpi .k-n{font-size:20px}
    .rp-table th{background:#eee !important;color:#222 !important;border:1px solid #888}
    .rp-table td{border:1px solid #bbb}
    .rp-matter-head,.rp-matter-meta,.rp-fig,table.rp-table{break-inside:avoid;page-break-inside:avoid}
    h2{break-after:avoid;page-break-after:avoid}
    .rp-matter{page-break-inside:auto}
    .rp-evidence{gap:8px}
  }
  @media print and (orientation:landscape){
    @page{size:A4 landscape;margin:9mm}
  }
</style>
</head>
<body>
<div class="rp-page">

  <div class="rp-head">
    <img class="rp-h-logo" src="{{ $logoDataUri ?: asset('assets/logo.png') }}" alt="Kenya Railways">
    <div class="rp-h-bar"></div>
    <div>
      <div class="rp-h-id">KENYA RAILWAYS &nbsp;·&nbsp; CM &amp; RM SYSTEM</div>
      <div class="rp-h-title">Matters Arising Report</div>
      <div class="rp-h-sub">{{ $scope['category'] ? 'Category: '.$scope['category'] : 'Running rooms — operational issues register' }}</div>
    </div>
    <div class="rp-h-meta">
      Generated: <b>{{ now()->format('d M Y, H:i') }}</b><br>
      Prepared by: <b>{{ $generatedBy }}</b>
    </div>
  </div>

  <div class="rp-actions rp-no-print">
    <button type="button" class="rp-btn primary" onclick="window.print()">&#128424;&nbsp;Print / Save as PDF</button>
    <button type="button" class="rp-btn" onclick="downloadReport()">&#11015;&nbsp;Download (HTML)</button>
    <a class="rp-btn ghost" href="{{ url('/running-rooms') }}">&#8592;&nbsp;Back to running rooms</a>
  </div>

  <div class="rp-options rp-no-print">
    <div class="rp-opt-title">Report options</div>
    <form method="get" action="{{ url('/running-rooms/report/matters') }}" id="rpOptionsForm">
      <div class="rp-opt-grid">
        <div>
          <label class="rp-opt-label" for="rpRoomsAll">Running rooms</label>
          <div class="rp-rooms" id="rpRoomsList">
            <label class="rp-opt-all"><input type="checkbox" id="rpRoomsAll" {{ empty($scope['roomIds']) ? 'checked' : '' }}> All rooms</label>
            @foreach ($rooms as $room)
              <label><input type="checkbox" name="room[]" value="{{ $room->id }}" {{ in_array((int) $room->id, $scope['roomIds'], true) ? 'checked' : '' }}> {{ $room->name }}</label>
            @endforeach
          </div>
        </div>
        <div>
          <label class="rp-opt-label">Period</label>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px">
            <input type="date" name="from" value="{{ $scope['from'] }}" title="From">
            <input type="date" name="to" value="{{ $scope['to'] }}" title="To">
          </div>
        </div>
        <div>
          <label class="rp-opt-label" for="rpStatus">Status</label>
          <select name="status" id="rpStatus">
            <option value="" {{ ! $scope['status'] ? 'selected' : '' }}>All</option>
            <option value="open" {{ $scope['status'] === 'open' ? 'selected' : '' }}>Open</option>
            <option value="resolved" {{ $scope['status'] === 'resolved' ? 'selected' : '' }}>Resolved</option>
          </select>
        </div>
        <div>
          <label class="rp-opt-label" for="rpCategory">Category</label>
          <select name="category" id="rpCategory">
            <option value="" {{ ! $scope['category'] ? 'selected' : '' }}>All</option>
            @foreach ($categories as $cat)
              <option value="{{ $cat }}" {{ $scope['category'] === $cat ? 'selected' : '' }}>{{ $cat }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="rp-opt-foot">
        <button type="submit" class="rp-btn primary">&#128269;&nbsp;Generate report</button>
        <a class="rp-btn ghost" href="{{ url('/running-rooms/report/matters') }}">Reset</a>
        <span class="rp-hint">Leave a filter blank to include everything, or pick "All rooms" for the whole register.</span>
      </div>
    </form>
  </div>

  @if ($matters->isEmpty())
    <div class="rp-section">
      <h2>No matters in this scope</h2>
      <p class="rp-sec-note">No matter records matched the selected filters. Adjust the date range, room, category or status and try again.</p>
    </div>
  @else
    @php
      $total = array_sum(array_column($byRoom, 'total'));
      $open = array_sum(array_column($byRoom, 'open'));
      $resolved = array_sum(array_column($byRoom, 'resolved'));
      $rate = $total ? round(($resolved / $total) * 100) : 0;
    @endphp

    <div class="rp-meta">
      <div class="m-cell"><div class="m-k">Rooms</div><div class="m-v">@if (count($scope['roomsSelected'])){{ 'Selected: '.implode(', ', $scope['roomsSelected']) }}@else{{ 'All '.count($scope['roomsAll']).' rooms' }}@endif</div></div>
      <div class="m-cell"><div class="m-k">Status</div><div class="m-v">{{ $scope['status'] ? ucfirst($scope['status']) : 'All' }}</div></div>
      <div class="m-cell"><div class="m-k">Category</div><div class="m-v">{{ $scope['category'] ?: 'All' }}</div></div>
      <div class="m-cell"><div class="m-k">Period</div><div class="m-v">{{ $scope['from'] ? \Illuminate\Support\Carbon::parse($scope['from'])->format('d M Y') : '—' }} &rarr; {{ $scope['to'] ? \Illuminate\Support\Carbon::parse($scope['to'])->format('d M Y') : now()->format('d M Y') }}</div></div>
    </div>

    <div class="rp-kpis">
      <div class="rp-kpi total"><div class="k-n">{{ $total }}</div><div class="k-l">Total matters</div></div>
      <div class="rp-kpi open"><div class="k-n">{{ $open }}</div><div class="k-l">Open</div></div>
      <div class="rp-kpi resolved"><div class="k-n">{{ $resolved }}</div><div class="k-l">Resolved</div></div>
      <div class="rp-kpi rate"><div class="k-n">{{ $rate }}%</div><div class="k-l">Resolution rate</div></div>
    </div>

    <div class="rp-section">
      <h2>Summary by room</h2>
      <p class="rp-sec-note">Open vs resolved matters per running room.</p>
      <table class="rp-table">
        <thead><tr><th>Room</th><th>Total</th><th>Open</th><th>Resolved</th><th>Resolution rate</th></tr></thead>
        <tbody>
          @foreach ($byRoom as $room => $s)
            <tr>
              <td><b>{{ $room }}</b></td>
              <td>{{ $s['total'] }}</td>
              <td>{{ $s['open'] }}</td>
              <td>{{ $s['resolved'] }}</td>
              <td>{{ $s['total'] ? round(($s['resolved'] / $s['total']) * 100) : 0 }}%</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="rp-section">
      <h2>Summary by category</h2>
      <p class="rp-sec-note">Distribution of matters by issue category.</p>
      <table class="rp-table">
        <thead><tr><th>Category</th><th>Count</th></tr></thead>
        <tbody>
          @foreach ($byCategory as $category => $count)
            <tr><td>{{ $category }}</td><td>{{ $count }}</td></tr>
          @endforeach
        </tbody>
      </table>
    </div>

    <div class="rp-section">
      <h2>Matters with evidence</h2>
      <p class="rp-sec-note">Full register — each matter with description, status and attached photographic evidence.</p>

      @foreach ($matters as $m)
        <div class="rp-matter">
          <div class="rp-matter-head">
            <span class="rp-matter-id">{{ $m['ticket_no'] }}</span>
            <span class="rp-matter-date">{{ $m['date'] }}</span>
            <span class="rp-badge {{ $m['status'] }}">{{ $m['status'] }}</span>
          </div>
          <div class="rp-matter-meta">
            <b>{{ $m['room'] }}</b> &nbsp;·&nbsp; {{ $m['category'] }}
            &nbsp;·&nbsp; Reported by: <b>{{ $m['reported_by'] }}</b>
            @if ($m['status'] === 'resolved')
              &nbsp;·&nbsp; Resolved {{ $m['resolved_date'] }}
            @endif
          </div>
          <div class="rp-desc">{!! $m['description'] !!}</div>
          @if ($m['photos']->isNotEmpty())
            <div class="rp-evid-label">Attached evidence ({{ $m['photos']->count() }})</div>
            <div class="rp-evidence">
              @foreach ($m['photos'] as $i => $photo)
                <figure class="rp-fig">
                  <img src="{{ $photo['src'] }}" alt="Evidence {{ $i + 1 }} for {{ $m['ticket_no'] }}" loading="lazy">
                  <figcaption>Evidence {{ $i + 1 }}@if (! $photo['embedded']) · <a href="{{ $photo['url'] }}" target="_blank" rel="noopener">view online</a>@endif</figcaption>
                </figure>
              @endforeach
            </div>
          @endif
        </div>
      @endforeach
    </div>
  @endif

  <div class="rp-foot">
    <span>Kenya Railways &middot; Crew Management &amp; Running Rooms</span>
    <span>Generated by {{ config('app.name') }} on {{ now()->format('Y-m-d H:i') }}</span>
  </div>

</div>

<script>
  (function () {
    var roomsAll = document.getElementById('rpRoomsAll');
    if (!roomsAll) return;
    var roomBoxes = Array.prototype.slice.call(document.querySelectorAll('#rpRoomsList input[name="room[]"]'));
    roomsAll.addEventListener('change', function () {
      if (roomsAll.checked) roomBoxes.forEach(function (b) { b.checked = false; });
    });
    roomBoxes.forEach(function (b) {
      b.addEventListener('change', function () {
        if (b.checked) roomsAll.checked = false;
        if (!roomBoxes.some(function (x) { return x.checked; })) roomsAll.checked = true;
      });
    });
  })();

  function downloadReport() {
    var html = '<!DOCTYPE html>\n' + document.documentElement.outerHTML;
    var blob = new Blob([html], { type: 'text/html;charset=utf-8' });
    var stamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'KR-Matters-Report-' + stamp + '.html';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
  }
</script>
</body>
</html>
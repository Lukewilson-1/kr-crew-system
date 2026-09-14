<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $report->title() }} – Kenya Railways</title>
<style>
  :root{
    --kr-maroon:#681828; --kr-gold:#F8C808; --dark:#1A1A2E;
    --ink:#22262b; --muted:#5b6169; --line:#d9dee7; --bg:#f4f6f9;
    --ok:#1B5E20; --err:#B71C1C; --warn:#E65100;
  }
  *{box-sizing:border-box}
  html,body{margin:0;padding:0}
  body{
    font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;
    color:var(--ink); background:var(--bg); font-size:12px; line-height:1.5;
  }
  .rp-page{max-width:900px;margin:0 auto;padding:22px 20px 40px}

  .rp-head{
    display:flex;align-items:center;gap:14px;padding:14px 18px;
    background:#fff;border:1px solid var(--line);border-radius:10px;margin-bottom:14px;
  }
  .rp-head .rp-h-logo{width:56px;height:56px;border-radius:8px;object-fit:contain;flex-shrink:0}
  .rp-head .rp-h-id{display:flex;align-items:center;gap:6px;color:var(--kr-maroon);font-weight:700;font-size:12px;letter-spacing:.04em}
  .rp-head .rp-h-title{font-size:22px;font-weight:800;color:var(--dark);letter-spacing:.01em}
  .rp-head .rp-h-bar{width:4px;align-self:stretch;border-radius:2px;background:var(--kr-gold)}
  .rp-head .rp-h-meta{margin-left:auto;text-align:right;font-size:11px;color:var(--muted);line-height:1.6}
  .rp-head .rp-h-meta b{color:var(--ink)}

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

  .rp-options{
    background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:14px;
  }
  .rp-options .rp-opt-title{font-size:12px;font-weight:800;color:var(--dark);margin:0 0 10px;text-transform:uppercase;letter-spacing:.05em}
  .rp-options .rp-opt-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px;align-items:start}
  .rp-options label.rp-opt-label{display:block;font-size:11px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:6px}
  .rp-options input[type=date],.rp-options input[type=month],.rp-options select{
    width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:6px;font-size:12px;font-family:inherit;color:var(--ink);background:#fff;
  }
  .rp-opt-foot{display:flex;align-items:center;gap:10px;margin-top:12px}
  .rp-opt-foot .rp-hint{font-size:11px;color:var(--muted)}

  .rp-meta{
    display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px;
    background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:14px;
  }
  .rp-meta .m-cell .m-k{font-size:10px;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)}
  .rp-meta .m-cell .m-v{font-size:12px;font-weight:600;color:var(--dark);margin-top:2px;word-break:break-word}

  .rp-kpis{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}
  .rp-kpi{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .rp-kpi .k-n{font-size:22px;font-weight:800;font-family:'Consolas','Courier New',monospace;line-height:1.1}
  .rp-kpi .k-l{font-size:11px;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:.05em}
  .rp-kpi .k-s{font-size:11px;color:var(--warn);margin-top:2px}

  .rp-section{background:#fff;border:1px solid var(--line);border-radius:10px;padding:16px 18px;margin-bottom:14px}
  .rp-section h2{display:flex;align-items:center;gap:8px;margin:0 0 4px;font-size:15px;font-weight:800;color:var(--dark)}
  .rp-section h2::before{content:'';width:4px;height:18px;border-radius:2px;background:var(--kr-gold)}
  .rp-sec-note{font-size:12px;color:var(--muted);margin:0 0 12px}

  table.rp-table{width:100%;border-collapse:collapse;font-size:12px}
  .rp-table th{text-align:left;padding:7px 9px;background:#f1f3f6;color:#3a3f46;font-size:11px;text-transform:uppercase;letter-spacing:.04em;border:1px solid var(--line)}
  .rp-table td{padding:7px 9px;border:1px solid var(--line);vertical-align:top;word-break:break-word}
  .rp-table tr:nth-child(even) td{background:#fafbfc}

  .rp-foot{display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;font-size:11px;color:var(--muted);padding:4px 4px 0}

  @media(max-width:768px){
    .rp-page{padding:16px 12px 32px}
    .rp-head{flex-direction:column;align-items:flex-start;gap:10px}
    .rp-head .rp-h-meta{margin-left:0;text-align:left}
    .rp-head .rp-h-title{font-size:18px}
    .rp-kpis{grid-template-columns:repeat(2,1fr);gap:8px}
    .rp-kpi .k-n{font-size:18px}
    .rp-meta{grid-template-columns:1fr 1fr}
    .rp-section{padding:12px 14px}
    table.rp-table{display:block;overflow-x:auto;-webkit-overflow-scrolling:touch}
    .rp-actions{flex-direction:column}
    .rp-btn{justify-content:center;width:100%}
  }
  @media(max-width:480px){
    .rp-kpis{grid-template-columns:1fr 1fr;gap:6px}
    .rp-meta{grid-template-columns:1fr}
    .rp-head .rp-h-title{font-size:16px}
    .rp-options .rp-opt-grid{grid-template-columns:1fr}
  }

  @media print{
    @page{size:A4 portrait;margin:10mm 11mm}
    html,body{background:#fff !important}
    body{font-size:12px}
    .rp-page{max-width:none;padding:0}
    *{-webkit-print-color-adjust:exact !important;print-color-adjust:exact !important}
    .rp-no-print{display:none !important}
    .rp-head{border:1.2px solid #333;border-radius:4px}
    .rp-kpis,.rp-meta,.rp-section{border:1px solid #888;border-radius:4px}
    .rp-kpis{grid-template-columns:repeat(4,1fr)}
    .rp-kpi{border:1px solid #999;border-radius:4px;padding:8px 10px}
    .rp-kpi .k-n{font-size:18px}
    .rp-table th{background:#eee !important;color:#222 !important;border:1px solid #888}
    .rp-table td{border:1px solid #bbb}
    .rp-section{break-inside:avoid;page-break-inside:avoid}
    table.rp-table{break-inside:auto;page-break-inside:auto}
    h2{break-after:avoid;page-break-after:avoid}
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
      <div class="rp-h-title">{{ $report->title() }}</div>
      <div class="rp-h-sub">{{ $report->description() }}</div>
    </div>
    <div class="rp-h-meta">
      Generated: <b>{{ now()->format('d M Y, H:i') }}</b><br>
      Prepared by: <b>{{ $user->username }}</b>
    </div>
  </div>

  <div class="rp-actions rp-no-print">
    <button type="button" class="rp-btn primary" onclick="window.print()">&#128424;&nbsp;Print / Save as PDF</button>
    <button type="button" class="rp-btn" onclick="downloadReport()">&#11015;&nbsp;Download (HTML)</button>
    <a class="rp-btn ghost" href="{{ route('reports.system') }}">&#8592;&nbsp;All reports</a>
  </div>

  @if (! empty($report->filters()))
    <div class="rp-options rp-no-print">
      <div class="rp-opt-title">Report options</div>
      <form method="get" action="{{ url()->current() }}">
        <div class="rp-opt-grid">
          @foreach ($report->filters() as $filter)
            <div>
              <label class="rp-opt-label" for="f-{{ $filter['key'] }}">{{ $filter['label'] }}</label>
              @if ($filter['type'] === 'date')
                <input type="date" id="f-{{ $filter['key'] }}" name="{{ $filter['key'] }}" value="{{ $filterValues[$filter['key']] ?? '' }}">
              @elseif ($filter['type'] === 'month')
                <input type="month" id="f-{{ $filter['key'] }}" name="{{ $filter['key'] }}" value="{{ $filterValues[$filter['key']] ?? '' }}">
              @elseif ($filter['type'] === 'select')
                <select id="f-{{ $filter['key'] }}" name="{{ $filter['key'] }}">
                  <option value="">All</option>
                  @foreach ($report->filterOptions($user, $filter['key']) as $value => $label)
                    <option value="{{ $value }}" {{ (string) ($filterValues[$filter['key']] ?? '') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                  @endforeach
                </select>
              @endif
            </div>
          @endforeach
        </div>
        <div class="rp-opt-foot">
          <button type="submit" class="rp-btn primary">&#128269;&nbsp;Generate report</button>
          <a class="rp-btn ghost" href="{{ url()->current() }}">Reset</a>
          <span class="rp-hint">Leave a filter blank to include everything.</span>
        </div>
      </form>
    </div>
  @endif

  @if (! empty($result['filters_applied']))
    <div class="rp-meta">
      @foreach ($result['filters_applied'] as $label => $value)
        <div class="m-cell"><div class="m-k">{{ $label }}</div><div class="m-v">{{ $value }}</div></div>
      @endforeach
      <div class="m-cell"><div class="m-k">As of</div><div class="m-v">{{ $result['as_of'] ?? now()->format('Y-m-d H:i') }}</div></div>
    </div>
  @endif

  @if (! empty($result['kpis']))
    <div class="rp-kpis">
      @foreach ($result['kpis'] as $kpi)
        <div class="rp-kpi">
          <div class="k-n">{{ $kpi['value'] }}</div>
          <div class="k-l">{{ $kpi['label'] }}</div>
          @if (! empty($kpi['sub']))
            <div class="k-s">{{ $kpi['sub'] }}</div>
          @endif
        </div>
      @endforeach
    </div>
  @endif

  @if (empty($result['sections']))
    <div class="rp-section">
      <h2>No data</h2>
      <p class="rp-sec-note">Nothing matched the selected filters. Adjust the options above and generate again.</p>
    </div>
  @else
    @foreach ($result['sections'] as $section)
      <div class="rp-section">
        <h2>{{ $section['title'] }}</h2>
        @if (! empty($section['note']))
          <p class="rp-sec-note">{{ $section['note'] }}</p>
        @endif

        @if (empty($section['columns']))
          <p class="rp-sec-note">{{ $section['rows'] !== [] ? $section['rows'][0]['note'] ?? '' : '' }}</p>
        @else
          <table class="rp-table">
            <thead>
              <tr>
                @foreach ($section['columns'] as $column)
                  <th>{{ $column }}</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach ($section['rows'] as $row)
                <tr>
                  @php $cells = array_values($row); @endphp
                  @foreach ($section['columns'] as $i => $column)
                    <td>{{ $cells[$i] ?? '' }}</td>
                  @endforeach
                </tr>
              @endforeach
            </tbody>
          </table>
        @endif
      </div>
    @endforeach
  @endif

  <div class="rp-foot">
    <span>Kenya Railways &middot; Crew Management &amp; Running Rooms</span>
    <span>Generated by {{ config('app.name') }} on {{ now()->format('Y-m-d H:i') }}</span>
  </div>

</div>

<script>
  function downloadReport() {
    var html = '<!DOCTYPE html>\n' + document.documentElement.outerHTML;
    var blob = new Blob([html], { type: 'text/html;charset=utf-8' });
    var stamp = new Date().toISOString().slice(0, 10).replace(/-/g, '');
    var slug = '{{ $report->slug() }}';
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'KR-' + slug + '-' + stamp + '.html';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(function () { URL.revokeObjectURL(a.href); }, 1000);
  }
</script>
</body>
</html>
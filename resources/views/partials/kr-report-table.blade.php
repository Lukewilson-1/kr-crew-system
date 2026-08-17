{{-- Report data table partial — KR-themed with progress bar support.
     Usage:
     @include('partials.kr-report-table', [
         'headers' => ['Name', 'Status'],
         'rows' => $rows,
         'empty' => 'No data.',
         'cell' => fn ($row) => '<td>'.$row->name.'</td>'.'<td>'.$row->status.'</td>',
     ])

     Optional: 'progressBars' => [['label' => 'Name', 'value' => 75, 'color' => '#16a34a']]
     --}}

@php
    $columns = count($headers);
@endphp

<div class="kr-report-table">
    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th class="kr-report-th">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $rowKey => $row)
                <tr>{!! $cell($rowKey, $row) !!}</tr>
            @empty
                <tr>
                    <td class="kr-report-empty" colspan="{{ $columns }}">{{ $empty }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (! empty($progressBars) && count($progressBars))
        <div class="kr-progress-section">
            <div class="kr-progress-title">Summary</div>
            @foreach ($progressBars as $bar)
                @php
                    $barValue = $bar['value'] ?? 0;
                    $barColor = $bar['color'] ?? 'var(--kr-maroon)';
                    $barMax = $bar['max'] ?? max(array_column($progressBars, 'value'), 1);
                    $pct = $barMax > 0 ? round(($barValue / $barMax) * 100) : 0;
                @endphp
                <div class="kr-progress-row">
                    <div class="kr-progress-label">{{ $bar['label'] ?? '' }}</div>
                    <div class="kr-progress-track">
                        <div class="kr-progress-fill" style="width: {{ $pct }}%; background: {{ $barColor }};"></div>
                    </div>
                    <div class="kr-progress-value">{{ $barValue }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<style>
    .kr-report-table {
        background: var(--kr-paper-raised);
        border: 1px solid var(--kr-line);
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 24px;
    }

    .kr-report-table table {
        border-collapse: collapse;
        width: 100%;
    }

    .kr-report-table .kr-report-th {
        background: var(--kr-bg);
        color: var(--kr-ink-soft);
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.08em;
        padding: 10px 14px;
        text-align: left;
        text-transform: uppercase;
    }

    .kr-report-table td {
        border-top: 1px solid var(--kr-line);
        color: var(--kr-ink);
        font-size: 13px;
        padding: 10px 14px;
    }

    .kr-report-table tr:hover td {
        background: rgba(0, 0, 0, 0.01);
    }

    .kr-report-table .kr-report-empty {
        color: var(--kr-ink-soft);
        padding: 22px 14px;
        text-align: center;
    }

    /* ── Progress bar section ── */
    .kr-progress-section {
        padding: 14px 14px 10px;
        border-top: 1px solid var(--kr-line);
        background: var(--kr-bg);
    }

    .kr-progress-title {
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--kr-ink-soft);
        margin-bottom: 10px;
        font-family: 'Oswald', sans-serif;
    }

    .kr-progress-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }

    .kr-progress-row:last-child {
        margin-bottom: 0;
    }

    .kr-progress-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--kr-ink);
        min-width: 100px;
        flex-shrink: 0;
    }

    .kr-progress-track {
        flex: 1;
        height: 8px;
        background: var(--kr-line);
        border-radius: 999px;
        overflow: hidden;
    }

    .kr-progress-fill {
        height: 100%;
        border-radius: 999px;
        transition: width 0.5s ease;
    }

    .kr-progress-value {
        font-size: 12px;
        font-weight: 800;
        color: var(--kr-ink);
        min-width: 32px;
        text-align: right;
        font-family: var(--mono, monospace);
    }

    @media print {
        .kr-report-table { border: 1px solid #999; border-radius: 4px; }
        .kr-report-table .kr-report-th { background: #f0f0f0; color: #333; border: 1px solid #999; font-size: 9px; }
        .kr-report-table td { border: 1px solid #ccc; font-size: 10px; padding: 5px 8px; }
        .kr-progress-section { border-top: 1px solid #999; background: #f5f5f5; }
        .kr-progress-track { background: #ddd; }
        .kr-progress-fill { background: #666 !important; print-color-adjust: exact; }
    }
</style>

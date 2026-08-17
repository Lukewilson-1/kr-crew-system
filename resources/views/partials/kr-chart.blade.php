@php
    $chartId = 'apex_' . ($name ?? 'chart') . '_' . substr(md5(json_encode($data)), 0, 8);
    $chartType = $type ?? 'bar';
    $chartData = $data ?? [];
    $chartHeight = $height ?? 260;
    $chartOptions = $options ?? [];

    $hasData = false;
    foreach ($chartData['datasets'] ?? [] as $ds) {
        if (array_sum($ds['data'] ?? []) > 0) {
            $hasData = true;
        }
    }

    $series = [];
    $categories = $chartData['labels'] ?? [];
    foreach ($chartData['datasets'] ?? [] as $ds) {
        $series[] = ['name' => $ds['label'] ?? '', 'data' => $ds['data'] ?? []];
    }
    $colors = $chartData['datasets'][0]['backgroundColor'] ?? ['#6C1A23', '#F14219', '#FEC000', '#16a34a', '#3b82f6'];
    if (! is_array($colors)) $colors = [$colors];

    $apexType = match($chartType) {
        'doughnut' => 'donut',
        'pie' => 'pie',
        'horizontalBar' => 'bar',
        default => $chartType,
    };

    $isHorizontal = ($chartType === 'horizontalBar');
@endphp

<div class="rounded-lg border p-4 mb-5" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
    @if (! empty($title))
        <h3 class="text-sm font-semibold uppercase tracking-wide mb-3" style="font-family:'Oswald',sans-serif;">
            {{ $title }}
        </h3>
    @endif

    @if (! $hasData)
        <p class="text-xs" style="color: var(--kr-ink-soft);">No data to display for this selection.</p>
    @else
        <div wire:key="{{ $chartName ?? $name ?? 'chart' }}-apex" style="position: relative; height: {{ $chartHeight }}px;">
            <div id="{{ $chartId }}" class="no-print" style="width: 100%; height: 100%;"></div>
            <div class="print-only kr-print-chart-data" style="display: none;">
                <table class="w-full text-sm">
                    <thead><tr style="border-bottom: 2px solid var(--kr-line); background: var(--kr-bg);">
                        <th class="px-3 py-1.5 text-left text-[10px] font-bold uppercase" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Category</th>
                        <th class="px-3 py-1.5 text-center text-[10px] font-bold uppercase" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Value</th>
                    </tr></thead>
                    <tbody>
                        @foreach($categories as $i => $cat)
                            <tr style="border-bottom: 1px solid var(--kr-line);">
                                <td class="px-3 py-1.5 text-sm" style="color: var(--kr-ink);">{{ $cat }}</td>
                                <td class="px-3 py-1.5 text-center font-mono text-sm" style="color: var(--kr-ink);">{{ $series[0]['data'][$i] ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if ($hasData)
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;
            var el = document.querySelector('#{{ $chartId }}');
            if (!el) return;

            var opts = {
                chart: {
                    type: '{{ $apexType }}',
                    height: {{ $chartHeight }},
                    toolbar: { show: false },
                    fontFamily: 'Segoe UI, system-ui, sans-serif',
                    @if($isHorizontal)
                    stacked: false,
                    @endif
                },
                series: @js(collect($chartData['datasets'] ?? [])->map(fn($ds) => ['name' => $ds['label'] ?? '', 'data' => $ds['data'] ?? []])->values()->all()),
                colors: @js($colors),
                dataLabels: { enabled: false },
                grid: { borderColor: '#eee' },
                legend: { position: 'bottom', fontSize: '11px', markers: { width: 10, height: 10, radius: 2 } },
                @if(! in_array($apexType, ['donut', 'pie']))
                xaxis: {
                    categories: @js($categories),
                    labels: { style: { fontSize: '10px' } }
                },
                yaxis: {
                    beginAtZero: true,
                    labels: { style: { fontSize: '10px' } }
                },
                plotOptions: {
                    bar: {
                        borderRadius: 3,
                        barPercentage: 0.6,
                        @if($isHorizontal)
                        horizontal: true
                        @endif
                    }
                },
                @endif
                @if(in_array($apexType, ['donut', 'pie']))
                plotOptions: {
                    pie: {
                        donut: { size: '65%' }
                    }
                },
                stroke: { width: 2, colors: ['#fff'] },
                @endif
                tooltip: { shared: true, intersect: false }
            };

            var chart = new ApexCharts(el, opts);
            chart.render();
        });
        </script>
        @endif
    @endif
</div>

<style>
    @media print {
        .kr-print-chart-data { display: block !important; }
        .kr-print-chart-data table { border-collapse: collapse; width: 100%; }
        .kr-print-chart-data th { background: #f0f0f0; color: #333; font-size: 9px; padding: 5px 8px; border: 1px solid #999; }
        .kr-print-chart-data td { font-size: 10px; padding: 5px 8px; border: 1px solid #ccc; }
    }
</style>

<x-filament-panels::page x-data x-on:kr-print-report.window="window.print()">
    @include('partials.report-print-header', ['title' => 'Monthly Position Register'])

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <div class="mb-5 no-print">
        {{ $this->form }}
    </div>

    @php $s = $this->getSummary(); @endphp

    @if (empty($s))
        <div class="rounded-lg border p-8 text-center" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <div class="text-sm" style="color: var(--kr-ink-soft);">Select a room and month above to view the report.</div>
        </div>
    @else
        {{-- ── KPI Cards ── --}}
        <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-6 print-show">
            <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid #16a34a;">
                <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Arrivals</div>
                <div class="text-2xl font-bold" style="color: #16a34a;">{{ $s['arrivals_count'] }}</div>
            </div>
            <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-orange);">
                <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Departures</div>
                <div class="text-2xl font-bold" style="color: var(--kr-orange);">{{ $s['departures_count'] }}</div>
            </div>
            <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-maroon);">
                <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Matters</div>
                <div class="text-2xl font-bold" style="color: var(--kr-maroon);">{{ $s['matters_total'] }}</div>
                @if($s['matters_open'] > 0)
                    <div class="text-[10px] mt-0.5" style="color: var(--kr-orange);">{{ $s['matters_open'] }} open</div>
                @endif
            </div>
            <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-gold);">
                <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Avg Stay</div>
                <div class="text-2xl font-bold" style="color: #8a6d00;">{{ $s['avg_stay_hours'] }}<span class="text-xs font-normal" style="color: var(--kr-ink-soft);">h</span></div>
            </div>
            <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid {{ $s['occupancy_rate'] >= 80 ? '#dc2626' : ($s['occupancy_rate'] >= 50 ? 'var(--kr-orange)' : '#16a34a') }};">
                <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Occupancy</div>
                <div class="text-2xl font-bold" style="color: {{ $s['occupancy_rate'] >= 80 ? '#dc2626' : ($s['occupancy_rate'] >= 50 ? 'var(--kr-orange)' : '#16a34a') }};">{{ $s['occupancy_rate'] }}%</div>
                <div class="text-[10px] mt-0.5" style="color: var(--kr-ink-soft);">{{ $s['beds'] }} beds</div>
            </div>
            <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-ink-soft);">
                <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Peak Day</div>
                <div class="text-lg font-bold" style="color: var(--kr-ink);">{{ $s['peak_day'] }}</div>
            </div>
        </div>

        {{-- ── Charts Row ── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6 no-print">
            {{-- Weekly Trend Bar --}}
            @if(count($s['weekly_breakdown']) > 1)
                <div class="lg:col-span-2 rounded-lg border" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
                    <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                        <div class="w-1 h-5 rounded" style="background: var(--kr-maroon);"></div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Weekly Trend</h3>
                    </div>
                    <div class="p-4">
                        <div id="weeklyTrendChart"></div>
                    </div>
                </div>
            @endif

            {{-- Matters Doughnut --}}
            @if($s['matters_total'] > 0)
                <div class="rounded-lg border" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
                    <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                        <div class="w-1 h-5 rounded" style="background: var(--kr-gold);"></div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Matters Status</h3>
                    </div>
                    <div class="p-4 flex justify-center">
                        <div id="mattersStatusChart"></div>
                    </div>
                </div>
            @endif
        </div>

        {{-- ── Print-only: Weekly Trend data table ── --}}
        @if(count($s['weekly_breakdown']) > 1)
            <div class="rounded-lg border mb-5 print-only" style="display:none;background: var(--kr-paper-raised); border-color: var(--kr-line);">
                <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                    <div class="w-1 h-5 rounded" style="background: var(--kr-maroon);"></div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Weekly Trend</h3>
                </div>
                <div class="p-0">
                    <table class="w-full text-sm">
                        <thead><tr style="border-bottom: 2px solid var(--kr-line); background: var(--kr-bg);">
                            <th class="px-4 py-2 text-left text-[10px] font-bold uppercase" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Week</th>
                            <th class="px-4 py-2 text-center text-[10px] font-bold uppercase" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Arrivals</th>
                            <th class="px-4 py-2 text-center text-[10px] font-bold uppercase" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Departures</th>
                        </tr></thead>
                        <tbody>
                            @foreach($s['weekly_breakdown'] as $week)
                                <tr style="border-bottom: 1px solid var(--kr-line);">
                                    <td class="px-4 py-2 font-medium" style="color: var(--kr-ink);">{{ $week['label'] }}</td>
                                    <td class="px-4 py-2 text-center font-mono"><span class="kr-status-badge kr-status-badge-resolved">{{ $week['arrivals'] }}</span></td>
                                    <td class="px-4 py-2 text-center font-mono"><span class="kr-status-badge kr-status-badge-open">{{ $week['departures'] }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- ── Print-only: Matters Status data table ── --}}
        @if($s['matters_total'] > 0)
            <div class="rounded-lg border mb-5 print-only" style="display:none;background: var(--kr-paper-raised); border-color: var(--kr-line);">
                <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                    <div class="w-1 h-5 rounded" style="background: var(--kr-gold);"></div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Matters Status</h3>
                </div>
                <div class="p-4">
                    <div class="flex flex-wrap gap-3">
                        <div class="flex items-center gap-2 rounded border px-3 py-1.5" style="border-color: var(--kr-line); background: var(--kr-paper);">
                            <span class="text-[11px] font-semibold" style="color: var(--kr-ink);">Open</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background: var(--kr-maroon); color: #fff;">{{ $s['matters_open'] }}</span>
                        </div>
                        <div class="flex items-center gap-2 rounded border px-3 py-1.5" style="border-color: var(--kr-line); background: var(--kr-paper);">
                            <span class="text-[11px] font-semibold" style="color: var(--kr-ink);">Resolved</span>
                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background: var(--kr-maroon); color: #fff;">{{ $s['matters_resolved'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Designation Breakdown ── --}}
        @if($s['designation_breakdown']->count())
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
                <div class="rounded-lg border no-print" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
                    <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                        <div class="w-1 h-5 rounded" style="background: var(--kr-orange);"></div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">By Designation</h3>
                    </div>
                    <div class="p-4">
                        <div id="designationChart"></div>
                    </div>
                </div>

                {{-- Designation chips for print --}}
                <div class="rounded-lg border print-only" style="background: var(--kr-paper-raised); border-color: var(--kr-line); display: none;">
                    <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                        <div class="w-1 h-5 rounded" style="background: var(--kr-orange);"></div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">By Designation</h3>
                    </div>
                    <div class="p-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($s['designation_breakdown'] as $designation => $count)
                                <div class="flex items-center gap-2 rounded border px-3 py-1.5" style="border-color: var(--kr-line); background: var(--kr-paper);">
                                    <span class="text-[11px] font-semibold" style="color: var(--kr-ink);">{{ $designation }}</span>
                                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background: var(--kr-maroon); color: #fff;">{{ $count }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ── Arrivals Table ── --}}
        <div class="rounded-lg border mb-5" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                <div class="w-1 h-5 rounded" style="background: #16a34a;"></div>
                <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">
                    {{ $s['room']->name }} — {{ $s['period_label'] }} — Arrivals
                </h3>
                <span class="ml-auto text-[10px] px-2 py-0.5 rounded-full font-bold" style="background: #e7d9db; color: var(--kr-maroon);">{{ $s['arrivals_count'] }}</span>
            </div>
            <div class="p-0">
                @include('partials.kr-report-table', [
                    'headers' => ['Name', 'Designation', 'Arrived', 'Departed', 'Status'],
                    'rows' => $s['arrivals'],
                    'empty' => 'No arrivals this month.',
                    'cell' => fn ($key, $rec) => '<td>'.e($rec->name).'</td>'
                        .'<td>'.e($rec->designation).'</td>'
                        .'<td class="font-mono text-xs">'.e($rec->arrival_date->format('Y-m-d')).' '.e($rec->arrival_time).'</td>'
                        .'<td class="font-mono text-xs">'.($rec->departure_date?->format('Y-m-d') ?? '—').'</td>'
                        .'<td><span class="kr-status-badge kr-status-badge-'.e($rec->status).'">'.e(ucfirst($rec->status)).'</span></td>',
                ])
            </div>
        </div>

        {{-- ── Departures Table ── --}}
        <div class="rounded-lg border mb-5" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                <div class="w-1 h-5 rounded" style="background: var(--kr-orange);"></div>
                <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">
                    Departures — {{ $s['departures_count'] }}
                </h3>
            </div>
            <div class="p-0">
                @include('partials.kr-report-table', [
                    'headers' => ['Name', 'Designation', 'Departed', 'Arrived', 'Status'],
                    'rows' => $s['departures'],
                    'empty' => 'No departures this month.',
                    'cell' => fn ($key, $rec) => '<td>'.e($rec->name).'</td>'
                        .'<td>'.e($rec->designation).'</td>'
                        .'<td class="font-mono text-xs">'.e($rec->departure_date?->format('Y-m-d')).' '.e($rec->departure_time ?? '').'</td>'
                        .'<td class="font-mono text-xs">'.e($rec->arrival_date->format('Y-m-d')).'</td>'
                        .'<td><span class="kr-status-badge kr-status-badge-'.e($rec->status).'">'.e(ucfirst($rec->status)).'</span></td>',
                ])
            </div>
        </div>

        {{-- ── Matters Table ── --}}
        <div class="rounded-lg border mb-5" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                <div class="w-1 h-5 rounded" style="background: var(--kr-maroon);"></div>
                <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">
                    Matters — {{ $s['matters_open'] }} open / {{ $s['matters_resolved'] }} resolved
                </h3>
            </div>
            <div class="p-0">
                @include('partials.kr-report-table', [
                    'headers' => ['Ticket', 'Category', 'Reported By', 'Date', 'Status', 'Description'],
                    'rows' => $s['matters'],
                    'empty' => 'No matters logged this month.',
                    'cell' => fn ($key, $m) => '<td class="font-mono text-xs">'.e($m->ticket_no ?? '—').'</td>'
                        .'<td>'.e($m->category ?? '—').'</td>'
                        .'<td>'.e($m->reported_by ?? '—').'</td>'
                        .'<td class="font-mono text-xs">'.e($m->date->format('Y-m-d')).'</td>'
                        .'<td><span class="kr-status-badge kr-status-badge-'.e($m->status).'">'.e(ucfirst($m->status)).'</span></td>'
                        .'<td class="kr-report-desc text-xs">'.e(str(strip_tags((string) $m->description ?? ''))->limit(80)).'</td>',
                ])
            </div>
        </div>

<div class="kr-report-desc-hint text-xs" style="color: var(--kr-ink-soft);">
            {{ $s['matters_open'] + $s['matters_resolved'] !== $s['matters_total'] ? 'Some matters have another status and are counted in the totals above.' : '' }}
        </div>
    @endif

    <style>
        @media print {
            .no-print { display: none !important; }
            .print-show { display: block !important; }
            .print-hide { display: none !important; }
            .print-only { display: block !important; }
        }
    </style>

    @if (! empty($s))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;
            var colors = ['#6C1A23', '#F14219', '#FEC000', '#16a34a', '#3b82f6', '#8b5cf6'];

            @if(count($s['weekly_breakdown']) > 1)
            new ApexCharts(document.querySelector('#weeklyTrendChart'), {
                chart: { type: 'bar', height: 240, toolbar: { show: false }, fontFamily: 'Segoe UI, system-ui, sans-serif' },
                series: [
                    { name: 'Arrivals', data: @js(collect($s['weekly_breakdown'])->pluck('arrivals')->toArray()) },
                    { name: 'Departures', data: @js(collect($s['weekly_breakdown'])->pluck('departures')->toArray()) }
                ],
                xaxis: { categories: @js(collect($s['weekly_breakdown'])->pluck('label')->toArray()), labels: { style: { fontSize: '10px' } } },
                yaxis: { beginAtZero: true, labels: { style: { fontSize: '10px' } } },
                colors: ['#16a34a', '#F14219'],
                plotOptions: { bar: { borderRadius: 3, barPercentage: 0.5 } },
                legend: { position: 'bottom', fontSize: '11px', markers: { width: 10, height: 10, radius: 2 } },
                grid: { borderColor: '#eee' },
                dataLabels: { enabled: false },
                tooltip: { shared: true, intersect: false }
            }).render();
            @endif

            @if($s['matters_total'] > 0)
            var mattersLabels = ['Open', 'Resolved'];
            var mattersData = [{{ $s['matters_open'] }}, {{ $s['matters_resolved'] }}];
            @if($s['matters_total'] - $s['matters_open'] - $s['matters_resolved'] > 0)
            mattersLabels.push('Other');
            mattersData.push({{ $s['matters_total'] - $s['matters_open'] - $s['matters_resolved'] }});
            @endif
            new ApexCharts(document.querySelector('#mattersStatusChart'), {
                chart: { type: 'donut', height: 240, fontFamily: 'Segoe UI, system-ui, sans-serif' },
                series: mattersData,
                labels: mattersLabels,
                colors: ['#F14219', '#6C1A23' @if($s['matters_total'] - $s['matters_open'] - $s['matters_resolved'] > 0), '#999' @endif],
                plotOptions: { pie: { donut: { size: '65%' } } },
                legend: { position: 'bottom', fontSize: '11px', markers: { width: 10, height: 10, radius: 2 } },
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: ['#fff'] }
            }).render();
            @endif

            @if($s['designation_breakdown']->count())
            new ApexCharts(document.querySelector('#designationChart'), {
                chart: { type: 'bar', height: 200, toolbar: { show: false }, fontFamily: 'Segoe UI, system-ui, sans-serif' },
                series: [{ name: 'Crew', data: @js($s['designation_breakdown']->values()->toArray()) }],
                xaxis: { categories: @js($s['designation_breakdown']->keys()->toArray()), labels: { style: { fontSize: '11px' } } },
                yaxis: { labels: { style: { fontSize: '10px' } } },
                colors: ['#6C1A23'],
                plotOptions: { bar: { borderRadius: 3, barPercentage: 0.6, horizontal: true } },
                legend: { show: false },
                grid: { borderColor: '#eee' },
                dataLabels: { enabled: false }
            }).render();
            @endif
        });
    </script>
    @endif
</x-filament-panels::page>

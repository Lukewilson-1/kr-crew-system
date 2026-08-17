<x-filament-panels::page x-data x-on:kr-print-report.window="window.print()">
    @include('partials.report-print-header', ['title' => 'Challenges Summary'])

    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

    <div class="mb-5 no-print">
        {{ $this->form }}
    </div>

    @php
        $filtered = $this->getFiltered();
        $kpi = $this->getKpiStats();
        $byRoom = $this->getByRoom();
        $byCategory = $this->getByCategory();
        $ageing = $this->getAgeingBreakdown();
        $weeklyTrend = $this->getWeeklyTrend();
        $ageingTotal = array_sum($ageing);
    @endphp

    {{-- ── KPI Cards ── --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 mb-6">
        <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-ink-soft);">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Total</div>
            <div class="text-2xl font-bold" style="color: var(--kr-ink);">{{ $kpi['total'] }}</div>
        </div>
        <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-orange);">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Open</div>
            <div class="text-2xl font-bold" style="color: var(--kr-orange);">{{ $kpi['open'] }}</div>
        </div>
        <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-maroon);">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Resolved</div>
            <div class="text-2xl font-bold" style="color: var(--kr-maroon);">{{ $kpi['resolved'] }}</div>
        </div>
        <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-gold);">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Resolution Rate</div>
            <div class="text-2xl font-bold" style="color: #8a6d00;">{{ $kpi['resolutionRate'] }}%</div>
        </div>
        <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid #16a34a;">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Avg Resolution</div>
            <div class="text-2xl font-bold" style="color: #16a34a;">{{ $kpi['avgResolutionDays'] }}<span class="text-xs font-normal" style="color: var(--kr-ink-soft);">d</span></div>
        </div>
        <div class="rounded-lg border p-3" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid {{ $kpi['oldestOpenDays'] > 14 ? '#dc2626' : ($kpi['oldestOpenDays'] > 7 ? 'var(--kr-orange)' : 'var(--kr-ink-soft)') }};">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Oldest Open</div>
            <div class="text-2xl font-bold" style="color: {{ $kpi['oldestOpenDays'] > 14 ? '#dc2626' : ($kpi['oldestOpenDays'] > 7 ? 'var(--kr-orange)' : 'var(--kr-ink)') }};">{{ $kpi['oldestOpenDays'] }}<span class="text-xs font-normal" style="color: var(--kr-ink-soft);">d</span></div>
        </div>
    </div>

    {{-- ── Charts Row ── --}}
    @if($kpi['total'] > 0)
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6 no-print">
            {{-- Status Doughnut --}}
            <div class="rounded-lg border" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
                <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                    <div class="w-1 h-5 rounded" style="background: var(--kr-maroon);"></div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Status Overview</h3>
                </div>
                <div class="p-4 flex justify-center">
                    <div id="statusOverviewChart"></div>
                </div>
            </div>

            {{-- Weekly Trend Line --}}
            @if(count($weeklyTrend) > 1)
                <div class="lg:col-span-2 rounded-lg border" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
                    <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                        <div class="w-1 h-5 rounded" style="background: var(--kr-orange);"></div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Weekly Trend</h3>
                    </div>
                    <div class="p-4">
                        <div id="weeklyTrendChart"></div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ── Print-only: Status Overview data ── --}}
    @if($kpi['total'] > 0)
        <div class="rounded-lg border mb-5 print-only" style="display:none;background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                <div class="w-1 h-5 rounded" style="background: var(--kr-maroon);"></div>
                <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Status Overview</h3>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap gap-3">
                    <div class="flex items-center gap-2 rounded border px-3 py-1.5" style="border-color: var(--kr-line); background: var(--kr-paper);">
                        <span class="text-[11px] font-semibold" style="color: var(--kr-ink);">Open</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background: var(--kr-orange); color: #fff;">{{ $kpi['open'] }}</span>
                    </div>
                    <div class="flex items-center gap-2 rounded border px-3 py-1.5" style="border-color: var(--kr-line); background: var(--kr-paper);">
                        <span class="text-[11px] font-semibold" style="color: var(--kr-ink);">Resolved</span>
                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background: var(--kr-maroon); color: #fff;">{{ $kpi['resolved'] }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Print-only: Weekly Trend data table ── --}}
    @if(count($weeklyTrend) > 1)
        <div class="rounded-lg border mb-5 print-only" style="display:none;background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                <div class="w-1 h-5 rounded" style="background: var(--kr-orange);"></div>
                <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Weekly Trend</h3>
            </div>
            <div class="p-0">
                <table class="w-full text-sm">
                    <thead><tr style="border-bottom: 2px solid var(--kr-line); background: var(--kr-bg);">
                        <th class="px-4 py-2 text-left text-[10px] font-bold uppercase" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Week</th>
                        <th class="px-4 py-2 text-center text-[10px] font-bold uppercase" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Filed</th>
                        <th class="px-4 py-2 text-center text-[10px] font-bold uppercase" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Resolved</th>
                    </tr></thead>
                    <tbody>
                        @foreach($weeklyTrend as $week)
                            <tr style="border-bottom: 1px solid var(--kr-line);">
                                <td class="px-4 py-2 font-medium" style="color: var(--kr-ink);">{{ $week['weekLabel'] }}</td>
                                <td class="px-4 py-2 text-center font-mono"><span class="kr-status-badge kr-status-badge-open">{{ $week['created'] }}</span></td>
                                <td class="px-4 py-2 text-center font-mono"><span class="kr-status-badge kr-status-badge-resolved">{{ $week['resolved'] }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ── Ageing Breakdown ── --}}
    @if($kpi['open'] > 0)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            {{-- Ageing Horizontal Bar --}}
            <div class="rounded-lg border no-print" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
                <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                    <div class="w-1 h-5 rounded" style="background: var(--kr-orange);"></div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Open Matters by Age</h3>
                    <span class="ml-auto text-[10px] px-2 py-0.5 rounded-full font-bold" style="background: var(--kr-line); color: var(--kr-ink-soft);">{{ $kpi['open'] }} open</span>
                </div>
                <div class="p-4">
                    <div id="ageingChart"></div>
                </div>
            </div>

            {{-- Stacked bar for print --}}
            <div class="rounded-lg border print-only" style="background: var(--kr-paper-raised); border-color: var(--kr-line); display: none;">
                <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                    <div class="w-1 h-5 rounded" style="background: var(--kr-orange);"></div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Open Matters by Age</h3>
                </div>
                <div class="p-4">
                    @php $ageingColors = ['Under 3 days' => '#16a34a', '3–7 days' => '#FEC000', '7–14 days' => '#F14219', 'Over 14 days' => '#dc2626']; @endphp
                    <div class="h-6 rounded-full overflow-hidden flex mb-3" style="background: var(--kr-line);">
                        @foreach($ageing as $label => $count)
                            @if($count > 0)
                                @php $pct = $ageingTotal > 0 ? ($count / $ageingTotal * 100) : 0; @endphp
                                <div style="width: {{ $pct }}%; background: {{ $ageingColors[$label] }};" title="{{ $label }}: {{ $count }}"></div>
                            @endif
                        @endforeach
                    </div>
                    <div class="flex flex-wrap gap-3">
                        @foreach($ageing as $label => $count)
                            <div class="flex items-center gap-1.5">
                                <div class="w-2.5 h-2.5 rounded-sm" style="background: {{ $ageingColors[$label] }};"></div>
                                <span class="text-[11px] font-medium" style="color: var(--kr-ink);">{{ $label }}</span>
                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded" style="background: var(--kr-line); color: var(--kr-ink-soft);">{{ $count }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- By Category Doughnut --}}
            @if($byCategory->isNotEmpty())
                <div class="rounded-lg border" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
                    <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                        <div class="w-1 h-5 rounded" style="background: var(--kr-gold);"></div>
                        <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">By Category</h3>
                    </div>
                    <div class="p-4 flex justify-center">
                        <div id="categoryChart"></div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- ── By Running Room Table ── --}}
    <div class="rounded-lg border mb-5" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
        <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
            <div class="w-1 h-5 rounded" style="background: var(--kr-maroon);"></div>
            <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">By Running Room</h3>
        </div>
        <div class="p-0">
            @if($byRoom->isEmpty())
                <div class="p-6 text-center text-sm" style="color: var(--kr-ink-soft);">No matters logged in this period.</div>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--kr-line); background: var(--kr-bg);">
                            <th class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Room</th>
                            <th class="px-4 py-2 text-center text-[10px] font-bold uppercase tracking-wider" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Total</th>
                            <th class="px-4 py-2 text-center text-[10px] font-bold uppercase tracking-wider" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Open</th>
                            <th class="px-4 py-2 text-center text-[10px] font-bold uppercase tracking-wider" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Resolved</th>
                            <th class="px-4 py-2 text-left text-[10px] font-bold uppercase tracking-wider min-w-[140px]" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Resolution Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byRoom as $room => $row)
                            <tr style="border-bottom: 1px solid var(--kr-line);" class="hover:bg-black/[.02] dark:hover:bg-white/[.02]">
                                <td class="px-4 py-2 font-medium" style="color: var(--kr-ink);">{{ $room }}</td>
                                <td class="px-4 py-2 text-center font-mono" style="color: var(--kr-ink);">{{ $row['total'] }}</td>
                                <td class="px-4 py-2 text-center"><span class="kr-status-badge kr-status-badge-open">{{ $row['open'] }} open</span></td>
                                <td class="px-4 py-2 text-center"><span class="kr-status-badge kr-status-badge-resolved">{{ $row['resolved'] }} resolved</span></td>
                                <td class="px-4 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 rounded-full overflow-hidden" style="background: var(--kr-line);">
                                            <div class="h-full rounded-full" style="width: {{ $row['rate'] }}%; background: {{ $row['rate'] >= 80 ? '#16a34a' : ($row['rate'] >= 50 ? 'var(--kr-gold)' : 'var(--kr-orange)') }};"></div>
                                        </div>
                                        <span class="text-[11px] font-bold min-w-[32px] text-right" style="color: var(--kr-ink);">{{ $row['rate'] }}%</span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- ── By Category Table ── --}}
    <div class="rounded-lg border mb-5" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
        <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
            <div class="w-1 h-5 rounded" style="background: var(--kr-gold);"></div>
            <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">By Category</h3>
        </div>
        <div class="p-0">
            @if($byCategory->isEmpty())
                <div class="p-6 text-center text-sm" style="color: var(--kr-ink-soft);">No matters logged in this period.</div>
            @else
                @php $maxCat = $byCategory->max(); @endphp
                <div class="p-4 space-y-2">
                    @foreach($byCategory->sortDesc() as $cat => $count)
                        @php $pct = $maxCat > 0 ? ($count / $maxCat * 100) : 0; @endphp
                        <div class="flex items-center gap-3">
                            <div class="text-[11px] font-semibold min-w-[130px]" style="color: var(--kr-ink);">{{ $cat }}</div>
                            <div class="flex-1 h-3 rounded-full overflow-hidden" style="background: var(--kr-line);">
                                <div class="h-full rounded-full transition-all duration-500" style="width: {{ $pct }}%; background: var(--kr-maroon);"></div>
                            </div>
                            <span class="text-[11px] font-bold min-w-[28px] text-right" style="color: var(--kr-ink);">{{ $count }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="no-print">
        <x-filament::button
            icon="heroicon-o-printer"
            onclick="window.print()"
        >
            Print summary
        </x-filament::button>
    </div>
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-show { display: block !important; }
            .print-hide { display: none !important; }
            .print-only { display: block !important; }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof ApexCharts === 'undefined') return;
            var catColors = ['#6C1A23','#F14219','#FEC000','#16a34a','#3b82f6','#8b5cf6','#a855f7'];

            @if($kpi['total'] > 0)
            new ApexCharts(document.querySelector('#statusOverviewChart'), {
                chart: { type: 'donut', height: 240, fontFamily: 'Segoe UI, system-ui, sans-serif' },
                series: [{{ $kpi['open'] }}, {{ $kpi['resolved'] }}],
                labels: ['Open', 'Resolved'],
                colors: ['#F14219', '#6C1A23'],
                plotOptions: { pie: { donut: { size: '65%' } } },
                legend: { position: 'bottom', fontSize: '11px', markers: { width: 10, height: 10, radius: 2 } },
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: ['#fff'] }
            }).render();
            @endif

            @if(count($weeklyTrend) > 1)
            new ApexCharts(document.querySelector('#weeklyTrendChart'), {
                chart: { type: 'line', height: 240, toolbar: { show: false }, fontFamily: 'Segoe UI, system-ui, sans-serif' },
                series: [
                    { name: 'Filed', data: @js(collect($weeklyTrend)->pluck('created')->toArray()) },
                    { name: 'Resolved', data: @js(collect($weeklyTrend)->pluck('resolved')->toArray()) }
                ],
                xaxis: { categories: @js(collect($weeklyTrend)->pluck('weekLabel')->toArray()), labels: { style: { fontSize: '9px' } } },
                yaxis: { beginAtZero: true, labels: { style: { fontSize: '10px' } } },
                colors: ['#F14219', '#6C1A23'],
                stroke: { curve: 'smooth', width: 2 },
                markers: { size: 4 },
                fill: { opacity: 0.1, type: 'solid' },
                legend: { position: 'bottom', fontSize: '11px', markers: { width: 10, height: 10, radius: 2 } },
                grid: { borderColor: '#eee' },
                dataLabels: { enabled: false }
            }).render();
            @endif

            @if($kpi['open'] > 0)
            new ApexCharts(document.querySelector('#ageingChart'), {
                chart: { type: 'bar', height: 200, toolbar: { show: false }, fontFamily: 'Segoe UI, system-ui, sans-serif' },
                series: [{ name: 'Matters', data: @js(array_values($ageing)) }],
                xaxis: { categories: @js(array_keys($ageing)), labels: { style: { fontSize: '10px' } } },
                yaxis: { labels: { style: { fontSize: '10px' } } },
                colors: ['#16a34a'],
                plotOptions: { bar: { borderRadius: 3, barPercentage: 0.6, horizontal: true } },
                legend: { show: false },
                grid: { borderColor: '#eee' },
                dataLabels: { enabled: false }
            }).render();

            @if($byCategory->isNotEmpty())
            new ApexCharts(document.querySelector('#categoryChart'), {
                chart: { type: 'donut', height: 200, fontFamily: 'Segoe UI, system-ui, sans-serif' },
                series: @js($byCategory->sortDesc()->values()->toArray()),
                labels: @js($byCategory->sortDesc()->keys()->toArray()),
                colors: catColors,
                plotOptions: { pie: { donut: { size: '55%' } } },
                legend: { position: 'right', fontSize: '10px', markers: { width: 10, height: 10, radius: 2 }, itemMargin: { vertical: 2 } },
                dataLabels: { enabled: false },
                stroke: { width: 2, colors: ['#fff'] }
            }).render();
            @endif
            @endif
        });
    </script>
</x-filament-panels::page>

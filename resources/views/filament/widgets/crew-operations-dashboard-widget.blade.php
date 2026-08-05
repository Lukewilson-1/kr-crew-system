<x-filament-widgets::widget>
    <x-filament::section>
        <div class="grid gap-4 md:grid-cols-4">
            @foreach ($this->getViewData()['summary'] as $item)
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="text-sm font-medium text-gray-500">{{ $item['label'] }}</div>
                    <div class="mt-2 text-2xl font-semibold text-gray-900">{{ $item['value'] }}</div>
                    <div class="mt-1 text-xs text-gray-500">{{ $item['hint'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-[1.4fr_0.9fr]">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Utilization snapshot</div>
                        <div class="text-sm text-gray-500">Booked days across the current month</div>
                    </div>
                    <div class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700">{{ $this->getViewData()['utilizationPercent'] }}%</div>
                </div>
                <div class="mt-5 overflow-hidden rounded-full bg-gray-100">
                    <div class="h-3 rounded-full bg-emerald-500 transition-all duration-300" style="width: {{ min(100, $this->getViewData()['utilizationPercent']) }}%"></div>
                </div>
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Booked days</div>
                        <div class="mt-3 text-2xl font-semibold text-slate-900">{{ $this->getViewData()['bookedDays'] }}</div>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Standby days</div>
                        <div class="mt-3 text-2xl font-semibold text-slate-900">{{ $this->getViewData()['standbyDays'] }}</div>
                    </div>
                </div>
            </div>

            <div class="grid gap-4">
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm font-semibold text-gray-900">Report activity</div>
                            <div class="text-sm text-gray-500">Access recent builder reports</div>
                        </div>
                        <div class="rounded-full bg-sky-50 px-3 py-1 text-sm font-semibold text-sky-700">{{ $this->getViewData()['reportCount'] }} reports</div>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($this->getViewData()['recentReports'] as $report)
                            <div class="rounded-2xl bg-slate-50 p-3 text-sm text-slate-700">
                                <div class="font-medium text-slate-900">{{ $report['name'] }}</div>
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ $report['type'] }}</div>
                            </div>
                        @empty
                            <div class="rounded-2xl bg-slate-50 p-3 text-sm text-slate-500">No reports created yet. Start with the Builder page.</div>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                    <div class="text-sm font-semibold text-gray-900">Top standby leaders</div>
                    <div class="mt-3 space-y-2">
                        @forelse ($this->getViewData()['standbyLeaders'] as $leader)
                            <div class="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 text-sm">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $leader['name'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $leader['depot'] }}</div>
                                </div>
                                <div class="font-semibold text-amber-600">{{ $leader['standby_days'] }}d</div>
                            </div>
                        @empty
                            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm text-slate-500">No standby leaders yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 lg:grid-cols-[1fr_0.9fr]">
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Depot booking mix</div>
                        <div class="text-sm text-gray-500">Current crew counts, booked and standby spread by depot.</div>
                    </div>
                    <div class="rounded-full bg-slate-50 px-3 py-1 text-sm font-semibold text-slate-700">{{ count($this->getViewData()['depotBreakdown']) }} depots</div>
                </div>
                <div class="mt-4 space-y-4">
                    @foreach ($this->getViewData()['depotBreakdown'] as $item)
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-4 text-sm">
                                <div>
                                    <div class="font-medium text-slate-900">{{ $item['depot'] }}</div>
                                    <div class="text-xs text-slate-500">{{ $item['crew'] }} crew</div>
                                </div>
                                <div class="text-sm text-slate-500">{{ $item['booked'] }} booked · {{ $item['standby'] }} standby</div>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $item['pct'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="text-sm font-semibold text-gray-900">Status composition</div>
                <div class="mt-4 flex gap-4">
                    <div class="relative h-48 w-48 rounded-full bg-slate-50 p-6 shadow-inner">
                        <svg viewBox="0 0 120 120" class="h-full w-full">
                            @php
                                $status = $this->getViewData()['statusCounts'];
                                $total = array_sum($status) ?: 1;
                                $angles = [];
                                $start = 0;
                                $colors = [
                                    'BK' => '#059669',
                                    'SB' => '#2563eb',
                                    'R' => '#7c3aed',
                                    'L' => '#ea580c',
                                    'SK' => '#dc2626',
                                    'NTB' => '#334155',
                                    'TO' => '#0f766e',
                                ];
                            @endphp
                            @foreach ($status as $code => $count)
                                @php
                                    $percent = $count / $total;
                                    $angle = $percent * 360;
                                    $end = $start + $angle;
                                    $largeArc = $angle > 180 ? 1 : 0;
                                    $startX = 60 + 40 * cos(deg2rad($start - 90));
                                    $startY = 60 + 40 * sin(deg2rad($start - 90));
                                    $endX = 60 + 40 * cos(deg2rad($end - 90));
                                    $endY = 60 + 40 * sin(deg2rad($end - 90));
                                @endphp
                                @if ($count > 0)
                                    <path d="M60,20 A40,40 0 {{ $largeArc }},1 {{ $endX }},{{ $endY }} L60,60 Z" fill="{{ $colors[$code] ?? '#94a3b8' }}" opacity="0.9" />
                                @endif
                                @php $start = $end; @endphp
                            @endforeach
                            <circle cx="60" cy="60" r="22" fill="#fff" />
                        </svg>
                        <div class="pointer-events-none absolute inset-0 grid place-items-center text-center">
                            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">Status</div>
                            <div class="text-2xl font-semibold text-slate-900">{{ $total }}</div>
                        </div>
                    </div>
                </div>
                <div class="mt-4 grid gap-2">
                    @foreach ($this->getViewData()['statusCounts'] as $code => $count)
                        @if ($count > 0)
                            <div class="flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3 text-sm">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-3 w-3 rounded-full" style="background: {{ ['BK' => '#059669','SB' => '#2563eb','R' => '#7c3aed','L' => '#ea580c','SK' => '#dc2626','NTB' => '#334155','TO' => '#0f766e'][$code] ?? '#94a3b8' }}"></span>
                                    <span class="font-medium text-slate-900">{{ $code }}</span>
                                </div>
                                <span class="text-slate-500">{{ $count }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

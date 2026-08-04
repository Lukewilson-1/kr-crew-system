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
                <div class="text-sm font-semibold text-gray-900">Depot booking mix</div>
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
                <div class="text-sm font-semibold text-gray-900">Deployment themes</div>
                <div class="mt-4 text-sm text-slate-600">This dashboard highlights crew status, depot trends, and report builder activity in a concise, card-based layout. Use the Builder page to define report exports or summary views that can accelerate admin decisions.</div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

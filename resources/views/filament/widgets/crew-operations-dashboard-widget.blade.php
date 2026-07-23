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

        <div class="mt-6 grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm font-semibold text-gray-900">Utilization snapshot</div>
                        <div class="text-sm text-gray-500">Booked days across the current month</div>
                    </div>
                    <div class="text-3xl font-semibold text-primary-600">{{ $this->getViewData()['utilizationPercent'] }}%</div>
                </div>
                <div class="mt-4 h-3 rounded-full bg-gray-100">
                    <div class="h-3 rounded-full bg-primary-500" style="width: {{ min(100, $this->getViewData()['utilizationPercent']) }}%"></div>
                </div>
                <div class="mt-2 text-sm text-gray-500">{{ $this->getViewData()['bookedDays'] }} booked days and {{ $this->getViewData()['standbyDays'] }} standby days logged this month.</div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="text-sm font-semibold text-gray-900">Top standby leaders</div>
                <div class="mt-3 space-y-2">
                    @forelse ($this->getViewData()['standbyLeaders'] as $leader)
                        <div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2 text-sm">
                            <div>
                                <div class="font-medium text-gray-900">{{ $leader['name'] }}</div>
                                <div class="text-xs text-gray-500">{{ $leader['depot'] }}</div>
                            </div>
                            <div class="font-semibold text-primary-600">{{ $leader['standby_days'] }} days</div>
                        </div>
                    @empty
                        <div class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-500">No standby leaders yet.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-6 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div class="text-sm font-semibold text-gray-900">Depot booking mix</div>
            <div class="mt-3 space-y-3">
                @foreach ($this->getViewData()['depotBreakdown'] as $item)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">{{ $item['depot'] }}</span>
                            <span class="text-gray-500">{{ $item['booked'] }} booked · {{ $item['standby'] }} standby</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100">
                            <div class="h-2 rounded-full bg-emerald-500" style="width: {{ $item['pct'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

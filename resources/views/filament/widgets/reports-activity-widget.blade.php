<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-900">Report activity</div>
                <div class="text-sm text-gray-500">Recent builder reports</div>
            </div>
            <div class="rounded-full bg-sky-50 px-3 py-1 text-sm font-semibold text-sky-700">{{ $reportCount }} reports</div>
        </div>

        <div class="mt-4 space-y-2">
            @forelse ($reports as $report)
                <div class="rounded-2xl bg-slate-50 p-3 text-sm text-slate-700">
                    <div class="font-medium text-slate-900">{{ $report['name'] }}</div>
                    <div class="text-xs uppercase tracking-[0.2em] text-slate-500">{{ $report['type'] }}</div>
                </div>
            @empty
                <div class="rounded-2xl bg-slate-50 p-3 text-sm text-slate-500">No reports created yet.</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

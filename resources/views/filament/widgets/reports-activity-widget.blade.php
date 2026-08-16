<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm font-semibold text-gray-900">Report activity</div>
                <div class="text-sm text-gray-500">Recent builder reports</div>
            </div>
            <div class="rounded-full px-3 py-1 text-sm font-semibold" style="background: #fde3dd; color: #F14219;">{{ $reportCount }} reports</div>
        </div>

        <div class="mt-4 space-y-2">
            @forelse ($reports as $report)
                <div class="rounded-2xl p-3 text-sm" style="background: #f8f6f6; color: #555555;">
                    <div class="font-medium text-gray-900">{{ $report['name'] }}</div>
                    <div class="text-xs uppercase tracking-[0.2em] text-gray-500">{{ $report['type'] }}</div>
                </div>
            @empty
                <div class="rounded-2xl p-3 text-sm" style="background: #f8f6f6; color: #555555;">No reports created yet.</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

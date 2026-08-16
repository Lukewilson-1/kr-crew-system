<x-filament-widgets::widget>
    <x-filament::section>
        <div class="text-sm font-semibold">Recent activity</div>
        <div class="mt-3 space-y-2">
            @forelse ($items as $it)
                <div class="flex items-start gap-3">
                    <div class="h-2 w-2 rounded-full mt-2" style="background: #6C1A23;"></div>
                    <div>
                        <div class="font-medium">{{ $it['label'] }}</div>
                        <div class="text-xs text-gray-500">{{ $it['meta'] }} • {{ $it['time'] }}</div>
                    </div>
                </div>
            @empty
                <div class="text-sm text-gray-500">No recent activity</div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

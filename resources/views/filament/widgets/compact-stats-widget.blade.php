<x-filament-widgets::widget>
    <div class="grid grid-cols-2 gap-2">
        @foreach ($items as $item)
            <div class="rounded-lg bg-white p-3 text-center shadow-sm">
                <div class="text-xs text-gray-500">{{ $item['label'] }}</div>
                <div class="text-lg font-semibold">{{ $item['value'] }}</div>
            </div>
        @endforeach
    </div>
</x-filament-widgets::widget>

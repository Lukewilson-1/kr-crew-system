<x-filament-panels::page>
    @include('partials.report-print-header', ['title' => 'Settings'])

    @php $stats = $this->getRoomStats(); @endphp

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

    {{-- ── Stats Row ── --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
        <div class="rounded-lg border p-3.5" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-maroon);">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Rooms</div>
            <div class="text-2xl font-bold" style="color: var(--kr-maroon);">{{ $stats['total_rooms'] }}</div>
        </div>
        <div class="rounded-lg border p-3.5" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-orange);">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Total Beds</div>
            <div class="text-2xl font-bold" style="color: var(--kr-orange);">{{ $stats['total_beds'] }}</div>
        </div>
        <div class="rounded-lg border p-3.5" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid var(--kr-gold);">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Occupied</div>
            <div class="text-2xl font-bold" style="color: #8a6d00;">{{ $stats['total_occupied'] }}</div>
        </div>
        <div class="rounded-lg border p-3.5" style="background: var(--kr-paper-raised); border-color: var(--kr-line); border-left: 3px solid #16a34a;">
            <div class="text-[10px] font-semibold uppercase tracking-wider mb-1" style="color: var(--kr-ink-soft); font-family:'Oswald',sans-serif;">Vacancy</div>
            <div class="text-2xl font-bold" style="color: #16a34a;">{{ $stats['vacancy_rate'] }}%</div>
        </div>
    </div>

    {{-- ── Charts Row ── --}}
    @php $rooms = $this->getRooms(); @endphp
    @if($rooms->isNotEmpty())
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6 no-print">
            {{-- Occupancy Doughnut --}}
            <div class="rounded-lg border" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
                <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                    <div class="w-1 h-5 rounded" style="background: var(--kr-maroon);"></div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Bed Occupancy</h3>
                </div>
                <div class="p-4 flex justify-center" style="height: 240px;">
                    <canvas x-data x-init="new Chart($el, {
                        type: 'doughnut',
                        data: {
                            labels: ['Occupied', 'Vacant'],
                            datasets: [{
                                data: [{{ $stats['total_occupied'] }}, {{ $stats['total_beds'] - $stats['total_occupied'] }}],
                                backgroundColor: ['#6C1A23', '#e8e0e1'],
                                borderWidth: 0,
                                hoverOffset: 6
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } }
                            }
                        }
                    })"></canvas>
                </div>
            </div>

            {{-- Per-Room Bar Chart --}}
            <div class="lg:col-span-2 rounded-lg border" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
                <div class="px-4 py-2.5 border-b flex items-center gap-2" style="border-color: var(--kr-line);">
                    <div class="w-1 h-5 rounded" style="background: var(--kr-orange);"></div>
                    <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif;">Occupancy by Room</h3>
                </div>
                <div class="p-4" style="height: 240px;">
                    <canvas x-data x-init="new Chart($el, {
                        type: 'bar',
                        data: {
                            labels: @js($rooms->pluck('name')),
                            datasets: [
                                {
                                    label: 'Occupied',
                                    data: @js($rooms->pluck('currentlyIn')->map->count()),
                                    backgroundColor: '#6C1A23',
                                    borderRadius: 3,
                                    barPercentage: 0.6
                                },
                                {
                                    label: 'Available',
                                    data: @js($rooms->map(fn($r) => max(0, $r->beds - $r->currentlyIn->count()))),
                                    backgroundColor: '#e8e0e1',
                                    borderRadius: 3,
                                    barPercentage: 0.6
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: { stacked: true, grid: { display: false }, ticks: { font: { size: 10, family: 'Oswald' } } },
                                y: { stacked: true, beginAtZero: true, grid: { color: '#eee' }, ticks: { font: { size: 10 }, stepSize: 1 } }
                            },
                            plugins: {
                                legend: { position: 'bottom', labels: { padding: 16, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } }
                            }
                        }
                    })"></canvas>
                </div>
            </div>
        </div>
    @endif

    {{-- ── Room Capacity Section ── --}}
    <div class="rounded-lg border mb-6" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
        <div class="px-4 py-3 border-b flex items-center gap-2" style="border-color: var(--kr-line); background: linear-gradient(135deg, var(--kr-paper-raised), var(--kr-bg));">
            <div class="w-1 h-5 rounded" style="background: var(--kr-maroon);"></div>
            <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Room Capacity</h3>
            <span class="ml-auto text-[10px] px-2 py-0.5 rounded-full font-bold" style="background: var(--kr-line); color: var(--kr-ink-soft);">{{ $rooms->count() }} rooms</span>
        </div>
        <div class="p-4">
            @if($rooms->isEmpty())
                <div class="text-center py-8" style="color: var(--kr-ink-soft);">
                    <p class="text-sm">No running rooms configured.</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @foreach($rooms as $room)
                        @php
                            $occupied = $room->currentlyIn->count();
                            $beds = $room->beds;
                            $pct = $beds > 0 ? round(($occupied / $beds) * 100) : 0;
                            $barColor = $pct >= 90 ? '#dc2626' : ($pct >= 60 ? 'var(--kr-orange)' : '#16a34a');
                        @endphp
                        <div x-data="{ beds: {{ $beds }}, saving: false }" class="rounded-lg border p-3" style="border-color: var(--kr-line); background: var(--kr-paper);">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <div class="text-sm font-bold" style="color: var(--kr-ink);">{{ $room->name }}</div>
                                    @if($room->depot_code)
                                        <div class="text-[10px] font-semibold uppercase tracking-wider mt-0.5" style="color: var(--kr-ink-soft);">{{ $room->depot_code }} depot</div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold leading-none" style="color: {{ $barColor }};">{{ $occupied }}<span class="text-xs font-normal" style="color: var(--kr-ink-soft);">/{{ $beds }}</span></div>
                                    <div class="text-[9px] uppercase tracking-wider" style="color: var(--kr-ink-soft);">{{ $pct }}%</div>
                                </div>
                            </div>
                            <div class="h-1.5 rounded-full mb-2.5 overflow-hidden" style="background: var(--kr-line);">
                                <div class="h-full rounded-full transition-all duration-500" style="width: {{ $pct }}%; background: {{ $barColor }};"></div>
                            </div>
                            @if($room->users->count())
                                <div class="flex items-center gap-1 mb-2.5">
                                    @foreach($room->users->take(3) as $user)
                                        <span class="text-[9px] px-1.5 py-0.5 rounded font-semibold" style="background: var(--kr-line); color: var(--kr-ink-soft);">{{ \Illuminate\Support\Str::words($user->name, 1, '') }}</span>
                                    @endforeach
                                    @if($room->users->count() > 3)
                                        <span class="text-[9px]" style="color: var(--kr-ink-soft);">+{{ $room->users->count() - 3 }}</span>
                                    @endif
                                </div>
                            @endif
                            <div class="flex items-center gap-2">
                                <input type="number" min="1" max="99" x-model.number="beds"
                                       class="border rounded px-2 py-1 text-xs font-mono w-16 text-center focus:ring-1 focus:outline-none"
                                       style="border-color: var(--kr-line); background: var(--kr-paper); color: var(--kr-ink);">
                                <button type="button" x-on:click="saving=true; $wire.saveBeds({{ $room->id }}, beds).then(()=>{ saving=false; })"
                                        class="text-[10px] font-semibold uppercase tracking-wide px-3 py-1 border rounded transition-colors"
                                        style="font-family:'Oswald',sans-serif; border-color: var(--kr-maroon); color: var(--kr-maroon);"
                                        :disabled="saving">
                                    <span x-show="!saving">Save</span>
                                    <span x-show="saving" x-cloak>Saving...</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ── Running Room Options ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div x-data="{ items: @js($this->getDesignations()), newItem: '', saving: false }"
             class="rounded-lg border" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <div class="px-4 py-3 border-b flex items-center gap-2" style="border-color: var(--kr-line); background: linear-gradient(135deg, var(--kr-paper-raised), var(--kr-bg));">
                <div class="w-1 h-5 rounded" style="background: var(--kr-orange);"></div>
                <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Designations</h3>
                <span class="ml-auto text-[10px] px-2 py-0.5 rounded-full font-bold" style="background: var(--kr-line); color: var(--kr-ink-soft);" x-text="items.length + ' active'"></span>
            </div>
            <div class="p-4">
                <p class="text-[11px] mb-3" style="color: var(--kr-ink-soft);">Crew designations used across check-in and the crew register.</p>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <template x-for="(item, idx) in items" :key="idx">
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded-full border"
                              style="border-color: var(--kr-orange); color: var(--kr-orange); background: rgba(241,66,25,0.06);">
                            <span x-text="item"></span>
                            <button type="button" x-on:click="items.splice(idx, 1)" class="ml-0.5 opacity-60 hover:opacity-100">&times;</button>
                        </span>
                    </template>
                </div>
                <div class="flex gap-2">
                    <input type="text" x-model="newItem" x-on:keydown.enter.prevent="if(newItem.trim()){ items.push(newItem.trim()); newItem=''; }"
                           class="border rounded px-2 py-1 text-xs flex-1 focus:ring-1 focus:outline-none"
                           style="border-color: var(--kr-line); background: var(--kr-paper); color: var(--kr-ink);" placeholder="Add new designation...">
                    <button type="button" x-on:click="if(newItem.trim()){ items.push(newItem.trim()); newItem=''; }"
                            class="text-[10px] font-semibold uppercase tracking-wide px-3 py-1 border rounded"
                            style="font-family:'Oswald',sans-serif; border-color: var(--kr-orange); color: var(--kr-orange);">Add</button>
                </div>
                <button type="button" x-on:click="saving=true; $wire.saveDesignations(items).then(()=>{ saving=false; })"
                        class="mt-3 w-full text-[11px] font-semibold uppercase tracking-wide py-2 rounded border transition-colors"
                        style="font-family:'Oswald',sans-serif; border-color: var(--kr-maroon); color: var(--kr-maroon);" :disabled="saving">
                    <span x-show="!saving">Save Designations</span>
                    <span x-show="saving" x-cloak>Saving...</span>
                </button>
            </div>
        </div>

        <div x-data="{ items: @js($this->getCategories()), newItem: '', saving: false }"
             class="rounded-lg border" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
            <div class="px-4 py-3 border-b flex items-center gap-2" style="border-color: var(--kr-line); background: linear-gradient(135deg, var(--kr-paper-raised), var(--kr-bg));">
                <div class="w-1 h-5 rounded" style="background: var(--kr-gold);"></div>
                <h3 class="text-sm font-semibold uppercase tracking-wide" style="font-family:'Oswald',sans-serif; color: var(--kr-ink);">Matter Categories</h3>
                <span class="ml-auto text-[10px] px-2 py-0.5 rounded-full font-bold" style="background: var(--kr-line); color: var(--kr-ink-soft);" x-text="items.length + ' active'"></span>
            </div>
            <div class="p-4">
                <p class="text-[11px] mb-3" style="color: var(--kr-ink-soft);">Categories available when logging matters in the running room register.</p>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    <template x-for="(item, idx) in items" :key="idx">
                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-1 rounded-full border"
                              style="border-color: #8a6d00; color: #8a6d00; background: rgba(254,192,0,0.1);">
                            <span x-text="item"></span>
                            <button type="button" x-on:click="items.splice(idx, 1)" class="ml-0.5 opacity-60 hover:opacity-100">&times;</button>
                        </span>
                    </template>
                </div>
                <div class="flex gap-2">
                    <input type="text" x-model="newItem" x-on:keydown.enter.prevent="if(newItem.trim()){ items.push(newItem.trim()); newItem=''; }"
                           class="border rounded px-2 py-1 text-xs flex-1 focus:ring-1 focus:outline-none"
                           style="border-color: var(--kr-line); background: var(--kr-paper); color: var(--kr-ink);" placeholder="Add new category...">
                    <button type="button" x-on:click="if(newItem.trim()){ items.push(newItem.trim()); newItem=''; }"
                            class="text-[10px] font-semibold uppercase tracking-wide px-3 py-1 border rounded"
                            style="font-family:'Oswald',sans-serif; border-color: #8a6d00; color: #8a6d00;">Add</button>
                </div>
                <button type="button" x-on:click="saving=true; $wire.saveCategories(items).then(()=>{ saving=false; })"
                        class="mt-3 w-full text-[11px] font-semibold uppercase tracking-wide py-2 rounded border transition-colors"
                        style="font-family:'Oswald',sans-serif; border-color: var(--kr-maroon); color: var(--kr-maroon);" :disabled="saving">
                    <span x-show="!saving">Save Categories</span>
                    <span x-show="saving" x-cloak>Saving...</span>
                </button>
            </div>
        </div>
    </div>
    </div>
    <style>
        @media print {
            .no-print { display: none !important; }
            .print-show { display: block !important; }
            .print-hide { display: none !important; }
        }
    </style>
</x-filament-panels::page>

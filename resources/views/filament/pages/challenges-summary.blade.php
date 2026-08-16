<x-filament-panels::page>
    @include('partials.report-print-header', ['title' => 'Challenges Summary'])

    <div class="mb-4 no-print">
        {{ $this->form }}
    </div>

    @php
        $filtered = $this->getFiltered();
        $byRoom = $this->getByRoom();
        $byCategory = $this->getByCategory();
    @endphp

    <div class="grid grid-cols-3 gap-px mb-5" style="background: var(--kr-line); border: 1px solid var(--kr-line);">
        <div class="text-center py-2.5 px-3.5" style="background: var(--kr-paper-raised);">
            <div class="text-2xl font-semibold font-mono">{{ $filtered->count() }}</div>
            <div class="text-[10px] uppercase tracking-wider" style="color: var(--kr-ink-soft);">Total Logged</div>
        </div>
        <div class="text-center py-2.5 px-3.5" style="background: var(--kr-paper-raised);">
            <div class="text-2xl font-semibold font-mono">{{ $filtered->where('status', 'open')->count() }}</div>
            <div class="text-[10px] uppercase tracking-wider" style="color: var(--kr-ink-soft);">Open</div>
        </div>
        <div class="text-center py-2.5 px-3.5" style="background: var(--kr-paper-raised);">
            <div class="text-2xl font-semibold font-mono">{{ $filtered->where('status', 'resolved')->count() }}</div>
            <div class="text-[10px] uppercase tracking-wider" style="color: var(--kr-ink-soft);">Resolved</div>
        </div>
    </div>

    <h3 class="text-sm font-semibold uppercase tracking-wide mb-2" style="font-family:'Oswald',sans-serif;">By running room</h3>
    <table class="w-full text-xs mb-6" style="border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f6f6;">
                <th class="text-left p-2 border" style="border-color: var(--kr-line);">Room</th>
                <th class="text-left p-2 border" style="border-color: var(--kr-line);">Total</th>
                <th class="text-left p-2 border" style="border-color: var(--kr-line);">Open</th>
                <th class="text-left p-2 border" style="border-color: var(--kr-line);">Resolved</th>
                <th class="text-left p-2 border" style="border-color: var(--kr-line);">Resolution Rate</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byRoom as $room => $row)
                <tr>
                    <td class="p-2 border" style="border-color: var(--kr-line);">{{ $room }}</td>
                    <td class="p-2 border font-mono" style="border-color: var(--kr-line);">{{ $row['total'] }}</td>
                    <td class="p-2 border font-mono" style="border-color: var(--kr-line);">{{ $row['open'] }}</td>
                    <td class="p-2 border font-mono" style="border-color: var(--kr-line);">{{ $row['resolved'] }}</td>
                    <td class="p-2 border font-mono" style="border-color: var(--kr-line);">{{ $row['rate'] }}%</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-3 text-center" style="color: var(--kr-ink-soft);">No matters logged in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h3 class="text-sm font-semibold uppercase tracking-wide mb-2" style="font-family:'Oswald',sans-serif;">By category</h3>
    <table class="w-full text-xs mb-6" style="border-collapse: collapse;">
        <thead>
            <tr style="background: #f8f6f6;">
                <th class="text-left p-2 border" style="border-color: var(--kr-line);">Category</th>
                <th class="text-left p-2 border" style="border-color: var(--kr-line);">Count</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($byCategory as $cat => $count)
                <tr>
                    <td class="p-2 border" style="border-color: var(--kr-line);">{{ $cat }}</td>
                    <td class="p-2 border font-mono" style="border-color: var(--kr-line);">{{ $count }}</td>
                </tr>
            @empty
                <tr><td colspan="2" class="p-3 text-center" style="color: var(--kr-ink-soft);">No matters logged in this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="no-print">
        <button type="button" onclick="window.print()"
                class="text-xs font-semibold uppercase tracking-wide px-4 py-2 border"
                style="font-family:'Oswald',sans-serif; border-color: var(--kr-ink); color: var(--kr-ink);">
            Print summary
        </button>
    </div>
</x-filament-panels::page>

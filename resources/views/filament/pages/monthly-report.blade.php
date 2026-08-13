<x-filament-panels::page>
    @include('partials.report-print-header', ['title' => 'Monthly Report'])

    <div class="mb-4 no-print">
        {{ $this->form }}
    </div>

    @php $s = $this->getSummary(); @endphp

    @if (empty($s))
        <p class="text-sm" style="color: var(--kr-ink-soft);">Select a room and month above.</p>
    @else
        <div class="grid grid-cols-3 gap-px mb-5" style="background: var(--kr-line); border: 1px solid var(--kr-line);">
            <div class="text-center py-2.5 px-3.5" style="background: var(--kr-paper-raised);">
                <div class="text-2xl font-semibold font-mono">{{ $s['arrivals_count'] }}</div>
                <div class="text-[10px] uppercase tracking-wider" style="color: var(--kr-ink-soft);">Arrivals</div>
            </div>
            <div class="text-center py-2.5 px-3.5" style="background: var(--kr-paper-raised);">
                <div class="text-2xl font-semibold font-mono">{{ $s['departures_count'] }}</div>
                <div class="text-[10px] uppercase tracking-wider" style="color: var(--kr-ink-soft);">Departures</div>
            </div>
            <div class="text-center py-2.5 px-3.5" style="background: var(--kr-paper-raised);">
                <div class="text-2xl font-semibold font-mono">{{ $s['matters_total'] }}</div>
                <div class="text-[10px] uppercase tracking-wider" style="color: var(--kr-ink-soft);">Matters Logged</div>
            </div>
        </div>

        <h3 class="text-sm font-semibold uppercase tracking-wide mb-2" style="font-family:'Oswald',sans-serif;">
            {{ $s['room']->name }} — {{ $s['period_label'] }} — Arrivals
        </h3>
        <table class="w-full text-xs mb-6" style="border-collapse: collapse;">
            <thead>
                <tr style="background: #F7F9FC;">
                    <th class="text-left p-2 border" style="border-color: var(--kr-line);">Name</th>
                    <th class="text-left p-2 border" style="border-color: var(--kr-line);">Designation</th>
                    <th class="text-left p-2 border" style="border-color: var(--kr-line);">Arrived</th>
                    <th class="text-left p-2 border" style="border-color: var(--kr-line);">Departed</th>
                    <th class="text-left p-2 border" style="border-color: var(--kr-line);">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($s['arrivals'] as $rec)
                    <tr>
                        <td class="p-2 border" style="border-color: var(--kr-line);">{{ $rec->name }}</td>
                        <td class="p-2 border" style="border-color: var(--kr-line);">{{ $rec->designation }}</td>
                        <td class="p-2 border font-mono" style="border-color: var(--kr-line);">{{ $rec->arrival_date->format('Y-m-d') }} {{ $rec->arrival_time }}</td>
                        <td class="p-2 border font-mono" style="border-color: var(--kr-line);">{{ $rec->departure_date?->format('Y-m-d') ?? '—' }}</td>
                        <td class="p-2 border" style="border-color: var(--kr-line);">{{ ucfirst($rec->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-3 text-center" style="color: var(--kr-ink-soft);">No arrivals this month.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h3 class="text-sm font-semibold uppercase tracking-wide mb-2" style="font-family:'Oswald',sans-serif;">
            Matters — {{ $s['matters_open'] }} open / {{ $s['matters_resolved'] }} resolved
        </h3>
    @endif
</x-filament-panels::page>

<x-filament-widgets::widget>
    <style>
        .kr-rooms-dashboard {
            color: var(--kr-ink);
            display: grid;
            gap: 20px;
        }

        .kr-rooms-dashboard * {
            box-sizing: border-box;
        }

        .kr-rooms-hero {
            background: var(--kr-maroon);
            border: 1px solid #3a0f15;
            border-radius: 16px;
            color: #fff;
            display: grid;
            gap: 24px;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
            overflow: hidden;
            padding: 28px;
        }

        .kr-rooms-eyebrow {
            color: var(--kr-gold);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .kr-rooms-title {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.15;
            margin: 8px 0 8px;
        }

        .kr-rooms-copy {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            max-width: 680px;
        }

        .kr-rooms-actions {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 22px;
        }

        .kr-rooms-action {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 10px;
            color: #fff;
            display: block;
            padding: 13px 14px;
            text-decoration: none;
        }

        .kr-rooms-action strong {
            display: block;
            font-size: 14px;
        }

        .kr-rooms-action span {
            color: #cbd5e1;
            display: block;
            font-size: 12px;
            margin-top: 3px;
        }

        .kr-rooms-stat-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .kr-rooms-stat {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            min-height: 122px;
            padding: 16px;
        }

        .kr-rooms-stat-label {
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .kr-rooms-stat-value {
            color: #fff;
            font-size: 34px;
            font-weight: 800;
            line-height: 1;
            margin-top: 14px;
        }

        .kr-rooms-stat-hint {
            color: #d8dee9;
            font-size: 13px;
            margin-top: 8px;
        }

        .kr-rooms-grid {
            display: grid;
            gap: 20px;
        }

        .kr-rooms-grid-two {
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
        }

        .kr-rooms-grid-three {
            grid-template-columns: minmax(0, 1fr) minmax(280px, 0.8fr) minmax(300px, 0.9fr);
        }

        .kr-rooms-card {
            background: var(--kr-panel);
            border: 1px solid var(--kr-line);
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            padding: 20px;
        }

        .kr-rooms-card-header {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .kr-rooms-card-title {
            font-size: 17px;
            font-weight: 800;
            margin: 0;
        }

        .kr-rooms-card-subtitle {
            color: var(--kr-muted);
            font-size: 13px;
            line-height: 1.5;
            margin: 4px 0 0;
        }

        .kr-rooms-pill {
            background: #f1f5f9;
            border-radius: 999px;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .kr-rooms-table {
            border: 1px solid var(--kr-line);
            border-radius: 12px;
            overflow: hidden;
        }

        .kr-rooms-table-row {
            align-items: center;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1.2fr) 90px 70px minmax(150px, 1fr);
            padding: 14px 16px;
        }

        .kr-rooms-table-row + .kr-rooms-table-row {
            border-top: 1px solid var(--kr-line);
        }

        .kr-rooms-table-head {
            background: var(--kr-bg);
            color: var(--kr-ink-soft);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .kr-rooms-name {
            font-size: 14px;
            font-weight: 800;
        }

        .kr-rooms-meta {
            color: var(--kr-muted);
            font-size: 12px;
            margin-top: 3px;
        }

        .kr-rooms-bar {
            background: var(--kr-line);
            border-radius: 999px;
            height: 9px;
            overflow: hidden;
            width: 100%;
        }

        .kr-rooms-bar-fill {
            background: var(--kr-maroon);
            border-radius: inherit;
            height: 100%;
        }

        .kr-rooms-list {
            display: grid;
            gap: 10px;
        }

        .kr-rooms-list-item {
            align-items: center;
            background: var(--kr-bg);
            border: 1px solid var(--kr-line);
            border-radius: 10px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px;
        }

        .kr-rooms-badge {
            background: var(--kr-line);
            border-radius: 999px;
            color: var(--kr-ink-soft);
            font-size: 11px;
            font-weight: 800;
            padding: 4px 9px;
            white-space: nowrap;
        }

        .kr-rooms-badge-open {
            background: #fde3dd;
            color: #F14219;
        }

        .kr-rooms-badge-resolved {
            background: #e7d9db;
            color: #6C1A23;
        }

        .kr-rooms-badge-in {
            background: #e7d9db;
            color: #6C1A23;
        }

        .kr-rooms-badge-out {
            background: #f8ecc9;
            color: #8a6d00;
        }

        .kr-rooms-empty {
            background: var(--kr-bg);
            border: 1px dashed var(--kr-line);
            border-radius: 10px;
            color: var(--kr-muted);
            font-size: 14px;
            padding: 18px;
            text-align: center;
        }

        .kr-rooms-link {
            color: var(--kr-maroon);
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
        }

        .kr-rooms-matter-stat {
            background: var(--kr-bg);
            border: 1px solid var(--kr-line);
            border-radius: 10px;
            padding: 14px;
        }

        .kr-rooms-matter-stat span {
            color: var(--kr-muted);
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .kr-rooms-matter-stat strong {
            display: block;
            font-size: 25px;
            line-height: 1;
            margin-top: 10px;
        }

        .kr-rooms-matter-grid {
            display: grid;
            gap: 10px;
            margin-bottom: 18px;
        }

        @media (max-width: 1180px) {
            .kr-rooms-hero,
            .kr-rooms-grid-two,
            .kr-rooms-grid-three {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .kr-rooms-hero {
                padding: 20px;
            }

            .kr-rooms-actions,
            .kr-rooms-stat-grid {
                grid-template-columns: 1fr;
            }

            .kr-rooms-table-head {
                display: none;
            }

            .kr-rooms-table-row {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .kr-rooms-hero { background: #fff !important; border: 2px solid #1A1A2E !important; color: #1A1A2E !important; padding: 16px !important; border-radius: 6px !important; }
            .kr-rooms-eyebrow { color: #B71C1C !important; }
            .kr-rooms-title { font-size: 20px !important; color: #1A1A2E !important; }
            .kr-rooms-copy { color: #555 !important; }
            .kr-rooms-stat { background: #f9f9f9 !important; border: 1px solid #ccc !important; border-radius: 4px !important; }
            .kr-rooms-stat-value { color: #1A1A2E !important; }
            .kr-rooms-stat-hint { color: #666 !important; }
            .kr-rooms-action { background: #f5f5f5 !important; border: 1px solid #ccc !important; color: #333 !important; }
            .kr-rooms-action span { color: #666 !important; }
            .kr-rooms-card { box-shadow: none !important; border: 1px solid #ccc !important; border-radius: 4px !important; break-inside: avoid; }
            .kr-rooms-card-title { color: #1A1A2E !important; }
            .kr-rooms-card-subtitle { color: #666 !important; }
            .kr-rooms-table { border: 1px solid #999 !important; }
            .kr-rooms-table-row { border-color: #ccc !important; }
            .kr-rooms-table-head { background: #f0f0f0 !important; color: #555 !important; }
            .kr-rooms-name { color: #1A1A2E !important; }
            .kr-rooms-meta { color: #666 !important; }
            .kr-rooms-bar { background: #ddd !important; }
            .kr-rooms-bar-fill { background: #999 !important; }
            .kr-rooms-pill { background: #eee !important; color: #555 !important; }
            .kr-rooms-list-item { background: #f9f9f9 !important; border-color: #ccc !important; }
            .kr-rooms-system-card { background: #f9f9f9 !important; border-color: #ccc !important; }
            .kr-rooms-empty { color: #999 !important; border-color: #ccc !important; }
            .kr-rooms-link { color: #B71C1C !important; }
            .kr-rooms-grid-two, .kr-rooms-grid-three { grid-template-columns: 1fr !important; }
        }
    </style>

    <div class="kr-rooms-dashboard">
        <section class="kr-rooms-hero">
            <div>
                <div class="kr-rooms-eyebrow">Running rooms</div>
                <h2 class="kr-rooms-title">Running room operations</h2>
                <p class="kr-rooms-copy">
                    Live bed availability, guest occupancy, and matters arising across all running rooms.
                </p>
                <div class="kr-rooms-actions">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['href'] }}" class="kr-rooms-action">
                            <strong>{{ $action['label'] }}</strong>
                            <span>{{ $action['description'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="kr-rooms-stat-grid">
                @foreach ($heroStats as $stat)
                    <article class="kr-rooms-stat">
                        <div class="kr-rooms-stat-label">{{ $stat['label'] }}</div>
                        <div class="kr-rooms-stat-value">{{ $stat['value'] }}</div>
                        <div class="kr-rooms-stat-hint">{{ $stat['hint'] }}</div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="kr-rooms-grid kr-rooms-grid-two">
            <section class="kr-rooms-card">
                <div class="kr-rooms-card-header">
                    <div>
                        <h3 class="kr-rooms-card-title">Room occupancy</h3>
                        <p class="kr-rooms-card-subtitle">Beds in use, vacant capacity, and open matters per room.</p>
                    </div>
                    <span class="kr-rooms-pill">{{ count($roomRows) }} rooms</span>
                </div>

                <div class="kr-rooms-table">
                    <div class="kr-rooms-table-row kr-rooms-table-head">
                        <div>Room</div>
                        <div>Beds</div>
                        <div>Matters</div>
                        <div>Occupancy</div>
                    </div>
                    @forelse ($roomRows as $room)
                        <div class="kr-rooms-table-row">
                            <div>
                                <div class="kr-rooms-name">{{ $room['name'] }}</div>
                                <div class="kr-rooms-meta">{{ $room['full'] ? 'At capacity' : ($room['vacant'] . ' beds free') }}</div>
                            </div>
                            <div class="kr-rooms-name">{{ $room['occupied'] }} / {{ $room['beds'] }}</div>
                            <div>
                                <span class="kr-rooms-badge {{ $room['open'] ? 'kr-rooms-badge-open' : '' }}">{{ $room['open'] }} open</span>
                            </div>
                            <div>
                                <div class="kr-rooms-meta">{{ $room['percent'] }}% full</div>
                                <div class="kr-rooms-bar">
                                    <div class="kr-rooms-bar-fill" style="width: {{ $room['percent'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="kr-rooms-empty">No running rooms have been configured yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="kr-rooms-card">
                <div class="kr-rooms-card-header">
                    <div>
                        <h3 class="kr-rooms-card-title">Matters arising</h3>
                        <p class="kr-rooms-card-subtitle">Latest issues logged against running rooms.</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\MatterResource::getUrl('index') }}" class="kr-rooms-link">View all</a>
                </div>

                <div class="kr-rooms-matter-grid">
                    @foreach ($matterStats as $stat)
                        <div class="kr-rooms-matter-stat">
                            <span>{{ $stat['label'] }}</span>
                            <strong>{{ $stat['value'] }}</strong>
                        </div>
                    @endforeach
                </div>

                <div class="kr-rooms-list">
                    @forelse ($matterRows as $matter)
                        <div class="kr-rooms-list-item">
                            <div>
                                <div class="kr-rooms-name">{{ $matter['ticket'] }} · {{ $matter['category'] }}</div>
                                <div class="kr-rooms-meta">{{ $matter['room'] }}</div>
                            </div>
                            <span class="kr-rooms-badge kr-rooms-badge-{{ $matter['status'] }}">{{ $matter['status'] }}</span>
                        </div>
                    @empty
                        <div class="kr-rooms-empty">No matters have been logged yet.</div>
                    @endforelse
                </div>
            </section>
        </div>

        <section class="kr-rooms-card">
            <div class="kr-rooms-card-header">
                <div>
                    <h3 class="kr-rooms-card-title">Recent check-ins / check-outs</h3>
                    <p class="kr-rooms-card-subtitle">Latest guest attendance activity.</p>
                </div>
                <a href="{{ \App\Filament\Resources\AttendanceRecordResource::getUrl('index') }}" class="kr-rooms-link">View all</a>
            </div>
            <div class="kr-rooms-list">
                @forelse ($recentCheckins as $guest)
                    <div class="kr-rooms-list-item">
                        <div>
                            <div class="kr-rooms-name">{{ $guest['name'] }}</div>
                            <div class="kr-rooms-meta">{{ $guest['room'] }}@if ($guest['meta']), {{ $guest['meta'] }}@endif</div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="kr-rooms-badge kr-rooms-badge-{{ $guest['status'] }}">{{ $guest['status'] }}</span>
                            <span class="kr-rooms-meta">{{ $guest['time'] }}</span>
                        </div>
                    </div>
                @empty
                    <div class="kr-rooms-empty">No attendance records have been logged yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-widgets::widget>

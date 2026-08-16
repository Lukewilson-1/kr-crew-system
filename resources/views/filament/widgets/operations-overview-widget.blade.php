<x-filament-widgets::widget>
    <style>
        .kr-unified-dashboard {
            --kr-bg: #f6f7f9;
            --kr-panel: #ffffff;
            --kr-ink: #1a1a1a;
            --kr-muted: #555555;
            --kr-line: #d9dee7;
            --kr-maroon: #6C1A23;
            --kr-orange: #F14219;
            --kr-gold: #FEC000;
            color: var(--kr-ink);
            display: grid;
            gap: 20px;
        }

        .kr-unified-dashboard * {
            box-sizing: border-box;
        }

        .kr-unified-hero {
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

        .kr-unified-eyebrow {
            color: var(--kr-gold);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .kr-unified-title {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.15;
            margin: 8px 0 8px;
        }

        .kr-unified-copy {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            max-width: 680px;
        }

        .kr-unified-actions {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 22px;
        }

        .kr-unified-action {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 10px;
            color: #fff;
            display: block;
            padding: 13px 14px;
            text-decoration: none;
        }

        .kr-unified-action strong {
            display: block;
            font-size: 14px;
        }

        .kr-unified-action span {
            color: #cbd5e1;
            display: block;
            font-size: 12px;
            margin-top: 3px;
        }

        .kr-unified-split {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .kr-unified-module {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            padding: 16px;
        }

        .kr-unified-module-title {
            color: #cbd5e1;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            margin-bottom: 12px;
            text-transform: uppercase;
        }

        .kr-unified-stat-grid {
            display: grid;
            gap: 8px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .kr-unified-stat {
            background: rgba(255, 255, 255, 0.07);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            min-height: 74px;
            padding: 12px;
        }

        .kr-unified-stat-label {
            color: #cbd5e1;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .kr-unified-stat-value {
            color: #fff;
            font-size: 26px;
            font-weight: 800;
            line-height: 1;
            margin-top: 10px;
        }

        .kr-unified-stat-hint {
            color: #d8dee9;
            font-size: 12px;
            margin-top: 6px;
        }

        .kr-unified-grid {
            display: grid;
            gap: 20px;
        }

        .kr-unified-grid-two {
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
        }

        .kr-unified-grid-three {
            grid-template-columns: minmax(0, 1.1fr) minmax(280px, 0.9fr) minmax(300px, 1fr);
        }

        .kr-unified-card {
            background: var(--kr-panel);
            border: 1px solid var(--kr-line);
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            padding: 20px;
        }

        .kr-unified-card-header {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .kr-unified-card-title {
            font-size: 17px;
            font-weight: 800;
            margin: 0;
        }

        .kr-unified-card-subtitle {
            color: var(--kr-muted);
            font-size: 13px;
            line-height: 1.5;
            margin: 4px 0 0;
        }

        .kr-unified-pill {
            background: #f1f5f9;
            border-radius: 999px;
            color: #475569;
            font-size: 12px;
            font-weight: 800;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .kr-unified-table {
            border: 1px solid var(--kr-line);
            border-radius: 12px;
            overflow: hidden;
        }

        .kr-unified-table-row {
            align-items: center;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1.2fr) 90px minmax(150px, 1fr);
            padding: 14px 16px;
        }

        .kr-unified-table-row + .kr-unified-table-row {
            border-top: 1px solid #edf0f5;
        }

        .kr-unified-table-head {
            background: #f8fafc;
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .kr-unified-name {
            font-size: 14px;
            font-weight: 800;
        }

        .kr-unified-meta {
            color: var(--kr-muted);
            font-size: 12px;
            margin-top: 3px;
        }

        .kr-unified-bar {
            background: #e8edf4;
            border-radius: 999px;
            height: 9px;
            overflow: hidden;
            width: 100%;
        }

        .kr-unified-bar-fill {
            background: var(--kr-maroon);
            border-radius: inherit;
            height: 100%;
        }

        .kr-unified-list {
            display: grid;
            gap: 10px;
        }

        .kr-unified-list-item {
            align-items: center;
            background: #f8fafc;
            border: 1px solid #edf0f5;
            border-radius: 10px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px;
        }

        .kr-unified-system {
            display: grid;
            gap: 10px;
        }

        .kr-unified-system-card {
            background: #f8fafc;
            border: 1px solid #edf0f5;
            border-radius: 10px;
            padding: 14px;
        }

        .kr-unified-system-card span {
            color: var(--kr-muted);
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .kr-unified-system-card strong {
            display: block;
            font-size: 25px;
            line-height: 1;
            margin-top: 10px;
        }

        .kr-unified-empty {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            color: var(--kr-muted);
            font-size: 14px;
            padding: 18px;
            text-align: center;
        }

        .kr-unified-link {
            color: var(--kr-maroon);
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
        }

        @media (max-width: 1180px) {
            .kr-unified-hero,
            .kr-unified-grid-two,
            .kr-unified-grid-three {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .kr-unified-hero {
                padding: 20px;
            }

            .kr-unified-actions,
            .kr-unified-split,
            .kr-unified-stat-grid {
                grid-template-columns: 1fr;
            }

            .kr-unified-table-head {
                display: none;
            }

            .kr-unified-table-row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="kr-unified-dashboard">
        <section class="kr-unified-hero">
            <div>
                <div class="kr-unified-eyebrow">Kenya Railways operations</div>
                <h2 class="kr-unified-title">Operations overview</h2>
                <p class="kr-unified-copy">
                    Live snapshot of crew readiness and running room availability for {{ $periodLabel }}.
                </p>
                <div class="kr-unified-actions">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['href'] }}" class="kr-unified-action">
                            <strong>{{ $action['label'] }}</strong>
                            <span>{{ $action['description'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="kr-unified-split">
                <div class="kr-unified-module">
                    <div class="kr-unified-module-title">Crew</div>
                    <div class="kr-unified-stat-grid">
                        @foreach ($crewStats as $stat)
                            <div class="kr-unified-stat">
                                <div class="kr-unified-stat-label">{{ $stat['label'] }}</div>
                                <div class="kr-unified-stat-value">{{ $stat['value'] }}</div>
                                <div class="kr-unified-stat-hint">{{ $stat['hint'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="kr-unified-module">
                    <div class="kr-unified-module-title">Running rooms</div>
                    <div class="kr-unified-stat-grid">
                        @foreach ($roomStats as $stat)
                            <div class="kr-unified-stat">
                                <div class="kr-unified-stat-label">{{ $stat['label'] }}</div>
                                <div class="kr-unified-stat-value">{{ $stat['value'] }}</div>
                                <div class="kr-unified-stat-hint">{{ $stat['hint'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        <div class="kr-unified-grid kr-unified-grid-two">
            <section class="kr-unified-card">
                <div class="kr-unified-card-header">
                    <div>
                        <h3 class="kr-unified-card-title">Room occupancy</h3>
                        <p class="kr-unified-card-subtitle">Largest running rooms by beds in use.</p>
                    </div>
                    <span class="kr-unified-pill">{{ count($roomRows) }} shown</span>
                </div>

                <div class="kr-unified-table">
                    <div class="kr-unified-table-row kr-unified-table-head">
                        <div>Room</div>
                        <div>Beds</div>
                        <div>Occupancy</div>
                    </div>
                    @forelse ($roomRows as $room)
                        <div class="kr-unified-table-row">
                            <div>
                                <div class="kr-unified-name">{{ $room['name'] }}</div>
                            </div>
                            <div class="kr-unified-name">{{ $room['occupied'] }} / {{ $room['beds'] }}</div>
                            <div>
                                <div class="kr-unified-meta">{{ $room['vacant'] }} vacant / {{ $room['percent'] }}%</div>
                                <div class="kr-unified-bar">
                                    <div class="kr-unified-bar-fill" style="width: {{ $room['percent'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="kr-unified-empty">No running rooms have been configured yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="kr-unified-card">
                <div class="kr-unified-card-header">
                    <div>
                        <h3 class="kr-unified-card-title">System setup</h3>
                        <p class="kr-unified-card-subtitle">Configured operational data.</p>
                    </div>
                </div>
                <div class="kr-unified-system">
                    @foreach ($systemCards as $card)
                        <div class="kr-unified-system-card">
                            <span>{{ $card['label'] }}</span>
                            <strong>{{ $card['value'] }}</strong>
                            <div class="kr-unified-meta">{{ $card['meta'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <div class="kr-unified-grid kr-unified-grid-two">
            <section class="kr-unified-card">
                <div class="kr-unified-card-header">
                    <div>
                        <h3 class="kr-unified-card-title">Recent crew records</h3>
                        <p class="kr-unified-card-subtitle">Latest profiles added to the roster.</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\CrewMemberResource::getUrl('index') }}" class="kr-unified-link">View all</a>
                </div>
                <div class="kr-unified-list">
                    @forelse ($recentCrew as $crew)
                        <div class="kr-unified-list-item">
                            <div>
                                <div class="kr-unified-name">{{ $crew['name'] }}</div>
                                <div class="kr-unified-meta">{{ $crew['meta'] }}</div>
                            </div>
                            <div class="kr-unified-meta">{{ $crew['time'] }}</div>
                        </div>
                    @empty
                        <div class="kr-unified-empty">No crew records have been created yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="kr-unified-card">
                <div class="kr-unified-card-header">
                    <div>
                        <h3 class="kr-unified-card-title">Recent check-ins</h3>
                        <p class="kr-unified-card-subtitle">Latest guests booked into running rooms.</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\AttendanceRecordResource::getUrl('index') }}" class="kr-unified-link">View all</a>
                </div>
                <div class="kr-unified-list">
                    @forelse ($recentCheckins as $guest)
                        <div class="kr-unified-list-item">
                            <div>
                                <div class="kr-unified-name">{{ $guest['name'] }}</div>
                                <div class="kr-unified-meta">{{ $guest['meta'] }}</div>
                            </div>
                            <div class="kr-unified-meta">{{ $guest['time'] }}</div>
                        </div>
                    @empty
                        <div class="kr-unified-empty">No check-ins have been logged yet.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-filament-widgets::widget>

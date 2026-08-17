<x-filament-widgets::widget>
    <style>
        .kr-admin-dashboard {
            color: var(--kr-ink);
            display: grid;
            gap: 20px;
        }

        .kr-admin-dashboard * {
            box-sizing: border-box;
        }

        .kr-admin-hero {
            background: var(--kr-rail);
            border: 1px solid #3a0f15;
            border-radius: 16px;
            color: #fff;
            display: grid;
            gap: 24px;
            grid-template-columns: minmax(0, 1.15fr) minmax(320px, 0.85fr);
            overflow: hidden;
            padding: 28px;
        }

        .kr-admin-eyebrow {
            color: var(--kr-gold);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .kr-admin-title {
            font-size: 30px;
            font-weight: 800;
            line-height: 1.15;
            margin: 8px 0 8px;
        }

        .kr-admin-copy {
            color: #cbd5e1;
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
            max-width: 680px;
        }

        .kr-admin-actions {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 22px;
        }

        .kr-admin-action {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 10px;
            color: #fff;
            display: block;
            padding: 13px 14px;
            text-decoration: none;
        }

        .kr-admin-action strong {
            display: block;
            font-size: 14px;
        }

        .kr-admin-action span {
            color: #cbd5e1;
            display: block;
            font-size: 12px;
            margin-top: 3px;
        }

        .kr-admin-stat-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .kr-admin-stat {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 12px;
            min-height: 122px;
            padding: 16px;
        }

        .kr-admin-stat-label {
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .kr-admin-stat-value {
            color: #fff;
            font-size: 34px;
            font-weight: 800;
            line-height: 1;
            margin-top: 14px;
        }

        .kr-admin-stat-hint {
            color: #d8dee9;
            font-size: 13px;
            margin-top: 8px;
        }

        .kr-admin-grid {
            display: grid;
            gap: 20px;
        }

        .kr-admin-grid-two {
            grid-template-columns: minmax(0, 1.2fr) minmax(320px, 0.8fr);
        }

        .kr-admin-grid-three {
            grid-template-columns: minmax(0, 1.25fr) minmax(260px, 0.75fr) minmax(280px, 0.85fr);
        }

        .kr-admin-card {
            background: var(--kr-panel);
            border: 1px solid var(--kr-line);
            border-radius: 14px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            padding: 20px;
        }

        .kr-admin-card-header {
            align-items: flex-start;
            display: flex;
            gap: 16px;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .kr-admin-card-title {
            font-size: 17px;
            font-weight: 800;
            margin: 0;
        }

        .kr-admin-card-subtitle {
            color: var(--kr-muted);
            font-size: 13px;
            line-height: 1.5;
            margin: 4px 0 0;
        }

        .kr-admin-pill {
            background: var(--kr-line);
            border-radius: 999px;
            color: var(--kr-ink-soft);
            font-size: 12px;
            font-weight: 800;
            padding: 6px 10px;
            white-space: nowrap;
        }

        .kr-admin-table {
            border: 1px solid var(--kr-line);
            border-radius: 12px;
            overflow: hidden;
        }

        .kr-admin-table-row {
            align-items: center;
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1.2fr) 90px minmax(150px, 1fr);
            padding: 14px 16px;
        }

        .kr-admin-table-row + .kr-admin-table-row {
            border-top: 1px solid var(--kr-line);
        }

        .kr-admin-table-head {
            background: var(--kr-bg);
            color: var(--kr-ink-soft);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .kr-admin-name {
            font-size: 14px;
            font-weight: 800;
        }

        .kr-admin-meta {
            color: var(--kr-muted);
            font-size: 12px;
            margin-top: 3px;
        }

        .kr-admin-bar {
            background: var(--kr-line);
            border-radius: 999px;
            height: 9px;
            overflow: hidden;
            width: 100%;
        }

        .kr-admin-bar-fill {
            background: var(--kr-maroon);
            border-radius: inherit;
            height: 100%;
        }

        .kr-admin-status-layout {
            align-items: center;
            display: grid;
            gap: 18px;
            grid-template-columns: 180px minmax(0, 1fr);
        }

        .kr-admin-stacked {
            display: grid;
            gap: 12px;
            text-align: center;
        }

        .kr-admin-stacked-bar {
            border: 1px solid var(--kr-line);
            border-radius: 999px;
            display: flex;
            height: 14px;
            overflow: hidden;
        }

        .kr-admin-stacked-seg {
            height: 100%;
        }

        .kr-admin-stacked-total strong {
            display: block;
            font-size: 28px;
            line-height: 1;
        }

        .kr-admin-stacked-total span {
            color: var(--kr-muted);
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            margin-top: 5px;
            text-transform: uppercase;
        }

        .kr-admin-status-list {
            display: grid;
            gap: 12px;
        }

        .kr-admin-status-row {
            display: grid;
            gap: 10px;
        }

        .kr-admin-status-top {
            align-items: center;
            display: flex;
            justify-content: space-between;
        }

        .kr-admin-status-label {
            align-items: center;
            display: flex;
            gap: 9px;
            min-width: 0;
        }

        .kr-admin-dot {
            border-radius: 999px;
            height: 10px;
            width: 10px;
        }

        .kr-admin-list {
            display: grid;
            gap: 10px;
        }

        .kr-admin-list-item {
            align-items: center;
            background: var(--kr-bg);
            border: 1px solid var(--kr-line);
            border-radius: 10px;
            display: flex;
            gap: 12px;
            justify-content: space-between;
            padding: 12px;
        }

        .kr-admin-system {
            display: grid;
            gap: 10px;
        }

        .kr-admin-system-card {
            background: var(--kr-bg);
            border: 1px solid var(--kr-line);
            border-radius: 10px;
            padding: 14px;
        }

        .kr-admin-system-card span {
            color: var(--kr-muted);
            display: block;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .kr-admin-system-card strong {
            display: block;
            font-size: 25px;
            line-height: 1;
            margin-top: 10px;
        }

        .kr-admin-report-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .kr-admin-report {
            background: var(--kr-bg);
            border: 1px solid var(--kr-line);
            border-radius: 12px;
            min-height: 120px;
            padding: 15px;
        }

        .kr-admin-empty {
            background: var(--kr-bg);
            border: 1px dashed var(--kr-line);
            border-radius: 10px;
            color: var(--kr-muted);
            font-size: 14px;
            padding: 18px;
            text-align: center;
        }

        .kr-admin-link {
            color: var(--kr-maroon);
            font-size: 13px;
            font-weight: 800;
            text-decoration: none;
        }

        @media (max-width: 1180px) {
            .kr-admin-hero,
            .kr-admin-grid-two,
            .kr-admin-grid-three {
                grid-template-columns: 1fr;
            }

            .kr-admin-report-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 720px) {
            .kr-admin-hero {
                padding: 20px;
            }

            .kr-admin-actions,
            .kr-admin-stat-grid,
            .kr-admin-report-grid,
            .kr-admin-status-layout {
                grid-template-columns: 1fr;
            }

            .kr-admin-table-head {
                display: none;
            }

            .kr-admin-table-row {
                grid-template-columns: 1fr;
            }
        }

        @media print {
            .kr-admin-hero {
                background: #fff !important;
                border: 2px solid #1A1A2E !important;
                color: #1A1A2E !important;
                padding: 16px !important;
                border-radius: 6px !important;
            }
            .kr-admin-eyebrow { color: #B71C1C !important; }
            .kr-admin-title { font-size: 20px !important; color: #1A1A2E !important; }
            .kr-admin-copy { color: #555 !important; }
            .kr-admin-stat { background: #f9f9f9 !important; border: 1px solid #ccc !important; border-radius: 4px !important; }
            .kr-admin-stat-value { color: #1A1A2E !important; }
            .kr-admin-stat-hint { color: #666 !important; }
            .kr-admin-action { background: #f5f5f5 !important; border: 1px solid #ccc !important; color: #333 !important; }
            .kr-admin-action span { color: #666 !important; }
            .kr-admin-card { box-shadow: none !important; border: 1px solid #ccc !important; border-radius: 4px !important; break-inside: avoid; }
            .kr-admin-card-title { color: #1A1A2E !important; }
            .kr-admin-card-subtitle { color: #666 !important; }
            .kr-admin-table { border: 1px solid #999 !important; }
            .kr-admin-table-row { border-color: #ccc !important; }
            .kr-admin-table-head { background: #f0f0f0 !important; color: #555 !important; }
            .kr-admin-name { color: #1A1A2E !important; }
            .kr-admin-meta { color: #666 !important; }
            .kr-admin-bar { background: #ddd !important; }
            .kr-admin-bar-fill { background: #999 !important; }
            .kr-admin-stacked-bar { border-color: #999 !important; }
            .kr-admin-stacked-seg { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
            .kr-admin-stacked-total strong { color: #1A1A2E !important; }
            .kr-admin-pill { background: #eee !important; color: #555 !important; }
            .kr-admin-list-item { background: #f9f9f9 !important; border-color: #ccc !important; }
            .kr-admin-system-card { background: #f9f9f9 !important; border-color: #ccc !important; }
            .kr-admin-report { background: #f9f9f9 !important; border-color: #ccc !important; border-radius: 4px !important; }
            .kr-admin-report-grid { grid-template-columns: repeat(2, 1fr) !important; }
            .kr-admin-link { color: #B71C1C !important; }
            .kr-admin-empty { color: #999 !important; border-color: #ccc !important; }
            .kr-admin-grid-two, .kr-admin-grid-three { grid-template-columns: 1fr !important; }
        }
    </style>

    @php
        $statusTotal = array_sum(array_column($statusRows, 'count'));
    @endphp

    <div class="kr-admin-dashboard">
        <section class="kr-admin-hero">
            <div>
                <div class="kr-admin-eyebrow">Admin operations</div>
                <h2 class="kr-admin-title">Crew control dashboard</h2>
                <p class="kr-admin-copy">
                    Monitor roster strength, depot readiness, status mix, and reporting setup for {{ $periodLabel }}.
                </p>
                <div class="kr-admin-actions">
                    @foreach ($quickActions as $action)
                        <a href="{{ $action['href'] }}" class="kr-admin-action">
                            <strong>{{ $action['label'] }}</strong>
                            <span>{{ $action['description'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="kr-admin-stat-grid">
                @foreach ($heroStats as $stat)
                    <article class="kr-admin-stat">
                        <div class="kr-admin-stat-label">{{ $stat['label'] }}</div>
                        <div class="kr-admin-stat-value">{{ $stat['value'] }}</div>
                        <div class="kr-admin-stat-hint">{{ $stat['hint'] }}</div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="kr-admin-grid kr-admin-grid-two">
            <section class="kr-admin-card">
                <div class="kr-admin-card-header">
                    <div>
                        <h3 class="kr-admin-card-title">Depot readiness</h3>
                        <p class="kr-admin-card-subtitle">Largest depots by roster size and active profile coverage.</p>
                    </div>
                    <span class="kr-admin-pill">{{ count($depotRows) }} shown</span>
                </div>

                <div class="kr-admin-table">
                    <div class="kr-admin-table-row kr-admin-table-head">
                        <div>Depot</div>
                        <div>Crew</div>
                        <div>Active coverage</div>
                    </div>
                    @forelse ($depotRows as $depot)
                        <div class="kr-admin-table-row">
                            <div>
                                <div class="kr-admin-name">{{ $depot['name'] }}</div>
                                <div class="kr-admin-meta">{{ $depot['code'] }}</div>
                            </div>
                            <div class="kr-admin-name">{{ $depot['crew'] }}</div>
                            <div>
                                <div class="kr-admin-meta">{{ $depot['active'] }} active / {{ $depot['percent'] }}%</div>
                                <div class="kr-admin-bar">
                                    <div class="kr-admin-bar-fill" style="width: {{ $depot['percent'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="kr-admin-empty">No depot data is available yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="kr-admin-card">
                <div class="kr-admin-card-header">
                    <div>
                        <h3 class="kr-admin-card-title">Status composition</h3>
                        <p class="kr-admin-card-subtitle">Current status entries grouped by code.</p>
                    </div>
                    <span class="kr-admin-pill">{{ $statusTotal }} entries</span>
                </div>

                <div class="kr-admin-status-layout">
                    <div class="kr-admin-stacked">
                        <div class="kr-admin-stacked-bar">
                            @forelse ($statusRows as $status)
                                <div class="kr-admin-stacked-seg" style="background: {{ $status['color'] }}; width: {{ $status['percent'] }}%"></div>
                            @empty
                                <div class="kr-admin-stacked-seg" style="background: var(--kr-line); width: 100%"></div>
                            @endforelse
                        </div>
                        <div class="kr-admin-stacked-total">
                            <strong>{{ $statusTotal }}</strong>
                            <span>Status</span>
                        </div>
                    </div>
                    <div class="kr-admin-status-list">
                        @forelse ($statusRows as $status)
                            <div class="kr-admin-status-row">
                                <div class="kr-admin-status-top">
                                    <div class="kr-admin-status-label">
                                        <span class="kr-admin-dot" style="background: {{ $status['color'] }}"></span>
                                        <strong>{{ $status['label'] }}</strong>
                                    </div>
                                    <span>{{ $status['count'] }}</span>
                                </div>
                                <div class="kr-admin-bar">
                                    <div class="kr-admin-bar-fill" style="background: {{ $status['color'] }}; width: {{ $status['percent'] }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="kr-admin-empty">No status activity has been recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>

        <div class="kr-admin-grid kr-admin-grid-three">
            <section class="kr-admin-card">
                <div class="kr-admin-card-header">
                    <div>
                        <h3 class="kr-admin-card-title">Recent crew records</h3>
                        <p class="kr-admin-card-subtitle">Latest profiles added to the roster.</p>
                    </div>
                    <a href="{{ \App\Filament\Resources\CrewMemberResource::getUrl('index') }}" class="kr-admin-link">View all</a>
                </div>
                <div class="kr-admin-list">
                    @forelse ($recentCrew as $crew)
                        <div class="kr-admin-list-item">
                            <div>
                                <div class="kr-admin-name">{{ $crew['name'] }}</div>
                                <div class="kr-admin-meta">{{ $crew['meta'] }}</div>
                            </div>
                            <div class="kr-admin-meta">{{ $crew['time'] }}</div>
                        </div>
                    @empty
                        <div class="kr-admin-empty">No crew records have been created yet.</div>
                    @endforelse
                </div>
            </section>

            <section class="kr-admin-card">
                <div class="kr-admin-card-header">
                    <div>
                        <h3 class="kr-admin-card-title">System setup</h3>
                        <p class="kr-admin-card-subtitle">Configured operational data.</p>
                    </div>
                </div>
                <div class="kr-admin-system">
                    @foreach ($systemCards as $card)
                        <div class="kr-admin-system-card">
                            <span>{{ $card['label'] }}</span>
                            <strong>{{ $card['value'] }}</strong>
                            <div class="kr-admin-meta">{{ $card['meta'] }}</div>
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="kr-admin-card">
                <div class="kr-admin-card-header">
                    <div>
                        <h3 class="kr-admin-card-title">Operational balance</h3>
                        <p class="kr-admin-card-subtitle">Quick visual scan of availability and configured locations.</p>
                    </div>
                </div>
                <div class="kr-admin-list">
                    @foreach ($heroStats as $stat)
                        <div class="kr-admin-list-item">
                            <div>
                                <div class="kr-admin-name">{{ $stat['label'] }}</div>
                                <div class="kr-admin-meta">{{ $stat['hint'] }}</div>
                            </div>
                            <strong>{{ $stat['value'] }}</strong>
                        </div>
                    @endforeach
                </div>
            </section>
        </div>

        <section class="kr-admin-card">
            <div class="kr-admin-card-header">
                <div>
                    <h3 class="kr-admin-card-title">Report activity</h3>
                    <p class="kr-admin-card-subtitle">Recent report definitions available to administrators.</p>
                </div>
                <a href="{{ \App\Filament\Resources\ReportResource::getUrl('index') }}" class="kr-admin-link">Manage reports</a>
            </div>
            <div class="kr-admin-report-grid">
                @forelse ($recentReports as $report)
                    <article class="kr-admin-report">
                        <div class="kr-admin-meta">{{ $report['category'] }}</div>
                        <div class="kr-admin-name" style="margin-top: 10px;">{{ $report['name'] }}</div>
                        <span class="kr-admin-pill" style="display: inline-block; margin-top: 16px;">{{ $report['type'] }}</span>
                    </article>
                @empty
                    <div class="kr-admin-empty">No report definitions have been created yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-widgets::widget>

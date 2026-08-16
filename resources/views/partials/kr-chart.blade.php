@php
    $chartName = $name ?? 'chart';
    $chartType = $type ?? 'bar';
    $chartData = $data ?? [];
    $chartOptions = array_replace_recursive([
        'responsive' => true,
        'maintainAspectRatio' => false,
        'plugins' => [
            'legend' => ['position' => 'bottom'],
        ],
    ], $options ?? []);
    $chartHeight = $height ?? 260;

    $hasData = false;
    foreach ($chartData['datasets'] ?? [] as $ds) {
        if (array_sum($ds['data'] ?? []) > 0) {
            $hasData = true;
        }
    }
@endphp

<div class="rounded-lg border p-4 mb-5" style="background: var(--kr-paper-raised); border-color: var(--kr-line);">
    @if (! empty($title))
        <h3 class="text-sm font-semibold uppercase tracking-wide mb-3" style="font-family:'Oswald',sans-serif;">
            {{ $title }}
        </h3>
    @endif

    @if (! $hasData)
        <p class="text-xs" style="color: var(--kr-ink-soft);">No data to display for this selection.</p>
    @else
        <div wire:key="{{ $chartName }}-{{ md5(json_encode($chartData)) }}" style="position: relative; height: {{ $chartHeight }}px;">
            <div
                x-load
                x-load-src="{{ \Filament\Support\Facades\FilamentAsset::getAlpineComponentSrc('chart', 'filament/widgets') }}"
                data-chart-type="{{ $chartType }}"
                x-data="chart({
                    cachedData: @js($chartData),
                    options: @js($chartOptions),
                    type: @js($chartType),
                })"
                style="height: 100%;"
            >
                <canvas x-ref="canvas" style="width: 100%; height: 100%;"></canvas>
                <span x-ref="backgroundColorElement" class="fi-wi-chart-bg-color"></span>
                <span x-ref="borderColorElement" class="fi-wi-chart-border-color"></span>
                <span x-ref="gridColorElement" class="fi-wi-chart-grid-color"></span>
                <span x-ref="textColorElement" class="fi-wi-chart-text-color"></span>
            </div>
        </div>
    @endif
</div>
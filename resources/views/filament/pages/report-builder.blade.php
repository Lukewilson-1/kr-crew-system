<x-filament-panels::page>
    <div class="space-y-6">
        <div class="kr-builder-intro">
            <h2 class="text-lg font-semibold" style="color: var(--kr-ink);">Report builder</h2>
            <p class="mt-2 text-sm leading-relaxed" style="color: var(--kr-ink-soft);">
                Create a report definition from the admin area and define its layout, grouping, columns, and filters.
                Use the <strong>Save report</strong> button in the top-right corner when you are done.
            </p>
        </div>

        {{ $this->form }}
    </div>

    <style>
        .kr-builder-intro {
            background: var(--kr-paper-raised);
            border: 1px solid var(--kr-line);
            border-radius: 14px;
            padding: 20px 22px;
        }
    </style>
</x-filament-panels::page>

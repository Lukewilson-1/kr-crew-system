<style>
    :root {
        --kr-maroon: #6C1A23;
        --kr-orange: #F14219;
        --kr-gold: #FEC000;
        --kr-ink: #1a1a1a;
        --kr-ink-soft: #667085;
        --kr-muted: #555555;
        --kr-line: #d9dee7;
        --kr-paper: #ffffff;
        --kr-paper-raised: #ffffff;
        --kr-bg: #f6f7f9;
        --kr-panel: #ffffff;
        --kr-rail: #6C1A23;
    }

    html.dark {
        --kr-ink: #f4f4f5;
        --kr-ink-soft: #a1a1aa;
        --kr-muted: #a1a1aa;
        --kr-line: #3f3f46;
        --kr-paper: #18181b;
        --kr-paper-raised: #242427;
        --kr-bg: #141416;
        --kr-panel: #242427;
    }

    .kr-status-badge {
        background: var(--kr-line);
        border-radius: 999px;
        color: var(--kr-ink-soft);
        display: inline-block;
        font-size: 11px;
        font-weight: 800;
        line-height: 1;
        padding: 5px 10px;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .kr-status-badge-open {
        background: #fde3dd;
        color: var(--kr-orange);
    }

    .kr-status-badge-resolved {
        background: #e7d9db;
        color: var(--kr-maroon);
    }

    .kr-status-badge-in {
        background: #e7d9db;
        color: var(--kr-maroon);
    }

    .kr-status-badge-out {
        background: #f8ecc9;
        color: #8a6d00;
    }

    html.dark .kr-status-badge-open {
        background: rgba(241, 66, 25, 0.22);
        color: #ff8a6b;
    }

    html.dark .kr-status-badge-resolved,
    html.dark .kr-status-badge-in {
        background: rgba(108, 26, 35, 0.55);
        color: #e7d9db;
    }

    html.dark .kr-status-badge-out {
        background: rgba(248, 236, 201, 0.16);
        color: #f8ecc9;
    }

    /* ── Print: ensure badges and charts render correctly ── */
    @media print {
        .kr-status-badge {
            border: 1px solid currentColor !important;
            print-color-adjust: exact !important;
            -webkit-print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        .kr-status-badge-open {
            background: #fde3dd !important;
            color: #B71C1C !important;
        }
        .kr-status-badge-resolved,
        .kr-status-badge-in {
            background: #e7d9db !important;
            color: #6C1A23 !important;
        }
        .kr-status-badge-out {
            background: #f8ecc9 !important;
            color: #8a6d00 !important;
        }
        /* Hide Filament chart widgets (ApexCharts renders via SVG, which prints natively) */
        canvas { display: none !important; }
        .print-only { display: block !important; }
        .no-print { display: none !important; }
    }
</style>
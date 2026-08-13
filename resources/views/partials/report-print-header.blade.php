{{-- Print-only header used by report pages so printed output carries the KR logo. --}}
<div class="kr-print-header">
    <img src="{{ asset('assets/logo.png') }}" alt="Kenya Railways">
    <div class="kr-print-brand">
        <div class="kr-print-title">Kenya Railways</div>
        <div class="kr-print-sub">{{ $title ?? '' }}</div>
        <div class="kr-print-meta">Running Room Register — {{ config('app.name', 'KR Crew System') }} · Printed {{ now()->format('d/m/Y H:i') }}</div>
    </div>
</div>

<style>
    .kr-print-header { display: none; }

    @media print {
        .no-print, .fi-topbar, .fi-sidebar { display: none !important; }

        .kr-print-header {
            display: flex;
            align-items: center;
            gap: 14px;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 10px;
            margin-bottom: 18px;
        }
        .kr-print-header img { width: 56px; height: 56px; object-fit: contain; }
        .kr-print-title { font-size: 20px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; line-height: 1.1; }
        .kr-print-sub { font-size: 13px; font-weight: 600; }
        .kr-print-meta { font-size: 11px; color: #555; }
    }
</style>

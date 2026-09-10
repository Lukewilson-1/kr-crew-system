@extends('layouts.app')

@section('content')
<div style="max-width:960px;margin:0 auto;padding:32px 24px;display:grid;gap:24px;">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
        <div>
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="width:4px;height:32px;border-radius:2px;background:#681828;display:inline-block;"></span>
                <h1 style="font-size:24px;font-weight:700;margin:0;color:#1a1a1a;">Decision-support reports</h1>
            </div>
            <p style="margin:6px 0 0 16px;color:#555555;">Built-in analytics across operations, crew, matters and security — open one to filter and print it.</p>
        </div>
        <a href="{{ route('reports.index') }}" style="padding:9px 16px;border-radius:8px;background:#f6f7f9;border:1px solid #d9dee7;color:#1a1a1a;text-decoration:none;font-weight:600;font-size:13px;">Report builder</a>
    </div>

    @if(empty($reports))
        <div style="background:#ffffff;border:1px solid #d9dee7;border-radius:12px;padding:28px;color:#555555;">
            No system reports are available to your account.
        </div>
    @else
        @php
            $grouped = collect($reports)->groupBy(fn ($r) => $r->category());
        @endphp
        @foreach($grouped as $category => $categoryReports)
            <section style="display:grid;gap:14px;">
                <h2 style="font-size:14px;font-weight:700;color:#681828;margin:0;text-transform:uppercase;letter-spacing:0.4px;">{{ $category }}</h2>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:16px;">
                    @foreach($categoryReports as $report)
                        <a href="{{ route('reports.system.show', $report->slug()) }}" style="background:#ffffff;border:1px solid #d9dee7;border-radius:12px;padding:20px;display:flex;flex-direction:column;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,0.05);text-decoration:none;color:inherit;">
                            <div style="width:44px;height:44px;border-radius:10px;background:#681828;color:#F8C808;display:flex;align-items:center;justify-content:center;font-size:22px;">
                                {{ $report->icon() }}
                            </div>
                            <div>
                                <div style="font-size:15px;font-weight:700;color:#1a1a1a;">{{ $report->title() }}</div>
                                <div style="font-size:12px;color:#555555;margin-top:4px;line-height:1.5;">{{ $report->description() }}</div>
                            </div>
                            <div style="margin-top:auto;display:inline-flex;padding:9px 14px;border-radius:8px;background:#f6f7f9;border:1px solid #d9dee7;color:#1a1a1a;text-decoration:none;font-weight:600;font-size:13px;width:fit-content;">
                                Open report
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endforeach
    @endif
</div>
@endsection
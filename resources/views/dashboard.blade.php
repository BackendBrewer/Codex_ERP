@extends('layouts.app', ['title' => 'Overview'])

@section('content')
@php
    $hour = (int) now()->timezone('Asia/Karachi')->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $attendanceTotal = (int) $attendanceToday->sum();
    $attendancePresent = (int) ($attendanceToday['present'] ?? 0) + (int) ($attendanceToday['late'] ?? 0);
    $attendanceRate = $attendanceTotal > 0 ? (int) round(($attendancePresent / $attendanceTotal) * 100) : 0;
    $maxCollection = max(1, (float) collect($collectionHistory)->max('amount'));
@endphp
<section class="welcome-panel">
    <div class="welcome-copy"><span class="section-eyebrow"><i></i> {{ now()->timezone('Asia/Karachi')->format('l · d F Y') }}</span><h1>{{ $greeting }}, {{ Str::before(auth()->user()->name, ' ') }}.</h1><p>Here’s what’s happening at <strong>{{ auth()->user()->academy?->name ?? 'your academy' }}</strong> today.</p></div>
    <div class="welcome-actions">
        @if ($features['inquiries'])<a class="button button-quiet" href="{{ route('modules.create', 'inquiries') }}"><span>＋</span> New inquiry</a>@endif
        @if ($features['students'])<a class="button button-bright" href="{{ route('modules.create', 'students') }}"><span>＋</span> Enroll student</a>@endif
    </div>
</section>

<section class="overview-grid" aria-label="Academy overview">
    @if ($features['students'])<a class="overview-card" href="{{ route('modules.index', 'students') }}"><span class="overview-icon icon-blue">♙</span><span class="overview-label">Enrolled students</span><strong>{{ number_format($stats['students']) }}</strong><small>Active student records <b>↗</b></small></a>@endif
    @if ($features['fees'])<a class="overview-card" href="{{ route('modules.index', 'payments') }}"><span class="overview-icon icon-green">₨</span><span class="overview-label">Collected this month</span><strong class="overview-money">₨ {{ number_format((float) $stats['collections']) }}</strong><small>Fee receipts · {{ now()->format('F') }} <b>↗</b></small></a>@endif
    @if ($features['fee_balance'])<a class="overview-card" href="{{ auth()->user()->hasFeature('fees.reports.pending') ? route('reports', ['fee_report' => 'pending']) : route('modules.index', 'fees') }}"><span class="overview-icon icon-amber">◷</span><span class="overview-label">Fees outstanding</span><strong class="overview-money">₨ {{ number_format((float) $stats['pending']) }}</strong><small>Unpaid and partial bills <b>↗</b></small></a>@endif
    @if ($features['inquiries'])<a class="overview-card" href="{{ route('modules.index', 'inquiries') }}"><span class="overview-icon icon-violet">↗</span><span class="overview-label">Admissions pipeline</span><strong>{{ number_format($stats['inquiries']) }}</strong><small>Inquiries needing follow-up <b>↗</b></small></a>@endif
</section>

<section class="dashboard-content-grid">
    <div class="dashboard-primary-column">
        @if ($features['fees'])
        <article class="workspace-card collection-card">
            <div class="workspace-card-heading"><div><span class="section-eyebrow">FINANCE · LAST SIX MONTHS</span><h2>Fee collection</h2><p>Payments recorded across your academy.</p></div><a class="button button-quiet" href="{{ route('modules.index', 'payments') }}">View receipts <span>↗</span></a></div>
            <div class="collection-chart" role="img" aria-label="Monthly fee collection for the last six months">
                @foreach ($collectionHistory as $month)
                <div class="chart-column"><div class="chart-value">@if ($month['amount'] > 0)₨ {{ number_format($month['amount'], 0) }}@endif</div><div class="chart-track"><span style="height: {{ max(3, (int) round(($month['amount'] / $maxCollection) * 100)) }}%"></span></div><small>{{ $month['label'] }}</small></div>
                @endforeach
            </div>
        </article>
        @endif

        @if ($features['fees'])
        <article class="workspace-card recent-card">
            <div class="workspace-card-heading"><div><span class="section-eyebrow">LATEST ACTIVITY</span><h2>Recent fee receipts</h2><p>Most recent payments entered by your team.</p></div><a class="text-link" href="{{ route('modules.index', 'payments') }}">All receipts <span>→</span></a></div>
            <div class="table-scroll"><table class="workspace-table"><thead><tr><th>Student</th><th>Receipt</th><th>Paid on</th><th>Method</th><th class="align-right">Amount</th></tr></thead><tbody>
            @forelse ($payments as $payment)
            <tr><td><div class="student-cell"><span class="student-avatar">{{ mb_substr($payment->student->name, 0, 1) }}</span><span><strong>{{ $payment->student->name }}</strong><small>{{ $payment->student->admission_no }} · {{ $payment->student->academyClass?->name ?? 'No class' }}</small></span></div></td><td><span class="receipt-code">{{ $payment->receipt_no }}</span></td><td>{{ $payment->paid_at->timezone('Asia/Karachi')->format('d M Y') }}</td><td><span class="method-tag">{{ Str::headline($payment->method) }}</span></td><td class="align-right amount-cell">₨ {{ number_format((float) $payment->amount, 0) }}</td></tr>
            @empty<tr><td colspan="5"><div class="table-empty"><span>₨</span><strong>No receipts recorded yet</strong><small>Once you record a payment, it will appear here.</small></div></td></tr>@endforelse
            </tbody></table></div>
        </article>
        @endif
    </div>

    <aside class="dashboard-secondary-column">
        @if ($features['attendance'])
        <article class="workspace-card attendance-card">
            <div class="workspace-card-heading"><div><span class="section-eyebrow">ACADEMIC DAY</span><h2>Attendance today</h2><p>{{ now()->timezone('Asia/Karachi')->format('d M Y') }}</p></div><a class="round-arrow" href="{{ route('modules.index', 'attendance') }}" aria-label="Open attendance">↗</a></div>
            <div class="attendance-summary"><div class="attendance-gauge" style="--attendance-rate: {{ $attendanceRate }}%"><div><strong>{{ $attendanceTotal ? $attendanceRate.'%' : '—' }}</strong><small>present</small></div></div><div class="attendance-legend"><div><i class="legend-present"></i><span>Present</span><strong>{{ number_format($attendancePresent) }}</strong></div><div><i class="legend-absent"></i><span>Absent</span><strong>{{ number_format((int) ($attendanceToday['absent'] ?? 0)) }}</strong></div><div><i class="legend-leave"></i><span>Leave / other</span><strong>{{ number_format((int) ($attendanceToday['leave'] ?? 0)) }}</strong></div></div></div>
            @if ($attendanceTotal === 0)<p class="attendance-empty">No attendance has been marked today.</p>@endif
            <a class="button button-dark full-button" href="{{ route('modules.create', 'attendance') }}">＋ Mark attendance</a>
        </article>
        @endif

        @if ($features['inquiries'] || $features['accounts'])
        <article class="workspace-card pulse-card"><div class="workspace-card-heading"><div><span class="section-eyebrow">CAMPUS PULSE</span><h2>This month</h2></div><span class="pulse-dot"></span></div>
            @if ($features['inquiries'])<a class="pulse-row" href="{{ route('modules.index', 'inquiries') }}"><span class="pulse-icon pulse-admissions">↗</span><span><small>Open inquiries</small><strong>{{ number_format($stats['inquiries']) }}</strong></span><b>→</b></a>@endif
            @if ($features['accounts'])<div class="pulse-row"><span class="pulse-icon pulse-income">＋</span><span><small>Other income</small><strong>₨ {{ number_format((float) $stats['income']) }}</strong></span></div><div class="pulse-row"><span class="pulse-icon pulse-expense">−</span><span><small>Expenses</small><strong>₨ {{ number_format((float) $stats['expenses']) }}</strong></span></div><div class="pulse-net"><span>Net ledger balance</span><strong class="{{ ((float) $stats['income'] - (float) $stats['expenses']) < 0 ? 'text-negative' : '' }}">₨ {{ number_format((float) $stats['income'] - (float) $stats['expenses']) }}</strong></div>@endif
        </article>
        @endif

        <article class="workspace-card shortcuts-card"><div class="workspace-card-heading"><div><span class="section-eyebrow">GET THINGS DONE</span><h2>Quick access</h2></div></div><div class="shortcut-grid">
            @if ($features['students'])<a href="{{ route('modules.index', 'students') }}"><span class="shortcut-icon">♙</span><span>Students<small>Directory</small></span><b>↗</b></a>@endif
            @if (auth()->user()->hasFeature('fees.invoices'))<a href="{{ route('modules.index', 'fees') }}"><span class="shortcut-icon">▤</span><span>Fee desk<small>Bills & balances</small></span><b>↗</b></a>@endif
            @if ($features['attendance'])<a href="{{ route('modules.index', 'attendance') }}"><span class="shortcut-icon">◷</span><span>Attendance<small>Daily register</small></span><b>↗</b></a>@endif
            @if (auth()->user()->hasFeature('exams'))<a href="{{ route('modules.index', 'exams') }}"><span class="shortcut-icon">✎</span><span>Exams & results<small>Assessments</small></span><b>↗</b></a>@endif
            @if ($features['accounts'])<a href="{{ route('modules.index', 'accounts') }}"><span class="shortcut-icon">₨</span><span>Accounts<small>Income & expenses</small></span><b>↗</b></a>@endif
            @if ($features['reports'])<a href="{{ route('reports') }}"><span class="shortcut-icon">▥</span><span>Reports<small>Academy insights</small></span><b>↗</b></a>@endif
        </div></article>
    </aside>
</section>
@endsection

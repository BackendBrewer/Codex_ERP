@extends('layouts.app', ['title' => 'Overview'])

@section('content')
<section class="page-heading"><div><div class="eyebrow">{{ now()->timezone('Asia/Karachi')->format('l, d F Y') }}</div><h1>Good morning, {{ Str::before(auth()->user()->name, ' ') }} <span>☀</span></h1><p>Your academy overview for today.</p></div><a class="btn-primary" href="{{ route('modules.create', 'students') }}">＋ Add student</a></section>
<section class="metric-grid">
    <article class="metric-card"><div class="metric-label">Active students <span class="metric-icon">♙</span></div><strong>{{ number_format($stats['students']) }}</strong><small>Currently enrolled</small></article>
    <article class="metric-card"><div class="metric-label">Fees collected this month <span class="metric-icon">₨</span></div><strong class="money">₨ {{ number_format((float) $stats['collections']) }}</strong><small>Recorded receipts</small></article>
    <article class="metric-card"><div class="metric-label">Outstanding challans <span class="metric-icon amber">◷</span></div><strong class="money">₨ {{ number_format((float) $stats['pending']) }}</strong><small>Unpaid or partial</small></article>
    <article class="metric-card"><div class="metric-label">Open inquiries <span class="metric-icon violet">♧</span></div><strong>{{ number_format($stats['inquiries']) }}</strong><small>Follow-up required</small></article>
</section>
<section class="dashboard-grid">
    <article class="panel"><div class="panel-heading"><div><h2>Recent fee receipts</h2><p>Latest payments recorded at your academy.</p></div><a class="text-link" href="{{ route('modules.index', 'payments') }}">All receipts →</a></div>
        <div class="table-scroll"><table><thead><tr><th>Receipt</th><th>Student</th><th>Class</th><th>Date</th><th>Amount</th><th>Method</th></tr></thead><tbody>
        @forelse ($payments as $payment)<tr><td class="table-strong">{{ $payment->receipt_no }}</td><td><div class="person-cell"><span class="person-initial">{{ mb_substr($payment->student->name, 0, 1) }}</span><span><b>{{ $payment->student->name }}</b><small>{{ $payment->student->admission_no }}</small></span></div></td><td>{{ $payment->student->academyClass?->name ?? '—' }}</td><td>{{ $payment->paid_at->timezone('Asia/Karachi')->format('d M, Y') }}</td><td class="table-strong">₨ {{ number_format((float) $payment->amount) }}</td><td><span class="status-pill paid">{{ ucfirst($payment->method) }}</span></td></tr>
        @empty<tr><td colspan="6" class="empty-state">No fee receipts yet. Create a fee challan, then record a payment.</td></tr>@endforelse
        </tbody></table></div>
    </article>
    <div class="dashboard-side">
        <article class="panel attendance-panel"><div class="panel-heading"><div><h2>Today at a glance</h2><p>Academy activity</p></div><span class="live-dot">●</span></div><div class="quick-metric"><span>New admissions pipeline</span><a href="{{ route('modules.index', 'inquiries') }}">{{ $stats['inquiries'] }} open inquiries →</a></div><div class="quick-metric"><span>Monthly income</span><b>₨ {{ number_format((float) $stats['income']) }}</b></div><div class="quick-metric"><span>Monthly expenses</span><b>₨ {{ number_format((float) $stats['expenses']) }}</b></div><a class="btn-outline full-width" href="{{ route('reports') }}">Open reports</a></article>
        <article class="panel quick-panel"><h2>Quick actions</h2><div class="quick-links"><a href="{{ route('modules.create', 'inquiries') }}"><span>＋</span>New inquiry</a><a href="{{ route('modules.create', 'fees') }}"><span>▤</span>Fee challan</a><a href="{{ route('modules.create', 'attendance') }}"><span>✓</span>Mark attendance</a><a href="{{ route('modules.create', 'accounts') }}"><span>₨</span>Ledger entry</a></div></article>
    </div>
</section>
@endsection

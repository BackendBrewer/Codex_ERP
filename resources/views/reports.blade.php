@extends('layouts.app', ['title' => 'Reports'])

@section('content')
@if ($feeReport)
<section class="page-heading"><div><div class="eyebrow"><a href="{{ route('reports') }}">Reports</a> · Fee module</div><h1>{{ $reportTitle }}</h1><p>
    @if ($feeReport === 'defaulters')
        Choose an overdue date to see every student with a pending balance.
    @else
        Search this academy’s fee records, then choose Search to load results.
    @endif
</p></div></section>
<form class="panel fee-filter live-report-filter {{ $feeReport === 'defaulters' ? 'defaulter-search' : 'fee-record-search' }}" method="GET" action="{{ route('reports') }}"><input type="hidden" name="fee_report" value="{{ $feeReport }}">
    @if ($feeReport === 'defaulters')
        <label>Overdue up to<input type="date" name="as_of" value="{{ request('as_of', now()->toDateString()) }}"></label>
    @else
        <label>Find a student<input class="select-search" type="search" data-filter-select="report_student_id" placeholder="Type name, admission ID or phone…" autocomplete="off"><select id="report_student_id" name="student_id"><option value="">All students</option>@foreach ($studentOptions as $student)<option value="{{ $student->id }}" data-sort-key="{{ $student->name }}" @selected((string) $reportStudentId === (string) $student->id)>{{ $student->admission_no }} · {{ $student->name }} · {{ $student->academyClass?->name ?? 'No class' }} · {{ $student->guardian_phone }}</option>@endforeach</select></label>
        <label>Find a bill<input type="search" name="q" value="{{ $feeReportSearch }}" placeholder="Challan or receipt number…" autocomplete="off" data-filter-select="report_record_options"><select id="report_record_options" aria-hidden="true"><option value="">All matching records</option>@foreach ($reportSearchChoices as $choice)<option value="{{ $choice['value'] }}" data-query-value="{{ $choice['query'] }}">{{ $choice['label'] }}</option>@endforeach</select></label>
        <label class="local-picker">Class / section<input class="select-search" type="search" data-filter-select="report_class_id" placeholder="Type class or section…" autocomplete="off"><select id="report_class_id" name="class_id"><option value="">All classes</option>@foreach ($classOptions as $class)<option value="{{ $class->id }}" @selected((string) $reportClassId === (string) $class->id)>{{ $class->name }} · {{ $class->section }}</option>@endforeach</select></label>
        @if ($feeReport === 'date' || $feeReport === 'summary')<label>Date<input type="date" name="date" value="{{ request('date') }}"></label>@elseif (in_array($feeReport, ['month', 'adjust'], true))<label>Month<input type="month" name="month" value="{{ request('month') }}"></label>@endif
    @endif
    <button class="btn-primary" type="submit">Search</button>@if ($hasReportSearch)<a class="fee-reset" href="{{ route('reports', ['fee_report' => $feeReport]) }}">Clear</a>@endif
</form>

@if ($hasReportSearch)
    @if (in_array($feeReport, ['date', 'month', 'summary'], true))
    <section class="metric-grid report-grid"><article class="metric-card"><div class="metric-label">Matching receipts</div><strong>{{ $feePayments->count() }}</strong></article><article class="metric-card"><div class="metric-label">Amount collected</div><strong class="money">₨ {{ number_format((float) $feePayments->sum('amount'), 2) }}</strong></article></section>
    <section class="panel data-panel"><div class="panel-heading"><div><h2>{{ $feePayments->count() }} matching receipts</h2></div><a class="btn-outline" target="_blank" href="{{ request()->fullUrlWithQuery(['print' => 1]) }}">Print receipts report</a></div><div class="table-scroll"><table class="sortable-table"><thead><tr><th data-sort="receipt">Receipt</th><th data-sort="student">Student</th><th data-sort="class">Class</th><th data-sort="date">Paid at</th><th data-sort="method">Method</th><th data-sort="amount">Amount</th></tr></thead><tbody>@forelse ($feePayments as $payment)<tr><td>{{ $payment->receipt_no }}</td><td>{{ $payment->student->name }}</td><td>{{ $payment->student->academyClass?->name ?? '—' }}</td><td>{{ $payment->paid_at->timezone('Asia/Karachi')->format('d M Y, h:i A') }}</td><td>{{ Str::headline($payment->method) }}</td><td>₨ {{ number_format((float) $payment->amount, 2) }}</td></tr>@empty<tr><td colspan="6" class="empty-state">No fee receipts match this search.</td></tr>@endforelse</tbody></table></div></section>
    @elseif ($feeReport === 'defaulters')
    <section class="panel data-panel"><div class="panel-heading"><div><h2>{{ $defaulterRows->count() }} students with pending dues</h2><p>Overdue on or before {{ \Illuminate\Support\Carbon::parse($reportAsOf)->format('d M Y') }}</p></div><a class="btn-primary" target="_blank" href="{{ request()->fullUrlWithQuery(['print' => 1]) }}">Print defaulter list</a></div><div class="table-scroll"><table class="sortable-table"><thead><tr><th data-sort="student">Student</th><th data-sort="admission">Admission ID</th><th data-sort="class">Class / section</th><th data-sort="phone">Parent contact</th><th data-sort="count">Pending bills</th><th data-sort="oldest">Oldest due</th><th data-sort="balance">Total pending</th></tr></thead><tbody>@forelse ($defaulterRows as $row)<tr><td>{{ $row['student']->name }}</td><td>{{ $row['student']->admission_no }}</td><td>{{ $row['student']->academyClass?->name ?? '—' }} · {{ $row['student']->academyClass?->section ?? '—' }}</td><td>{{ $row['student']->guardian_phone }}</td><td>{{ $row['invoice_count'] }}</td><td>{{ $row['oldest_due']?->format('d M Y') }}</td><td class="fee-balance-open">₨ {{ number_format($row['balance'], 2) }}</td></tr>@empty<tr><td colspan="7" class="empty-state">No students have overdue balances for this date.</td></tr>@endforelse</tbody></table></div></section>
    @else
    <section class="panel data-panel"><div class="panel-heading"><div><h2>{{ $feeReport === 'adjust' ? 'Generated monthly bills' : 'Outstanding bills' }}</h2><p>{{ $feeInvoices->count() }} matching challans</p></div><div class="list-toolbar">@if ($feeReport === 'adjust' && auth()->user()->hasFeature('fees.bulk'))<a class="btn-primary" href="{{ route('fees.bulk.form') }}">Generate bulk fee bills</a>@endif<a class="btn-outline" target="_blank" href="{{ request()->fullUrlWithQuery(['print' => 1]) }}">Print {{ $feeReport === 'adjust' ? 'monthly bill list' : 'fee report' }}</a></div></div><div class="table-scroll"><table class="sortable-table"><thead><tr><th data-sort="challan">Challan</th><th data-sort="student">Student</th><th data-sort="class">Class</th><th data-sort="month">Month</th><th data-sort="due">Due date</th><th data-sort="balance">Balance</th><th data-sort="status">Status</th><th>Actions</th></tr></thead><tbody>
    @forelse ($feeInvoices as $invoice)@php($balance = $invoice->status === 'waived' ? 0 : max(0, (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee - (float) $invoice->payments->sum('amount')))<tr><td>{{ $invoice->invoice_no }}</td><td>{{ $invoice->student->name }}</td><td>{{ $invoice->student->academyClass?->name ?? '—' }}</td><td>{{ $invoice->invoice_month->format('M Y') }}</td><td>{{ $invoice->due_date->format('d M Y') }}</td><td>₨ {{ number_format($balance, 2) }}</td><td>{{ Str::headline($invoice->status) }}</td><td class="report-row-actions">@if ($feeReport === 'adjust' && (auth()->user()->hasFeature('fees.reports.adjust') || auth()->user()->hasFeature('fees.modify')))<details class="bill-adjust"><summary>± Adjust</summary><form method="POST" action="{{ route('challans.adjust', $invoice->id) }}">@csrf<label>Action<select name="operation"><option value="add">Add to bill +</option><option value="subtract">Reduce bill −</option></select></label><label>PKR<input type="number" name="adjustment" min="0.01" step="0.01" max="99999999.99" required></label><label>Reason<input type="text" name="reason" maxlength="180" placeholder="Optional"></label><button type="submit">Apply adjustment</button></form></details>@endif @if (auth()->user()->hasFeature('fees.modify') || auth()->user()->hasFeature('fees.reports.adjust'))<a href="{{ route('modules.edit', ['fees', $invoice->id]) }}">Edit bill</a>@endif</td></tr>@empty<tr><td colspan="8" class="empty-state">No challans match this search.</td></tr>@endforelse
    </tbody></table></div></section>
    @endif
@else
    <section class="search-empty-state"><span>⌕</span><h2>
        @if ($feeReport === 'defaulters')
            Choose an overdue date
        @else
            Search this report
        @endif
    </h2><p>
        @if ($feeReport === 'defaulters')
            Select the date and press Search to load all students with unpaid or partially paid overdue bills.
        @else
            Enter a student, admission ID or phone number, or choose a class/date and press Search.
        @endif
    </p></section>
@endif
@else
<section class="page-heading"><div><div class="eyebrow">Academy insights · {{ now()->format('F Y') }}</div><h1>Reports</h1><p>Fee, ledger and attendance summaries for your campus.</p></div><button class="btn-outline" type="button" onclick="window.print()">Print report summary</button></section>
<section class="metric-grid report-grid"><article class="metric-card"><div class="metric-label">Collected this month</div><strong class="money">₨ {{ number_format((float) $collected) }}</strong><small>Posted fee receipts</small></article><article class="metric-card"><div class="metric-label">Outstanding fees</div><strong class="money">₨ {{ number_format((float) $outstanding) }}</strong><small>Unpaid / partial challans</small></article><article class="metric-card"><div class="metric-label">Ledger income</div><strong class="money">₨ {{ number_format((float) $income) }}</strong><small>Income entries this month</small></article><article class="metric-card"><div class="metric-label">Ledger expenses</div><strong class="money">₨ {{ number_format((float) $expenses) }}</strong><small>Expense entries this month</small></article></section>
<section class="panel report-panel"><div class="panel-heading"><div><h2>Attendance summary · Today</h2><p>Based on attendance entries recorded today.</p></div><a class="text-link" href="{{ route('modules.index', 'attendance') }}">Open attendance →</a></div><div class="report-stats">@foreach (['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'leave' => 'Leave'] as $key => $label)<div><span>{{ $label }}</span><strong>{{ number_format((int) ($attendance[$key] ?? 0)) }}</strong></div>@endforeach</div></section>
<section class="panel note-panel"><b>Profit / loss snapshot</b><span>Ledger income less ledger expenses this month:</span><strong class="{{ ((float) $income - (float) $expenses) < 0 ? 'negative' : 'positive' }}">₨ {{ number_format((float) $income - (float) $expenses) }}</strong><small>Fee receipts are tracked separately from income ledger entries to avoid double-counting.</small></section>
@endif
@endsection

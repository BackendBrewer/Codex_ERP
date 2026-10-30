@extends('layouts.app', ['title' => $student->name.' · Student workspace'])

@section('content')
<section class="student-profile-hero">
    <a class="student-back-link" href="{{ route('modules.index', ['students', 'q' => $student->admission_no]) }}">← Student finder</a>
    <div class="student-profile-main">
        @if ($student->photo_path)<img class="student-profile-photo" src="{{ asset('storage/'.$student->photo_path) }}" alt="{{ $student->name }}">@else<span class="student-profile-photo student-directory-avatar">{{ mb_substr($student->name, 0, 1) }}</span>@endif
        <div class="student-profile-copy"><span class="fee-kicker">STUDENT RECORD · {{ $student->admission_no }}</span><h1>{{ $student->name }}</h1><p>{{ $student->father_name }} · {{ $student->academyClass?->name ?? 'No class assigned' }} {{ $student->academyClass?->section ? '· Section '.$student->academyClass->section : '' }}</p><div class="student-profile-chips"><span>{{ $student->guardian_phone }}</span><span>{{ Str::headline($student->status) }}</span><span>Admitted {{ $student->admission_date?->format('d M Y') ?? '—' }}</span></div></div>
    </div>
    <div class="student-profile-actions">@if (auth()->user()->hasFeature('students'))<a class="btn-outline" href="{{ route('modules.edit', ['students', $student->id]) }}">Edit profile</a><a class="btn-outline" target="_blank" href="{{ route('students.card', $student->id) }}">Print ID card</a>@endif</div>
</section>

<nav class="student-section-nav"><a href="#overview">Overview</a>@if ($canViewFees)<a href="#fees">Fee details</a>@endif @if ($canViewAttendance)<a href="#attendance">Attendance</a>@endif @if ($canViewResults)<a href="#results">Exam results</a>@endif</nav>

<section id="overview" class="student-workspace-overview">
    <div class="student-overview-heading"><div><span class="fee-kicker">ONE STUDENT · ALL DAILY TASKS</span><h2>What would you like to handle?</h2></div><p>Shortcuts open the selected student directly in the relevant form.</p></div>
    <div class="student-action-grid">
        @if (auth()->user()->hasFeature('fees.single'))<a class="student-action-card" href="{{ route('modules.create', ['fees', 'student_id' => $student->id]) }}"><span class="student-action-icon fee-action-icon">₨</span><strong>Create fee bill</strong><small>Start a new challan for this student</small><b>Open form →</b></a>@endif
        @if (auth()->user()->hasFeature('fees.collect'))<a class="student-action-card" href="{{ route('modules.create', ['payments', 'student_id' => $student->id]) }}"><span class="student-action-icon payment-action-icon">↙</span><strong>Collect fee</strong><small>Choose one of this student’s pending bills</small><b>Open form →</b></a>@endif
        @if (auth()->user()->hasFeature('attendance'))<a class="student-action-card" href="{{ route('modules.create', ['attendance', 'student_id' => $student->id]) }}"><span class="student-action-icon attendance-action-icon">◷</span><strong>Add attendance</strong><small>Record today’s mark for this student</small><b>Open form →</b></a>@endif
        @if (auth()->user()->hasFeature('results'))<a class="student-action-card" href="{{ route('modules.create', ['results', 'student_id' => $student->id]) }}"><span class="student-action-icon result-action-icon">▥</span><strong>Add exam result</strong><small>Enter marks for an exam or award list</small><b>Open form →</b></a>@endif
        @if (auth()->user()->hasFeature('students'))<a class="student-action-card" target="_blank" href="{{ route('students.card', $student->id) }}"><span class="student-action-icon card-action-icon">▣</span><strong>Print student card</strong><small>Open a print-ready student ID card</small><b>Print card →</b></a>@endif
    </div>
</section>

@if ($canViewFees)
<section id="fees" class="panel student-record-section"><header class="student-record-heading"><div><span class="fee-kicker">BILLING HISTORY</span><h2>Fee details</h2><p>{{ $feeInvoices->count() }} generated bill(s) · Fee starts {{ $student->fee_adjust_month?->format('F Y') ?? '—' }}</p></div><div class="student-record-actions">@if (auth()->user()->hasFeature('fees.single'))<a class="btn-primary" href="{{ route('modules.create', ['fees', 'student_id' => $student->id]) }}">＋ Create bill</a>@endif @if (auth()->user()->hasFeature('fees.monthly_students'))<a class="btn-outline" href="{{ route('fees.monthly-students', ['student_id' => $student->id]) }}">Monthly fee</a>@endif</div></header>
    <div class="student-fee-summary"><article><small>Total billed</small><strong>₨ {{ number_format($feeSummary['billed'], 2) }}</strong></article><article><small>Received</small><strong>₨ {{ number_format($feeSummary['paid'], 2) }}</strong></article><article><small>Pending balance</small><strong class="fee-balance-open">₨ {{ number_format($feeSummary['due'], 2) }}</strong></article><article><small>Open bills</small><strong>{{ $feeSummary['open_count'] }}</strong></article></div>
    <div class="table-scroll"><table class="student-record-table"><thead><tr><th>Challan / month</th><th>Charges</th><th>Paid</th><th>Balance</th><th>Status</th><th>Actions</th></tr></thead><tbody>
        @forelse ($feeInvoices as $invoice)
            @php($paid = (float) $invoice->payments->sum('amount'))
            @php($balance = $invoice->status === 'waived' ? 0 : max(0, (float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee - $paid))
            <tr><td><strong>{{ $invoice->invoice_no }}</strong><small>{{ $invoice->invoice_month->format('F Y') }} · Due {{ $invoice->due_date->format('d M Y') }}</small></td><td>₨ {{ number_format((float) $invoice->amount - (float) $invoice->discount + (float) $invoice->late_fee, 2) }}<details class="student-breakdown"><summary>View fee heads</summary><div>@foreach ($invoice->fee_head_details ?? [] as $item)<span>{{ $item['label'] }} <b>₨ {{ number_format((float) $item['amount'], 2) }}</b></span>@endforeach</div></details></td><td>₨ {{ number_format($paid, 2) }}<small>{{ $invoice->payments->count() }} receipt(s)</small></td><td class="{{ $balance > 0 ? 'fee-balance-open' : 'fee-balance-paid' }}">₨ {{ number_format($balance, 2) }}</td><td><span class="fee-status {{ $invoice->status === 'waived' || $balance <= 0 ? 'settled' : ($paid > 0 ? 'part-paid' : 'awaiting') }}">{{ $invoice->status === 'waived' ? 'Waived' : ($balance <= 0 ? 'Paid' : ($paid > 0 ? 'Partial' : Str::headline($invoice->status))) }}</span></td><td class="student-row-actions"><a target="_blank" href="{{ route('challans.print', $invoice->id) }}">Print bill</a>@if ($balance > 0 && auth()->user()->hasFeature('fees.collect'))<a href="{{ route('modules.create', ['payments', 'fee_invoice_id' => $invoice->id]) }}">Collect</a>@endif</td></tr>
        @empty<tr><td colspan="6" class="student-empty-record">No fee bills have been generated for this student yet.</td></tr>@endforelse
    </tbody></table></div>
    @if ($canViewPayments)<div class="student-receipt-strip"><h3>Recent receipts</h3>@forelse ($feePayments as $payment)<a target="_blank" href="{{ route('receipts.print', $payment->id) }}"><span>{{ $payment->receipt_no }} · {{ $payment->paid_at->format('d M Y') }}</span><strong>₨ {{ number_format((float) $payment->amount, 2) }}</strong></a>@empty<p>No fee payments recorded yet.</p>@endforelse</div>@endif
</section>
@endif

@if ($canViewAttendance)
<section id="attendance" class="panel student-record-section"><header class="student-record-heading"><div><span class="fee-kicker">DAILY REGISTER</span><h2>Attendance</h2><p>Recent marks for {{ $student->name }} · {{ $attendanceRecords->count() }} latest entries</p></div>@if (auth()->user()->hasFeature('attendance'))<a class="btn-primary" href="{{ route('modules.create', ['attendance', 'student_id' => $student->id]) }}">＋ Add attendance</a>@endif</header>
    <div class="attendance-summary-chips">@foreach (['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'leave' => 'Leave'] as $key => $label)<span><b>{{ $attendanceSummary[$key] ?? 0 }}</b>{{ $label }}</span>@endforeach</div>
    <div class="table-scroll"><table class="student-record-table"><thead><tr><th>Date</th><th>Mark</th><th>Teacher note</th></tr></thead><tbody>@forelse ($attendanceRecords as $entry)<tr><td>{{ $entry->attendance_date->format('D, d M Y') }}</td><td><span class="status-pill {{ $entry->status === 'present' ? 'paid' : 'neutral' }}">{{ Str::headline($entry->status) }}</span></td><td>{{ $entry->note ?: '—' }}</td></tr>@empty<tr><td colspan="3" class="student-empty-record">No attendance has been recorded for this student.</td></tr>@endforelse</tbody></table></div>
</section>
@endif

@if ($canViewResults)
<section id="results" class="panel student-record-section"><header class="student-record-heading"><div><span class="fee-kicker">ASSESSMENT RECORD</span><h2>Exam results</h2><p>{{ $examResults->count() }} result(s) on record</p></div>@if (auth()->user()->hasFeature('results'))<a class="btn-primary" href="{{ route('modules.create', ['results', 'student_id' => $student->id]) }}">＋ Add exam result</a>@endif</header>
    <div class="table-scroll"><table class="student-record-table"><thead><tr><th>Exam / subject</th><th>Class</th><th>Date</th><th>Marks</th><th>Outcome</th></tr></thead><tbody>@forelse ($examResults as $result)@php($exam = $result->examination)@php($percentage = $exam && (float) $exam->total_marks > 0 ? ((float) $result->marks_obtained / (float) $exam->total_marks) * 100 : 0)<tr><td><strong>{{ $exam?->title ?? 'Exam removed' }}</strong><small>{{ $exam?->subject ?? '—' }}@if ($result->remarks) · {{ $result->remarks }}@endif</small></td><td>{{ $exam?->academyClass?->name ?? '—' }}</td><td>{{ $exam?->exam_date?->format('d M Y') ?? '—' }}</td><td>{{ number_format((float) $result->marks_obtained, 2) }} / {{ number_format((float) ($exam?->total_marks ?? 0), 2) }}</td><td><span class="status-pill {{ $exam && (float) $result->marks_obtained >= (float) $exam->passing_marks ? 'paid' : 'pending' }}">{{ $exam && (float) $result->marks_obtained >= (float) $exam->passing_marks ? 'Passed' : 'Needs review' }} · {{ number_format($percentage, 1) }}%</span></td></tr>@empty<tr><td colspan="5" class="student-empty-record">No exam results have been entered for this student.</td></tr>@endforelse</tbody></table></div>
</section>
@endif
@endsection

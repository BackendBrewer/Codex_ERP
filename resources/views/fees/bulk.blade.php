@extends('layouts.app', ['title' => 'Generate Bulk Fee Bills'])

@section('content')
<section class="page-heading"><div><div class="eyebrow"><a href="{{ route('modules.index', 'fees') }}">Fee List</a> · Fee module</div><h1>Generate Bulk Fee Bills</h1><p>Generate monthly challans for active students in this academy.</p></div></section>
<form method="POST" action="{{ route('fees.bulk.generate') }}" class="panel form-panel">@csrf
    <div class="form-hint">{{ number_format($students) }} active student(s) are currently enrolled. Students whose fee starts after the selected month will be skipped. Already generated challans will not be duplicated.</div>
    <div class="form-grid">
        <div class="form-field"><label for="invoice_month">Fee month <i>*</i></label><input type="month" id="invoice_month" name="invoice_month" value="{{ old('invoice_month', now()->format('Y-m')) }}" required>@error('invoice_month')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="due_date">Due date <i>*</i></label><input type="date" id="due_date" name="due_date" value="{{ old('due_date', now()->endOfMonth()->toDateString()) }}" required>@error('due_date')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="class_id">Limit to class</label><input class="select-search" type="search" data-filter-select="class_id" placeholder="Search academy classes…"><select id="class_id" name="class_id"><option value="">All classes</option>@foreach ($classes as $class)<option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->name }} · {{ $class->program }} · Section {{ $class->section }}</option>@endforeach</select>@error('class_id')<small class="field-error">{{ $message }}</small>@enderror</div>
    </div>
    @php($academyHeads = $feeHeads->where('scope', 'academy'))
    @php($classHeads = $feeHeads->where('scope', 'class'))
    @php($studentHeads = $feeHeads->where('scope', 'student'))
    <section class="bulk-fee-heads" id="bulkFeeHeads"><header><div><span class="fee-kicker">BILL BREAKDOWN</span><h2>Active fee heads included automatically</h2><p>Each bill uses that student’s tuition plus academy, matching class/section, and student-specific charges.</p></div><span class="bulk-head-count">{{ $feeHeads->count() }} active heads</span></header>
        <div class="bulk-head-groups">
            <article class="bulk-head-group" data-bulk-scope="academy"><h3>Whole academy <span>{{ $academyHeads->count() }}</span></h3>@forelse ($academyHeads as $head)<div class="bulk-head-item"><span>{{ $head->name }}</span><strong>₨ {{ number_format((float) $head->amount, 2) }}</strong></div>@empty<p>No academy-wide charges</p>@endforelse</article>
            <article class="bulk-head-group" data-bulk-scope="class"><h3>Class / section <span>{{ $classHeads->count() }}</span></h3>@forelse ($classHeads as $head)<div class="bulk-head-item" data-class-id="{{ $head->class_id }}"><span>{{ $head->name }}<small>{{ $head->academyClass?->name ?? 'Class' }} · {{ $head->academyClass?->section ?? '—' }}</small></span><strong>₨ {{ number_format((float) $head->amount, 2) }}</strong></div>@empty<p>No class-specific charges</p>@endforelse</article>
            <article class="bulk-head-group" data-bulk-scope="student"><h3>Student-specific <span>{{ $studentHeads->count() }}</span></h3>@forelse ($studentHeads as $head)<div class="bulk-head-item" data-class-id="{{ $head->student?->class_id }}"><span>{{ $head->name }}<small>{{ $head->student?->name ?? 'Student' }} · {{ $head->student?->academyClass?->name ?? '—' }} / {{ $head->student?->academyClass?->section ?? '—' }}</small></span><strong>₨ {{ number_format((float) $head->amount, 2) }}</strong></div>@empty<p>No student-specific charges</p>@endforelse</article>
        </div>
        <div class="bulk-head-footnote">The bill generator applies only charges assigned to each student. Class-specific and student-specific heads won’t be charged to other students.</div>
    </section>
    <div class="form-actions"><a class="btn-outline" href="{{ route('modules.index', 'fees') }}">Cancel</a><button class="btn-primary" type="submit">Generate fee bills</button></div>
</form>
@endsection

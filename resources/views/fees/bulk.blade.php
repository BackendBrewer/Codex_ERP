@extends('layouts.app', ['title' => 'Generate Bulk Fee Bills'])

@section('content')
<section class="page-heading"><div><div class="eyebrow"><a href="{{ route('fees.desk') }}">Fee workspace</a> · Step 2</div><h1>Create monthly bills</h1><p>Create one bill for each active student in a class or across the academy.</p></div></section>
<form method="POST" action="{{ route('fees.bulk.generate') }}" class="panel form-panel">@csrf
    <div class="fee-create-guide"><strong>{{ number_format($students) }} active students.</strong> Choose the month and payment deadline. You can limit this to one class. The portal uses each student’s tuition and assigned extra charges, skips duplicate bills and does not record any payments.</div>
    <div class="form-grid">
        <div class="form-field"><label for="invoice_month">Month to bill <i>*</i></label><input type="month" id="invoice_month" name="invoice_month" value="{{ old('invoice_month', now()->format('Y-m')) }}" required>@error('invoice_month')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="due_date">Payment deadline <i>*</i></label><input type="date" id="due_date" name="due_date" value="{{ old('due_date', today()->toDateString()) }}" required>@error('due_date')<small class="field-error">{{ $message }}</small>@enderror</div>
        <div class="form-field"><label for="class_id">Who should get a bill?</label><input class="select-search" type="search" data-filter-select="class_id" placeholder="Search by class or section…"><select id="class_id" name="class_id"><option value="">All active students</option>@foreach ($classes as $class)<option value="{{ $class->id }}" @selected((string) old('class_id') === (string) $class->id)>{{ $class->name }} · {{ $class->program }} · Section {{ $class->section }}</option>@endforeach</select>@error('class_id')<small class="field-error">{{ $message }}</small>@enderror</div>
    </div>
    @php($academyHeads = $feeHeads->where('scope', 'academy'))
    @php($classHeads = $feeHeads->where('scope', 'class'))
    @php($studentHeads = $feeHeads->where('scope', 'student'))
    <section class="bulk-fee-heads" id="bulkFeeHeads"><header><div><span class="fee-kicker">HOW EACH BILL IS CALCULATED</span><h2>Tuition plus assigned extra charges</h2><p>Only charges assigned to that student are added. Optional charges can be managed from Fee workspace.</p></div><span class="bulk-head-count">{{ $feeHeads->count() }} active charges</span></header>
        <div class="bulk-head-groups">
            <article class="bulk-head-group" data-bulk-scope="academy"><h3>For everyone <span>{{ $academyHeads->count() }}</span></h3>@forelse ($academyHeads as $head)<div class="bulk-head-item"><span>{{ $head->name }}</span><strong>₨ {{ number_format((float) $head->amount, 2) }}</strong></div>@empty<p>No academy-wide extras</p>@endforelse</article>
            <article class="bulk-head-group" data-bulk-scope="class"><h3>For a class / section <span>{{ $classHeads->count() }}</span></h3>@forelse ($classHeads as $head)<div class="bulk-head-item" data-class-id="{{ $head->class_id }}"><span>{{ $head->name }}<small>{{ $head->academyClass?->name ?? 'Class' }} · {{ $head->academyClass?->section ?? '—' }}</small></span><strong>₨ {{ number_format((float) $head->amount, 2) }}</strong></div>@empty<p>No class extras</p>@endforelse</article>
            <article class="bulk-head-group" data-bulk-scope="student"><h3>For one student <span>{{ $studentHeads->count() }}</span></h3>@forelse ($studentHeads as $head)<div class="bulk-head-item" data-class-id="{{ $head->student?->class_id }}"><span>{{ $head->name }}<small>{{ $head->student?->name ?? 'Student' }} · {{ $head->student?->academyClass?->name ?? '—' }} / {{ $head->student?->academyClass?->section ?? '—' }}</small></span><strong>₨ {{ number_format((float) $head->amount, 2) }}</strong></div>@empty<p>No student extras</p>@endforelse</article>
        </div>
        <div class="bulk-head-footnote">Bills for students whose fee starts after this month, or who already have a bill for it, are skipped.</div>
    </section>
    <div class="form-actions"><a class="btn-outline" href="{{ route('fees.desk') }}">Back</a><button class="btn-primary" type="submit">Create monthly bills</button></div>
</form>
@endsection

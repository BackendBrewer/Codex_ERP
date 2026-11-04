@extends('layouts.app', ['title' => 'Examination'])

@section('content')
<section class="page-heading"><div><div class="eyebrow">Academics / Examination</div><h1>Examination workspace</h1><p>Create an award list, enter marks, and print sheets or class results.</p></div></section>
<section class="panel form-panel"><div class="panel-heading"><div><h2>Create an award list</h2><p>Choose the class, subject, test, month and marks range.</p></div></div>
<form method="POST" action="{{ route('examination.award-lists.store') }}" class="form-grid">@csrf
    <label><span>Class / section</span><select name="class_id" required><option value="">Select class</option>@foreach ($classes as $class)<option value="{{ $class->id }}" @selected(old('class_id') == $class->id)>{{ $class->name }} · {{ $class->section }}</option>@endforeach</select>@error('class_id')<small class="field-error">{{ $message }}</small>@enderror</label>
    <label><span>Subject</span><input name="subject" required maxlength="120" value="{{ old('subject') }}" placeholder="e.g. Urdu">@error('subject')<small class="field-error">{{ $message }}</small>@enderror</label>
    <label><span>Test name</span><input name="title" required maxlength="120" value="{{ old('title') }}" placeholder="e.g. T1">@error('title')<small class="field-error">{{ $message }}</small>@enderror</label>
    <label><span>Month</span><input type="month" name="month" required value="{{ old('month', now()->format('Y-m')) }}">@error('month')<small class="field-error">{{ $message }}</small>@enderror</label>
    <label><span>Test date (optional)</span><input type="date" name="exam_date" value="{{ old('exam_date') }}">@error('exam_date')<small class="field-error">{{ $message }}</small>@enderror</label>
    <label><span>Total marks</span><input type="number" name="total_marks" min="1" step="0.01" required value="{{ old('total_marks', 20) }}">@error('total_marks')<small class="field-error">{{ $message }}</small>@enderror</label>
    <label><span>Passing marks</span><input type="number" name="passing_marks" min="0" step="0.01" required value="{{ old('passing_marks', 7) }}">@error('passing_marks')<small class="field-error">{{ $message }}</small>@enderror</label>
    <div class="form-actions"><button class="btn-primary" type="submit">Create award list</button></div>
</form></section>
<section class="panel data-panel"><div class="panel-heading"><div><h2>Recent award lists</h2><p>Open a list to edit marks or print the teacher sheet.</p></div><a class="btn-outline" href="{{ route('examination.result-sheet') }}">Class-wise result sheet</a></div>
<div class="table-scroll"><table class="data-table"><thead><tr><th>Test</th><th>Class / section</th><th>Subject</th><th>Month</th><th>Marks</th><th>Actions</th></tr></thead><tbody>
@forelse ($examinations as $examination)<tr><td><strong>{{ $examination->title }}</strong></td><td>{{ $examination->academyClass?->name }} · {{ $examination->academyClass?->section }}</td><td>{{ $examination->subject }}</td><td>{{ $examination->exam_date->format('F Y') }}</td><td>{{ rtrim(rtrim(number_format((float) $examination->total_marks, 2), '0'), '.') }} <small>(pass {{ rtrim(rtrim(number_format((float) $examination->passing_marks, 2), '0'), '.') }})</small></td><td class="row-actions"><a href="{{ route('examination.award-lists.show', $examination) }}">Enter marks</a><a target="_blank" href="{{ route('examination.award-lists.print', $examination) }}">Print sheet</a></td></tr>@empty<tr><td colspan="6">No award lists yet. Create one above to start.</td></tr>@endforelse
</tbody></table></div></section>
@endsection

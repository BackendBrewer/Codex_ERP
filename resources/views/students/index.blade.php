@extends('layouts.app', ['title' => 'Student workspace'])

@section('content')
<section class="student-directory-hero">
    <div class="student-hero-copy"><span class="fee-kicker">ACADEMY OPERATIONS · STUDENT FINDER</span><h1>Student workspace</h1><p>Find a student once, then open their profile to manage fees, attendance and exam results.</p></div>
    <div class="student-hero-actions"><a class="btn-outline" href="{{ route('fees.monthly-students') }}">Monthly fee list</a><a class="btn-primary" href="{{ route('modules.create', 'students') }}">＋ Add student</a></div>
</section>
<form class="student-directory-search" method="GET" action="{{ route('modules.index', 'students') }}" data-search-submit>
    <label class="student-search-main"><span>Search and choose a student</span><input id="studentDirectorySearch" type="search" name="q" value="{{ $search }}" placeholder="Click to browse students, or type name, admission ID, parent or phone…" autocomplete="off" data-filter-select="student_directory_options"><select id="student_directory_options" aria-hidden="true"><option value="">All students</option>@foreach ($searchChoices as $choice)<option value="{{ $choice['value'] }}" data-query-value="{{ $choice['query'] }}" data-sort-key="{{ $choice['sort'] ?? $choice['query'] }}">{{ $choice['label'] }}</option>@endforeach</select></label>
    <label class="local-picker"><span>Class / section</span><input class="select-search" type="search" data-filter-select="student_directory_class" placeholder="Type class or section…" autocomplete="off"><select id="student_directory_class" name="class_id"><option value="">All classes</option>@foreach ($classOptions as $class)<option value="{{ $class->id }}" @selected((string) $classId === (string) $class->id)>{{ $class->name }} · {{ $class->section }}</option>@endforeach</select></label>
    <label><span>Student status</span><select name="status"><option value="">All statuses</option>@foreach (['active' => 'Active', 'leave' => 'On leave', 'struck_off' => 'Struck off', 'passed_out' => 'Passed out'] as $key => $label)<option value="{{ $key }}" @selected($studentStatus === $key)>{{ $label }}</option>@endforeach</select></label>
    <button class="btn-primary" type="submit">Search students</button>
    @if ($hasSearch)<a class="fee-reset" href="{{ route('modules.index', 'students') }}">Clear</a>@endif
</form>
@if ($hasSearch)
    <section class="panel student-directory-panel">
        <header class="student-directory-panel-heading"><div><span class="fee-kicker">STUDENT REGISTER</span><h2>{{ number_format($records->total()) }} matching students</h2><p>Open a student record for their fee, attendance and exam details.</p></div><div class="list-toolbar"><a class="btn-outline" target="_blank" href="{{ route('modules.print', array_merge(['module' => 'students'], request()->query(), ['print' => 1])) }}">Print student list</a><a class="btn-primary" target="_blank" href="{{ route('modules.print', array_merge(['module' => 'students'], request()->query(), ['print' => 1, 'format' => 'cards'])) }}">Print ID cards</a></div></header>
        <div class="table-scroll"><table class="student-directory-table sortable-table"><thead><tr><th data-sort="student">Student</th><th data-sort="admission">Admission ID</th><th data-sort="class">Class / group / section</th><th data-sort="parent">Parent contact</th><th data-sort="fee">Monthly fee</th><th data-sort="status">Status</th><th>Actions</th></tr></thead><tbody>
            @forelse ($records as $student)
                <tr>
                    <td><div class="student-directory-person">@if ($student->photo_path)<img src="{{ asset('storage/'.$student->photo_path) }}" alt="{{ $student->name }}">@else<span class="student-directory-avatar">{{ mb_substr($student->name, 0, 1) }}</span>@endif<div><strong>{{ $student->name }}</strong><small>{{ $student->father_name }}</small></div></div></td>
                    <td><span class="student-admission-pill">{{ $student->admission_no }}</span></td>
                    <td><strong>{{ $student->academyClass?->name ?? 'No class assigned' }}</strong><small class="student-subline">{{ $student->academyClass?->program ?? '—' }} · Section {{ $student->academyClass?->section ?? '—' }}</small></td>
                    <td>{{ $student->guardian_phone }}</td>
                    <td class="fee-balance-open">₨ {{ number_format((float) $student->monthly_fee, 2) }}</td>
                    <td><span class="status-pill {{ $student->status === 'active' ? 'paid' : 'neutral' }}">{{ Str::headline($student->status) }}</span></td>
                    <td class="student-actions-cell"><a class="student-open-action" href="{{ route('students.workspace', $student->id) }}">Open workspace <span>→</span></a><details class="student-actions-menu"><summary>More</summary><div>
                        <a href="{{ route('students.workspace', $student->id) }}">Fees, attendance & results</a>
                        @if (auth()->user()->hasFeature('students'))<a href="{{ route('modules.edit', ['students', $student->id]) }}">Edit student</a><a target="_blank" href="{{ route('students.card', $student->id) }}">Print ID card</a>@endif
                        @if (auth()->user()->hasFeature('fees.single'))<a href="{{ route('modules.create', ['fees', 'student_id' => $student->id]) }}">Create fee bill</a>@endif
                        @if (auth()->user()->hasFeature('fees.collect'))<a href="{{ route('modules.create', ['payments', 'student_id' => $student->id]) }}">Collect fee</a>@endif
                        @if (auth()->user()->hasFeature('attendance'))<a href="{{ route('modules.create', ['attendance', 'student_id' => $student->id]) }}">Add attendance</a>@endif
                        @if (auth()->user()->hasFeature('results'))<a href="{{ route('modules.create', ['results', 'student_id' => $student->id]) }}">Add exam result</a>@endif
                    </div></details></td>
                </tr>
            @empty
                <tr><td colspan="7"><div class="fee-empty"><span>♙</span><strong>No students match these filters</strong><small>Try another name, admission ID, phone or class.</small></div></td></tr>
            @endforelse
        </tbody></table></div>
        <div class="pagination-wrap">{{ $records->links() }}</div>
    </section>
@else
    <section class="student-search-welcome"><span class="student-search-icon">⌕</span><div><h2>Start with a student</h2><p>Click the search field to browse the student list, type to narrow it down, select a student, then press Search students.</p></div><div class="student-search-hint"><b>Quick actions</b><small>Fee history · attendance · exam results · ID card</small></div></section>
@endif
@endsection

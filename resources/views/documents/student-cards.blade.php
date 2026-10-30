<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Student cards · {{ $academy?->name ?? 'MSZ ERP' }}</title><link rel="stylesheet" href="{{ asset('css/academy.css') }}"></head>
<body class="print-page student-card-page">
<header class="print-toolbar"><a href="{{ route('modules.index', 'students') }}">← Back to students</a><button type="button" onclick="window.print()">Print {{ $students->count() }} student cards</button></header>
<main class="student-card-sheet"><header class="student-card-sheet-heading"><div><span>MSZ ERP · STUDENT ID CARDS</span><h1>{{ $academy?->name ?? 'Academy' }}</h1></div><small>{{ $students->count() }} cards · {{ now()->timezone('Asia/Karachi')->format('d M Y') }}</small></header>
<section class="student-card-grid">
@foreach ($students as $student)
<article class="student-id-card"><header>@if ($academy?->logo_path)<img src="{{ asset('storage/'.$academy->logo_path) }}" alt="">@else<span class="student-card-logo">M</span>@endif<div><strong>{{ $academy?->name ?? 'MSZ ERP' }}</strong><small>STUDENT IDENTIFICATION</small></div><span class="student-card-mark">MSZ</span></header><div class="student-card-body">@if ($student->photo_path)<img class="student-card-photo" src="{{ asset('storage/'.$student->photo_path) }}" alt="">@else<div class="student-card-photo student-card-initial">{{ mb_substr($student->name, 0, 1) }}</div>@endif<div class="student-card-details"><h2>{{ $student->name }}</h2><div><span>Admission ID</span><strong>{{ $student->admission_no }}</strong></div><div><span>Father / guardian</span><strong>{{ $student->father_name }}</strong></div><div><span>Class / section</span><strong>{{ $student->academyClass?->name ?? '—' }} · {{ $student->academyClass?->section ?? '—' }}</strong></div><div><span>Parent contact</span><strong>{{ $student->guardian_phone }}</strong></div></div></div><footer><span>{{ Str::headline($student->status) }}</span><span>{{ $student->admission_date?->format('M Y') ?? '' }}</span></footer></article>
@endforeach
</section></main><script>window.addEventListener('load',()=>window.print());</script>
</body></html>

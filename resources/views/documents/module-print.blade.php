<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $definition['label'] }} · MSZ ERP</title>
    <link rel="stylesheet" href="{{ asset('css/academy.css') }}">
</head>
<body class="print-page">
    <header class="print-toolbar"><a href="{{ route('modules.index', $module) }}">← Back to {{ $definition['label'] }}</a><button type="button" onclick="window.print()">Print {{ $definition['label'] }}</button></header>
    <main class="print-document">
        <header class="print-document-heading"><div><span>MSZ ERP · ACADEMY RECORDS</span><h1>{{ $definition['label'] }}</h1>@if ($search !== '')<p>Search: “{{ $search }}”</p>@endif</div><div><strong>{{ $records->count() }}</strong><small>matching records</small><small>{{ now()->timezone('Asia/Karachi')->format('d M Y, h:i A') }}</small></div></header>
        @if ($module === 'fees')
            <table class="print-table"><thead><tr><th>Challan</th><th>Student</th><th>Admission ID</th><th>Class</th><th>Fee month</th><th>Net bill</th><th>Received</th><th>Balance</th><th>Status</th></tr></thead><tbody>
            @foreach ($records as $record)@php($received = (float) $record->payments->sum('amount'))@php($net = max(0, (float) $record->amount - (float) $record->discount + (float) $record->late_fee))@php($balance = $record->status === 'waived' ? 0 : max(0, $net - $received))<tr><td>{{ $record->invoice_no }}</td><td>{{ $record->student->name }}</td><td>{{ $record->student->admission_no }}</td><td>{{ $record->student->academyClass?->name ?? '—' }} {{ $record->student->academyClass?->section ?? '' }}</td><td>{{ $record->invoice_month->format('M Y') }}</td><td>PKR {{ number_format($net, 2) }}</td><td>PKR {{ number_format($received, 2) }}</td><td>PKR {{ number_format($balance, 2) }}</td><td>{{ Str::headline($record->status) }}</td></tr>@endforeach
            </tbody></table>
        @else
            <table class="print-table"><thead><tr>@foreach ($definition['columns'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead><tbody>
            @foreach ($records as $record)<tr>@foreach ($definition['columns'] as $key => $label)@php($value = data_get($record, $key))<td>@if ($key === 'photo_path')@if ($value)<img class="print-student-photo" src="{{ asset('storage/'.$value) }}" alt="{{ data_get($record, 'name', 'Student') }}">@else—@endif @elseif (str_ends_with($key, 'status')){{ Str::headline((string) $value) }}@elseif ($value instanceof \DateTimeInterface){{ $value->format('d M Y') }}@elseif (in_array($key, ['monthly_fee', 'admission_fee', 'amount', 'monthly_salary'], true))PKR {{ number_format((float) $value, 2) }}@else{{ $value ?: '—' }}@endif</td>@endforeach</tr>@endforeach
            </tbody></table>
        @endif
    </main>
    <script>window.addEventListener('load',()=>window.print());</script>
</body>
</html>

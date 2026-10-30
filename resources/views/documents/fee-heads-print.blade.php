<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fee heads · MSZ ERP</title>
    <link rel="stylesheet" href="{{ asset('css/academy.css') }}">
</head>
<body class="print-page">
<header class="print-toolbar">
    <a href="{{ route('fee-heads.index', $scope) }}">← Back to fee heads</a>
    <button onclick="window.print()">Print fee heads</button>
</header>
<main class="print-document">
    <header class="print-document-heading">
        <div><span>MSZ ERP · RECURRING CHARGES · {{ strtoupper($scope) }}</span><h1>Fee heads register</h1><p>Search: “{{ $search }}”</p></div>
        <div><strong>{{ $heads->count() }}</strong><small>matching fee heads</small></div>
    </header>
    <table class="print-table">
        <thead><tr><th>Fee head</th><th>Applies to</th><th>Monthly amount</th><th>Status</th></tr></thead>
        <tbody>
        @foreach ($heads as $head)
            <tr>
                <td>{{ $head->name }}</td>
                <td>
                    @if ($scope === 'class')
                        {{ $head->academyClass?->name ?? 'Class removed' }} · {{ $head->academyClass?->section ?? '—' }}
                    @elseif ($scope === 'student')
                        {{ $head->student?->name ?? 'Student removed' }} · {{ $head->student?->admission_no ?? '' }}
                    @else
                        {{ auth()->user()->academy?->name }} · All active students
                    @endif
                </td>
                <td>PKR {{ number_format((float) $head->amount, 2) }}</td>
                <td>{{ $head->active ? 'Active' : 'Inactive' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</main>
<script>window.addEventListener('load',()=>window.print());</script>
</body>
</html>

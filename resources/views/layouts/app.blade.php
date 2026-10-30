<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · MSZ ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/academy.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">M</span><span><strong>MSZ ERP</strong><small>ACADEMY MANAGEMENT</small></span></a>
        <div class="nav-label">Workspace</div>
        <nav class="nav-list">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>▦</span>Overview</a>
            @foreach (['inquiries', 'students', 'classes'] as $slug)
                @if (auth()->user()->hasFeature(config('academy_access.module_features.'.$slug)))
                    @php($module = config('academy.modules.'.$slug))
                    <a class="nav-link {{ request()->routeIs('modules.*') && request()->route('module') === $slug ? 'active' : '' }}" href="{{ route('modules.index', $slug) }}"><span>{{ $module['icon'] }}</span>{{ $module['label'] }}</a>
                @endif
            @endforeach
            @php($feeMenuOpen = request()->routeIs('reports', 'fees.*', 'fee-heads.*') || (request()->routeIs('modules.*') && in_array(request()->route('module'), ['fees', 'payments', 'students'], true)))
            @if (collect(['fees.invoices', 'fees.collect', 'fees.receipts', 'fees.single', 'fees.bulk', 'fees.modify', 'fees.monthly_students', 'fees.defaulters', 'fees.reports.pending', 'fees.reports.collection_date', 'fees.reports.collection_month', 'fees.reports.summary_date', 'fees.reports.recovery', 'fees.reports.adjust', 'fee_heads.class', 'fee_heads.academy', 'fee_heads.student'])->contains(fn ($feature) => auth()->user()->hasFeature($feature)))
            <details class="nav-group" @if ($feeMenuOpen) open @endif>
                <summary class="nav-link {{ $feeMenuOpen ? 'active' : '' }}"><span>▤</span>Fee <span class="nav-chevron">⌄</span></summary>
                <div class="nav-sublist">
                    @if (auth()->user()->hasFeature('fees.collect'))<a class="nav-sublink" href="{{ route('modules.create', 'payments') }}">Collect Fee</a>@endif
                    @if (auth()->user()->hasFeature('fees.reports.pending'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'pending']) }}">Pending Balance Report</a>@endif
                    @if (auth()->user()->hasFeature('fees.reports.collection_date'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'date']) }}">Fee Collection Detail By Date</a>@endif
                    @if (auth()->user()->hasFeature('fees.reports.collection_month'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'month']) }}">Fee Collection Detail By Month</a>@endif
                    @if (auth()->user()->hasFeature('fees.reports.summary_date'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'summary']) }}">Fee Collection Summary By Date</a>@endif
                    @if (auth()->user()->hasFeature('fees.reports.recovery'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'recovery']) }}">Fee Recovery Report</a>@endif
                    @if (auth()->user()->hasFeature('fees.invoices'))<a class="nav-sublink" href="{{ route('modules.index', 'fees') }}">Fee Desk / Bill List</a>@endif
                    @if (auth()->user()->hasFeature('fees.receipts'))<a class="nav-sublink" href="{{ route('modules.index', 'payments') }}">Fee Receipts</a>@endif
                    @if (auth()->user()->hasFeature('fees.monthly_students'))<a class="nav-sublink" href="{{ route('fees.monthly-students') }}">Monthly Fee List</a>@endif
                    @if (auth()->user()->hasFeature('fees.defaulters'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'defaulters']) }}">Basic / Defaulters Fee List</a>@endif
                    @if (auth()->user()->hasFeature('fees.single'))<a class="nav-sublink" href="{{ route('modules.create', 'fees') }}">Generate Single Fee Bill</a>@endif
                    @if (auth()->user()->hasFeature('fees.bulk'))<a class="nav-sublink" href="{{ route('fees.bulk.form') }}">Generate Bulk Fee Bills</a>@endif
                    @if (auth()->user()->hasFeature('fees.modify'))<a class="nav-sublink" href="{{ route('modules.index', 'fees') }}">Rollback / Modify Fee Bill</a>@endif
                    @if (auth()->user()->hasFeature('fees.reports.adjust'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'adjust']) }}">Adjust Monthly Generated Fee Bills</a>@endif
                    @if (auth()->user()->hasFeature('fee_heads.class') || auth()->user()->hasFeature('fee_heads.academy') || auth()->user()->hasFeature('fee_heads.student'))
                    <details class="nav-group nested-nav-group" @if (request()->routeIs('fee-heads.*')) open @endif><summary class="nav-sublink">Manage Fee Heads <span class="nav-chevron">⌄</span></summary><div class="nav-sublist">
                        @if (auth()->user()->hasFeature('fee_heads.class'))<a class="nav-sublink" href="{{ route('fee-heads.index', 'class') }}">Class / Section Wise</a>@endif
                        @if (auth()->user()->hasFeature('fee_heads.academy'))<a class="nav-sublink" href="{{ route('fee-heads.index', 'academy') }}">School Wise</a>@endif
                        @if (auth()->user()->hasFeature('fee_heads.student'))<a class="nav-sublink" href="{{ route('fee-heads.index', 'student') }}">Student Wise</a>@endif
                    </div></details>
                    @endif
                </div>
            </details>
            @endif
            @foreach (['attendance', 'exams', 'results', 'staff', 'accounts', 'messages'] as $slug)
                @if (auth()->user()->hasFeature(config('academy_access.module_features.'.$slug)))
                    @php($module = config('academy.modules.'.$slug))
                    <a class="nav-link {{ request()->routeIs('modules.*') && request()->route('module') === $slug ? 'active' : '' }}" href="{{ route('modules.index', $slug) }}"><span>{{ $module['icon'] }}</span>{{ $module['label'] }}</a>
                @endif
            @endforeach
            @if (auth()->user()->hasFeature('reports.dashboard'))<a class="nav-link {{ request()->routeIs('reports') && ! request()->query('fee_report') ? 'active' : '' }}" href="{{ route('reports') }}"><span>▥</span>Reports</a>@endif
        </nav>
        <div class="sidebar-bottom">
            <div class="support-box"><strong>Made for your academy</strong><p>Pakistan-ready student, fee and attendance records.</p><span>PKR · Asia/Karachi</span></div>
            <div class="user-card">@if (auth()->user()->photo_path)<img class="user-avatar" src="{{ asset('storage/'.auth()->user()->photo_path) }}" alt="">@else<div class="user-avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</div>@endif<div class="user-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(str_replace('_', ' ', auth()->user()->role ?? 'admin')) }}</small></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" title="Sign out" aria-label="Sign out">↗</button></form></div>
        </div>
    </aside>
    <main class="main-area">
        <header class="topbar"><button class="menu-toggle" id="menuToggle" aria-label="Open navigation">☰</button><div class="crumb">Academy workspace <span>/</span> <strong>{{ $title ?? 'Dashboard' }}</strong></div><div class="topbar-right"><span class="campus-chip">@if (auth()->user()->academy?->logo_path)<img class="campus-logo" src="{{ asset('storage/'.auth()->user()->academy->logo_path) }}" alt="">@else⌖ @endif {{ auth()->user()->academy?->name ?? 'Academy' }}</span><span class="top-date">{{ now()->timezone('Asia/Karachi')->format('D, d M Y') }}</span>@if (auth()->user()->photo_path)<img class="tiny-avatar" src="{{ asset('storage/'.auth()->user()->photo_path) }}" alt="">@else<div class="tiny-avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</div>@endif</div></header>
        <div class="page-content">
            @if (session('success'))<div class="flash success">✓ {{ session('success') }}</div>@endif
            @if ($errors->any())<div class="flash error">Please check the form and correct the highlighted details.</div>@endif
            @yield('content')
        </div>
    </main>
</div>
<div class="sidebar-scrim" id="sidebarScrim"></div>
<script src="{{ asset('js/academy.js') }}" defer></script>
@stack('scripts')
</body>
</html>

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
            @if (collect(['inquiries', 'students', 'classes'])->contains(fn ($feature) => auth()->user()->hasFeature($feature)))<div class="nav-section-label">Academy</div>@endif
            @foreach (['inquiries', 'students', 'classes'] as $slug)
                @if (auth()->user()->hasFeature(config('academy_access.module_features.'.$slug)))
                    @php($module = config('academy.modules.'.$slug))
                    <a class="nav-link {{ (request()->routeIs('modules.*') && request()->route('module') === $slug) || ($slug === 'students' && request()->routeIs('students.workspace')) ? 'active' : '' }}" href="{{ route('modules.index', $slug) }}"><span>{{ $module['icon'] }}</span>{{ $module['label'] }}</a>
                @endif
            @endforeach
            @php($feeMenuOpen = request()->routeIs('reports', 'fees.*', 'fee-heads.*', 'students.workspace') || (request()->routeIs('modules.*') && in_array(request()->route('module'), ['fees', 'payments', 'students'], true)))
            @if (collect(['fees.invoices', 'fees.collect', 'fees.receipts', 'fees.single', 'fees.bulk', 'fees.modify', 'fees.monthly_students', 'fees.defaulters', 'fees.reports.pending', 'fees.reports.collection_date', 'fees.reports.collection_month', 'fees.reports.summary_date', 'fees.reports.recovery', 'fees.reports.adjust', 'fee_heads.class', 'fee_heads.academy', 'fee_heads.student'])->contains(fn ($feature) => auth()->user()->hasFeature($feature)))
            <details class="nav-group" @if ($feeMenuOpen) open @endif>
                <summary class="nav-link {{ $feeMenuOpen ? 'active' : '' }}"><span>▤</span>Fee <span class="nav-chevron">⌄</span></summary>
                <div class="nav-sublist">
                    <a class="nav-sublink {{ request()->routeIs('fees.desk') ? 'active' : '' }}" href="{{ route('fees.desk') }}">Fee workspace</a>
                    @if (auth()->user()->hasFeature('fees.collect'))<a class="nav-sublink {{ request()->routeIs('modules.create') && request()->route('module') === 'payments' ? 'active' : '' }}" href="{{ route('modules.create', 'payments') }}">Record a payment</a>@endif
                    @if (auth()->user()->hasFeature('fees.invoices'))<a class="nav-sublink {{ request()->routeIs('modules.index') && request()->route('module') === 'fees' ? 'active' : '' }}" href="{{ route('modules.index', 'fees') }}">Find a bill</a>@endif
                    @if (auth()->user()->hasFeature('fees.monthly_students') || auth()->user()->hasFeature('fees.receipts') || auth()->user()->hasFeature('fees.single') || auth()->user()->hasFeature('fees.bulk') || auth()->user()->hasFeature('fees.defaulters') || auth()->user()->hasFeature('fees.reports.pending') || auth()->user()->hasFeature('fees.reports.collection_date') || auth()->user()->hasFeature('fees.reports.collection_month') || auth()->user()->hasFeature('fees.reports.summary_date') || auth()->user()->hasFeature('fees.reports.recovery') || auth()->user()->hasFeature('fees.reports.adjust') || auth()->user()->hasFeature('fees.modify') || auth()->user()->hasFeature('fee_heads.class') || auth()->user()->hasFeature('fee_heads.academy') || auth()->user()->hasFeature('fee_heads.student'))
                    <details class="nav-group nested-nav-group" @if (request()->routeIs('reports', 'fees.bulk.*', 'fees.monthly-students', 'fee-heads.*') || (request()->routeIs('modules.create') && in_array(request()->route('module'), ['fees', 'payments'], true))) open @endif><summary class="nav-sublink">More fee tools <span class="nav-chevron">⌄</span></summary><div class="nav-sublist">
                        @if (auth()->user()->hasFeature('fees.single'))<a class="nav-sublink" href="{{ route('modules.create', 'fees') }}">Create one bill</a>@endif
                        @if (auth()->user()->hasFeature('fees.bulk'))<a class="nav-sublink" href="{{ route('fees.bulk.form') }}">Create monthly bills</a>@endif
                        @if (auth()->user()->hasFeature('fees.monthly_students'))<a class="nav-sublink" href="{{ route('fees.monthly-students') }}">Student fee list</a>@endif
                        @if (auth()->user()->hasFeature('fees.receipts'))<a class="nav-sublink" href="{{ route('modules.index', 'payments') }}">Payment receipts</a>@endif
                        @if (auth()->user()->hasFeature('fees.reports.pending'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'pending']) }}">Unpaid balances</a>@elseif (auth()->user()->hasFeature('fees.defaulters'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'defaulters']) }}">Unpaid balances</a>@endif
                        @if (auth()->user()->hasFeature('fees.reports.collection_date'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'date']) }}">Collections by date</a>@endif
                        @if (auth()->user()->hasFeature('fees.reports.collection_month'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'month']) }}">Collections by month</a>@endif
                        @if (auth()->user()->hasFeature('fees.reports.summary_date'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'summary']) }}">Collection summary</a>@endif
                        @if (auth()->user()->hasFeature('fees.reports.recovery'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'recovery']) }}">Fee recovery report</a>@endif
                        @if (auth()->user()->hasFeature('fees.reports.adjust'))<a class="nav-sublink" href="{{ route('reports', ['fee_report' => 'adjust']) }}">Adjust monthly bills</a>@endif
                        @if (auth()->user()->hasFeature('fees.modify'))<a class="nav-sublink" href="{{ route('modules.index', 'fees') }}">Edit / remove a bill</a>@endif
                        @if (auth()->user()->hasFeature('fee_heads.academy') || auth()->user()->hasFeature('fee_heads.class') || auth()->user()->hasFeature('fee_heads.student'))
                        <details class="nav-group nested-nav-group" @if (request()->routeIs('fee-heads.*')) open @endif><summary class="nav-sublink">Optional extra charges <span class="nav-chevron">⌄</span></summary><div class="nav-sublist">
                            @foreach (['academy' => 'Whole academy', 'class' => 'A class / section', 'student' => 'One student'] as $scope => $label)
                                @if (auth()->user()->hasFeature('fee_heads.'.$scope))<a class="nav-sublink" href="{{ route('fee-heads.index', $scope) }}">{{ $label }}</a>@endif
                            @endforeach
                        </div></details>
                        @endif
                    </div></details>
                    @endif
                </div>
            </details>
            @endif
            @if (collect(['attendance', 'staff', 'accounts', 'messages'])->contains(fn ($feature) => auth()->user()->hasFeature($feature)))<div class="nav-section-label">Operations</div>@endif
            @foreach (['attendance', 'staff', 'accounts', 'messages'] as $slug)
                @if (auth()->user()->hasFeature(config('academy_access.module_features.'.$slug)))
                    @php($module = config('academy.modules.'.$slug))
                    <a class="nav-link {{ request()->routeIs('modules.*') && request()->route('module') === $slug ? 'active' : '' }}" href="{{ route('modules.index', $slug) }}"><span>{{ $module['icon'] }}</span>{{ $module['label'] }}</a>
                @endif
            @endforeach
            @if (auth()->user()->hasFeature('exams') || auth()->user()->hasFeature('results'))
                <div class="nav-section-label">Examination tools</div>
                @if (auth()->user()->hasFeature('exams'))<a class="nav-link {{ request()->routeIs('examination.index', 'examination.award-lists.*', 'examination.marks.*') ? 'active' : '' }}" href="{{ route('examination.index') }}"><span>✎</span>Award lists</a>@endif
                @if (auth()->user()->hasFeature('results'))<a class="nav-link {{ request()->routeIs('examination.result-sheet', 'examination.result-card', 'examination.send-result-sms') ? 'active' : '' }}" href="{{ route('examination.result-sheet') }}"><span>▥</span>Class-wise results</a>@endif
            @endif
            @if (auth()->user()->hasFeature('reports.dashboard'))<a class="nav-link {{ request()->routeIs('reports') && ! request()->query('fee_report') ? 'active' : '' }}" href="{{ route('reports') }}"><span>▥</span>Reports</a>@endif
        </nav>
        <div class="sidebar-bottom">
            <div class="support-box"><span class="support-orbit">✳</span><strong>Built for your campus</strong><p>Admissions, learning and accounts in one workspace.</p><span>PKR <i>·</i> Asia/Karachi</span></div>
            <div class="user-card"><a class="user-profile-link" href="{{ route('profile.show') }}">@if (auth()->user()->photo_path)<img class="user-avatar" src="{{ asset('storage/'.auth()->user()->photo_path) }}" alt="">@else<div class="user-avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</div>@endif<div class="user-copy"><strong>{{ auth()->user()->name }}</strong><small>My profile</small></div></a><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" title="Sign out" aria-label="Sign out">↗</button></form></div>
        </div>
    </aside>
    <main class="main-area">
        <header class="topbar"><button class="menu-toggle" id="menuToggle" aria-label="Open navigation">☰</button><div class="topbar-title"><span class="topbar-kicker">ACADEMY WORKSPACE</span><div class="crumb"><strong>{{ $title ?? 'Dashboard' }}</strong><span class="crumb-date">{{ now()->timezone('Asia/Karachi')->format('l · d M Y') }}</span></div></div><div class="topbar-right"><span class="campus-chip">@if (auth()->user()->academy?->logo_path)<img class="campus-logo" src="{{ asset('storage/'.auth()->user()->academy->logo_path) }}" alt="">@else<span class="campus-pin">⌖</span>@endif <b>{{ auth()->user()->academy?->name ?? 'Academy' }}</b></span>@if (auth()->user()->photo_path)<img class="tiny-avatar" src="{{ asset('storage/'.auth()->user()->photo_path) }}" alt="">@else<div class="tiny-avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</div>@endif</div></header>
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

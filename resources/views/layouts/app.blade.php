<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} · Ustaad Academy ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/academy.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark">U</span><span><strong>ustaad</strong><small>ACADEMY ERP</small></span></a>
        <div class="nav-label">Workspace</div>
        <nav class="nav-list">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>▦</span>Overview</a>
            @foreach (['inquiries', 'students', 'classes', 'fees', 'payments', 'attendance', 'exams', 'results', 'staff', 'accounts', 'messages'] as $slug)
                @php($module = config('academy.modules.'.$slug))
                <a class="nav-link {{ request()->routeIs('modules.*') && request()->route('module') === $slug ? 'active' : '' }}" href="{{ route('modules.index', $slug) }}"><span>{{ $module['icon'] }}</span>{{ $module['label'] }}</a>
            @endforeach
            <a class="nav-link {{ request()->routeIs('reports') ? 'active' : '' }}" href="{{ route('reports') }}"><span>▥</span>Reports</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="support-box"><strong>Made for your academy</strong><p>Pakistan-ready student, fee and attendance records.</p><span>PKR · Asia/Karachi</span></div>
            <div class="user-card"><div class="user-avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</div><div class="user-copy"><strong>{{ auth()->user()->name }}</strong><small>{{ ucfirst(str_replace('_', ' ', auth()->user()->role ?? 'admin')) }}</small></div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" title="Sign out" aria-label="Sign out">↗</button></form></div>
        </div>
    </aside>
    <main class="main-area">
        <header class="topbar"><button class="menu-toggle" id="menuToggle" aria-label="Open navigation">☰</button><div class="crumb">Academy workspace <span>/</span> <strong>{{ $title ?? 'Dashboard' }}</strong></div><div class="topbar-right"><span class="campus-chip">⌖ {{ auth()->user()->academy?->name ?? 'Academy' }}</span><span class="top-date">{{ now()->timezone('Asia/Karachi')->format('D, d M Y') }}</span><div class="tiny-avatar">{{ collect(explode(' ', auth()->user()->name))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</div></div></header>
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

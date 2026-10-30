<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Academies' }} · MSZ ERP Super Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/academy.css') }}">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="{{ route('superadmin.academies') }}">
            <span class="brand-mark">M</span>
            <span><strong>MSZ ERP</strong><small>SYSTEM CONTROL</small></span>
        </a>
        <div class="nav-label">Platform</div>
        <nav class="nav-list">
            <a class="nav-link active" href="{{ route('superadmin.academies') }}"><span>▦</span>Academies</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="support-box">
                <strong>Super Admin</strong>
                <p>Manage academy accounts and their administrators.</p>
                <span>Platform access only</span>
            </div>
            <div class="user-card">
                @if (auth()->user()->photo_path)<img class="user-avatar" src="{{ asset('storage/'.auth()->user()->photo_path) }}" alt="">@else<div class="user-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>@endif
                <div class="user-copy">
                    <strong>{{ auth()->user()->name }}</strong>
                    <small>Super Administrator</small>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout" title="Sign out" aria-label="Sign out">↗</button>
                </form>
            </div>
        </div>
    </aside>
    <main class="main-area">
        <header class="topbar">
            <div class="crumb">Platform <span>/</span> <strong>{{ $title ?? 'Academies' }}</strong></div>
            <div class="topbar-right">
                <span class="campus-chip">Super Admin</span>
                <span class="top-date">{{ now()->timezone('Asia/Karachi')->format('D, d M Y') }}</span>
            </div>
        </header>
        <div class="page-content">
            @if (session('success'))
                <div class="flash success">✓ {{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="flash error">Please check the form and correct the highlighted details.</div>
            @endif

            @yield('content')
        </div>
    </main>
</div>
</body>
</html>

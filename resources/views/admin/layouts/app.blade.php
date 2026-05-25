<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'Dashboard') — ArtHub Admin</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg:       #0d1117;
            --surface:  #161b22;
            --border:   #30363d;
            --gold:     #c9a84c;
            --gold-dim: #8a6d2e;
            --text:     #e6edf3;
            --muted:    #8b949e;
            --danger:   #da3633;
            --success:  #238636;
            --sidebar-w: 220px;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
               background: var(--bg); color: var(--text); min-height: 100vh;
               display: flex; font-size: 14px; }
        .sidebar {
            width: var(--sidebar-w); background: var(--surface); border-right: 1px solid var(--border);
            display: flex; flex-direction: column; position: fixed; top: 0; left: 0;
            height: 100vh; z-index: 100; overflow-y: auto;
        }
        .sidebar-brand { padding: 20px 16px 16px; border-bottom: 1px solid var(--border); }
        .sidebar-brand .logo { font-size: 22px; font-weight: 700; color: var(--gold); letter-spacing: -0.5px; }
        .sidebar-brand .sub  { font-size: 10px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 2px; }
        .sidebar-nav { flex: 1; padding: 12px 8px; }
        .nav-section { font-size: 10px; color: var(--muted); text-transform: uppercase;
                       letter-spacing: 1px; padding: 12px 8px 6px; }
        .nav-link {
            display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 6px;
            color: var(--muted); text-decoration: none; font-size: 13.5px; transition: all .15s; margin-bottom: 2px;
        }
        .nav-link:hover { background: rgba(201,168,76,.08); color: var(--text); }
        .nav-link.active { background: rgba(201,168,76,.15); color: var(--gold); font-weight: 500; }
        .nav-link .icon { width: 16px; text-align: center; flex-shrink: 0; }
        .sidebar-footer { padding: 12px 8px; border-top: 1px solid var(--border); }
        .sidebar-user { display: flex; align-items: center; gap: 9px; padding: 8px 10px 10px; }
        .sidebar-user .avatar {
            width: 30px; height: 30px; border-radius: 50%; background: var(--gold-dim);
            color: #fff; display: flex; align-items: center; justify-content: center;
            font-size: 12px; font-weight: 600; flex-shrink: 0;
        }
        .sidebar-user .info .name { font-size: 13px; font-weight: 500; }
        .sidebar-user .info .role { font-size: 11px; color: var(--muted); }
        .main { margin-left: var(--sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar {
            height: 56px; background: var(--surface); border-bottom: 1px solid var(--border);
            display: flex; align-items: center; padding: 0 24px; gap: 12px;
            position: sticky; top: 0; z-index: 50;
        }
        .topbar h1 { font-size: 16px; font-weight: 600; flex: 1; }
        .topbar .actions { display: flex; gap: 8px; }
        .content { padding: 24px; flex: 1; }
        .flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px; display: flex; align-items: center; gap: 8px; }
        .flash.success { background: rgba(35,134,54,.15); border: 1px solid rgba(35,134,54,.4); color: #3fb950; }
        .flash.error   { background: rgba(218,54,51,.15);  border: 1px solid rgba(218,54,51,.4);  color: #f85149; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 20px; display: flex; flex-direction: column; gap: 8px; }
        .stat-card .label { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: var(--gold); }
        .stat-card .sub   { font-size: 12px; color: var(--muted); }
        .card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; overflow: hidden; }
        .card-header { padding: 16px 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .card-header h2 { font-size: 15px; font-weight: 600; }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { border-bottom: 1px solid var(--border); }
        th { text-align: left; padding: 10px 16px; font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .6px; white-space: nowrap; }
        td { padding: 12px 16px; border-bottom: 1px solid rgba(48,54,61,.6); font-size: 13.5px; vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,.02); }
        .td-img { width: 44px; height: 44px; object-fit: cover; border-radius: 6px; background: var(--border); display: block; }
        .badge { display: inline-flex; align-items: center; padding: 3px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 500; }
        .badge-green  { background: rgba(35,134,54,.2);  color: #3fb950; }
        .badge-yellow { background: rgba(201,168,76,.2); color: var(--gold); }
        .badge-red    { background: rgba(218,54,51,.2);  color: #f85149; }
        .badge-gray   { background: rgba(139,148,158,.15); color: var(--muted); }
        .badge-blue   { background: rgba(88,166,255,.15); color: #79c0ff; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: all .15s; }
        .btn-primary { background: var(--gold); color: #0d1117; }
        .btn-primary:hover { background: #d4b45a; }
        .btn-ghost { background: transparent; color: var(--muted); border: 1px solid var(--border); }
        .btn-ghost:hover { color: var(--text); border-color: var(--muted); }
        .btn-danger { background: rgba(218,54,51,.15); color: #f85149; border: 1px solid rgba(218,54,51,.3); }
        .btn-danger:hover { background: rgba(218,54,51,.3); }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr)); gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }
        label { font-size: 12.5px; color: var(--muted); }
        input, select, textarea { background: var(--bg); border: 1px solid var(--border); color: var(--text); border-radius: 6px; padding: 9px 12px; font-size: 13.5px; width: 100%; transition: border-color .15s; font-family: inherit; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--gold); }
        textarea { resize: vertical; min-height: 90px; }
        .form-actions { display: flex; gap: 10px; margin-top: 8px; }
        .field-error { font-size: 12px; color: #f85149; }
        .search-wrap { position: relative; }
        .search-wrap input { padding-left: 34px; max-width: 280px; }
        .search-wrap::before { content: "🔍"; position: absolute; left: 10px; top: 50%; transform: translateY(-50%); font-size: 12px; }
        .pagination-wrap { padding: 16px 20px; border-top: 1px solid var(--border); }
        .empty { padding: 48px 24px; text-align: center; color: var(--muted); }
        .empty .icon { font-size: 36px; margin-bottom: 12px; }
        .empty p { font-size: 14px; }
        .form-card { background: var(--surface); border: 1px solid var(--border); border-radius: 10px; padding: 24px; }
        .form-card h2 { font-size: 15px; font-weight: 600; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
    </style>
    @stack('styles')
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="logo">ArtHub</div>
        <div class="sub">Admin Panel</div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">General</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
            <span class="icon">◈</span> Dashboard
        </a>
        <div class="nav-section">Catálogo</div>
        <a href="{{ route('admin.obras.index') }}" class="nav-link {{ request()->routeIs('admin.obras.*') ? 'active' : '' }}">
            <span class="icon">🖼</span> Obras
        </a>
        <a href="{{ route('admin.artistas.index') }}" class="nav-link {{ request()->routeIs('admin.artistas.*') ? 'active' : '' }}">
            <span class="icon">✦</span> Artistas
        </a>
        <a href="{{ route('admin.certificados.index') }}" class="nav-link {{ request()->routeIs('admin.certificados.*') ? 'active' : '' }}">
            <span class="icon">◎</span> Certificados
        </a>
        <div class="nav-section">Acceso</div>
        <a href="{{ route('admin.usuarios.index') }}" class="nav-link {{ request()->routeIs('admin.usuarios.*') ? 'active' : '' }}">
            <span class="icon">◉</span> Usuarios
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</div>
            <div class="info">
                <div class="name">{{ auth()->user()->name ?? 'Admin' }}</div>
                <div class="role">{{ auth()->user()->role ?? 'admin' }}</div>
            </div>
        </div>
        <form action="{{ route('admin.logout') }}" method="POST" style="padding: 0 10px 4px;">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm" style="width:100%;justify-content:center;">Cerrar sesión</button>
        </form>
    </div>
</aside>

<div class="main">
    <div class="topbar">
        <h1>@yield('page-title', 'Dashboard')</h1>
        <div class="actions">@yield('topbar-actions')</div>
    </div>
    <div class="content">
        @if(session('success'))
            <div class="flash success">✓ {{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="flash error">✕ {{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
</div>

@stack('scripts')
</body>
</html>

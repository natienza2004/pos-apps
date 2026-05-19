<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'StockFlow POS & Inventory' }}</title>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg: #f8fafc;
            --panel: #ffffff;
            --line: #d9dee7;
            --muted: #64748b;
            --ink: #020617;
            --blue: #4f46e5;
            --green: #009f6b;
            --orange: #f59e0b;
            --red: #ef4444;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--bg); color: var(--ink); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; font-size: 14px; }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        .app { min-height: 100vh; display: grid; grid-template-columns: 238px 1fr; }
        .sidebar { position: fixed; inset: 0 auto 0 0; width: 238px; background: #fff; border-right: 1px solid var(--line); z-index: 20; }
        .brand { height: 76px; display: flex; align-items: center; gap: 10px; padding: 0 18px; font-weight: 800; font-size: 16px; }
        .brand-mark { width: 30px; height: 30px; border-radius: 8px; display: grid; place-items: center; background: #4f36f5; color: #fff; font-weight: 900; }
        .nav { padding: 18px 10px; }
        .nav-title { margin: 22px 0 8px; padding: 0 2px; color: #94a3b8; font-size: 10px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; }
        .nav a { height: 44px; display: flex; align-items: center; gap: 12px; padding: 0 12px; border-radius: 7px; color: #475569; font-size: 14px; }
        .nav a.active { background: #e9edff; color: #3b27ff; font-weight: 700; }
        .nav svg { width: 18px; height: 18px; }
        .topbar { position: fixed; left: 238px; right: 0; top: 0; height: 76px; display: flex; align-items: center; justify-content: flex-end; gap: 16px; padding: 0 24px; background: #fff; z-index: 15; box-shadow: inset 0 -1px 0 var(--line); }
        .collapse { position: absolute; left: -18px; width: 30px; height: 30px; border: 1px solid var(--line); border-radius: 8px; background: #fff; color: #94a3b8; display: grid; place-items: center; }
        .search { width: 260px; height: 35px; border: 0; border-radius: 999px; background: #f6f8fb; color: #64748b; padding: 0 14px 0 42px; outline: none; }
        .search-wrap { position: relative; }
        .search-wrap svg { position: absolute; left: 17px; top: 9px; width: 16px; height: 16px; color: #94a3b8; }
        .user { display: flex; align-items: center; gap: 18px; font-size: 12px; }
        .avatar { width: 34px; height: 34px; border-radius: 999px; background: #e7e9ff; color: #4f46e5; display: grid; place-items: center; }
        .notification { position: relative; }
        .notification-btn { width: 38px; height: 38px; border: 1px solid var(--line); border-radius: 8px; background: #fff; color: #475569; display: grid; place-items: center; cursor: pointer; position: relative; }
        .notification-btn svg { width: 17px; height: 17px; }
        .notification-count { position: absolute; top: -7px; right: -7px; min-width: 19px; height: 19px; padding: 0 5px; border-radius: 999px; background: var(--red); color: #fff; border: 2px solid #fff; display: grid; place-items: center; font-size: 10px; font-weight: 900; }
        .notification-menu { position: absolute; top: 48px; right: 0; width: 340px; max-width: calc(100vw - 40px); background: #fff; border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 18px 46px rgba(15,23,42,.18); overflow: hidden; display: none; z-index: 90; }
        .notification.open .notification-menu { display: block; }
        .notification-head { padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e8ecf2; font-weight: 900; }
        .notification-list { max-height: 330px; overflow: auto; }
        .notification-item { display: grid; grid-template-columns: 1fr auto; gap: 10px; padding: 13px 16px; border-bottom: 1px solid #eef2f7; }
        .notification-item:last-child { border-bottom: 0; }
        .notification-item strong { display: block; font-size: 13px; }
        .notification-item span { display: block; margin-top: 3px; color: #64748b; font-size: 11px; }
        .notification-empty { padding: 24px 16px; color: #64748b; text-align: center; }
        .logout-form { margin: 0; }
        .logout-btn { width: 34px; height: 34px; border: 1px solid var(--line); border-radius: 8px; background: #fff; color: #64748b; display: grid; place-items: center; cursor: pointer; }
        .logout-btn svg { width: 15px; height: 15px; }
        .content { grid-column: 2; padding: 112px 32px 48px; }
        .container { width: min(1220px, 100%); margin: 0 auto; }
        .page-head { display: flex; align-items: start; justify-content: space-between; gap: 20px; margin-bottom: 28px; }
        h1 { margin: 0; font-size: 31px; line-height: 1.1; letter-spacing: -.03em; }
        .sub { margin-top: 8px; color: #64748b; }
        .grid { display: grid; gap: 22px; }
        .cards-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .cards-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .two { grid-template-columns: 1fr 1fr; }
        .main-side { grid-template-columns: 1fr 330px; }
        .card { background: var(--panel); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 2px 4px rgba(15, 23, 42, .08); }
        .metric { min-height: 110px; padding: 22px 24px; border-left: 4px solid transparent; }
        .metric .icon { width: 34px; height: 34px; border-radius: 8px; display: grid; place-items: center; margin-bottom: 14px; }
        .icon { display: inline-grid; place-items: center; border-radius: 8px; }
        .icon svg { width: 16px; height: 16px; }
        .metric small { display: block; color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; }
        .metric strong { display: block; margin-top: 5px; font-size: 25px; letter-spacing: -.04em; }
        .metric span { color: #64748b; font-size: 12px; }
        .panel-head { padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e8ecf2; font-weight: 800; }
        .panel-body { padding: 20px 22px; }
        .btn { height: 36px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid transparent; border-radius: 7px; padding: 0 15px; background: #fff; color: #0f172a; font-weight: 800; cursor: pointer; }
        .btn svg { width: 15px; height: 15px; }
        .btn.primary { background: #4f36f5; color: #fff; }
        .btn.green { background: #00a872; color: #fff; }
        .btn.orange { background: #ea6a00; color: #fff; }
        .btn.ghost { border-color: var(--line); }
        .btn.small { height: 30px; padding: 0 11px; font-size: 12px; }
        .table-card { overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #f3f5f8; color: #64748b; font-size: 11px; text-align: left; text-transform: uppercase; letter-spacing: .08em; padding: 14px 18px; }
        td { padding: 15px 18px; border-top: 1px solid #e8ecf2; vertical-align: middle; }
        .money { font-weight: 900; color: #3b27ff; }
        .muted { color: #64748b; }
        .tiny { font-size: 11px; }
        .sheet-input { width: 92px; min-height: 34px; height: 34px; margin: 0; padding: 0 8px; border-color: transparent; background: transparent; font-weight: 900; text-align: right; }
        .sheet-input:focus { border-color: #4f36f5; background: #fff; box-shadow: 0 0 0 3px rgba(79,54,245,.12); }
        .sheet-input.in { color: #059669; }
        .sheet-input.out { color: #ea6a00; }
        .badge { display: inline-flex; align-items: center; gap: 5px; border-radius: 999px; padding: 4px 9px; font-size: 10px; font-weight: 900; text-transform: uppercase; }
        .badge.green { background: #dff8ed; color: #008d5e; }
        .badge.orange { background: #fff1c9; color: #b45309; }
        .badge.red { background: #ffe4e6; color: #dc2626; }
        .tag { border-radius: 5px; background: #f1f5f9; padding: 4px 8px; font-size: 11px; color: #334155; }
        .form-card { width: 540px; max-width: 100%; margin: 0 auto; padding: 32px; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 18px 22px; }
        label { display: block; color: #0f172a; font-size: 12px; font-weight: 600; }
        input, select, textarea { width: 100%; margin-top: 8px; border: 1px solid #bfc8d7; border-radius: 6px; background: #fff; min-height: 42px; padding: 0 12px; outline-color: #4f36f5; }
        textarea { min-height: 94px; padding: 12px; resize: vertical; }
        .full { grid-column: 1 / -1; }
        .form-actions { margin-top: 24px; padding-top: 22px; border-top: 1px solid #e8ecf2; display: flex; justify-content: flex-end; gap: 10px; }
        .toast { position: fixed; top: 90px; right: 24px; z-index: 80; border-radius: 9px; padding: 14px 18px; background: #0f172a; color: #fff; box-shadow: 0 10px 30px rgba(15,23,42,.2); }
        .modal-backdrop { position: fixed; inset: 0; z-index: 60; background: rgba(0,0,0,.55); display: none; align-items: center; justify-content: center; padding: 20px; }
        .modal-backdrop:target { display: flex; }
        .modal { width: 672px; max-width: 100%; background: #fff; border-radius: 12px; box-shadow: 0 24px 60px rgba(15,23,42,.28); overflow: hidden; }
        .modal-head { height: 64px; display: flex; align-items: center; justify-content: space-between; padding: 0 26px; border-bottom: 1px solid #e8ecf2; font-weight: 800; }
        .modal-body { padding: 24px 26px; }
        .tip { padding: 20px; background: #eef2ff; border: 1px solid #d7defe; color: #3520a6; border-radius: 9px; font-size: 12px; line-height: 1.5; }
        .danger-tip { background: #fff1f2; border-color: #fecdd3; color: #9f1239; }
        .progress { height: 12px; background: #eef2f7; border-radius: 999px; overflow: hidden; }
        .progress span { display: block; height: 100%; border-radius: inherit; background: var(--orange); }
        .empty { min-height: 220px; display: grid; place-items: center; text-align: center; color: #94a3b8; }
        .method { margin-top: 28px; padding: 28px; background: #fff9e8; border: 1px solid #fde68a; border-radius: 10px; color: #a34c00; }
        .audit { margin-top: 28px; padding: 32px; border-radius: 10px; background: #332789; color: #fff; display: flex; align-items: center; justify-content: space-between; gap: 20px; }
        .date-nav { height: 48px; min-width: 280px; display: inline-flex; align-items: center; justify-content: space-between; border: 1px solid var(--line); border-radius: 8px; background: #fff; overflow: hidden; }
        .date-nav a, .date-nav button { width: 44px; height: 46px; border: 0; background: transparent; color: #64748b; display: grid; place-items: center; cursor: pointer; }
        .date-nav strong { min-width: 130px; text-align: center; font-size: 16px; }
        .date-nav input { position: absolute; width: 1px; height: 1px; opacity: 0; pointer-events: none; }
        nav[role="navigation"] { display: flex; align-items: center; justify-content: space-between; gap: 12px; color: #64748b; font-size: 12px; }
        nav[role="navigation"] > div { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
        nav[role="navigation"] p { margin: 0; }
        nav[role="navigation"] a, nav[role="navigation"] span[aria-disabled="true"] span, nav[role="navigation"] span[aria-current="page"] span {
            min-width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--line);
            border-radius: 7px;
            background: #fff;
            padding: 0 10px;
            line-height: 1;
            font-weight: 800;
        }
        nav[role="navigation"] span[aria-current="page"] span { background: #4f36f5; border-color: #4f36f5; color: #fff; }
        nav[role="navigation"] span[aria-disabled="true"] span { color: #cbd5e1; background: #f8fafc; }
        nav[role="navigation"] svg { width: 15px !important; height: 15px !important; flex: 0 0 15px; }
        .chart-filter-bar { padding: 16px 22px; border-bottom: 1px solid #e8ecf2; display: grid; gap: 14px; background: #fbfcfe; }
        .filter-row { display: flex; align-items: end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
        .filter-group { display: flex; align-items: end; gap: 8px; flex-wrap: wrap; }
        .filter-label { font-size: 11px; color: #64748b; font-weight: 800; text-transform: uppercase; letter-spacing: .06em; }
        .filter-field { font-size: 11px; color: #64748b; font-weight: 700; }
        .filter-field input, .filter-field select { width: 136px; height: 34px; min-height: 34px; margin-top: 5px; }
        .filter-field select.month { width: 144px; }
        .filter-field select.year { width: 96px; }
        .quick-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        @media (max-width: 1000px) {
            .app { grid-template-columns: 1fr; }
            .sidebar { display: none; }
            .topbar { left: 0; }
            .content { grid-column: 1; padding: 104px 18px 36px; }
            .cards-4, .cards-3, .two, .main-side { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="app">
        <aside class="sidebar">
            <a href="{{ route('dashboard') }}" class="brand">
                <span class="brand-mark">S</span>
                <span>StockFlow</span>
            </a>
            @php
                $nav = [
                    ['Dashboard', 'dashboard', 'layout-dashboard'],
                    ['Products', 'products.index', 'package'],
                    ['Inventory', 'reports.active', 'clipboard-list'],
                    ['Stock In', 'stock.in', 'circle-plus'],
                    ['Stock Out', 'stock.out', 'circle-minus'],
                    ['Daily Reports', 'reports.daily', 'file-text'],
                    ['Monthly Reports', 'reports.monthly', 'bar-chart-3'],
                    ['Costing Summary', 'reports.costing', 'activity'],
                    ['Inventory History', 'reports.history', 'history'],
                ];
            @endphp
            <nav class="nav">
                @foreach ($nav as [$label, $route, $icon])
                    <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'active' : '' }}"><i data-lucide="{{ $icon }}"></i>{{ $label }}</a>
                @endforeach
                <div class="nav-title">Administration</div>
                <a href="{{ route('settings') }}" class="{{ request()->routeIs('settings') ? 'active' : '' }}"><i data-lucide="settings"></i>Settings</a>
            </nav>
        </aside>

        <header class="topbar">
            <span class="collapse"><i data-lucide="chevron-left"></i></span>
            @php
                $formatQuantity = fn ($value): string => rtrim(rtrim(number_format((float) $value, 3, '.', ','), '0'), '.');
                $stockAlerts = \App\Models\Product::query()
                    ->where('user_id', auth()->id())
                    ->whereColumn('current_stock', '<=', 'low_stock_threshold')
                    ->orderBy('current_stock')
                    ->orderBy('name')
                    ->get();
            @endphp
            <div class="user">
                <div class="notification" data-notification>
                    <button type="button" class="notification-btn" data-notification-button aria-label="Stock notifications" aria-expanded="false">
                        <i data-lucide="bell"></i>
                        @if ($stockAlerts->isNotEmpty())
                            <span class="notification-count">{{ $stockAlerts->count() }}</span>
                        @endif
                    </button>
                    <div class="notification-menu">
                        <div class="notification-head">
                            <span>Stock Alerts</span>
                            <a class="tiny" style="color:var(--blue)" href="{{ route('reports.active') }}">View Inventory</a>
                        </div>
                        <div class="notification-list">
                            @forelse ($stockAlerts as $product)
                                <a class="notification-item" href="{{ route('reports.active') }}">
                                    <div>
                                        <strong>{{ $product->name }}</strong>
                                        <span>{{ $product->sku }} &bull; {{ $formatQuantity($product->current_stock) }} {{ $product->unit }} left</span>
                                    </div>
                                    <span class="badge {{ (float) $product->current_stock <= 0 ? 'red' : 'orange' }}">{{ (float) $product->current_stock <= 0 ? 'Out' : 'Low' }}</span>
                                </a>
                            @empty
                                <div class="notification-empty">No low stock or out of stock items.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
                <span style="height:32px;border-left:1px solid var(--line)"></span>
                <div style="text-align:right"><strong>{{ auth()->user()->name }}</strong><br><span class="muted">{{ auth()->user()->role }}</span></div>
                <form method="POST" action="{{ route('logout') }}" class="logout-form">
                    @csrf
                    <button class="logout-btn" type="submit" title="Logout" aria-label="Logout"><i data-lucide="log-out"></i></button>
                </form>
            </div>
        </header>

        <main class="content">
            <div class="container">
                @if (session('success'))
                    <div data-toast class="toast">{{ session('success') }}</div>
                @endif
                @if ($errors->any())
                    <div data-toast class="toast" style="background:#b45309">{{ $errors->first() }}</div>
                @endif
                {{ $slot }}
            </div>
        </main>
    </div>
    <script>
        window.lucide?.createIcons();
        document.querySelectorAll('[data-toast]').forEach((toast) => setTimeout(() => toast.remove(), 3200));
        document.querySelectorAll('[data-notification]').forEach((notification) => {
            const button = notification.querySelector('[data-notification-button]');

            button?.addEventListener('click', (event) => {
                event.stopPropagation();
                const isOpen = notification.classList.toggle('open');
                button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('[data-notification].open').forEach((notification) => {
                notification.classList.remove('open');
                notification.querySelector('[data-notification-button]')?.setAttribute('aria-expanded', 'false');
            });
        });

        document.querySelectorAll('[data-auto-submit]').forEach((field) => {
            field.addEventListener('change', () => field.form?.submit());
        });

        document.querySelectorAll('form[method="POST"]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.submitted === 'true') {
                    event.preventDefault();
                    return;
                }

                form.dataset.submitted = 'true';
                form.querySelectorAll('button[type="submit"], button:not([type])').forEach((button) => {
                    button.disabled = true;
                    button.style.opacity = '.65';
                    button.style.cursor = 'not-allowed';
                    if (!button.dataset.originalText) {
                        button.dataset.originalText = button.innerHTML;
                    }
                    button.innerHTML = 'Processing...';
                });
            });
        });
    </script>
</body>
</html>

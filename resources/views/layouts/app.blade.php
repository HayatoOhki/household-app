<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        @yield('title', '家計簿')
    </title>

    <style>
        :root {
            --background: #f5f7fa;
            --surface: #ffffff;
            --surface-subtle: #f8fafc;
            --sidebar: #111827;
            --sidebar-hover: #1f2937;
            --sidebar-active: #2563eb;
            --sidebar-text: #d1d5db;
            --sidebar-heading: #6b7280;
            --text: #111827;
            --text-subtle: #6b7280;
            --border: #e5e7eb;
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --income: #15803d;
            --expense: #dc2626;
            --balance: #2563eb;
            --radius: 10px;
            --shadow:
                0 1px 2px rgba(0, 0, 0, 0.04),
                0 1px 3px rgba(0, 0, 0, 0.06);
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            background: var(--background);
            color: var(--text);
            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                "Noto Sans JP",
                sans-serif;
            font-size: 14px;
            line-height: 1.5;
        }

        a {
            color: inherit;
        }

        button,
        input,
        select,
        textarea {
            font: inherit;
        }

        .app-layout {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 240px;
            padding: 24px 16px;
            overflow-y: auto;
            background: var(--sidebar);
            color: var(--sidebar-text);
        }

        .sidebar-logo {
            margin: 0 8px 28px;
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
        }

        .sidebar-section {
            margin-top: 24px;
        }

        .sidebar-section-title {
            margin: 0 12px 8px;
            color: var(--sidebar-heading);
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.08em;
        }

        .sidebar-nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .sidebar-link {
            display: block;
            padding: 10px 12px;
            border-radius: 7px;
            color: var(--sidebar-text);
            text-decoration: none;
        }

        .sidebar-link:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }

        .sidebar-link.is-active {
            background: var(--sidebar-active);
            color: #ffffff;
            font-weight: 600;
        }

        .sidebar-footer {
            margin-top: 32px;
            padding-top: 20px;
            border-top: 1px solid #374151;
        }

        .sidebar-user {
            margin: 0 12px 12px;
            color: #9ca3af;
            font-size: 13px;
        }

        .logout-button {
            width: 100%;
            padding: 10px 12px;
            border: 0;
            border-radius: 7px;
            background: transparent;
            color: var(--sidebar-text);
            text-align: left;
            cursor: pointer;
        }

        .logout-button:hover {
            background: var(--sidebar-hover);
            color: #ffffff;
        }

        .app-main {
            flex: 1;
            min-width: 0;
            margin-left: 240px;
        }

        .page-container {
            width: 100%;
            max-width: 1440px;
            margin: 0 auto;
            padding: 36px 40px 64px;
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .page-title {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .page-description {
            margin: 6px 0 0;
            color: var(--text-subtle);
        }

        .panel {
            overflow: hidden;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .panel + .panel {
            margin-top: 24px;
        }

        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
        }

        .panel-title {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
        }

        .panel-body {
            padding: 22px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            text-align: left;
            vertical-align: middle;
        }

        .data-table th {
            background: var(--surface-subtle);
            color: var(--text-subtle);
            font-size: 12px;
            font-weight: 700;
        }

        .data-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .data-table .amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .empty-message {
            margin: 0;
            color: var(--text-subtle);
        }

        .text-muted {
            color: var(--text-subtle);
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            padding: 8px 14px;
            border: 1px solid transparent;
            border-radius: 7px;
            text-decoration: none;
            cursor: pointer;
        }

        .button-primary {
            background: var(--primary);
            color: #ffffff;
        }

        .button-primary:hover {
            background: var(--primary-hover);
        }
    </style>

    @stack('styles')
</head>

<body>
    @auth
        <div class="app-layout">
            <aside class="sidebar">
                <h1 class="sidebar-logo">
                    家計簿
                </h1>

                <nav>
                    <div class="sidebar-nav">
                        <a
                            href="{{ route('dashboard') }}"
                            class="
                                sidebar-link
                                {{ request()->routeIs('dashboard')
                                    ? 'is-active'
                                    : '' }}
                            "
                        >
                            ホーム
                        </a>

                        <a
                            href="{{ route('transactions.index') }}"
                            class="
                                sidebar-link
                                {{ request()->routeIs('transactions.index')
                                    ? 'is-active'
                                    : '' }}
                            "
                        >
                            取引一覧
                        </a>

                        <a
                            href="{{ route('transactions.create') }}"
                            class="
                                sidebar-link
                                {{ request()->routeIs('transactions.create')
                                    ? 'is-active'
                                    : '' }}
                            "
                        >
                            取引登録
                        </a>

                        <a
                            href="{{ route('summary.index') }}"
                            class="
                                sidebar-link
                                {{ request()->routeIs('summary.*')
                                    ? 'is-active'
                                    : '' }}
                            "
                        >
                            集計
                        </a>
                    </div>

                    <div class="sidebar-section">
                        <p class="sidebar-section-title">
                            設定
                        </p>

                        <div class="sidebar-nav">
                            <a
                                href="{{ route('accounts.index') }}"
                                class="
                                    sidebar-link
                                    {{ request()->routeIs('accounts.*')
                                        ? 'is-active'
                                        : '' }}
                                "
                            >
                                口座管理
                            </a>

                            <a
                                href="{{ route('categories.index') }}"
                                class="
                                    sidebar-link
                                    {{ request()->routeIs('categories.*')
                                        ? 'is-active'
                                        : '' }}
                                "
                            >
                                カテゴリ管理
                            </a>

                            <a
                                href="{{ route('transaction-rules.index') }}"
                                class="
                                    sidebar-link
                                    {{ request()->routeIs('transaction-rules.*')
                                        ? 'is-active'
                                        : '' }}
                                "
                            >
                                入力テンプレート
                            </a>

                            <a
                                href="{{ route('opening-balances.create') }}"
                                class="
                                    sidebar-link
                                    {{ request()->routeIs('opening-balances.*')
                                        ? 'is-active'
                                        : '' }}
                                "
                            >
                                初期残高登録
                            </a>
                        </div>
                    </div>
                </nav>

                <div class="sidebar-footer">
                    <p class="sidebar-user">
                        {{ auth()->user()->name }}
                    </p>

                    <form
                        method="POST"
                        action="{{ route('logout') }}"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="logout-button"
                        >
                            ログアウト
                        </button>
                    </form>
                </div>
            </aside>

            <div class="app-main">
                <div class="page-container">
                    @yield('content')
                </div>
            </div>
        </div>
    @else
        @yield('content')
    @endauth

    <script>
        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter') {
                return;
            }

            if (event.target.tagName === 'TEXTAREA') {
                return;
            }

            if (event.target.closest('form')) {
                event.preventDefault();
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
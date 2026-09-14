@extends('layouts.app')

@section('title', '新規登録 - 家計簿')

@push('styles')
    <style>
        body {
            margin: 0;
            min-width: 1024px;
            background:
                linear-gradient(
                    135deg,
                    #111827 0%,
                    #1f2937 42%,
                    #f3f4f6 42%,
                    #f3f4f6 100%
                );
            color: #111827;
            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            box-sizing: border-box;
        }

        .auth-card {
            width: 100%;
            max-width: 460px;
            padding: 40px;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #ffffff;
            box-shadow:
                0 24px 60px rgba(17, 24, 39, 0.18);
        }

        .auth-brand {
            margin: 0 0 6px;
            color: #111827;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-align: center;
        }

        .auth-title {
            margin: 0;
            color: #374151;
            font-size: 18px;
            font-weight: 600;
            text-align: center;
        }

        .auth-description {
            margin: 10px 0 0;
            color: #6b7280;
            font-size: 14px;
            line-height: 1.6;
            text-align: center;
        }

        .auth-divider {
            margin: 28px 0;
            border: 0;
            border-top: 1px solid #e5e7eb;
        }

        .auth-error {
            margin-bottom: 22px;
            padding: 14px 16px;
            border: 1px solid #fecaca;
            border-radius: 10px;
            background: #fef2f2;
            color: #b91c1c;
            font-size: 14px;
            line-height: 1.6;
        }

        .auth-error ul {
            margin: 0;
            padding-left: 20px;
        }

        .auth-field + .auth-field {
            margin-top: 18px;
        }

        .auth-label {
            display: block;
            margin-bottom: 7px;
            color: #374151;
            font-size: 14px;
            font-weight: 600;
        }

        .auth-input {
            width: 100%;
            height: 44px;
            box-sizing: border-box;
            padding: 0 13px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: #ffffff;
            color: #111827;
            font-size: 15px;
            outline: none;
            transition:
                border-color 0.15s ease,
                box-shadow 0.15s ease;
        }

        .auth-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .auth-submit {
            width: 100%;
            height: 46px;
            margin-top: 24px;
            border: 0;
            border-radius: 8px;
            background: #2563eb;
            color: #ffffff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        .auth-submit:hover {
            background: #1d4ed8;
        }

        .auth-footer {
            margin: 26px 0 0;
            color: #6b7280;
            font-size: 14px;
            text-align: center;
        }

        .auth-link {
            color: #2563eb;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }

        .auth-link:hover {
            text-decoration: underline;
        }
    </style>
@endpush

@section('content')
    <main class="auth-page">
        <section class="auth-card">
            <header>
                <h1 class="auth-brand">家計簿</h1>

                <h2 class="auth-title">
                    新規登録
                </h2>

                <p class="auth-description">
                    家計簿を利用するアカウントを作成します。
                </p>
            </header>

            <hr class="auth-divider">

            @if ($errors->any())
                <div class="auth-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="auth-field">
                    <label
                        for="name"
                        class="auth-label"
                    >
                        名前
                    </label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name') }}"
                        class="auth-input"
                        autocomplete="name"
                        required
                        autofocus
                    >
                </div>

                <div class="auth-field">
                    <label
                        for="email"
                        class="auth-label"
                    >
                        メールアドレス
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="auth-input"
                        autocomplete="email"
                        required
                    >
                </div>

                <div class="auth-field">
                    <label
                        for="password"
                        class="auth-label"
                    >
                        パスワード
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="auth-input"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <div class="auth-field">
                    <label
                        for="password_confirmation"
                        class="auth-label"
                    >
                        パスワード（確認）
                    </label>

                    <input
                        type="password"
                        id="password_confirmation"
                        name="password_confirmation"
                        class="auth-input"
                        autocomplete="new-password"
                        required
                    >
                </div>

                <button
                    type="submit"
                    class="auth-submit"
                >
                    登録
                </button>
            </form>

            <p class="auth-footer">
                すでにアカウントをお持ちの方
                <a
                    href="{{ route('login') }}"
                    class="auth-link"
                >
                    ログイン
                </a>
            </p>
        </section>
    </main>
@endsection
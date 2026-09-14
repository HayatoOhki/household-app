@extends('layouts.app')

@section('title', 'ログイン - 家計簿')

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

        .auth-options {
            display: flex;
            align-items: center;
            margin-top: 18px;
        }

        .remember-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            font-size: 14px;
            cursor: pointer;
        }

        .remember-checkbox {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: #2563eb;
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
    </style>
@endpush

@section('content')
    <main class="auth-page">
        <section class="auth-card">
            <header>
                <h1 class="auth-brand">家計簿</h1>

                <h2 class="auth-title">
                    ログイン
                </h2>

                <p class="auth-description">
                    登録済みのアカウントでログインしてください。
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

            <form method="POST" action="{{ route('login') }}">
                @csrf

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
                        autofocus
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
                        autocomplete="current-password"
                        required
                    >
                </div>

                <div class="auth-options">
                    <label class="remember-label">
                        <input
                            type="checkbox"
                            name="remember"
                            value="1"
                            class="remember-checkbox"
                            @checked(old('remember'))
                        >

                        ログイン状態を保持する
                    </label>

                </div>

                <button
                    type="submit"
                    class="auth-submit"
                >
                    ログイン
                </button>
            </form>

            @if (Route::has('register'))
                <p class="auth-footer">
                    アカウントをお持ちでない方
                    <a
                        href="{{ route('register') }}"
                        class="auth-link"
                    >
                        新規登録
                    </a>
                </p>
            @endif
        </section>
    </main>
@endsection
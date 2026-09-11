@extends('layouts.app')

@section('title', '家計簿')

@section('content')
    <header>
        <h1>家計簿</h1>

        <p>
            {{ auth()->user()->name }} さん、こんにちは。
        </p>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">ログアウト</button>
        </form>
    </header>

    <main>
        <h2>ホーム</h2>

        <p>
            ここに家計簿のダッシュボードを作成します。
        </p>

        <nav>
            <ul>
                <li>
                    <a href="{{ route('transactions.index') }}">
                        取引管理
                    </a>
                </li>

                <li>
                    <a href="{{ route('accounts.index') }}">
                        口座管理
                    </a>
                </li>

                <li>
                    <a href="{{ route('categories.index') }}">
                        カテゴリ管理
                    </a>
                </li>
            </ul>
        </nav>
    </main>
@endsection
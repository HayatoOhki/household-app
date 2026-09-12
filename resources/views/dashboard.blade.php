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

            <button type="submit">
                ログアウト
            </button>
        </form>
    </header>

    <main>
        <h2>ダッシュボード</h2>

        <section>
            <h3>{{ $month }} の収支</h3>

            <table border="1">
                <thead>
                    <tr>
                        <th>収入</th>
                        <th>支出</th>
                        <th>収支</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>
                            {{ number_format($monthlyIncome) }}
                            円
                        </td>

                        <td>
                            {{ number_format($monthlyExpense) }}
                            円
                        </td>

                        <td>
                            {{ number_format($monthlyBalance) }}
                            円
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h3>口座残高</h3>

            @if ($accountBalances->isEmpty())
                <p>
                    口座が登録されていません。
                </p>
            @else
                <table border="1">
                    <thead>
                        <tr>
                            <th>口座</th>
                            <th>現在残高</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($accountBalances as $accountBalance)
                            <tr>
                                <td>
                                    {{ $accountBalance['account_name'] }}
                                </td>

                                <td>
                                    {{ number_format($accountBalance['balance']) }}
                                    円
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>

        <section>
            <h3>メニュー</h3>

            <nav>
                <ul>
                    <li>
                        <a href="{{ route('transactions.index') }}">
                            取引管理
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('transactions.create') }}">
                            支出・収入登録
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('transfers.create') }}">
                            振替登録
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('opening-balances.create') }}">
                            初期残高登録
                        </a>
                    </li>

                    <li>
                        <a href="{{ route('summary.index') }}">
                            集計
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

                    <li>
                        <a href="{{ route('transaction-rules.index') }}">
                            取引ルール管理
                        </a>
                    </li>
                </ul>
            </nav>
        </section>
    </main>
@endsection
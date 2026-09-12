@extends('layouts.app')

@section('title', '集計')

@section('content')
    <header>
        <h1>集計</h1>

        <p>
            <a href="{{ route('dashboard') }}">
                ホームへ戻る
            </a>
        </p>

        <nav>
            <a href="{{ route('transactions.index') }}">
                取引一覧
            </a>

            |

            <strong>
                集計
            </strong>
        </nav>
    </header>

    <main>
        <h2>月間集計</h2>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        @endif

        <form
            method="GET"
            action="{{ route('summary.index') }}"
        >
            <label for="month">
                対象月
            </label>

            <input
                id="month"
                type="month"
                name="month"
                value="{{ $month }}"
            >

            <button type="submit">
                表示
            </button>
        </form>

        <section>
            <h3>
                {{ $month }} の収支
            </h3>

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
                            {{ number_format($monthlyIncome) }} 円
                        </td>

                        <td>
                            {{ number_format($monthlyExpense) }} 円
                        </td>

                        <td>
                            {{ number_format($monthlyBalance) }} 円
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section>
            <h3>カテゴリ別支出</h3>

            @if ($categoryExpenses->isEmpty())
                <p>
                    この月の支出はありません。
                </p>
            @else
                <table border="1">
                    <thead>
                        <tr>
                            <th>カテゴリ</th>
                            <th>支出額</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach (
                            $categoryExpenses
                            as $categoryExpense
                        )
                            <tr>
                                <td>
                                    {{
                                        $categoryExpense[
                                            'category_name'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        number_format(
                                            $categoryExpense[
                                                'amount'
                                            ]
                                        )
                                    }}
                                    円
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
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
                        @foreach (
                            $accountBalances
                            as $accountBalance
                        )
                            <tr>
                                <td>
                                    {{
                                        $accountBalance[
                                            'account_name'
                                        ]
                                    }}
                                </td>

                                <td>
                                    {{
                                        number_format(
                                            $accountBalance[
                                                'balance'
                                            ]
                                        )
                                    }}
                                    円
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </main>
@endsection
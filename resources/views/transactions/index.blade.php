@extends('layouts.app')

@section('title', '取引一覧')

@section('content')
    <header>
        <h1>取引一覧</h1>

        <p>
            <a href="{{ route('home') }}">
                ホームへ戻る
            </a>
        </p>

        <p>
            <a href="{{ route('transactions.create') }}">
                取引を追加
            </a>
        </p>
    </header>

    <main>
        @if (session('success'))
            <p>
                {{ session('success') }}
            </p>
        @endif

        @if ($transactions->isEmpty())
            <p>
                登録されている取引はありません。
            </p>
        @else
            <table border="1" cellpadding="8">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>種別</th>
                        <th>取引先</th>
                        <th>口座</th>
                        <th>カテゴリ</th>
                        <th>金額</th>
                        <th>引落日</th>
                        <th>経費割合</th>
                        <th>操作</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($transactions as $transaction)
                        <tr>
                            <td>
                                {{ $transaction->transaction_date->format('Y-m-d') }}
                            </td>

                            <td>
                                @if ($transaction->type->value === 'expense')
                                    支出
                                @elseif ($transaction->type->value === 'income')
                                    収入
                                @elseif ($transaction->type->value === 'transfer')
                                    振替
                                @elseif ($transaction->type->value === 'opening_balance')
                                    初期残高
                                @endif
                            </td>

                            <td>
                                {{ $transaction->counterparty_name ?? '-' }}
                            </td>

                            <td>
                                {{ $transaction->account?->name ?? '-' }}
                            </td>

                            <td>
                                {{ $transaction->category?->name ?? '-' }}
                            </td>

                            <td>
                                {{ number_format($transaction->amount) }} 円
                            </td>

                            <td>
                                {{ $transaction->withdrawal_date?->format('Y-m-d') ?? '-' }}
                            </td>

                            <td>
                                {{ number_format((float) $transaction->expense_ratio, 0) }}%
                            </td>

                            <td>
                                @if (
                                    $transaction->type->value === 'expense'
                                    || $transaction->type->value === 'income'
                                )
                                    <a href="{{ route('transactions.edit', $transaction) }}">
                                        編集
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('transactions.destroy', $transaction) }}"
                                        style="display: inline;"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('この取引を削除しますか？')"
                                        >
                                            削除
                                        </button>
                                    </form>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </main>
@endsection
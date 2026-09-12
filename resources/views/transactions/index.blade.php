@extends('layouts.app')

@section('title', '取引管理')

@section('content')
    <header>
        <h1>取引管理</h1>

        <p>
            <a href="{{ route('dashboard') }}">
                ホームへ戻る
            </a>
        </p>

        <nav>
            <strong>
                一覧
            </strong>

            |

            <a href="{{ route('transactions.create') }}">
                支出・収入登録
            </a>

            |

            <a href="{{ route('transfers.create') }}">
                振替登録
            </a>

            |

            <a href="{{ route('opening-balances.create') }}">
                初期残高登録
            </a>
        </nav>
    </header>

    <main>
        <h2>取引一覧</h2>

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
                        <th style="text-align: center;">
                            日付
                        </th>

                        <th style="text-align: center;">
                            種別
                        </th>

                        <th style="text-align: center;">
                            取引先
                        </th>

                        <th style="text-align: center;">
                            口座
                        </th>

                        <th style="text-align: center;">
                            カテゴリ
                        </th>

                        <th style="text-align: center;">
                            金額
                        </th>

                        <th style="text-align: center;">
                            引落日
                        </th>

                        <th style="text-align: center;">
                            経費割合
                        </th>

                        <th style="text-align: center;">
                            経費登録
                        </th>

                        <th style="text-align: center;">
                            領収書保存
                        </th>

                        <th style="text-align: center;">
                            操作
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($transactions as $transaction)
                        @php
                            $isNormalTransaction =
                                $transaction->type->value === 'expense'
                                || $transaction->type->value === 'income';

                            $displayName =
                                $transaction->counterparty_name;

                            if (
                                $isNormalTransaction
                                && $transaction->counterparty_name !== null
                                && $transaction->account_id !== null
                            ) {
                                $matchedRule = $transactionRules->first(
                                    fn ($rule) =>
                                        $rule->account_id
                                            === $transaction->account_id
                                        && str_contains(
                                            $transaction->counterparty_name,
                                            $rule->keyword
                                        )
                                );

                                if (
                                    $matchedRule !== null
                                    && $matchedRule->display_name !== null
                                ) {
                                    $displayName =
                                        $matchedRule->display_name;
                                }
                            }
                        @endphp

                        <tr>
                            <td style="text-align: center;">
                                {{
                                    $transaction
                                        ->transaction_date
                                        ->format('Y-m-d')
                                }}
                            </td>

                            <td style="text-align: center;">
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

                            <td style="text-align: left;">
                                @if ($isNormalTransaction)
                                    {{ $displayName ?? '-' }}
                                @else
                                    -
                                @endif
                            </td>

                            <td style="text-align: left;">
                                @if ($transaction->type->value === 'transfer')
                                    {{ $transaction->account?->name ?? '-' }}

                                    →

                                    {{
                                        $transaction
                                            ->outgoingTransfer
                                            ?->toTransaction
                                            ?->account
                                            ?->name ?? '-'
                                    }}
                                @else
                                    {{ $transaction->account?->name ?? '-' }}
                                @endif
                            </td>

                            <td style="text-align: center;">
                                @if ($isNormalTransaction)
                                    {{ $transaction->category?->name ?? '-' }}
                                @else
                                    -
                                @endif
                            </td>

                            <td style="text-align: right;">
                                {{
                                    number_format(
                                        $transaction->amount
                                    )
                                }} 円
                            </td>

                            <td style="text-align: center;">
                                @if ($isNormalTransaction)
                                    {{
                                        $transaction
                                            ->withdrawal_date
                                            ?->format('Y-m-d') ?? '-'
                                    }}
                                @else
                                    -
                                @endif
                            </td>

                            <td style="text-align: center;">
                                @if ($isNormalTransaction)
                                    {{
                                        number_format(
                                            (float) $transaction->expense_ratio,
                                            0
                                        )
                                    }}%
                                @else
                                    -
                                @endif
                            </td>

                            <td style="text-align: center;">
                                @if ($isNormalTransaction)
                                    {{
                                        $transaction->expense_registered
                                            ? '済'
                                            : '未'
                                    }}
                                @else
                                    -
                                @endif
                            </td>

                            <td style="text-align: center;">
                                @if ($isNormalTransaction)
                                    {{
                                        $transaction->receipt_saved
                                            ? '済'
                                            : '未'
                                    }}
                                @else
                                    -
                                @endif
                            </td>

                            <td style="text-align: center;">
                                @if ($isNormalTransaction)
                                    <a
                                        href="{{
                                            route(
                                                'transactions.edit',
                                                $transaction
                                            )
                                        }}"
                                    >
                                        編集
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{
                                            route(
                                                'transactions.destroy',
                                                $transaction
                                            )
                                        }}"
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
                                @elseif (
                                    $transaction->type->value === 'transfer'
                                    && $transaction->outgoingTransfer !== null
                                )
                                    <a
                                        href="{{
                                            route(
                                                'transfers.edit',
                                                $transaction->outgoingTransfer
                                            )
                                        }}"
                                    >
                                        編集
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{
                                            route(
                                                'transfers.destroy',
                                                $transaction->outgoingTransfer
                                            )
                                        }}"
                                        style="display: inline;"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('この振替を削除しますか？')"
                                        >
                                            削除
                                        </button>
                                    </form>
                                @elseif (
                                    $transaction->type->value === 'opening_balance'
                                )
                                    <a
                                        href="{{
                                            route(
                                                'opening-balances.edit',
                                                $transaction
                                            )
                                        }}"
                                    >
                                        編集
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{
                                            route(
                                                'opening-balances.destroy',
                                                $transaction
                                            )
                                        }}"
                                        style="display: inline;"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            onclick="return confirm('この初期残高を削除しますか？')"
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
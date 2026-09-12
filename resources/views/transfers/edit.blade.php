@extends('layouts.app')

@section('title', '振替編集')

@section('content')
    <header>
        <h1>取引管理</h1>

        <p>
            <a href="{{ route('dashboard') }}">
                ホームへ戻る
            </a>
        </p>

        <nav>
            <a href="{{ route('transactions.index') }}">
                一覧
            </a>

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
        <h2>振替編集</h2>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($accounts->count() < 2)
            <p>
                振替を編集するには2つ以上の口座が必要です。

                <a href="{{ route('accounts.create') }}">
                    口座を登録
                </a>
            </p>
        @endif

        <form
            method="POST"
            action="{{ route('transfers.update', $transfer) }}"
        >
            @csrf
            @method('PUT')

            <div>
                <label for="transaction_date">
                    振替日
                </label>

                <input
                    id="transaction_date"
                    type="date"
                    name="transaction_date"
                    value="{{
                        old(
                            'transaction_date',
                            $transfer
                                ->fromTransaction
                                ->transaction_date
                                ->format('Y-m-d')
                        )
                    }}"
                    required
                >
            </div>

            <div>
                <label for="from_account_id">
                    振替元口座
                </label>

                <select
                    id="from_account_id"
                    name="from_account_id"
                    required
                >
                    <option value="">
                        選択してください
                    </option>

                    @foreach ($accounts as $account)
                        <option
                            value="{{ $account->id }}"
                            @selected(
                                (string) old(
                                    'from_account_id',
                                    $transfer
                                        ->fromTransaction
                                        ->account_id
                                )
                                === (string) $account->id
                            )
                        >
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="to_account_id">
                    振替先口座
                </label>

                <select
                    id="to_account_id"
                    name="to_account_id"
                    required
                >
                    <option value="">
                        選択してください
                    </option>

                    @foreach ($accounts as $account)
                        <option
                            value="{{ $account->id }}"
                            @selected(
                                (string) old(
                                    'to_account_id',
                                    $transfer
                                        ->toTransaction
                                        ->account_id
                                )
                                === (string) $account->id
                            )
                        >
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="amount">
                    金額
                </label>

                <input
                    id="amount"
                    type="number"
                    name="amount"
                    value="{{
                        old(
                            'amount',
                            $transfer
                                ->fromTransaction
                                ->amount
                        )
                    }}"
                    min="1"
                    step="1"
                    required
                >

                <span>円</span>
            </div>

            <button
                type="submit"
                @disabled($accounts->count() < 2)
            >
                更新
            </button>
        </form>

        <p>
            <a href="{{ route('transactions.index') }}">
                一覧へ戻る
            </a>
        </p>
    </main>
@endsection
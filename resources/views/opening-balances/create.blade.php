@extends('layouts.app')

@section('title', '初期残高登録')

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

            <strong>
                初期残高登録
            </strong>
        </nav>
    </header>

    <main>
        <h2>初期残高登録</h2>

        <p>
            家計簿の利用開始時点で、
            各口座に存在する残高を登録します。
        </p>

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($accounts->isEmpty())
            <p>
                初期残高を登録するには口座が必要です。

                <a href="{{ route('accounts.create') }}">
                    口座を登録
                </a>
            </p>
        @else
            <form
                method="POST"
                action="{{ route('opening-balances.store') }}"
            >
                @csrf

                <div>
                    <label for="transaction_date">
                        日付
                    </label>

                    <input
                        id="transaction_date"
                        type="date"
                        name="transaction_date"
                        value="{{ old(
                            'transaction_date',
                            now()->format('Y-m-d')
                        ) }}"
                        required
                    >
                </div>

                <div>
                    <label for="account_id">
                        口座
                    </label>

                    <select
                        id="account_id"
                        name="account_id"
                        required
                    >
                        <option value="">
                            選択してください
                        </option>

                        @foreach ($accounts as $account)
                            <option
                                value="{{ $account->id }}"
                                @selected(
                                    (string) old('account_id')
                                    === (string) $account->id
                                )
                                @disabled(
                                    $registeredAccountIds->contains(
                                        $account->id
                                    )
                                )
                            >
                                {{ $account->name }}

                                @if (
                                    $registeredAccountIds->contains(
                                        $account->id
                                    )
                                )
                                    （登録済み）
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="amount">
                        初期残高
                    </label>

                    <input
                        id="amount"
                        type="number"
                        name="amount"
                        value="{{ old('amount') }}"
                        min="0"
                        step="1"
                        required
                    >

                    <span>円</span>
                </div>

                <button type="submit">
                    登録
                </button>
            </form>
        @endif
    </main>
@endsection
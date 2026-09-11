@extends('layouts.app')

@section('title', '取引編集')

@section('content')
    <header>
        <h1>取引編集</h1>

        <p>
            <a href="{{ route('transactions.index') }}">
                取引一覧へ戻る
            </a>
        </p>
    </header>

    <main>
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
            method="POST"
            action="{{ route('transactions.update', $transaction) }}"
        >
            @csrf
            @method('PUT')

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
                        $transaction->transaction_date->format('Y-m-d')
                    ) }}"
                    required
                >
            </div>

            <div>
                <label for="type">
                    種別
                </label>

                <select
                    id="type"
                    name="type"
                    required
                >
                    <option
                        value="expense"
                        @selected(
                            old(
                                'type',
                                $transaction->type->value
                            ) === 'expense'
                        )
                    >
                        支出
                    </option>

                    <option
                        value="income"
                        @selected(
                            old(
                                'type',
                                $transaction->type->value
                            ) === 'income'
                        )
                    >
                        収入
                    </option>
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
                    value="{{ old('amount', $transaction->amount) }}"
                    min="1"
                    step="1"
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
                                (string) old(
                                    'account_id',
                                    $transaction->account_id
                                ) === (string) $account->id
                            )
                        >
                            {{ $account->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="category_id">
                    カテゴリ
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    required
                >
                    <option value="">
                        選択してください
                    </option>

                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected(
                                (string) old(
                                    'category_id',
                                    $transaction->category_id
                                ) === (string) $category->id
                            )
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="counterparty_name">
                    取引先名
                </label>

                <input
                    id="counterparty_name"
                    type="text"
                    name="counterparty_name"
                    value="{{ old(
                        'counterparty_name',
                        $transaction->counterparty_name
                    ) }}"
                    maxlength="255"
                >
            </div>

            <div id="withdrawal-date-field">
                <label for="withdrawal_date">
                    引落日
                </label>

                <input
                    id="withdrawal_date"
                    type="date"
                    name="withdrawal_date"
                    value="{{ old(
                        'withdrawal_date',
                        $transaction->withdrawal_date?->format('Y-m-d')
                    ) }}"
                >
            </div>

            <div>
                <label for="expense_ratio">
                    経費割合
                </label>

                <input
                    id="expense_ratio"
                    type="number"
                    name="expense_ratio"
                    value="{{ old(
                        'expense_ratio',
                        $transaction->expense_ratio
                    ) }}"
                    min="0"
                    max="100"
                    step="0.01"
                >

                <span>%</span>
            </div>

            <button type="submit">
                更新
            </button>
        </form>
    </main>
@endsection

@push('scripts')
    <script>
        const transactionRules = [
            @foreach ($transactionRules as $rule)
                {
                    account_id: @js((string) $rule->account_id),
                    keyword: @js($rule->keyword),
                    category_id: @js(
                        $rule->category_id !== null
                            ? (string) $rule->category_id
                            : null
                    ),
                },
            @endforeach
        ];

        const typeSelect =
            document.getElementById('type');

        const withdrawalDateField =
            document.getElementById('withdrawal-date-field');

        const withdrawalDateInput =
            document.getElementById('withdrawal_date');

        const accountSelect =
            document.getElementById('account_id');

        const counterpartyInput =
            document.getElementById('counterparty_name');

        const categorySelect =
            document.getElementById('category_id');

        function updateTransactionFields() {
            const isExpense =
                typeSelect.value === 'expense';

            withdrawalDateField.hidden = !isExpense;

            if (!isExpense) {
                withdrawalDateInput.value = '';
            }
        }

        function applyTransactionRule() {
            const accountId =
                accountSelect.value;

            const counterpartyName =
                counterpartyInput.value;

            if (!accountId || !counterpartyName) {
                return;
            }

            const matchedRule =
                transactionRules.find(
                    (rule) =>
                        rule.account_id === accountId
                        && counterpartyName.includes(
                            rule.keyword
                        )
                );

            if (
                matchedRule
                && matchedRule.category_id !== null
            ) {
                categorySelect.value =
                    matchedRule.category_id;
            }
        }

        typeSelect.addEventListener(
            'change',
            updateTransactionFields
        );

        accountSelect.addEventListener(
            'change',
            applyTransactionRule
        );

        counterpartyInput.addEventListener(
            'input',
            applyTransactionRule
        );

        updateTransactionFields();
    </script>
@endpush
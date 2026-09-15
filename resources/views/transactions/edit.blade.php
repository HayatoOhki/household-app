@extends('layouts.app')

@section('title', '取引編集')

@push('styles')
    <style>
        .transaction-form-page {
            max-width: 820px;
            margin: 0 auto;
        }

        .transaction-form-body {
            padding: 28px 32px 32px;
        }

        .form-grid {
            display: grid;
            gap: 12px;
        }

        .form-item {
            display: grid;
            grid-template-columns: 130px minmax(0, 1fr);
            align-items: center;
            gap: 16px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0;
            font-size: 13px;
            font-weight: 700;
        }

        .form-field {
            min-width: 0;
        }

        .form-control {
            width: 100%;
            height: 40px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: var(--surface);
            color: var(--text);
        }

        .form-control:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgb(37 99 235 / 10%);
        }

        .required-mark {
            color: var(--expense);
        }

        .type-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            overflow: hidden;
            height: 40px;
            border: 1px solid var(--border);
            border-radius: 7px;
        }

        .type-option {
            position: relative;
        }

        .type-option + .type-option {
            border-left: 1px solid var(--border);
        }

        .type-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .type-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            background: var(--surface);
            font-weight: 600;
            cursor: pointer;
        }

        .type-option input[value="expense"]:checked + label {
            background: #fef2f2;
            color: #b91c1c;
        }

        .type-option input[value="income"]:checked + label {
            background: #f0fdf4;
            color: #15803d;
        }

        .type-option input[value="transfer"]:checked + label {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .template-picker {
            display: none;
            margin-top: 8px;
        }

        .template-picker.is-visible {
            display: block;
        }

        .readonly-display {
            display: flex;
            align-items: center;
            min-height: 40px;
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: var(--surface-subtle);
            color: var(--text-subtle);
        }

        .amount-wrapper,
        .ratio-wrapper {
            position: relative;
        }

        .amount-input,
        .ratio-input {
            padding-right: 42px;
            text-align: right;
        }

        .amount-input {
            font-size: 16px;
            font-weight: 700;
        }

        .input-suffix {
            position: absolute;
            top: 50%;
            right: 13px;
            color: var(--text-subtle);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            margin-top: 28px;
            padding-top: 22px;
            border-top: 1px solid var(--border);
        }

        .error-message,
        .warning-message {
            margin-bottom: 20px;
            padding: 14px 16px;
            border-radius: 7px;
        }

        .error-message {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .warning-message {
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #92400e;
        }

        [hidden] {
            display: none !important;
        }
    </style>
@endpush

@section('content')
    @php
        $isTransfer =
            $transaction->type->value === 'transfer';

        $defaultType = old(
            'type',
            $transaction->type->value
        );

        $defaultAccountId = old(
            'account_id',
            $isTransfer
                ? ''
                : $transaction->account_id
        );

        $defaultCategoryId = old(
            'category_id',
            $isTransfer
                ? ''
                : $transaction->category_id
        );

        $defaultCounterparty = old(
            'counterparty_name',
            $isTransfer
                ? ''
                : $transaction->counterparty_name
        );

        $defaultAmount = old(
            'amount',
            $isTransfer
                ? $transfer->fromTransaction->amount
                : $transaction->amount
        );

        $defaultExpenseRatio = old(
            'expense_ratio',
            $isTransfer
                ? 0
                : $transaction->expense_ratio
        );

        $defaultWithdrawalDate = old(
            'withdrawal_date',
            $isTransfer
                ? null
                : $transaction->withdrawal_date?->format('Y-m-d')
        );

        $defaultFromAccountId = old(
            'from_account_id',
            $isTransfer
                ? $transfer->fromTransaction->account_id
                : ''
        );

        $defaultToAccountId = old(
            'to_account_id',
            $isTransfer
                ? $transfer->toTransaction->account_id
                : ''
        );
    @endphp

    <div class="transaction-form-page">
        <header class="page-header">
            <h1 class="page-title">
                取引編集
            </h1>
        </header>

        @if ($errors->any())
            <div class="error-message">
                <strong>入力内容を確認してください。</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($accounts->isEmpty())
            <div class="warning-message">
                取引を編集するには口座が必要です。
            </div>
        @endif

        @if ($categories->isEmpty())
            <div id="category-warning" class="warning-message">
                支出・収入を編集するにはカテゴリが必要です。
            </div>
        @endif

        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">取引情報</h2>
            </div>

            <div class="transaction-form-body">
                <form
                    method="POST"
                    action="{{ route(
                        'transactions.update',
                        $transaction
                    ) }}"
                >
                    @csrf
                    @method('PUT')

                    <div class="form-grid">
                        <div class="form-item">
                            <div class="form-label">
                                取引種別
                                <span class="required-mark">*</span>
                            </div>

                            <div class="form-field">
                                <div class="type-selector">
                                    @foreach ([
                                        'expense' => '支出',
                                        'income' => '収入',
                                        'transfer' => '振替',
                                    ] as $value => $label)
                                        <div class="type-option">
                                            <input
                                                id="type_{{ $value }}"
                                                type="radio"
                                                name="type"
                                                value="{{ $value }}"
                                                @checked($defaultType === $value)
                                            >
                                            <label for="type_{{ $value }}">
                                                {{ $label }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="form-item">
                            <label for="transaction_date" class="form-label">
                                日付
                                <span class="required-mark">*</span>
                            </label>

                            <div class="form-field">
                                <input
                                    id="transaction_date"
                                    type="date"
                                    name="transaction_date"
                                    value="{{ old(
                                        'transaction_date',
                                        $transaction
                                            ->transaction_date
                                            ->format('Y-m-d')
                                    ) }}"
                                    class="form-control"
                                    required
                                >
                            </div>
                        </div>

                        <div
                            id="normal-account-field"
                            class="form-item normal-field"
                        >
                            <label
                                id="account-label"
                                for="account_id"
                                class="form-label"
                            >
                                支払方法
                                <span class="required-mark">*</span>
                            </label>

                            <div class="form-field">
                                <select
                                        id="account_id"
                                        name="account_id"
                                        class="form-control"
                                    >
                                        <option value="">選択してください</option>

                                        @foreach ($accounts as $account)
                                            <option
                                                value="{{ $account->id }}"
                                                data-account-type="{{ $account->type->value }}"
                                                @selected(
                                                    (string) $defaultAccountId
                                                    === (string) $account->id
                                                )
                                            >
                                                {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                <div
                                    id="template-picker"
                                    class="template-picker"
                                >
                                    <select
                                        id="transaction-template"
                                        class="form-control"
                                    >
                                        <option value="">
                                            取引補助設定から選択
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div
                            id="category-field"
                            class="form-item normal-field"
                        >
                            <label for="category_id" class="form-label">
                                カテゴリ
                                <span class="required-mark">*</span>
                            </label>

                            <div class="form-field">
                                <select
                                    id="category_id"
                                    name="category_id"
                                    class="form-control"
                                >
                                    <option value="">選択してください</option>

                                    @foreach ($categories as $category)
                                        <option
                                            value="{{ $category->id }}"
                                            data-category-type="{{ $category->type->value }}"
                                            @selected(
                                                (string) $defaultCategoryId
                                                === (string) $category->id
                                            )
                                        >
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div
                            id="counterparty-field"
                            class="form-item normal-field"
                        >
                            <label
                                id="counterparty-label"
                                for="counterparty_name"
                                class="form-label"
                            >
                                取引先
                            </label>

                            <div class="form-field">
                                <input
                                    id="counterparty_name"
                                    type="text"
                                    name="counterparty_name"
                                    value="{{ $defaultCounterparty }}"
                                    maxlength="255"
                                    class="form-control"
                                >
                            </div>
                        </div>

                        <div
                            id="display-name-field"
                            class="form-item normal-field"
                        >
                            <div class="form-label">
                                表示名
                            </div>

                            <div
                                id="display-name"
                                class="readonly-display"
                                aria-live="polite"
                            >
                                -
                            </div>
                        </div>

                        <div
                            id="transfer-from-field"
                            class="form-item transfer-field"
                            hidden
                        >
                            <label for="from_account_id" class="form-label">
                                振替元口座
                                <span class="required-mark">*</span>
                            </label>

                            <div class="form-field">
                                <select
                                    id="from_account_id"
                                    name="from_account_id"
                                    class="form-control"
                                >
                                    <option value="">選択してください</option>

                                    @foreach ($accounts as $account)
                                        <option
                                            value="{{ $account->id }}"
                                            @selected(
                                                (string) $defaultFromAccountId
                                                === (string) $account->id
                                            )
                                        >
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div
                            id="transfer-to-field"
                            class="form-item transfer-field"
                            hidden
                        >
                            <label for="to_account_id" class="form-label">
                                振替先口座
                                <span class="required-mark">*</span>
                            </label>

                            <div class="form-field">
                                <select
                                    id="to_account_id"
                                    name="to_account_id"
                                    class="form-control"
                                >
                                    <option value="">選択してください</option>

                                    @foreach ($accounts as $account)
                                        <option
                                            value="{{ $account->id }}"
                                            @selected(
                                                (string) $defaultToAccountId
                                                === (string) $account->id
                                            )
                                        >
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-item">
                            <label for="amount" class="form-label">
                                金額
                                <span class="required-mark">*</span>
                            </label>

                            <div class="form-field">
                                <div class="amount-wrapper">
                                    <input
                                        id="amount"
                                        type="number"
                                        name="amount"
                                        value="{{ $defaultAmount }}"
                                        min="1"
                                        step="1"
                                        class="form-control amount-input"
                                        required
                                    >
                                    <span class="input-suffix">円</span>
                                </div>
                            </div>
                        </div>

                        <div
                            id="withdrawal-date-field"
                            class="form-item normal-field"
                            hidden
                        >
                            <label for="withdrawal_date" class="form-label">
                                引落日
                                <span
                                    id="withdrawal-required-mark"
                                    class="required-mark"
                                    hidden
                                >*</span>
                            </label>

                            <div class="form-field">
                                <input
                                    id="withdrawal_date"
                                    type="date"
                                    name="withdrawal_date"
                                    value="{{ $defaultWithdrawalDate }}"
                                    class="form-control"
                                >
                            </div>
                        </div>

                        <div
                            id="expense-ratio-field"
                            class="form-item normal-field"
                        >
                            <label for="expense_ratio" class="form-label">
                                経費割合
                            </label>

                            <div class="form-field">
                                <div class="ratio-wrapper">
                                    <input
                                        id="expense_ratio"
                                        type="number"
                                        name="expense_ratio"
                                        value="{{ $defaultExpenseRatio }}"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        class="form-control ratio-input"
                                    >
                                    <span class="input-suffix">%</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button
                            id="submit-button"
                            type="submit"
                            class="button button-primary"
                        >
                            更新する
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const transactionRules = [
                @foreach ($transactionRules as $rule)
                    {
                        id: @js((string) $rule->id),
                        account_id: @js((string) $rule->account_id),
                        keyword: @js($rule->keyword),
                        display_name: @js($rule->display_name),
                        category_id: @js(
                            $rule->category_id !== null
                                ? (string) $rule->category_id
                                : null
                        ),
                        category_name: @js(
                            $rule->category_id !== null
                                ? optional(
                                    $categories->firstWhere(
                                        'id',
                                        $rule->category_id
                                    )
                                )->name
                                : null
                        ),
                    },
                @endforeach
            ];

            const typeInputs =
                document.querySelectorAll('input[name="type"]');

            const normalFields =
                document.querySelectorAll('.normal-field');

            const transferFields =
                document.querySelectorAll('.transfer-field');

            const accountField =
                document.getElementById('normal-account-field');

            const accountLabel =
                document.getElementById('account-label');

            const accountSelect =
                document.getElementById('account_id');

            const categorySelect =
                document.getElementById('category_id');

            const counterpartyLabel =
                document.getElementById('counterparty-label');

            const counterpartyInput =
                document.getElementById('counterparty_name');

            const displayNameField =
                document.getElementById('display-name-field');

            const displayName =
                document.getElementById('display-name');

            const templatePicker =
                document.getElementById('template-picker');

            const templateSelect =
                document.getElementById('transaction-template');

            const withdrawalDateField =
                document.getElementById('withdrawal-date-field');

            const withdrawalDateInput =
                document.getElementById('withdrawal_date');

            const withdrawalRequiredMark =
                document.getElementById(
                    'withdrawal-required-mark'
                );

            const categoryWarning =
                document.getElementById('category-warning');

            function selectedType() {
                return document.querySelector(
                    'input[name="type"]:checked'
                )?.value ?? 'expense';
            }

            function filterCategoryOptions() {
                const type = selectedType();

                Array.from(categorySelect.options).forEach(function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden =
                        option.dataset.categoryType !== type;
                });

                const selectedOption =
                    categorySelect.options[categorySelect.selectedIndex];

                if (
                    selectedOption
                    && selectedOption.value
                    && selectedOption.hidden
                ) {
                    categorySelect.value = '';
                }
            }

            function selectedAccountType() {
                return accountSelect
                    .options[accountSelect.selectedIndex]
                    ?.dataset.accountType ?? null;
            }

            function accountRules() {
                if (selectedAccountType() === 'cash') {
                    return [];
                }

                const accountId = accountSelect.value;

                if (!accountId) {
                    return [];
                }

                return transactionRules.filter(
                    (rule) => rule.account_id === accountId
                );
            }

            function matchingRules() {
                const input = counterpartyInput.value.trim();

                if (!input) {
                    return [];
                }

                const matches = accountRules()
                    .filter(
                        (rule) =>
                            rule.keyword
                            && input.includes(rule.keyword)
                    )
                    .sort(
                        (a, b) =>
                            b.keyword.length - a.keyword.length
                    );

                if (matches.length === 0) {
                    return [];
                }

                const longestKeyword =
                    matches[0].keyword;

                return matches.filter(
                    (rule) =>
                        rule.keyword === longestKeyword
                );
            }

            function updateDisplayNameAndCategory() {
                const matches = matchingRules();

                if (matches.length === 0) {
                    displayName.textContent = '-';
                    return;
                }

                const visibleName =
                    matches.find(
                        (rule) => rule.display_name
                    )?.display_name ?? '-';

                displayName.textContent = visibleName;

                const categoryIds = [
                    ...new Set(
                        matches
                            .map((rule) => rule.category_id)
                            .filter(
                                (categoryId) =>
                                    categoryId !== null
                            )
                    ),
                ];

                if (categoryIds.length === 1) {
                    categorySelect.value =
                        categoryIds[0];
                }
            }

            function formatTemplateLabel(rule) {
                const keywordPart =
                    rule.display_name
                        ? `${rule.keyword}（${rule.display_name}）`
                        : rule.keyword;

                const categoryPart =
                    rule.category_name
                        ? `${rule.category_name} － `
                        : '';

                return categoryPart + keywordPart;
            }

            function refreshTemplateOptions() {
                templateSelect.innerHTML =
                    '<option value="">取引補助設定から選択</option>';

                accountRules().forEach(function (rule) {
                    const option =
                        document.createElement('option');

                    option.value = rule.id;
                    option.textContent =
                        formatTemplateLabel(rule);

                    templateSelect.appendChild(option);
                });
            }

            function applyTemplate(ruleId) {
                const rule = transactionRules.find(
                    (item) => item.id === ruleId
                );

                if (!rule) {
                    return;
                }

                counterpartyInput.value =
                    rule.keyword;

                if (rule.category_id !== null) {
                    categorySelect.value =
                        rule.category_id;
                }

                displayName.textContent =
                    rule.display_name || '-';
            }

            function updateTransactionFields() {
                const type = selectedType();
                const isTransfer = type === 'transfer';
                const isExpense = type === 'expense';
                const accountType = selectedAccountType();
                const isCash = accountType === 'cash';
                const isCreditCard = accountType === 'credit_card';
                const rules = accountRules();

                if (!isTransfer) {
                    filterCategoryOptions();
                }

                const showTemplatePicker =
                    !isTransfer
                    && !isCash
                    && rules.length > 0;

                normalFields.forEach(function (field) {
                    field.hidden = isTransfer;
                });

                transferFields.forEach(function (field) {
                    field.hidden = !isTransfer;
                });

                accountField.hidden = isTransfer;

                if (!isTransfer) {
                    accountLabel.childNodes[0].nodeValue =
                        isExpense
                            ? '支払方法 '
                            : '入金先 ';

                    counterpartyLabel.textContent =
                        isCash
                            ? '表示名'
                            : '取引先';

                    displayNameField.hidden =
                        isCash;
                }

                refreshTemplateOptions();

                templatePicker.classList.toggle(
                    'is-visible',
                    showTemplatePicker
                );

                if (!showTemplatePicker) {
                    templateSelect.value = '';
                }

                const showWithdrawal =
                    !isTransfer
                    && isExpense
                    && isCreditCard;

                withdrawalDateField.hidden =
                    !showWithdrawal;

                withdrawalDateInput.required =
                    showWithdrawal;

                withdrawalRequiredMark.hidden =
                    !showWithdrawal;

                if (!showWithdrawal) {
                    withdrawalDateInput.value = '';
                }

                if (categoryWarning) {
                    categoryWarning.hidden =
                        isTransfer;
                }

                categorySelect.required =
                    !isTransfer;

                accountSelect.required =
                    !isTransfer;

                counterpartyInput.disabled =
                    isTransfer;

                if (!isTransfer && !isCash) {
                    updateDisplayNameAndCategory();
                } else {
                    displayName.textContent = '-';
                }
            }

            typeInputs.forEach(function (input) {
                input.addEventListener(
                    'change',
                    updateTransactionFields
                );
            });

            accountSelect.addEventListener(
                'change',
                function () {
                    templatePicker.classList.remove(
                        'is-visible'
                    );
                    templateSelect.value = '';
                    updateTransactionFields();
                }
            );

            counterpartyInput.addEventListener(
                'input',
                updateDisplayNameAndCategory
            );

            templateSelect.addEventListener(
                'change',
                function () {
                    if (!templateSelect.value) {
                        return;
                    }

                    applyTemplate(
                        templateSelect.value
                    );
                }
            );

            updateTransactionFields();
        });
    </script>
@endpush
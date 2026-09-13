@extends('layouts.app')

@section('title', '取引一覧')

@push('styles')
    <style>
        .transactions-page {
            width: 100%;
        }

        .transactions-header-actions {
            display: flex;
            gap: 10px;
        }

        .button-secondary {
            background: var(--surface);
            color: var(--text);
            border-color: var(--border);
        }

        .button-secondary:hover {
            background: var(--surface-subtle);
        }

        .success-message {
            margin-bottom: 20px;
            padding: 12px 16px;
            border: 1px solid #bbf7d0;
            border-radius: 7px;
            background: #f0fdf4;
            color: #166534;
        }

        /*
        |--------------------------------------------------------------------------
        | フィルター
        |--------------------------------------------------------------------------
        */

        .filter-panel {
            margin-bottom: 24px;
        }

        .filter-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 18px 22px;
            cursor: pointer;
            user-select: none;
        }

        .filter-panel-header:hover {
            background: var(--surface-subtle);
        }

        .filter-panel-toggle {
            color: var(--text-subtle);
            font-size: 14px;
        }

        .filter-panel-body {
            display: none;
            padding: 18px 22px;
            border-top: 1px solid var(--border);
        }

        .filter-panel.is-open .filter-panel-body {
            display: block;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px 20px;
        }

        .filter-item {
            min-width: 0;
        }

        .filter-item label {
            display: block;
            margin-bottom: 6px;
            color: var(--text-subtle);
            font-size: 14px;
            font-weight: 700;
        }

        .filter-control {
            width: 100%;
            height: 38px;
            padding: 7px 10px;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: var(--surface);
            color: var(--text);
        }

        .filter-range {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: 7px;
            align-items: center;
        }

        .filter-range-separator {
            color: var(--text-subtle);
        }

        .expense-filters {
            display: none;
            grid-column: 1 / -1;
        }

        .expense-filters.is-visible {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .filter-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            grid-column: 1 / -1;
            margin-top: 4px;
        }

        /*
        |--------------------------------------------------------------------------
        | 一覧
        |--------------------------------------------------------------------------
        */

        .transactions-table-wrapper {
            overflow-x: auto;
        }

        .transactions-table {
            min-width: 1050px;
        }

        .transactions-table th,
        .transactions-table td {
            white-space: nowrap;
        }

        .transactions-table th {
            text-align: center;
        }

        .transactions-table td.center-cell {
            text-align: center;
        }

        .transactions-table td.left-cell {
            text-align: left;
        }

        .transactions-table td.amount-cell {
            text-align: right;
            font-weight: 600;
            font-variant-numeric: tabular-nums;
        }

        .expense-amount {
            color: var(--expense);
        }

        /*
        |--------------------------------------------------------------------------
        | 経費情報
        |--------------------------------------------------------------------------
        */

        .expense-column {
            display: none;
        }

        .transactions-table.show-expense-columns .expense-column {
            display: table-cell;
        }

        /*
        |--------------------------------------------------------------------------
        | 取引種別
        |--------------------------------------------------------------------------
        */

        .type-badge {
            display: inline-flex;
            align-items: center;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .type-expense {
            background: #fef2f2;
            color: #b91c1c;
        }

        .type-income {
            background: #f0fdf4;
            color: #15803d;
        }

        .type-transfer {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .type-opening {
            background: #f3f4f6;
            color: #4b5563;
        }

        /*
        |--------------------------------------------------------------------------
        | 操作
        |--------------------------------------------------------------------------
        */

        .action-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .action-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 54px;
            height: 30px;
            padding: 0 10px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--surface);
            color: var(--text);
            font-size: 12px;
            font-weight: 600;
            line-height: 1;
            text-decoration: none;
            cursor: pointer;
        }

        .action-button:hover {
            background: var(--surface-subtle);
        }

        .action-edit {
            border-color: #cbd5e1;
            color: #334155;
        }

        .action-duplicate {
            border-color: #93c5fd;
            color: #1d4ed8;
            background: #eff6ff;
        }

        .action-duplicate:hover {
            background: #dbeafe;
        }

        .action-delete {
            border-color: #fecaca;
            color: #b91c1c;
            background: #fef2f2;
        }

        .action-delete:hover {
            background: #fee2e2;
        }

        /*
        |--------------------------------------------------------------------------
        | チェックボックス
        |--------------------------------------------------------------------------
        */

        .status-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .status-checkbox:disabled {
            cursor: default;
            opacity: 0.4;
        }

        .status-saving {
            opacity: 0.5;
        }
    </style>
@endpush

@section('content')
    <div class="transactions-page">
        <header class="page-header">
            <div>
                <h1 class="page-title">
                    取引一覧
                </h1>
            </div>

            <div class="transactions-header-actions">
                <button
                    type="button"
                    id="toggle-expense-columns"
                    class="button button-secondary"
                >
                    経費情報を表示
                </button>

                <a
                    href="{{ route('transactions.create') }}"
                    class="button button-primary"
                >
                    ＋ 取引登録
                </a>
            </div>
        </header>

        @if (session('success'))
            <div class="success-message">
                {{ session('success') }}
            </div>
        @endif

        {{-- フィルター --}}
        <section
            id="filter-panel"
            class="panel filter-panel"
        >
            <div
                id="filter-panel-header"
                class="filter-panel-header"
            >
                <h2 class="panel-title">
                    フィルター
                </h2>

                <span
                    id="filter-panel-toggle"
                    class="filter-panel-toggle"
                >
                    ▼
                </span>
            </div>

            <div class="filter-panel-body">
                <form
                    method="GET"
                    action="{{ route('transactions.index') }}"
                    id="transaction-filter-form"
                >
                    <div class="filter-grid">
                        {{-- 日付 --}}
                        <div class="filter-item">
                            <label>
                                日付
                            </label>

                            <div class="filter-range">
                                <input
                                    type="date"
                                    name="date_from"
                                    value="{{ request('date_from') }}"
                                    class="filter-control"
                                >

                                <span class="filter-range-separator">
                                    ～
                                </span>

                                <input
                                    type="date"
                                    name="date_to"
                                    value="{{ request('date_to') }}"
                                    class="filter-control"
                                >
                            </div>
                        </div>

                        {{-- 取引種別 --}}
                        <div class="filter-item">
                            <label for="type">
                                取引種別
                            </label>

                            <select
                                id="type"
                                name="type"
                                class="filter-control"
                            >
                                <option value="">
                                    すべて
                                </option>

                                <option
                                    value="expense"
                                    @selected(request('type') === 'expense')
                                >
                                    支出
                                </option>

                                <option
                                    value="income"
                                    @selected(request('type') === 'income')
                                >
                                    収入
                                </option>

                                <option
                                    value="transfer"
                                    @selected(request('type') === 'transfer')
                                >
                                    振替
                                </option>

                                <option
                                    value="opening_balance"
                                    @selected(request('type') === 'opening_balance')
                                >
                                    初期残高
                                </option>
                            </select>
                        </div>

                        {{-- 口座 --}}
                        <div class="filter-item">
                            <label for="account_id">
                                口座
                            </label>

                            <select
                                id="account_id"
                                name="account_id"
                                class="filter-control"
                            >
                                <option value="">
                                    すべて
                                </option>

                                @foreach ($accounts as $account)
                                    <option
                                        value="{{ $account->id }}"
                                        @selected(
                                            (string) request('account_id')
                                            === (string) $account->id
                                        )
                                    >
                                        {{ $account->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- カテゴリ --}}
                        <div class="filter-item">
                            <label for="category_id">
                                カテゴリ
                            </label>

                            <select
                                id="category_id"
                                name="category_id"
                                class="filter-control"
                            >
                                <option value="">
                                    すべて
                                </option>

                                @foreach ($categories as $category)
                                    <option
                                        value="{{ $category->id }}"
                                        @selected(
                                            (string) request('category_id')
                                            === (string) $category->id
                                        )
                                    >
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 取引先 --}}
                        <div class="filter-item">
                            <label for="counterparty">
                                取引先
                            </label>

                            <input
                                type="text"
                                id="counterparty"
                                name="counterparty"
                                value="{{ request('counterparty') }}"
                                class="filter-control"
                                placeholder="取引先を検索"
                            >
                        </div>

                        {{-- 引落日 --}}
                        <div class="filter-item">
                            <label>
                                引落日
                            </label>

                            <div class="filter-range">
                                <input
                                    type="date"
                                    name="withdrawal_date_from"
                                    value="{{ request('withdrawal_date_from') }}"
                                    class="filter-control"
                                >

                                <span class="filter-range-separator">
                                    ～
                                </span>

                                <input
                                    type="date"
                                    name="withdrawal_date_to"
                                    value="{{ request('withdrawal_date_to') }}"
                                    class="filter-control"
                                >
                            </div>
                        </div>

                        {{-- 経費情報フィルター --}}
                        <div
                            id="expense-filters"
                            class="expense-filters"
                        >
                            {{-- 経費対象 --}}
                            <div class="filter-item">
                                <label for="has_expense_ratio">
                                    経費対象
                                </label>

                                <select
                                    id="has_expense_ratio"
                                    name="has_expense_ratio"
                                    class="filter-control"
                                >
                                    <option value="">
                                        すべて
                                    </option>

                                    <option
                                        value="1"
                                        @selected(
                                            request('has_expense_ratio') === '1'
                                        )
                                    >
                                        あり
                                    </option>

                                    <option
                                        value="0"
                                        @selected(
                                            request('has_expense_ratio') === '0'
                                        )
                                    >
                                        なし
                                    </option>
                                </select>
                            </div>

                            {{-- 経費登録 --}}
                            <div class="filter-item">
                                <label for="expense_registered">
                                    経費登録
                                </label>

                                <select
                                    id="expense_registered"
                                    name="expense_registered"
                                    class="filter-control"
                                >
                                    <option value="">
                                        すべて
                                    </option>

                                    <option
                                        value="0"
                                        @selected(
                                            request('expense_registered') === '0'
                                        )
                                    >
                                        未
                                    </option>

                                    <option
                                        value="1"
                                        @selected(
                                            request('expense_registered') === '1'
                                        )
                                    >
                                        済
                                    </option>
                                </select>
                            </div>

                            {{-- 領収書保存 --}}
                            <div class="filter-item">
                                <label for="receipt_saved">
                                    領収書保存
                                </label>

                                <select
                                    id="receipt_saved"
                                    name="receipt_saved"
                                    class="filter-control"
                                >
                                    <option value="">
                                        すべて
                                    </option>

                                    <option
                                        value="0"
                                        @selected(
                                            request('receipt_saved') === '0'
                                        )
                                    >
                                        未
                                    </option>

                                    <option
                                        value="1"
                                        @selected(
                                            request('receipt_saved') === '1'
                                        )
                                    >
                                        済
                                    </option>
                                </select>
                            </div>
                        </div>

                        {{-- 操作 --}}
                        <div class="filter-actions">
                            <button
                                type="submit"
                                class="button button-primary"
                            >
                                絞り込む
                            </button>

                            <a
                                href="{{ route('transactions.index') }}"
                                class="button button-secondary"
                            >
                                クリア
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        {{-- 取引一覧 --}}
        <section class="panel">
            <div class="panel-header">
                <h2 class="panel-title">
                    取引

                    <span class="text-muted">
                        {{ number_format($transactions->count()) }}件
                    </span>
                </h2>
            </div>

            @if ($transactions->isEmpty())
                <div class="panel-body">
                    <p class="empty-message">
                        条件に一致する取引はありません。
                    </p>
                </div>
            @else
                <div class="transactions-table-wrapper">
                    <table
                        id="transactions-table"
                        class="data-table transactions-table"
                    >
                        <thead>
                            <tr>
                                <th>
                                    日付
                                </th>

                                <th>
                                    取引種別
                                </th>

                                <th>
                                    口座
                                </th>

                                <th>
                                    カテゴリ
                                </th>

                                <th>
                                    取引先
                                </th>

                                <th>
                                    金額
                                </th>

                                <th>
                                    引落日
                                </th>

                                <th class="expense-column">
                                    経費割合
                                </th>

                                <th class="expense-column">
                                    経費登録
                                </th>

                                <th class="expense-column">
                                    領収書保存
                                </th>

                                <th>
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
                                        $matchedRule =
                                            $transactionRules->first(
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
                                    {{-- 日付 --}}
                                    <td class="center-cell">
                                        {{ $transaction
                                            ->transaction_date
                                            ->format('Y年m月d日') }}
                                    </td>

                                    {{-- 取引種別 --}}
                                    <td class="center-cell">
                                        @if ($transaction->type->value === 'expense')
                                            <span class="type-badge type-expense">
                                                支出
                                            </span>
                                        @elseif ($transaction->type->value === 'income')
                                            <span class="type-badge type-income">
                                                収入
                                            </span>
                                        @elseif ($transaction->type->value === 'transfer')
                                            <span class="type-badge type-transfer">
                                                振替
                                            </span>
                                        @elseif ($transaction->type->value === 'opening_balance')
                                            <span class="type-badge type-opening">
                                                初期残高
                                            </span>
                                        @endif
                                    </td>

                                    {{-- 口座 --}}
                                    <td class="left-cell">
                                        @if ($transaction->type->value === 'transfer')
                                            {{ $transaction->account?->name ?? '-' }}

                                            →

                                            {{ $transaction
                                                ->outgoingTransfer
                                                ?->toTransaction
                                                ?->account
                                                ?->name ?? '-' }}
                                        @else
                                            {{ $transaction->account?->name ?? '-' }}
                                        @endif
                                    </td>

                                    {{-- カテゴリ --}}
                                    <td class="center-cell">
                                        @if ($isNormalTransaction)
                                            {{ $transaction->category?->name ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- 取引先 --}}
                                    <td class="left-cell">
                                        @if ($isNormalTransaction)
                                            {{ $displayName ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- 金額 --}}
                                    <td
                                        class="
                                            amount-cell
                                            {{ $transaction->type->value === 'expense'
                                                ? 'expense-amount'
                                                : '' }}
                                        "
                                    >
                                        {{ number_format($transaction->amount) }}円
                                    </td>

                                    {{-- 引落日 --}}
                                    <td class="center-cell">
                                        @if ($isNormalTransaction)
                                            {{ $transaction
                                                ->withdrawal_date
                                                ?->format('Y年m月d日') ?? '-' }}
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- 経費割合 --}}
                                    <td class="expense-column center-cell">
                                        @if ($isNormalTransaction)
                                            {{ number_format(
                                                (float) $transaction->expense_ratio,
                                                0
                                            ) }}%
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- 経費登録 --}}
                                    <td class="expense-column center-cell">
                                        @if ($isNormalTransaction)
                                            <input
                                                type="checkbox"
                                                class="status-checkbox"
                                                data-transaction-id="{{ $transaction->id }}"
                                                data-field="expense_registered"
                                                @checked(
                                                    $transaction->expense_registered
                                                )
                                            >
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- 領収書保存 --}}
                                    <td class="expense-column center-cell">
                                        @if ($isNormalTransaction)
                                            <input
                                                type="checkbox"
                                                class="status-checkbox"
                                                data-transaction-id="{{ $transaction->id }}"
                                                data-field="receipt_saved"
                                                @checked(
                                                    $transaction->receipt_saved
                                                )
                                            >
                                        @else
                                            -
                                        @endif
                                    </td>

                                    {{-- 操作 --}}
                                    <td class="center-cell">
                                        <div class="action-group">
                                            @if (
                                                $transaction->type->value
                                                === 'opening_balance'
                                            )
                                                -
                                            @else
                                                <a
                                                    href="{{ route(
                                                        'transactions.edit',
                                                        $transaction
                                                    ) }}"
                                                    class="action-button action-edit"
                                                >
                                                    編集
                                                </a>

                                                <a
                                                    href="{{ route(
                                                        'transactions.duplicate',
                                                        $transaction
                                                    ) }}"
                                                    class="action-button action-duplicate"
                                                >
                                                    複製
                                                </a>

                                                <form
                                                    method="POST"
                                                    action="{{ route(
                                                        'transactions.destroy',
                                                        $transaction
                                                    ) }}"
                                                >
                                                    @csrf
                                                    @method('DELETE')

                                                    <button
                                                        type="submit"
                                                        class="action-button action-delete"
                                                        onclick="
                                                            return confirm(
                                                                'この取引を削除しますか？'
                                                            )
                                                        "
                                                    >
                                                        削除
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            /*
            |--------------------------------------------------------------------------
            | フィルター開閉
            |--------------------------------------------------------------------------
            */

            const filterPanel =
                document.getElementById('filter-panel');

            const filterPanelHeader =
                document.getElementById('filter-panel-header');

            const filterPanelToggle =
                document.getElementById('filter-panel-toggle');

            const hasFilter =
                @json(
                    request()->filled('date_from')
                    || request()->filled('date_to')
                    || request()->filled('type')
                    || request()->filled('account_id')
                    || request()->filled('category_id')
                    || request()->filled('counterparty')
                    || request()->filled('withdrawal_date_from')
                    || request()->filled('withdrawal_date_to')
                    || request()->filled('has_expense_ratio')
                    || request()->filled('expense_registered')
                    || request()->filled('receipt_saved')
                );

            function setFilterVisible(visible) {
                filterPanel.classList.toggle(
                    'is-open',
                    visible
                );

                filterPanelToggle.textContent =
                    visible ? '▲' : '▼';
            }

            /*
             * 初期状態：
             *
             * 条件なし → 閉じる
             * 条件あり → 開く
             *
             * 「クリア」は query string がなくなるため、
             * 自動的に閉じた状態へ戻る。
             */
            setFilterVisible(hasFilter);

            filterPanelHeader.addEventListener(
                'click',
                function () {
                    setFilterVisible(
                        !filterPanel.classList.contains(
                            'is-open'
                        )
                    );
                }
            );

            /*
            |--------------------------------------------------------------------------
            | 経費情報表示切替
            |--------------------------------------------------------------------------
            */

            const expenseToggleButton =
                document.getElementById('toggle-expense-columns');

            const table =
                document.getElementById('transactions-table');

            const expenseFilters =
                document.getElementById('expense-filters');

            const expenseStorageKey =
                'transactions-show-expense-information';

            function setExpenseInformationVisible(visible) {
                if (table) {
                    table.classList.toggle(
                        'show-expense-columns',
                        visible
                    );
                }

                expenseFilters.classList.toggle(
                    'is-visible',
                    visible
                );

                expenseToggleButton.textContent =
                    visible
                        ? '経費情報を非表示'
                        : '経費情報を表示';

                localStorage.setItem(
                    expenseStorageKey,
                    visible ? '1' : '0'
                );
            }

            const hasExpenseFilter =
                @json(
                    request()->filled('has_expense_ratio')
                    || request()->filled('expense_registered')
                    || request()->filled('receipt_saved')
                );

            const storedExpenseVisible =
                localStorage.getItem(
                    expenseStorageKey
                ) === '1';

            setExpenseInformationVisible(
                hasExpenseFilter || storedExpenseVisible
            );

            expenseToggleButton.addEventListener(
                'click',
                function () {
                    setExpenseInformationVisible(
                        !expenseFilters.classList.contains(
                            'is-visible'
                        )
                    );
                }
            );

            /*
            |--------------------------------------------------------------------------
            | 経費登録・領収書保存
            |--------------------------------------------------------------------------
            */

            document
                .querySelectorAll('.status-checkbox')
                .forEach(function (checkbox) {
                    checkbox.addEventListener(
                        'change',
                        async function () {
                            const previousValue =
                                !checkbox.checked;

                            checkbox.disabled = true;

                            checkbox.classList.add(
                                'status-saving'
                            );

                            try {
                                const response = await fetch(
                                    `/transactions/${checkbox.dataset.transactionId}/status`,
                                    {
                                        method: 'PATCH',

                                        headers: {
                                            'Content-Type':
                                                'application/json',

                                            'Accept':
                                                'application/json',

                                            'X-CSRF-TOKEN':
                                                '{{ csrf_token() }}',
                                        },

                                        body: JSON.stringify({
                                            field:
                                                checkbox.dataset.field,

                                            value:
                                                checkbox.checked,
                                        }),
                                    }
                                );

                                if (!response.ok) {
                                    throw new Error(
                                        '更新に失敗しました。'
                                    );
                                }
                            } catch (error) {
                                checkbox.checked =
                                    previousValue;

                                alert(
                                    '状態を更新できませんでした。'
                                );
                            } finally {
                                checkbox.disabled = false;

                                checkbox.classList.remove(
                                    'status-saving'
                                );
                            }
                        }
                    );
                });
        });
    </script>
@endpush
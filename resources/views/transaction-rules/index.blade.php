@extends('layouts.app')

@section('title', '取引補助設定')

@push('styles')
    <style>
        .template-page {
            max-width: 1100px;
            margin: 0 auto;
        }

        .template-page-header {
            margin-bottom: 24px;
        }

        .template-page-title {
            margin: 0;
            font-size: 28px;
        }

        .template-success,
        .template-errors {
            margin-bottom: 20px;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .template-success {
            background: #ecfdf5;
            color: #047857;
        }

        .template-errors {
            background: #fef2f2;
            color: #b91c1c;
        }

        .template-errors ul {
            margin: 0;
            padding-left: 20px;
        }

        .template-account {
            margin-bottom: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--surface);
        }

        .template-account-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 14px 18px;
            cursor: pointer;
            user-select: none;
        }

        .template-account-header:hover {
            background: var(--surface-subtle);
        }

        .template-account-title {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .template-account-name {
            margin: 0;
            font-size: 16px;
            font-weight: 700;
            color: var(--text);
        }

        .template-count {
            color: var(--text-subtle);
            font-size: 13px;
        }

        .template-header-right {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .template-toggle {
            color: var(--text-subtle);
            font-size: 14px;
        }

        .template-account-body {
            display: none;
            border-top: 1px solid var(--border);
        }

        .template-account.is-open
        .template-account-body {
            display: block;
        }

        .template-toolbar {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            background: var(--surface-subtle);
        }

        .template-add-button,
        .template-save-button,
        .template-delete-button {
            border: 0;
            border-radius: 7px;
            font: inherit;
            cursor: pointer;
        }

        .template-add-button {
            padding: 7px 12px;
            background: #e5e7eb;
            color: #111827;
        }

        .template-save-button {
            padding: 7px 16px;
            background: #2563eb;
            color: #fff;
            font-weight: 700;
        }

        .template-delete-button {
            padding: 5px 10px;
            background: #fee2e2;
            color: #b91c1c;
        }

        .template-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .template-table th,
        .template-table td {
            padding: 8px 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .template-table th {
            background: var(--surface);
            color: var(--text-subtle);
            font-size: 13px;
            font-weight: 600;
            text-align: left;
        }

        .template-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .keyword-column {
            width: 30%;
        }

        .display-name-column {
            width: 30%;
        }

        .category-column {
            width: 27%;
        }

        .action-column {
            width: 90px;
            text-align: center !important;
        }

        .template-input,
        .template-select {
            box-sizing: border-box;
            width: 100%;
            min-height: 32px;
            padding: 5px 8px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            background: #fff;
            font: inherit;
        }

        .template-empty-row td {
            padding: 20px;
            color: var(--text-subtle);
            text-align: center;
        }

        .template-no-accounts {
            padding: 24px;
            border: 1px solid var(--border);
            border-radius: 12px;
            background: var(--surface);
            color: var(--text-subtle);
        }
    </style>
@endpush

@section('content')
    @php
        $oldAccountId = (int) old(
            '_template_account_id',
            0
        );

        $savedAccountId = (int) session(
            'saved_template_account_id',
            0
        );

        $savedRows = session(
            'saved_template_rows',
            []
        );
    @endphp

    <div class="template-page">
        <header class="page-header template-page-header">
            <h1 class="page-title">
                取引補助設定
            </h1>
        </header>

        @if (session('success'))
            <div class="template-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="template-errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($accounts->isEmpty())
            <div class="template-no-accounts">
                取引補助設定を設定できる
                銀行口座・クレジットカードが
                登録されていません。
            </div>
        @else
            @foreach ($accounts as $account)
                @php
                    $databaseRows =
                        $transactionRulesByAccount
                            ->get(
                                $account->id,
                                collect()
                            )
                            ->map(
                                fn ($rule) => [
                                    'id' => $rule->id,
                                    'keyword' =>
                                        $rule->keyword,
                                    'display_name' =>
                                        $rule->display_name,
                                    'category_id' =>
                                        $rule->category_id,
                                ]
                            )
                            ->values()
                            ->all();

                    $hasValidationError =
                        $oldAccountId === $account->id
                        && $errors->any();

                    $wasJustSaved =
                        $savedAccountId === $account->id;

                    if ($hasValidationError) {
                        $rows = old(
                            'transaction_rules',
                            []
                        );
                    } elseif ($wasJustSaved) {
                        $rows = $savedRows;
                    } else {
                        $rows = $databaseRows;
                    }

                    $shouldOpen =
                        $hasValidationError
                        || $wasJustSaved;
                @endphp

                <form
                    method="POST"
                    action="{{ route(
                        'transaction-rules.bulk-update',
                        $account
                    ) }}"
                    class="template-account
                        {{ $shouldOpen ? 'is-open' : '' }}"
                    data-template-form
                >
                    @csrf
                    @method('PUT')

                    <input
                        type="hidden"
                        name="_template_account_id"
                        value="{{ $account->id }}"
                    >

                    <div
                        class="template-account-header"
                        data-account-header
                    >
                        <div class="template-account-title">
                            <h2 class="template-account-name">
                                {{ $account->name }}
                            </h2>

                            <span
                                class="template-count"
                                data-template-count
                            >
                                {{ count($rows) }}件
                            </span>
                        </div>

                        <div class="template-header-right">
                            <span
                                class="template-toggle"
                                data-account-toggle
                            >
                                {{ $shouldOpen ? '▲' : '▼' }}
                            </span>
                        </div>
                    </div>

                    <div class="template-account-body">
                        <div class="template-toolbar">
                            <button
                                type="button"
                                class="template-add-button"
                                data-add-row
                            >
                                ＋ 追加
                            </button>

                            <button
                                type="submit"
                                class="template-save-button"
                            >
                                保存
                            </button>
                        </div>

                        <table class="template-table">
                            <thead>
                                <tr>
                                    <th class="keyword-column">
                                        キーワード
                                    </th>

                                    <th class="display-name-column">
                                        表示名
                                    </th>

                                    <th class="category-column">
                                        カテゴリ
                                    </th>

                                    <th class="action-column">
                                        操作
                                    </th>
                                </tr>
                            </thead>

                            <tbody data-template-rows>
                                @forelse ($rows as $index => $row)
                                    <tr data-template-row>
                                        <td>
                                            <input
                                                type="hidden"
                                                data-field="id"
                                                name="transaction_rules[{{ $index }}][id]"
                                                value="{{ $row['id'] ?? '' }}"
                                            >

                                            <input
                                                type="text"
                                                class="template-input"
                                                data-field="keyword"
                                                name="transaction_rules[{{ $index }}][keyword]"
                                                value="{{ $row['keyword'] ?? '' }}"
                                                maxlength="255"
                                                required
                                            >
                                        </td>

                                        <td>
                                            <input
                                                type="text"
                                                class="template-input"
                                                data-field="display_name"
                                                name="transaction_rules[{{ $index }}][display_name]"
                                                value="{{ $row['display_name'] ?? '' }}"
                                                maxlength="255"
                                            >
                                        </td>

                                        <td>
                                            <select
                                                class="template-select"
                                                data-field="category_id"
                                                name="transaction_rules[{{ $index }}][category_id]"
                                            >
                                                <option value="">
                                                    未設定
                                                </option>

                                                @foreach ($categories as $category)
                                                    <option
                                                        value="{{ $category->id }}"
                                                        @selected(
                                                            (string) (
                                                                $row['category_id']
                                                                ?? ''
                                                            )
                                                            ===
                                                            (string) $category->id
                                                        )
                                                    >
                                                        {{ $category->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>

                                        <td class="action-column">
                                            <button
                                                type="button"
                                                class="template-delete-button"
                                                data-remove-row
                                            >
                                                削除
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr
                                        class="template-empty-row"
                                        data-empty-row
                                    >
                                        <td colspan="4">
                                            取引補助設定は
                                            まだ登録されていません。
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            @endforeach
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            () => {
                const categories = @json(
                    $categories
                        ->map(
                            fn ($category) => [
                                'id' => $category->id,
                                'name' => $category->name,
                            ]
                        )
                        ->values()
                );

                function escapeHtml(value) {
                    return String(value)
                        .replaceAll('&', '&amp;')
                        .replaceAll('<', '&lt;')
                        .replaceAll('>', '&gt;')
                        .replaceAll('"', '&quot;')
                        .replaceAll("'", '&#039;');
                }

                function createCategoryOptions() {
                    const options = [
                        '<option value="">未設定</option>',
                    ];

                    categories.forEach(
                        (category) => {
                            options.push(
                                `<option value="${category.id}">
                                    ${escapeHtml(category.name)}
                                </option>`
                            );
                        }
                    );

                    return options.join('');
                }

                function setOpen(form, open) {
                    form.classList.toggle(
                        'is-open',
                        open
                    );

                    const toggle =
                        form.querySelector(
                            '[data-account-toggle]'
                        );

                    toggle.textContent =
                        open ? '▲' : '▼';
                }

                function updateCount(form) {
                    const count =
                        form.querySelectorAll(
                            '[data-template-row]'
                        ).length;

                    form.querySelector(
                        '[data-template-count]'
                    ).textContent =
                        `${count}件`;
                }

                function reindexRows(form) {
                    const rows =
                        form.querySelectorAll(
                            '[data-template-row]'
                        );

                    rows.forEach(
                        (row, index) => {
                            row
                                .querySelectorAll(
                                    '[data-field]'
                                )
                                .forEach(
                                    (field) => {
                                        field.name =
                                            `transaction_rules[${index}][${field.dataset.field}]`;
                                    }
                                );
                        }
                    );
                }

                function updateEmptyRow(form) {
                    const tbody =
                        form.querySelector(
                            '[data-template-rows]'
                        );

                    const rows =
                        tbody.querySelectorAll(
                            '[data-template-row]'
                        );

                    const emptyRow =
                        tbody.querySelector(
                            '[data-empty-row]'
                        );

                    if (rows.length === 0) {
                        if (!emptyRow) {
                            const row =
                                document.createElement(
                                    'tr'
                                );

                            row.className =
                                'template-empty-row';

                            row.dataset.emptyRow = '';

                            row.innerHTML = `
                                <td colspan="4">
                                    取引補助設定は
                                    まだ登録されていません。
                                </td>
                            `;

                            tbody.appendChild(row);
                        }
                    } else if (emptyRow) {
                        emptyRow.remove();
                    }

                    updateCount(form);
                }

                function addRow(form) {
                    const tbody =
                        form.querySelector(
                            '[data-template-rows]'
                        );

                    const row =
                        document.createElement(
                            'tr'
                        );

                    row.dataset.templateRow = '';

                    row.innerHTML = `
                        <td>
                            <input
                                type="hidden"
                                data-field="id"
                                value=""
                            >

                            <input
                                type="text"
                                class="template-input"
                                data-field="keyword"
                                maxlength="255"
                                required
                            >
                        </td>

                        <td>
                            <input
                                type="text"
                                class="template-input"
                                data-field="display_name"
                                maxlength="255"
                            >
                        </td>

                        <td>
                            <select
                                class="template-select"
                                data-field="category_id"
                            >
                                ${createCategoryOptions()}
                            </select>
                        </td>

                        <td class="action-column">
                            <button
                                type="button"
                                class="template-delete-button"
                                data-remove-row
                            >
                                削除
                            </button>
                        </td>
                    `;

                    tbody.appendChild(row);

                    reindexRows(form);
                    updateEmptyRow(form);
                    setOpen(form, true);

                    row
                        .querySelector(
                            '[data-field="keyword"]'
                        )
                        .focus();
                }

                document
                    .querySelectorAll(
                        '[data-template-form]'
                    )
                    .forEach(
                        (form) => {
                            const header =
                                form.querySelector(
                                    '[data-account-header]'
                                );

                            const addButton =
                                form.querySelector(
                                    '[data-add-row]'
                                );

                            header.addEventListener(
                                'click',
                                () => {
                                    setOpen(
                                        form,
                                        !form.classList.contains(
                                            'is-open'
                                        )
                                    );
                                }
                            );

                            addButton.addEventListener(
                                'click',
                                () => {
                                    addRow(form);
                                }
                            );

                            form.addEventListener(
                                'click',
                                (event) => {
                                    const removeButton =
                                        event.target.closest(
                                            '[data-remove-row]'
                                        );

                                    if (!removeButton) {
                                        return;
                                    }

                                    const row =
                                        removeButton.closest(
                                            '[data-template-row]'
                                        );

                                    if (!row) {
                                        return;
                                    }

                                    row.remove();

                                    reindexRows(form);
                                    updateEmptyRow(form);
                                }
                            );

                            reindexRows(form);
                            updateEmptyRow(form);
                        }
                    );
            }
        );
    </script>
@endpush
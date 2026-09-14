@extends('layouts.app')

@section('title', 'カテゴリ管理')

@push('styles')
    <style>
        .master-page {
            max-width: 760px;
            margin: 0 auto;
        }

        .master-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            margin-bottom: 20px;
        }

        .master-header h1 {
            margin: 0;
            font-size: 28px;
        }

        .master-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .master-save-button,
        .master-add-button,
        .master-delete-button {
            border: 0;
            border-radius: 7px;
            font: inherit;
            cursor: pointer;
        }

        .master-save-button {
            padding: 9px 20px;
            background: #2563eb;
            color: #fff;
            font-weight: 700;
        }

        .master-add-button {
            padding: 9px 14px;
            background: #e5e7eb;
            color: #111827;
        }

        .master-delete-button {
            padding: 5px 10px;
            background: #fee2e2;
            color: #b91c1c;
        }

        .master-panel {
            overflow: hidden;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
        }

        .master-table {
            width: 100%;
            border-collapse: collapse;
        }

        .master-table th,
        .master-table td {
            padding: 8px 12px;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: middle;
        }

        .master-table th {
            background: #f9fafb;
            color: #4b5563;
            font-size: 13px;
            text-align: left;
        }

        .master-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .master-row {
            background: #fff;
        }

        .master-row.is-dragging {
            opacity: 0.45;
        }

        .master-row.drag-over {
            box-shadow: inset 0 2px 0 #2563eb;
        }

        .drag-cell {
            width: 44px;
            text-align: center;
        }

        .drag-handle {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: #6b7280;
            cursor: grab;
            user-select: none;
            font-size: 18px;
            line-height: 1;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .master-input {
            box-sizing: border-box;
            width: 100%;
            min-height: 32px;
            padding: 5px 8px;
            border: 1px solid #d1d5db;
            border-radius: 7px;
            background: #fff;
            font: inherit;
        }

        .action-cell {
            width: 88px;
            text-align: center;
        }

        .alert-success,
        .alert-error {
            margin-bottom: 16px;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .alert-success {
            background: #ecfdf5;
            color: #047857;
        }

        .alert-error {
            background: #fef2f2;
            color: #b91c1c;
        }

        .alert-error ul {
            margin: 0;
            padding-left: 20px;
        }
    </style>
@endpush

@section('content')
    <div class="master-page">
        <form
            id="category-bulk-form"
            method="POST"
            action="{{ route('categories.bulk-update') }}"
        >
            @csrf
            @method('PUT')

            <div class="master-header">
                <h1>カテゴリ管理</h1>

                <div class="master-actions">
                    <button
                        id="add-category-button"
                        class="master-add-button"
                        type="button"
                    >
                        ＋ カテゴリを追加
                    </button>

                    <button
                        class="master-save-button"
                        type="submit"
                    >
                        保存
                    </button>
                </div>
            </div>

            @if (session('success'))
                <div class="alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-error">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>
                                {{ $error }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="master-panel">
                <table class="master-table">
                    <thead>
                        <tr>
                            <th class="drag-cell"></th>
                            <th>カテゴリ名</th>
                            <th class="action-cell">操作</th>
                        </tr>
                    </thead>

                    <tbody id="category-rows">
                        @foreach ($categories as $index => $category)
                            <tr class="master-row">
                                <td class="drag-cell">
                                    <span
                                        class="drag-handle"
                                        draggable="true"
                                        title="ドラッグして並び替え"
                                    >
                                        ☰
                                    </span>
                                </td>

                                <td>
                                    <input
                                        type="hidden"
                                        data-field="id"
                                        name="categories[{{ $index }}][id]"
                                        value="{{ $category->id }}"
                                    >

                                    <input
                                        class="master-input"
                                        type="text"
                                        data-field="name"
                                        name="categories[{{ $index }}][name]"
                                        value="{{ old(
                                            'categories.' . $index . '.name',
                                            $category->name
                                        ) }}"
                                        maxlength="100"
                                        required
                                    >
                                </td>

                                <td class="action-cell">
                                    <button
                                        class="master-delete-button"
                                        type="submit"
                                        form="delete-category-{{ $category->id }}"
                                        onclick="return confirm('このカテゴリを削除しますか？')"
                                    >
                                        削除
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>

        @foreach ($categories as $category)
            <form
                id="delete-category-{{ $category->id }}"
                method="POST"
                action="{{ route(
                    'categories.destroy',
                    $category
                ) }}"
            >
                @csrf
                @method('DELETE')
            </form>
        @endforeach
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tbody =
                document.getElementById('category-rows');

            const addButton =
                document.getElementById(
                    'add-category-button'
                );

            let draggingRow = null;

            function reindexRows() {
                const rows =
                    tbody.querySelectorAll('.master-row');

                rows.forEach((row, index) => {
                    row
                        .querySelectorAll('[data-field]')
                        .forEach((field) => {
                            const fieldName =
                                field.dataset.field;

                            field.name =
                                `categories[${index}][${fieldName}]`;
                        });
                });
            }

            function addCategoryRow() {
                const row =
                    document.createElement('tr');

                row.className = 'master-row';

                row.innerHTML = `
                    <td class="drag-cell">
                        <span
                            class="drag-handle"
                            draggable="true"
                            title="ドラッグして並び替え"
                        >
                            ☰
                        </span>
                    </td>

                    <td>
                        <input
                            type="hidden"
                            data-field="id"
                            value=""
                        >

                        <input
                            class="master-input"
                            type="text"
                            data-field="name"
                            maxlength="100"
                            required
                        >
                    </td>

                    <td class="action-cell">
                        <button
                            class="master-delete-button"
                            type="button"
                            data-remove-new-row
                        >
                            削除
                        </button>
                    </td>
                `;

                tbody.appendChild(row);

                attachRowEvents(row);
                reindexRows();

                row
                    .querySelector('[data-field="name"]')
                    .focus();
            }

            function attachRowEvents(row) {
                const handle =
                    row.querySelector('.drag-handle');

                handle.addEventListener(
                    'dragstart',
                    () => {
                        draggingRow = row;

                        row.classList.add(
                            'is-dragging'
                        );
                    }
                );

                handle.addEventListener(
                    'dragend',
                    () => {
                        row.classList.remove(
                            'is-dragging'
                        );

                        tbody
                            .querySelectorAll(
                                '.drag-over'
                            )
                            .forEach((target) => {
                                target.classList.remove(
                                    'drag-over'
                                );
                            });

                        draggingRow = null;

                        reindexRows();
                    }
                );

                const removeButton =
                    row.querySelector(
                        '[data-remove-new-row]'
                    );

                if (removeButton) {
                    removeButton.addEventListener(
                        'click',
                        () => {
                            row.remove();
                            reindexRows();
                        }
                    );
                }
            }

            tbody.addEventListener(
                'dragover',
                (event) => {
                    event.preventDefault();

                    if (!draggingRow) {
                        return;
                    }

                    const targetRow =
                        event.target.closest(
                            '.master-row'
                        );

                    if (
                        !targetRow
                        || targetRow === draggingRow
                    ) {
                        return;
                    }

                    tbody
                        .querySelectorAll('.drag-over')
                        .forEach((row) => {
                            row.classList.remove(
                                'drag-over'
                            );
                        });

                    targetRow.classList.add(
                        'drag-over'
                    );

                    const rect =
                        targetRow
                            .getBoundingClientRect();

                    const insertAfter =
                        event.clientY
                        > rect.top
                            + rect.height / 2;

                    if (insertAfter) {
                        targetRow.after(
                            draggingRow
                        );
                    } else {
                        targetRow.before(
                            draggingRow
                        );
                    }
                }
            );

            tbody.addEventListener(
                'drop',
                (event) => {
                    event.preventDefault();

                    tbody
                        .querySelectorAll('.drag-over')
                        .forEach((row) => {
                            row.classList.remove(
                                'drag-over'
                            );
                        });

                    reindexRows();
                }
            );

            tbody
                .querySelectorAll('.master-row')
                .forEach(attachRowEvents);

            addButton.addEventListener(
                'click',
                addCategoryRow
            );

            reindexRows();
        });
    </script>
@endpush
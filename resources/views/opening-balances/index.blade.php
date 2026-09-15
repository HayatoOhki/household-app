@extends('layouts.app')

@section('title', '初期残高管理')

@push('styles')
    <style>
        .opening-balance-page {
            max-width: 980px;
            margin: 0 auto;
        }

        .opening-balance-page-header {
            margin-bottom: 24px;
        }

        .opening-balance-success,
        .opening-balance-errors {
            margin-bottom: 20px;
            padding: 10px 14px;
            border-radius: 8px;
        }

        .opening-balance-success {
            background: #ecfdf5;
            color: #047857;
        }

        .opening-balance-errors {
            background: #fef2f2;
            color: #b91c1c;
        }

        .opening-balance-errors ul {
            margin: 0;
            padding-left: 20px;
        }

        .opening-balance-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .opening-balance-table th,
        .opening-balance-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .opening-balance-table th {
            background: var(--surface-subtle);
            color: var(--text-subtle);
            font-size: 13px;
            font-weight: 700;
            text-align: left;
        }

        .opening-balance-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .account-column {
            width: 38%;
        }

        .amount-column {
            width: 34%;
        }

        .action-column {
            width: 28%;
            text-align: center !important;
        }

        .account-name {
            font-weight: 700;
        }

        .amount-wrapper {
            position: relative;
        }

        .amount-input {
            width: 100%;
            height: 38px;
            padding: 7px 38px 7px 10px;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: var(--surface);
            color: var(--text);
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .amount-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgb(37 99 235 / 10%);
        }

        .amount-suffix {
            position: absolute;
            top: 50%;
            right: 12px;
            color: var(--text-subtle);
            transform: translateY(-50%);
            pointer-events: none;
        }

        .row-actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .row-button {
            min-height: 34px;
            padding: 6px 12px;
            border: 0;
            border-radius: 7px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
        }

        .register-button,
        .save-button {
            background: var(--primary);
            color: #fff;
        }

        .register-button:hover,
        .save-button:hover {
            background: var(--primary-hover);
        }

        .delete-button {
            background: #fee2e2;
            color: #b91c1c;
        }

        .delete-button:hover {
            background: #fecaca;
        }

        .opening-balance-empty {
            padding: 28px;
            color: var(--text-subtle);
            text-align: center;
        }
    </style>
@endpush

@section('content')
    @php
        $errorAccountId = (string) old(
            '_opening_balance_account_id',
            ''
        );
    @endphp

    <div class="opening-balance-page">
        <header class="page-header opening-balance-page-header">
            <h1 class="page-title">
                初期残高管理
            </h1>
        </header>

        @if (session('success'))
            <div class="opening-balance-success">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="opening-balance-errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>
                            {{ $error }}
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="panel">
            @if ($accounts->isEmpty())
                <div class="opening-balance-empty">
                    初期残高を設定できる口座がありません。
                </div>
            @else
                <table class="opening-balance-table">
                    <thead>
                        <tr>
                            <th class="account-column">
                                口座
                            </th>

                            <th class="amount-column">
                                初期残高・残債
                            </th>

                            <th class="action-column">
                                操作
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($accounts as $account)
                            @php
                                $openingBalance =
                                    $openingBalancesByAccount
                                        ->get($account->id);

                                $formId =
                                    'opening-balance-form-'
                                    . $account->id;

                                $hasRowError =
                                    $errorAccountId
                                    === (string) $account->id;

                                $amountValue =
                                    $hasRowError
                                        ? old('amount')
                                        : (
                                            $openingBalance
                                                ? $openingBalance->amount
                                                : ''
                                        );
                            @endphp

                            <tr>
                                <td>
                                    <div class="account-name">
                                        {{ $account->name }}
                                    </div>
                                </td>

                                <td>
                                    <div class="amount-wrapper">
                                        <input
                                            type="number"
                                            name="amount"
                                            value="{{ $amountValue }}"
                                            min="0"
                                            step="1"
                                            class="amount-input"
                                            form="{{ $formId }}"
                                            required
                                        >

                                        <span class="amount-suffix">
                                            円
                                        </span>
                                    </div>
                                </td>

                                <td class="action-column">
                                    <div class="row-actions">
                                        <form
                                            id="{{ $formId }}"
                                            method="POST"
                                            action="{{
                                                $openingBalance
                                                    ? route(
                                                        'opening-balances.update',
                                                        $openingBalance
                                                    )
                                                    : route(
                                                        'opening-balances.store'
                                                    )
                                            }}"
                                        >
                                            @csrf

                                            @if ($openingBalance)
                                                @method('PUT')
                                            @endif

                                            <input
                                                type="hidden"
                                                name="account_id"
                                                value="{{ $account->id }}"
                                            >

                                            <input
                                                type="hidden"
                                                name="_opening_balance_account_id"
                                                value="{{ $account->id }}"
                                            >

                                            <button
                                                type="submit"
                                                class="row-button
                                                    {{
                                                        $openingBalance
                                                            ? 'save-button'
                                                            : 'register-button'
                                                    }}"
                                            >
                                                {{ $openingBalance
                                                    ? '保存'
                                                    : '登録' }}
                                            </button>
                                        </form>

                                        @if ($openingBalance)
                                            <form
                                                method="POST"
                                                action="{{
                                                    route(
                                                        'opening-balances.destroy',
                                                        $openingBalance
                                                    )
                                                }}"
                                                onsubmit="
                                                    return confirm(
                                                        'この初期残高を削除しますか？'
                                                    );
                                                "
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="row-button delete-button"
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
            @endif
        </section>
    </div>
@endsection
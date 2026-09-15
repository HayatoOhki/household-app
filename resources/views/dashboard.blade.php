@extends('layouts.app')

@section('title', 'ダッシュボード')

@push('styles')
    <style>
        .dashboard-content {
            max-width: 1200px;
            margin: 0 auto;
            padding-top: 80px;
        }

        .dashboard-today {
            margin: 0;
            color: var(--text-subtle);
            font-size: 16px;
            font-weight: 500;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }

        .summary-card {
            padding: 22px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .summary-card-label {
            margin: 0 0 8px;
            color: var(--text-subtle);
            font-size: 13px;
            font-weight: 600;
        }

        .summary-card-value {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .summary-card-income .summary-card-value {
            color: var(--income);
        }

        .summary-card-expense .summary-card-value {
            color: var(--expense);
        }

        .summary-card-balance .summary-card-value {
            color: var(--balance);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr);
            gap: 24px;
            align-items: start;
        }

        .dashboard-grid .panel {
            margin-top: 0;
        }

        .dashboard-side-column {
            display: grid;
            gap: 24px;
        }

        .withdrawal-date {
            white-space: nowrap;
        }

        .no-history {
            color: var(--text-subtle);
        }

        .account-balance {
            font-weight: 600;
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-content">
        <header class="page-header">
            <div>
                <h1 class="page-title">
                    ダッシュボード
                </h1>
            </div>

            <p class="dashboard-today">
                {{ now()->format('Y年m月d日') }}
            </p>
        </header>

        <main>
            <section class="summary-grid">
                <div class="summary-card summary-card-income">
                    <p class="summary-card-label">
                        今月の収入
                    </p>

                    <p class="summary-card-value">
                        {{ number_format($monthlyIncome) }}
                        円
                    </p>
                </div>

                <div class="summary-card summary-card-expense">
                    <p class="summary-card-label">
                        今月の支出
                    </p>

                    <p class="summary-card-value">
                        {{ number_format($monthlyExpense) }}
                        円
                    </p>
                </div>

                <div class="summary-card summary-card-balance">
                    <p class="summary-card-label">
                        今月の収支
                    </p>

                    <p class="summary-card-value">
                        {{ number_format($monthlyBalance) }}
                        円
                    </p>
                </div>
            </section>

            <div class="dashboard-grid">
                <section class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            クレジットカード引落予定
                        </h2>
                    </div>

                    @if ($creditCardWithdrawals->isEmpty())
                        <div class="panel-body">
                            <p class="empty-message">
                                クレジットカードが登録されていません。
                            </p>
                        </div>
                    @else
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>
                                        クレジットカード
                                    </th>

                                    <th>
                                        引落予定日
                                    </th>

                                    <th class="amount">
                                        引落予定額
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach (
                                    $creditCardWithdrawals
                                    as $creditCardWithdrawal
                                )
                                    <tr>
                                        <td>
                                            {{ $creditCardWithdrawal['account_name'] }}
                                        </td>

                                        <td class="withdrawal-date">
                                            @if (
                                                $creditCardWithdrawal['withdrawal_date']
                                                !== null
                                            )
                                                {{ $creditCardWithdrawal['withdrawal_date']
                                                    ->format('Y年m月d日') }}
                                            @else
                                                <span class="no-history">
                                                    履歴なし
                                                </span>
                                            @endif
                                        </td>

                                        <td class="amount">
                                            {{ number_format(
                                                $creditCardWithdrawal['amount']
                                            ) }}
                                            円
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>

                <div class="dashboard-side-column">
                <section class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            口座残高
                        </h2>
                    </div>

                    @if ($accountBalances->isEmpty())
                        <div class="panel-body">
                            <p class="empty-message">
                                現金・銀行・電子マネー口座が登録されていません。
                            </p>
                        </div>
                    @else
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>
                                        口座
                                    </th>

                                    <th class="amount">
                                        現在残高
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach (
                                    $accountBalances
                                    as $accountBalance
                                )
                                    <tr>
                                        <td>
                                            {{ $accountBalance['account_name'] }}
                                        </td>

                                        <td
                                            class="
                                                amount
                                                account-balance
                                            "
                                        >
                                            {{ number_format(
                                                $accountBalance['balance']
                                            ) }}
                                            円
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>

                <section class="panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            借入残高
                        </h2>
                    </div>

                    @if ($liabilityBalances->isEmpty())
                        <div class="panel-body">
                            <p class="empty-message">
                                借入口座が登録されていません。
                            </p>
                        </div>
                    @else
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>借入先</th>
                                    <th class="amount">残債</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($liabilityBalances as $liabilityBalance)
                                    <tr>
                                        <td>{{ $liabilityBalance['account_name'] }}</td>
                                        <td class="amount account-balance">
                                            {{ number_format($liabilityBalance['balance']) }} 円
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </section>
                </div>
            </div>
        </main>
    </div>
@endsection
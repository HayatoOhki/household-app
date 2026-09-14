<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SummaryService
{
    public function getAnnualSummary(
        User $user,
        int $year
    ): array {
        $startDate = CarbonImmutable::create(
            $year,
            1,
            1
        )->startOfDay();

        $endDate = $startDate
            ->endOfYear()
            ->endOfDay();

        $transactions = $user
            ->transactions()
            ->with([
                'account',
                'category',
            ])
            ->whereBetween(
                'transaction_date',
                [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]
            )
            ->get();

        $averageMonthCount =
            $this->getAverageMonthCount($year);

        $incomeTransactions =
            $transactions->filter(
                fn ($transaction) =>
                    $transaction->type
                    === TransactionType::INCOME
            );

        $expenseTransactions =
            $transactions->filter(
                fn ($transaction) =>
                    $transaction->type
                    === TransactionType::EXPENSE
            );

        $incomeMonths =
            $this->sumTransactionsByMonth(
                $incomeTransactions
            );

        $expenseMonths =
            $this->sumTransactionsByMonth(
                $expenseTransactions
            );

        $balanceMonths = [];

        for ($month = 1; $month <= 12; $month++) {
            $balanceMonths[$month] =
                $incomeMonths[$month]
                - $expenseMonths[$month];
        }

        $summaryRows = collect([
            $this->makeAnnualRow(
                '収入',
                $incomeMonths,
                $averageMonthCount
            ),
            $this->makeAnnualRow(
                '支出',
                $expenseMonths,
                $averageMonthCount
            ),
            $this->makeAnnualRow(
                '収支',
                $balanceMonths,
                $averageMonthCount
            ),
        ]);

        /*
         * カテゴリ別支出
         *
         * 取引を起点にせず、
         * ユーザーのカテゴリマスタを起点にする。
         *
         * そのため対象年度に支出が0件でも
         * 必ず年間収支へ表示される。
         */
        $categories = $user
            ->categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categoryRows = $categories
            ->map(function ($category) use (
                $expenseTransactions,
                $averageMonthCount
            ) {
                $categoryTransactions =
                    $expenseTransactions->where(
                        'category_id',
                        $category->id
                    );

                return $this->makeAnnualRow(
                    $category->name,
                    $this->sumTransactionsByMonth(
                        $categoryTransactions
                    ),
                    $averageMonthCount
                );
            })
            ->values();

        /*
         * カテゴリ削除などによって
         * category_id = null の支出が存在する場合のみ、
         * 「未分類」を末尾に表示する。
         */
        $uncategorizedTransactions =
            $expenseTransactions->filter(
                fn ($transaction) =>
                    $transaction->category_id === null
            );

        if ($uncategorizedTransactions->isNotEmpty()) {
            $categoryRows->push(
                $this->makeAnnualRow(
                    '未分類',
                    $this->sumTransactionsByMonth(
                        $uncategorizedTransactions
                    ),
                    $averageMonthCount
                )
            );
        }

        /*
         * クレジットカード別支出
         *
         * 全クレジットカード口座を表示。
         * 利用がない年度でも0円で表示する。
         */
        $creditCards = $user
            ->accounts()
            ->where(
                'type',
                AccountType::CREDIT_CARD->value
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $creditCardRows = $creditCards
            ->map(function ($account) use (
                $expenseTransactions,
                $averageMonthCount
            ) {
                $accountTransactions =
                    $expenseTransactions->where(
                        'account_id',
                        $account->id
                    );

                return $this->makeAnnualRow(
                    $account->name,
                    $this->sumTransactionsByMonth(
                        $accountTransactions
                    ),
                    $averageMonthCount
                );
            })
            ->values();

        /*
         * 口座別支出
         *
         * 現金・銀行をすべて表示。
         * クレジットカードは上の専用セクションへ分離。
         */
        $normalAccounts = $user
            ->accounts()
            ->whereIn(
                'type',
                [
                    AccountType::CASH->value,
                    AccountType::BANK->value,
                ]
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $accountRows = $normalAccounts
            ->map(function ($account) use (
                $expenseTransactions,
                $averageMonthCount
            ) {
                $accountTransactions =
                    $expenseTransactions->where(
                        'account_id',
                        $account->id
                    );

                return $this->makeAnnualRow(
                    $account->name,
                    $this->sumTransactionsByMonth(
                        $accountTransactions
                    ),
                    $averageMonthCount
                );
            })
            ->values();

        return [
            'year' => $year,
            'previousYear' => $year - 1,
            'nextYear' => $year + 1,
            'averageMonthCount' =>
                $averageMonthCount,
            'summaryRows' => $summaryRows,
            'categoryRows' => $categoryRows,
            'creditCardRows' =>
                $creditCardRows,
            'accountRows' => $accountRows,
        ];
    }

    private function sumTransactionsByMonth(
        Collection $transactions
    ): array {
        $months = array_fill(
            1,
            12,
            0
        );

        foreach ($transactions as $transaction) {
            $month = (int) $transaction
                ->transaction_date
                ->format('n');

            $months[$month] +=
                (int) $transaction->amount;
        }

        return $months;
    }

    private function makeAnnualRow(
        string $label,
        array $months,
        int $averageMonthCount
    ): array {
        $total = array_sum($months);

        $averageTargetTotal = 0;

        for (
            $month = 1;
            $month <= $averageMonthCount;
            $month++
        ) {
            $averageTargetTotal +=
                $months[$month];
        }

        return [
            'label' => $label,
            'months' => $months,
            'average' =>
                $averageMonthCount > 0
                    ? (int) round(
                        $averageTargetTotal
                        / $averageMonthCount
                    )
                    : 0,
            'total' => $total,
        ];
    }

    private function getAverageMonthCount(
        int $year
    ): int {
        $now = CarbonImmutable::now();

        if ($year < $now->year) {
            return 12;
        }

        if ($year === $now->year) {
            return $now->month;
        }

        return 12;
    }

    public function getMonthlySummary(
        User $user,
        string $month
    ): array {
        $startDate = CarbonImmutable::parse(
            $month . '-01'
        )->startOfMonth();

        $endDate =
            $startDate->endOfMonth();

        $monthlyTransactions = $user
            ->transactions()
            ->with('category')
            ->whereBetween(
                'transaction_date',
                [
                    $startDate->toDateString(),
                    $endDate->toDateString(),
                ]
            )
            ->get();

        $monthlyIncome =
            $monthlyTransactions
                ->filter(
                    fn ($transaction) =>
                        $transaction->type
                        === TransactionType::INCOME
                )
                ->sum('amount');

        $monthlyExpense =
            $monthlyTransactions
                ->filter(
                    fn ($transaction) =>
                        $transaction->type
                        === TransactionType::EXPENSE
                )
                ->sum('amount');

        $monthlyBalance =
            $monthlyIncome
            - $monthlyExpense;

        $categoryExpenses =
            $monthlyTransactions
                ->filter(
                    fn ($transaction) =>
                        $transaction->type
                        === TransactionType::EXPENSE
                )
                ->groupBy('category_id')
                ->map(function ($transactions) {
                    $first =
                        $transactions->first();

                    return [
                        'category_name' =>
                            $first
                                ->category
                                ?->name
                            ?? '未分類',

                        'amount' =>
                            $transactions
                                ->sum('amount'),
                    ];
                })
                ->sortByDesc('amount')
                ->values();

        return [
            'month' => $month,
            'monthlyIncome' =>
                $monthlyIncome,
            'monthlyExpense' =>
                $monthlyExpense,
            'monthlyBalance' =>
                $monthlyBalance,
            'categoryExpenses' =>
                $categoryExpenses,
        ];
    }

    public function getAccountBalances(
        User $user
    ): Collection {
        $accounts = $user
            ->accounts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $allTransactions = $user
            ->transactions()
            ->with([
                'outgoingTransfer',
                'incomingTransfer',
            ])
            ->get();

        return $accounts
            ->map(
                function ($account) use (
                    $allTransactions
                ) {
                    $transactions =
                        $allTransactions->where(
                            'account_id',
                            $account->id
                        );

                    $balance = 0;

                    foreach (
                        $transactions
                        as $transaction
                    ) {
                        if (
                            $transaction->type
                            ===
                            TransactionType::OPENING_BALANCE
                        ) {
                            $balance +=
                                $transaction->amount;

                            continue;
                        }

                        if (
                            $transaction->type
                            ===
                            TransactionType::INCOME
                        ) {
                            $balance +=
                                $transaction->amount;

                            continue;
                        }

                        if (
                            $transaction->type
                            ===
                            TransactionType::EXPENSE
                        ) {
                            $balance -=
                                $transaction->amount;

                            continue;
                        }

                        if (
                            $transaction->type
                            ===
                            TransactionType::TRANSFER
                        ) {
                            if (
                                $transaction
                                    ->outgoingTransfer
                                !== null
                            ) {
                                $balance -=
                                    $transaction->amount;
                            }

                            if (
                                $transaction
                                    ->incomingTransfer
                                !== null
                            ) {
                                $balance +=
                                    $transaction->amount;
                            }
                        }
                    }

                    return [
                        'account_id' =>
                            $account->id,
                        'account_name' =>
                            $account->name,
                        'account_type' =>
                            $account->type,
                        'balance' =>
                            $balance,
                    ];
                }
            );
    }

    public function getDashboardAccountBalances(
        User $user
    ): Collection {
        return $this
            ->getAccountBalances($user)
            ->filter(
                fn (
                    array $accountBalance
                ) =>
                    $accountBalance[
                        'account_type'
                    ]
                    !==
                    AccountType::CREDIT_CARD
            )
            ->values();
    }

    public function getCreditCardWithdrawals(
        User $user
    ): Collection {
        $today =
            CarbonImmutable::today();

        $creditCards = $user
            ->accounts()
            ->where(
                'type',
                AccountType::CREDIT_CARD->value
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return $creditCards
            ->map(
                function ($account) use (
                    $user,
                    $today
                ) {
                    $transactions = $user
                        ->transactions()
                        ->where(
                            'account_id',
                            $account->id
                        )
                        ->whereNotNull(
                            'withdrawal_date'
                        )
                        ->whereDate(
                            'withdrawal_date',
                            '>=',
                            $today
                                ->toDateString()
                        )
                        ->orderBy(
                            'withdrawal_date'
                        )
                        ->get();

                    $nextWithdrawalDate =
                        $transactions
                            ->first()
                            ?->withdrawal_date;

                    if (
                        $nextWithdrawalDate
                        === null
                    ) {
                        return [
                            'account_id' =>
                                $account->id,
                            'account_name' =>
                                $account->name,
                            'withdrawal_date' =>
                                null,
                            'amount' => 0,
                        ];
                    }

                    $amount = $transactions
                        ->filter(
                            fn (
                                $transaction
                            ) =>
                                $transaction
                                    ->withdrawal_date
                                    ->isSameDay(
                                        $nextWithdrawalDate
                                    )
                        )
                        ->sum('amount');

                    return [
                        'account_id' =>
                            $account->id,
                        'account_name' =>
                            $account->name,
                        'withdrawal_date' =>
                            CarbonImmutable::parse(
                                $nextWithdrawalDate
                            ),
                        'amount' =>
                            $amount,
                    ];
                }
            );
    }
}
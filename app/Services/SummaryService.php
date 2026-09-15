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

        $categories = $user
            ->categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $incomeCategories = $categories->filter(
            fn ($category) => $category->type->value === 'income'
        );
        $expenseCategories = $categories->filter(
            fn ($category) => $category->type->value === 'expense'
        );

        $incomeCategoryRows = $this->makeCategoryRows(
            $incomeCategories,
            $incomeTransactions,
            $averageMonthCount
        );
        $expenseCategoryRows = $this->makeCategoryRows(
            $expenseCategories,
            $expenseTransactions,
            $averageMonthCount
        );

        $creditCards = $user->accounts()
            ->where('type', AccountType::CREDIT_CARD->value)
            ->orderBy('sort_order')->orderBy('id')->get();

        $creditCardRows = $creditCards->map(function ($account) use ($expenseTransactions, $averageMonthCount) {
            return $this->makeAnnualRow(
                $account->name,
                $this->sumTransactionsByMonth($expenseTransactions->where('account_id', $account->id)),
                $averageMonthCount
            );
        })->values();

        $normalAccounts = $user->accounts()
            ->whereIn('type', [
                AccountType::CASH->value,
                AccountType::BANK->value,
                AccountType::E_MONEY->value,
            ])
            ->orderBy('sort_order')->orderBy('id')->get();

        $balanceTransactions = $user->transactions()
            ->with(['outgoingTransfer', 'incomingTransfer'])
            ->whereDate('transaction_date', '<=', $endDate->toDateString())
            ->get();

        $accountRows = $normalAccounts->map(function ($account) use ($balanceTransactions, $year) {
            $months = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthEnd = CarbonImmutable::create($year, $month, 1)->endOfMonth();
                $balance = 0;
                foreach ($balanceTransactions->where('account_id', $account->id) as $transaction) {
                    if ($transaction->transaction_date->gt($monthEnd)) {
                        continue;
                    }
                    if ($transaction->type === TransactionType::OPENING_BALANCE
                        || $transaction->type === TransactionType::INCOME) {
                        $balance += (int) $transaction->amount;
                    } elseif ($transaction->type === TransactionType::EXPENSE) {
                        $balance -= (int) $transaction->amount;
                    } elseif ($transaction->type === TransactionType::TRANSFER) {
                        if ($transaction->outgoingTransfer !== null) {
                            $balance -= (int) $transaction->amount;
                        }
                        if ($transaction->incomingTransfer !== null) {
                            $balance += (int) $transaction->amount;
                        }
                    }
                }
                $months[$month] = $balance;
            }
            return ['label' => $account->name, 'months' => $months, 'average' => null, 'total' => null];
        })->values();

        $liabilityAccounts = $user->accounts()
            ->where('type', AccountType::LIABILITY->value)
            ->orderBy('sort_order')->orderBy('id')->get();

        $liabilityRows = $liabilityAccounts->map(function ($account) use ($balanceTransactions, $year) {
            $months = [];
            for ($month = 1; $month <= 12; $month++) {
                $monthEnd = CarbonImmutable::create($year, $month, 1)->endOfMonth();
                $balance = 0;
                foreach ($balanceTransactions->where('account_id', $account->id) as $transaction) {
                    if ($transaction->transaction_date->gt($monthEnd)) {
                        continue;
                    }
                    if ($transaction->type === TransactionType::OPENING_BALANCE) {
                        $balance += (int) $transaction->amount;
                    } elseif ($transaction->type === TransactionType::TRANSFER) {
                        if ($transaction->outgoingTransfer !== null) {
                            $balance += (int) $transaction->amount;
                        }
                        if ($transaction->incomingTransfer !== null) {
                            $balance -= (int) $transaction->amount;
                        }
                    }
                }
                $months[$month] = $balance;
            }
            return ['label' => $account->name, 'months' => $months, 'average' => null, 'total' => null];
        })->values();

        return [
            'year' => $year,
            'previousYear' => $year - 1,
            'nextYear' => $year + 1,
            'averageMonthCount' =>
                $averageMonthCount,
            'summaryRows' => $summaryRows,
            'incomeCategoryRows' => $incomeCategoryRows,
            'expenseCategoryRows' => $expenseCategoryRows,
            'creditCardRows' =>
                $creditCardRows,
            'accountRows' => $accountRows,
            'liabilityRows' => $liabilityRows,
        ];
    }

    private function makeCategoryRows(
        Collection $categories,
        Collection $transactions,
        int $averageMonthCount
    ): Collection {
        $rows = $categories->map(function ($category) use ($transactions, $averageMonthCount) {
            return $this->makeAnnualRow(
                $category->name,
                $this->sumTransactionsByMonth($transactions->where('category_id', $category->id)),
                $averageMonthCount
            );
        })->values();

        $uncategorized = $transactions->whereNull('category_id');
        if ($uncategorized->isNotEmpty()) {
            $rows->push($this->makeAnnualRow(
                '未分類',
                $this->sumTransactionsByMonth($uncategorized),
                $averageMonthCount
            ));
        }

        return $rows;
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
                    $isLiability =
                        $account->type === AccountType::LIABILITY;

                    foreach (
                        $transactions
                        as $transaction
                    ) {
                        if ($isLiability) {
                            if (
                                $transaction->type
                                === TransactionType::OPENING_BALANCE
                            ) {
                                $balance += $transaction->amount;
                                continue;
                            }

                            if (
                                $transaction->type
                                === TransactionType::TRANSFER
                            ) {
                                if ($transaction->outgoingTransfer !== null) {
                                    $balance += $transaction->amount;
                                }

                                if ($transaction->incomingTransfer !== null) {
                                    $balance -= $transaction->amount;
                                }
                            }

                            continue;
                        }
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
                    !== AccountType::CREDIT_CARD
                    && $accountBalance['account_type']
                    !== AccountType::LIABILITY
            )
            ->values();
    }

    public function getDashboardLiabilityBalances(
        User $user
    ): Collection {
        return $this
            ->getAccountBalances($user)
            ->filter(
                fn (array $accountBalance) =>
                    $accountBalance['account_type']
                    === AccountType::LIABILITY
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
                            'sort_order' =>
                                $account->sort_order,
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
                        'sort_order' =>
                            $account->sort_order,
                    ];
                }
            )
            ->sort(function (
                array $left,
                array $right
            ): int {
                if (
                    $left['withdrawal_date'] === null
                    && $right['withdrawal_date'] !== null
                ) {
                    return 1;
                }

                if (
                    $left['withdrawal_date'] !== null
                    && $right['withdrawal_date'] === null
                ) {
                    return -1;
                }

                if (
                    $left['withdrawal_date'] !== null
                    && $right['withdrawal_date'] !== null
                ) {
                    $dateComparison =
                        $left['withdrawal_date']
                            ->getTimestamp()
                        <=>
                        $right['withdrawal_date']
                            ->getTimestamp();

                    if ($dateComparison !== 0) {
                        return $dateComparison;
                    }
                }

                $sortOrderComparison =
                    $left['sort_order']
                    <=>
                    $right['sort_order'];

                if ($sortOrderComparison !== 0) {
                    return $sortOrderComparison;
                }

                return $left['account_id']
                    <=>
                    $right['account_id'];
            })
            ->map(function (array $withdrawal): array {
                unset($withdrawal['sort_order']);

                return $withdrawal;
            })
            ->values();
    }
}
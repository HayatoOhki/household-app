<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class SummaryService
{
    /**
     * @return array{
     *     month: string,
     *     monthlyIncome: int,
     *     monthlyExpense: int,
     *     monthlyBalance: int,
     *     categoryExpenses: Collection
     * }
     */
    public function getMonthlySummary(
        User $user,
        string $month
    ): array {
        $startDate = CarbonImmutable::parse(
            $month . '-01'
        )->startOfMonth();

        $endDate = $startDate->endOfMonth();

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

        $monthlyIncome = $monthlyTransactions
            ->filter(
                fn ($transaction) =>
                    $transaction->type
                    === TransactionType::INCOME
            )
            ->sum('amount');

        $monthlyExpense = $monthlyTransactions
            ->filter(
                fn ($transaction) =>
                    $transaction->type
                    === TransactionType::EXPENSE
            )
            ->sum('amount');

        $monthlyBalance =
            $monthlyIncome - $monthlyExpense;

        $categoryExpenses = $monthlyTransactions
            ->filter(
                fn ($transaction) =>
                    $transaction->type
                    === TransactionType::EXPENSE
            )
            ->groupBy('category_id')
            ->map(function ($transactions) {
                $first = $transactions->first();

                return [
                    'category_name' =>
                        $first->category?->name
                        ?? '未分類',

                    'amount' =>
                        $transactions->sum('amount'),
                ];
            })
            ->sortByDesc('amount')
            ->values();

        return [
            'month' => $month,
            'monthlyIncome' => $monthlyIncome,
            'monthlyExpense' => $monthlyExpense,
            'monthlyBalance' => $monthlyBalance,
            'categoryExpenses' => $categoryExpenses,
        ];
    }

    /**
     * @return Collection<int, array{
     *     account_id: int,
     *     account_name: string,
     *     balance: int
     * }>
     */
    public function getAccountBalances(
        User $user
    ): Collection {
        $accounts = $user
            ->accounts()
            ->orderBy('name')
            ->get();

        $allTransactions = $user
            ->transactions()
            ->with([
                'outgoingTransfer',
                'incomingTransfer',
            ])
            ->get();

        return $accounts
            ->map(function ($account) use ($allTransactions) {
                $transactions = $allTransactions
                    ->where(
                        'account_id',
                        $account->id
                    );

                $balance = 0;

                foreach ($transactions as $transaction) {
                    if (
                        $transaction->type
                        === TransactionType::OPENING_BALANCE
                    ) {
                        $balance += $transaction->amount;

                        continue;
                    }

                    if (
                        $transaction->type
                        === TransactionType::INCOME
                    ) {
                        $balance += $transaction->amount;

                        continue;
                    }

                    if (
                        $transaction->type
                        === TransactionType::EXPENSE
                    ) {
                        $balance -= $transaction->amount;

                        continue;
                    }

                    if (
                        $transaction->type
                        === TransactionType::TRANSFER
                    ) {
                        if (
                            $transaction->outgoingTransfer
                            !== null
                        ) {
                            $balance -= $transaction->amount;
                        }

                        if (
                            $transaction->incomingTransfer
                            !== null
                        ) {
                            $balance += $transaction->amount;
                        }
                    }
                }

                return [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'balance' => $balance,
                ];
            });
    }
}
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * 口座間の資金移動を登録する。
     *
     * @return array{from: Transaction, to: Transaction, transfer: Transfer}
     */
    public function createTransfer(
        User $user,
        Account $fromAccount,
        Account $toAccount,
        string $transactionDate,
        string|int|float $amount,
    ): array {
        if ($fromAccount->id === $toAccount->id) {
            throw new \InvalidArgumentException(
                '振替元と振替先には異なる口座を指定してください。'
            );
        }

        if (
            $fromAccount->user_id !== $user->id
            || $toAccount->user_id !== $user->id
        ) {
            throw new \InvalidArgumentException(
                '振替元と振替先には自分の口座を指定してください。'
            );
        }

        if ((float) $amount <= 0) {
            throw new \InvalidArgumentException(
                '振替金額には0より大きい値を指定してください。'
            );
        }

        return DB::transaction(function () use (
            $user,
            $fromAccount,
            $toAccount,
            $transactionDate,
            $amount,
        ): array {
            $fromTransaction = Transaction::create([
                'user_id' => $user->id,
                'transaction_date' => $transactionDate,
                'type' => 'transfer',
                'account_id' => $fromAccount->id,
                'category_id' => null,
                'counterparty_name' => null,
                'amount' => $amount,
                'expense_ratio' => 0,
                'expense_registered' => false,
                'receipt_saved' => false,
            ]);

            $toTransaction = Transaction::create([
                'user_id' => $user->id,
                'transaction_date' => $transactionDate,
                'type' => 'transfer',
                'account_id' => $toAccount->id,
                'amount' => $amount,
                'category_id' => null,
                'counterparty_name' => null,
                'expense_ratio' => 0,
                'expense_registered' => false,
                'receipt_saved' => false,
            ]);

            $transfer = Transfer::create([
                'from_transaction_id' => $fromTransaction->id,
                'to_transaction_id' => $toTransaction->id,
            ]);

            return [
                'from' => $fromTransaction,
                'to' => $toTransaction,
                'transfer' => $transfer,
            ];
        });
    }
}
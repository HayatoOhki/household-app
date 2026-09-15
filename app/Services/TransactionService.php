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
        int $amount,
        ?int $categoryId = null,
    ): array {
        $this->validateTransferAccounts(
            $user,
            $fromAccount,
            $toAccount
        );

        $this->validateTransferAmount($amount);

        return DB::transaction(function () use (
            $user,
            $fromAccount,
            $toAccount,
            $transactionDate,
            $amount,
            $categoryId,
        ): array {
            $fromTransaction = Transaction::create([
                'user_id' => $user->id,
                'transaction_date' => $transactionDate,
                'type' => 'transfer',
                'account_id' => $fromAccount->id,
                'category_id' => $categoryId,
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
                'category_id' => $categoryId,
                'counterparty_name' => null,
                'amount' => $amount,
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

    /**
     * 口座間の資金移動を更新する。
     */
    public function updateTransfer(
        User $user,
        Transfer $transfer,
        Account $fromAccount,
        Account $toAccount,
        string $transactionDate,
        int $amount,
        ?int $categoryId = null,
    ): void {
        $this->validateTransferOwnership(
            $user,
            $transfer
        );

        $this->validateTransferAccounts(
            $user,
            $fromAccount,
            $toAccount
        );

        $this->validateTransferAmount($amount);

        DB::transaction(function () use (
            $transfer,
            $fromAccount,
            $toAccount,
            $transactionDate,
            $amount,
            $categoryId,
        ): void {
            $transfer->fromTransaction->update([
                'transaction_date' => $transactionDate,
                'account_id' => $fromAccount->id,
                'amount' => $amount,
                'category_id' => $categoryId,
            ]);

            $transfer->toTransaction->update([
                'transaction_date' => $transactionDate,
                'account_id' => $toAccount->id,
                'amount' => $amount,
                'category_id' => $categoryId,
            ]);
        });
    }

    /**
     * 口座間の資金移動を削除する。
     */
    public function deleteTransfer(
        User $user,
        Transfer $transfer,
    ): void {
        $this->validateTransferOwnership(
            $user,
            $transfer
        );

        DB::transaction(function () use ($transfer): void {
            $fromTransaction = $transfer->fromTransaction;
            $toTransaction = $transfer->toTransaction;

            $transfer->delete();

            $fromTransaction->delete();
            $toTransaction->delete();
        });
    }

    /**
     * 振替元・振替先口座を検証する。
     */
    private function validateTransferAccounts(
        User $user,
        Account $fromAccount,
        Account $toAccount,
    ): void {
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
    }

    /**
     * 振替金額を検証する。
     */
    private function validateTransferAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException(
                '振替金額には1円以上を指定してください。'
            );
        }
    }

    /**
     * 振替が操作対象ユーザーのものか検証する。
     */
    private function validateTransferOwnership(
        User $user,
        Transfer $transfer,
    ): void {
        $transfer->loadMissing([
            'fromTransaction',
            'toTransaction',
        ]);

        if (
            $transfer->fromTransaction === null
            || $transfer->toTransaction === null
            || $transfer->fromTransaction->user_id !== $user->id
            || $transfer->toTransaction->user_id !== $user->id
        ) {
            abort(404);
        }
    }
}
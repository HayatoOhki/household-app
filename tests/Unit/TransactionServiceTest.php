<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_transfer_creates_two_transactions_and_transfer(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
        ]);

        $toAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        $result = app(TransactionService::class)->createTransfer(
            user: $user,
            fromAccount: $fromAccount,
            toAccount: $toAccount,
            transactionDate: '2026-08-15',
            amount: 30000,
        );

        $this->assertInstanceOf(Transaction::class, $result['from']);
        $this->assertInstanceOf(Transaction::class, $result['to']);
        $this->assertInstanceOf(Transfer::class, $result['transfer']);

        $this->assertSame('transfer', $result['from']->type->value);
        $this->assertSame('transfer', $result['to']->type->value);

        $this->assertSame($fromAccount->id, $result['from']->account_id);
        $this->assertSame($toAccount->id, $result['to']->account_id);

        $this->assertSame('30000.00', $result['from']->amount);
        $this->assertSame('30000.00', $result['to']->amount);

        $this->assertSame(
            $result['from']->id,
            $result['transfer']->from_transaction_id
        );

        $this->assertSame(
            $result['to']->id,
            $result['transfer']->to_transaction_id
        );

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('transfers', 1);
    }

    public function test_create_transfer_rejects_same_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(TransactionService::class)->createTransfer(
            user: $user,
            fromAccount: $account,
            toAccount: $account,
            transactionDate: '2026-08-15',
            amount: 30000,
        );
    }

    public function test_create_transfer_rejects_account_belonging_to_another_user(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $fromAccount = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
        ]);

        $otherUserAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '現金',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(TransactionService::class)->createTransfer(
            user: $user,
            fromAccount: $fromAccount,
            toAccount: $otherUserAccount,
            transactionDate: '2026-08-15',
            amount: 30000,
        );
    }

public function test_create_transfer_rejects_zero_amount(): void
{
    $user = User::factory()->create();

    $fromAccount = Account::create([
        'user_id' => $user->id,
        'name' => '三井住友銀行',
    ]);

    $toAccount = Account::create([
        'user_id' => $user->id,
        'name' => '現金',
    ]);

    $this->expectException(\InvalidArgumentException::class);

    app(TransactionService::class)->createTransfer(
        user: $user,
        fromAccount: $fromAccount,
        toAccount: $toAccount,
        transactionDate: '2026-08-15',
        amount: 0,
    );
}

    public function test_create_transfer_rejects_negative_amount(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
        ]);

        $toAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(TransactionService::class)->createTransfer(
            user: $user,
            fromAccount: $fromAccount,
            toAccount: $toAccount,
            transactionDate: '2026-08-15',
            amount: -1000,
        );
    }

    public function test_create_transfer_accepts_minimum_positive_amount(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
        ]);

        $toAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        $result = app(TransactionService::class)->createTransfer(
            user: $user,
            fromAccount: $fromAccount,
            toAccount: $toAccount,
            transactionDate: '2026-08-15',
            amount: '0.01',
        );

        $this->assertSame('0.01', $result['from']->amount);
        $this->assertSame('0.01', $result['to']->amount);
    }

    public function test_create_transfer_sets_expected_transaction_attributes(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
        ]);

        $toAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        $result = app(TransactionService::class)->createTransfer(
            user: $user,
            fromAccount: $fromAccount,
            toAccount: $toAccount,
            transactionDate: '2026-08-15',
            amount: 30000,
        );

        foreach ([$result['from'], $result['to']] as $transaction) {
            $this->assertSame($user->id, $transaction->user_id);
            $this->assertSame('2026-08-15', $transaction->transaction_date->format('Y-m-d'));
            $this->assertSame('transfer', $transaction->type->value);
            $this->assertNull($transaction->category_id);
            $this->assertNull($transaction->counterparty_name);
            $this->assertSame('0.00', $transaction->expense_ratio);
            $this->assertFalse($transaction->expense_registered);
            $this->assertFalse($transaction->receipt_saved);
        }
    }
}
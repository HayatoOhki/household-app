<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_transaction_create_page_for_transfer(): void
    {
        $user = User::factory()->create();
        $this->createAccount($user, '銀行', AccountType::BANK);
        $this->createAccount($user, '現金', AccountType::CASH);

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.create', ['type' => 'transfer']));

        $response->assertOk();
        $response->assertSee('取引登録');
        $response->assertSee('振替');
        $response->assertSee('銀行');
        $response->assertSee('現金');
    }

    public function test_user_can_create_transfer_from_transaction_store(): void
    {
        $user = User::factory()->create();
        $fromAccount = $this->createAccount($user, '銀行', AccountType::BANK);
        $toAccount = $this->createAccount($user, '現金', AccountType::CASH);

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::TRANSFER->value,
                'transaction_date' => '2026-09-11',
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'category_id' => $this->createTransferCategory($user)->id,
                'amount' => 10000,
            ]);

        $response->assertRedirect(route('transactions.index'));
        $response->assertSessionHas('success', '振替を登録しました。');

        $this->assertDatabaseCount('transactions', 2);
        $this->assertDatabaseCount('transfers', 1);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::TRANSFER->value,
            'account_id' => $fromAccount->id,
            'amount' => 10000,
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::TRANSFER->value,
            'account_id' => $toAccount->id,
            'amount' => 10000,
        ]);
    }

    public function test_transfer_cannot_use_same_account(): void
    {
        $user = User::factory()->create();
        $account = $this->createAccount($user, '銀行', AccountType::BANK);

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::TRANSFER->value,
                'transaction_date' => '2026-09-11',
                'from_account_id' => $account->id,
                'to_account_id' => $account->id,
                'category_id' => $this->createTransferCategory($user)->id,
                'amount' => 10000,
            ]);

        $response->assertSessionHasErrors('to_account_id');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_transfer_cannot_use_other_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $fromAccount = $this->createAccount($user, '銀行', AccountType::BANK);
        $otherAccount = $this->createAccount($otherUser, '他人の現金', AccountType::CASH);

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::TRANSFER->value,
                'transaction_date' => '2026-09-11',
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $otherAccount->id,
                'category_id' => $this->createTransferCategory($user)->id,
                'amount' => 10000,
            ]);

        $response->assertSessionHasErrors('to_account_id');
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_transfer_amount_must_be_positive_integer(): void
    {
        $user = User::factory()->create();
        $fromAccount = $this->createAccount($user, '銀行', AccountType::BANK);
        $toAccount = $this->createAccount($user, '現金', AccountType::CASH);

        foreach ([0, 100.5] as $amount) {
            $response = $this
                ->actingAs($user)
                ->post(route('transactions.store'), [
                    'type' => TransactionType::TRANSFER->value,
                    'transaction_date' => '2026-09-11',
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'category_id' => $this->createTransferCategory($user)->id,
                    'amount' => $amount,
                ]);

            $response->assertSessionHasErrors('amount');
        }

        $this->assertDatabaseCount('transfers', 0);
    }

    public function test_transfer_is_displayed_as_single_row_in_transaction_index(): void
    {
        [$user, $transfer, $fromAccount, $toAccount] =
            $this->createTransferData();

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('振替');
        $response->assertSee($fromAccount->name);
        $response->assertSee($toAccount->name);
        $response->assertSee('10,000円');

        $this->assertSame(
            1,
            substr_count($response->getContent(), '10,000円')
        );
    }

    public function test_user_can_open_transfer_from_transaction_edit_route(): void
    {
        [$user, $transfer, $fromAccount, $toAccount] =
            $this->createTransferData();

        $transaction = $transfer->fromTransaction;

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.edit', $transaction));

        $response->assertOk();
        $response->assertSee('取引編集');
        $response->assertSee('振替');
        $response->assertSee($fromAccount->name);
        $response->assertSee($toAccount->name);
        $response->assertSee('10000');
    }

    public function test_user_can_update_transfer_from_transaction_update_route(): void
    {
        [$user, $transfer] = $this->createTransferData();

        $newFromAccount = $this->createAccount(
            $user,
            '新しい振替元',
            AccountType::BANK
        );
        $newToAccount = $this->createAccount(
            $user,
            '新しい振替先',
            AccountType::CASH
        );

        $fromTransactionId = $transfer->from_transaction_id;
        $toTransactionId = $transfer->to_transaction_id;

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transfer->fromTransaction), [
                'type' => TransactionType::TRANSFER->value,
                'transaction_date' => '2026-09-20',
                'from_account_id' => $newFromAccount->id,
                'to_account_id' => $newToAccount->id,
                'category_id' => $this->createTransferCategory($user)->id,
                'amount' => 20000,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'id' => $fromTransactionId,
            'account_id' => $newFromAccount->id,
            'amount' => 20000,
        ]);

        $this->assertDatabaseHas('transactions', [
            'id' => $toTransactionId,
            'account_id' => $newToAccount->id,
            'amount' => 20000,
        ]);
    }

    public function test_user_cannot_edit_another_users_transfer(): void
    {
        [$otherUser, $transfer] = $this->createTransferData();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.edit', $transfer->fromTransaction));

        $response->assertNotFound();
    }

    public function test_user_can_open_transfer_duplicate_as_transaction_create_page(): void
    {
        [$user, $transfer, $fromAccount, $toAccount] =
            $this->createTransferData();

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.duplicate', $transfer->fromTransaction));

        $response->assertOk();
        $response->assertSee('取引登録');
        $response->assertDontSee('取引複製');
        $response->assertSee($fromAccount->name);
        $response->assertSee($toAccount->name);
        $response->assertSee('10000');
    }

    public function test_user_cannot_duplicate_another_users_transfer(): void
    {
        [, $transfer] = $this->createTransferData();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.duplicate', $transfer->fromTransaction));

        $response->assertNotFound();
    }

    public function test_user_can_delete_transfer_from_transaction_destroy_route(): void
    {
        [$user, $transfer] = $this->createTransferData();

        $response = $this
            ->actingAs($user)
            ->delete(route('transactions.destroy', $transfer->fromTransaction));

        $response->assertRedirect(route('transactions.index'));
        $this->assertDatabaseCount('transfers', 0);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_user_cannot_delete_another_users_transfer(): void
    {
        [, $transfer] = $this->createTransferData();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('transactions.destroy', $transfer->fromTransaction));

        $response->assertNotFound();
        $this->assertDatabaseCount('transfers', 1);
        $this->assertDatabaseCount('transactions', 2);
    }

    private function createAccount(
        User $user,
        string $name,
        AccountType $type
    ): Account {
        return Account::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => $type,
        ]);
    }

    private function createTransferCategory(User $user): Category
    {
        return Category::firstOrCreate(
            [
                'user_id' => $user->id,
                'type' => CategoryType::TRANSFER,
                'name' => '資金移動',
            ],
            ['sort_order' => 10]
        );
    }

    private function createTransferData(): array
    {
        $user = User::factory()->create();

        $fromAccount = $this->createAccount(
            $user,
            '三井住友銀行',
            AccountType::BANK
        );

        $toAccount = $this->createAccount(
            $user,
            '現金',
            AccountType::CASH
        );

        $transferCategory = $this->createTransferCategory($user);

        $fromTransaction = Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-11',
            'type' => TransactionType::TRANSFER,
            'account_id' => $fromAccount->id,
            'category_id' => $transferCategory->id,
            'counterparty_name' => null,
            'amount' => 10000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $toTransaction = Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-11',
            'type' => TransactionType::TRANSFER,
            'account_id' => $toAccount->id,
            'category_id' => $transferCategory->id,
            'counterparty_name' => null,
            'amount' => 10000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $transfer = Transfer::create([
            'from_transaction_id' => $fromTransaction->id,
            'to_transaction_id' => $toTransaction->id,
        ]);

        $transfer->setRelation('fromTransaction', $fromTransaction);
        $transfer->setRelation('toTransaction', $toTransaction);

        return [
            $user,
            $transfer,
            $fromAccount,
            $toAccount,
        ];
    }
}
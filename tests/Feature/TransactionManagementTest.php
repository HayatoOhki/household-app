<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_transaction_index(): void
    {
        $response = $this->get(route('transactions.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_only_their_own_transactions(): void
    {
        [$user, $account, $category] = $this->createUserData();
        [$otherUser, $otherAccount, $otherCategory] = $this->createUserData();

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-11',
            'type' => TransactionType::EXPENSE,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'counterparty_name' => 'Amazon',
            'amount' => 3500,
        ]);

        Transaction::create([
            'user_id' => $otherUser->id,
            'transaction_date' => '2026-09-11',
            'type' => TransactionType::EXPENSE,
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id,
            'counterparty_name' => '他ユーザー取引',
            'amount' => 1000,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.index'));

        $response->assertOk();
        $response->assertSee('Amazon');
        $response->assertDontSee('他ユーザー取引');
    }

    public function test_user_can_create_expense_transaction(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'counterparty_name' => 'Amazon',
                'amount' => 3500,
                'withdrawal_date' => '2026-10-27',
                'expense_ratio' => 50,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::EXPENSE->value,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'counterparty_name' => 'Amazon',
            'amount' => 3500,
            'withdrawal_date' => '2026-10-27 00:00:00',
            'expense_ratio' => 50,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);
    }

    public function test_user_can_create_income_with_expense_ratio(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $category = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::INCOME,
            'name' => '給与',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::INCOME->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'counterparty_name' => '給与',
                'amount' => 300000,
                'expense_ratio' => 100,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::INCOME->value,
            'counterparty_name' => '給与',
            'amount' => 300000,
            'withdrawal_date' => null,
            'expense_ratio' => 100,
        ]);
    }

    public function test_income_transaction_clears_withdrawal_date(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $category = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::INCOME,
            'name' => '給与',
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::INCOME->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 300000,
                'withdrawal_date' => '2026-10-27',
                'expense_ratio' => 100,
            ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::INCOME->value,
            'withdrawal_date' => null,
            'expense_ratio' => 100,
        ]);
    }

    public function test_user_can_create_transfer_from_transaction_store(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::create([
            'user_id' => $user->id,
            'name' => '銀行',
            'type' => AccountType::BANK,
        ]);

        $toAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::TRANSFER->value,
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'category_id' => Category::create([
                    'user_id' => $user->id,
                    'type' => CategoryType::TRANSFER,
                    'name' => '資金移動',
                ])->id,
                'amount' => 10000,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseCount('transfers', 1);
        $this->assertDatabaseCount('transactions', 2);
    }

    public function test_opening_balance_cannot_be_created_from_normal_transaction_form(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::OPENING_BALANCE->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 1000,
            ]);

        $response->assertSessionHasErrors('type');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_user_cannot_use_another_users_account(): void
    {
        [$user, , $category] = $this->createUserData();
        [, $otherAccount] = $this->createUserData();

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $otherAccount->id,
                'category_id' => $category->id,
                'amount' => 1000,
            ]);

        $response->assertSessionHasErrors('account_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_user_cannot_use_another_users_category(): void
    {
        [$user, $account] = $this->createUserData();
        [, , $otherCategory] = $this->createUserData();

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $account->id,
                'category_id' => $otherCategory->id,
                'amount' => 1000,
            ]);

        $response->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_amount_must_be_greater_than_zero(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 0,
            ]);

        $response->assertSessionHasErrors('amount');
    }

    public function test_amount_must_be_integer(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 100.5,
            ]);

        $response->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_expense_ratio_must_be_between_zero_and_one_hundred(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 1000,
                'expense_ratio' => 101,
            ]);

        $response->assertSessionHasErrors('expense_ratio');
    }

    public function test_user_can_access_edit_page_for_their_transaction(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $transaction = $this->createTransaction(
            $user,
            $account,
            $category
        );

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.edit', $transaction));

        $response->assertOk();
        $response->assertSee('Amazon');
    }

    public function test_user_cannot_access_edit_page_for_another_users_transaction(): void
    {
        [$user] = $this->createUserData();
        [$otherUser, $otherAccount, $otherCategory] = $this->createUserData();

        $transaction = $this->createTransaction(
            $otherUser,
            $otherAccount,
            $otherCategory
        );

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.edit', $transaction));

        $response->assertNotFound();
    }

    public function test_user_can_update_their_transaction(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $transaction = $this->createTransaction(
            $user,
            $account,
            $category
        );

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'transaction_date' => '2026-09-12',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'counterparty_name' => 'スーパー',
                'amount' => 5000,
                'withdrawal_date' => '2026-10-28',
                'expense_ratio' => 75,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $user->id,
            'transaction_date' => '2026-09-12 00:00:00',
            'type' => TransactionType::EXPENSE->value,
            'counterparty_name' => 'スーパー',
            'amount' => 5000,
            'withdrawal_date' => '2026-10-28 00:00:00',
            'expense_ratio' => 75,
        ]);
    }

    public function test_user_cannot_update_another_users_transaction(): void
    {
        [$user, $account, $category] = $this->createUserData();
        [$otherUser, $otherAccount, $otherCategory] = $this->createUserData();

        $transaction = $this->createTransaction(
            $otherUser,
            $otherAccount,
            $otherCategory
        );

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'transaction_date' => '2026-09-12',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'amount' => 5000,
            ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'user_id' => $otherUser->id,
            'amount' => 3500,
        ]);
    }

    public function test_user_cannot_update_transaction_with_another_users_account(): void
    {
        [$user, $account, $category] = $this->createUserData();
        [, $otherAccount] = $this->createUserData();

        $transaction = $this->createTransaction(
            $user,
            $account,
            $category
        );

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $otherAccount->id,
                'category_id' => $category->id,
                'amount' => 3500,
            ]);

        $response->assertSessionHasErrors('account_id');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'account_id' => $account->id,
        ]);
    }

    public function test_user_cannot_update_transaction_with_another_users_category(): void
    {
        [$user, $account, $category] = $this->createUserData();
        [, , $otherCategory] = $this->createUserData();

        $transaction = $this->createTransaction(
            $user,
            $account,
            $category
        );

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $account->id,
                'category_id' => $otherCategory->id,
                'amount' => 3500,
            ]);

        $response->assertSessionHasErrors('category_id');

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_changing_expense_to_income_clears_withdrawal_date_and_keeps_expense_ratio(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $category = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::INCOME,
            'name' => '給与',
        ]);

        $transaction = $this->createTransaction(
            $user,
            $account,
            $category,
            [
                'withdrawal_date' => '2026-10-27',
                'expense_ratio' => 50,
            ]
        );

        $response = $this
            ->actingAs($user)
            ->put(route('transactions.update', $transaction), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::INCOME->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'counterparty_name' => '給与',
                'amount' => 300000,
                'withdrawal_date' => '2026-10-27',
                'expense_ratio' => 100,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'type' => TransactionType::INCOME->value,
            'amount' => 300000,
            'withdrawal_date' => null,
            'expense_ratio' => 100,
        ]);
    }

    public function test_user_can_open_transfer_from_transaction_edit_route(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::create([
            'user_id' => $user->id,
            'name' => '銀行',
            'type' => AccountType::BANK,
        ]);

        $toAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::TRANSFER->value,
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'category_id' => Category::create([
                    'user_id' => $user->id,
                    'type' => CategoryType::TRANSFER,
                    'name' => '資金移動',
                ])->id,
                'amount' => 10000,
            ]);

        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->where('type', TransactionType::TRANSFER->value)
            ->whereHas('outgoingTransfer')
            ->firstOrFail();

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.edit', $transaction));

        $response->assertOk();
        $response->assertSee('取引編集');
        $response->assertSee('振替');
        $response->assertSee('銀行');
        $response->assertSee('現金');
    }

    public function test_opening_balance_cannot_be_edited_from_normal_transaction_form(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $transaction = $this->createTransaction(
            $user,
            $account,
            $category,
            [
                'type' => TransactionType::OPENING_BALANCE,
            ]
        );

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.edit', $transaction));

        $response->assertNotFound();
    }

    public function test_user_can_delete_their_transaction(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $transaction = $this->createTransaction(
            $user,
            $account,
            $category
        );

        $response = $this
            ->actingAs($user)
            ->delete(route('transactions.destroy', $transaction));

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseMissing('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_transaction(): void
    {
        [$user] = $this->createUserData();
        [$otherUser, $otherAccount, $otherCategory] = $this->createUserData();

        $transaction = $this->createTransaction(
            $otherUser,
            $otherAccount,
            $otherCategory
        );

        $response = $this
            ->actingAs($user)
            ->delete(route('transactions.destroy', $transaction));

        $response->assertNotFound();

        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_credit_card_expense_requires_withdrawal_date(): void
    {
        [$user, , $category] = $this->createUserData();

        $creditCard = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::EXPENSE->value,
                'account_id' => $creditCard->id,
                'category_id' => $category->id,
                'amount' => 3500,
                'expense_ratio' => 0,
            ]);

        $response->assertSessionHasErrors('withdrawal_date');
    }

    public function test_user_can_create_refund_with_expense_category(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::REFUND->value,
                'account_id' => $account->id,
                'category_id' => $category->id,
                'counterparty_name' => '立替回収',
                'amount' => 59000,
                'expense_ratio' => 0,
            ]);

        $response->assertRedirect(route('transactions.index'));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type' => TransactionType::REFUND->value,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'counterparty_name' => '立替回収',
            'amount' => 59000,
            'withdrawal_date' => null,
        ]);
    }

    public function test_refund_requires_expense_category(): void
    {
        [$user, $account] = $this->createUserData();

        $incomeCategory = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::INCOME,
            'name' => '臨時収入',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::REFUND->value,
                'account_id' => $account->id,
                'category_id' => $incomeCategory->id,
                'amount' => 59000,
                'expense_ratio' => 0,
            ]);

        $response->assertSessionHasErrors('category_id');
    }

    public function test_credit_card_refund_does_not_require_withdrawal_date(): void
    {
        [$user, , $category] = $this->createUserData();

        $creditCard = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('transactions.store'), [
                'transaction_date' => '2026-09-11',
                'type' => TransactionType::REFUND->value,
                'account_id' => $creditCard->id,
                'category_id' => $category->id,
                'amount' => 3500,
                'expense_ratio' => 0,
            ]);

        $response->assertSessionDoesntHaveErrors('withdrawal_date');
        $response->assertRedirect(route('transactions.index'));
    }

    public function test_user_can_open_duplicate_as_transaction_create_page(): void
    {
        [$user, $account, $category] = $this->createUserData();

        $transaction = $this->createTransaction(
            $user,
            $account,
            $category
        );

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.duplicate', $transaction));

        $response->assertOk();
        $response->assertSee('取引登録');
        $response->assertDontSee('取引複製');
        $response->assertSee('Amazon');
        $response->assertSee('3500');
    }

    private function createUserData(): array
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
            'name' => '食費',
        ]);

        return [$user, $account, $category];
    }

    private function createTransaction(
        User $user,
        Account $account,
        Category $category,
        array $overrides = [],
    ): Transaction {
        return Transaction::create(array_merge([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-11',
            'type' => TransactionType::EXPENSE,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'counterparty_name' => 'Amazon',
            'amount' => 3500,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ], $overrides));
    }
}
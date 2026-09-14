<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpeningBalanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_opening_balance_index(): void
    {
        $response = $this->get(
            route('opening-balances.index')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_authenticated_user_can_open_opening_balance_index(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('opening-balances.index')
            );

        $response->assertOk();
        $response->assertSee('初期残高管理');
        $response->assertSee('三井住友銀行');
        $response->assertSee('未登録');
        $response->assertDontSee('type="date"', false);
    }

    public function test_accounts_are_displayed_in_sort_order(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
            'sort_order' => 30,
        ]);

        Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
            'sort_order' => 10,
        ]);

        Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
            'sort_order' => 20,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('opening-balances.index')
            );

        $response->assertOk();

        $response->assertSeeInOrder([
            '現金',
            '三井住友銀行',
            '楽天カード',
        ]);
    }

    public function test_user_can_create_opening_balance_without_date_input(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('opening-balances.store'),
                [
                    '_opening_balance_account_id' =>
                        $account->id,
                    'account_id' => $account->id,
                    'amount' => 500000,
                ]
            );

        $response->assertRedirect(
            route('opening-balances.index')
        );

        $response->assertSessionHas(
            'success',
            '初期残高を登録しました。'
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'user_id' => $user->id,
                'transaction_date' => '1900-01-01 00:00:00',
                'type' => 'opening_balance',
                'account_id' => $account->id,
                'category_id' => null,
                'counterparty_name' => null,
                'amount' => 500000,
                'withdrawal_date' => null,
                'expense_ratio' => 0,
                'expense_registered' => false,
                'receipt_saved' => false,
            ]
        );
    }

    public function test_opening_balance_can_be_zero(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('opening-balances.store'),
                [
                    '_opening_balance_account_id' =>
                        $account->id,
                    'account_id' => $account->id,
                    'amount' => 0,
                ]
            );

        $response->assertRedirect(
            route('opening-balances.index')
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'user_id' => $user->id,
                'type' => 'opening_balance',
                'account_id' => $account->id,
                'amount' => 0,
            ]
        );
    }

    public function test_user_cannot_create_multiple_opening_balances_for_same_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $this->createOpeningBalance(
            $user,
            $account
        );

        $response = $this
            ->actingAs($user)
            ->from(
                route('opening-balances.index')
            )
            ->post(
                route('opening-balances.store'),
                [
                    '_opening_balance_account_id' =>
                        $account->id,
                    'account_id' => $account->id,
                    'amount' => 600000,
                ]
            );

        $response->assertRedirect(
            route('opening-balances.index')
        );

        $response->assertSessionHasErrors(
            'account_id'
        );

        $this->assertDatabaseCount(
            'transactions',
            1
        );
    }

    public function test_user_can_create_opening_balances_for_different_accounts(): void
    {
        $user = User::factory()->create();

        $bankAccount = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $cashAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $this->createOpeningBalance(
            $user,
            $bankAccount
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('opening-balances.store'),
                [
                    '_opening_balance_account_id' =>
                        $cashAccount->id,
                    'account_id' => $cashAccount->id,
                    'amount' => 30000,
                ]
            );

        $response->assertRedirect(
            route('opening-balances.index')
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'user_id' => $user->id,
                'type' => 'opening_balance',
                'account_id' => $cashAccount->id,
                'amount' => 30000,
            ]
        );
    }

    public function test_user_cannot_use_other_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('opening-balances.store'),
                [
                    '_opening_balance_account_id' =>
                        $otherAccount->id,
                    'account_id' => $otherAccount->id,
                    'amount' => 100000,
                ]
            );

        $response->assertSessionHasErrors(
            'account_id'
        );

        $this->assertDatabaseCount(
            'transactions',
            0
        );
    }

    public function test_index_only_shows_current_users_accounts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '自分の銀行',
            'type' => AccountType::BANK,
        ]);

        Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('opening-balances.index')
            );

        $response->assertOk();
        $response->assertSee('自分の銀行');
        $response->assertDontSee('他人の銀行');
    }

    public function test_registered_opening_balance_is_displayed_on_index(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $this->createOpeningBalance(
            $user,
            $account
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('opening-balances.index')
            );

        $response->assertOk();
        $response->assertSee('登録済み');
        $response->assertSee('500000');
        $response->assertSee('保存');
        $response->assertSee('削除');
    }

    public function test_user_can_update_opening_balance_without_date_input(): void
    {
        [$user, $openingBalance, $account] =
            $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'opening-balances.update',
                    $openingBalance
                ),
                [
                    '_opening_balance_account_id' =>
                        $account->id,
                    'account_id' => $account->id,
                    'amount' => 600000,
                ]
            );

        $response->assertRedirect(
            route('opening-balances.index')
        );

        $response->assertSessionHas(
            'success',
            '初期残高を更新しました。'
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $openingBalance->id,
                'user_id' => $user->id,
                'transaction_date' => '1900-01-01 00:00:00',
                'type' => 'opening_balance',
                'account_id' => $account->id,
                'amount' => 600000,
            ]
        );
    }

    public function test_opening_balance_can_be_updated_to_zero(): void
    {
        [$user, $openingBalance, $account] =
            $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'opening-balances.update',
                    $openingBalance
                ),
                [
                    '_opening_balance_account_id' =>
                        $account->id,
                    'account_id' => $account->id,
                    'amount' => 0,
                ]
            );

        $response->assertRedirect(
            route('opening-balances.index')
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $openingBalance->id,
                'account_id' => $account->id,
                'amount' => 0,
            ]
        );
    }

    public function test_user_cannot_change_opening_balance_to_account_that_already_has_one(): void
    {
        [$user, $openingBalance] =
            $this->createOpeningBalanceData();

        $otherAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $this->createOpeningBalance(
            $user,
            $otherAccount,
            30000
        );

        $response = $this
            ->actingAs($user)
            ->from(
                route('opening-balances.index')
            )
            ->put(
                route(
                    'opening-balances.update',
                    $openingBalance
                ),
                [
                    '_opening_balance_account_id' =>
                        $otherAccount->id,
                    'account_id' => $otherAccount->id,
                    'amount' => 600000,
                ]
            );

        $response->assertRedirect(
            route('opening-balances.index')
        );

        $response->assertSessionHasErrors(
            'account_id'
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $openingBalance->id,
                'amount' => 500000,
            ]
        );
    }

    public function test_user_cannot_update_opening_balance_with_other_users_account(): void
    {
        [$user, $openingBalance, $account] =
            $this->createOpeningBalanceData();

        $otherUser = User::factory()->create();

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'opening-balances.update',
                    $openingBalance
                ),
                [
                    '_opening_balance_account_id' =>
                        $otherAccount->id,
                    'account_id' => $otherAccount->id,
                    'amount' => 600000,
                ]
            );

        $response->assertSessionHasErrors(
            'account_id'
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $openingBalance->id,
                'account_id' => $account->id,
                'amount' => 500000,
            ]
        );
    }

    public function test_user_cannot_update_other_users_opening_balance(): void
    {
        [$owner, $openingBalance, $account] =
            $this->createOpeningBalanceData();

        $otherUser = User::factory()->create();

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->put(
                route(
                    'opening-balances.update',
                    $openingBalance
                ),
                [
                    '_opening_balance_account_id' =>
                        $otherAccount->id,
                    'account_id' => $otherAccount->id,
                    'amount' => 600000,
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $openingBalance->id,
                'user_id' => $owner->id,
                'account_id' => $account->id,
                'amount' => 500000,
            ]
        );
    }

    public function test_user_can_delete_opening_balance(): void
    {
        [$user, $openingBalance] =
            $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'opening-balances.destroy',
                    $openingBalance
                )
            );

        $response->assertRedirect(
            route('opening-balances.index')
        );

        $response->assertSessionHas(
            'success',
            '初期残高を削除しました。'
        );

        $this->assertDatabaseMissing(
            'transactions',
            [
                'id' => $openingBalance->id,
            ]
        );
    }

    public function test_user_cannot_delete_other_users_opening_balance(): void
    {
        [$owner, $openingBalance] =
            $this->createOpeningBalanceData();

        $otherUser = User::factory()->create();

        $response = $this
            ->actingAs($otherUser)
            ->delete(
                route(
                    'opening-balances.destroy',
                    $openingBalance
                )
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $openingBalance->id,
                'user_id' => $owner->id,
                'type' => 'opening_balance',
                'amount' => 500000,
            ]
        );
    }

    public function test_normal_transaction_cannot_be_updated_as_opening_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $transaction = $this->createNormalTransaction(
            $user,
            $account
        );

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'opening-balances.update',
                    $transaction
                ),
                [
                    '_opening_balance_account_id' =>
                        $account->id,
                    'account_id' => $account->id,
                    'amount' => 20000,
                ]
            );

        $response->assertNotFound();
    }

    public function test_normal_transaction_cannot_be_deleted_as_opening_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $transaction = $this->createNormalTransaction(
            $user,
            $account
        );

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'opening-balances.destroy',
                    $transaction
                )
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $transaction->id,
                'type' => 'income',
                'amount' => 10000,
            ]
        );
    }

    public function test_opening_balance_cannot_be_edited_from_transaction_route(): void
    {
        [$user, $openingBalance] =
            $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'transactions.edit',
                    $openingBalance
                )
            );

        $response->assertNotFound();
    }

    public function test_opening_balance_cannot_be_duplicated_from_transaction_route(): void
    {
        [$user, $openingBalance] =
            $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'transactions.duplicate',
                    $openingBalance
                )
            );

        $response->assertNotFound();
    }

    public function test_opening_balance_cannot_be_deleted_from_transaction_route(): void
    {
        [$user, $openingBalance] =
            $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'transactions.destroy',
                    $openingBalance
                )
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $openingBalance->id,
                'type' => 'opening_balance',
            ]
        );
    }

    public function test_transaction_index_displays_opening_balance_date_as_dash(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $this->createOpeningBalance(
            $user,
            $account
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('transactions.index')
            );

        $response->assertOk();

        $response->assertSeeInOrder([
            '-',
            '初期残高',
            '三井住友銀行',
            '500,000円',
        ]);

        $response->assertDontSee('1900年01月01日');
    }

    public function test_transaction_index_places_opening_balance_after_normal_transactions(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $this->createOpeningBalance(
            $user,
            $account
        );

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2020-01-01',
            'type' => 'income',
            'account_id' => $account->id,
            'category_id' => null,
            'counterparty_name' => '通常取引',
            'amount' => 10000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('transactions.index')
            );

        $response->assertOk();

        $response->assertSeeInOrder([
            '通常取引',
            '初期残高',
        ]);
    }

    /**
     * @return array{User, Transaction, Account}
     */
    private function createOpeningBalanceData(): array
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $openingBalance = $this->createOpeningBalance(
            $user,
            $account
        );

        return [
            $user,
            $openingBalance,
            $account,
        ];
    }

    private function createOpeningBalance(
        User $user,
        Account $account,
        int $amount = 500000
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '1900-01-01',
            'type' => 'opening_balance',
            'account_id' => $account->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => $amount,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);
    }

    private function createNormalTransaction(
        User $user,
        Account $account
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-11',
            'type' => 'income',
            'account_id' => $account->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 10000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);
    }
}
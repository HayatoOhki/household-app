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

    public function test_guest_cannot_open_opening_balance_create_page(): void
    {
        $response = $this->get(
            route('opening-balances.create')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_authenticated_user_can_open_opening_balance_create_page(): void
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
                route('opening-balances.create')
            );

        $response->assertOk();
        $response->assertSee('初期残高登録');
        $response->assertSee('三井住友銀行');
    }

    public function test_user_can_create_opening_balance(): void
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
                    'transaction_date' => '2026-09-11',
                    'account_id' => $account->id,
                    'amount' => 500000,
                ]
            );

        $response->assertRedirect(
            route('transactions.index')
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'user_id' => $user->id,
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
                    'transaction_date' => '2026-09-11',
                    'account_id' => $account->id,
                    'amount' => 0,
                ]
            );

        $response->assertRedirect(
            route('transactions.index')
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

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-01',
            'type' => 'opening_balance',
            'account_id' => $account->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 500000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(
                route('opening-balances.create')
            )
            ->post(
                route('opening-balances.store'),
                [
                    'transaction_date' => '2026-09-11',
                    'account_id' => $account->id,
                    'amount' => 600000,
                ]
            );

        $response->assertRedirect(
            route('opening-balances.create')
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

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-01',
            'type' => 'opening_balance',
            'account_id' => $bankAccount->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 500000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('opening-balances.store'),
                [
                    'transaction_date' => '2026-09-11',
                    'account_id' => $cashAccount->id,
                    'amount' => 30000,
                ]
            );

        $response->assertRedirect(
            route('transactions.index')
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
                    'transaction_date' => '2026-09-11',
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

    public function test_create_page_only_shows_current_users_accounts(): void
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
                route('opening-balances.create')
            );

        $response->assertOk();

        $response->assertSee(
            '自分の銀行'
        );

        $response->assertDontSee(
            '他人の銀行'
        );
    }

    public function test_registered_account_is_disabled_on_create_page(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-01',
            'type' => 'opening_balance',
            'account_id' => $account->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 500000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('opening-balances.create')
            );

        $response->assertOk();
        $response->assertSee('（登録済み）');
    }

    public function test_opening_balance_is_displayed_in_transaction_index(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-11',
            'type' => 'opening_balance',
            'account_id' => $account->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 500000,
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

        $response->assertSee(
            '初期残高'
        );

        $response->assertSee(
            '三井住友銀行'
        );

        $response->assertSee(
            '500,000円'
        );
    }

    public function test_user_can_open_opening_balance_edit_page(): void
    {
        [$user, $openingBalance, $account] =
            $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'opening-balances.edit',
                    $openingBalance
                )
            );

        $response->assertOk();

        $response->assertSee('初期残高編集');
        $response->assertSee($account->name);
        $response->assertSee('500000');
    }

    public function test_user_can_update_opening_balance(): void
    {
        [$user, $openingBalance] =
            $this->createOpeningBalanceData();

        $newAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'opening-balances.update',
                    $openingBalance
                ),
                [
                    'transaction_date' => '2026-09-20',
                    'account_id' => $newAccount->id,
                    'amount' => 600000,
                ]
            );

        $response->assertRedirect(
            route('transactions.index')
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
                'transaction_date' => '2026-09-20 00:00:00',
                'type' => 'opening_balance',
                'account_id' => $newAccount->id,
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
                    'transaction_date' => '2026-09-11',
                    'account_id' => $account->id,
                    'amount' => 0,
                ]
            );

        $response->assertRedirect(
            route('transactions.index')
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

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-01',
            'type' => 'opening_balance',
            'account_id' => $otherAccount->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 30000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(
                route(
                    'opening-balances.edit',
                    $openingBalance
                )
            )
            ->put(
                route(
                    'opening-balances.update',
                    $openingBalance
                ),
                [
                    'transaction_date' => '2026-09-20',
                    'account_id' => $otherAccount->id,
                    'amount' => 600000,
                ]
            );

        $response->assertRedirect(
            route(
                'opening-balances.edit',
                $openingBalance
            )
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
                    'transaction_date' => '2026-09-20',
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

    public function test_user_cannot_open_other_users_opening_balance_edit_page(): void
    {
        [$owner, $openingBalance] =
            $this->createOpeningBalanceData();

        $otherUser = User::factory()->create();

        $response = $this
            ->actingAs($otherUser)
            ->get(
                route(
                    'opening-balances.edit',
                    $openingBalance
                )
            );

        $response->assertNotFound();
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
                    'transaction_date' => '2026-09-20',
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

    public function test_normal_transaction_cannot_be_edited_as_opening_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $transaction = Transaction::create([
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

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'opening-balances.edit',
                    $transaction
                )
            );

        $response->assertNotFound();
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
            route('transactions.index')
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

    public function test_normal_transaction_cannot_be_deleted_as_opening_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $transaction = Transaction::create([
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

    /**
     * @return array{User, Transaction, Account}
     */
    public function test_opening_balance_cannot_be_edited_from_transaction_route(): void
    {
        [$user, $openingBalance] = $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.edit', $openingBalance));

        $response->assertNotFound();
    }

    public function test_opening_balance_cannot_be_duplicated_from_transaction_route(): void
    {
        [$user, $openingBalance] = $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->get(route('transactions.duplicate', $openingBalance));

        $response->assertNotFound();
    }

    public function test_opening_balance_cannot_be_deleted_from_transaction_route(): void
    {
        [$user, $openingBalance] = $this->createOpeningBalanceData();

        $response = $this
            ->actingAs($user)
            ->delete(route('transactions.destroy', $openingBalance));

        $response->assertNotFound();

        $this->assertDatabaseHas('transactions', [
            'id' => $openingBalance->id,
            'type' => 'opening_balance',
        ]);
    }

    private function createOpeningBalanceData(): array
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $openingBalance = Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-11',
            'type' => 'opening_balance',
            'account_id' => $account->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 500000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        return [
            $user,
            $openingBalance,
            $account,
        ];
    }
}
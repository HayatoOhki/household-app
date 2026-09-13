<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionRuleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_transaction_rule_index(): void
    {
        $response = $this->get(
            route('transaction-rules.index')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_authenticated_user_can_open_transaction_rule_index(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route('transaction-rules.index')
            );

        $response->assertOk();
        $response->assertSee('入力テンプレート');
    }

    public function test_authenticated_user_can_open_transaction_rule_create_page(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        Category::create([
            'user_id' => $user->id,
            'name' => '家賃',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('transaction-rules.create')
            );

        $response->assertOk();
        $response->assertSee('三井住友銀行');
        $response->assertSee('家賃');
    }

    public function test_user_can_create_transaction_rule(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '家賃',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('transaction-rules.store'),
                [
                    'account_id' => $account->id,
                    'keyword' => 'ﾔﾁﾝ',
                    'display_name' => '家賃',
                    'category_id' => $category->id,
                ]
            );

        $response->assertRedirect(
            route('transaction-rules.index')
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'user_id' => $user->id,
                'account_id' => $account->id,
                'keyword' => 'ﾔﾁﾝ',
                'display_name' => '家賃',
                'category_id' => $category->id,
            ]
        );
    }

    public function test_same_keyword_and_category_cannot_be_registered_twice_for_same_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'AMAZON.CO.JP',
            'display_name' => 'Amazon',
            'category_id' => $category->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('transaction-rules.create'))
            ->post(route('transaction-rules.store'), [
                'account_id' => $account->id,
                'keyword' => 'AMAZON.CO.JP',
                'display_name' => 'Amazon',
                'category_id' => $category->id,
            ]);

        $response->assertRedirect(route('transaction-rules.create'));
        $response->assertSessionHasErrors('keyword');
        $this->assertDatabaseCount('transaction_rules', 1);
    }

    public function test_same_keyword_can_be_registered_for_different_categories_on_same_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $food = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        $dailyGoods = Category::create([
            'user_id' => $user->id,
            'name' => '日用品',
        ]);

        TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'AMAZON.CO.JP',
            'display_name' => 'Amazon',
            'category_id' => $food->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('transaction-rules.store'), [
                'account_id' => $account->id,
                'keyword' => 'AMAZON.CO.JP',
                'display_name' => 'Amazon',
                'category_id' => $dailyGoods->id,
            ]);

        $response->assertRedirect(route('transaction-rules.index'));

        $this->assertDatabaseHas('transaction_rules', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'AMAZON.CO.JP',
            'category_id' => $food->id,
        ]);

        $this->assertDatabaseHas('transaction_rules', [
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'AMAZON.CO.JP',
            'category_id' => $dailyGoods->id,
        ]);
    }

    public function test_same_keyword_can_be_registered_for_different_accounts(): void
    {
        $user = User::factory()->create();

        $bankAccount = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $creditCardAccount = Account::create([
            'user_id' => $user->id,
            'name' => 'クレジットカード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $bankAccount->id,
            'keyword' => 'test',
            'display_name' => 'テスト',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('transaction-rules.store'),
                [
                    'account_id' => $creditCardAccount->id,
                    'keyword' => 'test',
                    'display_name' => 'てすと',
                    'category_id' => null,
                ]
            );

        $response->assertRedirect(
            route('transaction-rules.index')
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'user_id' => $user->id,
                'account_id' => $bankAccount->id,
                'keyword' => 'test',
                'display_name' => 'テスト',
            ]
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'user_id' => $user->id,
                'account_id' => $creditCardAccount->id,
                'keyword' => 'test',
                'display_name' => 'てすと',
            ]
        );
    }

    public function test_user_cannot_create_rule_for_other_users_account(): void
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
                route('transaction-rules.store'),
                [
                    'account_id' => $otherAccount->id,
                    'keyword' => 'test',
                    'display_name' => 'テスト',
                    'category_id' => null,
                ]
            );

        $response->assertSessionHasErrors(
            'account_id'
        );

        $this->assertDatabaseCount(
            'transaction_rules',
            0
        );
    }

    public function test_user_cannot_create_rule_with_other_users_category(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $otherCategory = Category::create([
            'user_id' => $otherUser->id,
            'name' => '他人のカテゴリ',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                route('transaction-rules.store'),
                [
                    'account_id' => $account->id,
                    'keyword' => 'test',
                    'display_name' => 'テスト',
                    'category_id' => $otherCategory->id,
                ]
            );

        $response->assertSessionHasErrors(
            'category_id'
        );

        $this->assertDatabaseCount(
            'transaction_rules',
            0
        );
    }

    public function test_create_page_only_shows_current_users_accounts_and_categories(): void
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

        Category::create([
            'user_id' => $user->id,
            'name' => '自分のカテゴリ',
        ]);

        Category::create([
            'user_id' => $otherUser->id,
            'name' => '他人のカテゴリ',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('transaction-rules.create')
            );

        $response->assertOk();

        $response->assertSee(
            '自分の銀行'
        );

        $response->assertSee(
            '自分のカテゴリ'
        );

        $response->assertDontSee(
            '他人の銀行'
        );

        $response->assertDontSee(
            '他人のカテゴリ'
        );
    }

    public function test_user_can_update_own_transaction_rule(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '家賃',
        ]);

        $rule = TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'old',
            'display_name' => '旧表示名',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.update',
                    $rule
                ),
                [
                    'account_id' => $account->id,
                    'keyword' => 'ﾔﾁﾝ',
                    'display_name' => '家賃',
                    'category_id' => $category->id,
                ]
            );

        $response->assertRedirect(
            route('transaction-rules.index')
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'id' => $rule->id,
                'user_id' => $user->id,
                'account_id' => $account->id,
                'keyword' => 'ﾔﾁﾝ',
                'display_name' => '家賃',
                'category_id' => $category->id,
            ]
        );
    }

    public function test_user_cannot_edit_other_users_transaction_rule(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
        ]);

        $rule = TransactionRule::create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'keyword' => 'test',
            'display_name' => 'テスト',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'transaction-rules.edit',
                    $rule
                )
            );

        $response->assertNotFound();
    }

    public function test_user_cannot_update_other_users_transaction_rule(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $userAccount = Account::create([
            'user_id' => $user->id,
            'name' => '自分の銀行',
            'type' => AccountType::BANK,
        ]);

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
        ]);

        $rule = TransactionRule::create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'keyword' => 'old',
            'display_name' => '旧表示名',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.update',
                    $rule
                ),
                [
                    'account_id' => $userAccount->id,
                    'keyword' => 'new',
                    'display_name' => '新表示名',
                    'category_id' => null,
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'id' => $rule->id,
                'keyword' => 'old',
                'display_name' => '旧表示名',
            ]
        );
    }

    public function test_user_can_delete_own_transaction_rule(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $rule = TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'test',
            'display_name' => 'テスト',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'transaction-rules.destroy',
                    $rule
                )
            );

        $response->assertRedirect(
            route('transaction-rules.index')
        );

        $this->assertDatabaseMissing(
            'transaction_rules',
            [
                'id' => $rule->id,
            ]
        );
    }

    public function test_user_cannot_delete_other_users_transaction_rule(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
        ]);

        $rule = TransactionRule::create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'keyword' => 'test',
            'display_name' => 'テスト',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'transaction-rules.destroy',
                    $rule
                )
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'id' => $rule->id,
            ]
        );
    }

    public function test_transaction_create_page_contains_current_users_rule_data(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '家賃',
        ]);

        TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'ﾔﾁﾝ',
            'display_name' => '家賃',
            'category_id' => $category->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('transactions.create')
            );

        $response->assertOk();

        $response->assertSee(
            'ﾔﾁﾝ',
            false
        );
    }

    public function test_transaction_index_displays_rule_display_name_without_changing_database_value(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '家賃',
        ]);

        TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'ﾔﾁﾝ',
            'display_name' => '家賃表示',
            'category_id' => $category->id,
        ]);

        $transaction = Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-11',
            'type' => 'expense',
            'account_id' => $account->id,
            'category_id' => $category->id,
            'counterparty_name' => 'ﾔﾁﾝ',
            'amount' => 80000,
            'withdrawal_date' => null,
            'expense_ratio' => 40,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('transactions.index')
            );

        $response->assertOk();
        $response->assertSee('家賃表示');

        $this->assertDatabaseHas(
            'transactions',
            [
                'id' => $transaction->id,
                'counterparty_name' => 'ﾔﾁﾝ',
            ]
        );

        $this->assertDatabaseMissing(
            'transactions',
            [
                'id' => $transaction->id,
                'counterparty_name' => '家賃表示',
            ]
        );
    }

    public function test_rule_for_different_account_does_not_change_transaction_display_name(): void
    {
        $user = User::factory()->create();

        $bankAccount = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $creditCardAccount = Account::create([
            'user_id' => $user->id,
            'name' => 'クレジットカード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'その他',
        ]);

        TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $creditCardAccount->id,
            'keyword' => 'test',
            'display_name' => '変換後',
            'category_id' => $category->id,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-11',
            'type' => 'expense',
            'account_id' => $bankAccount->id,
            'category_id' => $category->id,
            'counterparty_name' => 'test',
            'amount' => 1000,
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
        $response->assertSee('test');
        $response->assertDontSee('変換後');
    }
}
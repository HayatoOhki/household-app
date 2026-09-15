<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CategoryType;
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
        $response->assertSee(
            '取引補助設定'
        );
    }

    public function test_index_only_shows_current_users_accounts_categories_and_rules(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '自分の銀行',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
            'name' => '自分のカテゴリ',
            'sort_order' => 10,
        ]);

        $otherCategory = Category::create([
            'user_id' => $otherUser->id,
            'type' => CategoryType::EXPENSE,
            'name' => '他人のカテゴリ',
            'sort_order' => 10,
        ]);

        TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => '自分のキーワード',
            'display_name' => '自分の表示名',
            'category_id' => $category->id,
        ]);

        TransactionRule::create([
            'user_id' => $otherUser->id,
            'account_id' => $otherAccount->id,
            'keyword' => '他人のキーワード',
            'display_name' => '他人の表示名',
            'category_id' => $otherCategory->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('transaction-rules.index')
            );

        $response->assertOk();

        $response->assertSee(
            '自分の銀行'
        );

        $response->assertSee(
            '自分のカテゴリ'
        );

        $response->assertSee(
            '自分のキーワード'
        );

        $response->assertDontSee(
            '他人の銀行'
        );

        $response->assertDontSee(
            '他人のカテゴリ'
        );

        $response->assertDontSee(
            '他人のキーワード'
        );
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
                route('transaction-rules.index')
            );

        $response->assertOk();

        $response->assertSeeInOrder([
            '三井住友銀行',
            '楽天カード',
        ]);

        $response->assertDontSee(
            '現金'
        );
    }

    public function test_user_can_create_transaction_rule_with_bulk_update(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
            'name' => '家賃',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $account
                ),
                [
                    '_template_account_id' =>
                        $account->id,

                    'transaction_rules' => [
                        [
                            'id' => '',
                            'keyword' => 'ﾔﾁﾝ',
                            'display_name' => '家賃',
                            'category_id' =>
                                $category->id,
                        ],
                    ],
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

    public function test_user_can_update_transaction_rules_for_one_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
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
                    'transaction-rules.bulk-update',
                    $account
                ),
                [
                    '_template_account_id' =>
                        $account->id,

                    'transaction_rules' => [
                        [
                            'id' => $rule->id,
                            'keyword' => 'ﾔﾁﾝ',
                            'display_name' => '家賃',
                            'category_id' =>
                                $category->id,
                        ],
                    ],
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

    public function test_removing_row_from_bulk_update_deletes_rule(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $keepRule = TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'AMAZON',
            'display_name' => 'Amazon',
            'category_id' => null,
        ]);

        $deleteRule = TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'APPLE',
            'display_name' => 'Apple',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $account
                ),
                [
                    '_template_account_id' =>
                        $account->id,

                    'transaction_rules' => [
                        [
                            'id' => $keepRule->id,
                            'keyword' => 'AMAZON',
                            'display_name' => 'Amazon',
                            'category_id' => null,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('transaction-rules.index')
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'id' => $keepRule->id,
            ]
        );

        $this->assertDatabaseMissing(
            'transaction_rules',
            [
                'id' => $deleteRule->id,
            ]
        );
    }

    public function test_saving_empty_account_removes_all_rules_for_that_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $rule = TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'AMAZON',
            'display_name' => 'Amazon',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $account
                ),
                [
                    '_template_account_id' =>
                        $account->id,
                ]
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

    public function test_same_keyword_and_category_cannot_be_saved_twice_for_same_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
            'name' => '食費',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $account
                ),
                [
                    '_template_account_id' =>
                        $account->id,

                    'transaction_rules' => [
                        [
                            'id' => '',
                            'keyword' =>
                                'AMAZON.CO.JP',
                            'display_name' =>
                                'Amazon',
                            'category_id' =>
                                $category->id,
                        ],
                        [
                            'id' => '',
                            'keyword' =>
                                'AMAZON.CO.JP',
                            'display_name' =>
                                'Amazon',
                            'category_id' =>
                                $category->id,
                        ],
                    ],
                ]
            );

        $response->assertSessionHasErrors(
            'transaction_rules.1.keyword'
        );

        $this->assertDatabaseCount(
            'transaction_rules',
            0
        );
    }

    public function test_same_keyword_can_be_saved_for_different_categories_on_same_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $food = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
            'name' => '食費',
        ]);

        $dailyGoods = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
            'name' => '日用品',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $account
                ),
                [
                    '_template_account_id' =>
                        $account->id,

                    'transaction_rules' => [
                        [
                            'id' => '',
                            'keyword' =>
                                'AMAZON.CO.JP',
                            'display_name' =>
                                'Amazon',
                            'category_id' =>
                                $food->id,
                        ],
                        [
                            'id' => '',
                            'keyword' =>
                                'AMAZON.CO.JP',
                            'display_name' =>
                                'Amazon',
                            'category_id' =>
                                $dailyGoods->id,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('transaction-rules.index')
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'account_id' => $account->id,
                'keyword' => 'AMAZON.CO.JP',
                'category_id' => $food->id,
            ]
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'account_id' => $account->id,
                'keyword' => 'AMAZON.CO.JP',
                'category_id' =>
                    $dailyGoods->id,
            ]
        );
    }

    public function test_same_keyword_can_be_saved_for_different_accounts(): void
    {
        $user = User::factory()->create();

        $bank = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $card = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $bank->id,
            'keyword' => 'test',
            'display_name' => '銀行',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $card
                ),
                [
                    '_template_account_id' =>
                        $card->id,

                    'transaction_rules' => [
                        [
                            'id' => '',
                            'keyword' => 'test',
                            'display_name' =>
                                'カード',
                            'category_id' => null,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('transaction-rules.index')
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'account_id' => $bank->id,
                'keyword' => 'test',
                'display_name' => '銀行',
            ]
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'account_id' => $card->id,
                'keyword' => 'test',
                'display_name' => 'カード',
            ]
        );
    }

    public function test_user_cannot_bulk_update_other_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $account
                ),
                [
                    '_template_account_id' =>
                        $account->id,

                    'transaction_rules' => [
                        [
                            'id' => '',
                            'keyword' => 'test',
                            'display_name' =>
                                'テスト',
                            'category_id' => null,
                        ],
                    ],
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseCount(
            'transaction_rules',
            0
        );
    }

    public function test_user_cannot_save_other_users_category(): void
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
            'type' => CategoryType::EXPENSE,
            'name' => '他人のカテゴリ',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $account
                ),
                [
                    '_template_account_id' =>
                        $account->id,

                    'transaction_rules' => [
                        [
                            'id' => '',
                            'keyword' => 'test',
                            'display_name' =>
                                'テスト',
                            'category_id' =>
                                $otherCategory->id,
                        ],
                    ],
                ]
            );

        $response->assertSessionHasErrors(
            'transaction_rules.0.category_id'
        );

        $this->assertDatabaseCount(
            'transaction_rules',
            0
        );
    }

    public function test_user_cannot_move_rule_from_another_account_by_submitting_its_id(): void
    {
        $user = User::factory()->create();

        $firstAccount = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $secondAccount = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $rule = TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $secondAccount->id,
            'keyword' => 'test',
            'display_name' => 'テスト',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $firstAccount
                ),
                [
                    '_template_account_id' =>
                        $firstAccount->id,

                    'transaction_rules' => [
                        [
                            'id' => $rule->id,
                            'keyword' => 'changed',
                            'display_name' =>
                                '変更後',
                            'category_id' => null,
                        ],
                    ],
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'id' => $rule->id,
                'account_id' =>
                    $secondAccount->id,
                'keyword' => 'test',
            ]
        );
    }

    public function test_user_can_swap_keywords_in_same_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '楽天カード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $first = TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'AAA',
            'display_name' => 'A',
            'category_id' => null,
        ]);

        $second = TransactionRule::create([
            'user_id' => $user->id,
            'account_id' => $account->id,
            'keyword' => 'BBB',
            'display_name' => 'B',
            'category_id' => null,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route(
                    'transaction-rules.bulk-update',
                    $account
                ),
                [
                    '_template_account_id' =>
                        $account->id,

                    'transaction_rules' => [
                        [
                            'id' => $first->id,
                            'keyword' => 'BBB',
                            'display_name' => 'A',
                            'category_id' => null,
                        ],
                        [
                            'id' => $second->id,
                            'keyword' => 'AAA',
                            'display_name' => 'B',
                            'category_id' => null,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('transaction-rules.index')
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'id' => $first->id,
                'keyword' => 'BBB',
            ]
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'id' => $second->id,
                'keyword' => 'AAA',
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
            'type' => CategoryType::EXPENSE,
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
            'type' => CategoryType::EXPENSE,
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
                'counterparty_name' =>
                    '家賃表示',
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
            'type' => CategoryType::EXPENSE,
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
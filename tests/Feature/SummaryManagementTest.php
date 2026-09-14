<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SummaryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_summary(): void
    {
        $response = $this->get(
            route('summary.index')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_authenticated_user_can_access_annual_summary(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();
        $response->assertSee('年間収支');
        $response->assertSee('2026年');
        $response->assertSee('カテゴリ別支出');
        $response->assertSee('クレジットカード別支出');
        $response->assertSee('口座別支出');
    }

    public function test_summary_calculates_annual_income_expense_and_balance(): void
    {
        [$user, $account, $category] =
            $this->createBaseData();

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-01-10',
            'income',
            300000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-01-15',
            'expense',
            100000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-02-10',
            'income',
            250000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-02-15',
            'expense',
            50000
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertSee('300,000円');
        $response->assertSee('250,000円');
        $response->assertSee('150,000円');
        $response->assertSee('550,000円');
        $response->assertSee('400,000円');
    }

    public function test_summary_only_includes_transactions_from_selected_year(): void
    {
        [$user, $account, $category] =
            $this->createBaseData();

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-09-10',
            'expense',
            12345
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2025-09-10',
            'expense',
            987654
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertSee('12,345円');
        $response->assertDontSee('987,654円');
    }

    public function test_summary_only_uses_current_users_transactions(): void
    {
        [$user, $account, $category] =
            $this->createBaseData();

        $otherUser = User::factory()->create();

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
        ]);

        $otherCategory = Category::create([
            'user_id' => $otherUser->id,
            'name' => '他人のカテゴリ',
        ]);

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-09-10',
            'expense',
            12345
        );

        $this->createTransaction(
            $otherUser->id,
            $otherAccount->id,
            $otherCategory->id,
            '2026-09-10',
            'expense',
            987654
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertSee('12,345円');
        $response->assertDontSee('987,654円');
        $response->assertDontSee('他人の銀行');
        $response->assertDontSee('他人のカテゴリ');
    }

    public function test_transfer_is_not_included_in_annual_income_or_expense(): void
    {
        [$user, $account] =
            $this->createBaseData();

        $this->createTransaction(
            $user->id,
            $account->id,
            null,
            '2026-09-10',
            'transfer',
            999999
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertDontSee('999,999円');
    }

    public function test_opening_balance_is_not_included_in_annual_income_or_expense(): void
    {
        [$user, $account] =
            $this->createBaseData();

        $this->createTransaction(
            $user->id,
            $account->id,
            null,
            '2026-01-01',
            'opening_balance',
            888888
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertDontSee('888,888円');
    }

    public function test_summary_groups_expenses_by_category(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
        ]);

        $food = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        $transport = Category::create([
            'user_id' => $user->id,
            'name' => '交通費',
        ]);

        $this->createTransaction(
            $user->id,
            $account->id,
            $food->id,
            '2026-01-01',
            'expense',
            3000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $food->id,
            '2026-02-01',
            'expense',
            2000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $transport->id,
            '2026-03-01',
            'expense',
            1500
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertSee('食費');
        $response->assertSee('5,000円');

        $response->assertSee('交通費');
        $response->assertSee('1,500円');
    }

    public function test_summary_groups_credit_card_expenses_by_account(): void
    {
        $user = User::factory()->create();

        $creditCard = Account::create([
            'user_id' => $user->id,
            'name' => 'テストカード',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        $this->createTransaction(
            $user->id,
            $creditCard->id,
            $category->id,
            '2026-01-10',
            'expense',
            12000
        );

        $this->createTransaction(
            $user->id,
            $creditCard->id,
            $category->id,
            '2026-02-10',
            'expense',
            8000
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertSee('テストカード');
        $response->assertSee('20,000円');
    }

    public function test_summary_groups_cash_and_bank_expenses_by_account(): void
    {
        $user = User::factory()->create();

        $bank = Account::create([
            'user_id' => $user->id,
            'name' => 'テスト銀行',
            'type' => AccountType::BANK,
        ]);

        $cash = Account::create([
            'user_id' => $user->id,
            'name' => 'テスト現金',
            'type' => AccountType::CASH,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'その他',
        ]);

        $this->createTransaction(
            $user->id,
            $bank->id,
            $category->id,
            '2026-01-10',
            'expense',
            15000
        );

        $this->createTransaction(
            $user->id,
            $cash->id,
            $category->id,
            '2026-01-11',
            'expense',
            5000
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertSee('テスト銀行');
        $response->assertSee('15,000円');

        $response->assertSee('テスト現金');
        $response->assertSee('5,000円');
    }

    public function test_credit_card_expense_is_not_included_in_account_rows(): void
    {
        $user = User::factory()->create();

        $creditCard = Account::create([
            'user_id' => $user->id,
            'name' => 'カード専用口座',
            'type' => AccountType::CREDIT_CARD,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'その他',
        ]);

        $this->createTransaction(
            $user->id,
            $creditCard->id,
            $category->id,
            '2026-01-10',
            'expense',
            54321
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertSee('カード専用口座');
        $response->assertSee('54,321円');

        $response->assertSee(
            'この年の口座支出はありません。'
        );
    }

    public function test_current_year_average_uses_months_from_january_through_current_month(): void
    {
        Carbon::setTestNow('2026-09-14 12:00:00');

        try {
            [$user, $account, $category] =
                $this->createBaseData();

            $this->createTransaction(
                $user->id,
                $account->id,
                $category->id,
                '2026-01-10',
                'expense',
                9000
            );

            $this->createTransaction(
                $user->id,
                $account->id,
                $category->id,
                '2026-09-10',
                'expense',
                9000
            );

            $response = $this
                ->actingAs($user)
                ->get(
                    route(
                        'summary.index',
                        ['year' => 2026]
                    )
                );

            $response->assertOk();

            // 18,000 ÷ 9 months = 2,000
            $response->assertSee('2,000円');
            $response->assertSee('18,000円');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_past_year_average_uses_all_twelve_months(): void
    {
        Carbon::setTestNow('2026-09-14 12:00:00');

        try {
            [$user, $account, $category] =
                $this->createBaseData();

            $this->createTransaction(
                $user->id,
                $account->id,
                $category->id,
                '2025-01-10',
                'expense',
                12000
            );

            $this->createTransaction(
                $user->id,
                $account->id,
                $category->id,
                '2025-12-10',
                'expense',
                12000
            );

            $response = $this
                ->actingAs($user)
                ->get(
                    route(
                        'summary.index',
                        ['year' => 2025]
                    )
                );

            $response->assertOk();

            // 24,000 ÷ 12 months = 2,000
            $response->assertSee('2,000円');
            $response->assertSee('24,000円');
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_previous_and_next_year_links_are_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2026]
                )
            );

        $response->assertOk();

        $response->assertSee('2025年を表示');
        $response->assertSee('2027年を表示');
    }

    public function test_invalid_year_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 'invalid']
                )
            );

        $response->assertSessionHasErrors('year');
    }

    public function test_year_before_minimum_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 1999]
                )
            );

        $response->assertSessionHasErrors('year');
    }

    public function test_year_after_maximum_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    ['year' => 2101]
                )
            );

        $response->assertSessionHasErrors('year');
    }

    /**
     * @return array{User, Account, Category}
     */
    private function createBaseData(): array
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => 'その他',
        ]);

        return [
            $user,
            $account,
            $category,
        ];
    }

    private function createTransaction(
        int $userId,
        int $accountId,
        ?int $categoryId,
        string $date,
        string $type,
        int $amount
    ): Transaction {
        return Transaction::create([
            'user_id' => $userId,
            'transaction_date' => $date,
            'type' => $type,
            'account_id' => $accountId,
            'category_id' => $categoryId,
            'counterparty_name' => null,
            'amount' => $amount,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);
    }
}
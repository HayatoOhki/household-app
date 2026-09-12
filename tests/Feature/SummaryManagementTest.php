<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_authenticated_user_can_access_summary(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route('summary.index')
            );

        $response->assertOk();
        $response->assertSee('月間集計');
    }

    public function test_summary_calculates_monthly_income_expense_and_balance(): void
    {
        [$user, $account, $category] =
            $this->createBaseData();

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-09-01',
            'income',
            300000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-09-05',
            'expense',
            100000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-09-10',
            'expense',
            3500
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    [
                        'month' => '2026-09',
                    ]
                )
            );

        $response->assertOk();

        $response->assertSee('300,000 円');
        $response->assertSee('103,500 円');
        $response->assertSee('196,500 円');
    }

    public function test_summary_does_not_include_transactions_from_other_months(): void
    {
        [$user, $account, $category] =
            $this->createBaseData();

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-09-10',
            'expense',
            10000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-08-31',
            'expense',
            999999
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    [
                        'month' => '2026-09',
                    ]
                )
            );

        $response->assertOk();

        $response->assertSee('10,000 円');
        $response->assertDontSee('999,999 円');
    }

    public function test_summary_only_uses_current_users_transactions(): void
    {
        [$user, $account, $category] =
            $this->createBaseData();

        $otherUser = User::factory()->create();

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
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
            10000
        );

        $this->createTransaction(
            $otherUser->id,
            $otherAccount->id,
            $otherCategory->id,
            '2026-09-10',
            'expense',
            999999
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    [
                        'month' => '2026-09',
                    ]
                )
            );

        $response->assertOk();

        $response->assertSee('10,000 円');
        $response->assertDontSee('999,999 円');
        $response->assertDontSee('他人の銀行');
    }

    public function test_transfer_is_not_included_in_monthly_income_or_expense(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::create([
            'user_id' => $user->id,
            'name' => '銀行',
        ]);

        $toAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        $fromTransaction = $this->createTransaction(
            $user->id,
            $fromAccount->id,
            null,
            '2026-09-10',
            'transfer',
            30000
        );

        $toTransaction = $this->createTransaction(
            $user->id,
            $toAccount->id,
            null,
            '2026-09-10',
            'transfer',
            30000
        );

        Transfer::create([
            'from_transaction_id' =>
                $fromTransaction->id,

            'to_transaction_id' =>
                $toTransaction->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    [
                        'month' => '2026-09',
                    ]
                )
            );

        $response->assertOk();

        $response->assertSee('0 円');
    }

    public function test_opening_balance_is_not_included_in_monthly_income_or_expense(): void
    {
        [$user, $account] =
            $this->createBaseData();

        $this->createTransaction(
            $user->id,
            $account->id,
            null,
            '2026-09-01',
            'opening_balance',
            500000
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    [
                        'month' => '2026-09',
                    ]
                )
            );

        $response->assertOk();

        $response->assertSee('0 円');

        $response->assertSee(
            '500,000'
        );
    }

    public function test_summary_groups_expenses_by_category(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
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
            '2026-09-01',
            'expense',
            3000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $food->id,
            '2026-09-02',
            'expense',
            2000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $transport->id,
            '2026-09-03',
            'expense',
            1500
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    [
                        'month' => '2026-09',
                    ]
                )
            );

        $response->assertOk();

        $response->assertSee('食費');

        $response->assertSee(
            '5,000'
        );

        $response->assertSee('交通費');

        $response->assertSee(
            '1,500'
        );
    }

    public function test_account_balance_includes_opening_balance_income_and_expense(): void
    {
        [$user, $account, $category] =
            $this->createBaseData();

        $this->createTransaction(
            $user->id,
            $account->id,
            null,
            '2026-08-01',
            'opening_balance',
            500000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-08-10',
            'income',
            300000
        );

        $this->createTransaction(
            $user->id,
            $account->id,
            $category->id,
            '2026-08-15',
            'expense',
            100000
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('summary.index')
            );

        $response->assertOk();

        $response->assertSee(
            '700,000'
        );
    }

    public function test_transfer_decreases_source_account_and_increases_destination_account(): void
    {
        $user = User::factory()->create();

        $bankAccount = Account::create([
            'user_id' => $user->id,
            'name' => '銀行',
        ]);

        $cashAccount = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        $this->createTransaction(
            $user->id,
            $bankAccount->id,
            null,
            '2026-08-01',
            'opening_balance',
            500000
        );

        $this->createTransaction(
            $user->id,
            $cashAccount->id,
            null,
            '2026-08-01',
            'opening_balance',
            30000
        );

        $fromTransaction = $this->createTransaction(
            $user->id,
            $bankAccount->id,
            null,
            '2026-08-15',
            'transfer',
            30000
        );

        $toTransaction = $this->createTransaction(
            $user->id,
            $cashAccount->id,
            null,
            '2026-08-15',
            'transfer',
            30000
        );

        Transfer::create([
            'from_transaction_id' =>
                $fromTransaction->id,

            'to_transaction_id' =>
                $toTransaction->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('summary.index')
            );

        $response->assertOk();

        $response->assertSee('銀行');

        $response->assertSee(
            '470,000'
        );

        $response->assertSee('現金');

        $response->assertSee(
            '60,000'
        );
    }

    public function test_invalid_month_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route(
                    'summary.index',
                    [
                        'month' => 'invalid',
                    ]
                )
            );

        $response->assertSessionHasErrors(
            'month'
        );
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
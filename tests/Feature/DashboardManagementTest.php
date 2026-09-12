<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_dashboard(): void
    {
        $response = $this->get(
            route('dashboard')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            );

        $response->assertSuccessful();

        $response->assertSee(
            $user->name
        );

        $response->assertSee(
            'ダッシュボード'
        );
    }

    public function test_dashboard_displays_current_month_income_expense_and_balance(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '銀行',
        ]);

        $incomeCategory = Category::create([
            'user_id' => $user->id,
            'name' => '給与',
        ]);

        $expenseCategory = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::INCOME,
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'counterparty_name' => '会社',
            'amount' => 300000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::EXPENSE,
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'counterparty_name' => 'スーパー',
            'amount' => 5000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            );

        $response->assertSuccessful();

        $response->assertSee(
            '300,000'
        );

        $response->assertSee(
            '5,000'
        );

        $response->assertSee(
            '295,000'
        );
    }

    public function test_dashboard_account_balance_includes_opening_balance_income_and_expense(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
        ]);

        $incomeCategory = Category::create([
            'user_id' => $user->id,
            'name' => '給与',
        ]);

        $expenseCategory = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::OPENING_BALANCE,
            'account_id' => $account->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 500000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::INCOME,
            'account_id' => $account->id,
            'category_id' => $incomeCategory->id,
            'counterparty_name' => '会社',
            'amount' => 300000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::EXPENSE,
            'account_id' => $account->id,
            'category_id' => $expenseCategory->id,
            'counterparty_name' => 'スーパー',
            'amount' => 30000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            );

        $response->assertSuccessful();

        $response->assertSee(
            '三井住友銀行'
        );

        $response->assertSee(
            '770,000'
        );
    }

    public function test_dashboard_account_balance_reflects_transfer(): void
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

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::OPENING_BALANCE,
            'account_id' => $fromAccount->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 100000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::OPENING_BALANCE,
            'account_id' => $toAccount->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 10000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $fromTransaction = Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::TRANSFER,
            'account_id' => $fromAccount->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 30000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $toTransaction = Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::TRANSFER,
            'account_id' => $toAccount->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 30000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        Transfer::create([
            'from_transaction_id' => $fromTransaction->id,
            'to_transaction_id' => $toTransaction->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            );

        $response->assertSuccessful();

        $response->assertSee(
            '70,000'
        );

        $response->assertSee(
            '40,000'
        );
    }

    public function test_dashboard_only_uses_current_users_data(): void
    {
        $user = User::factory()->create();

        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '自分の銀行',
        ]);

        $otherAccount = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '給与',
        ]);

        $otherCategory = Category::create([
            'user_id' => $otherUser->id,
            'name' => '他人の給与',
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::INCOME,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'counterparty_name' => '自分の会社',
            'amount' => 123456,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        Transaction::create([
            'user_id' => $otherUser->id,
            'transaction_date' => now()->toDateString(),
            'type' => TransactionType::INCOME,
            'account_id' => $otherAccount->id,
            'category_id' => $otherCategory->id,
            'counterparty_name' => '他人の会社',
            'amount' => 987654,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('dashboard')
            );

        $response->assertSuccessful();

        $response->assertSee(
            '123,456'
        );

        $response->assertSee(
            '自分の銀行'
        );

        $response->assertDontSee(
            '987,654'
        );

        $response->assertDontSee(
            '他人の銀行'
        );
    }
}
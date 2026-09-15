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
use App\Services\SummaryService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferClassificationAndLiabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_category_is_saved_to_both_transactions(): void
    {
        $user = User::factory()->create();
        $bank = $this->account($user, '銀行', AccountType::BANK);
        $card = $this->account($user, 'カード', AccountType::CREDIT_CARD);
        $category = $this->transferCategory($user, 'カード利用代金引落');

        app(TransactionService::class)->createTransfer(
            user: $user,
            fromAccount: $bank,
            toAccount: $card,
            transactionDate: '2026-09-15',
            amount: 20000,
            categoryId: $category->id,
        );

        $this->assertSame(2, Transaction::query()
            ->where('category_id', $category->id)
            ->where('type', TransactionType::TRANSFER->value)
            ->count());
    }

    public function test_borrowing_and_repayment_change_liability_without_affecting_income_or_expense(): void
    {
        $user = User::factory()->create();
        $bank = $this->account($user, '銀行', AccountType::BANK);
        $loan = $this->account($user, 'ローン', AccountType::LIABILITY);
        $borrow = $this->transferCategory($user, '借入');
        $repay = $this->transferCategory($user, '返済');

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '1900-01-01',
            'type' => TransactionType::OPENING_BALANCE,
            'account_id' => $loan->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 300000,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $service = app(TransactionService::class);
        $service->createTransfer($user, $loan, $bank, '2026-09-10', 100000, $borrow->id);
        $service->createTransfer($user, $bank, $loan, '2026-09-11', 50000, $repay->id);

        $summary = app(SummaryService::class);
        $liability = $summary->getDashboardLiabilityBalances($user)->first();
        $monthly = $summary->getMonthlySummary($user, '2026-09');

        $this->assertSame(350000, $liability['balance']);
        $this->assertSame(0, $monthly['monthlyIncome']);
        $this->assertSame(0, $monthly['monthlyExpense']);
    }

    public function test_transfer_category_is_shown_in_counterparty_column(): void
    {
        $user = User::factory()->create();
        $bank = $this->account($user, '銀行', AccountType::BANK);
        $card = $this->account($user, '楽天カード', AccountType::CREDIT_CARD);
        $category = $this->transferCategory($user, 'カード利用代金引落');

        app(TransactionService::class)->createTransfer($user, $bank, $card, '2026-09-15', 20000, $category->id);

        $this->actingAs($user)
            ->get(route('transactions.index'))
            ->assertOk()
            ->assertSee('カード利用代金引落');
    }


    public function test_management_pages_show_new_account_and_transfer_types(): void
    {
        $user = User::factory()->create();

        $this->account($user, 'PayPay', AccountType::E_MONEY);
        $this->account($user, 'ローン', AccountType::LIABILITY);

        $this->actingAs($user)
            ->get(route('accounts.index'))
            ->assertOk()
            ->assertSee('電子マネー・決済')
            ->assertSee('借入');

        $this->actingAs($user)
            ->get(route('categories.index'))
            ->assertOk()
            ->assertSee('振替');
    }

    public function test_transfer_requires_transfer_category(): void
    {
        $user = User::factory()->create();
        $bank = $this->account($user, '銀行', AccountType::BANK);
        $cash = $this->account($user, '現金', AccountType::CASH);
        $expense = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
            'name' => '食費',
        ]);

        $this->actingAs($user)
            ->post(route('transactions.store'), [
                'type' => TransactionType::TRANSFER->value,
                'transaction_date' => '2026-09-15',
                'from_account_id' => $bank->id,
                'to_account_id' => $cash->id,
                'category_id' => $expense->id,
                'amount' => 1000,
            ])
            ->assertSessionHasErrors('category_id');
    }

    private function account(User $user, string $name, AccountType $type): Account
    {
        return Account::create([
            'user_id' => $user->id,
            'name' => $name,
            'type' => $type,
        ]);
    }

    private function transferCategory(User $user, string $name): Category
    {
        return Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::TRANSFER,
            'name' => $name,
        ]);
    }
}
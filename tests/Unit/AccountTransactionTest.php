<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class AccountTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_has_transactions_relationship(): void
    {
        $account = new Account();

        $this->assertInstanceOf(
            HasMany::class,
            $account->transactions(),
        );
    }

    public function test_transaction_belongs_to_account_relationship(): void
    {
        $transaction = new Transaction();

        $this->assertInstanceOf(
            BelongsTo::class,
            $transaction->account(),
        );
    }
}
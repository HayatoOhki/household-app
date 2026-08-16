<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\TransactionType;
use PHPUnit\Framework\TestCase;

class TransactionTypeTest extends TestCase
{
    public function test_transaction_types_are_defined(): void
    {
        $this->assertSame('expense', TransactionType::EXPENSE->value);
        $this->assertSame('income', TransactionType::INCOME->value);
        $this->assertSame('transfer', TransactionType::TRANSFER->value);
        $this->assertSame('opening_balance', TransactionType::OPENING_BALANCE->value);
    }

    public function test_transaction_type_can_be_created_from_value(): void
    {
        $this->assertSame(
            TransactionType::EXPENSE,
            TransactionType::from('expense')
        );

        $this->assertSame(
            TransactionType::INCOME,
            TransactionType::from('income')
        );

        $this->assertSame(
            TransactionType::TRANSFER,
            TransactionType::from('transfer')
        );

        $this->assertSame(
            TransactionType::OPENING_BALANCE,
            TransactionType::from('opening_balance')
        );
    }

    public function test_withdrawal_is_not_defined(): void
    {
        $values = array_map(
            static fn (TransactionType $type): string => $type->value,
            TransactionType::cases()
        );

        $this->assertNotContains('withdrawal', $values);
    }
}
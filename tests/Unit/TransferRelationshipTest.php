<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Transaction;
use App\Models\Transfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_belongs_to_from_transaction(): void
    {
        $transfer = new Transfer();

        $relation = $transfer->fromTransaction();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
        $this->assertSame('from_transaction_id', $relation->getForeignKeyName());
    }

    public function test_transfer_belongs_to_to_transaction(): void
    {
        $transfer = new Transfer();

        $relation = $transfer->toTransaction();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
        $this->assertSame('to_transaction_id', $relation->getForeignKeyName());
    }

    public function test_transaction_has_outgoing_transfer(): void
    {
        $transaction = new Transaction();

        $relation = $transaction->outgoingTransfer();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasOne::class,
            $relation
        );
        $this->assertSame('from_transaction_id', $relation->getForeignKeyName());
    }

    public function test_transaction_has_incoming_transfer(): void
    {
        $transaction = new Transaction();

        $relation = $transaction->incomingTransfer();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasOne::class,
            $relation
        );
        $this->assertSame('to_transaction_id', $relation->getForeignKeyName());
    }
}
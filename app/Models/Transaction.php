<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'transaction_date',
        'type',
        'account_id',
        'category_id',
        'counterparty_name',
        'amount',
        'withdrawal_date',
        'expense_ratio',
        'expense_registered',
        'receipt_saved',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'withdrawal_date' => 'date',
            'type' => TransactionType::class,
            'amount' => 'integer',
            'expense_ratio' => 'decimal:2',
            'expense_registered' => 'boolean',
            'receipt_saved' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function outgoingTransfer(): HasOne
    {
        return $this->hasOne(
            Transfer::class,
            'from_transaction_id'
        );
    }

    public function incomingTransfer(): HasOne
    {
        return $this->hasOne(
            Transfer::class,
            'to_transaction_id'
        );
    }
}
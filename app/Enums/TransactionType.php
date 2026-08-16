<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionType: string
{
    case EXPENSE = 'expense';
    case INCOME = 'income';
    case TRANSFER = 'transfer';
    case OPENING_BALANCE = 'opening_balance';
}
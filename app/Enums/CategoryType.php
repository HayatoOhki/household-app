<?php

declare(strict_types=1);

namespace App\Enums;

enum CategoryType: string
{
    case EXPENSE = 'expense';
    case INCOME = 'income';
    case TRANSFER = 'transfer';

    public function label(): string
    {
        return match ($this) {
            self::EXPENSE => '支出',
            self::INCOME => '収入',
            self::TRANSFER => '振替',
        };
    }
}

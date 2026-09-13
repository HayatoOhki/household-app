<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case CREDIT_CARD = 'credit_card';

    public function label(): string
    {
        return match ($this) {
            self::CASH => '現金',
            self::BANK => '銀行',
            self::CREDIT_CARD => 'クレジットカード',
        };
    }
}
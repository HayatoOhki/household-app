<?php

declare(strict_types=1);

namespace App\Enums;

enum AccountType: string
{
    case CASH = 'cash';
    case BANK = 'bank';
    case CREDIT_CARD = 'credit_card';
    case E_MONEY = 'e_money';
    case LIABILITY = 'liability';

    public function label(): string
    {
        return match ($this) {
            self::CASH => '現金',
            self::BANK => '銀行',
            self::CREDIT_CARD => 'クレジットカード',
            self::E_MONEY => '電子マネー・決済',
            self::LIABILITY => '借入',
        };
    }
}
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionRule;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HouseholdDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $categories = [];

        foreach ([
            '食費',
            '交通費',
            '家賃',
            '給与',
            'その他',
        ] as $name) {
            $categories[$name] = Category::create([
                'user_id' => $user->id,
                'name' => $name,
            ]);
        }

        $accounts = [];

        foreach ([
            '現金',
            '三井住友銀行',
            'クレジットカード',
        ] as $name) {
            $accounts[$name] = Account::create([
                'user_id' => $user->id,
                'name' => $name,
            ]);
        }

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-08-01',
            'type' => TransactionType::EXPENSE->value,
            'account_id' => $accounts['三井住友銀行']->id,
            'category_id' => $categories['家賃']->id,
            'counterparty_name' => 'ﾔﾁﾝ',
            'amount' => 100000,
            'expense_ratio' => 40,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-08-02',
            'type' => TransactionType::EXPENSE->value,
            'account_id' => $accounts['クレジットカード']->id,
            'category_id' => $categories['食費']->id,
            'counterparty_name' => 'Amazon',
            'amount' => 3500,
            'withdrawal_date' => '2026-08-27',
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-08-10',
            'type' => TransactionType::INCOME->value,
            'account_id' => $accounts['三井住友銀行']->id,
            'category_id' => $categories['給与']->id,
            'counterparty_name' => '会社',
            'amount' => 300000,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $bankTransfer = Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-08-15',
            'type' => TransactionType::TRANSFER->value,
            'account_id' => $accounts['三井住友銀行']->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 30000,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $cashDeposit = Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-08-15',
            'type' => TransactionType::TRANSFER->value,
            'account_id' => $accounts['現金']->id,
            'category_id' => null,
            'counterparty_name' => null,
            'amount' => 30000,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        Transfer::create([
            'from_transaction_id' => $bankTransfer->id,
            'to_transaction_id' => $cashDeposit->id,
        ]);

        TransactionRule::create([
            'user_id' => $user->id,
            'keyword' => 'ﾔﾁﾝ',
            'display_name' => '家賃',
            'category_id' => $categories['家賃']->id,
            'priority' => 100,
        ]);
    }
}
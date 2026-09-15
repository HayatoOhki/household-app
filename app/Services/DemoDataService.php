<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionRule;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DemoDataService
{
    private const DEMO_NAME = 'デモユーザー';

    private const DEMO_EMAIL = 'demo@example.com';

    private const DEMO_PASSWORD = 'password';

    public function __construct(
        private readonly TransactionService $transactionService
    ) {
    }

    public function setup(): User
    {
        if (
            User::query()
                ->where('email', self::DEMO_EMAIL)
                ->exists()
        ) {
            throw new RuntimeException(
                'デモユーザーは既に存在します。'
            );
        }

        return DB::transaction(function (): User {
            $user = User::create([
                'name' => self::DEMO_NAME,
                'email' => self::DEMO_EMAIL,
                'password' => Hash::make(
                    self::DEMO_PASSWORD
                ),
            ]);

            $categories = $this->createCategories(
                $user
            );

            $accounts = $this->createAccounts(
                $user
            );

            $this->createOpeningBalances(
                $user,
                $accounts
            );

            $this->createTransactionRules(
                $user,
                $accounts,
                $categories
            );

            $this->createPreviousYearTransactions(
                $user,
                $accounts,
                $categories
            );

            $this->createCurrentYearTransactions(
                $user,
                $accounts,
                $categories
            );

            return $user;
        });
    }

    /**
     * @return array<string, Category>
     */
    private function createCategories(
        User $user
    ): array {
        $categories = [];

        $categoryData = [
            ['key' => '給与', 'name' => '給与', 'type' => 'income'],
            ['key' => '副業収入', 'name' => '副業収入', 'type' => 'income'],
            ['key' => '収入その他', 'name' => 'その他', 'type' => 'income'],
            ['key' => '食費', 'name' => '食費', 'type' => 'expense'],
            ['key' => '日用品', 'name' => '日用品', 'type' => 'expense'],
            ['key' => '家賃', 'name' => '家賃', 'type' => 'expense'],
            ['key' => '水道光熱費', 'name' => '水道光熱費', 'type' => 'expense'],
            ['key' => '通信費', 'name' => '通信費', 'type' => 'expense'],
            ['key' => '交通費', 'name' => '交通費', 'type' => 'expense'],
            ['key' => '娯楽費', 'name' => '娯楽費', 'type' => 'expense'],
            ['key' => '医療費', 'name' => '医療費', 'type' => 'expense'],
            ['key' => '衣服', 'name' => '衣服', 'type' => 'expense'],
            ['key' => 'その他', 'name' => 'その他', 'type' => 'expense'],
            ['key' => 'カード利用代金引落', 'name' => 'カード利用代金引落', 'type' => 'transfer'],
            ['key' => 'チャージ', 'name' => 'チャージ', 'type' => 'transfer'],
            ['key' => '借入', 'name' => '借入', 'type' => 'transfer'],
            ['key' => '返済', 'name' => '返済', 'type' => 'transfer'],
            ['key' => '資金移動', 'name' => '資金移動', 'type' => 'transfer'],
        ];

        $sortOrders = ['income' => 0, 'expense' => 0, 'transfer' => 0];

        foreach ($categoryData as $row) {
            $sortOrders[$row['type']] += 10;
            $categories[$row['key']] = Category::create([
                'user_id' => $user->id,
                'type' => $row['type'],
                'name' => $row['name'],
                'sort_order' => $sortOrders[$row['type']],
            ]);
        }

        return $categories;
    }

    /**
     * @return array<string, Account>
     */
    private function createAccounts(
        User $user
    ): array {
        $accounts = [];

        $accountData = [
            [
                'name' => '現金',
                'type' => AccountType::CASH,
                'sort_order' => 10,
            ],
            [
                'name' => '三井住友銀行',
                'type' => AccountType::BANK,
                'sort_order' => 20,
            ],
            [
                'name' => '楽天銀行',
                'type' => AccountType::BANK,
                'sort_order' => 30,
            ],
            [
                'name' => '楽天カード',
                'type' =>
                    AccountType::CREDIT_CARD,
                'sort_order' => 40,
            ],
            [
                'name' => '三井住友カード',
                'type' =>
                    AccountType::CREDIT_CARD,
                'sort_order' => 50,
            ],
            [
                'name' => 'PayPay',
                'type' => AccountType::E_MONEY,
                'sort_order' => 60,
            ],
            [
                'name' => 'サンプルローン',
                'type' => AccountType::LIABILITY,
                'sort_order' => 70,
            ],
        ];

        foreach ($accountData as $data) {
            $accounts[$data['name']] =
                Account::create([
                    'user_id' => $user->id,
                    'name' => $data['name'],
                    'type' => $data['type'],
                    'sort_order' =>
                        $data['sort_order'],
                ]);
        }

        return $accounts;
    }

    /**
     * @param array<string, Account> $accounts
     */
    private function createOpeningBalances(
        User $user,
        array $accounts
    ): void {
        $openingDate = CarbonImmutable::now()
            ->subYear()
            ->startOfYear()
            ->toDateString();

        $balances = [
            '現金' => 30000,
            '三井住友銀行' => 650000,
            '楽天銀行' => 180000,
            'PayPay' => 5000,
            'サンプルローン' => 300000,
        ];

        foreach (
            $balances as $accountName => $amount
        ) {
            Transaction::create([
                'user_id' => $user->id,
                'transaction_date' =>
                    $openingDate,
                'type' =>
                    TransactionType::OPENING_BALANCE,
                'account_id' =>
                    $accounts[$accountName]->id,
                'category_id' => null,
                'counterparty_name' => null,
                'amount' => $amount,
                'withdrawal_date' => null,
                'expense_ratio' => 0,
                'expense_registered' => false,
                'receipt_saved' => false,
            ]);
        }
    }

    /**
     * @param array<string, Account> $accounts
     * @param array<string, Category> $categories
     */
    private function createTransactionRules(
        User $user,
        array $accounts,
        array $categories
    ): void {
        $rules = [
            [
                'account' => '楽天カード',
                'keyword' => 'AMAZON.CO.JP',
                'display_name' => 'Amazon',
                'category' => '日用品',
            ],
            [
                'account' => '楽天カード',
                'keyword' => 'セブンイレブン',
                'display_name' =>
                    'セブンイレブン',
                'category' => '食費',
            ],
            [
                'account' => '楽天カード',
                'keyword' => 'イオン',
                'display_name' => 'イオン',
                'category' => '食費',
            ],
            [
                'account' => '三井住友カード',
                'keyword' => 'ENEOS',
                'display_name' => 'ENEOS',
                'category' => '交通費',
            ],
            [
                'account' => '三井住友カード',
                'keyword' => 'NETFLIX',
                'display_name' => 'Netflix',
                'category' => '娯楽費',
            ],
            [
                'account' => '三井住友銀行',
                'keyword' => 'NTT',
                'display_name' =>
                    'インターネット',
                'category' => '通信費',
            ],
            [
                'account' => '三井住友銀行',
                'keyword' => 'ﾔﾁﾝ',
                'display_name' => '家賃',
                'category' => '家賃',
            ],
        ];

        foreach ($rules as $rule) {
            TransactionRule::create([
                'user_id' => $user->id,
                'account_id' =>
                    $accounts[
                        $rule['account']
                    ]->id,
                'keyword' => $rule['keyword'],
                'display_name' =>
                    $rule['display_name'],
                'category_id' =>
                    $categories[
                        $rule['category']
                    ]->id,
            ]);
        }
    }

    /**
     * 前年は年間収支画面の年切替確認用。
     *
     * @param array<string, Account> $accounts
     * @param array<string, Category> $categories
     */
    private function createPreviousYearTransactions(
        User $user,
        array $accounts,
        array $categories
    ): void {
        $year =
            CarbonImmutable::now()->year - 1;

        foreach (
            [1, 3, 6, 9, 12] as $month
        ) {
            $this->createMonthlyIncome(
                $user,
                $accounts,
                $categories,
                $year,
                $month,
                300000 + ($month * 1000)
            );

            $this->createExpense(
                user: $user,
                account:
                    $accounts['三井住友銀行'],
                category:
                    $categories['家賃'],
                date: $this->date(
                    $year,
                    $month,
                    2
                ),
                counterparty: 'ﾔﾁﾝ',
                amount: 82000,
                expenseRatio: 40,
                expenseRegistered:
                    $month === 6,
                receiptSaved:
                    $month === 6
            );

            $this->createExpense(
                user: $user,
                account:
                    $accounts['楽天カード'],
                category:
                    $categories['食費'],
                date: $this->date(
                    $year,
                    $month,
                    8
                ),
                counterparty: 'イオン',
                amount:
                    12500 + ($month * 300),
                withdrawalDate:
                    $this->creditWithdrawalDate(
                        $year,
                        $month
                    )
            );

            $this->createExpense(
                user: $user,
                account:
                    $accounts['現金'],
                category:
                    $categories['食費'],
                date: $this->date(
                    $year,
                    $month,
                    15
                ),
                counterparty: 'ランチ',
                amount:
                    1800 + ($month * 100)
            );

            $this->createExpense(
                user: $user,
                account:
                    $accounts[
                        '三井住友カード'
                    ],
                category:
                    $categories['交通費'],
                date: $this->date(
                    $year,
                    $month,
                    18
                ),
                counterparty: 'ENEOS',
                amount:
                    6500 + ($month * 100),
                withdrawalDate:
                    $this->creditWithdrawalDate(
                        $year,
                        $month
                    ),
                expenseRatio: 50
            );
        }

        $this->transactionService
            ->createTransfer(
                user: $user,
                fromAccount:
                    $accounts[
                        '三井住友銀行'
                    ],
                toAccount:
                    $accounts['楽天銀行'],
                transactionDate:
                    $this->date(
                        $year,
                        6,
                        20
                    ),
                amount: 50000,
                categoryId: $categories['資金移動']->id,
            );
    }

    /**
     * @param array<string, Account> $accounts
     * @param array<string, Category> $categories
     */
    private function createCurrentYearTransactions(
        User $user,
        array $accounts,
        array $categories
    ): void {
        $now = CarbonImmutable::now();

        $year = $now->year;

        for (
            $month = 1;
            $month <= $now->month;
            $month++
        ) {
            $this->createCurrentMonthData(
                $user,
                $accounts,
                $categories,
                $year,
                $month
            );
        }
    }

    /**
     * @param array<string, Account> $accounts
     * @param array<string, Category> $categories
     */
    private function createCurrentMonthData(
        User $user,
        array $accounts,
        array $categories,
        int $year,
        int $month
    ): void {
        $this->createMonthlyIncome(
            $user,
            $accounts,
            $categories,
            $year,
            $month,
            320000
                + (($month % 3) * 5000)
        );

        $this->createExpense(
            user: $user,
            account:
                $accounts['三井住友銀行'],
            category:
                $categories['家賃'],
            date:
                $this->safeCurrentDate(
                    $year,
                    $month,
                    2
                ),
            counterparty: 'ﾔﾁﾝ',
            amount: 85000,
            expenseRatio: 40,
            expenseRegistered:
                $month % 2 === 0,
            receiptSaved:
                $month % 3 === 0
        );

        $this->createExpense(
            user: $user,
            account:
                $accounts['楽天カード'],
            category:
                $categories['食費'],
            date:
                $this->safeCurrentDate(
                    $year,
                    $month,
                    4
                ),
            counterparty: 'イオン',
            amount:
                11800 + ($month * 450),
            withdrawalDate:
                $this->creditWithdrawalDate(
                    $year,
                    $month
                )
        );

        $this->createExpense(
            user: $user,
            account:
                $accounts['楽天カード'],
            category:
                $categories['日用品'],
            date:
                $this->safeCurrentDate(
                    $year,
                    $month,
                    6
                ),
            counterparty:
                'AMAZON.CO.JP',
            amount:
                3500 + ($month * 230),
            withdrawalDate:
                $this->creditWithdrawalDate(
                    $year,
                    $month
                ),
            expenseRatio:
                $month % 3 === 0
                    ? 100
                    : 0,
            expenseRegistered:
                $month % 3 === 0,
            receiptSaved:
                $month % 3 === 0
        );

        $this->createExpense(
            user: $user,
            account:
                $accounts['三井住友銀行'],
            category:
                $categories['通信費'],
            date:
                $this->safeCurrentDate(
                    $year,
                    $month,
                    7
                ),
            counterparty: 'NTT',
            amount: 5280,
            expenseRatio: 50,
            expenseRegistered:
                $month % 2 === 1
        );

        $this->createExpense(
            user: $user,
            account:
                $accounts[
                    '三井住友カード'
                ],
            category:
                $categories['交通費'],
            date:
                $this->safeCurrentDate(
                    $year,
                    $month,
                    8
                ),
            counterparty: 'ENEOS',
            amount:
                6200 + ($month * 180),
            withdrawalDate:
                $this->creditWithdrawalDate(
                    $year,
                    $month
                ),
            expenseRatio: 50
        );

        $this->createExpense(
            user: $user,
            account:
                $accounts['現金'],
            category:
                $categories['食費'],
            date:
                $this->safeCurrentDate(
                    $year,
                    $month,
                    9
                ),
            counterparty: 'ランチ',
            amount:
                1200 + ($month * 80)
        );

        $this->createExpense(
            user: $user,
            account:
                $accounts[
                    '三井住友カード'
                ],
            category:
                $categories['娯楽費'],
            date:
                $this->safeCurrentDate(
                    $year,
                    $month,
                    10
                ),
            counterparty: 'NETFLIX',
            amount: 1980,
            withdrawalDate:
                $this->creditWithdrawalDate(
                    $year,
                    $month
                )
        );

        $this->createUtilityExpense(
            $user,
            $accounts,
            $categories,
            $year,
            $month
        );

        if ($month % 2 === 0) {
            $this->transactionService
                ->createTransfer(
                    user: $user,
                    fromAccount:
                        $accounts[
                            '三井住友銀行'
                        ],
                    toAccount:
                        $accounts['現金'],
                    transactionDate:
                        $this->safeCurrentDate(
                            $year,
                            $month,
                            11
                        ),
                    amount: 20000,
                    categoryId: $categories['資金移動']->id,
                );
        }

        if ($month % 3 === 0) {
            Transaction::create([
                'user_id' =>
                    $user->id,
                'transaction_date' =>
                    $this->safeCurrentDate(
                        $year,
                        $month,
                        12
                    ),
                'type' =>
                    TransactionType::INCOME,
                'account_id' =>
                    $accounts[
                        '楽天銀行'
                    ]->id,
                'category_id' =>
                    $categories[
                        '副業収入'
                    ]->id,
                'counterparty_name' =>
                    '副業クライアント',
                'amount' =>
                    40000
                    + ($month * 1000),
                'withdrawal_date' =>
                    null,
                'expense_ratio' => 100,
                'expense_registered' =>
                    true,
                'receipt_saved' =>
                    false,
            ]);
        }

        $demoDate = $this->safeCurrentDate($year, $month, 20);

        $this->transactionService->createTransfer(
            user: $user,
            fromAccount: $accounts['三井住友銀行'],
            toAccount: $accounts['PayPay'],
            transactionDate: $demoDate,
            amount: 5000,
            categoryId: $categories['チャージ']->id,
        );

        $this->transactionService->createTransfer(
            user: $user,
            fromAccount: $accounts['三井住友銀行'],
            toAccount: $accounts['楽天カード'],
            transactionDate: $demoDate,
            amount: 20000,
            categoryId: $categories['カード利用代金引落']->id,
        );

        $this->transactionService->createTransfer(
            user: $user,
            fromAccount: $accounts['サンプルローン'],
            toAccount: $accounts['三井住友銀行'],
            transactionDate: $demoDate,
            amount: 100000,
            categoryId: $categories['借入']->id,
        );

        $this->transactionService->createTransfer(
            user: $user,
            fromAccount: $accounts['三井住友銀行'],
            toAccount: $accounts['サンプルローン'],
            transactionDate: $demoDate,
            amount: 50000,
            categoryId: $categories['返済']->id,
        );
    }

    /**
     * @param array<string, Account> $accounts
     * @param array<string, Category> $categories
     */
    private function createMonthlyIncome(
        User $user,
        array $accounts,
        array $categories,
        int $year,
        int $month,
        int $amount
    ): void {
        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' =>
                $this->safeCurrentDate(
                    $year,
                    $month,
                    5
                ),
            'type' =>
                TransactionType::INCOME,
            'account_id' =>
                $accounts[
                    '三井住友銀行'
                ]->id,
            'category_id' =>
                $categories['給与']->id,
            'counterparty_name' =>
                '株式会社サンプル',
            'amount' => $amount,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' =>
                false,
            'receipt_saved' =>
                false,
        ]);
    }

    /**
     * @param array<string, Account> $accounts
     * @param array<string, Category> $categories
     */
    private function createUtilityExpense(
        User $user,
        array $accounts,
        array $categories,
        int $year,
        int $month
    ): void {
        $amount = match ($month) {
            1, 2 => 12500,
            7, 8 => 11800,
            default => 7800,
        };

        $this->createExpense(
            user: $user,
            account:
                $accounts[
                    '三井住友カード'
                ],
            category:
                $categories[
                    '水道光熱費'
                ],
            date:
                $this->safeCurrentDate(
                    $year,
                    $month,
                    11
                ),
            counterparty: '東京電力',
            amount: $amount,
            withdrawalDate:
                $this->creditWithdrawalDate(
                    $year,
                    $month
                ),
            expenseRatio: 40,
            receiptSaved: true
        );
    }

    private function createExpense(
        User $user,
        Account $account,
        Category $category,
        string $date,
        string $counterparty,
        int $amount,
        ?string $withdrawalDate = null,
        float $expenseRatio = 0,
        bool $expenseRegistered = false,
        bool $receiptSaved = false
    ): Transaction {
        return Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => $date,
            'type' =>
                TransactionType::EXPENSE,
            'account_id' =>
                $account->id,
            'category_id' =>
                $category->id,
            'counterparty_name' =>
                $counterparty,
            'amount' => $amount,
            'withdrawal_date' =>
                $withdrawalDate,
            'expense_ratio' =>
                $expenseRatio,
            'expense_registered' =>
                $expenseRegistered,
            'receipt_saved' =>
                $receiptSaved,
        ]);
    }

    private function creditWithdrawalDate(
        int $year,
        int $month
    ): string {
        return CarbonImmutable::create(
            $year,
            $month,
            1
        )
            ->addMonth()
            ->setDay(27)
            ->toDateString();
    }

    private function safeCurrentDate(
        int $year,
        int $month,
        int $day
    ): string {
        $now = CarbonImmutable::now();

        if (
            $year < $now->year
            || (
                $year === $now->year
                && $month < $now->month
            )
        ) {
            return $this->date(
                $year,
                $month,
                $day
            );
        }

        return $this->date(
            $year,
            $month,
            min(
                $day,
                $now->day
            )
        );
    }

    private function date(
        int $year,
        int $month,
        int $day
    ): string {
        return CarbonImmutable::create(
            $year,
            $month,
            $day
        )->toDateString();
    }
}
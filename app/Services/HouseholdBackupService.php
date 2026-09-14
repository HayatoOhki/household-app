<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionRule;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

class HouseholdBackupService
{
    private const FORMAT = 'household-app-backup';

    private const VERSION = 1;

    /**
     * ログインユーザーの家計簿データをバックアップ用配列に変換する。
     *
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        $accounts = $user->accounts()
            ->orderBy('id')
            ->get();

        $categories = $user->categories()
            ->orderBy('id')
            ->get();

        $transactions = $user->transactions()
            ->orderBy('id')
            ->get();

        $transactionIds = $transactions
            ->pluck('id');

        $transfers = Transfer::query()
            ->whereIn('from_transaction_id', $transactionIds)
            ->whereIn('to_transaction_id', $transactionIds)
            ->orderBy('id')
            ->get();

        $transactionRules = $user->transactionRules()
            ->orderBy('id')
            ->get();

        return [
            'format' => self::FORMAT,
            'version' => self::VERSION,
            'exported_at' => now()->toIso8601String(),

            'data' => [
                'accounts' => $accounts
                    ->map(fn (Account $account): array => [
                        'id' => $account->id,
                        'name' => $account->name,
                        'type' => $account->type->value,
                        'sort_order' => $account->sort_order,
                    ])
                    ->values()
                    ->all(),

                'categories' => $categories
                    ->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'sort_order' => $category->sort_order,
                    ])
                    ->values()
                    ->all(),

                'transactions' => $transactions
                    ->map(fn (Transaction $transaction): array => [
                        'id' => $transaction->id,
                        'transaction_date' => $transaction
                            ->transaction_date
                            ->format('Y-m-d'),
                        'type' => $transaction->type->value,
                        'account_id' => $transaction->account_id,
                        'category_id' => $transaction->category_id,
                        'counterparty_name' => $transaction->counterparty_name,
                        'amount' => $transaction->amount,
                        'withdrawal_date' => $transaction->withdrawal_date
                            ?->format('Y-m-d'),
                        'expense_ratio' => $transaction->expense_ratio,
                        'expense_registered' => $transaction->expense_registered,
                        'receipt_saved' => $transaction->receipt_saved,
                    ])
                    ->values()
                    ->all(),

                'transfers' => $transfers
                    ->map(fn (Transfer $transfer): array => [
                        'id' => $transfer->id,
                        'from_transaction_id' => $transfer->from_transaction_id,
                        'to_transaction_id' => $transfer->to_transaction_id,
                    ])
                    ->values()
                    ->all(),

                'transaction_rules' => $transactionRules
                    ->map(fn (TransactionRule $rule): array => [
                        'id' => $rule->id,
                        'account_id' => $rule->account_id,
                        'keyword' => $rule->keyword,
                        'display_name' => $rule->display_name,
                        'category_id' => $rule->category_id,
                    ])
                    ->values()
                    ->all(),
            ],
        ];
    }

    /**
     * JSON文字列を解析する。
     *
     * @return array<string, mixed>
     */
    public function decode(string $json): array
    {
        try {
            $backup = json_decode(
                $json,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException) {
            throw ValidationException::withMessages([
                'backup_file' => 'JSONファイルの形式が正しくありません。',
            ]);
        }

        if (! is_array($backup)) {
            throw ValidationException::withMessages([
                'backup_file' => 'バックアップファイルの形式が正しくありません。',
            ]);
        }

        $this->validateBackup($backup);

        return $backup;
    }

    /**
     * バックアップデータからログインユーザーの家計簿を完全復元する。
     *
     * @param array<string, mixed> $backup
     */
    public function restore(
        User $user,
        array $backup
    ): void {
        $this->validateBackup($backup);

        try {
            DB::transaction(function () use ($user, $backup): void {
                $data = $backup['data'];

                $this->deleteCurrentData($user);

                $accountIdMap = $this->restoreAccounts(
                    $user,
                    $data['accounts']
                );

                $categoryIdMap = $this->restoreCategories(
                    $user,
                    $data['categories']
                );

                $transactionIdMap = $this->restoreTransactions(
                    $user,
                    $data['transactions'],
                    $accountIdMap,
                    $categoryIdMap
                );

                $this->restoreTransfers(
                    $data['transfers'],
                    $transactionIdMap
                );

                $this->restoreTransactionRules(
                    $user,
                    $data['transaction_rules'],
                    $accountIdMap,
                    $categoryIdMap
                );
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            report($exception);

            throw ValidationException::withMessages([
                'backup_file' => '復元処理に失敗しました。現在のデータは変更されていません。',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $backup
     */
    private function validateBackup(array $backup): void
    {
        if (($backup['format'] ?? null) !== self::FORMAT) {
            $this->invalidBackup(
                'このアプリのバックアップファイルではありません。'
            );
        }

        if (($backup['version'] ?? null) !== self::VERSION) {
            $this->invalidBackup(
                '対応していないバックアップバージョンです。'
            );
        }

        if (! isset($backup['data'])
            || ! is_array($backup['data'])) {
            $this->invalidBackup();
        }

        $requiredCollections = [
            'accounts',
            'categories',
            'transactions',
            'transfers',
            'transaction_rules',
        ];

        foreach ($requiredCollections as $collection) {
            if (! array_key_exists(
                $collection,
                $backup['data']
            )
                || ! is_array(
                    $backup['data'][$collection]
                )) {
                $this->invalidBackup();
            }
        }

        $this->validateAccounts(
            $backup['data']['accounts']
        );

        $this->validateCategories(
            $backup['data']['categories']
        );

        $this->validateTransactions(
            $backup['data']['transactions']
        );

        $this->validateTransfers(
            $backup['data']['transfers']
        );

        $this->validateTransactionRules(
            $backup['data']['transaction_rules']
        );

        $this->validateReferences($backup['data']);
    }

    /**
     * @param array<int, mixed> $accounts
     */
    private function validateAccounts(array $accounts): void
    {
        $ids = [];
        $names = [];

        foreach ($accounts as $account) {
            if (! is_array($account)
                || ! $this->isPositiveInteger($account['id'] ?? null)
                || ! is_string($account['name'] ?? null)
                || trim($account['name']) === ''
                || mb_strlen($account['name']) > 100
                || ! is_string($account['type'] ?? null)
                || AccountType::tryFrom($account['type']) === null
                || ! $this->isNonNegativeInteger(
                    $account['sort_order'] ?? null
                )) {
                $this->invalidBackup();
            }

            if (isset($ids[$account['id']])
                || isset($names[$account['name']])) {
                $this->invalidBackup();
            }

            $ids[$account['id']] = true;
            $names[$account['name']] = true;
        }
    }

    /**
     * @param array<int, mixed> $categories
     */
    private function validateCategories(array $categories): void
    {
        $ids = [];
        $names = [];

        foreach ($categories as $category) {
            if (! is_array($category)
                || ! $this->isPositiveInteger($category['id'] ?? null)
                || ! is_string($category['name'] ?? null)
                || trim($category['name']) === ''
                || mb_strlen($category['name']) > 100
                || ! $this->isNonNegativeInteger(
                    $category['sort_order'] ?? null
                )) {
                $this->invalidBackup();
            }

            if (isset($ids[$category['id']])
                || isset($names[$category['name']])) {
                $this->invalidBackup();
            }

            $ids[$category['id']] = true;
            $names[$category['name']] = true;
        }
    }

    /**
     * @param array<int, mixed> $transactions
     */
    private function validateTransactions(
        array $transactions
    ): void {
        $ids = [];

        foreach ($transactions as $transaction) {
            if (! is_array($transaction)
                || ! $this->isPositiveInteger(
                    $transaction['id'] ?? null
                )
                || ! $this->isDate(
                    $transaction['transaction_date'] ?? null
                )
                || ! is_string($transaction['type'] ?? null)
                || TransactionType::tryFrom(
                    $transaction['type']
                ) === null
                || ! $this->isNullablePositiveInteger(
                    $transaction['account_id'] ?? null
                )
                || ! $this->isNullablePositiveInteger(
                    $transaction['category_id'] ?? null
                )
                || ! $this->isNullableString(
                    $transaction['counterparty_name'] ?? null,
                    255
                )
                || ! $this->isPositiveInteger(
                    $transaction['amount'] ?? null
                )
                || ! $this->isNullableDate(
                    $transaction['withdrawal_date'] ?? null
                )
                || ! $this->isExpenseRatio(
                    $transaction['expense_ratio'] ?? null
                )
                || ! is_bool(
                    $transaction['expense_registered'] ?? null
                )
                || ! is_bool(
                    $transaction['receipt_saved'] ?? null
                )) {
                $this->invalidBackup();
            }

            if (isset($ids[$transaction['id']])) {
                $this->invalidBackup();
            }

            $ids[$transaction['id']] = true;
        }
    }

    /**
     * @param array<int, mixed> $transfers
     */
    private function validateTransfers(array $transfers): void
    {
        $ids = [];
        $fromIds = [];
        $toIds = [];

        foreach ($transfers as $transfer) {
            if (! is_array($transfer)
                || ! $this->isPositiveInteger(
                    $transfer['id'] ?? null
                )
                || ! $this->isPositiveInteger(
                    $transfer['from_transaction_id'] ?? null
                )
                || ! $this->isPositiveInteger(
                    $transfer['to_transaction_id'] ?? null
                )
                || $transfer['from_transaction_id']
                    === $transfer['to_transaction_id']) {
                $this->invalidBackup();
            }

            if (isset($ids[$transfer['id']])
                || isset(
                    $fromIds[$transfer['from_transaction_id']]
                )
                || isset(
                    $toIds[$transfer['to_transaction_id']]
                )) {
                $this->invalidBackup();
            }

            $ids[$transfer['id']] = true;
            $fromIds[$transfer['from_transaction_id']] = true;
            $toIds[$transfer['to_transaction_id']] = true;
        }
    }

    /**
     * @param array<int, mixed> $rules
     */
    private function validateTransactionRules(
        array $rules
    ): void {
        $ids = [];
        $uniqueRules = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)
                || ! $this->isPositiveInteger($rule['id'] ?? null)
                || ! $this->isPositiveInteger(
                    $rule['account_id'] ?? null
                )
                || ! is_string($rule['keyword'] ?? null)
                || trim($rule['keyword']) === ''
                || mb_strlen($rule['keyword']) > 255
                || ! $this->isNullableString(
                    $rule['display_name'] ?? null,
                    255
                )
                || ! $this->isNullablePositiveInteger(
                    $rule['category_id'] ?? null
                )) {
                $this->invalidBackup();
            }

            if (isset($ids[$rule['id']])) {
                $this->invalidBackup();
            }

            $uniqueKey = implode('|', [
                $rule['account_id'],
                $rule['keyword'],
                $rule['category_id'] ?? 'null',
            ]);

            if (isset($uniqueRules[$uniqueKey])) {
                $this->invalidBackup();
            }

            $ids[$rule['id']] = true;
            $uniqueRules[$uniqueKey] = true;
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateReferences(array $data): void
    {
        $accountIds = array_fill_keys(
            array_column($data['accounts'], 'id'),
            true
        );

        $categoryIds = array_fill_keys(
            array_column($data['categories'], 'id'),
            true
        );

        $transactionTypes = [];

        foreach ($data['transactions'] as $transaction) {
            $transactionTypes[$transaction['id']]
                = $transaction['type'];

            if ($transaction['account_id'] !== null
                && ! isset(
                    $accountIds[$transaction['account_id']]
                )) {
                $this->invalidBackup(
                    '取引が存在しない口座を参照しています。'
                );
            }

            if ($transaction['category_id'] !== null
                && ! isset(
                    $categoryIds[$transaction['category_id']]
                )) {
                $this->invalidBackup(
                    '取引が存在しないカテゴリを参照しています。'
                );
            }
        }

        foreach ($data['transfers'] as $transfer) {
            $fromId = $transfer['from_transaction_id'];
            $toId = $transfer['to_transaction_id'];

            if (! isset($transactionTypes[$fromId])
                || ! isset($transactionTypes[$toId])) {
                $this->invalidBackup(
                    '振替が存在しない取引を参照しています。'
                );
            }

            if ($transactionTypes[$fromId]
                    !== TransactionType::TRANSFER->value
                || $transactionTypes[$toId]
                    !== TransactionType::TRANSFER->value) {
                $this->invalidBackup(
                    '振替データの取引種別が正しくありません。'
                );
            }
        }

        foreach ($data['transaction_rules'] as $rule) {
            if (! isset($accountIds[$rule['account_id']])) {
                $this->invalidBackup(
                    '取引補助設定が存在しない口座を参照しています。'
                );
            }

            if ($rule['category_id'] !== null
                && ! isset(
                    $categoryIds[$rule['category_id']]
                )) {
                $this->invalidBackup(
                    '取引補助設定が存在しないカテゴリを参照しています。'
                );
            }
        }
    }

    private function deleteCurrentData(User $user): void
    {
        $transactionIds = $user->transactions()
            ->pluck('id');

        if ($transactionIds->isNotEmpty()) {
            Transfer::query()
                ->whereIn(
                    'from_transaction_id',
                    $transactionIds
                )
                ->orWhereIn(
                    'to_transaction_id',
                    $transactionIds
                )
                ->delete();
        }

        $user->transactionRules()->delete();
        $user->transactions()->delete();
        $user->accounts()->delete();
        $user->categories()->delete();
    }

    /**
     * @param array<int, mixed> $accounts
     * @return array<int, int>
     */
    private function restoreAccounts(
        User $user,
        array $accounts
    ): array {
        $idMap = [];

        foreach ($accounts as $backupAccount) {
            $account = Account::create([
                'user_id' => $user->id,
                'name' => $backupAccount['name'],
                'type' => $backupAccount['type'],
                'sort_order' => $backupAccount['sort_order'],
            ]);

            $idMap[$backupAccount['id']] = $account->id;
        }

        return $idMap;
    }

    /**
     * @param array<int, mixed> $categories
     * @return array<int, int>
     */
    private function restoreCategories(
        User $user,
        array $categories
    ): array {
        $idMap = [];

        foreach ($categories as $backupCategory) {
            $category = Category::create([
                'user_id' => $user->id,
                'name' => $backupCategory['name'],
                'sort_order' => $backupCategory['sort_order'],
            ]);

            $idMap[$backupCategory['id']] = $category->id;
        }

        return $idMap;
    }

    /**
     * @param array<int, mixed> $transactions
     * @param array<int, int> $accountIdMap
     * @param array<int, int> $categoryIdMap
     * @return array<int, int>
     */
    private function restoreTransactions(
        User $user,
        array $transactions,
        array $accountIdMap,
        array $categoryIdMap
    ): array {
        $idMap = [];

        foreach ($transactions as $backupTransaction) {
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'transaction_date'
                    => $backupTransaction['transaction_date'],
                'type' => $backupTransaction['type'],
                'account_id'
                    => $backupTransaction['account_id'] === null
                        ? null
                        : $accountIdMap[
                            $backupTransaction['account_id']
                        ],
                'category_id'
                    => $backupTransaction['category_id'] === null
                        ? null
                        : $categoryIdMap[
                            $backupTransaction['category_id']
                        ],
                'counterparty_name'
                    => $backupTransaction['counterparty_name'],
                'amount' => $backupTransaction['amount'],
                'withdrawal_date'
                    => $backupTransaction['withdrawal_date'],
                'expense_ratio'
                    => $backupTransaction['expense_ratio'],
                'expense_registered'
                    => $backupTransaction['expense_registered'],
                'receipt_saved'
                    => $backupTransaction['receipt_saved'],
            ]);

            $idMap[$backupTransaction['id']]
                = $transaction->id;
        }

        return $idMap;
    }

    /**
     * @param array<int, mixed> $transfers
     * @param array<int, int> $transactionIdMap
     */
    private function restoreTransfers(
        array $transfers,
        array $transactionIdMap
    ): void {
        foreach ($transfers as $backupTransfer) {
            Transfer::create([
                'from_transaction_id'
                    => $transactionIdMap[
                        $backupTransfer['from_transaction_id']
                    ],
                'to_transaction_id'
                    => $transactionIdMap[
                        $backupTransfer['to_transaction_id']
                    ],
            ]);
        }
    }

    /**
     * @param array<int, mixed> $rules
     * @param array<int, int> $accountIdMap
     * @param array<int, int> $categoryIdMap
     */
    private function restoreTransactionRules(
        User $user,
        array $rules,
        array $accountIdMap,
        array $categoryIdMap
    ): void {
        foreach ($rules as $backupRule) {
            TransactionRule::create([
                'user_id' => $user->id,
                'account_id'
                    => $accountIdMap[$backupRule['account_id']],
                'keyword' => $backupRule['keyword'],
                'display_name' => $backupRule['display_name'],
                'category_id'
                    => $backupRule['category_id'] === null
                        ? null
                        : $categoryIdMap[
                            $backupRule['category_id']
                        ],
            ]);
        }
    }

    private function isPositiveInteger(mixed $value): bool
    {
        return is_int($value) && $value > 0;
    }

    private function isNonNegativeInteger(mixed $value): bool
    {
        return is_int($value) && $value >= 0;
    }

    private function isNullablePositiveInteger(
        mixed $value
    ): bool {
        return $value === null
            || $this->isPositiveInteger($value);
    }

    private function isNullableString(
        mixed $value,
        int $maxLength
    ): bool {
        return $value === null
            || (
                is_string($value)
                && mb_strlen($value) <= $maxLength
            );
    }

    private function isDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $value
        );

        return $date !== false
            && $date->format('Y-m-d') === $value;
    }

    private function isNullableDate(mixed $value): bool
    {
        return $value === null || $this->isDate($value);
    }

    private function isExpenseRatio(mixed $value): bool
    {
        if (! is_int($value)
            && ! is_float($value)
            && ! is_string($value)) {
            return false;
        }

        if (! is_numeric($value)) {
            return false;
        }

        $ratio = (float) $value;

        return $ratio >= 0 && $ratio <= 100;
    }

    private function invalidBackup(
        string $message = 'バックアップファイルの形式が正しくありません。'
    ): never {
        throw ValidationException::withMessages([
            'backup_file' => $message,
        ]);
    }
}
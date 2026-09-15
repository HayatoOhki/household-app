<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\TransactionRule;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_backup_page(): void
    {
        $response = $this->get(
            route('backup.index')
        );

        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_backup_page(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('backup.index'));

        $response->assertOk();
        $response->assertSee('バックアップ・復元');
        $response->assertSee(
            'バックアップをダウンロード'
        );
        $response->assertSee(
            'バックアップから復元'
        );
    }

    public function test_backup_download_contains_only_current_users_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の銀行',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $category = Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
            'name' => '食費',
            'sort_order' => 10,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-09-14',
            'type' => TransactionType::EXPENSE,
            'account_id' => $account->id,
            'category_id' => $category->id,
            'counterparty_name' => 'スーパー',
            'amount' => 1500,
            'withdrawal_date' => null,
            'expense_ratio' => 50,
            'expense_registered' => true,
            'receipt_saved' => false,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('backup.download'));

        $response->assertOk();

        $json = json_decode(
            $response->getContent(),
            true
        );

        $this->assertSame(
            'household-app-backup',
            $json['format']
        );

        $this->assertSame(
            1,
            $json['version']
        );

        $this->assertCount(
            1,
            $json['data']['accounts']
        );

        $this->assertSame(
            '三井住友銀行',
            $json['data']['accounts'][0]['name']
        );

        $this->assertCount(
            1,
            $json['data']['categories']
        );

        $this->assertCount(
            1,
            $json['data']['transactions']
        );

        $this->assertStringNotContainsString(
            '他人の銀行',
            $response->getContent()
        );

        $this->assertStringNotContainsString(
            $user->email,
            $response->getContent()
        );

        $this->assertStringNotContainsString(
            'password',
            $response->getContent()
        );
    }

    public function test_backup_restore_replaces_current_users_data(): void
    {
        $user = User::factory()->create();

        $oldAccount = Account::create([
            'user_id' => $user->id,
            'name' => '古い口座',
            'type' => AccountType::CASH,
            'sort_order' => 10,
        ]);

        Transaction::create([
            'user_id' => $user->id,
            'transaction_date' => '2026-01-01',
            'type' => TransactionType::INCOME,
            'account_id' => $oldAccount->id,
            'category_id' => null,
            'counterparty_name' => '古いデータ',
            'amount' => 100,
            'withdrawal_date' => null,
            'expense_ratio' => 0,
            'expense_registered' => false,
            'receipt_saved' => false,
        ]);

        $backup = $this->makeBackup();

        $response = $this
            ->actingAs($user)
            ->post(
                route('backup.restore'),
                [
                    'backup_file'
                        => $this->backupFile($backup),
                    'confirm_restore' => '1',
                ]
            );

        $response->assertRedirect(
            route('backup.index')
        );

        $response->assertSessionHas(
            'success',
            'バックアップからデータを復元しました。'
        );

        $this->assertDatabaseMissing(
            'accounts',
            [
                'user_id' => $user->id,
                'name' => '古い口座',
            ]
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'user_id' => $user->id,
                'name' => '楽天カード',
                'type' => AccountType::CREDIT_CARD->value,
                'sort_order' => 20,
            ]
        );

        $this->assertDatabaseHas(
            'categories',
            [
                'user_id' => $user->id,
                'name' => '食費',
                'sort_order' => 10,
            ]
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'user_id' => $user->id,
                'counterparty_name' => 'スーパー',
                'amount' => 3000,
            ]
        );

        $this->assertDatabaseHas(
            'transaction_rules',
            [
                'user_id' => $user->id,
                'keyword' => 'SUPERMARKET',
                'display_name' => 'スーパー',
            ]
        );
    }

    public function test_restore_remaps_account_category_and_transaction_ids(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '既存口座',
            'type' => AccountType::CASH,
            'sort_order' => 10,
        ]);

        Category::create([
            'user_id' => $user->id,
            'type' => CategoryType::EXPENSE,
            'name' => '既存カテゴリ',
            'sort_order' => 10,
        ]);

        $backup = $this->makeBackup();

        $this
            ->actingAs($user)
            ->post(
                route('backup.restore'),
                [
                    'backup_file'
                        => $this->backupFile($backup),
                    'confirm_restore' => '1',
                ]
            )
            ->assertRedirect(route('backup.index'));

        $restoredAccount = Account::query()
            ->where('user_id', $user->id)
            ->where('name', '楽天カード')
            ->firstOrFail();

        $restoredCategory = Category::query()
            ->where('user_id', $user->id)
            ->where('name', '食費')
            ->firstOrFail();

        $transaction = Transaction::query()
            ->where('user_id', $user->id)
            ->where('counterparty_name', 'スーパー')
            ->firstOrFail();

        $this->assertSame(
            $restoredAccount->id,
            $transaction->account_id
        );

        $this->assertSame(
            $restoredCategory->id,
            $transaction->category_id
        );

        $rule = TransactionRule::query()
            ->where('user_id', $user->id)
            ->firstOrFail();

        $this->assertSame(
            $restoredAccount->id,
            $rule->account_id
        );

        $this->assertSame(
            $restoredCategory->id,
            $rule->category_id
        );
    }

    public function test_restore_rebuilds_transfer_relationship(): void
    {
        $user = User::factory()->create();

        $backup = $this->makeBackup();

        $this
            ->actingAs($user)
            ->post(
                route('backup.restore'),
                [
                    'backup_file'
                        => $this->backupFile($backup),
                    'confirm_restore' => '1',
                ]
            )
            ->assertRedirect(route('backup.index'));

        $transfer = Transfer::query()
            ->firstOrFail();

        $fromTransaction = Transaction::findOrFail(
            $transfer->from_transaction_id
        );

        $toTransaction = Transaction::findOrFail(
            $transfer->to_transaction_id
        );

        $this->assertSame(
            $user->id,
            $fromTransaction->user_id
        );

        $this->assertSame(
            $user->id,
            $toTransaction->user_id
        );

        $this->assertSame(
            TransactionType::TRANSFER,
            $fromTransaction->type
        );

        $this->assertSame(
            TransactionType::TRANSFER,
            $toTransaction->type
        );
    }

    public function test_restore_does_not_change_other_users_data(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Account::create([
            'user_id' => $otherUser->id,
            'name' => '他人の口座',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $backup = $this->makeBackup();

        $this
            ->actingAs($user)
            ->post(
                route('backup.restore'),
                [
                    'backup_file'
                        => $this->backupFile($backup),
                    'confirm_restore' => '1',
                ]
            );

        $this->assertDatabaseHas(
            'accounts',
            [
                'user_id' => $otherUser->id,
                'name' => '他人の口座',
            ]
        );
    }

    public function test_restore_rejects_invalid_json_without_deleting_current_data(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '残す口座',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $file = UploadedFile::fake()
            ->createWithContent(
                'invalid.json',
                '{invalid json'
            );

        $response = $this
            ->actingAs($user)
            ->post(
                route('backup.restore'),
                [
                    'backup_file' => $file,
                    'confirm_restore' => '1',
                ]
            );

        $response->assertSessionHasErrors(
            'backup_file'
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'user_id' => $user->id,
                'name' => '残す口座',
            ]
        );
    }

    public function test_restore_rejects_wrong_backup_format_without_deleting_current_data(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '残す口座',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $backup = $this->makeBackup();
        $backup['format'] = 'wrong-format';

        $response = $this
            ->actingAs($user)
            ->post(
                route('backup.restore'),
                [
                    'backup_file'
                        => $this->backupFile($backup),
                    'confirm_restore' => '1',
                ]
            );

        $response->assertSessionHasErrors(
            'backup_file'
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'user_id' => $user->id,
                'name' => '残す口座',
            ]
        );
    }

    public function test_restore_requires_confirmation(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '残す口座',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $backup = $this->makeBackup();

        $response = $this
            ->actingAs($user)
            ->post(
                route('backup.restore'),
                [
                    'backup_file'
                        => $this->backupFile($backup),
                ]
            );

        $response->assertSessionHasErrors(
            'confirm_restore'
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'user_id' => $user->id,
                'name' => '残す口座',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function makeBackup(): array
    {
        return [
            'format' => 'household-app-backup',
            'version' => 1,
            'exported_at' => '2026-09-14T22:00:00+09:00',

            'data' => [
                'accounts' => [
                    [
                        'id' => 100,
                        'name' => '現金',
                        'type' => 'cash',
                        'sort_order' => 10,
                    ],
                    [
                        'id' => 200,
                        'name' => '楽天カード',
                        'type' => 'credit_card',
                        'sort_order' => 20,
                    ],
                ],

                'categories' => [
                    [
                        'id' => 300,
                        'type' => CategoryType::EXPENSE->value,
                        'name' => '食費',
                        'sort_order' => 10,
                    ],
                ],

                'transactions' => [
                    [
                        'id' => 400,
                        'transaction_date' => '2026-09-01',
                        'type' => 'expense',
                        'account_id' => 200,
                        'category_id' => 300,
                        'counterparty_name' => 'スーパー',
                        'amount' => 3000,
                        'withdrawal_date' => '2026-10-27',
                        'expense_ratio' => '50.00',
                        'expense_registered' => true,
                        'receipt_saved' => true,
                    ],
                    [
                        'id' => 500,
                        'transaction_date' => '2026-09-02',
                        'type' => 'transfer',
                        'account_id' => 100,
                        'category_id' => null,
                        'counterparty_name' => null,
                        'amount' => 10000,
                        'withdrawal_date' => null,
                        'expense_ratio' => '0.00',
                        'expense_registered' => false,
                        'receipt_saved' => false,
                    ],
                    [
                        'id' => 600,
                        'transaction_date' => '2026-09-02',
                        'type' => 'transfer',
                        'account_id' => 200,
                        'category_id' => null,
                        'counterparty_name' => null,
                        'amount' => 10000,
                        'withdrawal_date' => null,
                        'expense_ratio' => '0.00',
                        'expense_registered' => false,
                        'receipt_saved' => false,
                    ],
                ],

                'transfers' => [
                    [
                        'id' => 700,
                        'from_transaction_id' => 500,
                        'to_transaction_id' => 600,
                    ],
                ],

                'transaction_rules' => [
                    [
                        'id' => 800,
                        'account_id' => 200,
                        'keyword' => 'SUPERMARKET',
                        'display_name' => 'スーパー',
                        'category_id' => 300,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $backup
     */
    private function backupFile(
        array $backup
    ): UploadedFile {
        return UploadedFile::fake()
            ->createWithContent(
                'household-backup.json',
                json_encode(
                    $backup,
                    JSON_UNESCAPED_UNICODE
                    | JSON_THROW_ON_ERROR
                )
            );
    }
}
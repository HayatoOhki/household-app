<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_transfer_create_page(): void
    {
        $response = $this->get(
            route('transfers.create')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_authenticated_user_can_open_transfer_create_page(): void
    {
        $user = User::factory()->create();

        $this->createAccount(
            $user,
            '現金'
        );

        $this->createAccount(
            $user,
            '銀行'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('transfers.create')
            );

        $response->assertOk();

        $response->assertSee('振替登録');
        $response->assertSee('現金');
        $response->assertSee('銀行');
    }

    public function test_user_can_create_transfer(): void
    {
        $user = User::factory()->create();

        $fromAccount = $this->createAccount(
            $user,
            '銀行'
        );

        $toAccount = $this->createAccount(
            $user,
            '現金'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('transfers.store'),
                [
                    'transaction_date' => '2026-09-11',
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'amount' => 10000,
                ]
            );

        $response->assertRedirect(
            route('transactions.index')
        );

        $response->assertSessionHas(
            'success',
            '振替を登録しました。'
        );

        $this->assertDatabaseCount(
            'transactions',
            2
        );

        $this->assertDatabaseCount(
            'transfers',
            1
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'user_id' => $user->id,
                'transaction_date' => '2026-09-11 00:00:00',
                'type' => 'transfer',
                'account_id' => $fromAccount->id,
                'amount' => 10000,
            ]
        );

        $this->assertDatabaseHas(
            'transactions',
            [
                'user_id' => $user->id,
                'transaction_date' => '2026-09-11 00:00:00',
                'type' => 'transfer',
                'account_id' => $toAccount->id,
                'amount' => 10000,
            ]
        );

        $fromTransaction = Transaction::query()
            ->where('user_id', $user->id)
            ->where('account_id', $fromAccount->id)
            ->where('type', 'transfer')
            ->firstOrFail();

        $toTransaction = Transaction::query()
            ->where('user_id', $user->id)
            ->where('account_id', $toAccount->id)
            ->where('type', 'transfer')
            ->firstOrFail();

        $this->assertDatabaseHas(
            'transfers',
            [
                'from_transaction_id' => $fromTransaction->id,
                'to_transaction_id' => $toTransaction->id,
            ]
        );
    }

    public function test_user_cannot_create_transfer_with_same_account(): void
    {
        $user = User::factory()->create();

        $account = $this->createAccount(
            $user,
            '銀行'
        );

        $response = $this
            ->actingAs($user)
            ->from(
                route('transfers.create')
            )
            ->post(
                route('transfers.store'),
                [
                    'transaction_date' => '2026-09-11',
                    'from_account_id' => $account->id,
                    'to_account_id' => $account->id,
                    'amount' => 10000,
                ]
            );

        $response->assertRedirect(
            route('transfers.create')
        );

        $response->assertSessionHasErrors([
            'to_account_id',
        ]);

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertDatabaseCount(
            'transfers',
            0
        );
    }

    public function test_user_cannot_use_other_users_from_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $otherAccount = $this->createAccount(
            $otherUser,
            '他人の銀行'
        );

        $toAccount = $this->createAccount(
            $user,
            '現金'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('transfers.store'),
                [
                    'transaction_date' => '2026-09-11',
                    'from_account_id' => $otherAccount->id,
                    'to_account_id' => $toAccount->id,
                    'amount' => 10000,
                ]
            );

        $response->assertSessionHasErrors([
            'from_account_id',
        ]);

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertDatabaseCount(
            'transfers',
            0
        );
    }

    public function test_user_cannot_use_other_users_to_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $fromAccount = $this->createAccount(
            $user,
            '銀行'
        );

        $otherAccount = $this->createAccount(
            $otherUser,
            '他人の現金'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('transfers.store'),
                [
                    'transaction_date' => '2026-09-11',
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $otherAccount->id,
                    'amount' => 10000,
                ]
            );

        $response->assertSessionHasErrors([
            'to_account_id',
        ]);

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertDatabaseCount(
            'transfers',
            0
        );
    }

    public function test_transfer_amount_must_be_at_least_one_yen(): void
    {
        $user = User::factory()->create();

        $fromAccount = $this->createAccount(
            $user,
            '銀行'
        );

        $toAccount = $this->createAccount(
            $user,
            '現金'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('transfers.store'),
                [
                    'transaction_date' => '2026-09-11',
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'amount' => 0,
                ]
            );

        $response->assertSessionHasErrors([
            'amount',
        ]);

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertDatabaseCount(
            'transfers',
            0
        );
    }

    public function test_transfer_amount_must_be_integer(): void
    {
        $user = User::factory()->create();

        $fromAccount = $this->createAccount(
            $user,
            '銀行'
        );

        $toAccount = $this->createAccount(
            $user,
            '現金'
        );

        $response = $this
            ->actingAs($user)
            ->post(
                route('transfers.store'),
                [
                    'transaction_date' => '2026-09-11',
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'amount' => 100.5,
                ]
            );

        $response->assertSessionHasErrors([
            'amount',
        ]);

        $this->assertDatabaseCount(
            'transactions',
            0
        );

        $this->assertDatabaseCount(
            'transfers',
            0
        );
    }

    public function test_transfer_create_page_only_shows_users_accounts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->createAccount(
            $user,
            '自分の銀行'
        );

        $this->createAccount(
            $user,
            '自分の現金'
        );

        $this->createAccount(
            $otherUser,
            '他人の銀行'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('transfers.create')
            );

        $response->assertOk();

        $response->assertSee('自分の銀行');
        $response->assertSee('自分の現金');

        $response->assertDontSee(
            '他人の銀行'
        );
    }

    public function test_transfer_is_displayed_as_single_row_in_transaction_index(): void
    {
        $user = User::factory()->create();

        $fromAccount = $this->createAccount(
            $user,
            '三井住友銀行'
        );

        $toAccount = $this->createAccount(
            $user,
            '現金'
        );

        $this
            ->actingAs($user)
            ->post(
                route('transfers.store'),
                [
                    'transaction_date' => '2026-09-11',
                    'from_account_id' => $fromAccount->id,
                    'to_account_id' => $toAccount->id,
                    'amount' => 10000,
                ]
            );

        $response = $this
            ->actingAs($user)
            ->get(
                route('transactions.index')
            );

        $response->assertOk();

        $response->assertSee('振替');
        $response->assertSee('三井住友銀行');
        $response->assertSee('現金');
        $response->assertSee('10,000 円');

        $this->assertSame(
            1,
            substr_count(
                $response->getContent(),
                '10,000 円'
            )
        );
    }

    private function createAccount(
        User $user,
        string $name
    ): Account {
        return Account::query()->create([
            'user_id' => $user->id,
            'name' => $name,
        ]);
    }
}
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\AccountType;
use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_account_index(): void
    {
        $response = $this->get(
            route('accounts.index')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_user_can_view_only_their_own_accounts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
            'sort_order' => 10,
        ]);

        Account::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザー口座',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('accounts.index')
            );

        $response->assertOk();
        $response->assertSee('現金');
        $response->assertDontSee(
            '他ユーザー口座'
        );
    }

    public function test_accounts_are_displayed_in_sort_order(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '楽天銀行',
            'type' => AccountType::BANK,
            'sort_order' => 30,
        ]);

        Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
            'sort_order' => 10,
        ]);

        Account::create([
            'user_id' => $user->id,
            'name' => '三井住友銀行',
            'type' => AccountType::BANK,
            'sort_order' => 20,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('accounts.index')
            );

        $response->assertSeeInOrder([
            '現金',
            '三井住友銀行',
            '楽天銀行',
        ]);
    }

    public function test_user_can_create_account_with_bulk_update(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(
                route('accounts.bulk-update'),
                [
                    'accounts' => [
                        [
                            'id' => '',
                            'name' => '三井住友銀行',
                            'type' =>
                                AccountType::BANK->value,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('accounts.index')
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'user_id' => $user->id,
                'name' => '三井住友銀行',
                'type' =>
                    AccountType::BANK->value,
                'sort_order' => 10,
            ]
        );
    }

    public function test_account_name_is_required_in_bulk_update(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(
                route('accounts.bulk-update'),
                [
                    'accounts' => [
                        [
                            'id' => '',
                            'name' => '',
                            'type' =>
                                AccountType::BANK->value,
                        ],
                    ],
                ]
            );

        $response->assertSessionHasErrors(
            'accounts.0.name'
        );
    }

    public function test_same_user_cannot_save_duplicate_account_names(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(
                route('accounts.bulk-update'),
                [
                    'accounts' => [
                        [
                            'id' => '',
                            'name' => '現金',
                            'type' =>
                                AccountType::CASH->value,
                        ],
                        [
                            'id' => '',
                            'name' => '現金',
                            'type' =>
                                AccountType::BANK->value,
                        ],
                    ],
                ]
            );

        $response->assertSessionHasErrors(
            'accounts.1.name'
        );

        $this->assertDatabaseCount(
            'accounts',
            0
        );
    }

    public function test_different_users_can_use_same_account_name(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Account::create([
            'user_id' => $otherUser->id,
            'name' => '現金',
            'type' => AccountType::CASH,
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('accounts.bulk-update'),
                [
                    'accounts' => [
                        [
                            'id' => '',
                            'name' => '現金',
                            'type' =>
                                AccountType::BANK->value,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('accounts.index')
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'user_id' => $user->id,
                'name' => '現金',
            ]
        );
    }

    public function test_user_can_update_and_reorder_accounts(): void
    {
        $user = User::factory()->create();

        $cash = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
            'sort_order' => 10,
        ]);

        $bank = Account::create([
            'user_id' => $user->id,
            'name' => '銀行',
            'type' => AccountType::BANK,
            'sort_order' => 20,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('accounts.bulk-update'),
                [
                    'accounts' => [
                        [
                            'id' => $bank->id,
                            'name' => '三井住友銀行',
                            'type' =>
                                AccountType::BANK->value,
                        ],
                        [
                            'id' => $cash->id,
                            'name' => '財布',
                            'type' =>
                                AccountType::CASH->value,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('accounts.index')
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'id' => $bank->id,
                'name' => '三井住友銀行',
                'sort_order' => 10,
            ]
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'id' => $cash->id,
                'name' => '財布',
                'sort_order' => 20,
            ]
        );
    }

    public function test_user_can_swap_account_names(): void
    {
        $user = User::factory()->create();

        $first = Account::create([
            'user_id' => $user->id,
            'name' => '口座A',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $second = Account::create([
            'user_id' => $user->id,
            'name' => '口座B',
            'type' => AccountType::BANK,
            'sort_order' => 20,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('accounts.bulk-update'),
                [
                    'accounts' => [
                        [
                            'id' => $first->id,
                            'name' => '口座B',
                            'type' =>
                                AccountType::BANK->value,
                        ],
                        [
                            'id' => $second->id,
                            'name' => '口座A',
                            'type' =>
                                AccountType::BANK->value,
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('accounts.index')
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'id' => $first->id,
                'name' => '口座B',
            ]
        );

        $this->assertDatabaseHas(
            'accounts',
            [
                'id' => $second->id,
                'name' => '口座A',
            ]
        );
    }

    public function test_user_cannot_bulk_update_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザー口座',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('accounts.bulk-update'),
                [
                    'accounts' => [
                        [
                            'id' => $account->id,
                            'name' => '変更後',
                            'type' =>
                                AccountType::BANK->value,
                        ],
                    ],
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'accounts',
            [
                'id' => $account->id,
                'name' => '他ユーザー口座',
            ]
        );
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
            'type' => AccountType::CASH,
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'accounts.destroy',
                    $account
                )
            );

        $response->assertRedirect(
            route('accounts.index')
        );

        $this->assertDatabaseMissing(
            'accounts',
            [
                'id' => $account->id,
            ]
        );
    }

    public function test_user_cannot_delete_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザー口座',
            'type' => AccountType::BANK,
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'accounts.destroy',
                    $account
                )
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'accounts',
            [
                'id' => $account->id,
            ]
        );
    }
}
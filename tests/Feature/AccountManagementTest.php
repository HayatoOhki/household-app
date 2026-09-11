<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_account_index(): void
    {
        $response = $this->get(route('accounts.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_only_their_own_accounts(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        Account::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザー口座',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('accounts.index'));

        $response->assertOk();
        $response->assertSee('現金');
        $response->assertDontSee('他ユーザー口座');
    }

    public function test_user_can_create_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => '三井住友銀行',
            ]);

        $response->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => '三井住友銀行',
        ]);
    }

    public function test_account_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_same_user_cannot_create_duplicate_account_name(): void
    {
        $user = User::factory()->create();

        Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => '現金',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertSame(
            1,
            Account::where('user_id', $user->id)
                ->where('name', '現金')
                ->count()
        );
    }

    public function test_different_users_can_use_same_account_name(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Account::create([
            'user_id' => $otherUser->id,
            'name' => '現金',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('accounts.store'), [
                'name' => '現金',
            ]);

        $response->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'user_id' => $user->id,
            'name' => '現金',
        ]);
    }

    public function test_user_can_update_their_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('accounts.update', $account), [
                'name' => '財布',
            ]);

        $response->assertRedirect(route('accounts.index'));

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'user_id' => $user->id,
            'name' => '財布',
        ]);
    }

    public function test_user_cannot_edit_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザー口座',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('accounts.edit', $account));

        $response->assertNotFound();
    }

    public function test_user_cannot_update_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザー口座',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('accounts.update', $account), [
                'name' => '変更後',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => '他ユーザー口座',
        ]);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $account = Account::create([
            'user_id' => $user->id,
            'name' => '現金',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('accounts.destroy', $account));

        $response->assertRedirect(route('accounts.index'));

        $this->assertDatabaseMissing('accounts', [
            'id' => $account->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_account(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $account = Account::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザー口座',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('accounts.destroy', $account));

        $response->assertNotFound();

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
        ]);
    }
}
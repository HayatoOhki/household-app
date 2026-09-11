<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_category_index(): void
    {
        $response = $this->get(route('categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_user_can_view_only_their_own_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        Category::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザーカテゴリ',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('categories.index'));

        $response->assertOk();
        $response->assertSee('食費');
        $response->assertDontSee('他ユーザーカテゴリ');
    }

    public function test_user_can_create_category(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('categories.store'), [
                'name' => '食費',
            ]);

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => '食費',
        ]);
    }

    public function test_category_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('categories.store'), [
                'name' => '',
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_same_user_cannot_create_duplicate_category_name(): void
    {
        $user = User::factory()->create();

        Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('categories.store'), [
                'name' => '食費',
            ]);

        $response->assertSessionHasErrors('name');

        $this->assertSame(
            1,
            Category::where('user_id', $user->id)
                ->where('name', '食費')
                ->count()
        );
    }

    public function test_different_users_can_use_same_category_name(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Category::create([
            'user_id' => $otherUser->id,
            'name' => '食費',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('categories.store'), [
                'name' => '食費',
            ]);

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'user_id' => $user->id,
            'name' => '食費',
        ]);
    }

    public function test_user_can_update_their_category(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('categories.update', $category), [
                'name' => '食料品',
            ]);

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'user_id' => $user->id,
            'name' => '食料品',
        ]);
    }

    public function test_user_cannot_edit_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $category = Category::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザーカテゴリ',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('categories.edit', $category));

        $response->assertNotFound();
    }

    public function test_user_cannot_update_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $category = Category::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザーカテゴリ',
        ]);

        $response = $this
            ->actingAs($user)
            ->put(route('categories.update', $category), [
                'name' => '変更後',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => '他ユーザーカテゴリ',
        ]);
    }

    public function test_user_can_delete_their_category(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('categories.destroy', $category));

        $response->assertRedirect(route('categories.index'));

        $this->assertDatabaseMissing('categories', [
            'id' => $category->id,
        ]);
    }

    public function test_user_cannot_delete_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $category = Category::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザーカテゴリ',
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('categories.destroy', $category));

        $response->assertNotFound();

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
        ]);
    }
}
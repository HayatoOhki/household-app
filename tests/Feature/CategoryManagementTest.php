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
        $response = $this->get(
            route('categories.index')
        );

        $response->assertRedirect(
            route('login')
        );
    }

    public function test_user_can_view_only_their_own_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'sort_order' => 10,
        ]);

        Category::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザーカテゴリ',
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('categories.index')
            );

        $response->assertOk();
        $response->assertSee('食費');
        $response->assertDontSee(
            '他ユーザーカテゴリ'
        );
    }

    public function test_categories_are_displayed_in_sort_order(): void
    {
        $user = User::factory()->create();

        Category::create([
            'user_id' => $user->id,
            'name' => '家賃',
            'sort_order' => 30,
        ]);

        Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'sort_order' => 10,
        ]);

        Category::create([
            'user_id' => $user->id,
            'name' => '日用品',
            'sort_order' => 20,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(
                route('categories.index')
            );

        $response->assertSeeInOrder([
            '食費',
            '日用品',
            '家賃',
        ]);
    }

    public function test_user_can_create_category_with_bulk_update(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(
                route('categories.bulk-update'),
                [
                    'categories' => [
                        [
                            'id' => '',
                            'name' => '食費',
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('categories.index')
        );

        $this->assertDatabaseHas(
            'categories',
            [
                'user_id' => $user->id,
                'name' => '食費',
                'sort_order' => 10,
            ]
        );
    }

    public function test_category_name_is_required_in_bulk_update(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(
                route('categories.bulk-update'),
                [
                    'categories' => [
                        [
                            'id' => '',
                            'name' => '',
                        ],
                    ],
                ]
            );

        $response->assertSessionHasErrors(
            'categories.0.name'
        );
    }

    public function test_same_user_cannot_save_duplicate_category_names(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(
                route('categories.bulk-update'),
                [
                    'categories' => [
                        [
                            'id' => '',
                            'name' => '食費',
                        ],
                        [
                            'id' => '',
                            'name' => '食費',
                        ],
                    ],
                ]
            );

        $response->assertSessionHasErrors(
            'categories.1.name'
        );

        $this->assertDatabaseCount(
            'categories',
            0
        );
    }

    public function test_different_users_can_use_same_category_name(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Category::create([
            'user_id' => $otherUser->id,
            'name' => '食費',
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('categories.bulk-update'),
                [
                    'categories' => [
                        [
                            'id' => '',
                            'name' => '食費',
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('categories.index')
        );

        $this->assertDatabaseHas(
            'categories',
            [
                'user_id' => $user->id,
                'name' => '食費',
            ]
        );
    }

    public function test_user_can_update_and_reorder_categories(): void
    {
        $user = User::factory()->create();

        $food = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'sort_order' => 10,
        ]);

        $daily = Category::create([
            'user_id' => $user->id,
            'name' => '日用品',
            'sort_order' => 20,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('categories.bulk-update'),
                [
                    'categories' => [
                        [
                            'id' => $daily->id,
                            'name' => '生活用品',
                        ],
                        [
                            'id' => $food->id,
                            'name' => '食料品',
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('categories.index')
        );

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $daily->id,
                'name' => '生活用品',
                'sort_order' => 10,
            ]
        );

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $food->id,
                'name' => '食料品',
                'sort_order' => 20,
            ]
        );
    }

    public function test_user_can_swap_category_names(): void
    {
        $user = User::factory()->create();

        $first = Category::create([
            'user_id' => $user->id,
            'name' => 'カテゴリA',
            'sort_order' => 10,
        ]);

        $second = Category::create([
            'user_id' => $user->id,
            'name' => 'カテゴリB',
            'sort_order' => 20,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('categories.bulk-update'),
                [
                    'categories' => [
                        [
                            'id' => $first->id,
                            'name' => 'カテゴリB',
                        ],
                        [
                            'id' => $second->id,
                            'name' => 'カテゴリA',
                        ],
                    ],
                ]
            );

        $response->assertRedirect(
            route('categories.index')
        );

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $first->id,
                'name' => 'カテゴリB',
            ]
        );

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $second->id,
                'name' => 'カテゴリA',
            ]
        );
    }

    public function test_user_cannot_bulk_update_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $category = Category::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザーカテゴリ',
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->put(
                route('categories.bulk-update'),
                [
                    'categories' => [
                        [
                            'id' => $category->id,
                            'name' => '変更後',
                        ],
                    ],
                ]
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $category->id,
                'name' => '他ユーザーカテゴリ',
            ]
        );
    }

    public function test_user_can_delete_their_category(): void
    {
        $user = User::factory()->create();

        $category = Category::create([
            'user_id' => $user->id,
            'name' => '食費',
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'categories.destroy',
                    $category
                )
            );

        $response->assertRedirect(
            route('categories.index')
        );

        $this->assertDatabaseMissing(
            'categories',
            [
                'id' => $category->id,
            ]
        );
    }

    public function test_user_cannot_delete_another_users_category(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $category = Category::create([
            'user_id' => $otherUser->id,
            'name' => '他ユーザーカテゴリ',
            'sort_order' => 10,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(
                route(
                    'categories.destroy',
                    $category
                )
            );

        $response->assertNotFound();

        $this->assertDatabaseHas(
            'categories',
            [
                'id' => $category->id,
            ]
        );
    }
}
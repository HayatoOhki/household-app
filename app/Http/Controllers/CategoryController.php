<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = $request->user()
            ->categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view(
            'categories.index',
            compact('categories')
        );
    }

    public function bulkUpdate(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'categories' => [
                'nullable',
                'array',
            ],
            'categories.*.id' => [
                'nullable',
                'integer',
                'distinct',
            ],
            'categories.*.name' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],
        ]);

        $rows = collect(
            $validated['categories'] ?? []
        );

        $categoryIds = $rows
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $categories = $request->user()
            ->categories()
            ->whereIn('id', $categoryIds)
            ->get()
            ->keyBy('id');

        if (
            $categories->count()
            !== $categoryIds->count()
        ) {
            abort(404);
        }

        $names = $rows
            ->pluck('name')
            ->values();

        $hasConflict = $request->user()
            ->categories()
            ->when(
                $categoryIds->isNotEmpty(),
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $categoryIds
                    )
            )
            ->whereIn('name', $names)
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'categories' =>
                    '同じ名前のカテゴリが既に登録されています。',
            ]);
        }

        DB::transaction(
            function () use (
                $request,
                $rows,
                $categories
            ): void {
                foreach ($categories as $category) {
                    $category->update([
                        'name' =>
                            '__tmp_category_'
                            . $category->id
                            . '_'
                            . Str::uuid(),
                    ]);
                }

                foreach (
                    $rows->values() as $index => $row
                ) {
                    $sortOrder = ($index + 1) * 10;

                    if (! empty($row['id'])) {
                        $category = $categories->get(
                            (int) $row['id']
                        );

                        $category->update([
                            'name' => $row['name'],
                            'sort_order' => $sortOrder,
                        ]);

                        continue;
                    }

                    $request->user()
                        ->categories()
                        ->create([
                            'name' => $row['name'],
                            'sort_order' => $sortOrder,
                        ]);
                }
            }
        );

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'カテゴリを保存しました。'
            );
    }

    public function destroy(
        Request $request,
        Category $category
    ): RedirectResponse {
        $this->ensureOwnedByUser(
            $request,
            $category
        );

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                'カテゴリを削除しました。'
            );
    }

    private function ensureOwnedByUser(
        Request $request,
        Category $category
    ): void {
        abort_unless(
            $category->user_id ===
                $request->user()->id,
            404
        );
    }
}
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\CategoryType;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = $request->user()->categories()
            ->orderByRaw("CASE type WHEN 'income' THEN 0 WHEN 'expense' THEN 1 WHEN 'transfer' THEN 2 ELSE 3 END")
            ->orderBy('sort_order')->orderBy('id')->get();

        return view('categories.index', compact('categories'));
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'categories' => ['nullable', 'array'],
            'categories.*.id' => ['nullable', 'integer', 'distinct'],
            'categories.*.type' => [
                'required',
                Rule::in([
                    CategoryType::INCOME->value,
                    CategoryType::EXPENSE->value,
                    CategoryType::TRANSFER->value,
                ]),
            ],
            'categories.*.name' => ['required', 'string', 'max:100'],
        ]);

        $rows = collect($validated['categories'] ?? []);
        $categoryIds = $rows->pluck('id')->filter()->map(fn ($id) => (int) $id)->values();
        $categories = $request->user()->categories()->whereIn('id', $categoryIds)->get()->keyBy('id');

        if ($categories->count() !== $categoryIds->count()) {
            abort(404);
        }

        $pairs = [];
        foreach ($rows as $index => $row) {
            $key = $row['type'] . "\0" . $row['name'];
            if (isset($pairs[$key])) {
                throw ValidationException::withMessages([
                    "categories.$index.name" => '同じ区分に同じ名前のカテゴリは登録できません。',
                ]);
            }
            $pairs[$key] = true;
        }

        foreach ($rows as $row) {
            $conflict = $request->user()->categories()
                ->when($categoryIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $categoryIds))
                ->where('type', $row['type'])
                ->where('name', $row['name'])
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages([
                    'categories' => '同じ区分に同じ名前のカテゴリが既に登録されています。',
                ]);
            }
        }

        DB::transaction(function () use ($request, $rows, $categories): void {
            foreach ($categories as $category) {
                $category->update(['name' => '__tmp_category_' . $category->id . '_' . Str::uuid()]);
            }

            $sortOrders = [
                CategoryType::INCOME->value => 0,
                CategoryType::EXPENSE->value => 0,
                CategoryType::TRANSFER->value => 0,
            ];
            foreach ($rows as $row) {
                $sortOrders[$row['type']] += 10;
                $values = [
                    'type' => $row['type'],
                    'name' => $row['name'],
                    'sort_order' => $sortOrders[$row['type']],
                ];
                if (! empty($row['id'])) {
                    $categories->get((int) $row['id'])->update($values);
                } else {
                    $request->user()->categories()->create($values);
                }
            }
        });

        return redirect()->route('categories.index')->with('success', 'カテゴリを保存しました。');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->ensureOwnedByUser($request, $category);
        $category->delete();
        return redirect()->route('categories.index')->with('success', 'カテゴリを削除しました。');
    }

    private function ensureOwnedByUser(Request $request, Category $category): void
    {
        abort_unless($category->user_id === $request->user()->id, 404);
    }
}

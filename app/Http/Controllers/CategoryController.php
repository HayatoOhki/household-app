<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $categories = $request->user()
            ->categories()
            ->orderBy('name')
            ->get();

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')
                    ->where('user_id', $request->user()->id),
            ],
        ]);

        $request->user()->categories()->create($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'カテゴリを登録しました。');
    }

    public function edit(Request $request, Category $category): View
    {
        $this->ensureOwnedByUser($request, $category);

        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->ensureOwnedByUser($request, $category);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('categories', 'name')
                    ->where('user_id', $request->user()->id)
                    ->ignore($category->id),
            ],
        ]);

        $category->update($validated);

        return redirect()
            ->route('categories.index')
            ->with('success', 'カテゴリを更新しました。');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->ensureOwnedByUser($request, $category);

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'カテゴリを削除しました。');
    }

    private function ensureOwnedByUser(Request $request, Category $category): void
    {
        abort_unless(
            $category->user_id === $request->user()->id,
            404
        );
    }
}
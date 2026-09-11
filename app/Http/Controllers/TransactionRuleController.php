<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TransactionRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionRuleController extends Controller
{
    public function index(Request $request): View
    {
        $transactionRules = $request->user()
            ->transactionRules()
            ->with([
                'account',
                'category',
            ])
            ->orderBy('account_id')
            ->orderBy('keyword')
            ->get();

        return view(
            'transaction-rules.index',
            compact('transactionRules')
        );
    }

    public function create(Request $request): View
    {
        $accounts = $request->user()
            ->accounts()
            ->orderBy('name')
            ->get();

        $categories = $request->user()
            ->categories()
            ->orderBy('name')
            ->get();

        return view(
            'transaction-rules.create',
            compact(
                'accounts',
                'categories'
            )
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
            'keyword' => [
                'required',
                'string',
                'max:255',
                Rule::unique('transaction_rules', 'keyword')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'user_id',
                                $request->user()->id
                            )
                            ->where(
                                'account_id',
                                $request->input('account_id')
                            )
                    ),
            ],
            'display_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
        ]);

        $request->user()
            ->transactionRules()
            ->create([
                'account_id' => $validated['account_id'],
                'keyword' => $validated['keyword'],
                'display_name' => $validated['display_name'] ?? null,
                'category_id' => $validated['category_id'] ?? null,
            ]);

        return redirect()
            ->route('transaction-rules.index')
            ->with('success', '取引ルールを登録しました。');
    }

    public function edit(
        Request $request,
        TransactionRule $transactionRule
    ): View {
        $this->ensureOwnedByUser(
            $request,
            $transactionRule
        );

        $accounts = $request->user()
            ->accounts()
            ->orderBy('name')
            ->get();

        $categories = $request->user()
            ->categories()
            ->orderBy('name')
            ->get();

        return view(
            'transaction-rules.edit',
            compact(
                'transactionRule',
                'accounts',
                'categories'
            )
        );
    }

    public function update(
        Request $request,
        TransactionRule $transactionRule
    ): RedirectResponse {
        $this->ensureOwnedByUser(
            $request,
            $transactionRule
        );

        $validated = $request->validate([
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
            'keyword' => [
                'required',
                'string',
                'max:255',
                Rule::unique('transaction_rules', 'keyword')
                    ->where(
                        fn ($query) => $query
                            ->where(
                                'user_id',
                                $request->user()->id
                            )
                            ->where(
                                'account_id',
                                $request->input('account_id')
                            )
                    )
                    ->ignore($transactionRule->id),
            ],
            'display_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'category_id' => [
                'nullable',
                Rule::exists('categories', 'id')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
        ]);

        $transactionRule->update([
            'account_id' => $validated['account_id'],
            'keyword' => $validated['keyword'],
            'display_name' => $validated['display_name'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return redirect()
            ->route('transaction-rules.index')
            ->with('success', '取引ルールを更新しました。');
    }

    public function destroy(
        Request $request,
        TransactionRule $transactionRule
    ): RedirectResponse {
        $this->ensureOwnedByUser(
            $request,
            $transactionRule
        );

        $transactionRule->delete();

        return redirect()
            ->route('transaction-rules.index')
            ->with('success', '取引ルールを削除しました。');
    }

    private function ensureOwnedByUser(
        Request $request,
        TransactionRule $transactionRule
    ): void {
        abort_unless(
            $transactionRule->user_id === $request->user()->id,
            404
        );
    }
}
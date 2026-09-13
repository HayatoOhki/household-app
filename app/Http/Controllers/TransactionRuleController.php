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
            ->orderBy('category_id')
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
        $validated = $this->validateTransactionRule($request);

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
            ->with('success', '入力テンプレートを登録しました。');
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

        $validated = $this->validateTransactionRule(
            $request,
            $transactionRule
        );

        $transactionRule->update([
            'account_id' => $validated['account_id'],
            'keyword' => $validated['keyword'],
            'display_name' => $validated['display_name'] ?? null,
            'category_id' => $validated['category_id'] ?? null,
        ]);

        return redirect()
            ->route('transaction-rules.index')
            ->with('success', '入力テンプレートを更新しました。');
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
            ->with('success', '入力テンプレートを削除しました。');
    }

    private function validateTransactionRule(
        Request $request,
        ?TransactionRule $transactionRule = null
    ): array {
        $uniqueKeyword = Rule::unique(
            'transaction_rules',
            'keyword'
        )->where(
            function ($query) use ($request) {
                $query
                    ->where(
                        'user_id',
                        $request->user()->id
                    )
                    ->where(
                        'account_id',
                        $request->input('account_id')
                    );

                if ($request->filled('category_id')) {
                    $query->where(
                        'category_id',
                        $request->input('category_id')
                    );
                } else {
                    $query->whereNull('category_id');
                }
            }
        );

        if ($transactionRule !== null) {
            $uniqueKeyword->ignore($transactionRule->id);
        }

        return $request->validate([
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
                $uniqueKeyword,
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
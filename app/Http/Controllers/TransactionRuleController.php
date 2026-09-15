<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\CategoryType;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransactionRuleController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = $request->user()
            ->accounts()
            ->whereNotIn('type', [
                AccountType::CASH->value,
                AccountType::LIABILITY->value,
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = $request->user()
            ->categories()
            ->whereIn('type', [
                CategoryType::INCOME->value,
                CategoryType::EXPENSE->value,
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $transactionRulesByAccount = $request->user()
            ->transactionRules()
            ->whereIn(
                'account_id',
                $accounts->pluck('id')
            )
            ->orderBy('keyword')
            ->orderBy('id')
            ->get()
            ->groupBy('account_id');

        return view(
            'transaction-rules.index',
            compact(
                'accounts',
                'categories',
                'transactionRulesByAccount'
            )
        );
    }

    public function bulkUpdate(
        Request $request,
        Account $account
    ): RedirectResponse {
        $this->ensureAccountOwnedByUser(
            $request,
            $account
        );

        abort_if(
            in_array(
                $account->type,
                [AccountType::CASH, AccountType::LIABILITY],
                true
            ),
            404
        );

        $validated = $request->validate([
            'transaction_rules' => [
                'nullable',
                'array',
            ],
            'transaction_rules.*.id' => [
                'nullable',
                'integer',
                'distinct',
            ],
            'transaction_rules.*.keyword' => [
                'required',
                'string',
                'max:255',
            ],
            'transaction_rules.*.display_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'transaction_rules.*.category_id' => [
                'nullable',
                'integer',
                Rule::exists(
                    'categories',
                    'id'
                )->where(function ($query) use ($request) {
                    $query
                        ->where('user_id', $request->user()->id)
                        ->whereIn('type', [
                            CategoryType::INCOME->value,
                            CategoryType::EXPENSE->value,
                        ]);
                }),
            ],
        ]);

        $rows = collect(
            $validated['transaction_rules'] ?? []
        )->values();

        $this->validateDuplicateRows($rows);

        $ruleIds = $rows
            ->pluck('id')
            ->filter()
            ->map(
                fn ($id) => (int) $id
            )
            ->values();

        $existingRules = $request->user()
            ->transactionRules()
            ->where(
                'account_id',
                $account->id
            )
            ->get()
            ->keyBy('id');

        $submittedRules = $existingRules
            ->only(
                $ruleIds->all()
            );

        if (
            $submittedRules->count()
            !== $ruleIds->count()
        ) {
            abort(404);
        }

        $savedRows = DB::transaction(
            function () use (
                $request,
                $account,
                $rows,
                $existingRules,
                $ruleIds
            ): array {
                $existingRules
                    ->reject(
                        fn ($rule) =>
                            $ruleIds->contains(
                                $rule->id
                            )
                    )
                    ->each(
                        fn ($rule) =>
                            $rule->delete()
                    );

                $existingRules
                    ->filter(
                        fn ($rule) =>
                            $ruleIds->contains(
                                $rule->id
                            )
                    )
                    ->each(
                        function ($rule): void {
                            $rule->update([
                                'keyword' =>
                                    '__tmp_rule_'
                                    . $rule->id
                                    . '_'
                                    . Str::uuid(),
                            ]);
                        }
                    );

                $savedRows = [];

                foreach ($rows as $row) {
                    $data = [
                        'keyword' =>
                            $row['keyword'],
                        'display_name' =>
                            $row['display_name']
                            ?? null,
                        'category_id' =>
                            $row['category_id']
                            ?? null,
                    ];

                    if (! empty($row['id'])) {
                        $rule = $existingRules->get(
                            (int) $row['id']
                        );

                        $rule->update($data);
                    } else {
                        $rule = $request->user()
                            ->transactionRules()
                            ->create([
                                'account_id' =>
                                    $account->id,
                                ...$data,
                            ]);
                    }

                    $savedRows[] = [
                        'id' => $rule->id,
                        'keyword' => $rule->keyword,
                        'display_name' =>
                            $rule->display_name,
                        'category_id' =>
                            $rule->category_id,
                    ];
                }

                return $savedRows;
            }
        );

        return redirect()
            ->route(
                'transaction-rules.index'
            )
            ->with(
                'success',
                $account->name
                . 'の入力テンプレートを保存しました。'
            )
            ->with(
                'saved_template_account_id',
                $account->id
            )
            ->with(
                'saved_template_rows',
                $savedRows
            );
    }

    private function validateDuplicateRows(
        $rows
    ): void {
        $seen = [];

        foreach ($rows as $index => $row) {
            $keyword = mb_strtolower(
                $row['keyword']
            );

            $categoryId =
                $row['category_id']
                ?? 'null';

            $key =
                $keyword
                . "\0"
                . $categoryId;

            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    "transaction_rules.$index.keyword" =>
                        '同じキーワードとカテゴリの組み合わせが重複しています。',
                ]);
            }

            $seen[$key] = true;
        }
    }

    private function ensureAccountOwnedByUser(
        Request $request,
        Account $account
    ): void {
        abort_unless(
            $account->user_id
                === $request->user()->id,
            404
        );
    }
}
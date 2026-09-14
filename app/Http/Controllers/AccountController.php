<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = $request->user()
            ->accounts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view(
            'accounts.index',
            [
                'accounts' => $accounts,
                'accountTypes' => AccountType::cases(),
            ]
        );
    }

    public function bulkUpdate(
        Request $request
    ): RedirectResponse {
        $validated = $request->validate([
            'accounts' => [
                'nullable',
                'array',
            ],
            'accounts.*.id' => [
                'nullable',
                'integer',
                'distinct',
            ],
            'accounts.*.name' => [
                'required',
                'string',
                'max:100',
                'distinct',
            ],
            'accounts.*.type' => [
                'required',
                Rule::enum(AccountType::class),
            ],
        ]);

        $rows = collect(
            $validated['accounts'] ?? []
        );

        $accountIds = $rows
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        $accounts = $request->user()
            ->accounts()
            ->whereIn('id', $accountIds)
            ->get()
            ->keyBy('id');

        if (
            $accounts->count()
            !== $accountIds->count()
        ) {
            abort(404);
        }

        $names = $rows
            ->pluck('name')
            ->values();

        $hasConflict = $request->user()
            ->accounts()
            ->when(
                $accountIds->isNotEmpty(),
                fn ($query) =>
                    $query->whereNotIn(
                        'id',
                        $accountIds
                    )
            )
            ->whereIn('name', $names)
            ->exists();

        if ($hasConflict) {
            throw ValidationException::withMessages([
                'accounts' =>
                    '同じ名前の口座が既に登録されています。',
            ]);
        }

        DB::transaction(
            function () use (
                $request,
                $rows,
                $accounts
            ): void {
                foreach ($accounts as $account) {
                    $account->update([
                        'name' =>
                            '__tmp_account_'
                            . $account->id
                            . '_'
                            . Str::uuid(),
                    ]);
                }

                foreach (
                    $rows->values() as $index => $row
                ) {
                    $sortOrder = ($index + 1) * 10;

                    if (! empty($row['id'])) {
                        $account = $accounts->get(
                            (int) $row['id']
                        );

                        $account->update([
                            'name' => $row['name'],
                            'type' => $row['type'],
                            'sort_order' => $sortOrder,
                        ]);

                        continue;
                    }

                    $request->user()
                        ->accounts()
                        ->create([
                            'name' => $row['name'],
                            'type' => $row['type'],
                            'sort_order' => $sortOrder,
                        ]);
                }
            }
        );

        return redirect()
            ->route('accounts.index')
            ->with(
                'success',
                '口座を保存しました。'
            );
    }

    public function destroy(
        Request $request,
        Account $account
    ): RedirectResponse {
        $this->ensureOwnedByUser(
            $request,
            $account
        );

        $account->delete();

        return redirect()
            ->route('accounts.index')
            ->with(
                'success',
                '口座を削除しました。'
            );
    }

    private function ensureOwnedByUser(
        Request $request,
        Account $account
    ): void {
        abort_unless(
            $account->user_id ===
                $request->user()->id,
            404
        );
    }
}
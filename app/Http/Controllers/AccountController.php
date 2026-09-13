<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $accounts = $request->user()
            ->accounts()
            ->orderBy('name')
            ->get();

        return view(
            'accounts.index',
            compact('accounts')
        );
    }

    public function create(): View
    {
        return view(
            'accounts.create',
            [
                'accountTypes' =>
                    AccountType::cases(),
            ]
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $validated =
            $this->validateAccount(
                $request
            );

        $request->user()
            ->accounts()
            ->create($validated);

        return redirect()
            ->route('accounts.index')
            ->with(
                'success',
                '口座を登録しました。'
            );
    }

    public function edit(
        Request $request,
        Account $account
    ): View {
        $this->ensureOwnedByUser(
            $request,
            $account
        );

        return view(
            'accounts.edit',
            [
                'account' => $account,
                'accountTypes' =>
                    AccountType::cases(),
            ]
        );
    }

    public function update(
        Request $request,
        Account $account
    ): RedirectResponse {
        $this->ensureOwnedByUser(
            $request,
            $account
        );

        $validated =
            $this->validateAccount(
                $request,
                $account
            );

        $account->update($validated);

        return redirect()
            ->route('accounts.index')
            ->with(
                'success',
                '口座を更新しました。'
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

    private function validateAccount(
        Request $request,
        ?Account $account = null
    ): array {
        $nameRule =
            Rule::unique(
                'accounts',
                'name'
            )
                ->where(
                    'user_id',
                    $request->user()->id
                );

        if ($account !== null) {
            $nameRule->ignore(
                $account->id
            );
        }

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                $nameRule,
            ],
            'type' => [
                'required',
                Rule::enum(
                    AccountType::class
                ),
            ],
        ]);
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
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OpeningBalanceController extends Controller
{
    public function create(Request $request): View
    {
        $accounts = $request->user()
            ->accounts()
            ->orderBy('name')
            ->get();

        $registeredAccountIds = $request->user()
            ->transactions()
            ->where(
                'type',
                TransactionType::OPENING_BALANCE->value
            )
            ->pluck('account_id');

        return view(
            'opening-balances.create',
            compact(
                'accounts',
                'registeredAccountIds'
            )
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateOpeningBalance($request);

        $alreadyExists = $request->user()
            ->transactions()
            ->where(
                'type',
                TransactionType::OPENING_BALANCE->value
            )
            ->where(
                'account_id',
                $validated['account_id']
            )
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'account_id' =>
                    'この口座にはすでに初期残高が登録されています。',
            ]);
        }

        $request->user()
            ->transactions()
            ->create([
                'transaction_date' =>
                    $validated['transaction_date'],

                'type' =>
                    TransactionType::OPENING_BALANCE->value,

                'account_id' =>
                    $validated['account_id'],

                'category_id' => null,
                'counterparty_name' => null,

                'amount' =>
                    $validated['amount'],

                'withdrawal_date' => null,
                'expense_ratio' => 0,
                'expense_registered' => false,
                'receipt_saved' => false,
            ]);

        return redirect()
            ->route('transactions.index')
            ->with(
                'success',
                '初期残高を登録しました。'
            );
    }

    public function edit(
        Request $request,
        Transaction $openingBalance
    ): View {
        $this->ensureOwnedOpeningBalance(
            $request,
            $openingBalance
        );

        $accounts = $request->user()
            ->accounts()
            ->orderBy('name')
            ->get();

        $registeredAccountIds = $request->user()
            ->transactions()
            ->where(
                'type',
                TransactionType::OPENING_BALANCE->value
            )
            ->where(
                'id',
                '!=',
                $openingBalance->id
            )
            ->pluck('account_id');

        return view(
            'opening-balances.edit',
            compact(
                'openingBalance',
                'accounts',
                'registeredAccountIds'
            )
        );
    }

    public function update(
        Request $request,
        Transaction $openingBalance
    ): RedirectResponse {
        $this->ensureOwnedOpeningBalance(
            $request,
            $openingBalance
        );

        $validated = $this->validateOpeningBalance($request);

        $alreadyExists = $request->user()
            ->transactions()
            ->where(
                'type',
                TransactionType::OPENING_BALANCE->value
            )
            ->where(
                'account_id',
                $validated['account_id']
            )
            ->where(
                'id',
                '!=',
                $openingBalance->id
            )
            ->exists();

        if ($alreadyExists) {
            throw ValidationException::withMessages([
                'account_id' =>
                    'この口座にはすでに初期残高が登録されています。',
            ]);
        }

        $openingBalance->update([
            'transaction_date' =>
                $validated['transaction_date'],

            'account_id' =>
                $validated['account_id'],

            'amount' =>
                $validated['amount'],
        ]);

        return redirect()
            ->route('transactions.index')
            ->with(
                'success',
                '初期残高を更新しました。'
            );
    }

    public function destroy(
        Request $request,
        Transaction $openingBalance
    ): RedirectResponse {
        $this->ensureOwnedOpeningBalance(
            $request,
            $openingBalance
        );

        $openingBalance->delete();

        return redirect()
            ->route('transactions.index')
            ->with(
                'success',
                '初期残高を削除しました。'
            );
    }

    /**
     * 初期残高の入力値を検証する。
     *
     * @return array<string, mixed>
     */
    private function validateOpeningBalance(
        Request $request
    ): array {
        return $request->validate([
            'transaction_date' => [
                'required',
                'date',
            ],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
            'amount' => [
                'required',
                'integer',
                'min:0',
            ],
        ]);
    }

    /**
     * 操作対象が現在のユーザーの初期残高か確認する。
     */
    private function ensureOwnedOpeningBalance(
        Request $request,
        Transaction $openingBalance
    ): void {
        abort_unless(
            $openingBalance->user_id === $request->user()->id
            && $openingBalance->type
                === TransactionType::OPENING_BALANCE,
            404
        );
    }
}
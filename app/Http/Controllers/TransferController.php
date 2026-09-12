<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Transfer;
use App\Services\TransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransferController extends Controller
{
    public function create(Request $request): View
    {
        $accounts = $request->user()
            ->accounts()
            ->orderBy('name')
            ->get();

        return view(
            'transfers.create',
            compact('accounts')
        );
    }

    public function store(
        Request $request,
        TransactionService $transactionService
    ): RedirectResponse {
        $validated = $this->validateTransfer($request);

        $fromAccount = Account::findOrFail(
            $validated['from_account_id']
        );

        $toAccount = Account::findOrFail(
            $validated['to_account_id']
        );

        $transactionService->createTransfer(
            user: $request->user(),
            fromAccount: $fromAccount,
            toAccount: $toAccount,
            transactionDate: $validated['transaction_date'],
            amount: (int) $validated['amount'],
        );

        return redirect()
            ->route('transactions.index')
            ->with('success', '振替を登録しました。');
    }

    public function edit(
        Request $request,
        Transfer $transfer
    ): View {
        $this->ensureOwnedTransfer(
            $request,
            $transfer
        );

        $transfer->load([
            'fromTransaction.account',
            'toTransaction.account',
        ]);

        $accounts = $request->user()
            ->accounts()
            ->orderBy('name')
            ->get();

        return view(
            'transfers.edit',
            compact(
                'transfer',
                'accounts'
            )
        );
    }

    public function update(
        Request $request,
        Transfer $transfer,
        TransactionService $transactionService
    ): RedirectResponse {
        $this->ensureOwnedTransfer(
            $request,
            $transfer
        );

        $validated = $this->validateTransfer($request);

        $fromAccount = Account::findOrFail(
            $validated['from_account_id']
        );

        $toAccount = Account::findOrFail(
            $validated['to_account_id']
        );

        $transactionService->updateTransfer(
            user: $request->user(),
            transfer: $transfer,
            fromAccount: $fromAccount,
            toAccount: $toAccount,
            transactionDate: $validated['transaction_date'],
            amount: (int) $validated['amount'],
        );

        return redirect()
            ->route('transactions.index')
            ->with('success', '振替を更新しました。');
    }

    public function destroy(
        Request $request,
        Transfer $transfer,
        TransactionService $transactionService
    ): RedirectResponse {
        $this->ensureOwnedTransfer(
            $request,
            $transfer
        );

        $transactionService->deleteTransfer(
            user: $request->user(),
            transfer: $transfer,
        );

        return redirect()
            ->route('transactions.index')
            ->with('success', '振替を削除しました。');
    }

    /**
     * 振替入力値を検証する。
     *
     * @return array<string, mixed>
     */
    private function validateTransfer(Request $request): array
    {
        return $request->validate([
            'transaction_date' => [
                'required',
                'date',
            ],
            'from_account_id' => [
                'required',
                Rule::exists('accounts', 'id')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
            'to_account_id' => [
                'required',
                'different:from_account_id',
                Rule::exists('accounts', 'id')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],
        ]);
    }

    /**
     * 操作対象の振替が現在のユーザーのものか確認する。
     */
    private function ensureOwnedTransfer(
        Request $request,
        Transfer $transfer
    ): void {
        $transfer->loadMissing([
            'fromTransaction',
            'toTransaction',
        ]);

        abort_unless(
            $transfer->fromTransaction !== null
            && $transfer->toTransaction !== null
            && $transfer->fromTransaction->user_id
                === $request->user()->id
            && $transfer->toTransaction->user_id
                === $request->user()->id,
            404
        );
    }
}
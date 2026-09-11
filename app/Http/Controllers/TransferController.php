<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
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
        $validated = $request->validate([
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
}
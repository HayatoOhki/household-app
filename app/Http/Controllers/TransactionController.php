<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $transactions = $request->user()
            ->transactions()
            ->with([
                'account',
                'category',
                'outgoingTransfer.toTransaction.account',
            ])
            ->whereDoesntHave('incomingTransfer')
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $transactionRules = $request->user()
            ->transactionRules()
            ->get();

        return view(
            'transactions.index',
            compact(
                'transactions',
                'transactionRules'
            )
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

        $transactionRules = $request->user()
            ->transactionRules()
            ->get();

        return view(
            'transactions.create',
            compact(
                'accounts',
                'categories',
                'transactionRules'
            )
        );
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateTransaction($request);

        if ($validated['type'] === TransactionType::INCOME->value) {
            $validated['withdrawal_date'] = null;
        }

        $validated['expense_ratio'] ??= 0;
        $validated['expense_registered'] = false;
        $validated['receipt_saved'] = false;

        $request->user()
            ->transactions()
            ->create($validated);

        return redirect()
            ->route('transactions.index')
            ->with('success', '取引を登録しました。');
    }

    public function edit(
        Request $request,
        Transaction $transaction
    ): View {
        $this->ensureOwnedByUser($request, $transaction);
        $this->ensureNormalTransaction($transaction);

        $accounts = $request->user()
            ->accounts()
            ->orderBy('name')
            ->get();

        $categories = $request->user()
            ->categories()
            ->orderBy('name')
            ->get();

        $transactionRules = $request->user()
            ->transactionRules()
            ->get();

        return view(
            'transactions.edit',
            compact(
                'transaction',
                'accounts',
                'categories',
                'transactionRules'
            )
        );
    }

    public function update(
        Request $request,
        Transaction $transaction
    ): RedirectResponse {
        $this->ensureOwnedByUser($request, $transaction);
        $this->ensureNormalTransaction($transaction);

        $validated = $this->validateTransaction($request);

        if ($validated['type'] === TransactionType::INCOME->value) {
            $validated['withdrawal_date'] = null;
        }

        $validated['expense_ratio'] ??= 0;

        $transaction->update($validated);

        return redirect()
            ->route('transactions.index')
            ->with('success', '取引を更新しました。');
    }

    public function destroy(
        Request $request,
        Transaction $transaction
    ): RedirectResponse {
        $this->ensureOwnedByUser($request, $transaction);
        $this->ensureNormalTransaction($transaction);

        $transaction->delete();

        return redirect()
            ->route('transactions.index')
            ->with('success', '取引を削除しました。');
    }

    private function validateTransaction(Request $request): array
    {
        return $request->validate([
            'transaction_date' => [
                'required',
                'date',
            ],
            'type' => [
                'required',
                Rule::in([
                    TransactionType::EXPENSE->value,
                    TransactionType::INCOME->value,
                ]),
            ],
            'account_id' => [
                'required',
                Rule::exists('accounts', 'id')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where(
                        'user_id',
                        $request->user()->id
                    ),
            ],
            'counterparty_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],
            'withdrawal_date' => [
                'nullable',
                'date',
            ],
            'expense_ratio' => [
                'nullable',
                'numeric',
                'between:0,100',
            ],
        ]);
    }

    private function ensureOwnedByUser(
        Request $request,
        Transaction $transaction
    ): void {
        abort_unless(
            $transaction->user_id === $request->user()->id,
            404
        );
    }

    private function ensureNormalTransaction(
        Transaction $transaction
    ): void {
        abort_unless(
            in_array(
                $transaction->type,
                [
                    TransactionType::EXPENSE,
                    TransactionType::INCOME,
                ],
                true
            ),
            404
        );
    }
}
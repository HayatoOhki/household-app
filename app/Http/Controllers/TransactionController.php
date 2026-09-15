<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AccountType;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $query = $request->user()
            ->transactions()
            ->with([
                'account',
                'category',
                'outgoingTransfer.toTransaction.account',
            ])
            ->whereDoesntHave('incomingTransfer');

        if ($request->filled('date_from')) {
            $query->whereDate(
                'transaction_date',
                '>=',
                $request->string('date_from')->toString()
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'transaction_date',
                '<=',
                $request->string('date_to')->toString()
            );
        }

        if ($request->filled('type')) {
            $query->where(
                'type',
                $request->string('type')->toString()
            );
        }

        if ($request->filled('counterparty')) {
            $query->where(
                'counterparty_name',
                'like',
                '%' . $request->string('counterparty')->toString() . '%'
            );
        }

        if ($request->filled('account_id')) {
            $accountId = $request->integer('account_id');

            $query->where(function ($query) use ($accountId) {
                $query
                    ->where('account_id', $accountId)
                    ->orWhereHas(
                        'outgoingTransfer.toTransaction',
                        function ($query) use ($accountId) {
                            $query->where(
                                'account_id',
                                $accountId
                            );
                        }
                    );
            });
        }

        if ($request->filled('category_id')) {
            $query->where(
                'category_id',
                $request->integer('category_id')
            );
        }

        if ($request->filled('withdrawal_date_from')) {
            $query->whereDate(
                'withdrawal_date',
                '>=',
                $request
                    ->string('withdrawal_date_from')
                    ->toString()
            );
        }

        if ($request->filled('withdrawal_date_to')) {
            $query->whereDate(
                'withdrawal_date',
                '<=',
                $request
                    ->string('withdrawal_date_to')
                    ->toString()
            );
        }

        if ($request->filled('has_expense_ratio')) {
            if ($request->string('has_expense_ratio')->toString() === '1') {
                $query->where(
                    'expense_ratio',
                    '>',
                    0
                );
            }

            if ($request->string('has_expense_ratio')->toString() === '0') {
                $query->where(function ($query) {
                    $query
                        ->whereNull('expense_ratio')
                        ->orWhere(
                            'expense_ratio',
                            '<=',
                            0
                        );
                });
            }
        }

        if ($request->filled('expense_registered')) {
            $query->where(
                'expense_registered',
                $request->boolean('expense_registered')
            );
        }

        if ($request->filled('receipt_saved')) {
            $query->where(
                'receipt_saved',
                $request->boolean('receipt_saved')
            );
        }

        $transactions = $query
            ->orderByRaw(
                'CASE WHEN type = ? THEN 1 ELSE 0 END',
                [TransactionType::OPENING_BALANCE->value]
            )
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->get();

        $transactionRules = $request->user()
            ->transactionRules()
            ->get();

        $accounts = $request->user()
            ->accounts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = $request->user()
            ->categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view(
            'transactions.index',
            compact(
                'transactions',
                'transactionRules',
                'accounts',
                'categories'
            )
        );
    }

    public function create(Request $request): View
    {
        $accounts = $request->user()
            ->accounts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = $request->user()
            ->categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $transactionRules = $request->user()
            ->transactionRules()
            ->get();

        $duplicateTransaction = null;
        $duplicateTransfer = null;

        return view(
            'transactions.create',
            compact(
                'accounts',
                'categories',
                'transactionRules',
                'duplicateTransaction',
                'duplicateTransfer'
            )
        );
    }

    public function duplicate(
        Request $request,
        Transaction $transaction
    ): View {
        $this->ensureOwnedByUser($request, $transaction);

        abort_if(
            $transaction->type === TransactionType::OPENING_BALANCE,
            404
        );

        $accounts = $request->user()
            ->accounts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = $request->user()
            ->categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $transactionRules = $request->user()
            ->transactionRules()
            ->get();

        $duplicateTransaction = null;
        $duplicateTransfer = null;

        if ($transaction->type === TransactionType::TRANSFER) {
            $transaction->load([
                'outgoingTransfer.fromTransaction.account',
                'outgoingTransfer.toTransaction.account',
            ]);

            abort_if(
                $transaction->outgoingTransfer === null,
                404
            );

            $duplicateTransfer = $transaction->outgoingTransfer;
        } else {
            $this->ensureNormalTransaction($transaction);
            $duplicateTransaction = $transaction;
        }

        return view(
            'transactions.create',
            compact(
                'accounts',
                'categories',
                'transactionRules',
                'duplicateTransaction',
                'duplicateTransfer'
            )
        );
    }

    public function store(
        Request $request,
        TransactionService $transactionService
    ): RedirectResponse {
        if (
            $request->string('type')->toString()
            === TransactionType::TRANSFER->value
        ) {
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

        abort_if(
            $transaction->type === TransactionType::OPENING_BALANCE,
            404
        );

        $accounts = $request->user()
            ->accounts()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $categories = $request->user()
            ->categories()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $transactionRules = $request->user()
            ->transactionRules()
            ->get();

        $transfer = null;

        if ($transaction->type === TransactionType::TRANSFER) {
            $transaction->load([
                'outgoingTransfer.fromTransaction.account',
                'outgoingTransfer.toTransaction.account',
            ]);

            $transfer = $transaction->outgoingTransfer;

            abort_if(
                $transfer === null,
                404
            );
        } else {
            $this->ensureNormalTransaction($transaction);
        }

        return view(
            'transactions.edit',
            compact(
                'transaction',
                'accounts',
                'categories',
                'transactionRules',
                'transfer'
            )
        );
    }

    public function update(
        Request $request,
        Transaction $transaction,
        TransactionService $transactionService
    ): RedirectResponse {
        $this->ensureOwnedByUser($request, $transaction);

        abort_if(
            $transaction->type === TransactionType::OPENING_BALANCE,
            404
        );

        $requestedType = $request
            ->string('type')
            ->toString();

        $isCurrentTransfer =
            $transaction->type === TransactionType::TRANSFER;

        $isRequestedTransfer =
            $requestedType === TransactionType::TRANSFER->value;

        if ($isCurrentTransfer && $isRequestedTransfer) {
            $transaction->load('outgoingTransfer');

            $transfer = $transaction->outgoingTransfer;

            abort_if(
                $transfer === null,
                404
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
                ->with('success', '取引を更新しました。');
        }

        if (!$isCurrentTransfer && !$isRequestedTransfer) {
            $this->ensureNormalTransaction($transaction);

            $validated = $this->validateTransaction($request);

            if (
                $validated['type']
                === TransactionType::INCOME->value
            ) {
                $validated['withdrawal_date'] = null;
            }

            $validated['expense_ratio'] ??= 0;

            $transaction->update($validated);

            return redirect()
                ->route('transactions.index')
                ->with('success', '取引を更新しました。');
        }

        if (!$isCurrentTransfer && $isRequestedTransfer) {
            $this->ensureNormalTransaction($transaction);

            $validated = $this->validateTransfer($request);

            $fromAccount = Account::findOrFail(
                $validated['from_account_id']
            );

            $toAccount = Account::findOrFail(
                $validated['to_account_id']
            );

            DB::transaction(function () use (
                $request,
                $transaction,
                $transactionService,
                $fromAccount,
                $toAccount,
                $validated
            ): void {
                $transactionService->createTransfer(
                    user: $request->user(),
                    fromAccount: $fromAccount,
                    toAccount: $toAccount,
                    transactionDate: $validated['transaction_date'],
                    amount: (int) $validated['amount'],
                );

                $transaction->delete();
            });

            return redirect()
                ->route('transactions.index')
                ->with('success', '取引を更新しました。');
        }

        $transaction->load('outgoingTransfer');

        $transfer = $transaction->outgoingTransfer;

        abort_if(
            $transfer === null,
            404
        );

        $validated = $this->validateTransaction($request);

        if (
            $validated['type']
            === TransactionType::INCOME->value
        ) {
            $validated['withdrawal_date'] = null;
        }

        $validated['expense_ratio'] ??= 0;
        $validated['expense_registered'] = false;
        $validated['receipt_saved'] = false;

        DB::transaction(function () use (
            $request,
            $transactionService,
            $transfer,
            $validated
        ): void {
            $transactionService->deleteTransfer(
                user: $request->user(),
                transfer: $transfer,
            );

            $request->user()
                ->transactions()
                ->create($validated);
        });

        return redirect()
            ->route('transactions.index')
            ->with('success', '取引を更新しました。');
    }

    public function destroy(
        Request $request,
        Transaction $transaction,
        TransactionService $transactionService
    ): RedirectResponse {
        $this->ensureOwnedByUser($request, $transaction);

        abort_if(
            $transaction->type === TransactionType::OPENING_BALANCE,
            404
        );

        if ($transaction->type === TransactionType::TRANSFER) {
            $transaction->load('outgoingTransfer');

            $transfer = $transaction->outgoingTransfer;

            abort_if(
                $transfer === null,
                404
            );

            $transactionService->deleteTransfer(
                user: $request->user(),
                transfer: $transfer,
            );
        } else {
            $this->ensureNormalTransaction($transaction);
            $transaction->delete();
        }

        return redirect()
            ->route('transactions.index')
            ->with('success', '取引を削除しました。');
    }

    public function updateStatus(
        Request $request,
        Transaction $transaction
    ): JsonResponse {
        $this->ensureOwnedByUser($request, $transaction);
        $this->ensureNormalTransaction($transaction);

        $validated = $request->validate([
            'field' => [
                'required',
                Rule::in([
                    'expense_registered',
                    'receipt_saved',
                ]),
            ],
            'value' => [
                'required',
                'boolean',
            ],
        ]);

        $transaction->update([
            $validated['field'] => $validated['value'],
        ]);

        return response()->json([
            'success' => true,
            'field' => $validated['field'],
            'value' => $validated['value'],
        ]);
    }

    private function validateTransaction(Request $request): array
    {
        $isCreditCardExpense = function () use ($request): bool {
            if (
                $request->input('type')
                !== TransactionType::EXPENSE->value
            ) {
                return false;
            }

            if (!$request->filled('account_id')) {
                return false;
            }

            $account = $request->user()
                ->accounts()
                ->whereKey($request->integer('account_id'))
                ->first();

            return $account !== null
                && $account->type === AccountType::CREDIT_CARD;
        };

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
                    )
                    ->where(
                        'type',
                        $request->input('type')
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
                Rule::requiredIf($isCreditCardExpense),
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

    private function validateTransfer(Request $request): array
    {
        return $request->validate([
            'type' => [
                'required',
                Rule::in([
                    TransactionType::TRANSFER->value,
                ]),
            ],
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
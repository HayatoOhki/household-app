<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SummaryService $summaryService
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        $month = now()->format('Y-m');

        $monthlySummary =
            $this->summaryService
                ->getMonthlySummary(
                    $user,
                    $month
                );

        $creditCardWithdrawals =
            $this->summaryService
                ->getCreditCardWithdrawals(
                    $user
                );

        $accountBalances =
            $this->summaryService
                ->getDashboardAccountBalances(
                    $user
                );

        return view(
            'dashboard',
            [
                ...$monthlySummary,
                'creditCardWithdrawals' =>
                    $creditCardWithdrawals,
                'accountBalances' =>
                    $accountBalances,
            ]
        );
    }
}
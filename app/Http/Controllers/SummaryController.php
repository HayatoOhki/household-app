<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\SummaryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SummaryController extends Controller
{
    public function __construct(
        private readonly SummaryService $summaryService
    ) {
    }

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'month' => [
                'nullable',
                'date_format:Y-m',
            ],
        ]);

        $month = $validated['month']
            ?? now()->format('Y-m');

        $user = $request->user();

        $monthlySummary =
            $this->summaryService
                ->getMonthlySummary(
                    $user,
                    $month
                );

        $accountBalances =
            $this->summaryService
                ->getAccountBalances(
                    $user
                );

        return view(
            'summary.index',
            [
                ...$monthlySummary,
                'accountBalances' =>
                    $accountBalances,
            ]
        );
    }
}
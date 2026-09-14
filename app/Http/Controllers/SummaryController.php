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
            'year' => [
                'nullable',
                'integer',
                'min:2000',
                'max:2100',
            ],
        ]);

        $year = (int) ($validated['year'] ?? now()->year);

        return view(
            'summary.index',
            $this->summaryService->getAnnualSummary(
                $request->user(),
                $year
            )
        );
    }
}

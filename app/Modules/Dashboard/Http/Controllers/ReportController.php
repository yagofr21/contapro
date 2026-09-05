<?php

namespace App\Modules\Dashboard\Http\Controllers;

use App\Enums\Currency;
use App\Http\Controllers\Controller;
use App\Modules\Dashboard\Http\Requests\ReportRequest;
use App\Modules\Dashboard\Queries\FinancialReportQuery;
use App\Modules\Investment\Queries\PortfolioValuationQuery;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __invoke(
        ReportRequest $request,
        FinancialReportQuery $financialReport,
        PortfolioValuationQuery $portfolioValuation,
    ): Response {
        $filters = $request->validated();
        $report = $financialReport->forUser(
            $request->user(),
            (string) $filters['from'],
            (string) $filters['to'],
            (string) $filters['currency'],
        );
        $investments = $portfolioValuation->forUser($request->user());

        return Inertia::render('Reports/Index', [
            'filters' => $filters,
            'currencies' => array_map(fn (Currency $currency): string => $currency->value, Currency::cases()),
            ...$report,
            'investmentSummary' => collect($investments['summaries'])
                ->firstWhere('currency', $filters['currency']),
            'allocation' => collect($investments['allocation'])
                ->where('currency', $filters['currency'])
                ->values(),
        ]);
    }
}

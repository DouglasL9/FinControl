<?php

namespace App\Http\Controllers;

use App\Models\Payable;
use App\Services\PayableSummary;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PayableSummary $summary): View
    {
        $company = $request->user()->currentCompany();

        if ($company === null) {
            return view('dashboard', [
                'company' => null,
                'hasDashboardAccess' => true,
            ]);
        }

        $membership = $company->membershipFor($request->user());
        $access = $company->permissionsFor($membership);
        $canViewPayables = $access['payables.view'] ?? false;

        if (! ($access['dashboard.view'] ?? false)) {
            return view('dashboard', [
                'company' => $company,
                'hasDashboardAccess' => false,
                'canViewPayables' => $canViewPayables,
            ]);
        }

        $upcoming = Payable::query()
            ->whereBelongsTo($company)
            ->with('unit')
            ->whereNull('paid_at')
            ->whereDate('due_date', '>=', today())
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        return view('dashboard', [
            'company' => $company,
            'hasDashboardAccess' => true,
            'canViewPayables' => $canViewPayables,
            'summary' => $summary->forCompany($company),
            'unitTotals' => $summary->byUnit($company),
            'upcoming' => $upcoming,
        ]);
    }
}

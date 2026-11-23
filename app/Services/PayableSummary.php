<?php

namespace App\Services;

use App\Models\Company;
use App\Models\RecurringPayable;
use Illuminate\Database\Eloquent\Collection;

class PayableSummary
{
    /**
     * @return array{open_total: int|float, pending_total: int|float, paid_this_month: int|float, overdue_total: int|float, overdue_count: int}
     */
    public function forCompany(Company $company): array
    {
        $today = today();

        return [
            'open_total' => $company->payables()
                ->whereNull('paid_at')
                ->sum('amount'),
            'pending_total' => $company->payables()
                ->whereNull('paid_at')
                ->whereDate('due_date', '>=', $today)
                ->sum('amount'),
            'paid_this_month' => $company->payables()
                ->whereBetween('paid_at', [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()])
                ->sum('amount'),
            'overdue_total' => $company->payables()
                ->whereNull('paid_at')
                ->whereDate('due_date', '<', $today)
                ->sum('amount'),
            'overdue_count' => $company->payables()
                ->whereNull('paid_at')
                ->whereDate('due_date', '<', $today)
                ->count(),
        ];
    }

    public function byUnit(Company $company): Collection
    {
        $today = today();
        $monthStart = $today->copy()->startOfMonth();
        $monthEnd = $today->copy()->endOfMonth();

        return $company->payables()
            ->join('units', 'units.id', '=', 'payables.unit_id')
            ->select('units.name as unit')
            ->selectSub(
                RecurringPayable::query()
                    ->selectRaw('COUNT(*)')
                    ->where('company_id', $company->getKey())
                    ->whereColumn('unit_id', 'units.id'),
                'contract_count',
            )
            ->selectRaw('SUM(CASE WHEN payables.paid_at IS NULL THEN payables.amount ELSE 0 END) AS open_total')
            ->selectRaw(
                'SUM(CASE WHEN payables.paid_at IS NULL AND payables.due_date >= ? THEN payables.amount ELSE 0 END) AS pending_total',
                [$today->toDateString()],
            )
            ->selectRaw(
                'SUM(CASE WHEN payables.paid_at IS NOT NULL AND payables.paid_at BETWEEN ? AND ? THEN payables.amount ELSE 0 END) AS paid_this_month',
                [$monthStart, $monthEnd],
            )
            ->selectRaw(
                'SUM(CASE WHEN payables.paid_at IS NULL AND payables.due_date < ? THEN payables.amount ELSE 0 END) AS overdue_total',
                [$today->toDateString()],
            )
            ->groupBy('units.id', 'units.name')
            ->orderBy('units.name')
            ->get();
    }
}

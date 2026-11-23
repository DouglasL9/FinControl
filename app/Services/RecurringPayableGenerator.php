<?php

namespace App\Services;

use App\Models\RecurringPayable;
use Illuminate\Support\Carbon;

class RecurringPayableGenerator
{
    public function generateUpcoming(): int
    {
        $created = 0;

        RecurringPayable::query()
            ->where('is_active', true)
            ->whereHas('company', fn ($query) => $query->where('is_active', true))
            ->with('supplier')
            ->chunkById(100, function ($contracts) use (&$created): void {
                foreach ($contracts as $contract) {
                    $created += $this->generateFor($contract);
                }
            });

        return $created;
    }

    public function generateFor(RecurringPayable $contract, ?Carbon $today = null): int
    {
        if (! $contract->is_active || ! $contract->company()->where('is_active', true)->exists()) {
            return 0;
        }

        $today ??= now();
        $lastMonth = $today->copy()->startOfMonth()->addMonths(2);
        $month = $contract->starts_on->copy()->startOfMonth();
        $created = 0;

        while (
            $month->lte($lastMonth)
            && ($contract->ends_on === null || $month->lte($contract->ends_on->copy()->startOfMonth()))
        ) {
            $dueDate = $month->copy()->day(min($contract->due_day, $month->daysInMonth));

            if (
                $dueDate->gte($contract->starts_on)
                && ($contract->ends_on === null || $dueDate->lte($contract->ends_on))
            ) {
                $payable = $contract->payables()
                    ->whereDate('due_date', $dueDate->toDateString())
                    ->firstOrCreate(
                        [],
                        [
                            'company_id' => $contract->company_id,
                            'unit_id' => $contract->unit_id,
                            'description' => $contract->description,
                            'provider' => $contract->supplier?->name ?? $contract->provider,
                            'amount' => $contract->amount,
                            'due_date' => $dueDate->toDateString(),
                        ],
                    );

                if ($payable->wasRecentlyCreated) {
                    $created++;
                }
            }

            $month->addMonth();
        }

        return $created;
    }
}

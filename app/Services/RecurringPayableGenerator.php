<?php

namespace App\Services;

use App\Models\PayableHistory;
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

    /**
     * Propaga a edição do contrato para as contas ainda não pagas e gera as que faltam.
     * Contas pagas nunca são alteradas.
     */
    public function syncUnpaidPayables(RecurringPayable $contract, string $oldAmount, ?int $actorId, string $actorName): void
    {
        $amountChanged = $oldAmount !== (string) $contract->amount;

        foreach ($contract->payables()->whereNull('paid_at')->lockForUpdate()->get() as $payable) {
            $month = $payable->due_date->copy()->startOfMonth();
            $dueDate = $month->copy()->day(min($contract->due_day, $month->daysInMonth));

            if ($dueDate->lt($contract->starts_on) || ($contract->ends_on !== null && $dueDate->gt($contract->ends_on))) {
                $payable->delete();

                continue;
            }

            $payable->fill([
                'unit_id' => $contract->unit_id,
                'description' => $contract->description,
                'provider' => $contract->provider,
                'due_date' => $dueDate->toDateString(),
            ]);

            if ($amountChanged) {
                $previous = (string) $payable->amount;
                $payable->amount = $contract->amount;

                if ($previous !== (string) $contract->amount) {
                    $payable->histories()->create([
                        'user_id' => $actorId,
                        'actor_name' => $actorName,
                        'action' => PayableHistory::ACTION_AMOUNT_UPDATED,
                        'old_amount' => $previous,
                        'new_amount' => $contract->amount,
                    ]);
                }
            }

            $payable->save();
        }

        $this->generateFor($contract);
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
                    ->whereBetween('due_date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()])
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

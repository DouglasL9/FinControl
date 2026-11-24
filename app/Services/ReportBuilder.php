<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Payable;
use App\Models\RecurringPayable;
use Illuminate\Support\Collection;

class ReportBuilder
{
    public const TYPES = [
        'payables' => 'Contas a pagar',
        'contracts' => 'Contratos recorrentes',
        'suppliers' => 'Por fornecedor',
        'units' => 'Por unidade',
    ];

    public const PAYABLE_STATUSES = ['all', 'pending', 'paid', 'overdue'];

    public const CONTRACT_STATUSES = ['all', 'active', 'paused'];

    /**
     * @param  array{start?: ?string, end?: ?string, status?: ?string, supplier_id?: ?int, unit_id?: ?int}  $filters
     * @return array{columns: list<string>, rows: list<list<string>>, totals: list<array{label: string, value: string}>}
     */
    public function build(Company $company, string $type, array $filters): array
    {
        return match ($type) {
            'contracts' => $this->contracts($company, $filters),
            'suppliers' => $this->grouped($company, $filters, 'Fornecedor', fn (Payable $payable): string => $payable->provider),
            'units' => $this->grouped($company, $filters, 'Unidade', fn (Payable $payable): string => $payable->unit?->name ?? '—'),
            default => $this->payables($company, $filters),
        };
    }

    public static function money(float|string $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Payable>
     */
    private function payableModels(Company $company, array $filters): Collection
    {
        return $company->payables()
            ->with('unit:id,name')
            ->when($filters['start'] ?? null, fn ($query, $start) => $query->whereDate('due_date', '>=', $start))
            ->when($filters['end'] ?? null, fn ($query, $end) => $query->whereDate('due_date', '<=', $end))
            ->when($filters['unit_id'] ?? null, fn ($query, $unitId) => $query->where('unit_id', $unitId))
            ->when($filters['supplier_id'] ?? null, fn ($query, $supplierId) => $query
                ->whereHas('recurringPayable', fn ($contracts) => $contracts->where('supplier_id', $supplierId)))
            ->when($filters['status'] ?? 'all', fn ($query, $status) => match ($status) {
                'pending' => $query->whereNull('paid_at')->whereDate('due_date', '>=', today()),
                'paid' => $query->whereNotNull('paid_at'),
                'overdue' => $query->whereNull('paid_at')->whereDate('due_date', '<', today()),
                default => $query,
            })
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function payables(Company $company, array $filters): array
    {
        $models = $this->payableModels($company, $filters);
        $statusLabels = ['paid' => 'Paga', 'overdue' => 'Vencida', 'pending' => 'Pendente'];

        return [
            'columns' => ['Vencimento', 'Descrição', 'Fornecedor', 'Unidade', 'Valor', 'Status', 'Pago em'],
            'rows' => $models->map(fn (Payable $payable): array => [
                $payable->due_date->format('d/m/Y'),
                $payable->description,
                $payable->provider,
                $payable->unit?->name ?? '—',
                self::money($payable->amount),
                $statusLabels[$payable->status()],
                $payable->paid_at?->format('d/m/Y') ?? '—',
            ])->all(),
            'totals' => [
                ['label' => 'Parcelas', 'value' => (string) $models->count()],
                ['label' => 'Total', 'value' => self::money($models->sum('amount'))],
                ['label' => 'Pago', 'value' => self::money($models->filter(fn ($p) => $p->status() === 'paid')->sum('amount'))],
                ['label' => 'Pendente', 'value' => self::money($models->filter(fn ($p) => $p->status() === 'pending')->sum('amount'))],
                ['label' => 'Vencido', 'value' => self::money($models->filter(fn ($p) => $p->status() === 'overdue')->sum('amount'))],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function contracts(Company $company, array $filters): array
    {
        $contracts = $company->recurringPayables()
            ->with(['unit:id,name', 'supplier:id,name'])
            ->when($filters['unit_id'] ?? null, fn ($query, $unitId) => $query->where('unit_id', $unitId))
            ->when($filters['supplier_id'] ?? null, fn ($query, $supplierId) => $query->where('supplier_id', $supplierId))
            ->when($filters['end'] ?? null, fn ($query, $end) => $query->whereDate('starts_on', '<=', $end))
            ->when($filters['start'] ?? null, fn ($query, $start) => $query
                ->where(fn ($inner) => $inner->whereNull('ends_on')->orWhereDate('ends_on', '>=', $start)))
            ->when($filters['status'] ?? 'all', fn ($query, $status) => match ($status) {
                'active' => $query->where('is_active', true),
                'paused' => $query->where('is_active', false),
                default => $query,
            })
            ->orderBy('provider')
            ->orderBy('description')
            ->get();

        $active = $contracts->where('is_active', true);

        return [
            'columns' => ['Descrição', 'Fornecedor', 'Unidade', 'Valor mensal', 'Dia', 'Início', 'Término', 'Situação'],
            'rows' => $contracts->map(fn (RecurringPayable $contract): array => [
                $contract->description,
                $contract->supplier?->name ?? $contract->provider,
                $contract->unit?->name ?? '—',
                self::money($contract->amount),
                (string) $contract->due_day,
                $contract->starts_on->format('d/m/Y'),
                $contract->ends_on?->format('d/m/Y') ?? 'Indeterminado',
                $contract->is_active ? 'Ativo' : 'Pausado',
            ])->all(),
            'totals' => [
                ['label' => 'Contratos', 'value' => (string) $contracts->count()],
                ['label' => 'Ativos', 'value' => (string) $active->count()],
                ['label' => 'Pausados', 'value' => (string) ($contracts->count() - $active->count())],
                ['label' => 'Mensal (ativos)', 'value' => self::money($active->sum('amount'))],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  callable(Payable): string  $groupBy
     */
    private function grouped(Company $company, array $filters, string $label, callable $groupBy): array
    {
        $models = $this->payableModels($company, $filters);

        $rows = $models->groupBy($groupBy)->sortKeys()->map(function (Collection $items, string $name): array {
            $paid = $items->filter(fn ($p) => $p->status() === 'paid')->sum('amount');
            $overdue = $items->filter(fn ($p) => $p->status() === 'overdue')->sum('amount');
            $pending = $items->filter(fn ($p) => $p->status() === 'pending')->sum('amount');

            return [
                $name,
                (string) $items->count(),
                self::money($paid),
                self::money($pending),
                self::money($overdue),
                self::money($paid + $pending + $overdue),
            ];
        })->values()->all();

        $paid = $models->filter(fn ($p) => $p->status() === 'paid')->sum('amount');

        return [
            'columns' => [$label, 'Parcelas', 'Total pago', 'A vencer', 'Vencido', 'Total'],
            'rows' => $rows,
            'totals' => [
                ['label' => 'Total pago', 'value' => self::money($paid)],
                ['label' => 'A pagar', 'value' => self::money($models->sum('amount') - $paid)],
                ['label' => 'Total', 'value' => self::money($models->sum('amount'))],
            ],
        ];
    }
}

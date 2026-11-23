<?php

namespace App\Http\Controllers;

use App\Models\PayableHistory;
use App\Services\PayableSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayableController extends Controller
{
    public function index(Request $request, PayableSummary $summary): View|RedirectResponse
    {
        $company = $request->user()->currentCompany();

        if ($company === null) {
            return redirect()->route('dashboard');
        }

        $membership = $company->membershipFor($request->user());
        $access = $company->permissionsFor($membership);
        abort_unless($access['payables.view'] ?? false, 403);

        $filters = $request->validate([
            'status' => ['nullable', 'in:all,pending,paid,overdue'],
            'search' => ['nullable', 'string', 'max:100'],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('company_id', $company->id)],
        ]);

        $filterPayables = function ($query) use ($filters): void {
            match ($filters['status'] ?? 'all') {
                'pending' => $query->whereNull('paid_at')->whereDate('due_date', '>=', today()),
                'paid' => $query->whereNotNull('paid_at'),
                'overdue' => $query->whereNull('paid_at')->whereDate('due_date', '<', today()),
                default => null,
            };

            if (($filters['search'] ?? '') !== '') {
                $search = $filters['search'];

                $query->where(function ($query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('provider', 'like', "%{$search}%")
                        ->orWhereHas('unit', fn ($unitQuery) => $unitQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('recurringPayable', fn ($contractQuery) => $contractQuery
                            ->where('provider', 'like', "%{$search}%")
                            ->orWhereHas('supplier', fn ($supplierQuery) => $supplierQuery
                                ->where('name', 'like', "%{$search}%")));
                });
            }
        };

        $contracts = $company->recurringPayables()
            ->with('unit')
            ->when(
                ($filters['unit_id'] ?? null) !== null,
                fn ($query) => $query->where('unit_id', $filters['unit_id']),
            )
            ->with(['payables' => function ($query) use ($filterPayables): void {
                $filterPayables($query);
                $query->with('paidBy')
                    ->with(['histories' => fn ($historyQuery) => $historyQuery->orderByDesc('created_at')->orderByDesc('id')])
                    ->orderBy('due_date');
            }])
            ->when(
                ($filters['status'] ?? 'all') !== 'all' || ($filters['search'] ?? '') !== '',
                fn ($query) => $query->whereHas('payables', $filterPayables),
            )
            ->orderByDesc('is_active')
            ->orderBy('provider')
            ->paginate(10)
            ->withQueryString();

        return view('payables.index', [
            'company' => $company,
            'access' => [
                'create_contract' => $access['payables.create_contract'] ?? false,
                'update_amount' => $access['payables.update_amount'] ?? false,
                'change_payment_status' => $access['payables.change_payment_status'] ?? false,
                'manage_contract' => $access['payables.manage_contract'] ?? false,
                'view_company' => $company->canAccessSettings($membership),
            ],
            'contracts' => $contracts,
            'summary' => $summary->forCompany($company),
            'unitTotals' => $summary->byUnit($company),
            'units' => $company->units()->orderBy('name')->get(),
            'availableUnits' => $company->units()->where('is_active', true)->orderBy('name')->get(),
            'availableSuppliers' => $company->suppliers()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function update(Request $request, int $payable): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        if ($company === null) {
            return redirect()->route('dashboard');
        }

        abort_unless(
            $company->allows($company->membershipFor($request->user()), 'payables.update_amount'),
            403,
        );

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'],
        ]);
        $actorId = $request->user()->getKey();
        $actorName = $request->user()->name;

        DB::transaction(function () use ($company, $payable, $validated, $actorId, $actorName): void {
            $item = $company->payables()->lockForUpdate()->findOrFail($payable);

            abort_if(
                $item->paid_at !== null,
                409,
                'Contas pagas não podem ter seus dados alterados. Desfaça o pagamento para reabri-la.',
            );

            $oldAmount = (string) $item->amount;
            $newAmount = number_format((float) $validated['amount'], 2, '.', '');

            if ($oldAmount === $newAmount) {
                return;
            }

            $item->update(['amount' => $newAmount]);
            $item->histories()->create([
                'user_id' => $actorId,
                'actor_name' => $actorName,
                'action' => PayableHistory::ACTION_AMOUNT_UPDATED,
                'old_amount' => $oldAmount,
                'new_amount' => $newAmount,
            ]);
        });

        return back()->with('status', 'payable-updated');
    }

    public function togglePayment(Request $request, int $payable): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        if ($company === null) {
            return redirect()->route('dashboard');
        }

        abort_unless(
            $company->allows($company->membershipFor($request->user()), 'payables.change_payment_status'),
            403,
        );

        $actorId = $request->user()->getKey();
        $actorName = $request->user()->name;

        $status = DB::transaction(function () use ($company, $payable, $actorId, $actorName): string {
            $item = $company->payables()->lockForUpdate()->findOrFail($payable);

            if ($item->paid_at === null) {
                $item->update([
                    'paid_at' => now(),
                    'paid_by_user_id' => $actorId,
                ]);

                $item->histories()->create([
                    'user_id' => $actorId,
                    'actor_name' => $actorName,
                    'action' => PayableHistory::ACTION_PAYMENT_MARKED,
                ]);

                return 'payable-paid';
            }

            $item->update([
                'paid_at' => null,
                'paid_by_user_id' => null,
            ]);

            $item->histories()->create([
                'user_id' => $actorId,
                'actor_name' => $actorName,
                'action' => PayableHistory::ACTION_PAYMENT_REOPENED,
            ]);

            return 'payable-reopened';
        });

        return back()->with('status', $status);
    }
}

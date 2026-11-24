<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateRecurringPayableRequest;
use App\Services\RecurringPayableGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RecurringPayableController extends Controller
{
    public function store(Request $request, RecurringPayableGenerator $generator): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        if ($company === null) {
            return redirect()->route('dashboard');
        }

        abort_unless(
            $company->allows($company->membershipFor($request->user()), 'payables.create_contract'),
            403,
        );

        $validated = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')
                    ->where('company_id', $company->getKey())
                    ->where('is_active', true),
            ],
            'unit_id' => [
                'required',
                'integer',
                Rule::exists('units', 'id')
                    ->where('company_id', $company->getKey())
                    ->where('is_active', true),
            ],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ]);

        $unit = $company->units()->where('is_active', true)->findOrFail($validated['unit_id']);
        $supplier = $company->suppliers()->where('is_active', true)->findOrFail($validated['supplier_id']);
        $contract = $company->recurringPayables()->create([
            ...$validated,
            'unit_id' => $unit->getKey(),
            'provider' => $supplier->name,
            'created_by_user_id' => $request->user()->getKey(),
            'is_active' => true,
        ]);

        $generator->generateFor($contract);

        return redirect()->route('payables.index')->with('status', 'contract-created');
    }

    public function update(
        UpdateRecurringPayableRequest $request,
        RecurringPayableGenerator $generator,
        int $contract,
    ): RedirectResponse {
        $company = $request->user()->currentCompany();
        $validated = $request->validated();

        DB::transaction(function () use ($company, $contract, $validated, $request, $generator): void {
            $recurringPayable = $company->recurringPayables()->lockForUpdate()->findOrFail($contract);
            $supplier = $company->suppliers()->findOrFail($validated['supplier_id']);
            $unit = $company->units()->findOrFail($validated['unit_id']);
            $oldAmount = (string) $recurringPayable->amount;

            $recurringPayable->update([
                ...$validated,
                'unit_id' => $unit->getKey(),
                'provider' => $supplier->name,
            ]);

            $generator->syncUnpaidPayables(
                $recurringPayable->refresh(),
                $oldAmount,
                $request->user()->getKey(),
                $request->user()->name,
            );
        });

        return redirect()->route('payables.index')->with('status', 'contract-updated');
    }

    public function toggle(Request $request, int $contract): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        if ($company === null) {
            return redirect()->route('dashboard');
        }

        abort_unless(
            $company->allows($company->membershipFor($request->user()), 'payables.manage_contract'),
            403,
        );

        $recurringPayable = $company->recurringPayables()->findOrFail($contract);
        $recurringPayable->update(['is_active' => ! $recurringPayable->is_active]);

        return back()->with('status', $recurringPayable->is_active ? 'contract-resumed' : 'contract-paused');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        abort_if($company === null, 404);
        abort_unless($company->allows($company->membershipFor($request->user()), 'units.create'), 403);

        $validated = $this->validateUnit($request, $company);
        $validated['name'] = $validated['unit_name'];
        unset($validated['unit_name']);

        $company->units()->create($validated);

        return redirect()->route('company.show')->with('status', 'unit-created');
    }

    public function update(Request $request, int $unitId): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        abort_if($company === null, 404);
        abort_unless($company->allows($company->membershipFor($request->user()), 'units.update'), 403);

        $unit = $company->units()->findOrFail($unitId);
        $validated = $this->validateUnit($request, $company, $unit);
        $validated['name'] = $validated['unit_name'];
        unset($validated['unit_name']);
        $unit->update($validated);

        return redirect()->route('company.show')->with('status', 'unit-updated');
    }

    public function toggle(Request $request, int $unitId): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        abort_if($company === null, 404);
        abort_unless($company->allows($company->membershipFor($request->user()), 'units.archive'), 403);

        $unit = $company->units()->findOrFail($unitId);
        $unit->update(['is_active' => ! $unit->is_active]);

        return redirect()->route('company.show')->with(
            'status',
            $unit->is_active ? 'unit-activated' : 'unit-archived',
        );
    }

    private function validateUnit(Request $request, Company $company, ?Unit $unit = null): array
    {
        $postalCode = $request->input('postal_code');

        if (is_string($postalCode)) {
            $postalCode = preg_replace('/[\s.-]/', '', $postalCode);
            $request->merge(['postal_code' => $postalCode === '' ? null : $postalCode]);
        }

        $state = $request->input('state');

        if (is_string($state)) {
            $request->merge(['state' => strtoupper(trim($state))]);
        }

        $nameRule = Rule::unique('units', 'name')->where('company_id', $company->getKey());

        if ($unit !== null) {
            $nameRule->ignore($unit->getKey());
        }

        return $request->validate([
            'unit_name' => ['required', 'string', 'max:255', $nameRule],
            'postal_code' => ['nullable', 'digits:8'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:32'],
            'complement' => ['nullable', 'string', 'max:255'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2', 'alpha'],
            'phone' => ['nullable', 'string', 'max:32'],
        ]);
    }
}

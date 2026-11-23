<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        abort_if($company === null, 404);
        abort_unless($company->allows($company->membershipFor($request->user()), 'suppliers.create'), 403);

        $company->suppliers()->create($this->validateSupplier($request, $company));

        return redirect()->route('company.show')->with('status', 'supplier-created');
    }

    public function update(Request $request, int $supplierId): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        abort_if($company === null, 404);
        abort_unless($company->allows($company->membershipFor($request->user()), 'suppliers.update'), 403);

        $supplier = $company->suppliers()->findOrFail($supplierId);
        $validated = $this->validateSupplier($request, $company, $supplier);

        DB::transaction(function () use ($supplier, $validated): void {
            $supplier->update($validated);
            $supplier->recurringPayables()->update(['provider' => $supplier->name]);
        });

        return redirect()->route('company.show')->with('status', 'supplier-updated');
    }

    public function toggle(Request $request, int $supplierId): RedirectResponse
    {
        $company = $request->user()->currentCompany();

        abort_if($company === null, 404);
        abort_unless($company->allows($company->membershipFor($request->user()), 'suppliers.archive'), 403);

        $supplier = $company->suppliers()->findOrFail($supplierId);
        $supplier->update(['is_active' => ! $supplier->is_active]);

        return redirect()->route('company.show')->with(
            'status',
            $supplier->is_active ? 'supplier-activated' : 'supplier-archived',
        );
    }

    private function validateSupplier(Request $request, Company $company, ?Supplier $supplier = null): array
    {
        $cnpj = $request->input('cnpj');

        if (is_string($cnpj)) {
            $cnpj = preg_replace('/[\s.\/-]/', '', $cnpj);
            $request->merge(['cnpj' => $cnpj === '' ? null : $cnpj]);
        }

        $cnpjRule = Rule::unique('suppliers', 'cnpj')->where('company_id', $company->getKey());

        if ($supplier !== null) {
            $cnpjRule->ignore($supplier->getKey());
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'cnpj' => ['nullable', 'digits:14', $cnpjRule],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}

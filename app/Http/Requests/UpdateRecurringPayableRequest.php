<?php

namespace App\Http\Requests;

use App\Models\Company;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRecurringPayableRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->user()->currentCompany();

        return $company !== null
            && $company->allows($company->membershipFor($this->user()), 'payables.manage_contract');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $company = $this->user()->currentCompany();
        $contract = $company?->recurringPayables()->find((int) $this->route('contract'));

        return [
            'description' => ['required', 'string', 'max:255'],
            'supplier_id' => ['required', 'integer', $this->activeOrCurrent('suppliers', $company, $contract?->supplier_id)],
            'unit_id' => ['required', 'integer', $this->activeOrCurrent('units', $company, $contract?->unit_id)],
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
        ];
    }

    private function activeOrCurrent(string $table, ?Company $company, ?int $currentId): mixed
    {
        return Rule::exists($table, 'id')
            ->where('company_id', $company?->getKey())
            ->where(fn ($query) => $query->where('is_active', true)->orWhere('id', $currentId));
    }
}

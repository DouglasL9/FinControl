<?php

namespace App\Http\Requests;

use App\Services\ReportBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->user()->currentCompany();

        return $company !== null
            && $company->allows($company->membershipFor($this->user()), 'reports.view');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $companyId = $this->user()->currentCompany()?->getKey();
        $statuses = $this->route('report') === 'contracts'
            ? ReportBuilder::CONTRACT_STATUSES
            : ReportBuilder::PAYABLE_STATUSES;

        return [
            'start' => ['nullable', 'date_format:Y-m-d'],
            'end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start'],
            'status' => ['nullable', Rule::in($statuses)],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')->where('company_id', $companyId)],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('company_id', $companyId)],
        ];
    }
}

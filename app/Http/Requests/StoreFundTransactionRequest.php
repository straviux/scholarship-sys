<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreFundTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'disbursement_type' => ['required', 'in:disbursements,payroll'],
            'explanation' => ['nullable', 'string'],
            'course' => ['nullable', 'string'],
            'academic_year' => ['nullable', 'string'],
            'semester' => ['nullable', 'string'],
            'payee_type' => ['required', 'in:scholar,school,individual'],
            'payee_name' => ['required', 'string'],
            'payee_address' => ['nullable', 'string'],
            'responsibility_center' => ['nullable'],
            'account_code' => ['nullable', 'string'],
            'particulars_name' => ['nullable', 'string'],
            'particulars_description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'obr_type' => ['nullable', 'in:REGULAR,FINANCIAL ASSISTANCE,REIMBURSEMENT'],
            'scholar_ids' => ['nullable', 'array'],
            'scholar_ids.*.profile_id' => ['nullable'],
            'scholar_ids.*.scholarship_record_id' => ['nullable'],
            'scholar_ids.*.name' => ['nullable', 'string'],
            'scholar_ids.*.amount' => ['nullable', 'numeric'],
            'year_level' => ['nullable', 'string'],
            'school' => ['nullable', 'string'],
            'grant_provision' => ['nullable', 'string'],
            'remarks' => ['nullable', 'string'],
            'transaction_status' => ['nullable', 'string'],
            'fiscal_year' => ['nullable', 'integer'],
            'obr_no' => ['nullable', 'string'],
            'date_obligated' => ['nullable', 'date'],
            'dv_no' => ['nullable', 'string'],
            'created_by' => ['nullable', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'created_by' => Auth::id(),
        ]);
    }
}

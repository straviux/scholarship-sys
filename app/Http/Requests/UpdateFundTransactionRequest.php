<?php

namespace App\Http\Requests;

use App\Models\SystemOption;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateFundTransactionRequest extends FormRequest
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
            'obr_type' => ['nullable', 'string', Rule::in($this->validObrTypes())],
            'scholar_ids' => ['nullable', 'array'],
            'scholar_ids.*.profile_id' => ['nullable'],
            'scholar_ids.*.scholarship_record_id' => ['nullable'],
            'scholar_ids.*.name' => ['nullable', 'string'],
            'scholar_ids.*.amount' => ['nullable', 'numeric'],
            'year_level' => ['nullable', 'string'],
            'school' => ['nullable', 'string'],
            'grant_provision' => ['nullable', 'string'],
            'scholarship_program_id' => ['nullable', 'integer', 'exists:scholarship_programs,id'],
            'remarks' => ['nullable', 'string'],
            'transaction_status' => ['nullable', 'in:No OBR,LOA,Irregular,Transferred,Claimed,Paid,On Process,Denied'],
            'fiscal_year' => ['nullable', 'integer'],
            'obr_no' => ['nullable', 'string'],
            'date_obligated' => ['nullable', 'date'],
            'dv_no' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'obr_type' => $this->normalizeObrType($this->input('obr_type')),
        ]);
    }

    private function validObrTypes(): array
    {
        return SystemOption::getByCategory('disbursement_type', false)
            ->pluck('value')
            ->filter()
            ->map(fn($value) => $this->normalizeObrType($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeObrType(mixed $value): ?string
    {
        $text = trim((string) $value);

        if ($text === '') {
            return null;
        }

        return (string) Str::of($text)
            ->lower()
            ->replaceMatches('/[\s-]+/', '_');
    }
}

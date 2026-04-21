<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertScholarLedgerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'other_assistance' => ['nullable', 'string', 'max:1000'],
            'licensure_examination_result' => ['nullable', 'string', 'max:1000'],
            'entries' => ['nullable', 'array'],
            'entries.*.year_level' => ['nullable', 'string', 'max:50'],
            'entries.*.academic_year' => ['nullable', 'string', 'max:50'],
            'entries.*.semester' => ['nullable', 'string', 'max:100'],
            'entries.*.date_obligated' => ['nullable', 'date'],
            'entries.*.obr_no' => ['nullable', 'string', 'max:100'],
            'entries.*.disbursement_type' => ['nullable', 'string', 'max:100'],
            'entries.*.amount' => ['nullable', 'numeric', 'min:0'],
            'entries.*.ros_months' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

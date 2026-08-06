<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GraduateAcademicEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'graduation_date' => ['required', 'date'],
            'graduation_remarks' => ['nullable', 'string', 'max:1000'],
            'latin_honor' => ['nullable', 'string', Rule::in(['cum_laude', 'magna_cum_laude', 'summa_cum_laude'])],
        ];
    }
}
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        ];
    }
}
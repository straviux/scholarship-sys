<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteAcademicEnrollmentTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'completion_date' => ['nullable', 'date'],
            'completion_remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
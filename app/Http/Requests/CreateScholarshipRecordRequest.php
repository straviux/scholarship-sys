<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateScholarshipRecordRequest extends FormRequest
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
        // $id = $this->route('id');
        $id = $this->route('courses') ?? null;

        // Check if this is a G12 record
        $isG12 = $this->input('year_level') === 'G12';

        return [
            "school_name" => [
                'nullable',
                'string',
                'max:500'
            ],
            "company_name" => [
                'nullable',
                'string',
                'max:500'
            ],

            "year_level" => [
                'required',
                'string',
                'max:20'
            ],
            "academic_year" => $isG12
                ? ['nullable', 'string', 'max:20']
                : ['required', 'string', 'max:20'],
            "academic_status" => [
                'nullable',
                'string',
                'max:20'
            ],
            "term" => $isG12
                ? ['nullable', 'string', 'max:50']
                : ['required', 'string', 'max:50'],
            "remarks" => [
                'nullable',
                'string',
                'max:500'
            ],
            "start_date" => [
                'nullable',
                'date',
            ],
            "end_date" => [
                'nullable',
                'date',
            ],
            "date_filed" => [
                'nullable',
                'date',
            ],
            "date_approved" => [
                'nullable',
                'date',
            ],
            "is_active" => [
                'boolean'
            ],
            "profile_id" => [
                'required',
                'exists:scholarship_profiles,profile_id'
            ],
            "course_id" => $isG12
                ? ['nullable', 'exists:courses,id']
                : ['required', 'exists:courses,id'],
            "program_id" => $isG12
                ? ['nullable', 'exists:scholarship_programs,id']
                : ['required', 'exists:scholarship_programs,id'],
            "school_id" => $isG12
                ? ['nullable', 'exists:schools,id']
                : ['required', 'exists:schools,id'],
            "approval_status" => [
                'nullable',
                'string',
                'max:50'
            ],
            "grant_provision" => [
                'nullable',
                'string',
                'max:50'
            ],
            "yakap_category" => [
                'nullable',
                'string',
                'max:50'
            ],
            "yakap_location" => [
                'nullable',
                'string',
                'max:255'  // Store location name only (school or municipality)
            ],
            "unified_status" => [
                'nullable',
                'string',
                'max:50',
                'in:pending,approved,active,completed,denied,withdrawn,loa,suspended,unknown'
            ],

        ];
    }


    protected function prepareForValidation()
    {
        $this->merge([
            'created_by' => $this->user() ? $this->user()->id : null,
        ]);
    }
}

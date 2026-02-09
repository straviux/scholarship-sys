<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateScholarshipProfileRequest extends FormRequest
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
            "first_name" => [
                'required',
                'string',
                'max:255',
            ],
            "last_name" => [
                'required',
                'string',
                'max:255',
            ],
            "middle_name" => [
                'nullable',
                'string',
                'max:255'
            ],
            "extension_name" => [
                'nullable',
                'string',
                'max:50'
            ],
            "father_name" => [
                'nullable',
                'string',
                'max:255'
            ],
            "father_occupation" => [
                'nullable',
                'string',
                'max:255'
            ],
            "father_birthdate" => [
                'nullable',
                'date',
            ],
            "father_contact_no" => [
                'nullable',
                'string',
                'max:50'
            ],
            "mother_name" => [
                'nullable',
                'string',
                'max:255'
            ],
            "mother_occupation" => [
                'nullable',
                'string',
                'max:255'
            ],
            "mother_birthdate" => [
                'nullable',
                'date',
            ],
            "mother_contact_no" => [
                'nullable',
                'string',
                'max:50'
            ],
            "municipality" => [
                'nullable',
                'string',
                'max:255'
            ],
            "barangay" => [
                'nullable',
                'string',
                'max:255'
            ],
            "address" => [
                'nullable',
                'string',
                'max:500'
            ],
            "temporary_municipality" => [
                'nullable',
                'string',
                'max:255'
            ],
            "temporary_barangay" => [
                'nullable',
                'string',
                'max:255'
            ],
            "temporary_address" => [
                'nullable',
                'string',
                'max:500'
            ],
            "birthdate" => [
                'nullable',
                'date',
            ],
            "contact_no" => [
                'nullable',
                'string',
                'max:50'
            ],
            "contact_no_2" => [
                'nullable',
                'string',
                'max:50'
            ],
            "email" => [
                'nullable',
                'string',
                'max:100'
            ],
            "date_of_birth" => [
                'nullable',
                'date',
            ],
            "gender" => [
                'nullable',
                'string',
                'max:10'
            ],
            "place_of_birth" => [
                'nullable',
                'string',
                'max:50'
            ],
            "civil_status" => [
                'nullable',
                'string',
                'max:20'
            ],
            "religion" => [
                'nullable',
                'string',
                'max:50'
            ],
            "indigenous_group" => [
                'nullable',
                'string',
                'max:100'
            ],
            // "applied_year_level" => [
            //     'nullable',
            //     'string',
            //     'max:10'
            // ],
            // "applied_course" => [
            //     'nullable',
            //     'string',
            //     'max:100'
            // ],
            // "applied_school" => [
            //     'nullable',
            //     'string',
            //     'max:100'
            // ],
            "is_active" => [
                'boolean'
            ],
            // is_on_waiting_list is now managed through scholarship_records.application_status
            "remarks" => [
                'nullable',
                'string',
                'max:500'
            ],
            "date_filed" => [
                'nullable',
                'date',
            ],
            "date_approved" => [
                'nullable',
                'date',
            ],
            // application_status, application_status_remarks, application_status_date are now in scholarship_records
            "guardian_name" => [
                'nullable',
                'string',
                'max:100'
            ],
            "guardian_relationship" => [
                'nullable',
                'string',
                'max:50'
            ],
            "guardian_contact_no" => [
                'nullable',
                'string',
                'max:50'
            ],
            "guardian_occupation" => [
                'nullable',
                'string',
                'max:100'
            ],
            "parents_guardian_gross_monthly_income" => [
                'nullable',
                'numeric',
                'min:0'
            ],
            // Academic Information IDs (preferred)
            "course_id" => [
                'nullable',
                'integer',
                'exists:courses,id'
            ],
            "school_id" => [
                'nullable',
                'integer',
                'exists:schools,id'
            ],
            "program_id" => [
                'nullable',
                'integer',
                'exists:scholarship_programs,id'
            ],
            // Academic Information Names (fallback)
            "course" => [
                'nullable',
                'string',
                'max:255'
            ],
            "school" => [
                'nullable',
                'string',
                'max:255'
            ],
            "program" => [
                'nullable',
                'string',
                'max:255'
            ],
            "year_level" => [
                'nullable',
                'string',
                'max:50'
            ],
            "term" => [
                'nullable',
                'string',
                'max:50'
            ],
            "academic_year" => [
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
                'max:255'
            ],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $last = $this->input('last_name');
            $first = $this->input('first_name');
            $middle = $this->input('middle_name');
            $exists =
                \App\Models\ScholarshipProfile::where('last_name', $last)
                ->where('first_name', $first)
                ->where('middle_name', $middle)
                ->exists();
            if ($exists) {
                $validator->errors()->add('last_name', 'A profile with the same last name, first name, and middle name already exists.');
            }
        });
    }

    // public function messages()
    // {
    //     return [
    //         'name.unique' => 'Course already exists.',
    //         'name.required' => 'Course name cannot be blank'
    //     ];
    // }

    protected function prepareForValidation()
    {
        // Merge the 'created_by' field into the request data
        $this->merge([
            'created_by' => $this->user() ? $this->user()->id : null,
        ]);
    }
}

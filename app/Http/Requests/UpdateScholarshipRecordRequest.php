<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScholarshipRecordRequest extends FormRequest
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
            "academic_year" => [
                'required',
                'string',
                'max:20'
            ],
            "academic_status" => [
                'nullable',
                'string',
                'max:20'
            ],
            "term" => [
                'required',
                'string',
                'max:50'
            ],
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
            "course_id" => [
                'required',
                'exists:courses,id'
            ],
            "program_id" => [
                'required',
                'exists:scholarship_programs,id'
            ],
        ];
    }
}

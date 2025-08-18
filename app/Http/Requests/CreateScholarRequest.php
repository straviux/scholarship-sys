<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateScholarRequest extends FormRequest
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
        return [
            "school_name" => [
                'nullable',
                'string',
                'max:500'
            ],
            "program_name" => [
                'nullable',
                'string',
                'max:500'
            ],
            "course_name" => [
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
            "is_ongoing" => [
                'boolean'
            ],
            "is_active" => [
                'boolean'
            ],
            "applicant_id" => [
                'required',
                'exists:applicants,id'
            ],

            "scholarship_program_id" => [
                'required',
                'exists:scholarship_programs,id'
            ],
            "course_id" => [
                'required',
                'exists:courses,id'
            ],

        ];
    }

    // public function messages()
    // {
    //     return [
    //         'name.unique' => 'Program already exists.',
    //         'name.required' => 'Program name cannot be blank'
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

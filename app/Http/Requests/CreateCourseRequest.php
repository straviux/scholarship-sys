<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateCourseRequest extends FormRequest
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
            "name" => [
                'required',
                'string',
                'max:255',
                Rule::unique('courses', 'name')->ignore($id)
            ],
            "shortname" => [
                'nullable',
                'string',
                'max:50'
            ],
            "field_of_study" => [
                'nullable',
                'string',
                'max:255'
            ],
            "description" => [
                'nullable',
                'string',
                'max:500'
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
            "scholarship_program_id" => [
                'required',
                'exists:scholarship_programs,id'
            ],

        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'Course already exists.',
            'name.required' => 'Course name cannot be blank'
        ];
    }

    protected function prepareForValidation()
    {
        // Merge the 'created_by' field into the request data
        $this->merge([
            'created_by' => $this->user() ? $this->user()->id : null,
        ]);
    }
}

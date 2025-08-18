<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicantRequest extends FormRequest
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
        // $id = $this->route('applicants') ?? null;
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
                'max:20'
            ],
            "email" => [
                'nullable',
                'string',
                'max:100'
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
            "gender" => [
                'nullable',
                'string',
                'max:10'
            ],
            "remarks" => [
                'nullable',
                'string',
                'max:500'
            ],
        ];
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
        // Merge the 'updated_by' field into the request data
        $this->merge([
            'updated_by' => $this->user() ? $this->user()->id : null,
        ]);
    }
}

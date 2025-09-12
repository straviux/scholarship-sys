<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScholarshipProfileRequest extends FormRequest
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
                'max:50'
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
            "date_filed" => [
                'nullable',
                'date',
            ],
            "is_active" => [
                'boolean',
            ],
            "is_on_waiting_list" => [
                'boolean',
            ],
            "application_status" => [
                'integer'
            ],
            "application_status_date" => [
                'nullable',
                'date',
            ],
            "application_status_remarks" => [
                'nullable',
                'string',
                'max:100'
            ],
            "applied_year_level" => [
                'nullable',
                'string',
                'max:10'
            ],
            "applied_course" => [
                'nullable',
                'string',
                'max:100'
            ],
            "applied_school" => [
                'nullable',
                'string',
                'max:100'
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

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         $last = $this->input('last_name');
    //         $first = $this->input('first_name');
    //         $middle = $this->input('middle_name');
    //         $profileId = $this->route('profile') ? (is_object($this->route('profile')) ? $this->route('profile')->profile_id : $this->route('profile')) : null;
    //         $exists = \App\Models\ScholarshipProfile::where('last_name', $last)
    //             ->where('first_name', $first)
    //             ->where('middle_name', $middle)
    //             ->when($profileId, function ($query, $profileId) {
    //                 $query->where('profile_id', '!=', $profileId);
    //             })
    //             ->exists();
    //         if ($exists) {
    //             $validator->errors()->add('last_name', 'A profile with the same last name, first name, and middle name already exists.');
    //         }
    //     });
    // }
}

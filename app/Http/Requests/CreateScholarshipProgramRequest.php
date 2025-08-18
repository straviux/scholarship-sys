<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateScholarshipProgramRequest extends FormRequest
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
        $id = $this->route('scholarshipprograms') ?? null;
        return [
            "name" => [
                'required',
                'string',
                'max:255',
                Rule::unique('scholarship_programs', 'name')->ignore($id)
            ],
            "shortname" => [
                'nullable',
                'string',
                'max:50'
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
            ]

        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'Program already exists.',
            'name.required' => 'Program name cannot be blank'
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

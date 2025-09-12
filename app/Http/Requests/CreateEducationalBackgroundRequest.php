<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateEducationalBackgroundRequest extends FormRequest
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
            "school_name" => [
                'required',
                'string',
                'max:255',
            ],
            "level" => [
                'required',
                'string',
                'max:255',
            ],
            "course" => [
                'nullable',
                'string',
                'max:255'
            ],
            "academic_honors" => [
                'nullable',
                'string',
                'max:255'
            ],
            "start_date" => [
                'nullable',
                'date_format:Y',
                'before_or_equal:end_date',

            ],
            "end_date" => [
                'nullable',
                'date_format:Y',
                'after_or_equal:start_date',
            ],
            "remarks" => [
                'nullable',
                'string',
                'max:255'
            ],
            "profile_id" => [
                'required',
                'exists:scholarship_profiles,profile_id'
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
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateVoterProfileRequest extends FormRequest
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
        $id = $this->route('votersprofile') ?? null;
        return [
            "name" => [
                'required',
                'string',
                'max:255',
                Rule::unique('voter_profiles', 'name')->ignore($id)
            ],
            "firstname" => [
                'required',
                'string',
                'max:255'
            ],
            "lastname" => [
                'required',
                'string',
                'max:255'
            ],
            "middlename" => [
                'nullable',
                'string',
                'max:255'
            ],

            "barangay" => [
                'required',
                'string',
                'max:255'
            ],
            "precinct_no" => [
                'required',
                'string',
                'max:255'
            ],
            "position" => [
                'required',
                'string',
                'max:255'
            ],
            "contact_no" => [
                'nullable',
                'string',
                'max:255'
            ],
            "birthdate" => [
                'nullable',
                'string',
                'max:255'
            ],
            "gender" => [
                'nullable',
                'string',
                'max:255'
            ],
            "remarks" => [
                'nullable',
                'string',
                'max:500'
            ],
            "purok" => [
                'nullable',
                'string',
                'max:255'
            ],
            "parent_id" => [
                'nullable',
                'exists:voter_profiles,id'
            ],



        ];
    }

    public function messages()
    {
        return [
            'name.unique' => 'Profile already exists.',
            'name.required' => 'Name cannot be blank'
        ];
    }
}

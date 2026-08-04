<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatePermissionRequest extends FormRequest
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
            "name" => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*\.[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('permissions', 'name')->ignore($this->route('permission'))
            ],
            'description' => ['nullable', 'string', 'max:255']
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Permission name must follow the "resource.action" format using lowercase letters and hyphens (e.g. reports.view, return-of-service.export).',
        ];
    }
}

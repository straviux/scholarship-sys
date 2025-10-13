<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JpmStatusUpdateRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'is_jpm_member' => 'nullable|boolean',
            'is_mother_jpm' => 'nullable|boolean',
            'is_father_jpm' => 'nullable|boolean',
            'is_guardian_jpm' => 'nullable|boolean',
        ];
    }
}

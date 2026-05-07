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
        return $this->user()?->can('jpm.manage') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $booleanFields = [
            'is_jpm_member',
            'is_mother_jpm',
            'is_father_jpm',
            'is_guardian_jpm',
            'is_not_jpm',
            'is_unrenewed_jpm',
        ];
        $normalized = [];

        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $normalized[$field] = $this->boolean($field);
            }
        }

        if (($normalized['is_not_jpm'] ?? false) === true) {
            $normalized['is_jpm_member'] = false;
            $normalized['is_mother_jpm'] = false;
            $normalized['is_father_jpm'] = false;
            $normalized['is_guardian_jpm'] = false;
            $normalized['is_unrenewed_jpm'] = false;
        } elseif (($normalized['is_jpm_member'] ?? false)
            || ($normalized['is_mother_jpm'] ?? false)
            || ($normalized['is_father_jpm'] ?? false)
            || ($normalized['is_guardian_jpm'] ?? false)
            || ($normalized['is_unrenewed_jpm'] ?? false)
        ) {
            $normalized['is_not_jpm'] = false;
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'is_jpm_member' => ['nullable', 'boolean'],
            'is_mother_jpm' => ['nullable', 'boolean'],
            'is_father_jpm' => ['nullable', 'boolean'],
            'is_guardian_jpm' => ['nullable', 'boolean'],
            'is_not_jpm' => ['nullable', 'boolean'],
            'is_unrenewed_jpm' => ['nullable', 'boolean'],
            'jpm_remarks' => ['nullable', 'string', 'max:255'],
        ];
    }
}

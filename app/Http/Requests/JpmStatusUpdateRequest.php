<?php

namespace App\Http\Requests;

use App\Services\JpmTaggingService;
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
        $normalized = [];

        foreach (JpmTaggingService::STATUS_BOOLEAN_FIELDS as $field) {
            if ($this->has($field)) {
                $normalized[$field] = $this->boolean($field);
            }
        }

        if ($normalized !== []) {
            $this->merge(app(JpmTaggingService::class)->normalizeAttributes($normalized));
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

<?php

namespace App\Http\Requests;

/**
 * FormRequest for requirement file uploads
 */
class RequirementUploadRequest extends MobileUploadRequest
{
    public function rules(): array
    {
        return parent::rules();
    }

    public function messages(): array
    {
        return parent::messages();
    }
}

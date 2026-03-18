<?php

namespace App\Http\Requests;

/**
 * FormRequest for scholarship record file uploads
 */
class ScholarshipRecordUploadRequest extends MobileUploadRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'attachment_name' => ['required', 'string', 'max:255'],
            'page_number' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'attachment_name.required' => 'Attachment name is required',
            'attachment_name.string' => 'Attachment name must be text',
            'attachment_name.max' => 'Attachment name cannot exceed 255 characters',
            'page_number.integer' => 'Page number must be a number',
            'page_number.min' => 'Page number must be at least 1',
        ]);
    }
}

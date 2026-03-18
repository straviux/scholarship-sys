<?php

namespace App\Http\Requests;

/**
 * FormRequest for disbursement file uploads
 */
class DisbursementUploadRequest extends MobileUploadRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'attachment_type' => ['required', 'in:voucher,cheque,receipt'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'attachment_type.required' => 'Attachment type is required',
            'attachment_type.in' => 'Invalid attachment type. Allowed: voucher, cheque, receipt',
        ]);
    }
}

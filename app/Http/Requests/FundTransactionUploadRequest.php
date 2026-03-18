<?php

namespace App\Http\Requests;

/**
 * FormRequest for fund transaction file uploads
 */
class FundTransactionUploadRequest extends MobileUploadRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'document_type' => ['required', 'in:obr,dv_payroll,los,cheque'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'document_type.required' => 'Document type is required',
            'document_type.in' => 'Invalid document type. Allowed: obr, dv_payroll, los, cheque',
        ]);
    }
}

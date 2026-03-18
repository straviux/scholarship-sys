<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\File;

/**
 * Base FormRequest for all mobile file uploads
 * 
 * Handles common validation for file uploads across different entity types
 */
class MobileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Token validation happens in controller
        return true;
    }

    /**
     * Base validation rules (used by all mobile upload requests)
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'max:25600',  // 25MB max
                File::types(['jpg', 'jpeg', 'png', 'pdf']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File is required',
            'file.file' => 'Please upload a valid file',
            'file.max' => 'File must not exceed 25MB',
            'file.mimes' => 'File must be JPG, PNG, or PDF',
        ];
    }

    protected function prepareForValidation()
    {
        // Auto-set user_id for audit trail
        $this->merge([
            'user_id' => Auth::id(),
            'uploaded_at' => now(),
        ]);
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateFundTransactionStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'transaction_status' => ['nullable', 'in:No OBR,LOA,Irregular,Transferred,Claimed,Paid,On Process,Denied'],
            'remarks' => ['nullable', 'string'],
        ];
    }
}

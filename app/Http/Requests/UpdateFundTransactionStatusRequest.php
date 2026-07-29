<?php

namespace App\Http\Requests;

use App\Models\FundTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateFundTransactionStatusRequest extends FormRequest
{
    /**
     * Statuses that represent an exception/deviation from the normal flow
     * and therefore require the user to record a reason in remarks.
     */
    public const REASON_REQUIRED_STATUSES = ['Cancelled', 'Replacement', 'Denied', 'Irregular', 'Transferred', 'LOA'];

    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $status = $this->input('transaction_status');
        $voucher = FundTransaction::find($this->route('id'));
        $hasObrNo = $voucher && trim((string) $voucher->obr_no) !== '';

        return [
            'transaction_status' => ['nullable', 'in:No OBR,LOA,Irregular,Transferred,Claimed,Paid,On Process,Denied,Replacement,Cancelled'],
            'remarks' => [
                Rule::requiredIf(in_array($status, self::REASON_REQUIRED_STATUSES, true)),
                'string',
            ],
            'status_updated_at' => ['required', 'date'],
            'obr_no' => [
                Rule::requiredIf(!$hasObrNo && $status !== 'No OBR'),
                'string',
            ],
        ];
    }
}

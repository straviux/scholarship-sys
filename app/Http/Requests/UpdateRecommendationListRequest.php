<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateRecommendationListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'report_title' => ['nullable', 'string', 'max:255'],
            'request_date' => ['nullable', 'date'],
            'paper_size' => ['nullable', 'in:A4,Letter,Legal'],
            'orientation' => ['nullable', 'in:portrait,landscape'],
            'budget_program' => ['nullable', 'string', 'max:255'],
            'highlight_jpm_members' => ['nullable', 'boolean'],
            'include_endorsed_by' => ['nullable', 'boolean'],
            'prepared_by' => ['nullable', 'string', 'max:255'],
            'prepared_by_position' => ['nullable', 'string', 'max:255'],
            'prepared_by_office' => ['nullable', 'string', 'max:255'],
            'approved_by' => ['nullable', 'string', 'max:255'],
            'approved_by_position' => ['nullable', 'string', 'max:255'],
            'budget_allocation' => ['nullable', 'array'],
            'budget_allocation.key' => ['nullable', 'string', 'max:100'],
            'budget_allocation.particular_id' => ['nullable', 'integer'],
            'budget_allocation.particular_name' => ['nullable', 'string', 'max:255'],
            'budget_allocation.description' => ['nullable', 'string', 'max:255'],
            'budget_allocation.program_id' => ['nullable', 'integer'],
            'budget_allocation.program' => ['nullable', 'string', 'max:255'],
            'budget_allocation.programs' => ['nullable', 'array'],
            'budget_allocation.programs.*' => ['nullable', 'string', 'max:255'],
            'budget_allocation.program_ids' => ['nullable', 'array'],
            'budget_allocation.program_ids.*' => ['nullable', 'integer'],
            'budget_allocation.calendar_year' => ['nullable', 'integer'],
            'budget_allocation.rc_code' => ['nullable', 'string', 'max:100'],
            'budget_allocation.rc_name' => ['nullable', 'string', 'max:255'],
            'budget_allocation.fiscal_year' => ['nullable', 'integer'],
            'budget_allocation.total_allotment' => ['nullable', 'numeric'],
            'budget_allocation.disbursed' => ['nullable', 'numeric'],
            'budget_allocation.remaining' => ['nullable', 'numeric'],
            'budget_allocation.date_start' => ['nullable', 'date'],
            'budget_allocation.date_end' => ['nullable', 'date'],
            'budget_allocation.approved_scholars_to_date' => ['nullable', 'integer'],
            'budget_allocation.approved_scholars' => ['nullable', 'array'],
            'budget_allocation.approved_scholars.*.profile_id' => ['nullable'],
            'budget_allocation.approved_scholars.*.program_id' => ['nullable'],
            'budget_allocation.approved_scholars.*.name' => ['nullable', 'string', 'max:255'],
            'budget_allocation.approved_scholars.*.program' => ['nullable', 'string', 'max:255'],
            'budget_allocation.approved_scholars.*.program_name' => ['nullable', 'string', 'max:255'],
            'budget_allocation.approved_scholars.*.program_shortname' => ['nullable', 'string', 'max:255'],
            'budget_allocation.approved_scholars.*.date_approved' => ['nullable', 'date'],
            'budget_allocation.approved_scholars.*.status' => ['nullable', 'string', 'max:255'],
        ];
    }
}
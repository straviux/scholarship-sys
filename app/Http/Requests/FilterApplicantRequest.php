<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FilterApplicantRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'program' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'school' => 'nullable|string',
            'year_level' => 'nullable|string',
            'course' => 'nullable|string',
            'remarks' => 'nullable|string',
            'municipality' => 'nullable|string',
            'name' => 'nullable|string',
            'parent_name' => 'nullable|string',
            'global_search' => 'nullable|string',
            'sort' => 'nullable|array',
            'sort.date_filed' => 'nullable|in:asc,desc',
            'sort.last_name' => 'nullable|in:asc,desc',
            'sort.school' => 'nullable|in:asc,desc',
            'sort.course' => 'nullable|in:asc,desc',
            'sort.year_level' => 'nullable|in:asc,desc',
            'records' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }
}

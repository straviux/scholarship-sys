<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportJpmCsvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('jpm.manage') ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'csv_file' => [
                'required',
                'file',
                'max:10240',
                'mimetypes:text/csv,text/plain,application/csv,application/vnd.ms-excel,text/comma-separated-values,text/x-comma-separated-values,application/octet-stream',
            ],
        ];
    }
}

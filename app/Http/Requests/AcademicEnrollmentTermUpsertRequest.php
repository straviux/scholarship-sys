<?php

namespace App\Http\Requests;

use App\Models\AcademicEnrollment;
use App\Models\AcademicEnrollmentTerm;
use Illuminate\Foundation\Http\FormRequest;

class AcademicEnrollmentTermUpsertRequest extends FormRequest
{
    private const OPEN_TERM_STATUSES = ['pending', 'active'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isG12 = strtoupper((string) $this->input('year_level')) === 'G12';

        return [
            'year_level' => ['required', 'string', 'max:20'],
            'academic_year' => $isG12 ? ['nullable', 'string', 'max:20'] : ['required', 'string', 'max:20'],
            'term' => $isG12 ? ['nullable', 'string', 'max:50'] : ['required', 'string', 'max:50'],
            'date_filed' => ['nullable', 'date'],
            'date_approved' => ['nullable', 'date'],
            'unified_status' => ['nullable', 'string', 'max:50', 'in:pending,approved,active,completed,denied,withdrawn,loa,suspended,unknown'],
            'grant_provision' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $enrollment = $this->resolveEnrollment();
            $incomingStatus = strtolower((string) ($this->input('unified_status') ?? ''));
            $currentTerm = $this->route('academicEnrollmentTerm');

            if (!$enrollment) {
                return;
            }

            $query = AcademicEnrollmentTerm::query()
                ->where('academic_enrollment_id', $enrollment->id);

            $this->filled('academic_year')
                ? $query->where('academic_year', $this->input('academic_year'))
                : $query->whereNull('academic_year');

            $this->filled('term')
                ? $query->where('term', $this->input('term'))
                : $query->whereNull('term');

            if ($currentTerm instanceof AcademicEnrollmentTerm) {
                $query->whereKeyNot($currentTerm->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('term', 'An academic term for this school year and semester already exists.');
            }

            if (!in_array($incomingStatus, self::OPEN_TERM_STATUSES, true)) {
                return;
            }

            $openTermQuery = AcademicEnrollmentTerm::query()
                ->where('academic_enrollment_id', $enrollment->id)
                ->whereIn('unified_status', self::OPEN_TERM_STATUSES);

            if ($currentTerm instanceof AcademicEnrollmentTerm) {
                $openTermQuery->whereKeyNot($currentTerm->id);
            }

            if ($openTermQuery->exists()) {
                $validator->errors()->add(
                    'unified_status',
                    'Only one pending or active term is allowed per academic enrollment. Resolve the latest open term first.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'academic_year' => $this->normalizeNullableString($this->input('academic_year')),
            'term' => $this->normalizeNullableString($this->input('term')),
            'grant_provision' => $this->normalizeNullableString($this->input('grant_provision')),
            'remarks' => $this->normalizeNullableString($this->input('remarks')),
            'unified_status' => $this->normalizeNullableString($this->input('unified_status')),
        ]);
    }

    private function normalizeNullableString(mixed $value): mixed
    {
        if ($value === '') {
            return null;
        }

        return $value;
    }

    private function resolveEnrollment(): ?AcademicEnrollment
    {
        $enrollment = $this->route('academicEnrollment');
        if ($enrollment instanceof AcademicEnrollment) {
            return $enrollment;
        }

        $term = $this->route('academicEnrollmentTerm');
        if ($term instanceof AcademicEnrollmentTerm) {
            return $term->enrollment;
        }

        return null;
    }
}

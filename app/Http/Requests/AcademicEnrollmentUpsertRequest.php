<?php

namespace App\Http\Requests;

use App\Models\AcademicEnrollment;
use App\Models\ScholarshipProfile;
use Illuminate\Foundation\Http\FormRequest;

class AcademicEnrollmentUpsertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'program_id' => ['nullable', 'exists:scholarship_programs,id'],
            'school_id' => ['required', 'exists:schools,id'],
            'course_id' => ['nullable', 'exists:courses,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $profileId = $this->resolveProfileId();

            if (!$profileId || !$this->filled('school_id')) {
                return;
            }

            $query = AcademicEnrollment::query()
                ->where('profile_id', $profileId)
                ->where('school_id', $this->input('school_id'));

            $this->filled('course_id')
                ? $query->where('course_id', $this->input('course_id'))
                : $query->whereNull('course_id');

            $currentEnrollment = $this->route('academicEnrollment');
            if ($currentEnrollment instanceof AcademicEnrollment) {
                $query->whereKeyNot($currentEnrollment->id);
            }

            if ($query->exists()) {
                $validator->errors()->add('course_id', 'An academic enrollment for this school and course already exists.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'program_id' => $this->normalizeNullableId($this->input('program_id')),
            'school_id' => $this->normalizeNullableId($this->input('school_id')),
            'course_id' => $this->normalizeNullableId($this->input('course_id')),
        ]);
    }

    private function normalizeNullableId(mixed $value): mixed
    {
        if ($value === '' || $value === 'null') {
            return null;
        }

        return $value;
    }

    private function resolveProfileId(): ?string
    {
        $profile = $this->route('profile');
        if ($profile instanceof ScholarshipProfile) {
            return $profile->profile_id;
        }

        $academicEnrollment = $this->route('academicEnrollment');
        if ($academicEnrollment instanceof AcademicEnrollment) {
            return $academicEnrollment->profile_id;
        }

        return null;
    }
}
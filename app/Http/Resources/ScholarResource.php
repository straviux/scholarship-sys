<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScholarResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'applicant_id' => $this->applicant_id,
            'course_id' => $this->course_id,
            // 'course_name' => $this->course->name,
            'course' => $this->course ? [
                'id' => $this->course->id,
                'name' => $this->course->name,
            ] : null,
            'term' => $this->term,
            'year_level' => $this->year_level,
            'academic_year' => $this->academic_year,
            'school_name' => $this->school_name,
            'company_name' => $this->company_name,
            'remarks' => $this->remarks,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'scholarship_program_id' => $this->scholarship_program_id,
            'scholarship_status_id' => $this->scholarship_status_id,
            'scholarship_status' => $this->scholarshipStatus ? [
                'id' => $this->scholarshipStatus->id,
                'remarks' => $this->scholarshipStatus->remarks,
                'status' => $this->scholarshipStatus->status,
                'status_id' => $this->scholarshipStatus->status_id,
            ] : null,
            // 'program_name' => $this->program_name,
            'academic_status' => $this->academic_status,
            'scholarship_program' => $this->scholarshipProgram ? [
                'id' => $this->scholarshipProgram->id,
                'name' => $this->scholarshipProgram->name,
            ] : null,
            'created_by' => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ] : null,
            'updated_by' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
            ] : null,
            'is_active' => $this->is_active,
            // 'is_ongoing' => $this->is_ongoing,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'date_filed' => $this->date_filed,
        ];
    }
}

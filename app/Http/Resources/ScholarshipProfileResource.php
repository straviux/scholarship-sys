<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScholarshipProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'profile_id' => $this->profile_id,
            'sequence_number' => $this->sequence_number ?? null,
            'sequence_number_by_course' => $this->sequence_number_by_course ?? null,
            'sequence_number_by_school_course' => $this->sequence_number_by_school_course ?? null,
            'daily_sequence_number' => $this->daily_sequence_number ?? null,
            'unique_id' => $this->unique_id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'extension_name' => $this->extension_name,
            'father_name' => $this->father_name,
            'father_occupation' => $this->father_occupation,
            'father_birthdate' => $this->father_birthdate,
            'father_contact_no' => $this->father_contact_no,
            'mother_name' => $this->mother_name,
            'mother_occupation' => $this->mother_occupation,
            'mother_birthdate' => $this->mother_birthdate,
            'mother_contact_no' => $this->mother_contact_no,
            'municipality' => $this->municipality,
            'barangay' => $this->barangay,
            'address' => $this->address,
            'temporary_municipality' => $this->temporary_municipality,
            'temporary_barangay' => $this->temporary_barangay,
            'temporary_address' => $this->temporary_address,
            'birthdate' => $this->birthdate,
            'contact_no' => $this->contact_no,
            'contact_no_2' => $this->contact_no_2,
            'civil_status' => $this->civil_status,
            'religion' => $this->religion,
            'email' => $this->email,
            'gender' => $this->gender,
            'remarks' => $this->remarks,
            // 'applied_year_level' => $this->applied_year_level,
            // 'applied_course' => $this->applied_course,
            // 'applied_school' => $this->applied_school,
            'is_active' => $this->is_active,
            'is_jpm_member' => $this->is_jpm_member,
            'is_jpm_leader' => $this->is_jpm_leader,
            'is_father_jpm' => $this->is_father_jpm,
            'is_mother_jpm' => $this->is_mother_jpm,
            'is_guardian_jpm' => $this->is_guardian_jpm,
            'is_not_jpm' => $this->is_not_jpm,
            // is_on_waiting_list is now managed through scholarship_records.application_status (pending status)
            'jpm_remarks' => $this->jpm_remarks,
            'date_filed' => $this->date_filed,
            'created_by' => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ] : null,
            'updated_by' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
            ] : null,
            'created_at' => $this->created_at ? $this->created_at->format('Y-m-d') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('Y-m-d') : null,
            'educational_backgrounds' => $this->educationalBackgrounds ? $this->educationalBackgrounds : null,
            'ongoing_scholarship_grant' => $this->ongoingScholarshipGrant ? $this->ongoingScholarshipGrant : null,
            'scholarship_grant' => $this->scholarshipGrant ? $this->scholarshipGrant : null,
            // application_status, application_status_remarks, application_status_date are now in scholarship_records
            'guardian_name' => $this->guardian_name,
            'guardian_relationship' => $this->guardian_relationship,
            'guardian_contact_no' => $this->guardian_contact_no,
            'guardian_occupation' => $this->guardian_occupation,
            'parents_guardian_gross_monthly_income' => $this->parents_guardian_gross_monthly_income,
            'priority_level' => $this->priority_level,
            'priority_reason' => $this->priority_reason,
            'priority_assigned_at' => $this->priority_assigned_at,
            'priority_assigned_by' => $this->priorityAssignedBy ? [
                'id' => $this->priorityAssignedBy->id,
                'name' => $this->priorityAssignedBy->name,
            ] : null,
        ];
    }
}

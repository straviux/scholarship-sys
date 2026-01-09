<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScholarshipRecordResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'extension_name' => $this->extension_name,
            'father_name' => $this->father_name,
            'father_occupation' => $this->father_occupation,
            'father_birthdate' => $this->father_birthdate,
            'father_contact_no' => $this->father_contact_no,
            'municipality' => $this->municipality,
            'barangay' => $this->barangay,
            'address' => $this->address,
            'temporary_municipality' => $this->temporary_municipality,
            'temporary_barangay' => $this->temporary_barangay,
            'temporary_address' => $this->temporary_address,
            'birthdate' => $this->birthdate,
            'contact_no' => $this->contact_no,
            'civil_status' => $this->civil_status,
            'religion' => $this->religion,
            'email' => $this->email,
            'gender' => $this->gender,
            'remarks' => $this->remarks,
            'applied_year_level' => $this->applied_year_level,
            'applied_course' => $this->applied_course,
            'applied_school' => $this->applied_school,
            'is_active' => $this->is_active,
            'application_status' => $this->application_status, // 0=Waiting List, 1=Active, 2=Denied
            'date_filed' => $this->date_filed,
            'date_approved' => $this->date_approved,
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
            'pending_scholarship_grant' => $this->pendingScholarshipGrant ? $this->pendingScholarshipGrant : null,
            // Note: application_status fields removed - use scholarship_status instead
        ];
    }
}

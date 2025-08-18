<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicantResource extends JsonResource
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
            'firt_name' => $this->first_name,
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
            'is_approve' => $this->is_approve,
            'is_denied' => $this->is_denied,
            'is_pending' => $this->is_pending,
            'is_active' => $this->is_active,
            'created_by' => $this->createdBy ? [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ] : null,
            'updated_by' => $this->updatedBy ? [
                'id' => $this->updatedBy->id,
                'name' => $this->updatedBy->name,
            ] : null,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

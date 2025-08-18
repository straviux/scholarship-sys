<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
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
            'name' => $this->name,
            'shortname' => $this->shortname,
            'description' => $this->description,
            'remarks' => $this->remarks,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'scholarship_program_id' => $this->scholarship_program_id,
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

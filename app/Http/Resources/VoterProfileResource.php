<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoterProfileResource extends JsonResource
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
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'middlename' => $this->middlename,
            'precinct_no' => $this->precinct_no,
            'barangay' => $this->barangay,
            'purok' => $this->purok,
            'contact_no' => $this->contact_no,
            'gender' => $this->gender,
            'remarks' => $this->remarks,
            'position' => $this->position,
            'leader' => $this->leader,
            'members' => $this->members

        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            // 'id' => $this->id,
            'pro_voter_id' => $this->pro_voter_id,
            'voter_name' => $this->voter_name,
            'precinct_no' => $this->precinct_no,
            'barangay_name' => $this->barangay_name,
            'municipality_name' => $this->municipality_name

        ];
    }
}

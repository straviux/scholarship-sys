<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'username' => $this->username,
            'office_designation' => $this->office_designation,
            'profile_photo_url' => $this->profile_photo_url,
            'has_profile_photo' => !empty($this->profile_photo),
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
        ];
    }
}

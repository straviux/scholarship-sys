<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSharedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get permissions from assigned roles (no direct user permissions anymore)
        $permissions = $this->roles()
            ->with('permissions')
            ->get()
            ->flatMap(fn($role) => $role->permissions)
            ->pluck('name')
            ->unique()
            ->values()
            ->toArray();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'office_designation' => $this->office_designation,
            'roles' => $this->getRoleNames(),
            'permissions' => $permissions,
            'profile_photo_url' => $this->profile_photo_url,
            'has_profile_photo' => $this->hasProfilePhoto(),
            'unread_notifications_count' => $this->getUnreadNotificationsCount(),
        ];
    }
}

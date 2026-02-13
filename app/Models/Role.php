<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Get menu items assigned to this role
     */
    public function menuItems()
    {
        return $this->belongsToMany(MenuItem::class, 'role_menu_item')
            ->withPivot('order')
            ->withTimestamps()
            ->orderBy('role_menu_item.order');
    }
}

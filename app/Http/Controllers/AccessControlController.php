<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessControlController extends Controller
{
    /**
     * Display the unified access control page with users, roles, and permissions.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/AccessControl', [
            'users' => UserResource::collection(User::with('roles')->get()),
            'roles' => RoleResource::collection(Role::with('permissions')->get()),
            'permissions' => PermissionResource::collection(Permission::all())
        ]);
    }
}

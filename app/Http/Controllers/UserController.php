<?php

namespace App\Http\Controllers;

use App\Http\Resources\PermissionResource;
use App\Http\Resources\RoleResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Users/UserIndex', [
            'users' => UserResource::collection(User::with('roles')->get()),
            'roles' => RoleResource::collection(Role::all())
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Admin/Users/Create', [
            'roles' => RoleResource::collection(Role::all())
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|lowercase|max:255|unique:' . User::class,
            'office_designation' => 'nullable|string|max:255',
            'password' => ['required', 'confirmed', Rules\Password::min(4)],
            'roles' => ['required', 'array']
        ]);

        $getRole = $request->input('roles');
        $role = Role::findById($getRole['id']);
        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'office_designation' => $request->office_designation,
            'password' => Hash::make($request->password),
        ]);

        // Only assign role - permissions come from the role (RBAC model)
        $user->syncRoles($getRole['name']);

        // Log user creation
        ActivityLogService::logRecordCreated(
            profileId: null,
            recordData: [
                'name' => $user->name,
                'username' => $user->username,
                'role' => $getRole['name']
            ],
            remarks: "Created user account: {$user->name} ({$user->username})"
        );

        return to_route('users.index');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user): Response
    {
        // Only load roles - permissions come from the roles assigned to the user
        $user->load(['roles']);
        return Inertia::render('Admin/Users/Edit', [
            'user' => new UserResource($user),
            'roles' => RoleResource::collection(Role::all())
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|lowercase|max:255| ' .
                Rule::unique('users', 'username')->ignore($user),
            'office_designation' => 'nullable|string|max:255',
            'roles' => ['required', 'array']
        ]);

        $oldData = $user->getAttributes();
        $getRole = $request->input('roles');
        $role = Role::findById($getRole['id']);
        $user->update([
            'name' => $request->name,
            'username' => $request->username,
            'office_designation' => $request->office_designation
        ]);

        // Only assign role - permissions come from the role (RBAC model)
        $user->syncRoles($getRole['name']);

        // Log user update
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: $oldData,
            newData: $user->fresh()->getAttributes()
        );

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            $userData = $user->getAttributes();
            $user->delete();

            // Log user deletion
            ActivityLogService::logRecordDeleted(
                profileId: null,
                recordData: $userData,
                remarks: "Deleted user account: {$userData['name']} ({$userData['username']})"
            );

            return to_route('users.index')->with('success', 'User deleted successfully.');
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                // Integrity constraint violation
                return back()->withErrors(['delete' => 'This user is referenced in other records. You must remove or reassign those records before deleting.']);
            }
            throw $e;
        }
    }


    /**
     * Change the password for a user.
     */
    public function changePassword(\App\Http\Requests\ChangeUserPasswordRequest $request, User $user): \Illuminate\Http\RedirectResponse
    {
        $user->password = Hash::make($request->password);
        $user->save();

        // Log password change
        ActivityLogService::logRecordUpdated(
            profileId: null,
            oldData: ['password_changed' => false],
            newData: ['password_changed' => true],
            remarks: "Changed password for user: {$user->name}"
        );

        return back()->with('success', 'Password updated successfully.');
    }
}

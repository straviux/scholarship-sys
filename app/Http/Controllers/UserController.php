<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Guard against privilege escalation: only administrators may manage
     * administrator accounts or hand out the administrator role.
     */
    private function ensureCanManage(?User $target = null, ?string $roleBeingAssigned = null): void
    {
        $actor = auth()->user();

        if ($actor->hasRole('administrator')) {
            return;
        }

        if ($target && $target->hasRole('administrator')) {
            abort(403, 'Only administrators can manage administrator accounts.');
        }

        if ($roleBeingAssigned === 'administrator') {
            abort(403, 'Only administrators can assign the administrator role.');
        }
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
        $this->ensureCanManage(roleBeingAssigned: $getRole['name'] ?? null);
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

        return to_route('access-control.index');
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
        $this->ensureCanManage($user, $getRole['name'] ?? null);
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
        $this->ensureCanManage($user);

        try {
            $userData = $user->getAttributes();
            $user->delete();

            // Log user deletion
            ActivityLogService::logRecordDeleted(
                profileId: null,
                recordData: $userData,
                remarks: "Deleted user account: {$userData['name']} ({$userData['username']})"
            );

            return to_route('access-control.index')->with('success', 'User deleted successfully.');
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
        $this->ensureCanManage($user);

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

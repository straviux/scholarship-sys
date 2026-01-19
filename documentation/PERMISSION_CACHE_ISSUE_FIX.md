# Permission Cache Issue - Session-Level Caching

## Problem Summary

When you remove a permission from a user type (e.g., removing `applicants.delete` from moderator), the currently logged-in user of that type still sees the delete button/menu option even after a page refresh.

## Root Cause

The issue is **session-level permission caching** in Spatie's Laravel Permissions package:

1. **When a user logs in**: Their permissions are loaded from the database and cached in the User model instance
2. **When the page refreshes**: The middleware (`HandleInertiaRequests.php` line 39) calls `$request->user()->getAllPermissions()`
3. **The problem**: The User instance is loaded from the session, and it has the permissions cached at the model level
4. **Permission removal doesn't sync**: Even though the role permissions are updated and the Spatie cache is cleared, the **logged-in user's session still has the old permissions cached**

## How Permissions Flow Works

```
User Login
    ↓
Session stores User instance with cached permissions
    ↓
Frontend page loads → HandleInertiaRequests middleware runs
    ↓
Middleware calls $request->user()->getAllPermissions()
    ↓
Returns cached permissions from User model instance (from session)
    ↓
If session permissions are stale, they don't reflect changes
```

## Current Code Analysis

**PermissionManagementController.php** (lines 48 and 70):
- ✅ Clears Spatie's permission cache with `forgetCachedPermissions()`
- ❌ Does NOT refresh the currently logged-in user's session permissions

**HandleInertiaRequests.php** (line 39):
- Calls `$request->user()->getAllPermissions()` 
- ✅ Gets fresh permissions on each request (good!)
- ❌ But the User object is from the session with cached model data

## Solutions

### Solution 1: Force Session Refresh (Recommended for Now)

**In PermissionManagementController.php**, after updating permissions, refresh the current user's session:

```php
public function togglePermission(Request $request)
{
    $request->validate([
        'role_name' => 'required|string|exists:roles,name',
        'permission_name' => 'required|string|exists:permissions,name',
        'grant' => 'required|boolean',
    ]);

    $role = Role::findByName($request->role_name);

    if ($request->grant) {
        $role->givePermissionTo($request->permission_name);
    } else {
        $role->revokePermissionTo($request->permission_name);
    }

    // Clear permission cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // IMPORTANT: If the current user is affected, refresh their session
    if ($request->user()->hasRole($request->role_name)) {
        // Refresh the user instance from database
        auth()->setUser(auth()->user()->refresh());
    }

    return back()->with('success', 'Permission updated successfully');
}

public function updateRolePermissions(Request $request)
{
    $request->validate([
        'role_name' => 'required|string|exists:roles,name',
        'permissions' => 'required|array',
        'permissions.*' => 'string|exists:permissions,name',
    ]);

    $role = Role::findByName($request->role_name);
    $role->syncPermissions($request->permissions);

    // Clear permission cache
    app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

    // IMPORTANT: If the current user is affected, refresh their session
    if ($request->user()->hasRole($request->role_name)) {
        auth()->setUser(auth()->user()->refresh());
    }

    return back()->with('success', 'Permissions updated successfully for ' . $role->name);
}
```

**How it works:**
- After clearing the Spatie cache, it checks if the current user has the role being modified
- If yes, it refreshes the user instance from the database: `auth()->user()->refresh()`
- This forces the user's permissions to be reloaded from the database

**Why this works:**
- `refresh()` reloads the User model from database
- Spatie's cache is already cleared
- Next call to `getAllPermissions()` gets fresh data from DB

### Solution 2: User Logout Required (Current Default Behavior)

For now, the workaround is:
1. Remove the permission from the moderator role
2. **Current moderator user logs out**
3. Moderator logs back in
4. Now they won't see the delete button

This is the default behavior of Spatie permissions with session-based authentication.

### Solution 3: Use API/Token-Based Auth (Long-term)

If you switch to token-based authentication (JWT/Sanctum), each request can check permissions against the database without session caching.

## Implementation Steps

**Option 1 Implementation (Recommended):**

1. Open `app/Http/Controllers/PermissionManagementController.php`
2. Add user refresh logic to both `togglePermission()` and `updateRolePermissions()` methods
3. Test by:
   - Login as a moderator user
   - Remove `applicants.delete` permission from moderator role
   - Moderator refreshes the page
   - Delete button should now be gone

**Option 2 (Current Workaround):**
- Just log out and log back in after permission changes

## Testing the Fix

1. **Before fix**: Moderator logs in → Admin removes `applicants.delete` → Moderator refreshes page → Delete button STILL visible ❌

2. **After Solution 1**: Moderator logs in → Admin removes `applicants.delete` → Moderator refreshes page → Delete button is GONE ✅

3. **After Solution 2**: Moderator logs in → Admin removes `applicants.delete` → Moderator logs out and back in → Delete button is GONE ✅

## Why This Happened

Spatie Laravel Permissions uses **multi-level caching**:
1. **Database**: Actual permissions stored
2. **Spatie Cache**: Application-level cache (cleared when permissions are updated)
3. **Model Cache**: User model instance caches permissions (stored in session)
4. **Gate Cache**: Authorization gate caches decisions

We clear level 2 but the session user instance (level 3) still has old permissions until the user's session is refreshed or they log out.

## References

- Spatie Permissions: https://spatie.be/docs/laravel-permission
- Laravel Auth Refresh: https://laravel.com/docs/authentication#refreshing-user-state

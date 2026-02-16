# Role-Based Permissions Only

**Date:** February 16, 2026  
**Status:** ✅ IMPLEMENTED

## Overview

Removed the `model_has_permissions` table to enforce **role-based permissions only**. All permissions are now assigned through roles, not directly to users.

## Changes Made

### 1. Database Migration
- **Migration:** `2026_02_16_131747_drop_model_has_permissions_table.php`
- **Action:** Dropped `model_has_permissions` table
- **Status:** ✅ Applied successfully

### 2. Code Cleanup

#### PermissionController.php
- ❌ Removed: Check for orphaned user permissions in `model_has_permissions`
- ❌ Removed: Duplicate entry removal from `model_has_permissions`
- ✅ Kept: Role-based permission cleanup only

#### CleanupPermissions Command
- ❌ Removed: User permission orphan detection
- ❌ Removed: User permission duplicate detection
- ✅ Kept: Role-based permission cleanup only

## Permissions Architecture

### Before
```
User ──┐
       ├─→ [Direct Permissions] ──→ model_has_permissions ❌ REMOVED
       │
       └─→ Role ──→ [Permissions] ──→ role_has_permissions ✅
```

### After (Current)
```
User ──→ Role ──→ [Permissions] ──→ role_has_permissions ✅
         (Only way to assign permissions)
```

## Benefits

✅ **Simpler Management**
- Assign permissions once per role
- Apply role to multiple users
- No direct permission assignments to manage

✅ **Better Scalability**
- New user? Just assign a role
- No individual permission assignments needed
- Easier to audit and track

✅ **Reduced Complexity**
- Single source of truth: `role_has_permissions`
- No conflicting direct user permissions
- Cleaner codebase

✅ **Easier Maintenance**
- Consistent permission structure
- Less cleanup code needed
- Fewer edge cases to handle

## How to Assign Permissions

### To Assign Permission to User

```php
$user = User::find($userId);
$role = Role::where('name', 'admin')->first();

// Assign role to user
$user->assignRole($role);

// User now has all permissions from that role
```

### To Assign Permission to Role

```php
$role = Role::where('name', 'admin')->first();
$permission = Permission::where('name', 'applicants.edit')->first();

// Assign permission to role
$role->givePermissionTo($permission);

// All users with this role now have this permission
```

## Reverting This Change

If you need to revert (not recommended), run:

```bash
php artisan migrate:rollback --step=1
```

This will recreate the `model_has_permissions` table.

## Migration Path If You Had Existing Data

If you had existing direct user permissions before this change:
1. The table was dropped, so those permissions were lost
2. To restore them, you would need to:
   - Examine your backup for user permissions
   - Create new roles for those specific users
   - Assign those roles to the users
   - Assign the permissions to those roles

Example:

```php
// Create a role for a specific user's permissions
$role = Role::create(['name' => 'special_user_' . $userId]);

// Give it the permissions that user had
$role->givePermissionTo(['perm1', 'perm2', 'perm3']);

// Assign to user
$user->assignRole($role);
```

## Checking User Permissions

```php
$user = User::find($userId);

// Check if user has permission
if ($user->can('applicants.view')) {
    // User has this permission through their role
}

// Get all permissions for user
$permissions = $user->getAllPermissions();

// Get all roles for user
$roles = $user->getRoleNames();
```

## Current Roles in System

Run this to see all roles:

```bash
php artisan tinker
>>> Role::all()->pluck('name');
```

Common roles include:
- `admin` - Full system access
- `staff` - Staff member permissions
- `scholar` - Scholar/student permissions
- `user` - Basic user permissions

---

**Note:** This architecture is standard for Laravel permission systems using Spatie Permission package and is the recommended approach for most applications.

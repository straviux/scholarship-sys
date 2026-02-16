# Model Has Permissions Table Removal - Fixes

**Date:** February 16, 2026  
**Status:** ✅ FIXED

## Error Encountered

After dropping `model_has_permissions` table, the following error occurred:

```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'scholarship_program_devmode.model_has_permissions' doesn't exist
...
from `permissions` inner join `model_has_permissions` on `permissions`.`id` = `model_has_permissions`.`permission_id`
```

## Root Cause

The Spatie Permission package's `HasRoles` trait includes a `permissions()` method that creates a relationship to the dropped `model_has_permissions` table. Various parts of the code were still trying to access this method.

## Fixes Applied

### 1. **User Model Override** ✅
**File:** `app/Models/User.php`

Added override for the `permissions()` method:

```php
/**
 * Override the permissions() method from HasRoles trait.
 * Since we're using role-based permissions only (not direct user permissions),
 * this method is disabled to prevent queries against the dropped model_has_permissions table.
 * 
 * @deprecated Do not use. Use roles() or getAllPermissions() instead.
 * @return \Illuminate\Support\Collection Returns empty collection
 */
public function permissions()
{
    // Return empty collection - direct user permissions are no longer supported
    return collect();
}
```

**Why:** This prevents the trait's default `permissions()` method from trying to query the dropped table.

### 2. **CleanupPermissions Command** ✅
**File:** `app/Console/Commands/CleanupPermissions.php`

#### Change 1: Removed `removePermissionCompletely` user access
**From:**
```php
$userCount = $permission->users()->count();
...
$permission->users()->detach();
```

**To:**
```php
// Only access roles, not users
```

**Why:** The `users()` relationship on Permission tries to access the dropped table. Since we only support role-based permissions now, we removed this functionality.

#### Change 2: Updated `removePermissionFromUser` method
**From:**
```php
$permissionCount = $user->permissions()->count();
if ($permissionCount === 0) { ... }
if ($this->confirm("Remove all {$permissionCount} direct permissions...")) {
    $user->syncPermissions([]);
}
```

**To:**
```php
// Since we're using role-based permissions only (model_has_permissions table dropped),
// direct user permissions are no longer supported
$this->info("User '{$user->name}' uses role-based permissions only.");
$this->info("To manage permissions, assign/remove roles instead of direct permissions.");
```

**Why:** Since users no longer have direct permissions, this command is no longer needed. We now inform users about role-based management instead.

### 3. **PermissionController** ✅
**File:** `app/Http/Controllers/PermissionController.php`

**From:**
```php
if ($permission->roles()->exists()) {
    $permission->roles()->detach();
}
if ($permission->users()->exists()) {
    $permission->users()->detach();
}
```

**To:**
```php
if ($permission->roles()->exists()) {
    $permission->roles()->detach();
}
// Direct user permissions are no longer supported
```

**Why:** Removed attempt to access the `users()` relationship which no longer exists.

## Summary of Changes

| File | Change | Reason |
|------|--------|--------|
| User.php | Override `permissions()` method | Prevent querying dropped table |
| CleanupPermissions.php | Remove user permission access | Users no longer have direct permissions |
| PermissionController.php | Remove permission->users() calls | Relationship doesn't exist |

## Testing

After these fixes, verify:

1. ✅ Users can access the application without "table not found" errors
2. ✅ Permissions are correctly assigned through roles
3. ✅ Permission management works via role-based system
4. ✅ Cleanup commands run without database errors

## How to Verify

### Test in Tinker:
```bash
php artisan tinker

# Get a user
$user = User::find(15);

# Check permissions (should return empty collection)
$user->permissions();  // Collection []

# Check permissions through roles
$user->getAllPermissions()->pluck('name');  // Returns permissions from user's roles

# Check roles
$user->getRoleNames();  // Returns array of role names
```

### Test Permission Assignment:
```php
$user = User::find(15);
$role = Role::where('name', 'admin')->first();

# Assign role
$user->assignRole($role);

# User now has all permissions from that role
$user->can('applicants.edit');  // true (if admin role has it)
```

## Migration Impact

- **Migration Applied:** `2026_02_16_131747_drop_model_has_permissions_table.php`
- **Table Dropped:** `model_has_permissions`
- **Reversible:** Yes, running `php artisan migrate:rollback` will recreate the table

## Code References Removed

✅ **Replaced:**
- `$user->permissions()` → Use `$user->getAllPermissions()` or `$user->roles()`
- `$permission->users()` → Only use `$permission->roles()`
- Direct user permission assignments → Use role-based assignments only

✅ **No Changes Needed (Safe):**
- `$user->roles()` - Works fine
- `$user->getRoleNames()` - Works fine  
- `$user->getAllPermissions()` - Works fine
- `$user->hasPermissionTo()` - Works fine
- `$user->can()` - Works fine
- `$user->hasRole()` - Works fine

## Best Practices Going Forward

### To Add Permissions to Users:
```php
$user = User::find($userId);
$role = Role::where('name', 'admin')->first();
$user->assignRole($role);
```

### To Remove Permissions from Users:
```php
$user->removeRole('admin');
```

### To Add Permissions to Roles:
```php
$role = Role::where('name', 'admin')->first();
$permission = Permission::where('name', 'applicants.edit')->first();
$role->givePermissionTo($permission);
```

### To Check if User Has Permission:
```php
// Through Spatie's method
$user->can('applicants.edit');

// Or custom method
$user->hasPermission('applicants.edit');
```

---

**Note:** All these changes enforce a strict role-based access control model, making the system simpler and more maintainable.

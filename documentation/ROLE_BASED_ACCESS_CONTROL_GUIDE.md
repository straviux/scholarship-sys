# Role-Based & Permission-Based Access Control Guide

> Current note: the app no longer exposes separate `/roles` or `/permissions` management pages. The active management UI is the unified `/access-control` page, and the legacy `/users` GET route now redirects there while write endpoints remain on `/users`, `/roles`, and `/permissions`.

## Overview

The system now uses a **two-tiered access control model**:

1. **Role-Based Page Access** - Controls which pages/sections users can visit
2. **Permission-Based Action Control** - Controls what actions users can perform within those pages

## Architecture

### Pages (Role-Based)
Pages are the main UI sections in the system. Each role can have access to zero or more pages:

- `users` - Legacy alias used by some role checks; GET requests redirect to `access-control`
- `access-control` - Unified access control page
- `system-options` - System options management
- `system-report` - System reports
- `deleted-records` - Soft-deleted records recovery
- `maintenance` - System maintenance announcements

### Permissions (Action-Based)
Permissions control specific actions within pages:

- `users.create`, `users.edit`, `users.delete` - User actions
- `roles.manage`, `permissions.manage` - Admin actions
- `applicants.view`, `applicants.edit`, `applicants.delete` - Applicant actions
- `scholarships.edit`, `reports.generate` - Business actions
- etc.

## Usage Examples

### In Routes

**Page-Level Access (Role-Based)**
```php
// Unified access-control page
Route::middleware(['auth', 'check.role:access-control'])->group(function () {
   Route::get('/access-control', [AccessControlController::class, 'index']);
    
   // Further restrict writes to those with users.create permission
    Route::post('/users', [UserController::class, 'store'])
        ->middleware('check.permission:users.create');
});
```

**Multiple Pages**
```php
Route::middleware(['auth', 'check.role:users,access-control'])->group(function () {
   // Legacy aliases may still allow either page key during the transition
   Route::get('/users', fn () => to_route('access-control.index'));
});
```

### In Vue Components

**Using the Permission Composable**
```vue
<script setup>
import { usePermission } from '@/composable/permissions';

const { hasRole, hasPermission, hasPageAccess, isAdmin, can } = usePermission();
</script>

<template>
    <!-- Check if user has a role -->
    <Button v-if="hasRole('administrator')" />
    
    <!-- Check if user has any of multiple roles -->
    <Button v-if="hasRole(['admin', 'manager'])" />
    
    <!-- Check if user has a permission -->
    <Button v-if="hasPermission('users.create')" label="Create User" />
    
    <!-- Check multiple permissions -->
    <Button v-if="hasPermission(['users.edit', 'users.delete'])" />
    
    <!-- Check page access -->
    <span v-if="hasPageAccess('users')">Can access users page</span>
    
    <!-- Complex permission check -->
    <Button v-if="can({ roles: ['admin'], permissions: ['users.create'] })" />
    
    <!-- Quick admin check -->
    <Button v-if="isAdmin()" label="Admin Only" />
</template>
```

### In Controllers

**Check Page Access**
```php
if (!$request->user()->hasAccessToPage('users')) {
    abort(403, 'You do not have access to this page');
}
```

**Check Permission**
```php
if (!$request->user()->hasPermission('users.create')) {
    abort(403, 'You cannot perform this action');
}
```

## Role Setup

### Default Roles

| Role | Page Access | Purpose |
|------|-------------|---------|
| **administrator** | All pages | Full system access (automatic) |
| **program_manager** | users, access-control | Can manage users and system access |
| **moderator** | (none) | Can perform actions based on permissions |
| **user** | (none) | Limited view-only access |
| **jpm_admin** | (none) | JPM-specific functionality via permissions |

### Managing Role Pages

There is no dedicated `Page Access` tab in the current Access Control UI.

Use the live `/access-control` page for user, role, and permission management, and review `check.role` middleware usage in `routes/web.php` for page-level access rules.

If you still maintain legacy page-access mappings, inspect the `role_page` table directly rather than expecting an admin UI for it.

## Permission Assignment

**Via Admin UI** (Access Control → Roles & Permissions tab)
1. Navigate to Access Control page
2. Go to "Roles & Permissions" tab
3. Select a role from the left sidebar
4. Toggle permissions on/right to assign/revoke

**Via Code**
```php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$role = Role::where('name', 'program_manager')->first();

// Give permission
$role->givePermissionTo('users.create');

// Revoke permission
$role->revokePermissionTo('users.create');

// Sync (set exact permissions)
$role->syncPermissions(['users.view', 'users.edit']);
```

## Best Practices

1. **Separate Concerns**
   - Use roles for page/section access
   - Use permissions for specific actions
   - Don't duplicate logic

2. **Granular Permissions**
   ```php
   // Good
   'users.view', 'users.create', 'users.edit', 'users.delete'
   
   // Avoid
   'can-manage-users', 'can-do-user-stuff'
   ```

3. **Consistent Naming**
   ```
   {resource}.{action}
   
   Examples:
   - reports.generate
   - scholarships.export
   - disbursements.approve
   ```

4. **Don't Hardcode Roles**
   ```php
   // Bad
   if ($user->hasRole('administrator')) { ... }
   
   // Better
   if ($user->hasRole('administrator', 'program_manager')) { ... }
   
   // Even Better - use permissions
   if ($user->hasPermission('users.manage')) { ... }
   ```

5. **Cache Invalidation**
   - When updating permissions/roles, the middleware automatically clears cache
   - Manual clear: `php artisan cache:clear`

## Migration Checklist

If you're migrating from old permission names:

- [ ] Identify all old permission names in codebase
- [ ] Run standardize permissions migration
- [ ] Update Vue components to use new permission names
- [ ] Test page access for each role
- [ ] Verify permission-based action controls
- [ ] Clear application cache after changes

## Troubleshooting

**User can't access a page**
1. Check if their role is assigned to that page
2. Verify middleware is using `check.role` correctly
3. Clear cache with `php artisan cache:clear`

**Permission not working**
1. Verify permission exists in database
2. Check role has the permission assigned
3. Verify `hasPermission()` is being used, not role check
4. Clear cache

**Routes not found**
1. Ensure RolePageController exists in `app/Http/Controllers/`
2. Verify routes are registered in `routes/web.php`
3. Run `php artisan route:list` to verify

## Adding New Pages

1. **Define in controller** (`RolePageController.php`)
   ```php
   private const AVAILABLE_PAGES = [
       'new-page-name' => 'Display Name',
       // ...
   ];
   ```

2. **Create route with middleware**
   ```php
   Route::middleware(['auth', 'check.role:new-page-name'])->get('/new-page', ...);
   ```

3. **Update seeder** if specific roles should have access
   ```php
   $role->pages()->create(['page' => 'new-page-name']);
   ```

4. **Test middleware**
   ```bash
   php artisan route:list | grep new-page
   ```

## See Also

- [Laravel Spatie Permission Documentation](https://spatie.be/docs/laravel-permission/v6/introduction)
- `app/Models/User.php` - Permission/Role helper methods
- `resources/js/composable/permissions.js` - Vue permission helpers
- `app/Http/Middleware/CheckRole.php` - Role middleware
- `app/Http/Middleware/CheckPermission.php` - Permission middleware

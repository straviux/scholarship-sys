# Quick Reference: Role-Based Access Control

## 🚀 Quick Start

### Check User Access in Vue
```vue
<script setup>
import { usePermission } from '@/composable/permissions';
const { hasPermission, hasPageAccess, isAdmin } = usePermission();
</script>

<template>
    <!-- Permission check -->
    <Button v-if="hasPermission('users.create')" label="Create" />
    
    <!-- Page access check -->
    <div v-if="hasPageAccess('users')">Users page content</div>
    
    <!-- Admin check -->
    <span v-if="isAdmin()">You're an admin</span>
</template>
```

### Check User Access in PHP
```php
// Page access
if ($user->hasAccessToPage('users')) { ... }

// Permission
if ($user->hasPermission('users.create')) { ... }

// Multiple roles
if ($user->hasAnyRole(['admin', 'manager'])) { ... }
```

### Protect Routes
```php
// By page
Route::middleware(['check.role:users'])->get(...);

// By multiple pages
Route::middleware(['check.role:users,access-control'])->get(...);

// By permission
Route::middleware(['check.permission:users.create'])->post(...);

// Both
Route::middleware(['check.role:users', 'check.permission:users.create'])
    ->post(...);
```

---

## 📖 Common Permissions

### User Management
- `users.view` - See users
- `users.create` - Create new users
- `users.edit` - Edit user info
- `users.delete` - Delete users

### Roles & Permissions
- `roles.manage` - Manage roles
- `permissions.manage` - Manage permissions

### Applicants/Scholarships
- `applicants.view` - View applicants
- `applicants.create` - Create applicants
- `applicants.edit` - Edit applicants
- `applicants.delete` - Delete applicants
- `scholarships.view` - View scholarship records
- `scholarships.edit` - Edit records

### Admin
- `system-options.manage` - Manage system options
- `system-report.view` - View system reports

---

## 📄 Available Pages

- `users` - Legacy alias; GET requests redirect to `access-control`
- `access-control` - Unified user, role, and permission management
- `system-options` - System options
- `system-report` - System reports
- `deleted-records` - Soft-deleted records
- `maintenance` - System maintenance

---

## ⚙️ Admin Tasks

### Add Permission to Role
Go to **Access Control** → **Roles & Permissions** tab:
1. Select role from left sidebar
2. Toggle permission checkboxes
3. Auto-saves on toggle

### Assign Pages to Role
Go to **Access Control** → **Page Access** tab:
1. Click gear icon on role
2. Select/deselect pages
3. Click "Save Changes"

### Create New Role
Go to **Access Control** → **Roles & Permissions** tab:
1. Click "New Role" button
2. Enter role name
3. Click "Create Role"
4. Assign permissions as needed

---

## 🔍 Troubleshooting

**User can't see a page?**
- ✅ Check the route's `check.role` middleware and confirm the user's page keys are present in the auth payload
- ✅ Clear cache: `php artisan cache:clear`

**Action button not showing?**
- ✅ Check if they have the permission
- ✅ Verify permission names match exactly (case-sensitive)

**Getting 403 Forbidden?**
- ✅ Their role doesn't have page access
- ✅ They don't have the required permission
- ✅ Check both the middleware route and Vue component

---

## 📚 Full Documentation

For detailed information, see:
- `ROLE_BASED_ACCESS_CONTROL_GUIDE.md` - Complete guide
- `IMPLEMENTATION_SUMMARY.md` - What was implemented
- `app/Models/User.php` - User methods (hasAccessToPage, hasPermission, etc.)
- `resources/js/composable/permissions.js` - All Vue helpers

---

## 🎯 Custom Implementation

### Add New Permission
```php
// Create it
Permission::create(['name' => 'custom.action']);

// Give to role
Role::where('name', 'program_manager')
    ->first()
    ->givePermissionTo('custom.action');
```

### Add New Page
1. Edit `app/Http/Controllers/RolePageController.php` - Add to `AVAILABLE_PAGES`
2. Create route with `check.role:page-name` middleware
3. Assign to roles via Admin UI or seeder

---

**Last Updated**: Feb 12, 2026 | **Version**: 1.0

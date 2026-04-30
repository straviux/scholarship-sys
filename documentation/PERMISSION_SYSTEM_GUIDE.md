# Permission System - Usage Guide

## Overview

This system provides a comprehensive permission management solution using Spatie Laravel Permission package with a user-friendly Vue.js interface.

## Features

✅ Role-based permissions  
✅ Easy permission checking in Vue components  
✅ Directive for declarative permission checks  
✅ Composable for programmatic checks  
✅ Admin UI for managing permissions  
✅ Automatic permission sharing via Inertia

---

## 1. Vue Composable Usage

### Import the composable

```javascript
import { usePermissions } from '@/composables/usePermissions';

const { can, canAny, canAll, isRole } = usePermissions();
```

### Check single permission

```vue
<script setup>
	import { usePermissions } from '@/composables/usePermissions';

	const { can } = usePermissions();
</script>

<template>
	<!-- Show button only if user can create applicants -->
	<Button
		v-if="can('applicants.create')"
		@click="openCreateDialog"
		label="Add Applicant"
		icon="pi pi-plus"
	/>

	<!-- Show edit button only if user can edit scholarships -->
	<Button
		v-if="can('scholarships.edit')"
		@click="openEditDialog"
		label="Edit"
		icon="pi pi-pencil"
	/>

	<!-- Show delete button only if user can delete -->
	<Button
		v-if="can('disbursements.delete')"
		@click="confirmDelete"
		severity="danger"
		label="Delete"
		icon="pi pi-trash"
	/>
</template>
```

### Check multiple permissions

```javascript
// Check if user has ANY of these permissions
if (canAny(['applicants.edit', 'applicants.delete'])) {
	// Show actions menu
}

// Check if user has ALL of these permissions
if (canAll(['scholarships.create', 'scholarships.edit'])) {
	// Allow full management
}
```

### Check user role

```javascript
if (isRole('administrator')) {
	// Admin-only functionality
}

if (hasAnyRole(['administrator', 'moderator'])) {
	// Staff functionality
}
```

---

## 2. Vue Directive Usage

### Simple usage

```vue
<template>
	<!-- Button will be hidden if user doesn't have permission -->
	<Button v-can="'applicants.create'" @click="addApplicant" label="Add Applicant" />

	<!-- Multiple elements can use the directive -->
	<div v-can="'scholarships.edit'">
		<InputText v-model="form.name" />
		<Button @click="save" label="Save Changes" />
	</div>
</template>
```

### Combining with v-if for complex logic

```vue
<template>
	<!-- Show for specific role AND permission -->
	<Button
		v-if="isRole('moderator')"
		v-can="'scholarships.approve'"
		@click="approve"
		label="Approve"
	/>
</template>
```

---

## 3. Available Permissions

### Applicants Module

- `applicants.view` - View applicant profiles
- `applicants.create` - Create new applicants
- `applicants.edit` - Edit applicant information
- `applicants.delete` - Delete applicants
- `applicants.export` - Export applicant data
- `applicants.approve` - Approve/decline applications

### Scholarships Module

- `scholarships.view` - View scholarship records
- `scholarships.create` - Create scholarship records
- `scholarships.edit` - Edit scholarship records
- `scholarships.delete` - Delete scholarship records
- `scholarships.export` - Export scholarship data
- `scholarships.update-status` - Update scholarship status
- `scholarships.update-grant` - Update grant provision

### Disbursements Module

- `disbursements.view` - View disbursements
- `disbursements.create` - Create disbursements
- `disbursements.edit` - Edit disbursements
- `disbursements.delete` - Delete disbursements
- `disbursements.export` - Export disbursement data
- `disbursements.approve` - Approve disbursements

### Waiting List Module

- `waiting-list.view` - View waiting list
- `waiting-list.manage` - Manage waiting list
- `waiting-list.export` - Export waiting list

### Programs & Courses

- `programs.view` - View programs
- `programs.manage` - Manage programs
- `courses.view` - View courses
- `courses.manage` - Manage courses
- `schools.view` - View schools
- `schools.manage` - Manage schools
- `requirements.view` - View requirements
- `requirements.manage` - Manage requirements

### Reports

- `reports.view` - View reports
- `reports.generate` - Generate reports
- `reports.export` - Export reports

### System Settings

- `settings.view` - View system settings
- `settings.manage` - Manage system settings
- `users.view` - View users
- `users.manage` - Manage users
- `roles.view` - View roles
- `roles.manage` - Manage roles
- `permissions.view` - View permissions
- `permissions.manage` - Manage permissions
- `system-options.view` - View system options
- `system-options.manage` - Manage system options
- `system-stats.view` - View system statistics

### JPM (Job Placement & Monitoring)

- `jpm.view` - View JPM records
- `jpm.manage` - Manage JPM records

### Priority Management

- `priority.view` - View priority levels
- `priority.manage` - Manage priority levels

---

## 4. Backend Usage (Laravel)

### Check permission in controller

```php
use Illuminate\Support\Facades\Gate;

public function store(Request $request)
{
    // Check if user has permission
    if (!auth()->user()->can('applicants.create')) {
        abort(403, 'Unauthorized action.');
    }

    // Or use Gate
    Gate::authorize('applicants.create');

    // Your code here
}
```

### Middleware protection

```php
// In routes/web.php
Route::post('/applicants', [ApplicantController::class, 'store'])
    ->middleware('can:applicants.create');
```

### Blade directive

```blade
@can('applicants.edit')
    <button>Edit Applicant</button>
@endcan

@canany(['applicants.edit', 'applicants.delete'])
    <div class="actions">
        <!-- Action buttons -->
    </div>
@endcanany
```

---

## 5. Managing Permissions (Admin UI)

### Accessing Role Permissions

1. Login as administrator
2. Navigate to: **Access Control**
3. Open the **Roles & Permissions** area
4. Select the role you want to configure
5. Toggle permissions on or off

### Role Permissions Features

- ✅ Unified access-control workspace
- ✅ Role list with inline permission management
- ✅ Grouped permissions by module
- ✅ Quick select/clear all
- ✅ Real-time toggle switches or immediate updates
- ✅ Permission counter per module
- ✅ Administrator role is read-only (has all permissions)

---

## 6. Default Role Permissions

### Administrator

- Has ALL permissions (cannot be modified)

### Program Manager

- Can view, create, and edit applicants
- **Can mark applicants as approved or denied** ✓
- Can view, create, edit scholarships and update status
- Can view, create, edit disbursements
- Can view and manage waiting list
- Can view programs, courses, schools, requirements
- Can view and generate reports
- **Primary role for handling application approvals and denials**

### Moderator

- Can view, create, and edit applicants
- Can view, create, edit scholarships and update status
- Can view, create, edit disbursements
- Can view and manage waiting list
- Can view programs, courses, schools, requirements
- Can view and generate reports
- **Note: Cannot mark applicants as approved/denied (Program Manager only)**

### User (Read-only)

- Can view applicants
- Can view scholarships
- Can view disbursements
- Can view waiting list
- Can view programs, courses, schools, requirements
- Can view reports

### JPM Admin

- Can view applicants and scholarships
- Can view and manage JPM records
- Can view and manage priorities
- Can view and generate reports

---

## 7. Common Patterns

### Action buttons with permissions

```vue
<template>
	<div class="flex gap-2">
		<Button v-can="'applicants.edit'" @click="edit" icon="pi pi-pencil" severity="info" text />
		<Button
			v-can="'applicants.delete'"
			@click="confirmDelete"
			icon="pi pi-trash"
			severity="danger"
			text
		/>
	</div>
</template>
```

### Conditional form fields

```vue
<script setup>
	import { usePermissions } from '@/composables/usePermissions';

	const { can } = usePermissions();
</script>

<template>
	<form>
		<InputText v-model="form.name" />

		<!-- Only show status field if user can update status -->
		<Select
			v-if="can('scholarships.update-status')"
			v-model="form.status"
			:options="statusOptions"
		/>

		<!-- Only show grant field if user can update grant -->
		<Select
			v-if="can('scholarships.update-grant')"
			v-model="form.grant_provision"
			:options="grantOptions"
		/>
	</form>
</template>
```

### Export buttons

```vue
<template>
	<div class="export-actions">
		<Button
			v-can="'applicants.export'"
			@click="exportToPDF"
			label="Export PDF"
			icon="pi pi-file-pdf"
		/>
		<Button
			v-can="'applicants.export'"
			@click="exportToExcel"
			label="Export Excel"
			icon="pi pi-file-excel"
		/>
	</div>
</template>
```

---

## 8. Adding New Permissions

### Step 1: Add to seeder

Edit `database/seeders/PermissionSeeder.php`:

```php
$permissions = [
    // ... existing permissions ...

    // New Module
    ['name' => 'new-module.view', 'description' => 'View new module'],
    ['name' => 'new-module.create', 'description' => 'Create new module'],
];
```

### Step 2: Run seeder

```bash
php artisan db:seed --class=PermissionSeeder
```

### Step 3: Assign to roles

Use the Role Permissions UI to assign the new permission to roles, or update the seeder to include it in default role permissions.

### Step 4: Use in your components

```vue
<Button v-can="'new-module.create'" @click="create">Create</Button>
```

---

## 9. Troubleshooting

### Permissions not updating?

```bash
# Clear permission cache
php artisan cache:clear
php artisan permission:cache-reset
```

### User doesn't have expected permissions?

Check in tinker:

```bash
php artisan tinker
>>> $user = User::find(1);
>>> $user->getAllPermissions()->pluck('name');
>>> $user->roles;
```

### Permission not showing in Vue?

Check browser console for the `permissions` prop in Inertia page props.

---

## 10. Best Practices

✅ **DO:**

- Use semantic permission names (module.action)
- Check permissions at both frontend and backend
- Use composable for complex logic, directive for simple visibility
- Group related permissions by module
- Document custom permissions

❌ **DON'T:**

- Rely only on frontend permission checks for security
- Use permissions for UI styling (use CSS classes instead)
- Create too granular permissions (keep it maintainable)
- Hardcode permission strings (use constants for reusability)

---

## Support

For issues or questions, contact your system administrator.

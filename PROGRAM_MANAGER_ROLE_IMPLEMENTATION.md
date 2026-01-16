# Program Manager Role Implementation

## Overview
A new "Program Manager" role has been successfully implemented in the scholarship management system. This role has full access similar to an administrator but is specifically designed to handle application approvals and denials.

## Changes Made

### 1. Database Seeders
**File: `database/seeders/RoleSeeder.php`**
- Added new role: `program_manager`
- Role is created between `administrator` and `moderator` in the seeding order

```php
Role::create(['name' => 'program_manager']);
```

### 2. Permission Configuration
**File: `database/seeders/PermissionSeeder.php`**
- Created Program Manager role permissions
- Assigned `applicants.approve` permission (KEY DIFFERENCE from Moderator)
- All other permissions match the Moderator role

**Permissions assigned to Program Manager:**
- `applicants.view` - View applicant profiles
- `applicants.create` - Create new applicants
- `applicants.edit` - Edit applicant information
- **`applicants.approve`** - Approve/decline applications ✓ (Unique to this role + Admin)
- `scholarships.view` - View scholarship records
- `scholarships.create` - Create scholarship records
- `scholarships.edit` - Edit scholarship records
- `scholarships.update-status` - Update scholarship status
- `disbursements.view` - View disbursements
- `disbursements.create` - Create disbursements
- `disbursements.edit` - Edit disbursements
- `waiting-list.view` - View waiting list
- `waiting-list.manage` - Manage waiting list
- `programs.view` - View programs
- `courses.view` - View courses
- `schools.view` - View schools
- `requirements.view` - View requirements
- `reports.view` - View reports
- `reports.generate` - Generate reports
- `forms-templates.view` - View forms and templates
- `forms-templates.download` - Download forms and templates

### 3. Vue Component Updates

**File: `resources/js/Pages/Applicants/Index.vue`**
- Added role check to the "Mark as Approved" and "Mark as Denied" buttons in the profile review modal
- Only visible to users with `administrator` or `program_manager` roles
- Location: Lines 1817-1823

```vue
<div v-if="hasRole('administrator') || hasRole('program_manager')" class="flex gap-2 mb-4 pt-3 border-t">
    <Button label="Mark as Approved" icon="pi pi-check" severity="success" size="small"
        @click="markAsApproved" />
    <Button label="Mark as Denied" icon="pi pi-times" severity="danger" size="small"
        @click="markAsDenied" />
</div>
```

**File: `resources/js/Pages/Scholarship/ReviewedApplicants.vue`**
- Added `usePermission` composable import
- Added role check to "Approve" button for approved applicants (Line 81-83)
- Added role check to "Confirm" button for denied applicants (Line 136-138)
- Both buttons now only visible to `administrator` or `program_manager` roles

```vue
<Button v-if="hasRole('administrator') || hasRole('program_manager')" 
    label="Approve" icon="pi pi-check" size="small" severity="success" 
    @click="approveApplication(slotProps.data)" />
```

### 4. Documentation Update
**File: `PERMISSION_SYSTEM_GUIDE.md`**
- Updated section 6 "Default Role Permissions" with new Program Manager role
- Clearly documented that only Administrator and Program Manager can approve/deny
- Clarified that Moderator role cannot perform approval actions

## Access Control Summary

### Who Can Mark Applications as Approved/Denied?
✅ **Administrator** - Full system access
✅ **Program Manager** - Specific role for approval workflows

### Who CANNOT Mark Applications as Approved/Denied?
❌ **Moderator** - Can view and manage but not approve/deny
❌ **User** - Read-only access
❌ **JPM Admin** - Specialized JPM functionality only

## Implementation Details

### Role Check Implementation
The system uses the `usePermission()` composable with the `hasRole()` function to check user roles at runtime in Vue components:

```javascript
const { hasRole } = usePermission();

// Check if user is administrator or program_manager
v-if="hasRole('administrator') || hasRole('program_manager')"
```

### Backend Protection
The Laravel backend also validates permissions at the controller level using:
```php
if (!Gate::allows('applicants.approve')) {
    abort(403, 'You do not have permission...');
}
```

This ensures users cannot bypass frontend checks.

## Testing Checklist

To verify the implementation works correctly:

1. **Create a Program Manager User**
   - Go to User Management
   - Create new user with "Program Manager" role
   - Login as this user

2. **Test Approval Functionality**
   - ✓ Program Manager can see "Mark as Approved" and "Mark as Denied" buttons
   - ✓ Program Manager can mark applicants as approved in waiting list
   - ✓ Program Manager can approve/confirm in Reviewed Applicants page

3. **Test Other Roles**
   - ✓ Moderator users CANNOT see approval buttons
   - ✓ User role CANNOT see approval buttons
   - ✓ JPM Admin CANNOT see approval buttons

4. **Verify Backend Protection**
   - ✓ If user tries to directly call the API without proper role, they get 403 error
   - ✓ Permissions are properly checked by the gate in ScholarshipProfileController

## Database Seeding

To apply these changes to your database:

```bash
# Reset the entire database (WARNING: This deletes all data)
php artisan migrate:refresh --seed

# Or if you just want to refresh permissions and roles
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=PermissionSeeder
```

## Migration Path

If you have existing data and don't want to reset:

1. Manually create the `program_manager` role in the database:
   ```bash
   php artisan tinker
   > use Spatie\Permission\Models\Role;
   > Role::create(['name' => 'program_manager']);
   ```

2. Assign permissions manually:
   ```bash
   > use Spatie\Permission\Models\Role, Permission;
   > $role = Role::findByName('program_manager');
   > $permissions = ['applicants.view', 'applicants.create', ...]; // See list above
   > $role->syncPermissions($permissions);
   ```

3. Assign existing users to the new role as needed

## Files Modified

1. `database/seeders/RoleSeeder.php` - Added role creation
2. `database/seeders/PermissionSeeder.php` - Added role permissions
3. `resources/js/Pages/Applicants/Index.vue` - Added role checks
4. `resources/js/Pages/Scholarship/ReviewedApplicants.vue` - Added role checks
5. `PERMISSION_SYSTEM_GUIDE.md` - Updated documentation

## Summary

The Program Manager role provides a focused, secure way to delegate approval responsibilities without giving full administrative access. All approval functionality is now restricted to:
- **Administrator** (full system access)
- **Program Manager** (approval-focused access)

This implementation follows the principle of least privilege and ensures proper access control throughout the application.

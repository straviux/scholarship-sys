# Program Manager Role - Quick Reference

## Role Overview
A specialized role with administrative-like access, specifically designed to approve and deny scholarship applications.

## Key Features
- ✅ Full access to applicants management (view, create, edit)
- ✅ **Can mark applicants as approved or denied** (ONLY THIS ROLE + ADMIN)
- ✅ Can manage waiting lists
- ✅ Can view and update scholarship records
- ✅ Can view and manage disbursements
- ✅ Can view and generate reports

## Restrictions
- ❌ Cannot create, edit, or delete users
- ❌ Cannot manage roles and permissions
- ❌ Cannot access system settings/configuration
- ❌ Cannot delete system data

## How to Create a Program Manager User

### Via Admin Panel
1. Go to **Access Control** (Administrator only page)
2. Click **Users** tab
3. Click **+ New User** button
4. Fill in user details:
   - Name: [Name of user]
   - Username: [Username]
   - Office Designation: [Optional]
   - Password: [Secure password]
   - Role: **Program Manager** ← SELECT THIS
5. Click **Create User**

### Via Command Line
```bash
php artisan tinker

# Create the user
$user = App\Models\User::create([
    'name' => 'John Doe',
    'username' => 'johndoe',
    'password' => bcrypt('secure-password'),
    'office_designation' => 'Program Coordinator'
]);

# Assign the program_manager role
$user->syncRoles('program_manager');

exit
```

## Approval Workflow (Program Manager)

### Step 1: Review in Waiting List
1. Login as Program Manager
2. Go to **Applicants** page
3. View applicants in the waiting list
4. Click **Review** button to open applicant profile
5. View applicant details in modal
6. Click **Mark as Approved** OR **Mark as Denied** button
7. Confirm the action in the modal

### Step 2: Final Approval in Reviewed Applicants
1. Go to **Reviewed Applicants** page
2. Review applicants in either:
   - **Marked as Approved** section
   - **Marked as Denied** section
3. Click **Approve** (for approved) or **Confirm** (for denied) button
4. Fill in any additional details (date, remarks/reason)
5. Click **Approve** or **Confirm Deny**

## What Each Button Does

### In Waiting List (Applicants Page)
- **Mark as Approved**: Moves applicant to "Reviewed Applicants" with "Approved Pending" status
  - Removes from waiting list
  - Requires final approval in "Reviewed Applicants" page to complete

- **Mark as Denied**: Moves applicant to "Reviewed Applicants" with "Denied" status
  - Removes from waiting list
  - Requires final confirmation in "Reviewed Applicants" page

### In Reviewed Applicants Page
- **Approve**: Finalizes the approval (for approved applicants)
  - Applicant becomes an active scholar
  - Records the approval date and remarks
  - Cannot be undone without admin intervention

- **Confirm**: Finalizes the denial (for denied applicants)
  - Records the denial reason and details
  - Applicant cannot reapply for this scholarship
  - Cannot be undone without admin intervention

## Visibility Rules

### Approval Buttons Are Visible To:
- ✅ Administrator (always)
- ✅ Program Manager (always)

### Approval Buttons Are HIDDEN For:
- ❌ Moderator
- ❌ User (read-only)
- ❌ JPM Admin

## Permissions List
Program Manager has the following permissions:
- `applicants.view` ✓
- `applicants.create` ✓
- `applicants.edit` ✓
- `applicants.approve` ✓ **UNIQUE**
- `scholarships.view` ✓
- `scholarships.create` ✓
- `scholarships.edit` ✓
- `scholarships.update-status` ✓
- `disbursements.view` ✓
- `disbursements.create` ✓
- `disbursements.edit` ✓
- `waiting-list.view` ✓
- `waiting-list.manage` ✓
- `programs.view` ✓
- `courses.view` ✓
- `schools.view` ✓
- `requirements.view` ✓
- `reports.view` ✓
- `reports.generate` ✓
- `forms-templates.view` ✓
- `forms-templates.download` ✓

## Comparison with Other Roles

| Feature | Admin | Program Manager | Moderator | User | JPM Admin |
|---------|-------|-----------------|-----------|------|-----------|
| View Applicants | ✓ | ✓ | ✓ | ✓ | ✓ |
| Create Applicants | ✓ | ✓ | ✓ | ❌ | ❌ |
| Edit Applicants | ✓ | ✓ | ✓ | ❌ | ❌ |
| **Approve/Deny** | **✓** | **✓** | **❌** | **❌** | **❌** |
| Manage Users | ✓ | ❌ | ❌ | ❌ | ❌ |
| Manage Roles | ✓ | ❌ | ❌ | ❌ | ❌ |
| System Settings | ✓ | ❌ | ❌ | ❌ | ❌ |

## Troubleshooting

### Program Manager can't see approval buttons
**Problem**: Role might not be assigned correctly
**Solution**:
1. Go to Access Control > Users
2. Edit the user
3. Verify role is set to "Program Manager"
4. Save changes

### Getting "Unauthorized" error when trying to approve
**Problem**: User doesn't have `applicants.approve` permission
**Solution**:
1. Go to Access Control > Roles
2. Edit "Program Manager" role
3. Verify `applicants.approve` permission is checked
4. Save changes

### Created new role but it's not showing in dropdown
**Problem**: Need to refresh database permissions cache
**Solution**:
```bash
php artisan cache:clear
php artisan permission:cache-reset
```

## Security Notes
- All approval actions are logged in the system
- Frontend button visibility is enforced on the backend with permission gates
- Users cannot bypass role restrictions even if they modify frontend code
- Two-step approval process prevents accidental approvals

## Support
For detailed documentation, see [PROGRAM_MANAGER_ROLE_IMPLEMENTATION.md](PROGRAM_MANAGER_ROLE_IMPLEMENTATION.md)

For general permission system information, see [PERMISSION_SYSTEM_GUIDE.md](PERMISSION_SYSTEM_GUIDE.md)

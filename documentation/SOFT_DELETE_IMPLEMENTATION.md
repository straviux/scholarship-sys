# Soft Delete Implementation Documentation

**Date:** January 24, 2026  
**Status:** ✅ IMPLEMENTED

---

## Overview

Implemented a role-based soft delete functionality where:
- **Non-administrators**: Can soft delete records (marked as deleted but recoverable)
- **Administrators**: Can permanently delete and restore records

---

## Database Changes

### Migration: `2026_01_24_000001_add_soft_deletes_to_tables.php`

Added `deleted_at` timestamp columns (soft delete) to:
1. **scholarship_profiles** - Applicant profiles
2. **scholarship_records** - Scholarship records
3. **scholarship_record_attachments** - Attachment files
4. **disbursement_attachments** - Disbursement files

---

## Model Updates

### Updated Models with SoftDeletes Trait

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class ScholarshipProfile extends Model
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes;
    // ...
}
```

**Models Updated:**
- `ScholarshipProfile`
- `ScholarshipRecord`
- `ScholarshipRecordAttachment`
- `DisbursementAttachment`

---

## Controller Logic

### ScholarshipProfileController

#### destroy() Method
```php
public function destroy($id)
{
    $profile = ScholarshipProfile::findOrFail($id);
    
    if (auth()->user()->hasRole('administrator')) {
        // Permanent deletion for admins
        $profile->forceDelete();
    } else {
        // Soft deletion for non-admins
        $profile->delete();
    }
}
```

#### restore() Method (Admin Only)
```php
public function restore($id)
{
    // Check if user is administrator
    if (!auth()->user()->hasRole('administrator')) {
        abort(403, 'Only administrators can restore deleted profiles.');
    }

    $profile = ScholarshipProfile::onlyTrashed()->findOrFail($id);
    $profile->restore();
}
```

### ScholarshipRecordController

**Same pattern implemented:**
- `destroy()` - Soft delete for non-admins, permanent for admins
- `restore()` - Admin only, restores soft-deleted records

---

## Routes

### Added Routes

```php
// Profile soft delete and restore
Route::post('/applicants/{id}/restore', [ScholarshipProfileController::class, 'restore'])
    ->name('applicants.restore');
Route::delete('/applicants/{id}', [ScholarshipProfileController::class, 'destroy'])
    ->name('applicants.destroy');

// Scholarship record restore
Route::post('/scholarship_records/{id}/restore', 'restore')
    ->name('scholarship_records.restore');
```

---

## Frontend Implementation

### Updated Components

#### Applicants/Index.vue

**Delete Confirmation Dialog:**
```vue
<Dialog v-model:visible="showConfirmDeleteModal">
    <!-- Role-aware messaging -->
    <p class="text-lg font-semibold">
        {{ hasRole('administrator') ? 'Permanently delete this applicant?' : 'Delete this applicant?' }}
    </p>
    
    <!-- Informational text -->
    <p class="text-sm text-gray-600">
        {{ hasRole('administrator') 
            ? 'Administrators can permanently delete records. This action cannot be undone.' 
            : 'Non-administrators can soft delete records. Administrators can restore them later.' }}
    </p>
    
    <!-- Button text changes by role -->
    <Button :label="hasRole('administrator') ? 'Permanently Delete' : 'Delete Applicant'" />
</Dialog>
```

**Delete Handler:**
```javascript
const deleteApplicant = () => {
    router.delete(route('applicants.destroy', selectedApplicant.value.profile_id), {
        onSuccess: () => {
            toast.success('Applicant deleted successfully');
            if (refreshActivityLogs) refreshActivityLogs();
        }
    });
};
```

---

## How It Works

### For Non-Administrators

1. Click delete on any applicant/record
2. Confirmation dialog says "Delete this applicant?"
3. Message: "Non-administrators can soft delete records. Administrators can restore them later."
4. Record gets soft-deleted:
   - Marked with `deleted_at` timestamp
   - Remains in database but hidden from normal views
   - Can be restored by administrators
5. Activity log created for audit trail

### For Administrators

1. Click delete on any applicant/record
2. Confirmation dialog says "Permanently delete this applicant?"
3. Message: "Administrators can permanently delete records. This action cannot be undone."
4. Options available:
   - **Permanently Delete**: Uses `forceDelete()` to remove permanently
   - **Restore**: Can restore any soft-deleted record via restore route

---

## Database Queries

### Soft Delete (Non-Admin)
```php
// This creates deleted_at timestamp
$profile->delete(); 

// Records still exist in database
SELECT * FROM scholarship_profiles WHERE deleted_at IS NOT NULL;
```

### Permanent Delete (Admin)
```php
// This permanently removes from database
$profile->forceDelete();

// No recovery possible
```

### Restore (Admin Only)
```php
// Restore soft-deleted record
ScholarshipProfile::onlyTrashed()->find($id)->restore();

// Clears deleted_at timestamp
```

### Default Query Behavior
```php
// Automatically excludes soft-deleted records
$profiles = ScholarshipProfile::all();

// Include soft-deleted records
$profiles = ScholarshipProfile::withTrashed()->get();

// Only soft-deleted records
$profiles = ScholarshipProfile::onlyTrashed()->get();
```

---

## Activity Logging

All deletions are logged:

**Soft Deletion Log:**
```
Activity: Soft deleted applicant profile
Details: Marked with deleted_at timestamp
Remarks: "Soft deleted applicant profile: John Doe"
```

**Permanent Deletion Log:**
```
Activity: Permanently deleted applicant profile
Details: Complete removal from database
Remarks: "Permanently deleted applicant profile: John Doe"
```

**Restoration Log:**
```
Activity: Restored deleted profile
Details: Cleared deleted_at timestamp
Remarks: "Restored deleted applicant profile: John Doe"
```

---

## Affected Tables

| Table | Soft Delete | Hard Delete | Restore |
|-------|------------|------------|---------|
| scholarship_profiles | Non-admin | Admin | Admin only |
| scholarship_records | Non-admin | Admin | Admin only |
| scholarship_record_attachments | Non-admin | Admin | Admin only |
| disbursement_attachments | Non-admin | Admin | Admin only |

---

## Permissions Required

- **Delete**: All authenticated users (soft delete) or Administrators (permanent)
- **Restore**: Administrators only
- **View Deleted**: Not yet implemented (todo: add admin dashboard for viewing deleted records)

---

## Future Enhancements

- [ ] Admin dashboard to view and manage soft-deleted records
- [ ] Bulk restore/permanent delete functionality
- [ ] Soft delete retention policy (auto-delete after X days)
- [ ] Separate table for audit trail of deletions
- [ ] Notification when non-admin soft deletes a record
- [ ] Recovery deadline display for soft-deleted records

---

## Files Modified

### Backend
- `app/Models/ScholarshipProfile.php` - Added SoftDeletes trait
- `app/Models/ScholarshipRecord.php` - Added SoftDeletes trait
- `app/Models/ScholarshipRecordAttachment.php` - Added SoftDeletes trait
- `app/Models/DisbursementAttachment.php` - Added SoftDeletes trait
- `app/Http/Controllers/ScholarshipProfileController.php` - Updated destroy() and added restore()
- `app/Http/Controllers/ScholarshipRecordController.php` - Updated destroy() and added restore()
- `routes/web.php` - Added restore routes

### Frontend
- `resources/js/Pages/Applicants/Index.vue` - Updated delete dialog and handler

### Database
- `database/migrations/2026_01_24_000001_add_soft_deletes_to_tables.php` - New migration

---

## Testing Checklist

- [ ] Non-admin user can soft delete applicant
- [ ] Soft-deleted applicant doesn't appear in list
- [ ] Admin can see soft-deleted applicant (with withTrashed)
- [ ] Admin can restore soft-deleted applicant
- [ ] Admin can permanently delete applicant
- [ ] Permanent delete cannot be undone
- [ ] Activity logs created for all operations
- [ ] Delete confirmation message changes by role
- [ ] Restore button appears only for admins
- [ ] Related records (attachments, etc.) also get soft/hard deleted

---

## Notes

- Soft delete is based on Laravel's `SoftDeletes` trait
- `deleted_at` column automatically managed by Eloquent
- Related models automatically handle cascading soft deletes
- All queries automatically exclude soft-deleted records unless explicitly included
- Performance impact minimal as soft deletes use standard timestamp filtering

# Not JPM Checkbox Implementation

## Overview

Added a "Not JPM" checkbox option in the Tagged column of the Applicants waiting list to mark applicants who have been verified as not being JPM members.

## Changes Made

### 1. Database Migration

**File:** `database/migrations/2025_10_14_080443_add_is_not_jpm_to_scholarship_profiles_table.php`

Added new boolean column to `scholarship_profiles` table:

```php
$table->boolean('is_not_jpm')->default(false)->after('is_guardian_jpm');
```

This column stores whether an applicant has been explicitly marked as "Not JPM".

### 2. Backend Controller Update

**File:** `app/Http/Controllers/WaitingListController.php`

Updated `updateJpmStatus()` method to include the new field:

```php
$fields = [
    'is_jpm_member',
    'is_mother_jpm',
    'is_father_jpm',
    'is_guardian_jpm',
    'is_not_jpm',  // Added
];
```

The controller now accepts and processes the `is_not_jpm` field when updating JPM status.

### 3. Frontend Vue Component Update

**File:** `resources/js/Pages/Applicants/Index.vue`

#### Updated `updateJpmStatus` Function

Added `is_not_jpm` parameter to the function signature and payload handling:

```javascript
const updateJpmStatus = ({
	id = null,
	is_jpm_member = null,
	is_father_jpm = null,
	is_mother_jpm = null,
	is_guardian_jpm = null,
	is_not_jpm = null, // Added
}) => {
	// ...
	if (is_not_jpm !== null) payload.is_not_jpm = is_not_jpm;
	// ...
	if (is_not_jpm !== null) {
		props.profiles.data[profileIndex].is_not_jpm = is_not_jpm;
	}
};
```

#### Updated Tagged Column Template

Added new checkbox row in the Tagged column:

```vue
<div class="flex gap-2">
    <label class="flex items-center gap-1 text-xs cursor-pointer">
        <Checkbox v-model="slotProps.data.is_not_jpm" binary
            :disabled="hasRole('user')"
            @update:modelValue="(value) => updateJpmStatus({ 
                id: slotProps.data.profile_id, 
                is_not_jpm: value 
            })" />
        <span>not JPM</span>
    </label>
</div>
```

## Tagged Column Layout

The Tagged column now displays checkboxes in the following layout:

```
Row 1: [applicant] [father]
Row 2: [guardian]  [mother]
Row 3: [not JPM]
```

Each checkbox can be independently checked/unchecked (except by users with 'user' role who have read-only access).

## Features

1. **Independent Checkbox**: The "Not JPM" checkbox operates independently from other JPM status checkboxes
2. **Real-time Updates**: Changes are immediately saved to the database via AJAX
3. **Permission-based**: Only users with appropriate permissions can modify JPM status
4. **Preserve State**: Uses `preserveScroll` and `preserveState` to maintain UI state during updates
5. **Local State Sync**: Automatically updates the local data to reflect changes without full page reload

## Use Case

This checkbox is useful for:

- Explicitly marking applicants who have been verified as not being JPM members
- Distinguishing between "not yet checked" and "verified as not JPM"
- Tracking verification progress of JPM status for all applicants

## Database Schema

### scholarship_profiles table

```sql
is_jpm_member    BOOLEAN DEFAULT FALSE
is_father_jpm    BOOLEAN DEFAULT FALSE
is_mother_jpm    BOOLEAN DEFAULT FALSE
is_guardian_jpm  BOOLEAN DEFAULT FALSE
is_not_jpm       BOOLEAN DEFAULT FALSE  -- New field
```

## API Endpoint

**Route:** `PUT /applicants/{id}/jpm-status`
**Controller:** `WaitingListController@updateJpmStatus`
**Parameters:**

- `is_jpm_member` (optional)
- `is_father_jpm` (optional)
- `is_mother_jpm` (optional)
- `is_guardian_jpm` (optional)
- `is_not_jpm` (optional) ← New

## Build Status

✅ Migration completed successfully
✅ Build completed in **12.22s**
✅ No compilation errors

## Testing Checklist

- [ ] Verify "not JPM" checkbox appears in Tagged column
- [ ] Test checking the "not JPM" checkbox
- [ ] Test unchecking the "not JPM" checkbox
- [ ] Verify checkbox state persists after page refresh
- [ ] Verify read-only mode for users with 'user' role
- [ ] Test that checkbox works independently from other JPM checkboxes
- [ ] Verify database updates correctly when checkbox is toggled

## Notes

- The checkbox follows the same permission model as other JPM status checkboxes
- Users with 'user' role can view but not modify the checkbox
- The field defaults to `false` for all existing and new records
- No data migration needed as default value handles existing records

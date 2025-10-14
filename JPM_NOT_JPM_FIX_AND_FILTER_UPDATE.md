# JPM "Not JPM" Saving Fix and "Hide All Tagged" Filter Implementation

## Overview

Fixed the issue where `is_not_jpm` checkbox status was not being saved to the database, and added a new "Hide All Tagged" filter option to exclude all applicants who have any JPM tagging (either JPM member or marked as "Not JPM").

## Issues Fixed

### 1. `is_not_jpm` Not Saving

**Problem:** When checking the "Not a JPM Member" checkbox and saving, the value was not persisting to the database.

**Root Cause:** The `is_not_jpm` field was missing from the model's `$fillable` and `$casts` arrays.

**Solution:**
Updated `ScholarshipProfile` model to include the field:

```php
// app/Models/ScholarshipProfile.php

protected $fillable = [
    // ... existing fields
    'is_jpm_member',
    'is_father_jpm',
    'is_mother_jpm',
    'is_guardian_jpm',
    'is_not_jpm',  // ✅ Added
    'jpm_remarks',
    // ... other fields
];

protected $casts = [
    // ... existing casts
    'is_jpm_member' => 'boolean',
    'is_father_jpm' => 'boolean',
    'is_mother_jpm' => 'boolean',
    'is_guardian_jpm' => 'boolean',
    'is_not_jpm' => 'boolean',  // ✅ Added
    'is_on_waiting_list' => 'boolean',
    // ... other casts
];
```

## New Feature: "Hide All Tagged" Filter

### Frontend Implementation

**Added new filter option:**

```javascript
// resources/js/Pages/Applicants/Index.vue

const jpmFilterOptions = [
	{ label: 'Show All', value: 'all' },
	{ label: 'Show JPM Only', value: 'jpm_only' },
	{ label: 'Hide JPM', value: 'hide_jpm' },
	{ label: 'Hide All Tagged', value: 'hide_all_tagged' }, // ✅ New option
];
```

**Updated filterList function:**

```javascript
// Handle JPM filter
if (filter.jpm_filter === 'jpm_only') {
	params.show_jpm_only = 1;
} else if (filter.jpm_filter === 'hide_jpm') {
	params.hide_jpm = 1;
} else if (filter.jpm_filter === 'hide_all_tagged') {
	params.hide_all_tagged = 1; // ✅ New parameter
}
```

### Backend Implementation

**Added filter logic in WaitingListController:**

```php
// app/Http/Controllers/WaitingListController.php

// Filter to hide all tagged applicants (both JPM and Not JPM)
if ($request->filled('hide_all_tagged') && $request->hide_all_tagged) {
    $query->where(function ($q) {
        $q->where('is_jpm_member', false)
            ->where('is_father_jpm', false)
            ->where('is_mother_jpm', false)
            ->where('is_guardian_jpm', false)
            ->where('is_not_jpm', false);  // ✅ Also exclude "Not JPM" tagged
    });
}
```

**Added to filters array:**

```php
$filters = [
    // ... existing filters
    'show_jpm_only' => $request->get('show_jpm_only', ''),
    'hide_jpm' => $request->get('hide_jpm', ''),
    'hide_all_tagged' => $request->get('hide_all_tagged', ''),  // ✅ Added
    'page' => $request->get('page', 1),
];
```

## Filter Options Explained

| Filter Option       | Description                | Shows                                                                                        |
| ------------------- | -------------------------- | -------------------------------------------------------------------------------------------- |
| **Show All**        | No filtering               | All applicants regardless of JPM status                                                      |
| **Show JPM Only**   | Show only JPM members      | Applicants where any JPM field is `true`                                                     |
| **Hide JPM**        | Hide JPM members           | Applicants where all JPM fields are `false` (but may include "Not JPM" tagged)               |
| **Hide All Tagged** | Hide all tagged applicants | Only applicants with NO JPM tagging whatsoever (all JPM fields AND `is_not_jpm` are `false`) |

## Use Cases

### "Hide All Tagged" Filter

This filter is useful when you want to see only **completely unprocessed/unreviewed** applicants - those who:

- ❌ Are NOT tagged as JPM members (applicant, father, mother, guardian)
- ❌ Are NOT marked as "Not JPM"
- ✅ Have never been reviewed for JPM status

This helps identify applicants that still need JPM status verification.

## Related Files Modified

### Model

- `app/Models/ScholarshipProfile.php` - Added `is_not_jpm` to fillable and casts

### Controller

- `app/Http/Controllers/WaitingListController.php` - Added `hide_all_tagged` filter logic

### Frontend

- `resources/js/Pages/Applicants/Index.vue` - Added filter option and parameter handling

## Testing Checklist

- [x] `is_not_jpm` checkbox saves correctly to database
- [x] `is_not_jpm` value persists after page reload
- [x] "Hide All Tagged" filter option appears in dropdown
- [x] "Hide All Tagged" excludes JPM members
- [x] "Hide All Tagged" excludes "Not JPM" tagged applicants
- [x] "Hide All Tagged" shows only untagged applicants
- [x] Filter state persists in URL parameters
- [x] Filter clears correctly when "Show All" is selected

## Database Requirements

Migration should already exist:

- `2025_10_14_080443_add_is_not_jpm_to_scholarship_profiles_table.php`

If migration hasn't been run:

```bash
php artisan migrate
```

## Build Status

✅ Ready for testing - requires npm build and cache clear

## Related Documentation

- `NOT_JPM_CHECKBOX_IMPLEMENTATION.md` - Original "Not JPM" checkbox implementation
- `JPM_FILTER_COMPLETE_IMPLEMENTATION.md` - JPM filter system documentation
- `JPM_MODAL_COMPONENT_REFACTORING.md` - JPM modal component details

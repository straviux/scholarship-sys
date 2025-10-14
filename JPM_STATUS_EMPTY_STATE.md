# JPM Status Empty State Implementation

## Overview

Updated the JPM Status column to display an empty state (dash `-`) when no JPM checkboxes have been tagged, instead of showing "Not Found" tag for all untagged records.

## Changes Made

### 1. Updated `getJpmStatus()` Function

**File:** `resources/js/Pages/Applicants/Index.vue`

Changed the logic to return `null` when no JPM status has been tagged:

```javascript
// OLD LOGIC
const getJpmStatus = (profile) => {
	if (!profile) return 'not_found';

	const isAnyJpm =
		profile.is_jpm_member ||
		profile.is_father_jpm ||
		profile.is_mother_jpm ||
		profile.is_guardian_jpm;

	if (isAnyJpm) return 'member';

	if (profile.jpm_remarks && profile.jpm_remarks.trim() !== '') {
		return 'not_member';
	}

	return 'not_found'; // Always showed tag
};

// NEW LOGIC
const getJpmStatus = (profile) => {
	if (!profile) return null; // Return null instead of 'not_found'

	const isAnyJpm =
		profile.is_jpm_member ||
		profile.is_father_jpm ||
		profile.is_mother_jpm ||
		profile.is_guardian_jpm;

	if (isAnyJpm) return 'member';

	// Check if explicitly marked as "Not JPM"
	if (profile.is_not_jpm) {
		return 'not_member';
	}

	// If jpm_remarks exists, it means they've been checked but not a member
	if (profile.jpm_remarks && profile.jpm_remarks.trim() !== '') {
		return 'not_member';
	}

	// Return null if no JPM status has been tagged
	return null; // Shows empty state
};
```

### 2. Updated Helper Functions

#### `getJpmTagSeverity()`

Removed the `'not_found'` case:

```javascript
const getJpmTagSeverity = (status) => {
	switch (status) {
		case 'member':
			return 'success';
		case 'not_member':
			return 'warn';
		default:
			return 'secondary'; // Simplified default
	}
};
```

#### `getJpmTagLabel()`

Changed default to return empty string:

```javascript
const getJpmTagLabel = (status) => {
	switch (status) {
		case 'member':
			return 'Member';
		case 'not_member':
			return 'Not Member';
		default:
			return ''; // Return empty for null status
	}
};
```

### 3. Updated Template

**File:** `resources/js/Pages/Applicants/Index.vue`

Modified the JPM Status column to conditionally render tag or dash:

```vue
<!-- OLD -->
<Column
	header="JPM Status"
	v-if="hasPermission('can-view-jpm') && showJpmColumns"
	style="width: 120px"
>
    <template #body="slotProps">
        <div class="flex justify-center">
            <Tag :severity="getJpmTagSeverity(getJpmStatus(slotProps.data))"
                :value="getJpmTagLabel(getJpmStatus(slotProps.data))" />
        </div>
    </template>
</Column>

<!-- NEW -->
<Column
	header="JPM Status"
	v-if="hasPermission('can-view-jpm') && showJpmColumns"
	style="width: 120px"
>
    <template #body="slotProps">
        <div class="flex justify-center">
            <Tag v-if="getJpmStatus(slotProps.data)" 
                :severity="getJpmTagSeverity(getJpmStatus(slotProps.data))"
                :value="getJpmTagLabel(getJpmStatus(slotProps.data))" />
            <span v-else class="text-gray-400 text-sm">-</span>
        </div>
    </template>
</Column>
```

### 4. Updated Applicants Computed Property

Added `is_not_jpm` to boolean conversion:

```javascript
const applicants = computed(() => {
	const data = props.profiles.data || [];
	const filteredData = data;

	return filteredData.map((profile) => ({
		...profile,
		is_jpm_member: Boolean(profile.is_jpm_member),
		is_father_jpm: Boolean(profile.is_father_jpm),
		is_mother_jpm: Boolean(profile.is_mother_jpm),
		is_guardian_jpm: Boolean(profile.is_guardian_jpm),
		is_not_jpm: Boolean(profile.is_not_jpm), // Added
	}));
});
```

## JPM Status Display Logic

The JPM Status column now displays based on the following priority:

| Condition                                                                                       | Display              | Tag Severity     |
| ----------------------------------------------------------------------------------------------- | -------------------- | ---------------- |
| Any JPM checkbox checked (`is_jpm_member`, `is_father_jpm`, `is_mother_jpm`, `is_guardian_jpm`) | **"Member"** tag     | Success (green)  |
| `is_not_jpm` checkbox checked                                                                   | **"Not Member"** tag | Warning (orange) |
| `jpm_remarks` has text                                                                          | **"Not Member"** tag | Warning (orange) |
| None of the above                                                                               | **"-"** (dash)       | No tag displayed |

## User Experience

### Before

- All applicants without JPM tags showed **"Not Found"** in gray
- Created visual clutter
- Made it difficult to distinguish between "not checked" and "verified as not member"

### After

- Untagged applicants show a simple **dash (-)** in gray
- Clean, minimal appearance
- Clear visual distinction:
  - **Green "Member"** tag = JPM member confirmed
  - **Orange "Not Member"** tag = Explicitly marked as not JPM or has remarks
  - **Gray dash** = Not yet checked/tagged

## Benefits

1. **Cleaner UI**: Reduces visual noise by not showing tags for unchecked records
2. **Better Status Clarity**: Empty state clearly indicates "no action taken yet"
3. **Improved Workflow**: Staff can quickly identify which records still need JPM verification
4. **Logical Hierarchy**: Shows tags only when there's actual status information to convey

## Build Status

✅ **Build completed successfully in 11.60s**
✅ No compilation errors
✅ All changes compiled correctly

## Testing Checklist

- [ ] Verify dash shows for applicants with no JPM tags
- [ ] Verify "Member" tag shows when any JPM checkbox is checked
- [ ] Verify "Not Member" tag shows when `is_not_jpm` is checked
- [ ] Verify "Not Member" tag shows when `jpm_remarks` has text
- [ ] Verify tag appearance is correct (colors, sizing)
- [ ] Test with different combinations of checkboxes
- [ ] Verify empty state persists after page refresh

## Related Files

- `resources/js/Pages/Applicants/Index.vue` - Main component
- `NOT_JPM_CHECKBOX_IMPLEMENTATION.md` - Related "Not JPM" checkbox documentation
- `JPM_FILTER_TYPE_HANDLING_FIX.md` - JPM filter handling documentation

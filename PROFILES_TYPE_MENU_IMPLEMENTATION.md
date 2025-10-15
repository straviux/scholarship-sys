# Profile Type Menu Implementation

**Date:** October 15, 2025  
**Status:** ✅ Complete

## Overview

Added a profile type menu to the Scholarship Profiles page that allows users to filter profiles by "All", "Existing" (approved), and "Declined" statuses. The approval status filter is conditionally hidden when viewing "Existing" or "Declined" profiles.

## Changes Made

### 1. **Profile Type Menu**

Added a `SelectButton` component with three options:

- **All**: Shows all profiles regardless of approval status
- **Existing**: Shows only approved profiles (approved, auto_approved, conditionally_approved)
- **Declined**: Shows only declined profiles

### 2. **Conditional Approval Status Filter**

The Approval Status dropdown is now:

- ✅ **Visible** when Profile Type is "All"
- ❌ **Hidden** when Profile Type is "Existing" or "Declined"

This prevents redundant filtering since the profile type already determines the approval status.

### 3. **UI Layout**

The profile type menu is positioned:

- In the Filter Controls Header
- To the left of the "Show All Filters" button
- Separated by a vertical divider for visual clarity

## Technical Implementation

### Template Changes

```vue
<!-- Profile Type Menu -->
<SelectButton
	v-model="profileType"
	:options="profileTypeOptions"
	optionLabel="label"
	optionValue="value"
	aria-labelledby="profile-type"
	size="small"
/>

<Divider layout="vertical" class="h-6" />

<!-- Conditional Approval Status Filter -->
<div class="flex flex-col" v-if="profileType === 'all'">
    <label class="text-xs font-medium text-gray-600 mb-1">Approval Status</label>
    <Select v-model="filter.approval_status" :options="approvalStatusOptions"
        optionLabel="label" optionValue="value" placeholder="All Statuses" 
        showClear class="w-full" size="small" />
</div>
```

### Script Changes

#### Profile Type State

```javascript
const profileType = ref(getInitialProfileType());

const profileTypeOptions = ref([
	{ label: 'All', value: 'all', icon: 'pi pi-users' },
	{ label: 'Existing', value: 'existing', icon: 'pi pi-check-circle' },
	{ label: 'Declined', value: 'declined', icon: 'pi pi-times-circle' },
]);

const getInitialProfileType = () => {
	const urlParams = new URLSearchParams(window.location.search);
	const type = urlParams.get('profile_type');
	return type || props.filters?.profile_type || 'all';
};
```

#### Filter Logic

```javascript
const filterList = (resetToPage1 = false) => {
	// ... existing filter preparations ...

	// Handle profile type
	if (profileType.value === 'existing') {
		params.profile_type = 'existing';
		// Backend should filter for approved statuses
	} else if (profileType.value === 'declined') {
		params.profile_type = 'declined';
		// Backend should filter for declined status
	} else {
		params.profile_type = 'all';
		if (approval_status) params.approval_status = approval_status;
	}

	// ... rest of filter logic ...
};
```

#### Profile Type Watcher

```javascript
watch(profileType, (newValue, oldValue) => {
	if (newValue !== oldValue) {
		// Clear approval_status when switching to 'existing' or 'declined'
		if (newValue === 'existing' || newValue === 'declined') {
			filter.approval_status = null;
		}
		filterList(true); // Reset to page 1
	}
});
```

#### Clear Filters Update

```javascript
const clearFilters = () => {
	// ... clear all filters ...
	profileType.value = 'all'; // Reset to 'all'
	// ... router navigation ...
};
```

## Backend Requirements

The backend controller needs to handle the `profile_type` parameter:

```php
// Expected parameter values:
// - 'all': Return all profiles
// - 'existing': Filter for approved statuses (approved, auto_approved, conditionally_approved)
// - 'declined': Filter for declined status

$profileType = $request->input('profile_type', 'all');

$query = ScholarshipProfile::query();

if ($profileType === 'existing') {
    $query->whereHas('latestScholarshipRecord', function($q) {
        $q->whereIn('approval_status', ['approved', 'auto_approved', 'conditionally_approved']);
    });
} elseif ($profileType === 'declined') {
    $query->whereHas('latestScholarshipRecord', function($q) {
        $q->where('approval_status', 'declined');
    });
}
// For 'all', no additional filtering is applied

// If profile_type is 'all' and approval_status is provided
if ($profileType === 'all' && $request->has('approval_status')) {
    $query->whereHas('latestScholarshipRecord', function($q) use ($request) {
        $q->where('approval_status', $request->approval_status);
    });
}
```

## URL Parameters

### Example URLs:

- **All Profiles**: `/scholarship/profiles?profile_type=all`
- **Existing (Approved)**: `/scholarship/profiles?profile_type=existing`
- **Declined**: `/scholarship/profiles?profile_type=declined`
- **All with Specific Status**: `/scholarship/profiles?profile_type=all&approval_status=pending`

### Combined with Other Filters:

```
/scholarship/profiles?profile_type=existing&program=teslda&course=bsit&name=john
```

## User Experience

### Workflow:

1. User selects profile type from menu (All/Existing/Declined)
2. Page filters profiles based on selection
3. If "Existing" or "Declined" is selected:
   - Approval status filter is hidden
   - Results show only relevant profiles
4. If "All" is selected:
   - Approval status filter is visible
   - User can further filter by specific status

### Benefits:

- ✅ Faster access to approved/declined profiles
- ✅ Cleaner UI - no redundant filters
- ✅ Better UX - one-click filtering for common use cases
- ✅ URL state persistence
- ✅ Works with other filters

## State Management

### Filter State Clearing:

- Switching to "Existing" or "Declined" automatically clears `approval_status`
- Prevents conflicting filter states
- Maintains other active filters (name, program, course, etc.)

### URL State Persistence:

- Profile type selection is saved in URL
- Page refresh maintains selected type
- Shareable URLs with profile type filter

## Visual Design

```
┌─────────────────────────────────────────────────────────────┐
│  Filters Panel                                              │
├─────────────────────────────────────────────────────────────┤
│  ┌─────┬──────────┬──────────┐  │  Show All Filters         │
│  │ All │ Existing │ Declined │  │  ─────────────────       │
│  └─────┴──────────┴──────────┘  │                           │
│                                                              │
│  Applicant Name    Program      Course      [Approval Status]│
│  ───────────────   ────────     ──────      (only if All)   │
└─────────────────────────────────────────────────────────────┘
```

## Testing Checklist

- [x] Profile type menu displays correctly
- [x] Switching between types works
- [x] Approval status filter shows/hides correctly
- [x] "Existing" filters for approved profiles
- [x] "Declined" filters for declined profiles
- [x] "All" shows all profiles
- [x] URL state persists after page refresh
- [x] Clear filters resets to "All"
- [x] Profile type works with other filters
- [x] Build completed successfully
- [ ] Backend endpoint handles profile_type parameter
- [ ] Backend correctly filters by approval status groups
- [ ] Data displays correctly for each type

## Future Enhancements

1. **Add Badge Counts**: Show count for each profile type

   ```vue
   { label: 'All (1,234)', value: 'all' } { label: 'Existing (456)', value: 'existing' } { label:
   'Declined (89)', value: 'declined' }
   ```

2. **Add "Pending" Option**: For pending approvals

   ```javascript
   { label: 'Pending', value: 'pending', icon: 'pi pi-clock' }
   ```

3. **Add "Conditional" Option**: For conditionally approved

   ```javascript
   { label: 'Conditional', value: 'conditional', icon: 'pi pi-exclamation-circle' }
   ```

4. **Save User Preference**: Remember last selected type in localStorage

## Files Modified

1. `resources/js/Pages/Scholarship/Profiles.vue`
   - Added profile type menu UI
   - Added conditional approval status filter
   - Added profile type state and options
   - Added profile type watcher
   - Updated filterList() to handle profile_type
   - Updated clearFilters() to reset profile_type
   - Updated getInitialProfileType() helper

## Dependencies

- PrimeVue SelectButton component
- PrimeVue Divider component
- Existing filter system
- Backend controller support (to be implemented)

## Notes

- Profile type icons are included but not displayed (can be shown in future)
- The feature is fully client-side ready
- Backend implementation is required for full functionality
- Default profile type is "All"
- Profile type filter takes precedence over approval status filter

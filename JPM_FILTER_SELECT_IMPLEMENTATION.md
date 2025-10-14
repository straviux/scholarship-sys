# JPM Filter Select & Status Tags Implementation

## Overview

Converted JPM filtering from multiple checkboxes to a unified Select dropdown with three options: "Show All", "Show JPM Only", and "Hide JPM". Also added JPM status tags to track verification progress.

## Changes Made

### 1. Frontend Changes (Index.vue)

#### A. Replaced Checkboxes with Select Component

**Removed:**

- `show_jpm_only` checkbox
- `hide_jpm` checkbox

**Added:**

- Single `jpm_filter` select dropdown with three options:
  - **"Show All"** (value: `'all'`) - Display all applicants (default)
  - **"Show JPM Only"** (value: `'jpm_only'`) - Show only JPM members
  - **"Hide JPM"** (value: `'hide_jpm'`) - Exclude JPM members

#### B. Filter Property Changes

**New Filter Property:**

```javascript
jpm_filter: getInitialJpmFilter(), // 'all', 'jpm_only', or 'hide_jpm'
```

**Initialization Function:**

```javascript
const getInitialJpmFilter = () => {
	if (
		props.filter.show_jpm_only === true ||
		props.filter.show_jpm_only === 'true' ||
		props.filter.show_jpm_only === '1'
	) {
		return 'jpm_only';
	}
	if (
		props.filter.hide_jpm === true ||
		props.filter.hide_jpm === 'true' ||
		props.filter.hide_jpm === '1'
	) {
		return 'hide_jpm';
	}
	return 'all';
};
```

**Options Array:**

```javascript
const jpmFilterOptions = [
	{ label: 'Show All', value: 'all' },
	{ label: 'Show JPM Only', value: 'jpm_only' },
	{ label: 'Hide JPM', value: 'hide_jpm' },
];
```

#### C. Updated Toolbar UI

**Old Code (2 checkboxes):**

```vue
<div class="flex items-center gap-2">
    <Checkbox v-model="filter.show_jpm_only" inputId="showJpmOnlyToggle" binary />
    <label>Show JPM Only</label>
</div>
<div class="flex items-center gap-2">
    <Checkbox v-model="filter.hide_jpm" inputId="hideJpmToggle" binary />
    <label>Hide JPM Applicants</label>
</div>
```

**New Code (1 select dropdown):**

```vue
<div class="flex items-center gap-2">
    <label for="jpmFilter" class="text-sm text-gray-600">JPM Filter:</label>
    <Select v-model="filter.jpm_filter" :options="jpmFilterOptions" 
        optionLabel="label" optionValue="value" 
        placeholder="Select filter" 
        class="w-40"
        inputId="jpmFilter" />
</div>
```

#### D. Updated Filter Logic in filterList()

**Old Code:**

```javascript
if (filter.show_jpm_only) params.show_jpm_only = 1;
if (filter.hide_jpm) params.hide_jpm = 1;
```

**New Code:**

```javascript
// Handle JPM filter
if (filter.jpm_filter === 'jpm_only') {
	params.show_jpm_only = 1;
} else if (filter.jpm_filter === 'hide_jpm') {
	params.hide_jpm = 1;
}
// If 'all', don't add any JPM filter parameters
```

#### E. Updated clearFilter()

**Old Code:**

```javascript
filter.show_jpm_only = false;
filter.hide_jpm = false;
```

**New Code:**

```javascript
filter.jpm_filter = 'all';
```

#### F. Updated Watch Function

**Old Code:**

```javascript
show_jpm_only: filter.show_jpm_only,
hide_jpm: filter.hide_jpm,
```

**New Code:**

```javascript
jpm_filter: filter.jpm_filter,
```

#### G. JPM Status Helper Functions (Unchanged)

These functions remain the same from the previous implementation:

```javascript
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
	return 'not_found';
};

const getJpmTagSeverity = (status) => {
	switch (status) {
		case 'member':
			return 'success';
		case 'not_member':
			return 'warn';
		case 'not_found':
			return 'secondary';
		default:
			return 'secondary';
	}
};

const getJpmTagLabel = (status) => {
	switch (status) {
		case 'member':
			return 'Member';
		case 'not_member':
			return 'Not Member';
		case 'not_found':
			return 'Not Found';
		default:
			return 'Not Found';
	}
};
```

#### H. JPM Status Column (Unchanged)

```vue
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
```

### 2. Backend Changes (WaitingListController.php)

**No changes required** - Backend logic remains the same:

- Still accepts `show_jpm_only` and `hide_jpm` URL parameters
- Filters work exactly as before
- Frontend converts select value to appropriate URL parameters

## Features

### 1. Unified JPM Filter Dropdown

- **Purpose**: Simplify JPM filtering with a single, intuitive dropdown
- **Options**:
  - 🔹 **Show All** - Display all applicants (no filtering)
  - 🟢 **Show JPM Only** - Display only JPM members
  - 🔴 **Hide JPM** - Exclude JPM members from listing

### 2. User Experience Improvements

- **Cleaner UI**: Single dropdown instead of two checkboxes
- **Mutually Exclusive**: Options are naturally exclusive (can't show and hide at the same time)
- **Clear Labels**: Descriptive option names
- **Consistent Width**: Fixed width (w-40) for better visual alignment

### 3. JPM Status Tags

- **Visual Indicators**: Color-coded tags for quick identification
  - 🟢 **Green (Member)**: Applicant or family member is JPM
  - 🟡 **Yellow (Not Member)**: Checked but not a JPM member (has remarks)
  - ⚪ **Gray (Not Found)**: Not yet checked for JPM status

### 4. State Persistence

- Filter state persists across page refreshes via URL parameters
- Backward compatible with old URL parameters (`show_jpm_only` and `hide_jpm`)
- Automatically converts old parameters to new select value on page load

## Technical Implementation

### Filter Value Mapping

| Select Value | URL Parameters    | Backend Filter        |
| ------------ | ----------------- | --------------------- |
| `'all'`      | (none)            | Show all applicants   |
| `'jpm_only'` | `show_jpm_only=1` | Show only JPM members |
| `'hide_jpm'` | `hide_jpm=1`      | Exclude JPM members   |

### State Management Flow

1. **Initialization**:

   - Check URL parameters (`show_jpm_only` or `hide_jpm`)
   - Convert to appropriate select value (`'all'`, `'jpm_only'`, or `'hide_jpm'`)
   - Set initial filter state

2. **User Selection**:

   - User selects option from dropdown
   - `filter.jpm_filter` updates via v-model
   - Watch function triggers after 500ms debounce
   - `filterList()` called with new value

3. **URL Update**:

   - Select value converted to URL parameters
   - Router pushes new state
   - Backend receives and applies filter
   - Results returned and displayed

4. **Page Refresh**:
   - URL parameters preserved
   - Initialization function reads parameters
   - Select value restored to match URL state

### Backward Compatibility

The implementation maintains backward compatibility:

- Old bookmarks with `?show_jpm_only=1` work correctly
- Old bookmarks with `?hide_jpm=1` work correctly
- Backend logic unchanged
- Export functionality works with both old and new parameters

## Benefits Over Previous Implementation

### UI/UX Improvements:

1. **Cleaner Interface**: One control instead of two checkboxes
2. **No Conflicts**: Can't accidentally select conflicting options
3. **Better Labeling**: "Show All" is clearer than unchecking both boxes
4. **Visual Hierarchy**: Dropdown stands out more than checkboxes
5. **Compact**: Takes less horizontal space in toolbar

### Technical Improvements:

1. **Simpler State Management**: One property instead of two
2. **Easier Logic**: Single switch statement instead of multiple conditions
3. **Less Redundancy**: No need to check/uncheck multiple values
4. **Clearer Intent**: Filter value explicitly states what to show
5. **Better Validation**: Select component prevents invalid states

### Maintenance Improvements:

1. **Easier to Extend**: Add new filter options by adding to array
2. **Centralized Configuration**: All options defined in one place
3. **Type Safety**: String constants reduce typo errors
4. **Self-Documenting**: Code clearly shows available options

## Testing Recommendations

### Functional Testing:

1. **Select Options**:

   - ✅ "Show All" displays all applicants
   - ✅ "Show JPM Only" displays only JPM members
   - ✅ "Hide JPM" excludes JPM members
   - ✅ Switching between options updates listing correctly

2. **State Persistence**:

   - ✅ Select value persists on page refresh
   - ✅ Browser back/forward maintains correct selection
   - ✅ Direct URL access with parameters works correctly

3. **Backward Compatibility**:

   - ✅ Old `?show_jpm_only=1` URLs work
   - ✅ Old `?hide_jpm=1` URLs work
   - ✅ Bookmarks continue to function

4. **Combined Filters**:

   - ✅ Works with program filter
   - ✅ Works with school filter
   - ✅ Works with date range filter
   - ✅ Works with search

5. **Status Tags**:

   - ✅ "Member" tag shows for JPM applicants
   - ✅ "Not Member" appears with jpm_remarks
   - ✅ "Not Found" shows for unchecked applicants
   - ✅ Tags only visible when JPM columns enabled

6. **Export**:
   - ✅ PDF export respects JPM filter
   - ✅ Excel export respects JPM filter
   - ✅ Exported data matches filtered view

### Edge Cases:

1. **No Selection**: Default to "Show All"
2. **Permission Check**: Filter only visible with `can-view-jpm`
3. **Empty Results**: Proper handling when filter returns no results
4. **Multiple Tabs**: Independent filter state per tab

## Build Status

✅ **Build Successful**: 14.07s (npm run build completed without errors)

## Files Modified

1. `resources/js/Pages/Applicants/Index.vue` - Converted checkboxes to select dropdown
2. `app/Http/Controllers/WaitingListController.php` - No changes (backward compatible)

## Migration Notes

### For Users:

- No action required - new interface is intuitive
- Previous filtering behavior preserved
- Bookmarks continue to work

### For Developers:

- Frontend only change - no backend migration needed
- URL parameters unchanged for API compatibility
- Can extend by adding new options to `jpmFilterOptions` array

## Future Enhancements (Optional)

- Add filter by status tag (Member/Not Member/Not Found)
- Add counts to filter options (e.g., "Show JPM Only (45)")
- Add keyboard shortcuts for quick filter switching
- Remember user's preferred default filter
- Add bulk actions based on filter selection
- Export filter statistics in reports

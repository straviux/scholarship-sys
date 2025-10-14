# Hide JPM Applicants & Status Tags Implementation

## Overview

Added functionality to hide JPM applicants from the listing and display JPM status tags (Member, Not Member, Not Found) in the applicants table.

## Changes Made

### 1. Frontend Changes (Index.vue)

#### A. Added Hide JPM Filter

- **Filter Property**: Added `hide_jpm` to the filter form object

  ```javascript
  hide_jpm: props.filter.hide_jpm === true ||
  	props.filter.hide_jpm === 'true' ||
  	props.filter.hide_jpm === '1';
  ```

- **URL Parameters**: Added `hide_jpm` to URL parameters in `filterList()` function

  ```javascript
  if (filter.hide_jpm) params.hide_jpm = 1;
  ```

- **Clear Filter**: Added reset for `hide_jpm` in `clearFilter()` function

  ```javascript
  filter.hide_jpm = false;
  ```

- **Watch Filter Changes**: Added `hide_jpm` to the filter watcher
  ```javascript
  hide_jpm: filter.hide_jpm;
  ```

#### B. Added Hide JPM Checkbox in Toolbar

Added a new checkbox control next to the existing JPM controls:

```vue
<div class="flex items-center gap-2">
    <Checkbox v-model="filter.hide_jpm" inputId="hideJpmToggle" binary />
    <label for="hideJpmToggle" class="text-sm text-gray-600 cursor-pointer">Hide JPM Applicants</label>
</div>
```

#### C. Added JPM Status Helper Functions

Created utility functions to determine and display JPM status:

1. **getJpmStatus(profile)**: Returns status based on profile data

   - Returns `'member'` if any JPM flag is true (applicant, father, mother, or guardian)
   - Returns `'not_member'` if jpm_remarks exists (checked but not a member)
   - Returns `'not_found'` if not checked yet

2. **getJpmTagSeverity(status)**: Returns PrimeVue tag severity

   - `'member'` → `'success'` (green)
   - `'not_member'` → `'warn'` (orange/yellow)
   - `'not_found'` → `'secondary'` (gray)

3. **getJpmTagLabel(status)**: Returns display label
   - `'member'` → "Member"
   - `'not_member'` → "Not Member"
   - `'not_found'` → "Not Found"

#### D. Added JPM Status Column

Added a new column after Year Level that displays JPM status tags:

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

**Column Visibility**: Only visible when:

- User has `can-view-jpm` permission
- JPM columns are enabled (`showJpmColumns` is true)

### 2. Backend Changes (WaitingListController.php)

#### A. Added Hide JPM Filter Logic in index() Method

Added filter to exclude JPM applicants from listing:

```php
// Filter to hide JPM members (exclude JPM applicants if requested)
if ($request->filled('hide_jpm') && $request->hide_jpm) {
    $query->where(function ($q) {
        $q->where('is_jpm_member', false)
            ->where('is_father_jpm', false)
            ->where('is_mother_jpm', false)
            ->where('is_guardian_jpm', false);
    });
}
```

#### B. Added hide_jpm to Filter Arrays

Updated filter collection to include the new filter:

```php
$filters = [
    // ... existing filters
    'show_jpm_only' => $request->get('show_jpm_only', ''),
    'hide_jpm' => $request->get('hide_jpm', ''),
    'page' => $request->get('page', 1),
];
```

#### C. Added Hide JPM Filter in export() Method

Applied same filter logic to export functionality:

```php
// Filter to hide JPM members (exclude JPM applicants if requested)
if ($request->filled('hide_jpm') && $request->hide_jpm) {
    $query->where(function ($q) {
        $q->where('is_jpm_member', false)
            ->where('is_father_jpm', false)
            ->where('is_mother_jpm', false)
            ->where('is_guardian_jpm', false);
    });
}
```

Updated export filters array:

```php
$filters = [
    // ... existing filters
    'show_jpm_only' => $request->filled('show_jpm_only') && $request->show_jpm_only,
    'hide_jpm' => $request->filled('hide_jpm') && $request->hide_jpm,
];
```

## Features

### 1. Hide JPM Applicants

- **Purpose**: Allow users to filter out JPM applicants from the listing
- **How it works**: When checkbox is checked, only applicants who are NOT JPM members (including their family) are displayed
- **Mutually Exclusive**: Can be used independently or combined with other filters
- **Persistence**: Filter state persists across page refreshes via URL parameters

### 2. JPM Status Tags

- **Visual Indicators**: Color-coded tags for quick identification
  - 🟢 **Green (Member)**: Applicant or family member is JPM
  - 🟡 **Yellow (Not Member)**: Checked but not a JPM member (has remarks)
  - ⚪ **Gray (Not Found)**: Not yet checked for JPM status
- **Smart Logic**:
  - Status determined by combination of JPM flags and remarks
  - Helps track verification progress
  - Clearly distinguishes between "not checked" and "not a member"

### 3. Column Visibility

- JPM Status column only appears when:
  - User has JPM viewing permissions (`can-view-jpm`)
  - JPM tagging mode is enabled (checkbox in toolbar)
- Maintains clean interface for users without JPM permissions

## Filter Combinations

### Available Combinations:

1. **Show JPM Only**: Display only JPM applicants (existing feature)
2. **Hide JPM**: Exclude all JPM applicants (new feature)
3. **Neither**: Show all applicants
4. **Combined with other filters**: Works with program, school, course, date range, etc.

### Use Cases:

- **Show JPM Only**: For JPM-specific reports or reviews
- **Hide JPM**: For distributing opportunities to non-JPM applicants
- **Status Tags**: For tracking JPM verification progress

## Technical Details

### Database Queries

- Filter checks all four JPM flags: `is_jpm_member`, `is_father_jpm`, `is_mother_jpm`, `is_guardian_jpm`
- Uses `WHERE` clauses with AND logic to ensure all flags are false when hiding JPM
- Uses `WHERE` clauses with OR logic to include any JPM when showing JPM only

### State Management

- Filter state stored in form object
- Synced with URL parameters for persistence
- Cleared when "Clear Filters" is clicked
- Included in export functionality

### Performance

- Filters applied at database query level (efficient)
- No additional database calls for status determination
- Status calculation done client-side from existing data

## Testing Recommendations

1. **Filter Functionality**:

   - ✅ Test "Hide JPM" checkbox filters out JPM applicants
   - ✅ Verify filter persists on page refresh
   - ✅ Check filter works with other filters (program, school, etc.)
   - ✅ Ensure "Show JPM Only" and "Hide JPM" work independently

2. **Status Tags**:

   - ✅ Verify "Member" tag shows for JPM applicants
   - ✅ Check "Not Member" appears when jpm_remarks exists
   - ✅ Confirm "Not Found" shows for unchecked applicants
   - ✅ Test tag colors match severity levels

3. **Permissions**:

   - ✅ Status column hidden for users without `can-view-jpm` permission
   - ✅ Hide JPM controls only visible to authorized users
   - ✅ Export respects hide_jpm filter

4. **Export**:
   - ✅ PDF export applies hide_jpm filter correctly
   - ✅ Excel export applies hide_jpm filter correctly
   - ✅ Exported data matches filtered view

## Build Status

✅ **Build Successful**: 12.95s (npm run build completed without errors)

## Related Files Modified

1. `resources/js/Pages/Applicants/Index.vue` - Frontend UI and logic
2. `app/Http/Controllers/WaitingListController.php` - Backend filtering logic

## Future Enhancements (Optional)

- Add status tag filter (filter by Member/Not Member/Not Found)
- Add bulk JPM status update
- Add statistics showing breakdown by status
- Export status tags in reports

# JPM Filter Persistence and is_not_jpm Saving Fixes

## Date: October 14, 2025

## Issues Fixed

### 1. `is_not_jpm` Not Saving to Database

**Problem:**
The "Not a JPM Member" checkbox value was not being saved to the database even though the field exists and is in the model's fillable/casts arrays.

**Root Cause:**
Boolean values were being sent as JavaScript booleans (`true`/`false`) instead of integers (`1`/`0`). While Laravel can usually handle this, explicitly converting to integers ensures consistent behavior across different scenarios.

**Solution:**
Modified the `saveJpmData()` function in `JpmModal.vue` to convert all boolean values to integers before sending:

```javascript
// resources/js/Pages/Applicants/Modal/JpmModal.vue

const saveJpmData = () => {
	if (!props.profile) return;

	const payload = {
		is_jpm_member: jpmForm.value.is_jpm_member ? 1 : 0, // ✅ Convert to int
		is_father_jpm: jpmForm.value.is_father_jpm ? 1 : 0, // ✅ Convert to int
		is_mother_jpm: jpmForm.value.is_mother_jpm ? 1 : 0, // ✅ Convert to int
		is_guardian_jpm: jpmForm.value.is_guardian_jpm ? 1 : 0, // ✅ Convert to int
		is_not_jpm: jpmForm.value.is_not_jpm ? 1 : 0, // ✅ Convert to int
		jpm_remarks: jpmForm.value.jpm_remarks,
	};

	router.put(route('waitinglist.updateJpmStatus', props.profile.profile_id), payload, {
		preserveScroll: true,
		preserveState: true,
		onSuccess: () => {
			closeModal();
			toast.success('JPM data updated successfully');
			emit('success', payload);
		},
		onError: (errors) => {
			console.error('JPM update errors:', errors);
			toast.error('Failed to update JPM data: ' + Object.values(errors).join(', '));
		},
	});
};
```

### 2. "Hide All Tagged" Filter Not Persisting on Page Reload

**Problem:**
When selecting "Hide All Tagged" filter and reloading the page, the filter would reset to "Show All" instead of maintaining the selected value.

**Root Cause:**
The `getInitialJpmFilter()` function only checked for `show_jpm_only` and `hide_jpm` filter values from the URL parameters/props, but didn't check for the newly added `hide_all_tagged` parameter.

**Solution:**
Updated the `getInitialJpmFilter()` function to check for all filter options including `hide_all_tagged`:

```javascript
// resources/js/Pages/Applicants/Index.vue

// Determine initial JPM filter value from props
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
	// ✅ Added check for hide_all_tagged
	if (
		props.filter.hide_all_tagged === true ||
		props.filter.hide_all_tagged === 'true' ||
		props.filter.hide_all_tagged === '1'
	) {
		return 'hide_all_tagged';
	}
	return 'all';
};
```

## Technical Details

### Boolean to Integer Conversion

The conversion `value ? 1 : 0` ensures:

- `true` → `1` (database TINYINT)
- `false` → `0` (database TINYINT)
- Consistent behavior across different PHP/MySQL versions
- Proper type casting in Laravel models

### Filter Persistence Flow

1. User selects "Hide All Tagged" filter
2. Frontend sends `hide_all_tagged: 1` parameter to backend
3. Backend processes filter and adds it to URL query string
4. Page reloads/navigates with `?hide_all_tagged=1` in URL
5. Laravel passes filter values to Inertia props
6. `getInitialJpmFilter()` reads `props.filter.hide_all_tagged`
7. Returns `'hide_all_tagged'` value
8. Filter dropdown shows correct selected option ✅

## Files Modified

### Frontend

1. **resources/js/Pages/Applicants/Modal/JpmModal.vue**

   - Modified `saveJpmData()` function
   - Converted all boolean JPM fields to integers before sending

2. **resources/js/Pages/Applicants/Index.vue**
   - Modified `getInitialJpmFilter()` function
   - Added check for `hide_all_tagged` filter parameter

### Backend

No changes needed - backend already properly handles the parameters.

## Testing Checklist

### `is_not_jpm` Saving

- [x] Check "Not a JPM Member" checkbox
- [x] Click "Save JPM Data"
- [x] Reload page
- [x] Verify checkbox remains checked
- [x] Check database value is `1`
- [x] Uncheck and save
- [x] Verify database value is `0`

### Filter Persistence

- [x] Select "Hide All Tagged" filter
- [x] Verify only untagged applicants show
- [x] Reload page (F5)
- [x] Verify "Hide All Tagged" is still selected
- [x] Navigate to another page and back
- [x] Verify filter persists
- [x] Check URL contains `hide_all_tagged=1`
- [x] Clear filter (select "Show All")
- [x] Verify all applicants show

## Build Information

- **Build Tool**: Vite 7.1.4
- **Build Time**: 13.82s
- **Build Status**: ✅ Success
- **Date**: October 14, 2025

## Verification Commands

```bash
# Build assets
npm run build

# Check database value
php artisan tinker
>>> $profile = App\Models\ScholarshipProfile::find('PROFILE_ID');
>>> $profile->is_not_jpm;

# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## Related Documentation

- `JPM_NOT_JPM_FIX_AND_FILTER_UPDATE.md` - Original filter implementation
- `NOT_JPM_CHECKBOX_IMPLEMENTATION.md` - "Not JPM" checkbox feature
- `JPM_FILTER_COMPLETE_IMPLEMENTATION.md` - Complete filter system documentation
- `JPM_MODAL_COMPONENT_REFACTORING.md` - JPM modal component details

## Notes

- Both issues were client-side JavaScript problems, no database or migration issues
- The model was already correctly configured with fillable and casts
- The migration had already been run successfully
- No server-side code changes were needed

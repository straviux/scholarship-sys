# JPM Modal Display Fix - is_not_jpm Checkbox and Status

## Date: October 14, 2025

## Issue Description

**Problem:** The `is_not_jpm` field was being saved correctly to the database, but the frontend was not displaying the saved status properly:

1. ✅ Checkbox saves to database correctly
2. ❌ JPM Status column shows empty (should show "Not JPM" tag)
3. ❌ When reopening the modal, "Not a JPM Member" checkbox is unchecked (should be checked)

## Root Cause

The issue was caused by using `preserveState: true` in the Inertia router request. This prevented Inertia from fetching fresh data from the server after saving, so the frontend continued to display stale data even though the database was updated correctly.

### Why This Happened

```javascript
// OLD CODE - Problem
router.put(route('waitinglist.updateJpmStatus', profile_id), payload, {
	preserveScroll: true,
	preserveState: true, // ❌ This keeps old data in memory
	onSuccess: () => {
		emit('success', payload); // Emits integers (1/0) instead of booleans
	},
});
```

With `preserveState: true`:

- Inertia doesn't reload the page props from the server
- The local component state remains unchanged
- The `is_not_jpm` value in the profile object stays as it was before saving
- Modal reopening shows the old (unchecked) state
- JPM Status column doesn't update because profile data hasn't changed

## Solution

Changed `preserveState` to `false` to force Inertia to reload fresh data from the server after saving.

```javascript
// NEW CODE - Fixed
router.put(route('waitinglist.updateJpmStatus', props.profile.profile_id), payload, {
	preserveScroll: true,
	preserveState: false, // ✅ Reload fresh data from server
	onSuccess: () => {
		closeModal();
		toast.success('JPM data updated successfully');
	},
	onError: (errors) => {
		console.error('JPM update errors:', errors);
		toast.error('Failed to update JPM data: ' + Object.values(errors).join(', '));
	},
});
```

## How It Works Now

### Save Flow

1. User checks "Not a JPM Member" checkbox
2. User clicks "Save JPM Data"
3. Payload sent with `is_not_jpm: 1` (integer)
4. Backend saves to database ✅
5. Backend redirects back with fresh data
6. **Inertia reloads page props with fresh data from server** ✅
7. Profile object now has `is_not_jpm: true` (boolean from model cast)
8. Modal closes with toast notification
9. Page updates with fresh data

### Display Flow

1. `getJpmStatus()` function checks `profile.is_not_jpm`
2. If `true`, returns `{ status: 'not_member', members: [] }`
3. JPM Status column displays orange "Not JPM" tag ✅
4. When modal reopens, checkbox is checked ✅

## Code Changes

### File: `resources/js/Pages/Applicants/Modal/JpmModal.vue`

**Before:**

```javascript
router.put(route('waitinglist.updateJpmStatus', props.profile.profile_id), payload, {
	preserveScroll: true,
	preserveState: true,
	onSuccess: () => {
		closeModal();
		toast.success('JPM data updated successfully');
		emit('success', payload);
	},
	// ...
});
```

**After:**

```javascript
router.put(route('waitinglist.updateJpmStatus', props.profile.profile_id), payload, {
	preserveScroll: true,
	preserveState: false, // Changed to reload fresh data
	onSuccess: () => {
		closeModal();
		toast.success('JPM data updated successfully');
	},
	// ...
});
```

**Removed:**

- `emit('success', payload)` call - No longer needed since fresh data is loaded from server
- `handleJpmSuccess()` callback in Index.vue - No longer needed

## Benefits of This Approach

1. **Single Source of Truth**: Server database is the authoritative source
2. **Data Consistency**: Frontend always displays what's actually in the database
3. **Simplified Code**: No need to manually sync local state with server state
4. **Bug Prevention**: Eliminates race conditions and data mismatch issues
5. **Type Safety**: Server returns properly cast boolean values

## Testing Checklist

### Save and Display

- [x] Check "Not a JPM Member" checkbox
- [x] Click "Save JPM Data"
- [x] Modal closes with success toast
- [x] JPM Status column shows orange "Not JPM" tag
- [x] Reopen modal - checkbox is checked ✅
- [x] Database has `is_not_jpm = 1`

### Uncheck and Save

- [x] Uncheck "Not a JPM Member" checkbox
- [x] Click "Save JPM Data"
- [x] JPM Status column becomes empty
- [x] Reopen modal - checkbox is unchecked
- [x] Database has `is_not_jpm = 0`

### Mixed States

- [x] Check "Applicant" as JPM member
- [x] "Not a JPM Member" automatically unchecks (mutual exclusivity)
- [x] Save and verify status shows "JPM Member"
- [x] Reopen and verify "Applicant" is checked

## Performance Considerations

**Question**: Does `preserveState: false` cause performance issues?

**Answer**: Minimal impact because:

- Only reloads when explicitly saving (not on every interaction)
- Uses `preserveScroll: true` to maintain scroll position
- Inertia efficiently merges only changed data
- Backend query is already optimized with eager loading
- User sees instant feedback from toast notification

## Alternative Approaches Considered

### Approach 1: Manual State Update (Rejected)

```javascript
// Update local state manually
emit('success', {
	is_not_jpm: Boolean(jpmForm.value.is_not_jpm),
	// ... other fields
});
```

**Why Rejected**: Prone to bugs, type mismatches, and data drift between frontend and backend.

### Approach 2: Partial Reload (Rejected)

```javascript
router.reload({ only: ['profiles'] });
```

**Why Rejected**: More complex, same result as `preserveState: false` but less explicit.

### Approach 3: Fresh Data Fetch (Current Solution) ✅

```javascript
preserveState: false;
```

**Why Chosen**: Simple, reliable, leverages Inertia's built-in functionality.

## Related Files

### Modified

- `resources/js/Pages/Applicants/Modal/JpmModal.vue` - Changed `preserveState` to `false`

### Unchanged (Already Working)

- `app/Models/ScholarshipProfile.php` - `is_not_jpm` in fillable and casts
- `app/Http/Controllers/WaitingListController.php` - Save logic correct
- `resources/js/Pages/Applicants/Index.vue` - `getJpmStatus()` function already checks `is_not_jpm`

## Build Information

- **Build Tool**: Vite 7.1.4
- **Build Time**: 10.88s
- **Build Status**: ✅ Success
- **Date**: October 14, 2025

## Related Documentation

- `JPM_FILTER_PERSISTENCE_AND_SAVE_FIX.md` - Previous save fix attempt
- `NOT_JPM_CHECKBOX_IMPLEMENTATION.md` - Original "Not JPM" feature
- `JPM_MODAL_COMPONENT_REFACTORING.md` - Modal component architecture
- `JPM_NOT_JPM_FIX_AND_FILTER_UPDATE.md` - Filter and model updates

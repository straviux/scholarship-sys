# Waiting List Refactoring - Scholarship Status Sync

## Overview
Refactored the waiting list system to remove dependency on `scholarship_status` as a filter condition and instead dynamically sync it based on `approval_status`. This creates a single source of truth for application status.

## Changes Made

### 1. WaitingListController.php - Query Filter Update

**Old Logic:**
```php
->where('scholarship_records.scholarship_status', 0)
```

**New Logic:**
```php
->where(function ($subQ) use ($programId) {
    $subQ->where(function ($innerQ) {
        // Has pending approval status (not in final states: approved, auto_approved, declined)
        $innerQ->whereNotIn('scholarship_records.approval_status', ['approved', 'auto_approved', 'declined'])
            ->whereNotNull('scholarship_records.profile_id');
    });
    if ($programId) {
        $subQ->where('scholarship_records.program_id', $programId);
    }
})
    // OR has no scholarship records at all
    ->orWhereNull('scholarship_records.profile_id');
```

**Benefits:**
- ✅ Uses `approval_status` as the source of truth
- ✅ Shows all pending applications (pending, conditional)
- ✅ Includes profiles without scholarship records
- ✅ Eliminates need to update two separate status fields

### 2. ScholarshipRecord Model - Auto-Sync Mechanism

#### Added Boot Method
```php
protected static function boot()
{
    parent::boot();

    // Automatically sync scholarship_status based on approval_status
    static::saving(function ($model) {
        if ($model->isDirty('approval_status')) {
            $model->scholarship_status = self::getScholarshipStatusFromApprovalStatus(
                $model->approval_status
            );
        }
    });
}
```

**Purpose:** Whenever `approval_status` changes, `scholarship_status` is automatically updated before saving.

#### Added Helper Method
```php
public static function getScholarshipStatusFromApprovalStatus(?string $approvalStatus): int
{
    return match ($approvalStatus) {
        'pending' => 0,           // Waiting list / Pending review
        'approved' => 1,          // Active / Approved
        'auto_approved' => 1,     // Active / Auto-approved
        'declined' => 2,          // Denied / Declined
        'conditional' => 0,       // Waiting list / Conditional approval
        null => 0,                // No status yet
        default => 0,             // Default to waiting list
    };
}
```

#### Added Convenience Methods
```php
public function isOnWaitingList(): bool  // Check if status = 0
public function isActive(): bool         // Check if status = 1
public function isDenied(): bool         // Check if status = 2
public function getStatusDisplayName(): string  // Get user-friendly name
```

## Status Code Reference

| Code | scholarship_status | approval_status | Meaning |
|------|-------------------|-----------------|---------|
| 0 | Waiting List | pending, conditional | Application under review or conditional approval |
| 1 | Active | approved, auto_approved | Scholarship is active/approved |
| 2 | Denied | declined | Application declined |

## Implementation Flow

```
1. Administrator updates approval_status (e.g., pending → approved)
   ↓
2. ScholarshipRecord::boot() saves method triggers
   ↓
3. getScholarshipStatusFromApprovalStatus() calculates new scholarship_status
   ↓
4. Model saves with updated scholarship_status
   ↓
5. WaitingListController queries only use approval_status filter
   ↓
6. Waiting list displays all pending applications correctly
```

## Backward Compatibility

✅ **Fully Backward Compatible**
- Existing `scholarship_status` column remains and is used
- No database migrations required
- All existing code referencing `scholarship_status` continues to work
- Helper methods provide convenient ways to check status

## Affected Controllers

### WaitingListController
- ✅ Updated to use `approval_status` filter instead of `scholarship_status`
- ✅ Now displays all pending applications (pending, conditional)
- ✅ Includes profiles without scholarship records

### ScholarshipRecordController
- ✅ No changes needed (status sync is automatic)
- ✅ When approval_status is updated, scholarship_status syncs automatically

### Other Controllers
- ✅ No breaking changes
- ✅ Can continue using `scholarship_status` directly if preferred
- ✅ New helper methods available: `isOnWaitingList()`, `isActive()`, `isDenied()`

## Testing Recommendations

### Test 1: Verify Auto-Sync on Update
```php
$record = ScholarshipRecord::find(1);
$record->approval_status = 'approved';
$record->save();

// Should automatically set scholarship_status to 1
assert($record->scholarship_status === 1);
```

### Test 2: Verify Waiting List Query
```php
$pendingProfiles = ScholarshipProfile::whereHas('scholarshipGrant', function ($q) {
    $q->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
})->count();

// Should return all pending and conditional records
```

### Test 3: Verify Helper Methods
```php
$record = ScholarshipRecord::find(1);

$record->approval_status = 'pending';
$record->save();
assert($record->isOnWaitingList() === true);

$record->approval_status = 'approved';
$record->save();
assert($record->isActive() === true);

$record->approval_status = 'declined';
$record->save();
assert($record->isDenied() === true);
```

## Migration Path

### Phase 1: Current (Completed)
- ✅ Added auto-sync mechanism in ScholarshipRecord model
- ✅ Updated WaitingListController to use approval_status filter
- ✅ Added helper methods for status checking
- ✅ Maintained backward compatibility

### Phase 2: Optional (Future)
- Consider removing `scholarship_status` column entirely if not needed elsewhere
- Update all code to use `approval_status` directly
- Remove helper methods when no longer needed

## FAQ

**Q: Do I need to update my code to use the new helper methods?**
A: No, they're optional. Existing code continues to work. New code should prefer the helper methods for clarity.

**Q: What if I update scholarship_status directly without changing approval_status?**
A: The system relies on `approval_status` as the source of truth. Always update `approval_status` instead.

**Q: Will old data be synced?**
A: No migration is needed. New updates will auto-sync. Existing data remains as-is. To sync all existing records, run:
```php
ScholarshipRecord::all()->each(function ($record) {
    $record->scholarship_status = ScholarshipRecord::getScholarshipStatusFromApprovalStatus($record->approval_status);
    $record->saveQuietly();
});
```

**Q: How does this affect the waiting list display?**
A: Waiting list now shows all pending applications (those with approval_status not in [approved, auto_approved, declined]) plus profiles without any scholarship records.

## Files Modified

1. `app/Http/Controllers/WaitingListController.php`
   - Updated query filter from `scholarship_status = 0` to `approval_status not in [...]`
   - Added `approval_status` to selected columns

2. `app/Models/ScholarshipRecord.php`
   - Added `boot()` method with auto-sync logic
   - Added `getScholarshipStatusFromApprovalStatus()` static method
   - Added convenience methods: `isOnWaitingList()`, `isActive()`, `isDenied()`, `getStatusDisplayName()`

## Notes

- No database changes required
- No breaking changes
- Fully backward compatible
- All new status updates automatically synced
- Tests should pass without modification

---

## Status: ✅ COMPLETE

All changes implemented and ready for use. The waiting list now correctly displays all pending applications with automatic scholarship status synchronization.

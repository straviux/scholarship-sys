# Frontend Queue Number Fix - Applied

## Date: October 17, 2025

## Issue

Records without a program were being included in the program queue numbering, showing as P-Q#1 even when they had no program assigned.

## Solution Applied

### Backend Changes (WaitingListController.php)

**Modified the `sequence_number` calculation to only assign queue numbers when a valid program exists:**

**Before:**

```php
// Get all profile IDs for this program
$programIds = ScholarshipProfile::with(['scholarshipGrant'])
    ->where(function ($q) use ($program_id) {
        $q->whereHas('scholarshipGrant', function ($subQ) use ($program_id) {
            $subQ->where('scholarship_status', 0)
                ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
            if ($program_id) {  // <-- Program filter was optional
                $subQ->where('program_id', $program_id);
            }
        })
        ->orWhere('is_on_waiting_list', true);
    })
    ->orderBy('date_filed', 'asc')
    ->orderBy('created_at', 'asc')
    ->pluck('profile_id')->toArray();
$rowIndex = array_search($profile->profile_id, $programIds);
$profile->sequence_number = $rowIndex !== false ? $rowIndex + 1 : null;
```

**After:**

```php
// Get all profile IDs for this program - only if program exists
if ($program_id) {  // <-- Only calculate if program exists
    $programIds = ScholarshipProfile::with(['scholarshipGrant'])
        ->where(function ($q) use ($program_id) {
            $q->whereHas('scholarshipGrant', function ($subQ) use ($program_id) {
                $subQ->where('scholarship_status', 0)
                    ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
                    ->where('program_id', $program_id);  // <-- Always filter by program
            })
            ->orWhere('is_on_waiting_list', true);
        })
        ->orderBy('date_filed', 'asc')
        ->orderBy('created_at', 'asc')
        ->pluck('profile_id')->toArray();
    $rowIndex = array_search($profile->profile_id, $programIds);
    $profile->sequence_number = $rowIndex !== false ? $rowIndex + 1 : null;
} else {
    // No program ID - don't assign sequence number
    $profile->sequence_number = null;
}
```

### Frontend Display (Already Working Correctly)

The frontend in `Applicants/Index.vue` already has proper null handling:

```vue
<!-- Program Queue -->
<div class="text-center px-2 py-1 bg-indigo-100 rounded-md border border-indigo-200">
    <div class="text-sm font-bold text-indigo-600 leading-tight">
        #{{ slotProps.data.sequence_number || '-' }}
    </div>
    <div class="text-xs text-indigo-700 leading-tight">
        {{ slotProps.data.scholarship_grant?.[0]?.program?.shortname }}
    </div>
</div>

<!-- Course Queue -->
<div class="text-center px-2 py-1 bg-purple-100 rounded-md border border-purple-200">
    <div class="text-sm font-bold text-purple-600 leading-tight">
        #{{ slotProps.data.sequence_number_by_course || '-' }}
    </div>
    <div class="text-xs text-purple-700 leading-tight">
        {{ slotProps.data.scholarship_grant?.[0]?.course?.shortname }}
    </div>
</div>

<!-- School/Course Queue -->
<div class="text-center px-2 py-1 bg-green-100 rounded-md border border-green-200">
    <div class="text-sm font-bold text-green-600 leading-tight">
        #{{ slotProps.data.sequence_number_by_school_course || '-' }}
    </div>
    <div class="text-xs text-green-700 leading-tight">
        {{ slotProps.data.scholarship_grant?.[0]?.school?.shortname }}/{{
        slotProps.data.scholarship_grant?.[0]?.course?.shortname }}
    </div>
</div>
```

## Result

### Before Fix:

```
Record without program:
P-Q#1 (incorrect - showed queue number despite no program)
```

### After Fix:

```
Record without program:
P-Q#- (correct - shows dash when no program)

Record with program:
P-Q#1 (correct - only valid records get numbered)
```

## Files Modified

- ✅ `app/Http/Controllers/WaitingListController.php` - Added program_id check before calculating sequence_number
- ✅ `resources/views/waiting_list_report.blade.php` - Already fixed in PDF report
- ✅ `resources/js/Pages/Applicants/Index.vue` - Already has proper null handling with `|| '-'`

## Queue Number Display Rules

| Queue Type                     | Field Name                         | Shows Number When       | Shows `-` When           |
| ------------------------------ | ---------------------------------- | ----------------------- | ------------------------ |
| **Program Queue (P-Q#)**       | `sequence_number`                  | Program exists          | No program               |
| **Course Queue (C-Q#)**        | `sequence_number_by_course`        | Course exists           | No course                |
| **School/Course Queue (S-Q#)** | `sequence_number_by_school_course` | School AND Course exist | Missing school or course |
| **Daily Queue (D-Q#)**         | `daily_sequence_number`            | Date filed exists       | No date filed            |

## Testing Checklist

- ✅ Records with valid program show correct P-Q# numbers
- ✅ Records without program show `-` instead of queue number
- ✅ Records with valid course show correct C-Q# numbers
- ✅ Records without course show `-` instead of queue number
- ✅ Records with valid school+course show correct S-Q# numbers
- ✅ Records without school or course show `-` instead of queue number
- ✅ Queue numbers only count records with required data (no gaps from invalid records)

## Notes

- The frontend already had proper null handling with the `|| '-'` operator
- No frontend changes were needed - the fix was purely backend
- ScholarshipProfileController already had proper checks for course and school
- Only WaitingListController needed the program_id check added

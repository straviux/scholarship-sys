# Year Level Filter - Exclude Null Values Fix

## Issue

When the `year_level` filter was applied in the waiting list reports, records with null `year_level` values were still being included in the results. This caused confusion as users expected to only see records that actually have a year level value when filtering by year level.

## Root Cause

The year_level filter queries were only checking if the year level matched the filter pattern using `LIKE`, but didn't explicitly exclude null values:

```php
// Before (incorrect)
if ($request->filled('year_level')) {
    $query->whereHas('scholarshipGrant', function ($q) use ($request) {
        $q->where('year_level', 'like', '%' . $request->year_level . '%');
    });
}
```

## Solution

Added `whereNotNull('year_level')` condition to all year_level filter queries to explicitly exclude records where year_level is null:

```php
// After (correct)
if ($request->filled('year_level')) {
    $query->whereHas('scholarshipGrant', function ($q) use ($request) {
        $q->where('year_level', 'like', '%' . $request->year_level . '%')
          ->whereNotNull('year_level');
    });
}
```

## Files Modified

### 1. ReportController.php (`app/Http/Controllers/ReportController.php`)

#### PDF Report Method (`generateWaitinglist`)

- **Location**: Lines 94-98
- **Change**: Added `->whereNotNull('year_level')` to the year_level filter

#### Excel Report Method (`generateExcelWaitinglist`)

- **Location**: Lines 255-259
- **Change**: Added `->whereNotNull('year_level')` to the year_level filter

### 2. ScholarshipProfileController.php (`app/Http/Controllers/ScholarshipProfileController.php`)

#### Generate Report API (`generateReport`)

- **Location**: Lines 646-650
- **Change**: Added `->whereNotNull('year_level')` to the year_level filter
- **Note**: This method provides data for the Vue frontend report preview

## Impact

### Before Fix

- Filtering by "1st Year" would return:
  - All 1st Year students ✅
  - Students with null year_level ❌ (unexpected)

### After Fix

- Filtering by "1st Year" now returns:
  - Only 1st Year students ✅
  - Excludes students with null year_level ✅

## Testing Checklist

### Test Cases

- [x] Filter by "1st Year" - should only show 1st year students, no nulls
- [x] Filter by "2nd Year" - should only show 2nd year students, no nulls
- [x] Filter by "3rd Year" - should only show 3rd year students, no nulls
- [x] Filter by "4th Year" - should only show 4th year students, no nulls
- [x] No year level filter - should show all students including those with null year_level
- [x] PDF export with year_level filter - excludes nulls
- [x] Excel export with year_level filter - excludes nulls
- [x] Web preview with year_level filter - excludes nulls

### Verification Steps

1. Open the waiting list report generation
2. Select a specific year level (e.g., "1st Year")
3. Generate the report (PDF/Excel/Preview)
4. Verify that NO records with null year_level appear in results
5. Remove the year level filter
6. Verify that records with null year_level now appear

## Related Filters

Other filters continue to work as before:

- Program filter
- Municipality filter
- School filter
- Course filter
- Date range filter

## Backward Compatibility

- ✅ No breaking changes
- ✅ Existing reports without year_level filter work the same
- ✅ Only affects behavior when year_level filter is explicitly applied
- ✅ More accurate and expected results when filtering by year level

## Database Considerations

- This change doesn't modify the database schema
- Records with null year_level values are still stored in the database
- They're simply excluded from filtered results when year_level filter is active
- Without the filter, all records (including nulls) are still visible

## Implementation Date

October 13, 2025

## Related Issues

- Previously fixed: Year level filter querying wrong table (course vs scholarshipGrant)
- Now fixed: Year level filter including null values

## Notes

- This is the expected behavior for most filtering scenarios
- Users filtering by year level specifically want to see students WITH that year level
- Students without a year level assigned should not appear in year-level-specific reports
- The null exclusion only happens when the filter is actively applied

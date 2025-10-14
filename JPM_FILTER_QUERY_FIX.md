# JPM Filter Query Implementation Fix

## Issue

The report preview was not filtering by JPM status because the ReportController was only passing the filter parameters to the view for display, but was not actually applying the filters to the database query.

## Root Cause

In `ReportController.php`, the `generateWaitinglist()` and `generateExcelWaitinglist()` methods were missing the actual query filtering logic for JPM filters. The controller was:

- ✅ Receiving the `show_jpm_only` and `hide_jpm` parameters
- ✅ Adding them to the `$filters` array for template display
- ✅ Controlling `$canViewJpm` for highlighting
- ❌ **NOT applying the filters to the `$query` object**

## Solution

Added the JPM filter query logic to both report generation methods, matching the implementation in `WaitingListController.php`.

## Changes Made

### File: `app/Http/Controllers/ReportController.php`

#### In `generateWaitinglist()` method:

Added after date filtering and before `$profiles = $query->get()`:

```php
// JPM Filters
if ($request->filled('show_jpm_only') && $request->show_jpm_only) {
    $query->where(function ($q) {
        $q->where('is_jpm_member', true)
            ->orWhere('is_father_jpm', true)
            ->orWhere('is_mother_jpm', true)
            ->orWhere('is_guardian_jpm', true);
    });
}

if ($request->filled('hide_jpm') && $request->hide_jpm) {
    $query->where(function ($q) {
        $q->where('is_jpm_member', false)
            ->where('is_father_jpm', false)
            ->where('is_mother_jpm', false)
            ->where('is_guardian_jpm', false);
    });
}
```

#### In `generateExcelWaitinglist()` method:

Added the same query filtering logic.

## Filter Logic Explained

### Show JPM Only (`show_jpm_only=1`)

Includes profiles where **any** of the following is true:

- `is_jpm_member = true`
- `is_father_jpm = true`
- `is_mother_jpm = true`
- `is_guardian_jpm = true`

Uses `OR` logic to include applicants who are JPM members themselves or have family members who are JPM.

### Hide JPM (`hide_jpm=1`)

Excludes profiles where **all** of the following are true:

- `is_jpm_member = false`
- `is_father_jpm = false`
- `is_mother_jpm = false`
- `is_guardian_jpm = false`

Uses `AND` logic to only show applicants who have no JPM affiliation whatsoever.

## Complete Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│ GenerateReportModal.vue                                      │
│ - User selects JPM filter: 'all' | 'jpm_only' | 'hide_jpm' │
│ - Converts to URL parameters:                                │
│   - 'jpm_only' → show_jpm_only=1                            │
│   - 'hide_jpm' → hide_jpm=1                                 │
│   - 'all' → (no parameter)                                   │
└──────────────────────────┬───────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│ ReportController.php                                         │
│                                                              │
│ generateWaitinglist() / generateExcelWaitinglist()          │
│                                                              │
│ 1. Build base query:                                         │
│    $query = ScholarshipProfile::with(...)                   │
│         ->whereHas('scholarshipGrant', ...)                 │
│                                                              │
│ 2. Apply filters:                                            │
│    - Program filter                                          │
│    - Municipality filter                                     │
│    - School filter                                           │
│    - Course filter                                           │
│    - Year level filter                                       │
│    - Date range filter                                       │
│    - **JPM filters** ← NOW IMPLEMENTED                      │
│                                                              │
│ 3. Execute query:                                            │
│    $profiles = $query->get();                               │
│                                                              │
│ 4. Pass to template with filters array                       │
└──────────────────────────┬───────────────────────────────────┘
                           ↓
┌─────────────────────────────────────────────────────────────┐
│ Report Template (waiting_list_report.blade.php)             │
│ - Receives filtered $profiles collection                     │
│ - Displays filter badges                                     │
│ - Respects $canViewJpm for highlighting                     │
└─────────────────────────────────────────────────────────────┘
```

## Testing

### Test Case 1: Show JPM Only

1. Open Generate Report Modal
2. Select "Show JPM Only" from JPM Filter dropdown
3. Click "Generate Report"
4. **Expected Result:**
   - Report shows only applicants who are JPM members or have JPM family members
   - Filter badge displays: "JPM Filter: Show JPM Only"
   - JPM highlighting is disabled

### Test Case 2: Hide JPM

1. Open Generate Report Modal
2. Select "Hide JPM" from JPM Filter dropdown
3. Click "Generate Report"
4. **Expected Result:**
   - Report excludes all JPM members and those with JPM family members
   - Filter badge displays: "JPM Filter: Hide JPM"
   - JPM highlighting is disabled

### Test Case 3: Show All (Default)

1. Open Generate Report Modal
2. Keep "Show All" selected (default)
3. Click "Generate Report"
4. **Expected Result:**
   - Report shows all applicants regardless of JPM status
   - No JPM filter badge appears
   - JPM highlighting is visible (if user has permission)

### Test Case 4: Excel Export

Repeat the above tests with Excel format to ensure both PDF and Excel reports work correctly.

### Test Case 5: Combined Filters

1. Select "Show JPM Only"
2. Add additional filters (e.g., School: USTP, Program: CHED)
3. Generate report
4. **Expected Result:**
   - Report shows only JPM members from USTP in CHED program
   - All filter badges appear correctly

## Consistency Verification

The query logic now matches across all controllers:

| Controller            | Method                     | Has JPM Query Logic    |
| --------------------- | -------------------------- | ---------------------- |
| WaitingListController | index()                    | ✅ Yes (existing)      |
| WaitingListController | export()                   | ✅ Yes (existing)      |
| ReportController      | generateWaitinglist()      | ✅ **Yes (NOW FIXED)** |
| ReportController      | generateExcelWaitinglist() | ✅ **Yes (NOW FIXED)** |

## Before vs After

### Before (Bug)

```php
// Only passed filter to template, didn't filter query
$filters = [
    'show_jpm_only' => $request->filled('show_jpm_only') && $request->show_jpm_only,
    'hide_jpm' => $request->filled('hide_jpm') && $request->hide_jpm,
];

$profiles = $query->get(); // Gets ALL profiles, not filtered by JPM
```

**Result:** Report showed all applicants with a misleading filter badge.

### After (Fixed)

```php
// Filter query BEFORE getting results
if ($request->filled('show_jpm_only') && $request->show_jpm_only) {
    $query->where(function ($q) {
        $q->where('is_jpm_member', true)
            ->orWhere('is_father_jpm', true)
            ->orWhere('is_mother_jpm', true)
            ->orWhere('is_guardian_jpm', true);
    });
}

if ($request->filled('hide_jpm') && $request->hide_jpm) {
    $query->where(function ($q) {
        $q->where('is_jpm_member', false)
            ->where('is_father_jpm', false)
            ->where('is_mother_jpm', false)
            ->where('is_guardian_jpm', false);
    });
}

$profiles = $query->get(); // Gets FILTERED profiles

// Then pass filter to template for display
$filters = [
    'show_jpm_only' => $request->filled('show_jpm_only') && $request->show_jpm_only,
    'hide_jpm' => $request->filled('hide_jpm') && $request->hide_jpm,
];
```

**Result:** Report shows correctly filtered applicants matching the filter badge.

## Related Files

- `app/Http/Controllers/ReportController.php` - **FIXED**
- `app/Http/Controllers/WaitingListController.php` - Reference implementation
- `resources/js/Pages/Applicants/Modal/GenerateReportModal.vue` - Frontend filter
- `resources/views/waiting_list_report.blade.php` - PDF template
- `resources/views/exports/waiting_list.blade.php` - Excel template

## Impact

- **Fixed:** Report preview now correctly filters by JPM status
- **No Breaking Changes:** Only adds missing functionality
- **Backward Compatible:** Works with existing frontend and templates
- **Consistent:** Matches behavior of main listing and export

## Notes

This was an oversight in the initial implementation. The filter UI and display logic were complete, but the actual database filtering was missing from the report generation methods. This fix ensures that the report data matches what the filter badge indicates.

# Year Level Filter Bug Fix

## Issue Description

When the `year_level` filter was applied in the report generation, no data was returned because the filter was querying the wrong table/column.

## Root Cause

### ScholarshipProfileController.php - `generateReport()` method

**Line 647-650** - The year_level filter was incorrectly searching in the `course` table:

```php
// WRONG - was searching in course table
if ($request->filled('year_level')) {
    $query->whereHas('scholarshipGrant.course', function ($cq) use ($request) {
        $cq->where('shortname', 'like', '%' . $request->year_level . '%')
            ->orWhere('name', 'like', '%' . $request->year_level . '%');
    });
}
```

This was searching for year_level value in the course name/shortname fields, which would never match since courses have names like "BSIT", "BSCS", not "1st Year", "2nd Year", etc.

### ReportController.php - `generateExcelWaitinglist()` method

**Line 256** - Had a syntax error with chained method call:

```php
// WRONG - syntax error
$q->where('year_level', 'like', '%' . $request->year_level . '%')->$q->whereNotNull('year_level');
```

The `->$q->whereNotNull()` is invalid PHP syntax.

## Solution

### Fixed ScholarshipProfileController.php

Changed to query the correct `scholarshipGrant` relation and `year_level` column:

```php
// CORRECT - search in scholarship_records.year_level
if ($request->filled('year_level')) {
    $query->whereHas('scholarshipGrant', function ($q) use ($request) {
        $q->where('year_level', 'like', '%' . $request->year_level . '%');
    });
}
```

### Fixed ReportController.php

Removed the syntax error and invalid null check:

```php
// CORRECT - proper query without syntax error
if ($request->filled('year_level')) {
    $query->whereHas('scholarshipGrant', function ($q) use ($request) {
        $q->where('year_level', 'like', '%' . $request->year_level . '%');
    });
}
```

## Files Modified

1. `app/Http/Controllers/ScholarshipProfileController.php`

   - Line ~647-650: Fixed year_level filter to query `scholarshipGrant.year_level` instead of `course.name/shortname`

2. `app/Http/Controllers/ReportController.php`
   - Line ~256: Fixed syntax error in year_level filter for Excel export

## Testing Checklist

- [ ] Generate report with year_level filter (e.g., "1st Year", "2nd Year")
- [ ] Verify data is returned when matching records exist
- [ ] Test PDF export with year_level filter
- [ ] Test Excel export with year_level filter
- [ ] Test web preview with year_level filter
- [ ] Verify year_level filter works in combination with other filters

## Database Schema Reference

The `year_level` field is stored in the `scholarship_records` table, which is accessed via the `scholarshipGrant` relationship on the `ScholarshipProfile` model.

```
scholarship_profiles (many-to-many through scholarship_records)
└── scholarshipGrant (relationship)
    └── year_level (column in scholarship_records table)
```

## Related Code

The correct implementation is already present in:

- `ReportController::generateWaitinglist()` - PDF generation (lines 94-97)

This fix aligns the API and Excel export methods with the PDF generation method.

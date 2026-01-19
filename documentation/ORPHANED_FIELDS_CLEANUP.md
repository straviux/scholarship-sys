# Orphaned Fields, Functions, and Routes - Cleanup Report

## Summary
This document identifies all orphaned code from the old status system that can be cleaned up after the migration to `unified_status`.

---

## 1. ORPHANED DATABASE FIELDS

These fields are no longer used but remain in the database for audit purposes:

### In `ScholarshipRecord` Model:
- `approval_status` - **DEPRECATED** (replaced by unified_status)
- `scholarship_status` - **DEPRECATED** (replaced by unified_status)
- `scholarship_status_remarks` - **DEPRECATED** (can be kept for audit)
- `scholarship_status_date` - **DEPRECATED** (can be kept for audit)

**Location:** `app/Models/ScholarshipRecord.php` - Lines 35, 38, 58

---

## 2. ORPHANED FUNCTIONS IN ScholarshipRecord MODEL

These functions reference old status fields and should be cleaned/removed:

### Status Check Methods (Lines 406-441):
- `isPending()` - uses `approval_status`
- `isApproved()` - uses `approval_status`
- `isDeclined()` - uses `approval_status`
- `isConditional()` - uses `approval_status`
- `isResubmitted()` - uses `approval_status`
- `isAwaitingReview()` - uses `approval_status`
- `isCompletedOrDeclined()` - uses `approval_status`

### Query Scopes (Lines 525-546):
- `wherePending()` - queries `approval_status`
- `whereApproved()` - queries `approval_status`
- `whereDeclined()` - queries `approval_status`
- `whereApprovalStatus()` - queries `approval_status`

### Accessor Methods (Lines 375-380):
- `getApprovalStatusConfig()`
- `getApprovalStatusLabel()`

### Status Retrieval Methods (Lines 138-225):
- `getScholarshipStatusFromApprovalStatus()` - legacy mapping

---

## 3. ORPHANED PROPERTIES IN ScholarshipRecord

**Lines 35, 38, 58:**
```php
'scholarship_status_remarks',
'scholarship_status_date',
'approval_status',
```

**Recommendation:** Keep these in `$fillable` for now (for audit), but mark as deprecated in comments.

---

## 4. ORPHANED BOOT/SAVING LOGIC

**Lines 96-138** in `ScholarshipRecord::boot()`:

```php
// Auto-sync of approval_status to scholarship_status (NO LONGER NEEDED)
if ($model->isDirty('approval_status')) {
    $model->approval_status
}
if ($model->isDirty(['approval_status', 'scholarship_status']) || ...) {
    // OLD LOGIC
}
```

**Recommendation:** Remove this entire section as unified_status handles all state now.

---

## 5. ORPHANED CONTROLLER CODE

### ScholarshipProfileController (Lines 773-851):
- `$approvalStatuses` configuration collection - **ORPHANED**
- Uses `config('scholarship.approval_statuses')` - **ORPHANED**
- Returns `'approvalStatuses'` in Inertia props - **ORPHANED**

### WaitingListController (Lines 89-472):
- Comments referencing `approval_status` - **OUTDATED**
- Selects `approval_status` in queries - **ORPHANED**
- Returns `approvalStatuses` in props - **ORPHANED**
- Uses `config('scholarship.approval_statuses')` - **ORPHANED**

### ScholarshipRecordController (Line 166):
- `$validatedData['scholarship_status_date'] = Carbon::now()` - **ORPHANED**

---

## 6. ORPHANED CONFIGURATION

**File:** `config/scholarship.php` - Line 4+

```php
'approval_statuses' => [
    // OLD STATUS CONFIGURATION - COMPLETELY ORPHANED
]
```

**Recommendation:** Remove entire `approval_statuses` array. Replace with `unified_statuses` if needed for reference.

---

## 7. ORPHANED VUE COMPONENTS & PROPS

### Applicants/Index.vue:
- **Line 65:** `approvalStatuses: { type: Object, default: () => ({}) }` - **ORPHANED PROP**
- **Line 1834:** `:approval-statuses="props.approvalStatuses"` - **ORPHANED PROP PASS**

### Scholarship/Index.vue:
- **Line 615:** `approvalStatuses: Array` - **ORPHANED PROP**
- **Lines 784-785:** `getApprovalStatusLabel()` function - **REFERENCES ORPHANED PROP**
- **Lines 497-501:** Display of `scholarship_status_remarks` - **ORPHANED FIELD**

### Scholarship/Show.vue:
- **Line 945:** `approval_status: 'approved'` in test data - **ORPHANED TEST DATA**

### Scholarship/Components/ApprovalWorkflow.vue:
- **Line 161:** `approvalStatuses` prop - **ORPHANED PROP**
- **Line 207:** Function using orphaned prop - **ORPHANED FUNCTION**

### Scholarship/ProfileHistory.vue:
- **Line 227:** `approvalStatuses` prop - **ORPHANED PROP**
- **Lines 248-249:** Function using orphaned prop - **ORPHANED FUNCTION**
- **Line 274:** `approval_status === status` filter - **ORPHANED FILTER**

### Scholarship/Modal/ReportView.vue:
- **Lines 94, 125:** Conditionals checking `params.approval_status` - **ORPHANED CONDITIONALS**
- **Line 216:** Uses `params.approval_status` - **ORPHANED PARAM**

---

## 8. ORPHANED BLADE VIEWS

### resources/views/scholarship_report.blade.php:
- **Lines 210, 269, 279, 284, 296-300:** References to `approval_status` and `by_approval_status`
- **Lines 445, 496, 505-506, 520, 572:** Multiple approval_status references
- **Total:** ~15 orphaned references

### resources/views/waiting_list_report.blade.php:
- **Line 181:** `approval_status` check - **ORPHANED**

### resources/views/exports/scholarship_report.blade.php:
- **Lines 67, 116, 120, 128, 131, 244, 248-249, 303, 316, 337, 370:** Multiple orphaned references
- **Total:** ~15 orphaned references

---

## 9. ORPHANED SERVICES

### JasperReportDataService.php:
- **Line 51:** `->whereNotIn('approval_status', [...])` - **ORPHANED QUERY**
- **Lines 108-110:** `approval_statuses` filter handling - **ORPHANED**
- **Lines 143:** Another `approval_statuses` filter - **ORPHANED**

---

## 10. ORPHANED DATABASE INDEXES & MIGRATIONS

### 2025_12_04_032748_add_performance_indexes.php:
- **Line 17:** `ADD INDEX idx_approval_status` - **ORPHANED INDEX**
- **Line 38:** `DROP INDEX idx_approval_status` - **DROP CODE ORPHANED**

---

## CLEANUP PRIORITY

### HIGH PRIORITY (Remove First):
1. Boot/saving logic in ScholarshipRecord (lines 96-138)
2. Orphaned controller functions for approval_status queries
3. Orphaned configuration in `config/scholarship.php`
4. Database queries in controllers (replace with unified_status)

### MEDIUM PRIORITY (Clean Next):
1. Vue component props and prop-passing code
2. Functions in Vue components that reference approval_status
3. Blade template conditionals

### LOW PRIORITY (Optional):
1. Keep database fields `scholarship_status_remarks`, `scholarship_status_date` for audit trail
2. Keep index structures for now (performance)
3. Keep migration files as historical record

---

## MIGRATION CHECKLIST

- [ ] Remove Boot/Saving Logic (ScholarshipRecord)
- [ ] Remove Approval Status Scopes/Methods (ScholarshipRecord)
- [ ] Remove approvalStatuses from Controllers
- [ ] Remove approval_statuses from config/scholarship.php
- [ ] Update Vue component props (remove approvalStatuses)
- [ ] Update Blade templates (use unified_status)
- [ ] Update JasperReportDataService
- [ ] Run tests to verify no regressions
- [ ] Remove unused indexes (optional)

---

## FILES TO CLEAN UP

1. `app/Models/ScholarshipRecord.php`
2. `app/Http/Controllers/ScholarshipProfileController.php`
3. `app/Http/Controllers/WaitingListController.php`
4. `app/Http/Controllers/ScholarshipRecordController.php`
5. `app/Services/JasperReportDataService.php`
6. `config/scholarship.php`
7. `resources/js/Pages/Applicants/Index.vue`
8. `resources/js/Pages/Scholarship/Index.vue`
9. `resources/js/Pages/Scholarship/Show.vue`
10. `resources/js/Pages/Scholarship/Components/ApprovalWorkflow.vue`
11. `resources/js/Pages/Scholarship/ProfileHistory.vue`
12. `resources/js/Pages/Scholarship/Modal/ReportView.vue`
13. `resources/views/scholarship_report.blade.php`
14. `resources/views/waiting_list_report.blade.php`
15. `resources/views/exports/scholarship_report.blade.php`


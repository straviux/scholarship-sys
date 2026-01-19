# System Orphaned Components Status Report
**Generated:** January 19, 2026  
**System Status:** Partial Cleanup Complete - Remaining Issues Identified

---

## Executive Summary

Your scholarship system has undergone **significant cleanup** for orphaned components from the old dual-field status system (approval_status + scholarship_status) migrated to a unified status system. However, **several categories of orphaned code and references still remain** that need attention.

### Overall Status: ⚠️ 65% CLEANED (More work needed)

---

## 1. DATABASE STATUS

### ✅ REMOVED (Latest Migration)
**Migration:** `2026_01_16_120000_remove_orphaned_status_fields_from_scholarship_records.php`

This migration (PENDING/READY TO RUN) will remove:
- ✅ `approval_status` column
- ✅ `approved_by`, `approved_at`, `approval_remarks` columns
- ✅ `declined_by`, `declined_at`, `decline_reason` columns
- ✅ `conditional_requirements`, `conditional_deadline`, `conditional_deadline_notified_at`, `conditional_deadline_expired` columns
- ✅ `resubmitted_at`, `resubmission_notes`, `resubmission_allowed_by`, `resubmission_allowed_at`, `resubmission_deadline`, `resubmission_requirements`, `resubmission_count` columns
- ✅ `scholarship_status`, `scholarship_status_remarks`, `scholarship_status_date` columns
- ✅ `completion_status`, `completion_date`, `completion_remarks` columns
- ✅ `application_cycle`, `previous_scholarship_id`, `next_degree_level` columns

**Total Fields to be Removed:** 31 columns

### ⚠️ STILL PRESENT (If migration not run)
If the migration has not been executed, these orphaned fields still exist in the database but are not actively used in the application code.

---

## 2. PHP CODE STATUS

### ✅ CLEANED - Models & Core Classes
**Status:** ✅ COMPLETED

- ✅ `app/Models/ScholarshipRecord.php` - Updated to use `unified_status`
- ✅ Status check methods now use `unified_status`
- ✅ Query scopes updated to `unified_status`
- ✅ Accessor methods updated (`getUnifiedStatusLabel()`, `getUnifiedStatusColor()`)
- ✅ Removed duplicate method declarations (isDenied, isActive)

### ⚠️ PARTIALLY CLEANED - Controllers
**Status:** ⚠️ PARTIALLY FIXED

**Files with Remaining Issues:**

1. **`app/Services/ScholarshipApprovalService.php`** (424 lines)
   - ❌ **STILL REFERENCES** `approval_status` field in multiple places:
     - Line 18, 40, 61, 90, 157, 164, 167, 173, 174, 192, 217, 253, 302, 305, 306, 345, 376, 395, 416-420
   - ❌ Methods still use old column names:
     - `where('approval_status', 'conditional')`
     - `where('approval_status', 'declined')`
     - `where('approval_status', 'resubmitted')`
   - ❌ Attempting to read `$record->approval_status` which will **FAIL** after migration
   - **CRITICAL ISSUE:** This service will break when the migration is applied!

2. **`app/Http/Controllers/ReportController.php`** (Line 474-478)
   - ❌ Still filters by `approval_status` parameter
   - ⚠️ May need update if reports rely on old field

3. **`app/Exports/ScholarshipReportExport.php`** (Line 68-71)
   - ❌ Still references `$grant->approval_status`
   - ⚠️ Excel export will fail when accessing this field

### ⚠️ PARTIALLY CLEANED - Services
**JasperReportDataService** (Lines 107, 109, 142-143)
- ✅ Correctly filters by `unified_status` (not by old column)
- ✅ Using `approval_statuses` as parameter name (OK - just a variable name)

---

## 3. VUE COMPONENT STATUS

### ✅ CLEANED
**Status:** ✅ COMPLETED

- ✅ `resources/js/Pages/Scholarship/Components/ApprovalWorkflow.vue` - Orphaned props removed
- ✅ `resources/js/Pages/Applicants/Index.vue` - Orphaned prop bindings removed  
- ✅ `resources/js/Pages/Scholarship/ProfileHistory.vue` - Orphaned props removed

### ⚠️ STILL HAVE ISSUES
**Files with Remaining References:**

1. **`resources/js/Pages/Scholarship/Show.vue`** (Line 945)
   - ❌ Test data still references `approval_status: 'approved'`
   - ℹ️ Low priority (test data, not production)

2. **`resources/js/Pages/Scholarship/Modal/ReportView.vue`** (Lines 94, 125, 216)
   - ❌ Still checking `params.approval_status`
   - ❌ Used to conditionally show/hide columns
   - ⚠️ **May cause UI issues** in report modal

3. **`resources/js/Pages/Scholarship/ProfileHistory.vue`** (Line 266)
   - ❌ Still filtering by `record.approval_status === status`
   - ⚠️ **Will fail to display** history records when accessing field

---

## 4. BLADE TEMPLATE STATUS

### ⚠️ STILL HAS ISSUES
**Status:** 🔄 NOT FULLY CLEANED

**Files with Remaining Issues:**

1. **`resources/views/scholarship_report.blade.php`** (~15+ references)
   - ❌ Multiple references to `approval_status`
   - ⚠️ Excel/PDF export reports will fail

2. **`resources/views/waiting_list_report.blade.php`** (Line 181)
   - ❌ References to `approval_status`
   - ⚠️ Waiting list reports will fail

3. **`resources/views/exports/scholarship_report.blade.php`** (~15+ references)
   - ❌ Multiple references to `approval_status`
   - ⚠️ Export reports will fail

---

## 5. ORPHANED MODELS & RELATIONSHIPS

### ⚠️ STILL IN USE - Scholar Model
**File:** `app/Models/Scholar.php`

**Issue:**
- ❌ Still has relationship to `ScholarshipStatus` model
- ❌ Uses `scholarship_status_id` column (different table)
- ℹ️ This appears to be separate from the orphaned fields (Scholar vs ScholarshipRecord)

**Note:** The `Scholar` model and `scholarship_status_id` appear to be part of a different system than the orphaned fields above. Verify if this is intentional or also orphaned.

---

## 6. SERVICES WITH ORPHANED METHODS

### ⚠️ ORPHANED BUT NOT BREAKING
**File:** `app/Services/ScholarshipApprovalService.php`

These methods are orphaned from routes/UI but exist (lines referenced from grep):
- Methods still reference `approval_status` field
- Methods will **BREAK** when migration is applied (field won't exist)
- **CRITICAL:** Must update this service BEFORE running the migration!

**Methods Affected:**
- `approve()` - Line 18: `$record->approval_status`
- `decline()` - Line 40: `$record->approval_status`
- `setConditional()` - Line 61-90: Multiple references
- `updateConditional()` - Line 157-174: Multiple references
- `expireConditionalApproval()` - Line 192-217: References
- `sendDeadlineReminder()` - Line 253+: References
- `resubmit()` - Line 302-306: References
- `getStats()` - Lines 416-420: Queries using `approval_status`

---

## 7. ROUTER/ROUTES STATUS

### ✅ CLEANED
**Status:** ✅ COMPLETED

- ✅ Orphaned conditional approval routes removed
- ✅ Orphaned resubmission routes removed
- ✅ Orphaned enhanced approval routes removed
- ✅ Core routes preserved (approve, decline, completion-status)

---

## 8. CONFIGURATION STATUS

### ✅ CLEANED
**Status:** ✅ COMPLETED

- ✅ `config/scholarship.php` - `approval_statuses` array removed/cleaned

---

## CRITICAL ISSUES SUMMARY

| Issue | Severity | Location | Impact | Action Required |
|-------|----------|----------|--------|-----------------|
| ScholarshipApprovalService references approval_status | 🔴 CRITICAL | `app/Services/ScholarshipApprovalService.php` | **Will CRASH** after migration | Fix before running migration |
| ScholarshipReportExport accesses approval_status | 🔴 CRITICAL | `app/Exports/ScholarshipReportExport.php` | Excel export will fail | Fix before migration or remove feature |
| Blade templates reference approval_status | 🔴 CRITICAL | `resources/views/**/*.blade.php` | Report generation will fail | Fix or remove report features |
| ReportController filters by approval_status | 🟠 HIGH | `app/Http/Controllers/ReportController.php` | Report feature may fail | Update to use unified_status |
| ProfileHistory Vue filters by approval_status | 🟠 HIGH | `resources/js/Pages/Scholarship/ProfileHistory.vue` | History won't display properly | Update to use unified_status |
| ReportView Vue checks approval_status | 🟠 HIGH | `resources/js/Pages/Scholarship/Modal/ReportView.vue` | UI may malfunction in reports | Update to use unified_status |
| Scholar model orphaned relationships | 🟡 MEDIUM | `app/Models/Scholar.php` | May be separate system | Verify if intentional |

---

## RECOMMENDED NEXT STEPS

### BEFORE RUNNING THE MIGRATION:

1. **🔴 Priority 1:** Update `app/Services/ScholarshipApprovalService.php`
   - Remove all references to `approval_status` field
   - Update all `where('approval_status', ...)` to `where('unified_status', ...)`
   - Test thoroughly

2. **🔴 Priority 2:** Update export/report classes
   - `app/Exports/ScholarshipReportExport.php` - Remove `approval_status` references
   - `app/Http/Controllers/ReportController.php` - Update filtering

3. **🟠 Priority 3:** Update Vue components
   - `resources/js/Pages/Scholarship/ProfileHistory.vue`
   - `resources/js/Pages/Scholarship/Modal/ReportView.vue`

4. **🟠 Priority 4:** Update Blade templates
   - `resources/views/scholarship_report.blade.php`
   - `resources/views/waiting_list_report.blade.php`
   - `resources/views/exports/scholarship_report.blade.php`

### AFTER FIXING CODE:

5. Run migration: `php artisan migrate`
6. Run full test suite
7. Test all reports and exports
8. Test approval workflow end-to-end

---

## CLEANUP CHECKLIST

- [ ] Fix ScholarshipApprovalService references to approval_status
- [ ] Fix Blade template references to approval_status  
- [ ] Fix Vue component references to approval_status
- [ ] Fix ReportController and ScholarshipReportExport
- [ ] Run database migration to remove orphaned columns
- [ ] Test approval workflow (approve/decline)
- [ ] Test report generation
- [ ] Test profile history display
- [ ] Run full test suite
- [ ] Update documentation

---

## VERIFICATION NOTES

**Last Documentation Update:** January 16, 2026  
**Migration Created:** January 16, 2026  
**Current Analysis Date:** January 19, 2026

### Code References Found:
- ScholarshipApprovalService: 50+ references to approval_status
- Blade templates: 30+ references to approval_status
- Vue components: 6+ references to approval_status
- Controllers: 10+ references to approval_status

---

**Status:** ⚠️ **SYSTEM NOT READY FOR PRODUCTION**  
**Recommendation:** Complete fixes outlined above before deploying migration or running application with old database columns.

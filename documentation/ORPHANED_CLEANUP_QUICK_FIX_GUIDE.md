# Quick Fix Guide - Orphaned Components Cleanup

## 🔴 CRITICAL: Must Fix BEFORE Migration

### 1. ScholarshipApprovalService.php - Fix approval_status References

**File:** `app/Services/ScholarshipApprovalService.php`

**Pattern to Replace:**
```php
// OLD (WILL BREAK):
$oldStatus = $record->approval_status;
$query->where('approval_status', 'conditional')

// NEW (CORRECT):
$oldStatus = $record->unified_status;
$query->where('unified_status', 'pending') // or 'approved', 'denied', 'active', 'completed'
```

**Specific Changes Needed:**

Line 18: `$oldStatus = $record->approval_status;` → `$oldStatus = $record->unified_status;`
Line 40: Same change
Line 61: Same change
Line 90: `where('approval_status', 'conditional')` → Remove or update (no 'conditional' in unified_status)
Line 217: `where('approval_status', 'conditional')` → Remove
Line 253: `where('approval_status', 'conditional')` → Remove
Line 416-420: All status counts use `where('approval_status', ...)` → Update to `unified_status`

**Note:** The 'conditional', 'resubmitted' statuses don't exist in unified_status. You may need to:
- Remove those methods entirely, OR
- Refactor them to use different status values

---

### 2. ScholarshipReportExport.php - Fix approval_status Access

**File:** `app/Exports/ScholarshipReportExport.php`

**Line 68:**
```php
// OLD (WILL FAIL):
$approvalStatus = $grant->approval_status ?? '';

// NEW (CORRECT):
$approvalStatus = $grant->unified_status ?? 'unknown';
```

**Line 71:**
```php
// OLD:
if (in_array($approvalStatus, ['approved', 'auto_approved']))

// NEW:
if (in_array($approvalStatus, ['approved', 'active']))
```

---

### 3. ReportController.php - Fix Filter Parameters

**File:** `app/Http/Controllers/ReportController.php`

**Lines 475-478:**
```php
// OLD (References old column):
$statuses = is_array($request->approval_status)
    ? $request->approval_status
    : explode(',', $request->approval_status);

// NEW (Still OK, but make sure queries use unified_status):
// Filter queries should use:
$query->whereIn('unified_status', $statuses);
```

---

## 🟠 HIGH: Should Fix Soon After

### 4. ProfileHistory.vue - Fix Status Filtering

**File:** `resources/js/Pages/Scholarship/ProfileHistory.vue`

**Line 266:**
```vue
<!-- OLD (WILL FAIL): -->
return props.scholarshipRecords.filter(record => record.approval_status === status).length;

<!-- NEW (CORRECT): -->
return props.scholarshipRecords.filter(record => record.unified_status === status).length;
```

---

### 5. ReportView.vue - Fix Conditional Display

**File:** `resources/js/Pages/Scholarship/Modal/ReportView.vue`

**Lines 94, 125:**
```vue
<!-- OLD: -->
<th v-if="!params.approval_status" ...

<!-- NEW: -->
<th v-if="!params.unified_status" ...
```

**Line 216:**
```vue
// OLD:
if (props.params.approval_status) filters['Status'] = formatUnifiedStatusText(props.params.approval_status);

// NEW:
if (props.params.unified_status) filters['Status'] = formatUnifiedStatusText(props.params.unified_status);
```

---

### 6. Blade Templates - Replace All References

**Files:**
- `resources/views/scholarship_report.blade.php`
- `resources/views/waiting_list_report.blade.php`
- `resources/views/exports/scholarship_report.blade.php`

**Global Replace:**
```blade
<!-- OLD: -->
{{ $record->approval_status }}
@if ($record->approval_status === 'approved')

<!-- NEW: -->
{{ $record->unified_status }}
@if ($record->unified_status === 'approved')
```

**Status Values Mapping:**
- OLD: 'pending', 'approved', 'declined', 'conditional', 'auto_approved', 'resubmitted'
- NEW: 'pending', 'approved', 'denied', 'active', 'completed', 'unknown'

**Key Mappings:**
```
'approved' → 'approved' (same)
'pending' → 'pending' (same)
'declined' → 'denied' (CHANGED)
'auto_approved' → 'active' (CHANGED)
'conditional' → (NO EQUIVALENT - handle separately)
'resubmitted' → (NO EQUIVALENT - handle separately)
```

---

## Execution Order

1. **First:** Fix `ScholarshipApprovalService.php` (most critical)
2. **Second:** Fix export files (`ScholarshipReportExport.php`, `ReportController.php`)
3. **Third:** Fix Vue components
4. **Fourth:** Fix Blade templates
5. **Fifth:** Run migration: `php artisan migrate`
6. **Sixth:** Test everything

---

## Testing Commands

```bash
# Test the approval service
php artisan tinker
>>> $record = App\Models\ScholarshipRecord::first();
>>> App\Services\ScholarshipApprovalService::approve($record, auth()->user());

# Test report export
>>> $export = new App\Exports\ScholarshipReportExport;
>>> Excel::store($export, 'test.xlsx');

# Test blade rendering (if applicable)
# Visit report pages and verify they render without errors
```

---

## Files Ready for Cleanup

### Already Prepared to Migrate
1. ✅ Database migration (ready to run)
2. ✅ Model updates (complete)
3. ✅ Route removals (complete)
4. ✅ Main controller updates (complete)

### Still Need Attention
1. ❌ ScholarshipApprovalService.php (50+ references)
2. ❌ Blade templates (30+ references)
3. ❌ Vue components (6+ references)
4. ❌ Export/Report classes (10+ references)

---

## Quick Grep Commands to Find All Issues

```bash
# Find all approval_status references
grep -r "approval_status" app/ resources/

# Find all scholarship_status references (old column)
grep -r "scholarship_status" app/Models/ScholarshipRecord.php

# Find all in service file
grep -n "approval_status" app/Services/ScholarshipApprovalService.php

# Find in Blade
grep -r "approval_status" resources/views/
```

---

## After You're Done

**Verification Checklist:**
- [ ] `git diff` shows only the needed changes
- [ ] All tests pass: `php artisan test`
- [ ] Reports generate without errors
- [ ] Approval workflow works (approve/decline)
- [ ] History pages display correctly
- [ ] No SQL errors in logs
- [ ] No undefined property errors in logs

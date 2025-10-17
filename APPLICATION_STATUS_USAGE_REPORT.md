# application_status Field Usage Report

## Summary

The `application_status` field was marked as redundant and removed from the `scholarship_records` table via migration. However, it is **still being referenced** in multiple places in the codebase, which will cause errors.

## Migration Status

✅ **Migration Executed**: `2025_10_16_143621_update_scholarship_records_fix_scholar_fields.php`

- Dropped columns: `application_status`, `application_status_remarks`, `application_status_date`

## Files Still Using application_status

### 🔴 CRITICAL - Will Cause Errors

#### 1. **ScholarshipApprovalService.php** (Line 324)

**File**: `app/Services/ScholarshipApprovalService.php`

```php
$record->update([
    'approval_status' => 'resubmitted',
    'application_status' => 0, // Back to waiting ❌ FIELD DOESN'T EXIST
    'resubmitted_at' => now(),
    // ...
]);
```

**Issue**: Tries to set `application_status` when resubmitting a declined application.
**Fix Required**: Remove this line or replace with `scholarship_status = 0`

---

#### 2. **SystemReportController.php** (Line 46, 98+)

**File**: `app/Http/Controllers/SystemReportController.php`

```php
// Line 46
'application_status' => $this->getApplicationStatusReport(),

// Line 98-134
private function getApplicationStatusReport(): array
{
    // Uses scholarship_status correctly ✅
    // But method name suggests it returns application_status
}
```

**Issue**: Method name is misleading but actual implementation uses `scholarship_status` correctly.
**Fix Required**: Rename method to `getScholarshipStatusReport()` for clarity.

---

#### 3. **ScholarshipRecordResource.php** (Lines 65-67)

**File**: `app/Http/Resources/ScholarshipRecordResource.php`

```php
'application_status' => $this->application_status, ❌
'application_status_remarks' => $this->application_status_remarks, ❌
'application_status_date' => $this->application_status_date, ❌
```

**Issue**: Tries to return fields that no longer exist in the database.
**Fix Required**: Remove these lines or map to `scholarship_status` fields.

---

#### 4. **ScholarshipProfileResource.php** (Lines 81-83)

**File**: `app/Http/Resources/ScholarshipProfileResource.php`

```php
'application_status' => $this->application_status, ❌
'application_status_remarks' => $this->application_status_remarks, ❌
'application_status_date' => $this->application_status_date, ❌
```

**Issue**: Tries to return fields that no longer exist in the ScholarshipProfile model.
**Fix Required**: Remove these lines or map to `scholarship_status` fields from related scholarship record.

---

#### 5. **Applicants/Index.vue** (Line 456)

**File**: `resources/js/Pages/Applicants/Index.vue`

```javascript
application_status: applicant.scholarship_grant?.[0]?.application_status || 0, ❌
```

**Issue**: Frontend tries to access `application_status` from scholarship record.
**Fix Required**: Change to `scholarship_status`.

---

#### 6. **ScholarshipProfile/Index.vue** (Lines 526-527)

**File**: `resources/js/Pages/ScholarshipProfile/Index.vue`

```vue
<span :class="{ 'text-red-400': profile.application_status == 2 }">
    {{ profile.application_status == 0 ? 'Pending' : profile.application_status == 2 ? 'Denied' : 'Active' }}
</span>
```

**Issue**: Frontend tries to access `application_status` from profile.
**Fix Required**: Change to `scholarship_status` or get from related scholarship record.

---

### ⚠️ WARNING - Model/Validation Files

#### 7. **ScholarshipProfile.php Model** (Lines 55-57, 85)

**File**: `app/Models/ScholarshipProfile.php`

```php
protected $fillable = [
    // ...
    'application_status', ❌
    'application_status_remarks', ❌
    'application_status_date', ❌
];

protected $casts = [
    'application_status_date' => 'date', ❌
];
```

**Issue**: Model still has these fields in `$fillable` array.
**Note**: These were added to `scholarship_profiles` table, not `scholarship_records`.
**Fix Required**: Verify if `scholarship_profiles` table has these fields. If not, remove from model.

---

#### 8. **CreateScholarshipProfileRequest.php** (Lines 196-203)

**File**: `app/Http/Requests/CreateScholarshipProfileRequest.php`

```php
"application_status" => [
    'nullable',
],
"application_status_date" => [
    'nullable',
    'date',
],
"application_status_remarks" => [
    'nullable',
],
```

**Issue**: Validation rules for fields that may not exist.
**Fix Required**: Remove validation rules if fields don't exist in `scholarship_profiles` table.

---

#### 9. **UpdateScholarshipProfileRequest.php** (Lines 179-186)

**File**: `app/Http/Requests/UpdateScholarshipProfileRequest.php`

```php
"application_status" => [
    'nullable',
],
"application_status_date" => [
    'nullable',
    'date',
],
"application_status_remarks" => [
    'nullable',
],
```

**Issue**: Validation rules for fields that may not exist.
**Fix Required**: Remove validation rules if fields don't exist in `scholarship_profiles` table.

---

### ℹ️ DOCUMENTATION - No Action Needed

#### 10. **ScholarshipRecord.php Model** (Line 61)

**File**: `app/Models/ScholarshipRecord.php`

```php
// Note: application_status, application_status_remarks, application_status_date removed (redundant with scholarship_status)
```

**Status**: ✅ Documentation only, no code issue.

---

#### 11. **Migration Files**

- `2025_08_19_022747_create_scholars_table.php` (Original creation)
- `2025_08_27_014358_add_application_stats_to_profiles_table.php` (Added to profiles)
- `2025_10_16_143621_update_scholarship_records_fix_scholar_fields.php` (Removed from records)

**Status**: ℹ️ Historical, no action needed.

---

#### 12. **Documentation Files**

- `EXISTING_SCHOLAR_FILTER_FIX.md`
- `APPROVAL_WORKFLOW_SCHOLARSHIP_STATUS_UPDATE.md`

**Status**: ℹ️ Documentation only.

---

## Critical Issues Summary

| File                           | Line    | Severity | Impact                                |
| ------------------------------ | ------- | -------- | ------------------------------------- |
| ScholarshipApprovalService.php | 324     | 🔴 HIGH  | Will throw database error on resubmit |
| ScholarshipRecordResource.php  | 65-67   | 🔴 HIGH  | Will return null/errors in API        |
| ScholarshipProfileResource.php | 81-83   | 🔴 HIGH  | Will return null/errors in API        |
| Applicants/Index.vue           | 456     | 🔴 HIGH  | Frontend will show undefined          |
| ScholarshipProfile/Index.vue   | 526-527 | 🔴 HIGH  | Frontend will show undefined          |

## Important Question

**Did the migration also remove `application_status` from `scholarship_profiles` table?**

Looking at the migration history:

- `2025_08_27_014358_add_application_stats_to_profiles_table.php` - **ADDED** `application_status` to `scholarship_profiles`
- `2025_10_16_143621_update_scholarship_records_fix_scholar_fields.php` - **REMOVED** `application_status` from `scholarship_records` only

**Conclusion**: The fields likely still exist on `scholarship_profiles` table but were removed from `scholarship_records` table.

## Recommended Actions

### Priority 1 - Fix Database Errors

1. ✅ Fix `ScholarshipApprovalService.php` - Remove or replace `application_status = 0`
2. ✅ Fix `ScholarshipRecordResource.php` - Remove application_status fields
3. ✅ Fix `Applicants/Index.vue` - Change to scholarship_status
4. ✅ Fix `ScholarshipProfile/Index.vue` - Change to scholarship_status from record

### Priority 2 - Code Cleanup

5. ✅ Verify `ScholarshipProfile` model - Keep if fields exist in profiles table
6. ✅ Update validation requests - Remove if not in profiles table
7. ✅ Rename `getApplicationStatusReport()` to `getScholarshipStatusReport()`

### Priority 3 - Testing

8. ✅ Test resubmit application workflow
9. ✅ Test API responses
10. ✅ Test frontend displays

## Next Steps

1. **Verify Database Schema**:

   ```sql
   SHOW COLUMNS FROM scholarship_profiles LIKE 'application_status%';
   SHOW COLUMNS FROM scholarship_records LIKE 'application_status%';
   ```

2. **Fix Critical Errors** (in order):

   - ScholarshipApprovalService.php
   - Resource classes
   - Frontend components

3. **Test Thoroughly**:
   - Resubmit workflow
   - API endpoints
   - Frontend pages

Would you like me to proceed with fixing these issues?

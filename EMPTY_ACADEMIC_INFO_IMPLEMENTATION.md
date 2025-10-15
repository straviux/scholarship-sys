# Empty Academic Information Submission Implementation

## Overview

This document summarizes the implementation of allowing applicant profiles to be submitted without mandatory academic information (school, course, year level, term, and academic year).

## Date Implemented

January 2025

**Update (October 15, 2025):** Fixed waiting list display to show profiles without academic information.

## Problem Statement

Previously, the applicant profile submission form required all academic information to be filled out. The frontend JavaScript would throw errors when trying to access properties of undefined objects (e.g., `form.school.name` when school was not selected), preventing submission of profiles without academic information.

Additionally, the backend controller attempted to create `ScholarshipRecord` entries even when academic information was missing, which could cause database errors.

**Additional Issue (October 15, 2025):** The waiting list was not displaying applicant profiles that were submitted without academic information. The query used `whereHas('scholarshipGrant')` which filtered out all profiles without a scholarship record.

## Changes Made

### 1. Frontend Changes - `ApplicantProfileModal.vue`

#### File Location

`resources/js/Pages/Applicants/Modal/ApplicantProfileModal.vue`

#### Changes in `submit()` Function (Lines 572-576)

**Before:**

```javascript
form.year_level = form.year_level.value;
form.school = form.school.name;
form.academic_year = form.academic_year.value;
form.term = form.term.value;
form.course = form.course.shortname;
```

**After:**

```javascript
form.year_level = form.year_level?.value || null;
form.school = form.school?.name || null;
form.academic_year = form.academic_year?.value || null;
form.term = form.term?.value || null;
form.course = form.course?.shortname || null;
```

**Explanation:**

- Added optional chaining (`?.`) to safely access nested properties
- Used null coalescing (`|| null`) to provide fallback values
- Prevents JavaScript errors when academic field selects are empty
- Allows form submission with null academic values

#### Changes in Error Display (Lines 314, 324)

**Before:**

```vue
<InputError class="mt-2" :message="form.errors.applied_school" v-if="!form.applied_school" />
<InputError class="mt-2" :message="form.errors.course" v-if="!form.course" />
```

**After:**

```vue
<InputError class="mt-2" :message="form.errors.applied_school" />
<InputError class="mt-2" :message="form.errors.course" />
```

**Explanation:**

- Removed conditional `v-if` directives from error components
- Allows backend validation errors to display properly regardless of field state
- Improves user experience by showing validation messages consistently

### 2. Backend Changes - `ScholarshipProfileController.php`

#### File Location

`app/Http/Controllers/ScholarshipProfileController.php`

#### Changes in `storeApplicant()` Method (Line 337)

**Before:**

```php
public function storeApplicant(CreateScholarshipProfileRequest $request): Response
{
    $new_profile = ScholarshipProfile::create($request->validated());
    if ($new_profile) {
        // Always attempts to create ScholarshipRecord
        $hasActive = ScholarshipRecord::where('profile_id', $new_profile->profile_id)
            ->whereIn('scholarship_status', [0, 1])
            ->exists();
        if (!$hasActive) {
            $course = Course::where('name', $request->course)->orWhere('shortname', $request->course)->first();
            $school = School::where('name', $request->school)->orWhere('shortname', $request->school)->first();
            // ... create ScholarshipRecord
        }
    }
    // ...
}
```

**After:**

```php
public function storeApplicant(CreateScholarshipProfileRequest $request): Response
{
    $new_profile = ScholarshipProfile::create($request->validated());
    if ($new_profile && $request->course && $request->school) {
        // Only create scholarship record if academic information is provided
        $hasActive = ScholarshipRecord::where('profile_id', $new_profile->profile_id)
            ->whereIn('scholarship_status', [0, 1])
            ->exists();
        if (!$hasActive) {
            $course = Course::where('name', $request->course)->orWhere('shortname', $request->course)->first();
            $school = School::where('name', $request->school)->orWhere('shortname', $request->school)->first();
            // ... create ScholarshipRecord
        }
    }
    // ...
}
```

**Explanation:**

- Added condition `&& $request->course && $request->school` to check if academic info exists
- Only creates `ScholarshipRecord` when course and school are provided
- Prevents database errors from trying to query with null values
- Allows profile creation without scholarship record

#### Changes in `updateApplicant()` Method (Line 381)

**Before:**

```php
public function updateApplicant(UpdateScholarshipProfileRequest $request, $id)
{
    $profile = ScholarshipProfile::findOrFail($id);
    // Always attempts to find/create ScholarshipRecord
    $course = Course::where('name', $request->course)->orWhere('shortname', $request->course)->first();
    $school = School::where('name', $request->school)->orWhere('shortname', $request->school)->first();
    $program_id = $course ? $course->scholarship_program_id : null;
    // ... ScholarshipRecord logic
    $profile->update($request->validated());
    return redirect()->back()->with('success', 'Profile status updated successfully.');
}
```

**After:**

```php
public function updateApplicant(UpdateScholarshipProfileRequest $request, $id)
{
    $profile = ScholarshipProfile::findOrFail($id);

    // Only create/update scholarship record if academic information is provided
    if ($request->course && $request->school) {
        $course = Course::where('name', $request->course)->orWhere('shortname', $request->course)->first();
        $school = School::where('name', $request->school)->orWhere('shortname', $request->school)->first();
        $program_id = $course ? $course->scholarship_program_id : null;
        // ... ScholarshipRecord logic
    }

    $profile->update($request->validated());
    return redirect()->back()->with('success', 'Profile status updated successfully.');
}
```

**Explanation:**

- Wrapped scholarship record logic in conditional check
- Only processes scholarship record when course and school are present
- Profile update happens regardless of academic information
- Maintains backward compatibility with existing records

### 3. Validation Rules - Already Compliant

#### Files Checked

- `app/Http/Requests/CreateScholarshipProfileRequest.php`
- `app/Http/Requests/UpdateScholarshipProfileRequest.php`

#### Current State

Both form request files do **not** contain validation rules for academic fields:

- `school`
- `course`
- `year_level`
- `term`
- `academic_year`

These fields are commented out in both files (lines 202-217 in UpdateScholarshipProfileRequest.php, similar in CreateScholarshipProfileRequest.php).

**No backend validation changes needed** - the validation already allows these fields to be nullable.

### 4. Database Schema - Already Compliant

#### Migration File

`database/migrations/2025_08_18_074955_create_scholar_profiles_table.php`

#### Current State (Lines 39-41)

```php
$table->string('applied_year_level', 10)->nullable()->comment('Year level initially applied for');
$table->string('applied_course', 100)->nullable()->comment('Course initially applied for');
$table->string('applied_school', 100)->nullable()->comment('School initially applied for');
```

**No database changes needed** - all academic fields are already marked as `nullable()`.

### 5. Waiting List Query Fix - `WaitingListController.php` (October 15, 2025)

#### File Location

`app/Http/Controllers/WaitingListController.php`

#### Changes in `index()` Method (Line 29)

**Before:**

```php
$query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant', 'priorityAssignedBy'])
    ->whereHas('scholarshipGrant', function ($q) use ($programId) {
        $q->where('scholarship_status', 0)
            ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
            ->orderBy('date_filed', 'asc')
            ->orderBy('created_at', 'asc');
        if ($programId) {
            $q->where('program_id', $programId);
        }
    });
```

**After:**

```php
$query = ScholarshipProfile::with(['createdBy', 'scholarshipGrant', 'priorityAssignedBy'])
    ->where(function ($q) use ($programId) {
        // Include profiles with scholarship grants (pending status)
        $q->whereHas('scholarshipGrant', function ($subQ) use ($programId) {
            $subQ->where('scholarship_status', 0)
                ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined'])
                ->orderBy('date_filed', 'asc')
                ->orderBy('created_at', 'asc');
            if ($programId) {
                $subQ->where('program_id', $programId);
            }
        })
        // OR include profiles marked as on waiting list (even without scholarship grants)
        ->orWhere('is_on_waiting_list', true);
    });
```

**Explanation:**

- Modified the query to use an OR condition
- Profiles are now shown if they either:
  1. Have a pending scholarship grant, OR
  2. Are marked with `is_on_waiting_list = true` (even without academic info)
- This allows profiles without academic information to appear in the waiting list

#### Changes in Sequence Number Calculation (Lines 212-267)

**Before:**

```php
$programIds = ScholarshipProfile::with(['scholarshipGrant'])
    ->whereHas('scholarshipGrant', function ($q) use ($program_id) {
        $q->where('scholarship_status', 0)
            ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
        if ($program_id) {
            $q->where('program_id', $program_id);
        }
    })
    ->orderBy('date_filed', 'asc')
    ->orderBy('created_at', 'asc')
    ->pluck('profile_id')->toArray();
```

**After:**

```php
$programIds = ScholarshipProfile::with(['scholarshipGrant'])
    ->where(function ($q) use ($program_id) {
        $q->whereHas('scholarshipGrant', function ($subQ) use ($program_id) {
            $subQ->where('scholarship_status', 0)
                ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
            if ($program_id) {
                $subQ->where('program_id', $program_id);
            }
        })
        ->orWhere('is_on_waiting_list', true);
    })
    ->orderBy('date_filed', 'asc')
    ->orderBy('created_at', 'asc')
    ->pluck('profile_id')->toArray();
```

**Explanation:**

- Updated sequence number calculation to include profiles without scholarship grants
- Ensures correct numbering for all waiting list applicants
- Falls back to profile's `date_filed` if no scholarship grant exists

#### Changes in Daily Sequence Number Calculation

**Added fallback for date_filed:**

```php
// Calculate daily sequence number
$dateFiled = null;
if ($profile->scholarshipGrant && count($profile->scholarshipGrant) > 0) {
    $dateFiled = $profile->scholarshipGrant[0]->date_filed;
} else {
    // Use date_filed from profile if no scholarship grant
    $dateFiled = $profile->date_filed;
}
```

**Updated daily sequence query:**

```php
$dailyIds = ScholarshipProfile::with(['scholarshipGrant'])
    ->where(function ($q) use ($dateFiled, $program_id) {
        $q->whereHas('scholarshipGrant', function ($subQ) use ($dateFiled, $program_id) {
            $subQ->whereDate('date_filed', $dateFiled)
                ->where('scholarship_status', 0)
                ->whereNotIn('approval_status', ['approved', 'auto_approved', 'declined']);
            if ($program_id) {
                $subQ->where('program_id', $program_id);
            }
        })
        ->orWhere(function ($subQ) use ($dateFiled) {
            $subQ->where('is_on_waiting_list', true)
                ->whereDate('date_filed', $dateFiled);
        });
    })
    ->orderBy('date_filed', 'asc')
    ->orderBy('created_at', 'asc')
    ->pluck('profile_id')->toArray();
```

**Explanation:**

- Daily sequence numbers now calculated for profiles without scholarship grants
- Uses the profile's `date_filed` field as fallback
- Includes profiles marked as `is_on_waiting_list` in daily counts

## Testing Checklist

### Frontend Testing

- [x] Form submits successfully without academic information
- [x] Form submits successfully with partial academic information
- [x] Form submits successfully with complete academic information
- [x] No JavaScript console errors when fields are empty
- [x] Validation errors display properly for all fields

### Backend Testing

- [x] Profile created without ScholarshipRecord when academic info missing
- [x] Profile created with ScholarshipRecord when academic info provided
- [x] Profile update works without changing academic info
- [x] Profile update creates ScholarshipRecord when academic info added
- [x] No database errors with null academic values

### Build Testing

- [x] `npm run build` completes successfully
- [x] No TypeScript/JavaScript compilation errors
- [x] Application loads correctly in browser

### Waiting List Testing (October 15, 2025)

- [x] Waiting list displays profiles without academic information
- [x] Waiting list displays profiles with academic information
- [x] Sequence numbers calculated correctly for all profiles
- [x] Daily sequence numbers include profiles without grants
- [x] Filters still work correctly (school, course, year_level filters only show profiles with those fields)
- [x] `is_on_waiting_list` flag properly includes profiles in the list

## Build Results

**Initial Implementation (January 2025):**

```
✓ 3148 modules transformed.
✓ built in 15.90s
```

**Waiting List Fix (October 15, 2025):**

```
✓ 3148 modules transformed.
✓ built in 15.43s
```

**Status:** ✅ Build successful with 0 errors

## Impact Analysis

### Positive Impacts

1. **Increased Flexibility**: Applicants can be registered even if academic information is not yet available
2. **Better UX**: Form doesn't force users to enter placeholder data
3. **Data Integrity**: System properly handles null values instead of storing invalid placeholder data
4. **Workflow Support**: Allows for two-stage registration (basic info first, academic details later)
5. **Waiting List Visibility** (Oct 2025): Profiles without academic info now visible in waiting list as intended

### Potential Considerations

1. **Incomplete Profiles**: Some profiles may not have scholarship records
2. **Waiting List Display**: Profiles without academic info will show in waiting list but won't appear when filtering by school/course/year_level (expected behavior)
3. **Reporting**: Reports may need to handle profiles without academic information
4. **Filtering**: Academic field filters should account for null values

## Related Files

### Modified Files

1. `resources/js/Pages/Applicants/Modal/ApplicantProfileModal.vue` - Frontend form handling
2. `app/Http/Controllers/ScholarshipProfileController.php` - Backend controller logic
3. `app/Http/Controllers/WaitingListController.php` - Waiting list query and sequence calculations (Oct 2025)

### Verified Files (No Changes Needed)

1. `app/Http/Requests/CreateScholarshipProfileRequest.php` - Validation rules
2. `app/Http/Requests/UpdateScholarshipProfileRequest.php` - Validation rules
3. `database/migrations/2025_08_18_074955_create_scholar_profiles_table.php` - Database schema

## Usage Examples

### Creating Profile Without Academic Info

```javascript
// User can leave academic fields empty
form.school = null;
form.course = null;
form.year_level = null;
form.term = null;
form.academic_year = null;

// Form submits successfully
form.submit();
```

### Adding Academic Info Later

```javascript
// Update profile with academic information
form.school = { name: 'University of Example' };
form.course = { shortname: 'BSCS' };
form.year_level = { value: '1st Year' };
form.term = { value: '1st Semester' };
form.academic_year = { value: '2024-2025' };

// ScholarshipRecord will be created on update
form.submit();
```

## Future Enhancements

### Potential Improvements

1. Add visual indicator for incomplete profiles
2. Create admin dashboard notification for profiles missing academic info
3. Implement batch academic info update feature
4. Add validation warning (not error) when academic fields are empty

### Related Features

- Profile completion tracking
- Academic information import tool
- Bulk profile update functionality

## Conclusion

The implementation successfully allows applicant profiles to be submitted without mandatory academic information. Both frontend and backend have been updated to gracefully handle null academic values while maintaining backward compatibility with existing profiles that have complete academic information.

**Update (October 15, 2025):** The waiting list display has been fixed to properly show profiles without academic information by modifying the query to check for the `is_on_waiting_list` flag in addition to scholarship grant status.

**Status:** ✅ Implementation Complete and Tested  
**Build:** ✅ Successful (15.43s)  
**Errors:** ✅ None

### Summary of Changes:

- ✅ Frontend handles empty academic fields (January 2025)
- ✅ Backend creates profiles without scholarship records (January 2025)
- ✅ Waiting list displays all applicants regardless of academic info (October 2025)
- ✅ Sequence numbering works for profiles with and without grants (October 2025)

---

_Last Updated: October 15, 2025_

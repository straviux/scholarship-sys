# Reviewed Applicants Page - Implementation Summary

## Overview
A new page has been created to manage scholarship applicants that have been marked as "Approved" or "Denied" during the review phase. This page allows moderators to view, filter, and take final approval/denial actions on reviewed applications.

## Files Created

### 1. Vue Component
**File:** `resources/js/Pages/Scholarship/ReviewedApplicants.vue`

**Features:**
- Display statistics for total, approved, and denied applicants
- Filter applicants by:
  - Status (approved pending / denied)
  - Name (searchable)
  - Program (dropdown)
- Two sections:
  - **Approved (Pending)** - Applications marked as approved during review
  - **Denied** - Applications marked as denied during review
- Columns in datatable:
  - Applicant name and contact
  - Program and course
  - Year level
  - Date filed
  - Action buttons
- Action buttons for each applicant:
  - **Approve** - Finalize approval with date and remarks
  - **Confirm Deny** - Finalize denial with reason and details
  - **Mark as Pending** - Revert status back to pending_approval
  - **View Profile** - Navigate to full profile page

**Dialogs:**
- **Approval Dialog** - Captures approval date and remarks
- **Denial Dialog** - Captures denial reason and details

## Files Modified

### 1. Controller
**File:** `app/Http/Controllers/ScholarshipProfileController.php`

**New Method:** `showReviewedApplicants(Request $request)`
- Check permission: `applicants.approve`
- Query scholarship records with status: `approved_pending` or `denied`
- Filter by:
  - Status
  - Applicant name
  - Program
- Order by date_filed (newest first)
- Return Inertia response with reviewed applicants and decline reasons config

### 2. Routes
**File:** `routes/web.php`

**New Route:**
```php
Route::get('/reviewed-applicants', [ScholarshipProfileController::class, 'showReviewedApplicants'])
    ->name('scholarship.reviewed-applicants');
```

### 3. Navigation
**File:** `resources/js/Layouts/AdminLayout.vue`

**Changes:**
- Added "Reviewed Applicants" link in the Scholarship section menu
- Only visible to users with `applicants.approve` permission
- Navigation link appears in both full and minimized sidebar views

## Status Flow Diagram

```
Applicants List (Index.vue)
    ↓
Marked as Approved/Denied via Dropdown
    ↓
updateStatus() PATCH route
    ↓
unified_status set to:
  - approved_pending (for approved)
  - denied (for denied)
    ↓
Reviewed Applicants Page (ReviewedApplicants.vue)
    ↓
Final Approval/Denial Actions
    ↓
approve() POST route → active_scholar
decline() POST route → declined
```

## Permission Requirements

Users need the following permission to access this page:
- `applicants.approve` - Required to view and manage reviewed applicants

## Data Flow

### Retrieving Reviewed Applicants
1. GET request to `/reviewed-applicants`
2. Controller queries ScholarshipRecord where:
   - `unified_status` IN ('approved_pending', 'denied')
   - Applies optional filters (status, name, program)
3. Eager loads relationships:
   - Profile (name, contact info)
   - Program (name, shortname)
   - Course (name, shortname)
   - School (name, shortname)
4. Returns Inertia response with:
   - reviewed_applicants (array)
   - decline_reasons (config)

### Approving an Application
1. User clicks "Approve" button in approved section
2. Opens approval dialog with date and remarks fields
3. User submits form
4. POST to `/scholarship/{record}/approve` route
5. Uses existing `approve()` method in controller
6. Updates status to `active_scholar`
7. Refreshes page on success

### Denying an Application
1. User clicks "Confirm Deny" button in denied section
2. Opens denial dialog with reason dropdown and details textarea
3. User selects decline reason and provides details
4. User submits form
5. POST to `/scholarship/{record}/decline` route
6. Uses existing `decline()` method in controller
7. Updates status to `declined`
8. Refreshes page on success

### Reverting Status
1. User clicks "Mark as Pending" button on any applicant
2. Direct PATCH request to `/scholarship/{record}/update-status`
3. Reverts unified_status to `pending_approval`
4. Sets reviewed flag to false
5. Refreshes page on success

## Statistics Display

The page shows real-time statistics:
- **Total Reviewed:** Count of all records with approved_pending or denied status
- **Marked as Approved:** Count of records with approved_pending status
- **Marked as Denied:** Count of records with denied status

## User Experience

1. **Navigate to page:** Click "Reviewed Applicants" in Scholarship menu
2. **View applicants:** See separated lists for approved and denied applicants
3. **Filter:** Use filter section to narrow down results by name, program, or status
4. **Take action:** 
   - Approve: Click button → fill date/remarks → confirm
   - Deny: Click button → select reason → fill details → confirm
   - Revert: Click button → confirm status reversion
5. **View full profile:** Click eye icon to navigate to full profile page

## Integration Points

- Uses `useScholarshipStatus` composable for status display
- Uses existing `approve()` and `decline()` methods
- Uses existing `updateStatus()` method for reverting
- Uses PrimeVue DataTable for displaying applicants
- Uses Inertia.js for server-side rendering
- Uses vue3-toastify for notifications

## Testing Checklist

- [ ] Navigate to Reviewed Applicants page
- [ ] Verify page loads with statistics
- [ ] Filter by name - results update
- [ ] Filter by status - lists update accordingly
- [ ] Filter by program - results filter correctly
- [ ] Click "Approve" button - dialog opens
- [ ] Fill approval form and submit - applicant moves to profiles/active
- [ ] Click "Deny" button - dialog opens
- [ ] Fill denial form and submit - applicant status updated
- [ ] Click "Mark as Pending" - status reverts to pending_approval
- [ ] Click "View Profile" - navigates to profile page
- [ ] Verify permission check works - non-privileged users cannot access
- [ ] Check toast notifications appear on success/error
- [ ] Verify page refreshes after actions

## Future Enhancements

1. Add bulk actions (approve/deny multiple at once)
2. Add export functionality for reviewed applicants
3. Add approval history/timeline for each applicant
4. Add comments/notes field for reviewers
5. Add email notifications when applicants are approved/denied
6. Add approval deadline tracking
7. Add reviewer assignment
8. Add supervisor approval step before final approval

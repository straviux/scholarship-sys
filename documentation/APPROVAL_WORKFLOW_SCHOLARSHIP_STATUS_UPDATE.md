# ApprovalWorkflow Update - Using scholarship_status

## Overview

Updated the ApprovalWorkflow component and ScholarshipApprovalService to properly set `scholarship_status` when approving/declining applications, in addition to the `approval_status` workflow tracking.

## Changes Made

### 1. **ScholarshipApprovalService.php**

**File**: `app/Services/ScholarshipApprovalService.php`

#### Approve Method

```php
$record->update([
    'approval_status' => 'approved',
    'approved_by' => $approver->id,
    'approved_at' => now(),
    'approval_remarks' => $data['remarks'] ?? null,
    'scholarship_status' => 1, // 1 = Approved/Active
    'scholarship_status_remarks' => 'Active Scholar',
    // Clear decline fields if previously declined
    'declined_by' => null,
    'declined_at' => null,
    'decline_reason' => null,
]);
```

#### Decline Method

```php
$record->update([
    'approval_status' => 'declined',
    'declined_by' => $decliner->id,
    'declined_at' => now(),
    'decline_reason' => $data['reason'],
    'scholarship_status' => 4, // 4 = Cancelled/Declined
    'scholarship_status_remarks' => 'Application Declined',
    // Clear approval fields if previously approved
    'approved_by' => null,
    'approved_at' => null,
    'approval_remarks' => null,
]);
```

#### Expire Conditional Approval

```php
$record->update([
    'approval_status' => 'declined',
    'conditional_deadline_expired' => true,
    'declined_by' => $systemUser->id,
    'declined_at' => now(),
    'decline_reason' => 'conditional_deadline_expired',
    'approval_remarks' => 'Conditional approval expired - deadline not met',
    'scholarship_status' => 4, // 4 = Cancelled/Declined
    'scholarship_status_remarks' => 'Conditional Deadline Expired',
]);
```

### 2. **ApprovalWorkflow.vue**

**File**: `resources/js/Pages/Scholarship/Components/ApprovalWorkflow.vue`

Added comprehensive documentation comment explaining the dual status system:

```javascript
/**
 * ApprovalWorkflow Component
 *
 * This component handles the approval workflow for scholarship applications.
 *
 * Status Management:
 * - Uses `approval_status` for workflow display (pending, approved, declined, conditional)
 * - When approved, backend also sets:
 *   - `scholarship_status = 1` (Active/Approved)
 *   - `scholarship_status_remarks = 'Active Scholar'`
 * - When declined, backend also sets:
 *   - `scholarship_status = 4` (Cancelled/Declined)
 *   - `scholarship_status_remarks = 'Application Declined'`
 *
 * The dual status system ensures:
 * - approval_status: Tracks workflow state (for this component and approval history)
 * - scholarship_status: Primary status for filtering and business logic
 */
```

## Status Values Reference

### scholarship_status (Primary Status)

- `0`: Pending
- `1`: Approved/Active/Ongoing ✅ **Used for "Existing" filter**
- `2`: Completed
- `3`: Suspended
- `4`: Cancelled/Declined

### approval_status (Workflow Status)

- `pending`: Waiting for review
- `approved`: Application approved ✅ **Sets scholarship_status=1**
- `declined`: Application declined ✅ **Sets scholarship_status=4**
- `conditional`: Conditionally approved (pending requirements)
- `auto_approved`: Automatically approved by system

## Dual Status System Benefits

### Why Two Status Fields?

1. **approval_status** (Workflow Tracking)

   - Tracks the approval workflow state
   - Shows approval history and audit trail
   - Displays in ApprovalWorkflow component
   - Useful for conditional approvals

2. **scholarship_status** (Primary Business Status)
   - Used for filtering (e.g., "Existing" scholars)
   - Drives business logic and reports
   - Simpler integer values (0-4)
   - Used in ScholarController for creating active scholars

### How They Work Together

When an application is **approved**:

- `approval_status` → `'approved'` (for workflow tracking)
- `scholarship_status` → `1` (for business logic/filtering)
- `scholarship_status_remarks` → `'Active Scholar'`

When an application is **declined**:

- `approval_status` → `'declined'` (for workflow tracking)
- `scholarship_status` → `4` (for business logic/filtering)
- `scholarship_status_remarks` → `'Application Declined'`

## Integration with Existing Features

### "Existing" Filter

Now correctly shows scholars with:

- `scholarship_status = 1` ✅
- `scholarship_status_remarks = 'Active Scholar'` ✅

### Add Existing Scholar

Creates scholars directly with:

- `scholarship_status = 1` ✅
- `scholarship_status_remarks = 'Active Scholar'` ✅
- `approval_status = 'approved'` ✅
- `approved_by` = Current user ✅
- `approved_at` = date_approved or now() ✅

### Approval Workflow

When approving through the workflow:

- Sets both `approval_status` and `scholarship_status` ✅
- Maintains approval history ✅
- Triggers proper filtering ✅

## Testing Checklist

- [x] Service updates compile successfully
- [x] Frontend builds without errors (15.91s)
- [ ] Approve an application via ApprovalWorkflow
- [ ] Verify approved application appears in "Existing" filter
- [ ] Verify `scholarship_status=1` is set after approval
- [ ] Decline an application via ApprovalWorkflow
- [ ] Verify declined application has `scholarship_status=4`
- [ ] Test conditional approval and expiration
- [ ] Verify auto-approval still works correctly

## Database Validation

```sql
-- Check that approved applications have correct status
SELECT
    id,
    profile_id,
    approval_status,
    scholarship_status,
    scholarship_status_remarks,
    approved_by,
    approved_at
FROM scholarship_records
WHERE approval_status = 'approved'
LIMIT 10;

-- Should show scholarship_status = 1 and scholarship_status_remarks = 'Active Scholar'

-- Check that declined applications have correct status
SELECT
    id,
    profile_id,
    approval_status,
    scholarship_status,
    scholarship_status_remarks,
    declined_by,
    declined_at
FROM scholarship_records
WHERE approval_status = 'declined'
LIMIT 10;

-- Should show scholarship_status = 4
```

## Related Documentation

- **EXISTING_SCHOLAR_FILTER_FIX.md** - Filter implementation details
- **ADD_EXISTING_SCHOLAR_IMPLEMENTATION.md** - Add Existing Scholar feature
- **SCHOLAR_ROUTES_IMPLEMENTATION.md** - Route definitions

## Notes

- The auto-approval feature uses the regular `approve()` method, so it automatically benefits from this update
- Conditional approvals only set `approval_status`, not `scholarship_status` until fully approved
- The system maintains backward compatibility with existing approval history
- All approval/decline operations are wrapped in database transactions for consistency

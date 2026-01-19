# Unified Status Migration Guide

## Overview
The system has been refactored to use a single `unified_status` field instead of separate `approval_status` and `scholarship_status` fields.

## New Status Values
- **`pending`** - New applicant, waiting for review
- **`approved`** - Marked as approved by reviewer
- **`denied`** - Marked as denied by reviewer
- **`active`** - Approved and active in scholarship program
- **`completed`** - Scholarship has been completed
- **`unknown`** - Fallback for errors (should not occur with new logic)

## Migration Steps

### 1. Test the Migration Command (Dry-Run)
```bash
php artisan migrate:unified-status --dry-run
```

This will show what records will be updated without making changes.

### 2. Run the Actual Migration
```bash
php artisan migrate:unified-status
```

This command will:
- Process all existing scholarship records
- Generate the correct `unified_status` based on current `approval_status` and `scholarship_status` values
- Display progress and summary

### 3. Clear Application Cache
```bash
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

## Status Mapping (What Happens to Old Data)

| Old approval_status | Old scholarship_status | → New unified_status |
|---|---|---|
| `pending` | 0 | `pending` |
| `pending` | 1 | `active` |
| `approved` | 0 | `active` |
| `approved` | 1 | `active` |
| `approved` | 3 | `completed` |
| `auto_approved` | 0 | `active` |
| `auto_approved` | 1 | `active` |
| `auto_approved` | 3 | `completed` |
| `declined` | any | `denied` |
| `null`/not set | any | `pending` |

## Code Changes

### Controllers Updated
- ✅ `ScholarshipProfileController` - All status queries use `unified_status`
- ✅ `WaitingListController` - All pending filters use `unified_status = 'pending'`

### Models Updated
- ✅ `ScholarshipRecord::generateUnifiedStatus()` - Updated to use 6 new statuses

### What Changed in Queries
- OLD: `where('scholarship_status', 0)` 
- NEW: `where('unified_status', 'pending')`

- OLD: `where('approval_status', 'approved')`
- NEW: `where('unified_status', 'active')`

- OLD: `where('approval_status', 'declined')`
- NEW: `where('unified_status', 'denied')`

## Database Notes

**⚠️ DO NOT DELETE FIELDS YET**

The `approval_status` and `scholarship_status` fields are still in the database for now. They:
- Remain populated for audit/debugging purposes
- Are read-only (no new writes)
- Can be removed in a future migration after extended testing

To completely remove these fields later:
```bash
php artisan make:migration remove_old_status_fields_from_scholarship_records
```

## Validation

The system now validates against the 6 new status values:
```php
'unified_status' => 'required|string|in:pending,approved,denied,active,completed,unknown'
```

## Testing Checklist

- [ ] Run dry-run migration
- [ ] Review the output carefully
- [ ] Run actual migration
- [ ] Verify waiting list shows only pending applications
- [ ] Test marking applicants as approved (should show `active`)
- [ ] Test marking applicants as denied (should show `denied`)
- [ ] Verify existing scholars show `active` status
- [ ] Check reports and exports

## Production Deployment

1. **Backup database** - Always backup before running migration
2. **Test in staging** - Run migration in staging environment first
3. **Schedule maintenance window** - Migration shouldn't be long-running
4. **Run migration command** - `php artisan migrate:unified-status`
5. **Clear caches** - `php artisan cache:clear`
6. **Monitor logs** - Watch for any errors post-migration
7. **Verify functionality** - Test key workflows

## Troubleshooting

**Issue**: Migration shows no records updated
- Check if data is already in correct state
- Review records manually with: `SELECT DISTINCT unified_status FROM scholarship_records;`

**Issue**: Some records have `unknown` status after migration
- Check for null or unexpected `approval_status` values
- Review the `generateUnifiedStatus()` method logic

**Issue**: Waiting list showing too many/too few records
- Verify `unified_status = 'pending'` filter is correct
- Check if new applicants are being created with `unified_status = 'pending'`

## Rollback (If Needed)

If you need to rollback, the old fields are still there:
1. Restore `approval_status` and `scholarship_status` logic
2. Revert controller changes
3. Clear cache

The old fields were NOT deleted, so data remains intact.

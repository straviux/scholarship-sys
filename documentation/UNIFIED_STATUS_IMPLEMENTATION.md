# Unified Scholarship Status Implementation

## Overview
This implementation consolidates `approval_status` and `scholarship_status` into a single `unified_status` field while maintaining full backward compatibility with legacy data.

## Implementation Details

### 1. Migration
- **File**: `database/migrations/2025_01_15_000001_add_unified_status_to_scholarship_records.php`
- **Changes**: Adds nullable `unified_status` column (VARCHAR 50) with index
- **Backward Compatible**: Legacy columns remain untouched

### 2. Composable
- **File**: `resources/js/composables/useScholarshipStatus.js`
- **Purpose**: Centralized status logic for Vue components
- **Key Functions**:
  - `getUnifiedStatus(record)` - Gets unified status with fallback to legacy fields
  - `getStatusLabel()`, `getStatusSeverity()` - Display utilities
  - `mapLegacyStatus()` - Converts old two-field status to unified

### 3. Model Updates
- **File**: `app/Models/ScholarshipRecord.php`
- **Changes**:
  - Added `unified_status` to `$fillable`
  - Added `boot()` logic to auto-generate `unified_status` when status fields change
  - Added `generateUnifiedStatus()` static method

### 4. Artisan Command
- **File**: `app/Console/Commands/MigrateScholarshipUnifiedStatus.php`
- **Usage**:
  ```bash
  # Verify records needing migration
  php artisan scholarship:migrate-unified-status --verify
  
  # Auto-fix all records
  php artisan scholarship:migrate-unified-status --fix
  
  # Interactive mode (asks for confirmation)
  php artisan scholarship:migrate-unified-status
  ```

### 5. Validation
- **File**: `app/Http/Requests/CreateScholarshipRecordRequest.php`
- **Added**: `unified_status` validation with allowed values

## Unified Status Values

| Status | Label | Severity | Description |
|--------|-------|----------|-------------|
| `pending_approval` | Pending Approval | warning | Awaiting approval |
| `approved_pending` | Approved (Pending Enrollment) | info | Approved but not enrolled |
| `active_scholar` | Active Scholar | success | Currently enrolled |
| `completed` | Completed | secondary | Scholarship completed |
| `declined` | Declined | danger | Application declined |
| `withdrawn` | Withdrawn | secondary | Withdrawn from scholarship |

## Migration Path

### Phase 1: Deploy & Monitor
1. Run migration to add `unified_status` column
2. Deploy with dual-read logic (uses `unified_status` if available, falls back to legacy)
3. Monitor for any issues
4. No data needs to be changed yet

### Phase 2: Auto-Migration
When ready to populate data:
```bash
php artisan scholarship:migrate-unified-status --fix
```

This will:
- Scan all records with null `unified_status`
- Auto-generate values based on legacy fields
- Show progress bar and completion summary

### Phase 3: Verification
Verify all records migrated:
```bash
php artisan scholarship:migrate-unified-status --verify
```

### Phase 4: Deprecation (Future)
- In 2-3 releases, stop updating legacy fields
- Eventually remove `approval_status` and `scholarship_status` columns

## Using in Vue Components

```javascript
import { useScholarshipStatus } from '@/composables/useScholarshipStatus';

export default {
  setup() {
    const { 
      getUnifiedStatus, 
      getStatusLabel, 
      getStatusSeverity,
      getRecordStatusInfo 
    } = useScholarshipStatus();

    // Get complete status info
    const statusInfo = getRecordStatusInfo(record);
    // Returns: { status, label, severity, description, isLegacy }
    
    // Get just the status value
    const status = getUnifiedStatus(record);
    
    // Get display label
    const label = getStatusLabel(status);
    
    return { statusInfo, status, label };
  }
};
```

## Backward Compatibility

The implementation is fully backward compatible:

1. **Reading**: If `unified_status` is null, automatically falls back to legacy fields
2. **Writing**: Both old and new fields can be set; system auto-syncs
3. **Rollback**: If needed, can disable new logic without data loss

## Database Consistency

The `boot()` method ensures:
- When `approval_status` changes, `unified_status` auto-updates
- When `scholarship_status` changes, `unified_status` auto-updates
- Legacy fields continue to work as before
- No manual updates needed for existing functionality

## Filtering & Searching

All filters automatically work with the new status:
- Filter by `unified_status` directly
- Or continue using legacy `approval_status` and `scholarship_status`
- Composable provides status options for form dropdowns

## Testing the Migration

```bash
# Check status before migration
SELECT COUNT(*) FROM scholarship_records WHERE unified_status IS NULL;

# Run migration
php artisan scholarship:migrate-unified-status --fix

# Verify all migrated
php artisan scholarship:migrate-unified-status --verify

# Check random records
SELECT id, approval_status, scholarship_status, unified_status 
FROM scholarship_records 
WHERE created_at > NOW() - INTERVAL 1 DAY 
LIMIT 10;
```

## Troubleshooting

### Records not migrating
- Check if command ran to completion
- Verify legacy fields have valid values
- Check Laravel logs for errors

### Status showing as 'unknown'
- Record has unexpected approval/scholarship status combination
- Check the record directly and fix manually
- Add mapping for new status combination to model

### Falling back to legacy instead of unified
- `unified_status` is null - run migration command
- Check if `boot()` logic is executing
- Verify column exists in database

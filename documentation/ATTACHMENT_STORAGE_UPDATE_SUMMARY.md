# Attachment Storage Update Summary

## Changes Made

### 1. Updated Upload Logic (`DisbursementController.php`)

**Changed:**

- Storage path from `disbursements/attachments/` to `attachments/[unique_id]/`
- Filename format from `[scholar_name]_[type]_[timestamp]` to `disbursement_[scholar_name]_[type]_[timestamp]`

**Code Changes:**

```php
// OLD:
$fileName = "{$scholarName}_{$attachmentType}_{$timestamp}.{$extension}";
$filePath = $file->storeAs('disbursements/attachments', $fileName, 'public');

// NEW:
$uniqueId = $profile->unique_id;
$fileName = "disbursement_{$scholarName}_{$attachmentType}_{$timestamp}.{$extension}";
$filePath = $file->storeAs("attachments/{$uniqueId}", $fileName, 'public');
```

### 2. Created Migration Command

**File:** `app/Console/Commands/MigrateAttachmentsToUniqueIdFolders.php`

**Features:**

- Migrates all existing attachments to new structure
- Updates filenames to include `disbursement_` prefix
- Updates database records with new paths
- Cleans up empty old directories
- Shows detailed progress and summary
- Safe to run multiple times (idempotent)

### 3. Created Documentation

**File:** `ATTACHMENT_MIGRATION_GUIDE.md`

**Contents:**

- Before/After folder structure comparison
- Benefits of new structure
- Step-by-step migration instructions
- Testing checklist
- Troubleshooting guide
- Future module guidelines

## New Folder Structure

```
storage/app/public/
└── attachments/
    ├── ABC12345/  (scholar unique_id)
    │   ├── disbursement_John_Doe_voucher_20251028123456.pdf
    │   └── disbursement_John_Doe_receipt_20251028130000.jpg
    ├── XYZ67890/  (another scholar)
    │   └── disbursement_Jane_Smith_cheque_20251028123457.pdf
    └── ...
```

## Migration Steps

### 1. Backup (Recommended)

```bash
# Backup database
mysqldump -u username -p database_name > backup_before_migration.sql

# Backup storage
cp -r storage/app/public/disbursements storage_backup_disbursements/
```

### 2. Run Migration

```bash
php artisan attachments:migrate-to-unique-id-folders
```

### 3. Verify Results

- Check command output for success/failure counts
- Test uploading new attachments
- Test viewing/downloading existing attachments
- Check that files are in new locations

## Benefits

1. **Organization**: All scholar files in one folder
2. **Module Identification**: Filename prefix shows source module
3. **Scalability**: Easy to add new modules later
4. **Privacy**: Scholar files are isolated
5. **Maintenance**: Easier backup/restore per scholar

## Testing Checklist

Before deploying to production:

- [ ] Run migration on development/staging first
- [ ] Verify all files migrated successfully
- [ ] Test new uploads
- [ ] Test viewing existing files
- [ ] Test downloading files
- [ ] Test deleting files
- [ ] Check application logs for errors
- [ ] Backup production before migration

## Notes

- **Non-destructive**: Migration copies files first, then deletes after success
- **Backward Compatible**: No frontend changes needed
- **Automatic Cleanup**: Empty old directories removed automatically
- **Progress Tracking**: Command shows progress bar and detailed summary

## Future Enhancements

When adding new modules (e.g., scholarship records, requirements):

```php
// Use same structure with different prefix
$fileName = "scholarship_record_{$scholarName}_{$documentType}_{$timestamp}.{$extension}";
$filePath = $file->storeAs("attachments/{$uniqueId}", $fileName, 'public');
```

This keeps all scholar files organized while maintaining module identification through filename prefixes.

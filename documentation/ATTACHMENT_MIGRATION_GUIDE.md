# Attachment Storage Migration Guide

## Overview

This guide explains the migration from the old attachment storage structure to the new unique_id-based folder structure for both disbursement and scholarship record attachments.

## New Storage Structure

### Before (Old Structure)

```
storage/app/public/
├── disbursements/
│   └── attachments/
│       ├── John_Doe_voucher_20251028123456.pdf
│       ├── Jane_Smith_cheque_20251028123457.pdf
│       └── ...
└── scholarship_records/
    └── attachments/
        ├── John_Doe_Contract_20251028123458.pdf
        ├── Jane_Smith_COR_20251028123459.jpg
        └── ...
```

### After (New Structure)

```
storage/app/public/
└── attachments/
    ├── ABC12345/  (unique_id of scholar)
    │   ├── disbursement_John_Doe_voucher_20251028123456.pdf
    │   ├── disbursement_John_Doe_receipt_20251028130000.pdf
    │   ├── scholarship_record_John_Doe_Contract_20251028123458.pdf
    │   ├── scholarship_record_John_Doe_COR_20251028123459.jpg
    │   └── ...
    ├── XYZ67890/  (unique_id of another scholar)
    │   ├── disbursement_Jane_Smith_cheque_20251028123457.pdf
    │   ├── scholarship_record_Jane_Smith_Contract_20251028140000.pdf
    │   └── ...
    └── ...
```

## Benefits

1. **Organized by Scholar**: All files for a specific scholar are in one folder
2. **Module Identification**: Filename prefix identifies which module the file belongs to:
   - `disbursement_` - Disbursement attachments (vouchers, cheques, receipts)
   - `scholarship_record_` - Scholarship record attachments (contracts, COR, grades, etc.)
3. **Scalability**: Easy to add more modules (requirements, applications, etc.)
4. **Privacy**: Each scholar's files are isolated in their own folder
5. **Easy Backup**: Can backup/restore files per scholar
6. **Unified Structure**: Single storage location for all scholar-related documents

## Filename Convention

New files follow this pattern:

```
[module]_[scholar_name]_[attachment_type]_[timestamp].[extension]
```

### Disbursement Examples:

- `disbursement_John_Doe_voucher_20251028123456.pdf`
- `disbursement_Jane_Smith_receipt_20251028130000.jpg`
- `disbursement_Peter_Santos_cheque_20251028140000.pdf`

### Scholarship Record Examples:

- `scholarship_record_John_Doe_Contract_20251028123458.pdf`
- `scholarship_record_Jane_Smith_COR_20251028123459.jpg`
- `scholarship_record_Peter_Santos_Grades_page_1_20251028150000.jpg`

## Migration Process

### Step 1: Run the Migration Command

```bash
php artisan attachments:migrate-to-unique-id-folders
```

This command will:

1. Find all existing disbursement and scholarship record attachments in the database
2. For each attachment:
   - Get the scholar's unique_id
   - Create the new folder structure: `attachments/[unique_id]/`
   - Update filename to include module prefix if needed
   - Copy file to new location
   - Update database record with new file_path and file_name
   - Delete old file
3. Clean up empty old directories
4. Show summary report

### Step 2: Verify Migration

After running the migration:

2. **Check the summary output** - Should show summaries for both:

   - Disbursement Attachments Migration Summary
   - Scholarship Record Attachments Migration Summary
   - Each showing: Success count, Failed count, Skipped count, Total count

3. **Check file structure**:

   ```bash
   ls storage/app/public/attachments/
   ```

   You should see folders named with unique_ids (e.g., ABC12345, XYZ67890)

4. **Check database**:

   ```sql
   SELECT file_path, file_name FROM disbursement_attachments LIMIT 5;
   ```

   Paths should look like: `attachments/ABC12345/disbursement_John_Doe_voucher_20251028123456.pdf`

5. **Test in application**:
   - Upload a new disbursement attachment
   - Upload a new scholarship record attachment
   - View existing attachments (both types)
   - Download attachments (both types)
   - Delete attachments (both types)

### Step 3: Backup Before Migration (Recommended)

```bash
# Backup database
php artisan db:backup

# Backup storage folders
cp -r storage/app/public/disbursements storage_backup/
cp -r storage/app/public/scholarship_records storage_backup/
```

## Rollback (If Needed)

If something goes wrong, the migration command is non-destructive. It:

- Copies files (doesn't move them initially)
- Only deletes old files after successful copy and database update
- Keeps database backups

To manually rollback:

1. Restore database from backup
2. Restore storage folder from backup
3. Run application normally

## Upload Logic Changes

### DisbursementController.php

**Old Code:**

```php
$fileName = "{$scholarName}_{$attachmentType}_{$timestamp}.{$extension}";
$filePath = $file->storeAs('disbursements/attachments', $fileName, 'public');
```

**New Code:**

```php
$uniqueId = $profile->unique_id;
$fileName = "disbursement_{$scholarName}_{$attachmentType}_{$timestamp}.{$extension}";
$filePath = $file->storeAs("attachments/{$uniqueId}", $fileName, 'public');
```

### ScholarshipRecordAttachmentController.php

**Old Code:**

```php
$fileName = "{$scholarName}_{$attachmentName}_{$timestamp}{$pageNumberSuffix}.{$fileExtension}";
$filePath = 'scholarship_records/attachments/' . $fileName;
Storage::disk('public')->put($filePath, $processedContent);
```

**New Code:**

```php
$uniqueId = $profile->unique_id;
$fileName = "scholarship_record_{$scholarName}_{$attachmentName}_{$timestamp}{$pageNumberSuffix}.{$fileExtension}";
$filePath = "attachments/{$uniqueId}/" . $fileName;
Storage::disk('public')->put($filePath, $processedContent);
```

### MobileUploadController.php (Scholarship Records)

**Old Code:**

```php
$fileName = "{$scholarName}_{$attachmentName}_{$timestamp}{$pageNumberSuffix}.{$fileExtension}";
$filePath = 'scholarship_records/attachments/' . $fileName;
```

**New Code:**

```php
$uniqueId = $profile->unique_id;
$fileName = "scholarship_record_{$scholarName}_{$attachmentName}_{$timestamp}{$pageNumberSuffix}.{$fileExtension}";
$filePath = "attachments/{$uniqueId}/" . $fileName;
```

## Testing Checklist

- [ ] Run migration command
- [ ] Check summary shows both disbursement and scholarship record migrations
- [ ] Verify all files migrated successfully for both types
- [ ] Verify folder structure in storage/app/public/attachments/
- [ ] Upload new disbursement attachment - should go to attachments/[unique_id]/
- [ ] Upload new scholarship record attachment - should go to attachments/[unique_id]/
- [ ] View existing disbursement attachments - should display correctly
- [ ] View existing scholarship record attachments - should display correctly
- [ ] Download attachments (both types) - should download correctly
- [ ] Delete attachments (both types) - should remove file and database record
- [ ] Check that old directories are cleaned up (disbursements/, scholarship_records/)

## Troubleshooting

### Issue: "File not found at [old_path]"

- **Cause**: Database has record but file is missing
- **Solution**: These will be skipped. Review if they should be removed from database

### Issue: "No profile or unique_id found"

- **Cause**: Orphaned attachment records
- **Solution**: Check if disbursement still exists, clean up if needed

### Issue: "Failed to copy file"

- **Cause**: Permission issues or disk space
- **Solution**: Check storage folder permissions and disk space

### Issue: Migration runs but files still in old location

- **Cause**: Already migrated or error during copy
- **Solution**: Check the command output for specific errors

## Future Modules

When adding new modules (e.g., requirements, applications), use the same structure:

```php
// Example for application requirements
$fileName = "application_requirement_{$scholarName}_{$documentType}_{$timestamp}.{$extension}";
$filePath = $file->storeAs("attachments/{$uniqueId}", $fileName, 'public');

// Example for medical records
$fileName = "medical_record_{$scholarName}_{$documentType}_{$timestamp}.{$extension}";
$filePath = $file->storeAs("attachments/{$uniqueId}", $fileName, 'public');
```

This maintains consistency across all modules while keeping files organized by scholar.

## Notes

- Migration handles both disbursement and scholarship record attachments
- Migration is idempotent - safe to run multiple times
- Files already in new structure will be skipped
- Command provides detailed progress and summary for each module
- Old empty directories are automatically cleaned up after migration
- Both modules now share the same `attachments/[unique_id]/` structure
- File type is identified by the filename prefix (disbursement* or scholarship_record*)

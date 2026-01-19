# Scholarship Profiles Refactoring: Removing Redundant Columns

## Overview
This refactoring removes redundant columns from the `scholarship_profiles` table that are now managed in the `scholarship_records` table. This improves data consistency and simplifies the database schema.

## Columns Removed from `scholarship_profiles`

1. **`is_on_waiting_list`** - Now determined by checking `scholarship_records.application_status` 
   - Application status: 0 = Waiting List, 1 = Active, 2 = Denied

2. **`application_status`** - Moved to `scholarship_records` table
   - 0: Waiting List
   - 1: Active
   - 2: Denied

3. **`application_status_remarks`** - Moved to `scholarship_records` table

4. **`application_status_date`** - Moved to `scholarship_records` table

5. **`applied_year_level`** - Now available via `scholarship_records.year_level`

6. **`applied_course`** - Now available via `scholarship_records.course_id` (relationship to courses table)

7. **`applied_school`** - Now available via `scholarship_records.school_id` (relationship to schools table)

## Files Modified

### Backend

#### Migrations
- **`2025_01_09_remove_redundant_profile_columns.php`** (NEW)
  - Drops the 7 redundant columns from `scholarship_profiles`
  - Removes `idx_is_on_waiting_list` index
  - Includes rollback functionality to restore columns if needed

#### Models
- **`app/Models/ScholarshipProfile.php`**
  - Removed fillable fields: `is_on_waiting_list`, `application_status`, `application_status_remarks`, `application_status_date`
  - Removed casts: `is_on_waiting_list`, `application_status_date`

#### Controllers
- **`app/Http/Controllers/ScholarshipRecordController.php`**
  - Removed sorting logic for `applied_course` and `applied_year_level`
  - These columns are now accessed through relationships to `ScholarshipRecord`

### Frontend

#### Components
- **`resources/js/Pages/Applicants/Modal/ApplicantProfileModal.vue`**
  - Removed `is_on_waiting_list` from form initialization
  - Removed `is_on_waiting_list` from CREATE submission data
  - Removed `is_on_waiting_list` from UPDATE submission data

- **`resources/js/Pages/Applicants/Index.vue`**
  - Removed `course` and `applied_year_level` from sort props
  - These can be accessed through the latest scholarship record relationship

## How to Access This Information Now

### Getting Waiting List Status
**Before:**
```php
$profile->is_on_waiting_list;
```

**After:**
```php
// Check if profile has a scholarship record with waiting list status
$profile->scholarshipGrant()->where('application_status', 0)->exists();

// Or through latest record
$profile->latest_scholarship_record->application_status === 0;
```

### Getting Applied Year Level
**Before:**
```php
$profile->applied_year_level;
```

**After:**
```php
// Through latest scholarship record
$profile->latest_scholarship_record->year_level;

// Or through specific record
$record->year_level;
```

### Getting Applied Course
**Before:**
```php
$profile->applied_course;
```

**After:**
```php
// Through latest scholarship record relationship
$profile->latest_scholarship_record->course->shortname;
```

### Getting Applied School
**Before:**
```php
$profile->applied_school;
```

**After:**
```php
// Through latest scholarship record relationship
$profile->latest_scholarship_record->school->shortname;
```

## Migration Steps

1. **Backup your database** before running the migration
   ```bash
   php artisan migrate:backup  # Or use your preferred backup method
   ```

2. **Run the migration**
   ```bash
   php artisan migrate
   ```

3. **Update any custom queries** that reference the removed columns
   - Search your codebase for: `is_on_waiting_list`, `application_status`, `application_status_remarks`, `application_status_date`, `applied_year_level`, `applied_course`, `applied_school`

4. **Test profile queries** to ensure relationships work correctly

## Rollback

If you need to restore these columns:
```bash
php artisan migrate:rollback
```

This will restore all 7 columns and re-add the `idx_is_on_waiting_list` index.

## Benefits

✅ **Single Source of Truth**: Application status is now only stored in `scholarship_records`
✅ **Reduced Redundancy**: No data duplication between profile and record tables
✅ **Simpler Schema**: Fewer columns to manage in the profile table
✅ **Better Relationships**: Data accessed through proper model relationships
✅ **Improved Consistency**: No risk of profile data being out of sync with records

## Notes

- All relationships between `ScholarshipProfile` and `ScholarshipRecord` are already defined in the models
- The `latest_scholarship_record` relationship provides quick access to the most recent record
- Multiple records per profile are supported and can be accessed through `scholarshipGrant()`

# Enum to System Options Refactoring

## Overview

This document describes the refactoring of hardcoded enum fields to use the flexible SystemOption model, allowing administrators to dynamically manage option values through the UI.

## Changes Made

### 1. Database Schema Changes

#### Migration: `2025_11_06_045955_refactor_enum_fields_to_use_system_options.php`

Converted the following enum fields to string type:

| Table                      | Field               | Old Type                                    | New Type |
| -------------------------- | ------------------- | ------------------------------------------- | -------- |
| `disbursement_attachments` | `attachment_type`   | `enum(['voucher', 'cheque', 'receipt'])`    | `string` |
| `scholarship_records`      | `grant_provision`   | `enum(['Matriculation', 'RLE', 'Tuition'])` | `string` |
| `disbursements`            | `obr_status`        | `enum(['LOA', 'IRREGULAR', ...])`           | `string` |
| `disbursements`            | `disbursement_type` | `enum(['regular', 'reimbursement', ...])`   | `string` |
| `scholarship_profiles`     | `priority_level`    | `enum(['low', 'normal', 'high', 'urgent'])` | `string` |

### 2. New System Option Categories

Added three new categories to the SystemOption model:

#### Categories

1. **term** - Terms
   - Academic terms or semesters (e.g., 1st Semester, 2nd Semester, Summer)
2. **year_level** - Year Levels
   - Student year levels (e.g., 1st Year, 2nd Year, 3rd Year, 4th Year)
3. **academic_year** - Academic Years
   - Academic year periods (e.g., 2024-2025, 2025-2026)

### 3. SystemOption Model Updates

Updated `app/Models/SystemOption.php`:

```php
public static function getCategories()
{
    return [
        'attachment_type' => 'Attachment Types',
        'grant_provision' => 'Grant Provisions',
        'obr_status' => 'OBR Status',
        'disbursement_type' => 'Disbursement Types',
        'priority_level' => 'Priority Levels',
        'term' => 'Terms',                    // NEW
        'year_level' => 'Year Levels',        // NEW
        'academic_year' => 'Academic Years',  // NEW
    ];
}
```

### 4. Seeder Updates

Updated `database/seeders/SystemOptionSeeder.php` with initial values for all categories:

#### Terms

- 1st Semester (Blue)
- 2nd Semester (Green)
- Summer (Orange)

#### Year Levels

- 1st Year (Blue)
- 2nd Year (Green)
- 3rd Year (Orange)
- 4th Year (Red)
- 5th Year (Purple)

#### Academic Years

- 2023-2024 (Gray, Inactive)
- 2024-2025 (Blue, Active)
- 2025-2026 (Green, Active)

### 5. Frontend Updates

Updated `resources/js/Pages/SystemOptions/Index.vue`:

- Changed page title from "System Options" to "Option Values"
- Changed Panel header icon from `pi-cog` to `pi-sliders-h`
- Added descriptions for new categories
- Updated Panel title to "Option Values Management"

### 6. Navigation Updates

Updated `resources/js/Layouts/AdminLayout.vue`:

- Moved "Option Values" from Administrator section to Library section
- Renamed from "System Options" to "Option Values"
- Positioned after "Schools" in the Library menu

## Benefits

### Flexibility

- No more hardcoded enum values in database schema
- Administrators can add/edit/reorder option values through the UI
- No need for migrations when adding new option values

### Consistency

- All option types now use the same SystemOption model
- Uniform management interface in the Option Values page
- Consistent color coding and labeling across all categories

### User Experience

- Administrators can customize options without developer intervention
- Options can be activated/deactivated without database changes
- Easy to add new academic years or terms as needed

## Usage

### Accessing Option Values in Code

```php
// Get all active options for a category
$terms = SystemOption::getByCategory('term');

// Get all options including inactive
$allYearLevels = SystemOption::getByCategory('year_level', false);

// Get grouped options
$allOptions = SystemOption::getAllGrouped();
```

### Managing Options in UI

1. Navigate to **Library → Option Values**
2. Select the category tab
3. Use the "Add Option" button to create new values
4. Use order buttons to reorder values
5. Edit or delete existing values as needed

## Migration Instructions

If you need to rollback:

```bash
php artisan migrate:rollback
```

To re-run:

```bash
php artisan migrate
php artisan db:seed --class=SystemOptionSeeder
```

## Data Integrity

All existing data is preserved during the migration. The enum values are converted to their string equivalents:

- `'voucher'` → `'voucher'`
- `'Matriculation'` → `'Matriculation'`
- `'LOA'` → `'LOA'`
- etc.

## Future Considerations

- Consider adding validation rules to ensure selected values exist in SystemOptions
- May want to add foreign key-like validation at the application level
- Consider adding a SystemOption relationship in relevant models for easier access

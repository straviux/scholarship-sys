# JPM Highlighting Permission Implementation

## Overview

This document describes the implementation of permission-based JPM (Jaycees) member highlighting in the scholarship waiting list reports. JPM highlighting is now only displayed to users with the `can-view-jpm` permission.

## Changes Made

### 1. Backend Controllers

#### ReportController.php (`app/Http/Controllers/ReportController.php`)

- **PDF Report Method (`generateWaitinglist`)**: Added permission check before rendering

  ```php
  $canViewJpm = $request->user() && $request->user()->can('can-view-jpm');
  ```

  - Passes `canViewJpm` flag to the Blade template

- **Excel Report Method (`generateExcelWaitinglist`)**: Added same permission check
  - Passes `canViewJpm` to `WaitingListExport` class constructor

#### ScholarshipProfileController.php (`app/Http/Controllers/ScholarshipProfileController.php`)

- **Generate Report API (`generateReport`)**: Added permission check to JSON responses
  ```php
  'canViewJpm' => $request->user() && $request->user()->can('can-view-jpm')
  ```
  - Returns permission status in both list and summary report types
  - Used by Vue frontend component

### 2. Export Classes

#### WaitingListExport.php (`app/Exports/WaitingListExport.php`)

- Added `$canViewJpm` property to store permission state
- Updated constructor to accept `$canViewJpm` parameter (defaults to `false`)
- Modified `view()` method to pass permission to Blade template
- Updated `registerEvents()` to conditionally apply JPM highlighting:
  ```php
  if ($this->reportType === 'list' && $this->canViewJpm) {
      // Apply green highlighting to JPM member rows
  }
  ```

### 3. Blade Templates

#### waiting_list_report.blade.php (PDF Template)

- Updated JPM detection logic to check permission:
  ```php
  $isJpm = ($canViewJpm ?? false) && ($profile->is_jpm_member ||
           $profile->is_father_jpm || $profile->is_mother_jpm ||
           $profile->is_guardian_jpm);
  ```
- Green highlighting (`#d1fae5`) only applied when both:
  1. User has `view-jpm` permission
  2. Applicant/parent/guardian is a JPM member

#### exports/waiting_list.blade.php (Excel HTML Template)

- Same conditional JPM detection as PDF template
- Row background color only applied with permission

### 4. Vue Component

#### ReportView.vue (`resources/js/Pages/Applicants/Modal/ReportView.vue`)

- Added `canViewJpm` reactive reference:

  ```javascript
  const canViewJpm = ref(false);
  ```

- Updated `fetchReport()` method to capture permission from API:

  ```javascript
  canViewJpm.value = res.data.canViewJpm || false;
  ```

- Modified `isJpm()` helper function to check permission:

  ```javascript
  const isJpm = (item) => {
  	return (
  		canViewJpm.value &&
  		(item.is_jpm_member || item.is_father_jpm || item.is_mother_jpm || item.is_guardian_jpm)
  	);
  };
  ```

- Updated JPM legend to only display with permission:
  ```vue
  <div v-if="reportType === 'list' && canViewJpm" class="mb-4 ...">
      <!-- Legend content -->
  </div>
  ```

## Permission Setup

### Required Permission

- **Name**: `can-view-jpm`
- **Description**: Allows viewing JPM member highlighting in reports
- **Guard**: `web` (default)

### Note

The `can-view-jpm` permission should already exist in your system. If you need to create it manually:

```php
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'can-view-jpm', 'guard_name' => 'web']);
```

### To Assign Permission to Users/Roles

```php
use App\Models\User;
use Spatie\Permission\Models\Role;

// Assign to specific user
$user = User::find(1);
$user->givePermissionTo('can-view-jpm');

// Assign to role (e.g., administrators)
$adminRole = Role::findByName('administrator');
$adminRole->givePermissionTo('can-view-jpm');
```

## How It Works

### Permission Check Flow

1. **User Request**: User generates a report (PDF/Excel/Preview)
2. **Backend Check**: Controller checks `$request->user()->can('can-view-jpm')`
3. **Data Passing**: Permission result passed to template/export/API
4. **Conditional Rendering**:
   - If `canViewJpm = true`: JPM members highlighted in green
   - If `canViewJpm = false`: No highlighting applied, all rows appear normal

### JPM Detection Criteria (when permission granted)

A profile is highlighted if ANY of these are true:

- `is_jpm_member` = true (applicant is JPM member)
- `is_father_jpm` = true (father is JPM member)
- `is_mother_jpm` = true (mother is JPM member)
- `is_guardian_jpm` = true (guardian is JPM member)

### Highlighting Style

- **Color**: Green (#d1fae5 background, #10b981 border)
- **Application**:
  - PDF: Inline styles on each `<td>` with `!important` flag
  - Excel: PhpSpreadsheet cell fill (RGB: D1FAE5)
  - Web Preview: Inline row styles

## Security Considerations

### Access Control

- Permission check uses Laravel's built-in authorization via Spatie Permission package
- Uses `can()` method which respects role hierarchy
- Null-safe: `$request->user() && $request->user()->can('view-jpm')`
- Defaults to `false` if user not authenticated

### Data Privacy

- JPM status information stored in database remains unchanged
- Only the **display** of highlighting is restricted
- Users without permission see standard reports without indicators
- No data is filtered or hidden, only visual highlighting is controlled

## Testing Checklist

### With Permission (can-view-jpm granted)

- [ ] PDF report shows green highlighting for JPM members
- [ ] Excel export shows green highlighting for JPM members
- [ ] Web preview shows green highlighting for JPM members
- [ ] Legend appears in web preview
- [ ] All JPM types detected (applicant, father, mother, guardian)

### Without Permission (can-view-jpm not granted)

- [ ] PDF report shows NO highlighting
- [ ] Excel export shows NO highlighting
- [ ] Web preview shows NO highlighting
- [ ] Legend does NOT appear in web preview
- [ ] All other report features work normally

### Edge Cases

- [ ] Unauthenticated users (should default to no highlighting)
- [ ] Mixed permissions in team (each user sees based on their permission)
- [ ] Permission revoked while viewing report (requires page refresh)

## Files Modified

1. `app/Http/Controllers/ReportController.php`
2. `app/Http/Controllers/ScholarshipProfileController.php`
3. `app/Exports/WaitingListExport.php`
4. `resources/views/waiting_list_report.blade.php`
5. `resources/views/exports/waiting_list.blade.php`
6. `resources/js/Pages/Applicants/Modal/ReportView.vue`

## Migration Notes

### For Existing Installations

1. Ensure the `can-view-jpm` permission exists in database (should already exist)
2. Assign permission to appropriate roles/users who should see JPM highlighting
3. Clear application cache: `php artisan cache:clear`
4. Rebuild frontend assets: `npm run build`
5. Test reports with and without permission

### Backward Compatibility

- Default behavior: **No highlighting** (permission required)
- Existing reports generated before this change will not be affected
- Users must be explicitly granted permission to see highlighting

## Future Enhancements

### Potential Improvements

- [ ] Add permission to admin panel UI for easy assignment
- [ ] Create migration/seeder for automatic permission creation
- [ ] Add permission check to permission list in roles management
- [ ] Create audit log for permission-based report access
- [ ] Add tooltip explaining JPM highlighting when legend is visible

## Related Documentation

- See: `JPM_HIGHLIGHTING_IMPLEMENTATION.md` for original highlighting feature
- See: `REPORT_GENERATION_REFACTORING.md` for report generation details
- Package: [Spatie Laravel Permission](https://spatie.be/docs/laravel-permission)

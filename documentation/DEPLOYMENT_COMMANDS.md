# Deployment Commands

## System Option Refactoring - Required Commands

This guide provides the exact commands needed to deploy the SystemOption refactoring changes.

---

## 1. Node Modules Installation

Install required npm packages (if any new dependencies were added):

```bash
npm install
```

Or if using legacy peer dependencies:

```bash
npm install --legacy-peer-deps
```

Build assets for production:

```bash
npm run build
```

For development with hot reload:

```bash
npm run dev
```

---

## 2. Database Migration

Run the migration to convert enum fields to string type:

```bash
php artisan migrate
```

**Migration File:** `2025_11_06_045955_refactor_enum_fields_to_use_system_options.php`

**What it does:**

- Converts `attachment_type` in `disbursement_attachments` from enum to string
- Converts `grant_provision` in `scholarship_records` from enum to string
- Converts `obr_status` in `disbursements` from enum to string
- Converts `disbursement_type` in `disbursements` from enum to string
- Converts `priority_level` in `scholarship_profiles` from enum to string

**To rollback (if needed):**

```bash
php artisan migrate:rollback
```

---

## 3. Database Seeders

### A. SystemOption Seeder (REQUIRED)

Populate the SystemOption table with all predefined values:

```bash
php artisan db:seed --class=SystemOptionSeeder
```

**Populates 8 categories:**

- `attachment_type` - Attachment Types (3 items)
- `grant_provision` - Grant Provisions (4 items)
- `obr_status` - OBR Status (7 items)
- `disbursement_type` - Disbursement Types (3 items)
- `priority_level` - Priority Levels (4 items)
- `term` - Terms (6 items: 1ST SEMESTER, 2ND SEMESTER, 1ST TRIMESTER, SUMMER, REVIEW, N/A)
- `year_level` - Year Levels (7 items: 1ST, 2ND, 3RD, 4TH, 5TH, GRADUATE, REVIEW)
- `academic_year` - Academic Years (7 items: 2019-2020 through 2025-2026)

### B. PermissionSeeder (Optional)

Only run if setting up fresh installation or missing permissions:

```bash
php artisan db:seed --class=PermissionSeeder
```

**When to use:**

- Fresh installation
- Missing base permissions
- Resetting permission structure

### C. AddNewModulePermissionsSeeder (Optional)

Run when adding new module permissions:

```bash
php artisan db:seed --class=AddNewModulePermissionsSeeder
```

**Before running:**

1. Edit `database/seeders/AddNewModulePermissionsSeeder.php`
2. Update the `$newPermissions` array with your new permissions
3. Optionally configure role assignments

**Example:**

```php
$newPermissions = [
    ['name' => 'module.view', 'description' => 'View module'],
    ['name' => 'module.create', 'description' => 'Create module'],
    ['name' => 'module.edit', 'description' => 'Edit module'],
    ['name' => 'module.delete', 'description' => 'Delete module'],
];
```

---

## 4. Cache Management

Clear all caches after deployment:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Clear permission cache (Spatie):

```bash
php artisan permission:cache-reset
```

Optimize for production:

```bash
php artisan optimize
```

---

## 5. Combined Commands

### Fresh Setup (Development Only - DESTRUCTIVE)

**WARNING: This will drop all tables and lose all data!**

```bash
php artisan migrate:fresh --seed
php artisan db:seed --class=SystemOptionSeeder
```

### Standard Deployment

```bash
npm install --legacy-peer-deps
npm run build
php artisan migrate
php artisan db:seed --class=SystemOptionSeeder
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### Quick Development Setup

```bash
php artisan migrate && php artisan db:seed --class=SystemOptionSeeder
```

---

## 6. Verification Commands

### Check Migration Status

```bash
php artisan migrate:status
```

Look for: `2025_11_06_045955_refactor_enum_fields_to_use_system_options` with status `Ran`

### Count SystemOptions

```bash
php artisan tinker
```

Then in Tinker:

```php
use App\Models\SystemOption;

// Count by category
SystemOption::where('category', 'term')->count();           // Should be 6
SystemOption::where('category', 'year_level')->count();     // Should be 7
SystemOption::where('category', 'academic_year')->count();  // Should be 7

// List all categories
SystemOption::getCategories();

// View all terms
SystemOption::where('category', 'term')->pluck('value', 'label');
```

### Check Permissions

```bash
php artisan tinker
```

Then in Tinker:

```php
use Spatie\Permission\Models\Permission;

// Total permissions
Permission::count();

// List all permissions
Permission::pluck('name');
```

---

## 7. Production Deployment Checklist

- [ ] **Backup database before deployment**

  ```bash
  mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql
  ```

- [ ] **Put application in maintenance mode**

  ```bash
  php artisan down
  ```

- [ ] **Pull latest code**

  ```bash
  git pull origin main
  ```

- [ ] **Install/update dependencies**

  ```bash
  composer install --no-dev --optimize-autoloader
  npm install --legacy-peer-deps
  npm run build
  ```

- [ ] **Run migrations**

  ```bash
  php artisan migrate --force
  ```

- [ ] **Run seeders**

  ```bash
  php artisan db:seed --class=SystemOptionSeeder --force
  ```

- [ ] **Clear and optimize**

  ```bash
  php artisan cache:clear
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan optimize
  ```

- [ ] **Bring application back online**

  ```bash
  php artisan up
  ```

- [ ] **Verify deployment**
  - Navigate to Library → Option Values
  - Check all 8 tabs appear
  - Test creating/editing options
  - Verify existing records display correctly

---

## 8. Troubleshooting

### Migration Fails

```bash
# Check current migration status
php artisan migrate:status

# Rollback last migration
php artisan migrate:rollback --step=1

# Re-run migration
php artisan migrate
```

### Seeder Fails

```bash
# Run with verbose output
php artisan db:seed --class=SystemOptionSeeder -v

# Check database connection
php artisan db:show
```

### Cache Issues

```bash
# Nuclear option - clear everything
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan permission:cache-reset

# Rebuild
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### Node Module Issues

```bash
# Remove node_modules and reinstall
rm -rf node_modules package-lock.json
npm install --legacy-peer-deps

# Or on Windows PowerShell
Remove-Item -Recurse -Force node_modules, package-lock.json
npm install --legacy-peer-deps
```

---

## 9. Environment-Specific Notes

### Development

```bash
# Use .env with APP_ENV=local
npm run dev
php artisan migrate
php artisan db:seed --class=SystemOptionSeeder
```

### Staging

```bash
# Use .env.staging
npm run build
php artisan migrate --force
php artisan db:seed --class=SystemOptionSeeder --force
```

### Production

```bash
# Use .env.production with APP_ENV=production
composer install --no-dev --optimize-autoloader
npm install --legacy-peer-deps
npm run build
php artisan migrate --force
php artisan db:seed --class=SystemOptionSeeder --force
php artisan optimize
```

---

## 10. Rollback Plan

If issues occur after deployment:

```bash
# 1. Put in maintenance mode
php artisan down

# 2. Rollback migration
php artisan migrate:rollback --step=1

# 3. Clear cache
php artisan cache:clear
php artisan config:clear

# 4. Restore database backup (if needed)
mysql -u username -p database_name < backup_file.sql

# 5. Bring back online
php artisan up
```

---

## Notes

- Always test in development/staging before production
- Keep database backups before major changes
- Monitor logs after deployment: `storage/logs/laravel.log`
- Verify all critical workflows after deployment
- Document any custom modifications made during deployment

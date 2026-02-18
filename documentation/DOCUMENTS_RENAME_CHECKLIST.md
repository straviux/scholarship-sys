# Post-Migration Checklist: Forms & Letters → Documents and Forms

## ✅ Completed in This Session

### Core Files Created
- [x] `app/Models/Document.php` - New Document model
- [x] `app/Http/Controllers/DocumentsController.php` - New Documents controller with all CRUD operations
- [x] `resources/js/Pages/Documents/Index.vue` - New Documents management interface
- [x] `database/migrations/2026_02_18_000001_rename_form_templates_to_documents.php` - Database migration

### System Configuration Updated
- [x] `routes/web.php` - Routes updated to use `/documents` and `documents.` naming
- [x] `database/seeders/PermissionSeeder.php` - Permissions updated to `documents.*`
- [x] `database/seeders/MenuItemSeeder.php` - Menu updated to "Documents and Forms"
- [x] All role permissions updated (program_manager, moderator, user, jpm_admin)

### Controller Updates
- [x] `app/Http/Controllers/Admin/MenuItemController.php` - Menu icon labels updated
- [x] `app/Console/Commands/CreateProgramManagerRole.php` - Permissions updated
- [x] Utility scripts updated (`setup_program_manager.php`, `create_program_manager_role.php`)

### UI & User-Facing Updates
- [x] `resources/js/Pages/Home/Index.vue` - Card updated to "Documents and Forms"
- [x] `resources/js/Pages/Help/Index.vue` - Help section updated

### Documentation Updated
- [x] All documentation files updated with new controller/permission names
- [x] `DOCUMENTS_RENAME_COMPLETE.md` - Comprehensive summary created
- [x] `DOCUMENTS_RENAME_QUICK_REFERENCE.md` - Quick reference guide created

---

## 🔄 Next Steps for Full Implementation

### 1. Database Migration (MUST DO)
```bash
# Run the migration to rename the table
php artisan migrate

# Verify the migration ran successfully
php artisan migrate:status
```
**Status:** ⏳ Needs to be run in your environment

### 2. Clear Application Caches
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optional: Rebuild caches
php artisan config:cache
php artisan route:cache
```
**Status:** ⏳ Needs to be run after migration

### 3. Frontend Updates
- [x] Vue component created (`Documents/Index.vue`)
- [ ] Verify in browser that Documents and Forms section loads correctly
- [ ] Test file upload functionality
- [ ] Test file download functionality
- [ ] Verify permission checks work for different roles

**Status:** ⏳ Needs manual testing

### 4. Permission Testing
- [ ] Test as Administrator - should see full access
- [ ] Test as Program Manager - should see view/download only
- [ ] Test as Moderator - should see view/download only
- [ ] Test as User - should see view/download only
- [ ] Test as JPM Admin - should see view/download only

**Status:** ⏳ Needs manual testing

### 5. Database Verification
```bash
# Verify the table was renamed correctly
php artisan tinker

# Check the documents table exists
DB::table('documents')->count();

# Verify permissions exist
Permission::where('name', 'like', 'documents.%')->pluck('name');
```
**Status:** ⏳ Needs to be verified after migration

---

## 📋 Files to Review/Delete (When Ready)

These files are now legacy and should eventually be deleted:

```
❌ app/Models/FormTemplate.php
❌ app/Http/Controllers/FormTemplateController.php
❌ resources/js/Pages/FormTemplates/Index.vue
❌ database/migrations/2025_11_06_054757_create_form_templates_table.php
```

⚠️ **WARNING:** Do NOT delete these until you've confirmed the new system is working 100%.

**Suggested process:**
1. Deploy the new code
2. Run migrations
3. Test everything for 24-48 hours
4. Once confirmed working, remove legacy files
5. Make a final commit

---

## 🧪 Testing Checklist

### Navigation & Menu
- [ ] "Documents and Forms" appears in main menu
- [ ] Clicking menu item navigates to `/documents`
- [ ] Page loads without errors
- [ ] Sidebar shows active state correctly

### File Management (Admin)
- [ ] Can upload new document
- [ ] Upload success message appears
- [ ] File appears in list immediately
- [ ] Can edit document details
- [ ] Can delete document
- [ ] Delete confirmation appears
- [ ] All CRUD operations complete without errors

### File Access (All Users)
- [ ] Non-admin users see Documents and Forms in menu
- [ ] Can view list of available documents
- [ ] Can download documents (if permission allows)
- [ ] Cannot upload without permission
- [ ] Cannot edit without permission
- [ ] Cannot delete without permission

### Permissions & Roles
- [ ] Administrator role: full access (view, upload, edit, delete, download)
- [ ] Program Manager role: limited access (view, download)
- [ ] Moderator role: limited access (view, download)
- [ ] User role: limited access (view, download)
- [ ] JPM Admin role: limited access (view, download)

### Data Integrity
- [ ] All documents from old system are present in new table
- [ ] File paths are correct
- [ ] Metadata (title, description, category) intact
- [ ] Download counts preserved
- [ ] Creator/updater information preserved

### Browser Compatibility
- [ ] Works in Chrome
- [ ] Works in Firefox
- [ ] Works in Safari
- [ ] Works in Edge
- [ ] Responsive on mobile

---

## 📊 Rollback Plan (If Needed)

If something goes wrong and you need to revert:

```bash
# Rollback the migration (reverses table rename)
php artisan migrate:rollback

# The system will fall back to the old FormTemplate/FormTemplateController
# (Old code files still exist in legacy form)
```

**However:** Since you've updated routes/permissions, you'll need to keep the old system available or fix the routing. It's better to fix the new system forward than rollback.

---

## 🔍 Known Issues & Solutions

### Issue: Routes return 404
**Solution:** Run `php artisan route:clear` and check that `routes/web.php` has `DocumentsController` imported

### Issue: Permissions denied for all users
**Solution:** Ensure migration ran - check `permissions` table for `documents.*` entries

### Issue: Files not uploading
**Solution:** Verify `storage/app/public/documents/` folder exists and is writable

### Issue: Old `/form-templates` routes still work
**Solution:** If running side-by-side, both may work during transition period. Remove old routes when ready.

---

## 📞 Support Reference

For detailed information about what changed:
- See: [DOCUMENTS_RENAME_COMPLETE.md](./DOCUMENTS_RENAME_COMPLETE.md)
- See: [DOCUMENTS_RENAME_QUICK_REFERENCE.md](./DOCUMENTS_RENAME_QUICK_REFERENCE.md)

For specific file locations and code changes:
- Check git diff for exact changes made
- Review updated files listed in DOCUMENTS_RENAME_COMPLETE.md

---

## ✨ Summary

**All code changes are complete.** The system is ready for:

1. ✅ Database migration
2. ✅ Testing in your environment  
3. ✅ Deployment
4. ✅ Legacy file cleanup

The renaming from "Forms & Letters" to "Documents and Forms" is now fully implemented throughout the application:
- Database structure ✅
- Controllers & Models ✅
- Routes & Permissions ✅
- UI Components ✅
- Documentation ✅

Good luck with the deployment! 🚀

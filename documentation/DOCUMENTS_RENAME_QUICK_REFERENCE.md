# Documents and Forms Rename - Quick Implementation Guide

## What Changed - At a Glance

### New Files Created
```
✅ app/Models/Document.php
✅ app/Http/Controllers/DocumentsController.php
✅ resources/js/Pages/Documents/Index.vue
✅ database/migrations/2026_02_18_000001_rename_form_templates_to_documents.php
✅ DOCUMENTS_RENAME_COMPLETE.md (this project's summary)
```

### Files Updated
```
✅ routes/web.php
✅ database/seeders/PermissionSeeder.php
✅ database/seeders/MenuItemSeeder.php
✅ app/Http/Controllers/Admin/MenuItemController.php
✅ resources/js/Pages/Home/Index.vue
✅ resources/js/Pages/Help/Index.vue
✅ utilities/setup_program_manager.php
✅ utilities/create_program_manager_role.php
✅ app/Console/Commands/CreateProgramManagerRole.php
✅ documentation/SIDEBAR_MENU_IMPLEMENTATION.md
✅ documentation/PROGRAM_MANAGER_ROLE_IMPLEMENTATION.md
✅ documentation/PROGRAM_MANAGER_QUICK_REFERENCE.md
✅ documentation/ACTIVITY_LOGGING_IMPLEMENTATION_COMPLETE.md
✅ documentation/ACTIVITY_LOGGING_AUDIT_REPORT.md
```

### Legacy Files (Can be deprecated)
```
📝 app/Models/FormTemplate.php (OLD)
📝 app/Http/Controllers/FormTemplateController.php (OLD)
📝 resources/js/Pages/FormTemplates/Index.vue (OLD)
📝 database/migrations/2025_11_06_054757_create_form_templates_table.php (OLD)
```

---

## Key Mappings

### Models & Controllers
| Old | New |
|-----|-----|
| `FormTemplate` | `Document` |
| `FormTemplateController` | `DocumentsController` |

### Database
| Old | New |
|-----|-----|
| Table: `form_templates` | Table: `documents` |
| Field storage: `form-templates/` | Field storage: `documents/` |

### Routes & Permissions
| Old | New |
|-----|-----|
| `form-templates.index` | `documents.index` |
| `form-templates.store` | `documents.store` |
| `form-templates.update` | `documents.update` |
| `form-templates.destroy` | `documents.destroy` |
| `form-templates.download` | `documents.download` |
| `form-templates.view` | `documents.view` |
| `form-templates.upload` | `documents.upload` |
| `form-templates.edit` | `documents.edit` |
| `form-templates.delete` | `documents.delete` |

### UI Text
| Old | New |
|-----|-----|
| "Forms & Letters" | "Documents and Forms" |
| "Form/letter uploaded" | "Document uploaded" |
| "Form/letter updated" | "Document updated" |

---

## How to Verify Everything Works

### 1. Check Database
```sql
-- Verify the documents table exists and has the correct structure
SELECT * FROM documents LIMIT 1;
```

### 2. Check Routes
```bash
php artisan route:list | grep documents
```

### 3. Check Permissions
```bash
php artisan tinker
Permission::where('name', 'like', 'documents.%')->get();
```

### 4. Test in UI
- Navigate to Documents and Forms in the menu
- Verify you can upload a file
- Verify you can download a file
- Verify permissions are enforced for different roles

---

## Implementation Order (If Migrating)

If you're migrating an existing system:

1. **Backup your database** ⚠️
2. Run migration: `php artisan migrate`
3. Clear caches: `php artisan cache:clear && php artisan config:cache`
4. Test all functionality
5. Update any external documentation
6. Delete legacy files when confident everything works

---

## Files That Reference the New Names

### Routes
- `documents.index` - View all documents
- `documents.store` - Upload a new document
- `documents.update` - Update a document
- `documents.destroy` - Delete a document
- `documents.download` - Download a document

### Permissions Needed
- `documents.view` - Can view documents
- `documents.upload` - Can upload documents
- `documents.edit` - Can edit documents
- `documents.delete` - Can delete documents
- `documents.download` - Can download documents

### Roles Updated
All roles that had `form-templates.*` permissions now have `documents.*`:
- ✅ Administrator (all permissions)
- ✅ Program Manager (`view`, `download`)
- ✅ Moderator (`view`, `download`)
- ✅ User (`view`, `download`)
- ✅ JPM Admin (`view`, `download`)

---

## What Stays the Same

- Activity logging still works (updated to reference DocumentsController)
- File storage structure remains the same (just moved to `documents/` folder)
- All relationships and functionality intact
- Permission-based access control unchanged
- Menu integration unchanged

---

## Important Notes

⚠️ **Breaking Changes:**
- Old routes (`/form-templates`) will not work - must use new routes (`/documents`)
- Old permissions (`form-templates.*`) are no longer used - use `documents.*`
- Old Vue components referencing old routes will break

✅ **Non-Breaking:**
- Database migration is reversible
- Old model/controller files can coexist (not recommended long-term)
- Menu links will automatically update to new routes

---

## Questions or Issues?

Refer to [DOCUMENTS_RENAME_COMPLETE.md](./DOCUMENTS_RENAME_COMPLETE.md) for detailed documentation of all changes.

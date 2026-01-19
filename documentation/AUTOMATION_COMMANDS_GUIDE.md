# Automated Permission Generation Guide

## 🚀 Quick Start

Instead of manually creating permissions, use the automated command:

```bash
php artisan make:permissions {module-name}
```

---

## 📖 Usage Examples

### **Example 1: Basic Usage (Default Actions)**

Generate standard CRUD permissions (view, create, edit, delete):

```bash
php artisan make:permissions announcements
```

**Output:**

```
✓ Created: announcements.view
✓ Created: announcements.create
✓ Created: announcements.edit
✓ Created: announcements.delete
```

---

### **Example 2: Custom Actions**

Specify custom actions for your module:

```bash
php artisan make:permissions documents --actions=view --actions=upload --actions=download --actions=approve
```

**Output:**

```
✓ Created: documents.view
✓ Created: documents.upload
✓ Created: documents.download
✓ Created: documents.approve
```

---

### **Example 3: Generate with Seeder**

Create permissions AND generate a seeder file:

```bash
php artisan make:permissions announcements --seeder
```

**Output:**

```
✓ Created: announcements.view
✓ Created: announcements.create
✓ Created: announcements.edit
✓ Created: announcements.delete
📄 Seeder generated: database/seeders/AnnouncementsPermissionsSeeder.php

Run: php artisan db:seed --class=AnnouncementsPermissionsSeeder
```

---

### **Example 4: Auto-Assign to Roles**

Create permissions and automatically assign to roles:

```bash
php artisan make:permissions announcements --assign
```

**Interactive prompts:**

```
✓ Assigned all to: administrator
? Assign permissions to moderator role? (yes/no) [yes]
✓ Assigned 3 permissions to: moderator (view, create, edit)
? Assign view permission to user role? (yes/no) [yes]
✓ Assigned view permission to: user
```

---

### **Example 5: Complete Automation (One Command)**

Create permissions, generate seeder, run seeder, and assign to roles:

```bash
php artisan make:permissions announcements --run --assign
```

This does **everything** in one go! ✨

---

## 🎛️ Command Options

| Option        | Description                                 | Example                           |
| ------------- | ------------------------------------------- | --------------------------------- |
| `--actions=*` | Custom actions (can specify multiple)       | `--actions=view --actions=create` |
| `--assign`    | Automatically assign to roles (interactive) | `--assign`                        |
| `--seeder`    | Generate a seeder file                      | `--seeder`                        |
| `--run`       | Generate and run seeder immediately         | `--run`                           |

---

## 📋 Common Workflows

### **Workflow 1: Quick Permission Creation**

For rapid development, just create the permissions:

```bash
php artisan make:permissions blog-posts
```

Then assign via Admin UI later.

---

### **Workflow 2: Production Setup**

Generate seeder for version control and deployment:

```bash
php artisan make:permissions blog-posts --seeder
```

Commit the seeder to git, run on production server.

---

### **Workflow 3: Development Mode**

Everything automated for local development:

```bash
php artisan make:permissions blog-posts --run --assign
```

Permissions created, seeder generated, permissions assigned!

---

## 🎯 Real-World Examples

### **Example: Blog System**

```bash
php artisan make:permissions blog-posts --actions=view --actions=create --actions=edit --actions=delete --actions=publish --actions=schedule --run --assign
```

**Creates:**

- ✓ blog-posts.view
- ✓ blog-posts.create
- ✓ blog-posts.edit
- ✓ blog-posts.delete
- ✓ blog-posts.publish
- ✓ blog-posts.schedule

**Generates:**

- database/seeders/BlogPostsPermissionsSeeder.php

**Assigns:**

- Administrator: all permissions
- Moderator: view, create, edit (prompted)
- User: view only (prompted)

---

### **Example: Document Management**

```bash
php artisan make:permissions documents --actions=view --actions=upload --actions=download --actions=edit --actions=delete --actions=approve --actions=reject --seeder
```

**Creates:**

- ✓ documents.view
- ✓ documents.upload
- ✓ documents.download
- ✓ documents.edit
- ✓ documents.delete
- ✓ documents.approve
- ✓ documents.reject

---

### **Example: Inventory System**

```bash
php artisan make:permissions inventory --actions=view --actions=create --actions=edit --actions=delete --actions=stock-in --actions=stock-out --actions=adjust --run
```

---

## ⚡ Power User Tips

### **Tip 1: Default Actions**

If you don't specify `--actions`, it defaults to:

- view
- create
- edit
- delete

Perfect for standard CRUD modules!

---

### **Tip 2: Kebab-Case Module Names**

The command automatically handles module name formatting:

```bash
php artisan make:permissions "Blog Posts"
# Creates: blog-posts.view, blog-posts.create, etc.

php artisan make:permissions blog_posts
# Creates: blog-posts.view, blog-posts.create, etc.
```

---

### **Tip 3: Seeder is Editable**

Generated seeders can be customized:

```bash
php artisan make:permissions announcements --seeder
```

Then edit `database/seeders/AnnouncementsPermissionsSeeder.php` to add custom role assignments.

---

### **Tip 4: Re-run is Safe**

Running the command again won't duplicate permissions:

```bash
php artisan make:permissions announcements
# ⚠ Already exists: announcements.view
# ⚠ Already exists: announcements.create
```

---

### **Tip 5: Add More Permissions Later**

Add additional actions to existing module:

```bash
# First run created: view, create, edit, delete
php artisan make:permissions announcements

# Later, add more actions
php artisan make:permissions announcements --actions=publish --actions=schedule
```

---

## 🔄 Complete Example Workflow

Let's add a complete "Events" module:

### **Step 1: Generate Permissions**

```bash
php artisan make:permissions events --actions=view --actions=create --actions=edit --actions=delete --actions=publish --actions=rsvp --run --assign
```

### **Step 2: Create Routes**

Add to `routes/web.php`:

```php
Route::middleware(['auth'])->prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index'])
        ->middleware('permission:events.view')
        ->name('events.index');

    Route::post('/', [EventController::class, 'store'])
        ->middleware('permission:events.create')
        ->name('events.store');

    Route::put('/{event}', [EventController::class, 'update'])
        ->middleware('permission:events.edit')
        ->name('events.update');

    Route::delete('/{event}', [EventController::class, 'destroy'])
        ->middleware('permission:events.delete')
        ->name('events.destroy');
});
```

### **Step 3: Use in Vue Component**

```vue
<script setup>
	import { usePermissions } from '@/composables/usePermissions';

	const { can } = usePermissions();
</script>

<template>
	<div>
		<Button v-if="can('events.create')" label="Create Event" />
		<Button v-can="'events.publish'" label="Publish" />
		<Button v-can="'events.rsvp'" label="RSVP" />
	</div>
</template>
```

**Done!** 🎉

---

## 📊 Comparison: Manual vs Automated

### **Manual Method (Old Way)**

1. Create seeder file manually
2. Write permissions array
3. Write permission creation logic
4. Write role assignment logic
5. Run seeder
6. Assign via Admin UI

**Time: ~15-20 minutes**

---

### **Automated Method (New Way)**

```bash
php artisan make:permissions module-name --run --assign
```

**Time: ~30 seconds**

---

## 🛠️ Advanced Usage

### **Create Multiple Modules at Once**

```bash
php artisan make:permissions announcements --seeder
php artisan make:permissions events --seeder
php artisan make:permissions documents --seeder
```

Then run all seeders:

```bash
php artisan db:seed --class=AnnouncementsPermissionsSeeder
php artisan db:seed --class=EventsPermissionsSeeder
php artisan db:seed --class=DocumentsPermissionsSeeder
```

---

### **Production Deployment**

On your server after deployment:

```bash
# Run all new permission seeders
php artisan db:seed --class=AnnouncementsPermissionsSeeder
php artisan db:seed --class=EventsPermissionsSeeder

# Clear caches
php artisan cache:clear
php artisan permission:cache-reset
```

---

## ❓ FAQ

### **Q: What if I need to add more actions later?**

A: Just run the command again with the new actions:

```bash
php artisan make:permissions announcements --actions=archive --actions=restore
```

Existing permissions are preserved.

---

### **Q: Can I customize the generated seeder?**

A: Yes! After generation, edit the seeder file to add custom logic.

---

### **Q: Do I still need the Admin UI?**

A: Yes! Use the Admin UI to adjust permissions after initial setup.

---

### **Q: What if I typo the module name?**

A: Just create the correct one. The old permissions can be deleted via Admin UI.

---

## 🎓 Best Practices

✅ **Use descriptive module names:** `blog-posts` not `posts`

✅ **Generate seeder for production:** `--seeder` for version control

✅ **Use --run for development:** Quick iteration during development

✅ **Review generated seeders:** Check and customize as needed

✅ **Document custom actions:** Add comments in seeder for complex permissions

---

## 🚀 Quick Reference

```bash
# Basic (default CRUD actions)
php artisan make:permissions module-name

# Custom actions
php artisan make:permissions module-name --actions=view --actions=create

# With seeder
php artisan make:permissions module-name --seeder

# Auto-assign to roles
php artisan make:permissions module-name --assign

# Everything automated
php artisan make:permissions module-name --run --assign
```

---

**Happy coding!** 🎉

For more details, see: `PERMISSION_SYSTEM_GUIDE.md` and `ADDING_NEW_FEATURES_GUIDE.md`

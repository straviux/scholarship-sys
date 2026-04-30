# Adding New Features with Permissions - Quick Guide

## 📋 Checklist for Adding New Functionality

When you add a new feature/module to your scholarship system, follow these steps:

---

## **Step 1: Define Your Permissions**

Think about what actions users can perform in your new module. Use this naming convention:

- `module-name.view` - View/read access
- `module-name.create` - Create new records
- `module-name.edit` - Modify existing records
- `module-name.delete` - Remove records
- `module-name.export` - Export data
- `module-name.approve` - Approval actions
- `module-name.manage` - Full management (combines multiple actions)

**Example:** For an "Announcements" module:

```
announcements.view
announcements.create
announcements.edit
announcements.delete
announcements.publish
```

---

## **Step 2: Add Permissions to Database**

### **Option A: Quick Method (Add to existing seeder)**

Edit `database/seeders/PermissionSeeder.php`:

```php
$permissions = [
    // ... existing permissions ...

    // Announcements Module (NEW)
    ['name' => 'announcements.view', 'description' => 'View announcements'],
    ['name' => 'announcements.create', 'description' => 'Create announcements'],
    ['name' => 'announcements.edit', 'description' => 'Edit announcements'],
    ['name' => 'announcements.delete', 'description' => 'Delete announcements'],
    ['name' => 'announcements.publish', 'description' => 'Publish announcements'],
];
```

Then run:

```bash
php artisan db:seed --class=PermissionSeeder
```

### **Option B: Create Separate Seeder (Recommended for production)**

```bash
php artisan make:seeder AnnouncementPermissionsSeeder
```

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AnnouncementPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            ['name' => 'announcements.view', 'description' => 'View announcements'],
            ['name' => 'announcements.create', 'description' => 'Create announcements'],
            ['name' => 'announcements.edit', 'description' => 'Edit announcements'],
            ['name' => 'announcements.delete', 'description' => 'Delete announcements'],
            ['name' => 'announcements.publish', 'description' => 'Publish announcements'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                ['guard_name' => 'web']
            );
        }

        // Give all permissions to administrator
        $adminRole = Role::findByName('administrator');
        $adminRole->givePermissionTo(Permission::whereIn('name', [
            'announcements.view',
            'announcements.create',
            'announcements.edit',
            'announcements.delete',
            'announcements.publish',
        ])->get());

        $this->command->info('Announcement permissions added!');
    }
}
```

Run:

```bash
php artisan db:seed --class=AnnouncementPermissionsSeeder
```

---

## **Step 3: Protect Your Routes**

### **Method A: Using Middleware**

```php
// routes/web.php

Route::middleware(['auth'])->group(function () {
    // View announcements (requires permission)
    Route::get('/announcements', [AnnouncementController::class, 'index'])
        ->middleware('permission:announcements.view')
        ->name('announcements.index');

    // Create announcement (requires permission)
    Route::post('/announcements', [AnnouncementController::class, 'store'])
        ->middleware('permission:announcements.create')
        ->name('announcements.store');

    // Edit announcement (requires permission)
    Route::put('/announcements/{announcement}', [AnnouncementController::class, 'update'])
        ->middleware('permission:announcements.edit')
        ->name('announcements.update');

    // Delete announcement (requires permission)
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])
        ->middleware('permission:announcements.delete')
        ->name('announcements.destroy');
});
```

### **Method B: Using Resource Routes with Middleware**

```php
Route::middleware(['auth', 'permission:announcements.view|announcements.create|announcements.edit|announcements.delete'])
    ->group(function () {
        Route::resource('announcements', AnnouncementController::class);
    });
```

---

## **Step 4: Add Permission Checks in Controller**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnnouncementController extends Controller
{
    public function index()
    {
        // Optional: Additional check (middleware already protects route)
        $this->authorize('viewAny', Announcement::class);

        $announcements = Announcement::latest()->paginate(10);

        return Inertia::render('Announcements/Index', [
            'announcements' => $announcements,
        ]);
    }

    public function store(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('announcements.create')) {
            abort(403, 'You do not have permission to create announcements.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $announcement = Announcement::create($validated);

        return redirect()->route('announcements.index')
            ->with('success', 'Announcement created successfully.');
    }

    public function update(Request $request, Announcement $announcement)
    {
        // Using Gate authorize method
        $this->authorize('update', $announcement);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);

        $announcement->update($validated);

        return back()->with('success', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        $this->authorize('delete', $announcement);

        $announcement->delete();

        return back()->with('success', 'Announcement deleted successfully.');
    }
}
```

---

## **Step 5: Use Permissions in Your Vue Components**

### **Example: Announcements Index Page**

```vue
<script setup>
	import { usePermissions } from '@/composables/usePermissions';
	import AdminLayout from '@/Layouts/AdminLayout.vue';
	import Button from 'primevue/button';
	import DataTable from 'primevue/datatable';
	import Column from 'primevue/column';

	const { can } = usePermissions();

	defineProps({
		announcements: Object,
	});

	const openCreateDialog = () => {
		// Your create logic
	};

	const editAnnouncement = (announcement) => {
		// Your edit logic
	};

	const deleteAnnouncement = (announcement) => {
		// Your delete logic
	};

	const publishAnnouncement = (announcement) => {
		// Your publish logic
	};
</script>

<template>
	<AdminLayout>
		<div class="space-y-6">
			<!-- Header with Create Button -->
			<div class="flex items-center justify-between">
				<h1 class="text-2xl font-bold">Announcements</h1>

				<!-- Only show if user has create permission -->
				<Button
					v-if="can('announcements.create')"
					@click="openCreateDialog"
					label="Create Announcement"
					icon="pi pi-plus"
					severity="success"
				/>
			</div>

			<!-- Announcements Table -->
			<DataTable :value="announcements.data">
				<Column field="title" header="Title" />
				<Column field="content" header="Content" />
				<Column header="Actions">
					<template #body="slotProps">
						<div class="flex gap-2">
							<!-- Edit button - only if has permission -->
							<Button
								v-if="can('announcements.edit')"
								@click="editAnnouncement(slotProps.data)"
								icon="pi pi-pencil"
								severity="info"
								text
							/>

							<!-- Publish button - only if has permission -->
							<Button
								v-if="can('announcements.publish')"
								@click="publishAnnouncement(slotProps.data)"
								icon="pi pi-send"
								severity="success"
								text
							/>

							<!-- Delete button - only if has permission -->
							<Button
								v-if="can('announcements.delete')"
								@click="deleteAnnouncement(slotProps.data)"
								icon="pi pi-trash"
								severity="danger"
								text
							/>
						</div>
					</template>
				</Column>
			</DataTable>
		</div>
	</AdminLayout>
</template>
```

### **Using Directive (Simpler)**

```vue
<template>
	<div class="flex gap-2">
		<!-- Elements automatically hidden if no permission -->
		<Button v-can="'announcements.edit'" @click="edit" icon="pi pi-pencil" />
		<Button v-can="'announcements.publish'" @click="publish" icon="pi pi-send" />
		<Button v-can="'announcements.delete'" @click="del" icon="pi pi-trash" />
	</div>
</template>
```

---

## **Step 6: Add Navigation Link (Optional)**

If you want to add the new module to the sidebar, edit `AdminLayout.vue`:

```vue
<li>
    <SidebarLink 
        :href="route('announcements.index')"
        :active="route().current('announcements.index')"
    >
        <i class="pi pi-megaphone mr-2"></i>
        <span class="-mr-1 font-medium">Announcements</span>
    </SidebarLink>
</li>
```

### **With Permission Check:**

```vue
<li v-if="can('announcements.view')">
    <SidebarLink 
        :href="route('announcements.index')"
        :active="route().current('announcements.index')"
    >
        <i class="pi pi-megaphone mr-2"></i>
        <span class="-mr-1 font-medium">Announcements</span>
    </SidebarLink>
</li>
```

---

## **Step 7: Assign Permissions to Roles**

After adding permissions, assign them to roles:

### **Method A: Using Admin UI**

1. Login as administrator
2. Go to **Access Control** (`/access-control`)
3. Open the **Roles & Permissions** tab
4. Select the role you want to update
5. Toggle the new permissions on/off
6. Click "Save All Changes"

### **Method B: Programmatically in Seeder**

```php
// In your seeder after creating permissions

$moderatorRole = Role::findByName('moderator');
$moderatorRole->givePermissionTo([
    'announcements.view',
    'announcements.create',
    'announcements.edit',
]);

$userRole = Role::findByName('user');
$userRole->givePermissionTo([
    'announcements.view',
]);
```

---

## **Quick Reference: Common Patterns**

### **1. CRUD Permissions (Standard Module)**

```php
'module.view'     // List and view details
'module.create'   // Create new records
'module.edit'     // Update existing records
'module.delete'   // Delete records
'module.export'   // Export data
```

### **2. Approval Workflow Permissions**

```php
'module.view'
'module.create'
'module.edit'
'module.submit'   // Submit for approval
'module.approve'  // Approve submitted items
'module.reject'   // Reject submitted items
```

### **3. Status Management Permissions**

```php
'module.view'
'module.edit'
'module.update-status'    // Change status
'module.publish'          // Publish content
'module.archive'          // Archive old content
```

### **4. Advanced Permissions**

```php
'module.view-own'         // View only own records
'module.view-all'         // View all records
'module.edit-own'         // Edit only own records
'module.edit-all'         // Edit all records
'module.manage-settings'  // Module-specific settings
```

---

## **Real-World Example: Adding "Document Library" Module**

### **1. Define Permissions**

```php
// database/seeders/DocumentLibraryPermissionsSeeder.php
$permissions = [
    ['name' => 'documents.view', 'description' => 'View documents'],
    ['name' => 'documents.upload', 'description' => 'Upload documents'],
    ['name' => 'documents.download', 'description' => 'Download documents'],
    ['name' => 'documents.edit', 'description' => 'Edit document details'],
    ['name' => 'documents.delete', 'description' => 'Delete documents'],
    ['name' => 'documents.approve', 'description' => 'Approve documents'],
];
```

### **2. Create Routes**

```php
// routes/web.php
Route::middleware(['auth'])->prefix('documents')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])
        ->middleware('permission:documents.view')
        ->name('documents.index');

    Route::post('/', [DocumentController::class, 'store'])
        ->middleware('permission:documents.upload')
        ->name('documents.store');

    Route::get('/{document}/download', [DocumentController::class, 'download'])
        ->middleware('permission:documents.download')
        ->name('documents.download');

    Route::put('/{document}', [DocumentController::class, 'update'])
        ->middleware('permission:documents.edit')
        ->name('documents.update');

    Route::delete('/{document}', [DocumentController::class, 'destroy'])
        ->middleware('permission:documents.delete')
        ->name('documents.destroy');
});
```

### **3. Use in Vue Component**

```vue
<script setup>
	import { usePermissions } from '@/composables/usePermissions';

	const { can, canAny } = usePermissions();
</script>

<template>
	<div>
		<Button v-if="can('documents.upload')" @click="uploadDocument" label="Upload Document" />

		<div v-if="canAny(['documents.edit', 'documents.delete'])" class="actions">
			<Button v-can="'documents.edit'" @click="edit" />
			<Button v-can="'documents.delete'" @click="remove" />
		</div>

		<Button v-can="'documents.download'" @click="download" label="Download" />
	</div>
</template>
```

---

## **Testing Your Permissions**

### **Test in Browser Console:**

```javascript
// Check if permissions are loaded
console.log(this.$page.props.permissions);

// Test permission check
import { usePage } from '@inertiajs/vue3';
const page = usePage();
console.log(page.props.permissions.includes('announcements.create'));
```

### **Test in Tinker:**

```bash
php artisan tinker
```

```php
// Check user permissions
$user = User::find(1);
$user->getAllPermissions()->pluck('name');

// Check specific permission
$user->can('announcements.create');

// Check role permissions
$role = Role::findByName('moderator');
$role->permissions->pluck('name');
```

---

## **Troubleshooting**

### **Permission not working?**

```bash
# Clear all caches
php artisan cache:clear
php artisan permission:cache-reset
php artisan config:clear
```

### **Can't see permission in UI?**

1. Check if permission exists in database
2. Check if role has the permission
3. Check if user has the role
4. Clear browser cache and refresh

### **Permission check always returns false?**

1. Verify user is logged in
2. Check permission name spelling (case-sensitive!)
3. Verify middleware on route
4. Check HandleInertiaRequests shares permissions

---

## **Best Practices**

✅ **DO:**

- Use consistent naming: `module.action`
- Protect routes with middleware
- Add permission checks in controllers (defense in depth)
- Document new permissions in your code
- Test with different roles before deploying

❌ **DON'T:**

- Skip backend validation (never trust frontend alone)
- Create overly granular permissions (keep it simple)
- Forget to assign permissions to administrator role
- Hardcode permission checks everywhere (use the composable)

---

## **Summary Checklist**

- [ ] Define permission names (module.action format)
- [ ] Add permissions to seeder
- [ ] Run seeder to add to database
- [ ] Protect routes with middleware
- [ ] Add permission checks in controller
- [ ] Use permissions in Vue components (`can()` or `v-can`)
- [ ] Add navigation link (if needed)
- [ ] Assign permissions to roles via Admin UI
- [ ] Test with different user roles
- [ ] Clear caches
- [ ] Document new permissions

---

**Need help?** Check the main `PERMISSION_SYSTEM_GUIDE.md` for complete reference.

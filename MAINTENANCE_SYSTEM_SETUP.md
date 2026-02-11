# Maintenance Announcement System Setup Guide

## Overview
This system allows administrators to schedule and manage system maintenance announcements. During maintenance, only administrators can access the system, while other users see a maintenance page with a countdown timer.

## Files Created

### 1. **Migration**
- `database/migrations/2026_02_11_000000_create_maintenance_announcements_table.php`
  - Creates the `maintenance_announcements` table with fields for storing maintenance data

### 2. **Model**
- `app/Models/MaintenanceAnnouncement.php`
  - Handles database operations and countdown calculations
  - Key methods:
    - `getActive()` - Get current active maintenance
    - `isUnderMaintenance()` - Check if system is under maintenance
    - `getCountdownData()` - Get countdown status and remaining time

### 3. **Controller**
- `app/Http/Controllers/Admin/MaintenanceController.php`
  - API endpoints for managing maintenance announcements
  - Endpoints:
    - `GET /api/admin/maintenance/status` - Get current status
    - `GET /api/admin/maintenance/list` - Get all announcements
    - `POST /api/admin/maintenance` - Create/update announcement
    - `POST /api/admin/maintenance/activate` - Activate maintenance
    - `POST /api/admin/maintenance/deactivate` - Deactivate maintenance

### 4. **Middleware**
- `app/Http/Middleware/CheckMaintenance.php`
  - Intercepts requests and checks maintenance status
  - Allows admins through, blocks other users with 503 response
  - Returns maintenance page for web requests, JSON for AJAX

### 5. **Vue Components**
- `resources/views/admin/maintenance/index.vue` - Admin control panel
- `resources/components/MaintenanceBanner.vue` - Countdown banner component

### 6. **Views**
- `resources/views/maintenance/show.blade.php` - Maintenance page for users

## Setup Instructions

### Step 1: Run Migration
```bash
php artisan migrate
```

### Step 2: Register Middleware
Add to `app/Http/Kernel.php` in the `$routeMiddleware` array:
```php
'maintenance' => \App\Http\Middleware\CheckMaintenance::class,
```

### Step 3: Add Routes
Add to `routes/api.php`:
```php
use App\Http\Controllers\Admin\MaintenanceController;

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::prefix('admin/maintenance')->group(function () {
        Route::get('/', [MaintenanceController::class, 'index']);
        Route::get('/status', [MaintenanceController::class, 'getStatus']);
        Route::get('/list', [MaintenanceController::class, 'list']);
        Route::post('/', [MaintenanceController::class, 'store']);
        Route::post('/activate', [MaintenanceController::class, 'activate']);
        Route::post('/deactivate', [MaintenanceController::class, 'deactivate']);
    });
});
```

### Step 4: Apply Middleware to Routes
Add the middleware to routes you want to protect:
```php
Route::middleware(['maintenance'])->group(function () {
    // Your app routes here
});
```

Or globally in `app/Http/Kernel.php` `$middleware` array:
```php
protected $middleware = [
    // ... other middleware
    \App\Http\Middleware\CheckMaintenance::class,
];
```

### Step 5: Add Route for Admin Panel
Add to `routes/web.php`:
```php
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/maintenance', [MaintenanceController::class, 'index']);
});
```

## Features

### ✅ What This System Does
1. **Admin Control Panel** - Manage maintenance announcements from a Vue dashboard
2. **Scheduled Maintenance** - Set start and end times
3. **Countdown Timer** - Shows users how long until maintenance
4. **Real-time Status** - Check current maintenance status via API
5. **Alert Types** - Info, Warning, or Critical alerts
6. **Admin Access** - Admins bypass maintenance mode automatically
7. **History Tracking** - View all past and current announcements
8. **Responsive Design** - Works on all devices

### Using the Admin Panel

1. **Enable Maintenance Mode**
   - Go to `/admin/maintenance`
   - Toggle "Enable Maintenance Mode"
   - Set title, message, start time, and end time
   - Select alert type (info/warning/critical)
   - Click "Save Changes"

2. **Immediate Activation**
   - Use the "Activate Now" button for urgent maintenance
   - Requires start and end times

3. **Deactivate**
   - Click "Deactivate Now" to immediately end maintenance mode
   - Users regain access instantly

4. **Preview**
   - See how the banner looks before activating
   - Live countdown preview

### User Experience

**Before Maintenance:**
- Users see countdown banner on relevant pages
- Format: "HH:MM:SS remaining"
- Can dismiss banner (if dismissible is enabled)

**During Maintenance:**
- Users redirected to maintenance page (non-admins)
- Shows active maintenance message
- Page auto-refreshes every 30 seconds
- Admins see normal interface

**After Maintenance:**
- Users regain access automatically
- Page auto-refreshes after maintenance ends

## Security Considerations

1. **Admin-Only Access** - Only authenticated admins can manage maintenance
2. **CSRF Protection** - All POST requests require valid CSRF token
3. **Role Checking** - Uses `hasRole('admin')` to verify admin status
4. **Database Validation** - Server-side validation on all inputs

## API Endpoints

### Get Status
```
GET /api/admin/maintenance/status
Response:
{
  "is_under_maintenance": boolean,
  "announcement": {
    "title": string,
    "message": string,
    "type": "info|warning|critical",
    "countdown": {
      "status": "upcoming|active|completed",
      "message": string,
      "start_time": ISO8601,
      "seconds_remaining": number
    }
  }
}
```

### List All Announcements
```
GET /api/admin/maintenance/list
Response:
{
  "data": [MaintenanceAnnouncement, ...]
}
```

### Create/Update Announcement
```
POST /api/admin/maintenance
Body:
{
  "is_active": boolean,
  "title": string,
  "message": string,
  "start_time": "2026-02-11T10:00:00",
  "end_time": "2026-02-11T12:00:00",
  "type": "warning",
  "allow_admin_access": true
}
```

### Activate Maintenance
```
POST /api/admin/maintenance/activate
Body:
{
  "title": string,
  "message": string,
  "start_time": "2026-02-11T10:00:00",
  "end_time": "2026-02-11T12:00:00",
  "type": "warning"
}
```

### Deactivate Maintenance
```
POST /api/admin/maintenance/deactivate
Response:
{
  "success": true,
  "message": "Maintenance deactivated"
}
```

## Customization

### Styling
Modify `resources/views/maintenance/show.blade.php` to match your branding

### Components
Modify Vue components for custom UI:
- `resources/views/admin/maintenance/index.vue` - Admin panel UI
- `resources/components/MaintenanceBanner.vue` - Banner styling

### Alerts
Add custom notification system integration (email, SMS, push notifications)

## Troubleshooting

**Problem:** Admin still sees maintenance page
- **Solution:** Verify `hasRole('admin')` is returning true
- Check user role in database

**Problem:** Maintenance timer not updating
- **Solution:** Check browser console for JavaScript errors
- Verify server time is synchronized (NTP)

**Problem:** Routes not working
- **Solution:** Clear route cache: `php artisan route:clear`
- Verify middleware is registered in Kernel.php

**Problem:** Middleware not triggering
- **Solution:** Ensure middleware is added to routes
- Check middleware order in Kernel.php

## Testing

```php
// Create test maintenance record
$maintenance = MaintenanceAnnouncement::create([
    'is_active' => true,
    'title' => 'Test Maintenance',
    'message' => 'This is a test',
    'start_time' => now(),
    'end_time' => now()->addHours(2),
    'type' => 'warning',
    'allow_admin_access' => true,
]);

// Check if under maintenance
MaintenanceAnnouncement::isUnderMaintenance(); // true

// Get countdown
$maintenance->getCountdownData();
```

## Notes

- Times should be in the server's timezone
- Ensure database is backed up before migration
- Test in staging environment first
- Consider setting up notifications for when maintenance starts/ends

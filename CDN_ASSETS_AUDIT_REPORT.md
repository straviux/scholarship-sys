# CDN & External Assets Audit Report
**Date:** February 18, 2026

## Summary
Found **7 external CDN sources** with multiple asset references that should be localized for:
- Security (prevent data leakage)
- Privacy (prevent tracking)
- Performance (faster loading with local assets)
- Reliability (no external dependencies)

---

## Critical Issues Found

### 1. **Video.js Library** ❌ CRITICAL
**File:** `resources/views/vendor/laravel-file-viewer/previewFileVideo.blade.php`

External CDN URLs:
```php
<link href="https://vjs.zencdn.net/7.18.1/video-js.css" rel="stylesheet" />
<script src="https://vjs.zencdn.net/7.18.1/video.min.js"></script>
<link href="https://unpkg.com/@videojs/themes@1/dist/city/index.css" rel="stylesheet" />
<link href="https://unpkg.com/@videojs/themes@1/dist/fantasy/index.css" rel="stylesheet">
<link href="https://unpkg.com/@videojs/themes@1/dist/forest/index.css" rel="stylesheet">
<link href="https://unpkg.com/@videojs/themes@1/dist/sea/index.css" rel="stylesheet">
```

**Status:** Multiple external CDNs (vjs.zencdn.net, unpkg.com)

---

### 2. **Image Viewer (ViewerJS)** ❌ CRITICAL
**File:** `resources/views/vendor/laravel-file-viewer/previewFileImage.blade.php`

External CDN URLs:
```php
<script src="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.1/viewer.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/viewerjs/1.11.1/viewer.min.css" />
```

**Status:** Cloudflare CDN

---

### 3. **Audio Player (Plyr)** ❌ CRITICAL
**File:** `resources/views/vendor/laravel-file-viewer/previewFileAudio.blade.php`

External CDN URLs:
```php
<script src="https://cdn.plyr.io/3.7.2/plyr.js"></script>
<link rel="stylesheet" href="https://cdn.plyr.io/3.7.2/plyr.css" />
```

**Status:** Plyr CDN

---

### 4. **Document Viewer (DOCX Preview)** ⚠️ MODERATE
**File:** `resources/views/vendor/laravel-file-viewer/previewFileDocxjs.blade.php`

External CDN URLs:
```php
<script src="https://unpkg.com/jszip/dist/jszip.min.js"></script>
```

**Status:** npm CDN

---

### 5. **Icon & Font Libraries** ❌ CRITICAL
**File:** `resources/views/vendor/laravel-file-viewer/layouts/blank_app_no_logo.blade.php`

External CDN URLs:
```php
<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js"></script>
```

**Issues:**
- Google Fonts (privacy concern - Google tracking)
- Bootstrap CDN (jsdelivr)
- Font Awesome CDN (Cloudflare)
- Ionic Icons CDN
- Popper.js CDN

---

### 6. **Tailwind CSS** ⚠️ MODERATE
**Files:** 
- `resources/views/mobile/upload-expired.blade.php`
- `resources/views/mobile/upload-error.blade.php`
- `resources/views/mobile/profile-upload.blade.php`
- `resources/views/mobile/disbursement-upload.blade.php`
- `resources/views/mobile/scholarship-record-upload.blade.php`
- `resources/views/mobile/disbursement-upload.backup.blade.php`
- `resources/views/maintenance/show.blade.php`

External CDN URLs:
```php
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

**Status:** CDN Tailwind (should use locally compiled Tailwind from npm build)

---

## Security & Privacy Concerns

### Data Leakage Risks:
1. **IP Address logging** - CDNs can track user IPs
2. **Request patterns** - External services see which assets are requested
3. **User identification** - Tracking pixels via Google Fonts, Font Awesome
4. **Timing attacks** - Asset load patterns could reveal information

### Privacy Impact:
- Google Fonts tracking users
- Cloudflare logging all requests
- CDN analytics collecting user data

---

## Recommendations

### Priority: HIGH
All external CDN assets should be downloaded and served locally:

1. **Video.js** → Download and place in `/public/vendor/videojs/`
2. **ViewerJS** → Already structured for local use, verify it's in `/public/vendor/`
3. **Plyr** → Download and place in `/public/vendor/plyr/`
4. **Font Awesome** → Use local npm package (already in `package.json`?)
5. **Bootstrap** → Use local npm package if needed
6. **Google Fonts** → Download font files locally, self-host
7. **Ionic Icons** → Download and place locally
8. **Popper.js** → Use local npm package
9. **Tailwind CSS** → Already handled via npm build, remove CDN references

---

## Implementation Steps

### Step 1: Check Current node_modules
```bash
npm list | grep -E "video|viewer|plyr|bootstrap|font-awesome|tailwind|popper"
```

### Step 2: Install Missing Packages
```bash
npm install video.js
npm install viewer
npm install plyr
npm install @fortawesome/fontawesome-free
npm install popper.js
```

### Step 3: Download External Libraries
- Video.js themes → `/public/vendor/videojs-themes/`
- Google Fonts → `/public/fonts/` (convert woff2)
- Ionic Icons → `/public/vendor/ionicons/`

### Step 4: Update Blade Templates
Replace all `https://` CDN URLs with `{{ asset('path/to/local/file') }}`

### Step 5: Build & Test
```bash
npm run build
php artisan optimize
```

---

## Files Requiring Updates

| File | Type | Count | Priority |
|------|------|-------|----------|
| previewFileVideo.blade.php | Video/themes | 6 URLs | HIGH |
| previewFileImage.blade.php | Image viewer | 2 URLs | HIGH |
| previewFileAudio.blade.php | Audio player | 2 URLs | HIGH |
| blank_app_no_logo.blade.php | Fonts/icons/UI | 8 URLs | HIGH |
| Mobile views (6 files) | Tailwind/Icons | 2 URLs each | MEDIUM |
| previewFileDocxjs.blade.php | JS libraries | 1 URL | MEDIUM |
| maintenance/show.blade.php | Tailwind | 1 URL | MEDIUM |

**Total CDN URLs:** 30+

---

## Security Compliance

- ✅ No API keys exposed
- ❌ External CDN dependencies (CRITICAL)
- ❌ Privacy risk from tracking services
- ❌ No offline capability
- ⚠️ Performance degradation if CDNs are down

---

## Next Steps

1. Create task to download and locally host all CDN assets
2. Remove all external `https://` URLs from Blade templates
3. Update asset references to use `{{ asset() }}`
4. Run full test suite after migration
5. Document all local asset locations in architecture guide


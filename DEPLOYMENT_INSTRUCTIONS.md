# Quick Deployment Guide - OBR Chrome Fix

## What Was Fixed
Production OBR PDF generation was failing with:
```
Could not find Chrome (ver. 140.0.7339.82)
```

The issue was hardcoded Chrome paths in configuration that didn't exist on production servers.

## What You Need To Do

### ✅ Option 1: Do Nothing (Recommended)
The application now auto-detects Chrome. Most servers will work without any configuration:
- Browsershot will automatically find Chrome if installed
- Browsershot can auto-install Chromium if needed
- **No configuration required**

### ✅ Option 2: Set Chrome Path (Optional)
If you have Chrome installed at a non-standard location, set in your `.env`:
```env
CHROME_PATH=/usr/bin/google-chrome
# or on Windows:
CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe
```

### ✅ Option 3: Install Chrome (Optional)
If Chrome isn't installed and you want to use system Chrome instead of bundled Chromium:

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install google-chrome-stable
```

**CentOS/RHEL:**
```bash
sudo yum install google-chrome-stable
```

**Windows:**
Download from https://www.google.com/chrome/

## Deployment Steps

1. **Pull latest code** with the fixes:
   - `config/scholarship.php` (updated)
   - `app/Http/Controllers/ReportController.php` (updated)
   - `app/Traits/ManagesChromeForPdf.php` (new)

2. **No database migrations needed** - configuration only

3. **No cache clearing needed** - but recommended:
   ```bash
   php artisan config:cache
   php artisan view:cache
   ```

4. **Test OBR generation:**
   - Navigate to a voucher record
   - Try generating OBR PDF
   - If successful, you're done!

## Verification Checklist

- [ ] OBR PDF generation works
- [ ] Waiting list reports generate PDFs
- [ ] Scholarship reports generate PDFs
- [ ] Export selected applicants generates PDFs
- [ ] No Chrome version errors in logs

## If Issues Persist

1. **Check Chrome installation:**
   ```bash
   # Linux
   which google-chrome
   # Windows
   where chrome
   ```

2. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

3. **Enable debug mode temporarily:**
   ```env
   APP_DEBUG=true
   ```

4. **Test Chrome path:**
   ```bash
   php artisan tinker
   > exec('chrome --version')
   > exec('chromium --version')
   ```

## Files Changed

| File | Changes |
|------|---------|
| `config/scholarship.php` | Removed version-specific paths, added auto-detection |
| `app/Http/Controllers/ReportController.php` | Enhanced Chrome path detection logic |
| `app/Traits/ManagesChromeForPdf.php` | NEW - Reusable Chrome management |
| `documentation/OBR_CHROME_VERSION_FIX.md` | Full technical documentation |
| `.env.chrome.example` | Example environment configuration |

## Rollback (if needed)

If something breaks, the changes are fully reversible. The old logic threw exceptions; the new logic gracefully falls back to auto-detection. No breaking changes were introduced.

---

**Questions?** See [documentation/OBR_CHROME_VERSION_FIX.md](documentation/OBR_CHROME_VERSION_FIX.md) for detailed technical information.

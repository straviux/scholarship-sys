# Quick Deployment Guide - OBR Chrome Fix

## What Was Fixed
Production OBR PDF generation was failing with:
```
Could not find Chrome (ver. 140.0.7339.82)
```

The issue was hardcoded Chrome paths in configuration that didn't exist on production servers, especially when running under service accounts (Apache, System).

## ⚠️ CRITICAL FOR PRODUCTION WINDOWS SERVERS

If your application runs under Apache or System service account:

### You MUST install Google Chrome

1. **Download and Install:**
   - Visit https://www.google.com/chrome/
   - Download the installer
   - Run as Administrator to install to `C:\Program Files\Google\Chrome`

   OR use Chocolatey:
   ```powershell
   choco install googlechrome
   ```

2. **Configure in .env:**
   ```env
   CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe
   ```

3. **Automated Setup (Recommended):**
   ```powershell
   # Run as Administrator from project directory
   .\setup-chrome-production.bat
   ```

4. **Restart Apache:**
   ```powershell
   net stop Apache2.4
   net start Apache2.4
   ```

## What You Need To Do

### ✅ Option 1: Use Automated Setup Script (EASIEST)
```powershell
# Run as Administrator
cd C:\Apache24\htdocs\scholarship-sys
.\setup-chrome-production.bat
```

This script will:
- Verify Chrome installation
- Set file permissions
- Update .env file
- Install Node dependencies
- Restart services

### ✅ Option 2: Manual Setup
1. Install Chrome from https://www.google.com/chrome/
2. Add to `.env`:
   ```env
   CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe
   BROWSERSHOT_TIMEOUT=120
   BROWSERSHOT_HEADLESS=true
   ```
3. Restart Apache service
4. Test OBR generation

### ✅ Option 3: Puppeteer Setup (Advanced)
If you prefer using Puppeteer's bundled Chrome:
```bash
cd C:\Apache24\htdocs\scholarship-sys
npm install puppeteer
npx puppeteer browsers install chrome
```

Then set in `.env`:
```env
PUPPETEER_CACHE_DIR=C:\ProgramData\.cache\puppeteer
```

## Deployment Steps

1. **Pull latest code** with the fixes:
   - `config/scholarship.php` (updated)
   - `app/Http/Controllers/ReportController.php` (updated)
   - `app/Traits/ManagesChromeForPdf.php` (new)
   - `setup-chrome-production.bat` (new script)

2. **Run setup script:**
   ```powershell
   .\setup-chrome-production.bat
   ```

3. **Clear application cache:**
   ```bash
   php artisan config:cache
   php artisan view:cache
   ```

4. **Restart Apache:**
   ```powershell
   net stop Apache2.4
   net start Apache2.4
   ```

5. **Test OBR generation:**
   - Navigate to a voucher record
   - Try generating OBR PDF
   - If successful, you're done!

## Verification Checklist

- [ ] Chrome is installed in `C:\Program Files\Google\Chrome\Application\`
- [ ] CHROME_PATH is set in `.env`
- [ ] Service account has permissions on Chrome directory
- [ ] Apache service has been restarted
- [ ] OBR PDF generation works without errors
- [ ] Check `storage/logs/laravel.log` for any errors

## Troubleshooting

### Chrome not found error
```
Error: Could not find Chrome
```
**Solution:** Install Chrome manually and set `CHROME_PATH` in `.env`

### Permission denied error
```
Access denied to Chrome executable
```
**Solution:** Run setup script as Administrator to grant permissions

### Service account issue
```
Cache path: C:\Windows\system32\config\systemprofile\.cache\puppeteer
```
**Solution:** This is expected for service accounts. Setting explicit `CHROME_PATH` fixes this.

### Still having issues?
1. Check `storage/logs/laravel.log`
2. Enable debug mode: `APP_DEBUG=true`
3. Review: [documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md](documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md)

## Files Changed

| File | Purpose |
|------|---------|
| `config/scholarship.php` | Updated fallback paths for service accounts |
| `app/Http/Controllers/ReportController.php` | Enhanced Chrome path detection |
| `app/Traits/ManagesChromeForPdf.php` | NEW - Chrome management trait |
| `setup-chrome-production.bat` | NEW - Automated setup script |
| `documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md` | NEW - Detailed service account guide |

## Environment Variables Reference

```env
# REQUIRED for production service accounts
CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe

# OPTIONAL - Advanced configuration
BROWSERSHOT_TIMEOUT=120          # PDF generation timeout (seconds)
BROWSERSHOT_HEADLESS=true        # Run Chrome headless (recommended)
PUPPETEER_CACHE_DIR=...          # Custom Puppeteer cache location

# For Puppeteer-based Chrome
NODE_PATH=/usr/bin/node
NPM_PATH=/usr/local/bin/npm
```

---

**Questions?** See [documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md](documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md) for detailed technical information.

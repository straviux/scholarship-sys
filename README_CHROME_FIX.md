# Production Chrome Issue - Complete Resolution

## The Real Problem

Your production server is running Apache under a **service account** (SYSTEM, Apache user, etc.), not a regular Windows user account. When Browsershot tries to use Puppeteer's bundled Chrome, it looks for it in:

```
C:\Windows\system32\config\systemprofile\.cache\puppeteer
```

This cache directory doesn't have the required Chrome installation, hence the error.

## The Solution

### ✅ IMMEDIATE FIX (5 minutes)

Run this script as Administrator on your Windows production server:

```powershell
# Navigate to project directory
cd C:\Apache24\htdocs\scholarship-sys

# Run automated setup
.\setup-chrome-production.bat
```

**This script will:**
1. ✓ Check if Chrome is installed
2. ✓ Grant file permissions to service account
3. ✓ Update .env with CHROME_PATH
4. ✓ Install Node dependencies
5. ✓ Verify everything works

Then restart Apache:
```powershell
net stop Apache2.4
net start Apache2.4
```

### ✅ OR Manual Fix (if script doesn't work)

1. **Install Chrome:**
   ```powershell
   choco install googlechrome
   ```
   Or download from https://www.google.com/chrome/

2. **Edit .env file** and add:
   ```env
   CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe
   BROWSERSHOT_TIMEOUT=120
   BROWSERSHOT_HEADLESS=true
   ```

3. **Grant permissions:**
   ```powershell
   icacls "C:\Program Files\Google\Chrome" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)RX" /T
   ```

4. **Restart Apache:**
   ```powershell
   net stop Apache2.4
   net start Apache2.4
   ```

## Files Updated

### Code Changes
- ✅ `config/scholarship.php` - Updated fallback paths for service accounts
- ✅ `app/Http/Controllers/ReportController.php` - Fixed Chrome path detection
- ✅ `app/Traits/ManagesChromeForPdf.php` - NEW trait for Chrome management

### New Files
- ✅ `setup-chrome-production.bat` - Automated setup script
- ✅ `documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md` - Detailed guide
- ✅ `TROUBLESHOOTING_CHROME.md` - Command reference

## Why This Works

The new configuration:
1. **Doesn't rely on user home directories** - Works with service accounts
2. **Checks Program Files first** - Chrome installed normally is found
3. **Falls back gracefully** - Multiple fallback paths
4. **Allows explicit override** - `CHROME_PATH` environment variable
5. **Supports auto-detection** - Browsershot can still handle it

## Verification

After setup, verify it works:

```bash
# Test OBR PDF generation
curl -H "Authorization: Bearer TOKEN" \
  http://yourserver/api/vouchers/{id}/obr-pdf

# Or in Laravel Tinker
php artisan tinker
> \App\Models\Voucher::first()->id  # Get a voucher ID
> config('scholarship.browsershot.chrome_path')
> // Should show: C:\Program Files\Google\Chrome\Application\chrome.exe
```

## Key Points

**For Service Account Issues:**
- ✅ Install Chrome in `C:\Program Files` (accessible to all accounts)
- ✅ Use explicit `CHROME_PATH` in .env
- ✅ Don't rely on Puppeteer's bundled Chrome for service accounts
- ✅ Always grant proper NTFS permissions

**Configuration Priority:**
1. Environment variable `CHROME_PATH` (highest priority)
2. Configured fallback paths with glob search
3. Browsershot auto-detection (lowest priority)

**For Different Service Accounts:**
```powershell
# Apache user (if different from SYSTEM)
icacls "C:\Program Files\Google\Chrome" /grant "APACHE_USER:(OI)(CI)RX" /T

# Apache service
net session  # Shows current user
icacls "C:\Program Files\Google\Chrome" /grant "NT AUTHORITY\NETWORK SERVICE:(OI)(CI)RX" /T
```

## Success Checklist

- [ ] Chrome installed at `C:\Program Files\Google\Chrome\Application\chrome.exe`
- [ ] File permissions granted to service account
- [ ] `CHROME_PATH` set in .env
- [ ] Apache service restarted
- [ ] OBR PDF generation works
- [ ] No errors in `storage/logs/laravel.log`

## Documentation

1. **For Setup:** `DEPLOYMENT_INSTRUCTIONS.md`
2. **For Details:** `documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md`
3. **For Troubleshooting:** `TROUBLESHOOTING_CHROME.md`
4. **For Code Changes:** `CHROME_FIX_SUMMARY.md`

---

**Need Help?**
- Run: `setup-chrome-production.bat`
- Check: `TROUBLESHOOTING_CHROME.md`
- Review: `documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md`

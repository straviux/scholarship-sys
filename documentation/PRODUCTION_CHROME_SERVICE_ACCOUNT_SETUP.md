# Production Chrome Setup - Service Account Configuration

## Issue
When running under Apache/System service account, Browsershot fails to find Chrome:
```
Error: Could not find Chrome (ver. 140.0.7339.82)
Cache path: C:\Windows\system32\config\systemprofile\.cache\puppeteer
```

This happens because:
1. The application runs under a service account (SYSTEM, Apache, etc.)
2. Service accounts don't have the same home directory as regular users
3. Puppeteer's bundled Chrome isn't available in the service account's cache

## Solutions

### ✅ Solution 1: Install Google Chrome (RECOMMENDED)

This is the most reliable solution for production:

**On Windows Server:**

1. Download Chrome installer:
   ```powershell
   # Option A: Using chocolatey
   choco install googlechrome
   
   # Option B: Manual download and install
   # Download from https://www.google.com/chrome/
   # Run as Administrator
   ```

2. Verify installation:
   ```powershell
   # Check if Chrome is installed
   Test-Path "C:\Program Files\Google\Chrome\Application\chrome.exe"
   
   # Test Chrome execution
   & "C:\Program Files\Google\Chrome\Application\chrome.exe" --version
   ```

3. Grant permissions (if needed):
   ```powershell
   # Ensure Apache/service account can read Chrome directory
   icacls "C:\Program Files\Google\Chrome" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)F"
   ```

4. Set in `.env`:
   ```env
   CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe
   ```

5. Restart Apache:
   ```powershell
   net stop Apache2.4
   net start Apache2.4
   ```

---

### ✅ Solution 2: Install Puppeteer Chrome for Service Account

If you prefer using Puppeteer's bundled Chrome:

1. **Install using shared cache location:**
   ```bash
   # Navigate to your project
   cd C:\Apache24\htdocs\scholarship-sys
   
   # Install Puppeteer browsers to shared location
   npm install puppeteer
   npx puppeteer browsers install chrome
   ```

2. **Set cache path for service account:**
   Create or edit `.env`:
   ```env
   PUPPETEER_CACHE_DIR=C:\ProgramData\.cache\puppeteer
   CHROME_PATH=C:\ProgramData\.cache\puppeteer\chrome\win64-140.0.7339.82\chrome-win64\chrome.exe
   ```

3. **Grant permissions:**
   ```powershell
   icacls "C:\ProgramData\.cache\puppeteer" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)F"
   ```

---

### ✅ Solution 3: Use Relative Path to Local Chrome

If Chrome is installed in node_modules:

1. **Install Puppeteer locally:**
   ```bash
   cd C:\Apache24\htdocs\scholarship-sys
   npm install puppeteer
   ```

2. **Set path:**
   ```env
   CHROME_PATH=C:\Apache24\htdocs\scholarship-sys\node_modules\puppeteer\.cache\chrome\win64-140.0.7339.82\chrome-win64\chrome.exe
   ```

---

## Quick Fix Checklist

- [ ] Chrome is installed in `C:\Program Files\Google\Chrome\Application\chrome.exe`
- [ ] Service account (SYSTEM/Apache) has read permissions on Chrome directory
- [ ] `CHROME_PATH` environment variable is set correctly in `.env`
- [ ] Apache service has been restarted after `.env` changes
- [ ] Application logs show no Chrome-related errors

---

## Verification Commands

**Test in PowerShell (as Administrator):**

```powershell
# 1. Check Chrome installation
Test-Path "C:\Program Files\Google\Chrome\Application\chrome.exe"

# 2. Test Chrome execution
& "C:\Program Files\Google\Chrome\Application\chrome.exe" --version

# 3. Check environment variable
$env:CHROME_PATH

# 4. Check Apache service account
whoami  # Should show: NT AUTHORITY\SYSTEM or similar

# 5. Verify .env file
Get-Content "C:\Apache24\htdocs\scholarship-sys\.env" | Select-String "CHROME_PATH"
```

**Test in Laravel (Tinker):**

```php
php artisan tinker
> config('scholarship.browsershot.chrome_path')
> config('scholarship.browsershot.fallback_paths')

// Try generating a test PDF
> \Spatie\Browsershot\Browsershot::html('<h1>Test</h1>')->pdf()
```

---

## .env Configuration Examples

**Example 1: Using System Chrome**
```env
CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe
BROWSERSHOT_TIMEOUT=120
BROWSERSHOT_HEADLESS=true
```

**Example 2: Using Puppeteer Cache**
```env
CHROME_PATH=C:\ProgramData\.cache\puppeteer\chrome\win64-140.0.7339.82\chrome-win64\chrome.exe
PUPPETEER_CACHE_DIR=C:\ProgramData\.cache\puppeteer
BROWSERSHOT_TIMEOUT=120
BROWSERSHOT_HEADLESS=true
```

**Example 3: Auto-detection (requires Chrome installed)**
```env
# Leave CHROME_PATH empty - fallback paths will be checked automatically
BROWSERSHOT_TIMEOUT=120
BROWSERSHOT_HEADLESS=true
```

---

## File Permissions Issues

If you get permission errors, ensure the service account can access Chrome:

```powershell
# For Chrome installation
icacls "C:\Program Files\Google\Chrome" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)RX"

# For cache directories
icacls "C:\ProgramData\.cache\puppeteer" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)F"

# For node_modules
icacls "C:\Apache24\htdocs\scholarship-sys\node_modules" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)RX"
```

---

## Apache Service Configuration

If Apache service account is different:

```powershell
# Check which account Apache runs under
Get-Service Apache2.4 | Format-List *

# Grant permissions to Apache account
icacls "C:\Program Files\Google\Chrome" /grant "APACHE_ACCOUNT_NAME:(OI)(CI)RX"
```

---

## Troubleshooting Tips

1. **Chrome version mismatch:**
   - Keep Chrome updated automatically (default on Windows)
   - Or use explicit version in CHROME_PATH

2. **Network/Firewall issues:**
   - Chrome needs access to localhost for communication
   - Check Windows Firewall rules

3. **Temporary directory access:**
   - Chrome needs to write temporary files
   - Ensure `%TEMP%` directory is accessible to service account
   - The error shows: `C:\Windows\TEMP\`

4. **Node.js/Puppeteer issues:**
   - Ensure Node.js is properly installed
   - Check `npm list puppeteer` in your project directory

---

## Still Having Issues?

1. **Check logs:**
   ```powershell
   tail -f "C:\Apache24\htdocs\scholarship-sys\storage\logs\laravel.log"
   ```

2. **Enable debug mode temporarily:**
   ```env
   APP_DEBUG=true
   LOG_LEVEL=debug
   ```

3. **Test command directly:**
   ```powershell
   # Find the exact command being executed and run it manually
   # Copy-paste from logs and execute
   ```

4. **Contact Support:**
   - Provide the full error message from logs
   - Confirm Chrome installation and version
   - Share output of: `whoami`, `echo %TEMP%`, `npm list puppeteer`

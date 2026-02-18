# Quick Troubleshooting Commands - Chrome PDF Generation

## Problem: "Could not find Chrome (ver. 140.0.7339.82)"

### Run This First (Windows PowerShell - As Administrator)

```powershell
# 1. Check if Chrome is installed
Test-Path "C:\Program Files\Google\Chrome\Application\chrome.exe"
Test-Path "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe"

# 2. Test Chrome version
& "C:\Program Files\Google\Chrome\Application\chrome.exe" --version

# 3. Check current .env configuration
Get-Content ".\.env" | Select-String "CHROME_PATH"

# 4. Check which account Apache runs under
Get-Service Apache2.4 | Format-List *

# 5. Check permissions on Chrome directory
icacls "C:\Program Files\Google\Chrome\Application"

# 6. Display environment variable
$env:CHROME_PATH
```

### Run Setup Script

```powershell
# Run as Administrator from project root
.\setup-chrome-production.bat
```

This will automatically:
- Detect Chrome installation
- Fix file permissions
- Update .env file
- Verify configuration

### Manual Fix

```powershell
# 1. Install Chrome
choco install googlechrome

# 2. Grant permissions
icacls "C:\Program Files\Google\Chrome" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)RX" /T

# 3. Update .env
Add-Content ".\.env" @"

# Chrome Configuration
CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe
BROWSERSHOT_TIMEOUT=120
BROWSERSHOT_HEADLESS=true
"@

# 4. Restart Apache
net stop Apache2.4
net start Apache2.4

# 5. Clear cache
php artisan config:cache
php artisan view:cache
```

## Problem: Permission Denied

```powershell
# Fix permissions for service account
$CHROME_DIR = "C:\Program Files\Google\Chrome\Application"
icacls "$CHROME_DIR" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)RX" /T
icacls "$CHROME_DIR" /grant "BUILTIN\Administrators:(OI)(CI)F" /T
```

## Problem: Still Getting Errors After Setup

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Enable debug mode temporarily
# In .env:
APP_DEBUG=true
LOG_LEVEL=debug

# Test OBR PDF generation in Tinker
php artisan tinker
> $voucher = \App\Models\Voucher::first()
> $html = view('vouchers.obr', ['voucher' => $voucher])->render()
> $pdf = \Spatie\Browsershot\Browsershot::html($html)->pdf()
> // If this works without errors, Chrome is properly configured
```

## Verify Chrome Works

```powershell
# Test 1: Chrome can be executed
$chromePath = "C:\Program Files\Google\Chrome\Application\chrome.exe"
& "$chromePath" --version

# Test 2: Chrome can launch headless (what Browsershot uses)
& "$chromePath" --headless --dump-dom "about:blank" > $null
if ($?) {
    Write-Host "✓ Chrome is working correctly"
} else {
    Write-Host "✗ Chrome failed to launch"
}
```

## Check Environment Variables

```powershell
# Show all relevant environment variables
Write-Host "CHROME_PATH: $env:CHROME_PATH"
Write-Host "PUPPETEER_CACHE_DIR: $env:PUPPETEER_CACHE_DIR"
Write-Host "TEMP: $env:TEMP"
Write-Host "NODE_PATH: $env:NODE_PATH"
```

## Restart Services

```powershell
# Restart Apache
net stop Apache2.4
net start Apache2.4

# Or using PowerShell
Restart-Service -Name Apache2.4 -Force
```

## Check Node/NPM

```powershell
# Verify Node is installed
node --version
npm --version

# Check Puppeteer installation
npm list puppeteer

# Reinstall Puppeteer if needed
npm install puppeteer
npx puppeteer browsers install chrome
```

## View Application Logs

```powershell
# Real-time log viewing
# In a PowerShell window as Administrator:
Get-Content "storage\logs\laravel.log" -Tail 50 -Wait

# Or use Linux tail equivalent:
# tail -f storage/logs/laravel.log
```

## Reset Everything

```powershell
# Complete reset (when in doubt)

# 1. Stop Apache
net stop Apache2.4

# 2. Clear Laravel cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 3. Install Chrome fresh
choco uninstall googlechrome -y
choco install googlechrome

# 4. Fix permissions
icacls "C:\Program Files\Google\Chrome" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)RX" /T

# 5. Update .env with explicit path
# Edit .env and set:
# CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe

# 6. Rebuild cache
php artisan config:cache

# 7. Start Apache
net start Apache2.4
```

## Success Indicators

✓ OBR PDF generates without errors
✓ No "Could not find Chrome" in logs
✓ No permission errors
✓ PDF appears in browser
✓ `storage/logs/laravel.log` is clean (no Chrome warnings)

---

**Still stuck?** Check the full guide:
[documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md](documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md)

# OBR PDF Generation - Chrome Version Error Fix

## Problem
Production error when generating OBR reports:
```
Could not find Chrome (ver. 140.0.7339.82)
```

This error occurred because the configuration file had a hardcoded Chrome executable path pointing to a specific version that may not exist on the production server.

**File affected:** `config/scholarship.php`

## Root Cause
The configuration was too rigid:
```php
'chrome_path' => env('CHROME_PATH', 'C:\\Users\\Administrator\\.cache\\puppeteer\\chrome\\win64-140.0.7339.82\\chrome-win64\\chrome.exe'),
```

This approach has several problems:
1. **Version-specific paths** - The path includes `win64-140.0.7339.82`, which may not exist on production
2. **User-specific paths** - Hardcoded `Administrator` username won't work on different servers
3. **No fallback mechanism** - If the primary path fails, it throws an exception

## Solution

### 1. Updated Configuration (`config/scholarship.php`)
- Removed hardcoded version numbers and user-specific paths
- Set `CHROME_PATH` to `null` by default, allowing Browsershot auto-detection
- Added flexible fallback paths with version-agnostic directories
- Added timeout and headless mode configurations

```php
'browsershot' => [
    'chrome_path' => env('CHROME_PATH', null),
    'fallback_paths' => [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Users\\' . get_current_user() . '\\.cache\\puppeteer\\chrome',
        base_path('node_modules/puppeteer/.cache/chrome'),
    ],
]
```

### 2. Enhanced Chrome Path Detection (`ReportController.php`)
- Updated `getChromePath()` method to return `null` instead of throwing exceptions
- Added glob pattern matching to find Chrome in directories
- Implemented conditional Chrome path setting in Browsershot initialization

**Key changes:**
```php
$browsershot = Browsershot::html($html);
$chromePath = $this->getChromePath();
if ($chromePath) {
    $browsershot->setChromePath($chromePath);
}
$browsershot->showBackground()...
```

### 3. New Trait for Code Reuse (`app/Traits/ManagesChromeForPdf.php`)
- Created `ManagesChromeForPdf` trait for centralizing Chrome path logic
- Can be used by all controllers that generate PDFs

## How It Works Now

1. **Environment Variable** - First checks `CHROME_PATH` environment variable
2. **Configuration** - Uses configured path if it exists
3. **Fallback Paths** - Searches standard Chrome installation locations
4. **Auto-detection** - Falls back to Browsershot's built-in auto-detection
5. **Browsershot Handles Missing Chrome** - If Chrome isn't found, Browsershot will:
   - Install Puppeteer's bundled Chrome automatically
   - Use system-installed Chrome if available
   - Provide clear error messages if Chrome cannot be resolved

## Deployment Steps

### For Production Servers

**Option 1: Auto-detection (Recommended)**
- No configuration needed
- Browsershot will automatically find or install Chrome
- Works with both Google Chrome and Chromium

**Option 2: Explicit Chrome Path**
Set in your `.env` file:
```env
CHROME_PATH=/usr/bin/google-chrome
# or on Windows:
CHROME_PATH=C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe
```

**Option 3: Install Google Chrome**
```bash
# Ubuntu/Debian
sudo apt-get install google-chrome-stable

# Windows - download from https://www.google.com/chrome/
```

## Verification

To verify the fix works:

1. **Check Config:**
   ```bash
   php artisan config:show scholarship.browsershot
   ```

2. **Test OBR Generation:**
   ```php
   // In tinker or test
   $voucher = Voucher::first();
   $html = view('vouchers.obr', ['voucher' => $voucher])->render();
   $pdf = Browsershot::html($html)->pdf();
   ```

3. **Check Logs:**
   - Look for any Chrome-related errors in `storage/logs/laravel.log`

## Files Modified

1. **config/scholarship.php** - Updated browsershot configuration
2. **app/Http/Controllers/ReportController.php** - Enhanced Chrome path detection
3. **app/Traits/ManagesChromeForPdf.php** (new) - Reusable Chrome management trait

## Testing Scenarios

- ✅ Chrome installed in `Program Files`
- ✅ Chrome installed in user cache via Puppeteer
- ✅ Chrome path set via environment variable
- ✅ Browsershot auto-detection with bundled Chrome
- ✅ All three PDF generation routes:
  - Waiting List reports
  - Scholarship reports
  - Export selected applicants

## Troubleshooting

If OBR generation still fails:

1. **Check Chrome Installation:**
   ```bash
   # Windows
   where chrome
   # or
   ls "C:\Program Files\Google\Chrome\Application\"
   
   # Linux
   which google-chrome
   ```

2. **Set Explicit Path in .env:**
   ```env
   CHROME_PATH=/full/path/to/chrome
   ```

3. **Enable Debug Logging:**
   ```env
   APP_DEBUG=true
   LOG_LEVEL=debug
   ```

4. **Check Permissions:**
   - Ensure Laravel can execute Chrome binary
   - Check file permissions on Chrome executable

## Related Configuration

Additional Browsershot settings available in `config/scholarship.php`:
- `timeout` - Maximum time to wait for PDF generation (default: 120 seconds)
- `headless` - Run Chrome in headless mode (default: true)
- `node_path` - Node.js executable path
- `npm_path` - NPM executable path

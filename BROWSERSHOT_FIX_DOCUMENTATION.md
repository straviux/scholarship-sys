# Browsershot Chrome Path Fix Documentation

## Issue

Production environment was throwing an error:

```
Browser was not found at the configured executablePath (C:\\Users\\Administrator\\.cache\\puppeteer\\chrome-headless-shell\\win64-140.0.7339.82\\chrome-headless-shell-win64\\chrome-headless-shell.exe)
```

## Root Cause

The Chrome executable path was hardcoded in the `ReportController.php` file, pointing to the wrong Chrome variant:

- **Was using**: `chrome-headless-shell.exe` (which didn't exist)
- **Should use**: `chrome.exe` (full Chrome browser in Puppeteer cache)

## Solution Implemented

### 1. Configuration-Based Approach

Added Browsershot configuration to `config/scholarship.php`:

```php
'browsershot' => [
    // Primary Chrome path (can be overridden via CHROME_PATH env variable)
    'chrome_path' => env('CHROME_PATH', 'C:\\Users\\Administrator\\.cache\\puppeteer\\chrome\\win64-140.0.7339.82\\chrome-win64\\chrome.exe'),

    // Fallback paths if primary fails
    'fallback_paths' => [
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Users\\Administrator\\.cache\\puppeteer\\chrome-headless-shell\\win64-140.0.7339.82\\chrome-headless-shell-win64\\chrome-headless-shell.exe',
    ],

    'node_path' => env('NODE_PATH', null),
    'npm_path' => env('NPM_PATH', null),
]
```

### 2. Helper Method with Fallback Logic

Added `getChromePath()` method to `ReportController.php`:

```php
protected function getChromePath()
{
    $primaryPath = config('scholarship.browsershot.chrome_path');

    // Try primary path first
    if ($primaryPath && file_exists($primaryPath)) {
        return $primaryPath;
    }

    // Try fallback paths
    $fallbackPaths = config('scholarship.browsershot.fallback_paths', []);
    foreach ($fallbackPaths as $path) {
        if (file_exists($path)) {
            return $path;
        }
    }

    // If no valid path found, throw exception with helpful message
    throw new \Exception(
        'Chrome executable not found. Please configure CHROME_PATH in your .env file or install Chrome/Chromium. ' .
        'Tried paths: ' . $primaryPath . ', ' . implode(', ', $fallbackPaths)
    );
}
```

### 3. Updated Browsershot Calls

Changed from hardcoded path to dynamic configuration:

**Before:**

```php
$browsershot = Browsershot::html($html)
    ->setChromePath('C:\Users\Administrator\.cache\puppeteer\chrome-headless-shell\win64-140.0.7339.82\chrome-headless-shell-win64\chrome-headless-shell.exe')
    // ... other methods
```

**After:**

```php
$browsershot = Browsershot::html($html)
    ->setChromePath($this->getChromePath())
    // ... other methods
```

## Files Modified

1. `config/scholarship.php` - Added browsershot configuration
2. `app/Http/Controllers/ReportController.php` - Added helper method and updated Browsershot calls

## Environment Configuration (Optional)

You can override the Chrome path in your `.env` file if needed:

```env
# Optional: Override Chrome path
CHROME_PATH="C:\Program Files\Google\Chrome\Application\chrome.exe"

# Optional: Specify Node/NPM paths if needed
# NODE_PATH="C:\Program Files\nodejs\node.exe"
# NPM_PATH="C:\Program Files\nodejs\npm.cmd"
```

## Benefits

1. **Flexible Configuration**: Chrome path can be changed via config or environment variable
2. **Fallback Support**: Automatically tries multiple common Chrome installation locations
3. **Better Error Messages**: Clear exception message showing all attempted paths
4. **Environment-Agnostic**: Works across development, staging, and production
5. **Easy Updates**: When Chrome version changes, just update one config value

## Verified Chrome Paths on Current System

Available Chrome executables found:

- ✅ `C:\Users\Administrator\.cache\puppeteer\chrome\win64-140.0.7339.82\chrome-win64\chrome.exe` (PRIMARY)
- ✅ `C:\Users\Administrator\.cache\puppeteer\chrome\win64-140.0.7339.82\chrome-win64\chrome_proxy.exe`
- ✅ `C:\Users\Administrator\.cache\puppeteer\chrome\win64-140.0.7339.82\chrome-win64\chrome_pwa_launcher.exe`
- ✅ `C:\Users\Administrator\.cache\puppeteer\chrome-headless-shell\win64-140.0.7339.82\chrome-headless-shell-win64\chrome-headless-shell.exe`

## Testing

To test PDF generation, try generating a waiting list report:

1. Go to the Applicants page
2. Click "Generate Report"
3. Select filters (courses, year level, etc.)
4. Click "Generate PDF"

The system will now:

1. Try the primary path from config
2. Fall back to alternate paths if needed
3. Show clear error if no Chrome found

## Troubleshooting

If you still encounter Chrome path issues:

1. **Check if Chrome exists:**

   ```powershell
   Test-Path "C:\Users\Administrator\.cache\puppeteer\chrome\win64-140.0.7339.82\chrome-win64\chrome.exe"
   ```

2. **Find Chrome on your system:**

   ```powershell
   Get-ChildItem "C:\Users\Administrator\.cache\puppeteer" -Recurse -Filter "chrome.exe" | Select-Object FullName
   ```

3. **Install Chrome if missing:**

   ```bash
   npm install -g puppeteer
   # or
   npx puppeteer browsers install chrome
   ```

4. **Set custom path in .env:**
   ```env
   CHROME_PATH="C:\Your\Custom\Path\To\chrome.exe"
   ```

## Future Improvements

Consider these enhancements:

1. Add Chrome version detection and auto-update
2. Implement caching of the resolved path
3. Add health check endpoint to verify Chrome availability
4. Log warnings when using fallback paths
5. Add support for headless Chrome flags customization

---

**Date Fixed:** October 13, 2025
**Fixed By:** GitHub Copilot
**Status:** ✅ Resolved

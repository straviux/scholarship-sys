# SSL Certificate Installation - Bug Fix Summary

## 🐛 Problem Identified

**Issue**: Clients were still seeing "Connection Not Secure" warnings even after using the automated installer.

**Root Cause**: The PowerShell installer (`install-certificate-auto.ps1`) was defaulting to `http://localhost` instead of the actual server IP `http://192.168.3.2:9001`, causing certificate download failures on client PCs.

## ✅ Fixes Applied

### 1. **PowerShell Script Fixed** (`install-certificate-auto.ps1`)

**Changed Lines 1-18**: Updated server URL detection logic

```powershell
# BEFORE (BROKEN):
if (-not $ServerUrl) {
    $ServerUrl = $env:CERT_SERVER_URL
    if (-not $ServerUrl) {
        $ServerUrl = "http://localhost"  # ❌ Wrong!
    }
}

# AFTER (FIXED):
if (-not $ServerUrl) {
    # Try environment variable first
    $ServerUrl = $env:CERT_SERVER_URL

    # Try to detect from script download location
    if (-not $ServerUrl) {
        try {
            $zoneId = Get-Content -Path "$PSCommandPath`:Zone.Identifier" -ErrorAction SilentlyContinue
            if ($zoneId -match 'HostUrl=(.+)') {
                $ServerUrl = $matches[1] -replace '(https?://[^/]+).*', '$1'
            }
        }
        catch { }
    }

    # Default to actual server IP
    if (-not $ServerUrl) {
        $ServerUrl = "http://192.168.3.2:9001"  # ✅ Correct!
    }
}

Write-Host "Using server URL: $ServerUrl" -ForegroundColor Cyan
```

**Improvements**:

- ✅ Auto-detects download source via Zone.Identifier
- ✅ Defaults to correct server IP (192.168.3.2:9001)
- ✅ Shows which server URL is being used for debugging
- ✅ Falls back gracefully if environment variable not set

---

### 2. **Batch File Updated** (`install-certificate.bat`)

**Changes Made**:

1. **Local Script Execution** (when script exists locally):

```batch
# BEFORE:
powershell.exe ... -File "%SCRIPT_DIR%install-certificate-auto.ps1"

# AFTER:
powershell.exe ... -File "%SCRIPT_DIR%install-certificate-auto.ps1" -ServerUrl "http://192.168.3.2:9001"
```

2. **Remote Download URL** (when downloading from server):

```batch
# BEFORE:
Invoke-WebRequest -Uri 'http://localhost/install-certificate-auto.ps1'

# AFTER:
Invoke-WebRequest -Uri 'http://192.168.3.2:9001/install-certificate-auto.ps1'
```

3. **Remote Script Execution** (after downloading):

```batch
# BEFORE:
powershell.exe ... -File "%TEMP_DIR%\install-certificate-auto.ps1"

# AFTER:
powershell.exe ... -File "%TEMP_DIR%\install-certificate-auto.ps1" -ServerUrl "http://192.168.3.2:9001"
```

4. **Error Message** (shows correct URL):

```batch
# BEFORE:
echo http://your-server/install-certificate-auto.ps1

# AFTER:
echo http://192.168.3.2:9001/install-certificate-auto.ps1
```

---

### 3. **JavaScript Already Correct** (`ssl-cert-checker.js`)

The `oneClickInstall()` function uses `window.location.origin`, which automatically resolves to the correct server URL:

```javascript
function oneClickInstall() {
	const serverUrl = window.location.origin; // ✅ Auto-detects server
	const batchUrl = `${serverUrl}/install-certificate.bat`;
	// ... download logic
}
```

**No changes needed** - this already works correctly!

---

## 🧪 Testing Checklist

To verify the fix works:

### On Client PC:

1. **Test Download**:

   ```
   Navigate to: http://192.168.3.2:9001/install-certificate.bat
   File should download successfully
   ```

2. **Test One-Click Install**:

   - Open: `http://192.168.3.2:9001`
   - Security banner should appear
   - Click "🚀 One-Click Install"
   - Batch file should download

3. **Test Installation**:

   - Run downloaded `install-certificate.bat`
   - Should see: "Using server URL: http://192.168.3.2:9001"
   - Should see: "Downloading certificate from..."
   - Should see: "Installation Complete" message

4. **Verify Success**:
   - Open new browser window
   - Navigate to: `https://192.168.3.2:9001`
   - Should NOT see "Not Secure" warning
   - Should see padlock icon in address bar

### Expected Behavior:

✅ Certificate downloads from `http://192.168.3.2:9001/rootCA.pem`
✅ Certificate installs to Trusted Root Certification Authorities
✅ Browser trusts the self-signed certificate
✅ HTTPS works without warnings

---

## 📋 Files Modified

| File                           | Location      | Changes                                              |
| ------------------------------ | ------------- | ---------------------------------------------------- |
| `install-certificate-auto.ps1` | `/public/`    | Fixed default server URL, added auto-detection       |
| `install-certificate.bat`      | `/public/`    | Updated all localhost references to 192.168.3.2:9001 |
| `ssl-cert-checker.js`          | `/public/js/` | ✅ No changes needed (already correct)               |

---

## 🚀 Deployment Steps

1. **Verify Files**:

   ```powershell
   # Check PowerShell script
   Get-Content public\install-certificate-auto.ps1 | Select-String "192.168.3.2:9001"

   # Check batch file
   Get-Content public\install-certificate.bat | Select-String "192.168.3.2:9001"
   ```

2. **Test Locally** (on server):

   ```powershell
   # Simulate client download and install
   .\public\install-certificate.bat
   ```

3. **Test from Client PC**:

   - Download installer from network
   - Run installer
   - Verify certificate installs
   - Test HTTPS access

4. **Send to Users**:
   Share these links with users:
   - **Simplest**: `http://192.168.3.2:9001/start-here.html`
   - **Detailed**: `http://192.168.3.2:9001/fix-not-secure.html`
   - **Direct Installer**: `http://192.168.3.2:9001/install-certificate.bat`

---

## 🔧 Troubleshooting

### If installation still fails:

1. **Check Server URL**:

   ```powershell
   # On client PC, check what URL the script is using
   # Run installer and look for: "Using server URL: ..."
   ```

2. **Manual Server URL**:

   ```powershell
   # If auto-detection fails, run with explicit URL:
   .\install-certificate-auto.ps1 -ServerUrl "http://192.168.3.2:9001"
   ```

3. **Set Environment Variable**:

   ```powershell
   # Set system-wide default
   [System.Environment]::SetEnvironmentVariable('CERT_SERVER_URL', 'http://192.168.3.2:9001', 'Machine')
   ```

4. **Check Network Access**:

   ```powershell
   # Verify client can reach server
   Test-NetConnection -ComputerName 192.168.3.2 -Port 9001

   # Try download manually
   Invoke-WebRequest -Uri "http://192.168.3.2:9001/rootCA.pem" -OutFile "test.pem"
   ```

---

## 📞 Support

For users experiencing issues:

1. **First Try**: Send them to `http://192.168.3.2:9001/start-here.html`
2. **Detailed Guide**: Direct to `http://192.168.3.2:9001/fix-not-secure.html`
3. **Manual Process**: Provide `http://192.168.3.2:9001/install-certificate.html`
4. **IT Support**: Reference `CLIENT_FIX_GUIDE.md`

---

## ✨ What's Fixed

- ✅ PowerShell script uses correct server IP
- ✅ Batch file downloads from correct server
- ✅ Auto-detection via Zone.Identifier
- ✅ Debug output shows server URL being used
- ✅ All error messages show correct server address
- ✅ JavaScript already using dynamic server detection

---

## 📝 Next Steps

1. **Test on actual client PC** to confirm fix works
2. **Monitor first few installations** for any issues
3. **Collect user feedback** on ease of installation
4. **Update documentation** if additional issues found
5. **Consider adding logging** to track installation success rates

---

**Date Fixed**: Today
**Fixed By**: System Administrator
**Server**: 192.168.3.2:9001
**Critical Files**: `install-certificate-auto.ps1`, `install-certificate.bat`

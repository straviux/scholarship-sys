# SSL Certificate Auto-Installation System

This system automatically detects when users are accessing the application without a valid SSL certificate and guides them through the installation process.

## 📁 Files Created

1. **`public/install-certificate.html`** - User-friendly web interface for certificate installation
2. **`public/install-certificate.ps1`** - PowerShell script for automated installation
3. **`public/js/ssl-cert-checker.js`** - JavaScript client-side detection script
4. **`public/rootCA.pem`** - Your root CA certificate (already exists)

## 🚀 Setup Instructions

### 1. Include the Detection Script in Your Layout

Add the following to your main layout file (e.g., `resources/views/layouts/app.blade.php`):

```html
<!-- Before closing </body> tag -->
<script src="{{ asset('js/ssl-cert-checker.js') }}"></script>
```

Or if using Vite in your `resources/js/app.js`:

```javascript
// Import the SSL checker
import '../../../public/js/ssl-cert-checker.js';
```

### 2. Configure the Detection Script (Optional)

You can customize the behavior by modifying the config in `public/js/ssl-cert-checker.js`:

```javascript
const config = {
	certificateUrl: '/rootCA.pem',
	installGuideUrl: '/install-certificate.html',
	checkOnLoad: true, // Check when page loads
	showBanner: true, // Show warning banner
	autoRedirect: false, // Auto-redirect to HTTPS (not recommended)
	checkInterval: null, // Periodic check in milliseconds (e.g., 60000)
};
```

## 📖 Usage Methods

### Method 1: Web Interface (Recommended for End Users)

Users can visit: `http://your-server/install-certificate.html`

This page provides:

- ✅ Step-by-step installation instructions
- 📥 Download button for the certificate
- ✅ Installation verification
- ⚡ PowerShell alternative method

### Method 2: PowerShell Script (Recommended for IT/Admin)

```powershell
# Download and run the script
Invoke-WebRequest -Uri "http://your-server/install-certificate.ps1" -OutFile "$env:TEMP\install-cert.ps1"
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
& "$env:TEMP\install-cert.ps1"
```

Or if the script is already downloaded:

```powershell
# Run as Administrator
.\install-certificate.ps1
```

### Method 3: Direct PowerShell Command

After downloading the certificate:

```powershell
Import-Certificate -FilePath "$env:USERPROFILE\Downloads\rootCA.pem" -CertStoreLocation Cert:\CurrentUser\Root
```

### Method 4: Manual Installation (Windows)

1. Download `rootCA.pem` from `http://your-server/rootCA.pem`
2. Double-click the downloaded file
3. Click **"Install Certificate..."**
4. Select **"Current User"** → Click **"Next"**
5. Choose **"Place all certificates in the following store"**
6. Click **"Browse"** → Select **"Trusted Root Certification Authorities"**
7. Click **"Next"** → **"Finish"**
8. Click **"Yes"** on the security warning
9. Restart your browser completely

## 🎯 How It Works

### Automatic Detection

The `ssl-cert-checker.js` script automatically:

1. **Detects HTTP connections** on local networks (localhost, 192.168.x.x, 10.x.x.x)
2. **Shows a warning banner** at the top of the page
3. **Provides quick access** to the installation guide
4. **Persists during session** to avoid annoying users repeatedly

### Warning Banner Example

```
🔓 Unsecured Connection Detected
Install the security certificate to access the system safely
[📥 Install Certificate] [Remind Later] [×]
```

## 🔧 Integration Examples

### Laravel Blade Template

```blade
<!DOCTYPE html>
<html>
<head>
    <title>Scholarship System</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        @yield('content')
    </div>

    <!-- SSL Certificate Checker -->
    <script src="{{ asset('js/ssl-cert-checker.js') }}"></script>
</body>
</html>
```

### Vue.js App

```javascript
// In your main app component or layout
import { onMounted } from 'vue';

onMounted(() => {
	// The SSL checker loads automatically via the included script
	// You can also manually trigger checks:
	if (window.SSLCertChecker) {
		window.SSLCertChecker.check();
	}
});
```

### Manual Trigger

```javascript
// Show banner manually
window.SSLCertChecker.showBanner();

// Show modal manually
window.SSLCertChecker.showModal();

// Check certificate status
if (!window.SSLCertChecker.isSecure()) {
	console.log('Connection is not secure');
}

// Redirect to HTTPS
window.SSLCertChecker.redirectToHttps();
```

## 📋 Deployment Checklist

- [ ] Verify `rootCA.pem` is in the `public/` folder
- [ ] Include `ssl-cert-checker.js` in your main layout
- [ ] Test accessing via HTTP on a client PC
- [ ] Verify the warning banner appears
- [ ] Test the installation guide page
- [ ] Test the PowerShell script
- [ ] Document the installation URL for your users

## 🌐 User Communication

### Email Template

```
Subject: Action Required: Install Security Certificate

Dear [User],

To ensure secure access to the Scholarship System, please install our security certificate.

Installation Link: http://[your-server]/install-certificate.html

The installation takes less than 5 minutes and only needs to be done once.

If you need assistance, please contact IT support.

Best regards,
[Your Name]
```

### Quick Reference Card

```
Scholarship System - Security Certificate Installation

Quick Link: http://[your-server]/install-certificate.html

Steps:
1. Click the link above
2. Download the certificate
3. Double-click to install
4. Follow the wizard
5. Restart your browser

Need Help? Contact: [support-email]
```

## 🛠️ Troubleshooting

### Certificate doesn't install

**Solution**: Run PowerShell as Administrator and use the automated script.

### Browser still shows "Not Secure"

**Solution**:

1. Restart the browser **completely** (close all windows)
2. Clear browser cache
3. Verify certificate is in `Trusted Root Certification Authorities`:
   - Run: `certmgr.msc`
   - Navigate to: Trusted Root Certification Authorities → Certificates
   - Look for your certificate

### Banner doesn't appear

**Solution**:

1. Check browser console for errors
2. Verify `ssl-cert-checker.js` is loaded
3. Ensure accessing via HTTP (not HTTPS)
4. Clear session storage: `sessionStorage.clear()`

### PowerShell script blocked

**Solution**:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\install-certificate.ps1
```

## 🔒 Security Notes

- ⚠️ Only distribute this certificate to trusted users within your organization
- ⚠️ Keep the private key (`rootCA-key.pem`) secure and never distribute it
- ⚠️ Consider certificate expiration dates and plan for renewal
- ⚠️ Monitor certificate usage and revoke if necessary

## 📊 Analytics (Optional)

Track certificate installations by adding to `install-certificate.html`:

```javascript
// After successful download
fetch('/api/track-cert-download', {
	method: 'POST',
	headers: { 'Content-Type': 'application/json' },
	body: JSON.stringify({
		timestamp: new Date().toISOString(),
		userAgent: navigator.userAgent,
	}),
});
```

## 🎓 Support

For additional support or questions, refer to:

- Installation guide: `http://[your-server]/install-certificate.html`
- IT support: [your-support-contact]
- Documentation: This README file

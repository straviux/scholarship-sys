# Certificate Auto-Installer for Scholarship System
# Run this script as Administrator to automatically install the rootCA certificate

param(
    [string]$CertificateUrl = "http://localhost/rootCA.pem",
    [switch]$Silent = $false
)

$ErrorActionPreference = "Stop"

Write-Host "================================================" -ForegroundColor Cyan
Write-Host "   Scholarship System Certificate Installer    " -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host ""

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "⚠️  WARNING: Not running as Administrator" -ForegroundColor Yellow
    Write-Host "Some operations may require elevated privileges." -ForegroundColor Yellow
    Write-Host ""
    
    if (-not $Silent) {
        $response = Read-Host "Do you want to restart as Administrator? (Y/N)"
        if ($response -eq 'Y' -or $response -eq 'y') {
            Start-Process powershell.exe -ArgumentList "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`"" -Verb RunAs
            exit
        }
    }
}

# Function to check if certificate is already installed
function Test-CertificateInstalled {
    param([string]$Thumbprint)
    
    $cert = Get-ChildItem -Path Cert:\CurrentUser\Root | Where-Object { $_.Thumbprint -eq $Thumbprint }
    return $null -ne $cert
}

# Function to download certificate
function Get-Certificate {
    param([string]$Url, [string]$OutputPath)
    
    Write-Host "📥 Downloading certificate from: $Url" -ForegroundColor Cyan
    
    try {
        # Try with local file path first (if accessing from public folder)
        $scriptDir = Split-Path -Parent $PSCommandPath
        $localCertPath = Join-Path $scriptDir "rootCA.pem"
        
        if (Test-Path $localCertPath) {
            Write-Host "✅ Found local certificate: $localCertPath" -ForegroundColor Green
            Copy-Item $localCertPath $OutputPath -Force
            return $true
        }
        
        # Download from URL
        Invoke-WebRequest -Uri $Url -OutFile $OutputPath -UseBasicParsing
        Write-Host "✅ Certificate downloaded successfully" -ForegroundColor Green
        return $true
    }
    catch {
        Write-Host "❌ Failed to download certificate: $_" -ForegroundColor Red
        return $false
    }
}

# Function to install certificate
function Install-RootCertificate {
    param([string]$CertPath)
    
    Write-Host "📦 Installing certificate..." -ForegroundColor Cyan
    
    try {
        # Import certificate to Trusted Root Certification Authorities
        $cert = Import-Certificate -FilePath $CertPath -CertStoreLocation Cert:\CurrentUser\Root -ErrorAction Stop
        
        Write-Host "✅ Certificate installed successfully!" -ForegroundColor Green
        Write-Host "   Subject: $($cert.Subject)" -ForegroundColor Gray
        Write-Host "   Thumbprint: $($cert.Thumbprint)" -ForegroundColor Gray
        Write-Host "   Valid Until: $($cert.NotAfter)" -ForegroundColor Gray
        
        return $true
    }
    catch {
        Write-Host "❌ Failed to install certificate: $_" -ForegroundColor Red
        return $false
    }
}

# Main execution
try {
    # Create temp directory for certificate
    $tempDir = Join-Path $env:TEMP "ScholarshipCert"
    if (-not (Test-Path $tempDir)) {
        New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
    }
    
    $certPath = Join-Path $tempDir "rootCA.pem"
    
    # Download certificate
    if (-not (Get-Certificate -Url $CertificateUrl -OutputPath $certPath)) {
        Write-Host ""
        Write-Host "Please ensure the certificate file is accessible at: $CertificateUrl" -ForegroundColor Yellow
        Write-Host "Or place rootCA.pem in the same folder as this script." -ForegroundColor Yellow
        exit 1
    }
    
    # Check if already installed
    $certContent = Get-Content $certPath -Raw
    $certObj = [System.Security.Cryptography.X509Certificates.X509Certificate2]::new($certPath)
    
    if (Test-CertificateInstalled -Thumbprint $certObj.Thumbprint) {
        Write-Host ""
        Write-Host "ℹ️  Certificate is already installed!" -ForegroundColor Yellow
        Write-Host "   Thumbprint: $($certObj.Thumbprint)" -ForegroundColor Gray
        
        if (-not $Silent) {
            $response = Read-Host "Do you want to reinstall? (Y/N)"
            if ($response -ne 'Y' -and $response -ne 'y') {
                Write-Host "Installation cancelled." -ForegroundColor Gray
                exit 0
            }
        }
    }
    
    # Install certificate
    if (Install-RootCertificate -CertPath $certPath) {
        Write-Host ""
        Write-Host "================================================" -ForegroundColor Green
        Write-Host "   Installation Complete!                      " -ForegroundColor Green
        Write-Host "================================================" -ForegroundColor Green
        Write-Host ""
        Write-Host "⚠️  IMPORTANT: Please restart your browser completely" -ForegroundColor Yellow
        Write-Host "   (close all browser windows) for changes to take effect." -ForegroundColor Yellow
        Write-Host ""
        
        # Clean up
        Remove-Item $certPath -Force -ErrorAction SilentlyContinue
    }
    else {
        Write-Host ""
        Write-Host "Installation failed. Please try manual installation:" -ForegroundColor Red
        Write-Host "1. Download rootCA.pem" -ForegroundColor Gray
        Write-Host "2. Double-click the file" -ForegroundColor Gray
        Write-Host "3. Click 'Install Certificate...'" -ForegroundColor Gray
        Write-Host "4. Select 'Current User' -> Next" -ForegroundColor Gray
        Write-Host "5. Select 'Trusted Root Certification Authorities'" -ForegroundColor Gray
        Write-Host "6. Click Next -> Finish" -ForegroundColor Gray
        exit 1
    }
}
catch {
    Write-Host ""
    Write-Host "❌ An error occurred: $_" -ForegroundColor Red
    exit 1
}
finally {
    if (-not $Silent) {
        Write-Host ""
        Read-Host "Press Enter to exit"
    }
}

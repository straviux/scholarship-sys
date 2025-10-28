# ONE-CLICK Certificate Auto-Installer for Scholarship System
# This script automatically downloads and installs the certificate with minimal user interaction

param(
    [string]$ServerUrl = "",
    [switch]$Silent = $false
)

# If no server URL provided, try to detect it
if (-not $ServerUrl) {
    # Try environment variable first
    $ServerUrl = $env:CERT_SERVER_URL
    
    # Try to detect from script location (Zone.Identifier alternate data stream)
    if (-not $ServerUrl) {
        try {
            $zoneId = Get-Content -Path "$PSCommandPath`:Zone.Identifier" -ErrorAction SilentlyContinue
            if ($zoneId -match 'HostUrl=(.+)') {
                $ServerUrl = $matches[1] -replace '(https?://[^/]+).*', '$1'
            }
        }
        catch { }
    }
    
    # Default to your server IP
    if (-not $ServerUrl) {
        $ServerUrl = "http://192.168.3.2:9001"
    }
}

# Ensure we have just the base URL
$ServerUrl = $ServerUrl -replace '(https?://[^/]+).*', '$1'

Write-Host "Using server URL: $ServerUrl" -ForegroundColor Cyan

$ErrorActionPreference = "Stop"

# Hide PowerShell window for truly silent operation
Add-Type -Name Window -Namespace Console -MemberDefinition '
[DllImport("Kernel32.dll")]
public static extern IntPtr GetConsoleWindow();
[DllImport("user32.dll")]
public static extern bool ShowWindow(IntPtr hWnd, Int32 nCmdShow);
'

function Hide-Console {
    $consolePtr = [Console.Window]::GetConsoleWindow()
    [Console.Window]::ShowWindow($consolePtr, 0) | Out-Null
}

function Show-Console {
    $consolePtr = [Console.Window]::GetConsoleWindow()
    [Console.Window]::ShowWindow($consolePtr, 5) | Out-Null
}

# Show progress notification using Windows Forms
Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

function Show-Notification {
    param(
        [string]$Title,
        [string]$Message,
        [string]$Icon = "Info"
    )
    
    $notify = New-Object System.Windows.Forms.NotifyIcon
    $notify.Icon = [System.Drawing.SystemIcons]::Information
    $notify.BalloonTipTitle = $Title
    $notify.BalloonTipText = $Message
    $notify.Visible = $true
    $notify.ShowBalloonTip(5000)
    
    Start-Sleep -Seconds 2
    $notify.Dispose()
}

function Show-ProgressForm {
    param([string]$Message)
    
    $form = New-Object System.Windows.Forms.Form
    $form.Text = "Installing Security Certificate"
    $form.Size = New-Object System.Drawing.Size(400, 150)
    $form.StartPosition = "CenterScreen"
    $form.FormBorderStyle = "FixedDialog"
    $form.MaximizeBox = $false
    $form.MinimizeBox = $false
    $form.TopMost = $true
    
    $label = New-Object System.Windows.Forms.Label
    $label.Location = New-Object System.Drawing.Point(20, 20)
    $label.Size = New-Object System.Drawing.Size(360, 40)
    $label.Text = $Message
    $label.Font = New-Object System.Drawing.Font("Segoe UI", 10)
    $form.Controls.Add($label)
    
    $progressBar = New-Object System.Windows.Forms.ProgressBar
    $progressBar.Location = New-Object System.Drawing.Point(20, 70)
    $progressBar.Size = New-Object System.Drawing.Size(360, 30)
    $progressBar.Style = "Marquee"
    $progressBar.MarqueeAnimationSpeed = 30
    $form.Controls.Add($progressBar)
    
    $form.Show()
    $form.Refresh()
    
    return $form
}

# Check if running as Administrator
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    # Restart as Administrator automatically
    $arguments = "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`" -ServerUrl `"$ServerUrl`""
    if ($Silent) { $arguments += " -Silent" }
    
    Start-Process powershell.exe -ArgumentList $arguments -Verb RunAs
    exit
}

# Hide console for clean experience
if ($Silent) {
    Hide-Console
}

try {
    # Show progress
    $progressForm = Show-ProgressForm -Message "Downloading security certificate..."
    
    # Create temp directory
    $tempDir = Join-Path $env:TEMP "ScholarshipCert_$(Get-Date -Format 'yyyyMMddHHmmss')"
    New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
    
    $certPath = Join-Path $tempDir "rootCA.pem"
    
    # Try multiple download methods
    $downloaded = $false
    
    # Method 1: Try local script directory
    $scriptDir = Split-Path -Parent $PSCommandPath
    $localCertPath = Join-Path $scriptDir "rootCA.pem"
    if (Test-Path $localCertPath) {
        Copy-Item $localCertPath $certPath -Force
        $downloaded = $true
    }
    
    # Method 2: Try downloading from server
    if (-not $downloaded) {
        $certUrl = "$ServerUrl/rootCA.pem"
        try {
            Invoke-WebRequest -Uri $certUrl -OutFile $certPath -UseBasicParsing -TimeoutSec 10
            $downloaded = $true
        }
        catch {
            # Try without SSL verification
            try {
                [System.Net.ServicePointManager]::ServerCertificateValidationCallback = { $true }
                $webClient = New-Object System.Net.WebClient
                $webClient.DownloadFile($certUrl, $certPath)
                $downloaded = $true
            }
            catch { }
        }
    }
    
    if (-not $downloaded) {
        throw "Could not download certificate. Please ensure the server is accessible."
    }
    
    # Update progress
    $progressForm.Controls[0].Text = "Installing certificate to Trusted Root..."
    $progressForm.Refresh()
    
    # Check if already installed
    $certObj = [System.Security.Cryptography.X509Certificates.X509Certificate2]::new($certPath)
    $existingCert = Get-ChildItem -Path Cert:\CurrentUser\Root | Where-Object { $_.Thumbprint -eq $certObj.Thumbprint }
    
    if ($existingCert) {
        # Already installed - remove and reinstall to ensure it's current
        Remove-Item -Path "Cert:\CurrentUser\Root\$($existingCert.Thumbprint)" -Force -ErrorAction SilentlyContinue
    }
    
    # Install certificate
    $cert = Import-Certificate -FilePath $certPath -CertStoreLocation Cert:\CurrentUser\Root -ErrorAction Stop
    
    # Update progress
    $progressForm.Controls[0].Text = "Certificate installed successfully!"
    $progressForm.Refresh()
    Start-Sleep -Seconds 1
    
    # Close progress form
    $progressForm.Close()
    $progressForm.Dispose()
    
    # Clean up
    Remove-Item $certPath -Force -ErrorAction SilentlyContinue
    Remove-Item $tempDir -Force -Recurse -ErrorAction SilentlyContinue
    
    # Show success notification
    Show-Notification -Title "Installation Complete" -Message "Security certificate installed successfully! Please restart your browser."
    
    # Show success message
    $result = [System.Windows.Forms.MessageBox]::Show(
        "Security certificate installed successfully!`n`nIMPORTANT: Please close ALL browser windows and reopen them for the changes to take effect.`n`nWould you like to visit the system now?",
        "Installation Complete",
        [System.Windows.Forms.MessageBoxButtons]::YesNo,
        [System.Windows.Forms.MessageBoxIcon]::Information
    )
    
    if ($result -eq [System.Windows.Forms.DialogResult]::Yes) {
        # Open browser to HTTPS version
        $httpsUrl = $ServerUrl -replace '^http:', 'https:'
        Start-Process $httpsUrl
    }
    
    exit 0
}
catch {
    if ($progressForm) {
        $progressForm.Close()
        $progressForm.Dispose()
    }
    
    # Show error
    [System.Windows.Forms.MessageBox]::Show(
        "Installation failed: $($_.Exception.Message)`n`nPlease try manual installation or contact IT support.",
        "Installation Error",
        [System.Windows.Forms.MessageBoxButtons]::OK,
        [System.Windows.Forms.MessageBoxIcon]::Error
    )
    
    exit 1
}
finally {
    if ($Silent) {
        Show-Console
    }
}

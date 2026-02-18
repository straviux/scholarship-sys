# Download Google Fonts using alternative method
$sourceUrl = "https://fonts.gstatic.com/s/sourcesanspro/v21/"
$nunitoUrl = "https://fonts.gstatic.com/s/nunito/v26/"
$baseDir = "c:\Users\Administrator\Desktop\SCHOLARSHIP PROGRAM\scholarship-sys\public\vendor\cdn\google-fonts"

# Source Sans Pro files
$sourceFonts = @(
    "6xKQ9ZakAECM26y1EBvDvvP9kNJhD9Ld5ghAVMnKg_g.woff2",  # 300
    "6xKQ9ZakAECM26y1EBvDvvP9kMLBD9Ld5ghAVMnKg_g.woff2",  # 400
    "6xKQ9ZakAECM26y1EBvDvvP9kOLmD9Ld5ghAVMnKg_g.woff2",  # 600
    "6xKQ9ZakAECM26y1EBvDvvP9kPvnD9Ld5ghAVMnKg_g.woff2"   # 700
)

$weights = @("300", "400", "600", "700")

Write-Host "Attempting to download Source Sans Pro fonts..."
for ($i = 0; $i -lt $sourceFonts.Length; $i++) {
    $url = $sourceUrl + $sourceFonts[$i]
    $outFile = "$baseDir\source-sans-pro\$($weights[$i]).woff2"
    Write-Host "  $($weights[$i]): $url"
    try {
        $webClient = New-Object System.Net.WebClient
        $webClient.DownloadFile($url, $outFile)
        Write-Host "    ✓ Downloaded"
    } catch {
        Write-Host "    ✗ Failed: $_"
    }
}

# Nunito files  
$nunitoFonts = @(
    "XRXV3I6Li01BKofINeaBTmNWcpJc-aIXglR7-pJ8-7kQ66H-K_sHUe-Oi7Yw.woff2",  # 400
    "XRXV3I6Li01BKofINeaBTmNWcpJc-aIXglR7-pJ8-xkQ66H-K_sHUe-Oi7Yw.woff2",  # 600
    "XRXV3I6Li01BKofINeaBTmNWcpJc-aIXglR7-pJ8-6kQ66H-K_sHUe-Oi7Yw.woff2"   # 700
)

$nWeights = @("400", "600", "700")

Write-Host "`nAttempting to download Nunito fonts..."
for ($i = 0; $i -lt $nunitoFonts.Length; $i++) {
    $url = $nunitoUrl + $nunitoFonts[$i]
    $outFile = "$baseDir\nunito\$($nWeights[$i]).woff2"
    Write-Host "  $($nWeights[$i]): $url"
    try {
        $webClient = New-Object System.Net.WebClient
        $webClient.DownloadFile($url, $outFile)
        Write-Host "    ✓ Downloaded"
    } catch {
        Write-Host "    ✗ Failed: $_"
    }
}

Write-Host "`nDownload attempt complete!"

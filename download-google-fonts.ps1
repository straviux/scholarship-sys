# Download Google Fonts locally
$baseUrl = "c:\Users\Administrator\Desktop\SCHOLARSHIP PROGRAM\scholarship-sys\public\vendor\cdn\google-fonts"

# Create font directories
New-Item -ItemType Directory -Force -Path "$baseUrl\source-sans-pro", "$baseUrl\nunito" | Out-Null

# Source Sans Pro fonts
$sourceGlyph = @(
    @{ url = "https://fonts.gstatic.com/s/sourcesanspro/v21/6xKQ9ZakAECM26y1EBvDvvP9kNJhD9Ld5ghAVMnKg_g.woff2"; file = "300" },
    @{ url = "https://fonts.gstatic.com/s/sourcesanspro/v21/6xKQ9ZakAECM26y1EBvDvvP9kMLBD9Ld5ghAVMnKg_g.woff2"; file = "400" },
    @{ url = "https://fonts.gstatic.com/s/sourcesanspro/v21/6xKQ9ZakAECM26y1EBvDvvP9kOLmD9Ld5ghAVMnKg_g.woff2"; file = "600" },
    @{ url = "https://fonts.gstatic.com/s/sourcesanspro/v21/6xKQ9ZakAECM26y1EBvDvvP9kPvnD9Ld5ghAVMnKg_g.woff2"; file = "700" }
)

Write-Host "Downloading Source Sans Pro fonts..."
foreach ($font in $sourceGlyph) {
    Write-Host "  Downloading Source Sans Pro $($font.file)"
    try {
        Invoke-WebRequest -Uri $font.url -OutFile "$baseUrl\source-sans-pro\$($font.file).woff2" -ErrorAction Stop
    }
    catch {
        Write-Host "    Warning: Could not download Source Sans Pro $($font.file)"
    }
}

# Nunito fonts
$nunito = @(
    @{ url = "https://fonts.gstatic.com/s/nunito/v26/XRXV3I6Li01BKofINeaBTmNWcpJc-aIXglR7-pJ8-7kQ66H-K_sHUe-Oi7Yw.woff2"; file = "400" },
    @{ url = "https://fonts.gstatic.com/s/nunito/v26/XRXV3I6Li01BKofINeaBTmNWcpJc-aIXglR7-pJ8-xkQ66H-K_sHUe-Oi7Yw.woff2"; file = "600" },
    @{ url = "https://fonts.gstatic.com/s/nunito/v26/XRXV3I6Li01BKofINeaBTmNWcpJc-aIXglR7-pJ8-6kQ66H-K_sHUe-Oi7Yw.woff2"; file = "700" }
)

Write-Host "`nDownloading Nunito fonts..."
foreach ($font in $nunito) {
    Write-Host "  Downloading Nunito $($font.file)"
    try {
        Invoke-WebRequest -Uri $font.url -OutFile "$baseUrl\nunito\$($font.file).woff2" -ErrorAction Stop
    }
    catch {
        Write-Host "    Warning: Could not download Nunito $($font.file)"
    }
}

Write-Host "`nFont download complete!"

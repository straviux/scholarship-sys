# Download CDN assets for localization
$baseUrl = "c:\Users\Administrator\Desktop\SCHOLARSHIP PROGRAM\scholarship-sys\public\vendor\cdn"

# Create output directories
$dirs = @(
    "$baseUrl\bootstrap\css",
    "$baseUrl\bootstrap\js",
    "$baseUrl\font-awesome\css",
    "$baseUrl\font-awesome\webfonts",
    "$baseUrl\ionicons\css",
    "$baseUrl\ionicons\fonts",
    "$baseUrl\popper",
    "$baseUrl\google-fonts"
)

foreach ($dir in $dirs) {
    if (!(Test-Path $dir)) {
        New-Item -ItemType Directory -Force -Path $dir | Out-Null
    }
}

Write-Host "Downloading Bootstrap CSS..."
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" -OutFile "$baseUrl\bootstrap\css\bootstrap.min.css"

Write-Host "Downloading Bootstrap JS..."
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" -OutFile "$baseUrl\bootstrap\js\bootstrap.min.js"

Write-Host "Downloading Popper.js..."
Invoke-WebRequest -Uri "https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" -OutFile "$baseUrl\popper\popper.min.js"

Write-Host "Downloading Font Awesome CSS..."
Invoke-WebRequest -Uri "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" -OutFile "$baseUrl\font-awesome\css\all.min.css"

Write-Host "Downloading Ionicons CSS..."
Invoke-WebRequest -Uri "https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" -OutFile "$baseUrl\ionicons\css\ionicons.min.css"

Write-Host "Note: Font files and Google Fonts require additional setup."
Write-Host "Download complete!"

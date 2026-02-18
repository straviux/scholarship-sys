# Download font files for CDN assets
$baseUrl = "c:\Users\Administrator\Desktop\SCHOLARSHIP PROGRAM\scholarship-sys\public\vendor\cdn"

# Font Awesome webfont URLs
$faFonts = @(
    @{
        url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-solid-900.woff2"
        file = "$baseUrl\font-awesome\webfonts\fa-solid-900.woff2"
    },
    @{
        url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-solid-900.woff"
        file = "$baseUrl\font-awesome\webfonts\fa-solid-900.woff"
    },
    @{
        url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-solid-900.ttf"
        file = "$baseUrl\font-awesome\webfonts\fa-solid-900.ttf"
    },
    @{
        url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-brands-400.woff2"
        file = "$baseUrl\font-awesome\webfonts\fa-brands-400.woff2"
    },
    @{
        url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-brands-400.woff"
        file = "$baseUrl\font-awesome\webfonts\fa-brands-400.woff"
    },
    @{
        url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-brands-400.ttf"
        file = "$baseUrl\font-awesome\webfonts\fa-brands-400.ttf"
    },
    @{
        url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-regular-400.woff2"
        file = "$baseUrl\font-awesome\webfonts\fa-regular-400.woff2"
    },
    @{
        url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-regular-400.woff"
        file = "$baseUrl\font-awesome\webfonts\fa-regular-400.woff"
    },
    @{
        url = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/webfonts/fa-regular-400.ttf"
        file = "$baseUrl\font-awesome\webfonts\fa-regular-400.ttf"
    }
)

Write-Host "Downloading Font Awesome webfonts..."
foreach ($font in $faFonts) {
    Write-Host "  Downloading $(Split-Path $font.file -Leaf)..."
    try {
        Invoke-WebRequest -Uri $font.url -OutFile $font.file -ErrorAction Stop
    } catch {
        Write-Host "    Warning: Could not download $(Split-Path $font.file -Leaf)"
    }
}

# Ionicons font URLs
$ionFonts = @(
    @{
        url = "https://code.ionicframework.com/ionicons/2.0.1/fonts/ionicons.woff2"
        file = "$baseUrl\ionicons\fonts\ionicons.woff2"
    },
    @{
        url = "https://code.ionicframework.com/ionicons/2.0.1/fonts/ionicons.woff"
        file = "$baseUrl\ionicons\fonts\ionicons.woff"
    },
    @{
        url = "https://code.ionicframework.com/ionicons/2.0.1/fonts/ionicons.ttf"
        file = "$baseUrl\ionicons\fonts\ionicons.ttf"
    }
)

Write-Host "`nDownloading Ionicons fonts..."
foreach ($font in $ionFonts) {
    Write-Host "  Downloading $(Split-Path $font.file -Leaf)..."
    try {
        Invoke-WebRequest -Uri $font.url -OutFile $font.file -ErrorAction Stop
    } catch {
        Write-Host "    Warning: Could not download $(Split-Path $font.file -Leaf)"
    }
}

Write-Host "`nFont download complete!"

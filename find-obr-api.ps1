# PowerShell Script to Help Find and Test OBR API Endpoint
# Usage: .\find-obr-api.ps1

param(
    [string]$Url = "https://tracking.pgpict.com/obr-tracking",
    [string]$ObrNo = "200-25-12-24188",
    [int]$Timeout = 10
)

Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "OBR Tracking API Discovery Tool" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host ""

# Common OBR API endpoint patterns to test
$apiPatterns = @(
    "https://tracking.pgpict.com/api/obr-tracking",
    "https://tracking.pgpict.com/api/obr",
    "https://tracking.pgpict.com/api/dashboard/obr",
    "https://tracking.pgpict.com/api/search/obr",
    "https://tracking.pgpict.com/api/v1/obr",
    "https://tracking.pgpict.com/obr/data",
    "https://tracking.pgpict.com/api/data/obr"
)

# Build test query
$query = @{
    type          = "GF"
    fiscal_year   = 2025
    sortField     = "obrDate"
    sortDirection = "desc"
    page          = 0
    pageSize      = 10
    obrNo         = $ObrNo
}

Write-Host "Attempting to find OBR API endpoint..." -ForegroundColor Yellow
Write-Host "Search OBR Number: $ObrNo" -ForegroundColor White
Write-Host ""

$foundEndpoints = @()

foreach ($pattern in $apiPatterns) {
    Write-Host "Testing: $pattern" -ForegroundColor Gray
    
    try {
        $response = Invoke-WebRequest -Uri $pattern `
            -Method Get `
            -Body $query `
            -TimeoutSec $Timeout `
            -ErrorAction Stop
        
        if ($response.StatusCode -eq 200) {
            Write-Host "  ✓ SUCCESS (HTTP 200)" -ForegroundColor Green
            Write-Host "    Content-Type: $($response.Headers['Content-Type'])" -ForegroundColor Green
            Write-Host "    Content Length: $($response.Content.Length) bytes" -ForegroundColor Green
            
            $foundEndpoints += @{
                Url        = $pattern
                StatusCode = 200
                Response   = $response.Content
            }
            
            # Try to parse as JSON
            try {
                $json = $response.Content | ConvertFrom-Json
                Write-Host "    JSON Response: YES" -ForegroundColor Green
                Write-Host "    Top-level keys: $(($json | Get-Member -MemberType NoteProperty | Select-Object -ExpandProperty Name) -join ', ')" -ForegroundColor Green
            }
            catch {
                Write-Host "    JSON Response: NO (HTML or other format)" -ForegroundColor Yellow
            }
        }
    }
    catch {
        $errorMsg = $_.Exception.Message
        if ($errorMsg -match "404") {
            Write-Host "  ✗ Not Found (HTTP 404)" -ForegroundColor Red
        }
        elseif ($errorMsg -match "403") {
            Write-Host "  ✗ Forbidden (HTTP 403)" -ForegroundColor Red
        }
        elseif ($errorMsg -match "timeout") {
            Write-Host "  ✗ Timeout" -ForegroundColor Red
        }
        else {
            Write-Host "  ✗ Error: $($errorMsg.Substring(0, [Math]::Min(60, $errorMsg.Length)))" -ForegroundColor Red
        }
    }
    
    Write-Host ""
}

Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan
Write-Host "RESULTS" -ForegroundColor Cyan
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan

if ($foundEndpoints.Count -gt 0) {
    Write-Host "✓ Found $($foundEndpoints.Count) working endpoint(s):" -ForegroundColor Green
    Write-Host ""
    
    foreach ($endpoint in $foundEndpoints) {
        Write-Host "Endpoint: $($endpoint.Url)" -ForegroundColor Green
        Write-Host "Status: HTTP $($endpoint.StatusCode)" -ForegroundColor White
        
        # Save response to file for inspection
        $filename = "obr-response-$(Get-Date -Format 'yyyyMMdd-HHmmss').json"
        $endpoint.Response | Out-File -FilePath $filename -Encoding UTF8
        Write-Host "Response saved to: $filename" -ForegroundColor White
        Write-Host ""
    }
}
else {
    Write-Host "✗ No working OBR API endpoints found among the common patterns." -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. Visit: https://tracking.pgpict.com/obr-tracking" -ForegroundColor Gray
    Write-Host "2. Open DevTools: F12" -ForegroundColor Gray
    Write-Host "3. Go to Network tab" -ForegroundColor Gray
    Write-Host "4. Search for an OBR number" -ForegroundColor Gray
    Write-Host "5. Look for XHR/Fetch requests" -ForegroundColor Gray
    Write-Host "6. Note the actual API endpoint URL" -ForegroundColor Gray
    Write-Host "7. Run this script with: .\find-obr-api.ps1 -Url 'https://tracking.pgpict.com/[REAL_ENDPOINT]'" -ForegroundColor Gray
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════" -ForegroundColor Cyan

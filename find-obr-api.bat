@echo off
REM OBR API Endpoint Discovery Script
REM Usage: find-obr-api.bat

setlocal enabledelayedexpansion

echo.
echo ====================================================================
echo OBR Tracking API Discovery Tool
echo ====================================================================
echo.

REM Test common API endpoint patterns
set "endpoints[0]=https://tracking.pgpict.com/api/obr-tracking"
set "endpoints[1]=https://tracking.pgpict.com/api/obr"
set "endpoints[2]=https://tracking.pgpict.com/api/dashboard/obr"
set "endpoints[3]=https://tracking.pgpict.com/api/search/obr"
set "endpoints[4]=https://tracking.pgpict.com/api/v1/obr"
set "endpoints[5]=https://tracking.pgpict.com/obr/data"

echo Testing common OBR API patterns...
echo.

for /L %%i in (0,1,5) do (
    echo Testing: !endpoints[%%i]!
    
    REM Test with curl
    curl -s -o nul -w "Status: %%{http_code}\n" "!endpoints[%%i]!?type=GF&fiscal_year=2025&pageSize=10&obrNo=200-25-12-24188"
    echo.
)

echo.
echo ====================================================================
echo INSTRUCTIONS
echo ====================================================================
echo.
echo To find the REAL API endpoint:
echo.
echo 1. Open: https://tracking.pgpict.com/obr-tracking
echo 2. Press F12 to open Developer Tools
echo 3. Click the "Network" tab
echo 4. Clear existing requests (trash icon)
echo 5. Enter OBR number: 200-25-12-24188
echo 6. Click "Search" button
echo 7. Look for XHR or Fetch requests
echo 8. Click on the request with data
echo 9. Copy the full Request URL
echo 10. That's your real API endpoint!
echo.
echo EXAMPLE:
echo   Request URL: https://tracking.pgpict.com/api/obr?...
echo.
echo Once found, test it with:
echo   curl "https://tracking.pgpict.com/[YOUR_API_ENDPOINT]?type=GF&fiscal_year=2025&..."
echo.

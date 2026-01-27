@echo off
REM Production Chrome Setup Script for Scholarship System
REM Run as Administrator

echo ============================================
echo Chrome Setup for Production
echo ============================================
echo.

REM Check if running as Administrator
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: This script must be run as Administrator
    echo Please right-click and select "Run as administrator"
    exit /b 1
)

setlocal enabledelayexpansion

REM Configuration
set CHROME_PATH=C:\Program Files\Google\Chrome\Application\chrome.exe
set CHROME_PATH_X86=C:\Program Files (x86)\Google\Chrome\Application\chrome.exe
set PUPPETEER_CACHE=C:\ProgramData\.cache\puppeteer
set PROJECT_DIR=C:\Apache24\htdocs\scholarship-sys
set ENV_FILE=%PROJECT_DIR%\.env

echo Step 1: Checking Chrome Installation...
echo.

if exist "%CHROME_PATH%" (
    echo ✓ Chrome found at: %CHROME_PATH%
    for /f "tokens=*" %%i in ('"%CHROME_PATH%" --version') do set CHROME_VERSION=%%i
    echo   Version: !CHROME_VERSION!
) else if exist "%CHROME_PATH_X86%" (
    echo ✓ Chrome found at: %CHROME_PATH_X86%
    for /f "tokens=*" %%i in ('"%CHROME_PATH_X86%" --version') do set CHROME_VERSION=%%i
    echo   Version: !CHROME_VERSION!
    set CHROME_PATH=%CHROME_PATH_X86%
) else (
    echo ✗ Chrome NOT found
    echo.
    echo To fix, download and install Chrome from:
    echo https://www.google.com/chrome/
    echo.
    echo Or install via Chocolatey:
    echo choco install googlechrome
    echo.
    exit /b 1
)

echo.
echo Step 2: Setting permissions for service account...
echo.

icacls "%CHROME_PATH:chrome.exe=Application%" /grant "NT AUTHORITY\SYSTEM:(OI)(CI)RX" /T >nul
echo ✓ Permissions granted to SYSTEM account

echo.
echo Step 3: Configuring .env file...
echo.

REM Create or update .env with CHROME_PATH
if exist "%ENV_FILE%" (
    echo ✓ .env file exists
    
    REM Check if CHROME_PATH already exists
    findstr /M "CHROME_PATH" "%ENV_FILE%" >nul
    if !errorLevel! equ 0 (
        echo ✓ CHROME_PATH already configured
    ) else (
        echo ✓ Adding CHROME_PATH to .env
        echo. >> "%ENV_FILE%"
        echo # Chrome Configuration >> "%ENV_FILE%"
        echo CHROME_PATH=%CHROME_PATH% >> "%ENV_FILE%"
    )
) else (
    echo ✗ .env file not found at: %ENV_FILE%
    echo Please copy .env.example to .env and try again
    exit /b 1
)

echo.
echo Step 4: Installing Node dependencies...
echo.

if exist "%PROJECT_DIR%\package.json" (
    cd "%PROJECT_DIR%"
    call npm install >nul 2>&1
    
    REM Try to install Puppeteer browsers
    call npx puppeteer browsers install chrome >nul 2>&1
    
    if !errorLevel! equ 0 (
        echo ✓ Node dependencies installed
    ) else (
        echo ⚠ Node dependencies may need manual installation
        echo   Run from project directory: npm install
    )
) else (
    echo ⚠ No package.json found, skipping npm install
)

echo.
echo Step 5: Verifying configuration...
echo.

REM Test if we can run chrome
"%CHROME_PATH%" --version >nul 2>&1
if !errorLevel! equ 0 (
    echo ✓ Chrome is executable
) else (
    echo ✗ Chrome cannot be executed
    echo   Check file permissions and try again
    exit /b 1
)

echo.
echo ============================================
echo Setup Complete!
echo ============================================
echo.
echo Configuration summary:
echo   Chrome Path: %CHROME_PATH%
echo   Project Dir: %PROJECT_DIR%
echo   .env File: %ENV_FILE%
echo.
echo Next steps:
echo   1. Verify CHROME_PATH in your .env file
echo   2. Restart Apache: net stop Apache2.4 ^&^& net start Apache2.4
echo   3. Test OBR PDF generation in your application
echo.
echo For troubleshooting, see:
echo   documentation/PRODUCTION_CHROME_SERVICE_ACCOUNT_SETUP.md
echo.
pause

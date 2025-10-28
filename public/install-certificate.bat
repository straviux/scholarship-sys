@echo off
REM One-Click Certificate Installer for Scholarship System
REM This batch file automatically runs the PowerShell installer with admin rights

TITLE Scholarship System - Certificate Installer
COLOR 0A

echo.
echo ========================================================
echo    SCHOLARSHIP SYSTEM - CERTIFICATE INSTALLER
echo ========================================================
echo.
echo  This will automatically install the security certificate.
echo  You will be prompted for Administrator permission.
echo.
echo  Press any key to continue or close this window to cancel.
echo ========================================================
echo.

pause >nul

echo.
echo [*] Starting installation...
echo.

REM Get the directory where this batch file is located
set "SCRIPT_DIR=%~dp0"

REM Check if PowerShell script exists in same directory
if exist "%SCRIPT_DIR%install-certificate-auto.ps1" (
    echo [*] Found installer script
    echo [*] Requesting administrator privileges...
    echo.
    
    REM Run PowerShell script with admin rights, passing server URL
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "Start-Process powershell.exe -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File \"%SCRIPT_DIR%install-certificate-auto.ps1\" -ServerUrl \"http://192.168.3.2:9001\"' -Verb RunAs"
    
    echo.
    echo [*] Installation launched!
    echo [*] Follow the on-screen prompts.
    echo.
) else (
    REM Try to download from server
    echo [*] Downloading installer...
    
    REM Create temp directory
    set "TEMP_DIR=%TEMP%\ScholarshipCert"
    if not exist "%TEMP_DIR%" mkdir "%TEMP_DIR%"
    
    REM Download PowerShell script from correct server
    powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "try { Invoke-WebRequest -Uri 'http://192.168.3.2:9001/install-certificate-auto.ps1' -OutFile '%TEMP_DIR%\install-certificate-auto.ps1' -UseBasicParsing; exit 0 } catch { exit 1 }"
    
    if %ERRORLEVEL% EQU 0 (
        echo [*] Download complete
        echo [*] Starting installation...
        powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "Start-Process powershell.exe -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File \"%TEMP_DIR%\install-certificate-auto.ps1\" -ServerUrl \"http://192.168.3.2:9001\"' -Verb RunAs"
    ) else (
        echo.
        echo [ERROR] Could not download installer.
        echo.
        echo Please ensure:
        echo  1. You are connected to the network
        echo  2. The server is accessible
        echo  3. You have internet access
        echo.
        echo Alternatively, download the installer manually from:
        echo http://192.168.3.2:9001/install-certificate-auto.ps1
        echo.
        pause
        exit /b 1
    )
)

echo.
echo [*] You can close this window now.
echo.
timeout /t 5 >nul
exit /b 0

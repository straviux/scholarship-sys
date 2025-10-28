@echo off
REM ===========================================================
REM  mkcert Root CA Installer for Windows Clients
REM  This script installs the mkcert rootCA.pem as a trusted CA
REM  Requires admin privileges
REM ===========================================================

echo -----------------------------------------------------------
echo mkcert Root CA Installer
echo -----------------------------------------------------------

REM Change this path to point to your rootCA.pem file
set "CERT_PATH=\\192.168.3.2\mkcert\rootCA.pem"

echo Installing certificate from: %CERT_PATH%
echo.

REM Check if running as Administrator
net session >nul 2>&1
if %errorlevel% neq 0 (
    echo This script must be run as Administrator.
    pause
    exit /b
)

REM Import certificate into Trusted Root Certification Authorities
certutil -addstore -f "Root" "%CERT_PATH%"
if %errorlevel% equ 0 (
    echo Successfully installed mkcert root CA certificate.
) else (
    echo Failed to install mkcert certificate. Check the file path and permissions.
)

pause

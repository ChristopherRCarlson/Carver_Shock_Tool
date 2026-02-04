@echo off
setlocal
title Carver Digital Infrastructure Server

:: Find the local IP address
for /f "tokens=2 delims=:" %%a in ('ipconfig ^| findstr /c:"IPv4 Address"') do (
    set "MYIP=%%a"
)
set "MYIP=%MYIP: =%"

echo ====================================================
echo   CARVER DIGITAL INFRASTRUCTURE - SERVER ACTIVE
echo ====================================================
echo.
echo   LOCAL ACCESS: http://localhost:8000
echo   NETWORK ACCESS: http://%MYIP%:8000
echo.
echo   TELL TECHNICIANS TO USE: http://%MYIP%:8000
echo.
echo   (Do not close this window while using the tool)
echo ====================================================
echo.

php -S 0.0.0.0:8000 -t .
pause
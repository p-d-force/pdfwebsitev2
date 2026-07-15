@echo off
echo ========================================
echo Parent Data Force — Platform Restart
echo ========================================
echo.

REM Kill existing processes
taskkill /f /im php.exe >nul 2>&1
taskkill /f /im mysqld.exe >nul 2>&1
echo Stopped existing processes.

REM Start MariaDB
start /b "" "C:\projects\pdf-website\mariadb-portable\mariadb-11.4.5-winx64\bin\mysqld.exe" --datadir="C:\projects\pdf-website\mariadb-data" --port=3307 --skip-grant-tables
echo Starting MariaDB...
timeout /t 3 /nobreak >nul
echo MariaDB started on port 3307.

REM Start PHP dev server
start /b "" C:\php\php.exe -S localhost:8081 -t "C:\projects\pdf-website\dev\public"
echo Starting PHP dev server...
timeout /t 2 /nobreak >nul

REM Verify
echo.
echo Verification:
C:\php\php.exe -r "echo @file_get_contents('http://localhost:8081/about') ? '  PHP Server: OK' : '  PHP Server: FAIL';" 2>nul
echo.
echo ========================================
echo Platform running at http://localhost:8081
echo Admin: http://localhost:8081/admin/login
echo ========================================
pause

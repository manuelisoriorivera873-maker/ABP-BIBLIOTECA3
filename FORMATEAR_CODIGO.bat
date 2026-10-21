@echo off
cd /d %~dp0
if not exist vendor\bin\pint.bat if not exist vendor\bin\pint (
    echo Primero ejecuta: composer install
    pause
    exit /b 1
)
call composer format
pause

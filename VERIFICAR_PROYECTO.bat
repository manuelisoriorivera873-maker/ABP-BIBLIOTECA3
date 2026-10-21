@echo off
cd /d %~dp0

echo ==========================================
echo   Biblioteca 3 - Verificacion del proyecto
echo ==========================================
echo.

if not exist "resources\views\layouts\app.blade.php" (
    echo ERROR: Falta resources\views\layouts\app.blade.php
    echo Asegurate de ejecutar este archivo desde la carpeta raiz de Biblioteca 3.
    pause
    exit /b 1
)

if not exist "resources\views\usuarios\index.blade.php" (
    echo ERROR: Falta la vista de usuarios.
    pause
    exit /b 1
)

if not exist "public\assets\vendor\bootstrap\bootstrap.min.css" (
    echo ERROR: Falta Bootstrap local.
    pause
    exit /b 1
)

echo Estructura de vistas: OK
echo Layout principal: OK
echo Bootstrap local: OK
echo.

php artisan view:clear
php artisan optimize:clear

echo.
echo Proyecto verificado. Ahora ejecuta: php artisan serve
echo.
pause

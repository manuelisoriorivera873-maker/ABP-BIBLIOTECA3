@echo off
setlocal
cd /d "%~dp0"
echo =====================================================
echo BIBLIOTECA3 - SOLO INSTALA LARAVEL Y TABLAS DE ACCESO
echo =====================================================
echo Se conservara la base de datos EXISTENTE biblioteca2.
echo No se recrearan ni borraran las 17 tablas de biblioteca.
choice /c SN /m "Hiciste respaldo de biblioteca2 y configuraste .env?"
if errorlevel 2 goto cancel
if not exist .env (
  copy .env.example .env >nul
  echo Revisa .env y vuelve a ejecutar el instalador.
  goto cancel
)
where php >nul 2>&1
if errorlevel 1 goto error
where composer >nul 2>&1
if errorlevel 1 goto error
call composer install
if errorlevel 1 goto error
findstr /r /c:"^APP_KEY=$" ".env" >nul
if not errorlevel 1 (
    php artisan key:generate
    if errorlevel 1 goto error
)
php artisan migrate --path=database/migrations/2026_10_20_000001_create_acceso_y_permisos_tables.php
if errorlevel 1 goto error
php artisan biblioteca:crear-superadmin
if errorlevel 1 echo Si ya existe un SUPERADMIN, este mensaje es esperado.
php artisan optimize:clear
echo Listo. Ejecuta INICIAR.bat. Login: http://127.0.0.1:8000/login
pause
exit /b 0
:cancel
echo Cancelado. No se ejecutaron migraciones.
pause
exit /b 1
:error
echo ERROR. Abre INICIO_AQUI.txt y revisa la configuracion.
pause
exit /b 1

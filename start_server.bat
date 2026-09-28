@echo off
chcp 65001 > nul
title ระบบประปาหมู่บ้านวังยาง (XAMPP Launcher)

echo ====================================================================
echo    ระบบบริหารจัดการการประปาหมู่บ้านวังยาง (XAMPP PHP + MySQL)
echo ====================================================================
echo.

set "XAMPP_DIR=D:\Games\xamppp"

echo กำลังตรวจสอบสถานะ Apache และ MySQL ใน XAMPP...

:: ตรวจสอบและเริ่ม MySQL
tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I /N "mysqld.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] MySQL กำลังทำงานอยู่แล้ว
) else (
    echo [*] กำลังเริ่ม MySQL Server...
    start /B "" "%XAMPP_DIR%\mysql\bin\mysqld.exe" --defaults-file="%XAMPP_DIR%\mysql\bin\my.ini" --standalone
    timeout /t 2 > nul
)

:: ตรวจสอบและเริ่ม Apache
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo [OK] Apache กำลังทำงานอยู่แล้ว
) else (
    echo [*] กำลังเริ่ม Apache Web Server...
    start /B "" "%XAMPP_DIR%\apache\bin\httpd.exe"
    timeout /t 2 > nul
)

echo.
echo ====================================================================
echo ระบบพร้อมใช้งานแล้วที่:
echo   - ระบบการประปาหมู่บ้านวังยาง (งานกลุ่ม): http://localhost/plumber/
echo   - จัดการฐานข้อมูล (phpMyAdmin):        http://localhost/phpmyadmin/
echo   - งานเดิม (db_city_water_supply):      http://localhost/Water/
echo ====================================================================
echo.

start "" "http://localhost/plumber/"
pause

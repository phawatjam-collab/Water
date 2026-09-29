@echo off
chcp 65001 > nul
title เปิดไฟล์ Plumber_v4.rp ในโปรแกรม Axure RP 11
echo ====================================================================
echo    เปิดไฟล์ต้นแบบ Plumber_v4.rp ในโปรแกรม Axure RP 11 (เครื่องนี้)
echo ====================================================================
echo.

set "AXURE_EXE=D:\Games\AXURE\AxureRP11.exe"
set "RP_FILE=%~dp0axure\Plumber_v4.rp"

if not exist "%RP_FILE%" (
    set "RP_FILE=C:\Users\Little_Jedi\Downloads\Plumber_v4.rp"
)

echo [*] กำลังเปิดโปรแกรม Axure RP 11...
echo [*] ไฟล์: %RP_FILE%
echo.

start "" "%AXURE_EXE%" "%RP_FILE%"

echo [OK] สั่งเปิดโปรแกรม Axure RP 11 เรียบร้อยแล้ว
timeout /t 3 > nul

@echo off
title Cloudflare Public Tunnel for Water System (Plumber)
chcp 65001 >nul
echo ========================================================
echo   เปิดระบบ Cloudflare Public Tunnel (HTTPS)
echo   ระบบประปาหมู่บ้านวังยาง (XAMPP Plumber)
echo ========================================================
echo.
echo กำลังเชื่อมต่อท่อส่งสัญญาณออกสู่อินเทอร์เน็ต...
"d:\Games\xamppp\cloudflared.exe" tunnel --url http://127.0.0.1:80
pause

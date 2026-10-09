@echo off
title Cloudflare Public Tunnel for Water System (Plumber)
chcp 65001 >nul
echo ========================================================
echo   เปิดระบบ Cloudflare Public Tunnel (HTTPS)
echo   ระบบประปาหมู่บ้านวังยาง (XAMPP Plumber)
echo ========================================================
echo.
echo ตรวจสอบและรีเซ็ตการเชื่อมต่อเก่า...
taskkill /f /im cloudflared.exe >nul 2>&1
timeout /t 1 >nul

echo กำลังเชื่อมต่อท่อสัญญาณผ่าน HTTP/2 (IPv4) เพื่อความเสถียรสูงสุด...
echo เมื่อขึ้นกรอบข้อความสีเขียว ให้คัดลอกลิงก์ https://...trycloudflare.com ไปเปิดบนมือถือได้เลย
echo (ห้ามปิดหน้าต่างนี้ขณะใช้งาน ให้ย่อหน้าต่างเก็บไว้)
echo.
"d:\Games\xamppp\cloudflared.exe" tunnel --protocol http2 --edge-ip-version 4 --url http://127.0.0.1:80
pause

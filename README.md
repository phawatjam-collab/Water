# ระบบบริหารจัดการ "การประปาหมู่บ้านวังยาง" (Village Water Supply Management System)

เว็บแอปพลิเคชันและฐานข้อมูลสำหรับบริหารจัดการระบบประปาชุมชน พัฒนาด้วย **PHP + MySQL** บน **XAMPP** โดยแยกข้อมูลและโฟลเดอร์เป็นอิสระ 100% 

---

## 📍 ตำแหน่งโฟลเดอร์และการเชื่อมต่อใน XAMPP

- **โฟลเดอร์โปรเจกต์**: `D:\Games\Plumber\` (เชื่อมผ่าน Junction ไปยัง `D:\Games\xamppp\htdocs\plumber\`)
- **URL เข้าใช้งาน**: [http://localhost/plumber/](http://localhost/plumber/)
- **ฐานข้อมูล MySQL**: `db_wangyang_water` (ดูผ่าน phpMyAdmin ได้ที่ [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/))
- **GitHub Repository**: [https://github.com/phawatjam-collab/Plumber.git](https://github.com/phawatjam-collab/Plumber.git)

---

## 🗄️ การติดตั้งฐานข้อมูลผ่าน phpMyAdmin

หากต้องการนำเข้าฐานข้อมูลบนเครื่องใหม่ หรือตั้งค่าบน phpMyAdmin:
1. เปิดเบราว์เซอร์ไปที่ [http://localhost/phpmyadmin/](http://localhost/phpmyadmin/)
2. สร้างฐานข้อมูลใหม่ชื่อ **`db_wangyang_water`** (การเปรียบเทียบ: `utf8mb4_general_ci` หรือ `utf8mb4_unicode_ci`)
3. คลิกแท็บ **Import (นำเข้า)**
4. เลือกไฟล์ **`database.sql`** (หรือ `database/db_wangyang_water.sql`) จากโฟลเดอร์โปรเจกต์
5. กดปุ่ม **Import (ดำเนินการ)** ด้านล่าง ฐานข้อมูลจะถูกสร้างและนำเข้าข้อมูลพร้อมใช้งานทันที

---

## 🌟 ฟังก์ชันหลักและการแบ่งระดับสิทธิ์ (Role-Based Access Control - RBAC)

ระบบจัดโครงสร้างการเข้าถึงและการแสดงผลเมนูบน Sidebar ตามสิทธิ์ของผู้ใช้จริง:

### 1. ผู้ใช้ทั่วไป / ประชาชน (Public Citizen / Guest - ยังไม่เข้าสู่ระบบ)
- **หน้าแรก (`index.php`)**: ภาพรวมสถิติชุมชน, ค้นหายอดค่าน้ำออนไลน์, เข้าสู่ระบบ
- **ตรวจสอบค่าน้ำออนไลน์ (`portal_citizen.php`)**: ค้นหาบิลตามรหัสผู้ใช้น้ำหรือบ้านเลขที่, สแกนชำระผ่าน PromptPay QR Code, พิมพ์ใบแจ้งหนี้/ใบเสร็จ
- **บริการประชาชนและคำร้อง (`portal_citizen.php#citizen-services`)**: แบบฟอร์มแจ้งเหตุท่อแตก/น้ำไม่ไหล, ยื่นขอติดตั้งมิเตอร์ใหม่, แจ้งตรวจเช็กมิเตอร์ชำรุด

### 2. สมาชิกผู้ใช้น้ำ (Member)
- เข้าสู่ระบบด้วยรหัสผู้ใช้น้ำ (เช่น `WY-001`) หรือเบอร์โทรศัพท์ที่ลงทะเบียน
- **แดชบอร์ดสมาชิกส่วนบุคคล**: ดึงประวัติการใช้น้ำ มิเตอร์น้ำ และบิลประจำงวดของตนเองมาแสดงให้อัตโนมัติทันที
- ติดตามสถานะคำร้องและดาวน์โหลดใบแจ้งหนี้/ใบเสร็จ

### 3. เจ้าหน้าที่จดมิเตอร์ภาคสนาม (Reader)
- เข้าสู่ระบบ: บัญชี `reader` / รหัสผ่าน `123456`
- **สมุดจดมิเตอร์น้ำ (แบบ ป.17) (`meter_reading.php`)**: บันทึกเลขมิเตอร์ครั้งหลัง คำนวณหน่วยใช้และทบยอดค้างชำระอัตโนมัติ

### 4. ฝ่ายการเงินและเหรัญญิก (Finance)
- เข้าสู่ระบบ: บัญชี `finance` / รหัสผ่าน `123456`
- **งานการเงิน ออกใบเสร็จ และฎีกาเบิกจ่าย (`finance_billing.php`)**:
  - ตัดรับชำระเงินค่าน้ำ พิมพ์ใบเสร็จรับเงินมาตรฐานพร้อมแปลงตัวเลขเป็นตัวหนังสือไทย (`baht_text`)
  - ทะเบียนหนี้ค้างชำระ (`#pane-arrears`)
  - ฎีกาเบิกจ่ายเงินกองทุน (`#pane-vouchers`) คำนวณค่าตอบแทนคนเก็บค่าน้ำ 10% จากยอดจัดเก็บจริง
  - ทะเบียนสมาชิกผู้ใช้น้ำ

### 5. คณะกรรมการบริหาร / แอดมิน (Admin / Executive)
- เข้าสู่ระบบ: บัญชี `admin` / รหัสผ่าน `123456`
- **งานกำกับนโยบายและรายงานการเงินกองทุน (แบบ กค.3) (`executive_reports.php`)**: รายงานสรุปรายรับ-รายจ่ายประจำเดือน, วิเคราะห์น้ำสูญเสีย (NRW), ตั้งค่าอัตราค่าน้ำตามช่วงเวลา
- **ศูนย์ควบคุมระบบรวม (`dashboard.php`)**: แดชบอร์ดสรุปผลรวมและควบคุมการปฏิบัติงานทั้งระบบ

---

## 📁 โครงสร้างโปรเจกต์ (Project Structure)

```text
D:\Games\Plumber\
├── index.php                 # หน้าแรก (Home Portal พร้อม Topbar และ Sidebar RBAC)
├── portal_citizen.php        # บริการประชาชนและสมาชิกผู้ใช้น้ำ (ตรวจสอบค่าน้ำ / คำร้อง)
├── meter_reading.php         # สมุดบันทึกการจดมาตรวัดน้ำภาคสนาม (แบบ ป.17)
├── finance_billing.php       # งานการเงิน ตัดรับชำระเงิน ออกใบเสร็จ และฎีกาเบิกจ่าย
├── executive_reports.php     # รายงานงบการเงินกองทุน (แบบ กค.3) และนโยบายอัตราค่าน้ำ
├── dashboard.php             # ศูนย์ควบคุมระบบรวมสำหรับผู้บริหาร/แอดมิน
├── sidebar.php               # แถบเมนูด้านข้างส่วนกลาง กรองตามสิทธิ์ พร้อม Topbar และ Modal ล็อกอิน
├── auth.php                  # ระบบจัดการ Session, RBAC Gatekeeper และความปลอดภัย
├── style.css                 # สไตล์ชีท UI รูปแบบ Responsive และ Print Layout
├── database.sql              # ไฟล์ SQL Dump ฐานข้อมูลสมบูรณ์สำหรับ phpMyAdmin
├── database/
│   ├── db_wangyang_water.sql # สำรองฐานข้อมูล MySQL ฉบับสมบูรณ์
│   └── schema_mysql.sql      # สคริปต์โครงสร้างตาราง DDL และข้อมูลตั้งต้น
├── api/
│   ├── auth.php              # API Authentication & Session Login/Logout
│   ├── db.php                # การเชื่อมต่อ MySQL (db_wangyang_water)
│   ├── bahtText.php          # แปลงตัวเลขเป็นตัวหนังสือภาษาไทย
│   ├── customers.php         # API ทะเบียนสมาชิกผู้ใช้น้ำ
│   ├── cycles.php            # API จัดการรอบบิลประจำเดือน
│   ├── readings.php          # API บันทึกเลขมิเตอร์และคำนวณค่าน้ำ
│   ├── receipts.php          # API ออกใบเสร็จรับเงิน
│   ├── vouchers.php          # API ฎีกาเบิกจ่ายกองทุน
│   ├── financials.php        # API สรุปรายงานการเงินประจำงวด
│   └── export_excel.php      # API ส่งออกข้อมูลเป็นไฟล์ Excel / CSV
└── start_server.bat          # สคริปต์เริ่มการทำงาน Apache + MySQL บน XAMPP
```

---

## 🚀 วิธีเปิดใช้งาน

1. เปิด XAMPP Control Panel และ Start โมดูล **Apache** และ **MySQL** (หรือดับเบิลคลิกไฟล์ **`start_server.bat`**)
2. เปิดเบราว์เซอร์ไปที่ **[http://localhost/plumber/](http://localhost/plumber/)** พร้อมใช้งานทันที

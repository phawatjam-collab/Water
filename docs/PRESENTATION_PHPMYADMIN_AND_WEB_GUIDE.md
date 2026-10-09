# คู่มือการนำเสนอและการเชื่อมต่อสถาปัตยกรรม (Presentation & Integration Guide)
## ระบบบริหารจัดการน้ำประปาหมู่บ้านวังยาง (Wang Yang Village Water Supply System)
### การเชื่อมต่อระหว่าง phpMyAdmin (MySQL Database) กับระบบเว็บไซต์ และแนวทางตอบคำถามกรรมการ

---

![phpMyAdmin Database Tables](../assets/phpmyadmin_tables.png)

---

## 1. บทนำและสถาปัตยกรรมภาพรวม (System Architecture Overview)

### 1.1 ความสัมพันธ์ระหว่าง phpMyAdmin, MySQL และ เว็บไซต์
* **MySQL Database Server:** เป็นระบบจัดการฐานข้อมูลหลัก (Database Management System Engine) ที่รันอยู่ในพื้นหลังบนพอร์ต 3306 ทำหน้าที่เก็บข้อมูลดิบทั้งหมดลงในฮาร์ดดิสก์อย่างปลอดภัย
* **phpMyAdmin:** เป็น **Web GUI Tool (หน้ากากกราฟิกผ่านเว็บ)** ที่พัฒนาด้วยภาษา PHP เพื่อให้ผู้ดูแลระบบ (Admin) สามารถดูโครงสร้างตาราง, ตรวจสอบข้อมูล, แก้ไข Record, และ Import/Export ไฟล์ SQL ได้อย่างสะดวกโดยไม่ต้องพิมพ์คำสั่ง Command-Line SQL ใน Terminal
* **ระบบเว็บไซต์ (Plumber Web Application):** เป็นโปรแกรมประยุกต์ฝั่งเว็บที่เขียนด้วยภาษา PHP (Modern PHP 8), JavaScript, CSS3 และ HTML5 เชื่อมต่อกับ MySQL ผ่านไดรเวอร์ **PDO (PHP Data Objects)** เพื่อดึงข้อมูลมาแสดงผลและบันทึกข้อมูลธุรกรรมแบบ Real-time

```
┌────────────────────────────────────────────────────────────────────────┐
│                        ผู้ใช้งาน / อุปกรณ์ลูกข่าย                       │
│    [เจ้าหน้าที่จดมิเตอร์บนมือถือ]    [ฝ่ายการเงินบน PC]    [ลูกบ้านบนสมาร์ตโฟน]  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ (HTTP / HTTPS Request)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                   เว็บเซิร์ฟเวอร์ Apache (Port 80 / 443)                 │
│              รองรับทั้ง Localhost, LAN IP และ Cloudflare Public Tunnel  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                     PHP 8 Application Backend Engine                    │
│   • meter_reading.php    • finance_billing.php    • portal_citizen.php │
│   • auth.php (RBAC)      • api/readings.php       • api/customers.php  │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ (PDO Prepared Statements)
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│                      MySQL Database Server (Port 3306)                 │
│                         ฐานข้อมูล: db_city_water_supply / plumber      │
│                                                                        │
│   ┌────────────────────────────────────────────────────────────────┐   │
│   │ phpMyAdmin (Admin GUI Interface)                               │   │
│   │ หน้าต่างสำหรับผู้ดูแลระบบตรวจสอบตารางและจัดการข้อมูลดิบ          │   │
│   └────────────────────────────────────────────────────────────────┘   │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. อธิบายบทบาทหน้าที่ของตารางทั้ง 16 ตาราง (Table-by-Table Breakdown)

จากรูปภาพหน้าต่าง phpMyAdmin ที่แคปเจอร์มา โครงสร้างตารางในฐานข้อมูลถูกแบ่งออกเป็น 5 หมวดหมู่การทำงานที่สอดประสานกันอย่างเป็นระบบ:

| ลำดับ | ชื่อตารางใน phpMyAdmin | คำอธิบายและบทบาทในระบบ | คีย์หลัก / คีย์เชื่อมโยง (Key & Relationship) |
| :---: | :--- | :--- | :--- |
| **1** | `billing_cycles` | **รอบบิลการใช้น้ำประจำเดือน:** จัดเก็บงวดการจดมิเตอร์ เช่น `8-2567`, วันเริ่มต้นรอบ, วันสิ้นสุดรอบ, วันกำหนดชำระ (Due Date), และสถานะรอบบิล (`open`, `billed`, `closed`) | `id` (PK), `cycle_code` (Unique Key เชื่อมไปยัง `meter_readings.cycle_code`) |
| **2** | `customers` | **ทะเบียนผู้ใช้น้ำ (ลูกบ้าน):** ข้อมูลรหัสผู้ใช้น้ำ (`customer_code` เช่น `WY-001`), ชื่อ-นามสกุล, บ้านเลขที่, คุ้ม/โซน, เบอร์โทรศัพท์, ละติจูด-ลองจิจูด, และสถานะการใช้น้ำ (`active`, `suspended`) | `id` (PK), `customer_code` (Unique Key), `zone_id` (FK เชื่อม `tb_zone`) |
| **3** | `meter_readings` | **สมุดบันทึกเลขอ่านมิเตอร์ภาคสนาม:** บันทึกเลขอ่านครั้งก่อน (`previous_reading`), เลขครั้งนี้ (`current_reading`), จำนวนหน่วยน้ำที่ใช้ (`units_used`), ยอดเงินรวม (`total_amount`), สถานะการชำระ (`unpaid`, `paid`), และวันที่จด | `id` (PK), `customer_id` (FK เชื่อม `customers.id`), `cycle_code` (FK เชื่อม `billing_cycles`) |
| **4** | `monthly_financial_reports` | **รายงานสรุปงบการเงินประจำเดือน:** บันทึกรายได้รวมค่าน้ำ, รายได้ค่าธรรมเนียม, ยอดค้างชำระสะสม, ค่าใช้จ่ายในการผลิตน้ำ, และกำไรสุทธิ เพื่อป้อนเข้าหน้า Dashboard ผู้บริหาร | `id` (PK), `cycle_code` (Unique Key เชื่อม `billing_cycles`) |
| **5** | `payment_vouchers` | **หลักฐานการรับชำระเงินและใบเสร็จ:** บันทึกการออกใบเสร็จรับเงิน, ช่องทางการชำระ (เงินสด, PromptPay QR), วันเวลาที่ชำระ, และรหัสเจ้าหน้าที่ผู้รับเงิน | `id` (PK), `reading_id` (FK เชื่อม `meter_readings.id`), `customer_id` (FK เชื่อม `customers.id`) |
| **6** | `service_tickets` | **ระบบแจ้งซ่อมและบริการลูกบ้าน:** จัดเก็บบันทึกคำร้องขอน้ำประปา, แจ้งท่อแตก/รั่ว, น้ำไม่ไหล, และสถานะการเข้าซ่อมของทีมช่าง (`pending`, `in_progress`, `resolved`) | `id` (PK), `customer_id` (FK เชื่อม `customers.id`) |
| **7** | `tariff_rates` | **ตารางอัตราค่าน้ำแบบขั้นบันได (Progressive Tariff):** บันทึกช่วงหน่วยน้ำ (เช่น 0-10 หน่วย, 11-20 หน่วย, 21-30 หน่วย), ราคาต่อหน่วย, และค่าธรรมเนียมบำรุงรักษาท่อรายเดือน (10 บาท) | `id` (PK), `rate_tier`, `min_unit`, `max_unit`, `price_per_unit` |
| **8** | `tb_customers` | **ตารางข้อมูลลูกค้ามาตรฐานแบบ Relational:** รองรับโครงสร้าง Normalized Database มาตรฐาน ใช้จับคู่ข้อมูลกับ `tb_installation` และ `tb_zone` | `cus_id` (PK), `cus_code`, `cus_name`, `zone_id` (FK) |
| **9** | `tb_expenses` | **บันทึกรายจ่ายกองการประปา:** ค่าไฟฟ้าปั๊มน้ำแรงดันสูง, ค่าสารส้ม-คลอรีน, ค่าซ่อมบำรุงท่อ, ค่าจ้างเจ้าหน้าที่ | `exp_id` (PK), `exp_date`, `exp_amount`, `exp_category` |
| **10** | `tb_financial_summary` | **สรุปยอดบัญชีรับ-จ่าย:** ข้อมูลทางการเงินระดับสรุปยอด เพื่อคำนวณเงินคงเหลือและกระแสเงินสดของกองทุนประปา | `summary_id` (PK), `fiscal_year`, `total_income`, `total_expense` |
| **11** | `tb_installation` | **ประวัติการขอติดตั้งมิเตอร์ใหม่:** บันทึกคำขอใช้น้ำ, ขนาดมิเตอร์ (1/2 นิ้ว, 3/4 นิ้ว), ค่าธรรมเนียมติดตั้ง, และวันที่เริ่มจ่ายน้ำ | `inst_id` (PK), `cus_id` (FK เชื่อม `tb_customers`) |
| **12** | `tb_meter_readings` | **ตารางเลขอ่านมิเตอร์ (Relational Normalized Format):** ใช้สำรองและรองรับการออกใบเสร็จแบบ Cross-Schema | `reading_id` (PK), `cus_id` (FK), `meter_prev`, `meter_curr`, `units` |
| **13** | `tb_receipts` | **ประวัติใบเสร็จรับเงินแบบละเอียด:** จัดเก็บเลขที่ใบเสร็จรับเงินอย่างเป็นทางการ (Tax/Official Invoice No.) สำหรับการพิมพ์สมุดบัญชี | `rcpt_id` (PK), `reading_id` (FK), `rcpt_no`, `amount_paid` |
| **14** | `tb_water_rates` | **อัตราค่าน้ำมาตรฐานท้องถิ่น:** จัดเก็บกฎเกณฑ์การคิดคำนวณค่าน้ำตามระเบียบกระทรวงมหาดไทย/ท้องถิ่น | `rate_id` (PK), `rate_type`, `unit_price`, `service_fee` |
| **15** | `tb_zone` | **ทำเนียบโซนและคุ้มสายเดินจด:** แบ่งพื้นที่ในหมู่บ้าน เช่น โซน 1 คุ้มทุ่งสามัคคี, โซน 2 คุ้มวัดศรีดอนไชย เพื่อจัดกลุ่มสายเดินจดมิเตอร์ | `zone_id` (PK), `zone_name`, `reader_staff_name` |
| **16** | `water_loss_logs` | **บันทึกปริมาณน้ำสูญเสีย (Non-Revenue Water - NRW):** เปรียบเทียบปริมาณน้ำที่สูบออกจากโรงผลิต vs ปริมาณน้ำรวมที่จดได้ตามมิเตอร์บ้าน เพื่อตรวจจับท่อแตกรั่วใต้ดิน | `id` (PK), `log_date`, `produced_cubic_meter`, `sold_cubic_meter`, `loss_percent` |

---

## 3. กลไกการเชื่อมต่อโค้ดภาษา PHP กับ phpMyAdmin (Code Linkage)

### 3.1 ไฟล์ศูนย์กลางการเชื่อมต่อ: `api/db.php`
เว็บไซต์ไม่ได้เชื่อมต่อไปที่ phpMyAdmin โดยตรง แต่เชื่อมต่อเข้าที่ **MySQL Database Engine** ตัวเดียวกันกับที่ phpMyAdmin เปิดอ่านอยู่ ผ่านไดรเวอร์ `PDO`:

```php
// โค้ดเชื่อมต่อฐานข้อมูลใน api/db.php
$host = 'localhost';
$db_name = 'db_city_water_supply'; // หรือ plumber
$username = 'root';
$password = '';

$pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $username, $password, [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // แจ้งเตือนข้อผิดพลาดเป็น Exception
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // คืนค่าผลลัพธ์เป็น Associative Array
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+07:00'" // รองรับภาษาไทยและเวลาไทย
]);
```

### 3.2 ความปลอดภัยและการทำงานแบบ Prepared Statements
ทุกคำสั่ง SQL ในระบบ ถูกเขียนโดยใช้ **Prepared Statements (`$stmt->prepare()` และ `$stmt->execute()`)** เพื่อแยกคำสั่ง SQL ออกจากข้อมูลที่ผู้ใช้ป้อนเข้ามา 100% จึงช่วยป้องกันการถูกโจมตีแบบ **SQL Injection** ได้อย่างสิ้นเชิง

```php
// ตัวอย่างการบันทึกค่าน้ำใน api/readings.php
$stmt = $pdo->prepare("
    INSERT INTO meter_readings (customer_id, cycle_code, previous_reading, current_reading, units_used, total_amount, reading_date)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE 
        current_reading = VALUES(current_reading),
        units_used = VALUES(units_used),
        total_amount = VALUES(total_amount),
        reading_date = NOW()
");
$stmt->execute([$customerId, $cycleCode, $prev, $curr, $units, $totalAmount]);
```

---

## 4. แผนผังการทำงานจากหน้าเว็บสู่ตารางฐานข้อมูล (Frontend to Database Mapping)

| หน้าเว็บในระบบ | ฟังก์ชันงานหลัก | ตารางที่เกี่ยวข้องใน phpMyAdmin | คำสั่ง SQL ที่เกิดขึ้น |
| :--- | :--- | :--- | :--- |
| **`meter_reading.php`**<br>(จดมิเตอร์ภาคสนาม) | • สแกน QR Code หน้าบ้าน<br>• บันทึกเลขมิเตอร์ปัจจุบัน<br>• แสดงเลขมิเตอร์ครั้งก่อน | • `customers`<br>• `meter_readings`<br>• `billing_cycles`<br>• `tariff_rates` | `SELECT c.*, m.current_reading FROM customers c LEFT JOIN meter_readings m ...`<br>`INSERT/UPDATE INTO meter_readings ...` |
| **`finance_billing.php`**<br>(งานการเงินและออกบิล) | • ออกใบแจ้งหนี้ / ใบเสร็จ<br>• สร้าง PromptPay QR Code<br>• รับชำระเงินและบันทึกบัญชี | • `meter_readings`<br>• `payment_vouchers`<br>• `monthly_financial_reports`<br>• `tariff_rates` | `UPDATE meter_readings SET status = 'paid' WHERE id = ?`<br>`INSERT INTO payment_vouchers (reading_id, amount, method) ...` |
| **`portal_citizen.php`**<br>(พอร์ทัลลูกบ้าน) | • ลูกบ้านค้นหาประวัติค่าน้ำ<br>• สแกนจ่ายผ่าน QR Code<br>• ส่งคำร้องแจ้งท่อแตกรั่ว | • `customers`<br>• `meter_readings`<br>• `service_tickets` | `SELECT * FROM meter_readings WHERE customer_id = ? ORDER BY id DESC`<br>`INSERT INTO service_tickets (customer_id, topic, status) ...` |
| **`dashboard.php`**<br>(แดชบอร์ดผู้บริหาร) | • ดูกราฟรายรับ-รายจ่าย<br>• ตรวจสอบอัตราน้ำสูญเสีย (NRW)<br>• สรุปสถานะลูกหนี้ค้างชำระ | • `monthly_financial_reports`<br>• `water_loss_logs`<br>• `meter_readings` | `SELECT SUM(total_amount), SUM(units_used) FROM meter_readings WHERE cycle_code = ?`<br>`SELECT * FROM water_loss_logs ORDER BY log_date DESC` |

---

## 5. คู่มือตอบคำถามอาจารย์และกรรมการสอบโปรเจกต์ (10 Defense Q&A)

### Q1: phpMyAdmin ในภาพที่แคปเจอร์มา ทำหน้าที่อะไรในระบบนี้?
> **แนวทางการตอบ:** "phpMyAdmin เป็นเครื่องมือ Web-based GUI สำหรับผู้ดูแลระบบ (Database Administrator) ทำหน้าที่บริหารจัดการฐานข้อมูล MySQL เช่น ตรวจสอบตาราง, ดูข้อมูลดิบ, และสำรองข้อมูล โดยเว็บของเราไม่ได้คุยกับ phpMyAdmin โดยตรง แต่เว็บกับ phpMyAdmin ทำงานอยู่บน MySQL Database Server เดียวกัน เมื่อเจ้าหน้าที่จดมิเตอร์บันทึกข้อมูลผ่านหน้าเว็บ ข้อมูลจะถูกเขียนลงตารางใน MySQL ทันที ทำให้เมื่อเรากดรีเฟรชใน phpMyAdmin ก็จะเห็นข้อมูลอัปเดตตรงกันทันทีแบบ Real-time ครับ"

### Q2: เว็บไซต์เชื่อมต่อไปยังฐานข้อมูลอย่างไร มีโครงสร้างโค้ดแบบไหน?
> **แนวทางการตอบ:** "ระบบเชื่อมต่อผ่านคลาส `PDO (PHP Data Objects)` ที่เขียนไว้ในไฟล์ `api/db.php` ครับ โดยใช้ Connection String ระบุ Host, Database Name, User และ Password พร้อมกำหนด `UTF-8mb4` เพื่อรองรับภาษาไทยอย่างสมบูรณ์ และตั้งค่า Error Mode เป็น `ERRMODE_EXCEPTION` เพื่อให้สามารถดักจับข้อผิดพลาดผ่านบล็อก `try-catch` ได้อย่างปลอดภัยครับ"

### Q3: ทำไมในรูปภาพจึงมีทั้งตาราง `customers` และ `tb_customers`?
> **แนวทางการตอบ:** "ระบบออกแบบโดยรองรับสถาปัตยกรรม 2 ระดับครับ: 
> 1. ตารางกลุ่ม `customers`, `meter_readings` เป็น **Clean Modern Schema** ที่ออกแบบฟิลด์ให้กระชับ เหมาะกับ RESTful JSON API และการทำงานความเร็วสูงบนมือถือ (Mobile Responsive)
> 2. ตารางกลุ่ม `tb_...` เป็น **Relational Normalized Schema** แบบดั้งเดิมที่จัดกลุ่มเป็น 3NF เพื่อเก็บประวัติเชิงลึกและการเชื่อมโยงกับฝ่ายบัญชี ซึ่งระบบมีฟังก์ชัน Sync ข้อมูลร่วมกัน ทำให้มีความเข้ากันได้สูง (Backward Compatibility) และไม่เกิดปัญหาข้อมูลขัดแย้งกันครับ"

### Q4: ระบบมีวิธีคำนวณค่าน้ำประปาอย่างไร และดึงอัตรามาจากตารางไหน?
> **แนวทางการตอบ:** "ระบบคำนวณตามอัตราค่าน้ำแบบขั้นบันได (Progressive Tier) ที่บันทึกไว้ในตาราง `tariff_rates` ครับ เช่น 0-10 หน่วยแรกคิดหน่วยละ 5 บาท, 11-20 หน่วยคิดหน่วยละ 7 บาท และมีค่าธรรมเนียมบำรุงรักษามิเตอร์คงที่ 10 บาท/เดือน เมื่อเจ้าหน้าที่คีย์เลขมิเตอร์บนมือถือ ระบบจะนำเลขปัจจุบันลบด้วยเลขครั้งก่อน แล้วส่งเข้าอัลกอริทึมคำนวณอัตราขั้นบันไดและแสดงผลลัพธ์เป็นยอดเงินบาทให้เห็นทันทีครับ"

### Q5: ถ้าลูกบ้านใช้มิเตอร์จนหมุนครบรอบ (Rollover เช่น จาก 9999 กลับมาเป็น 0005) ระบบจัดการอย่างไร?
> **แนวทางการตอบ:** "ระบบมีสูตรตรวจสอบความผิดปกติในไฟล์ `meter_reading.php` และ API ครับ หากพบว่า `เลขปัจจุบัน < เลขครั้งก่อน` ระบบจะไม่แจ้งว่าติดลบ แต่จะคำนวณตามสูตร Rollover: `(10,000 - เลขครั้งก่อน) + เลขปัจจุบัน` พร้อมติดป้ายแจ้งเตือนให้เจ้าหน้าที่ตรวจสอบซ้ำเพื่อความแม่นยำครับ"

### Q6: ระบบป้องกันการถูกโจมตีทางฐานข้อมูล (SQL Injection) อย่างไร?
> **แนวทางการตอบ:** "ระบบใช้ **PDO Prepared Statements** พร้อม Parameter Binding ทุกจุดที่มีการรับค่าจากผู้ใช้ครับ ไม่มีการนำตัวแปรจาก `$_GET` หรือ `$_POST` ไปต่อ String ในคำสั่ง SQL โดยตรง ข้อมูลที่ส่งเข้ามาจะถูกส่งเป็น Parameter แยกต่างหาก ทำให้ Hacker ไม่สามารถแทรกคำสั่ง SQL อันตรายเพื่อขโมยหรือทำลายฐานข้อมูลได้ครับ"

### Q7: การชำระเงินผ่าน PromptPay QR Code ทำงานเชื่อมโยงกับฐานข้อมูลอย่างไร?
> **แนวทางการตอบ:** "เมื่อเปิดหน้าออกบิล `finance_billing.php` หรือหน้าพอร์ทัลลูกบ้าน `portal_citizen.php` ระบบจะอ่านยอดหนี้จากตาราง `meter_readings` แล้วนำยอดเงินสุทธิไปสร้างเป็น **EMVCo QR Code มาตรฐานธนาคารแห่งประเทศไทย (PromptPay Payload)** แสดงผลบนหน้าจอ เมื่อลูกบ้านชำระเงิน เจ้าหน้าที่จะกดยืนยัน ระบบจะทำธุรกรรมบันทึกลงตาราง `payment_vouchers` และอัปเดตสถานะในตาราง `meter_readings` เป็น `paid` ทันทีครับ"

### Q8: ตาราง `water_loss_logs` มีประโยชน์อย่างไรกับการบริหารจัดการน้ำ?
> **แนวทางการตอบ:** "เป็นตารางสำหรับติดตาม **ค่าน้ำสูญเสีย (Non-Revenue Water - NRW)** ครับ โดยนำปริมาณน้ำที่สูบจ่ายออกจากสถานีผลิตน้ำ มาเปรียบเทียบกับผลรวมของหน่วยน้ำที่จดได้จริงตามบ้านในตาราง `meter_readings` หากผลต่างเกิน 15-20% หน้า Dashboard จะแจ้งเตือนระดับวิกฤต แสดงว่าอาจเกิดเหตุการณ์ท่อส่งน้ำหลักแตกรั่วใต้ดิน ช่วยให้ทีมช่างเข้าซ่อมแซมได้ทันท่วงทีก่อนสิ้นเปลืองงบประมาณครับ"

### Q9: เมื่อเจ้าหน้าที่เดินจดมิเตอร์บนมือถือกดบันทึก ข้อมูลวิ่งอย่างไร?
> **แนวทางการตอบ:** "เมื่อเจ้าหน้าที่แตะปุ่ม `บันทึก & สแกนหลังถัดไป` บนมือถือ:
> 1. JavaScript จะส่งข้อมูล JSON ผ่านคำสั่ง `fetch()` ไปที่ `api/readings.php`
> 2. ฝั่งเซิร์ฟเวอร์จะตรวจสอบความถูกต้องของสิทธิ์และข้อมูล
> 3. ทำการรันคำสั่ง `INSERT ... ON DUPLICATE KEY UPDATE` ลงตาราง `meter_readings`
> 4. ส่งผลตอบรับ `{ success: true }` กลับมาที่มือถือในเวลาไม่ถึง 0.2 วินาที เพื่อให้กล้องพร้อมสแกนบ้านหลังถัดไปได้อย่างรวดเร็วครับ"

### Q10: ระบบรองรับการเปิดใช้งานจริงนอกสถานที่ และมีความปลอดภัยของข้อมูลอย่างไร?
> **แนวทางการตอบ:** "ระบบรองรับการเปิดสาธารณะผ่าน **Cloudflare Public Tunnel (HTTPS)** ซึ่งมีใบรับรองความปลอดภัยระดับสากล ทำให้เจ้าหน้าที่สามารถใช้มือถือสแกนผ่านเน็ต 4G/5G จากหน้าบ้านลูกบ้านได้โดยไม่ต้องอยู่ในวงแลนเดียวกับคอมพิวเตอร์ และระบบยังควบคุมสิทธิ์ด้วย **RBAC (Role-Based Access Control)** ใน `auth.php` แบ่งสิทธิ์ผู้ดูแลระบบ (Admin), เจ้าหน้าที่จดมิเตอร์ (Staff), และลูกบ้าน (Member) อย่างชัดเจนครับ"

---

## 6. สรุปสถานะการเปิดระบบ Public Cloudflare Tunnel สำหรับการนำเสนอ

ปัจจุบันระบบได้เปิดท่อส่งสัญญาณ **Cloudflare Public Tunnel (HTTPS)** เรียบร้อยแล้ว:
* **ลิงก์สาธารณะระดับโลก:** สามารถนำลิงก์ที่ระบบสร้างให้ไปเปิดบนมือถือของกรรมการหรือสมาร์ตโฟนของตนเองเพื่อสาธิตการสแกน QR Code หน้าบ้านแบบสดๆ โดยไม่ต้องกังวลเรื่อง Wi-Fi หรือการตั้งค่าความปลอดภัย
* **การเปิดระบบซ้ำในอนาคต:** เพียงดับเบิลคลิกไฟล์ [`start_public_tunnel.bat`](file:///d:/Games/xamppp/htdocs/plumber/start_public_tunnel.bat) ที่สร้างไว้ในโฟลเดอร์หลัก ระบบจะเปิดท่อสาธารณะใหม่อัตโนมัติทันที

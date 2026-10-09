# The Complete SQL Encyclopedia & Exam Bible (สารานุกรมคำสั่ง SQL ฉบับสมบูรณ์)
**รวบรวมคำสั่ง SQL ทุกหมวดหมู่ ทุกฟังก์ชัน ทุกประเภทไวยากรณ์ พร้อมตัวอย่างโค้ดและคำอธิบายเชิงลึก**

---

## 📑 สารบัญหมวดหมู่คำสั่ง SQL (Classification Index)
1. **[หมวดที่ 1: DDL (Data Definition Language)]** — โครงสร้างฐานข้อมูล ตาราง ชนิดข้อมูล และ Constraints
2. **[หมวดที่ 2: DML (Data Manipulation Language)]** — การเพิ่ม แก้ไข ลบข้อมูลในตาราง
3. **[หมวดที่ 3: DQL พื้นฐาน & Operators ทั้งหมด]** — SELECT, WHERE, เครื่องหมายเปรียบเทียบและตรรกศาสตร์ครบทุกตัว
4. **[หมวดที่ 4: ฟังก์ชันเดี่ยว (Built-in Scalar Functions)]** — ข้อความ (String), ตัวเลข (Math), วันที่และเวลา (Date/Time), และเงื่อนไข (Control Flow)
5. **[หมวดที่ 5: สถิติและการจัดกลุ่ม (Aggregation & Grouping)]** — COUNT, SUM, AVG, MIN, MAX, GROUP BY, HAVING, WITH ROLLUP
6. **[หมวดที่ 6: การเชื่อมโยงตาราง (Relational JOINs)]** — INNER, LEFT, RIGHT, FULL, CROSS, SELF, NATURAL, Anti-Join
7. **[หมวดที่ 7: การรวมเซตข้อมูล (Set Operations)]** — UNION, UNION ALL, INTERSECT, EXCEPT
8. **[หมวดที่ 8: คิวรีย่อย (Subqueries)]** — Scalar, Column/Row, Correlated, EXISTS, IN, ANY, ALL
9. **[หมวดที่ 9: CTE & Window Functions]** — WITH (Common Table Expressions), ROW_NUMBER, RANK, DENSE_RANK, LAG, LEAD, Running Total
10. **[หมวดที่ 10: ธุรกรรมและความปลอดภัย (TCL & DCL)]** — Transaction, COMMIT, ROLLBACK, ACID, GRANT, REVOKE
11. **[หมวดที่ 11: ออบเจกต์ฐานข้อมูลระดับสูง (Views, Triggers, Procedures)]** — VIEW, INDEX, TRIGGER, STORED PROCEDURE
12. **[หมวดที่ 12: คลังโจทย์ข้อสอบจริง 25 ข้อ พร้อมเฉลยละเอียดแบบ Step-by-Step]**

---

# หมวดที่ 1: DDL (Data Definition Language) — การจัดการโครงสร้าง

คำสั่ง DDL ใช้สำหรับ **สร้าง ปรับเปลี่ยน และลบ** ออบเจกต์ในฐานข้อมูล เช่น ฐานข้อมูล ตาราง ดัชนี หรือวิว

### 1.1 การจัดการฐานข้อมูล (Database Management)
```sql
-- 1. สร้างฐานข้อมูลใหม่ พร้อมกำหนด Charset ภาษาไทย utf8mb4
CREATE DATABASE IF NOT EXISTS db_water_supply 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. เลือกใช้งานฐานข้อมูล
USE db_water_supply;

-- 3. ลบฐานข้อมูลทิ้งทั้งก้อน (ระวัง! ข้อมูลทั้งหมดจะหายทันที)
DROP DATABASE IF EXISTS db_water_supply;
```

---

### 1.2 ชนิดข้อมูลใน SQL (SQL Data Types Summary)
* **ตัวเลขจำนวนเต็ม:**
  * `TINYINT` (-128 ถึง 127 หรือ 0 ถึง 255 มักใช้เก็บค่า Boolean 0/1 หรือสถานะย่อย)
  * `INT` หรือ `INTEGER` (จำนวนเต็มมาตรฐาน ประมาณ ±2,000 ล้าน)
  * `BIGINT` (จำนวนเต็มขนาดใหญ่มาก มักใช้เก็บ ID ระดับพันล้าน หรือ Timestamp ละเอียด)
* **ตัวเลขทศนิยม:**
  * `DECIMAL(M, D)` หรือ `NUMERIC(M, D)`: **แนะนำสูงสุดสำหรับเงินและบัญชี** โดย `M` คือจำนวนหลักทั้งหมด และ `D` คือจำนวนหลักทศนิยม เช่น `DECIMAL(10, 2)` เก็บได้ถึง `99999999.99` (ไม่มีปัญหาปัดเศษผิดพลาด)
  * `FLOAT`, `DOUBLE`: ทศนิยมแบบลอยตัว เหมาะกับงานวิทยาศาสตร์/พิกัด GPS
* **ข้อความและสตริง:**
  * `CHAR(N)`: ข้อความความยาวคงที่ จองพื้นที่เท่ากับ N ตัวอักษรเสมอ (เช่น รหัสไปรษณีย์ `CHAR(5)`, รหัสจังหวัด `CHAR(2)`)
  * `VARCHAR(N)`: ข้อความความยาวแปรผันตามจริง ไม่เกิน N ตัวอักษร (เช่น ชื่อ-นามสกุล `VARCHAR(100)`, อีเมล `VARCHAR(150)`)
  * `TEXT`: ข้อความยาวมาก (เก็บบทความ รายละเอียด หมายเหตุ ไม่เกิน 65,535 ตัวอักษร)
* **วันและเวลา:**
  * `DATE`: เก็บเฉพาะวันที่ รูปแบบ `'YYYY-MM-DD'` (เช่น `'2024-08-25'`)
  * `TIME`: เก็บเฉพาะเวลา รูปแบบ `'HH:MM:SS'` (เช่น `'14:30:00'`)
  * `DATETIME`: เก็บวันและเวลา รูปแบบ `'YYYY-MM-DD HH:MM:SS'` (ช่วงปี 1000 ถึง 9999)
  * `TIMESTAMP`: เก็บวันและเวลาอิง Timezone สากล มักใช้กับ `DEFAULT CURRENT_TIMESTAMP`

---

### 1.3 ข้อกำหนดความถูกต้องของข้อมูล (Table Constraints ทั้งหมด)
1. `PRIMARY KEY` (คีย์หลัก): เอกลักษณ์ประจำแถว ค่าห้ามซ้ำ และ **ห้ามเป็น NULL**
2. `FOREIGN KEY` (คีย์นอก): เชื่อมโยงไปยัง Primary Key ของตารางอื่น เพื่อรักษาความสัมพันธ์ (Referential Integrity)
3. `NOT NULL`: บังคับว่าคอลัมน์นี้ **ห้ามเว้นว่าง**
4. `UNIQUE`: ค่าในคอลัมน์นี้ **ห้ามซ้ำกัน** แต่สามารถเป็น NULL ได้
5. `DEFAULT`: กำหนดค่าเริ่มต้นอัตโนมัติหากไม่ได้ระบุข้อมูลตอนเพิ่ม
6. `CHECK`: กำหนดเงื่อนไขตรวจสอบค่าที่ป้อน (เช่น เงินเดือนต้อง > 0)
7. `AUTO_INCREMENT`: เพิ่มค่าตัวเลขรหัสอัตโนมัติทีละ 1

---

### 1.4 ตัวอย่างคำสั่ง `CREATE TABLE` ฉบับสมบูรณ์
```sql
CREATE TABLE tb_customers (
    cus_id INT(4) UNSIGNED ZEROFILL AUTO_INCREMENT, -- รหัสลูกค้า รันอัตโนมัติ เช่น 0001
    customer_code VARCHAR(20) NOT NULL UNIQUE,      -- รหัสประจำตัว เช่น WY-001 ห้ามซ้ำ
    first_name VARCHAR(50) NOT NULL,                -- ชื่อ ห้ามว่าง
    last_name VARCHAR(50) NOT NULL,                 -- นามสกุล ห้ามว่าง
    zone_id INT NOT NULL,                           -- รหัสโซน (FK)
    meter_size VARCHAR(10) DEFAULT '5/8 นิ้ว',       -- ขนาดมิเตอร์ ค่าตั้งต้น
    monthly_limit DECIMAL(10,2) CHECK (monthly_limit >= 0), -- วงเงินค่าน้ำ ต้อง >= 0
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- เวลาบันทึกอัตโนมัติ
    PRIMARY KEY (cus_id),
    CONSTRAINT fk_customer_zone 
        FOREIGN KEY (zone_id) REFERENCES tb_zones(zone_id)
        ON UPDATE CASCADE   -- ถ้าโซนเปลี่ยนรหัส ให้แก้ตามอัตโนมัติ
        ON DELETE RESTRICT  -- ถ้ามีลูกค้าอยู่ในโซน ห้ามลบโซนนั้นทิ้ง
);
```

#### ตัวเลือกของ FOREIGN KEY Action (`ON DELETE` / `ON UPDATE`):
* `CASCADE`: ลบหรือแก้ไขข้อมูลตารางลูกตามตารางแม่ทันที
* `RESTRICT` / `NO ACTION`: ปฏิเสธการลบตารางแม่ หากยังมีข้อมูลลูกอ้างอิงอยู่ (ความปลอดภัยสูงสุด)
* `SET NULL`: ปรับค่าในตารางลูกให้กลายเป็น `NULL` เมื่อตารางแม่ถูกลบ

---

### 1.5 การปรับโครงสร้างตารางด้วย `ALTER TABLE`
```sql
-- 1. เพิ่มคอลัมน์ใหม่
ALTER TABLE tb_customers ADD COLUMN phone VARCHAR(15) AFTER last_name;

-- 2. ลบคอลัมน์ที่ไม่ต้องการ
ALTER TABLE tb_customers DROP COLUMN monthly_limit;

-- 3. แก้ไขชนิดข้อมูลหรือคุณสมบัติของคอลัมน์เดิม (MODIFY)
ALTER TABLE tb_customers MODIFY COLUMN phone VARCHAR(20) NOT NULL;

-- 4. เปลี่ยนชื่อคอลัมน์พร้อมเปลี่ยนชนิดข้อมูล (CHANGE)
ALTER TABLE tb_customers CHANGE COLUMN phone cus_tel VARCHAR(20) NOT NULL;

-- 5. เพิ่ม Constraint ภายหลัง
ALTER TABLE tb_customers ADD CONSTRAINT uc_customer_tel UNIQUE (cus_tel);

-- 6. ลบ Foreign Key
ALTER TABLE tb_customers DROP FOREIGN KEY fk_customer_zone;

-- 7. เปลี่ยนชื่อตาราง
ALTER TABLE tb_customers RENAME TO members;
```

---

### 1.6 ข้อแตกต่างระหว่าง `DROP` vs `TRUNCATE` vs `DELETE` (ข้อสอบชอบถาม!)
| คุณสมบัติ | `DROP TABLE` | `TRUNCATE TABLE` | `DELETE FROM` |
| :--- | :--- | :--- | :--- |
| **สิ่งที่ถูกลบ** | ลบทั้ง **ข้อมูล และ โครงสร้างตาราง** | ลบ **เฉพาะข้อมูลทั้งหมด** โครงสร้างตารางยังอยู่ | ลบ **เฉพาะข้อมูล** ตามเงื่อนไข `WHERE` |
| **ประเภทคำสั่ง** | DDL | DDL | DML |
| **ความเร็ว** | เร็วมาก | เร็วมาก (ตัด Deallocate หน้าหน่วยความจำ) | ช้ากว่า (บันทึกล็อกทีละแถว) |
| **Rollback ได้ไหม?** | ไม่ได้ (Auto-commit) | ไม่ได้ (Auto-commit ในส่วนใหญ่) | ✅ ได้ (หากอยู่ใน Transaction) |
| **รีเซ็ต AUTO_INCREMENT** | ตารางหายไปเลย | ✅ รีเซ็ตกลับไปเริ่มนับ 1 ใหม่ | ❌ ไม่รีเซ็ต นับต่อจากค่าล่าสุด |

---

# หมวดที่ 2: DML (Data Manipulation Language) — การจัดการข้อมูล

คำสั่ง DML ใช้สำหรับ **เพิ่ม ลบ แก้ไข** ข้อมูลภายในตาราง

### 2.1 คำสั่ง `INSERT` (การเพิ่มข้อมูล)
```sql
-- แบบที่ 1: ระบุชื่อคอลัมน์ชัดเจน (แนะนำที่สุด ปลอดภัยแม้ตารางมีการเพิ่มคอลัมน์ใหม่)
INSERT INTO tb_customers (customer_code, first_name, last_name, zone_id)
VALUES ('WY-009', 'กิตติศักดิ์', 'วงษ์สุวรรณ', 1);

-- แบบที่ 2: เพิ่มหลายแถวพร้อมกันในคำสั่งเดียว (Bulk Insert เร็วกว่ารันทีละครั้งมาก)
INSERT INTO tb_customers (customer_code, first_name, last_name, zone_id)
VALUES 
    ('WY-010', 'มณีรัตน์', 'คงทอง', 2),
    ('WY-011', 'ประเสริฐ', 'ยิ่งเจริญ', 1),
    ('WY-012', 'วรรณา', 'ดวงดี', 3);

-- แบบที่ 3: คัดลอกข้อมูลจากตารางอื่นมาใส่ (INSERT INTO ... SELECT)
INSERT INTO customer_archive (cus_id, first_name, last_name)
SELECT cus_id, first_name, last_name 
FROM tb_customers 
WHERE status = 'INACTIVE';

-- แบบที่ 4: Upsert (ถ้ามีข้อมูลอยู่แล้วให้อัปเดต ถ้ายังไม่มีให้แทรกใหม่)
INSERT INTO system_settings (setting_key, setting_value)
VALUES ('water_rate', '8.00')
ON DUPLICATE KEY UPDATE setting_value = '8.00';
```

---

### 2.2 คำสั่ง `UPDATE` (การแก้ไขข้อมูล)
```sql
-- 1. แก้ไขข้อมูลแบบระบุเงื่อนไขเฉพาะเจาะจง
UPDATE tb_customers 
SET first_name = 'ณัฐพงษ์', 
    cus_tel = '0891234567'
WHERE cus_id = 5;

-- 2. แก้ไขโดยนำค่าเดิมมาคำนวณ (เช่น ปรับขึ้นเงินเดือน 10%)
UPDATE employees 
SET salary = salary * 1.10 
WHERE department = 'ไอที';

-- ⚠️ คำเตือนอันตราย: หากรัน UPDATE โดยไม่มี WHERE ข้อมูล "ทุกแถวในตาราง" จะถูกเปลี่ยนค่าทั้งหมด!
```

---

### 2.3 คำสั่ง `DELETE` (การลบข้อมูล)
```sql
-- 1. ลบข้อมูลตามเงื่อนไข
DELETE FROM meter_readings 
WHERE payment_status = 'CANCELLED' 
  AND reading_date < '2023-01-01';

-- 2. ลบข้อมูลโดยเชื่อมโยงตาราง (Multi-table Delete)
-- ลบการจดมิเตอร์ของลูกค้าที่อยู่ในโซน 4 ทั้งหมด
DELETE mr 
FROM meter_readings mr
JOIN tb_customers c ON mr.customer_id = c.cus_id
WHERE c.zone_id = 4;
```

---

# หมวดที่ 3: DQL & Operators ทั้งหมด (การเลือกและกรองข้อมูล)

โครงสร้างคำสั่งพื้นฐาน:
```sql
SELECT [DISTINCT] column1, column2, ...
FROM table_name
WHERE condition
GROUP BY column_name
HAVING group_condition
ORDER BY column_name [ASC|DESC]
LIMIT count OFFSET skip_rows;
```

### 3.1 ตัวดำเนินการเปรียบเทียบ (Comparison Operators ครบทุกตัว)
| ตัวดำเนินการ | ความหมาย | ตัวอย่างคำสั่ง SQL | ผลลัพธ์ |
| :---: | :--- | :--- | :--- |
| `=` | เท่ากับ | `WHERE status = 'PAID'` | คัดเฉพาะแถวที่สถานะเป็น PAID |
| `!=` หรือ `<>` | ไม่เท่ากับ | `WHERE zone_id <> 1` | คัดเฉพาะโซนที่ไม่ใช่ 1 |
| `>` | มากกว่า | `WHERE units_used > 50` | คัดหน่วยใช้น้ำเกิน 50 หน่วย |
| `<` | น้อยกว่า | `WHERE salary < 20000` | คัดเงินเดือนต่ำกว่า 2 หมื่น |
| `>=` | มากกว่าหรือเท่ากับ | `WHERE age >= 18` | คัดอายุตั้งแต่ 18 ปีขึ้นไป |
| `<=` | น้อยกว่าหรือเท่ากับ | `WHERE amount <= 500` | คัดยอดเงินไม่เกิน 500 บาท |
| `<=>` | เท่ากับแบบปลอดภัยต่อ NULL (Null-safe equal) | `WHERE parent_id <=> NULL` | จะได้ผลเป็น TRUE เมื่อเทียบกับ NULL |
| `BETWEEN a AND b` | อยู่ในช่วง a ถึง b (รวมจุดขอบ) | `WHERE score BETWEEN 80 AND 100` | เทียบเท่ากับ `score >= 80 AND score <= 100` |
| `NOT BETWEEN` | ไม่อยู่ในช่วง | `WHERE age NOT BETWEEN 13 AND 19` | คัดคนที่ไม่ใช่วัยรุ่น |
| `IN (...)` | มีค่าตรงกับสมาชิกในเซต | `WHERE zone_id IN (1, 3, 5)` | เทียบเท่ากับ `zone=1 OR zone=3 OR zone=5` |
| `NOT IN (...)` | ไม่มีค่าอยู่ในเซต | `WHERE status NOT IN ('EXPIRED', 'VOID')` | ตัดสถานะที่ไม่ต้องการออก |
| `IS NULL` | ตรวจสอบค่าว่าง (ไม่มีข้อมูล) | `WHERE receipt_no IS NULL` | คัดรายการที่ยังไม่ออกใบเสร็จ |
| `IS NOT NULL` | ตรวจสอบว่ามีข้อมูล | `WHERE phone IS NOT NULL` | คัดเฉพาะคนที่มีเบอร์โทรศัพท์ |

---

### 3.2 ตัวดำเนินการตรรกศาสตร์ (Logical Operators)
* **`AND`:** จะเป็นจริงเมื่อ **ทุกเงื่อนไข** เป็นจริงพร้อมกัน
* **`OR`:** จะเป็นจริงเมื่อ **เงื่อนไขใดเงื่อนไขหนึ่ง** เป็นจริง
* **`NOT`:** กลับค่าความจริง (จากจริงเป็นเท็จ จากเท็จเป็นจริง)
* **`XOR`:** Exclusive OR จะเป็นจริงเมื่อเงื่อนไขข้างใดข้างหนึ่งเป็นจริง **เพียงข้างเดียวเท่านั้น** (ถ้าจริงทั้งคู่หรือเท็จทั้งคู่จะได้ FALSE)

---

### 3.3 การจับคู่ข้อความ (String Pattern Matching)
1. **คำสั่ง `LIKE` และ Wildcards:**
   * `%` แทนตัวอักษรใดๆ 0 ตัวขึ้นไป
     * `LIKE 'สม%'` $\rightarrow$ ขึ้นต้นด้วย สม เช่น สมชาย, สมบูรณ์, สม
     * `LIKE '%ทอง'` $\rightarrow$ ลงท้ายด้วย ทอง เช่น บัวทอง, สร้อยทอง
     * `LIKE '%น้ำ%'` $\rightarrow$ มีคำว่า น้ำ อยู่ตรงไหนก็ได้ เช่น ค่าน้ำประปา, น้ำไม่ไหล, ดื่มน้ำ
   * `_` (ขีดล่าง) แทนตัวอักษรใดๆ **1 ตัวอักษรพอดีเป๊ะ**
     * `LIKE 'นาย_บ'` $\rightarrow$ มี 4 ตัวอักษร เช่น นายกบ, นายจบ
     * `LIKE 'WY-___'` $\rightarrow$ รหัสที่มี 3 ตัวอักษรต่อท้าย เช่น WY-001, WY-999
2. **คำสั่ง Regular Expression (`REGEXP` หรือ `RLIKE`):**
   ```sql
   -- หาชื่อที่ขึ้นต้นด้วย A, B หรือ C
   SELECT * FROM customers WHERE first_name REGEXP '^[ABC]';

   -- หาข้อความที่มีแต่ตัวเลขล้วน
   SELECT * FROM documents WHERE doc_code REGEXP '^[0-9]+$';
   ```

---

### 3.4 การจัดเรียง (`ORDER BY`) และการแบ่งหน้า (`LIMIT / OFFSET`)
```sql
-- จัดเรียงหลายคอลัมน์: เรียงตามยอดค้างชำระมากไปน้อย ถ้าเท่ากันให้เรียงชื่อตาม ก-ฮ
SELECT customer_code, fullname, previous_arrears
FROM customers
ORDER BY previous_arrears DESC, fullname ASC;

-- การแบ่งหน้าข้อมูล (Pagination):
-- หน้าที่ 1 (แสดง 10 แถวแรก)
SELECT * FROM meter_readings ORDER BY id ASC LIMIT 10 OFFSET 0;

-- หน้าที่ 2 (ข้าม 10 แถวแรก แสดงแถวที่ 11-20)
SELECT * FROM meter_readings ORDER BY id ASC LIMIT 10 OFFSET 10;
-- หรือใช้ไวยากรณ์ย่อ: LIMIT 10, 10 (LIMIT ข้าม, เอาจำนวน)
```

---

# หมวดที่ 4: ฟังก์ชันเดี่ยว (Built-in Scalar Functions)

Scalar Function คือฟังก์ชันที่รับค่าจากแต่ละแถวแล้ว **คืนผลลัพธ์กลับมา 1 ค่าต่อ 1 แถว**

### 4.1 ฟังก์ชันจัดการข้อความ (String Functions)
```sql
-- 1. CONCAT(): เชื่อมต่อข้อความเข้าด้วยกัน
SELECT CONCAT(first_name, ' ', last_name) AS full_name FROM customers;

-- 2. CONCAT_WS(): เชื่อมข้อความโดยมีตัวคั่นอัตโนมัติ (ข้ามค่า NULL)
SELECT CONCAT_WS(', ', house_no, village, subdistrict, province) AS full_address FROM addresses;

-- 3. UPPER() / LOWER(): แปลงเป็นตัวพิมพ์ใหญ่/พิมพ์เล็ก
SELECT UPPER(username), LOWER(email) FROM users;

-- 4. LENGTH() vs CHAR_LENGTH(): นับไบต์ vs นับจำนวนตัวอักษรจริง
-- คำว่า 'ภาษาไทย' มี 7 ตัวอักษร แต่ UTF-8 กิน 21 ไบต์!
SELECT CHAR_LENGTH('ภาษาไทย'); -- ได้ 7
SELECT LENGTH('ภาษาไทย');      -- ได้ 21

-- 5. SUBSTRING(str, start, length): ตัดข้อความ (ใน SQL เริ่มนับตำแหน่งที่ 1 ไม่ใช่ 0)
SELECT SUBSTRING('WY-00123', 4, 5); -- ได้ '00123'

-- 6. TRIM(), LTRIM(), RTRIM(): ตัดช่องว่างส่วนเกินหน้า-หลัง
SELECT TRIM('   สมชาย ใจดี   '); -- ได้ 'สมชาย ใจดี'

-- 7. REPLACE(str, from, to): แทนที่ข้อความ
SELECT REPLACE('081-234-5678', '-', ''); -- ได้ '0812345678'

-- 8. LPAD(str, len, pad) / RPAD(): เติมตัวอักษรหน้า/หลังให้ครบหลัก
SELECT LPAD(5, 4, '0'); -- ได้ '0005'
```

---

### 4.2 ฟังก์ชันตัวเลขและคณิตศาสตร์ (Math Functions)
```sql
-- 1. ROUND(number, decimals): ปัดเศษทศนิยมตามหลักคณิตศาสตร์
SELECT ROUND(125.456, 2); -- ได้ 125.46
SELECT ROUND(125.454, 2); -- ได้ 125.45

-- 2. CEIL() หรือ CEILING(): ปัดเศษขึ้นเป็นจำนวนเต็มเสมอ
SELECT CEIL(10.01); -- ได้ 11

-- 3. FLOOR(): ปัดเศษทิ้งลงเป็นจำนวนเต็มเสมอ
SELECT FLOOR(10.99); -- ได้ 10

-- 4. ABS(): หาค่าสัมบูรณ์ (ตัดเครื่องหมายลบออก)
SELECT ABS(-500); -- ได้ 500

-- 5. MOD(N, M): การหารเอาเศษ (Modulo หรือ N % M)
SELECT MOD(10, 3); -- ได้ 1
```

---

### 4.3 ฟังก์ชันวันและเวลา (Date & Time Functions — จุดเน้นข้อสอบ!)
```sql
-- 1. ดึงวัน-เวลาปัจจุบันของเซิร์ฟเวอร์
SELECT CURDATE();               -- ได้เฉพาะวันที่ เช่น '2026-10-10'
SELECT CURTIME();               -- ได้เฉพาะเวลา เช่น '02:30:15'
SELECT NOW();                   -- ได้ทั้งวันและเวลา เช่น '2026-10-10 02:30:15'

-- 2. ดึงชิ้นส่วนย่อยของวันที่
SELECT YEAR(NOW());             -- ได้เลขปี เช่น 2026
SELECT MONTH(NOW());            -- ได้เลขเดือน 1-12
SELECT DAY(NOW());              -- ได้เลขวันที่ 1-31
SELECT DAYNAME(NOW());          -- ได้ชื่อวัน เช่น 'Saturday'
SELECT MONTHNAME(NOW());        -- ได้ชื่อเดือน เช่น 'October'

-- 3. การคำนวณอายุงานและผลต่างของวัน (DATEDIFF & TIMESTAMPDIFF)
-- DATEDIFF(date1, date2): คืนค่าจำนวนวัน (date1 - date2)
SELECT DATEDIFF('2024-08-30', '2024-08-20'); -- ได้ 10 วัน

-- TIMESTAMPDIFF(unit, start_date, end_date): คำนวณความต่างตามหน่วย (YEAR, MONTH, DAY)
-- คำนวณอายุงานเต็มปี (ไม่ปัดเศษมั่ว):
SELECT TIMESTAMPDIFF(YEAR, hire_date, CURDATE()) AS exact_years FROM employees;

-- 4. การบวก/ลบวันที่ (DATE_ADD & DATE_SUB)
-- หาว่าอีก 15 วันข้างหน้าคือวันไหน (เช่น กำหนดวันครบกำหนดชำระบิล):
SELECT DATE_ADD(CURDATE(), INTERVAL 15 DAY) AS due_date;
-- หาวันที่ย้อนหลัง 3 เดือน:
SELECT DATE_SUB(CURDATE(), INTERVAL 3 MONTH) AS past_date;

-- 5. การจัดรูปแบบวันที่ (DATE_FORMAT)
-- แปลงเป็น วัน/เดือน/ปี เช่น '25/08/2024'
SELECT DATE_FORMAT(reading_date, '%d/%m/%Y') AS formatted_date FROM meter_readings;
```

---

### 4.4 ฟังก์ชันเงื่อนไขและควบคุมค่าว่าง (Control Flow Functions)
```sql
-- 1. IF(condition, value_if_true, value_if_false): คล้าย IF() ใน Excel
SELECT fullname, units_used,
       IF(units_used > 50, 'ใช้น้ำมากผิดปกติ', 'ใช้น้ำปกติ') AS usage_status
FROM meter_readings;

-- 2. IFNULL(expression, alt_value): ถ้าตัวแรกเป็น NULL ให้ใช้ตัวหลังแทน
SELECT fullname, IFNULL(phone, 'ไม่มีเบอร์โทรศัพท์') AS contact_phone FROM customers;

-- 3. COALESCE(v1, v2, v3, ...): คืนค่า "ตัวแรกที่ไม่ใช่ NULL"
SELECT COALESCE(mobile_phone, home_phone, office_phone, 'ไม่มีเบอร์ติดต่อ') FROM users;

-- 4. CASE WHEN ... THEN ... ELSE ... END (ทรงพลังที่สุด รองรับหลายเงื่อนไข)
SELECT customer_code, units_used,
       CASE 
           WHEN units_used <= 10 THEN 'อัตราขั้นต่ำ (เหมาจ่าย)'
           WHEN units_used BETWEEN 11 AND 30 THEN 'อัตราปกติ'
           WHEN units_used BETWEEN 31 AND 50 THEN 'อัตราสูง'
           ELSE 'อัตราใช้น้ำเชิงพาณิชย์/รั่วซึม'
       END AS tariff_category
FROM meter_readings;
```

---

# หมวดที่ 5: Aggregate Functions & Grouping (สถิติและการจัดกลุ่ม)

Aggregate Function รับข้อมูลหลายๆ แถวเข้ามาคำนวณ แล้ว **ยุบรวมเหลือผลลัพธ์เพียงค่าเดียว**

### 5.1 ฟังก์ชันสถิติหลัก 5 ตัว
* `COUNT(*)`: นับจำนวนแถวทั้งหมดในตาราง (รวมถึงแถวที่มีค่า NULL)
* `COUNT(column)`: นับเฉพาะแถวที่คอลัมน์นั้น **มีข้อมูล (ไม่นับแถวที่เป็น NULL)**
* `COUNT(DISTINCT column)`: นับเฉพาะค่าที่ไม่ซ้ำกัน และไม่ใช่ NULL
* `SUM(column)`: ผลรวมตัวเลข (ข้ามแถวที่เป็น NULL อัตโนมัติ)
* `AVG(column)`: ค่าเฉลี่ยเลขคณิต (ข้ามแถวที่เป็น NULL อัตโนมัติ)
* `MIN(column)` / `MAX(column)`: ค่าน้อยที่สุด และ ค่ามากที่สุด (ใช้ได้ทั้งตัวเลข วันที่ และตัวหนังสือ)
* `GROUP_CONCAT(column SEPARATOR ', ')`: นำข้อความในกลุ่มมาร้อยต่อกันเป็นบรรทัดเดียว!

---

### 5.2 กฎเหล็ก `GROUP BY` และการเปรียบเทียบ `WHERE` vs `HAVING`
```sql
-- กรองแถวก่อนรวมด้วย WHERE และกรองกลุ่มหลังรวมด้วย HAVING
SELECT 
    z.zone_name,
    COUNT(c.cus_id) AS total_customers,
    SUM(mr.units_used) AS total_units,
    ROUND(AVG(mr.units_used), 2) AS avg_units
FROM tb_customers c
JOIN tb_zone z ON c.zone_id = z.zone_id
JOIN meter_readings mr ON c.cus_id = mr.customer_id
WHERE mr.payment_status = 'PAID'          -- 1. กรองเฉพาะบิลที่จ่ายแล้ว (Row-level)
GROUP BY z.zone_id, z.zone_name           -- 2. จัดกลุ่มตามโซน
HAVING SUM(mr.units_used) > 100           -- 3. เอาเฉพาะโซนที่มียอดใช้น้ำรวมเกิน 100 หน่วย (Group-level)
ORDER BY total_units DESC;
```

---

# หมวดที่ 6: Relational JOINs (การเชื่อมโยงมิติข้อมูลครบทุกแบบ)

การดึงข้อมูลจากตารางที่สัมพันธ์กันผ่าน Key

### 6.1 รูปแบบการเชื่อมโยงตารางทั้งหมด
1. **`INNER JOIN`:** คืนเฉพาะแถวที่มีคีย์ตรงกันทั้งสองตาราง (จุดตัด $A \cap B$)
   ```sql
   SELECT c.fullname, mr.units_used, mr.grand_total
   FROM tb_customers c
   INNER JOIN meter_readings mr ON c.cus_id = mr.customer_id;
   ```
2. **`LEFT (OUTER) JOIN`:** คืนข้อมูลตารางซ้าย (ตารางหลัก) ทุกแถว หากตารางขวาไม่มีคู่สัมพันธ์ ให้เติม `NULL`
   ```sql
   -- หาว่าลูกค้าคนไหน "ไม่เคยมีประวัติการจดมิเตอร์เลย" (Anti-Join Pattern)
   SELECT c.cus_id, c.fullname
   FROM tb_customers c
   LEFT JOIN meter_readings mr ON c.cus_id = mr.customer_id
   WHERE mr.id IS NULL;
   ```
3. **`RIGHT (OUTER) JOIN`:** คืนข้อมูลตารางขวาทุกแถว (พฤติกรรมเหมือน LEFT JOIN แต่สลับฝั่ง)
4. **`CROSS JOIN`:** Cartesian Product นำทุกแถวของตารางแรก ไปจับคู่กับทุกแถวของตารางที่สอง ($M \times N$ แถว)
   ```sql
   SELECT z.zone_name, r.rate_per_unit
   FROM tb_zone z
   CROSS JOIN tariff_rates r;
   ```
5. **`SELF JOIN`:** การนำตารางเดิมมา JOIN เข้ากับตัวเอง (มักใช้กับความสัมพันธ์ลำดับชั้น เช่น ลูกน้อง-หัวหน้า)
   ```sql
   SELECT emp.fullname AS employee_name, boss.fullname AS manager_name
   FROM tb_users emp
   LEFT JOIN tb_users boss ON emp.manager_id = boss.user_id;
   ```
6. **`FULL OUTER JOIN` (ใน MySQL จำลองด้วย `UNION`):**
   ```sql
   SELECT c.cus_id, c.fullname, mr.id AS bill_id
   FROM tb_customers c
   LEFT JOIN meter_readings mr ON c.cus_id = mr.customer_id
   UNION
   SELECT c.cus_id, c.fullname, mr.id AS bill_id
   FROM tb_customers c
   RIGHT JOIN meter_readings mr ON c.cus_id = mr.customer_id;
   ```

---

# หมวดที่ 7: Set Operations (การรวมเซตข้อมูล)

ตารางที่จะทำ Set Operations ต้องเป็น **Union-Compatible** (มีจำนวนคอลัมน์เท่ากัน และลำดับคอลัมน์มีชนิดข้อมูลตรงกัน)

* **`UNION`:** รวมข้อมูล 2 ชุดเข้าด้วยกัน **และคัดกรองแถวที่ซ้ำซ้อนออก** (เหมือน `DISTINCT`)
* **`UNION ALL`:** รวมข้อมูล 2 ชุดเข้าด้วยกัน **โดยไม่ตัดแถวซ้ำ** (ทำงานเร็วกว่า `UNION` มากเพราะไม่ต้องเสียเวลา Sort หาตัวซ้ำ)
* **`INTERSECT`:** หาเฉพาะแถวที่ปรากฏในทั้งสองชุดข้อมูล
* **`EXCEPT` หรือ `MINUS`:** เอาเฉพาะแถวที่อยู่ในชุดแรก แต่ไม่อยู่ในชุดที่สอง

```sql
-- ตัวอย่าง UNION: รวมรายชื่อเบอร์โทรทั้งหมดของทั้งลูกค้าและเจ้าหน้าที่
SELECT fullname, cus_tel AS phone, 'Customer' AS user_type FROM tb_customers
UNION
SELECT fullname, phone, 'Staff' AS user_type FROM tb_users;
```

---

# หมวดที่ 8: Subqueries (คิวรีย่อยระดับโปร)

Subquery คือคำสั่ง `SELECT` ที่ซ้อนอยู่ภายในคำสั่ง SQL อื่น

### 8.1 ชนิดของ Subqueries
1. **Scalar Subquery:** คืนค่าออกมาเพียง 1 แถว 1 คอลัมน์ (1 ค่าเดี่ยว) สามารถวางไว้ใน `SELECT`, `WHERE`, หรือ `HAVING` ได้
   ```sql
   -- หาบิลค่าน้ำที่มียอดสูงกว่า "ค่าเฉลี่ยของทั้งหมู่บ้าน"
   SELECT id, customer_id, grand_total
   FROM meter_readings
   WHERE grand_total > (SELECT AVG(grand_total) FROM meter_readings);
   ```
2. **Multi-row Subquery (`IN`, `ANY`, `ALL`):** คืนค่าเป็นรายการหลายแถว
   ```sql
   -- หาข้อมูลลูกค้าที่อยู่ในโซนที่มียอดค้างชำระ
   SELECT fullname FROM tb_customers
   WHERE zone_id IN (
       SELECT DISTINCT zone_id FROM tb_customers c
       JOIN meter_readings mr ON c.cus_id = mr.customer_id
       WHERE mr.payment_status = 'UNPAID'
   );
   ```
3. **Correlated Subquery:** คิวรีย่อยที่มีการอ้างอิงคอลัมน์จากตารางด้านนอก (ประมวลผลซ้ำทีละแถว)
   ```sql
   -- หาพนักงานที่เงินเดือนสูงกว่าค่าเฉลี่ยของแผนกที่ตนเองสังกัด
   SELECT e.emp_name, e.dept_id, e.salary
   FROM employees e
   WHERE e.salary > (
       SELECT AVG(sub.salary) 
       FROM employees sub 
       WHERE sub.dept_id = e.dept_id
   );
   ```
4. **`EXISTS` และ `NOT EXISTS`:** ตรวจสอบว่ามีข้อมูลส่งกลับมาอย่างน้อย 1 แถวหรือไม่ (เร็วกว่า `IN` ในกรณีตารางขนาดใหญ่ เพราะหยุดค้นหาทันทีที่เจอตัวแรก)
   ```sql
   -- หาลูกค้าที่เคยแจ้งเรื่องร้องเรียนท่อแตก
   SELECT c.fullname FROM tb_customers c
   WHERE EXISTS (
       SELECT 1 FROM service_tickets t 
       WHERE t.house_no = c.house_id
   );
   ```

---

# หมวดที่ 9: CTEs & Window Functions (บัตรผ่านสู่ Senior SQL)

### 9.1 Common Table Expressions (`WITH ... AS`)
CTE ช่วยแยกตารางชั่วคราวออกมาด้านบน ทำให้อ่านเข้าใจง่ายกว่า Subquery ที่ซ้อนกันหลายชั้น:

```sql
WITH UnpaidBills AS (
    SELECT customer_id, SUM(grand_total) AS total_debt
    FROM meter_readings
    WHERE payment_status = 'UNPAID'
    GROUP BY customer_id
),
HighDebtCustomers AS (
    SELECT customer_id, total_debt
    FROM UnpaidBills
    WHERE total_debt >= 500
)
SELECT c.fullname, c.cus_tel, h.total_debt
FROM HighDebtCustomers h
JOIN tb_customers c ON h.customer_id = c.cus_id
ORDER BY h.total_debt DESC;
```

---

### 9.2 Window Functions (การคำนวณข้ามแถวโดยไม่ยุบรวมแถว)
* **การจัดอันดับ (Ranking):**
  * `ROW_NUMBER()`: รันลำดับ 1, 2, 3, 4 ไม่สนค่าซ้ำ
  * `RANK()`: 1, 2, 2, 4 (ถ้าค่าซ้ำ อันดับเท่ากัน แต่อันดับถัดไปกระโดดข้าม)
  * `DENSE_RANK()`: 1, 2, 2, 3 (ถ้าค่าซ้ำ อันดับเท่ากัน แต่อันดับถัดไป**ไม่กระโดด**)
* **การดึงค่าแถวข้างเคียง (Offset):**
  * `LAG(col, N, default)`: ดึงค่าของแถว **ก่อนหน้า** N แถว
  * `LEAD(col, N, default)`: ดึงค่าของแถว **ถัดไป** N แถว
* **การคำนวณสะสม (Running Total):**
  ```sql
  SELECT payment_date, amount_paid,
         SUM(amount_paid) OVER (ORDER BY payment_date ASC) AS cumulative_income
  FROM receipts;
  ```

---

# หมวดที่ 10: ธุรกรรมและความปลอดภัย (TCL & DCL)

### 10.1 Transactions & คุณสมบัติ ACID (ข้อสอบทฤษฎีออกบ่อย!)
ธุรกรรมคือชุดคำสั่งฐานข้อมูลที่ต้องทำงานร่วมกันเสร็จสมบูรณ์ 100% หรือไม่ทำเลย
* **A - Atomicity (ความแบ่งแยกไม่ได้):** ต้องทำสำเร็จทั้งหมด หากมีคำสั่งใดล้มเหลว ต้อง Rollback คืนค่าเดิมทั้งหมด
* **C - Consistency (ความถูกต้องสอดคล้อง):** ข้อมูลต้องถูกต้องตามกฎและเงื่อนไข (Constraints) ทุกประการ
* **I - Isolation (ความโดดเดี่ยว):** การทำงานพร้อมกันของหลายคนต้องไม่กวนข้อมูลกัน
* **D - Durability (ความคงทนถาวร):** เมื่อ Commit สำเร็จแล้ว ข้อมูลต้องคงอยู่ถาวรแม้ไฟดับ

```sql
-- ตัวอย่างการตัดเงินและบันทึกใบเสร็จ (ต้องอยู่ใน Transaction เดียวกัน)
START TRANSACTION;

UPDATE meter_readings 
SET payment_status = 'PAID', payment_date = CURDATE()
WHERE id = 105;

INSERT INTO payment_logs (reading_id, amount, paid_at)
VALUES (105, 250.00, NOW());

-- หากทุกอย่างเรียบร้อยให้ยืนยัน:
COMMIT;

-- หากเกิดข้อผิดพลาด ให้ยกเลิกทั้งหมด:
-- ROLLBACK;
```

---

# หมวดที่ 11: Views, Indexes & Triggers

```sql
-- 1. CREATE VIEW: สร้างตารางเสมือนเพื่อความปลอดภัยและง่ายต่อการใช้งาน
CREATE OR REPLACE VIEW v_debtors_summary AS
SELECT c.cus_id, c.fullname, c.house_id, z.zone_name, mr.grand_total, mr.reading_date
FROM tb_customers c
JOIN tb_zone z ON c.zone_id = z.zone_id
JOIN meter_readings mr ON c.cus_id = mr.customer_id
WHERE mr.payment_status = 'UNPAID';

-- 2. CREATE INDEX: สร้างดัชนีเพื่อเร่งความเร็วในการสืบค้นข้อมูล
CREATE INDEX idx_reading_status_date ON meter_readings(payment_status, reading_date);

-- 3. CREATE TRIGGER: สร้างตัวดักจับเหตุการณ์อัตโนมัติ
-- บันทึกประวัติทันทีเมื่อมีการอัปเดตเลขมิเตอร์
DELIMITER //
CREATE TRIGGER trg_after_reading_update
AFTER UPDATE ON meter_readings
FOR EACH ROW
BEGIN
    IF OLD.current_reading <> NEW.current_reading THEN
        INSERT INTO audit_logs(table_name, record_id, action, changed_at)
        VALUES ('meter_readings', NEW.id, 'METER_CHANGED', NOW());
    END IF;
END //
DELIMITER ;
```

---

# หมวดที่ 12: คลังตัวอย่างโจทย์ 25 ข้อ พร้อมเฉลยละเอียดแบบ Step-by-Step

*(คลังโจทย์จำลองจากแนวข้อสอบจริงและงานระบบประปา/ธุรกิจ)*

#### โจทย์ที่ 1: การเลือกคอลัมน์และคำนวณค่าบริการ (SELECT, Expressions)
* **โจทย์:** จงแสดงรหัสลูกค้า (`customer_code`), ชื่อ-นามสกุล (`fullname`), และยอดค่าน้ำที่คำนวณจากหน่วยใช้น้ำคูณด้วยอัตราหน่วยละ 7 บาท โดยตั้งชื่อคอลัมน์ว่า `calculated_charge`
```sql
SELECT customer_code, fullname, (units_used * 7.00) AS calculated_charge
FROM meter_readings;
```

#### โจทย์ที่ 2: การคัดกรองข้อมูลด้วยช่วงตัวเลข (BETWEEN)
* **โจทย์:** จงหารายการจดมิเตอร์น้ำที่มียอดใช้น้ำอยู่ระหว่าง 15 ถึง 30 หน่วย ในรอบบิลรหัส '8-2567'
```sql
SELECT * FROM meter_readings
WHERE billing_cycle_id = '8-2567'
  AND units_used BETWEEN 15.00 AND 30.00;
```

#### โจทย์ที่ 3: การใช้ตรรกะร่วม AND / OR (การใส่วงเล็บ)
* **โจทย์:** จงแสดงรายชื่อสมาชิกที่อยู่ในโซน 1 หรือโซน 2 ที่มีสถานะมิเตอร์เป็น 'ACTIVE' และติดตั้งมิเตอร์ขนาด '5/8 นิ้ว'
```sql
SELECT fullname, zone_id, meter_size, status
FROM customers
WHERE (zone_id = 1 OR zone_id = 2)
  AND status = 'ACTIVE'
  AND meter_size = 'มิเตอร์ขนาด 5/8 นิ้ว';
```

#### โจทย์ที่ 4: การค้นหาข้อความด้วย LIKE Wildcards
* **โจทย์:** จงหาข้อร้องเรียนแจ้งซ่อมท่อ (`service_tickets`) ที่มีคำว่า 'แตก' หรือ 'รั่ว' อยู่ในประเภทปัญหา (`issue_type`)
```sql
SELECT ticket_no, reporter_name, issue_type, description
FROM service_tickets
WHERE issue_type LIKE '%แตก%' OR issue_type LIKE '%รั่ว%';
```

#### โจทย์ที่ 5: การตรวจสอบค่าว่างที่ถูกต้องตามหลัก 3VL
* **โจทย์:** จงแสดงรายการบิลค่าน้ำทั้งหมดที่ยังไม่ได้ออกเลขที่ใบเสร็จรับเงิน (`receipt_no`)
```sql
SELECT id, customer_id, grand_total, payment_status
FROM meter_readings
WHERE receipt_no IS NULL;
```

#### โจทย์ที่ 6: การคำนวณอายุงาน/ระยะเวลาด้วยฟังก์ชันวันที่
* **โจทย์:** จงแสดงชื่อลูกค้า วันที่ติดตั้งมิเตอร์ (`meter_installed_date`) และจำนวนปีที่ติดตั้งจนถึงปี 2024 เฉพาะผู้ที่ติดตั้งมาแล้วมากกว่าหรือเท่ากับ 3 ปี
```sql
SELECT fullname, meter_installed_date,
       (2024 - YEAR(meter_installed_date)) AS installed_years
FROM customers
WHERE (2024 - YEAR(meter_installed_date)) >= 3;
```

#### โจทย์ที่ 7: การคำนวณอายุงานเทียบกับวันปัจจุบัน (TIMESTAMPDIFF)
* **โจทย์:** จงแสดงรหัสเจ้าหน้าที่ ชื่อ และอายุการทำงานเต็มปีเทียบกับวันปัจจุบันของระบบ โดยเรียงจากคนที่ทำงานนานที่สุดลงมา
```sql
SELECT user_id, fullname, created_at,
       TIMESTAMPDIFF(YEAR, created_at, CURDATE()) AS service_years
FROM tb_users
WHERE role = 'staff'
ORDER BY service_years DESC;
```

#### โจทย์ที่ 8: การหาผลรวมและค่าเฉลี่ยด้วย GROUP BY
* **โจทย์:** จงสรุปปริมาณการใช้น้ำรวม (`total_units`) และค่าน้ำรวม (`total_revenue`) แยกตามแต่ละโซน
```sql
SELECT c.zone_id, 
       SUM(mr.units_used) AS total_units,
       SUM(mr.current_total) AS total_revenue
FROM meter_readings mr
JOIN tb_customers c ON mr.customer_id = c.cus_id
GROUP BY c.zone_id;
```

#### โจทย์ที่ 9: การกรองผลกลุ่มด้วย HAVING
* **โจทย์:** จงหาโซนที่มีลูกหนี้ค้างชำระรวมกันมากกว่าหรือเท่ากับ 3 ราย
```sql
SELECT c.zone_id, COUNT(mr.id) AS unpaid_count
FROM meter_readings mr
JOIN tb_customers c ON mr.customer_id = c.cus_id
WHERE mr.payment_status = 'UNPAID'
GROUP BY c.zone_id
HAVING COUNT(mr.id) >= 3;
```

#### โจทย์ที่ 10: การเชื่อมโยง 3 ตารางด้วย INNER JOIN
* **โจทย์:** จงแสดงรหัสบิล, ชื่อ-นามสกุลลูกค้า, ชื่อโซน (`zone_name`), และยอดชำระสุทธิ (`grand_total`)
```sql
SELECT mr.id AS bill_id, c.fullname, z.zone_name, mr.grand_total
FROM meter_readings mr
JOIN tb_customers c ON mr.customer_id = c.cus_id
JOIN tb_zone z ON c.zone_id = z.zone_id;
```

#### โจทย์ที่ 11: การหาข้อมูลที่ไม่มีคู่ (Anti-Join ด้วย LEFT JOIN)
* **โจทย์:** จงหารายชื่อลูกค้าที่ไม่มีประวัติการใช้น้ำในรอบบิลปัจจุบันเลย
```sql
SELECT c.cus_id, c.fullname, c.house_id
FROM tb_customers c
LEFT JOIN meter_readings mr ON c.cus_id = mr.customer_id AND mr.billing_cycle_id = 1
WHERE mr.id IS NULL;
```

#### โจทย์ที่ 12: การใช้ CASE WHEN สร้างการจัดกลุ่มข้อมูล
* **โจทย์:** จงแสดงชื่อลูกค้า หน่วยใช้น้ำ และระดับการใช้น้ำ: ถ้าน้อยกว่า 10 ให้แสดง 'น้อย', 10-25 ให้แสดง 'ปานกลาง', เกิน 25 ให้แสดง 'สูง'
```sql
SELECT c.fullname, mr.units_used,
       CASE 
           WHEN mr.units_used < 10 THEN 'น้อย'
           WHEN mr.units_used BETWEEN 10 AND 25 THEN 'ปานกลาง'
           ELSE 'สูง'
       END AS consumption_level
FROM meter_readings mr
JOIN tb_customers c ON mr.customer_id = c.cus_id;
```

#### โจทย์ที่ 13: Conditional Aggregation (Pivot ตารางในคำสั่งเดียว)
* **โจทย์:** จงสรุปจำนวนบิลในแต่ละโซน โดยแยกเป็น 2 คอลัมน์: บิลที่จ่ายแล้ว (`paid_bills`) และบิลที่ค้างชำระ (`unpaid_bills`)
```sql
SELECT c.zone_id,
       COUNT(CASE WHEN mr.payment_status = 'PAID' THEN 1 END) AS paid_bills,
       COUNT(CASE WHEN mr.payment_status = 'UNPAID' THEN 1 END) AS unpaid_bills,
       COUNT(mr.id) AS total_bills
FROM meter_readings mr
JOIN tb_customers c ON mr.customer_id = c.cus_id
GROUP BY c.zone_id;
```

#### โจทย์ที่ 14: Subquery ใน WHERE (เปรียบเทียบกับค่าสถิติรวม)
* **โจทย์:** จงหาข้อมูลบิลค่าน้ำที่มียอดใช้น้ำสูงที่สุดในระบบ
```sql
SELECT * FROM meter_readings
WHERE units_used = (SELECT MAX(units_used) FROM meter_readings);
```

#### โจทย์ที่ 15: Subquery ด้วย NOT IN และข้อควรระวัง
* **โจทย์:** จงหารายชื่อลูกค้าที่ไม่มีหนี้ค้างชำระเลยแม้แต่บาทเดียว
```sql
SELECT cus_id, fullname FROM customers
WHERE cus_id NOT IN (
    SELECT customer_id FROM meter_readings 
    WHERE previous_arrears > 0 AND customer_id IS NOT NULL
);
```

#### โจทย์ที่ 16: การหาเงินเดือน/ยอดค่าน้ำ สูงสุดอันดับ 2
* **โจทย์:** จงหายอดเงินค่าน้ำที่สูงที่สุดเป็นอันดับที่ 2 ของหมู่บ้าน
```sql
SELECT MAX(grand_total) AS second_highest_bill
FROM meter_readings
WHERE grand_total < (SELECT MAX(grand_total) FROM meter_readings);
```

#### โจทย์ที่ 17: การใช้ Subquery ใน FROM (Derived Table)
* **โจทย์:** จงหาค่าเฉลี่ยของ "ยอดเงินรวมต่อโซน" ของทุกโซนในหมู่บ้าน
```sql
SELECT AVG(zone_total) AS average_zone_revenue
FROM (
    SELECT c.zone_id, SUM(mr.grand_total) AS zone_total
    FROM meter_readings mr
    JOIN tb_customers c ON mr.customer_id = c.cus_id
    GROUP BY c.zone_id
) AS ZoneSummaries;
```

#### โจทย์ที่ 18: การจัดอันดับด้วย ROW_NUMBER() และ DENSE_RANK()
* **โจทย์:** จงจัดอันดับผู้ที่ใช้น้ำมากที่สุด 3 อันดับแรกในแต่ละโซน
```sql
WITH RankedUsage AS (
    SELECT c.zone_id, c.fullname, mr.units_used,
           DENSE_RANK() OVER (
               PARTITION BY c.zone_id 
               ORDER BY mr.units_used DESC
           ) AS usage_rank
    FROM meter_readings mr
    JOIN tb_customers c ON mr.customer_id = c.cus_id
)
SELECT * FROM RankedUsage
WHERE usage_rank <= 3;
```

#### โจทย์ที่ 19: การคำนวณผลต่างเทียบกับรอบก่อนหน้าด้วย LAG()
* **โจทย์:** จงแสดงประวัติการใช้น้ำของลูกค้าแต่ละคน พร้อมดึงยอดใช้น้ำของเดือนก่อนหน้ามาเปรียบเทียบ
```sql
SELECT customer_id, reading_date, units_used,
       LAG(units_used, 1, 0) OVER (
           PARTITION BY customer_id 
           ORDER BY reading_date ASC
       ) AS prev_month_units,
       (units_used - LAG(units_used, 1, 0) OVER (
           PARTITION BY customer_id 
           ORDER BY reading_date ASC
       )) AS unit_difference
FROM meter_readings;
```

#### โจทย์ที่ 20: การคำนวณยอดเงินสะสม (Running Total)
* **โจทย์:** จงแสดงยอดจัดเก็บเงินค่าน้ำรายวัน พร้อมยอดสะสมรวมตั้งแต่วันแรกจนถึงปัจจุบัน
```sql
SELECT payment_date, SUM(amount_paid) AS daily_income,
       SUM(SUM(amount_paid)) OVER (ORDER BY payment_date ASC) AS running_total_income
FROM meter_readings
WHERE payment_status = 'PAID' AND payment_date IS NOT NULL
GROUP BY payment_date;
```

#### โจทย์ที่ 21: การรวมเซตข้อมูลด้วย UNION ALL
* **โจทย์:** จงรวมรายการเบิกจ่ายของกองทุนประปา (`vouchers`) และรายจ่ายซ่อมบำรุงในคำร้อง (`service_tickets`) เป็นบัญชีค่าใช้จ่ายรวมหน้าเดียว
```sql
SELECT voucher_date AS exp_date, voucher_type AS exp_type, amount, recipient_name AS payee
FROM payment_vouchers
UNION ALL
SELECT DATE(resolved_at) AS exp_date, issue_type AS exp_type, repair_cost AS amount, reporter_name AS payee
FROM service_tickets
WHERE status = 'RESOLVED' AND repair_cost > 0
ORDER BY exp_date DESC;
```

#### โจทย์ที่ 22: การจำลอง Relational Division (ซื้อครบทุกชิ้น / เรียนครบทุกวิชา)
* **โจทย์:** จงหารายชื่อลูกค้าที่จ่ายค่าน้ำครบทุกรอบบิลที่มีในระบบ (ไม่เคยค้างแม้แต่งวดเดียว)
```sql
SELECT c.cus_id, c.fullname
FROM tb_customers c
WHERE NOT EXISTS (
    -- หารอบบิลที่ลูกค้าคนนี้ "ยังไม่ได้จ่าย"
    SELECT b.id FROM billing_cycles b
    WHERE NOT EXISTS (
        SELECT 1 FROM meter_readings mr
        WHERE mr.billing_cycle_id = b.id 
          AND mr.customer_id = c.cus_id
          AND mr.payment_status = 'PAID'
    )
);
```

#### โจทย์ที่ 23: การอัปเดตข้อมูลข้ามตาราง (UPDATE with JOIN)
* **โจทย์:** จงอัปเดตสถานะบิลค่าน้ำใน `meter_readings` ให้เป็น 'PAID' สำหรับลูกค้าทุกคนที่ชำระผ่านระบบ QR Code แล้วมียอดเงินตรงกัน
```sql
UPDATE meter_readings mr
JOIN promptpay_transactions pt ON mr.id = pt.bill_id
SET mr.payment_status = 'PAID',
    mr.payment_date = pt.transaction_date,
    mr.receipt_no = pt.ref_no
WHERE pt.status = 'SUCCESS' AND mr.payment_status = 'UNPAID';
```

#### โจทย์ที่ 24: SARGable Optimization (เขียนเงื่อนไขให้ใช้ Index)
* **โจทย์:** ปรับคำสั่ง `WHERE YEAR(reading_date) = 2024` ให้เป็นรูปแบบ SARGable ที่ใช้ Index บนฟิลด์ `reading_date` ได้เต็มประสิทธิภาพ
```sql
-- ❌ ก่อนปรับ (ไม่ใช้ Index, ทำงานช้า):
SELECT * FROM meter_readings WHERE YEAR(reading_date) = 2024;

-- ✅ หลังปรับ (SARGable ใช้ Range Scan บน B-Tree Index ทันที):
SELECT * FROM meter_readings 
WHERE reading_date >= '2024-01-01' AND reading_date < '2025-01-01';
```

#### โจทย์ที่ 25: Recursive CTE (การประมวลผลลำดับขั้นไม่จำกัด)
* **โจทย์:** จงแสดงผังการรายงานตัวของเจ้าหน้าที่การประปา จากประธานกองทุน (id=1) ลงไปหาคณะกรรมการและเจ้าหน้าที่จดมิเตอร์
```sql
WITH RECURSIVE StaffTree AS (
    SELECT user_id, fullname, role, manager_id, 1 AS depth_level,
           CAST(fullname AS CHAR(500)) AS org_chain
    FROM tb_users
    WHERE user_id = 1
    
    UNION ALL
    
    SELECT u.user_id, u.fullname, u.role, u.manager_id, st.depth_level + 1,
           CONCAT(st.org_chain, ' ➔ ', u.fullname)
    FROM tb_users u
    JOIN StaffTree st ON u.manager_id = st.user_id
)
SELECT * FROM StaffTree ORDER BY depth_level, user_id;
```

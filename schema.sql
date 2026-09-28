-- ====================================================================
-- โครงสร้างฐานข้อมูล: ระบบบริหารจัดการการประปาหมู่บ้านวังยาง
-- Village Water Supply Management System (Wang Yang Village)
-- Database Engine: SQLite / PostgreSQL / MySQL Compatible
-- ====================================================================

-- 1. ตารางข้อมูลผู้ใช้น้ำ (Customers / Water Users)
CREATE TABLE IF NOT EXISTS customers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_code VARCHAR(30) NOT NULL UNIQUE,       -- รหัสผู้ใช้น้ำ เช่น WY-001
    seq_no INTEGER NOT NULL,                         -- ลำดับตามเส้นทางจดมิเตอร์
    first_name VARCHAR(100) NOT NULL,                -- ชื่อ
    last_name VARCHAR(100) NOT NULL,                 -- สกุล
    house_no VARCHAR(50) NOT NULL,                   -- บ้านเลขที่ เช่น 45/1 หมู่ 3
    zone VARCHAR(100) NOT NULL,                      -- โซน/กลุ่ม เช่น โซนเหนือ, หมู่ 3 ซอยวัด
    phone VARCHAR(30),                               -- เบอร์โทรศัพท์
    meter_serial VARCHAR(50),                        -- หมายเลขเครื่องวัดน้ำ/ซีเรียล
    meter_installed_date DATE,                       -- วันที่ติดตั้ง
    status VARCHAR(20) DEFAULT 'ACTIVE',             -- ACTIVE (ปกติ), SUSPENDED (ระงับ), INACTIVE (ยกเลิก)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT                                       -- หมายเหตุ
);

-- 2. ตารางอัตราค่าน้ำตามช่วงเวลา (Tariff Rates & Policy Versioning)
CREATE TABLE IF NOT EXISTS tariff_rates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,                      -- เช่น มติที่ประชุมหมู่บ้าน ปรับค่าน้ำ 7 บาท
    rate_per_unit DECIMAL(10, 2) NOT NULL,           -- อัตราค่าน้ำต่อหน่วย (บาท/ลบ.ม.) เช่น 6.00, 7.00, 8.00
    maintenance_fee DECIMAL(10, 2) NOT NULL DEFAULT 10.00, -- ค่าบำรุงรักษามิเตอร์ประจำเดือน (บาท/เดือน)
    effective_from DATE NOT NULL,                    -- วันที่มีผลบังคับใช้
    effective_to DATE,                               -- วันที่สิ้นสุด (NULL หากยังใช้อยู่)
    is_active BOOLEAN DEFAULT 1,                     -- สถานะการใช้งาน
    resolution_details TEXT                          -- รายละเอียดมติที่ประชุม
);

-- 3. ตารางรอบบิลประจำเดือน (Billing Cycles / Monthly Periods)
CREATE TABLE IF NOT EXISTS billing_cycles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cycle_code VARCHAR(20) NOT NULL UNIQUE,          -- เช่น 8-2567 (เดือน 8 พ.ศ. 2567)
    month INTEGER NOT NULL,                          -- 1 - 12
    year_be INTEGER NOT NULL,                        -- ปี พ.ศ. เช่น 2567
    reading_start_date DATE NOT NULL,                -- วันที่เริ่มจด
    reading_end_date DATE NOT NULL,                  -- วันที่สิ้นสุดการจด
    due_date DATE NOT NULL,                          -- กำหนดชำระเงิน
    tariff_rate_id INTEGER NOT NULL REFERENCES tariff_rates(id),
    status VARCHAR(20) DEFAULT 'OPEN',               -- OPEN (เปิดรับข้อมูล), LOCKED (ประมวลผล), CLOSED (ปิดยอด)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 4. ตารางบันทึกการจดมิเตอร์และการคำนวณยอด (Meter Readings & Invoices)
CREATE TABLE IF NOT EXISTS meter_readings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    billing_cycle_id INTEGER NOT NULL REFERENCES billing_cycles(id),
    customer_id INTEGER NOT NULL REFERENCES customers(id),
    
    -- ข้อมูลมิเตอร์
    previous_reading DECIMAL(10, 2) NOT NULL,        -- เลขมิเตอร์ครั้งก่อน (Previous)
    current_reading DECIMAL(10, 2) NOT NULL,         -- เลขมิเตอร์ครั้งหลัง (Current)
    units_used DECIMAL(10, 2) NOT NULL,              -- จำนวนหน่วยที่ใช้ (Current - Previous)
    
    -- การคำนวณเงินประจำงวด
    rate_per_unit DECIMAL(10, 2) NOT NULL,           -- อัตราต่อหน่วยในงวดนี้
    water_charge DECIMAL(10, 2) NOT NULL,            -- ค่าน้ำตามหน่วย (units * rate)
    maintenance_fee DECIMAL(10, 2) NOT NULL,         -- ค่าบำรุงรักษามิเตอร์
    current_total DECIMAL(10, 2) NOT NULL,           -- ยอดเงินงวดนี้ (water_charge + maintenance_fee)
    
    -- การจัดการยอดค้างชำระย้อนหลัง
    previous_arrears DECIMAL(10, 2) DEFAULT 0.00,    -- ยอดค้างชำระยกมาจากรอบก่อน
    grand_total DECIMAL(10, 2) NOT NULL,             -- ยอดรวมที่ต้องชำระ (current_total + previous_arrears)
    
    -- สถานะการชำระเงินและใบเสร็จ
    amount_paid DECIMAL(10, 2) DEFAULT 0.00,         -- จำนวนเงินที่ชำระจริง
    remaining_balance DECIMAL(10, 2) DEFAULT 0.00,   -- ยอดคงเหลือค้างชำระ
    payment_status VARCHAR(20) DEFAULT 'UNPAID',     -- UNPAID (ยังไม่จ่าย), PARTIAL (จ่ายบางส่วน), PAID (จ่ายครบแล้ว)
    payment_date DATE,                               -- วันที่รับชำระ
    receipt_no VARCHAR(50),                          -- เลขที่ใบเสร็จรับเงิน เช่น 8-2567/541
    reading_date DATE NOT NULL,                      -- วันที่จดมิเตอร์
    reader_name VARCHAR(100),                        -- เจ้าหน้าที่ผู้จดมิเตอร์
    remarks TEXT,                                    -- หมายเหตุ เช่น เปลี่ยนมิเตอร์ใหม่, มาตรชำรุด
    
    UNIQUE(billing_cycle_id, customer_id)
);

-- 5. ตารางประวัติการชำระเงินและออกใบเสร็จ (Payments & Receipts)
CREATE TABLE IF NOT EXISTS payments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    receipt_no VARCHAR(50) NOT NULL UNIQUE,          -- รูปแบบมาตรฐาน: [เดือน]-[ปี พ.ศ.]/[เลขรัน] เช่น 8-2567/541
    meter_reading_id INTEGER NOT NULL REFERENCES meter_readings(id),
    customer_id INTEGER NOT NULL REFERENCES customers(id),
    billing_cycle_id INTEGER NOT NULL REFERENCES billing_cycles(id),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    water_amount DECIMAL(10, 2) NOT NULL,            -- ค่าน้ำ
    maintenance_amount DECIMAL(10, 2) NOT NULL,      -- ค่าบำรุงรักษา
    arrears_amount DECIMAL(10, 2) DEFAULT 0.00,      -- ยอดค้างชำระที่จ่าย
    total_amount DECIMAL(10, 2) NOT NULL,            -- ยอดรวมสุทธิ
    amount_text_th VARCHAR(255) NOT NULL,            -- แปลงตัวเลขเป็นตัวหนังสือไทย (เช่น "ห้าร้อยสี่สิบบาทถ้วน")
    collector_name VARCHAR(100) NOT NULL,            -- เจ้าหน้าที่ผู้รับเงิน
    payment_method VARCHAR(30) DEFAULT 'CASH'        -- CASH, PROMPTPAY_TRANSFER
);

-- 6. ตารางใบสำคัญรับเงิน / ฎีกาเบิกจ่าย (Payment Vouchers / Mail Merge)
CREATE TABLE IF NOT EXISTS payment_vouchers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    voucher_no VARCHAR(50) NOT NULL UNIQUE,          -- เลขที่ฎีกา/ใบสำคัญ เช่น ฎีกา-2567/08-01
    billing_cycle_id INTEGER NOT NULL REFERENCES billing_cycles(id),
    voucher_date DATE NOT NULL,                      -- วันที่ทำเอกสาร
    recipient_name VARCHAR(150) NOT NULL,            -- ชื่อผู้รับเงิน
    recipient_position VARCHAR(100) NOT NULL,        -- ตำแหน่ง เช่น เจ้าหน้าที่เก็บค่าน้ำ, ผู้ดูแลระบบประปา, คณะกรรมการ
    voucher_type VARCHAR(50) NOT NULL,               -- COLLECTOR_10_PCT, CARETAKER_STIPEND, COMMITTEE_ALLOWANCE, MAINTENANCE, ELECTRICITY
    amount DECIMAL(10, 2) NOT NULL,                  -- จำนวนเงินที่ขอเบิก
    amount_text_th VARCHAR(255) NOT NULL,            -- ตัวหนังสือภาษาไทย
    calculation_basis TEXT,                          -- ฐานการคำนวณ เช่น "คิด 10% จากยอดจัดเก็บ 28,450 บาท"
    description TEXT,                                -- รายละเอียดการเบิกจ่าย
    approved_by VARCHAR(100),                        -- ผู้อนุมัติ เช่น ประธานกรรมการหมู่บ้าน
    status VARCHAR(20) DEFAULT 'APPROVED'            -- PENDING, APPROVED, PAID
);

-- 7. ตารางรายงานสรุปรายรับ-รายจ่ายประจำเดือน (Monthly Financial Reports)
CREATE TABLE IF NOT EXISTS monthly_financial_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    billing_cycle_id INTEGER NOT NULL UNIQUE REFERENCES billing_cycles(id),
    report_code VARCHAR(50) NOT NULL,                -- รหัสรายงาน เช่น RPT-8-2567
    report_date DATE NOT NULL,                       -- วันที่จัดทำรายงาน
    
    -- === รายรับ (Revenues) ===
    rev_water_maintenance DECIMAL(12, 2) DEFAULT 0.00, -- ยอดเก็บค่าน้ำและค่าบำรุงมาตรเดือนปัจจุบัน
    rev_collected_arrears DECIMAL(12, 2) DEFAULT 0.00, -- ค่าน้ำค้างชำระสะสมที่ตามเก็บได้
    rev_new_meter_fee DECIMAL(12, 2) DEFAULT 0.00,     -- ค่าธรรมเนียมติดตั้งผู้ใช้น้ำรายใหม่
    rev_bank_interest DECIMAL(12, 2) DEFAULT 0.00,     -- ดอกเบี้ยธนาคาร
    rev_other DECIMAL(12, 2) DEFAULT 0.00,             -- รายรับอื่นๆ
    total_revenue DECIMAL(12, 2) NOT NULL,             -- รวมรายรับทั้งสิ้น
    
    -- === รายจ่าย (Expenses) ===
    exp_caretaker DECIMAL(12, 2) DEFAULT 0.00,         -- ค่าตอบแทนผู้ดูแลระบบ
    exp_committee DECIMAL(12, 2) DEFAULT 0.00,         -- ค่าตอบแทนคณะกรรมการ
    exp_collector DECIMAL(12, 2) DEFAULT 0.00,         -- ค่าตอบแทนเจ้าหน้าที่เก็บค่าน้ำ (เช่น 10% ของยอดจัดเก็บ)
    exp_electricity DECIMAL(12, 2) DEFAULT 0.00,       -- ค่ากระแสไฟฟ้าบ่อบาดาล/ปั๊มน้ำ
    exp_supplies_repairs DECIMAL(12, 2) DEFAULT 0.00,  -- ค่าวัสดุอุปกรณ์/ซ่อมบำรุงระบบ
    exp_other DECIMAL(12, 2) DEFAULT 0.00,             -- รายจ่ายอื่นๆ
    total_expense DECIMAL(12, 2) NOT NULL,             -- รวมรายจ่ายทั้งสิ้น
    
    -- === สรุปกำไร/ขาดทุน และยอดสะสม (Financial Summary) ===
    net_profit_loss DECIMAL(12, 2) NOT NULL,           -- กำไร / (ขาดทุน) ประจำเดือน (รายรับ - รายจ่าย)
    prev_accumulated_balance DECIMAL(12, 2) NOT NULL,  -- ยอดเงินคงเหลือสะสมยกมาจากเดือนก่อน
    total_accumulated_balance DECIMAL(12, 2) NOT NULL, -- ยอดเงินคงเหลือสะสมสุทธิสิ้นเดือน
    cash_in_hand DECIMAL(12, 2) DEFAULT 5000.00,       -- เงินสดในมือ (สำรองจ่าย เช่น 5,000 บาท)
    bank_deposit DECIMAL(12, 2) NOT NULL,              -- เงินฝากธนาคาร (ยอดสะสม - เงินสดในมือ)
    
    certified_by VARCHAR(150),                         -- ผู้ตรวจสอบบัญชี/ประธานกรรมการ
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ====================================================================
-- Indexing เพื่อประสิทธิภาพการค้นหาและออกรายงาน
-- ====================================================================
CREATE INDEX IF NOT EXISTS idx_customers_zone ON customers(zone);
CREATE INDEX IF NOT EXISTS idx_customers_code ON customers(customer_code);
CREATE INDEX IF NOT EXISTS idx_readings_cycle ON meter_readings(billing_cycle_id);
CREATE INDEX IF NOT EXISTS idx_readings_customer ON meter_readings(customer_id);
CREATE INDEX IF NOT EXISTS idx_readings_status ON meter_readings(payment_status);
CREATE INDEX IF NOT EXISTS idx_payments_receipt ON payments(receipt_no);
CREATE INDEX IF NOT EXISTS idx_vouchers_cycle ON payment_vouchers(billing_cycle_id);

-- ====================================================================
-- ข้อมูลตัวอย่างเริ่มต้น (Seed Data) สำหรับหมู่บ้านวังยาง
-- ====================================================================
INSERT INTO tariff_rates (name, rate_per_unit, maintenance_fee, effective_from, is_active, resolution_details)
VALUES ('มติที่ประชุมสัญจร ปรับค่าน้ำ 7 บาท/ลบ.ม.', 7.00, 10.00, '2567-01-01', 1, 'มติคณะกรรมการการประปาหมู่บ้านวังยาง ค่าบริการมาตร 10 บาท/เดือน');

INSERT INTO customers (customer_code, seq_no, first_name, last_name, house_no, zone, phone, meter_serial)
VALUES 
('WY-001', 1, 'สมชาย', 'ใจดี', '12 หมู่ 3', 'โซนเหนือ - ซอย 1', '081-234-5678', 'M-2023-001'),
('WY-002', 2, 'สมศรี', 'มีสุข', '14/1 หมู่ 3', 'โซนเหนือ - ซอย 1', '089-876-5432', 'M-2023-002'),
('WY-003', 3, 'ประเสริฐ', 'วงษ์ทอง', '19 หมู่ 3', 'โซนกลาง - ซอยวัด', '086-555-1122', 'M-2023-003'),
('WY-004', 4, 'บุญส่ง', 'เกษมสุข', '25 หมู่ 3', 'โซนกลาง - ซอยวัด', '084-333-8899', 'M-2023-004'),
('WY-005', 5, 'วันชัย', 'ทองแท้', '38/2 หมู่ 3', 'โซนใต้ - ท้ายบ้าน', '082-111-9988', 'M-2023-005');

INSERT INTO billing_cycles (cycle_code, month, year_be, reading_start_date, reading_end_date, due_date, tariff_rate_id, status)
VALUES 
('8-2567', 8, 2567, '2567-08-25', '2567-08-28', '2567-09-10', 1, 'OPEN');

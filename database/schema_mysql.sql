-- ====================================================================
-- โครงสร้างฐานข้อมูล MySQL: ระบบบริหารจัดการการประปาหมู่บ้านวังยาง (งานกลุ่ม)
-- Database Name: db_wangyang_water (แยกอิสระจาก db_city_water_supply)
-- ====================================================================

CREATE DATABASE IF NOT EXISTS db_wangyang_water CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_wangyang_water;

-- 1. ตารางข้อมูลผู้ใช้น้ำ (Customers)
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_code VARCHAR(30) NOT NULL UNIQUE,       -- รหัสผู้ใช้น้ำ เช่น WY-001
    seq_no INT NOT NULL,                             -- ลำดับตามเส้นทางเดินจดมิเตอร์
    first_name VARCHAR(100) NOT NULL,                -- ชื่อ
    last_name VARCHAR(100) NOT NULL,                 -- สกุล
    house_no VARCHAR(50) NOT NULL,                   -- บ้านเลขที่ เช่น 12 หมู่ 3
    zone VARCHAR(100) NOT NULL,                      -- โซน/กลุ่ม เช่น โซนเหนือ - ซอย 1
    phone VARCHAR(30) NULL,                          -- เบอร์โทรศัพท์
    meter_serial VARCHAR(50) NULL,                   -- หมายเลขเครื่องวัดน้ำ
    status VARCHAR(20) DEFAULT 'ACTIVE',             -- ACTIVE, SUSPENDED, INACTIVE
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. ตารางอัตราค่าน้ำตามช่วงเวลา (Tariff Rates)
CREATE TABLE IF NOT EXISTS tariff_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,                      -- เช่น มติที่ประชุมหมู่บ้าน ปรับค่าน้ำ 7 บาท
    rate_per_unit DECIMAL(10, 2) NOT NULL,           -- อัตราต่อหน่วย (บาท/ลบ.ม.) เช่น 7.00
    maintenance_fee DECIMAL(10, 2) NOT NULL DEFAULT 10.00, -- ค่าบำรุงรักษามิเตอร์ประจำเดือน (10 บาท)
    effective_from DATE NOT NULL,                    -- วันที่มีผลบังคับใช้
    effective_to DATE NULL,                          -- วันที่สิ้นสุด
    is_active TINYINT(1) DEFAULT 1,                  -- 1 = ใช้งาน, 0 = ยกเลิก
    resolution_details TEXT NULL                     -- รายละเอียดมติที่ประชุม
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. ตารางรอบบิลประจำเดือน (Billing Cycles)
CREATE TABLE IF NOT EXISTS billing_cycles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cycle_code VARCHAR(20) NOT NULL UNIQUE,          -- เช่น 8-2567
    month INT NOT NULL,                              -- 1 - 12
    year_be INT NOT NULL,                            -- ปี พ.ศ. เช่น 2567
    reading_start_date DATE NOT NULL,                -- วันที่เริ่มจด
    reading_end_date DATE NOT NULL,                  -- วันที่สิ้นสุด
    due_date DATE NOT NULL,                          -- กำหนดชำระ
    tariff_rate_id INT NOT NULL,
    status VARCHAR(20) DEFAULT 'OPEN',               -- OPEN, CLOSED
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (tariff_rate_id) REFERENCES tariff_rates(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. ตารางบันทึกการจดมิเตอร์และการคำนวณเงิน (Meter Readings)
CREATE TABLE IF NOT EXISTS meter_readings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billing_cycle_id INT NOT NULL,
    customer_id INT NOT NULL,
    previous_reading DECIMAL(10, 2) NOT NULL DEFAULT 0.00,  -- เลขมิเตอร์ครั้งก่อน
    current_reading DECIMAL(10, 2) NOT NULL DEFAULT 0.00,   -- เลขมิเตอร์ครั้งหลัง
    units_used DECIMAL(10, 2) NOT NULL DEFAULT 0.00,        -- จำนวนหน่วยที่ใช้
    rate_per_unit DECIMAL(10, 2) NOT NULL DEFAULT 7.00,     -- อัตราค่าน้ำต่อหน่วย
    water_charge DECIMAL(10, 2) NOT NULL DEFAULT 0.00,      -- ค่าน้ำตามหน่วย
    maintenance_fee DECIMAL(10, 2) NOT NULL DEFAULT 10.00,  -- ค่าบำรุงมิเตอร์
    current_total DECIMAL(10, 2) NOT NULL DEFAULT 10.00,    -- ยอดงวดนี้
    previous_arrears DECIMAL(10, 2) DEFAULT 0.00,           -- ยอดค้างชำระยกมา
    grand_total DECIMAL(10, 2) NOT NULL DEFAULT 10.00,      -- ยอดรวมสุทธิ
    amount_paid DECIMAL(10, 2) DEFAULT 0.00,
    remaining_balance DECIMAL(10, 2) DEFAULT 0.00,
    payment_status VARCHAR(20) DEFAULT 'UNPAID',            -- UNPAID, PAID
    payment_date DATE NULL,
    receipt_no VARCHAR(50) NULL,                            -- เช่น 8-2567/501
    reading_date DATE NOT NULL,
    reader_name VARCHAR(100) NULL,
    remarks TEXT NULL,
    UNIQUE KEY uq_cycle_customer (billing_cycle_id, customer_id),
    FOREIGN KEY (billing_cycle_id) REFERENCES billing_cycles(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. ตารางใบสำคัญรับเงิน / ฎีกาเบิกจ่าย (Payment Vouchers)
CREATE TABLE IF NOT EXISTS payment_vouchers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_no VARCHAR(50) NOT NULL UNIQUE,          -- เช่น ฎีกา-8-2567/01
    billing_cycle_id INT NOT NULL,
    voucher_date DATE NOT NULL,
    recipient_name VARCHAR(150) NOT NULL,
    recipient_position VARCHAR(100) NOT NULL,
    voucher_type VARCHAR(50) NOT NULL,               -- COLLECTOR_10_PCT, CARETAKER, COMMITTEE, MAINTENANCE
    amount DECIMAL(10, 2) NOT NULL,
    amount_text_th VARCHAR(255) NOT NULL,
    calculation_basis TEXT NULL,
    description TEXT NULL,
    approved_by VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (billing_cycle_id) REFERENCES billing_cycles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. ตารางสรุปรายรับ-รายจ่ายประจำเดือน (Monthly Financial Reports)
CREATE TABLE IF NOT EXISTS monthly_financial_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    billing_cycle_id INT NOT NULL UNIQUE,
    rev_water_maintenance DECIMAL(12, 2) DEFAULT 0.00, -- ยอดเก็บค่าน้ำและค่าบำรุงมาตร
    rev_collected_arrears DECIMAL(12, 2) DEFAULT 0.00, -- ค่าน้ำค้างชำระสะสมที่เก็บได้
    rev_new_meter_fee DECIMAL(12, 2) DEFAULT 0.00,     -- ค่าธรรมเนียมติดตั้งใหม่
    rev_bank_interest DECIMAL(12, 2) DEFAULT 0.00,     -- ดอกเบี้ยธนาคาร
    rev_other DECIMAL(12, 2) DEFAULT 0.00,
    total_revenue DECIMAL(12, 2) NOT NULL,
    exp_caretaker DECIMAL(12, 2) DEFAULT 0.00,         -- ค่าตอบแทนผู้ดูแล
    exp_committee DECIMAL(12, 2) DEFAULT 0.00,         -- ค่าตอบแทนกรรมการ
    exp_collector DECIMAL(12, 2) DEFAULT 0.00,         -- ค่าตอบแทนคนเก็บค่าน้ำ (10%)
    exp_electricity DECIMAL(12, 2) DEFAULT 0.00,       -- ค่ากระแสไฟฟ้า
    exp_supplies_repairs DECIMAL(12, 2) DEFAULT 0.00,  -- ค่าวัสดุอุปกรณ์/ซ่อมบำรุง
    exp_other DECIMAL(12, 2) DEFAULT 0.00,
    total_expense DECIMAL(12, 2) NOT NULL,
    net_profit_loss DECIMAL(12, 2) NOT NULL,           -- กำไร / ขาดทุน
    prev_accumulated_balance DECIMAL(12, 2) NOT NULL,  -- ยอดสะสมยกมา
    total_accumulated_balance DECIMAL(12, 2) NOT NULL, -- ยอดสะสมสุทธิ
    cash_in_hand DECIMAL(12, 2) DEFAULT 5000.00,       -- เงินสดในมือ (5,000 บาท)
    bank_deposit DECIMAL(12, 2) NOT NULL,              -- เงินฝากธนาคาร
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (billing_cycle_id) REFERENCES billing_cycles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ====================================================================
-- ข้อมูลตัวอย่างเริ่มต้น (Seed Data)
-- ====================================================================
INSERT INTO tariff_rates (id, name, rate_per_unit, maintenance_fee, effective_from, is_active, resolution_details)
VALUES (1, 'มติที่ประชุมสัญจร ปรับค่าน้ำ 7 บาท/ลบ.ม.', 7.00, 10.00, '2567-01-01', 1, 'มติคณะกรรมการการประปาหมู่บ้านวังยาง ค่าบริการมาตร 10 บาท/เดือน')
ON DUPLICATE KEY UPDATE name=VALUES(name);

INSERT INTO customers (id, customer_code, seq_no, first_name, last_name, house_no, zone, phone, meter_serial)
VALUES 
(1, 'WY-001', 1, 'สมชาย', 'ใจดี', '12 หมู่ 3', 'โซนเหนือ - ซอย 1', '081-234-5678', 'M-2023-001'),
(2, 'WY-002', 2, 'สมศรี', 'มีสุข', '14/1 หมู่ 3', 'โซนเหนือ - ซอย 1', '089-876-5432', 'M-2023-002'),
(3, 'WY-003', 3, 'ประเสริฐ', 'วงษ์ทอง', '19 หมู่ 3', 'โซนกลาง - ซอยวัด', '086-555-1122', 'M-2023-003'),
(4, 'WY-004', 4, 'บุญส่ง', 'เกษมสุข', '25 หมู่ 3', 'โซนกลาง - ซอยวัด', '084-333-8899', 'M-2023-004'),
(5, 'WY-005', 5, 'วันชัย', 'ทองแท้', '38/2 หมู่ 3', 'โซนใต้ - ท้ายบ้าน', '082-111-9988', 'M-2023-005'),
(6, 'WY-006', 6, 'อำนวย', 'คงทน', '41 หมู่ 3', 'โซนใต้ - ท้ายบ้าน', '085-777-6655', 'M-2023-006'),
(7, 'WY-007', 7, 'กัญญา', 'ศรีสวัสดิ์', '52/1 หมู่ 3', 'โซนตะวันออก - ซอยพัฒนา', '087-444-2211', 'M-2023-007'),
(8, 'WY-008', 8, 'ชูเกียรติ', 'รุ่งเรือง', '60 หมู่ 3', 'โซนตะวันออก - ซอยพัฒนา', '083-999-3344', 'M-2023-008')
ON DUPLICATE KEY UPDATE first_name=VALUES(first_name);

INSERT INTO billing_cycles (id, cycle_code, month, year_be, reading_start_date, reading_end_date, due_date, tariff_rate_id, status)
VALUES 
(1, '8-2567', 8, 2567, '2567-08-25', '2567-08-28', '2567-09-10', 1, 'OPEN')
ON DUPLICATE KEY UPDATE cycle_code=VALUES(cycle_code);

INSERT INTO meter_readings 
(billing_cycle_id, customer_id, previous_reading, current_reading, units_used, rate_per_unit, water_charge, maintenance_fee, current_total, previous_arrears, grand_total, payment_status, receipt_no, reading_date)
VALUES
(1, 1, 150.00, 170.00, 20.00, 7.00, 140.00, 10.00, 150.00, 0.00, 150.00, 'PAID', '8-2567/501', '2567-08-27'),
(1, 2, 210.00, 235.00, 25.00, 7.00, 175.00, 10.00, 185.00, 0.00, 185.00, 'PAID', '8-2567/502', '2567-08-27'),
(1, 3, 380.00, 412.00, 32.00, 7.00, 224.00, 10.00, 234.00, 150.00, 384.00, 'UNPAID', NULL, '2567-08-27'),
(1, 4, 95.00, 110.00, 15.00, 7.00, 105.00, 10.00, 115.00, 0.00, 115.00, 'PAID', '8-2567/503', '2567-08-27'),
(1, 5, 512.00, 540.00, 28.00, 7.00, 196.00, 10.00, 206.00, 0.00, 206.00, 'PAID', '8-2567/504', '2567-08-27'),
(1, 6, 180.00, 198.00, 18.00, 7.00, 126.00, 10.00, 136.00, 80.00, 216.00, 'UNPAID', NULL, '2567-08-27'),
(1, 7, 304.00, 326.00, 22.00, 7.00, 154.00, 10.00, 164.00, 0.00, 164.00, 'PAID', '8-2567/505', '2567-08-27'),
(1, 8, 420.00, 455.00, 35.00, 7.00, 245.00, 10.00, 255.00, 0.00, 255.00, 'PAID', '8-2567/506', '2567-08-27')
ON DUPLICATE KEY UPDATE units_used=VALUES(units_used);

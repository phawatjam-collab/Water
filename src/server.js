/**
 * เว็บเซิร์ฟเวอร์ Backend API สำหรับระบบบริหารจัดการการประปาหมู่บ้านวังยาง
 * Express REST API + SQLite WASM Engine + Static File Hosting
 */

const express = require('express');
const cors = require('cors');
const path = require('path');
const xlsx = require('xlsx');

const db = require('./db');
const { bahtText } = require('./services/bahtText');
const { calculateWaterBill } = require('./services/billingService');
const { formatReceiptNumber, generateReceiptData } = require('./services/receiptService');
const { calculateMonthlyFinancials, generateVouchers } = require('./services/financialReportService');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, '..', 'public')));
app.use('/src', express.static(path.join(__dirname, '..', 'src')));

// -------------------------------------------------------------
// 1. Health & System Settings API
// -------------------------------------------------------------
app.get('/api/health', (req, res) => {
  res.json({ status: 'OK', message: 'ระบบการประปาหมู่บ้านวังยางพร้อมใช้งาน', time: new Date() });
});

app.get('/api/settings', async (req, res) => {
  try {
    const tariff = await db.get('SELECT * FROM tariff_rates WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    res.json({
      villageName: 'การประปาหมู่บ้านวังยาง หมู่ที่ 3',
      subdistrict: 'ตำบลวังยาง',
      tariff: tariff || { rate_per_unit: 7.0, maintenance_fee: 10.0, name: 'มติปัจจุบัน 7 บาท' },
      collectorPct: 10,
      cashReserve: 5000.0,
      collectorName: 'นายสมาน เก็บเงินดี',
      caretakerName: 'นายประสิทธิ์ ดูแลดี',
      committeeChairman: 'นายประธาน บริหารกิจการ'
    });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.post('/api/settings/tariff', async (req, res) => {
  try {
    const { name, ratePerUnit, maintenanceFee, resolutionDetails } = req.body;
    await db.run('UPDATE tariff_rates SET is_active = 0 WHERE is_active = 1');
    const result = await db.run(
      `INSERT INTO tariff_rates (name, rate_per_unit, maintenance_fee, effective_from, is_active, resolution_details)
       VALUES (?, ?, ?, date('now'), 1, ?)`,
      [name || 'ปรับอัตราค่าน้ำใหม่', ratePerUnit || 7.0, maintenanceFee || 10.0, resolutionDetails || '']
    );
    res.json({ success: true, id: result.lastInsertRowid });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// -------------------------------------------------------------
// 2. Customer Management API (ทะเบียนผู้ใช้น้ำ)
// -------------------------------------------------------------
app.get('/api/customers', async (req, res) => {
  try {
    const search = req.query.search ? `%${req.query.search.trim()}%` : null;
    let sql = 'SELECT * FROM customers';
    const params = [];

    if (search) {
      sql += ' WHERE first_name LIKE ? OR last_name LIKE ? OR house_no LIKE ? OR customer_code LIKE ?';
      params.push(search, search, search, search);
    }
    sql += ' ORDER BY seq_no ASC';

    const customers = await db.query(sql, params);
    res.json(customers);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.post('/api/customers', async (req, res) => {
  try {
    const { customerCode, seqNo, firstName, lastName, houseNo, zone, phone, meterSerial } = req.body;
    const result = await db.run(
      `INSERT INTO customers (customer_code, seq_no, first_name, last_name, house_no, zone, phone, meter_serial)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)`,
      [customerCode, seqNo || 99, firstName, lastName, houseNo, zone, phone || '', meterSerial || '']
    );
    res.json({ success: true, id: result.lastInsertRowid });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.put('/api/customers/:id', async (req, res) => {
  try {
    const { id } = req.params;
    const { customerCode, seqNo, firstName, lastName, houseNo, zone, phone, meterSerial, status } = req.body;
    await db.run(
      `UPDATE customers 
       SET customer_code = ?, seq_no = ?, first_name = ?, last_name = ?, house_no = ?, zone = ?, phone = ?, meter_serial = ?, status = ?, updated_at = CURRENT_TIMESTAMP
       WHERE id = ?`,
      [customerCode, seqNo, firstName, lastName, houseNo, zone, phone, meterSerial, status || 'ACTIVE', id]
    );
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

app.delete('/api/customers/:id', async (req, res) => {
  try {
    const { id } = req.params;
    await db.run('DELETE FROM customers WHERE id = ?', [id]);
    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// -------------------------------------------------------------
// 3. Billing Cycles & Meter Readings API (งวดบิล & บันทึกมิเตอร์)
// -------------------------------------------------------------
app.get('/api/cycles', async (req, res) => {
  try {
    const cycles = await db.query('SELECT * FROM billing_cycles ORDER BY id DESC');
    res.json(cycles);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * เปิดรอบบิลใหม่ประจำเดือน พร้อมทบยอดค้างชำระ และดึงเลขมิเตอร์ครั้งก่อนจากเลขครั้งหลังของเดือนที่แล้วอัตโนมัติ!
 */
app.post('/api/cycles/open', async (req, res) => {
  try {
    const { cycleCode, month, yearBe, prevCycleCode } = req.body;

    const tariff = await db.get('SELECT * FROM tariff_rates WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
    const tariffId = tariff ? tariff.id : 1;

    // ตรวจสอบว่ามีรอบบิลนี้หรือยัง
    const existing = await db.get('SELECT * FROM billing_cycles WHERE cycle_code = ?', [cycleCode]);
    let cycleId;
    if (existing) {
      cycleId = existing.id;
    } else {
      const cycleRes = await db.run(
        `INSERT INTO billing_cycles (cycle_code, month, year_be, reading_start_date, reading_end_date, due_date, tariff_rate_id, status)
         VALUES (?, ?, ?, date('now'), date('now', '+3 days'), date('now', '+15 days'), ?, 'OPEN')`,
        [cycleCode, month, yearBe, tariffId]
      );
      cycleId = cycleRes.lastInsertRowid;
    }

    // ดึงข้อมูลมิเตอร์จากรอบก่อน (ถ้ามี) เพื่อนำเลขล่าสุดมาเป็น Previous Reading และทบยอดค้างชำระ
    const prevReadings = prevCycleCode ? await db.query(
      `SELECT r.*, c.id as cust_id 
       FROM customers c
       LEFT JOIN billing_cycles bc ON bc.cycle_code = ?
       LEFT JOIN meter_readings r ON r.billing_cycle_id = bc.id AND r.customer_id = c.id`,
      [prevCycleCode]
    ) : [];

    const prevMap = {};
    for (const pr of prevReadings) {
      if (pr.cust_id) {
        prevMap[pr.cust_id] = {
          lastReading: Number(pr.current_reading) || 0,
          unpaidArrears: pr.payment_status !== 'PAID' ? Number(pr.grand_total) || 0 : 0
        };
      }
    }

    // สร้างหรืออัปเดตบันทึกมิเตอร์สำหรับผู้ใช้น้ำทุกคนในรอบบิลใหม่
    const customers = await db.query('SELECT * FROM customers WHERE status = "ACTIVE" ORDER BY seq_no ASC');
    for (const cust of customers) {
      const prevInfo = prevMap[cust.id] || { lastReading: 0, unpaidArrears: 0 };
      const exists = await db.get(
        'SELECT id FROM meter_readings WHERE billing_cycle_id = ? AND customer_id = ?',
        [cycleId, cust.id]
      );

      if (!exists) {
        const ratePerUnit = tariff ? tariff.rate_per_unit : 7.0;
        const maintenanceFee = tariff ? tariff.maintenance_fee : 10.0;
        const currentTotal = maintenanceFee;
        const grandTotal = currentTotal + prevInfo.unpaidArrears;

        await db.run(
          `INSERT INTO meter_readings 
           (billing_cycle_id, customer_id, previous_reading, current_reading, units_used, rate_per_unit, water_charge, maintenance_fee, current_total, previous_arrears, grand_total, payment_status, reading_date)
           VALUES (?, ?, ?, ?, 0, ?, 0, ?, ?, ?, ?, 'UNPAID', date('now'))`,
          [cycleId, cust.id, prevInfo.lastReading, prevInfo.lastReading, ratePerUnit, maintenanceFee, currentTotal, prevInfo.unpaidArrears, grandTotal]
        );
      }
    }

    res.json({ success: true, cycleId, cycleCode });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * ดึงรายการจดมิเตอร์ของงวดประจำเดือน
 */
app.get('/api/readings/:cycleCode', async (req, res) => {
  try {
    const { cycleCode } = req.params;
    const cycle = await db.get('SELECT * FROM billing_cycles WHERE cycle_code = ?', [cycleCode]);
    if (!cycle) return res.status(404).json({ error: 'ไม่พบงวดประจำเดือนนี้' });

    const readings = await db.query(
      `SELECT r.*, c.customer_code, c.seq_no, c.first_name, c.last_name, c.house_no, c.zone, c.phone, c.meter_serial
       FROM meter_readings r
       JOIN customers c ON r.customer_id = c.id
       WHERE r.billing_cycle_id = ?
       ORDER BY c.seq_no ASC`,
      [cycle.id]
    );

    res.json({ cycle, readings });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * บันทึกเลขมิเตอร์ (Batch Save) และคำนวณค่าน้ำอัตโนมัติ
 */
app.post('/api/readings/:cycleCode/save', async (req, res) => {
  try {
    const { cycleCode } = req.params;
    const { items } = req.body; // Array of { customerId, previousReading, currentReading, previousArrears }

    const cycle = await db.get('SELECT * FROM billing_cycles WHERE cycle_code = ?', [cycleCode]);
    if (!cycle) return res.status(404).json({ error: 'ไม่พบงวดประจำเดือน' });

    const tariff = await db.get('SELECT * FROM tariff_rates WHERE id = ?', [cycle.tariff_rate_id]) || { rate_per_unit: 7.0, maintenance_fee: 10.0 };

    for (const item of items) {
      const calc = calculateWaterBill({
        previousReading: item.previousReading,
        currentReading: item.currentReading,
        ratePerUnit: tariff.rate_per_unit,
        maintenanceFee: tariff.maintenance_fee,
        previousArrears: item.previousArrears || 0
      });

      await db.run(
        `UPDATE meter_readings
         SET previous_reading = ?, current_reading = ?, units_used = ?, rate_per_unit = ?, water_charge = ?, maintenance_fee = ?, current_total = ?, previous_arrears = ?, grand_total = ?, reading_date = date('now')
         WHERE billing_cycle_id = ? AND customer_id = ?`,
        [
          calc.previousReading,
          calc.currentReading,
          calc.unitsUsed,
          calc.ratePerUnit,
          calc.waterCharge,
          calc.maintenanceFee,
          calc.currentTotal,
          calc.previousArrears,
          calc.grandTotal,
          cycle.id,
          item.customerId
        ]
      );
    }

    res.json({ success: true, message: 'บันทึกเลขมิเตอร์และคำนวณเงินเรียบร้อยแล้ว' });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

/**
 * สลับสถานะการชำระเงิน (PAID / UNPAID) พร้อมออกเลขที่ใบเสร็จมาตรฐาน
 */
app.post('/api/readings/:cycleCode/:customerId/toggle-paid', async (req, res) => {
  try {
    const { cycleCode, customerId } = req.params;
    const cycle = await db.get('SELECT * FROM billing_cycles WHERE cycle_code = ?', [cycleCode]);
    if (!cycle) return res.status(404).json({ error: 'ไม่พบงวดประจำเดือน' });

    const reading = await db.get(
      'SELECT * FROM meter_readings WHERE billing_cycle_id = ? AND customer_id = ?',
      [cycle.id, customerId]
    );
    if (!reading) return res.status(404).json({ error: 'ไม่พบบันทึกมิเตอร์' });

    const newStatus = reading.payment_status === 'PAID' ? 'UNPAID' : 'PAID';
    let receiptNo = reading.receipt_no;

    if (newStatus === 'PAID' && !receiptNo) {
      // สร้างเลขที่ใบเสร็จมาตรฐาน เช่น 8-2567/541
      const countRow = await db.get(
        'SELECT count(*) as count FROM meter_readings WHERE billing_cycle_id = ? AND receipt_no IS NOT NULL',
        [cycle.id]
      );
      const nextSeq = 500 + (countRow ? countRow.count + 1 : 1);
      receiptNo = formatReceiptNumber(cycle.month, cycle.year_be, nextSeq);
    }

    await db.run(
      `UPDATE meter_readings 
       SET payment_status = ?, receipt_no = ?, payment_date = ${newStatus === 'PAID' ? "date('now')" : "NULL"}
       WHERE id = ?`,
      [newStatus, newStatus === 'PAID' ? receiptNo : null, reading.id]
    );

    res.json({ success: true, paymentStatus: newStatus, receiptNo });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// -------------------------------------------------------------
// 4. Receipt Details API (ออกใบเสร็จรับเงิน & ตัวหนังสือไทย)
// -------------------------------------------------------------
app.get('/api/receipts/:cycleCode/:customerId', async (req, res) => {
  try {
    const { cycleCode, customerId } = req.params;
    const cycle = await db.get('SELECT * FROM billing_cycles WHERE cycle_code = ?', [cycleCode]);
    const cust = await db.get('SELECT * FROM customers WHERE id = ?', [customerId]);
    const reading = await db.get(
      'SELECT * FROM meter_readings WHERE billing_cycle_id = ? AND customer_id = ?',
      [cycle.id, customerId]
    );

    if (!cycle || !cust || !reading) {
      return res.status(404).json({ error: 'ไม่พบข้อมูลใบเสร็จ' });
    }

    const receiptNo = reading.receipt_no || formatReceiptNumber(cycle.month, cycle.year_be, 500 + cust.seq_no);

    const receipt = generateReceiptData({
      receiptNo,
      billingCycle: { month: cycle.month, yearBe: cycle.year_be, cycleCode: cycle.cycle_code },
      customer: {
        customerCode: cust.customer_code,
        seqNo: cust.seq_no,
        firstName: cust.first_name,
        lastName: cust.last_name,
        houseNo: cust.house_no,
        zone: cust.zone,
        meterSerial: cust.meter_serial
      },
      reading: {
        previousReading: reading.previous_reading,
        currentReading: reading.current_reading,
        unitsUsed: reading.units_used,
        ratePerUnit: reading.rate_per_unit,
        waterCharge: reading.water_charge,
        maintenanceFee: reading.maintenance_fee,
        currentTotal: reading.current_total,
        previousArrears: reading.previous_arrears,
        grandTotal: reading.grand_total
      },
      paymentDate: reading.payment_date ? new Date(reading.payment_date) : new Date(),
      collectorName: 'นายสมาน เก็บเงินดี'
    });

    res.json(receipt);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// -------------------------------------------------------------
// 5. Payment Vouchers & Mail Merge API (ใบสำคัญรับเงิน / ฎีกาเบิกจ่าย)
// -------------------------------------------------------------
app.get('/api/vouchers/:cycleCode', async (req, res) => {
  try {
    const { cycleCode } = req.params;
    const cycle = await db.get('SELECT * FROM billing_cycles WHERE cycle_code = ?', [cycleCode]);
    if (!cycle) return res.status(404).json({ error: 'ไม่พบงวดประจำเดือน' });

    // คำนวณยอดจัดเก็บค่าน้ำได้จริง
    const paidRow = await db.get(
      `SELECT sum(grand_total) as total_paid
       FROM meter_readings 
       WHERE billing_cycle_id = ? AND payment_status = 'PAID'`,
      [cycle.id]
    );
    const collectedRevenue = paidRow && paidRow.total_paid ? Number(paidRow.total_paid) : 0;
    const collectorCommission = Math.round((collectedRevenue * 0.10) * 100) / 100;

    const reportData = {
      billingCycle: { cycleCode },
      expenses: {
        expCollector: collectorCommission,
        collectorBasis: `คิด 10% จากยอดจัดเก็บจริง ${collectedRevenue.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`,
        expCaretaker: 3000.0,
        expCommittee: 2500.0,
        expSuppliesRepairs: 1450.0
      }
    };

    const personnelList = [
      { name: 'นายสมาน เก็บเงินดี', position: 'เจ้าหน้าที่จัดเก็บค่าน้ำประปา', type: 'COLLECTOR' },
      { name: 'นายประสิทธิ์ ดูแลดี', position: 'ผู้ดูแลรักษาระบบประปาและบ่อบาดาล', type: 'CARETAKER' },
      { name: 'นายประธาน บริหารกิจการ', position: 'ประธานกรรมการการประปาหมู่บ้านวังยาง', type: 'COMMITTEE' },
      { name: 'ร้านวังยางการช่าง & อุปกรณ์', position: 'ผู้จัดจำหน่ายวัสดุอุปกรณ์', type: 'MAINTENANCE', description: 'ค่าท่อ PVC ข้อต่อ กาว และอุปกรณ์ซ่อมแซมจุดรั่วไหล' }
    ];

    const vouchers = generateVouchers(reportData, personnelList);
    res.json({ cycleCode, collectedRevenue, vouchers });
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// -------------------------------------------------------------
// 6. Monthly Financial Report API (สรุปรายรับ-รายจ่าย & กระเป๋าเงิน)
// -------------------------------------------------------------
app.get('/api/financials/:cycleCode', async (req, res) => {
  try {
    const { cycleCode } = req.params;
    const cycle = await db.get('SELECT * FROM billing_cycles WHERE cycle_code = ?', [cycleCode]);
    if (!cycle) return res.status(404).json({ error: 'ไม่พบงวดประจำเดือน' });

    // คำนวณยอดจัดเก็บจริงจากระบบมิเตอร์
    const paidStats = await db.get(
      `SELECT 
         sum(water_charge + maintenance_fee) as current_collected,
         sum(previous_arrears) as arrears_collected
       FROM meter_readings 
       WHERE billing_cycle_id = ? AND payment_status = 'PAID'`,
      [cycle.id]
    );

    const report = calculateMonthlyFinancials({
      billingCycle: { cycleCode },
      revWaterMaintenance: paidStats?.current_collected || 24500.0,
      revCollectedArrears: paidStats?.arrears_collected || 3200.0,
      revNewMeterFee: 1500.0,
      revBankInterest: 125.50,
      revOther: 0.0,
      expCaretaker: 3000.0,
      expCommittee: 2500.0,
      collectorPercent: 10,
      expElectricity: 6840.0,
      expSuppliesRepairs: 1450.0,
      expOther: 300.0,
      prevAccumulatedBalance: 145200.0,
      targetCashInHand: 5000.0
    });

    res.json(report);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// -------------------------------------------------------------
// 7. Export to Excel API (.xlsx)
// -------------------------------------------------------------
app.get('/api/export/excel/:cycleCode', async (req, res) => {
  try {
    const { cycleCode } = req.params;
    const cycle = await db.get('SELECT * FROM billing_cycles WHERE cycle_code = ?', [cycleCode]);
    if (!cycle) return res.status(404).json({ error: 'ไม่พบงวดประจำเดือน' });

    const readings = await db.query(
      `SELECT 
         c.seq_no as "ลำดับ",
         c.customer_code as "รหัสผู้ใช้น้ำ",
         c.first_name || ' ' || c.last_name as "ชื่อ-สกุล",
         c.house_no as "บ้านเลขที่",
         c.zone as "โซน",
         r.previous_reading as "เลขมิเตอร์ครั้งก่อน",
         r.current_reading as "เลขมิเตอร์ครั้งหลัง",
         r.units_used as "หน่วยที่ใช้",
         r.water_charge as "ค่าน้ำ (บาท)",
         r.maintenance_fee as "ค่าบำรุงรักษา (บาท)",
         r.current_total as "ยอดงวดนี้ (บาท)",
         r.previous_arrears as "ค้างเก่า (บาท)",
         r.grand_total as "ยอดรวมสุทธิ (บาท)",
         case when r.payment_status = 'PAID' then 'ชำระแล้ว' else 'ค้างชำระ' end as "สถานะชำระ",
         r.receipt_no as "เลขที่ใบเสร็จ"
       FROM meter_readings r
       JOIN customers c ON r.customer_id = c.id
       WHERE r.billing_cycle_id = ?
       ORDER BY c.seq_no ASC`,
      [cycle.id]
    );

    const worksheet = xlsx.utils.json_to_sheet(readings);
    const workbook = xlsx.utils.book_new();
    xlsx.utils.book_append_sheet(workbook, worksheet, `งวด-${cycleCode}`);

    const buffer = xlsx.write(workbook, { type: 'buffer', bookType: 'xlsx' });

    res.setHeader('Content-Disposition', `attachment; filename=Water_Billing_${cycleCode}.xlsx`);
    res.setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    res.send(buffer);
  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Start Server
app.listen(PORT, () => {
  console.log('================================================================');
  console.log(`💧 เซิร์ฟเวอร์การประปาหมู่บ้านวังยาง เริ่มทำงานแล้วที่:`);
  console.log(`   🌐 http://localhost:${PORT}`);
  console.log('================================================================');
});

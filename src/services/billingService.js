/**
 * ตรรกะทางธุรกิจ: การคำนวณมิเตอร์และค่าบริการน้ำประปา
 * Village Water Supply Billing Service
 */

/**
 * คำนวณค่าน้ำประจำงวดและยอดค้างชำระ
 * @param {Object} params
 * @param {number} params.previousReading - เลขมิเตอร์ครั้งก่อน
 * @param {number} params.currentReading - เลขมิเตอร์ครั้งหลัง
 * @param {number} params.ratePerUnit - อัตราค่าน้ำต่อหน่วย (เช่น 7.00 บาท)
 * @param {number} [params.maintenanceFee=10.00] - ค่าบำรุงรักษามิเตอร์ (เช่น 10.00 บาท)
 * @param {number} [params.previousArrears=0.00] - ยอดค้างชำระยกมาจากเดือนก่อน
 * @returns {Object} ผลการคำนวณอย่างละเอียด
 */
function calculateWaterBill({
  previousReading,
  currentReading,
  ratePerUnit = 7.0,
  maintenanceFee = 10.0,
  previousArrears = 0.0
}) {
  const prev = Number(previousReading) || 0;
  const curr = Number(currentReading) || 0;
  const rate = Number(ratePerUnit) || 0;
  const fee = Number(maintenanceFee) || 0;
  const arrears = Number(previousArrears) || 0;

  // ตรวจสอบความถูกต้องของเลขมิเตอร์ (กรณีมิเตอร์วนรอบหรือกรอกผิด)
  let unitsUsed = 0;
  let isRollover = false;

  if (curr >= prev) {
    unitsUsed = curr - prev;
  } else {
    // กรณีมิเตอร์ 4 หรือ 5 หลักหมุนวนรอบ (Rollover) เช่น 9999 -> 0015
    isRollover = true;
    unitsUsed = (10000 - prev) + curr;
  }

  // คำนวณค่าน้ำตามหน่วย
  const waterCharge = Math.round(unitsUsed * rate * 100) / 100;

  // ค่าน้ำรวมรอบนี้ (ค่าน้ำตามหน่วย + ค่าบำรุงรักษา)
  const currentTotal = Math.round((waterCharge + fee) * 100) / 100;

  // ยอดรวมทั้งสิ้น (รอบนี้ + ยอดค้างเก่า)
  const grandTotal = Math.round((currentTotal + arrears) * 100) / 100;

  return {
    previousReading: prev,
    currentReading: curr,
    unitsUsed,
    isRollover,
    ratePerUnit: rate,
    waterCharge,
    maintenanceFee: fee,
    currentTotal,
    previousArrears: arrears,
    grandTotal
  };
}

/**
 * ตรวจสอบและดึงยอดค้างชำระข้ามเดือน (Arrears Rollover)
 * หากผู้ใช้น้ำไม่ได้ชำระในรอบก่อน ยอดค้างจะถูกทบมาเป็น previous_arrears ของรอบใหม่
 */
function rolloverUnpaidBills(previousCycleReadings) {
  const arrearsMap = {};

  for (const record of previousCycleReadings) {
    const remaining = Number(record.remaining_balance) || 0;
    const unpaid = record.payment_status !== 'PAID' ? (Number(record.grand_total) - (Number(record.amount_paid) || 0)) : 0;
    const totalDue = Math.max(remaining, unpaid);

    if (totalDue > 0) {
      arrearsMap[record.customer_id] = totalDue;
    }
  }

  return arrearsMap;
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { calculateWaterBill, rolloverUnpaidBills };
}

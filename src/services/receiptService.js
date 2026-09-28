/**
 * บริการออกใบเสร็จรับเงินมาตรฐาน: การประปาหมู่บ้านวังยาง
 * Receipt Service with Standard Formatting & Thai Baht Text
 */

const { bahtText } = typeof require !== 'undefined' ? require('./bahtText') : { bahtText: window.bahtText };

/**
 * สร้างเลขที่ใบเสร็จตามรูปแบบมาตรฐาน (เช่น 8-2567/541)
 * @param {number} month - เดือน (1-12)
 * @param {number} yearBe - ปี พ.ศ. (เช่น 2567)
 * @param {number} seqNumber - ลำดับที่ใบเสร็จในเดือนนั้น (เช่น 541)
 * @returns {string} เช่น "8-2567/541"
 */
function formatReceiptNumber(month, yearBe, seqNumber) {
  const paddedSeq = String(seqNumber).padStart(3, '0');
  return `${month}-${yearBe}/${paddedSeq}`;
}

/**
 * สร้างข้อมูลใบเสร็จรับเงินฉบับสมบูรณ์สำหรับแสดงผลและสั่งพิมพ์
 * @param {Object} params
 */
function generateReceiptData({
  receiptNo,
  billingCycle, // { month, yearBe, cycleCode }
  customer,     // { customerCode, seqNo, fullName, houseNo, zone, meterSerial }
  reading,      // { previousReading, currentReading, unitsUsed, ratePerUnit, waterCharge, maintenanceFee, previousArrears, grandTotal }
  paymentDate = new Date(),
  collectorName = 'เจ้าหน้าที่จัดเก็บค่าน้ำ',
  organizationName = 'การประปาหมู่บ้านวังยาง หมู่ 3'
}) {
  const formattedDate = new Intl.DateTimeFormat('th-TH', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  }).format(paymentDate);

  const totalAmount = reading.grandTotal;
  const totalAmountTextTh = bahtText(totalAmount);

  return {
    organizationName,
    receiptNo,
    billingCycleText: `งวดประจำเดือน ${billingCycle.month}/${billingCycle.yearBe}`,
    issueDateText: formattedDate,
    customer: {
      code: customer.customerCode,
      seq: customer.seqNo,
      name: customer.fullName || `${customer.firstName} ${customer.lastName}`,
      houseNo: customer.houseNo,
      zone: customer.zone,
      meterSerial: customer.meterSerial || '-'
    },
    meter: {
      previous: reading.previousReading,
      current: reading.currentReading,
      unitsUsed: reading.unitsUsed,
      ratePerUnit: reading.ratePerUnit
    },
    breakdown: {
      waterCharge: reading.waterCharge,
      maintenanceFee: reading.maintenanceFee,
      currentTotal: reading.currentTotal,
      previousArrears: reading.previousArrears,
      grandTotal: totalAmount
    },
    totalAmountTextTh,
    collectorName,
    villageCommitteeHeader: 'คณะกรรมการบริหารกิจการประปาหมู่บ้านวังยาง'
  };
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { formatReceiptNumber, generateReceiptData };
}

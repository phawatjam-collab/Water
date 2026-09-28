/**
 * บริการรายงานการเงินประจำเดือนและระบบฎีกาเบิกจ่าย (Monthly Financial & Vouchers)
 * Village Water Supply Financial & Voucher Service
 */

const { bahtText } = typeof require !== 'undefined' ? require('./bahtText') : { bahtText: window.bahtText };

/**
 * คำนวณสรุปรายงานรายรับ-รายจ่ายประจำเดือน
 * @param {Object} params
 */
function calculateMonthlyFinancials({
  billingCycle,
  // รายรับ
  revWaterMaintenance = 0,    // ยอดเก็บค่าน้ำและค่าบำรุงมาตร
  revCollectedArrears = 0,    // ค่าน้ำค้างชำระสะสมรายเดือน
  revNewMeterFee = 0,         // ค่าธรรมเนียมติดตั้งผู้ใช้น้ำรายใหม่
  revBankInterest = 0,        // ดอกเบี้ยธนาคาร
  revOther = 0,               // รายรับอื่นๆ
  // รายจ่าย
  expCaretaker = 0,           // ค่าตอบแทนผู้ดูแลระบบ
  expCommittee = 0,           // ค่าตอบแทนคณะกรรมการ
  collectorPercent = 10,      // คิด % ค่าตอบแทนเจ้าหน้าที่เก็บค่าน้ำ (ค่าเริ่มต้น 10%)
  customExpCollector = null,  // หากระบุยอดตรงข้ามกับการคำนวณ %
  expElectricity = 0,         // ค่ากระแสไฟฟ้า
  expSuppliesRepairs = 0,     // ค่าวัสดุอุปกรณ์/ซ่อมบำรุงระบบ
  expOther = 0,               // รายจ่ายอื่นๆ
  // ยอดยกมาและเงินสดสำรอง
  prevAccumulatedBalance = 0, // ยอดสะสมยกมาจากเดือนก่อน
  targetCashInHand = 5000     // เงินสดในมือสำรองจ่าย (เช่น 5,000 บาท)
}) {
  // 1. รวมรายรับ
  const collectedWaterTotal = Number(revWaterMaintenance) + Number(revCollectedArrears);
  const totalRevenue = Math.round((
    collectedWaterTotal +
    Number(revNewMeterFee) +
    Number(revBankInterest) +
    Number(revOther)
  ) * 100) / 100;

  // 2. คำนวณค่าตอบแทนคนเก็บค่าน้ำ (เช่น 10% จากยอดที่จัดเก็บได้จริง)
  const expCollector = customExpCollector !== null 
    ? Number(customExpCollector) 
    : Math.round((collectedWaterTotal * (collectorPercent / 100)) * 100) / 100;

  // รวมรายจ่าย
  const totalExpense = Math.round((
    Number(expCaretaker) +
    Number(expCommittee) +
    expCollector +
    Number(expElectricity) +
    Number(expSuppliesRepairs) +
    Number(expOther)
  ) * 100) / 100;

  // 3. กำไร / ขาดทุน ประจำเดือน
  const netProfitLoss = Math.round((totalRevenue - totalExpense) * 100) / 100;

  // 4. ยอดเงินสะสมสุทธิ
  const totalAccumulatedBalance = Math.round((Number(prevAccumulatedBalance) + netProfitLoss) * 100) / 100;

  // 5. แยกเงินสดในมือ และ เงินฝากธนาคาร
  const cashInHand = Math.min(Number(targetCashInHand), Math.max(0, totalAccumulatedBalance));
  const bankDeposit = Math.round((totalAccumulatedBalance - cashInHand) * 100) / 100;

  return {
    billingCycle,
    revenues: {
      revWaterMaintenance: Number(revWaterMaintenance),
      revCollectedArrears: Number(revCollectedArrears),
      revNewMeterFee: Number(revNewMeterFee),
      revBankInterest: Number(revBankInterest),
      revOther: Number(revOther),
      totalRevenue
    },
    expenses: {
      expCaretaker: Number(expCaretaker),
      expCommittee: Number(expCommittee),
      expCollector,
      collectorBasis: `คิด ${collectorPercent}% จากยอดจัดเก็บ ${collectedWaterTotal.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`,
      expElectricity: Number(expElectricity),
      expSuppliesRepairs: Number(expSuppliesRepairs),
      expOther: Number(expOther),
      totalExpense
    },
    summary: {
      netProfitLoss,
      isProfit: netProfitLoss >= 0,
      prevAccumulatedBalance: Number(prevAccumulatedBalance),
      totalAccumulatedBalance,
      cashInHand,
      bankDeposit,
      totalAccumulatedTextTh: bahtText(totalAccumulatedBalance)
    }
  };
}

/**
 * สร้างชุดเอกสารใบสำคัญรับเงิน / ฎีกาเบิกจ่าย (Voucher Mail Merge)
 * @param {Object} reportData
 * @param {Array} personnelList รายชื่อและตำแหน่งผู้รับเงิน
 */
function generateVouchers(reportData, personnelList = []) {
  const vouchers = [];
  const cycleCode = reportData.billingCycle.cycleCode;
  let counter = 1;

  for (const person of personnelList) {
    let amount = 0;
    let basis = '';

    if (person.type === 'COLLECTOR') {
      amount = reportData.expenses.expCollector;
      basis = reportData.expenses.collectorBasis;
    } else if (person.type === 'CARETAKER') {
      amount = reportData.expenses.expCaretaker;
      basis = 'ค่าตอบแทนผู้ดูแลระบบประปาประจำเดือน';
    } else if (person.type === 'COMMITTEE') {
      amount = person.amount || (reportData.expenses.expCommittee / (person.committeeCount || 1));
      basis = 'ค่าตอบแทนเบี้ยประชุมคณะกรรมการประปาหมู่บ้าน';
    } else if (person.type === 'MAINTENANCE') {
      amount = person.amount || reportData.expenses.expSuppliesRepairs;
      basis = person.description || 'ค่าวัสดุอุปกรณ์และซ่อมบำรุงท่อน้ำ';
    } else {
      amount = person.amount || 0;
      basis = person.description || 'ค่าใช้จ่ายทั่วไป';
    }

    if (amount > 0) {
      const voucherNo = `ฎีกา-${cycleCode}/${String(counter++).padStart(2, '0')}`;
      vouchers.push({
        voucherNo,
        cycleCode,
        date: new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }),
        recipientName: person.name,
        recipientPosition: person.position,
        voucherType: person.type,
        amount,
        amountTextTh: bahtText(amount),
        calculationBasis: basis,
        approvedBy: person.approvedBy || 'ประธานคณะกรรมการประปาหมู่บ้านวังยาง'
      });
    }
  }

  return vouchers;
}

if (typeof module !== 'undefined' && module.exports) {
  module.exports = { calculateMonthlyFinancials, generateVouchers };
}

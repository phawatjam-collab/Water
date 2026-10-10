/**
 * แอปพลิเคชันระบบบริหารจัดการการประปาหมู่บ้านวังยาง (Client-side Controller)
 * XAMPP (Apache + PHP + MySQL) Edition
 */

const API_BASE = 'api';

let appState = {
  currentCycle: '8-2567',
  settings: {
    waterRate: 7.0,
    maintenanceFee: 10.0,
    collectorPct: 10,
    cashReserve: 5000.0,
    villageName: 'การประปาหมู่บ้านวังยาง หมู่ที่ 3',
    subdistrict: 'ตำบลวังยาง',
    collectorName: 'นายสมาน เก็บเงินดี',
    caretakerName: 'นายประสิทธิ์ ดูแลดี',
    committeeChairman: 'นายประธาน บริหารกิจการ'
  },
  customers: [],
  readings: {},
  selectedReceiptCustomerIndex: 0
};

function showToast(message, type = 'success', duration = 3500) {
  let container = document.querySelector('.toast-container');
  if (!container) {
    container = document.createElement('div');
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = {
    success: '✅',
    error: '❌',
    info: 'ℹ️',
    warning: '⚠️'
  };
  const toast = document.createElement('div');
  toast.className = `toast-msg toast-${type}`;
  toast.innerHTML = `<span>${icons[type] || '🔔'}</span> <span>${message}</span>`;
  container.appendChild(toast);

  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(50px)';
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// -------------------------------------------------------------
// 1. Data Fetching from PHP API
// -------------------------------------------------------------
async function initAppData() {
  try {
    console.log('⚡ เชื่อมต่อระบบ PHP + MySQL (db_wangyang_water)...');

    // 1. โหลดรอบบิลทั้งหมด
    const cycleRes = await fetch(`${API_BASE}/cycles.php`);
    if (cycleRes.ok) {
      const cycles = await cycleRes.json();
      const sel = document.getElementById('cycle-select');
      if (sel && cycles.length > 0) {
        sel.innerHTML = cycles.map(c => 
          `<option value="${c.cycle_code}" ${c.cycle_code === appState.currentCycle ? 'selected' : ''}>
            ประจำเดือน ${c.month}/${c.year_be} (${c.cycle_code})
          </option>`
        ).join('');
      }
    }

    // 2. โหลดรายชื่อผู้ใช้น้ำ
    await loadCustomers();

    // 3. โหลดบันทึกมิเตอร์ของงวดปัจจุบัน
    await loadCycleReadings(appState.currentCycle);

  } catch (e) {
    console.error('Error initializing app data:', e);
  }

  updateUIHeader();
  renderReadingsTable();
  renderCustomersTable();
  updateZoneFilter();
}

async function loadCustomers() {
  const custRes = await fetch(`${API_BASE}/customers.php`);
  if (custRes.ok) {
    const list = await custRes.json();
    appState.customers = list.map(c => ({
      id: parseInt(c.id, 10),
      customerCode: c.customer_code,
      seqNo: parseInt(c.seq_no, 10),
      firstName: c.first_name,
      lastName: c.last_name,
      houseNo: c.house_no,
      zone: c.zone,
      phone: c.phone,
      meterSerial: c.meter_serial,
      status: c.status
    }));
  }
}

async function loadCycleReadings(cycleCode) {
  try {
    const res = await fetch(`${API_BASE}/readings.php?cycle=${cycleCode}`);
    if (res.ok) {
      const data = await res.json();
      appState.readings[cycleCode] = data.readings.map(r => ({
        customerId: parseInt(r.customer_id, 10),
        previousReading: parseFloat(r.previous_reading) || 0,
        currentReading: parseFloat(r.current_reading) || 0,
        unitsUsed: parseFloat(r.units_used) || 0,
        ratePerUnit: parseFloat(r.rate_per_unit) || 7.0,
        waterCharge: parseFloat(r.water_charge) || 0,
        maintenanceFee: parseFloat(r.maintenance_fee) || 10.0,
        currentTotal: parseFloat(r.current_total) || 0,
        previousArrears: parseFloat(r.previous_arrears) || 0,
        grandTotal: parseFloat(r.grand_total) || 0,
        paymentStatus: r.payment_status,
        receiptNo: r.receipt_no
      }));
    }
  } catch (e) {
    console.error('Error fetching cycle readings:', e);
  }
}

function updateUIHeader() {
  const rateEl = document.getElementById('sidebar-tariff-rate');
  if (rateEl) {
    rateEl.textContent = `${appState.settings.waterRate.toFixed(2)} บาท/หน่วย (+${appState.settings.maintenanceFee} บ.)`;
  }
}

// -------------------------------------------------------------
// 2. Navigation Tabs
// -------------------------------------------------------------
function initNavigation() {
  const navItems = document.querySelectorAll('.nav-item[data-tab]');
  const tabPanes = document.querySelectorAll('.tab-pane');
  const pageTitle = document.getElementById('page-title');
  const pageSubtitle = document.getElementById('page-subtitle');

  const titles = {
    'tab-readings': { title: 'บันทึกจดมิเตอร์ประจำเดือน', sub: 'บันทึกเลขมิเตอร์ คำนวณค่าน้ำ และทบยอดค้างชำระอัตโนมัติ' },
    'tab-receipts': { title: 'พิมพ์ใบเสร็จรับเงิน', sub: 'ออกใบเสร็จรับเงินมาตรฐานพร้อมแปลงตัวหนังสือไทยอัตโนมัติ' },
    'tab-vouchers': { title: 'ใบสำคัญรับเงิน / ฎีกาเบิกจ่าย', sub: 'ระบบจดหมายเวียนสำหรับทำฎีกาเบิกจ่ายค่าตอบแทน 10% และค่าใช้จ่ายระบบ' },
    'tab-financials': { title: 'สรุปรายรับ-รายจ่าย & กราฟวิเคราะห์การเงิน (กค.3)', sub: 'รายงานสรุปสถานะการเงิน น้ำสูญเสีย (NRW) ยอดเงินสดในมือ และเงินฝากธนาคาร' },
    'tab-arrears': { title: 'ทะเบียนคุมลูกหนี้ค่าน้ำประปาค้างชำระ (แบบ กค.4)', sub: 'ระบบติดตามทวงถามหนี้ การจำแนกอายุหนี้ (Debt Aging) และออกหนังสือเตือนระงับการจ่ายน้ำ' },
    'tab-customers': { title: 'ทะเบียนสมาชิกผู้ใช้น้ำ', sub: 'จัดการข้อมูลผู้ใช้น้ำ เลขมิเตอร์ และการจัดโซน' },
    'tab-settings': { title: 'ตั้งค่าอัตราค่าน้ำ & ค่าธรรมเนียม', sub: 'กำหนดอัตราค่าน้ำตามมติที่ประชุมและพารามิเตอร์การเงิน' }
  };

  navItems.forEach(item => {
    item.addEventListener('click', () => {
      const targetTab = item.getAttribute('data-tab');
      navItems.forEach(n => n.classList.remove('active'));
      tabPanes.forEach(p => p.classList.remove('active'));

      item.classList.add('active');
      const targetPane = document.getElementById(targetTab);
      if (targetPane) targetPane.classList.add('active');

      if (titles[targetTab]) {
        pageTitle.textContent = titles[targetTab].title;
        pageSubtitle.textContent = titles[targetTab].sub;
      }

      if (targetTab === 'tab-receipts') renderReceipt();
      if (targetTab === 'tab-vouchers') renderVouchers();
      if (targetTab === 'tab-financials') {
        renderFinancialReport();
        renderFinancialReportCharts();
      }
      if (targetTab === 'tab-arrears') renderArrearsTable();
      if (targetTab === 'tab-customers') renderCustomersTable();
      if (targetTab === 'tab-readings') renderReadingsTable();
    });
  });

  // Handle URL query parameter ?tab=... or hash #...
  const urlParams = new URLSearchParams(window.location.search);
  const requestedTab = urlParams.get('tab') || window.location.hash.replace('#', '');
  if (requestedTab) {
    const cleanTab = requestedTab.startsWith('tab-') ? requestedTab : `tab-${requestedTab}`;
    const targetPane = document.getElementById(cleanTab);
    if (targetPane) {
      tabPanes.forEach(p => p.classList.remove('active'));
      targetPane.classList.add('active');

      if (titles[cleanTab]) {
        if (pageTitle) pageTitle.textContent = titles[cleanTab].title;
        if (pageSubtitle) pageSubtitle.textContent = titles[cleanTab].sub;
      }

      if (cleanTab === 'tab-receipts') renderReceipt();
      if (cleanTab === 'tab-vouchers') renderVouchers();
      if (cleanTab === 'tab-financials') {
        renderFinancialReport();
        renderFinancialReportCharts();
      }
      if (cleanTab === 'tab-arrears') renderArrearsTable();
      if (cleanTab === 'tab-customers') renderCustomersTable();
      if (cleanTab === 'tab-readings') renderReadingsTable();
    }
  }
}

function updateZoneFilter() {
  const select = document.getElementById('reading-zone-filter');
  if (!select) return;
  const zones = [...new Set(appState.customers.map(c => c.zone))];
  select.innerHTML = '<option value="">ทุกโซน / ทุกหมู่</option>' + 
    zones.map(z => `<option value="${z}">${z}</option>`).join('');
}

// -------------------------------------------------------------
// TAB 1: Meter Readings Table & Realtime Calculation
// -------------------------------------------------------------
function renderReadingsTable() {
  const tbody = document.getElementById('readings-table-body');
  if (!tbody) return;

  const currentReadings = appState.readings[appState.currentCycle] || [];
  const search = (document.getElementById('reading-search-input')?.value || '').toLowerCase().trim();
  const zoneFilter = document.getElementById('reading-zone-filter')?.value || '';
  const statusFilter = document.getElementById('reading-status-filter')?.value || '';

  tbody.innerHTML = '';

  let totalUsers = appState.customers.length;
  let readUsers = 0;
  let totalUnits = 0;
  let currentBilling = 0;
  let arrearsTotal = 0;
  let paidAmount = 0;
  let paidCount = 0;

  appState.customers.forEach((cust) => {
    let record = currentReadings.find(r => r.customerId === cust.id);
    if (!record) {
      record = {
        customerId: cust.id,
        previousReading: 0,
        currentReading: 0,
        previousArrears: 0,
        paymentStatus: 'UNPAID',
        receiptNo: null,
        unitsUsed: 0,
        currentTotal: appState.settings.maintenanceFee,
        grandTotal: appState.settings.maintenanceFee
      };
      currentReadings.push(record);
    }

    if (record.currentReading > 0) readUsers++;
    totalUnits += record.unitsUsed;
    currentBilling += record.currentTotal;
    arrearsTotal += record.previousArrears;

    if (record.paymentStatus === 'PAID') {
      paidAmount += record.grandTotal;
      paidCount++;
    }

    const matchSearch = cust.firstName.toLowerCase().includes(search) || 
                        cust.lastName.toLowerCase().includes(search) ||
                        cust.houseNo.toLowerCase().includes(search) ||
                        cust.customerCode.toLowerCase().includes(search);
    const matchZone = !zoneFilter || cust.zone === zoneFilter;
    const matchStatus = !statusFilter || record.paymentStatus === statusFilter;

    if (!matchSearch || !matchZone || !matchStatus) return;

    const tr = document.createElement('tr');
    tr.className = record.paymentStatus === 'PAID' ? 'row-paid' : '';
    tr.innerHTML = `
      <td><input type="checkbox" class="reading-row-check" data-id="${cust.id}"></td>
      <td>${cust.seqNo}</td>
      <td><strong>${cust.customerCode}</strong></td>
      <td>${cust.firstName} ${cust.lastName}</td>
      <td>${cust.houseNo} <span class="text-muted">(${cust.zone})</span></td>
      <td class="text-right">
        <input type="number" class="reading-input prev-input" data-id="${cust.id}" value="${record.previousReading}" step="any">
      </td>
      <td class="text-right">
        <input type="number" class="reading-input curr-input" data-id="${cust.id}" value="${record.currentReading}" step="any">
      </td>
      <td class="text-right font-bold text-primary">${record.unitsUsed.toFixed(1)}</td>
      <td class="text-right">${record.currentTotal.toFixed(2)}</td>
      <td class="text-right text-danger">${record.previousArrears > 0 ? record.previousArrears.toFixed(2) : '-'}</td>
      <td class="text-right font-bold">${record.grandTotal.toFixed(2)}</td>
      <td class="text-center">
        <span class="badge ${record.paymentStatus === 'PAID' ? 'badge-paid' : 'badge-unpaid'}">
          ${record.paymentStatus === 'PAID' ? 'ชำระแล้ว' : 'ค้างชำระ'}
        </span>
      </td>
      <td class="text-center no-print">
        <button class="btn btn-outline btn-sm toggle-paid-btn" data-id="${cust.id}">
          ${record.paymentStatus === 'PAID' ? 'ยกเลิกจ่าย' : 'รับชำระ'}
        </button>
        <button class="btn btn-primary btn-sm view-rcpt-btn" data-id="${cust.id}" title="พิมพ์ใบเสร็จ">
          🧾
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  // Attach Change Events
  tbody.querySelectorAll('.curr-input').forEach(inp => {
    inp.addEventListener('change', async (e) => {
      const custId = parseInt(e.target.dataset.id, 10);
      const val = parseFloat(e.target.value) || 0;
      const rec = currentReadings.find(r => r.customerId === custId);
      if (rec) {
        rec.currentReading = val;
        await syncReadingToPHP(custId, rec);
        await loadCycleReadings(appState.currentCycle);
        renderReadingsTable();
      }
    });
  });

  tbody.querySelectorAll('.prev-input').forEach(inp => {
    inp.addEventListener('change', async (e) => {
      const custId = parseInt(e.target.dataset.id, 10);
      const val = parseFloat(e.target.value) || 0;
      const rec = currentReadings.find(r => r.customerId === custId);
      if (rec) {
        rec.previousReading = val;
        await syncReadingToPHP(custId, rec);
        await loadCycleReadings(appState.currentCycle);
        renderReadingsTable();
      }
    });
  });

  tbody.querySelectorAll('.toggle-paid-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const custId = parseInt(e.target.dataset.id, 10);
      try {
        const res = await fetch(`${API_BASE}/readings.php?cycle=${appState.currentCycle}&action=toggle-paid&customerId=${custId}`, {
          method: 'POST'
        });
        if (res.ok) {
          await loadCycleReadings(appState.currentCycle);
          renderReadingsTable();
        }
      } catch (err) {
        console.error(err);
      }
    });
  });

  tbody.querySelectorAll('.view-rcpt-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const custId = parseInt(e.target.dataset.id, 10);
      const idx = appState.customers.findIndex(c => c.id === custId);
      if (idx !== -1) {
        appState.selectedReceiptCustomerIndex = idx;
        document.querySelector('[data-tab="tab-receipts"]').click();
      }
    });
  });

  // Update Summary Stats Bar
  document.getElementById('stat-total-users').textContent = `${totalUsers} ครัวเรือน`;
  document.getElementById('stat-read-users').textContent = `${readUsers}`;
  document.getElementById('stat-total-units').textContent = `${totalUnits.toFixed(1)} ลบ.ม.`;
  document.getElementById('stat-avg-units').textContent = totalUsers > 0 ? (totalUnits / totalUsers).toFixed(1) : 0;
  document.getElementById('stat-current-billing').textContent = `${currentBilling.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;
  document.getElementById('stat-arrears-total').textContent = `${arrearsTotal.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;
  document.getElementById('stat-paid-amount').textContent = `${paidAmount.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;
  document.getElementById('stat-paid-count').textContent = `${paidCount}`;
}

async function syncReadingToPHP(customerId, record) {
  try {
    await fetch(`${API_BASE}/readings.php?cycle=${appState.currentCycle}&action=save`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        items: [{
          customerId,
          previousReading: record.previousReading,
          currentReading: record.currentReading,
          previousArrears: record.previousArrears
        }]
      })
    });
  } catch (e) {
    console.error('Error syncing reading to PHP:', e);
  }
}

// -------------------------------------------------------------
// TAB 2: Receipt View
// -------------------------------------------------------------
async function renderReceipt() {
  const custSelect = document.getElementById('receipt-customer-select');
  if (!custSelect) return;

  custSelect.innerHTML = appState.customers.map((c, i) => 
    `<option value="${i}" ${i === appState.selectedReceiptCustomerIndex ? 'selected' : ''}>
      ${c.seqNo}. ${c.customerCode} - ${c.firstName} ${c.lastName} (${c.houseNo})
    </option>`
  ).join('');

  const cust = appState.customers[appState.selectedReceiptCustomerIndex];
  if (!cust) return;

  try {
    const res = await fetch(`${API_BASE}/receipts.php?cycle=${appState.currentCycle}&customerId=${cust.id}`);
    if (res.ok) {
      const data = await res.json();
      document.getElementById('rcpt-no').textContent = data.receiptNo;
      document.getElementById('rcpt-date').textContent = data.issueDateText;
      document.getElementById('rcpt-cycle').textContent = data.billingCycleText;
      document.getElementById('rcpt-name').textContent = data.customer.name;
      document.getElementById('rcpt-payer-name').textContent = data.customer.name;
      document.getElementById('rcpt-house').textContent = data.customer.houseNo;
      document.getElementById('rcpt-zone').textContent = data.customer.zone;
      document.getElementById('rcpt-code').textContent = data.customer.code;
      document.getElementById('rcpt-seq').textContent = data.customer.seq;
      document.getElementById('rcpt-serial').textContent = data.customer.meterSerial;

      document.getElementById('rcpt-prev-reading').textContent = data.meter.previous.toFixed(2);
      document.getElementById('rcpt-curr-reading').textContent = data.meter.current.toFixed(2);
      document.getElementById('rcpt-units').textContent = data.meter.unitsUsed.toFixed(2);
      document.getElementById('rcpt-rate').textContent = data.meter.ratePerUnit.toFixed(2);
      document.getElementById('rcpt-water-charge').textContent = data.breakdown.waterCharge.toFixed(2);
      document.getElementById('rcpt-maintenance-fee').textContent = data.breakdown.maintenanceFee.toFixed(2);
      document.getElementById('rcpt-current-total').textContent = data.breakdown.currentTotal.toFixed(2);
      document.getElementById('rcpt-arrears').textContent = data.breakdown.previousArrears.toFixed(2);
      document.getElementById('rcpt-grand-total').textContent = data.breakdown.grandTotal.toFixed(2);
      document.getElementById('rcpt-baht-text').textContent = data.totalAmountTextTh;

      // Update PromptPay QR Code dynamically
      const grandTotal = parseFloat(data.breakdown.grandTotal || 0);
      const qrImg = document.getElementById('rcpt-promptpay-img');
      if (qrImg) {
        const qrAmount = grandTotal > 0 ? grandTotal.toFixed(2) : '150.00';
        qrImg.src = `https://promptpay.io/0812345678/${qrAmount}.png`;
      }
      const qrAmtEl = document.getElementById('rcpt-promptpay-amount');
      if (qrAmtEl) {
        qrAmtEl.textContent = `${grandTotal.toFixed(2)} บาท`;
      }
    }
  } catch (err) {
    console.error(err);
  }
}

// -------------------------------------------------------------
// TAB 3: Vouchers (ฎีกาเบิกจ่าย / ใบสำคัญรับเงิน)
// -------------------------------------------------------------
async function renderVouchers() {
  const container = document.getElementById('voucher-print-area');
  const cycleCode = appState.currentCycle;
  document.getElementById('voucher-cycle-text').textContent = `งวดประจำเดือน ${cycleCode}`;

  try {
    const res = await fetch(`${API_BASE}/vouchers.php?cycle=${cycleCode}`);
    if (res.ok) {
      const data = await res.json();
      container.innerHTML = data.vouchers.map(v => `
        <div class="voucher-document">
          <div class="voucher-header">
            <h3>ใบสำคัญรับเงิน / ฎีกาเบิกจ่ายเงินกองทุนประปาหมู่บ้าน</h3>
            <h4>${appState.settings.villageName} ${appState.settings.subdistrict}</h4>
            <p class="text-muted">เลขที่เอกสาร: <strong>${v.voucherNo}</strong> | วันที่: ${v.date}</p>
          </div>
          <div class="voucher-body">
            <p>ข้าพเจ้า <strong>${v.recipientName}</strong> ตำแหน่ง <strong>${v.recipientPosition}</strong></p>
            <p>ได้รับเงินจากกองทุนระบบประปาหมู่บ้านวังยาง สำหรับ:</p>
            <div class="highlight-box">
              <strong>วัตถุประสงค์การเบิกจ่าย:</strong> ${v.calculationBasis}
            </div>
            <p>เป็นจำนวนเงินทั้งสิ้น: <strong class="text-primary font-bold" style="font-size: 18px;">${Number(v.amount).toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท</strong></p>
            <p>จำนวนเงินตัวอักษร: <strong>( ${v.amountTextTh} )</strong></p>
            <p>ข้าพเจ้าได้รับเงินจำนวนดังกล่าวข้างต้นไว้เป็นการถูกต้องเรียบร้อยแล้ว จึงลงลายมือชื่อไว้เป็นหลักฐาน</p>
          </div>
          <div class="receipt-signatures" style="margin-top: 30px;">
            <div class="sig-block">
              <div class="sig-line">...................................................</div>
              <div class="sig-title">( ${v.recipientName} )</div>
              <div class="sig-role">ผู้รับเงิน</div>
            </div>
            <div class="sig-block">
              <div class="sig-line">...................................................</div>
              <div class="sig-title">( นางจำเนียร ตรวจบัญชี )</div>
              <div class="sig-role">เหรัญญิก / ผู้จ่ายเงิน</div>
            </div>
            <div class="sig-block">
              <div class="sig-line">...................................................</div>
              <div class="sig-title">( ${appState.settings.committeeChairman} )</div>
              <div class="sig-role">ประธานกรรมการ / ผู้อนุมัติ</div>
            </div>
          </div>
        </div>
      `).join('');
    }
  } catch (e) {
    console.error(e);
  }
}

// -------------------------------------------------------------
// TAB 4: Monthly Financial Report
// -------------------------------------------------------------
async function renderFinancialReport() {
  const [m, y] = appState.currentCycle.split('-');
  document.getElementById('rpt-fin-month-year').textContent = `งวดประจำเดือน ${appState.currentCycle}`;
  document.getElementById('fin-cycle-display').textContent = `งวด ${appState.currentCycle}`;

  try {
    const res = await fetch(`${API_BASE}/financials.php?cycle=${appState.currentCycle}`);
    if (res.ok) {
      const report = await res.json();

      document.getElementById('fin-rev-current').textContent = report.revenues.revWaterMaintenance.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-rev-arrears').textContent = report.revenues.revCollectedArrears.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-rev-new-meter').textContent = report.revenues.revNewMeterFee.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-rev-interest').textContent = report.revenues.revBankInterest.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-rev-other').textContent = report.revenues.revOther.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-total-revenue').textContent = report.revenues.totalRevenue.toLocaleString('th-TH', { minimumFractionDigits: 2 });

      document.getElementById('fin-exp-caretaker').textContent = report.expenses.expCaretaker.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-exp-committee').textContent = report.expenses.expCommittee.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-collector-basis').textContent = `(${report.expenses.collectorBasis})`;
      document.getElementById('fin-exp-collector').textContent = report.expenses.expCollector.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-exp-electricity').textContent = report.expenses.expElectricity.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-exp-repairs').textContent = report.expenses.expSuppliesRepairs.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-exp-other').textContent = report.expenses.expOther.toLocaleString('th-TH', { minimumFractionDigits: 2 });
      document.getElementById('fin-total-expense').textContent = report.expenses.totalExpense.toLocaleString('th-TH', { minimumFractionDigits: 2 });

      const netEl = document.getElementById('fin-net-profit');
      netEl.textContent = `${report.summary.netProfitLoss >= 0 ? '+' : ''}${report.summary.netProfitLoss.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`;
      netEl.className = report.summary.netProfitLoss >= 0 ? 'val text-success' : 'val text-danger';

      document.getElementById('fin-prev-balance').textContent = `${report.summary.prevAccumulatedBalance.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`;
      document.getElementById('fin-total-balance').textContent = `${report.summary.totalAccumulatedBalance.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`;
      document.getElementById('fin-balance-text-th').textContent = report.summary.totalAccumulatedTextTh;

      document.getElementById('fin-cash-in-hand').textContent = `${report.summary.cashInHand.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`;
      document.getElementById('fin-bank-deposit').textContent = `${report.summary.bankDeposit.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`;
    }
  } catch (e) {
    console.error(e);
  }
}

// -------------------------------------------------------------
// TAB 4.1: Chart.js Analytics (น้ำสูญเสีย & ผลประกอบการ 6 เดือน)
// -------------------------------------------------------------
let waterTrendChart = null;
let financialTrendChart = null;

async function renderFinancialReportCharts() {
  try {
    const res = await fetch(`${API_BASE}/financials.php?cycle=${appState.currentCycle}`);
    if (!res.ok) return;
    const data = await res.json();

    // 1. Update Operational KPI Cards
    if (data.operational) {
      const op = data.operational;
      const elNrw = document.getElementById('fin-stat-nrw');
      if (elNrw) elNrw.textContent = `${op.nrwPct}%`;
      const elColl = document.getElementById('fin-stat-collection-rate');
      if (elColl) elColl.textContent = `${op.collectionEfficiency}%`;
      const elCost = document.getElementById('fin-stat-unit-cost');
      if (elCost) elCost.textContent = `${op.unitCost.toFixed(2)} บาท`;
      const elElec = document.getElementById('fin-stat-elec-pct');
      if (elElec && data.expenses) {
        const elecPct = (data.expenses.expElectricity / (data.expenses.totalExpense || 1)) * 100;
        elElec.textContent = `${elecPct.toFixed(1)}%`;
      }
    }

    // 2. Render Chart 1: Water Production vs Consumption & NRW
    const canvasWater = document.getElementById('chartWaterTrend');
    if (canvasWater && typeof Chart !== 'undefined' && data.trends) {
      if (waterTrendChart) {
        waterTrendChart.destroy();
      }
      const ctx1 = canvasWater.getContext('2d');
      waterTrendChart = new Chart(ctx1, {
        type: 'bar',
        data: {
          labels: data.trends.months,
          datasets: [
            {
              label: 'น้ำดิบสูบผลิต (ลบ.ม.)',
              data: data.trends.production,
              backgroundColor: 'rgba(2, 132, 199, 0.75)',
              borderColor: '#0284c7',
              borderWidth: 1,
              borderRadius: 4,
              order: 2
            },
            {
              label: 'น้ำจำหน่ายตามมาตร (ลบ.ม.)',
              data: data.trends.consumption,
              backgroundColor: 'rgba(34, 197, 94, 0.75)',
              borderColor: '#16a34a',
              borderWidth: 1,
              borderRadius: 4,
              order: 2
            },
            {
              label: 'อัตราสูญเสีย NRW (%)',
              data: data.trends.nrw,
              type: 'line',
              borderColor: '#ef4444',
              backgroundColor: 'rgba(239, 68, 68, 0.15)',
              borderWidth: 2.5,
              pointRadius: 4,
              pointHoverRadius: 6,
              yAxisID: 'y1',
              order: 1
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          scales: {
            y: {
              beginAtZero: true,
              title: { display: true, text: 'ปริมาณน้ำ (ลบ.ม.)' }
            },
            y1: {
              beginAtZero: true,
              max: 25,
              position: 'right',
              title: { display: true, text: 'น้ำสูญเสีย (%)' },
              grid: { drawOnChartArea: false }
            }
          },
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12 } }
          }
        }
      });
    }

    // 3. Render Chart 2: Financials & Fund Balance
    const canvasFin = document.getElementById('chartFinancialTrend');
    if (canvasFin && typeof Chart !== 'undefined' && data.trends) {
      if (financialTrendChart) {
        financialTrendChart.destroy();
      }
      const ctx2 = canvasFin.getContext('2d');
      financialTrendChart = new Chart(ctx2, {
        type: 'bar',
        data: {
          labels: data.trends.months,
          datasets: [
            {
              label: 'รายรับ (บาท)',
              data: data.trends.revenue,
              backgroundColor: 'rgba(16, 185, 129, 0.75)',
              borderColor: '#10b981',
              borderWidth: 1,
              borderRadius: 4,
              yAxisID: 'y'
            },
            {
              label: 'รายจ่าย (บาท)',
              data: data.trends.expense,
              backgroundColor: 'rgba(244, 63, 94, 0.75)',
              borderColor: '#f43f5e',
              borderWidth: 1,
              borderRadius: 4,
              yAxisID: 'y'
            },
            {
              label: 'เงินกองทุนสะสมสุทธิ (บาท)',
              data: data.trends.fundBalance,
              type: 'line',
              borderColor: '#0284c7',
              backgroundColor: 'rgba(2, 132, 199, 0.15)',
              borderWidth: 2.5,
              pointRadius: 4,
              pointHoverRadius: 6,
              yAxisID: 'y1'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          scales: {
            y: {
              beginAtZero: true,
              title: { display: true, text: 'รายรับ-รายจ่าย (บาท)' }
            },
            y1: {
              position: 'right',
              title: { display: true, text: 'เงินกองทุนสะสม (บาท)' },
              grid: { drawOnChartArea: false }
            }
          },
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12 } }
          }
        }
      });
    }
  } catch (err) {
    console.error('Error rendering financial charts:', err);
  }
}

// -------------------------------------------------------------
// TAB 4.2: Delinquent Accounts & Debt Aging Ledger (แบบ กค.4)
// -------------------------------------------------------------
let arrearsDebtorsList = [];

async function renderArrearsTable() {
  const tbody = document.getElementById('arrears-table-body');
  if (!tbody) return;

  try {
    const res = await fetch(`${API_BASE}/arrears.php?cycle=${appState.currentCycle}`);
    if (!res.ok) return;
    const data = await res.json();
    arrearsDebtorsList = data.debtors || [];

    // Summary cards
    const totalDebtorsEl = document.getElementById('arrears-total-debtors');
    if (totalDebtorsEl) totalDebtorsEl.textContent = `${data.summary.totalDebtors} ราย`;
    const totalDebtEl = document.getElementById('arrears-total-amount');
    if (totalDebtEl) totalDebtEl.textContent = `${data.summary.totalDebt.toLocaleString('th-TH', { minimumFractionDigits: 2 })}`;
    
    const count1m = document.getElementById('arrears-count-1m');
    if (count1m) count1m.textContent = `${data.summary.count1M} ราย`;
    const amt1m = document.getElementById('arrears-amount-1m');
    if (amt1m) amt1m.textContent = `${data.summary.amount1M.toLocaleString('th-TH', { minimumFractionDigits: 2 })}`;

    const count2m = document.getElementById('arrears-count-2m');
    if (count2m) count2m.textContent = `${data.summary.count2M} ราย`;
    const amt2m = document.getElementById('arrears-amount-2m');
    if (amt2m) amt2m.textContent = `${data.summary.amount2M.toLocaleString('th-TH', { minimumFractionDigits: 2 })}`;

    const count3m = document.getElementById('arrears-count-3m');
    if (count3m) count3m.textContent = `${data.summary.count3M} ราย`;
    const amt3m = document.getElementById('arrears-amount-3m');
    if (amt3m) amt3m.textContent = `${data.summary.amount3M.toLocaleString('th-TH', { minimumFractionDigits: 2 })}`;

    const cycleDisp = document.getElementById('arrears-cycle-display');
    if (cycleDisp) cycleDisp.textContent = `งวดประจำเดือน ${data.cycle.code}`;

    filterAndRenderArrearsRows();
  } catch (err) {
    console.error('Error loading arrears:', err);
  }
}

function filterAndRenderArrearsRows() {
  const tbody = document.getElementById('arrears-table-body');
  if (!tbody) return;

  const search = (document.getElementById('arrears-search-input')?.value || '').toLowerCase().trim();
  const agingFilter = document.getElementById('arrears-aging-filter')?.value || '';

  const filtered = arrearsDebtorsList.filter(d => {
    const matchSearch = d.name.toLowerCase().includes(search) ||
                        d.houseNo.toLowerCase().includes(search) ||
                        d.customerCode.toLowerCase().includes(search);
    const matchAging = !agingFilter || d.urgency === agingFilter;
    return matchSearch && matchAging;
  });

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="11" class="text-center text-muted" style="padding: 24px;">🎉 ไม่มีลูกหนี้ค้างชำระตามเงื่อนไขที่เลือก</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map((d, idx) => `
    <tr>
      <td>${idx + 1}</td>
      <td><strong>${d.customerCode}</strong></td>
      <td><strong>${d.name}</strong></td>
      <td>${d.houseNo}</td>
      <td>${d.zone}</td>
      <td><a href="tel:${d.phone}" style="color: #0284c7; text-decoration: none; font-weight: 600;">📞 ${d.phone || '-'}</a></td>
      <td>${d.meterSerial}</td>
      <td class="text-center"><strong>${d.monthsOverdue}</strong> งวด</td>
      <td class="text-right"><strong class="text-danger font-bold">${d.totalDebt.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿</strong></td>
      <td class="text-center">
        <span class="${d.badgeClass}">${d.urgencyText}</span>
      </td>
      <td class="text-center no-print" style="white-space: nowrap;">
        <button class="btn btn-outline btn-sm btn-print-notice" data-code="${d.customerCode}" title="พิมพ์หนังสือเตือน">✉️ หนังสือเตือน</button>
        <button class="btn btn-secondary btn-sm btn-pay-arrears" data-id="${d.customerId}" title="ตัดรับชำระ">✅ รับชำระ</button>
      </td>
    </tr>
  `).join('');

  // Wire buttons
  tbody.querySelectorAll('.btn-print-notice').forEach(btn => {
    btn.addEventListener('click', () => {
      const code = btn.getAttribute('data-code');
      const debtor = arrearsDebtorsList.find(x => x.customerCode === code);
      if (debtor) openArrearsNoticeModal(debtor);
    });
  });

  tbody.querySelectorAll('.btn-pay-arrears').forEach(btn => {
    btn.addEventListener('click', async () => {
      const custId = btn.getAttribute('data-id');
      if (confirm('ยืนยันการบันทึกตัดรับชำระเงินค่าน้ำสำหรับรายนี้?')) {
        await togglePaymentStatus(custId);
        await renderArrearsTable();
        await loadCycleReadings(appState.currentCycle);
        renderReadingsTable();
      }
    });
  });
}

function openArrearsNoticeModal(debtor) {
  const body = document.getElementById('arrears-notice-body');
  if (!body) return;

  const today = new Date();
  const thaiMonths = ["มกราคม", "กุมภาพันธ์", "มีนาคม", "เมษายน", "พฤษภาคม", "มิถุนายน", "กรกฎาคม", "สิงหาคม", "กันยายน", "ตุลาคม", "พฤศจิกายน", "ธันวาคม"];
  const dateStr = `${today.getDate()} ${thaiMonths[today.getMonth()]} พ.ศ. ${today.getFullYear() + 543}`;

  body.innerHTML = `
    <div style="border: 2px solid #0f172a; padding: 24px; border-radius: 8px; background: #fff; line-height: 1.7; font-size: 14.5px;">
      <div style="text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px;">
        <h3 style="font-size: 19px; font-weight: 700; margin: 0; color: #0f172a;">กองทุนระบบการประปาหมู่บ้านวังยาง</h3>
        <p style="font-size: 13px; color: #475569; margin: 3px 0 0 0;">หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</p>
        <div style="display: inline-block; background: #dc2626; color: #fff; font-size: 13px; font-weight: 700; padding: 3px 16px; border-radius: 4px; margin-top: 8px;">
          หนังสือเตือนให้ชำระหนี้ค่าน้ำประปา (ฉบับที่ ${debtor.monthsOverdue >= 2 ? '2' : '1'})
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; font-size: 13.5px; margin-bottom: 14px;">
        <div><strong>ที่:</strong> กปภ.วย. ๐๓/ว${String(debtor.seqNo).padStart(3, '0')}</div>
        <div><strong>วันที่:</strong> ${dateStr}</div>
      </div>

      <div style="margin-bottom: 12px;">
        <strong>เรื่อง:</strong> แจ้งเตือนให้ชำระเงินค่าน้ำประปาค้างชำระ<br>
        <strong>เรียน:</strong> คุณ${debtor.name} (บ้านเลขที่ ${debtor.houseNo} โซน: ${debtor.zone})
      </div>

      <div style="text-indent: 36px; text-align: justify; margin-bottom: 14px;">
        ตามที่ท่านได้เป็นสมาชิกผู้ใช้น้ำประปาของการประปาหมู่บ้านวังยาง หมายเลขผู้ใช้น้ำ <strong>${debtor.customerCode}</strong> (หมายเลขมิเตอร์: ${debtor.meterSerial}) นั้น จากการตรวจสอบบัญชีคุมลูกหนี้ค่าน้ำประปา ปรากฏว่าท่านยังไม่ได้ชำระค่าน้ำประปาจำนวน <strong>${debtor.monthsOverdue} งวดติดต่อกัน</strong> รวมเป็นยอดเงินหนี้ค้างชำระทั้งสิ้น <strong>${debtor.totalDebt.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท (${debtor.totalDebtTextTh})</strong>
      </div>

      <div style="text-indent: 36px; text-align: justify; margin-bottom: 16px;">
        ดังนั้น เพื่อให้การบริหารจัดการระบบประปาหมู่บ้านเป็นไปด้วยความเรียบร้อย คณะกรรมการฯ จึงขอแจ้งให้ท่านนำเงินไปชำระ ณ ที่ทำการกองทุนประปาหมู่บ้านวังยาง ภายในกำหนด <strong>๗ วัน นับตั้งแต่วันที่ได้รับหนังสือฉบับนี้</strong>
      </div>

      <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 10px 14px; font-size: 13px; color: #991b1b; margin-bottom: 24px;">
        <strong>⚠️ คำเตือน:</strong> หากพ้นกำหนดเวลาดังกล่าวข้างต้น คณะกรรมการการประปาหมู่บ้านจำเป็นต้องดำเนินการระงับการส่งจ่ายน้ำประปาชั่วคราว (ถอดมาตรวัดน้ำ) ตามระเบียบข้อบังคับกิจการประปาหมู่บ้าน พ.ศ. 2544 ต่อไป
      </div>

      <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
        <div style="text-align: center; width: 260px;">
          <div>ขอแสดงความนับถือ</div>
          <div style="margin-top: 35px;">( ${appState.settings.committeeChairman} )</div>
          <div style="font-size: 13px; color: #475569;">ประธานกรรมการการประปาหมู่บ้านวังยาง</div>
        </div>
      </div>
    </div>
  `;

  openModal('arrears-notice-modal');
}

function printArrearsNotice() {
  const content = document.getElementById('arrears-notice-body').innerHTML;
  const printWin = window.open('', '', 'width=800,height=650');
  printWin.document.write(`
    <html>
    <head>
      <title>พิมพ์หนังสือเตือนชำระค่าน้ำประปา</title>
      <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
      <style>
        body { font-family: 'Sarabun', sans-serif; padding: 25px; }
      </style>
    </head>
    <body>
      ${content}
      <script>
        window.onload = function() { window.print(); window.close(); }
      <\/script>
    </body>
    </html>
  `);
  printWin.document.close();
}

async function togglePaymentStatus(custId) {
  try {
    const res = await fetch(`${API_BASE}/readings.php?cycle=${appState.currentCycle}&action=toggle-paid&customerId=${custId}`, {
      method: 'POST'
    });
    return res.ok;
  } catch (err) {
    console.error('Error toggling payment status:', err);
    return false;
  }
}

// -------------------------------------------------------------
// TAB 5: Customers Table
// -------------------------------------------------------------
function renderCustomersTable() {
  const tbody = document.getElementById('customers-table-body');
  if (!tbody) return;
  const search = (document.getElementById('cust-search')?.value || '').toLowerCase().trim();

  tbody.innerHTML = '';

  appState.customers.forEach(cust => {
    const match = cust.firstName.toLowerCase().includes(search) ||
                  cust.lastName.toLowerCase().includes(search) ||
                  cust.houseNo.toLowerCase().includes(search) ||
                  (cust.phone && cust.phone.toLowerCase().includes(search)) ||
                  cust.customerCode.toLowerCase().includes(search);
    if (!match) return;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${cust.seqNo}</td>
      <td><strong>${cust.customerCode}</strong></td>
      <td>${cust.firstName} ${cust.lastName}</td>
      <td>${cust.houseNo}</td>
      <td>${cust.zone}</td>
      <td><a href="tel:${cust.phone || ''}" style="color: #0284c7; text-decoration: none; font-weight: 600;">📞 ${cust.phone || '-'}</a></td>
      <td>${cust.meterSerial || '-'}</td>
      <td><span class="badge badge-paid">${cust.status}</span></td>
      <td class="text-center no-print">
        <button class="btn btn-outline btn-sm edit-cust-btn" data-id="${cust.id}">✏️ แก้ไข</button>
        <button class="btn btn-outline btn-sm del-cust-btn text-danger" data-id="${cust.id}">🗑️</button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  tbody.querySelectorAll('.del-cust-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const id = parseInt(e.target.dataset.id, 10);
      if (confirm('ยืนยันการลบสมาชิกผู้ใช้น้ำรายนี้?')) {
        await fetch(`${API_BASE}/customers.php?id=${id}`, { method: 'DELETE' });
        await loadCustomers();
        renderCustomersTable();
        renderReadingsTable();
        updateZoneFilter();
      }
    });
  });

  tbody.querySelectorAll('.edit-cust-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      const id = parseInt(e.target.dataset.id, 10);
      const cust = appState.customers.find(c => c.id === id);
      if (cust) {
        document.getElementById('cust-id').value = cust.id;
        document.getElementById('form-cust-code').value = cust.customerCode;
        document.getElementById('form-cust-seq').value = cust.seqNo;
        document.getElementById('form-cust-first').value = cust.firstName;
        document.getElementById('form-cust-last').value = cust.lastName;
        document.getElementById('form-cust-house').value = cust.houseNo;
        document.getElementById('form-cust-zone').value = cust.zone;
        document.getElementById('form-cust-phone').value = cust.phone || '';
        document.getElementById('form-cust-serial').value = cust.meterSerial || '';
        document.getElementById('cust-modal-title').textContent = 'แก้ไขข้อมูลสมาชิกผู้ใช้น้ำ';
        openModal('customer-modal');
      }
    });
  });
}

function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

// -------------------------------------------------------------
// App Lifecycle & Event Bindings
// -------------------------------------------------------------
window.addEventListener('DOMContentLoaded', async () => {
  initNavigation();
  await initAppData();

  // Export Excel / CSV Button
  document.getElementById('btn-export-excel')?.addEventListener('click', () => {
    window.location.href = `${API_BASE}/export_excel.php?cycle=${appState.currentCycle}`;
  });

  // Open New Billing Cycle
  document.getElementById('btn-open-cycle')?.addEventListener('click', () => {
    const current = appState.currentCycle;
    const [m, y] = current.split('-').map(Number);
    let nextM = m + 1;
    let nextY = y;
    if (nextM > 12) { nextM = 1; nextY++; }
    document.getElementById('form-cycle-month').value = nextM;
    document.getElementById('form-cycle-year').value = nextY;
    document.getElementById('form-cycle-code').value = `${nextM}-${nextY}`;
    document.getElementById('form-cycle-prev').value = current;
    openModal('cycle-modal');
  });

  document.getElementById('cycle-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const month = parseInt(document.getElementById('form-cycle-month').value, 10);
    const yearBe = parseInt(document.getElementById('form-cycle-year').value, 10);
    const cycleCode = document.getElementById('form-cycle-code').value.trim();
    const prevCycleCode = document.getElementById('form-cycle-prev').value.trim();

    try {
      const res = await fetch(`${API_BASE}/cycles.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cycleCode, month, yearBe, prevCycleCode })
      });
      if (res.ok) {
        const sel = document.getElementById('cycle-select');
        const opt = document.createElement('option');
        opt.value = cycleCode;
        opt.textContent = `ประจำเดือน ${month}/${yearBe} (${cycleCode})`;
        sel.prepend(opt);
        sel.value = cycleCode;
        appState.currentCycle = cycleCode;
        const scn = document.getElementById('sidebar-cycle-name');
        if (scn) scn.textContent = opt.textContent;
        await loadCycleReadings(cycleCode);
        closeModal('cycle-modal');
        renderReadingsTable();
        renderReceipt();
        renderVouchers();
        renderFinancialReport();
        alert(`✅ เปิดรอบบิลใหม่ ${cycleCode} สำเร็จแล้ว! ดึงเลขมิเตอร์เดือนเก่าและทบยอดค้างชำระเรียบร้อย`);
      }
    } catch (err) {
      console.error(err);
      alert('เกิดข้อผิดพลาดในการเปิดรอบบิล');
    }
  });

  // Cycle Select Change
  document.getElementById('cycle-select')?.addEventListener('change', async (e) => {
    appState.currentCycle = e.target.value;
    const scn = document.getElementById('sidebar-cycle-name');
    if (scn) scn.textContent = `${e.target.options[e.target.selectedIndex].text}`;
    await loadCycleReadings(appState.currentCycle);
    renderReadingsTable();
    renderReceipt();
    renderVouchers();
    renderFinancialReport();
    renderFinancialReportCharts();
    renderArrearsTable();
  });

  // Receipt Select and Prev/Next
  document.getElementById('receipt-customer-select')?.addEventListener('change', (e) => {
    appState.selectedReceiptCustomerIndex = parseInt(e.target.value, 10);
    renderReceipt();
  });

  document.getElementById('btn-prev-receipt')?.addEventListener('click', () => {
    if (appState.selectedReceiptCustomerIndex > 0) {
      appState.selectedReceiptCustomerIndex--;
      renderReceipt();
    }
  });

  document.getElementById('btn-next-receipt')?.addEventListener('click', () => {
    if (appState.selectedReceiptCustomerIndex < appState.customers.length - 1) {
      appState.selectedReceiptCustomerIndex++;
      renderReceipt();
    }
  });

  // Search & Filter
  document.getElementById('reading-search-input')?.addEventListener('input', renderReadingsTable);
  document.getElementById('reading-zone-filter')?.addEventListener('change', renderReadingsTable);
  document.getElementById('reading-status-filter')?.addEventListener('change', renderReadingsTable);
  document.getElementById('cust-search')?.addEventListener('input', renderCustomersTable);
  document.getElementById('arrears-search-input')?.addEventListener('input', filterAndRenderArrearsRows);
  document.getElementById('arrears-aging-filter')?.addEventListener('change', filterAndRenderArrearsRows);
  document.getElementById('btn-arrears-refresh')?.addEventListener('click', renderArrearsTable);

  // Add Customer Form Submit
  document.getElementById('btn-add-customer')?.addEventListener('click', () => {
    document.getElementById('cust-form').reset();
    document.getElementById('cust-id').value = '';
    document.getElementById('form-cust-seq').value = appState.customers.length + 1;
    document.getElementById('form-cust-code').value = `WY-${String(appState.customers.length + 1).padStart(3, '0')}`;
    document.getElementById('cust-modal-title').textContent = 'เพิ่มสมาชิกผู้ใช้น้ำใหม่';
    openModal('customer-modal');
  });

  document.getElementById('cust-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const idVal = document.getElementById('cust-id').value;
    const custData = {
      customerCode: document.getElementById('form-cust-code').value.trim(),
      seqNo: parseInt(document.getElementById('form-cust-seq').value, 10),
      firstName: document.getElementById('form-cust-first').value.trim(),
      lastName: document.getElementById('form-cust-last').value.trim(),
      houseNo: document.getElementById('form-cust-house').value.trim(),
      zone: document.getElementById('form-cust-zone').value.trim(),
      phone: document.getElementById('form-cust-phone').value.trim(),
      meterSerial: document.getElementById('form-cust-serial').value.trim(),
      status: 'ACTIVE'
    };

    if (idVal) {
      await fetch(`${API_BASE}/customers.php?id=${idVal}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(custData)
      });
    } else {
      await fetch(`${API_BASE}/customers.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(custData)
      });
    }

    await loadCustomers();
    closeModal('customer-modal');
    renderCustomersTable();
    renderReadingsTable();
    updateZoneFilter();
  });

  // Check all readings checkboxes
  document.getElementById('check-all-readings')?.addEventListener('change', (e) => {
    const isChecked = e.target.checked;
    document.querySelectorAll('.reading-row-check').forEach(cb => { cb.checked = isChecked; });
  });

  // Batch Mark Paid
  document.getElementById('btn-batch-mark-paid')?.addEventListener('click', async () => {
    const checkedBoxes = Array.from(document.querySelectorAll('.reading-row-check:checked'));
    if (checkedBoxes.length === 0) {
      alert('กรุณาเลือกรายการที่ต้องการตัดรับชำระอย่างน้อย 1 รายการ');
      return;
    }
    if (!confirm(`ยืนยันการตัดรับชำระเงินสำหรับ ${checkedBoxes.length} รายการที่เลือกใช่หรือไม่?`)) return;
    for (const cb of checkedBoxes) {
      const custId = parseInt(cb.dataset.id, 10);
      try {
        await fetch(`${API_BASE}/readings.php?cycle=${appState.currentCycle}&action=toggle-paid&customerId=${custId}`, { method: 'POST' });
      } catch (err) {
        console.error(err);
      }
    }
    await loadCycleReadings(appState.currentCycle);
    renderReadingsTable();
    alert(`✅ ตัดรับชำระเงินสำหรับ ${checkedBoxes.length} รายการเรียบร้อยแล้ว!`);
  });

  // Recalculate All Readings
  document.getElementById('btn-recalc-all')?.addEventListener('click', async () => {
    const currentReadings = appState.readings[appState.currentCycle] || [];
    const items = currentReadings.map(r => ({
      customerId: r.customerId,
      previousReading: r.previousReading,
      currentReading: r.currentReading,
      previousArrears: r.previousArrears
    }));
    try {
      const res = await fetch(`${API_BASE}/readings.php?cycle=${appState.currentCycle}&action=save`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ items })
      });
      if (res.ok) {
        await loadCycleReadings(appState.currentCycle);
        renderReadingsTable();
        alert('✅ คำนวณยอดและบันทึกข้อมูลเรียบร้อยแล้ว!');
      }
    } catch (err) {
      console.error(err);
      alert('เกิดข้อผิดพลาดในการคำนวณยอด');
    }
  });

  // Print All Receipts in Batch
  document.getElementById('btn-print-all-receipts')?.addEventListener('click', async () => {
    const printWin = window.open('', '', 'width=850,height=700');
    if (!printWin) {
      alert('กรุณาอนุญาตหน้าต่างป๊อปอัป (Pop-up) ในเบราว์เซอร์เพื่อสั่งพิมพ์');
      return;
    }
    printWin.document.write(`<html><head><title>พิมพ์ใบเสร็จรับเงินทั้งหมด - งวด ${appState.currentCycle}</title><link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet"><style>body{font-family:'Sarabun',sans-serif;padding:20px;font-size:13.5px;}.receipt-page{page-break-after:always;margin-bottom:30px;border:1.5px solid #000;padding:16px;border-radius:6px;}table{width:100%;border-collapse:collapse;margin:10px 0;}th,td{border:1px solid #333;padding:6px 8px;font-size:13px;}</style></head><body><h3 style="text-align:center;">กำลังประมวลผลใบเสร็จทั้งหมด...</h3>`);
    
    let allHtml = '';
    for (const cust of appState.customers) {
      try {
        const res = await fetch(`${API_BASE}/receipts.php?cycle=${appState.currentCycle}&customerId=${cust.id}`);
        if (res.ok) {
          const d = await res.json();
          allHtml += `
            <div class="receipt-page">
              <div style="text-align:center;border-bottom:1.5px solid #000;padding-bottom:6px;margin-bottom:8px;">
                <h3 style="margin:0;font-size:16px;">การประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง</h3>
                <div style="font-weight:700;font-size:13.5px;">ใบเสร็จรับเงินค่าน้ำประปา ประจำงวด ${d.billingCycleText}</div>
              </div>
              <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;">
                <div><strong>เลขที่:</strong> ${d.receiptNo} | <strong>รหัส:</strong> ${d.customer.code}</div>
                <div><strong>ชื่อ:</strong> ${d.customer.name} (บ้านเลขที่ ${d.customer.houseNo})</div>
              </div>
              <table>
                <thead><tr><th>รายการ</th><th style="text-align:right;">เลขก่อน</th><th style="text-align:right;">เลขหลัง</th><th style="text-align:right;">หน่วยใช้</th><th style="text-align:right;">จำนวนเงิน (บาท)</th></tr></thead>
                <tbody>
                  <tr><td>ค่าน้ำประปา (@ ${d.meter.ratePerUnit.toFixed(2)} บ.)</td><td style="text-align:right;">${d.meter.previous.toFixed(1)}</td><td style="text-align:right;">${d.meter.current.toFixed(1)}</td><td style="text-align:right;">${d.meter.unitsUsed.toFixed(1)}</td><td style="text-align:right;">${d.breakdown.waterCharge.toFixed(2)}</td></tr>
                  <tr><td colspan="4">ค่าบำรุงรักษามิเตอร์</td><td style="text-align:right;">10.00</td></tr>
                  ${d.breakdown.previousArrears > 0 ? `<tr><td colspan="4" style="color:red;">ยอดค้างชำระยกยอดมา</td><td style="text-align:right;color:red;">${d.breakdown.previousArrears.toFixed(2)}</td></tr>` : ''}
                  <tr style="font-weight:700;background:#f8fafc;"><td colspan="4">ยอดรวมสุทธิ (${d.totalAmountTextTh})</td><td style="text-align:right;color:#dc2626;">${d.breakdown.grandTotal.toFixed(2)} บ.</td></tr>
                </tbody>
              </table>
              <div style="display:flex;justify-content:space-between;margin-top:12px;font-size:13px;">
                <div>ผู้รับเงิน: ....................................... ( ${d.customer.name} )</div>
                <div>เจ้าหน้าที่การเงิน: ....................................... ( นางจำเนียร ตรวจบัญชี )</div>
              </div>
            </div>
          `;
        }
      } catch (e) { console.error(e); }
    }
    printWin.document.body.innerHTML = allHtml;
    printWin.document.close();
    setTimeout(() => { printWin.print(); }, 500);
  });

  // Custom Voucher Creator
  document.getElementById('btn-add-custom-voucher')?.addEventListener('click', () => {
    const purpose = prompt('ระบุวัตถุประสงค์ในการเบิกจ่าย (เช่น ค่าซ่อมแซมท่อเมน, ค่าน้ำมันเครื่องสูบน้ำ):', 'ค่าอุปกรณ์ซ่อมบำรุงระบบประปา');
    if (!purpose) return;
    const amount = parseFloat(prompt('ระบุจำนวนเงินที่ต้องการเบิก (บาท):', '500'));
    if (isNaN(amount) || amount <= 0) {
      alert('กรุณากรอกจำนวนเงินที่ถูกต้อง');
      return;
    }
    const recipient = prompt('ระบุชื่อผู้รับเงิน:', 'นายสมาน เก็บเงินดี');
    if (!recipient) return;
    alert(`✅ สร้างฎีกาเบิกจ่ายสำหรับ "${purpose}" จำนวน ${amount.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท เรียบร้อยแล้ว!`);
    renderVouchers();
  });

  // Edit Financials
  document.getElementById('btn-edit-financials')?.addEventListener('click', () => {
    const newOtherExp = prompt('ระบุยอดรายจ่ายค่าซ่อมบำรุงหรืออื่นๆ ประจำเดือน (บาท):', '1450');
    if (newOtherExp !== null) {
      alert('✅ ปรับปรุงยอดรายรับ-รายจ่ายทางการเงินเรียบร้อยแล้ว!');
      renderFinancialReport();
      renderFinancialReportCharts();
    }
  });

  // Settings Save & Reset
  document.getElementById('btn-save-settings')?.addEventListener('click', () => {
    appState.settings.waterRate = parseFloat(document.getElementById('setting-water-rate')?.value) || 7.0;
    appState.settings.maintenanceFee = parseFloat(document.getElementById('setting-maintenance-fee')?.value) || 10.0;
    appState.settings.collectorPct = parseFloat(document.getElementById('setting-collector-pct')?.value) || 10;
    appState.settings.cashReserve = parseFloat(document.getElementById('setting-cash-reserve')?.value) || 5000.0;
    alert('✅ บันทึกมติคณะกรรมการและอัตราค่าน้ำเรียบร้อยแล้ว!');
    renderReadingsTable();
    renderFinancialReport();
  });

  document.getElementById('btn-reset-demo')?.addEventListener('click', () => {
    if (confirm('คุณต้องการโหลดค่าเริ่มต้นของระบบใช่หรือไม่?')) {
      const wr = document.getElementById('setting-water-rate');
      const mf = document.getElementById('setting-maintenance-fee');
      const cp = document.getElementById('setting-collector-pct');
      const cr = document.getElementById('setting-cash-reserve');
      if (wr) wr.value = '7.00';
      if (mf) mf.value = '10.00';
      if (cp) cp.value = '10';
      if (cr) cr.value = '5000';
      alert('🔄 โหลดค่าเริ่มต้นเรียบร้อยแล้ว!');
      renderReadingsTable();
    }
  });

  // -------------------------------------------------------------
  // Service Tickets Management (Tab 7)
  // -------------------------------------------------------------
  let adminTicketsData = [];

  async function loadServiceTicketsAdmin() {
    const tbody = document.getElementById('tickets-admin-tbody');
    if (!tbody) return;

    try {
      const res = await fetch(`${API_BASE}/tickets.php`);
      const data = await res.json();
      adminTicketsData = data.tickets || [];

      // Update stat cards
      const stats = data.stats || {};
      if (document.getElementById('ticket-stat-total')) document.getElementById('ticket-stat-total').textContent = stats.total || 0;
      if (document.getElementById('ticket-stat-pending')) document.getElementById('ticket-stat-pending').textContent = stats.pending || 0;
      if (document.getElementById('ticket-stat-progress')) document.getElementById('ticket-stat-progress').textContent = stats.inProgress || 0;
      if (document.getElementById('ticket-stat-resolved')) document.getElementById('ticket-stat-resolved').textContent = stats.resolved || 0;

      filterAndRenderTickets();
    } catch (err) {
      console.error('Error loading tickets:', err);
      tbody.innerHTML = '<tr><td colspan="9" class="text-center" style="padding: 20px; color: #dc2626;">เกิดข้อผิดพลาดในการโหลดรายการคำร้อง</td></tr>';
    }
  }

  function filterAndRenderTickets() {
    const tbody = document.getElementById('tickets-admin-tbody');
    if (!tbody) return;

    const statusFilter = document.getElementById('ticket-filter-status')?.value || 'ALL';
    const kw = (document.getElementById('ticket-search-input')?.value || '').toLowerCase().trim();

    let filtered = adminTicketsData;
    if (statusFilter !== 'ALL') {
      filtered = filtered.filter(t => t.status === statusFilter);
    }
    if (kw) {
      filtered = filtered.filter(t => 
        (t.ticket_no && t.ticket_no.toLowerCase().includes(kw)) ||
        (t.reporter_name && t.reporter_name.toLowerCase().includes(kw)) ||
        (t.phone && t.phone.toLowerCase().includes(kw)) ||
        (t.house_no && t.house_no.toLowerCase().includes(kw)) ||
        (t.issue_type && t.issue_type.toLowerCase().includes(kw))
      );
    }

    if (filtered.length === 0) {
      tbody.innerHTML = '<tr><td colspan="10" class="text-center" style="padding: 24px; color: #64748b;">ไม่พบรายการคำร้องแจ้งซ่อม</td></tr>';
      return;
    }

    tbody.innerHTML = filtered.map(t => {
      let badgeHtml = '';
      if (t.status === 'PENDING') {
        badgeHtml = '<span class="badge" style="background: #fef3c7; color: #b45309; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">🟡 รอดำเนินการ</span>';
      } else if (t.status === 'IN_PROGRESS') {
        badgeHtml = '<span class="badge" style="background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">🔵 กำลังซ่อมแซม</span>';
      } else {
        badgeHtml = '<span class="badge" style="background: #d1fae5; color: #047857; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">🟢 แก้ไขเรียบร้อย</span>';
      }

      const photoHtml = t.photo_url 
        ? `<a href="${t.photo_url}" target="_blank" title="คลิกดูภาพขยาย"><img src="${t.photo_url}" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer; transition: transform 0.15s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'"></a>`
        : '<span class="text-muted" style="font-size: 12px;">ไม่มี</span>';

      return `
        <tr>
          <td><strong style="color: #0284c7;">${t.ticket_no}</strong></td>
          <td style="font-size: 13px;">${t.created_at ? t.created_at.substring(0, 16) : '-'}</td>
          <td><strong>${t.reporter_name}</strong></td>
          <td><a href="tel:${t.phone}" style="color: #0284c7; text-decoration: none;">📞 ${t.phone}</a></td>
          <td>${t.house_no} <br><small class="text-muted">${t.zone}</small></td>
          <td><span style="font-weight: 600; color: #334155;">${t.issue_type}</span></td>
          <td style="max-width: 250px; font-size: 13px;">${t.description || '-'}</td>
          <td class="text-center">${photoHtml}</td>
          <td class="text-center">${badgeHtml}</td>
          <td class="text-center no-print">
            <button type="button" class="btn btn-outline btn-sm btn-edit-ticket" data-id="${t.id}" style="padding: 4px 10px; font-size: 12.5px;">
              ✏️ อัปเดตงาน
            </button>
          </td>
        </tr>
      `;
    }).join('');

    // Attach edit button handlers
    document.querySelectorAll('.btn-edit-ticket').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const ticket = adminTicketsData.find(x => String(x.id) === String(id));
        if (ticket) {
          document.getElementById('edit-ticket-id').value = ticket.id;
          document.getElementById('edit-ticket-no').value = ticket.ticket_no;
          let photoPreviewHtml = ticket.photo_url 
            ? `<div style="margin-top: 8px;"><a href="${ticket.photo_url}" target="_blank" title="คลิกดูภาพขนาดเต็ม"><img src="${ticket.photo_url}" style="max-height: 130px; border-radius: 6px; border: 1px solid #cbd5e1; box-shadow: 0 2px 4px rgba(0,0,0,0.05);"></a><br><small style="color: #64748b;">(คลิกที่รูปเพื่อเปิดดูภาพขนาดเต็ม)</small></div>`
            : '';
          document.getElementById('edit-ticket-summary').innerHTML = `
            <strong>ผู้แจ้ง:</strong> ${ticket.reporter_name} (โทร ${ticket.phone})<br>
            <strong>เรื่อง:</strong> ${ticket.issue_type} | <strong>สถานที่:</strong> ${ticket.house_no} (${ticket.zone})<br>
            <strong>อาการ:</strong> ${ticket.description || '-'}${photoPreviewHtml}
          `;
          document.getElementById('edit-ticket-status').value = ticket.status;
          document.getElementById('edit-ticket-notes').value = ticket.repair_notes || '';
          document.getElementById('edit-ticket-cost').value = parseFloat(ticket.repair_cost) || 0;
          openModal('ticket-edit-modal');
        }
      });
    });
  }

  window.handleTicketUpdateSubmit = async function(e) {
    e.preventDefault();
    const id = document.getElementById('edit-ticket-id').value;
    const status = document.getElementById('edit-ticket-status').value;
    const repair_notes = document.getElementById('edit-ticket-notes').value.trim();
    const repair_cost = parseFloat(document.getElementById('edit-ticket-cost').value) || 0;

    try {
      const res = await fetch(`${API_BASE}/tickets.php?id=${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ status, repair_notes, repair_cost })
      });
      const data = await res.json();
      if (res.ok && data.success) {
        showToast('บันทึกผลการซ่อมแซมและอัปเดตสถานะเรียบร้อยแล้ว!', 'success');
        closeModal('ticket-edit-modal');
        await loadServiceTicketsAdmin();
      } else {
        showToast('เกิดข้อผิดพลาด: ' + (data.error || 'ไม่สามารถอัปเดตได้'), 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
    }
  };

  window.openLineSettingsModal = async function() {
    try {
      const res = await fetch(`${API_BASE}/tickets.php?action=get_line_settings`);
      const data = await res.json();
      const tokenInput = document.getElementById('line-setting-token');
      const statusSpan = document.getElementById('line-token-status');
      const enabledSelect = document.getElementById('line-setting-enabled');

      if (enabledSelect) enabledSelect.value = String(data.enabled ?? 1);
      if (data.has_token) {
        tokenInput.value = '';
        tokenInput.placeholder = `Token ปัจจุบัน: ${data.token}`;
        statusSpan.innerHTML = `🟢 มีการบันทึก Token เรียบร้อยแล้ว (หากไม่ต้องการเปลี่ยน ให้เว้นว่างไว้)`;
      } else {
        tokenInput.value = '';
        tokenInput.placeholder = 'กรอก Token จาก notify-bot.line.me';
        statusSpan.innerHTML = `⚪ ยังไม่มีการตั้งค่า Token`;
      }
      openModal('line-notify-modal');
    } catch (e) {
      console.error(e);
      openModal('line-notify-modal');
    }
  };

  window.handleLineSettingsSubmit = async function(e) {
    e.preventDefault();
    const tokenVal = document.getElementById('line-setting-token').value.trim();
    const enabledVal = document.getElementById('line-setting-enabled').value;
    const btn = document.getElementById('btn-save-line-settings');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '⏳ กำลังบันทึกและทดสอบ...';

    try {
      const res = await fetch(`${API_BASE}/tickets.php?action=save_line_settings`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          token: tokenVal ? tokenVal : 'KEEP_CURRENT',
          enabled: parseInt(enabledVal, 10)
        })
      });
      const data = await res.json();
      if (res.ok && data.success) {
        showToast(data.message, 'success');
        if (data.test_result && data.test_result.success) {
          showToast('📲 ส่งข้อความทดสอบเข้า LINE เรียบร้อยแล้ว!', 'success');
        }
        closeModal('line-notify-modal');
      } else {
        showToast('❌ ไม่สามารถบันทึกได้: ' + (data.error || 'กรุณาลองใหม่'), 'error');
      }
    } catch (err) {
      console.error(err);
      showToast('❌ เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    } finally {
      btn.disabled = false;
      btn.textContent = originalText;
    }
  };

  document.getElementById('ticket-filter-status')?.addEventListener('change', filterAndRenderTickets);
  document.getElementById('ticket-search-input')?.addEventListener('input', filterAndRenderTickets);
  document.getElementById('btn-refresh-tickets')?.addEventListener('click', loadServiceTicketsAdmin);

  // Hook tab-tickets button click to load data
  document.querySelector('[data-tab="tab-tickets"]')?.addEventListener('click', loadServiceTicketsAdmin);

  // Handle Logged-in Staff User Session
  const rawUser = sessionStorage.getItem('plumber_user');
  if (rawUser) {
    try {
      const user = JSON.parse(rawUser);
      const topName = document.getElementById('topbar-user-name');
      const sideName = document.getElementById('sidebar-user-name');
      const sideRole = document.getElementById('sidebar-user-role');
      if (topName) topName.textContent = `${user.name} (${user.position || user.role})`;
      if (sideName) sideName.textContent = user.name;
      if (sideRole) sideRole.textContent = `(${user.position || user.role})`;
    } catch (e) {
      console.error(e);
    }
  }

  function handleLogout() {
    if (confirm('คุณต้องการออกจากระบบใช่หรือไม่?')) {
      sessionStorage.removeItem('plumber_user');
      window.location.href = 'index.php';
    }
  }

  document.getElementById('btn-topbar-logout')?.addEventListener('click', handleLogout);
  document.getElementById('btn-sidebar-logout')?.addEventListener('click', handleLogout);
});

/**
 * แอปพลิเคชันระบบบริหารจัดการการประปาหมู่บ้านวังยาง (Client-side Controller)
 * Dual-Mode: REST API (SQLite Backend) + LocalStorage Fallback (Standalone)
 */

const IS_API_MODE = window.location.protocol.startsWith('http');
const API_BASE = ''; // Same origin

// Initial Seed Data Fallback
const DEFAULT_SETTINGS = {
  waterRate: 7.0,
  maintenanceFee: 10.0,
  collectorPct: 10,
  cashReserve: 5000.0,
  villageName: 'การประปาหมู่บ้านวังยาง หมู่ที่ 3',
  subdistrict: 'ตำบลวังยาง',
  collectorName: 'นายสมาน เก็บเงินดี',
  caretakerName: 'นายประสิทธิ์ ดูแลดี',
  committeeChairman: 'นายประธาน บริหารกิจการ'
};

const DEFAULT_CUSTOMERS = [
  { id: 1, customerCode: 'WY-001', seqNo: 1, firstName: 'สมชาย', lastName: 'ใจดี', houseNo: '12 หมู่ 3', zone: 'โซนเหนือ - ซอย 1', phone: '081-234-5678', meterSerial: 'M-2023-001', status: 'ACTIVE' },
  { id: 2, customerCode: 'WY-002', seqNo: 2, firstName: 'สมศรี', lastName: 'มีสุข', houseNo: '14/1 หมู่ 3', zone: 'โซนเหนือ - ซอย 1', phone: '089-876-5432', meterSerial: 'M-2023-002', status: 'ACTIVE' },
  { id: 3, customerCode: 'WY-003', seqNo: 3, firstName: 'ประเสริฐ', lastName: 'วงษ์ทอง', houseNo: '19 หมู่ 3', zone: 'โซนกลาง - ซอยวัด', phone: '086-555-1122', meterSerial: 'M-2023-003', status: 'ACTIVE' },
  { id: 4, customerCode: 'WY-004', seqNo: 4, firstName: 'บุญส่ง', lastName: 'เกษมสุข', houseNo: '25 หมู่ 3', zone: 'โซนกลาง - ซอยวัด', phone: '084-333-8899', meterSerial: 'M-2023-004', status: 'ACTIVE' },
  { id: 5, customerCode: 'WY-005', seqNo: 5, firstName: 'วันชัย', lastName: 'ทองแท้', houseNo: '38/2 หมู่ 3', zone: 'โซนใต้ - ท้ายบ้าน', phone: '082-111-9988', meterSerial: 'M-2023-005', status: 'ACTIVE' }
];

const DEFAULT_READINGS = {
  '8-2567': [
    { customerId: 1, previousReading: 150, currentReading: 170, previousArrears: 0, paymentStatus: 'PAID', receiptSeq: 541 },
    { customerId: 2, previousReading: 210, currentReading: 235, previousArrears: 0, paymentStatus: 'PAID', receiptSeq: 542 },
    { customerId: 3, previousReading: 380, currentReading: 412, previousArrears: 150, paymentStatus: 'UNPAID', receiptSeq: 543 },
    { customerId: 4, previousReading: 95, currentReading: 110, previousArrears: 0, paymentStatus: 'PAID', receiptSeq: 544 },
    { customerId: 5, previousReading: 512, currentReading: 540, previousArrears: 0, paymentStatus: 'PAID', receiptSeq: 545 }
  ]
};

const DEFAULT_FINANCIALS = {
  '8-2567': {
    revWaterMaintenance: 24500.0,
    revCollectedArrears: 3200.0,
    revNewMeterFee: 1500.0,
    revBankInterest: 125.5,
    revOther: 0.0,
    expCaretaker: 3000.0,
    expCommittee: 2500.0,
    expElectricity: 6840.0,
    expSuppliesRepairs: 1450.0,
    expOther: 300.0,
    prevAccumulatedBalance: 145200.0
  }
};

let appState = {
  currentCycle: '8-2567',
  settings: { ...DEFAULT_SETTINGS },
  customers: [...DEFAULT_CUSTOMERS],
  readings: JSON.parse(JSON.stringify(DEFAULT_READINGS)),
  financials: JSON.parse(JSON.stringify(DEFAULT_FINANCIALS)),
  selectedReceiptCustomerIndex: 0
};

// -------------------------------------------------------------
// Data Fetching & State Management
// -------------------------------------------------------------
async function initAppData() {
  if (IS_API_MODE) {
    try {
      console.log('⚡ เชื่อมต่อเซิร์ฟเวอร์ Backend API (SQLite)...');
      // Load Settings
      const settingsRes = await fetch(`${API_BASE}/api/settings`);
      if (settingsRes.ok) {
        const s = await settingsRes.json();
        appState.settings.waterRate = s.tariff.rate_per_unit;
        appState.settings.maintenanceFee = s.tariff.maintenance_fee;
        appState.settings.collectorPct = s.collectorPct;
        appState.settings.cashReserve = s.cashReserve;
        appState.settings.villageName = s.villageName;
        appState.settings.subdistrict = s.subdistrict;
        appState.settings.collectorName = s.collectorName;
        appState.settings.caretakerName = s.caretakerName;
        appState.settings.committeeChairman = s.committeeChairman;
      }

      // Load Customers
      const custRes = await fetch(`${API_BASE}/api/customers`);
      if (custRes.ok) {
        const dbCusts = await custRes.json();
        if (dbCusts.length > 0) {
          appState.customers = dbCusts.map(c => ({
            id: c.id,
            customerCode: c.customer_code,
            seqNo: c.seq_no,
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

      // Load Readings for current cycle
      await loadCycleReadings(appState.currentCycle);

    } catch (e) {
      console.warn('⚠️ ไม่สามารถเชื่อมต่อ Backend API ได้ เปลี่ยนเป็น LocalStorage:', e);
      loadLocalState();
    }
  } else {
    console.log('📁 ทำงานในโหมด Standalone (LocalStorage)');
    loadLocalState();
  }

  updateUIHeader();
  renderReadingsTable();
  renderCustomersTable();
  updateZoneFilter();
}

async function loadCycleReadings(cycleCode) {
  if (!IS_API_MODE) return;
  try {
    const res = await fetch(`${API_BASE}/api/readings/${cycleCode}`);
    if (res.ok) {
      const data = await res.json();
      appState.readings[cycleCode] = data.readings.map(r => ({
        customerId: r.customer_id,
        previousReading: r.previous_reading,
        currentReading: r.current_reading,
        previousArrears: r.previous_arrears,
        paymentStatus: r.payment_status,
        receiptNo: r.receipt_no,
        unitsUsed: r.units_used,
        grandTotal: r.grand_total
      }));
    }
  } catch (e) {
    console.error('Error fetching cycle readings:', e);
  }
}

function loadLocalState() {
  try {
    const saved = localStorage.getItem('wangyang_water_app_state');
    if (saved) {
      const parsed = JSON.parse(saved);
      appState = { ...appState, ...parsed };
    }
  } catch (e) {
    console.error('Error loading state from localStorage:', e);
  }
}

function saveState() {
  if (!IS_API_MODE) {
    try {
      localStorage.setItem('wangyang_water_app_state', JSON.stringify(appState));
    } catch (e) {
      console.error('Error saving state:', e);
    }
  }
}

function updateUIHeader() {
  const rateEl = document.getElementById('sidebar-tariff-rate');
  if (rateEl) {
    rateEl.textContent = `${appState.settings.waterRate.toFixed(2)} บาท/หน่วย (+${appState.settings.maintenanceFee} บ.)`;
  }
}

// -------------------------------------------------------------
// UI Navigation
// -------------------------------------------------------------
function initNavigation() {
  const navItems = document.querySelectorAll('.nav-item');
  const tabPanes = document.querySelectorAll('.tab-pane');
  const pageTitle = document.getElementById('page-title');
  const pageSubtitle = document.getElementById('page-subtitle');

  const titles = {
    'tab-readings': { title: 'บันทึกจดมิเตอร์ประจำเดือน', sub: 'บันทึกเลขมิเตอร์ คำนวณค่าน้ำ และทบยอดค้างชำระอัตโนมัติ' },
    'tab-receipts': { title: 'พิมพ์ใบเสร็จรับเงิน', sub: 'ออกใบเสร็จรับเงินมาตรฐานพร้อมแปลงตัวหนังสือไทยอัตโนมัติ' },
    'tab-vouchers': { title: 'ใบสำคัญรับเงิน / ฎีกาเบิกจ่าย', sub: 'ระบบจดหมายเวียนสำหรับทำฎีกาเบิกจ่ายค่าตอบแทน 10% และค่าใช้จ่ายระบบ' },
    'tab-financials': { title: 'สรุปรายรับ-รายจ่ายประจำเดือน', sub: 'รายงานสรุปสถานะการเงิน ยอดเงินสดในมือ และเงินฝากธนาคาร' },
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
      if (targetTab === 'tab-financials') renderFinancialReport();
      if (targetTab === 'tab-customers') renderCustomersTable();
      if (targetTab === 'tab-readings') renderReadingsTable();
    });
  });
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
        receiptSeq: 500 + cust.seqNo
      };
      currentReadings.push(record);
    }

    const calc = calculateWaterBill({
      previousReading: record.previousReading,
      currentReading: record.currentReading,
      ratePerUnit: appState.settings.waterRate,
      maintenanceFee: appState.settings.maintenanceFee,
      previousArrears: record.previousArrears
    });

    if (record.currentReading > 0) readUsers++;
    totalUnits += calc.unitsUsed;
    currentBilling += calc.currentTotal;
    arrearsTotal += calc.previousArrears;

    if (record.paymentStatus === 'PAID') {
      paidAmount += calc.grandTotal;
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
      <td class="text-right font-bold text-primary">${calc.unitsUsed.toFixed(1)}</td>
      <td class="text-right">${calc.currentTotal.toFixed(2)}</td>
      <td class="text-right text-danger">${calc.previousArrears > 0 ? calc.previousArrears.toFixed(2) : '-'}</td>
      <td class="text-right font-bold">${calc.grandTotal.toFixed(2)}</td>
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
        saveState();
        if (IS_API_MODE) {
          await syncReadingToBackend(custId, rec);
        }
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
        saveState();
        if (IS_API_MODE) {
          await syncReadingToBackend(custId, rec);
        }
        renderReadingsTable();
      }
    });
  });

  tbody.querySelectorAll('.toggle-paid-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      const custId = parseInt(e.target.dataset.id, 10);
      const rec = currentReadings.find(r => r.customerId === custId);
      if (rec) {
        if (IS_API_MODE) {
          try {
            const res = await fetch(`${API_BASE}/api/readings/${appState.currentCycle}/${custId}/toggle-paid`, { method: 'POST' });
            const data = await res.json();
            rec.paymentStatus = data.paymentStatus;
            rec.receiptNo = data.receiptNo;
          } catch (err) {
            console.error(err);
            rec.paymentStatus = rec.paymentStatus === 'PAID' ? 'UNPAID' : 'PAID';
          }
        } else {
          rec.paymentStatus = rec.paymentStatus === 'PAID' ? 'UNPAID' : 'PAID';
          saveState();
        }
        renderReadingsTable();
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

async function syncReadingToBackend(customerId, record) {
  try {
    await fetch(`${API_BASE}/api/readings/${appState.currentCycle}/save`, {
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
    console.error('Error syncing reading:', e);
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

  const currentReadings = appState.readings[appState.currentCycle] || [];
  const record = currentReadings.find(r => r.customerId === cust.id) || {
    previousReading: 0,
    currentReading: 0,
    previousArrears: 0,
    receiptSeq: 541
  };

  const calc = calculateWaterBill({
    previousReading: record.previousReading,
    currentReading: record.currentReading,
    ratePerUnit: appState.settings.waterRate,
    maintenanceFee: appState.settings.maintenanceFee,
    previousArrears: record.previousArrears
  });

  const [month, yearBe] = appState.currentCycle.split('-');
  const receiptNo = record.receiptNo || formatReceiptNumber(month, yearBe, record.receiptSeq || 500 + cust.seqNo);

  document.getElementById('rcpt-no').textContent = receiptNo;
  document.getElementById('rcpt-date').textContent = new Intl.DateTimeFormat('th-TH', { year: 'numeric', month: 'long', day: 'numeric' }).format(new Date());
  document.getElementById('rcpt-cycle').textContent = `สิงหาคม ${yearBe} (${appState.currentCycle})`;
  document.getElementById('rcpt-name').textContent = `${cust.firstName} ${cust.lastName}`;
  document.getElementById('rcpt-payer-name').textContent = `${cust.firstName} ${cust.lastName}`;
  document.getElementById('rcpt-house').textContent = cust.houseNo;
  document.getElementById('rcpt-zone').textContent = cust.zone;
  document.getElementById('rcpt-code').textContent = cust.customerCode;
  document.getElementById('rcpt-seq').textContent = cust.seqNo;
  document.getElementById('rcpt-serial').textContent = cust.meterSerial || '-';
  document.getElementById('rcpt-due').textContent = `10 กันยายน ${yearBe}`;

  document.getElementById('rcpt-prev-reading').textContent = calc.previousReading.toFixed(2);
  document.getElementById('rcpt-curr-reading').textContent = calc.currentReading.toFixed(2);
  document.getElementById('rcpt-units').textContent = calc.unitsUsed.toFixed(2);
  document.getElementById('rcpt-rate').textContent = calc.ratePerUnit.toFixed(2);
  document.getElementById('rcpt-water-charge').textContent = calc.waterCharge.toFixed(2);
  document.getElementById('rcpt-maintenance-fee').textContent = calc.maintenanceFee.toFixed(2);
  document.getElementById('rcpt-current-total').textContent = calc.currentTotal.toFixed(2);
  document.getElementById('rcpt-arrears').textContent = calc.previousArrears.toFixed(2);
  document.getElementById('rcpt-grand-total').textContent = calc.grandTotal.toFixed(2);

  const thaiText = bahtText(calc.grandTotal);
  document.getElementById('rcpt-baht-text').textContent = thaiText;
}

// -------------------------------------------------------------
// TAB 3: Vouchers (ฎีกาเบิกจ่าย / ใบสำคัญรับเงิน)
// -------------------------------------------------------------
async function renderVouchers() {
  const container = document.getElementById('voucher-print-area');
  const cycleCode = appState.currentCycle;
  document.getElementById('voucher-cycle-text').textContent = `งวดประจำเดือน ${cycleCode}`;

  let vouchers = [];

  if (IS_API_MODE) {
    try {
      const res = await fetch(`${API_BASE}/api/vouchers/${cycleCode}`);
      if (res.ok) {
        const data = await res.json();
        vouchers = data.vouchers;
      }
    } catch (e) {
      console.error(e);
    }
  }

  // Fallback if not returned from API
  if (vouchers.length === 0) {
    const currentReadings = appState.readings[cycleCode] || [];
    let collectedWaterRevenue = 0;
    currentReadings.forEach(rec => {
      if (rec.paymentStatus === 'PAID') {
        const calc = calculateWaterBill({
          previousReading: rec.previousReading,
          currentReading: rec.currentReading,
          ratePerUnit: appState.settings.waterRate,
          maintenanceFee: appState.settings.maintenanceFee,
          previousArrears: rec.previousArrears
        });
        collectedWaterRevenue += calc.grandTotal;
      }
    });

    const collectorFee = Math.round((collectedWaterRevenue * (appState.settings.collectorPct / 100)) * 100) / 100;
    const personnel = [
      { name: appState.settings.collectorName, position: 'เจ้าหน้าที่จัดเก็บค่าน้ำประปา', type: 'COLLECTOR', amount: collectorFee, basis: `คิด ${appState.settings.collectorPct}% จากยอดจัดเก็บจริง ${collectedWaterRevenue.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท` },
      { name: appState.settings.caretakerName, position: 'ผู้ดูแลรักษาระบบประปาและบ่อบาดาล', type: 'CARETAKER', amount: 3000.0, basis: 'ค่าตอบแทนประจำเดือนในการเปิด-ปิดและดูแลความสะอาดระบบประปา' },
      { name: appState.settings.committeeChairman, position: 'ประธานกรรมการการประปาหมู่บ้านวังยาง', type: 'COMMITTEE', amount: 2500.0, basis: 'ค่าตอบแทนและเบี้ยประชุมคณะกรรมการบริหารกิจการประปา' },
      { name: 'ร้านวังยางการช่าง & วัสดุก่อสร้าง', position: 'ผู้จัดจำหน่ายวัสดุอุปกรณ์', type: 'MAINTENANCE', amount: 1450.0, basis: 'ค่าท่อ PVC ข้อต่อ กาวประสานท่อ และอุปกรณ์ซ่อมแซมจุดรั่วไหลซอย 2' }
    ];

    vouchers = personnel.map((item, idx) => ({
      voucherNo: `ฎีกา-${cycleCode}/${String(idx + 1).padStart(2, '0')}`,
      recipientName: item.name,
      recipientPosition: item.position,
      amount: item.amount,
      amountTextTh: bahtText(item.amount),
      calculationBasis: item.basis,
      approvedBy: appState.settings.committeeChairman
    }));
  }

  container.innerHTML = vouchers.map(v => `
    <div class="voucher-document">
      <div class="voucher-header">
        <h3>ใบสำคัญรับเงิน / ฎีกาเบิกจ่ายเงินกองทุนประปาหมู่บ้าน</h3>
        <h4>${appState.settings.villageName} ${appState.settings.subdistrict}</h4>
        <p class="text-muted">เลขที่เอกสาร: <strong>${v.voucherNo}</strong> | วันที่: ${new Date().toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
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

// -------------------------------------------------------------
// TAB 4: Monthly Financial Report
// -------------------------------------------------------------
async function renderFinancialReport() {
  const [m, y] = appState.currentCycle.split('-');
  document.getElementById('rpt-fin-month-year').textContent = `สิงหาคม ${y} (${appState.currentCycle})`;
  document.getElementById('fin-cycle-display').textContent = `สิงหาคม ${y}`;

  let report = null;

  if (IS_API_MODE) {
    try {
      const res = await fetch(`${API_BASE}/api/financials/${appState.currentCycle}`);
      if (res.ok) {
        report = await res.json();
      }
    } catch (e) {
      console.error(e);
    }
  }

  if (!report) {
    const fin = appState.financials[appState.currentCycle] || DEFAULT_FINANCIALS['8-2567'];
    report = calculateMonthlyFinancials({
      billingCycle: { cycleCode: appState.currentCycle },
      revWaterMaintenance: fin.revWaterMaintenance,
      revCollectedArrears: fin.revCollectedArrears,
      revNewMeterFee: fin.revNewMeterFee,
      revBankInterest: fin.revBankInterest,
      revOther: fin.revOther,
      expCaretaker: fin.expCaretaker,
      expCommittee: fin.expCommittee,
      collectorPercent: appState.settings.collectorPct,
      expElectricity: fin.expElectricity,
      expSuppliesRepairs: fin.expSuppliesRepairs,
      expOther: fin.expOther,
      prevAccumulatedBalance: fin.prevAccumulatedBalance,
      targetCashInHand: appState.settings.cashReserve
    });
  }

  // Populate Elements
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
                  cust.customerCode.toLowerCase().includes(search);
    if (!match) return;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${cust.seqNo}</td>
      <td><strong>${cust.customerCode}</strong></td>
      <td>${cust.firstName} ${cust.lastName}</td>
      <td>${cust.houseNo}</td>
      <td>${cust.zone}</td>
      <td>${cust.phone || '-'}</td>
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
        if (IS_API_MODE) {
          await fetch(`${API_BASE}/api/customers/${id}`, { method: 'DELETE' });
        }
        appState.customers = appState.customers.filter(c => c.id !== id);
        saveState();
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

  // Export Excel Button
  document.getElementById('btn-export-excel')?.addEventListener('click', () => {
    if (IS_API_MODE) {
      window.location.href = `${API_BASE}/api/export/excel/${appState.currentCycle}`;
    } else {
      alert('การส่งออกไฟล์ Excel อัตโนมัติเปิดใช้งานเมื่อรันร่วมกับเซิร์ฟเวอร์ Backend');
    }
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

    if (IS_API_MODE) {
      try {
        const res = await fetch(`${API_BASE}/api/cycles/open`, {
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
          document.getElementById('sidebar-cycle-name').textContent = opt.textContent;
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
    } else {
      closeModal('cycle-modal');
      alert(`เปิดรอบบิลใหม่ ${cycleCode} (โหมด Standalone)`);
    }
  });

  // Cycle Change
  document.getElementById('cycle-select')?.addEventListener('change', async (e) => {
    appState.currentCycle = e.target.value;
    document.getElementById('sidebar-cycle-name').textContent = `${e.target.options[e.target.selectedIndex].text}`;
    if (IS_API_MODE) {
      await loadCycleReadings(appState.currentCycle);
    }
    renderReadingsTable();
    renderReceipt();
    renderVouchers();
    renderFinancialReport();
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
      const id = parseInt(idVal, 10);
      if (IS_API_MODE) {
        await fetch(`${API_BASE}/api/customers/${id}`, {
          method: 'PUT',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(custData)
        });
      }
      const index = appState.customers.findIndex(c => c.id === id);
      if (index !== -1) appState.customers[index] = { ...appState.customers[index], ...custData };
    } else {
      if (IS_API_MODE) {
        const res = await fetch(`${API_BASE}/api/customers`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(custData)
        });
        const created = await res.json();
        custData.id = created.id;
      } else {
        custData.id = appState.customers.length > 0 ? Math.max(...appState.customers.map(c => c.id)) + 1 : 1;
      }
      appState.customers.push(custData);
    }

    saveState();
    closeModal('customer-modal');
    renderCustomersTable();
    renderReadingsTable();
    updateZoneFilter();
  });

  // Save Settings
  document.getElementById('btn-save-settings')?.addEventListener('click', async () => {
    appState.settings.waterRate = parseFloat(document.getElementById('setting-water-rate').value) || 7.0;
    appState.settings.maintenanceFee = parseFloat(document.getElementById('setting-maintenance-fee').value) || 10.0;
    appState.settings.collectorPct = parseFloat(document.getElementById('setting-collector-pct').value) || 10;
    appState.settings.cashReserve = parseFloat(document.getElementById('setting-cash-reserve').value) || 5000.0;

    if (IS_API_MODE) {
      await fetch(`${API_BASE}/api/settings/tariff`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ratePerUnit: appState.settings.waterRate,
          maintenanceFee: appState.settings.maintenanceFee
        })
      });
    }

    updateUIHeader();
    saveState();
    renderReadingsTable();
    alert('บันทึกการตั้งค่าอัตราค่าน้ำเรียบร้อยแล้ว');
  });
});

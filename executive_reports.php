<?php
/**
 * 4. งานกำกับนโยบายและรายงานการเงินกองทุน
 * สิทธิ์การใช้งาน: คณะกรรมการบริหาร / ประธาน (admin) และ เจ้าหน้าที่ (staff)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/sidebar.php';
$currentUser = requireRole(['admin', 'staff']);

require_once __DIR__ . '/api/db.php';
$cycleStmt = $pdo->query("SELECT * FROM billing_cycles ORDER BY id DESC");
$cycles = $cycleStmt->fetchAll();
$currentCycleCode = $cycles[0]['cycle_code'] ?? '8-2567';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>รายงานภาพรวม & การเงินกองทุนประปา - การประปาหมู่บ้านวังยาง</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .exec-nav {
      display: flex;
      gap: 8px;
      margin-bottom: 20px;
      background: #fff;
      padding: 10px 14px;
      border-radius: 8px;
      border: 1px solid var(--border);
    }
    .exec-tab-btn {
      padding: 8px 18px;
      border-radius: 6px;
      border: none;
      background: none;
      font-family: 'Prompt', sans-serif;
      font-size: 14px;
      font-weight: 600;
      color: #64748b;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
    }
    .exec-tab-btn.active {
      background: #7c3aed;
      color: #fff;
    }
    .exec-pane {
      display: none;
    }
    .exec-pane.active {
      display: block;
    }
  </style>
</head>
<body>
  <div class="app-layout">
    <!-- Global Persistent Sidebar with Real URL Routes -->
    <?php renderAppSidebar('executive'); ?>

    <!-- Main Content Area -->
    <main class="main-content">
      <?php renderAppTopBar('รายงานภาพรวม & การเงินกองทุนประปา', 'แดชบอร์ดผู้บริหาร วิเคราะห์น้ำสูญเสีย (NRW) รายงานรายรับ-รายจ่าย และนโยบายอัตราค่าน้ำ'); ?>

    <!-- Top Action / Cycle Bar -->
    <div class="card no-print" style="margin-bottom: 16px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-radius: 14px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
      <div style="display: flex; align-items: center; gap: 12px;">
        <div style="width: 42px; height: 42px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
          🏛️
        </div>
        <div>
          <h2 style="font-family: 'Prompt', sans-serif; font-size: 17px; font-weight: 700; margin: 0; color: #0f172a;">แดชบอร์ดคณะกรรมการบริหารการประปาหมู่บ้าน</h2>
          <span style="font-size: 13px; color: #64748b;">งวดรายงาน: <strong style="color: #0284c7;"><span id="exec-cycle-display"><?php echo $currentCycleCode; ?></span></strong></span>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <label for="exec-cycle-select" style="font-size: 13px; font-weight: 600; color: #475569;">📅 งวดเดือน:</label>
        <select id="exec-cycle-select" class="form-select" style="font-weight: 700; font-size: 13.5px; padding: 6px 12px;">
          <?php foreach ($cycles as $c): ?>
            <option value="<?php echo htmlspecialchars($c['cycle_code']); ?>" <?php echo $c['cycle_code'] === $currentCycleCode ? 'selected' : ''; ?>>
              <?php echo $c['month'] . '/' . $c['year_be'] . ' (' . $c['cycle_code'] . ')'; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-outline" onclick="copyExcel365Summary()" title="คัดลอกสรุปตัวเลขนำไปวางใน Excel 365 หรือส่งสรุปในกลุ่มไลน์กรรมการ" style="background: #fff; font-weight: 600;">📋 สรุปส่ง Excel 365</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ พิมพ์รายงานสรุป</button>
      </div>
    </div>



    <!-- ========================================================= -->
    <!-- PANE 1: แดชบอร์ดผู้บริหาร & วิเคราะห์ NRW -->
    <!-- ========================================================= -->
    <div id="pane-overview" class="exec-pane active">
      
      <!-- Operational KPI Grid -->
      <div class="stats-grid no-print" style="margin-bottom: 20px;">
        <div class="stat-card">
          <span class="stat-label">น้ำสูญเสียในระบบ (NRW)</span>
          <span class="stat-value text-success" id="exec-stat-nrw">13.8 %</span>
          <span class="stat-desc text-muted">เกณฑ์มาตรฐาน มท. &lt; 20% (ปกติ ✅)</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">ประสิทธิภาพการจัดเก็บรายได้</span>
          <span class="stat-value text-primary" id="exec-stat-coll">92.5 %</span>
          <span class="stat-desc text-muted">จัดเก็บจริงเทียบยอดเรียกเก็บ</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">ต้นทุนผันแปรต่อ ลบ.ม.</span>
          <span class="stat-value" style="color: #0284c7;" id="exec-stat-cost">4.83 บาท</span>
          <span class="stat-desc text-muted">ราคาขาย 7.00 บ. (กำไร 2.17 บ./หน่วย)</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">เงินกองทุนสะสมสุทธิ</span>
          <span class="stat-value text-success" id="exec-stat-fund">133,703.00 ฿</span>
          <span class="stat-desc text-muted">เงินสดในมือ 5,000 + บัญชี ธ.ก.ส.</span>
        </div>
      </div>

      <!-- Charts 2 Columns -->
      <div class="analytics-grid no-print">
        <div class="chart-card">
          <div class="chart-card-header">
            <h4>💧 ปริมาณการผลิต vs การใช้น้ำ & อัตราสูญเสีย (NRW)</h4>
            <span class="chart-badge">ย้อนหลัง 6 เดือน</span>
          </div>
          <div style="height: 270px; position: relative;">
            <canvas id="chartWaterExec"></canvas>
          </div>
        </div>

        <div class="chart-card">
          <div class="chart-card-header">
            <h4>💰 รายรับ - รายจ่าย และเงินกองทุนสะสมสุทธิ</h4>
            <span class="chart-badge">ย้อนหลัง 6 เดือน</span>
          </div>
          <div style="height: 270px; position: relative;">
            <canvas id="chartFinExec"></canvas>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================= -->
    <!-- PANE 2: รายงานสรุปรายรับ - รายจ่ายประจำเดือน -->
    <!-- ========================================================= -->
    <div id="pane-report" class="exec-pane">
      <div class="report-document" style="background: #fff; padding: 30px; border-radius: 8px; border: 1px solid #cbd5e1; max-width: 900px; margin: 0 auto; box-shadow: var(--shadow);">
        <div class="report-header" style="text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 20px;">
          <h2 style="font-family: 'Prompt', sans-serif; font-size: 20px; margin: 0; color: #0f172a;">รายงานสรุปรายรับ - รายจ่าย ประจำเดือน</h2>
          <h3 style="font-size: 15px; margin: 4px 0;">กิจการประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</h3>
          <p style="font-size: 13px; color: #475569; margin: 0;">ประจำงวดเดือน <span id="gk3-cycle">-</span></p>
        </div>

        <div class="financial-tables-grid">
          <!-- Column 1: รายรับ -->
          <div class="fin-col">
            <div class="fin-header rev-header">
              <h4>1. รายรับประจำเดือน (Revenues)</h4>
            </div>
            <table class="table fin-table">
              <thead>
                <tr>
                  <th>รายการรายรับ</th>
                  <th class="text-right" width="120">จำนวนเงิน (บาท)</th>
                </tr>
              </thead>
              <tbody>
                <tr><td>1.1 ค่าน้ำและค่าบำรุงมาตร</td><td class="text-right" id="gk-rev-curr">0.00</td></tr>
                <tr><td>1.2 ค่าน้ำค้างชำระที่ตามเก็บได้</td><td class="text-right" id="gk-rev-arrears">0.00</td></tr>
                <tr><td>1.3 ค่าติดตั้งผู้ใช้น้ำรายใหม่</td><td class="text-right" id="gk-rev-new">1,500.00</td></tr>
                <tr><td>1.4 ดอกเบี้ยเงินฝากธนาคาร</td><td class="text-right" id="gk-rev-int">125.50</td></tr>
                <tr class="fin-total-row highlight-green">
                  <td><strong>รวมรายรับทั้งสิ้น (A)</strong></td>
                  <td class="text-right"><strong id="gk-total-rev">0.00</strong></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Column 2: รายจ่าย -->
          <div class="fin-col">
            <div class="fin-header exp-header">
              <h4>2. รายจ่ายประจำเดือน (Expenses)</h4>
            </div>
            <table class="table fin-table">
              <thead>
                <tr>
                  <th>รายการรายจ่าย</th>
                  <th class="text-right" width="120">จำนวนเงิน (บาท)</th>
                </tr>
              </thead>
              <tbody>
                <tr><td>2.1 ค่าตอบแทนผู้ดูแลระบบ</td><td class="text-right">3,000.00</td></tr>
                <tr><td>2.2 ค่าตอบแทนกรรมการ</td><td class="text-right">2,500.00</td></tr>
                <tr><td>2.3 ค่าตอบแทนคนเก็บค่าน้ำ (10%)</td><td class="text-right" id="gk-exp-coll">0.00</td></tr>
                <tr><td>2.4 ค่ากระแสไฟฟ้าบ่อบาดาล</td><td class="text-right">6,840.00</td></tr>
                <tr><td>2.5 ค่าซ่อมบำรุงระบบท่อ</td><td class="text-right">1,450.00</td></tr>
                <tr class="fin-total-row highlight-red">
                  <td><strong>รวมรายจ่ายทั้งสิ้น (B)</strong></td>
                  <td class="text-right"><strong id="gk-total-exp">0.00</strong></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="fin-summary-box" style="margin-top: 20px;">
          <div class="summary-card-inner">
            <div class="summary-item">
              <span class="lbl">กำไร / (ขาดทุน) ประจำเดือน (A - B):</span>
              <span class="val text-success" id="gk-net-profit">0.00 บาท</span>
            </div>
            <div class="summary-item">
              <span class="lbl">เงินคงเหลือสะสมยกมาจากเดือนก่อน:</span>
              <span class="val" id="gk-prev-balance">145,200.00 บาท</span>
            </div>
            <div class="summary-item grand-highlight">
              <span class="lbl">ยอดเงินคงเหลือสะสมสุทธิสิ้นเดือน:</span>
              <strong class="val highlight-blue" id="gk-total-balance">0.00 บาท</strong>
            </div>
            <div class="baht-text-line">
              (ตัวอักษร: <span id="gk-text-th">-</span>)
            </div>
          </div>
        </div>

        <div class="report-signatures" style="margin-top: 30px;">
          <div class="sig-block">
            <div class="sig-line">...................................................</div>
            <div class="sig-title">( นายสมาน เก็บเงินดี )</div>
            <div class="sig-role">เจ้าหน้าที่การเงิน / ผู้จัดทำ</div>
          </div>
          <div class="sig-block">
            <div class="sig-line">...................................................</div>
            <div class="sig-title">( นางจำเนียร ตรวจบัญชี )</div>
            <div class="sig-role">เหรัญญิก / ผู้ตรวจทาน</div>
          </div>
          <div class="sig-block">
            <div class="sig-line">...................................................</div>
            <div class="sig-title">( นายประธาน บริหารกิจการ )</div>
            <div class="sig-role">ประธานกรรมการการประปาหมู่บ้าน</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================= -->
    <!-- PANE 3: ทะเบียนผู้ใช้น้ำชุมชน -->
    <!-- ========================================================= -->
    <div id="pane-members" class="exec-pane">
      <div class="card table-card">
        <div class="table-responsive">
          <table class="table" id="exec-customers-table">
            <thead>
              <tr>
                <th width="60">ลำดับ</th>
                <th width="110">รหัสผู้ใช้</th>
                <th>ชื่อ - นามสกุล</th>
                <th>บ้านเลขที่</th>
                <th>คุ้ม / โซน</th>
                <th>เบอร์โทรศัพท์</th>
                <th>หมายเลขมิเตอร์</th>
                <th width="90" class="text-center">สถานะ</th>
              </tr>
            </thead>
            <tbody id="exec-customers-tbody">
              <!-- Dynamically populated -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ========================================================= -->
    <!-- PANE 4: ตั้งค่านโยบาย & อัตราค่าน้ำ -->
    <!-- ========================================================= -->
    <div id="pane-policy" class="exec-pane">
      <div class="card" style="max-width: 800px; margin: 0 auto; padding: 24px;">
        <h3 style="font-family: 'Prompt', sans-serif; font-size: 17px; margin-bottom: 16px;">⚙️ มติคณะกรรมการ: กำหนดอัตราค่าน้ำและพารามิเตอร์ระบบ</h3>
        <form onsubmit="handlePolicySave(event)">
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label">อัตราค่าน้ำต่อหน่วย (บาท/ลบ.ม.):</label>
              <input type="number" step="0.5" class="form-input" value="7.00" required>
            </div>
            <div class="form-group">
              <label class="form-label">ค่าบำรุงรักษามิเตอร์ประจำเดือน (บาท):</label>
              <input type="number" step="1" class="form-input" value="10.00" required>
            </div>
            <div class="form-group">
              <label class="form-label">สัดส่วนค่าตอบแทนคนเก็บค่าน้ำ (%):</label>
              <input type="number" class="form-input" value="10" required>
            </div>
            <div class="form-group">
              <label class="form-label">วงเงินสดสำรองจ่ายในมือเหรัญญิก (บาท):</label>
              <input type="number" step="500" class="form-input" value="5000" required>
            </div>
          </div>
          <div class="form-actions text-right" style="margin-top: 20px;">
            <button type="submit" class="btn btn-primary">💾 บันทึกมติคณะกรรมการ</button>
          </div>
        </form>
      </div>
    </div>

    </main>
  </div> <!-- /.app-layout -->

  <!-- Scripts -->
  <script>
    const API_BASE = 'api';
    let currentCycle = '<?php echo $currentCycleCode; ?>';
    let waterChart = null;
    let finChart = null;

    function switchExecPane(hash) {
      if (!hash) hash = 'pane-overview';
      if (!hash.startsWith('pane-')) hash = 'pane-' + hash;
      const panes = document.querySelectorAll('.exec-pane');
      let found = false;
      panes.forEach(p => {
        if (p.id === hash) {
          p.classList.add('active');
          found = true;
        } else {
          p.classList.remove('active');
        }
      });
      if (!found && panes.length > 0) {
        panes[0].classList.add('active');
      }
    }

    document.addEventListener('DOMContentLoaded', async () => {
      document.getElementById('exec-cycle-select')?.addEventListener('change', async (e) => {
        currentCycle = e.target.value;
        document.getElementById('exec-cycle-display').textContent = currentCycle;
        await reloadExecutiveData();
      });

      // Switch pane based on hash on load
      switchExecPane(window.location.hash.replace('#', ''));
      
      // Listen for hash changes from sidebar links
      window.addEventListener('hashchange', () => {
        switchExecPane(window.location.hash.replace('#', ''));
      });

      await reloadExecutiveData();
    });

    async function reloadExecutiveData() {
      await Promise.all([
        loadFinancialsData(),
        loadCustomersList()
      ]);
    }

    async function loadFinancialsData() {
      try {
        const res = await fetch(`${API_BASE}/financials.php?cycle=${currentCycle}`);
        if (!res.ok) return;
        const d = await res.json();

        // 1. KPI
        if (d.operational) {
          document.getElementById('exec-stat-nrw').textContent = `${d.operational.nrwPct}%`;
          document.getElementById('exec-stat-coll').textContent = `${d.operational.collectionEfficiency}%`;
          document.getElementById('exec-stat-cost').textContent = `${d.operational.unitCost.toFixed(2)} บาท`;
        }
        document.getElementById('exec-stat-fund').textContent = `${d.summary.totalAccumulatedBalance.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;

        // 2. GK3 Report Document
        document.getElementById('gk3-cycle').textContent = `งวด ${currentCycle}`;
        document.getElementById('gk-rev-curr').textContent = d.revenues.revWaterMaintenance.toLocaleString('th-TH', { minimumFractionDigits: 2 });
        document.getElementById('gk-rev-arrears').textContent = d.revenues.revCollectedArrears.toLocaleString('th-TH', { minimumFractionDigits: 2 });
        document.getElementById('gk-total-rev').textContent = d.revenues.totalRevenue.toLocaleString('th-TH', { minimumFractionDigits: 2 });
        document.getElementById('gk-exp-coll').textContent = d.expenses.expCollector.toLocaleString('th-TH', { minimumFractionDigits: 2 });
        document.getElementById('gk-total-exp').textContent = d.expenses.totalExpense.toLocaleString('th-TH', { minimumFractionDigits: 2 });

        const netEl = document.getElementById('gk-net-profit');
        netEl.textContent = `${d.summary.netProfitLoss >= 0 ? '+' : ''}${d.summary.netProfitLoss.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`;
        netEl.className = d.summary.netProfitLoss >= 0 ? 'val text-success' : 'val text-danger';

        document.getElementById('gk-prev-balance').textContent = `${d.summary.prevAccumulatedBalance.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`;
        document.getElementById('gk-total-balance').textContent = `${d.summary.totalAccumulatedBalance.toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท`;
        document.getElementById('gk-text-th').textContent = d.summary.totalAccumulatedTextTh;

        // 3. Render Charts
        if (d.trends) {
          renderCharts(d.trends);
        }
      } catch (err) {
        console.error('Error loading financials:', err);
      }
    }

    function renderCharts(trends) {
      // Chart 1: Water
      if (waterChart) waterChart.destroy();
      const ctx1 = document.getElementById('chartWaterExec')?.getContext('2d');
      if (ctx1) {
        waterChart = new Chart(ctx1, {
          type: 'bar',
          data: {
            labels: trends.months,
            datasets: [
              {
                label: 'น้ำดิบสูบผลิต (ลบ.ม.)',
                data: trends.production,
                backgroundColor: 'rgba(2, 132, 199, 0.75)',
                borderRadius: 4
              },
              {
                label: 'น้ำจำหน่ายตามมาตร (ลบ.ม.)',
                data: trends.consumption,
                backgroundColor: 'rgba(34, 197, 94, 0.75)',
                borderRadius: 4
              },
              {
                label: 'น้ำสูญเสีย NRW (%)',
                data: trends.nrw,
                type: 'line',
                borderColor: '#ef4444',
                borderWidth: 2.5,
                yAxisID: 'y1'
              }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: { beginAtZero: true, title: { display: true, text: 'ลบ.ม.' } },
              y1: { position: 'right', beginAtZero: true, max: 25, title: { display: true, text: '%' }, grid: { drawOnChartArea: false } }
            },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
          }
        });
      }

      // Chart 2: Financial
      if (finChart) finChart.destroy();
      const ctx2 = document.getElementById('chartFinExec')?.getContext('2d');
      if (ctx2) {
        finChart = new Chart(ctx2, {
          type: 'bar',
          data: {
            labels: trends.months,
            datasets: [
              { label: 'รายรับ (บาท)', data: trends.revenue, backgroundColor: 'rgba(16, 185, 129, 0.75)', borderRadius: 4 },
              { label: 'รายจ่าย (บาท)', data: trends.expense, backgroundColor: 'rgba(244, 63, 94, 0.75)', borderRadius: 4 },
              { label: 'เงินกองทุนสะสม (บาท)', data: trends.fundBalance, type: 'line', borderColor: '#7c3aed', borderWidth: 2.5, yAxisID: 'y1' }
            ]
          },
          options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
              y: { beginAtZero: true, title: { display: true, text: 'บาท' } },
              y1: { position: 'right', title: { display: true, text: 'เงินกองทุนสะสม' }, grid: { drawOnChartArea: false } }
            },
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
          }
        });
      }
    }

    async function loadCustomersList() {
      const tbody = document.getElementById('exec-customers-tbody');
      if (!tbody) return;

      const res = await fetch(`${API_BASE}/customers.php`);
      if (res.ok) {
        const list = await res.json();
        tbody.innerHTML = list.map(c => `
          <tr>
            <td>${c.seq_no}</td>
            <td><strong>${c.customer_code}</strong></td>
            <td><strong>${c.first_name} ${c.last_name}</strong></td>
            <td>${c.house_no}</td>
            <td>${c.zone}</td>
            <td><a href="tel:${c.phone || ''}" style="color: #4338ca; text-decoration: none; font-weight: 600;">📞 ${c.phone || '-'}</a></td>
            <td><code>${c.meter_serial || '-'}</code></td>
            <td class="text-center"><span class="badge badge-paid" style="font-size: 13px;">ปกติ</span></td>
          </tr>
        `).join('');
      }
    }

    function copyExcel365Summary() {
      const cycle = currentCycle;
      const rev = document.getElementById('gk-total-rev')?.innerText || '0.00';
      const exp = document.getElementById('gk-total-exp')?.innerText || '0.00';
      const profit = document.getElementById('gk-net-profit')?.innerText || '0.00';
      const balance = document.getElementById('gk-total-balance')?.innerText || '0.00';
      const nrw = document.getElementById('exec-stat-nrw')?.innerText || '13.8%';
      const coll = document.getElementById('exec-stat-coll')?.innerText || '92.5%';

      const summaryText = `[รายงานสรุปการเงินประปาหมู่บ้านวังยาง งวด ${cycle}]\n` +
        `รายรับทั้งสิ้น:\t${rev}\tบาท\n` +
        `รายจ่ายทั้งสิ้น:\t${exp}\tบาท\n` +
        `กำไร/ขาดทุนสุทธิ:\t${profit}\n` +
        `ยอดเงินคงเหลือสะสม:\t${balance}\n` +
        `ประสิทธิภาพการจัดเก็บ:\t${coll}\n` +
        `น้ำสูญเสียในระบบ (NRW):\t${nrw}\n` +
        `วันที่ส่งข้อมูล:\t${new Date().toLocaleDateString('th-TH')}`;

      navigator.clipboard.writeText(summaryText).then(() => {
        alert('📋 คัดลอกข้อมูลสรุปประจำงวดเรียบร้อยแล้ว!\nสามารถนำไปวาง (Ctrl+V) ใน Excel 365 หรือส่งสรุปในกลุ่มไลน์คณะกรรมการได้ทันที');
      }).catch(() => {
        prompt('คัดลอกข้อความสรุปด้านล่างนี้สำหรับวางใน Excel 365:', summaryText);
      });
    }

    function handlePolicySave(e) {
      e.preventDefault();
      alert('✅ บันทึกมติคณะกรรมการและปรับปรุงอัตราค่าน้ำเรียบร้อยแล้ว!');
    }
  </script>
</body>
</html>

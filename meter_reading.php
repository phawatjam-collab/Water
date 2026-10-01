<?php
/**
 * 2. งานจดบันทึกมาตรวัดน้ำภาคสนาม (แบบ ป.17)
 * สิทธิ์การใช้งาน: เจ้าหน้าที่การประปา (staff) และ ผู้ดูแลระบบ (admin)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/sidebar.php';
$currentUser = requireRole(['staff', 'admin']);

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
  <title>งานจดบันทึกมาตรวัดน้ำภาคสนาม (แบบ ป.17) - การประปาหมู่บ้านวังยาง</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    .field-reading-container {
      max-width: 1300px;
      margin: 20px auto;
      padding: 0 16px;
    }
    .field-header-card {
      background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
      color: #fff;
      padding: 24px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
    }
    .progress-bar-wrap {
      background: rgba(255, 255, 255, 0.25);
      border-radius: 9999px;
      height: 12px;
      width: 100%;
      overflow: hidden;
      margin-top: 8px;
    }
    .progress-bar-fill {
      background: #22c55e;
      height: 100%;
      width: 0%;
      transition: width 0.3s ease;
    }
    .meter-input-cell {
      width: 110px;
      padding: 8px 10px;
      font-size: 16px;
      font-weight: 700;
      text-align: right;
      border: 2px solid #cbd5e1;
      border-radius: 6px;
      font-family: monospace;
      outline: none;
    }
    .meter-input-cell:focus {
      border-color: #0284c7;
      background: #eff6ff;
    }
    .abnormal-warning {
      background: #fee2e2;
      color: #b91c1c;
      font-size: 13px;
      padding: 3px 8px;
      border-radius: 4px;
      display: inline-block;
      margin-top: 4px;
      font-weight: 600;
    }
    .mobile-field-actions {
      display: flex;
      gap: 8px;
      align-items: center;
    }
  </style>
</head>
<body>
  <div class="app-layout">
    <!-- Global Persistent Sidebar with Real URL Routes -->
    <?php renderAppSidebar('field'); ?>

    <!-- Main Content Area -->
    <main class="main-content">
      <?php renderAppTopBar('งานจดบันทึกมาตรวัดน้ำภาคสนาม (แบบ ป.17)', 'ระบบบันทึกเลขมิเตอร์ คำนวณหน่วยใช้น้ำ และตรวจจับความผิดปกติหน้างาน'); ?>

    <!-- Field Header with Progress -->
    <div class="field-header-card no-print">
      <div>
        <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 9999px; font-size: 13.5px; font-weight: 600;">
          🚶‍♂️ โมดูลพนักงานจดมิเตอร์ภาคสนาม (Field Reader Interface)
        </span>
        <h1 style="font-family: 'Prompt', sans-serif; font-size: 22px; margin: 8px 0 2px 0;">
          สมุดบันทึกการจดมาตรวัดน้ำประจำงวด (แบบ ป.17)
        </h1>
        <p style="opacity: 0.9; font-size: 13.5px; margin: 0;">
          บันทึกเลขมิเตอร์ครั้งหลัง ระบบคำนวณหน่วยใช้และทบยอดค้างชำระให้อัตโนมัติทันที
        </p>
      </div>

      <div style="min-width: 260px; background: rgba(0,0,0,0.15); padding: 12px 16px; border-radius: 8px;">
        <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600;">
          <span>ความคืบหน้าการเดินจด:</span>
          <span id="field-progress-text">0 / 0 หลังคาเรือน (0%)</span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" id="field-progress-bar"></div>
        </div>
      </div>
    </div>

    <!-- Filter and Route Selection Bar -->
    <div class="filter-card no-print">
      <div class="filter-inputs">
        <select id="field-cycle-select" class="form-select" style="font-weight: 600;">
          <?php foreach ($cycles as $c): ?>
            <option value="<?php echo htmlspecialchars($c['cycle_code']); ?>" <?php echo $c['cycle_code'] === $currentCycleCode ? 'selected' : ''; ?>>
              งวดเดือน <?php echo $c['month'] . '/' . $c['year_be'] . ' (' . $c['cycle_code'] . ')'; ?>
            </option>
          <?php endforeach; ?>
        </select>

        <select id="field-zone-select" class="form-select">
          <option value="">ทุกคุ้ม / ทุกโซนสายเดินจด</option>
        </select>

        <input type="text" id="field-search-input" class="form-input" placeholder="🔍 ค้นหาบ้านเลขที่, รหัสผู้ใช้ หรือชื่อ...">
      </div>

      <div class="mobile-field-actions">
        <button type="button" class="btn btn-outline" id="btn-reload-field">🔄 รีเฟรช</button>
        <button type="button" class="btn btn-secondary" id="btn-save-field">💾 บันทึกข้อมูล</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ พิมพ์สมุดจด (ป.17)</button>
      </div>
    </div>

    <!-- Printable Header for Official Form ป.17 -->
    <div class="only-print" style="margin-bottom: 20px; text-align: center;">
      <h2 style="font-family: 'Prompt', sans-serif; font-size: 18px; margin: 0;">สมุดบันทึกการจดมาตรวัดน้ำประจำเดือน (แบบ ป.17)</h2>
      <h3 style="font-size: 15px; margin: 4px 0;">การประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง</h3>
      <p style="font-size: 13px; margin: 0;">งวดประจำเดือน <span id="print-cycle-text"></span> | เจ้าหน้าที่ผู้จด: <?php echo htmlspecialchars($currentUser['name']); ?></p>
    </div>

    <!-- Readings Table Card -->
    <div class="card table-card">
      <div class="table-responsive">
        <table class="table" id="field-readings-table">
          <thead>
            <tr>
              <th width="60" class="text-center">สายจด</th>
              <th width="100">รหัสผู้ใช้</th>
              <th>ชื่อ - นามสกุล</th>
              <th>บ้านเลขที่</th>
              <th width="120">เบอร์โทรศัพท์</th>
              <th>โซน / คุ้ม</th>
              <th>เลขมิเตอร์</th>
              <th width="110" class="text-right">เลขครั้งก่อน</th>
              <th width="130" class="text-right">เลขครั้งหลัง (กรอก)</th>
              <th width="100" class="text-right">หน่วยที่ใช้</th>
              <th width="110" class="text-right">ยอดเงินงวดนี้</th>
              <th width="90" class="text-center">สถานะ</th>
            </tr>
          </thead>
          <tbody id="field-table-body">
            <!-- Dynamically populated rows -->
          </tbody>
        </table>
      </div>
    </div>

    </main>
  </div> <!-- /.app-layout -->

  <!-- Scripts -->
  <script>
    const API_BASE = 'api';
    let fieldReadings = [];
    let fieldCustomers = [];
    let activeCycle = '<?php echo $currentCycleCode; ?>';

    document.addEventListener('DOMContentLoaded', async () => {
      document.getElementById('print-cycle-text').textContent = activeCycle;
      await loadFieldData();

      document.getElementById('field-cycle-select')?.addEventListener('change', async (e) => {
        activeCycle = e.target.value;
        document.getElementById('print-cycle-text').textContent = activeCycle;
        await loadFieldData();
      });

      document.getElementById('field-zone-select')?.addEventListener('change', renderFieldRows);
      document.getElementById('field-search-input')?.addEventListener('input', renderFieldRows);
      document.getElementById('btn-reload-field')?.addEventListener('click', loadFieldData);

      document.getElementById('btn-save-field')?.addEventListener('click', async () => {
        const inputs = document.querySelectorAll('.meter-input-cell');
        const items = [];
        inputs.forEach(inp => {
          const custId = parseInt(inp.dataset.id, 10);
          const prev = parseFloat(inp.dataset.prev) || 0;
          const curr = parseFloat(inp.value) || 0;
          const arrears = parseFloat(inp.dataset.arrears) || 0;
          if (curr > 0) {
            items.push({ customerId: custId, previousReading: prev, currentReading: curr, previousArrears: arrears });
          }
        });

        if (items.length === 0) {
          alert('ยังไม่มีข้อมูลเลขมิเตอร์ที่กรอก');
          return;
        }

        try {
          const res = await fetch(`${API_BASE}/readings.php?cycle=${activeCycle}&action=save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items })
          });
          if (res.ok) {
            alert(`✅ บันทึกเลขมิเตอร์สำเร็จเรียบร้อยแล้ว (${items.length} รายการ)`);
            await loadFieldData();
          }
        } catch (err) {
          console.error(err);
          alert('เกิดข้อผิดพลาดในการบันทึกข้อมูล');
        }
      });
    });

    async function loadFieldData() {
      try {
        const [custRes, readRes] = await Promise.all([
          fetch(`${API_BASE}/customers.php`),
          fetch(`${API_BASE}/readings.php?cycle=${activeCycle}`)
        ]);

        if (custRes.ok && readRes.ok) {
          fieldCustomers = await custRes.json();
          const rData = await readRes.json();
          fieldReadings = rData.readings || [];

          populateZoneSelect();
          renderFieldRows();
        }
      } catch (err) {
        console.error('Error loading field data:', err);
      }
    }

    function populateZoneSelect() {
      const sel = document.getElementById('field-zone-select');
      const zones = [...new Set(fieldCustomers.map(c => c.zone))];
      sel.innerHTML = '<option value="">ทุกคุ้ม / ทุกโซนสายเดินจด</option>' + 
        zones.map(z => `<option value="${z}">${z}</option>`).join('');
    }

    function renderFieldRows() {
      const tbody = document.getElementById('field-table-body');
      if (!tbody) return;

      const zoneVal = document.getElementById('field-zone-select').value;
      const searchVal = document.getElementById('field-search-input').value.trim().toLowerCase();

      let totalCount = fieldCustomers.length;
      let readCount = 0;

      const filtered = fieldCustomers.filter(c => {
        const matchZone = !zoneVal || c.zone === zoneVal;
        const matchSearch = c.first_name.toLowerCase().includes(searchVal) ||
                            c.last_name.toLowerCase().includes(searchVal) ||
                            c.house_no.toLowerCase().includes(searchVal) ||
                            c.customer_code.toLowerCase().includes(searchVal) ||
                            (c.phone && c.phone.includes(searchVal));
        return matchZone && matchSearch;
      });

      tbody.innerHTML = filtered.map(c => {
        let r = fieldReadings.find(x => parseInt(x.customer_id, 10) === parseInt(c.id, 10));
        const prev = r ? parseFloat(r.previous_reading) : 0;
        const curr = r ? parseFloat(r.current_reading) : 0;
        const units = r ? parseFloat(r.units_used) : 0;
        const grandTotal = r ? parseFloat(r.grand_total) : 10;
        const isRead = (curr > 0 && curr >= prev);

        if (isRead) readCount++;

        const isSpike = (units > 35); // Leak detection threshold

        return `
          <tr data-customer-id="${c.id}">
            <td class="text-center font-bold" style="color: #64748b;">${c.seq_no}</td>
            <td><strong>${c.customer_code}</strong></td>
            <td><strong>${c.first_name} ${c.last_name}</strong></td>
            <td>${c.house_no}</td>
            <td><a href="tel:${c.phone || ''}" style="color: #0284c7; text-decoration: none; font-weight: 600; white-space: nowrap;">📞 ${c.phone || '-'}</a></td>
            <td><span style="font-size: 13.5px; color: #475569;">${c.zone}</span></td>
            <td><code style="font-size: 13px;">${c.meter_serial || '-'}</code></td>
            <td class="text-right" style="color: #475569; font-weight: 600;">${prev.toFixed(1)}</td>
            <td class="text-right">
              <input type="number" 
                     step="0.1" 
                     class="meter-input-cell no-print" 
                     data-id="${c.id}" 
                     data-prev="${prev}" 
                     data-arrears="${r ? r.previous_arrears : 0}"
                     value="${curr > 0 ? curr.toFixed(1) : ''}" 
                     placeholder="${prev.toFixed(1)}">
              <span class="only-print font-bold">${curr.toFixed(1)}</span>
              ${isSpike ? '<br><span class="abnormal-warning">⚠️ ใช้น้ำพุ่งสูงผิดปกติ</span>' : ''}
            </td>
            <td class="text-right font-bold" style="color: ${units > 0 ? '#0284c7' : '#94a3b8'};">
              <span id="units-disp-${c.id}">${units.toFixed(1)}</span>
            </td>
            <td class="text-right font-bold text-danger">
              <span id="amount-disp-${c.id}">${grandTotal.toFixed(2)}</span> ฿
            </td>
            <td class="text-center">
              <span class="badge ${isRead ? 'badge-paid' : 'badge-unpaid'}" style="font-size: 13px;">
                ${isRead ? 'จดแล้ว' : 'ยังไม่จด'}
              </span>
            </td>
          </tr>
        `;
      }).join('');

      // Update Progress Card
      const pct = totalCount > 0 ? Math.round((readCount / totalCount) * 100) : 0;
      document.getElementById('field-progress-text').textContent = `${readCount} / ${totalCount} หลังคาเรือน (${pct}%)`;
      document.getElementById('field-progress-bar').style.width = `${pct}%`;

      // Wire Auto-save on blur/change
      tbody.querySelectorAll('.meter-input-cell').forEach(inp => {
        inp.addEventListener('change', async (e) => {
          const custId = parseInt(e.target.dataset.id, 10);
          const prev = parseFloat(e.target.dataset.prev) || 0;
          const curr = parseFloat(e.target.value) || 0;
          const arrears = parseFloat(e.target.dataset.arrears) || 0;

          if (curr < prev) {
            alert(`⚠️ คำเตือน: เลขมิเตอร์ครั้งหลัง (${curr}) น้อยกว่าครั้งก่อน (${prev})! กรุณาตรวจสอบว่ามิเตอร์วนรอบหรือคีย์ผิด`);
          }

          const units = curr >= prev ? (curr - prev) : ((10000 - prev) + curr);
          const waterCharge = units * 7.0;
          const currentTotal = waterCharge + 10.0;
          const grandTotal = currentTotal + arrears;

          document.getElementById(`units-disp-${custId}`).textContent = units.toFixed(1);
          document.getElementById(`amount-disp-${custId}`).textContent = grandTotal.toFixed(2);

          // Save to server via API
          try {
            await fetch(`${API_BASE}/readings.php?cycle=${activeCycle}&action=save`, {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                items: [{
                  customerId: custId,
                  previousReading: prev,
                  currentReading: curr,
                  previousArrears: arrears
                }]
              })
            });
            e.target.style.borderColor = '#22c55e';
            e.target.style.background = '#f0fdf4';
          } catch (err) {
            console.error('Error saving reading:', err);
            e.target.style.borderColor = '#dc2626';
          }
        });
      });
    }
  </script>
</body>
</html>

<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/sidebar.php';
$currentUser = requireRole(['admin']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ระบบบริหารจัดการ การประปาหมู่บ้านวังยาง</title>
  <!-- Google Fonts: Sarabun & Prompt -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <!-- Chart.js for Visual Analytics & Trends -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <!-- Sidebar / Navigation -->
  <div class="app-layout">
    <?php renderAppSidebar('dashboard'); ?>

    <!-- Main Content Area -->
    <main class="main-content">
      
      <!-- Unified Global Top Bar -->
      <?php renderAppTopBar('ศูนย์บริหารจัดการระบบประปา', 'ภาพรวมระบบ จัดการงวดบัญชี สรุปยอด และการบริหารงานแบบรวมศูนย์'); ?>

      <!-- Dashboard Admin Actions & Cycle Control Bar -->
      <div class="card no-print" style="margin-bottom: 20px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; border-radius: 14px; border: 1px solid var(--border); box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
          <div style="width: 40px; height: 40px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
            ⚙️
          </div>
          <div>
            <h2 style="font-family: 'Prompt', sans-serif; font-size: 16px; margin: 0; color: #0f172a; font-weight: 700;">แผงบริหารจัดการงวดบัญชี</h2>
            <span style="font-size: 13px; color: #64748b;">รอบบิลปัจจุบัน: <strong style="color: #0284c7;"><span id="dashboard-cycle-display">สิงหาคม 2567 (8-2567)</span></strong></span>
          </div>
          <div class="select-cycle-wrap" style="margin-left: 8px;">
            <select id="cycle-select" class="form-select" style="font-weight: 700; min-width: 180px; font-size: 13.5px; padding: 6px 12px;">
              <option value="8-2567" selected>สิงหาคม 2567 (8-2567)</option>
              <option value="7-2567">กรกฎาคม 2567 (7-2567)</option>
              <option value="6-2567">มิถุนายน 2567 (6-2567)</option>
            </select>
          </div>
        </div>

        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
          <button class="btn btn-primary" id="btn-open-cycle" title="เปิดรอบบิลเดือนใหม่" style="font-size: 13px;">➕ เปิดรอบบิลใหม่</button>
          <button class="btn btn-outline" id="btn-export-excel" title="ส่งออกข้อมูลเป็น Excel" style="font-size: 13px;">📥 ส่งออก Excel</button>
          <a href="api/backup.php" class="btn btn-outline" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-size: 13px;" title="ดาวน์โหลดไฟล์สำรองฐานข้อมูล SQL">💾 สำรอง SQL</a>
          <button class="btn btn-outline" onclick="window.print()" style="font-size: 13px;">🖨️ พิมพ์เอกสาร</button>
        </div>
      </div>

      <!-- Dashboard Internal Tab Bar for Admin Tools -->
      <div class="dashboard-tabs-bar no-print">
        <button class="nav-item active" data-tab="tab-readings">
          <span class="icon">📝</span> จดมิเตอร์น้ำประปา
        </button>
        <button class="nav-item" data-tab="tab-receipts">
          <span class="icon">🧾</span> ออกใบเสร็จรับเงิน
        </button>
        <button class="nav-item" data-tab="tab-vouchers">
          <span class="icon">📜</span> ค่าตอบแทนคนจด (10%)
        </button>
        <button class="nav-item" data-tab="tab-financials">
          <span class="icon">📊</span> งบการเงิน & สรุปผล
        </button>
        <button class="nav-item" data-tab="tab-arrears">
          <span class="icon">⚠️</span> ติดตามยอดค้างชำระ
        </button>
        <button class="nav-item" data-tab="tab-customers">
          <span class="icon">👥</span> ทะเบียนผู้ใช้น้ำ
        </button>
        <button class="nav-item" data-tab="tab-settings">
          <span class="icon">⚙️</span> ตั้งค่าอัตราค่าน้ำ
        </button>
        <button class="nav-item" data-tab="tab-tickets">
          <span class="icon">🔧</span> แจ้งซ่อม/คำร้อง
        </button>
      </div>

      <!-- ======================================================= -->
      <!-- TAB 1: จดมิเตอร์ประจำเดือน -->
      <!-- ======================================================= -->
      <section id="tab-readings" class="tab-pane active">
        <!-- Quick Summary Cards -->
        <div class="stats-grid no-print">
          <div class="stat-card">
            <span class="stat-label">ผู้ใช้น้ำทั้งหมด</span>
            <span class="stat-value" id="stat-total-users">0 ครัวเรือน</span>
            <span class="stat-desc text-muted">จดแล้ว <span id="stat-read-users">0</span> ครัวเรือน</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">ยอดใช้น้ำรวมประจำงวด</span>
            <span class="stat-value text-primary" id="stat-total-units">0 ลบ.ม.</span>
            <span class="stat-desc text-muted">เฉลี่ยต่อหลังคาเรือน <span id="stat-avg-units">0</span> ลบ.ม.</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">ยอดเรียกเก็บงวดนี้</span>
            <span class="stat-value text-warning" id="stat-current-billing">0.00 ฿</span>
            <span class="stat-desc text-muted">รวมยอดค้างเก่า <span id="stat-arrears-total">0.00</span> ฿</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">ยอดจัดเก็บได้จริง (รับชำระแล้ว)</span>
            <span class="stat-value text-success" id="stat-paid-amount">0.00 ฿</span>
            <span class="stat-desc text-success">ชำระแล้ว <span id="stat-paid-count">0</span> ราย</span>
          </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-card no-print">
          <div class="filter-inputs">
            <div class="search-input-box" style="flex: 1; min-width: 260px;">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" id="reading-search-input" class="form-input" placeholder="ค้นหาชื่อ, สกุล, บ้านเลขที่ หรือรหัสผู้ใช้น้ำ...">
            </div>
            <select id="reading-zone-filter" class="form-select">
              <option value="">ทุกโซน / ทุกหมู่</option>
            </select>
            <select id="reading-status-filter" class="form-select">
              <option value="">ทุกสถานะการชำระ</option>
              <option value="PAID">ชำระแล้ว (PAID)</option>
              <option value="UNPAID">ค้างชำระ (UNPAID)</option>
            </select>
          </div>
          <div class="filter-actions">
            <button class="btn btn-outline" id="btn-recalc-all">🔄 คำนวณยอดใหม่ทั้งหมด</button>
            <button class="btn btn-secondary" id="btn-batch-mark-paid">✅ ตัดรับชำระรายการที่เลือก</button>
          </div>
        </div>

        <!-- Table -->
        <div class="card table-card">
          <div class="table-responsive">
            <table class="table" id="readings-table">
              <thead>
                <tr>
                  <th width="40"><input type="checkbox" id="check-all-readings"></th>
                  <th width="70">ลำดับ</th>
                  <th width="110">รหัสผู้ใช้น้ำ</th>
                  <th>ชื่อ - สกุล</th>
                  <th>บ้านเลขที่ / โซน</th>
                  <th width="100" class="text-right">เลขครั้งก่อน</th>
                  <th width="110" class="text-right">เลขครั้งหลัง</th>
                  <th width="90" class="text-right">หน่วยที่ใช้</th>
                  <th width="100" class="text-right">ค่าน้ำงวดนี้</th>
                  <th width="90" class="text-right">ค้างเก่า</th>
                  <th width="110" class="text-right">ยอดสุทธิ</th>
                  <th width="100" class="text-center">สถานะ</th>
                  <th width="130" class="text-center no-print">จัดการ</th>
                </tr>
              </thead>
              <tbody id="readings-table-body">
                <!-- Dynamic Content -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ======================================================= -->
      <!-- TAB 2: พิมพ์ใบเสร็จรับเงิน (Receipt Generation) -->
      <!-- ======================================================= -->
      <section id="tab-receipts" class="tab-pane">
        <div class="card no-print receipt-toolbar">
          <div class="toolbar-left">
            <label for="receipt-customer-select">เลือกพิมพ์ใบเสร็จของสมาชิก:</label>
            <select id="receipt-customer-select" class="form-select">
              <!-- Customers list -->
            </select>
          </div>
          <div class="toolbar-right">
            <button class="btn btn-outline" id="btn-prev-receipt">⬅️ รายก่อนหน้า</button>
            <button class="btn btn-outline" id="btn-next-receipt">รายถัดไป ➡️</button>
            <button class="btn btn-primary" onclick="window.print()">🖨️ สั่งพิมพ์ใบเสร็จนี้</button>
            <button class="btn btn-secondary" id="btn-print-all-receipts">📑 พิมพ์ใบเสร็จทั้งหมดในงวด</button>
          </div>
        </div>

        <!-- Receipt Printable Container -->
        <div class="printable-wrapper" id="receipt-print-area">
          <div class="receipt-document">
            <div class="receipt-header">
              <div class="receipt-emblem">💧</div>
              <div class="receipt-title-block">
                <h2>การประปาหมู่บ้านวังยาง</h2>
                <p>ที่ทำการกองทุนระบบน้ำประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง</p>
                <div class="receipt-badge-title">ใบเสร็จรับเงินค่าน้ำประปา</div>
              </div>
              <div class="receipt-meta-box">
                <div class="meta-row">
                  <span class="meta-label">เลขที่:</span>
                  <strong class="meta-val highlight-red" id="rcpt-no">8-2567/541</strong>
                </div>
                <div class="meta-row">
                  <span class="meta-label">วันที่:</span>
                  <span class="meta-val" id="rcpt-date">-</span>
                </div>
                <div class="meta-row">
                  <span class="meta-label">ประจำงวด:</span>
                  <strong class="meta-val" id="rcpt-cycle">สิงหาคม 2567</strong>
                </div>
              </div>
            </div>

            <div class="receipt-customer-info">
              <div class="info-col">
                <p><strong>ผู้ใช้น้ำ:</strong> <span id="rcpt-name">นายสมชาย ใจดี</span></p>
                <p><strong>บ้านเลขที่:</strong> <span id="rcpt-house">12 หมู่ 3</span></p>
                <p><strong>กลุ่ม/โซน:</strong> <span id="rcpt-zone">โซนเหนือ - ซอย 1</span></p>
              </div>
              <div class="info-col">
                <p><strong>รหัสผู้ใช้น้ำ:</strong> <span id="rcpt-code">WY-001</span> (ลำดับที่ <span id="rcpt-seq">1</span>)</p>
                <p><strong>เลขมิเตอร์:</strong> <span id="rcpt-serial">M-2023-001</span></p>
                <p><strong>กำหนดชำระ:</strong> <span id="rcpt-due">10 กันยายน 2567</span></p>
              </div>
            </div>

            <!-- Reading Details Table -->
            <table class="receipt-table">
              <thead>
                <tr>
                  <th>รายการคำนวณ</th>
                  <th class="text-right">เลขมิเตอร์ครั้งก่อน</th>
                  <th class="text-right">เลขมิเตอร์ครั้งหลัง</th>
                  <th class="text-right">จำนวนหน่วยที่ใช้</th>
                  <th class="text-right">อัตรา/หน่วย</th>
                  <th class="text-right">จำนวนเงิน (บาท)</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>ค่าน้ำประปาประจำงวด</td>
                  <td class="text-right" id="rcpt-prev-reading">150.00</td>
                  <td class="text-right" id="rcpt-curr-reading">170.00</td>
                  <td class="text-right" id="rcpt-units">20.00</td>
                  <td class="text-right" id="rcpt-rate">7.00</td>
                  <td class="text-right" id="rcpt-water-charge">140.00</td>
                </tr>
                <tr>
                  <td colspan="5">ค่าบำรุงรักษามิเตอร์ประจำเดือน</td>
                  <td class="text-right" id="rcpt-maintenance-fee">10.00</td>
                </tr>
                <tr class="row-subtotal">
                  <td colspan="5"><strong>รวมยอดเงินงวดปัจจุบัน</strong></td>
                  <td class="text-right"><strong id="rcpt-current-total">150.00</strong></td>
                </tr>
                <tr class="row-arrears">
                  <td colspan="5">ยอดค้างชำระยกมาจากเดือนก่อน</td>
                  <td class="text-right" id="rcpt-arrears">0.00</td>
                </tr>
                <tr class="row-grand-total">
                  <td colspan="3" class="baht-text-cell">
                    <span>(ตัวอักษร): </span>
                    <strong id="rcpt-baht-text">หนึ่งร้อยห้าสิบบาทถ้วน</strong>
                  </td>
                  <td colspan="2" class="text-right"><strong>ยอดรวมสุทธิที่ต้องชำระ:</strong></td>
                  <td class="text-right"><strong class="grand-amount" id="rcpt-grand-total">150.00</strong></td>
                </tr>
              </tbody>
            </table>

            <!-- PromptPay QR Code & Bank Info -->
            <div class="rcpt-payment-channel" style="margin: 16px 0; padding: 12px 16px; background: #f0fdf4; border: 1px dashed #22c55e; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; gap: 16px;">
              <div style="display: flex; align-items: center; gap: 14px;">
                <img id="rcpt-promptpay-img" src="https://promptpay.io/0812345678/150.00.png" alt="PromptPay QR" style="width: 95px; height: 95px; border-radius: 6px; border: 1px solid #bbf7d0; background: #fff; padding: 4px;">
                <div>
                  <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                    <span style="background: #15803d; color: #fff; font-size: 13px; font-weight: 700; padding: 3px 8px; border-radius: 4px;">พร้อมเพย์ (PromptPay)</span>
                    <strong style="color: #166534; font-size: 13.5px;">สแกนจ่ายผ่าน Mobile Banking ได้ทุกธนาคาร</strong>
                  </div>
                  <div style="font-size: 13.5px; color: #374151; line-height: 1.5;">
                    บัญชี: <strong>กองทุนระบบประปาหมู่บ้านวังยาง</strong> (ธนาคารเพื่อการเกษตรและสหกรณ์การเกษตร - ธ.ก.ส.)<br>
                    หมายเลขพร้อมเพย์: <strong>081-234-5678</strong> | ยอดชำระ: <strong id="rcpt-promptpay-amount" style="color: #b91c1c; font-size: 14px;">150.00 บาท</strong>
                  </div>
                </div>
              </div>
              <div class="no-print" style="text-align: right;">
                <span style="font-size: 13px; color: #15803d; display: block; font-weight: 600;">⚡ อัปเดต QR อัตโนมัติตามยอด</span>
              </div>
            </div>

            <!-- Receipt Footer Signatures -->
            <div class="receipt-signatures">
              <div class="sig-block">
                <div class="sig-line">...................................................</div>
                <div class="sig-title">(<span id="rcpt-payer-name">...................................................</span>)</div>
                <div class="sig-role">ผู้ชำระเงิน</div>
              </div>
              <div class="sig-block">
                <div class="sig-line">...................................................</div>
                <div class="sig-title">( นายสมาน เก็บเงินดี )</div>
                <div class="sig-role">เจ้าหน้าที่จัดเก็บค่าน้ำ</div>
              </div>
              <div class="sig-block">
                <div class="sig-line">...................................................</div>
                <div class="sig-title">( นายประธาน บริหารกิจการ )</div>
                <div class="sig-role">ประธานกรรมการการประปาหมู่บ้านวังยาง</div>
              </div>
            </div>

            <div class="receipt-note">
              * โปรดเก็บใบเสร็จรับเงินไว้เป็นหลักฐาน หากมีข้อสงสัยโปรดติดต่อคณะกรรมการการประปาหมู่บ้านวังยาง
            </div>
          </div>
        </div>
      </section>

      <!-- ======================================================= -->
      <!-- TAB 3: เอกสารเบิกจ่าย & ค่าตอบแทนคนจด 10% (Vouchers / Mail Merge) -->
      <!-- ======================================================= -->
      <section id="tab-vouchers" class="tab-pane">
        <div class="card no-print voucher-filter-bar">
          <div class="voucher-info-box">
            <h3>📜 เอกสารเบิกจ่ายค่าตอบแทนและค่าใช้จ่าย ประจำงวด <span id="voucher-cycle-text">สิงหาคม 2567</span></h3>
            <p class="text-muted">ระบบคำนวณค่าตอบแทนเจ้าหน้าที่เก็บค่าน้ำ 10% จากยอดจัดเก็บจริง และค่าใช้จ่ายระบบอัตโนมัติ</p>
          </div>
          <div class="voucher-actions">
            <button class="btn btn-outline" id="btn-add-custom-voucher">➕ เพิ่มรายการเบิกจ่ายใหม่</button>
            <button class="btn btn-primary" onclick="window.print()">🖨️ สั่งพิมพ์ชุดใบสำคัญรับเงินทั้งหมด</button>
          </div>
        </div>

        <div class="voucher-list-container" id="voucher-print-area">
          <!-- Vouchers Cards dynamically rendered -->
        </div>
      </section>

      <!-- ======================================================= -->
      <!-- TAB 4: รายงานสรุปรายรับ-รายจ่ายประจำเดือน (Financial Report) -->
      <!-- ======================================================= -->
      <section id="tab-financials" class="tab-pane">
        <div class="card no-print financial-top-actions">
          <div>
            <h3>รายงานสถานะการเงินและการจัดเก็บรายได้</h3>
            <p class="text-muted">สรุปรายรับ-รายจ่าย ยอดเงินสดในมือ และเงินฝากธนาคาร ประจำงวด <span id="fin-cycle-display">สิงหาคม 2567</span></p>
          </div>
          <div>
            <button class="btn btn-outline" id="btn-edit-financials">✏️ ปรับปรุงรายการรับ-จ่าย</button>
            <button class="btn btn-primary" onclick="window.print()">🖨️ พิมพ์รายงานสรุปการเงิน</button>
          </div>
        </div>

        <!-- Operational KPIs & Efficiency Cards (no-print) -->
        <div class="stats-grid no-print" style="margin-bottom: 20px;">
          <div class="stat-card">
            <span class="stat-label">น้ำสูญเสียในระบบ (NRW)</span>
            <span class="stat-value text-success" id="fin-stat-nrw">13.8 %</span>
            <span class="stat-desc text-muted">ผลิต 4,050 ลบ.ม. / จ่าย 3,490 ลบ.ม. (เกณฑ์ มท. &lt; 20% ✅)</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">ประสิทธิภาพการจัดเก็บรายได้</span>
            <span class="stat-value text-primary" id="fin-stat-collection-rate">92.5 %</span>
            <span class="stat-desc text-muted">ยอดจัดเก็บจริงเทียบกับยอดประเมินเรียกเก็บ</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">ต้นทุนผันแปรเฉลี่ยต่อน้ำ 1 ลบ.ม.</span>
            <span class="stat-value" style="color: #0284c7;" id="fin-stat-unit-cost">4.83 บาท</span>
            <span class="stat-desc text-muted">ราคาขาย 7.00 บ. (ส่วนต่างสะสมกองทุน 2.17 บ./หน่วย)</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">สัดส่วนค่าไฟสูบน้ำต่อต้นทุนรวม</span>
            <span class="stat-value text-warning" id="fin-stat-elec-pct">48.2 %</span>
            <span class="stat-desc text-muted">ค่าไฟบ่อบาดาล 6,840 ฿ (โครงสร้างหลักของต้นทุน)</span>
          </div>
        </div>

        <!-- Interactive Visual Analytics Charts (no-print) -->
        <div class="analytics-grid no-print">
          <div class="chart-card">
            <div class="chart-card-header">
              <h4>💧 ปริมาณการผลิต vs การใช้น้ำ & อัตราสูญเสีย (NRW)</h4>
              <span class="chart-badge">ย้อนหลัง 6 เดือน</span>
            </div>
            <div style="position: relative; height: 260px;">
              <canvas id="chartWaterTrend"></canvas>
            </div>
          </div>
          <div class="chart-card">
            <div class="chart-card-header">
              <h4>💰 รายรับ - รายจ่าย และการเติบโตของกองทุนสะสม</h4>
              <span class="chart-badge">ย้อนหลัง 6 เดือน</span>
            </div>
            <div style="position: relative; height: 260px;">
              <canvas id="chartFinancialTrend"></canvas>
            </div>
          </div>
        </div>

        <!-- Printable Financial Report -->
        <div class="printable-wrapper" id="financial-print-area">
          <div class="report-document">
            <div class="report-header">
              <h2>รายงานสรุปรายรับ - รายจ่าย ประจำเดือน</h2>
              <h3>กิจการประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง</h3>
              <p>ประจำงวดเดือน <span id="rpt-fin-month-year">สิงหาคม 2567 (8-2567)</span></p>
            </div>

            <!-- Financial Tables 2 Columns -->
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
                      <th class="text-right" width="130">จำนวนเงิน (บาท)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>1.1 ยอดเก็บค่าน้ำและค่าบำรุงมาตรเดือนปัจจุบัน</td>
                      <td class="text-right" id="fin-rev-current">24,500.00</td>
                    </tr>
                    <tr>
                      <td>1.2 ยอดค่าน้ำค้างชำระสะสมที่ตามเก็บได้</td>
                      <td class="text-right" id="fin-rev-arrears">3,200.00</td>
                    </tr>
                    <tr>
                      <td>1.3 ค่าธรรมเนียมติดตั้งผู้ใช้น้ำรายใหม่</td>
                      <td class="text-right" id="fin-rev-new-meter">1,500.00</td>
                    </tr>
                    <tr>
                      <td>1.4 ดอกเบี้ยเงินฝากธนาคาร</td>
                      <td class="text-right" id="fin-rev-interest">125.50</td>
                    </tr>
                    <tr>
                      <td>1.5 รายรับอื่นๆ / เงินอุดหนุน</td>
                      <td class="text-right" id="fin-rev-other">0.00</td>
                    </tr>
                    <tr class="fin-total-row highlight-green">
                      <td><strong>รวมรายรับทั้งสิ้น (A)</strong></td>
                      <td class="text-right"><strong id="fin-total-revenue">29,325.50</strong></td>
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
                      <th class="text-right" width="130">จำนวนเงิน (บาท)</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>2.1 ค่าตอบแทนผู้ดูแลระบบประปา</td>
                      <td class="text-right" id="fin-exp-caretaker">3,000.00</td>
                    </tr>
                    <tr>
                      <td>2.2 ค่าตอบแทนคณะกรรมการประปา</td>
                      <td class="text-right" id="fin-exp-committee">2,500.00</td>
                    </tr>
                    <tr>
                      <td>
                        2.3 ค่าตอบแทนเจ้าหน้าที่เก็บค่าน้ำ
                        <div class="sub-basis" id="fin-collector-basis">(คิด 10% จากยอดจัดเก็บ 27,700 บาท)</div>
                      </td>
                      <td class="text-right" id="fin-exp-collector">2,770.00</td>
                    </tr>
                    <tr>
                      <td>2.4 ค่ากระแสไฟฟ้า (บ่อบาดาล/ปั๊มสูบน้ำ)</td>
                      <td class="text-right" id="fin-exp-electricity">6,840.00</td>
                    </tr>
                    <tr>
                      <td>2.5 ค่าวัสดุอุปกรณ์ / ซ่อมบำรุงระบบท่อ</td>
                      <td class="text-right" id="fin-exp-repairs">1,450.00</td>
                    </tr>
                    <tr>
                      <td>2.6 รายจ่ายอื่นๆ</td>
                      <td class="text-right" id="fin-exp-other">300.00</td>
                    </tr>
                    <tr class="fin-total-row highlight-red">
                      <td><strong>รวมรายจ่ายทั้งสิ้น (B)</strong></td>
                      <td class="text-right"><strong id="fin-total-expense">16,860.00</strong></td>
                    </tr>
                  </tbody>
                </table>
              </div>

            </div>

            <!-- Financial Summary Box -->
            <div class="fin-summary-box">
              <div class="summary-card-inner">
                <div class="summary-item">
                  <span class="lbl">กำไร / (ขาดทุน) ประจำเดือน (A - B):</span>
                  <span class="val text-success" id="fin-net-profit">+12,465.50 บาท</span>
                </div>
                <div class="summary-item">
                  <span class="lbl">ยอดเงินคงเหลือสะสมยกมาจากเดือนก่อน:</span>
                  <span class="val" id="fin-prev-balance">145,200.00 บาท</span>
                </div>
                <div class="summary-item grand-highlight">
                  <span class="lbl">ยอดเงินคงเหลือสะสมสุทธิสิ้นเดือน:</span>
                  <strong class="val highlight-blue" id="fin-total-balance">157,665.50 บาท</strong>
                </div>
                <div class="baht-text-line">
                  (ตัวอักษร: <span id="fin-balance-text-th">หนึ่งแสนห้าหมื่นเจ็ดพันหกร้อยหกสิบห้าบาทห้าสิบสตางค์</span>)
                </div>
              </div>

              <!-- Separation of Funds -->
              <div class="funds-split-grid">
                <div class="fund-box cash-box">
                  <div class="fund-icon">💵</div>
                  <div class="fund-info">
                    <span class="fund-label">เงินสดในมือ (สำรองจ่าย)</span>
                    <strong class="fund-amount" id="fin-cash-in-hand">5,000.00 บาท</strong>
                    <span class="fund-desc text-muted">เก็บไว้ที่เหรัญญิกสำหรับหมุนเวียน</span>
                  </div>
                </div>
                <div class="fund-box bank-box">
                  <div class="fund-icon">🏦</div>
                  <div class="fund-info">
                    <span class="fund-label">เงินฝากธนาคาร</span>
                    <strong class="fund-amount text-primary" id="fin-bank-deposit">152,665.50 บาท</strong>
                    <span class="fund-desc text-muted">บัญชี ธ.ก.ส./ออมสิน สาขาอำเภอ</span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Committee Signatures -->
            <div class="report-signatures">
              <div class="sig-block">
                <div class="sig-line">...................................................</div>
                <div class="sig-title">( นายสมาน เก็บเงินดี )</div>
                <div class="sig-role">เจ้าหน้าที่การเงิน / ผู้จัดทำรายงาน</div>
              </div>
              <div class="sig-block">
                <div class="sig-line">...................................................</div>
                <div class="sig-title">( นางจำเนียร ตรวจบัญชี )</div>
                <div class="sig-role">เหรัญญิก / ผู้ตรวจทาน</div>
              </div>
              <div class="sig-block">
                <div class="sig-line">...................................................</div>
                <div class="sig-title">( นายประธาน บริหารกิจการ )</div>
                <div class="sig-role">ประธานกรรมการการประปาหมู่บ้านวังยาง</div>
              </div>
            </div>

          </div>
        </div>
      </section>

      <!-- ======================================================= -->
      <!-- TAB: ทะเบียนติดตามยอดค้างชำระ (Delinquent Accounts) -->
      <!-- ======================================================= -->
      <section id="tab-arrears" class="tab-pane">
        <!-- Aging Stats Cards (no-print) -->
        <div class="stats-grid no-print">
          <div class="stat-card">
            <span class="stat-label">ลูกหนี้ค้างชำระรวม</span>
            <span class="stat-value text-danger" id="arrears-total-debtors">0 ราย</span>
            <span class="stat-desc text-muted">ยอดหนี้รวม <span id="arrears-total-amount">0.00</span> ฿</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">ค้าง 1 งวด (ตักเตือนรอบแรก)</span>
            <span class="stat-value text-warning" id="arrears-count-1m">0 ราย</span>
            <span class="stat-desc text-muted">ยอดค้าง <span id="arrears-amount-1m">0.00</span> ฿</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">ค้าง 2 งวด (ออกหนังสือเตือนฉบับที่ 2)</span>
            <span class="stat-value" style="color: #ea580c;" id="arrears-count-2m">0 ราย</span>
            <span class="stat-desc text-muted">ยอดค้าง <span id="arrears-amount-2m">0.00</span> ฿</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">ค้าง 3 งวดขึ้นไป (วิกฤต/ระงับจ่ายน้ำ)</span>
            <span class="stat-value text-danger" id="arrears-count-3m">0 ราย</span>
            <span class="stat-desc text-danger">ยอดค้าง <span id="arrears-amount-3m">0.00</span> ฿</span>
          </div>
        </div>

        <!-- Filter and Action Bar (no-print) -->
        <div class="filter-card no-print">
          <div class="filter-inputs">
            <div class="search-input-box" style="flex: 1; min-width: 260px;">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" id="arrears-search-input" class="form-input" placeholder="ค้นหาชื่อลูกหนี้, บ้านเลขที่ หรือรหัสผู้ใช้น้ำ...">
            </div>
            <select id="arrears-aging-filter" class="form-select">
              <option value="">ทุกระดับอายุหนี้</option>
              <option value="WARNING_1">ค้าง 1 งวด (เตือนรอบ 1)</option>
              <option value="WARNING_2">ค้าง 2 งวด (เตือนรอบ 2)</option>
              <option value="CRITICAL">ค้างเกิน 3 งวด (วิกฤต/ระงับจ่ายน้ำ)</option>
            </select>
          </div>
          <div class="filter-actions">
            <button class="btn btn-outline" id="btn-arrears-refresh">🔄 รีเฟรชข้อมูลลูกหนี้</button>
            <button class="btn btn-primary" onclick="window.print()">🖨️ พิมพ์รายงานยอดค้างชำระ</button>
          </div>
        </div>

        <!-- Arrears Table Card / Printable Document -->
        <div class="card table-card" id="arrears-print-area">
          <div class="table-responsive">
            <div class="report-header only-print" style="margin-bottom: 16px;">
              <h2>รายงานรายชื่อผู้ใช้น้ำค้างชำระค่าน้ำประปา</h2>
              <h3>กิจการประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง</h3>
              <p>ประจำงวดเดือน <span id="arrears-cycle-display">สิงหาคม 2567</span></p>
            </div>
            <table class="table" id="arrears-table">
              <thead>
                <tr>
                  <th width="60">ลำดับ</th>
                  <th width="100">รหัสผู้ใช้</th>
                  <th>ชื่อ - นามสกุล</th>
                  <th>บ้านเลขที่</th>
                  <th>โซน / ซอย</th>
                  <th width="120">เบอร์โทรศัพท์</th>
                  <th>มิเตอร์</th>
                  <th class="text-center">อายุหนี้ (งวด)</th>
                  <th class="text-right">ยอดค้าง (บาท)</th>
                  <th class="text-center">สถานะติดตาม</th>
                  <th width="190" class="text-center no-print">การดำเนินการ</th>
                </tr>
              </thead>
              <tbody id="arrears-table-body">
                <!-- Dynamically populated -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ======================================================= -->
      <!-- TAB 5: ทะเบียนสมาชิกผู้ใช้น้ำ (Customers) -->
      <!-- ======================================================= -->
      <section id="tab-customers" class="tab-pane">
        <div class="card no-print customer-top-actions">
          <div class="search-wrap" style="flex: 1; min-width: 280px;">
            <div class="search-input-box" style="width: 100%;">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" id="cust-search" class="form-input" placeholder="ค้นหาสมาชิกด้วยชื่อ, นามสกุล, บ้านเลขที่ หรือรหัส...">
            </div>
          </div>
          <div>
            <button class="btn btn-primary" id="btn-add-customer">➕ เพิ่มสมาชิกผู้ใช้น้ำใหม่</button>
          </div>
        </div>

        <div class="card table-card">
          <div class="table-responsive">
            <table class="table" id="customers-table">
              <thead>
                <tr>
                  <th width="70">ลำดับ</th>
                  <th width="120">รหัสผู้ใช้น้ำ</th>
                  <th>ชื่อ - สกุล</th>
                  <th>บ้านเลขที่</th>
                  <th>กลุ่ม / โซน</th>
                  <th>เบอร์โทรศัพท์</th>
                  <th>หมายเลขมิเตอร์</th>
                  <th>สถานะ</th>
                  <th width="120" class="text-center no-print">จัดการ</th>
                </tr>
              </thead>
              <tbody id="customers-table-body">
                <!-- Dynamically populated -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

      <!-- ======================================================= -->
      <!-- TAB 6: ตั้งค่าอัตราค่าน้ำ & พารามิเตอร์ระบบ (Settings) -->
      <!-- ======================================================= -->
      <section id="tab-settings" class="tab-pane">
        <div class="card">
          <div class="card-header">
            <h3>⚙️ กำหนดอัตราค่าน้ำและค่าธรรมเนียมบำรุงรักษา</h3>
            <p class="text-muted">กำหนดอัตราตามมติที่ประชุมคณะกรรมการประปาหมู่บ้านวังยาง</p>
          </div>
          <div class="card-body">
            <form id="settings-form">
              <div class="form-grid">
                <div class="form-group">
                  <label class="form-label" for="setting-water-rate">อัตราค่าน้ำต่อหน่วย (บาท/ลบ.ม.):</label>
                  <input type="number" id="setting-water-rate" class="form-input" step="0.5" min="1" value="7.00">
                  <span class="form-help">อัตราตามมติล่าสุด (เดิม 6 บาท ปรับเป็น 7 หรือ 8 บาท)</span>
                </div>
                <div class="form-group">
                  <label class="form-label" for="setting-maintenance-fee">ค่าบำรุงรักษามิเตอร์ประจำเดือน (บาท/เดือน):</label>
                  <input type="number" id="setting-maintenance-fee" class="form-input" step="1" min="0" value="10.00">
                  <span class="form-help">ค่าบำรุงรักษามาตรวัดน้ำเรียกเก็บทุกเดือน</span>
                </div>
                <div class="form-group">
                  <label class="form-label" for="setting-collector-pct">สัดส่วนค่าตอบแทนคนเก็บค่าน้ำ (%):</label>
                  <input type="number" id="setting-collector-pct" class="form-input" step="1" min="0" max="100" value="10">
                  <span class="form-help">ค่ามาตรฐาน 10% จากยอดที่จัดเก็บได้จริง</span>
                </div>
                <div class="form-group">
                  <label class="form-label" for="setting-cash-reserve">วงเงินสดในมือสำรองจ่าย (บาท):</label>
                  <input type="number" id="setting-cash-reserve" class="form-input" step="500" min="0" value="5000">
                  <span class="form-help">เงินสดสำรองติดตัวเหรัญญิก ส่วนที่เหลือฝากธนาคาร</span>
                </div>
              </div>
              <div class="form-actions mt-4">
                <button type="button" class="btn btn-primary" id="btn-save-settings">💾 บันทึกการตั้งค่า</button>
                <button type="button" class="btn btn-outline" id="btn-reset-demo">🔄 โหลดข้อมูลตัวอย่างใหม่</button>
              </div>
            </form>
          </div>
        </div>
      </section>

      <!-- ======================================================= -->
      <!-- TAB 7: แจ้งซ่อมบำรุงและคำร้องบริการ (Service Tickets) -->
      <!-- ======================================================= -->
      <section id="tab-tickets" class="tab-pane">
        <div class="stats-grid no-print" style="margin-bottom: 20px;">
          <div class="stat-card">
            <span class="stat-label">คำร้องทั้งหมด</span>
            <span class="stat-value" id="ticket-stat-total">0</span>
            <span class="stat-desc text-muted">รวมทุกสถานะ</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">รอดำเนินการ (Pending)</span>
            <span class="stat-value" style="color: #d97706;" id="ticket-stat-pending">0</span>
            <span class="stat-desc text-muted">รอช่างเข้าตรวจสอบ</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">กำลังดำเนินการ (In Progress)</span>
            <span class="stat-value" style="color: #0284c7;" id="ticket-stat-progress">0</span>
            <span class="stat-desc text-muted">ช่างกำลังลงพื้นที่</span>
          </div>
          <div class="stat-card">
            <span class="stat-label">แก้ไขเรียบร้อย (Resolved)</span>
            <span class="stat-value text-success" id="ticket-stat-resolved">0</span>
            <span class="stat-desc text-muted">ซ่อมเสร็จสมบูรณ์</span>
          </div>
        </div>

        <div class="card no-print" style="margin-bottom: 16px; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
          <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <label for="ticket-filter-status" style="font-size: 13.5px; font-weight: 600;">กรองสถานะ:</label>
            <select id="ticket-filter-status" class="form-select" style="min-width: 160px;">
              <option value="ALL">ทั้งหมด</option>
              <option value="PENDING">🟡 รอดำเนินการ</option>
              <option value="IN_PROGRESS">🔵 กำลังซ่อมแซม</option>
              <option value="RESOLVED">🟢 แก้ไขเรียบร้อย</option>
            </select>
            <div class="search-input-box" style="min-width: 250px;">
              <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
              </svg>
              <input type="text" id="ticket-search-input" class="form-input" placeholder="ค้นหารหัสคำร้อง, ผู้แจ้ง หรือเบอร์โทร...">
            </div>
          </div>
          <div style="display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" id="btn-line-settings" onclick="openLineSettingsModal()" style="border-color: #06c755; color: #16a34a; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;">
              <span>📲</span> ตั้งค่า LINE Notify
            </button>
            <button type="button" class="btn btn-outline" id="btn-refresh-tickets">🔄 รีเฟรชรายการ</button>
          </div>
        </div>

        <div class="card table-card">
          <div class="table-responsive">
            <table class="table" id="tickets-admin-table">
              <thead>
                <tr>
                  <th width="120">รหัสคำร้อง</th>
                  <th width="130">วันที่แจ้ง</th>
                  <th>ผู้แจ้งเหตุ</th>
                  <th width="120">เบอร์โทรศัพท์</th>
                  <th>สถานที่ / โซน</th>
                  <th>ประเภทคำร้อง</th>
                  <th>รายละเอียดอาการ</th>
                  <th width="70" class="text-center">รูปถ่าย</th>
                  <th width="130" class="text-center">สถานะ</th>
                  <th width="120" class="text-center no-print">จัดการ</th>
                </tr>
              </thead>
              <tbody id="tickets-admin-tbody">
                <!-- Dynamically populated -->
              </tbody>
            </table>
          </div>
        </div>
      </section>

    </main>
  </div>

  <!-- Modal for Adding/Editing Customer -->
  <div class="modal" id="customer-modal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="cust-modal-title">เพิ่มสมาชิกผู้ใช้น้ำ</h3>
          <button class="modal-close" onclick="closeModal('customer-modal')">&times;</button>
        </div>
        <div class="modal-body">
          <form id="cust-form">
            <input type="hidden" id="cust-id">
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">รหัสผู้ใช้น้ำ:</label>
                <input type="text" id="form-cust-code" class="form-input" placeholder="เช่น WY-006" required>
              </div>
              <div class="form-group">
                <label class="form-label">ลำดับการจด:</label>
                <input type="number" id="form-cust-seq" class="form-input" value="6" required>
              </div>
              <div class="form-group">
                <label class="form-label">ชื่อ:</label>
                <input type="text" id="form-cust-first" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">นามสกุล:</label>
                <input type="text" id="form-cust-last" class="form-input" required>
              </div>
              <div class="form-group">
                <label class="form-label">บ้านเลขที่:</label>
                <input type="text" id="form-cust-house" class="form-input" placeholder="เช่น 24 หมู่ 3" required>
              </div>
              <div class="form-group">
                <label class="form-label">กลุ่ม / โซน:</label>
                <input type="text" id="form-cust-zone" class="form-input" placeholder="เช่น โซนเหนือ, ซอย 2" required>
              </div>
              <div class="form-group">
                <label class="form-label">เบอร์โทรศัพท์:</label>
                <input type="text" id="form-cust-phone" class="form-input">
              </div>
              <div class="form-group">
                <label class="form-label">หมายเลขซีเรียลมิเตอร์:</label>
                <input type="text" id="form-cust-serial" class="form-input" placeholder="เช่น M-2023-006">
              </div>
            </div>
            <div class="form-actions mt-4 text-right">
              <button type="button" class="btn btn-outline" onclick="closeModal('customer-modal')">ยกเลิก</button>
              <button type="submit" class="btn btn-primary">บันทึกข้อมูล</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal for Opening New Billing Cycle -->
  <div class="modal" id="cycle-modal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h3>➕ เปิดรอบบิลประจำเดือนใหม่</h3>
          <button class="modal-close" onclick="closeModal('cycle-modal')">&times;</button>
        </div>
        <div class="modal-body">
          <form id="cycle-form">
            <p class="text-muted" style="margin-bottom: 14px;">
              ระบบจะดึง <strong>"เลขมิเตอร์ครั้งหลัง"</strong> ของเดือนที่แล้วมาเป็น <strong>"เลขครั้งก่อน"</strong> และทบ <strong>"ยอดค้างชำระ"</strong> มายังรอบใหม่อัตโนมัติ
            </p>
            <div class="form-grid">
              <div class="form-group">
                <label class="form-label">เดือน (1 - 12):</label>
                <input type="number" id="form-cycle-month" class="form-input" min="1" max="12" value="9" required>
              </div>
              <div class="form-group">
                <label class="form-label">ปี พ.ศ.:</label>
                <input type="number" id="form-cycle-year" class="form-input" value="2567" required>
              </div>
              <div class="form-group">
                <label class="form-label">รหัสรอบบิล:</label>
                <input type="text" id="form-cycle-code" class="form-input" value="9-2567" required>
              </div>
              <div class="form-group">
                <label class="form-label">ยกยอดมาจากงวด:</label>
                <input type="text" id="form-cycle-prev" class="form-input" value="8-2567" readonly>
              </div>
            </div>
            <div class="form-actions mt-4 text-right">
              <button type="button" class="btn btn-outline" onclick="closeModal('cycle-modal')">ยกเลิก</button>
              <button type="submit" class="btn btn-primary">สร้างรอบบิลและทบยอด</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal for Warning Notice Letter (หนังสือเตือนระงับการจ่ายน้ำ) -->
  <div class="modal" id="arrears-notice-modal">
    <div class="modal-dialog" style="max-width: 680px;">
      <div class="modal-content">
        <div class="modal-header" style="background: #dc2626; color: #fff;">
          <h3 style="color: #fff; margin: 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
            <span>⚠️</span> หนังสือเตือนให้ชำระหนี้ค่าน้ำประปา (ก่อนระงับการจ่ายน้ำ)
          </h3>
          <button class="modal-close" onclick="closeModal('arrears-notice-modal')" style="color: #fff;">&times;</button>
        </div>
        <div class="modal-body" id="arrears-notice-body" style="padding: 24px; font-family: 'Sarabun', sans-serif;">
          <!-- Dynamically populated notice letter -->
        </div>
        <div class="form-actions text-right" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('arrears-notice-modal')">ปิดหน้าต่าง</button>
          <button type="button" class="btn btn-primary" onclick="printArrearsNotice()" style="background: #dc2626; border-color: #dc2626;">🖨️ สั่งพิมพ์หนังสือเตือน</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal for Updating Service Ticket Status -->
  <div class="modal" id="ticket-edit-modal">
    <div class="modal-dialog" style="max-width: 540px;">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="ticket-modal-title" style="margin: 0; font-size: 16px; font-weight: 700;">🔧 บันทึกการดำเนินการซ่อมบำรุง</h3>
          <button class="modal-close" onclick="closeModal('ticket-edit-modal')">&times;</button>
        </div>
        <div class="modal-body">
          <form id="ticket-edit-form" onsubmit="handleTicketUpdateSubmit(event)">
            <input type="hidden" id="edit-ticket-id">
            <div class="form-group" style="margin-bottom: 12px;">
              <label class="form-label">รหัสคำร้อง:</label>
              <input type="text" id="edit-ticket-no" class="form-input" readonly style="background: #f1f5f9; font-weight: 700;">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
              <label class="form-label">ผู้แจ้ง / อาการ:</label>
              <div id="edit-ticket-summary" style="padding: 10px; background: #f8fafc; border-radius: 6px; font-size: 13.5px; border: 1px solid #e2e8f0;"></div>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
              <label class="form-label">อัปเดตสถานะงาน:</label>
              <select id="edit-ticket-status" class="form-select" required>
                <option value="PENDING">🟡 รอดำเนินการ (รอช่างเข้าพื้นที่)</option>
                <option value="IN_PROGRESS">🔵 กำลังดำเนินการซ่อมบำรุง</option>
                <option value="RESOLVED">🟢 แก้ไขเรียบร้อยแล้ว (ปิดงาน)</option>
              </select>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
              <label class="form-label">บันทึกผลการตรวจสอบ / การซ่อมแซม:</label>
              <textarea id="edit-ticket-notes" class="form-input" rows="3" placeholder="ระบุการใช้อุปกรณ์ การเปลี่ยนท่อ ข้อต่อ หรือแนวทางแก้ไข..."></textarea>
            </div>
            <div class="form-group" style="margin-bottom: 14px;">
              <label class="form-label">ค่าใช้จ่ายในการซ่อมบำรุง (บาท):</label>
              <input type="number" id="edit-ticket-cost" class="form-input" step="10" min="0" value="0">
            </div>
            <div class="form-actions text-right">
              <button type="button" class="btn btn-outline" onclick="closeModal('ticket-edit-modal')">ยกเลิก</button>
              <button type="submit" class="btn btn-primary">💾 บันทึกผลการซ่อม</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal for LINE Notify Settings -->
  <div class="modal" id="line-notify-modal">
    <div class="modal-dialog" style="max-width: 520px;">
      <div class="modal-content">
        <div class="modal-header" style="background: #06c755; color: #fff;">
          <h3 style="margin: 0; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
            <span>📲</span> ตั้งค่าการแจ้งเตือน LINE Notify
          </h3>
          <button class="modal-close" onclick="closeModal('line-notify-modal')" style="color: #fff; background: none; border: none; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
          <form id="line-notify-form" onsubmit="handleLineSettingsSubmit(event)">
            <p style="font-size: 13.5px; color: #475569; margin-top: 0; line-height: 1.5;">
              เมื่อชาวบ้านส่งคำร้องแจ้งท่อแตก น้ำรั่ว หรือมาตรวัดน้ำชำรุด ระบบจะส่งข้อความและรูปถ่ายแจ้งเตือนเข้ากลุ่ม LINE ของเจ้าหน้าที่ประปาอัตโนมัติทันที
            </p>

            <div class="form-group" style="margin-bottom: 14px;">
              <label class="form-label" style="font-weight: 600;">สถานะระบบแจ้งเตือน:</label>
              <select id="line-setting-enabled" class="form-select">
                <option value="1">🟢 เปิดใช้งานระบบแจ้งเตือน LINE (Enabled)</option>
                <option value="0">🔴 ปิดการแจ้งเตือนชั่วคราว (Disabled)</option>
              </select>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
              <label class="form-label" style="font-weight: 600;">LINE Notify Access Token:</label>
              <input type="password" id="line-setting-token" class="form-input" placeholder="กรอก Token จาก notify-bot.line.me">
              <span id="line-token-status" style="font-size: 12.5px; color: #64748b; display: block; margin-top: 4px;">* กำลังตรวจสอบสถานะ Token...</span>
            </div>

            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; font-size: 13px; color: #166534; line-height: 1.5;">
              💡 <strong>วิธีรับ Token:</strong> เข้าเว็บไซต์ <a href="https://notify-bot.line.me" target="_blank" style="color: #06c755; font-weight: 700;">notify-bot.line.me</a> &rarr; เข้าสู่ระบบด้วย LINE &rarr; เมนู My page &rarr; กด <em>Generate token</em> &rarr; เลือกห้องแชท/กลุ่มช่างที่ต้องการ &rarr; คัดลอก Token มาวางที่นี่
            </div>

            <div class="form-actions text-right" style="display: flex; justify-content: flex-end; gap: 8px;">
              <button type="button" class="btn btn-outline" onclick="closeModal('line-notify-modal')">ปิด</button>
              <button type="submit" class="btn btn-primary" style="background: #06c755; border-color: #06c755; font-weight: 600;" id="btn-save-line-settings">
                💾 บันทึกและทดสอบส่งแจ้งเตือน
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script src="src/services/bahtText.js"></script>
  <script src="src/services/billingService.js"></script>
  <script src="src/services/receiptService.js"></script>
  <script src="src/services/financialReportService.js"></script>
  <script src="app.js"></script>
</body>
</html>

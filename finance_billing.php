<?php
/**
 * 3. งานรับชำระเงินและจัดทำฎีกาเบิกจ่าย
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
  <title>งานการเงิน ออกใบเสร็จ และฎีกาเบิกจ่าย - การประปาหมู่บ้านวังยาง</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <?php renderPwaHead(); ?>
  <style>
    .finance-subnav {
      display: flex;
      gap: 8px;
      margin-bottom: 20px;
      background: #fff;
      padding: 8px 12px;
      border-radius: 10px;
      border: 1px solid var(--border);
      box-shadow: 0 1px 3px rgba(0,0,0,0.04);
      flex-wrap: wrap;
    }
    .fin-tab-btn {
      padding: 9px 18px;
      border-radius: 8px;
      border: 1px solid transparent;
      background: #f8fafc;
      font-family: 'Prompt', sans-serif;
      font-size: 13.5px;
      font-weight: 600;
      color: #475569;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.15s ease;
    }
    .fin-tab-btn:hover {
      background: #f1f5f9;
      color: #0f172a;
    }
    .fin-tab-btn.active {
      background: #0284c7;
      color: #fff;
      border-color: #0284c7;
      box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
    }
    .fin-pane {
      display: none;
    }
    .fin-pane.active {
      display: block;
      animation: finFadeIn 0.22s ease-out;
    }
    @keyframes finFadeIn {
      from { opacity: 0; transform: translateY(4px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .fin-status-pills {
      display: inline-flex;
      gap: 4px;
      background: #f1f5f9;
      padding: 4px;
      border-radius: 8px;
    }
    .fin-pill-btn {
      padding: 6px 14px;
      border-radius: 6px;
      border: none;
      background: transparent;
      font-size: 13px;
      font-weight: 600;
      color: #64748b;
      cursor: pointer;
      transition: all 0.15s ease;
    }
    .fin-pill-btn:hover {
      color: #0f172a;
    }
    .fin-pill-btn.active {
      background: #ffffff;
      color: #0f172a;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .toast-container {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .toast-msg {
      background: #0f172a;
      color: #fff;
      padding: 12px 18px;
      border-radius: 8px;
      font-size: 13.5px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.25);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .toast-success { border-left: 4px solid #22c55e; }
    .toast-info { border-left: 4px solid #38bdf8; }
    .toast-error { border-left: 4px solid #ef4444; }
  </style>
</head>
<body>
  <div class="app-layout">
    <!-- Global Persistent Sidebar with Real URL Routes -->
    <?php renderAppSidebar('finance'); ?>

    <!-- Main Content Area -->
    <main class="main-content">
      <?php renderAppTopBar('งานรับชำระเงินและจัดทำฎีกาเบิกจ่าย', 'งานการเงิน เหรัญญิก ออกใบเสร็จมาตรฐาน ทะเบียนคุมหนี้ และฎีกา 10%'); ?>

    <!-- Top Action / Cycle Bar -->
    <div class="card no-print" style="margin-bottom: 16px; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
      <div style="display: flex; align-items: center; gap: 12px;">
        <span style="font-size: 24px;">💼</span>
        <div>
          <h2 style="font-family: 'Prompt', sans-serif; font-size: 18px; margin: 0; color: #0f172a;">ระบบงานการเงินและบัญชีกองทุนประปา</h2>
          <span style="font-size: 13.5px; color: #64748b;">งวดบัญชีประจำเดือน: <strong><span id="current-cycle-display"><?php echo $currentCycleCode; ?></span></strong></span>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 10px;">
        <label for="fin-cycle-select" style="font-size: 13px; font-weight: 600;">เลือกงวดเดือน:</label>
        <select id="fin-cycle-select" class="form-select" style="font-weight: 600;">
          <?php foreach ($cycles as $c): ?>
            <option value="<?php echo htmlspecialchars($c['cycle_code']); ?>" <?php echo $c['cycle_code'] === $currentCycleCode ? 'selected' : ''; ?>>
              งวดเดือน <?php echo $c['month'] . '/' . $c['year_be'] . ' (' . $c['cycle_code'] . ')'; ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-outline" id="btn-export-excel">📥 ส่งออก Excel</button>
      </div>
    </div>

    <!-- Finance Sub-navigation Tabs -->
    <div class="finance-subnav no-print">
      <button class="fin-tab-btn active" data-target="pane-collection">
        <span>✅</span> 1. ตัดรับชำระเงินประจำงวด
      </button>
      <button class="fin-tab-btn" data-target="pane-receipt">
        <span>🧾</span> 2. พิมพ์ใบเสร็จรับเงิน (ป.31/32)
      </button>
      <button class="fin-tab-btn" data-target="pane-arrears">
        <span>⚠️</span> 3. ทะเบียนคุมหนี้ค้างชำระ (กค.4)
      </button>
      <button class="fin-tab-btn" data-target="pane-vouchers">
        <span>📜</span> 4. ฎีกาเบิกจ่าย & ใบสำคัญรับเงิน (10%)
      </button>
    </div>

    <!-- ========================================================= -->
    <!-- PANE 1: ตัดรับชำระเงินประจำงวด (Payment Collection) -->
    <!-- ========================================================= -->
    <div id="pane-collection" class="fin-pane active">
      <div class="stats-grid no-print" style="margin-bottom: 16px;">
        <div class="stat-card">
          <span class="stat-label">ยอดเรียกเก็บงวดนี้</span>
          <span class="stat-value text-primary" id="col-stat-billed">0.00 ฿</span>
          <span class="stat-desc text-muted">รวมหนี้เก่า <span id="col-stat-arrears">0.00</span> ฿</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">รับชำระแล้ว (เงินสด/โอน)</span>
          <span class="stat-value text-success" id="col-stat-paid">0.00 ฿</span>
          <span class="stat-desc text-success">ชำระแล้ว <span id="col-stat-paid-count">0</span> ราย</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">ยอดค้างชำระงวดนี้</span>
          <span class="stat-value text-danger" id="col-stat-unpaid">0.00 ฿</span>
          <span class="stat-desc text-danger">ค้างชำระ <span id="col-stat-unpaid-count">0</span> ราย</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">ค่าตอบแทนคนเก็บ (10%)</span>
          <span class="stat-value" style="color: #4338ca;" id="col-stat-fee10">0.00 ฿</span>
          <span class="stat-desc text-muted">คิด 10% จากยอดรับชำระจริง</span>
        </div>
      </div>

      <div class="card table-card">
        <div class="no-print" style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
          <!-- Filter status pills & Zone dropdown -->
          <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <div class="fin-status-pills">
              <button type="button" class="fin-pill-btn active" data-col-status="all" onclick="setColStatusFilter('all')">
                ทั้งหมด <span id="col-pill-all" style="font-weight: 700;">0</span>
              </button>
              <button type="button" class="fin-pill-btn" data-col-status="unpaid" onclick="setColStatusFilter('unpaid')">
                ⏳ ยังไม่จ่าย <span id="col-pill-unpaid" style="color: #dc2626; font-weight: 700;">0</span>
              </button>
              <button type="button" class="fin-pill-btn" data-col-status="paid" onclick="setColStatusFilter('paid')">
                ✅ ชำระแล้ว <span id="col-pill-paid" style="color: #16a34a; font-weight: 700;">0</span>
              </button>
            </div>

            <select id="col-zone-select" class="form-select" onchange="renderCollectionRows()" style="font-size: 13px; padding: 6px 12px; min-width: 150px;">
              <option value="">ทุกโซน / ทุกคุ้ม</option>
              <option value="โซน 1">โซน 1 วังยางเหนือ</option>
              <option value="โซน 2">โซน 2 วังยางกลาง</option>
              <option value="โซน 3">โซน 3 วังยางใต้</option>
            </select>
          </div>

          <!-- Search input & Action buttons -->
          <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <input type="text" id="col-search-input" oninput="renderCollectionRows()" class="form-input" placeholder="🔍 ค้นหาบ้านเลขที่, รหัส หรือชื่อ..." style="font-size: 13.5px; padding: 7px 14px; min-width: 230px;">
            <button type="button" class="btn btn-outline" id="btn-reload-fin" style="font-size: 13px; padding: 7px 12px;">🔄 รีเฟรช</button>
            <button type="button" class="btn btn-secondary" id="btn-pay-all-fin" style="font-size: 13px; padding: 7px 16px;">✅ ตัดรับชำระทั้งหมด</button>
          </div>
        </div>
        <div class="table-responsive">
          <table class="table" id="collection-table">
            <thead>
              <tr>
                <th width="50" class="text-center">ลำดับ</th>
                <th width="100">รหัสผู้ใช้</th>
                <th>ชื่อ - นามสกุล</th>
                <th>บ้านเลขที่</th>
                <th>โซน</th>
                <th width="120">เบอร์โทรศัพท์</th>
                <th class="text-right">หน่วยใช้</th>
                <th class="text-right">ยอดรวมสุทธิ</th>
                <th class="text-center" width="120">สถานะ</th>
                <th class="text-center" width="130">เลขที่ใบเสร็จ</th>
                <th class="text-center no-print" width="160">การดำเนินการ</th>
              </tr>
            </thead>
            <tbody id="collection-table-body">
              <!-- Dynamically populated -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ========================================================= -->
    <!-- PANE 2: พิมพ์ใบเสร็จรับเงิน (แบบ ป.31/32) -->
    <!-- ========================================================= -->
    <div id="pane-receipt" class="fin-pane">
      <div class="card no-print receipt-toolbar" style="margin-bottom: 20px; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <label for="rcpt-cust-select" style="font-weight: 600;">เลือกสมาชิกผู้ใช้น้ำ:</label>
          <select id="rcpt-cust-select" class="form-select" style="min-width: 280px; font-weight: 600;"></select>
          <button type="button" class="btn btn-outline" id="btn-rcpt-prev">&larr; คนก่อนหน้า</button>
          <button type="button" class="btn btn-outline" id="btn-rcpt-next">คนถัดไป &rarr;</button>
        </div>
        <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ สั่งพิมพ์ใบเสร็จนี้</button>
      </div>

      <!-- Printable Receipt Document -->
      <div class="receipt-document" style="background: #fff; padding: 30px; border-radius: 8px; border: 1px solid #cbd5e1; max-width: 820px; margin: 0 auto; box-shadow: var(--shadow);">
        <div class="receipt-header" style="text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px;">
          <h2 style="font-family: 'Prompt', sans-serif; font-size: 20px; margin: 0; color: #0f172a;">ใบเสร็จรับเงินค่าน้ำประปาหมู่บ้าน</h2>
          <h3 style="font-size: 15px; margin: 2px 0; color: #334151;">การประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</h3>
          <span style="display: inline-block; background: #0f172a; color: #fff; font-size: 13px; font-weight: 700; padding: 3px 14px; border-radius: 4px; margin-top: 6px;">
            แบบ ป.31/32 (ต้นฉบับสำหรับผู้ใช้น้ำ)
          </span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, 1fr); font-size: 13.5px; margin-bottom: 16px; background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px;">
          <div>
            <strong>เลขที่ใบเสร็จ:</strong> <span id="r-no">8-2567/501</span><br>
            <strong>วันที่ออกเอกสาร:</strong> <span id="r-date">-</span><br>
            <strong>ประจำงวดเดือน:</strong> <span id="r-cycle">-</span>
          </div>
          <div>
            <strong>ชื่อผู้ใช้น้ำ:</strong> <span id="r-name">-</span><br>
            <strong>รหัสผู้ใช้น้ำ:</strong> <span id="r-code">-</span> | <strong>บ้านเลขที่:</strong> <span id="r-house">-</span><br>
            <strong>หมายเลขมิเตอร์:</strong> <span id="r-serial">-</span> | <strong>เบอร์โทรศัพท์:</strong> <span id="r-phone">-</span>
          </div>
        </div>

        <table class="table" style="margin-bottom: 16px;">
          <thead>
            <tr>
              <th>รายการเรียกเก็บ</th>
              <th class="text-right">เลขครั้งก่อน</th>
              <th class="text-right">เลขครั้งหลัง</th>
              <th class="text-right">หน่วยที่ใช้</th>
              <th class="text-right">อัตรา/หน่วย</th>
              <th class="text-right">จำนวนเงิน (บาท)</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>ค่าน้ำประปาประจำงวด</td>
              <td class="text-right" id="r-prev">0.00</td>
              <td class="text-right" id="r-curr">0.00</td>
              <td class="text-right" id="r-units">0.00</td>
              <td class="text-right" id="r-rate">7.00</td>
              <td class="text-right font-bold" id="r-water-charge">0.00</td>
            </tr>
            <tr>
              <td colspan="5">ค่าบำรุงรักษามิเตอร์ประจำเดือน</td>
              <td class="text-right font-bold" id="r-fee">10.00</td>
            </tr>
            <tr style="color: #b91c1c;">
              <td colspan="5">ยอดค้างชำระยกยอดมาจากเดือนก่อน</td>
              <td class="text-right font-bold" id="r-arrears">0.00</td>
            </tr>
            <tr class="row-grand-total">
              <td colspan="3" style="font-size: 13px;">
                (ตัวอักษร): <strong id="r-text-th">-</strong>
              </td>
              <td colspan="2" class="text-right"><strong>ยอดรวมสุทธิที่ต้องชำระ:</strong></td>
              <td class="text-right"><strong class="grand-amount" id="r-grand" style="font-size: 18px; color: #dc2626;">0.00</strong></td>
            </tr>
          </tbody>
        </table>

        <!-- PromptPay Block -->
        <div style="background: #f0fdf4; border: 1px dashed #22c55e; border-radius: 8px; padding: 12px 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
          <div style="display: flex; align-items: center; gap: 14px;">
            <img id="r-qr-img" src="" alt="PromptPay QR" style="width: 90px; height: 90px; background: #fff; border: 1px solid #bbf7d0; border-radius: 6px; padding: 2px;">
            <div>
              <span style="background: #15803d; color: #fff; font-size: 13px; font-weight: 700; padding: 3px 8px; border-radius: 4px;">พร้อมเพย์ (PromptPay)</span>
              <div style="font-size: 13.5px; font-weight: 700; color: #166534; margin: 3px 0;">กองทุนประปาหมู่บ้านวังยาง (ธนาคารเพื่อการเกษตรและสหกรณ์การเกษตร)</div>
              <div style="font-size: 13.5px; color: #374151;">หมายเลขพร้อมเพย์: <strong>081-234-5678</strong> | ยอด: <strong id="r-qr-amt" style="color: #b91c1c;">0.00 บาท</strong></div>
            </div>
          </div>
          <div class="no-print" style="text-align: right; font-size: 13px; color: #15803d; font-weight: 600;">
            ⚡ รองรับ Mobile Banking ทุกธนาคาร
          </div>
        </div>

        <div class="receipt-signatures" style="display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; margin-top: 30px; font-size: 13.5px;">
          <div>
            <div>...................................................</div>
            <div style="margin-top: 4px;">(<span id="r-sig-payer">-</span>)</div>
            <div style="color: #64748b;">ผู้ชำระเงิน</div>
          </div>
          <div>
            <div>...................................................</div>
            <div style="margin-top: 4px;">( นางจำเนียร ตรวจบัญชี )</div>
            <div style="color: #64748b;">เจ้าหน้าที่การเงิน / เหรัญญิก</div>
          </div>
          <div>
            <div>...................................................</div>
            <div style="margin-top: 4px;">( นายประธาน บริหารกิจการ )</div>
            <div style="color: #64748b;">ประธานกรรมการประปาหมู่บ้าน</div>
          </div>
        </div>
      </div>
    </div>

    <!-- ========================================================= -->
    <!-- PANE 3: ทะเบียนคุมหนี้ค้างชำระ (แบบ กค.4) -->
    <!-- ========================================================= -->
    <div id="pane-arrears" class="fin-pane">
      <div class="stats-grid no-print" style="margin-bottom: 16px;">
        <div class="stat-card">
          <span class="stat-label">ลูกหนี้ค้างชำระรวม</span>
          <span class="stat-value text-danger" id="fa-total-debtors">0 ราย</span>
          <span class="stat-desc text-muted">ยอดหนี้รวม <span id="fa-total-debt">0.00</span> ฿</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">ค้าง 1 งวด (เตือนรอบแรก)</span>
          <span class="stat-value text-warning" id="fa-1m-count">0 ราย</span>
          <span class="stat-desc text-muted">ยอดค้าง <span id="fa-1m-amt">0.00</span> ฿</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">ค้าง 2 งวด (เตือนฉบับที่ 2)</span>
          <span class="stat-value" style="color: #ea580c;" id="fa-2m-count">0 ราย</span>
          <span class="stat-desc text-muted">ยอดค้าง <span id="fa-2m-amt">0.00</span> ฿</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">ค้าง 3 งวดขึ้นไป (วิกฤต/ระงับน้ำ)</span>
          <span class="stat-value text-danger" id="fa-3m-count">0 ราย</span>
          <span class="stat-desc text-danger">ยอดค้าง <span id="fa-3m-amt">0.00</span> ฿</span>
        </div>
      </div>

      <div class="card table-card">
        <div class="no-print" style="padding: 14px 18px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
          <div class="fin-status-pills">
            <button type="button" class="fin-pill-btn active" data-arr-filter="all" onclick="setArrearsFilter('all')">
              ทั้งหมด <span id="arr-pill-all" style="font-weight: 700;">0</span>
            </button>
            <button type="button" class="fin-pill-btn" data-arr-filter="1" onclick="setArrearsFilter('1')">
              ค้าง 1 งวด <span id="arr-pill-1" style="color: #d97706; font-weight: 700;">0</span>
            </button>
            <button type="button" class="fin-pill-btn" data-arr-filter="2" onclick="setArrearsFilter('2')">
              ค้าง 2 งวด <span id="arr-pill-2" style="color: #ea580c; font-weight: 700;">0</span>
            </button>
            <button type="button" class="fin-pill-btn" data-arr-filter="3" onclick="setArrearsFilter('3')">
              ค้าง 3+ งวด (วิกฤต) <span id="arr-pill-3" style="color: #dc2626; font-weight: 700;">0</span>
            </button>
          </div>

          <div style="display: flex; align-items: center; gap: 8px;">
            <input type="text" id="fa-search-input" oninput="renderArrearsRows()" class="form-input" placeholder="🔍 ค้นหาลูกหนี้, รหัส หรือบ้านเลขที่..." style="font-size: 13.5px; padding: 7px 14px; min-width: 240px;">
          </div>
        </div>
        <div class="table-responsive">
          <table class="table" id="fin-arrears-table">
            <thead>
              <tr>
                <th width="60">ลำดับ</th>
                <th width="100">รหัสผู้ใช้</th>
                <th>ชื่อ - นามสกุล</th>
                <th>บ้านเลขที่</th>
                <th>โซน</th>
                <th width="120">เบอร์โทรศัพท์</th>
                <th>มิเตอร์</th>
                <th class="text-center">อายุหนี้ (งวด)</th>
                <th class="text-right">ยอดค้างรวม (บาท)</th>
                <th class="text-center">ระดับการติดตาม</th>
                <th class="text-center no-print" width="180">การจัดการ</th>
              </tr>
            </thead>
            <tbody id="fin-arrears-tbody">
              <!-- Dynamically populated -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ========================================================= -->
    <!-- PANE 4: ฎีกาเบิกจ่าย & ใบสำคัญรับเงิน (10%) -->
    <!-- ========================================================= -->
    <div id="pane-vouchers" class="fin-pane">
      <div class="no-print" style="margin-bottom: 16px; display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="btn btn-primary" onclick="window.print()" style="font-weight: 600; padding: 8px 18px; display: inline-flex; align-items: center; gap: 6px;">
          🖨️ สั่งพิมพ์เอกสารฎีกาเบิกจ่ายทั้งหมด
        </button>
      </div>
      <div id="fin-vouchers-container">
        <!-- Dynamically populated payment vouchers -->
      </div>
    </div>

    </main>
  </div> <!-- /.app-layout -->

  <!-- Modal for Warning Notice Letter (หนังสือเตือนระงับการจ่ายน้ำ) -->
  <div class="modal" id="fin-notice-modal">
    <div class="modal-dialog" style="max-width: 680px;">
      <div class="modal-content">
        <div class="modal-header" style="background: #dc2626; color: #fff;">
          <h3 style="color: #fff; margin: 0; font-size: 16px; font-weight: 700;">⚠️ หนังสือเตือนให้ชำระหนี้ค่าน้ำประปา (ก่อนระงับการใช้น้ำ)</h3>
          <button class="modal-close" onclick="closeModal('fin-notice-modal')" style="color: #fff;">&times;</button>
        </div>
        <div class="modal-body" id="fin-notice-body" style="padding: 24px; font-family: 'Sarabun', sans-serif;">
          <!-- Dynamically populated -->
        </div>
        <div class="form-actions text-right" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-outline" onclick="closeModal('fin-notice-modal')">ปิด</button>
          <button type="button" class="btn btn-primary" onclick="printFinNotice()" style="background: #dc2626; border-color: #dc2626;">🖨️ สั่งพิมพ์หนังสือเตือน</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div class="toast-container" id="toast-container"></div>

  <!-- Scripts -->
  <script>
    const API_BASE = 'api';
    let currentCycle = '<?php echo $currentCycleCode; ?>';
    let customersList = [];
    let readingsList = [];
    let debtorsList = [];
    let selectedCustIdx = 0;

    // Filters state
    let colStatusFilter = 'all'; // 'all', 'unpaid', 'paid'
    let arrFilter = 'all'; // 'all', '1', '2', '3'

    document.addEventListener('DOMContentLoaded', async () => {
      // Setup Subnav Tab click listeners
      document.querySelectorAll('.fin-tab-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
          e.preventDefault();
          const targetId = btn.dataset.target;
          switchFinanceTab(targetId, true);
        });
      });

      // Intercept clicks on sidebar items pointing to finance_billing.php to prevent unnecessary full-page reload
      document.querySelectorAll('.sidebar .nav-item').forEach(link => {
        const href = link.getAttribute('href');
        if (href && (href.startsWith('finance_billing.php') || href.startsWith('/finance_billing.php'))) {
          link.addEventListener('click', (e) => {
            const hashIndex = href.indexOf('#');
            const targetPane = hashIndex !== -1 ? href.substring(hashIndex + 1) : 'pane-collection';
            switchFinanceTab(targetPane, true);
            e.preventDefault();
          });
        }
      });

      // Handle browser back/forward or hash changes
      window.addEventListener('hashchange', () => {
        handleUrlHash();
      });

      // Cycle selector change
      document.getElementById('fin-cycle-select')?.addEventListener('change', async (e) => {
        currentCycle = e.target.value;
        const disp = document.getElementById('current-cycle-display');
        if (disp) disp.textContent = currentCycle;
        await reloadAllFinanceData();
      });

      // Export Excel button
      document.getElementById('btn-export-excel')?.addEventListener('click', () => {
        window.location.href = `${API_BASE}/export_excel.php?cycle=${encodeURIComponent(currentCycle)}`;
      });

      // Reload Button
      document.getElementById('btn-reload-fin')?.addEventListener('click', async () => {
        showToast('กำลังรีเฟรชข้อมูล...', 'info');
        await reloadAllFinanceData();
        showToast('รีเฟรชข้อมูลเรียบร้อยแล้ว', 'success');
      });

      // Pay All Unpaid Button
      document.getElementById('btn-pay-all-fin')?.addEventListener('click', async () => {
        const unpaids = readingsList.filter(r => r.payment_status !== 'PAID');
        if (unpaids.length === 0) {
          alert('สมาชิกทุกท่านชำระเงินเรียบร้อยแล้วในงวดนี้');
          return;
        }
        if (!confirm(`ยืนยันการตัดรับชำระเงินสำหรับ ${unpaids.length} รายการที่ค้างอยู่ใช่หรือไม่?`)) return;

        showToast(`กำลังตัดรับชำระ ${unpaids.length} รายการ...`, 'info');
        // Optimistically mark all as PAID
        readingsList.forEach(r => {
          if (r.payment_status !== 'PAID') {
            r.payment_status = 'PAID';
            if (!r.receipt_no) {
              r.receipt_no = 'RC-' + currentCycle + '-' + String(r.customer_id).padStart(3, '0');
            }
          }
        });
        renderCollectionRows();

        try {
          for (const r of unpaids) {
            await fetch(`${API_BASE}/readings.php?cycle=${encodeURIComponent(currentCycle)}&action=toggle-paid&customerId=${r.customer_id}`, { method: 'POST' });
          }
          showToast(`✅ ตัดรับชำระเงินสำหรับ ${unpaids.length} รายการสำเร็จ!`, 'success');
          // Reload background arrears & vouchers
          loadArrearsData();
          loadVouchersData();
        } catch (e) {
          console.error(e);
          showToast('เกิดข้อผิดพลาดในการตัดรับชำระเงิน', 'error');
          await reloadAllFinanceData();
        }
      });

      // Receipt customer controls
      document.getElementById('rcpt-cust-select')?.addEventListener('change', (e) => {
        selectedCustIdx = parseInt(e.target.value, 10);
        renderSingleReceipt();
      });
      document.getElementById('btn-rcpt-prev')?.addEventListener('click', () => {
        if (selectedCustIdx > 0) { selectedCustIdx--; renderSingleReceipt(); }
      });
      document.getElementById('btn-rcpt-next')?.addEventListener('click', () => {
        if (selectedCustIdx < customersList.length - 1) { selectedCustIdx++; renderSingleReceipt(); }
      });

      // Initial tab detection from hash
      handleUrlHash();

      // Load initial data
      await reloadAllFinanceData();
    });

    // Hash Handler & Tab Switcher
    function handleUrlHash() {
      let hash = window.location.hash.replace('#', '').trim();
      if (!hash) hash = 'pane-collection';
      if (!hash.startsWith('pane-')) hash = 'pane-' + hash;
      switchFinanceTab(hash, false);
    }

    function switchFinanceTab(targetId, updateHash = false) {
      if (!targetId.startsWith('pane-')) targetId = 'pane-' + targetId;

      // Active state on Tab Buttons
      let activeBtn = null;
      document.querySelectorAll('.fin-tab-btn').forEach(btn => {
        if (btn.dataset.target === targetId) {
          btn.classList.add('active');
          activeBtn = btn;
        } else {
          btn.classList.remove('active');
        }
      });

      // Active state on Panes
      let activePane = null;
      document.querySelectorAll('.fin-pane').forEach(p => {
        if (p.id === targetId) {
          p.classList.add('active');
          activePane = p;
        } else {
          p.classList.remove('active');
        }
      });

      // Fallback if targetId doesn't exist
      if (!activePane) {
        document.getElementById('pane-collection')?.classList.add('active');
        document.querySelector('.fin-tab-btn[data-target="pane-collection"]')?.classList.add('active');
        targetId = 'pane-collection';
      }

      // Sync Sidebar Active Highlight
      document.querySelectorAll('.sidebar .nav-item').forEach(link => {
        const href = link.getAttribute('href') || '';
        let shouldHighlight = false;

        if (targetId === 'pane-arrears' && href.includes('#pane-arrears')) {
          shouldHighlight = true;
        } else if (targetId === 'pane-vouchers' && href.includes('#pane-vouchers')) {
          shouldHighlight = true;
        } else if ((targetId === 'pane-collection' || targetId === 'pane-receipt') && 
                   (href === 'finance_billing.php' || href.endsWith('/finance_billing.php'))) {
          shouldHighlight = true;
        }

        if (href.includes('finance_billing.php')) {
          link.classList.toggle('active', shouldHighlight);
        }
      });

      // Update URL hash smoothly without scroll jump
      if (updateHash) {
        if (history.replaceState) {
          history.replaceState(null, '', '#' + targetId);
        } else {
          window.location.hash = targetId;
        }
      }
    }

    // Filter handlers
    function setColStatusFilter(status) {
      colStatusFilter = status;
      document.querySelectorAll('.fin-pill-btn[data-col-status]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.colStatus === status);
      });
      renderCollectionRows();
    }

    function setArrearsFilter(filter) {
      arrFilter = filter;
      document.querySelectorAll('.fin-pill-btn[data-arr-filter]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.arrFilter === filter);
      });
      renderArrearsRows();
    }

    // Data Reloading
    async function reloadAllFinanceData() {
      await Promise.all([
        loadCollectionData(),
        loadReceiptCustomers(),
        loadArrearsData(),
        loadVouchersData()
      ]);
    }

    async function loadCollectionData() {
      try {
        const res = await fetch(`${API_BASE}/readings.php?cycle=${encodeURIComponent(currentCycle)}`);
        if (!res.ok) return;
        const data = await res.json();
        readingsList = data.readings || [];
        renderCollectionRows();
      } catch (err) {
        console.error('Error loading collection:', err);
      }
    }

    function renderCollectionRows() {
      const tbody = document.getElementById('collection-table-body');
      if (!tbody) return;

      const zoneVal = document.getElementById('col-zone-select')?.value.trim() || '';
      const searchVal = document.getElementById('col-search-input')?.value.trim().toLowerCase() || '';

      let billedTotal = 0;
      let arrearsTotal = 0;
      let paidTotal = 0;
      let paidCount = 0;
      let unpaidTotal = 0;
      let unpaidCount = 0;

      // Filtered rows
      const filtered = readingsList.filter(r => {
        const grand = parseFloat(r.grand_total || 0);
        const arrears = parseFloat(r.previous_arrears || 0);
        const isPaid = (r.payment_status === 'PAID');

        // Global tallies
        billedTotal += grand;
        arrearsTotal += arrears;
        if (isPaid) {
          paidTotal += grand;
          paidCount++;
        } else {
          unpaidTotal += grand;
          unpaidCount++;
        }

        // Apply Status Filter
        if (colStatusFilter === 'paid' && !isPaid) return false;
        if (colStatusFilter === 'unpaid' && isPaid) return false;

        // Apply Zone Filter
        if (zoneVal && (r.zone || '').trim() !== zoneVal) return false;

        // Apply Search Filter
        if (searchVal) {
          const matchCode = (r.customer_code || '').toLowerCase().includes(searchVal);
          const matchName = `${r.first_name || ''} ${r.last_name || ''}`.toLowerCase().includes(searchVal);
          const matchHouse = (r.house_no || '').toLowerCase().includes(searchVal);
          const matchPhone = (r.phone || '').includes(searchVal);
          const matchReceipt = (r.receipt_no || '').toLowerCase().includes(searchVal);
          if (!matchCode && !matchName && !matchHouse && !matchPhone && !matchReceipt) return false;
        }

        return true;
      });

      // Update Summary Cards
      const elBilled = document.getElementById('col-stat-billed');
      if (elBilled) elBilled.textContent = `${billedTotal.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;
      const elArrears = document.getElementById('col-stat-arrears');
      if (elArrears) elArrears.textContent = `${arrearsTotal.toLocaleString('th-TH', { minimumFractionDigits: 2 })}`;
      const elPaid = document.getElementById('col-stat-paid');
      if (elPaid) elPaid.textContent = `${paidTotal.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;
      const elPaidCount = document.getElementById('col-stat-paid-count');
      if (elPaidCount) elPaidCount.textContent = paidCount;
      const elUnpaid = document.getElementById('col-stat-unpaid');
      if (elUnpaid) elUnpaid.textContent = `${unpaidTotal.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;
      const elUnpaidCount = document.getElementById('col-stat-unpaid-count');
      if (elUnpaidCount) elUnpaidCount.textContent = unpaidCount;
      const elFee = document.getElementById('col-stat-fee10');
      if (elFee) elFee.textContent = `${(paidTotal * 0.10).toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;

      // Update Pills Count
      const pAll = document.getElementById('col-pill-all');
      if (pAll) pAll.textContent = readingsList.length;
      const pUnpaid = document.getElementById('col-pill-unpaid');
      if (pUnpaid) pUnpaid.textContent = unpaidCount;
      const pPaid = document.getElementById('col-pill-paid');
      if (pPaid) pPaid.textContent = paidCount;

      if (filtered.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="11" class="text-center text-muted" style="padding: 32px 16px;">
              <div style="font-size: 28px; margin-bottom: 8px;">🔍</div>
              <div style="font-size: 15px; font-weight: 600; color: #475569;">ไม่พบรายการที่ตรงกับเงื่อนไขการค้นหา</div>
              <div style="font-size: 13px; color: #94a3b8; margin-top: 4px;">ลองเปลี่ยนคำค้นหา หรือเลือกตัวกรองสถานะอื่น</div>
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = filtered.map((r, i) => {
        const grand = parseFloat(r.grand_total || 0);
        const isPaid = (r.payment_status === 'PAID');

        return `
          <tr>
            <td class="text-center">${i + 1}</td>
            <td><strong style="color: #0284c7;">${r.customer_code}</strong></td>
            <td><strong>${r.first_name} ${r.last_name}</strong></td>
            <td>${r.house_no}</td>
            <td><span class="badge" style="background: #f1f5f9; color: #475569;">${r.zone}</span></td>
            <td>
              ${r.phone ? `<a href="tel:${r.phone}" style="color: #2563eb; text-decoration: none; font-size: 13px;">📞 ${r.phone}</a>` : '<span class="text-muted">-</span>'}
            </td>
            <td class="text-right font-bold">${parseFloat(r.units_used || 0).toFixed(1)}</td>
            <td class="text-right font-bold ${isPaid ? 'text-success' : 'text-danger'}" style="font-size: 14.5px;">
              ${grand.toFixed(2)} ฿
            </td>
            <td class="text-center">
              <span class="badge ${isPaid ? 'badge-paid' : 'badge-unpaid'}" style="font-size: 12.5px;">
                ${isPaid ? '✓ ชำระแล้ว' : 'ค้างชำระ'}
              </span>
            </td>
            <td class="text-center font-bold" style="font-size: 13px; color: #4338ca;">
              ${r.receipt_no || '<span class="text-muted" style="font-weight: normal;">-</span>'}
            </td>
            <td class="text-center no-print">
              <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                <button type="button" class="btn btn-sm ${isPaid ? 'btn-outline' : 'btn-secondary'} btn-toggle-pay" data-id="${r.customer_id}" onclick="togglePayment(${r.customer_id}, this)">
                  ${isPaid ? 'ยกเลิกจ่าย' : '✅ รับชำระ'}
                </button>
                <button type="button" class="btn btn-sm btn-outline" style="padding: 4px 8px; font-size: 12px;" onclick="viewCustomerReceipt(${r.customer_id})" title="เปิดดูใบเสร็จรับเงิน">
                  🧾
                </button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    // Toggle Payment with Optimistic UI & Toast Notification
    async function togglePayment(custId, btn) {
      if (btn) btn.disabled = true;

      // Find reading in local memory
      const item = readingsList.find(r => Number(r.customer_id) === Number(custId));
      if (!item) return;

      const previousStatus = item.payment_status;
      const willBePaid = (previousStatus !== 'PAID');

      // Optimistic update
      item.payment_status = willBePaid ? 'PAID' : 'UNPAID';
      if (willBePaid && !item.receipt_no) {
        item.receipt_no = 'RC-' + currentCycle + '-' + String(custId).padStart(3, '0');
      }
      renderCollectionRows();

      showToast(willBePaid ? `✅ รับชำระเงินสำหรับ ${item.first_name} เรียบร้อยแล้ว` : `ℹ️ ยกเลิกสถานะชำระเงินของ ${item.first_name}`, willBePaid ? 'success' : 'info');

      try {
        const res = await fetch(`${API_BASE}/readings.php?cycle=${encodeURIComponent(currentCycle)}&action=toggle-paid&customerId=${custId}`, { method: 'POST' });
        if (!res.ok) throw new Error('Failed to update status');

        // Refresh debtors & vouchers silently in background
        loadArrearsData();
        loadVouchersData();
      } catch (e) {
        console.error('Toggle pay error:', e);
        // Rollback
        item.payment_status = previousStatus;
        renderCollectionRows();
        showToast('❌ ไม่สามารถบันทึกสถานะได้ กรุณาลองใหม่อีกครั้ง', 'error');
      } finally {
        if (btn) btn.disabled = false;
      }
    }

    // Shortcut to view customer receipt in Pane 2
    function viewCustomerReceipt(custId) {
      const idx = customersList.findIndex(c => Number(c.id) === Number(custId));
      if (idx !== -1) {
        selectedCustIdx = idx;
        const sel = document.getElementById('rcpt-cust-select');
        if (sel) sel.value = idx;
        renderSingleReceipt();
        switchFinanceTab('pane-receipt', true);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      } else {
        switchFinanceTab('pane-receipt', true);
      }
    }

    async function loadReceiptCustomers() {
      try {
        const res = await fetch(`${API_BASE}/customers.php`);
        if (res.ok) {
          customersList = await res.json();
          const sel = document.getElementById('rcpt-cust-select');
          if (sel) {
            sel.innerHTML = customersList.map((c, i) => `
              <option value="${i}">WY-${String(c.seq_no).padStart(3, '0')} - ${c.first_name} ${c.last_name} (${c.house_no})</option>
            `).join('');
          }
          renderSingleReceipt();
        }
      } catch (err) {
        console.error('Error loading customers:', err);
      }
    }

    async function renderSingleReceipt() {
      const cust = customersList[selectedCustIdx];
      if (!cust) return;
      const sel = document.getElementById('rcpt-cust-select');
      if (sel) sel.value = selectedCustIdx;

      try {
        const res = await fetch(`${API_BASE}/receipts.php?cycle=${encodeURIComponent(currentCycle)}&customerId=${cust.id}`);
        if (res.ok) {
          const d = await res.json();
          document.getElementById('r-no').textContent = d.receiptNo;
          document.getElementById('r-date').textContent = d.issueDateText;
          document.getElementById('r-cycle').textContent = d.billingCycleText;
          document.getElementById('r-name').textContent = d.customer.name;
          document.getElementById('r-code').textContent = d.customer.code;
          document.getElementById('r-house').textContent = d.customer.houseNo;
          document.getElementById('r-serial').textContent = d.customer.meterSerial;
          document.getElementById('r-phone').textContent = d.customer.phone || '-';

          document.getElementById('r-prev').textContent = d.meter.previous.toFixed(2);
          document.getElementById('r-curr').textContent = d.meter.current.toFixed(2);
          document.getElementById('r-units').textContent = d.meter.unitsUsed.toFixed(2);
          document.getElementById('r-rate').textContent = d.meter.ratePerUnit.toFixed(2);
          document.getElementById('r-water-charge').textContent = d.breakdown.waterCharge.toFixed(2);
          document.getElementById('r-fee').textContent = d.breakdown.maintenanceFee.toFixed(2);
          document.getElementById('r-arrears').textContent = d.breakdown.previousArrears.toFixed(2);
          document.getElementById('r-grand').textContent = d.breakdown.grandTotal.toFixed(2);
          document.getElementById('r-text-th').textContent = d.totalAmountTextTh;
          document.getElementById('r-sig-payer').textContent = d.customer.name;

          // QR Code with safety fallback
          const qrAmt = d.breakdown.grandTotal.toFixed(2);
          const qrImg = document.getElementById('r-qr-img');
          if (qrImg) {
            qrImg.onerror = function() {
              this.src = "data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160' viewBox='0 0 160 160'%3E%3Crect width='160' height='160' fill='%23f1f5f9' rx='8'/%3E%3Ctext x='80' y='75' font-family='sans-serif' font-size='12' fill='%2364748b' text-anchor='middle'%3Eพร้อมเพย์ กองทุน%3C/text%3E%3Ctext x='80' y='95' font-family='sans-serif' font-size='14' font-weight='bold' fill='%230f172a' text-anchor='middle'%3E081-234-5678%3C/text%3E%3C/svg%3E";
            };
            qrImg.src = `https://promptpay.io/0812345678/${qrAmt}.png`;
          }
          const qrAmtText = document.getElementById('r-qr-amt');
          if (qrAmtText) qrAmtText.textContent = `${qrAmt} บาท`;
        }
      } catch (err) {
        console.error('Error rendering receipt:', err);
      }
    }

    async function loadArrearsData() {
      try {
        const res = await fetch(`${API_BASE}/arrears.php?cycle=${encodeURIComponent(currentCycle)}`);
        if (!res.ok) return;
        const data = await res.json();
        debtorsList = data.debtors || [];

        // Summary counts
        document.getElementById('fa-total-debtors').textContent = `${data.summary.totalDebtors} ราย`;
        document.getElementById('fa-total-debt').textContent = `${data.summary.totalDebt.toFixed(2)}`;
        document.getElementById('fa-1m-count').textContent = `${data.summary.count1M} ราย`;
        document.getElementById('fa-1m-amt').textContent = `${data.summary.amount1M.toFixed(2)}`;
        document.getElementById('fa-2m-count').textContent = `${data.summary.count2M} ราย`;
        document.getElementById('fa-2m-amt').textContent = `${data.summary.amount2M.toFixed(2)}`;
        document.getElementById('fa-3m-count').textContent = `${data.summary.count3M} ราย`;
        document.getElementById('fa-3m-amt').textContent = `${data.summary.amount3M.toFixed(2)}`;

        // Arrears pill buttons
        document.getElementById('arr-pill-all').textContent = data.summary.totalDebtors;
        document.getElementById('arr-pill-1').textContent = data.summary.count1M;
        document.getElementById('arr-pill-2').textContent = data.summary.count2M;
        document.getElementById('arr-pill-3').textContent = data.summary.count3M;

        renderArrearsRows();
      } catch (err) {
        console.error('Error loading arrears:', err);
      }
    }

    function renderArrearsRows() {
      const tbody = document.getElementById('fin-arrears-tbody');
      if (!tbody) return;

      const searchVal = document.getElementById('fa-search-input')?.value.trim().toLowerCase() || '';

      const filtered = debtorsList.filter(d => {
        // Overdue filter
        if (arrFilter === '1' && d.monthsOverdue !== 1) return false;
        if (arrFilter === '2' && d.monthsOverdue !== 2) return false;
        if (arrFilter === '3' && d.monthsOverdue < 3) return false;

        // Search filter
        if (searchVal) {
          const matchCode = (d.customerCode || '').toLowerCase().includes(searchVal);
          const matchName = (d.name || '').toLowerCase().includes(searchVal);
          const matchHouse = (d.houseNo || '').toLowerCase().includes(searchVal);
          const matchPhone = (d.phone || '').includes(searchVal);
          if (!matchCode && !matchName && !matchHouse && !matchPhone) return false;
        }

        return true;
      });

      if (filtered.length === 0) {
        tbody.innerHTML = `
          <tr>
            <td colspan="11" class="text-center text-muted" style="padding: 28px 16px;">
              <div style="font-size: 26px; margin-bottom: 6px;">🎉</div>
              <div style="font-size: 14.5px; font-weight: 600; color: #475569;">ไม่พบรายการหนี้ค้างชำระตามเงื่อนไขที่เลือก</div>
            </td>
          </tr>
        `;
        return;
      }

      tbody.innerHTML = filtered.map((d, i) => `
        <tr>
          <td class="text-center">${i + 1}</td>
          <td><strong style="color: #dc2626;">${d.customerCode}</strong></td>
          <td><strong>${d.name}</strong></td>
          <td>${d.houseNo}</td>
          <td><span class="badge" style="background: #f1f5f9; color: #475569;">${d.zone}</span></td>
          <td>
            ${d.phone ? `<a href="tel:${d.phone}" style="color: #2563eb; text-decoration: none; font-size: 13px;">📞 ${d.phone}</a>` : '-'}
          </td>
          <td>${d.meterSerial}</td>
          <td class="text-center"><strong style="color: ${d.monthsOverdue >= 3 ? '#dc2626' : '#d97706'};">${d.monthsOverdue}</strong> เดือน</td>
          <td class="text-right font-bold text-danger" style="font-size: 14px;">${d.totalDebt.toFixed(2)} ฿</td>
          <td class="text-center"><span class="${d.badgeClass}" style="font-size: 12px;">${d.urgencyText}</span></td>
          <td class="text-center no-print">
            <button type="button" class="btn btn-outline btn-sm" onclick="openFinNotice('${d.customerCode}')" style="font-size: 12.5px; padding: 4px 10px;">
              ✉️ หนังสือเตือน
            </button>
          </td>
        </tr>
      `).join('');
    }

    function openFinNotice(code) {
      const d = debtorsList.find(x => x.customerCode === code);
      if (!d) return;

      const body = document.getElementById('fin-notice-body');
      body.innerHTML = `
        <div style="border: 2px solid #0f172a; padding: 24px; border-radius: 8px; background: #fff; line-height: 1.7; font-size: 14.5px;">
          <div style="text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="font-size: 19px; font-weight: 700; margin: 0; color: #0f172a;">กองทุนระบบการประปาหมู่บ้านวังยาง</h3>
            <p style="font-size: 13px; color: #475569; margin: 3px 0 0 0;">หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</p>
            <div style="display: inline-block; background: #dc2626; color: #fff; font-size: 13px; font-weight: 700; padding: 3px 16px; border-radius: 4px; margin-top: 8px;">
              หนังสือเตือนให้ชำระหนี้ค่าน้ำประปา (ฉบับที่ ${d.monthsOverdue >= 2 ? '2' : '1'})
            </div>
          </div>
          <div><strong>เรียน:</strong> คุณ${d.name} (บ้านเลขที่ ${d.houseNo} โซน: ${d.zone} | เบอร์โทร: ${d.phone})</div>
          <p style="text-indent: 30px; margin: 10px 0;">
            จากการตรวจสอบบัญชี ปรากฏว่าท่านค้างชำระค่าน้ำประปาเป็นเวลา <strong>${d.monthsOverdue} งวด</strong> ยอดหนี้รวมทั้งสิ้น <strong>${d.totalDebt.toFixed(2)} บาท (${d.totalDebtTextTh})</strong> ขอให้ท่านนำเงินไปชำระ ณ ที่ทำการกองทุนประปาหมู่บ้าน ภายใน <strong>๗ วัน</strong>
          </p>
          <div style="background: #fee2e2; border-left: 4px solid #dc2626; padding: 8px 12px; font-size: 12.5px; color: #991b1b; margin-top: 14px;">
            ⚠️ หากพ้นกำหนด คณะกรรมการจำเป็นต้องระงับการจ่ายน้ำ (ถอดมิเตอร์) ตามระเบียบข้อบังคับ พ.ศ. 2544
          </div>
        </div>
      `;
      document.getElementById('fin-notice-modal').classList.add('show');
    }

    function printFinNotice() {
      const c = document.getElementById('fin-notice-body').innerHTML;
      const w = window.open('', '', 'width=800,height=600');
      w.document.write(`<html><head><title>พิมพ์หนังสือเตือน</title><link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;700&display=swap" rel="stylesheet"><style>body{font-family:'Sarabun',sans-serif;padding:20px;}</style></head><body>${c}<script>window.onload=function(){window.print();window.close();}<\/script></body></html>`);
      w.document.close();
    }

    async function loadVouchersData() {
      const container = document.getElementById('fin-vouchers-container');
      if (!container) return;

      try {
        const res = await fetch(`${API_BASE}/vouchers.php?cycle=${encodeURIComponent(currentCycle)}`);
        if (!res.ok) return;
        const data = await res.json();
        container.innerHTML = (data.vouchers || []).map(v => `
          <div class="voucher-document" style="margin-bottom: 24px;">
            <div class="voucher-header">
              <h3>ใบสำคัญรับเงิน / ฎีกาเบิกจ่ายเงินกองทุนประปาหมู่บ้าน</h3>
              <h4>การประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง</h4>
              <p class="text-muted">เลขที่เอกสาร: <strong>${v.voucherNo}</strong> | วันที่: ${v.date}</p>
            </div>
            <div class="voucher-body">
              <p>ข้าพเจ้า <strong>${v.recipientName}</strong> ตำแหน่ง <strong>${v.recipientPosition}</strong></p>
              <p>ได้รับเงินจากกองทุนระบบประปาหมู่บ้านวังยาง สำหรับ:</p>
              <div class="highlight-box">
                <strong>วัตถุประสงค์:</strong> ${v.calculationBasis}
              </div>
              <p>เป็นจำนวนเงินทั้งสิ้น: <strong class="text-primary font-bold" style="font-size: 18px;">${Number(v.amount).toLocaleString('th-TH', { minimumFractionDigits: 2 })} บาท</strong></p>
              <p>ตัวอักษร: <strong>( ${v.amountTextTh} )</strong></p>
            </div>
            <div class="receipt-signatures" style="margin-top: 24px;">
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
                <div class="sig-title">( นายประธาน บริหารกิจการ )</div>
                <div class="sig-role">ประธานกรรมการ / ผู้อนุมัติ</div>
              </div>
            </div>
          </div>
        `).join('');
      } catch (err) {
        console.error('Error loading vouchers:', err);
      }
    }

    function closeModal(id) {
      document.getElementById(id)?.classList.remove('show');
    }

    // Toast notification helper
    function showToast(msg, type = 'info') {
      const container = document.getElementById('toast-container');
      if (!container) return;
      const toast = document.createElement('div');
      toast.className = `toast-msg toast-${type}`;
      toast.innerHTML = `<span>${msg}</span>`;
      container.appendChild(toast);
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    }
  </script>
</body>
</html>

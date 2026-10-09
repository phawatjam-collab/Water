<?php
/**
 * 3. งานรับชำระเงิน ออกใบเสร็จ และสรุปค่าใช้จ่าย
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
  <title>งานการเงิน ออกใบเสร็จ และสรุปค่าใช้จ่าย - การประปาหมู่บ้านวังยาง</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
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
      border: 1px solid #e2e8f0;
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
      border-color: #cbd5e1;
    }
    .fin-tab-btn.active {
      background: #0284c7;
      color: #fff;
      border-color: #0284c7;
      box-shadow: 0 2px 6px rgba(2, 132, 199, 0.25);
    }
    .fin-tab-badge {
      display: inline-block;
      padding: 2px 8px;
      font-size: 11px;
      font-weight: 700;
      border-radius: 9999px;
      background: #e2e8f0;
      color: #475569;
      line-height: 1.2;
      transition: all 0.15s ease;
    }
    .fin-tab-btn.active .fin-tab-badge {
      background: rgba(255, 255, 255, 0.28);
      color: #ffffff;
    }
    .btn-toast-link {
      background: #0284c7;
      color: #ffffff !important;
      border: none;
      padding: 3px 9px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      margin-left: 8px;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      text-decoration: none;
    }
    .btn-toast-link:hover {
      background: #0369a1;
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
      border: 1px solid #e2e8f0;
      max-width: 100%;
      overflow-x: auto;
      white-space: nowrap;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none;
    }
    .fin-status-pills::-webkit-scrollbar {
      display: none;
    }
    .fin-pill-btn {
      white-space: nowrap;
      flex-shrink: 0;
      padding: 6px 14px;
      border-radius: 6px;
      border: none;
      background: transparent;
      font-size: 13px;
      font-weight: 600;
      color: #64748b;
      cursor: pointer;
      transition: all 0.15s ease;
      font-family: inherit;
    }
    .fin-pill-btn:hover {
      color: #0f172a;
    }
    .fin-pill-btn.active {
      background: #0284c7;
      color: #ffffff;
      box-shadow: 0 1px 3px rgba(2, 132, 199, 0.3);
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
      <?php renderAppTopBar('งานรับชำระเงิน & ออกใบเสร็จ', 'งานการเงิน เหรัญญิก ออกใบเสร็จรับเงิน ติดตามหนี้ค้าง และค่าตอบแทนคนจด 10%'); ?>

    <!-- Unified Finance Control Header -->
    <div class="card no-print" style="margin-bottom: 20px; padding: 18px 22px; border-radius: 12px; border: 1px solid var(--border); box-shadow: 0 2px 6px rgba(0,0,0,0.04);">
      <!-- Top Level: Cycle Selector & Quick Actions -->
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; padding-bottom: 14px; border-bottom: 1px solid #e2e8f0;">
        <div style="display: flex; align-items: center; gap: 14px;">
          <div style="width: 44px; height: 44px; border-radius: 10px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 22px; flex-shrink: 0;">
            💼
          </div>
          <div>
            <h2 style="font-family: 'Prompt', sans-serif; font-size: 18px; font-weight: 700; margin: 0; color: #0f172a;">
              ระบบงานการเงินและบัญชีกองทุนประปา
            </h2>
            <div style="font-size: 13px; color: #64748b; margin-top: 2px;">
              งวดบัญชี: <strong style="color: #0284c7;"><span id="current-cycle-display"><?php echo $currentCycleCode; ?></span></strong>
              <span style="margin: 0 6px; color: #cbd5e1;">|</span>
              <span id="header-quick-stat" style="color: #475569;">กำลังโหลดสรุปยอด...</span>
            </div>
          </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
          <div style="display: flex; align-items: center; gap: 6px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 4px 10px;">
            <label for="fin-cycle-select" style="font-size: 13px; font-weight: 600; color: #475569; white-space: nowrap;">📅 งวดเดือน:</label>
            <select id="fin-cycle-select" class="form-select" style="border: none; background: transparent; font-weight: 700; font-size: 13.5px; padding: 4px 6px; cursor: pointer; color: #0f172a;">
              <?php foreach ($cycles as $c): ?>
                <option value="<?php echo htmlspecialchars($c['cycle_code']); ?>" <?php echo $c['cycle_code'] === $currentCycleCode ? 'selected' : ''; ?>>
                  <?php echo $c['month'] . '/' . $c['year_be'] . ' (' . $c['cycle_code'] . ')'; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="button" class="btn btn-outline" id="btn-export-excel" style="font-size: 13px; padding: 7px 14px; background: #fff; display: inline-flex; align-items: center; gap: 6px;">
            <span>📥</span> ส่งออก Excel
          </button>
          <button type="button" class="btn btn-outline" id="btn-reload-fin-top" onclick="reloadAllFinanceData()" style="font-size: 13px; padding: 7px 12px; background: #fff;" title="รีเฟรชข้อมูลทั้งหมด">
            <span>🔄</span>
          </button>
        </div>
      </div>

      <!-- Navigation Tabs Layer -->
      <div class="finance-subnav" style="margin-top: 14px; margin-bottom: 0; padding: 0; border: none; box-shadow: none; background: transparent; display: flex; gap: 8px; flex-wrap: wrap;">
        <button class="fin-tab-btn active" data-target="pane-collection">
          <span>✅</span> 1. ตัดรับชำระเงินประจำงวด
          <span class="fin-tab-badge" id="tab-badge-unpaid">-</span>
        </button>
        <button class="fin-tab-btn" data-target="pane-receipt">
          <span>🧾</span> 2. พิมพ์ใบเสร็จรับเงิน
        </button>
        <button class="fin-tab-btn" data-target="pane-arrears">
          <span>⚠️</span> 3. ติดตามยอดค้างชำระ & พิมพ์ใบเตือน
          <span class="fin-tab-badge" id="tab-badge-debtors">-</span>
        </button>
        <button class="fin-tab-btn" data-target="pane-vouchers">
          <span>📜</span> 4. สรุปค่าใช้จ่าย & ค่าตอบแทนคนจด (10%)
        </button>
      </div>
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
            <input type="text" id="col-search-input" oninput="renderCollectionRows()" class="form-input" placeholder="🔍 ค้นหาเบอร์โทร, ชื่อ, รหัส หรือบ้านเลขที่..." style="font-size: 13.5px; padding: 7px 14px; min-width: 240px;">
            <button type="button" class="btn btn-outline" id="btn-reload-fin" style="font-size: 13px; padding: 7px 12px; background: #fff;">🔄 รีเฟรช</button>
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
                <th class="text-center no-print" width="180">การดำเนินการ</th>
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
    <!-- PANE 2: พิมพ์ใบเสร็จรับเงิน -->
    <!-- ========================================================= -->
    <div id="pane-receipt" class="fin-pane">
      <div class="card no-print receipt-toolbar" style="margin-bottom: 20px; padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; border-left: 4px solid #0284c7; border-radius: 12px;">
        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
          <button type="button" class="btn btn-outline" onclick="switchFinanceTab('pane-collection', true)" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; background: #fff; padding: 7px 14px;">
            <span>&larr;</span> กลับไปหน้ารายการตัดรับชำระ
          </button>
          <div style="display: flex; align-items: center; gap: 8px;">
            <label for="rcpt-cust-select" style="font-weight: 600; font-size: 13.5px; white-space: nowrap;">สมาชิกผู้ใช้น้ำ:</label>
            <select id="rcpt-cust-select" class="form-select" onchange="onSelectReceiptCustomer(this.value)" style="min-width: 270px; font-weight: 600; font-size: 13.5px;"></select>
            <button type="button" class="btn btn-outline btn-sm" id="btn-rcpt-prev" style="padding: 6px 10px; background: #fff;" title="คนก่อนหน้า">&larr; ก่อนหน้า</button>
            <button type="button" class="btn btn-outline btn-sm" id="btn-rcpt-next" style="padding: 6px 10px; background: #fff;" title="คนถัดไป">ถัดไป &rarr;</button>
          </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
          <div id="rcpt-status-badge-area">
            <!-- Dynamic: Paid status badge & Quick Pay button -->
          </div>
          <button type="button" class="btn btn-primary" onclick="window.print()" style="padding: 8px 18px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
            <span>🖨️</span> สั่งพิมพ์ใบเสร็จนี้
          </button>
        </div>
      </div>

      <!-- Printable Receipt Document -->
      <div class="receipt-document" style="background: #fff; padding: 30px; border-radius: 8px; border: 1px solid #cbd5e1; max-width: 820px; margin: 0 auto; box-shadow: var(--shadow);">
        <div class="receipt-header" style="text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px;">
          <h2 style="font-family: 'Prompt', sans-serif; font-size: 20px; margin: 0; color: #0f172a;">ใบเสร็จรับเงินค่าน้ำประปาหมู่บ้าน</h2>
          <h3 style="font-size: 15px; margin: 2px 0; color: #334151;">การประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</h3>
          <span style="display: inline-block; background: #0f172a; color: #fff; font-size: 13px; font-weight: 700; padding: 3px 14px; border-radius: 4px; margin-top: 6px;">
            ใบเสร็จรับเงินค่าน้ำประปา (ต้นฉบับสำหรับผู้ใช้น้ำ)
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
    <!-- PANE 3: ทะเบียนติดตามยอดค้างชำระ & พิมพ์หนังสือเตือน -->
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
    <!-- PANE 4: สรุปค่าใช้จ่าย & ค่าตอบแทนคนจด (10%) -->
    <!-- ========================================================= -->
    <div id="pane-vouchers" class="fin-pane">
      <!-- Summary Statistics Bar -->
      <div class="stats-grid no-print" style="margin-bottom: 16px;">
        <div class="stat-card">
          <span class="stat-label">ยอดจัดเก็บจริงงวดนี้</span>
          <span class="stat-value text-primary" id="vc-stat-collected">0.00 ฿</span>
          <span class="stat-desc text-muted">ค่าตอบแทน 10%: <strong id="vc-stat-comm">0.00</strong> ฿</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">ยอดเบิกจ่ายรวมงวดนี้</span>
          <span class="stat-value text-danger" id="vc-stat-disbursed">0.00 ฿</span>
          <span class="stat-desc text-muted">จากรายการเบิกจ่ายทั้งหมด <span id="vc-stat-count">0</span> ฉบับ</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">ยอดเงินกองทุนคงเหลืองวดนี้</span>
          <span class="stat-value text-success" id="vc-stat-balance">0.00 ฿</span>
          <span class="stat-desc text-muted">รายรับลบรายจ่ายประจำงวด</span>
        </div>
        <div class="stat-card">
          <span class="stat-label">จัดการและแบบฟอร์ม</span>
          <div style="margin-top: 6px; display: flex; gap: 6px; flex-wrap: wrap;">
            <button type="button" class="btn btn-sm btn-outline" onclick="printBlankVoucher()" style="font-size: 12.5px; padding: 5px 10px; background: #fff;" title="พิมพ์แบบฟอร์มเปล่าเพื่อนำไปเขียนด้วยมือ">
              📄 แบบฟอร์มเปล่า
            </button>
            <button type="button" class="btn btn-sm btn-outline" onclick="syncCommissionVoucher()" style="font-size: 12.5px; padding: 5px 10px; background: #fff;" title="ปรับยอดค่าตอบแทน 10% ให้ตรงกับยอดจัดเก็บจริง">
              ⚡ ซิงค์ 10%
            </button>
          </div>
        </div>
      </div>

      <!-- Action & Filter Bar -->
      <div class="card no-print" style="margin-bottom: 20px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <!-- Filter Category Pills -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
          <div class="fin-status-pills">
            <button type="button" class="fin-pill-btn active" data-vc-filter="all" onclick="setVoucherFilter('all')">
              ทั้งหมด <span id="vc-pill-all" style="font-weight: 700;">0</span>
            </button>
            <button type="button" class="fin-pill-btn" data-vc-filter="COMMISSION_10" onclick="setVoucherFilter('COMMISSION_10')">
              💼 ค่าตอบแทน 10%
            </button>
            <button type="button" class="fin-pill-btn" data-vc-filter="MAINTENANCE" onclick="setVoucherFilter('MAINTENANCE')">
              🔧 ซ่อมบำรุง
            </button>
            <button type="button" class="fin-pill-btn" data-vc-filter="ELECTRICITY" onclick="setVoucherFilter('ELECTRICITY')">
              ⚡ ค่าไฟฟ้า
            </button>
            <button type="button" class="fin-pill-btn" data-vc-filter="OTHER" onclick="setVoucherFilter('OTHER')">
              📝 อื่นๆ
            </button>
          </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
          <button type="button" class="btn btn-primary" onclick="openCreateVoucherModal()" style="font-weight: 600; padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
            <span>➕</span> บันทึกรายการเบิกจ่ายใหม่
          </button>
          <button type="button" class="btn btn-outline" onclick="printBlankVoucher()" style="font-weight: 600; padding: 8px 14px; display: inline-flex; align-items: center; gap: 6px; background: #fff;">
            <span>📄</span> พิมพ์แบบฟอร์มเปล่า
          </button>
          <button type="button" class="btn btn-outline" onclick="window.print()" style="font-weight: 600; padding: 8px 14px; display: inline-flex; align-items: center; gap: 6px; background: #fff;">
            <span>🖨️</span> พิมพ์ทั้งหมด
          </button>
        </div>
      </div>

      <!-- Vouchers List Container -->
      <div id="fin-vouchers-container" class="voucher-list-container">
        <!-- Dynamically populated payment vouchers -->
      </div>
    </main>
  </div> <!-- /.app-layout -->

  <!-- Modal for Quick Receipt Preview & Printing (ใบเสร็จรับเงินมาตรฐาน) -->
  <div class="modal" id="fin-receipt-modal">
    <div class="modal-dialog" style="max-width: 820px;">
      <div class="modal-content">
        <div class="modal-header" style="background: #0284c7; color: #fff;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 20px;">🧾</span>
            <h3 style="color: #fff; margin: 0; font-size: 16px; font-weight: 700;" id="m-rcpt-title">ใบเสร็จรับเงินค่าน้ำประปา</h3>
          </div>
          <button type="button" class="modal-close" onclick="closeModal('fin-receipt-modal')" style="color: #fff;">&times;</button>
        </div>
        <div class="modal-body" id="fin-receipt-modal-body" style="padding: 22px; max-height: 75vh; overflow-y: auto;">
          <!-- Dynamically populated receipt content -->
        </div>
        <div class="form-actions" style="padding: 14px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
          <div id="m-rcpt-status-area">
            <!-- Payment status badge & toggle button -->
          </div>
          <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="btn btn-outline" onclick="closeModal('fin-receipt-modal')" style="background: #fff;">ปิดหน้าต่าง</button>
            <button type="button" class="btn btn-outline" id="btn-m-rcpt-fullpage" style="background: #fff; color: #0284c7; border-color: #0284c7; font-weight: 600;">
              🖥️ เปิดหน้าเต็มใบเสร็จ
            </button>
            <button type="button" class="btn btn-primary" id="btn-m-rcpt-print" style="font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
              <span>🖨️</span> สั่งพิมพ์ใบเสร็จนี้
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

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

  <!-- Modal for Creating New Custom Payment Voucher (แบบฟอร์มบันทึกรายการเบิกจ่ายใหม่) -->
  <div class="modal" id="fin-voucher-modal">
    <div class="modal-dialog" style="max-width: 640px;">
      <div class="modal-content">
        <div class="modal-header" style="background: #0f172a; color: #fff;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 20px;">📜</span>
            <h3 style="color: #38bdf8; margin: 0; font-size: 16px; font-weight: 700;">บันทึกรายการเบิกจ่ายเงิน / ใบสำคัญรับเงิน (รายการใหม่)</h3>
          </div>
          <button type="button" class="modal-close" onclick="closeModal('fin-voucher-modal')" style="color: #94a3b8;">&times;</button>
        </div>

        <form id="form-create-voucher" onsubmit="handleCreateVoucher(event)" style="padding: 20px 24px;">
          <!-- Quick Preset Buttons -->
          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 16px;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 6px;">⚡ เติมข้อมูลด่วนตามประเภทค่าใช้จ่ายทั่วไป:</span>
            <div style="display: flex; flex-wrap: wrap; gap: 6px;">
              <button type="button" class="btn btn-sm btn-outline" onclick="applyVoucherPreset('repair')" style="font-size: 12px; padding: 4px 8px; background: #fff;">🔧 ซ่อมท่อแตก</button>
              <button type="button" class="btn btn-sm btn-outline" onclick="applyVoucherPreset('electric')" style="font-size: 12px; padding: 4px 8px; background: #fff;">⚡ ค่าไฟปั๊มสูบน้ำ</button>
              <button type="button" class="btn btn-sm btn-outline" onclick="applyVoucherPreset('chemical')" style="font-size: 12px; padding: 4px 8px; background: #fff;">🧪 สารส้ม/คลอรีน</button>
              <button type="button" class="btn btn-sm btn-outline" onclick="applyVoucherPreset('caretaker')" style="font-size: 12px; padding: 4px 8px; background: #fff;">🛠️ ค่าดูแลระบบ</button>
              <button type="button" class="btn btn-sm btn-outline" onclick="applyVoucherPreset('commission')" style="font-size: 12px; padding: 4px 8px; background: #fff;">💼 ค่าตอบแทน 10%</button>
              <button type="button" class="btn btn-sm btn-outline" onclick="applyVoucherPreset('meeting')" style="font-size: 12px; padding: 4px 8px; background: #fff;">👥 เบี้ยประชุมกรรมการ</button>
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
            <div>
              <label class="form-label" style="font-size: 13px; font-weight: 600;">ประเภทการเบิกจ่าย: <span class="text-danger">*</span></label>
              <select id="vc-input-type" class="form-select" required style="width: 100%;">
                <option value="MAINTENANCE">🔧 ค่าซ่อมบำรุง / ท่อแตก / เปลี่ยนอุปกรณ์</option>
                <option value="ELECTRICITY">⚡ ค่ากระแสไฟฟ้าเครื่องสูบน้ำ</option>
                <option value="CHEMICALS">🧪 คลอรีน / สารส้ม / เคมีภัณฑ์</option>
                <option value="COMMISSION_10">💼 ค่าตอบแทนจัดเก็บค่าน้ำ (10%)</option>
                <option value="CARETAKER">🛠️ ค่าตอบแทนผู้ดูแลรักษาระบบประปา</option>
                <option value="COMMITTEE">👥 ค่าตอบแทน/เบี้ยประชุมกรรมการ</option>
                <option value="SUPPLIES">📦 ค่าวัสดุสิ้นเปลือง / อุปกรณ์สำนักงาน</option>
                <option value="OTHER">📝 ค่าใช้จ่ายอื่นๆ (กำหนดเอง)</option>
              </select>
            </div>

            <div>
              <label class="form-label" style="font-size: 13px; font-weight: 600;">วันที่เบิกจ่าย: <span class="text-danger">*</span></label>
              <input type="date" id="vc-input-date" class="form-input" required style="width: 100%;">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
            <div>
              <label class="form-label" style="font-size: 13px; font-weight: 600;">ชื่อผู้รับเงิน / ร้านค้า: <span class="text-danger">*</span></label>
              <input type="text" id="vc-input-recipient" class="form-input" placeholder="เช่น ร้านวังยางการช่าง, นายสมชาย ใจดี" required style="width: 100%;">
            </div>

            <div>
              <label class="form-label" style="font-size: 13px; font-weight: 600;">ตำแหน่ง / สถานะผู้รับเงิน: <span class="text-danger">*</span></label>
              <input type="text" id="vc-input-position" class="form-input" placeholder="เช่น ผู้จัดจำหน่ายวัสดุ, ช่างซ่อมประปา" required style="width: 100%;">
            </div>
          </div>

          <div style="margin-bottom: 14px;">
            <label class="form-label" style="font-size: 13px; font-weight: 600;">จำนวนเงินที่เบิกจ่าย (บาท): <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="1" id="vc-input-amount" oninput="updateLiveBahtText(this.value)" class="form-input" placeholder="0.00" required style="width: 100%; font-size: 16px; font-weight: 700; color: #0284c7;">
            <div id="live-baht-text" style="font-size: 13px; color: #059669; font-weight: 600; margin-top: 4px;">ตัวอักษร: ( ศูนย์บาทถ้วน )</div>
          </div>

          <div style="margin-bottom: 14px;">
            <label class="form-label" style="font-size: 13px; font-weight: 600;">วัตถุประสงค์ / รายละเอียดรายการเบิกจ่าย: <span class="text-danger">*</span></label>
            <textarea id="vc-input-basis" class="form-input" rows="3" placeholder="ระบุเหตุผล รายละเอียดสิ่งของหรืออุปกรณ์ที่จัดซื้อหรือซ่อมแซม..." required style="width: 100%; resize: vertical;"></textarea>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px;">
            <div>
              <label class="form-label" style="font-size: 13px; font-weight: 600;">ผู้อนุมัติการจ่ายเงิน:</label>
              <input type="text" id="vc-input-approved" class="form-input" value="ประธานคณะกรรมการประปาหมู่บ้านวังยาง" style="width: 100%;">
            </div>
            <div>
              <label class="form-label" style="font-size: 13px; font-weight: 600;">ประจำงวดเดือน:</label>
              <input type="text" id="vc-input-cycle-display" class="form-input" readonly style="background: #f1f5f9; color: #64748b; width: 100%;">
            </div>
          </div>

          <div class="form-actions text-right" style="padding-top: 14px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="closeModal('fin-voucher-modal')">ยกเลิก</button>
            <button type="submit" class="btn btn-primary" id="btn-submit-voucher" style="font-weight: 600;">💾 บันทึกรายการเบิกจ่าย</button>
          </div>
        </form>
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

      // Update Nav Tab Badge & Header Stats
      const bUnpaid = document.getElementById('tab-badge-unpaid');
      if (bUnpaid) {
        if (unpaidCount > 0) {
          bUnpaid.textContent = `${unpaidCount} รอรับชำระ`;
          bUnpaid.style.display = 'inline-block';
          bUnpaid.style.background = '#0284c7';
        } else {
          bUnpaid.textContent = '✓ ชำระครบแล้ว';
          bUnpaid.style.background = '#16a34a';
          bUnpaid.style.display = 'inline-block';
        }
      }
      const hStat = document.getElementById('header-quick-stat');
      if (hStat) {
        hStat.innerHTML = `
          <span style="display: inline-flex; align-items: center; gap: 6px;">
            <span>ชำระแล้ว: <strong style="color: #16a34a;">${paidCount}</strong></span>
            <span style="color: #cbd5e1;">|</span>
            <span>รอรับชำระ: <strong style="color: #dc2626;">${unpaidCount}</strong> ราย</span>
          </span>
        `;
      }

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
                <button type="button" class="btn btn-sm ${isPaid ? 'btn-outline' : 'btn-secondary'} btn-toggle-pay" data-id="${r.customer_id}" onclick="togglePayment(${r.customer_id}, this)" style="font-weight: 600;">
                  ${isPaid ? '↩️ ยกเลิกจ่าย' : '✅ รับชำระ'}
                </button>
                <button type="button" class="btn btn-sm btn-outline" style="padding: 5px 10px; font-size: 12.5px; display: inline-flex; align-items: center; gap: 4px; background: #fff;" onclick="openQuickReceiptModal(${r.customer_id})" title="ดูและพิมพ์ใบเสร็จ">
                  <span>🧾</span> ใบเสร็จ
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

      showToast(willBePaid ? `✅ รับชำระเงินสำหรับ ${item.first_name} เรียบร้อยแล้ว <button type="button" class="btn-toast-link" onclick="openQuickReceiptModal(${custId})">🖨️ พิมพ์ใบเสร็จ</button>` : `ℹ️ ยกเลิกสถานะชำระเงินของ ${item.first_name}`, willBePaid ? 'success' : 'info');

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

    // Quick Receipt Modal State & Helpers
    let currentModalReceiptData = null;
    let currentModalCustId = null;

    function generateReceiptHtml(d) {
      const qrAmt = parseFloat(d.breakdown?.grandTotal || 0).toFixed(2);
      const qrSrc = `https://promptpay.io/0812345678/${qrAmt}.png`;

      return `
        <div class="receipt-document" style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #cbd5e1; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
          <div class="receipt-header" style="text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px;">
            <h2 style="font-family: 'Prompt', sans-serif; font-size: 19px; margin: 0; color: #0f172a;">${d.organizationName || 'การประปาหมู่บ้านวังยาง หมู่ที่ 3'}</h2>
            <h3 style="font-size: 14.5px; margin: 3px 0; color: #334151;">ใบเสร็จรับเงินค่าน้ำประปาหมู่บ้าน</h3>
            <span style="display: inline-block; background: #0f172a; color: #fff; font-size: 12px; font-weight: 700; padding: 2px 12px; border-radius: 4px; margin-top: 4px;">
              ใบเสร็จรับเงินค่าน้ำประปา (ต้นฉบับสำหรับผู้ใช้น้ำ)
            </span>
          </div>

          <div style="display: grid; grid-template-columns: repeat(2, 1fr); font-size: 13px; margin-bottom: 14px; background: #f8fafc; padding: 12px; border: 1px solid #e2e8f0; border-radius: 6px; line-height: 1.6;">
            <div>
              <strong>เลขที่ใบเสร็จ:</strong> <span style="color: #4338ca; font-weight: 700;">${d.receiptNo || '-'}</span><br>
              <strong>วันที่ออกเอกสาร:</strong> <span>${d.issueDateText || '-'}</span><br>
              <strong>ประจำงวดเดือน:</strong> <span>${d.billingCycleText || '-'}</span>
            </div>
            <div>
              <strong>ชื่อผู้ใช้น้ำ:</strong> <span style="font-weight: 600;">${d.customer?.name || '-'}</span><br>
              <strong>รหัสผู้ใช้น้ำ:</strong> <span>${d.customer?.code || '-'}</span> | <strong>บ้านเลขที่:</strong> <span>${d.customer?.houseNo || '-'}</span><br>
              <strong>หมายเลขมิเตอร์:</strong> <span>${d.customer?.meterSerial || '-'}</span> | <strong>เบอร์โทร:</strong> <span>${d.customer?.phone || '-'}</span>
            </div>
          </div>

          <table class="table" style="margin-bottom: 14px; font-size: 13px;">
            <thead>
              <tr style="background: #f1f5f9;">
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
                <td class="text-right">${Number(d.meter?.previous || 0).toFixed(2)}</td>
                <td class="text-right">${Number(d.meter?.current || 0).toFixed(2)}</td>
                <td class="text-right">${Number(d.meter?.unitsUsed || 0).toFixed(2)}</td>
                <td class="text-right">${Number(d.meter?.ratePerUnit || 0).toFixed(2)}</td>
                <td class="text-right font-bold">${Number(d.breakdown?.waterCharge || 0).toFixed(2)}</td>
              </tr>
              <tr>
                <td colspan="5">ค่าบำรุงรักษามิเตอร์ประจำเดือน</td>
                <td class="text-right font-bold">${Number(d.breakdown?.maintenanceFee || 0).toFixed(2)}</td>
              </tr>
              <tr style="color: #b91c1c;">
                <td colspan="5">ยอดค้างชำระยกยอดมาจากเดือนก่อน</td>
                <td class="text-right font-bold">${Number(d.breakdown?.previousArrears || 0).toFixed(2)}</td>
              </tr>
              <tr class="row-grand-total" style="background: #f8fafc;">
                <td colspan="3" style="font-size: 12.5px;">
                  (ตัวอักษร): <strong>${d.totalAmountTextTh || '-'}</strong>
                </td>
                <td colspan="2" class="text-right font-bold">ยอดรวมสุทธิที่ต้องชำระ:</td>
                <td class="text-right"><strong class="grand-amount text-danger" style="font-size: 17px;">${Number(d.breakdown?.grandTotal || 0).toFixed(2)} ฿</strong></td>
              </tr>
            </tbody>
          </table>

          <!-- PromptPay Block -->
          <div style="background: #f0fdf4; border: 1px dashed #22c55e; border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <img src="${qrSrc}" onerror="this.src='data:image/svg+xml;charset=utf-8,%3Csvg xmlns=\\'http://www.w3.org/2000/svg\\' width=\\'80\\' height=\\'80\\' viewBox=\\'0 0 80 80\\'%3E%3Crect width=\\'80\\' height=\\'80\\' fill=\\'%23f1f5f9\\' rx=\\'4\\'/%3E%3Ctext x=\\'40\\' y=\\'44\\' font-family=\\'sans-serif\\' font-size=\\'10\\' fill=\\'%2364748b\\' text-anchor=\\'middle\\'%3EPromptPay%3C/text%3E%3C/svg%3E'" alt="PromptPay QR" style="width: 75px; height: 75px; background: #fff; border: 1px solid #bbf7d0; border-radius: 6px; padding: 2px;">
              <div>
                <span style="background: #15803d; color: #fff; font-size: 11.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px;">พร้อมเพย์ (PromptPay)</span>
                <div style="font-size: 13px; font-weight: 700; color: #166534; margin: 2px 0;">กองทุนประปาหมู่บ้านวังยาง (ธ.ก.ส.)</div>
                <div style="font-size: 12.5px; color: #374151;">หมายเลข: <strong>081-234-5678</strong> | ยอด: <strong style="color: #b91c1c;">${Number(d.breakdown?.grandTotal || 0).toFixed(2)} บาท</strong></div>
              </div>
            </div>
            <div class="no-print" style="text-align: right; font-size: 12px; color: #15803d; font-weight: 600;">
              ⚡ สแกนจ่ายผ่าน Mobile Banking
            </div>
          </div>

          <div class="receipt-signatures" style="display: grid; grid-template-columns: repeat(3, 1fr); text-align: center; margin-top: 24px; font-size: 12.5px;">
            <div>
              <div>...................................................</div>
              <div style="margin-top: 3px;">( ${d.customer?.name || '-'} )</div>
              <div style="color: #64748b;">ผู้ชำระเงิน</div>
            </div>
            <div>
              <div>...................................................</div>
              <div style="margin-top: 3px;">( นางจำเนียร ตรวจบัญชี )</div>
              <div style="color: #64748b;">เจ้าหน้าที่การเงิน / เหรัญญิก</div>
            </div>
            <div>
              <div>...................................................</div>
              <div style="margin-top: 3px;">( นายประธาน บริหารกิจการ )</div>
              <div style="color: #64748b;">ประธานกรรมการประปา</div>
            </div>
          </div>
        </div>
      `;
    }

    async function openQuickReceiptModal(custId) {
      currentModalCustId = custId;
      const modal = document.getElementById('fin-receipt-modal');
      const body = document.getElementById('fin-receipt-modal-body');
      const statusArea = document.getElementById('m-rcpt-status-area');
      const fullpageBtn = document.getElementById('btn-m-rcpt-fullpage');
      const printBtn = document.getElementById('btn-m-rcpt-print');

      if (!modal || !body) return;

      body.innerHTML = `
        <div style="text-align: center; padding: 40px; color: #64748b;">
          <div style="font-size: 32px; margin-bottom: 8px;">⏳</div>
          <div style="font-size: 15px; font-weight: 600;">กำลังโหลดข้อมูลใบเสร็จรับเงิน...</div>
        </div>
      `;
      if (statusArea) statusArea.innerHTML = '';
      modal.classList.add('show');

      try {
        const res = await fetch(`${API_BASE}/receipts.php?cycle=${encodeURIComponent(currentCycle)}&customerId=${custId}`);
        if (!res.ok) throw new Error('Failed to load receipt');
        const d = await res.json();
        currentModalReceiptData = d;

        const readingItem = readingsList.find(r => Number(r.customer_id) === Number(custId));
        const isPaid = (readingItem && readingItem.payment_status === 'PAID') || (d.paymentStatus === 'PAID');

        const titleEl = document.getElementById('m-rcpt-title');
        if (titleEl) {
          titleEl.textContent = `ใบเสร็จรับเงินค่าน้ำประปา - ${d.customer?.name || ''} (บ้านเลขที่ ${d.customer?.houseNo || ''})`;
        }
        body.innerHTML = generateReceiptHtml(d);

        updateModalStatusArea(custId, isPaid);

        if (fullpageBtn) {
          fullpageBtn.onclick = () => openFullPageReceipt(custId);
        }
        if (printBtn) {
          printBtn.onclick = () => printModalReceipt();
        }
      } catch (err) {
        console.error(err);
        body.innerHTML = `
          <div style="text-align: center; padding: 30px; color: #dc2626;">
            <div style="font-size: 32px; margin-bottom: 8px;">❌</div>
            <div style="font-size: 15px; font-weight: 600;">ไม่สามารถโหลดข้อมูลใบเสร็จได้</div>
            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">กรุณาลองใหม่อีกครั้ง</div>
          </div>
        `;
      }
    }

    function updateModalStatusArea(custId, isPaid) {
      const statusArea = document.getElementById('m-rcpt-status-area');
      if (!statusArea) return;

      statusArea.innerHTML = `
        <div style="display: flex; align-items: center; gap: 10px;">
          <span class="badge ${isPaid ? 'badge-paid' : 'badge-unpaid'}" style="font-size: 13px; padding: 6px 14px;">
            ${isPaid ? '✓ ชำระเงินแล้ว' : '⏳ รอรับชำระเงิน'}
          </span>
          <button type="button" class="btn btn-sm ${isPaid ? 'btn-outline' : 'btn-secondary'}" onclick="togglePaymentFromModal(${custId})" style="font-size: 13px; padding: 6px 14px; font-weight: 600; ${isPaid ? 'background: #fff; color: #dc2626; border-color: #fca5a5;' : ''}">
            ${isPaid ? '↩️ ยกเลิกจ่าย' : '✅ ตัดรับชำระเดี๋ยวนี้'}
          </button>
        </div>
      `;
    }

    async function togglePaymentFromModal(custId) {
      await togglePayment(custId);
      const readingItem = readingsList.find(r => Number(r.customer_id) === Number(custId));
      const isPaid = readingItem && readingItem.payment_status === 'PAID';
      updateModalStatusArea(custId, isPaid);
      if (currentModalReceiptData) {
        currentModalReceiptData.paymentStatus = isPaid ? 'PAID' : 'UNPAID';
      }
    }

    function printModalReceipt() {
      const content = document.getElementById('fin-receipt-modal-body')?.innerHTML || '';
      const w = window.open('', '', 'width=880,height=750');
      w.document.write(`
        <!DOCTYPE html>
        <html lang="th">
        <head>
          <meta charset="UTF-8">
          <title>พิมพ์ใบเสร็จรับเงินค่าน้ำประปา - การประปาหมู่บ้านวังยาง</title>
          <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600;700&family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
          <style>
            @page { size: A4 portrait; margin: 15mm; }
            * { box-sizing: border-box; }
            body { font-family: 'Sarabun', sans-serif; font-size: 14px; line-height: 1.6; color: #000; padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
            th, td { border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 13.5px; }
            th { background: #f8fafc; font-weight: 600; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .font-bold { font-weight: 700; }
            .no-print { display: none !important; }
            @media print {
              body { padding: 0; }
              .no-print { display: none !important; }
            }
          </style>
        </head>
        <body>
          ${content}
          <script>
            window.onload = function() { window.print(); };
          <\/script>
        </body>
        </html>
      `);
      w.document.close();
    }

    function openFullPageReceipt(custId) {
      closeModal('fin-receipt-modal');
      const idx = customersList.findIndex(c => Number(c.id) === Number(custId));
      if (idx !== -1) {
        selectedCustIdx = idx;
        const sel = document.getElementById('rcpt-cust-select');
        if (sel) sel.value = idx;
        renderSingleReceipt();
      }
      switchFinanceTab('pane-receipt', true);
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Shortcut to view customer receipt (seamless preview)
    function viewCustomerReceipt(custId) {
      openQuickReceiptModal(custId);
    }

    function onSelectReceiptCustomer(idx) {
      selectedCustIdx = parseInt(idx, 10);
      renderSingleReceipt();
    }

    async function togglePaymentFromReceipt(custId) {
      await togglePayment(custId);
      await renderSingleReceipt();
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

          // Status badge and toggle pay button in Tab 2 toolbar
          const readingItem = readingsList.find(r => Number(r.customer_id) === Number(cust.id));
          const isPaid = (readingItem && readingItem.payment_status === 'PAID') || (d.paymentStatus === 'PAID');
          const badgeArea = document.getElementById('rcpt-status-badge-area');
          if (badgeArea) {
            badgeArea.innerHTML = `
              <div style="display: flex; align-items: center; gap: 8px;">
                <span class="badge ${isPaid ? 'badge-paid' : 'badge-unpaid'}" style="font-size: 13px; padding: 6px 12px;">
                  ${isPaid ? '✓ ชำระเงินแล้ว' : '⏳ รอรับชำระเงิน'}
                </span>
                <button type="button" class="btn btn-sm ${isPaid ? 'btn-outline' : 'btn-secondary'}" onclick="togglePaymentFromReceipt(${cust.id})" style="font-size: 13px; padding: 6px 12px; font-weight: 600; ${isPaid ? 'background: #fff; color: #dc2626; border-color: #fca5a5;' : ''}">
                  ${isPaid ? '↩️ ยกเลิกจ่าย' : '✅ ตัดรับชำระเดี๋ยวนี้'}
                </button>
              </div>
            `;
          }
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

        // Nav tab debtors badge
        const bDebtors = document.getElementById('tab-badge-debtors');
        if (bDebtors) {
          if (data.summary.totalDebtors > 0) {
            bDebtors.textContent = `${data.summary.totalDebtors} ราย`;
            bDebtors.style.display = 'inline-block';
          } else {
            bDebtors.style.display = 'none';
          }
        }

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
            <div style="display: inline-flex; gap: 6px; justify-content: center; align-items: center;">
              <button type="button" class="btn btn-secondary btn-sm" onclick="payDebtorFromArrears('${d.customerCode}')" style="font-size: 12px; padding: 4px 8px; font-weight: 600;" title="ไปยังรายการเพื่อตัดรับชำระ">
                💳 ตัดชำระ
              </button>
              <button type="button" class="btn btn-outline btn-sm" onclick="openFinNotice('${d.customerCode}')" style="font-size: 12px; padding: 4px 8px; background: #fff;" title="ออกหนังสือเตือน">
                ✉️ เตือน
              </button>
            </div>
          </td>
        </tr>
      `).join('');
    }

    function payDebtorFromArrears(code) {
      switchFinanceTab('pane-collection', true);
      const searchInput = document.getElementById('col-search-input');
      if (searchInput) {
        searchInput.value = code;
        renderCollectionRows();
        searchInput.focus();
      }
      showToast(`🔍 ค้นหา ${code} ในรายการตัดรับชำระแล้ว`, 'info');
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
          <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 10px 14px; font-size: 13px; color: #991b1b; margin-top: 14px; border-radius: 4px;">
            ⚠️ <strong>ระเบียบกองทุนประปาหมู่บ้านวังยาง:</strong> ค้างชำระได้ไม่เกิน 3 เดือน (เตือนเมื่อค้างครบ 2 เดือน) หากค้างเกิน 3 เดือนและไม่ชำระตามกำหนด กองทุนจะทำการระงับการจ่ายน้ำและถอดมิเตอร์ออกจากระบบ หากต้องการขอต่อระบบเข้าใหม่ภายหลัง จะต้องชำระหนี้คงค้างทั้งหมด พร้อม <strong>ค่าแรกเข้าขอต่อระบบใหม่ 2,500 บาท</strong> ตามมติที่ประชุมกองทุน
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

    let vouchersList = [];
    let lastCollectorCommission = 0;
    let voucherCategoryFilter = 'all';

    function setVoucherFilter(filter) {
      voucherCategoryFilter = filter;
      document.querySelectorAll('.fin-pill-btn[data-vc-filter]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.vcFilter === filter);
      });
      renderVoucherCards();
    }

    async function loadVouchersData() {
      const container = document.getElementById('fin-vouchers-container');
      if (!container) return;

      try {
        const res = await fetch(`${API_BASE}/vouchers.php?cycle=${encodeURIComponent(currentCycle)}`);
        if (!res.ok) return;
        const data = await res.json();
        vouchersList = data.vouchers || [];
        lastCollectorCommission = data.collectorCommission || 0;

        // Update Statistics
        const elColl = document.getElementById('vc-stat-collected');
        if (elColl) elColl.textContent = `${Number(data.collectedRevenue || 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;
        const elComm = document.getElementById('vc-stat-comm');
        if (elComm) elComm.textContent = `${Number(data.collectorCommission || 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })}`;
        const elDisb = document.getElementById('vc-stat-disbursed');
        if (elDisb) elDisb.textContent = `${Number(data.totalDisbursed || 0).toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;
        const elCount = document.getElementById('vc-stat-count');
        if (elCount) elCount.textContent = `${vouchersList.length} ฉบับ`;
        const elBal = document.getElementById('vc-stat-balance');
        if (elBal) {
          const bal = Number(data.netBalance || 0);
          elBal.textContent = `${bal.toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿`;
          elBal.className = bal >= 0 ? 'stat-value text-success' : 'stat-value text-danger';
        }

        const elPillAll = document.getElementById('vc-pill-all');
        if (elPillAll) elPillAll.textContent = vouchersList.length;

        renderVoucherCards();
      } catch (err) {
        console.error('Error loading vouchers:', err);
      }
    }

    function renderVoucherCards() {
      const container = document.getElementById('fin-vouchers-container');
      if (!container) return;

      const filtered = vouchersList.filter(v => {
        if (voucherCategoryFilter === 'all') return true;
        if (voucherCategoryFilter === 'OTHER') {
          return !['COMMISSION_10', 'MAINTENANCE', 'ELECTRICITY'].includes(v.voucherType);
        }
        return v.voucherType === voucherCategoryFilter;
      });

      if (filtered.length === 0) {
        container.innerHTML = `
          <div class="card text-center" style="padding: 40px 20px; border: 2px dashed #cbd5e1; background: #f8fafc;">
            <div style="font-size: 36px; margin-bottom: 10px;">📜</div>
            <h4 style="font-size: 16px; color: #475569; margin: 0 0 6px 0;">ยังไม่มีรายการเบิกจ่ายในหมวดนี้</h4>
            <p style="font-size: 13.5px; color: #94a3b8; margin: 0 0 16px 0;">คุณสามารถบันทึกรายการเบิกจ่ายใหม่ หรือสั่งพิมพ์แบบฟอร์มเปล่าสำหรับเขียนด้วยมือได้</p>
            <div style="display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
              <button type="button" class="btn btn-primary" onclick="openCreateVoucherModal()">➕ บันทึกรายการเบิกจ่ายใหม่</button>
              <button type="button" class="btn btn-outline" onclick="printBlankVoucher()">📄 พิมพ์แบบฟอร์มเปล่า</button>
            </div>
          </div>
        `;
        return;
      }

      const typeConfig = {
        'COMMISSION_10': { label: '💼 ค่าตอบแทนจัดเก็บ 10%', bg: '#e0f2fe', color: '#0284c7' },
        'CARETAKER': { label: '🛠️ ดูแลระบบประปา', bg: '#fef3c7', color: '#b45309' },
        'COMMITTEE': { label: '👥 เบี้ยประชุมกรรมการ', bg: '#f3e8ff', color: '#7c3aed' },
        'MAINTENANCE': { label: '🔧 ซ่อมบำรุง / อุปกรณ์', bg: '#fee2e2', color: '#dc2626' },
        'ELECTRICITY': { label: '⚡ ค่าไฟฟ้าเครื่องสูบ', bg: '#ffedd5', color: '#ea580c' },
        'CHEMICALS': { label: '🧪 สารส้ม / คลอรีน', bg: '#ecfdf5', color: '#059669' },
        'SUPPLIES': { label: '📦 วัสดุสำนักงาน', bg: '#f1f5f9', color: '#475569' },
        'OTHER': { label: '📝 ค่าใช้จ่ายอื่นๆ', bg: '#f1f5f9', color: '#334155' }
      };

      container.innerHTML = filtered.map(v => {
        const cfg = typeConfig[v.voucherType] || typeConfig['OTHER'];
        return `
          <div class="voucher-document" id="voucher-card-${v.id}" style="margin-bottom: 24px; position: relative;">
            <!-- Card Top Bar: Badge and Action Buttons -->
            <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px dashed #cbd5e1;">
              <span style="background: ${cfg.bg}; color: ${cfg.color}; font-size: 12.5px; font-weight: 700; padding: 4px 12px; border-radius: 9999px;">
                ${cfg.label}
              </span>
              <div style="display: flex; gap: 8px;">
                <button type="button" class="btn btn-sm btn-outline" onclick="printSingleVoucher(${v.id})" style="font-size: 12.5px; padding: 4px 10px; display: inline-flex; align-items: center; gap: 4px; background: #fff;">
                  <span>🖨️</span> พิมพ์ใบนี้
                </button>
                <button type="button" class="btn btn-sm btn-outline" onclick="deleteVoucher(${v.id}, '${v.voucherNo}')" style="font-size: 12.5px; padding: 4px 10px; color: #dc2626; border-color: #fca5a5; background: #fff;" title="ลบรายการนี้">
                  <span>🗑️</span> ลบ
                </button>
              </div>
            </div>

            <div class="voucher-header">
              <h3>ใบสำคัญรับเงิน / เอกสารเบิกจ่ายเงินกองทุนประปาหมู่บ้าน</h3>
              <h4>การประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</h4>
              <p class="text-muted" style="margin-top: 6px; font-size: 13.5px;">
                เลขที่เอกสาร: <strong>${v.voucherNo}</strong> | ประจำงวดเดือน: <strong>${v.cycleCode}</strong> | วันที่: <strong>${v.date}</strong>
              </p>
            </div>

            <div class="voucher-body">
              <p>ข้าพเจ้า <strong>${v.recipientName}</strong> ตำแหน่ง / สังกัด <strong>${v.recipientPosition}</strong></p>
              <p>ได้รับเงินจาก <strong>กองทุนระบบประปาหมู่บ้านวังยาง</strong> สำหรับ:</p>
              <div class="highlight-box">
                <strong>วัตถุประสงค์ / รายการค่าใช้จ่าย:</strong> ${v.calculationBasis}
                ${v.description ? `<div style="font-size: 13px; color: #64748b; margin-top: 4px;">หมายเหตุ: ${v.description}</div>` : ''}
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
        `;
      }).join('');
    }

    function openCreateVoucherModal() {
      const modal = document.getElementById('fin-voucher-modal');
      if (!modal) return;

      const cycleDisplay = document.getElementById('vc-input-cycle-display');
      if (cycleDisplay) cycleDisplay.value = currentCycle;

      const dateInput = document.getElementById('vc-input-date');
      if (dateInput && !dateInput.value) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
      }

      modal.classList.add('show');
    }

    function applyVoucherPreset(preset) {
      const typeSelect = document.getElementById('vc-input-type');
      const recipInput = document.getElementById('vc-input-recipient');
      const posInput = document.getElementById('vc-input-position');
      const basisInput = document.getElementById('vc-input-basis');
      const amtInput = document.getElementById('vc-input-amount');

      if (preset === 'repair') {
        typeSelect.value = 'MAINTENANCE';
        recipInput.value = 'ร้านวังยางการช่าง & อุปกรณ์';
        posInput.value = 'ผู้จัดจำหน่ายวัสดุอุปกรณ์ประปา';
        basisInput.value = 'ค่าท่อ PVC ข้อต่อ กาวประสานท่อ และอุปกรณ์ซ่อมแซมจุดรั่วไหลเร่งด่วน';
        if (!amtInput.value) amtInput.value = '1450.00';
      } else if (preset === 'electric') {
        typeSelect.value = 'ELECTRICITY';
        recipInput.value = 'การไฟฟ้าส่วนภูมิภาค';
        posInput.value = 'หน่วยงานรัฐวิสาหกิจ';
        basisInput.value = 'ค่ากระแสไฟฟ้าสำหรับเครื่องสูบน้ำประปาบาดาลประจำเดือน';
        if (!amtInput.value) amtInput.value = '4200.00';
      } else if (preset === 'chemical') {
        typeSelect.value = 'CHEMICALS';
        recipInput.value = 'ร้านเคมีภัณฑ์นครพนม';
        posInput.value = 'ผู้จัดจำหน่ายเคมีภัณฑ์บำบัดน้ำ';
        basisInput.value = 'ค่าสารส้ม คลอรีนผง 65% ปรับปรุงคุณภาพน้ำประปาให้สะอาดตามมาตรฐาน';
        if (!amtInput.value) amtInput.value = '1800.00';
      } else if (preset === 'caretaker') {
        typeSelect.value = 'CARETAKER';
        recipInput.value = 'นายประสิทธิ์ ดูแลดี';
        posInput.value = 'ผู้ดูแลรักษาระบบประปาและบ่อบาดาล';
        basisInput.value = 'ค่าตอบแทนประจำเดือนในการเปิด-ปิดและดูแลความสะอาดระบบประปา';
        if (!amtInput.value) amtInput.value = '3000.00';
      } else if (preset === 'commission') {
        typeSelect.value = 'COMMISSION_10';
        recipInput.value = 'นายสมาน เก็บเงินดี';
        posInput.value = 'เจ้าหน้าที่จัดเก็บค่าน้ำประปา';
        const commAmt = lastCollectorCommission || 0;
        amtInput.value = commAmt > 0 ? commAmt.toFixed(2) : '1000.00';
        basisInput.value = `คิด 10% จากยอดจัดเก็บจริงประจำงวด (${amtInput.value} บาท)`;
      } else if (preset === 'meeting') {
        typeSelect.value = 'COMMITTEE';
        recipInput.value = 'นายประธาน บริหารกิจการ';
        posInput.value = 'ประธานกรรมการการประปาหมู่บ้านวังยาง';
        basisInput.value = 'ค่าตอบแทนและเบี้ยประชุมคณะกรรมการบริหารกิจการประปา';
        if (!amtInput.value) amtInput.value = '2500.00';
      }
      updateLiveBahtText(amtInput.value);
    }

    function updateLiveBahtText(val) {
      const el = document.getElementById('live-baht-text');
      if (!el) return;
      const num = parseFloat(val);
      if (isNaN(num) || num <= 0) {
        el.textContent = 'ตัวอักษร: ( ศูนย์บาทถ้วน )';
      } else {
        el.textContent = `ตัวอักษร: ( ${thaiBahtTextJs(num)} )`;
      }
    }

    function thaiBahtTextJs(number) {
      if (isNaN(number) || number <= 0) return 'ศูนย์บาทถ้วน';
      const numStr = Number(number).toFixed(2);
      const parts = numStr.split('.');
      const intPart = parts[0];
      const decPart = parts[1];

      const digits = ['', 'หนึ่ง', 'สอง', 'สาม', 'สี่', 'ห้า', 'หก', 'เจ็ด', 'แปด', 'เก้า'];
      const places = ['', 'สิบ', 'ร้อย', 'พัน', 'หมื่น', 'แสน', 'ล้าน'];

      function convertGroup(groupStr) {
        let result = '';
        const len = groupStr.length;
        for (let i = 0; i < len; i++) {
          const d = parseInt(groupStr[i], 10);
          const place = len - 1 - i;
          if (d !== 0) {
            if (place === 0 && d === 1 && len > 1) {
              result += 'เอ็ด';
            } else if (place === 1 && d === 1) {
              result += 'สิบ';
            } else if (place === 1 && d === 2) {
              result += 'ยี่สิบ';
            } else {
              result += digits[d] + places[place];
            }
          }
        }
        return result;
      }

      let intResult = '';
      if (parseInt(intPart, 10) === 0) {
        intResult = 'ศูนย์';
      } else {
        let remaining = intPart;
        let isMillion = false;
        while (remaining.length > 0) {
          const chunk = remaining.length > 6 ? remaining.slice(-6) : remaining;
          remaining = remaining.length > 6 ? remaining.slice(0, -6) : '';
          const groupText = convertGroup(chunk);
          if (isMillion && groupText) {
            intResult = groupText + 'ล้าน' + intResult;
          } else {
            intResult = groupText + intResult;
          }
          isMillion = true;
        }
      }

      let result = intResult + 'บาท';
      if (parseInt(decPart, 10) === 0) {
        result += 'ถ้วน';
      } else {
        let decResult = '';
        const d0 = parseInt(decPart[0], 10);
        const d1 = parseInt(decPart[1], 10);
        if (d0 === 1) decResult += 'สิบ';
        else if (d0 === 2) decResult += 'ยี่สิบ';
        else if (d0 > 2) decResult += digits[d0] + 'สิบ';

        if (d1 === 1 && d0 > 0) decResult += 'เอ็ด';
        else if (d1 > 0) decResult += digits[d1];

        result += decResult + 'สตางค์';
      }
      return result;
    }

    async function handleCreateVoucher(e) {
      e.preventDefault();
      const btn = document.getElementById('btn-submit-voucher');
      if (btn) btn.disabled = true;

      const payload = {
        cycleCode: currentCycle,
        voucherType: document.getElementById('vc-input-type').value,
        voucherDate: document.getElementById('vc-input-date').value,
        recipientName: document.getElementById('vc-input-recipient').value.trim(),
        recipientPosition: document.getElementById('vc-input-position').value.trim(),
        amount: parseFloat(document.getElementById('vc-input-amount').value || 0),
        calculationBasis: document.getElementById('vc-input-basis').value.trim(),
        approvedBy: document.getElementById('vc-input-approved').value.trim()
      };

      try {
        const res = await fetch(`${API_BASE}/vouchers.php?action=create`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
          showToast(`✅ บันทึกรายการเบิกจ่าย ${data.voucher.voucherNo} เรียบร้อยแล้ว`, 'success');
          closeModal('fin-voucher-modal');
          document.getElementById('form-create-voucher').reset();
          await loadVouchersData();
        } else {
          showToast(data.error || 'เกิดข้อผิดพลาดในการบันทึก', 'error');
        }
      } catch (err) {
        console.error(err);
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
      } finally {
        if (btn) btn.disabled = false;
      }
    }

    async function deleteVoucher(id, voucherNo) {
      if (!confirm(`ยืนยันการลบรายการเบิกจ่าย "${voucherNo}" ใช่หรือไม่?`)) return;

      try {
        const res = await fetch(`${API_BASE}/vouchers.php?action=delete&id=${id}`, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
          showToast(`🗑️ ลบรายการเบิกจ่าย ${voucherNo} เรียบร้อยแล้ว`, 'info');
          await loadVouchersData();
        } else {
          showToast(data.error || 'เกิดข้อผิดพลาดในการลบ', 'error');
        }
      } catch (err) {
        console.error(err);
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์', 'error');
      }
    }

    async function syncCommissionVoucher() {
      try {
        showToast('กำลังซิงค์ค่าตอบแทน 10%...', 'info');
        const res = await fetch(`${API_BASE}/vouchers.php?action=sync_commission`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ cycleCode: currentCycle })
        });
        const data = await res.json();
        if (data.success) {
          showToast(`✅ อัปเดตค่าตอบแทน 10% เป็น ${Number(data.commission).toLocaleString('th-TH', { minimumFractionDigits: 2 })} ฿ สำเร็จ`, 'success');
          await loadVouchersData();
        }
      } catch (err) {
        console.error(err);
        showToast('ไม่สามารถซิงค์ค่าตอบแทนได้', 'error');
      }
    }

    function printBlankVoucher() {
      const w = window.open('', '', 'width=880,height=750');
      w.document.write(`
        <!DOCTYPE html>
        <html lang="th">
        <head>
          <meta charset="UTF-8">
          <title>แบบฟอร์มเปล่า ใบสำคัญรับเงิน / เอกสารเบิกจ่ายเงินกองทุนประปาหมู่บ้านวังยาง</title>
          <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
          <style>
            @page { size: A4 portrait; margin: 15mm 20mm; }
            * { box-sizing: border-box; }
            body { font-family: 'Sarabun', sans-serif; font-size: 15px; line-height: 1.8; color: #000; padding: 20px; }
            .voucher-doc { border: 2px solid #000; padding: 28px 34px; border-radius: 4px; }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 14px; margin-bottom: 20px; }
            .header h2 { font-size: 20px; font-weight: 700; margin: 0; }
            .header h3 { font-size: 16px; font-weight: 600; margin: 4px 0 0 0; }
            .meta-line { display: flex; justify-content: space-between; margin-top: 10px; font-size: 14px; }
            .dot { border-bottom: 1px dotted #000; display: inline-block; min-width: 140px; }
            .dot-long { border-bottom: 1px dotted #000; display: inline-block; width: 100%; min-height: 24px; }
            .box { border: 1px solid #000; padding: 14px 18px; margin: 18px 0; min-height: 90px; }
            .sig-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 45px; text-align: center; }
            .sig-box { display: flex; flex-direction: column; align-items: center; }
            .sig-line { margin-bottom: 8px; }
            .note { margin-top: 24px; font-size: 13px; color: #444; border-top: 1px dashed #666; padding-top: 8px; text-align: center; }
            @media print {
              body { padding: 0; }
              .no-print { display: none; }
            }
          </style>
        </head>
        <body>
          <div class="no-print" style="margin-bottom: 16px; text-align: right;">
            <button onclick="window.print()" style="padding: 8px 18px; font-size: 15px; font-weight: bold; background: #0284c7; color: #fff; border: none; border-radius: 6px; cursor: pointer;">🖨️ สั่งพิมพ์แบบฟอร์มเปล่า</button>
          </div>

          <div class="voucher-doc">
            <div class="header">
              <h2>ใบสำคัญรับเงิน / เอกสารเบิกจ่ายเงินกองทุนประปาหมู่บ้าน</h2>
              <h3>กองทุนระบบการประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</h3>
              <div class="meta-line">
                <div>เลขที่เอกสาร: <span class="dot" style="min-width: 160px;"></span></div>
                <div>ประจำงวดเดือน: <span class="dot" style="min-width: 120px;"></span></div>
                <div>วันที่: ........ เดือน .................... พ.ศ. ............</div>
              </div>
            </div>

            <div style="margin-bottom: 12px;">
              ข้าพเจ้า <span class="dot" style="min-width: 320px;"></span> ตำแหน่ง / สังกัด <span class="dot" style="min-width: 250px;"></span>
            </div>
            <div>
              ได้รับเงินจาก <strong>กองทุนระบบการประปาหมู่บ้านวังยาง</strong> สำหรับรายการดังต่อไปนี้:
            </div>

            <div class="box">
              <strong>วัตถุประสงค์ / รายการค่าใช้จ่าย:</strong>
              <div style="margin-top: 8px; line-height: 2;">
                <div class="dot-long"></div>
                <div class="dot-long" style="margin-top: 6px;"></div>
              </div>
            </div>

            <div style="margin: 16px 0; font-size: 16px;">
              เป็นจำนวนเงินทั้งสิ้น: <span class="dot" style="min-width: 180px; text-align: right; font-weight: bold;"></span> <strong>บาท</strong>
              <span style="margin-left: 20px;">( ตัวอักษร: <span class="dot" style="min-width: 280px; text-align: center;"></span> )</span>
            </div>

            <div class="sig-grid">
              <div class="sig-box">
                <div class="sig-line">ลงชื่อ ....................................................</div>
                <div>( .................................................... )</div>
                <div style="font-size: 13.5px; color: #333;">ผู้รับเงิน</div>
                <div style="font-size: 12px; margin-top: 4px;">วันที่ ......./......./.......</div>
              </div>
              <div class="sig-box">
                <div class="sig-line">ลงชื่อ ....................................................</div>
                <div>( นางจำเนียร ตรวจบัญชี )</div>
                <div style="font-size: 13.5px; color: #333;">เหรัญญิก / ผู้จ่ายเงิน</div>
                <div style="font-size: 12px; margin-top: 4px;">วันที่ ......./......./.......</div>
              </div>
              <div class="sig-box">
                <div class="sig-line">ลงชื่อ ....................................................</div>
                <div>( นายประธาน บริหารกิจการ )</div>
                <div style="font-size: 13.5px; color: #333;">ประธานกรรมการ / ผู้อนุมัติ</div>
                <div style="font-size: 12px; margin-top: 4px;">วันที่ ......./......./.......</div>
              </div>
            </div>

            <div class="note">
              * เอกสารนี้ใช้เป็นหลักฐานการจ่ายเงินตามระเบียบกองทุนระบบประปาหมู่บ้านวังยาง พ.ศ. ๒๕๔๔ (โปรดแนบใบเสร็จรับเงินหรือใบส่งของ ถ้ามี)
            </div>
          </div>
        </body>
        </html>
      `);
      w.document.close();
    }

    function printSingleVoucher(id) {
      const v = vouchersList.find(x => Number(x.id) === Number(id));
      if (!v) return;

      const w = window.open('', '', 'width=880,height=750');
      w.document.write(`
        <!DOCTYPE html>
        <html lang="th">
        <head>
          <meta charset="UTF-8">
          <title>เอกสารเบิกจ่าย ${v.voucherNo} - การประปาหมู่บ้านวังยาง</title>
          <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
          <style>
            @page { size: A4 portrait; margin: 15mm 20mm; }
            * { box-sizing: border-box; }
            body { font-family: 'Sarabun', sans-serif; font-size: 15px; line-height: 1.8; color: #000; padding: 20px; }
            .voucher-doc { border: 2px solid #000; padding: 28px 34px; border-radius: 4px; }
            .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 14px; margin-bottom: 20px; }
            .header h2 { font-size: 20px; font-weight: 700; margin: 0; }
            .header h3 { font-size: 16px; font-weight: 600; margin: 4px 0 0 0; }
            .meta-line { display: flex; justify-content: space-between; margin-top: 10px; font-size: 14px; }
            .box { border: 1px solid #000; background: #fafafa; padding: 14px 18px; margin: 18px 0; }
            .sig-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; margin-top: 45px; text-align: center; }
            .sig-box { display: flex; flex-direction: column; align-items: center; }
            .sig-line { margin-bottom: 8px; }
            .note { margin-top: 24px; font-size: 13px; color: #444; border-top: 1px dashed #666; padding-top: 8px; text-align: center; }
            @media print {
              body { padding: 0; }
              .no-print { display: none; }
            }
          </style>
        </head>
        <body>
          <div class="no-print" style="margin-bottom: 16px; text-align: right;">
            <button onclick="window.print()" style="padding: 8px 18px; font-size: 15px; font-weight: bold; background: #0284c7; color: #fff; border: none; border-radius: 6px; cursor: pointer;">🖨️ สั่งพิมพ์ใบสำคัญรับเงินฉบับนี้</button>
          </div>

          <div class="voucher-doc">
            <div class="header">
              <h2>ใบสำคัญรับเงิน / เอกสารเบิกจ่ายเงินกองทุนประปาหมู่บ้าน</h2>
              <h3>กองทุนระบบการประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</h3>
              <div class="meta-line">
                <div>เลขที่เอกสาร: <strong>${v.voucherNo}</strong></div>
                <div>ประจำงวดเดือน: <strong>${v.cycleCode}</strong></div>
                <div>วันที่: <strong>${v.date}</strong></div>
              </div>
            </div>

            <div style="margin-bottom: 10px;">
              ข้าพเจ้า <strong>${v.recipientName}</strong> ตำแหน่ง / สังกัด <strong>${v.recipientPosition}</strong>
            </div>
            <div>
              ได้รับเงินจาก <strong>กองทุนระบบประปาหมู่บ้านวังยาง</strong> สำหรับรายการดังต่อไปนี้:
            </div>

            <div class="box">
              <strong>วัตถุประสงค์ / รายการค่าใช้จ่าย:</strong>
              <div style="margin-top: 6px; font-size: 15.5px;">${v.calculationBasis}</div>
              ${v.description ? `<div style="font-size: 13.5px; color: #555; margin-top: 4px;">หมายเหตุ: ${v.description}</div>` : ''}
            </div>

            <div style="margin: 16px 0; font-size: 16.5px;">
              เป็นจำนวนเงินทั้งสิ้น: <strong style="font-size: 19px;">${Number(v.amount).toLocaleString('th-TH', { minimumFractionDigits: 2 })}</strong> <strong>บาท</strong>
              <span style="margin-left: 14px;">( ตัวอักษร: <strong>${v.amountTextTh}</strong> )</span>
            </div>

            <div class="sig-grid">
              <div class="sig-box">
                <div class="sig-line">...................................................</div>
                <div style="font-weight: 600;">( ${v.recipientName} )</div>
                <div style="font-size: 13px; color: #555;">ผู้รับเงิน</div>
              </div>
              <div class="sig-box">
                <div class="sig-line">...................................................</div>
                <div style="font-weight: 600;">( นางจำเนียร ตรวจบัญชี )</div>
                <div style="font-size: 13px; color: #555;">เหรัญญิก / ผู้จ่ายเงิน</div>
              </div>
              <div class="sig-box">
                <div class="sig-line">...................................................</div>
                <div style="font-weight: 600;">( นายประธาน บริหารกิจการ )</div>
                <div style="font-size: 13px; color: #555;">ประธานกรรมการ / ผู้อนุมัติ</div>
              </div>
            </div>

            <div class="note">
              * เอกสารนี้ใช้เป็นหลักฐานการจ่ายเงินตามระเบียบกองทุนระบบประปาหมู่บ้านวังยาง พ.ศ. ๒๕๔๔
            </div>
          </div>
          <script>
            window.onload = function() {
              window.print();
            };
          <\/script>
        </body>
        </html>
      `);
      w.document.close();
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

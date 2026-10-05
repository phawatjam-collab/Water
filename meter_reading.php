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
  
  <!-- PWA & Mobile Meta Tags -->
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0284c7">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="ประปาวังยาง">
  <link rel="apple-touch-icon" href="assets/icon-192.png">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  
  <!-- Local HTML5 QR Code Scanner Library -->
  <script src="assets/html5-qrcode.min.js"></script>

  <style>
    .field-reading-container {
      max-width: 1300px;
      margin: 20px auto;
      padding: 0 16px;
    }
    .field-header-card {
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #fff;
      padding: 24px;
      border-radius: 12px;
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      box-shadow: 0 4px 15px rgba(2, 132, 199, 0.2);
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
      transition: all 0.2s ease;
    }
    .meter-input-cell:focus {
      border-color: #0284c7;
      background: #eff6ff;
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.2);
    }
    .abnormal-warning {
      background: #fee2e2;
      color: #b91c1c;
      font-size: 12px;
      padding: 2px 6px;
      border-radius: 4px;
      display: inline-block;
      margin-top: 4px;
      font-weight: 600;
    }
    .btn-qr-scan {
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
      color: #fff;
      font-weight: 700;
      box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
      border: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .btn-qr-scan:hover {
      background: linear-gradient(135deg, #047857 0%, #059669 100%);
      transform: translateY(-1px);
    }
    .table tbody tr:hover {
      background-color: #f8fafc;
    }
  </style>
</head>
<body>
  <div class="app-layout">
    <!-- Global Persistent Sidebar with Real URL Routes -->
    <?php renderAppSidebar('field'); ?>

    <!-- Main Content Area -->
    <main class="main-content">
      <?php renderAppTopBar('งานจดบันทึกมาตรวัดน้ำภาคสนาม (แบบ ป.17)', 'ระบบบันทึกเลขมิเตอร์ คำนวณหน่วยใช้น้ำ สแกน QR และนำเข้าไฟล์ Excel/CSV'); ?>

    <!-- Field Header with Progress -->
    <div class="field-header-card no-print">
      <div>
        <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 9999px; font-size: 13.5px; font-weight: 600;">
          🚶‍♂️ โมดูลพนักงานจดมิเตอร์ภาคสนาม (Field Reader Interface)
        </span>
        <h1 style="font-family: 'Prompt', sans-serif; font-size: 22px; margin: 8px 0 2px 0;">
          สมุดบันทึกการจดมาตรวัดน้ำประจำงวด (แบบ ป.17)
        </h1>
        <p style="opacity: 0.9; font-size: 13.5px; margin: 0 0 10px 0;">
          สแกน QR บนมิเตอร์ หรือคีย์เลขครั้งหลัง ระบบคำนวณเงินทันที พร้อมรองรับการนำเข้าไฟล์ Excel/CSV
        </p>
        <button type="button" onclick="openStaffGuideModal()" style="background: rgba(255,255,255,0.2); color: #fff; border: 1px solid rgba(255,255,255,0.4); font-size: 12.5px; font-weight: 600; padding: 5px 12px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
          📖 วิธีจดมิเตอร์ & คู่มือพนักงาน
        </button>
      </div>

      <div style="min-width: 270px; background: rgba(0,0,0,0.2); padding: 14px 18px; border-radius: 10px;">
        <div style="display: flex; justify-content: space-between; font-size: 13px; font-weight: 600;">
          <span>ความคืบหน้าการเดินจด:</span>
          <span id="field-progress-text">0 / 0 หลังคาเรือน (0%)</span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar-fill" id="field-progress-bar"></div>
        </div>
        <div style="display: flex; justify-content: space-between; font-size: 11.5px; margin-top: 6px; opacity: 0.85;">
          <span id="field-count-pending">ยังไม่จด: 0 หลัง</span>
          <span id="field-count-done">จดแล้ว: 0 หลัง</span>
        </div>
      </div>
    </div>

    <!-- Quick SOP Staff Guide Accordion/Card -->
    <div class="card no-print" style="margin-bottom: 20px; border-left: 5px solid #0284c7; background: #fff;">
      <div style="padding: 14px 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="toggleQuickGuide()">
        <div style="display: flex; align-items: center; gap: 10px;">
          <span style="font-size: 24px;">📖</span>
          <div>
            <h3 style="margin: 0; font-size: 15.5px; font-weight: 700; color: #0f172a; font-family: 'Prompt', sans-serif;">
              คู่มือแนะนำ: วิธีจดมิเตอร์น้ำภาคสนาม 4 ขั้นตอน (สำหรับเจ้าหน้าที่)
            </h3>
            <span style="font-size: 13px; color: #64748b;">
              สรุปขั้นตอนการทำงานหน้างาน วิธีอ่านหน้าปัดมิเตอร์จริง และระบบช่วยคำนวณอัตโนมัติ
            </span>
          </div>
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
          <button type="button" class="btn btn-outline" onclick="event.stopPropagation(); openStaffGuideModal();" style="font-size: 12.5px; padding: 5px 12px; border-radius: 6px; font-weight: 600; color: #0284c7; border-color: #38bdf8; background: #f0f9ff;">
            🔍 เปิดคู่มือฉบับเต็ม
          </button>
          <span id="quick-guide-arrow" style="font-size: 16px; color: #64748b; transition: transform 0.2s;">▼</span>
        </div>
      </div>

      <div id="quick-guide-content" style="display: block; padding: 0 20px 18px 20px; border-top: 1px solid #f1f5f9; margin-top: 4px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 12px; margin-top: 14px;">
          <!-- Step 1 -->
          <div class="sop-step-card">
            <span class="sop-step-num">01</span>
            <div style="font-size: 20px; margin-bottom: 4px;">🎯</div>
            <strong style="font-size: 14px; color: #0f172a; display: block; margin-bottom: 3px;">1. เลือกงวด & โซนเดินจด</strong>
            <p style="font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.4;">
              เลือกงวดเดือนปัจจุบัน และกรองคุ้ม/โซนที่รับผิดชอบ หรือคลิกแท็บ <strong>"⏳ ยังไม่จด"</strong> เพื่อเก็บตกหลังที่ค้าง
            </p>
          </div>

          <!-- Step 2 -->
          <div class="sop-step-card">
            <span class="sop-step-num">02</span>
            <div style="font-size: 20px; margin-bottom: 4px;">🔍</div>
            <strong style="font-size: 14px; color: #0f172a; display: block; margin-bottom: 3px;">2. อ่านหน้าปัดมิเตอร์จริง</strong>
            <p style="font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.4;">
              จดเฉพาะ <strong style="color: #000;">ตัวเลขสีดำ</strong> (ลูกบาศก์เมตร) ตัวเลขสีแดง/เข็มหมุนคือลิตร (จดเป็นทศนิยมหรือปัดเศษ)
            </p>
          </div>

          <!-- Step 3 -->
          <div class="sop-step-card">
            <span class="sop-step-num">03</span>
            <div style="font-size: 20px; margin-bottom: 4px;">📱</div>
            <strong style="font-size: 14px; color: #0f172a; display: block; margin-bottom: 3px;">3. บันทึกเลขหน้างาน</strong>
            <p style="font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.4;">
              สแกน QR หน้าบ้าน <strong>(เร็วสุด)</strong> หรือคีย์เลขแล้วกด <kbd style="background:#e2e8f0; padding:1px 4px; border-radius:3px;">Enter</kbd> เลื่อนแถวอัตโนมัติ
            </p>
          </div>

          <!-- Step 4 -->
          <div class="sop-step-card">
            <span class="sop-step-num">04</span>
            <div style="font-size: 20px; margin-bottom: 4px;">⚠️</div>
            <strong style="font-size: 14px; color: #0f172a; display: block; margin-bottom: 3px;">4. ตรวจสอบ & ส่งต่อ</strong>
            <p style="font-size: 12.5px; color: #64748b; margin: 0; line-height: 1.4;">
              ระบบจะเตือนหากใช้น้ำพุ่งสูง (>35 หน่วย) หรือเลขน้อยกว่าเดิม เมื่อจดครบส่งต่องานให้ฝ่ายการเงินออกบิล
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Filter and Quick Action Bar -->
    <div class="filter-card no-print" style="display: flex; flex-direction: column; gap: 12px;">
      <!-- Row 1: Filter inputs and Status Tabs -->
      <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: space-between;">
        <div class="filter-inputs" style="flex: 1; min-width: 300px;">
          <select id="field-cycle-select" class="form-select" style="font-weight: 600; min-width: 200px;">
            <?php foreach ($cycles as $c): ?>
              <option value="<?php echo htmlspecialchars($c['cycle_code']); ?>" <?php echo $c['cycle_code'] === $currentCycleCode ? 'selected' : ''; ?>>
                งวดเดือน <?php echo $c['month'] . '/' . $c['year_be'] . ' (' . $c['cycle_code'] . ')'; ?>
              </option>
            <?php endforeach; ?>
          </select>

          <select id="field-zone-select" class="form-select" style="min-width: 170px;">
            <option value="">ทุกคุ้ม / ทุกโซนสายเดินจด</option>
          </select>

          <input type="text" id="field-search-input" class="form-input" placeholder="🔍 ค้นหาบ้านเลขที่, รหัสผู้ใช้ หรือชื่อ..." style="flex: 1; min-width: 200px;">
        </div>

        <!-- Status Filter Tabs -->
        <div class="filter-status-tabs">
          <button type="button" class="filter-status-tab active" data-status="all" onclick="setStatusFilter('all')">
            ทั้งหมด <span class="tab-badge" id="tab-badge-all">0</span>
          </button>
          <button type="button" class="filter-status-tab" data-status="pending" onclick="setStatusFilter('pending')">
            ⏳ ยังไม่จด <span class="tab-badge" id="tab-badge-pending">0</span>
          </button>
          <button type="button" class="filter-status-tab" data-status="done" onclick="setStatusFilter('done')">
            ✅ จดแล้ว <span class="tab-badge" id="tab-badge-done">0</span>
          </button>
        </div>
      </div>

      <!-- Row 2: Action Buttons (Guide, QR Scanner, CSV Import/Export, Save, Print) -->
      <div style="display: flex; flex-wrap: wrap; gap: 8px; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 10px;">
        <div style="display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
          <!-- 0. Staff Guide Button -->
          <button type="button" class="btn btn-outline" onclick="openStaffGuideModal()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600; color: #0284c7; border-color: #38bdf8; background: #f0f9ff;">
            📖 วิธีจดมิเตอร์
          </button>

          <!-- 1. QR Code Camera Scanner Button -->
          <button type="button" class="btn btn-qr-scan" onclick="openQrScannerModal()">
            📷 สแกน QR มิเตอร์
          </button>

          <!-- 2. Excel/CSV Batch Import -->
          <button type="button" class="btn btn-outline" onclick="openCsvImportModal()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
            📥 นำเข้าจาก Excel/CSV
          </button>

          <!-- 3. Export CSV Template -->
          <button type="button" class="btn btn-outline" onclick="downloadCsvTemplate()" style="display: inline-flex; align-items: center; gap: 6px;">
            📤 ส่งออก Excel/CSV
          </button>

          <!-- 4. Print QR Stickers -->
          <a href="print_qr_labels.php" target="_blank" class="btn btn-outline" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            🏷️ พิมพ์สติกเกอร์ QR
          </a>
        </div>

        <div style="display: flex; gap: 8px; align-items: center;">
          <button type="button" class="btn btn-outline" id="btn-reload-field">🔄 รีเฟรช</button>
          <button type="button" class="btn btn-secondary" id="btn-save-field">💾 บันทึกทั้งหมด</button>
          <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ พิมพ์สมุดจด (ป.17)</button>
        </div>
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

    <!-- Instructions & Keyboard Shortcuts Tip -->
    <div class="no-print" style="margin-top: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #64748b; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
      <div>
        💡 <strong>คีย์ลัดสำหรับพนักงาน:</strong> กด <kbd style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-weight:700;">Enter</kbd> หรือ <kbd style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-weight:700;">↓</kbd> เพื่อข้ามไปกรอกแถวถัดไปอัตโนมัติ | กด <kbd style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-weight:700;">↑</kbd> เพื่อย้อนแถวก่อนหน้า
      </div>
      <div>
        ⚡ <strong>ระบบบันทึกอัตโนมัติ:</strong> เมื่อกรอกเลขแล้วเลื่อนแถว ระบบจะบันทึกลงฐานข้อมูลและคำนวณค่าน้ำทันที
      </div>
    </div>

    </main>
  </div> <!-- /.app-layout -->

  <!-- =========================================================
       Modal 1: QR Code Camera Scanner
       ========================================================= -->
  <div id="modal-qr-scanner" class="modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="max-width: 520px; width: 95%;">
      <div class="modal-header" style="background: #0f172a; color: #fff; padding: 16px 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="background: #059669; font-size: 20px; width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">📷</div>
          <div>
            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #34d399;">สแกน QR Code ติดมิเตอร์น้ำ</h4>
            <span style="font-size: 12.5px; color: #94a3b8;">ส่องกล้องไปที่สติกเกอร์มิเตอร์เพื่อกระโดดไปยังช่องกรอกทันที</span>
          </div>
        </div>
        <button type="button" class="modal-close" onclick="closeQrScannerModal()" style="color: #94a3b8; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
      </div>

      <div class="modal-body" style="padding: 16px 20px; text-align: center;">
        <!-- Scanner Viewfinder Box -->
        <div id="qr-scanner-viewfinder">
          <div id="qr-reader" style="width: 100%;"></div>
        </div>

        <div style="margin-top: 14px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 13px;">
          <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #475569;">
            <input type="checkbox" id="chk-continuous-scan">
            <span>สแกนต่อเนื่อง (ไม่ปิดกล้องอัตโนมัติ)</span>
          </label>

          <span id="qr-scanner-status" style="font-weight: 600; color: #0284c7;">
            กำลังเปิดกล้อง...
          </span>
        </div>
      </div>

      <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
        <button type="button" class="btn btn-outline" onclick="closeQrScannerModal()">ปิดหน้าต่างกล้อง</button>
      </div>
    </div>
  </div>

  <!-- =========================================================
       Modal 2: Excel / CSV Batch Import
       ========================================================= -->
  <div id="modal-csv-import" class="modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="max-width: 720px; width: 95%;">
      <div class="modal-header" style="background: #0f172a; color: #fff; padding: 16px 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="background: #0284c7; font-size: 20px; width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">📥</div>
          <div>
            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #38bdf8;">นำเข้าเลขมิเตอร์จาก Excel / CSV</h4>
            <span style="font-size: 12.5px; color: #94a3b8;">นำเข้าข้อมูลเลขมิเตอร์แบบชุดทั้งหมดในครั้งเดียว</span>
          </div>
        </div>
        <button type="button" class="modal-close" onclick="closeCsvImportModal()" style="color: #94a3b8; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
      </div>

      <div class="modal-body" style="padding: 20px;">
        <!-- Upload Box -->
        <div class="csv-dropzone" id="csv-dropzone" onclick="document.getElementById('csv-file-input').click()">
          <div style="font-size: 36px; margin-bottom: 8px;">📊</div>
          <h3 style="font-size: 16px; margin-bottom: 4px; color: #0284c7;">คลิกเพื่อเลือกไฟล์ CSV หรือลากไฟล์มาวางที่นี่</h3>
          <p style="font-size: 13px; color: #64748b; margin: 0;">
            รองรับไฟล์ .csv (ที่เปิดและแก้ไขได้จากโปรแกรม Microsoft Excel)
          </p>
          <input type="file" id="csv-file-input" accept=".csv" style="display: none;">
        </div>

        <div style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center; font-size: 12.5px; color: #64748b;">
          <span>* ไฟล์ต้องมีคอลัมน์รหัสผู้ใช้ (เช่น WY-001) และเลขครั้งหลัง</span>
          <a href="javascript:void(0)" onclick="downloadCsvTemplate()" style="color: #0284c7; text-decoration: none; font-weight: 600;">
            📥 ดาวน์โหลดแบบฟอร์มเปล่างวดนี้ (.csv)
          </a>
        </div>

        <!-- Preview Container -->
        <div id="csv-preview-container" style="display: none; margin-top: 16px;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <h4 style="font-size: 14px; font-weight: 700; color: #1e293b; margin: 0;">
              รายการตรวจสอบข้อมูล (<span id="csv-preview-count">0</span> รายการ)
            </h4>
            <span id="csv-preview-summary-badge" class="badge badge-paid" style="font-size: 12px;">พร้อมนำเข้า</span>
          </div>

          <div style="max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px;">
            <table class="table" style="font-size: 13px; margin: 0;">
              <thead style="position: sticky; top: 0; background: #f8fafc;">
                <tr>
                  <th>รหัสผู้ใช้</th>
                  <th>ชื่อผู้ใช้น้ำ</th>
                  <th class="text-right">เลขเดิม</th>
                  <th class="text-right">เลขใหม่ที่กรอก</th>
                  <th class="text-right">หน่วยใช้</th>
                  <th class="text-center">สถานะ</th>
                </tr>
              </thead>
              <tbody id="csv-preview-tbody"></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" class="btn btn-outline" onclick="closeCsvImportModal()">ยกเลิก</button>
        <button type="button" class="btn btn-primary" id="btn-confirm-csv-import" onclick="submitCsvImport()" disabled style="font-weight: 600; padding: 9px 20px;">
          🚀 ยืนยันบันทึกทั้งชุดลงฐานข้อมูล
        </button>
      </div>
    </div>
  </div>

  <!-- =========================================================
       Modal 3: Staff Guide & Operating Procedure (คู่มือการจดมิเตอร์)
       ========================================================= -->
  <div id="modal-staff-guide" class="modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="max-width: 840px; width: 95%; max-height: 90vh; display: flex; flex-direction: column;">
      <div class="modal-header" style="background: #0f172a; color: #fff; padding: 16px 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="background: #0284c7; font-size: 20px; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">📖</div>
          <div>
            <h4 style="margin: 0; font-size: 16.5px; font-weight: 700; color: #38bdf8;">คู่มือขั้นตอนการจดมิเตอร์น้ำภาคสนาม (แบบ ป.17)</h4>
            <span style="font-size: 12.5px; color: #94a3b8;">แนวทางการปฏิบัติงาน วิธีอ่านหน้าปัดมิเตอร์ และการรับมือกรณีพิเศษสำหรับเจ้าหน้าที่</span>
          </div>
        </div>
        <button type="button" class="modal-close" onclick="closeStaffGuideModal()" style="color: #94a3b8; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
      </div>

      <!-- Guide Tabs Navigation -->
      <div style="display: flex; gap: 4px; background: #f1f5f9; padding: 8px 16px; border-bottom: 1px solid #e2e8f0; overflow-x: auto;">
        <button type="button" class="guide-tab-btn active" id="gtab-btn-1" onclick="switchGuideTab(1)" style="padding: 8px 14px; border: none; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; background: #0284c7; color: #fff; font-family: inherit; white-space: nowrap;">
          🔍 1. วิธีอ่านหน้าปัดจริง
        </button>
        <button type="button" class="guide-tab-btn" id="gtab-btn-2" onclick="switchGuideTab(2)" style="padding: 8px 14px; border: none; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; background: transparent; color: #64748b; font-family: inherit; white-space: nowrap;">
          📱 2. 3 วิธีบันทึกหน้างาน
        </button>
        <button type="button" class="guide-tab-btn" id="gtab-btn-3" onclick="switchGuideTab(3)" style="padding: 8px 14px; border: none; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; background: transparent; color: #64748b; font-family: inherit; white-space: nowrap;">
          ⚠️ 3. กรณีมิเตอร์วนรอบ/ท่อรั่ว
        </button>
        <button type="button" class="guide-tab-btn" id="gtab-btn-4" onclick="switchGuideTab(4)" style="padding: 8px 14px; border: none; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer; background: transparent; color: #64748b; font-family: inherit; white-space: nowrap;">
          🧮 4. สูตรคำนวณค่าน้ำ
        </button>
      </div>

      <div class="modal-body" style="padding: 20px; overflow-y: auto; flex: 1;">
        <!-- TAB 1: วิธีอ่านหน้าปัดจริง -->
        <div id="guide-pane-1" class="guide-pane" style="display: block;">
          <h3 style="font-size: 16px; color: #0284c7; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
            <span>💧</span> ภาพจำลองหน้าปัดมาตรวัดน้ำจริง (Mechanical Water Meter)
          </h3>

          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 16px;">
            <div class="meter-dial-sim">
              <span style="font-size: 11px; font-weight: 700; color: #0369a1; letter-spacing: 0.5px;">💧 การประปาหมู่บ้านวังยาง</span>
              <span style="font-size: 10px; color: #64748b;">CLASS B • ISO 4064</span>
              
              <div class="meter-counter-box">
                <span class="digit-box digit-black">0</span>
                <span class="digit-box digit-black">1</span>
                <span class="digit-box digit-black">7</span>
                <span class="digit-box digit-black">5</span>
                <span class="digit-dot">.</span>
                <span class="digit-box digit-red">4</span>
                <span class="unit-label">m³</span>
              </div>

              <div style="font-size: 11px; color: #475569; margin-top: 10px; font-weight: 600;">
                ตัวอย่าง: อ่านค่าได้ <span style="color: #0284c7; font-weight: 800; font-size: 13px;">175.4</span> ลบ.ม.
              </div>
            </div>

            <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-top: 10px; font-size: 13px;">
              <div style="display: flex; align-items: center; gap: 6px;">
                <span style="display: inline-block; width: 14px; height: 14px; background: #000; border-radius: 3px;"></span>
                <strong>ตัวเลขสีดำ (Black) = ลูกบาศก์เมตร (หน่วยคิวที่นำมาคิดเงิน)</strong>
              </div>
              <div style="display: flex; align-items: center; gap: 6px;">
                <span style="display: inline-block; width: 14px; height: 14px; background: #dc2626; border-radius: 3px;"></span>
                <span style="color: #64748b;">ตัวเลขสีแดง (Red) = ทศนิยมลิตร (จดเป็นทศนิยม หรือปัดเศษ)</span>
              </div>
            </div>
          </div>

          <div style="background: #eff6ff; border-left: 4px solid #0284c7; padding: 12px 16px; border-radius: 6px; font-size: 13.5px; line-height: 1.5; color: #1e3a8a;">
            <strong>📝 คำแนะนำการกรอกลงระบบ:</strong>
            <ul style="margin: 6px 0 0 18px; padding: 0;">
              <li>ให้นำตัวเลขที่อ่านได้ มากรอกลงในช่อง <strong>"เลขครั้งหลัง (กรอก)"</strong></li>
              <li>สามารถกรอกทศนิยม 1 ตำแหน่งได้ เช่น <code>175.4</code> หรือกรอกเฉพาะเลขจำนวนเต็ม <code>175</code></li>
              <li>ระบบจะดึง <strong>เลขครั้งก่อน</strong> ของเดือนที่แล้วมาหักลบให้อัตโนมัติทันที ไม่ต้องคำนวณมือ</li>
            </ul>
          </div>
        </div>

        <!-- TAB 2: 3 วิธีบันทึกหน้างาน -->
        <div id="guide-pane-2" class="guide-pane" style="display: none;">
          <h3 style="font-size: 16px; color: #0284c7; margin-bottom: 14px;">
            📱 3 วิธีการบันทึกข้อมูลหน้างานที่สะดวกรวดเร็ว (เลือกตามความถนัด)
          </h3>

          <div style="display: flex; flex-direction: column; gap: 14px;">
            <!-- Method 1 -->
            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 16px;">
              <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                <h4 style="font-size: 15px; font-weight: 700; color: #166534; margin: 0;">
                  📷 วิธีที่ 1: กล้องสแกน QR Code หน้าบ้าน (เร็วและแม่นยำที่สุด)
                </h4>
                <span class="badge badge-paid">แนะนำสูงสุด</span>
              </div>
              <p style="font-size: 13px; color: #15803d; margin: 0 0 8px 0; line-height: 1.45;">
                เหมาะสำหรับพนักงานที่เดินจดด้วยมือถือหรือแท็บเล็ตหน้างานจริง
              </p>
              <ol style="font-size: 13px; color: #1e293b; margin: 0 0 0 18px; padding: 0; line-height: 1.5;">
                <li>กดปุ่มสีเขียว <strong>"📷 สแกน QR มิเตอร์"</strong> หรือปุ่มลอยด้านล่างจอมือถือ</li>
                <li>ส่องกล้องไปที่สติกเกอร์ QR Code ที่ติดอยู่บนมิเตอร์น้ำของบ้านนั้น</li>
                <li>เมื่อตรวจพบ จะมีเสียงบี๊บ <code>Beep!</code> และมือถือจะสั่นเตือน</li>
                <li>หน้าเว็บจะ <strong>เลื่อนไปยังแถวของลูกบ้านหลังนั้นตรงกลางจออัตโนมัติ</strong> พร้อมกะพริบสีเขียว และโฟกัสช่องกรอกเลขให้พิมพ์ได้ทันที</li>
              </ol>
            </div>

            <!-- Method 2 -->
            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 16px;">
              <h4 style="font-size: 15px; font-weight: 700; color: #0369a1; margin: 0 0 6px 0;">
                ⌨️ วิธีที่ 2: เดินคีย์ตามสายจดด้วยปุ่มลัด (Enter-to-Next-Row)
              </h4>
              <p style="font-size: 13px; color: #0284c7; margin: 0 0 8px 0; line-height: 1.45;">
                เหมาะสำหรับพนักงานที่เดินจดตามลำดับบ้านในคุ้ม หรือคีย์บนแป้นพิมพ์คอมพิวเตอร์
              </p>
              <ol style="font-size: 13px; color: #1e293b; margin: 0 0 0 18px; padding: 0; line-height: 1.5;">
                <li>รายชื่อลูกบ้านถูกเรียงตาม <strong>ลำดับสายเดินจด (Seq No)</strong> จากต้นซอยไปท้ายซอยเรียบร้อยแล้ว</li>
                <li>เมื่อพิมพ์ตัวเลขบ้านแรกเสร็จ ให้กดปุ่ม <kbd style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-weight:700;">Enter</kbd> หรือ <kbd style="background:#e2e8f0; padding:2px 6px; border-radius:4px; font-weight:700;">↓</kbd></li>
                <li>ระบบจะ <strong>บันทึกลงฐานข้อมูลและคำนวณค่าน้ำทันที</strong> พร้อมกระโดดไปยังช่องกรอกของบ้านถัดไปโดยอัตโนมัติ</li>
              </ol>
            </div>

            <!-- Method 3 -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px;">
              <h4 style="font-size: 15px; font-weight: 700; color: #334155; margin: 0 0 6px 0;">
                📊 วิธีที่ 3: จดใส่กระดาษ / นำเข้าไฟล์ Excel หรือ CSV แบบชุด (Batch Import)
              </h4>
              <p style="font-size: 13px; color: #64748b; margin: 0 0 8px 0; line-height: 1.45;">
                เหมาะสำหรับกรณีที่ชอบจดใส่กระดาษหรือสัญญาณเน็ตไม่เสถียร
              </p>
              <ol style="font-size: 13px; color: #1e293b; margin: 0 0 0 18px; padding: 0; line-height: 1.5;">
                <li>กดปุ่ม <strong>"🖨️ พิมพ์สมุดจด (ป.17)"</strong> เพื่อสั่งพิมพ์กระดาษนำไปเดินจดหน้างาน หรือกด <strong>"📤 ส่งออก Excel/CSV"</strong></li>
                <li>นำตัวเลขที่ได้มากรอกลงในโปรแกรม Microsoft Excel ในคอลัมน์เลขครั้งหลัง</li>
                <li>กดปุ่ม <strong>"📥 นำเข้าจาก Excel/CSV"</strong> ลากไฟล์มาวาง ระบบจะ Preview ตรวจสอบความถูกต้องและบันทึกอัปเดตทั้ง 20 หลังในครั้งเดียว</li>
              </ol>
            </div>
          </div>
        </div>

        <!-- TAB 3: กรณีพิเศษ & ตรวจจับท่อรั่ว -->
        <div id="guide-pane-3" class="guide-pane" style="display: none;">
          <h3 style="font-size: 16px; color: #dc2626; margin-bottom: 14px;">
            ⚠️ การรับมือกรณีพิเศษหน้างานและการตรวจจับท่อแตกรั่ว
          </h3>

          <div style="display: flex; flex-direction: column; gap: 12px;">
            <!-- Condition 1: Rollover -->
            <div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px;">
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 18px;">🔄</span>
                <strong style="color: #92400e; font-size: 14.5px;">1. กรณีมิเตอร์เดินวนรอบครบ 9999 แล้วเริ่ม 0000 ใหม่</strong>
              </div>
              <p style="font-size: 13px; color: #78350f; margin: 0; line-height: 1.45;">
                ตัวอย่าง: เลขครั้งก่อนคือ <code>9980</code> แต่เลขครั้งหลังอ่านได้ <code>0015</code><br>
                👉 <strong>ระบบคำนวณให้อัตโนมัติ:</strong> ระบบจะใช้สูตร <code>(10,000 - 9980) + 15 = 35 หน่วย</code> ให้เองทันที เจ้าหน้าที่สามารถคีย์เลข <code>15</code> ตามหน้าปัดได้ตามปกติ
              </p>
            </div>

            <!-- Condition 2: Typo Alert -->
            <div style="background: #fee2e2; border: 1px solid #fca5a5; border-radius: 8px; padding: 14px;">
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 18px;">❌</span>
                <strong style="color: #b91c1c; font-size: 14.5px;">2. กรณีเลขครั้งหลังน้อยกว่าครั้งก่อน (แจ้งเตือนคีย์ผิด)</strong>
              </div>
              <p style="font-size: 13px; color: #991b1b; margin: 0; line-height: 1.45;">
                หากพิมพ์เลขครั้งหลังน้อยกว่าครั้งก่อน เช่น เลขเดิม 150 แต่คีย์ 140 ระบบจะแสดงแถบเตือนสีส้มทันที ให้เจ้าหน้าที่ตรวจทานว่าพิมพ์สลับหลักหรือดูมิเตอร์ผิดบ้านหรือไม่
              </p>
            </div>

            <!-- Condition 3: Leak Alert -->
            <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px;">
              <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 18px;">🚨</span>
                <strong style="color: #991b1b; font-size: 14.5px;">3. การตรวจจับท่อแตกรั่วภายในบ้าน (ใช้น้ำพุ่งสูงเกิน 35 หน่วย)</strong>
              </div>
              <p style="font-size: 13px; color: #7f1d1d; margin: 0; line-height: 1.45;">
                โดยเฉลี่ยครัวเรือนในหมู่บ้านวังยางจะใช้น้ำประมาณ 10 - 20 หน่วย/เดือน หากระบบพบหน่วยใช้น้ำ <strong>มากกว่า 35 หน่วย</strong> จะขึ้นป้ายสีแดง <code>⚠️ ใช้น้ำพุ่งสูงผิดปกติ</code> แนะนำให้เจ้าหน้าที่สอบถามลูกบ้านทันทีว่ามีลูกลอยแทงก์น้ำค้าง หรือท่อฝังใต้ดินแตกรั่วหรือไม่
              </p>
            </div>
          </div>
        </div>

        <!-- TAB 4: สูตรคำนวณค่าน้ำ -->
        <div id="guide-pane-4" class="guide-pane" style="display: none;">
          <h3 style="font-size: 16px; color: #0284c7; margin-bottom: 14px;">
            🧮 สูตรคำนวณค่าน้ำประปาและการคิดเงิน (ตามระเบียบกองทุน)
          </h3>

          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin-bottom: 16px;">
            <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 10px;">
              สูตรคำนวณมาตรฐาน:
            </div>
            <div style="background: #0f172a; color: #38bdf8; padding: 12px 16px; border-radius: 6px; font-family: monospace; font-size: 14px; font-weight: 700;">
              ยอดรวมงวดนี้ = (หน่วยน้ำที่ใช้ × 7.00 บาท) + ค่าบำรุงรักษา 10.00 บาท + หนี้ค้างเก่า
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 14px; font-size: 13px;">
              <div style="background: #fff; border: 1px solid #cbd5e1; padding: 10px 14px; border-radius: 6px;">
                <strong>1. อัตราค่าน้ำประปา:</strong><br>
                <span style="color: #0284c7; font-size: 16px; font-weight: 800;">7.00 ฿</span> / หน่วย (ลบ.ม.)
              </div>
              <div style="background: #fff; border: 1px solid #cbd5e1; padding: 10px 14px; border-radius: 6px;">
                <strong>2. ค่าบำรุงรักษามาตรวัด:</strong><br>
                <span style="color: #059669; font-size: 16px; font-weight: 800;">10.00 ฿</span> / เดือน / หลัง
              </div>
              <div style="background: #fff; border: 1px solid #cbd5e1; padding: 10px 14px; border-radius: 6px;">
                <strong>3. ค้างชำระเดิม:</strong><br>
                <span style="color: #dc2626; font-size: 16px; font-weight: 800;">ทบยอดอัตโนมัติ</span>
              </div>
            </div>
          </div>

          <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px; font-size: 13px;">
            <strong>💡 ตัวอย่างการคำนวณจริง:</strong><br>
            บ้านนายสมเกียรติ เลขครั้งก่อน <code>150.0</code> เลขครั้งหลัง <code>170.0</code><br>
            - หน่วยน้ำที่ใช้ = 170.0 - 150.0 = <strong>20.0 หน่วย</strong><br>
            - ค่าน้ำดิบ = 20 หน่วย × 7 บาท = <strong>140.00 บาท</strong><br>
            - ค่าบำรุงรักษามิเตอร์ = <strong>10.00 บาท</strong><br>
            - ยอดค้างเก่า = <strong>0.00 บาท</strong><br>
            👉 <strong>ยอดเงินที่เรียกเก็บงวดนี้ = 140.00 + 10.00 = 150.00 บาท</strong>
          </div>
        </div>
      </div>

      <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end;">
        <button type="button" class="btn btn-primary" onclick="closeStaffGuideModal()" style="font-weight: 600; padding: 8px 24px;">
          เข้าใจแล้ว / ปิดหน้าต่างคู่มือ
        </button>
      </div>
    </div>
  </div>


  <!-- =========================================================
       Mobile Floating Action Bar (Sticky at Bottom)
       ========================================================= -->
  <div class="mobile-bottom-fab no-print">
    <div>
      <div style="font-size: 11px; opacity: 0.8;">ความคืบหน้า:</div>
      <strong id="fab-progress-text" style="font-size: 13px; color: #38bdf8;">0/0 (0%)</strong>
    </div>

    <button type="button" class="btn btn-qr-scan" onclick="openQrScannerModal()" style="padding: 8px 16px; border-radius: 20px; font-size: 14px;">
      📷 สแกน QR
    </button>

    <button type="button" class="btn btn-secondary" onclick="document.getElementById('btn-save-field').click()" style="padding: 8px 14px; font-size: 13px; border-radius: 8px;">
      💾 บันทึก
    </button>
  </div>

  <!-- Toast Notification Container -->
  <div class="toast-container"></div>

  <!-- =========================================================
       Client-Side JavaScript Logic
       ========================================================= -->
  <script>
    const API_BASE = 'api';
    let fieldReadings = [];
    let fieldCustomers = [];
    let activeCycle = '<?php echo $currentCycleCode; ?>';
    let currentStatusFilter = 'all'; // 'all', 'pending', 'done'
    let html5QrScanner = null;
    let pendingCsvItems = [];

    // Synthesize Beep Sound using Web Audio API
    function playBeep(freq = 880, duration = 140) {
      try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
        gain.gain.setValueAtTime(0.18, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + (duration / 1000));
        osc.connect(gain);
        gain.connect(audioCtx.destination);
        osc.start();
        osc.stop(audioCtx.currentTime + (duration / 1000));
      } catch (err) {
        // AudioContext might be blocked until user gesture, ignore safely
      }
    }

    // Modern Toast Notification
    function showToast(message, type = 'success') {
      let container = document.querySelector('.toast-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
      }
      const toast = document.createElement('div');
      toast.className = `toast-msg toast-${type}`;
      toast.innerHTML = message;
      container.appendChild(toast);
      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(50px)';
        setTimeout(() => toast.remove(), 350);
      }, 3500);
    }

    document.addEventListener('DOMContentLoaded', async () => {
      document.getElementById('print-cycle-text').textContent = activeCycle;
      await loadFieldData();

      // Cycle change handler
      document.getElementById('field-cycle-select')?.addEventListener('change', async (e) => {
        activeCycle = e.target.value;
        document.getElementById('print-cycle-text').textContent = activeCycle;
        await loadFieldData();
      });

      // Zone & Search inputs
      document.getElementById('field-zone-select')?.addEventListener('change', renderFieldRows);
      document.getElementById('field-search-input')?.addEventListener('input', renderFieldRows);
      document.getElementById('btn-reload-field')?.addEventListener('click', loadFieldData);

      // Save all handler
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
          showToast('⚠️ ยังไม่มีข้อมูลเลขมิเตอร์ที่กรอก', 'warning');
          return;
        }

        try {
          const res = await fetch(`${API_BASE}/readings.php?cycle=${encodeURIComponent(activeCycle)}&action=save`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ items })
          });
          if (res.ok) {
            playBeep(1046, 180);
            showToast(`✅ บันทึกเลขมิเตอร์สำเร็จแล้ว (${items.length} หลังคาเรือน)`, 'success');
            await loadFieldData();
          }
        } catch (err) {
          console.error(err);
          showToast('❌ เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
        }
      });

      // CSV File Drag & Drop Setup
      const dropzone = document.getElementById('csv-dropzone');
      const fileInput = document.getElementById('csv-file-input');
      
      if (dropzone && fileInput) {
        dropzone.addEventListener('dragover', (e) => { e.preventDefault(); dropzone.classList.add('dragover'); });
        dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
        dropzone.addEventListener('drop', (e) => {
          e.preventDefault();
          dropzone.classList.remove('dragover');
          if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            handleCsvFileSelect(e.dataTransfer.files[0]);
          }
        });
        fileInput.addEventListener('change', (e) => {
          if (e.target.files.length) {
            handleCsvFileSelect(e.target.files[0]);
          }
        });
      }
    });

    // Load Customers and Readings from Server
    async function loadFieldData() {
      try {
        const [custRes, readRes] = await Promise.all([
          fetch(`${API_BASE}/customers.php`),
          fetch(`${API_BASE}/readings.php?cycle=${encodeURIComponent(activeCycle)}`)
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
        showToast('❌ ไม่สามารถโหลดข้อมูลสมุดจดมิเตอร์ได้', 'error');
      }
    }

    // Populate Zone Dropdown
    function populateZoneSelect() {
      const sel = document.getElementById('field-zone-select');
      const cur = sel.value;
      const zones = [...new Set(fieldCustomers.map(c => c.zone).filter(Boolean))];
      sel.innerHTML = '<option value="">ทุกคุ้ม / ทุกโซนสายเดินจด</option>' + 
        zones.map(z => `<option value="${z}" ${z === cur ? 'selected' : ''}>${z}</option>`).join('');
    }

    // Set Status Filter (all, pending, done)
    function setStatusFilter(status) {
      currentStatusFilter = status;
      document.querySelectorAll('.filter-status-tab').forEach(t => {
        if (t.dataset.status === status) {
          t.classList.add('active');
        } else {
          t.classList.remove('active');
        }
      });
      renderFieldRows();
    }

    // Render Table Rows with Dynamic Filtering & Keyboard Navigation
    function renderFieldRows() {
      const tbody = document.getElementById('field-table-body');
      if (!tbody) return;

      const zoneVal = document.getElementById('field-zone-select').value;
      const searchVal = document.getElementById('field-search-input').value.trim().toLowerCase();

      let totalCount = fieldCustomers.length;
      let readCount = 0;

      // Filter data
      const filtered = fieldCustomers.filter(c => {
        const matchZone = !zoneVal || c.zone === zoneVal;
        const matchSearch = c.first_name.toLowerCase().includes(searchVal) ||
                            c.last_name.toLowerCase().includes(searchVal) ||
                            c.house_no.toLowerCase().includes(searchVal) ||
                            c.customer_code.toLowerCase().includes(searchVal) ||
                            (c.phone && c.phone.includes(searchVal));

        const r = fieldReadings.find(x => parseInt(x.customer_id, 10) === parseInt(c.id, 10));
        const prev = r ? parseFloat(r.previous_reading) : 0;
        const curr = r ? parseFloat(r.current_reading) : 0;
        const isRead = (curr > 0 && curr >= prev);

        if (isRead) readCount++;

        let matchStatus = true;
        if (currentStatusFilter === 'pending') matchStatus = !isRead;
        else if (currentStatusFilter === 'done') matchStatus = isRead;

        return matchZone && matchSearch && matchStatus;
      });

      // Update counters
      const pendingCount = totalCount - readCount;
      const pct = totalCount > 0 ? Math.round((readCount / totalCount) * 100) : 0;

      document.getElementById('field-progress-text').textContent = `${readCount} / ${totalCount} หลังคาเรือน (${pct}%)`;
      document.getElementById('field-progress-bar').style.width = `${pct}%`;
      document.getElementById('field-count-pending').textContent = `ยังไม่จด: ${pendingCount} หลัง`;
      document.getElementById('field-count-done').textContent = `จดแล้ว: ${readCount} หลัง`;
      document.getElementById('fab-progress-text').textContent = `${readCount}/${totalCount} (${pct}%)`;

      document.getElementById('tab-badge-all').textContent = totalCount;
      document.getElementById('tab-badge-pending').textContent = pendingCount;
      document.getElementById('tab-badge-done').textContent = readCount;

      if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="12" class="text-center" style="padding: 30px; color: #94a3b8;">ไม่พบข้อมูลลูกบ้านตามเงื่อนไขที่เลือก</td></tr>`;
        return;
      }

      tbody.innerHTML = filtered.map(c => {
        let r = fieldReadings.find(x => parseInt(x.customer_id, 10) === parseInt(c.id, 10));
        const prev = r ? parseFloat(r.previous_reading) : 0;
        const curr = r ? parseFloat(r.current_reading) : 0;
        const units = r ? parseFloat(r.units_used) : 0;
        const grandTotal = r ? parseFloat(r.grand_total) : 10;
        const isRead = (curr > 0 && curr >= prev);
        const isSpike = (units > 35); // Leak / spike warning threshold

        return `
          <tr data-customer-id="${c.id}" data-customer-code="${c.customer_code}">
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
                     data-code="${c.customer_code}"
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

      // Wire Auto-Save and Keyboard Navigation (Enter/Arrows)
      const inputs = tbody.querySelectorAll('.meter-input-cell');
      inputs.forEach((inp, idx) => {
        // Auto-select text on focus for effortless mobile typing
        inp.addEventListener('focus', function() {
          this.select();
        });

        // Keydown navigation: Enter / Down Arrow -> jump to next row, Up Arrow -> jump to prev row
        inp.addEventListener('keydown', function(e) {
          if (e.key === 'Enter' || e.key === 'ArrowDown') {
            e.preventDefault();
            if (idx + 1 < inputs.length) {
              inputs[idx + 1].focus();
            }
          } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (idx - 1 >= 0) {
              inputs[idx - 1].focus();
            }
          }
        });

        // Change event -> Calculate & Save
        inp.addEventListener('change', async (e) => {
          const custId = parseInt(e.target.dataset.id, 10);
          const prev = parseFloat(e.target.dataset.prev) || 0;
          const curr = parseFloat(e.target.value) || 0;
          const arrears = parseFloat(e.target.dataset.arrears) || 0;

          if (curr > 0 && curr < prev) {
            showToast(`⚠️ คำเตือน: เลขมิเตอร์ (${curr}) น้อยกว่าครั้งก่อน (${prev})!`, 'warning');
          }

          const units = curr >= prev ? (curr - prev) : ((10000 - prev) + curr);
          const waterCharge = units * 7.0;
          const currentTotal = waterCharge + 10.0;
          const grandTotal = currentTotal + arrears;

          const uDisp = document.getElementById(`units-disp-${custId}`);
          const aDisp = document.getElementById(`amount-disp-${custId}`);
          if (uDisp) uDisp.textContent = units.toFixed(1);
          if (aDisp) aDisp.textContent = grandTotal.toFixed(2);

          // Save to server via API
          try {
            const res = await fetch(`${API_BASE}/readings.php?cycle=${encodeURIComponent(activeCycle)}&action=save`, {
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
            if (res.ok) {
              e.target.style.borderColor = '#22c55e';
              e.target.style.background = '#f0fdf4';
              // Update local cache
              const item = fieldReadings.find(x => parseInt(x.customer_id, 10) === custId);
              if (item) {
                item.current_reading = curr;
                item.units_used = units;
                item.grand_total = grandTotal;
              } else {
                fieldReadings.push({
                  customer_id: custId,
                  previous_reading: prev,
                  current_reading: curr,
                  units_used: units,
                  grand_total: grandTotal,
                  previous_arrears: arrears
                });
              }
            }
          } catch (err) {
            console.error('Error saving reading:', err);
            e.target.style.borderColor = '#dc2626';
            showToast('❌ ไม่สามารถบันทึกข้อมูลได้', 'error');
          }
        });
      });
    }

    // =========================================================
    // QR Code Camera Scanner Logic
    // =========================================================
    function openQrScannerModal() {
      const modal = document.getElementById('modal-qr-scanner');
      if (modal) {
        modal.style.display = 'flex';
        startQrScanner();
      }
    }

    function closeQrScannerModal() {
      const modal = document.getElementById('modal-qr-scanner');
      if (modal) {
        modal.style.display = 'none';
        stopQrScanner();
      }
    }

    async function startQrScanner() {
      const statusEl = document.getElementById('qr-scanner-status');
      if (statusEl) statusEl.textContent = 'กำลังเชื่อมต่อกล้อง...';

      if (!html5QrScanner) {
        html5QrScanner = new Html5Qrcode("qr-reader");
      }

      const qrSuccessCallback = (decodedText, decodedResult) => {
        handleQrScanSuccess(decodedText);
      };

      const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0
      };

      try {
        await html5QrScanner.start(
          { facingMode: "environment" },
          config,
          qrSuccessCallback
        );
        if (statusEl) statusEl.textContent = '🟢 กล้องพร้อมทำงาน ส่องที่ QR ติดมิเตอร์';
      } catch (err) {
        console.warn('Back camera failed, trying default camera:', err);
        try {
          await html5QrScanner.start(
            { facingMode: "user" },
            config,
            qrSuccessCallback
          );
          if (statusEl) statusEl.textContent = '🟢 กล้องหน้าพร้อมทำงาน';
        } catch (err2) {
          console.error('Camera error:', err2);
          if (statusEl) statusEl.textContent = '⚠️ ไม่สามารถเปิดกล้องได้ กรุณาอนุญาตสิทธิ์การใช้กล้อง';
        }
      }
    }

    async function stopQrScanner() {
      if (html5QrScanner && html5QrScanner.isScanning) {
        try {
          await html5QrScanner.stop();
        } catch (e) {
          console.warn('Error stopping QR scanner:', e);
        }
      }
    }

    // Handler when QR code is detected
    function handleQrScanSuccess(decodedText) {
      playBeep(880, 150);
      if (navigator.vibrate) navigator.vibrate([100, 50, 100]);

      // Extract customer code from raw string or URL parameter
      let custCode = decodedText.trim();
      const codeMatch = custCode.match(/WY-\d+/i);
      if (codeMatch) {
        custCode = codeMatch[0].toUpperCase();
      }

      // Check if continuous scan is unchecked -> close modal
      const isContinuous = document.getElementById('chk-continuous-scan')?.checked;
      if (!isContinuous) {
        closeQrScannerModal();
      }

      // Find corresponding customer
      const targetCustomer = fieldCustomers.find(c => 
        c.customer_code.toUpperCase() === custCode.toUpperCase() ||
        c.house_no === custCode ||
        c.meter_serial === custCode
      );

      if (!targetCustomer) {
        showToast(`⚠️ สแกนพบ "${decodedText}" แต่ไม่พบรหัสผู้ใช้ในระบบ`, 'warning');
        return;
      }

      // Reset filters so customer row is guaranteed to be rendered
      if (document.getElementById('field-search-input').value) {
        document.getElementById('field-search-input').value = '';
      }
      if (currentStatusFilter !== 'all') {
        setStatusFilter('all');
      }
      renderFieldRows();

      // Find table row
      const targetRow = document.querySelector(`tr[data-customer-id="${targetCustomer.id}"]`);
      if (targetRow) {
        targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
        targetRow.classList.remove('qr-highlight-row');
        void targetRow.offsetWidth; // Trigger reflow
        targetRow.classList.add('qr-highlight-row');

        const inputCell = targetRow.querySelector('.meter-input-cell');
        if (inputCell) {
          setTimeout(() => {
            inputCell.focus();
            inputCell.select();
          }, 400);
        }

        showToast(`📷 สแกนพบ: <strong>${targetCustomer.customer_code}</strong> ${targetCustomer.first_name} (บ้านเลขที่ ${targetCustomer.house_no})`, 'success');
      }
    }

    // =========================================================
    // Excel / CSV Batch Import Logic
    // =========================================================
    function openCsvImportModal() {
      const modal = document.getElementById('modal-csv-import');
      if (modal) {
        modal.style.display = 'flex';
        resetCsvModal();
      }
    }

    function closeCsvImportModal() {
      const modal = document.getElementById('modal-csv-import');
      if (modal) {
        modal.style.display = 'none';
      }
    }

    function resetCsvModal() {
      document.getElementById('csv-file-input').value = '';
      document.getElementById('csv-preview-container').style.display = 'none';
      document.getElementById('btn-confirm-csv-import').disabled = true;
      pendingCsvItems = [];
    }

    function downloadCsvTemplate() {
      window.location.href = `api/export_readings_csv.php?cycle=${encodeURIComponent(activeCycle)}`;
    }

    function handleCsvFileSelect(file) {
      if (!file) return;
      const reader = new FileReader();
      reader.onload = function(e) {
        parseCsvContent(e.target.result);
      };
      reader.readAsText(file, 'UTF-8');
    }

    function parseCsvContent(text) {
      const lines = text.split(/\r\n|\n/).filter(line => line.trim() !== '');
      if (lines.length <= 1) {
        showToast('⚠️ ไฟล์ CSV ไม่มีข้อมูลหรือว่างเปล่า', 'warning');
        return;
      }

      // Check header or parse rows
      const itemsToUpdate = [];
      const previewRowsHtml = [];
      let validCount = 0;

      // Skip header if line 0 contains header names
      const startIdx = lines[0].includes('รหัส') || lines[0].includes('customer') ? 1 : 0;

      for (let i = startIdx; i < lines.length; i++) {
        // Simple CSV splitter handling quoted cells
        const row = lines[i].split(',').map(s => s.replace(/^"(.*)"$/, '$1').trim());
        if (row.length < 2) continue;

        // Try to identify customer code and new reading
        let custCode = '';
        let newReading = null;

        // Pattern matching: Find column matching WY-XXX
        for (let col = 0; col < row.length; col++) {
          if (/^WY-\d+/i.test(row[col])) {
            custCode = row[col].toUpperCase();
            // Next numeric columns
            for (let c2 = col + 1; c2 < row.length; c2++) {
              const val = parseFloat(row[c2]);
              if (!isNaN(val) && val > 0) {
                newReading = val;
              }
            }
            break;
          }
        }

        // Fallback: If standard exported template (col 1 is Code, col 9 is Reading)
        if (!custCode && /^WY-\d+/i.test(row[1])) {
          custCode = row[1].toUpperCase();
          if (row[9] && !isNaN(parseFloat(row[9]))) {
            newReading = parseFloat(row[9]);
          }
        }

        if (custCode) {
          const cust = fieldCustomers.find(c => c.customer_code.toUpperCase() === custCode);
          if (cust) {
            const existingReading = fieldReadings.find(r => parseInt(r.customer_id, 10) === parseInt(cust.id, 10));
            const prev = existingReading ? parseFloat(existingReading.previous_reading) : 0;
            const arrears = existingReading ? parseFloat(existingReading.previous_arrears) : 0;

            if (newReading !== null && newReading >= 0) {
              const units = newReading >= prev ? (newReading - prev) : ((10000 - prev) + newReading);
              const isValid = newReading >= prev;
              const isSpike = units > 35;

              itemsToUpdate.push({
                customerId: cust.id,
                customerCode: cust.customer_code,
                previousReading: prev,
                currentReading: newReading,
                previousArrears: arrears,
                units: units
              });

              validCount++;

              previewRowsHtml.push(`
                <tr>
                  <td><strong>${cust.customer_code}</strong></td>
                  <td>${cust.first_name} ${cust.last_name} (${cust.house_no})</td>
                  <td class="text-right font-bold" style="color: #64748b;">${prev.toFixed(1)}</td>
                  <td class="text-right font-bold" style="color: #0284c7;">${newReading.toFixed(1)}</td>
                  <td class="text-right font-bold">${units.toFixed(1)}</td>
                  <td class="text-center">
                    ${isValid ? '<span class="badge badge-paid">✅ ถูกต้อง</span>' : '<span class="badge badge-unpaid">⚠️ เลขน้อยกว่าเดิม</span>'}
                    ${isSpike ? '<br><span style="font-size: 11px; color: #dc2626;">พุ่งสูง</span>' : ''}
                  </td>
                </tr>
              `);
            }
          }
        }
      }

      if (itemsToUpdate.length === 0) {
        showToast('❌ ไม่พบข้อมูลรหัสผู้ใช้น้ำที่ถูกต้องในไฟล์ CSV', 'error');
        return;
      }

      pendingCsvItems = itemsToUpdate;
      document.getElementById('csv-preview-tbody').innerHTML = previewRowsHtml.join('');
      document.getElementById('csv-preview-count').textContent = itemsToUpdate.length;
      document.getElementById('csv-preview-container').style.display = 'block';
      document.getElementById('btn-confirm-csv-import').disabled = false;
      showToast(`📊 ตรวจสอบพบข้อมูล ${itemsToUpdate.length} รายการพร้อมนำเข้า`, 'info');
    }

    async function submitCsvImport() {
      if (pendingCsvItems.length === 0) return;

      const btn = document.getElementById('btn-confirm-csv-import');
      btn.disabled = true;
      btn.textContent = '⏳ กำลังบันทึกข้อมูล...';

      try {
        const res = await fetch(`${API_BASE}/readings.php?cycle=${encodeURIComponent(activeCycle)}&action=save`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ items: pendingCsvItems })
        });

        if (res.ok) {
          playBeep(1046, 200);
          showToast(`🎉 นำเข้าข้อมูลสำเร็จเรียบร้อย (${pendingCsvItems.length} รายการ)`, 'success');
          closeCsvImportModal();
          await loadFieldData();
        } else {
          showToast('❌ เซิร์ฟเวอร์ตอบรับข้อผิดพลาดในการบันทึก', 'error');
        }
      } catch (err) {
        console.error('Error submitting CSV import:', err);
        showToast('❌ ไม่สามารถนำเข้าข้อมูลได้', 'error');
      } finally {
        btn.disabled = false;
        btn.textContent = '🚀 ยืนยันบันทึกทั้งชุดลงฐานข้อมูล';
      }
    }

    // =========================================================
    // Staff Guide Modal & Accordion Logic
    // =========================================================
    function toggleQuickGuide() {
      const content = document.getElementById('quick-guide-content');
      const arrow = document.getElementById('quick-guide-arrow');
      if (content) {
        if (content.style.display === 'none' || content.style.display === '') {
          content.style.display = 'block';
          if (arrow) arrow.textContent = '▲';
        } else {
          content.style.display = 'none';
          if (arrow) arrow.textContent = '▼';
        }
      }
    }

    function openStaffGuideModal(tabIndex = 1) {
      const modal = document.getElementById('modal-staff-guide');
      if (modal) {
        modal.style.display = 'flex';
        switchGuideTab(tabIndex);
      }
    }

    function closeStaffGuideModal() {
      const modal = document.getElementById('modal-staff-guide');
      if (modal) {
        modal.style.display = 'none';
      }
    }

    function switchGuideTab(tabIndex) {
      for (let i = 1; i <= 4; i++) {
        const pane = document.getElementById(`guide-pane-${i}`);
        const btn = document.getElementById(`gtab-btn-${i}`);
        if (pane) pane.style.display = (i === tabIndex) ? 'block' : 'none';
        if (btn) {
          if (i === tabIndex) {
            btn.style.background = '#0284c7';
            btn.style.color = '#fff';
          } else {
            btn.style.background = 'transparent';
            btn.style.color = '#64748b';
          }
        }
      }
    }

    // Auto open guide if requested via URL
    window.addEventListener('DOMContentLoaded', () => {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('open_guide')) {
        setTimeout(openStaffGuideModal, 300);
      }
    });
  </script>
</body>
</html>

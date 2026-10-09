<?php
// Auto-Redirect HTTP to HTTPS on non-localhost IP so mobile cameras have zero barriers
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalhost = strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false;

if (!$isHttps && !$isLocalhost) {
    $redirectUrl = "https://" . $host . $_SERVER['REQUEST_URI'];
    if (strpos($redirectUrl, 'quick_login=') === false) {
        $redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . 'quick_login=staff';
    }
    header("Location: " . $redirectUrl);
    exit;
}

/**
 * 2. งานจดบันทึกมาตรวัดน้ำภาคสนาม & ตรวจสอบระบบท่อ
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
  <title>จดมิเตอร์น้ำประปา - การประปาหมู่บ้านวังยาง</title>
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
      <?php renderAppTopBar('จดมิเตอร์น้ำประปา', 'ระบบบันทึกเลขอ่านมิเตอร์ คำนวณค่าน้ำ สแกน QR หน้าบ้าน และสรุปยอดประจำเดือน'); ?>

    <!-- Field Header with Progress -->
    <div class="field-header-card no-print">
      <div>
        <span style="background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 9999px; font-size: 13.5px; font-weight: 600;">
          🚶‍♂️ ระบบจดมิเตอร์น้ำประปาภาคสนาม (Field Meter Reader)
        </span>
        <h1 style="font-family: 'Prompt', sans-serif; font-size: 22px; margin: 8px 0 2px 0;">
          สมุดบันทึกการจดมิเตอร์น้ำประปาประจำงวด
        </h1>
        <p style="opacity: 0.9; font-size: 13.5px; margin: 0 0 10px 0;">
          สแกน QR หน้าบ้าน หรือคีย์เลขอ่านมิเตอร์ ระบบคำนวณเงินสดทันที พร้อมรองรับการนำเข้าไฟล์ Excel/CSV
        </p>
        <button type="button" onclick="openAddCustomerModal()" style="background: #ffffff; color: #0284c7; border: none; font-size: 13.5px; font-weight: 700; padding: 7px 16px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.15);">
          ➕ เพิ่มผู้ใช้น้ำ / ติดตั้งมิเตอร์ใหม่
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
          <!-- 0. Add Customer Button -->
          <button type="button" class="btn btn-primary" onclick="openAddCustomerModal()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; background: #0284c7; box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);">
            ➕ เพิ่มผู้ใช้น้ำ / มิเตอร์ใหม่
          </button>

          <!-- 1. QR Code Camera Scanner Button -->
          <button type="button" class="btn btn-qr-scan" onclick="openQrScannerModal()">
            📷 สแกน QR สติกเกอร์มิเตอร์
          </button>

          <!-- 2. Excel/CSV Batch Import -->
          <button type="button" class="btn btn-outline" onclick="openCsvImportModal()" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 600;">
            📥 นำเข้าจาก Excel 365
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
          <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ พิมพ์สมุดจดมิเตอร์</button>
        </div>
      </div>
    </div>

    <!-- Printable Header for Readings Book -->
    <div class="only-print" style="margin-bottom: 20px; text-align: center;">
      <h2 style="font-family: 'Prompt', sans-serif; font-size: 18px; margin: 0;">สมุดบันทึกการจดมิเตอร์น้ำประปาประจำเดือน</h2>
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
    <div class="modal-dialog" style="max-width: 480px; width: 95%;">
      <div class="modal-header" style="background: #0f172a; color: #fff; padding: 14px 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="background: #059669; font-size: 20px; width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">📷</div>
          <div>
            <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #34d399;">สแกน QR Code ติดมิเตอร์น้ำ</h4>
            <span style="font-size: 12px; color: #94a3b8;">ระบบค้นหาและไฮไลต์ช่องกรอกเลขมิเตอร์อัตโนมัติ</span>
          </div>
        </div>
        <button type="button" class="modal-close" onclick="closeQrScannerModal()" style="color: #94a3b8; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
      </div>

      <div class="modal-body" style="padding: 16px 20px; text-align: center;">
        
        <!-- Mode Tabs -->
        <div class="qr-mode-tabs">
          <button type="button" class="qr-mode-tab active" id="tab-btn-snapshot" onclick="switchQrScannerMode('snapshot')">
            📸 ถ่ายรูปสแกนด่วน (แนะนำบนมือถือ)
          </button>
          <button type="button" class="qr-mode-tab" id="tab-btn-live" onclick="switchQrScannerMode('live')">
            📹 ส่องกล้องสด (Live Video)
          </button>
        </div>

        <!-- Option 3: Quick House Number / Name Search Bar (No camera needed) -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; margin-bottom: 12px; text-align: left;">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
            <label for="modal-quick-search-input" style="font-size: 12.5px; font-weight: 700; color: #475569;">
              🔍 ค้นหาด้วยเลขที่บ้าน / ชื่อ (หากไม่สะดวกส่องกล้อง):
            </label>
            <span style="font-size: 11px; color: #0284c7; font-weight: 600;">ไม่ต้องใช้กล้อง</span>
          </div>
          <input type="text" id="modal-quick-search-input" placeholder="พิมพ์เลขที่บ้าน เช่น 24/1 หรือ ชื่อลูกบ้าน..." class="form-input" style="width: 100%; font-size: 14px; padding: 7px 10px; background: #fff;" oninput="handleModalQuickSearch(this.value)">
          <div id="modal-quick-search-results" style="display: none; max-height: 140px; overflow-y: auto; margin-top: 6px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.06);"></div>
        </div>

        <!-- 1. SNAPSHOT MODE VIEW (Default on Mobile - 100% Works, No Black Screen) -->
        <div id="qr-snapshot-panel">
          <div class="qr-target-box" onclick="triggerQrCameraCapture()">
            <div class="qr-target-corners">
              <span class="corner tl"></span>
              <span class="corner tr"></span>
              <span class="corner bl"></span>
              <span class="corner br"></span>
            </div>

            <div id="qr-snapshot-preview" style="display: none; position: relative;">
              <img id="qr-preview-img" src="" alt="ภาพถ่ายมิเตอร์" style="max-height: 190px; max-width: 100%; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.15);">
              <div class="qr-scan-line"></div>
            </div>

            <div id="qr-target-prompt">
              <div style="font-size: 46px; margin-bottom: 6px;">📷</div>
              <strong style="font-size: 16px; color: #0284c7; display: block;">แตะเพื่อถ่ายรูปสแกนสติกเกอร์มิเตอร์</strong>
              <span style="font-size: 13px; color: #64748b; margin-top: 4px; display: block;">
                เปิดกล้องมือถือถ่ายภาพสติกเกอร์ QR หน้าบ้าน<br>ระบบจะอ่านรหัสและเปิดช่องกรอกเลขให้ทันที
              </span>
            </div>
          </div>

          <!-- Status badge -->
          <div id="qr-snapshot-status" style="margin-top: 12px; font-size: 13.5px; font-weight: 600; min-height: 22px; color: #0369a1;">
            💡 ใช้กล้องมือถือถ่ายภาพสติกเกอร์มิเตอร์ หรือเลือกจากอัลบั้ม
          </div>

          <!-- Actions -->
          <div style="margin-top: 14px; display: flex; flex-direction: column; gap: 8px;">
            <input type="file" id="qr-camera-capture" accept="image/*" capture="environment" style="display: none;" onchange="handleQrCameraFile(this)">
            <input type="file" id="qr-file-upload" accept="image/*" style="display: none;" onchange="handleQrCameraFile(this)">

            <button type="button" class="btn btn-primary" onclick="triggerQrCameraCapture()" style="width: 100%; height: 48px; font-size: 15px; font-weight: 700; background: #059669; border: none; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25); cursor: pointer;">
              📸 แตะถ่ายรูปสแกนมิเตอร์ทันที
            </button>
            <button type="button" class="btn btn-outline" onclick="document.getElementById('qr-file-upload').click()" style="width: 100%; height: 42px; font-size: 13.5px; background: #fff; border-radius: 8px; cursor: pointer;">
              📁 หรือเลือกภาพสติกเกอร์จากอัลบั้มในเครื่อง
            </button>
          </div>
        </div>

        <!-- 2. LIVE VIDEO MODE VIEW -->
        <div id="qr-live-panel" style="display: none;">
          <!-- 2A. Insecure HTTP Warning Panel (Shown when opened on plain HTTP over LAN) -->
          <div id="qr-insecure-guide" class="qr-insecure-guide" style="display: none;">
            <div class="guide-icon">🔒</div>
            <h4>จำเป็นต้องใช้ HTTPS เพื่อเปิดกล้องสดบนมือถือ</h4>
            <p>
              ระบบความปลอดภัยของเบราว์เซอร์มือถือ (Chrome/Safari) ปิดกั้นการสตรีมวิดีโอกล้องสดบนลิงก์ HTTP ธรรมดา กรุณาสลับไปเข้าผ่าน HTTPS เพื่อให้เบราว์เซอร์อนุญาตใช้กล้อง
            </p>

            <div class="qr-insecure-actions">
              <button type="button" class="btn btn-primary" onclick="redirectToHttps()" style="height: 48px; font-size: 15px; font-weight: 700; background: #0284c7; border: none; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; color: #fff; cursor: pointer;">
                🔒 แตะเพื่อสลับไปใช้ HTTPS ทันที
              </button>
              <button type="button" class="btn btn-outline" onclick="switchQrScannerMode('snapshot')" style="height: 42px; font-size: 13.5px; background: #fff; border-radius: 8px; cursor: pointer;">
                📸 หรือใช้โหมดถ่ายรูปสแกนด่วน (ใช้งานได้ทันทีบน HTTP 100%)
              </button>
            </div>

            <div class="qr-ssl-steps">
              <strong>📌 ขั้นตอนเมื่อเข้า HTTPS ครั้งแรก:</strong>
              <ol>
                <li>แตะปุ่ม <em>"สลับไปใช้ HTTPS"</em> ด้านบน</li>
                <li>เมื่อมีคำเตือนความปลอดภัย ให้แตะ <strong>"ขั้นสูง (Advanced)"</strong> หรือ <strong>"แสดงรายละเอียด"</strong></li>
                <li>แตะ <strong>"ไปยัง 192.168.88.69"</strong> หรือ <strong>"เข้าชมเว็บไซต์นี้"</strong></li>
                <li>แตะ <strong>"อนุญาต"</strong> กล้องสดจะเปิดทำงานทันที!</li>
              </ol>
            </div>
          </div>

          <!-- 2B. Viewfinder (Shown when in Secure Context) -->
          <div id="qr-scanner-viewfinder" style="display: none;">
            <div id="qr-camera-loading" class="qr-camera-loading">
              <div class="qr-camera-spinner"></div>
              <div class="qr-loading-text">กำลังเชื่อมต่อกล้อง...</div>
              <div class="qr-loading-subtext">หากมีข้อความขึ้นเตือน ให้กด "อนุญาต (Allow)" บนหน้าจอ</div>
            </div>

            <div class="qr-viewfinder-hud">
              <div class="hud-box">
                <span class="hud-corner tl"></span>
                <span class="hud-corner tr"></span>
                <span class="hud-corner bl"></span>
                <span class="hud-corner br"></span>
              </div>
              <div class="hud-hint">เล็ง QR Code ให้อยู่ในกรอบสีเขียว</div>
            </div>

            <div id="qr-reader" style="width: 100%;"></div>
            <div class="qr-scan-line" id="qr-scan-laser" style="display: none;"></div>
          </div>

          <!-- Quick fallback to snapshot if live stream has glare/reflection -->
          <button type="button" class="btn btn-outline" onclick="triggerQrCameraCapture()" style="width: 100%; margin-top: 8px; font-size: 13px; padding: 8px; border-radius: 8px; background: #f0fdf4; color: #166534; font-weight: 600; border: 1px dashed #22c55e; cursor: pointer;">
            📸 ส่องสแกนยาก หรือแสงสะท้อน? แตะที่นี่เพื่อถ่ายรูปสแกนแทนทันที
          </button>

          <!-- 2C. Camera Hardware / Permission Error Panel (Shown if start() fails) -->
          <div id="qr-camera-error-panel" class="qr-camera-error-card" style="display: none; margin-top: 10px;">
            <div style="font-size: 38px; margin-bottom: 6px;">📷</div>
            <h4 style="color: #b91c1c; font-size: 15px; margin: 0 0 6px 0; font-weight: 700;">ไม่สามารถเปิดสตรีมกล้องสดได้</h4>
            <div id="qr-camera-error-msg" style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 14px;">
              เบราว์เซอร์ปฏิเสธการเข้าถึง หรือยังไม่อนุญาตสิทธิ์กล้อง
            </div>
            <div style="display: flex; flex-direction: column; gap: 8px;">
              <button type="button" class="btn btn-primary" onclick="switchQrScannerMode('snapshot')" style="background: #059669; border: none; font-size: 14px; padding: 10px; border-radius: 8px; color: #fff; font-weight: 700; cursor: pointer;">
                📸 แตะถ่ายรูปสแกนมิเตอร์แทน (โหมดนี้ 100% ใช้งานได้ทันที)
              </button>
              <button type="button" class="btn btn-outline" onclick="retryLiveCamera()" style="font-size: 13px; padding: 8px; border-radius: 8px; background: #fff; cursor: pointer;">
                🔄 ลองเชื่อมต่อใหม่อีกครั้ง
              </button>
            </div>
          </div>

          <!-- Controls under viewfinder -->
          <div id="qr-live-controls" style="margin-top: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; font-size: 13px;">
            <div style="display: flex; align-items: center; gap: 8px;">
              <button type="button" id="btn-toggle-camera" class="btn btn-outline" onclick="toggleCameraDevice()" style="display: none; padding: 5px 10px; font-size: 12px; border-radius: 6px; background: #fff; cursor: pointer;">
                🔄 สลับกล้อง
              </button>
              <button type="button" id="btn-toggle-torch" class="btn btn-outline" onclick="toggleTorch()" style="display: none; padding: 5px 10px; font-size: 12px; border-radius: 6px; background: #fff; cursor: pointer;">
                💡 ไฟฉาย
              </button>
            </div>

            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #475569;">
              <input type="checkbox" id="chk-continuous-scan">
              <span>สแกนต่อเนื่อง (ไม่ปิดกล้อง)</span>
            </label>

            <span id="qr-scanner-status" style="font-weight: 600; color: #0284c7; width: 100%; text-align: center; margin-top: 4px;"></span>
          </div>

          <div style="margin-top: 10px; display: flex; gap: 8px;">
            <button type="button" class="btn btn-outline" onclick="retryLiveCamera()" style="flex: 1; font-size: 13px; padding: 8px; background: #fff; cursor: pointer;">
              🔄 รีสตาร์ทกล้อง
            </button>
            <button type="button" class="btn btn-outline" onclick="switchQrScannerMode('snapshot')" style="flex: 1; font-size: 13px; padding: 8px; background: #fff; cursor: pointer;">
              📸 ถ่ายรูปแทน
            </button>
          </div>
        </div>

        <!-- Quick Meter Entry Card (Shown instantly when customer is scanned or selected) -->
        <div id="qr-quick-entry-card" class="qr-quick-entry-card" style="display: none;">
          <div style="display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
            <div>
              <span class="qe-customer-badge" id="qe-cust-code">WY-001</span>
              <h3 id="qe-cust-name" style="margin: 4px 0 0 0; font-size: 17px; color: #0f172a; font-weight: 700;">นายสมเกียรติ สุขใจ</h3>
              <div id="qe-cust-meta" style="font-size: 12.5px; color: #64748b; margin-top: 2px;">บ้านเลขที่ 24/1 ม.3 • โซนทุ่งสามัคคี</div>
            </div>
            <div style="text-align: right; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 12px;">
              <span style="font-size: 11px; color: #64748b; display: block; font-weight: 600;">เลขครั้งก่อน</span>
              <strong id="qe-prev-reading" style="font-size: 17px; color: #475569; font-family: monospace;">120.0</strong>
            </div>
          </div>

          <div style="margin-bottom: 12px;">
            <label for="qe-current-reading" style="font-size: 13px; font-weight: 700; color: #0369a1; display: block; margin-bottom: 4px;">
              🔢 กรอกเลขมิเตอร์ครั้งนี้ (ปัจจุบัน):
            </label>
            <input type="number" step="0.1" min="0" id="qe-current-reading" class="form-input" placeholder="เช่น 125.0" inputmode="decimal" style="font-size: 24px; font-weight: 700; text-align: center; height: 52px; border: 2px solid #0284c7; border-radius: 10px; width: 100%; font-family: monospace; background: #f0f9ff;" oninput="updateQuickEntryCalc()">
          </div>

          <div id="qe-calc-preview" class="qe-calc-preview">
            <div>💧 หน่วยใช้: <strong id="qe-units-val" style="color: #15803d; font-size: 16px;">0.0</strong> ลบ.ม.</div>
            <div>💰 ยอดค่าน้ำ: <strong id="qe-amount-val" style="color: #047857; font-size: 16px;">0.00</strong> บาท</div>
          </div>

          <div style="display: flex; gap: 8px; flex-direction: column;">
            <button type="button" class="btn btn-primary" id="btn-qe-save-next" onclick="submitQuickEntry(true)" style="height: 48px; font-size: 15px; font-weight: 700; background: #059669; border: none; border-radius: 10px; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; box-shadow: 0 4px 12px rgba(5, 150, 105, 0.25);">
              💾 บันทึก & สแกนหลังถัดไป ⏭️
            </button>
            <div style="display: flex; gap: 8px;">
              <button type="button" class="btn btn-outline" onclick="submitQuickEntry(false)" style="flex: 1; height: 42px; font-size: 13.5px; border-radius: 8px; background: #fff; cursor: pointer;">
                💾 บันทึก & ปิดหน้าต่าง
              </button>
              <button type="button" class="btn btn-outline" onclick="cancelQuickEntry()" style="height: 42px; font-size: 13px; border-radius: 8px; background: #fff; color: #64748b; cursor: pointer;">
                ❌ ยกเลิก
              </button>
            </div>
          </div>
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
       Modal 3: Add Customer & Meter (เพิ่มข้อมูลผู้ใช้น้ำใหม่)
       ========================================================= -->
  <div id="modal-add-customer" class="modal" style="display: none; align-items: center; justify-content: center;">
    <div class="modal-dialog" style="max-width: 520px; width: 95%;">
      <div class="modal-header" style="background: #0f172a; color: #fff; padding: 16px 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
          <div style="background: #0284c7; font-size: 20px; width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">➕</div>
          <div>
            <h4 style="margin: 0; font-size: 16.5px; font-weight: 700; color: #38bdf8;">เพิ่มข้อมูลผู้ใช้น้ำ & ติดตั้งมิเตอร์ใหม่</h4>
            <span style="font-size: 12.5px; color: #94a3b8;">บันทึกข้อมูลลูกบ้านและสร้างมิเตอร์เข้าสู่ระบบทันที</span>
          </div>
        </div>
        <button type="button" class="modal-close" onclick="closeAddCustomerModal()" style="color: #94a3b8; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
      </div>

      <form id="form-add-customer" onsubmit="submitAddCustomer(event)">
        <div class="modal-body" style="padding: 20px 24px; max-height: 75vh; overflow-y: auto;">
          <div id="add-cust-alert" style="display: none; padding: 10px 14px; border-radius: 6px; font-size: 13.5px; margin-bottom: 14px;"></div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
            <div>
              <label style="font-weight: 600; font-size: 13px; color: #334155; display: block; margin-bottom: 4px;">
                ชื่อ (First Name) <span style="color: #ef4444;">*</span>
              </label>
              <input type="text" name="firstName" class="form-input" required placeholder="เช่น สมเกียรติ" style="width: 100%;">
            </div>
            <div>
              <label style="font-weight: 600; font-size: 13px; color: #334155; display: block; margin-bottom: 4px;">
                นามสกุล (Last Name) <span style="color: #ef4444;">*</span>
              </label>
              <input type="text" name="lastName" class="form-input" required placeholder="เช่น สุขใจ" style="width: 100%;">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
            <div>
              <label style="font-weight: 600; font-size: 13px; color: #334155; display: block; margin-bottom: 4px;">
                บ้านเลขที่ <span style="color: #ef4444;">*</span>
              </label>
              <input type="text" name="houseNo" class="form-input" required placeholder="เช่น 24/1 ม.3" style="width: 100%;">
            </div>
            <div>
              <label style="font-weight: 600; font-size: 13px; color: #334155; display: block; margin-bottom: 4px;">
                เบอร์โทรศัพท์
              </label>
              <input type="tel" name="phone" class="form-input" placeholder="เช่น 0812345678" style="width: 100%;">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
            <div>
              <label style="font-weight: 600; font-size: 13px; color: #334155; display: block; margin-bottom: 4px;">
                โซน / คุ้มสายจด <span style="color: #ef4444;">*</span>
              </label>
              <select name="zoneId" class="form-select" required style="width: 100%;">
                <option value="1">โซนทุ่งสามัคคี</option>
                <option value="2">โซนโค้งขี้เหล็ก</option>
              </select>
            </div>
            <div>
              <label style="font-weight: 600; font-size: 13px; color: #334155; display: block; margin-bottom: 4px;">
                ขนาดมาตรวัดน้ำ
              </label>
              <select name="installTypeId" class="form-select" style="width: 100%;">
                <option value="1">5/8 นิ้ว (บ้านพักอาศัยทั่วไป)</option>
                <option value="2">1 นิ้ว (ร้านค้า/การเกษตร)</option>
                <option value="3">1.5 นิ้ว (ขนาดใหญ่)</option>
              </select>
            </div>
          </div>

          <div style="margin-bottom: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 14px;">
            <label style="font-weight: 700; font-size: 13px; color: #166534; display: block; margin-bottom: 4px;">
              🔢 เลขมิเตอร์เริ่มต้น (เลขครั้งก่อน)
            </label>
            <div style="display: flex; align-items: center; gap: 8px;">
              <input type="number" step="0.1" min="0" name="initialReading" value="0.0" class="form-input" style="width: 140px; font-weight: 700; font-family: monospace; font-size: 15px; text-align: right;">
              <span style="font-size: 13px; color: #15803d;">ลูกบาศก์เมตร (ลบ.ม.)</span>
            </div>
            <span style="font-size: 12px; color: #15803d; display: block; margin-top: 4px;">
              * หากเป็นมิเตอร์ใหม่ให้ใส่ 0.0 หรือหากเปลี่ยนมิเตอร์เดิมมาให้กรอกเลขหน้าปัดปัจจุบัน
            </span>
          </div>
        </div>

        <div class="modal-footer" style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
          <button type="button" class="btn btn-outline" onclick="closeAddCustomerModal()">ยกเลิก</button>
          <button type="submit" class="btn btn-primary" id="btn-submit-add-cust" style="font-weight: 700; padding: 9px 22px; background: #0284c7;">
            💾 บันทึกและเพิ่มเข้าสมุดจดทันที
          </button>
        </div>
      </form>
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

    <button type="button" class="btn btn-primary" onclick="openAddCustomerModal()" style="padding: 8px 12px; border-radius: 8px; font-size: 13px; background: #0284c7; color: #fff; font-weight: 700; border: none; display: inline-flex; align-items: center; gap: 4px;">
      ➕ เพิ่มผู้ใช้
    </button>

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
        const isRead = (curr > 0);
        const isNegative = (curr > 0 && curr < prev);
        const isSpike = (units > 35); // Leak / spike warning threshold

        return `
          <tr data-customer-id="${c.id}" data-customer-code="${c.customer_code}" class="${isRead ? 'row-done' : 'row-pending'}">
            <td data-label="ลำดับสายจด" class="text-center font-bold" style="color: #64748b;">${c.seq_no}</td>
            <td data-label="รหัสผู้ใช้น้ำ"><strong>${c.customer_code}</strong></td>
            <td data-label="ชื่อ - นามสกุล"><strong>${c.first_name} ${c.last_name}</strong></td>
            <td data-label="บ้านเลขที่">${c.house_no}</td>
            <td data-label="เบอร์โทรศัพท์"><a href="tel:${c.phone || ''}" style="color: #0284c7; text-decoration: none; font-weight: 600; white-space: nowrap;">📞 ${c.phone || '-'}</a></td>
            <td data-label="โซน / คุ้ม"><span style="font-size: 13.5px; color: #475569;">${c.zone}</span></td>
            <td data-label="หมายเลขมิเตอร์"><code style="font-size: 13px;">${c.meter_serial || '-'}</code></td>
            <td data-label="เลขครั้งก่อน" class="text-right" style="color: #475569; font-weight: 600;">${prev.toFixed(1)}</td>
            <td data-label="เลขครั้งหลัง (กรอก)" class="text-right meter-td-input">
              <input type="number" 
                     step="0.1" 
                     inputmode="decimal"
                     autocomplete="off"
                     class="meter-input-cell no-print" 
                     data-id="${c.id}" 
                     data-code="${c.customer_code}"
                     data-prev="${prev}" 
                     data-arrears="${r ? r.previous_arrears : 0}"
                     value="${curr > 0 ? curr.toFixed(1) : ''}" 
                     placeholder="${prev.toFixed(1)}">
              <span class="only-print font-bold">${curr.toFixed(1)}</span>
              ${isNegative ? '<br><span class="abnormal-warning" style="background: #fef3c7; color: #92400e; border: 1px solid #f59e0b;" title="สันนิษฐาน: 2 ระบบดันกันเอง (ปั๊มบาดาลดันย้อน) หรือใช้น้ำผิดปกติ">⚠️ มิเตอร์ติดลบ (2 ระบบดันกัน)</span>' : ''}
              ${isSpike ? '<br><span class="abnormal-warning">⚠️ ใช้น้ำพุ่งสูงผิดปกติ</span>' : ''}
            </td>
            <td data-label="หน่วยที่ใช้" class="text-right font-bold" style="color: ${units > 0 ? '#0284c7' : '#94a3b8'};">
              <span id="units-disp-${c.id}">${units.toFixed(1)}</span>
            </td>
            <td data-label="ยอดเงินงวดนี้" class="text-right font-bold text-danger">
              <span id="amount-disp-${c.id}">${grandTotal.toFixed(2)}</span> ฿
            </td>
            <td data-label="สถานะ" class="text-center">
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
            showToast(`⚠️ สันนิษฐาน: เลขมิเตอร์ (${curr}) ต่ำกว่าเดิม (${prev}) อาจเกิดจากระบบน้ำ 2 ระบบดันกันเอง (ปั๊มบาดาลดันย้อน) หรือใช้น้ำผิดปกติ`, 'warning', 6000);
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
    // QR Code Camera Scanner Logic (Robust Dual-Mode: Snapshot & Live)
    // =========================================================
    let currentQrMode = 'snapshot';
    let availableCameras = [];
    let currentCameraIndex = 0;
    let isTorchOn = false;

    function isSecureContextCheck() {
      return (window.isSecureContext || location.protocol === 'https:' || location.hostname === 'localhost' || location.hostname === '127.0.0.1');
    }

    function redirectToHttps() {
      const host = window.location.hostname;
      const path = window.location.pathname;
      const search = window.location.search || '';
      const urlParams = new URLSearchParams(search);
      urlParams.set('quick_login', 'staff');
      window.location.href = `https://${host}${path}?${urlParams.toString()}`;
    }

    function openQrScannerModal() {
      const modal = document.getElementById('modal-qr-scanner');
      if (!modal) return;
      modal.style.display = 'flex';

      // Reset state & clear previous quick entry / search previews
      const qeCard = document.getElementById('qr-quick-entry-card');
      if (qeCard) qeCard.style.display = 'none';
      activeQuickEntryCustomer = null;

      const searchInp = document.getElementById('modal-quick-search-input');
      if (searchInp) searchInp.value = '';
      const searchRes = document.getElementById('modal-quick-search-results');
      if (searchRes) { searchRes.style.display = 'none'; searchRes.innerHTML = ''; }

      const previewBox = document.getElementById('qr-snapshot-preview');
      if (previewBox) previewBox.style.display = 'none';

      const promptBox = document.getElementById('qr-target-prompt');
      if (promptBox) promptBox.style.display = 'block';

      const snapStatus = document.getElementById('qr-snapshot-status');
      if (snapStatus) snapStatus.innerHTML = '💡 ใช้กล้องมือถือถ่ายภาพสติกเกอร์มิเตอร์ หรือเลือกจากอัลบั้ม';

      // If already on HTTPS or localhost, start in Live mode. Otherwise default to snapshot
      if (isSecureContextCheck()) {
        switchQrScannerMode('live');
      } else {
        switchQrScannerMode('snapshot');
      }
    }

    function closeQrScannerModal() {
      const modal = document.getElementById('modal-qr-scanner');
      if (modal) {
        modal.style.display = 'none';
        stopQrScanner();
        const qeCard = document.getElementById('qr-quick-entry-card');
        if (qeCard) qeCard.style.display = 'none';
        activeQuickEntryCustomer = null;
      }
    }

    function switchQrScannerMode(mode) {
      currentQrMode = mode;
      const tabSnap = document.getElementById('tab-btn-snapshot');
      const tabLive = document.getElementById('tab-btn-live');
      const panelSnap = document.getElementById('qr-snapshot-panel');
      const panelLive = document.getElementById('qr-live-panel');
      const insecureGuide = document.getElementById('qr-insecure-guide');
      const viewfinder = document.getElementById('qr-scanner-viewfinder');
      const errPanel = document.getElementById('qr-camera-error-panel');
      const liveControls = document.getElementById('qr-live-controls');

      if (tabSnap) tabSnap.classList.toggle('active', mode === 'snapshot');
      if (tabLive) tabLive.classList.toggle('active', mode === 'live');
      if (panelSnap) panelSnap.style.display = (mode === 'snapshot') ? 'block' : 'none';
      if (panelLive) panelLive.style.display = (mode === 'live') ? 'block' : 'none';

      if (mode === 'live') {
        if (!isSecureContextCheck()) {
          // Insecure HTTP origin -> Never display an empty black box!
          if (viewfinder) viewfinder.style.display = 'none';
          if (errPanel) errPanel.style.display = 'none';
          if (liveControls) liveControls.style.display = 'none';
          if (insecureGuide) insecureGuide.style.display = 'block';
          stopQrScanner();
        } else {
          if (insecureGuide) insecureGuide.style.display = 'none';
          if (errPanel) errPanel.style.display = 'none';
          if (viewfinder) viewfinder.style.display = 'flex';
          if (liveControls) liveControls.style.display = 'flex';
          startQrScanner();
        }
      } else {
        stopQrScanner();
      }
    }

    function triggerQrCameraCapture() {
      const input = document.getElementById('qr-camera-capture');
      if (input) input.click();
    }

    async function handleQrCameraFile(input) {
      if (!input.files || input.files.length === 0) return;
      const file = input.files[0];
      const snapStatus = document.getElementById('qr-snapshot-status');
      const previewBox = document.getElementById('qr-snapshot-preview');
      const promptBox = document.getElementById('qr-target-prompt');
      const previewImg = document.getElementById('qr-preview-img');

      // Stop any live video stream if running
      await stopQrScanner();

      // Display preview thumbnail immediately so the user sees the photo they just took
      if (previewImg && file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          previewImg.src = e.target.result;
          if (previewBox) previewBox.style.display = 'block';
          if (promptBox) promptBox.style.display = 'none';
        };
        reader.readAsDataURL(file);
      }

      if (snapStatus) snapStatus.innerHTML = '<span style="color: #0284c7;">⚡ กำลังประมวลผลและอ่านรหัส QR Code จากภาพ...</span>';

      if (!html5QrScanner) {
        html5QrScanner = new Html5Qrcode("qr-reader");
      }

      try {
        const decodedText = await html5QrScanner.scanFile(file, true);
        if (snapStatus) snapStatus.innerHTML = '<span style="color: #16a34a;">✅ สแกนสำเร็จ!</span>';
        handleQrScanSuccess(decodedText);
      } catch (err) {
        console.error('File scan error:', err);
        if (snapStatus) snapStatus.innerHTML = '<span style="color: #dc2626;">⚠️ ไม่พบ QR Code ในภาพ กรุณาถ่ายใหม่อีกครั้ง</span>';
        showToast('⚠️ ไม่พบ QR Code ในภาพ กรุณาเล็งให้ตรงและชัดเจนแล้วถ่ายใหม่อีกครั้ง', 'warning');
      } finally {
        input.value = '';
      }
    }

    async function startQrScanner() {
      const viewfinder = document.getElementById('qr-scanner-viewfinder');
      const loadingEl = document.getElementById('qr-camera-loading');
      const laserEl = document.getElementById('qr-scan-laser');
      const statusEl = document.getElementById('qr-scanner-status');
      const errPanel = document.getElementById('qr-camera-error-panel');
      const errMsgEl = document.getElementById('qr-camera-error-msg');
      const btnToggleCam = document.getElementById('btn-toggle-camera');

      if (viewfinder) viewfinder.style.display = 'flex';
      if (loadingEl) loadingEl.style.display = 'flex';
      if (laserEl) laserEl.style.display = 'none';
      if (errPanel) errPanel.style.display = 'none';
      if (statusEl) statusEl.textContent = 'กำลังเชื่อมต่อกล้อง...';

      await stopQrScanner();

      if (!html5QrScanner) {
        html5QrScanner = new Html5Qrcode("qr-reader");
      }

      // Enumerate cameras if available
      let selectedCameraIdOrConfig = { facingMode: "environment" };
      try {
        const devices = await Html5Qrcode.getCameras();
        if (devices && devices.length > 0) {
          availableCameras = devices;
          if (btnToggleCam) btnToggleCam.style.display = devices.length > 1 ? 'inline-block' : 'none';
          const backIndex = devices.findIndex(d => /back|rear|environment|หลัง|wide|main/i.test(d.label));
          currentCameraIndex = backIndex !== -1 ? backIndex : devices.length - 1;
          selectedCameraIdOrConfig = devices[currentCameraIndex].id;
        }
      } catch (camErr) {
        console.warn('Camera enumeration error, fallback to facingMode constraint:', camErr);
        selectedCameraIdOrConfig = { facingMode: "environment" };
      }

      const qrSuccessCallback = (decodedText, decodedResult) => {
        handleQrScanSuccess(decodedText);
      };

      const config = {
        fps: 15,
        qrbox: (viewfinderWidth, viewfinderHeight) => {
          const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
          const qrboxSize = Math.max(180, Math.floor(minEdge * 0.72));
          return { width: qrboxSize, height: qrboxSize };
        },
        aspectRatio: 1.0,
        showTorchButtonIfSupported: true
      };

      try {
        await html5QrScanner.start(
          selectedCameraIdOrConfig,
          config,
          qrSuccessCallback,
          () => {}
        );

        if (loadingEl) loadingEl.style.display = 'none';
        if (laserEl) laserEl.style.display = 'block';
        if (statusEl) statusEl.textContent = '🟢 กล้องพร้อมทำงาน ส่องที่ QR สติกเกอร์';

        // Set playsinline & muted explicitly on video element for mobile WebKit & Blink
        const videoEl = document.querySelector('#qr-reader video');
        if (videoEl) {
          videoEl.setAttribute('playsinline', 'true');
          videoEl.setAttribute('webkit-playsinline', 'true');
          videoEl.muted = true;
          videoEl.autoplay = true;
          videoEl.style.width = '100%';
          videoEl.style.height = '100%';
          videoEl.style.objectFit = 'cover';
          videoEl.play().catch(e => console.warn('video play:', e));
        }

        checkTorchCapability();

      } catch (err) {
        console.error('Camera start error:', err);
        // Fallback: try user camera
        if (typeof selectedCameraIdOrConfig === 'object' && selectedCameraIdOrConfig.facingMode === 'environment') {
          try {
            await html5QrScanner.start({ facingMode: "user" }, config, qrSuccessCallback, () => {});
            if (loadingEl) loadingEl.style.display = 'none';
            if (laserEl) laserEl.style.display = 'block';
            if (statusEl) statusEl.textContent = '🟢 กล้องหน้าพร้อมทำงาน';
            return;
          } catch (err2) {
            console.error('User camera fallback error:', err2);
          }
        }

        // Hide viewfinder so NO BLACK BOX remains
        if (viewfinder) viewfinder.style.display = 'none';
        if (loadingEl) loadingEl.style.display = 'none';
        if (laserEl) laserEl.style.display = 'none';
        if (errPanel) errPanel.style.display = 'block';

        let errMessage = 'เบราว์เซอร์ไม่อนุญาตให้เปิดสตรีมกล้องสด';
        const errString = String(err);
        if (/NotAllowedError|Permission/i.test(errString)) {
          errMessage = '⚠️ สิทธิ์ถูกปฏิเสธ: กรุณาแตะไอคอน 🔒 ข้างช่อง URL ของเบราว์เซอร์ แล้วเลือก "อนุญาตการเข้าถึงกล้อง"';
        } else if (/NotFoundError|DevicesNotFoundError/i.test(errString)) {
          errMessage = '⚠️ ไม่พบกล้องบนอุปกรณ์ หรือกล้องกำลังถูกใช้งานโดยแอปอื่น';
        } else if (/NotReadableError|TrackStartError/i.test(errString)) {
          errMessage = '⚠️ ฮาร์ดแวร์กล้องไม่ตอบสนอง กรุณาปิดแอปกล้องอื่นแล้วลองใหม่อีกครั้ง';
        }
        if (errMsgEl) errMsgEl.textContent = errMessage;
        if (statusEl) statusEl.innerHTML = '<span style="color: #dc2626;">❌ ไม่สามารถเปิดกล้องสดได้</span>';
      }
    }

    async function toggleCameraDevice() {
      if (availableCameras.length < 2) return;
      currentCameraIndex = (currentCameraIndex + 1) % availableCameras.length;
      const statusEl = document.getElementById('qr-scanner-status');
      if (statusEl) statusEl.textContent = `กำลังสลับกล้อง (${currentCameraIndex + 1}/${availableCameras.length})...`;
      await stopQrScanner();
      await startQrScanner();
    }

    async function toggleTorch() {
      if (!html5QrScanner) return;
      try {
        const caps = html5QrScanner.getRunningTrackCameraCapabilities();
        if (caps && caps.torchFeature().isSupported()) {
          isTorchOn = !isTorchOn;
          await html5QrScanner.applyVideoConstraints({
            advanced: [{ torch: isTorchOn }]
          });
          const btn = document.getElementById('btn-toggle-torch');
          if (btn) btn.textContent = isTorchOn ? '🔦 ปิดไฟฉาย' : '💡 เปิดไฟฉาย';
        }
      } catch (e) {
        console.warn('Torch toggle error:', e);
      }
    }

    function checkTorchCapability() {
      try {
        const btn = document.getElementById('btn-toggle-torch');
        if (!btn || !html5QrScanner) return;
        const caps = html5QrScanner.getRunningTrackCameraCapabilities();
        if (caps && caps.torchFeature().isSupported()) {
          btn.style.display = 'inline-block';
          btn.textContent = '💡 ไฟฉาย';
        } else {
          btn.style.display = 'none';
        }
      } catch (e) {
        const btn = document.getElementById('btn-toggle-torch');
        if (btn) btn.style.display = 'none';
      }
    }

    function retryLiveCamera() {
      const errPanel = document.getElementById('qr-camera-error-panel');
      if (errPanel) errPanel.style.display = 'none';
      stopQrScanner().then(() => {
        startQrScanner();
      });
    }

    async function stopQrScanner() {
      if (html5QrScanner) {
        try {
          if (html5QrScanner.isScanning) {
            await html5QrScanner.stop();
          }
          html5QrScanner.clear();
        } catch (e) {
          console.warn('Error stopping QR scanner:', e);
        }
      }
      const loadingEl = document.getElementById('qr-camera-loading');
      const laserEl = document.getElementById('qr-scan-laser');
      if (loadingEl) loadingEl.style.display = 'none';
      if (laserEl) laserEl.style.display = 'none';
    }

    let activeQuickEntryCustomer = null;

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

      // Find corresponding customer
      const targetCustomer = fieldCustomers.find(c => 
        c.customer_code.toUpperCase() === custCode.toUpperCase() ||
        c.house_no === custCode ||
        c.meter_serial === custCode ||
        (c.phone && c.phone.replace(/\D/g, '') === custCode.replace(/\D/g, ''))
      );

      if (!targetCustomer) {
        showToast(`⚠️ สแกนพบ "${decodedText}" แต่ไม่พบรหัสผู้ใช้น้ำในระบบ`, 'warning');
        return;
      }

      // Open Quick Meter Entry Card directly inside the modal!
      openQuickEntryCardForCustomer(targetCustomer);
    }

    function openQuickEntryCardForCustomer(targetCustomer) {
      activeQuickEntryCustomer = targetCustomer;
      stopQrScanner();

      // Hide scanner panels & search dropdown
      const panelSnap = document.getElementById('qr-snapshot-panel');
      const panelLive = document.getElementById('qr-live-panel');
      const searchResults = document.getElementById('modal-quick-search-results');
      const quickEntryCard = document.getElementById('qr-quick-entry-card');

      if (panelSnap) panelSnap.style.display = 'none';
      if (panelLive) panelLive.style.display = 'none';
      if (searchResults) searchResults.style.display = 'none';

      // Populate customer info
      document.getElementById('qe-cust-code').textContent = targetCustomer.customer_code;
      document.getElementById('qe-cust-name').textContent = `${targetCustomer.first_name} ${targetCustomer.last_name}`;
      document.getElementById('qe-cust-meta').textContent = `บ้านเลขที่ ${targetCustomer.house_no} • โซน: ${targetCustomer.zone || 'ทั่วไป'}`;

      // Get previous reading & existing reading
      const r = fieldReadings.find(x => parseInt(x.customer_id, 10) === parseInt(targetCustomer.id, 10));
      const prev = r ? parseFloat(r.previous_reading) : 0;
      const curr = r && r.current_reading > 0 ? parseFloat(r.current_reading) : '';

      document.getElementById('qe-prev-reading').textContent = prev.toFixed(1);
      const currInp = document.getElementById('qe-current-reading');
      currInp.value = curr;
      updateQuickEntryCalc();

      if (quickEntryCard) quickEntryCard.style.display = 'block';

      // Focus input with numeric keypad ready
      setTimeout(() => {
        currInp.focus();
        if (currInp.value) currInp.select();
      }, 150);
    }

    function updateQuickEntryCalc() {
      if (!activeQuickEntryCustomer) return;
      const r = fieldReadings.find(x => parseInt(x.customer_id, 10) === parseInt(activeQuickEntryCustomer.id, 10));
      const prev = r ? parseFloat(r.previous_reading) : 0;
      const valStr = document.getElementById('qe-current-reading').value;
      const curr = parseFloat(valStr);

      const unitsEl = document.getElementById('qe-units-val');
      const amountEl = document.getElementById('qe-amount-val');

      if (isNaN(curr) || valStr === '') {
        unitsEl.textContent = '0.0';
        amountEl.textContent = '0.00';
        return;
      }

      const units = curr >= prev ? (curr - prev) : ((10000 - prev) + curr);
      let waterCharge = 0;
      let remaining = units;
      const tiers = [
        { max: 10, rate: 10 },
        { max: 10, rate: 15 },
        { max: 10, rate: 20 },
        { max: Infinity, rate: 25 }
      ];
      for (const t of tiers) {
        if (remaining <= 0) break;
        const take = Math.min(remaining, t.max);
        waterCharge += take * t.rate;
        remaining -= take;
      }
      const maintenanceFee = 10;
      const total = waterCharge + maintenanceFee;

      unitsEl.textContent = units.toFixed(1);
      amountEl.textContent = total.toFixed(2);
    }

    async function submitQuickEntry(continueNext) {
      if (!activeQuickEntryCustomer) return;
      const custId = parseInt(activeQuickEntryCustomer.id, 10);
      const currStr = document.getElementById('qe-current-reading').value.trim();

      if (currStr === '' || isNaN(parseFloat(currStr))) {
        showToast('⚠️ กรุณากรอกเลขมิเตอร์ปัจจุบัน', 'warning');
        document.getElementById('qe-current-reading').focus();
        return;
      }

      const curr = parseFloat(currStr);
      const r = fieldReadings.find(x => parseInt(x.customer_id, 10) === custId);
      const prev = r ? parseFloat(r.previous_reading) : 0;
      const arrears = r ? parseFloat(r.previous_arrears) : 0;
      const units = curr >= prev ? (curr - prev) : ((10000 - prev) + curr);

      const btnSaveNext = document.getElementById('btn-qe-save-next');
      if (btnSaveNext) btnSaveNext.disabled = true;

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
          playBeep(1046, 180);
          showToast(`🎉 บันทึก ${activeQuickEntryCustomer.customer_code} (${activeQuickEntryCustomer.first_name}) เรียบร้อย: ${curr.toFixed(1)} ลบ.ม.`, 'success');

          // Update local memory and table
          if (r) {
            r.current_reading = curr;
            r.units_used = units;
          } else {
            fieldReadings.push({
              customer_id: custId,
              previous_reading: prev,
              current_reading: curr,
              units_used: units,
              previous_arrears: arrears
            });
          }

          // Update corresponding table row DOM
          const targetRow = document.querySelector(`tr[data-customer-id="${custId}"]`);
          if (targetRow) {
            const inputCell = targetRow.querySelector('.meter-input-cell');
            if (inputCell) {
              inputCell.value = curr.toFixed(1);
              inputCell.style.borderColor = '#22c55e';
              inputCell.style.background = '#f0fdf4';
            }
            const uDisp = document.getElementById(`units-disp-${custId}`);
            if (uDisp) uDisp.textContent = units.toFixed(1);
          }
          updateProgressStats();

          if (continueNext) {
            // Reset for next house
            document.getElementById('qr-quick-entry-card').style.display = 'none';
            const sInp = document.getElementById('modal-quick-search-input');
            if (sInp) sInp.value = '';
            const sRes = document.getElementById('modal-quick-search-results');
            if (sRes) sRes.style.display = 'none';
            activeQuickEntryCustomer = null;
            // Resume scanner
            switchQrScannerMode(currentQrMode);
          } else {
            closeQrScannerModal();
            if (targetRow) {
              targetRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
              targetRow.classList.remove('qr-highlight-row');
              void targetRow.offsetWidth;
              targetRow.classList.add('qr-highlight-row');
            }
          }
        } else {
          showToast('❌ ไม่สามารถบันทึกข้อมูลได้', 'error');
        }
      } catch (err) {
        console.error('Error saving quick entry:', err);
        showToast('❌ การเชื่อมต่อผิดพลาด ไม่สามารถบันทึกได้', 'error');
      } finally {
        if (btnSaveNext) btnSaveNext.disabled = false;
      }
    }

    function cancelQuickEntry() {
      const qeCard = document.getElementById('qr-quick-entry-card');
      if (qeCard) qeCard.style.display = 'none';
      activeQuickEntryCustomer = null;
      switchQrScannerMode(currentQrMode);
    }

    function handleModalQuickSearch(keyword) {
      const resultsBox = document.getElementById('modal-quick-search-results');
      if (!resultsBox) return;
      const kw = (keyword || '').trim().toLowerCase();
      if (!kw) {
        resultsBox.style.display = 'none';
        resultsBox.innerHTML = '';
        return;
      }

      const matches = fieldCustomers.filter(c => {
        const code = (c.customer_code || '').toLowerCase();
        const name = `${c.first_name || ''} ${c.last_name || ''}`.toLowerCase();
        const house = (c.house_no || '').toLowerCase();
        const phone = (c.phone || '').replace(/\D/g, '');
        return code.includes(kw) || name.includes(kw) || house.includes(kw) || phone.includes(kw);
      }).slice(0, 6);

      if (matches.length === 0) {
        resultsBox.innerHTML = '<div style="padding: 10px; font-size: 13px; color: #94a3b8; text-align: center;">ไม่พบข้อมูลผู้ใช้น้ำที่ตรงกับคำค้นหา</div>';
        resultsBox.style.display = 'block';
        return;
      }

      resultsBox.innerHTML = matches.map(c => `
        <div class="modal-search-item" onclick="selectQuickSearchCustomer(${c.id})">
          <div>
            <strong style="color: #0284c7;">${c.customer_code}</strong>: ${c.first_name} ${c.last_name}
            <div style="font-size: 12px; color: #64748b;">บ้านเลขที่ ${c.house_no} • โซน: ${c.zone || 'ทั่วไป'}</div>
          </div>
          <button type="button" class="btn btn-primary" style="padding: 4px 10px; font-size: 12px; border-radius: 6px; background: #0284c7; border: none; color: #fff;">
            เลือก 👉
          </button>
        </div>
      `).join('');
      resultsBox.style.display = 'block';
    }

    function selectQuickSearchCustomer(customerId) {
      const cust = fieldCustomers.find(c => parseInt(c.id, 10) === parseInt(customerId, 10));
      if (!cust) return;
      const resultsBox = document.getElementById('modal-quick-search-results');
      const searchInp = document.getElementById('modal-quick-search-input');
      if (resultsBox) resultsBox.style.display = 'none';
      if (searchInp) searchInp.value = '';
      openQuickEntryCardForCustomer(cust);
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
    // Staff Add Customer & Meter Logic
    // =========================================================
    function openAddCustomerModal() {
      const modal = document.getElementById('modal-add-customer');
      if (modal) {
        modal.style.display = 'flex';
        const alertBox = document.getElementById('add-cust-alert');
        if (alertBox) alertBox.style.display = 'none';
        const firstInp = modal.querySelector('input[name="firstName"]');
        if (firstInp) setTimeout(() => firstInp.focus(), 100);
      }
    }

    function closeAddCustomerModal() {
      const modal = document.getElementById('modal-add-customer');
      if (modal) modal.style.display = 'none';
    }

    async function submitAddCustomer(e) {
      e.preventDefault();
      const form = e.target;
      const submitBtn = document.getElementById('btn-submit-add-cust');
      const alertBox = document.getElementById('add-cust-alert');
      
      const formData = new FormData(form);
      const data = {
        firstName: formData.get('firstName')?.trim(),
        lastName: formData.get('lastName')?.trim(),
        houseNo: formData.get('houseNo')?.trim(),
        phone: formData.get('phone')?.trim(),
        zoneId: parseInt(formData.get('zoneId'), 10),
        installTypeId: parseInt(formData.get('installTypeId'), 10),
        initialReading: parseFloat(formData.get('initialReading')) || 0.0
      };

      if (!data.firstName) {
        if (alertBox) {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.color = '#b91c1c';
          alertBox.textContent = 'กรุณาระบุชื่อลูกค้า';
        }
        return;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = '⏳ กำลังบันทึกข้อมูล...';

      try {
        const res = await fetch(`${API_BASE}/customers.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });

        const result = await res.json();

        if (res.ok && result.success) {
          closeAddCustomerModal();
          form.reset();
          showToast(`🎉 เพิ่มผู้ใช้น้ำใหม่ ${result.customer_code} (${data.firstName}) เข้าสู่ระบบสำเร็จ!`, 'success');
          
          // Refresh field readings table immediately
          await loadFieldData();

          // Scroll to the newly added row & highlight
          setTimeout(() => {
            const row = document.querySelector(`tr[data-customer-id="${result.id}"]`);
            if (row) {
              row.scrollIntoView({ behavior: 'smooth', block: 'center' });
              row.style.transition = 'background-color 0.5s';
              row.style.backgroundColor = '#bbf7d0';
              setTimeout(() => {
                row.style.backgroundColor = '';
                const inp = row.querySelector('.meter-input-cell');
                if (inp) inp.focus();
              }, 1800);
            }
          }, 300);
        } else {
          if (alertBox) {
            alertBox.style.display = 'block';
            alertBox.style.background = '#fee2e2';
            alertBox.style.color = '#b91c1c';
            alertBox.textContent = `❌ ${result.error || 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'}`;
          }
        }
      } catch (err) {
        console.error(err);
        if (alertBox) {
          alertBox.style.display = 'block';
          alertBox.style.background = '#fee2e2';
          alertBox.style.color = '#b91c1c';
          alertBox.textContent = '❌ ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้';
        }
      } finally {
        submitBtn.disabled = false;
        submitBtn.textContent = '💾 บันทึกและเพิ่มเข้าสมุดจดทันที';
      }
    }

    // Auto open modal if requested via URL (?action=add_customer)
    window.addEventListener('DOMContentLoaded', () => {
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.get('action') === 'add_customer') {
        setTimeout(openAddCustomerModal, 300);
      }
    });
  </script>
</body>
</html>

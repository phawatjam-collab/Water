<?php
/**
 * 1. บริการข้อมูลและตรวจสอบค่าน้ำออนไลน์ (Citizen Portal)
 * สิทธิ์การใช้งาน: ประชาชนทั่วไปและสมาชิกผู้ใช้น้ำ (Public Access / ไม่ต้องล็อกอิน)
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/sidebar.php';
require_once __DIR__ . '/api/db.php';

// ดึงรอบบิลล่าสุดและสถิติ
$cycleStmt = $pdo->query("SELECT * FROM billing_cycles ORDER BY id DESC LIMIT 1");
$currentCycle = $cycleStmt->fetch() ?: ['cycle_code' => '8-2567', 'month' => 8, 'year_be' => 2567];

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>บริการข้อมูลและตรวจสอบค่าน้ำออนไลน์ - การประปาหมู่บ้านวังยาง</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <?php renderPwaHead(); ?>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .citizen-hero-box {
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #fff;
      padding: 40px 24px;
      text-align: center;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .citizen-hero-box h1 {
      font-family: 'Prompt', sans-serif;
      font-size: 26px;
      font-weight: 700;
      margin-bottom: 8px;
    }
    .search-card {
      background: #fff;
      border-radius: 12px;
      padding: 24px;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      max-width: 720px;
      margin: -25px auto 30px auto;
      position: relative;
    }
    .citizen-search-row {
      display: flex;
      gap: 10px;
      position: relative;
      align-items: center;
    }
    .citizen-search-row .search-input-wrapper {
      flex: 1;
      position: relative;
      display: flex;
      align-items: center;
    }
    .citizen-search-row input {
      width: 100%;
      height: 48px;
      box-sizing: border-box;
      padding: 12px 18px;
      border: 2px solid #cbd5e1;
      border-radius: 8px;
      font-size: 15px;
      font-family: 'Sarabun', sans-serif;
      outline: none;
      transition: all 0.2s;
    }
    .citizen-search-row input:focus {
      border-color: #0284c7;
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    }
    .btn-search {
      background: #0284c7;
      color: #fff;
      border: none;
      padding: 0 24px;
      height: 48px;
      border-radius: 8px;
      font-size: 15px;
      font-family: 'Prompt', sans-serif;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
      flex-shrink: 0;
      transition: background 0.15s;
    }
    .btn-search:hover {
      background: #0369a1;
    }
    @media (max-width: 560px) {
      .citizen-search-row {
        flex-direction: column;
      }
      .btn-search {
        width: 100%;
        justify-content: center;
      }
    }
    .search-autocomplete-dropdown {
      position: absolute !important;
      top: calc(100% + 8px) !important;
      left: 0 !important;
      right: 0 !important;
      width: 100% !important;
      box-sizing: border-box !important;
      background: #ffffff !important;
      border: 1px solid #cbd5e1 !important;
      border-radius: 14px !important;
      box-shadow: 0 20px 45px -8px rgba(15, 23, 42, 0.22), 0 8px 18px -4px rgba(0, 0, 0, 0.08) !important;
      max-height: 420px !important;
      overflow-y: auto !important;
      z-index: 10000 !important;
      display: none;
      flex-direction: column !important;
      padding: 8px !important;
      gap: 4px !important;
      animation: dropdownFadeSmooth 0.2s cubic-bezier(0.16, 1, 0.3, 1) !important;
      scrollbar-width: thin !important;
    }
    .search-autocomplete-dropdown:empty {
      display: none !important;
      border: none !important;
      padding: 0 !important;
      box-shadow: none !important;
      background: transparent !important;
    }
    @keyframes dropdownFadeSmooth {
      from { opacity: 0; transform: translateY(-8px) scale(0.99); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .search-dropdown-header {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 9px 14px 7px 14px !important;
      font-size: 12px !important;
      font-weight: 700 !important;
      color: #64748b !important;
      border-bottom: 1px solid #f1f5f9 !important;
      text-transform: uppercase !important;
      letter-spacing: 0.5px !important;
    }
    .search-dropdown-footer {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 8px 14px !important;
      font-size: 11.5px !important;
      color: #94a3b8 !important;
      border-top: 1px solid #f1f5f9 !important;
      background: #f8fafc !important;
      border-radius: 0 0 10px 10px !important;
      margin-top: 4px !important;
    }
    .search-dropdown-item {
      display: flex !important;
      flex-direction: row !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 10px 14px !important;
      border-radius: 10px !important;
      border: 1px solid transparent !important;
      text-decoration: none !important;
      color: inherit !important;
      transition: all 0.15s ease !important;
      cursor: pointer !important;
      background: #ffffff !important;
    }
    .search-dropdown-item:hover,
    .search-dropdown-item.active {
      background: #f0f9ff !important;
      border-color: #bae6fd !important;
      transform: translateX(3px) !important;
    }
    .search-dropdown-item .item-main {
      display: flex !important;
      flex-direction: column !important;
      gap: 3px !important;
      flex: 1 !important;
      min-width: 0 !important;
    }
    .search-dropdown-item .item-title {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      font-size: 14.5px !important;
      font-weight: 600 !important;
      color: #0f172a !important;
      flex-wrap: wrap !important;
    }
    .search-dropdown-item .item-phone-badge {
      display: inline-flex !important;
      align-items: center !important;
      gap: 4px !important;
      font-size: 12.5px !important;
      font-weight: 700 !important;
      padding: 2px 9px !important;
      border-radius: 9999px !important;
      background: #e0f2fe !important;
      color: #0284c7 !important;
      border: 1px solid #bae6fd !important;
    }
    .search-dropdown-item .item-code-badge {
      font-size: 11.5px !important;
      padding: 1px 7px !important;
      border-radius: 4px !important;
      background: #f1f5f9 !important;
      color: #475569 !important;
      font-family: monospace !important;
      font-weight: 600 !important;
    }
    .search-dropdown-item .item-sub {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      font-size: 12.5px !important;
      color: #64748b !important;
      flex-wrap: wrap !important;
    }
    .search-dropdown-item .item-meta {
      display: flex !important;
      flex-direction: column !important;
      align-items: flex-end !important;
      gap: 3px !important;
      margin-left: 12px !important;
      flex-shrink: 0 !important;
    }
    .search-dropdown-item .item-amount {
      font-size: 14.5px !important;
      font-weight: 700 !important;
      color: #0284c7 !important;
    }
    .search-dropdown-item .item-action-pill {
      font-size: 11px !important;
      padding: 2px 7px !important;
      border-radius: 4px !important;
      background: #0284c7 !important;
      color: #fff !important;
      font-weight: 600 !important;
    }
    .search-dropdown-empty {
      padding: 24px 16px !important;
      text-align: center !important;
      color: #64748b !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      gap: 6px !important;
    }
    .search-highlight {
      background: #fef08a !important;
      color: #854d0e !important;
      padding: 0 2px !important;
      border-radius: 3px !important;
      font-weight: 700 !important;
    }
    .bill-result-card {
      background: #fff;
      border-radius: 12px;
      border: 1px solid var(--border);
      padding: 24px;
      box-shadow: var(--shadow);
      margin-bottom: 30px;
    }
    .quick-examples {
      margin-top: 10px;
      font-size: 13px;
      color: #64748b;
    }
    .quick-examples a {
      color: #0284c7;
      text-decoration: underline;
      cursor: pointer;
      margin: 0 4px;
    }
    .service-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
      gap: 20px;
      margin-top: 24px;
    }
  </style>
</head>
<body>
  <div class="app-layout">
    <!-- Global Persistent Sidebar with Real URL Routes -->
    <?php renderAppSidebar(($currentUser && ($currentUser['role'] ?? '') === 'member') ? 'member_bill' : 'citizen'); ?>

    <!-- Main Content Area -->
    <main class="main-content">
      <?php renderAppTopBar('บริการข้อมูลและตรวจสอบค่าน้ำออนไลน์', 'ระบบตรวจสอบค่าน้ำและบริการตนเองสำหรับประชาชน (Public Citizen Portal)'); ?>
    
    <!-- Hero Banner -->
    <div class="citizen-hero-box">
      <span style="background: rgba(255,255,255,0.2); padding: 4px 14px; border-radius: 9999px; font-size: 13.5px; font-weight: 600;">
        👥 ศูนย์บริการประชาชนและสมาชิกผู้ใช้น้ำ (Public Portal)
      </span>
      <h1 style="margin-top: 12px;">บริการข้อมูลและตรวจสอบยอดค่าน้ำประปาออนไลน์</h1>
      <p style="opacity: 0.9; max-width: 650px; margin: 0 auto; font-size: 14.5px;">
        อำนวยความสะดวกแก่สมาชิกผู้ใช้น้ำในการตรวจสอบเลขมิเตอร์ ยอดค่าน้ำประจำงวด ตรวจสอบหนี้ค้างชำระ 
        และสแกนชำระผ่าน PromptPay QR Code ได้ตลอด 24 ชั่วโมง
      </p>
    </div>

    <!-- Search Box -->
    <div class="search-card">
      <div class="citizen-search-row">
        <div class="search-input-wrapper">
          <input type="text" id="citizen-search-input" autocomplete="off" placeholder="พิมพ์เบอร์โทรศัพท์ (เช่น 081-234-5678) หรือรหัสผู้ใช้น้ำ (เช่น WY-001)...">
          <div id="citizen-search-dropdown" class="search-autocomplete-dropdown" style="display: none;"></div>
        </div>
        <button type="button" id="btn-search-bill" class="btn-search">
          <span>🔍</span> ค้นหาบิล
        </button>
      </div>
      <div class="quick-examples" style="text-align: center; margin-top: 12px;">
        💡 <strong>Key หลัก:</strong> ค้นหาด้วยเบอร์โทรศัพท์มือถือ หรือรหัสผู้ใช้น้ำ เช่น <a onclick="setSearchDemo('0812345678')">081-234-5678</a>, <a onclick="setSearchDemo('0810001111')">081-000-1111</a>, <a onclick="setSearchDemo('WY-001')">WY-001</a>
      </div>
    </div>

    <!-- Search Result Area -->
    <div id="bill-result-container" style="display: none;">
      <!-- Dynamically populated bill details -->
    </div>

    <!-- Additional Public Citizen Services (Service Grid) -->
    <div style="margin-top: 40px;" id="citizen-services">
      <h3 style="font-family: 'Prompt', sans-serif; font-size: 20px; color: #0f172a; margin-bottom: 4px;">
        🛠️ บริการประชาชนและคำขอด้านน้ำประปา
      </h3>
      <p style="color: #64748b; font-size: 13.5px; margin-bottom: 16px;">ยื่นคำขอหรือแจ้งเรื่องร้องเรียนออนไลน์ ส่งตรงถึงคณะกรรมการบริหารการประปาหมู่บ้าน</p>

      <div class="service-grid">
        <!-- Service 1: แจ้งท่อแตกรั่ว -->
        <div class="card" style="padding: 20px;">
          <div style="font-size: 28px; margin-bottom: 8px;">🚨</div>
          <h4 style="font-family: 'Prompt', sans-serif; font-size: 16px; margin-bottom: 6px;">แจ้งเหตุท่อแตกรั่ว / น้ำไม่ไหล</h4>
          <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 14px;">พบเห็นน้ำรั่วไหลริมทาง ท่อเมนแตก หรือน้ำประปาไม่ไหลในซอย แจ้งเจ้าหน้าที่เข้าตรวจสอบด่วน</p>
          <button class="btn btn-outline" style="width: 100%;" onclick="openServiceModal('แจ้งท่อแตกรั่ว / น้ำไม่ไหล')">📝 กรอกแบบฟอร์มแจ้งเหตุ</button>
        </div>

        <!-- Service 2: ขอติดตั้งมิเตอร์ใหม่ -->
        <div class="card" style="padding: 20px;">
          <div style="font-size: 28px; margin-bottom: 8px;">🚰</div>
          <h4 style="font-family: 'Prompt', sans-serif; font-size: 16px; margin-bottom: 6px;">ขอติดตั้งมาตรวัดน้ำรายใหม่</h4>
          <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 14px;">สร้างบ้านใหม่หรือย้ายเข้า ต้องการขอต่อท่อเมนและติดตั้งมิเตอร์น้ำ ค่าธรรมเนียมตามมติชุมชน 1,500 บาท</p>
          <button class="btn btn-outline" style="width: 100%;" onclick="openServiceModal('ยื่นขอติดตั้งมาตรวัดน้ำรายใหม่')">📋 ยื่นคำร้องขอติดตั้ง</button>
        </div>

        <!-- Service 3: ตรวจสอบมาตรวัดชำรุด -->
        <div class="card" style="padding: 20px;">
          <div style="font-size: 28px; margin-bottom: 8px;">⚙️</div>
          <h4 style="font-family: 'Prompt', sans-serif; font-size: 16px; margin-bottom: 6px;">แจ้งมาตรวัดน้ำชำรุด / ผิดปกติ</h4>
          <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin-bottom: 14px;">ตัวเลขไม่เดิน หน้าปัดฝ้า มีน้ำซึมออกจากตัวมิเตอร์ หรือค่าน้ำพุ่งสูงผิดปกติ แจ้งให้ช่างมาทดสอบ</p>
          <button class="btn btn-outline" style="width: 100%;" onclick="openServiceModal('แจ้งมาตรวัดน้ำชำรุด')">🔧 แจ้งตรวจเช็กมิเตอร์</button>
        </div>
      </div>

      <!-- Ticket Tracker Section -->
      <div class="card" style="margin-top: 24px; padding: 24px; border-left: 4px solid #0284c7;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px;">
          <div>
            <h4 style="font-family: 'Prompt', sans-serif; font-size: 16px; color: #0f172a; margin: 0 0 4px 0;">🔍 ติดตามสถานะคำร้องแจ้งซ่อม</h4>
            <span style="font-size: 13px; color: #64748b;">กรอกเบอร์โทรศัพท์ที่ใช้แจ้ง หรือรหัสคำร้อง (เช่น TK-2567-0801) เพื่อตรวจสอบความคืบหน้า</span>
          </div>
          <div style="display: flex; gap: 8px;">
            <input type="text" id="track-ticket-input" class="form-input" placeholder="พิมพ์เบอร์โทร หรือรหัส TK-..." style="min-width: 240px;">
            <button type="button" class="btn btn-primary" onclick="trackServiceTickets()">ค้นหาคำร้อง</button>
          </div>
        </div>
        <div id="ticket-track-results" style="display: none;">
          <div class="table-responsive">
            <table class="table" style="font-size: 13.5px;">
              <thead>
                <tr>
                  <th width="130">รหัสคำร้อง</th>
                  <th>วันที่แจ้ง</th>
                  <th>ผู้แจ้ง</th>
                  <th>ประเภทคำร้อง</th>
                  <th>สถานที่ / จุดสังเกต</th>
                  <th width="80" class="text-center">รูปถ่าย</th>
                  <th width="120" class="text-center">สถานะ</th>
                  <th>บันทึกจากเจ้าหน้าที่</th>
                </tr>
              </thead>
              <tbody id="ticket-track-tbody">
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    </main>
  </div> <!-- /.app-layout -->

  <!-- Service Request Modal -->
  <div class="modal" id="service-request-modal">
    <div class="modal-dialog" style="max-width: 520px;">
      <div class="modal-content">
        <div class="modal-header">
          <h3 id="service-modal-title" style="font-size: 16px; font-weight: 700;">แบบฟอร์มคำร้องออนไลน์</h3>
          <button class="modal-close" onclick="closeModal('service-request-modal')">&times;</button>
        </div>
        <div class="modal-body">
          <form id="service-req-form" onsubmit="handleServiceSubmit(event)">
            <input type="hidden" id="form-service-topic">
            <div class="form-group" style="margin-bottom: 12px;">
              <label class="form-label">ชื่อ - นามสกุล ผู้แจ้ง:</label>
              <input type="text" class="form-input" id="req-name" required placeholder="เช่น นายสมใจ รักถิ่น">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
              <label class="form-label">เบอร์โทรศัพท์ที่ติดต่อได้:</label>
              <input type="text" class="form-input" id="req-phone" required placeholder="เช่น 081-xxxxxxx">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
              <label class="form-label">คุ้ม / โซน:</label>
              <select class="form-select" id="req-zone">
                <option value="โซน 1 วังยางเหนือ">โซน 1 วังยางเหนือ</option>
                <option value="โซน 2 วังยางกลาง">โซน 2 วังยางกลาง</option>
                <option value="โซน 3 วังยางใต้">โซน 3 วังยางใต้</option>
              </select>
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
              <label class="form-label">สถานที่ / จุดสังเกต (บ้านเลขที่ / ซอย):</label>
              <input type="text" class="form-input" id="req-location" required placeholder="เช่น หน้าบ้านเลขที่ 25 ซอยวัดเหนือ">
            </div>
            <div class="form-group" style="margin-bottom: 12px;">
              <label class="form-label">รายละเอียดเพิ่มเติม:</label>
              <textarea class="form-input" id="req-details" rows="3" placeholder="ระบุรายละเอียดอาการที่พบ..."></textarea>
            </div>
            <div class="form-group" style="margin-bottom: 14px;">
              <label class="form-label">📸 แนบรูปถ่ายจุดเกิดเหตุ (ท่อแตก / น้ำรั่ว / หน้าปัดมิเตอร์):</label>
              <input type="file" id="req-photo" class="form-input" accept="image/*" style="padding: 6px 10px;" onchange="previewTicketImage(this)">
              <div id="photo-preview-container" style="display: none; margin-top: 8px; text-align: center; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px dashed #cbd5e1;">
                <img id="photo-preview-img" src="" alt="ตัวอย่างรูปภาพ" style="max-height: 140px; max-width: 100%; border-radius: 6px; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                <button type="button" onclick="clearPhotoPreview()" style="display: block; margin: 6px auto 0; font-size: 12.5px; color: #dc2626; background: none; border: none; cursor: pointer; font-weight: 600;">❌ ลบรูปภาพ</button>
              </div>
              <span style="font-size: 12px; color: #64748b; display: block; margin-top: 4px;">* รองรับไฟล์ภาพ JPG, PNG, WEBP ขนาดไม่เกิน 5 MB</span>
            </div>
            <div class="form-actions text-right">
              <button type="button" class="btn btn-outline" onclick="closeModal('service-request-modal')">ยกเลิก</button>
              <button type="submit" class="btn btn-primary" id="btn-submit-ticket">🚀 ส่งคำร้องเข้าระบบ</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts -->
  <script>
    const currentCycleCode = '<?php echo $currentCycle['cycle_code']; ?>';
    let cachedReadings = null;
    let activeDropdownIndex = -1;

    async function getReadings() {
      if (cachedReadings) return cachedReadings;
      try {
        const res = await fetch(`api/readings.php?cycle=${currentCycleCode}`);
        if (!res.ok) throw new Error('API Error');
        const data = await res.json();
        cachedReadings = data.readings || [];
        return cachedReadings;
      } catch (err) {
        console.error('Failed to load readings cache:', err);
        return [];
      }
    }

    const searchInput = document.getElementById('citizen-search-input');
    const searchDropdown = document.getElementById('citizen-search-dropdown');

    function closeSearchDropdown() {
      if (searchDropdown) {
        searchDropdown.style.display = 'none';
        searchDropdown.innerHTML = '';
        activeDropdownIndex = -1;
      }
    }

    function formatPhone(phone) {
      if (!phone) return '-';
      const clean = String(phone).replace(/\D/g, '');
      if (clean.length === 10) {
        return clean.replace(/(\d{3})(\d{3})(\d{4})/, '$1-$2-$3');
      } else if (clean.length === 9) {
        return clean.replace(/(\d{2})(\d{3})(\d{4})/, '$1-$2-$3');
      }
      return phone;
    }

    function escapeHtml(text) {
      if (!text) return '';
      return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function highlightMatch(text, kw) {
      if (!text) return '';
      const escapedText = escapeHtml(text);
      if (!kw) return escapedText;
      const escapedKw = escapeHtml(kw).trim();
      if (!escapedKw) return escapedText;
      try {
        const regex = new RegExp(`(${escapedKw.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return escapedText.replace(regex, '<mark class="search-highlight">$1</mark>');
      } catch (e) {
        return escapedText;
      }
    }

    async function handleSearchInput() {
      const kwRaw = searchInput.value.trim();
      if (!kwRaw) {
        closeSearchDropdown();
        return;
      }

      const kwLower = kwRaw.toLowerCase();
      const kwDigits = kwRaw.replace(/\D/g, '');

      const readings = await getReadings();

      // Phone Number is the Primary Key across the entire system
      const matches = readings.map(r => {
        let score = 0;
        const phoneRaw = r.phone || '';
        const phoneDigits = phoneRaw.replace(/\D/g, '');
        const phoneFormatted = formatPhone(phoneRaw);
        const fullName = `${r.first_name || ''} ${r.last_name || ''}`.trim();
        const custCode = r.customer_code || '';
        const houseNo = r.house_no || '';

        // 1. Phone number matching has the highest score
        if (kwDigits && phoneDigits.includes(kwDigits)) {
          score += (phoneDigits === kwDigits ? 200 : 100);
        }
        if (phoneFormatted.toLowerCase().includes(kwLower)) {
          score += 80;
        }
        // 2. Customer code matching
        if (custCode.toLowerCase().includes(kwLower)) {
          score += (custCode.toLowerCase() === kwLower ? 90 : 40);
        }
        // 3. House number matching
        if (houseNo.toLowerCase().includes(kwLower)) {
          score += 30;
        }
        // 4. Name matching
        if (fullName.toLowerCase().includes(kwLower)) {
          score += 20;
        }
        if (r.meter_serial && r.meter_serial.toLowerCase().includes(kwLower)) {
          score += 15;
        }

        return { reading: r, score, phoneFormatted, fullName };
      })
      .filter(item => item.score > 0)
      .sort((a, b) => b.score - a.score)
      .slice(0, 8);

      if (!searchDropdown) return;

      if (matches.length === 0) {
        searchDropdown.innerHTML = `
          <div class="search-dropdown-empty">
            <span style="font-size: 26px;">🔍</span>
            <div style="font-weight: 600; color: #475569;">ไม่พบข้อมูลผู้ใช้น้ำที่ตรงกับ "<strong>${escapeHtml(kwRaw)}</strong>"</div>
            <small style="color: #94a3b8;">ลองค้นหาด้วยเบอร์โทรศัพท์ (เช่น 081-234-5678), รหัสผู้ใช้น้ำ หรือบ้านเลขที่</small>
          </div>
        `;
        searchDropdown.style.display = 'flex';
        activeDropdownIndex = -1;
        return;
      }

      const headerHtml = `
        <div class="search-dropdown-header">
          <span>📋 ผลการค้นหา (${matches.length} รายการ) — ค้นหาด้วยเบอร์โทรศัพท์</span>
          <span style="font-size: 11px; font-weight: normal; color: #0284c7;">คลิกเพื่อเปิดดูบิล</span>
        </div>
      `;

      const itemsHtml = matches.map((item, idx) => {
        const r = item.reading;
        const isPaid = (r.payment_status === 'PAID');
        const total = parseFloat(r.grand_total || 0).toLocaleString('th-TH', { minimumFractionDigits: 2 });
        const targetUrl = `portal_citizen.php?phone=${encodeURIComponent(r.phone || '')}&customer=${encodeURIComponent(r.customer_code)}`;
        return `
          <a href="${targetUrl}" class="search-dropdown-item" data-code="${escapeHtml(r.customer_code)}" data-phone="${escapeHtml(r.phone || '')}" data-index="${idx}">
            <div class="item-main">
              <div class="item-title">
                <span class="item-phone-badge">📞 ${highlightMatch(item.phoneFormatted, kwRaw)}</span>
                <span class="item-name">${highlightMatch(item.fullName, kwRaw)}</span>
                <span class="item-code-badge">${highlightMatch(r.customer_code, kwRaw)}</span>
              </div>
              <div class="item-sub">
                <span>🏠 บ้านเลขที่: <strong>${highlightMatch(r.house_no, kwRaw)}</strong></span>
                <span>•</span>
                <span>${escapeHtml(r.zone || '')}</span>
                ${r.meter_serial ? `<span>• มาตร: ${highlightMatch(r.meter_serial, kwRaw)}</span>` : ''}
              </div>
            </div>
            <div class="item-meta">
              <span class="item-amount">${total} ฿</span>
              <div style="display: flex; align-items: center; gap: 6px;">
                <span class="badge ${isPaid ? 'badge-paid' : 'badge-unpaid'}" style="font-size: 11px; padding: 2px 8px; border-radius: 9999px;">
                  ${isPaid ? '✅ ชำระแล้ว' : '⏳ ค้างชำระ'}
                </span>
                <span class="item-action-pill">ดูบิล &rarr;</span>
              </div>
            </div>
          </a>
        `;
      }).join('');

      const footerHtml = `
        <div class="search-dropdown-footer">
          <span>💡 ใช้ลูกศร <strong>↑ ↓</strong> เพื่อเลือก และกด <strong>Enter</strong> เพื่อเปิดดูบิล</span>
          <span>⚡ ข้อมูลประจำงวด ${currentCycleCode}</span>
        </div>
      `;

      searchDropdown.innerHTML = headerHtml + itemsHtml + footerHtml;
      searchDropdown.style.display = 'flex';
      activeDropdownIndex = -1;

      searchDropdown.querySelectorAll('.search-dropdown-item').forEach(item => {
        item.addEventListener('click', (e) => {
          e.preventDefault();
          e.stopPropagation();
          const phone = item.getAttribute('data-phone');
          const code = item.getAttribute('data-code');
          const target = phone || code;
          if (target) {
            searchInput.value = target;
            if (window.history && window.history.pushState) {
              window.history.pushState(null, '', `?phone=${encodeURIComponent(phone || '')}&customer=${encodeURIComponent(code || '')}`);
            }
            closeSearchDropdown();
            performCitizenSearch(target);
          }
        });
      });
    }

    function updateActiveDropdownItem(items) {
      items.forEach((item, idx) => {
        if (idx === activeDropdownIndex) {
          item.classList.add('active');
          item.scrollIntoView({ block: 'nearest' });
        } else {
          item.classList.remove('active');
        }
      });
    }

    searchInput?.addEventListener('input', handleSearchInput);
    searchInput?.addEventListener('focus', () => {
      if (searchInput.value.trim().length > 0) {
        handleSearchInput();
      }
    });

    searchInput?.addEventListener('keydown', (e) => {
      const items = searchDropdown?.querySelectorAll('.search-dropdown-item');
      if (searchDropdown && searchDropdown.style.display !== 'none' && items && items.length > 0) {
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          activeDropdownIndex = (activeDropdownIndex + 1) % items.length;
          updateActiveDropdownItem(items);
          return;
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          activeDropdownIndex = (activeDropdownIndex - 1 + items.length) % items.length;
          updateActiveDropdownItem(items);
          return;
        } else if (e.key === 'Enter') {
          if (activeDropdownIndex >= 0 && items[activeDropdownIndex]) {
            e.preventDefault();
            items[activeDropdownIndex].click();
            return;
          }
        } else if (e.key === 'Escape') {
          closeSearchDropdown();
          return;
        }
      }

      if (e.key === 'Enter') {
        closeSearchDropdown();
        performCitizenSearch();
      }
    });

    document.addEventListener('click', (e) => {
      if (!e.target.closest('.search-input-wrapper')) {
        closeSearchDropdown();
      }
    });

    document.getElementById('btn-search-bill')?.addEventListener('click', () => {
      closeSearchDropdown();
      performCitizenSearch();
    });

    window.setSearchDemo = function(term) {
      if (searchInput) {
        searchInput.value = term;
        searchInput.focus();
        handleSearchInput();
      }
    };

    async function performCitizenSearch(exactQuery) {
      closeSearchDropdown();
      const kw = (exactQuery !== undefined ? exactQuery : document.getElementById('citizen-search-input').value).trim();
      const container = document.getElementById('bill-result-container');
      if (!kw) {
        alert('กรุณากรอกเบอร์โทรศัพท์, รหัสผู้ใช้น้ำ หรือบ้านเลขที่');
        return;
      }

      container.style.display = 'block';
      container.innerHTML = '<div style="text-align: center; padding: 30px; color: #64748b;">⏳ กำลังค้นหาข้อมูลบิลค่าน้ำ...</div>';
      container.scrollIntoView({ behavior: 'smooth', block: 'start' });

      try {
        const readings = await getReadings();
        const kwLower = kw.toLowerCase();
        const kwDigits = kw.replace(/\D/g, '');

        let match = null;
        if (kwDigits && kwDigits.length >= 4) {
          match = readings.find(r => r.phone && r.phone.replace(/\D/g, '').includes(kwDigits));
        }
        if (!match) {
          match = readings.find(r => 
            (r.customer_code && r.customer_code.toLowerCase().includes(kwLower)) ||
            (r.house_no && r.house_no.toLowerCase().includes(kwLower)) ||
            (r.first_name && r.first_name.toLowerCase().includes(kwLower)) ||
            (r.last_name && r.last_name.toLowerCase().includes(kwLower)) ||
            (r.meter_serial && r.meter_serial.toLowerCase().includes(kwLower))
          );
        }

        if (!match) {
          container.innerHTML = `
            <div class="bill-result-card" style="text-align: center; border-color: #fee2e2; background: #fff5f5;">
              <span style="font-size: 36px;">❌</span>
              <h4 style="color: #dc2626; margin-top: 8px;">ไม่พบข้อมูลบิลค่าน้ำที่ตรงกับ "${escapeHtml(kw)}"</h4>
              <p style="color: #64748b; font-size: 13.5px;">กรุณาตรวจสอบเบอร์โทรศัพท์ หรือรหัสผู้ใช้น้ำอีกครั้ง หรือติดต่อคณะกรรมการประปาหมู่บ้าน</p>
            </div>
          `;
          return;
        }

        const isPaid = (match.payment_status === 'PAID');
        const grandTotal = parseFloat(match.grand_total || 0);

        container.innerHTML = `
          <div class="bill-result-card" style="border-top: 4px solid ${isPaid ? '#16a34a' : '#0284c7'};">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 20px;">
              <div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 8px;">
                  <span style="background: #e0f2fe; color: #0284c7; font-size: 13.5px; font-weight: 700; padding: 4px 12px; border-radius: 6px; border: 1px solid #bae6fd;">
                    📞 เบอร์โทรศัพท์ (Key หลัก): ${formatPhone(match.phone)}
                  </span>
                  <span style="background: #f1f5f9; color: #475569; font-size: 13px; font-weight: 700; padding: 4px 10px; border-radius: 6px;">
                    รหัสผู้ใช้น้ำ: ${match.customer_code}
                  </span>
                </div>
                <h2 style="font-family: 'Prompt', sans-serif; font-size: 22px; color: #0f172a; margin: 4px 0 2px 0;">
                  👤 ${match.first_name} ${match.last_name}
                </h2>
                <div style="font-size: 14px; color: #64748b;">
                  🏠 บ้านเลขที่ ${match.house_no} | โซน: <strong>${match.zone}</strong> | มาตรเลขที่: <strong>${match.meter_serial || '-'}</strong>
                </div>
              </div>
              <div style="text-align: right;">
                <span class="badge ${isPaid ? 'badge-paid' : 'badge-unpaid'}" style="font-size: 14px; padding: 6px 16px; border-radius: 9999px;">
                  ${isPaid ? '✅ ชำระเงินเรียบร้อยแล้ว' : '⏳ ยังไม่ได้ชำระเงิน'}
                </span>
                <div style="font-size: 13px; color: #64748b; margin-top: 4px;">ประจำงวด: <strong>${currentCycleCode}</strong></div>
              </div>
            </div>

            <!-- Meter Reading Data Breakdown -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
              <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; text-align: center;">
                <span style="font-size: 13px; color: #64748b; display: block;">เลขมิเตอร์ครั้งก่อน</span>
                <strong style="font-size: 20px; color: #334151;">${parseFloat(match.previous_reading).toFixed(1)}</strong>
              </div>
              <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; text-align: center;">
                <span style="font-size: 13px; color: #64748b; display: block;">เลขมิเตอร์ครั้งหลัง</span>
                <strong style="font-size: 20px; color: #334151;">${parseFloat(match.current_reading).toFixed(1)}</strong>
              </div>
              <div style="background: #e0f2fe; border: 1px solid #bae6fd; border-radius: 8px; padding: 14px; text-align: center;">
                <span style="font-size: 13.5px; color: #0369a1; display: block; font-weight: 600;">ปริมาณการใช้น้ำ</span>
                <strong style="font-size: 22px; color: #0284c7;">${parseFloat(match.units_used).toFixed(1)} ลบ.ม.</strong>
              </div>
              <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 14px; text-align: center;">
                <span style="font-size: 13.5px; color: #991b1b; display: block; font-weight: 600;">ยอดรวมสุทธิที่ต้องชำระ</span>
                <strong style="font-size: 24px; color: #dc2626;">${grandTotal.toFixed(2)} บาท</strong>
              </div>
            </div>

            <!-- PromptPay QR Code Payment Channel -->
            <div style="background: #f0fdf4; border: 1px dashed #22c55e; border-radius: 10px; padding: 20px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
              <div style="display: flex; align-items: center; gap: 16px;">
                <img src="https://promptpay.io/0812345678/${grandTotal.toFixed(2)}.png" alt="PromptPay QR" style="width: 120px; height: 120px; border-radius: 8px; background: #fff; border: 1px solid #bbf7d0; padding: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                <div>
                  <div style="display: inline-block; background: #15803d; color: #fff; font-size: 13px; font-weight: 700; padding: 3px 10px; border-radius: 4px; margin-bottom: 4px;">
                    📱 สแกนจ่ายผ่าน Mobile Banking ได้ทุกธนาคาร
                  </div>
                  <div style="font-size: 14px; font-weight: 700; color: #166534; margin-bottom: 4px;">
                    พร้อมเพย์กองทุนประปาหมู่บ้านวังยาง (ธนาคาร ธ.ก.ส.)
                  </div>
                  <div style="font-size: 13.5px; color: #374151; line-height: 1.5;">
                    หมายเลขพร้อมเพย์: <strong>081-234-5678</strong><br>
                    ยอดชำระตามบิล: <strong style="color: #b91c1c; font-size: 16px;">${grandTotal.toFixed(2)} บาท</strong>
                  </div>
                </div>
              </div>
              <div style="text-align: right;">
                <button type="button" class="btn btn-outline" onclick="window.print()" style="display: inline-flex; align-items: center; gap: 6px;">
                  🖨️ พิมพ์ใบแจ้งยอด / สลิป
                </button>
              </div>
            </div>

            <!-- Historical Usage Visual Chart -->
            <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin-top: 14px;">
              <h4 style="font-family: 'Prompt', sans-serif; font-size: 15px; margin-bottom: 12px; color: #0f172a;">
                📊 ประวัติการใช้น้ำย้อนหลัง 6 เดือนของบ้านเลขที่ ${match.house_no}
              </h4>
              <div style="height: 180px; position: relative;">
                <canvas id="userHistoryChart"></canvas>
              </div>
            </div>

          </div>
        `;

        // Render Chart.js for this user
        setTimeout(() => {
          const ctx = document.getElementById('userHistoryChart')?.getContext('2d');
          if (ctx) {
            new Chart(ctx, {
              type: 'line',
              data: {
                labels: ['มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.'],
                datasets: [{
                  label: 'หน่วยน้ำที่ใช้ (ลบ.ม.)',
                  data: [18.0, 24.5, 21.0, 19.5, 17.0, parseFloat(match.units_used)],
                  borderColor: '#0284c7',
                  backgroundColor: 'rgba(2, 132, 199, 0.1)',
                  fill: true,
                  tension: 0.3,
                  pointRadius: 5
                }]
              },
              options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                  y: { beginAtZero: true, title: { display: true, text: 'ลบ.ม.' } }
                }
              }
            });
          }
        }, 100);

      } catch (err) {
        console.error(err);
        container.innerHTML = '<div style="color: #dc2626; text-align: center; padding: 20px;">เกิดข้อผิดพลาดในการโหลดข้อมูล</div>';
      }
    }

    function openServiceModal(topic) {
      document.getElementById('service-modal-title').textContent = topic;
      document.getElementById('form-service-topic').value = topic;
      document.getElementById('service-request-modal').classList.add('show');
    }

    function closeModal(id) {
      document.getElementById(id).classList.remove('show');
    }

    function previewTicketImage(input) {
      const container = document.getElementById('photo-preview-container');
      const img = document.getElementById('photo-preview-img');
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
          img.src = e.target.result;
          container.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
      } else {
        clearPhotoPreview();
      }
    }

    function clearPhotoPreview() {
      const input = document.getElementById('req-photo');
      const container = document.getElementById('photo-preview-container');
      const img = document.getElementById('photo-preview-img');
      if (input) input.value = '';
      if (img) img.src = '';
      if (container) container.style.display = 'none';
    }

    async function handleServiceSubmit(e) {
      e.preventDefault();
      const btn = document.getElementById('btn-submit-ticket');
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = '⏳ กำลังส่งข้อมูลและอัปโหลดรูปภาพ...';

      const formData = new FormData();
      formData.append('issue_type', document.getElementById('form-service-topic').value);
      formData.append('reporter_name', document.getElementById('req-name').value.trim());
      formData.append('phone', document.getElementById('req-phone').value.trim());
      formData.append('zone', document.getElementById('req-zone').value);
      formData.append('house_no', document.getElementById('req-location').value.trim());
      formData.append('description', document.getElementById('req-details').value.trim());

      const photoInput = document.getElementById('req-photo');
      if (photoInput && photoInput.files && photoInput.files[0]) {
        formData.append('photo', photoInput.files[0]);
      }

      try {
        const res = await fetch('api/tickets.php', {
          method: 'POST',
          body: formData
        });
        const data = await res.json();
        if (res.ok && data.success) {
          let lineNotice = data.line_notified ? '\n📲 ระบบส่งการแจ้งเตือนด่วนเข้า LINE เจ้าหน้าที่แล้ว' : '';
          alert(`✅ ${data.message}${lineNotice}`);
          closeModal('service-request-modal');
          e.target.reset();
          clearPhotoPreview();

          // Auto-track the newly submitted ticket
          const trackInput = document.getElementById('track-ticket-input');
          if (trackInput) {
            trackInput.value = formData.get('phone');
            trackServiceTickets();
          }
        } else {
          alert('❌ ไม่สามารถส่งคำร้องได้: ' + (data.error || 'กรุณาลองใหม่อีกครั้ง'));
        }
      } catch (err) {
        console.error(err);
        alert('❌ เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์');
      } finally {
        btn.disabled = false;
        btn.textContent = originalText;
      }
    }

    async function trackServiceTickets() {
      const kw = document.getElementById('track-ticket-input').value.trim();
      const resultsDiv = document.getElementById('ticket-track-results');
      const tbody = document.getElementById('ticket-track-tbody');

      if (!kw) {
        alert('กรุณากรอกเบอร์โทรศัพท์ หรือรหัสคำร้องเพื่อค้นหา');
        return;
      }

      tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="padding: 16px;">กำลังค้นหาข้อมูล...</td></tr>';
      resultsDiv.style.display = 'block';

      try {
        const res = await fetch(`api/tickets.php?search=${encodeURIComponent(kw)}`);
        const data = await res.json();
        const tickets = data.tickets || [];

        if (tickets.length === 0) {
          tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="padding: 20px; color: #64748b;">ไม่พบข้อมูลคำร้องที่ตรงกับคำค้นหา</td></tr>';
          return;
        }

        tbody.innerHTML = tickets.map(t => {
          let badgeHtml = '';
          if (t.status === 'PENDING') {
            badgeHtml = '<span class="badge" style="background: #fef3c7; color: #b45309; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">🟡 รอดำเนินการ</span>';
          } else if (t.status === 'IN_PROGRESS') {
            badgeHtml = '<span class="badge" style="background: #e0f2fe; color: #0284c7; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">🔵 กำลังตรวจสอบ/ซ่อม</span>';
          } else {
            badgeHtml = '<span class="badge" style="background: #d1fae5; color: #047857; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">🟢 แก้ไขเรียบร้อย</span>';
          }

          const photoHtml = t.photo_url 
            ? `<a href="${t.photo_url}" target="_blank" title="คลิกดูภาพขยาย"><img src="${t.photo_url}" style="width: 44px; height: 44px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1; cursor: pointer;"></a>`
            : '<span class="text-muted" style="font-size: 12px;">ไม่มี</span>';

          return `
            <tr>
              <td><strong>${t.ticket_no}</strong></td>
              <td>${t.created_at ? t.created_at.substring(0, 16) : '-'}</td>
              <td>${t.reporter_name} (${t.phone})</td>
              <td>${t.issue_type}</td>
              <td>${t.house_no} (${t.zone})</td>
              <td class="text-center">${photoHtml}</td>
              <td class="text-center">${badgeHtml}</td>
              <td style="color: #475569;">${t.repair_notes || (t.status === 'RESOLVED' ? 'ซ่อมแซมเสร็จสมบูรณ์' : 'อยู่ระหว่างประสานงานช่าง')}</td>
            </tr>
          `;
        }).join('');

      } catch (err) {
        console.error(err);
        tbody.innerHTML = '<tr><td colspan="8" class="text-center" style="padding: 20px; color: #dc2626;">เกิดข้อผิดพลาดในการดึงข้อมูลคำร้อง</td></tr>';
      }
    }

    // Auto-search if phone or customer param is present in URL or logged-in member
    window.addEventListener('DOMContentLoaded', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const searchTarget = urlParams.get('phone') || urlParams.get('customer') || '<?php echo ($currentUser && ($currentUser['role'] ?? '') === 'member') ? addslashes($currentUser['phone'] ?? $currentUser['customer_code'] ?? '') : ''; ?>';
      if (searchTarget) {
        const input = document.getElementById('citizen-search-input');
        if (input) {
          input.value = searchTarget;
          performCitizenSearch(searchTarget);
        }
      }
    });
  </script>
</body>
</html>

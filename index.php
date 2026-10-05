<?php
/**
 * หน้าหลัก (Portal / Landing Page): ระบบบริหารจัดการการประปาหมู่บ้านวังยาง
 * โครงการพัฒนาระบบสารสนเทศและฐานข้อมูล (งานกลุ่ม)
 */
require_once __DIR__ . '/sidebar.php';
$currentUser = getCurrentUser();

$host = 'localhost';
$db_name = 'db_city_water_supply';
$username = 'root';
$password = '';

$stats = [
    'total_customers' => 0,
    'total_zones' => 3,
    'current_rate' => 7.00,
    'maintenance_fee' => 10.00,
    'current_cycle' => '8-2567',
    'total_units' => 0,
    'accumulated_balance' => 145200.00
];

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // ดึงจำนวนผู้ใช้น้ำทั้งหมด
    $cStmt = $pdo->query("SELECT COUNT(*) as cnt FROM customers WHERE status = 'ACTIVE'");
    $stats['total_customers'] = (int)($cStmt->fetch()['cnt'] ?? 0);

    // ดึงจำนวนโซน
    $zStmt = $pdo->query("SELECT COUNT(DISTINCT zone) as cnt FROM customers");
    $stats['total_zones'] = (int)($zStmt->fetch()['cnt'] ?? 0);

    // ดึงอัตราค่าน้ำปัจจุบัน
    $tStmt = $pdo->query("SELECT rate_per_unit, maintenance_fee FROM tariff_rates WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    if ($tariff = $tStmt->fetch()) {
        $stats['current_rate'] = (float)$tariff['rate_per_unit'];
        $stats['maintenance_fee'] = (float)$tariff['maintenance_fee'];
    }

    // ดึงรอบบิลล่าสุด
    $bStmt = $pdo->query("SELECT * FROM billing_cycles ORDER BY id DESC LIMIT 1");
    if ($cycle = $bStmt->fetch()) {
        $stats['current_cycle'] = $cycle['cycle_code'];
        $rStmt = $pdo->prepare("SELECT SUM(units_used) as units FROM meter_readings WHERE billing_cycle_id = ?");
        $rStmt->execute([(int)$cycle['id']]);
        $stats['total_units'] = (float)($rStmt->fetch()['units'] ?? 0);
    }
} catch (Exception $e) {
    // ใช้งานค่าเริ่มต้นหากยังไม่ได้เริ่ม MySQL
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ระบบบริหารจัดการ การประปาหมู่บ้านวังยาง | โครงการพัฒนาระบบสารสนเทศ</title>
  <!-- Google Fonts: Prompt & Sarabun -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <?php renderPwaHead(); ?>
  <style>
    /* สไตล์เฉพาะหน้า Portal หน้าหลัก */
    .portal-nav {
      background: #0f172a;
      color: #fff;
      padding: 14px 40px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }
    .portal-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
      color: #fff;
    }
    .portal-brand-logo {
      font-size: 28px;
      background: #0284c7;
      padding: 6px;
      border-radius: 8px;
      line-height: 1;
    }
    .portal-brand-text h2 {
      font-size: 18px;
      font-weight: 700;
      color: #38bdf8;
      margin: 0;
    }
    .portal-brand-text span {
      font-size: 13px;
      color: #94a3b8;
    }
    .portal-menu {
      display: flex;
      gap: 20px;
      align-items: center;
    }
    .portal-menu a {
      color: #cbd5e1;
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: color 0.15s;
    }
    .portal-menu a:hover {
      color: #38bdf8;
    }
    .btn-dashboard-enter, .btn-portal-login {
      background: #0284c7;
      color: #fff !important;
      padding: 8px 18px;
      border-radius: 6px;
      border: none;
      font-weight: 600 !important;
      font-size: 14px;
      font-family: inherit;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: all 0.2s;
      text-decoration: none;
    }
    .btn-dashboard-enter:hover, .btn-portal-login:hover {
      background: #0369a1;
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgba(2, 132, 199, 0.4);
    }

    /* Hero Section */
    .portal-hero {
      background: linear-gradient(135deg, #075985 0%, #0369a1 50%, #0284c7 100%);
      color: #fff;
      padding: 60px 40px;
      text-align: center;
      position: relative;
    }
    .hero-badge {
      display: inline-block;
      background: rgba(255,255,255,0.15);
      border: 1px solid rgba(255,255,255,0.3);
      padding: 5px 16px;
      border-radius: 9999px;
      font-size: 13px;
      margin-bottom: 16px;
    }
    .portal-hero h1 {
      font-size: 34px;
      font-weight: 700;
      margin-bottom: 12px;
      letter-spacing: -0.5px;
    }
    .portal-hero p {
      font-size: 17px;
      color: #e0f2fe;
      max-width: 750px;
      margin: 0 auto 28px auto;
      line-height: 1.6;
    }

    /* Container */
    .portal-container {
      max-width: 1200px;
      margin: -30px auto 60px auto;
      padding: 0 20px;
      position: relative;
    }

    /* KPI Cards */
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 18px;
      margin-bottom: 36px;
    }
    .kpi-card {
      background: #fff;
      border-radius: 10px;
      padding: 22px 20px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1);
      border: 1px solid #e2e8f0;
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .kpi-icon {
      font-size: 32px;
      width: 56px;
      height: 56px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #e0f2fe;
      color: #0284c7;
    }
    .kpi-content span {
      display: block;
      font-size: 13px;
      color: #64748b;
      font-weight: 500;
    }
    .kpi-content strong {
      font-size: 24px;
      font-weight: 700;
      color: #0f172a;
    }

    /* Section Headers */
    .section-title-wrap {
      text-align: center;
      margin-bottom: 28px;
      margin-top: 40px;
    }
    .section-title-wrap h3 {
      font-size: 24px;
      font-weight: 700;
      color: #0f172a;
    }
    .section-title-wrap p {
      font-size: 14px;
      color: #64748b;
      margin-top: 4px;
    }

    /* Citizen Bill Search Widget */
    .citizen-box {
      background: #fff;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
      border: 2px solid #0284c7;
      margin-bottom: 40px;
    }
    .citizen-search-bar {
      display: flex;
      gap: 12px;
      margin-top: 16px;
      max-width: 650px;
      margin-left: auto;
      margin-right: auto;
    }
    .citizen-search-input {
      flex: 1;
      padding: 12px 18px;
      font-size: 16px;
      border: 2px solid #cbd5e1;
      border-radius: 8px;
      outline: none;
      font-family: inherit;
    }
    .citizen-search-input:focus {
      border-color: #0284c7;
    }
    .btn-search-bill {
      background: #0284c7;
      color: #fff;
      font-size: 15px;
      font-weight: 600;
      border: none;
      border-radius: 8px;
      padding: 0 24px;
      cursor: pointer;
      transition: background 0.15s;
    }
    .btn-search-bill:hover {
      background: #0369a1;
    }

    /* Portal Footer */
    .portal-footer {
      background: #0f172a;
      color: #94a3b8;
      padding: 30px 40px;
      text-align: center;
      font-size: 13px;
      border-top: 1px solid #1e293b;
    }
    .portal-footer strong {
      color: #f1f5f9;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(4px);
      z-index: 2000;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    .modal.show {
      display: flex;
    }
    .modal-dialog {
      background: #fff;
      border-radius: 12px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 20px 25px -5px rgba(0,0,0,0.25);
      overflow: hidden;
      animation: modalFadeIn 0.2s ease-out;
    }
    @keyframes modalFadeIn {
      from { opacity: 0; transform: scale(0.95); }
      to { opacity: 1; transform: scale(1); }
    }
    .modal-header {
      padding: 16px 20px;
      border-bottom: 1px solid #e2e8f0;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-close {
      background: none;
      border: none;
      font-size: 26px;
      cursor: pointer;
      color: #94a3b8;
      line-height: 1;
      transition: color 0.15s;
    }
    .modal-close:hover {
      color: #0f172a;
    }
    .modal-body {
      padding: 20px 24px;
    }
  </style>
</head>
<body>
  <div class="app-layout">
    <!-- Global Persistent Sidebar with Real URL Routes -->
    <?php renderAppSidebar('home'); ?>

    <!-- Main Content Area -->
    <main class="main-content" style="min-height: 100vh; background: #f8fafc;">
      
      <!-- Top Navigation Bar with Authentication Status -->
      <?php renderAppTopBar('ระบบบริการและบริหารจัดการน้ำประปา', 'การประปาหมู่บ้านวังยาง หมู่ที่ 3'); ?>

      <?php if ($currentUser && ($currentUser['role'] ?? '') === 'member'): ?>
      <!-- Personalized Member Welcome Banner -->
      <section style="margin-bottom: 20px; background: #e0f2fe; border: 2px solid #0284c7; border-radius: 12px; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px;">
        <div style="display: flex; align-items: center; gap: 14px;">
          <div style="font-size: 30px; background: #0284c7; color: #fff; width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">👤</div>
          <div>
            <h3 style="margin: 0; font-size: 18px; color: #0369a1; font-weight: 700;">ยินดีต้อนรับคุณ <?php echo htmlspecialchars($currentUser['name']); ?></h3>
            <p style="margin: 2px 0 0 0; font-size: 13.5px; color: #334155;">
              รหัสสมาชิกผู้ใช้น้ำ: <strong><?php echo htmlspecialchars($currentUser['customer_code'] ?? ''); ?></strong> | 
              บ้านเลขที่: <strong><?php echo htmlspecialchars($currentUser['house_no'] ?? ''); ?></strong>
            </p>
          </div>
        </div>
        <a href="portal_citizen.php?customer=<?php echo urlencode($currentUser['customer_code'] ?? ''); ?>" class="btn btn-primary" style="padding: 10px 20px; font-size: 14px; text-decoration: none; border-radius: 6px; font-weight: 600;">
          🧾 ดูบิลค่าน้ำและประวัติการใช้น้ำของฉัน &rarr;
        </a>
      </section>
      <?php endif; ?>

      <!-- Hero Banner -->
      <section class="portal-hero" id="about-section" style="padding: 36px 28px 40px 28px; background: linear-gradient(135deg, #075985 0%, #0284c7 100%); color: #fff; border-radius: 14px; margin-bottom: 24px; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.15);">
        <div class="hero-badge">🏛️ กองทุนน้ำประปาหมู่บ้านวังยาง หมู่ที่ 3</div>
        <h1 style="font-size: 28px; margin-bottom: 8px;">ระบบบริการและบริหารจัดการน้ำประปาชุมชน</h1>
        <p style="font-size: 15px; margin: 0 auto; max-width: 800px; opacity: 0.95; line-height: 1.6;">
          ยินดีต้อนรับสู่ระบบบริการน้ำประปาหมู่บ้านวังยาง อำนวยความสะดวกแก่สมาชิกผู้ใช้น้ำในการตรวจสอบค่าน้ำออนไลน์ 
          พร้อมระบบบริหารจัดการข้อมูลการประปาชุมชนอย่างเป็นระบบ โปร่งใส และเข้าถึงบริการทั้งหมดได้จากแถบเมนูด้านข้าง (Sidebar)
        </p>
        <?php if (!$currentUser): ?>
        <div style="margin-top: 22px; display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
          <a href="register.php" class="btn" style="background: #38bdf8; color: #0f172a; font-weight: 700; padding: 10px 22px; border-radius: 8px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0,0,0,0.2); font-size: 14px;">
            <span>📝</span> ลงทะเบียนสมาชิกผู้ใช้น้ำ / เจ้าหน้าที่
          </a>
          <button type="button" onclick="openLoginModal()" class="btn" style="background: rgba(255,255,255,0.18); color: #fff; border: 1px solid rgba(255,255,255,0.4); font-weight: 600; padding: 10px 20px; border-radius: 8px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; font-size: 14px;">
            <span>🔐</span> เข้าสู่ระบบ
          </button>
        </div>
        <?php endif; ?>
      </section>

      <!-- Main Portal Container -->
      <div class="portal-container" style="max-width: 100%; margin: 0; padding: 0;">
    
    <!-- Live Statistics KPI Grid -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-icon">👥</div>
        <div class="kpi-content">
          <span>ผู้ใช้น้ำในระบบ</span>
          <strong><?php echo $stats['total_customers']; ?> ครัวเรือน</strong>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon">🗺️</div>
        <div class="kpi-content">
          <span>พื้นที่ให้บริการ</span>
          <strong><?php echo $stats['total_zones']; ?> โซนชุมชน</strong>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon">💧</div>
        <div class="kpi-content">
          <span>อัตราค่าน้ำปัจจุบัน</span>
          <strong><?php echo number_format($stats['current_rate'], 2); ?> บาท/หน่วย</strong>
        </div>
      </div>
      <div class="kpi-card">
        <div class="kpi-icon">🏦</div>
        <div class="kpi-content">
          <span>เงินกองทุนสะสม</span>
          <strong style="color: #0284c7;"><?php echo number_format($stats['accumulated_balance'], 2); ?> บาท</strong>
        </div>
      </div>
    </div>

    <!-- Citizen Bill Search Widget -->
    <section id="citizen-section" class="citizen-box" style="margin-bottom: 24px;">
      <div style="text-align: center;">
        <span style="font-size: 32px;">🔍</span>
        <h3 style="font-size: 22px; font-weight: 700; color: #0f172a; margin-top: 6px;">ตรวจสอบยอดค่าน้ำประปาออนไลน์</h3>
        <p style="font-size: 14px; color: #64748b;">กรอกรหัสผู้ใช้น้ำ หรือบ้านเลขที่ เพื่อตรวจสอบยอดค่าน้ำประจำงวดและสถานะการชำระเงิน</p>
      </div>

      <div class="citizen-search-bar">
        <input type="text" id="citizen-input" class="citizen-search-input" placeholder="พิมพ์รหัสผู้ใช้น้ำ (เช่น WY-001) หรือบ้านเลขที่ (เช่น 12)...">
        <button type="button" id="btn-citizen-search" class="btn-search-bill">ค้นหายอดค่าน้ำ</button>
      </div>

      <div id="citizen-result-area" style="margin-top: 20px; display: none;">
        <!-- Dynamic Result Card -->
      </div>
    </section>

    </div> <!-- /.portal-container -->

      <!-- Footer -->
      <footer class="portal-footer" style="padding: 24px 32px; background: #0f172a; color: #94a3b8; font-size: 13.5px; text-align: center;">
        <p><strong>กองทุนระบบน้ำประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</strong></p>
        <p style="margin-top: 4px;">ระบบบริการและบริหารจัดการน้ำประปาชุมชน © 2567 เพื่อการบริหารงานที่โปร่งใสและตรวจสอบได้</p>
      </footer>
    </main>
  </div> <!-- /.app-layout -->

  <!-- Modal: พิมพ์ใบแจ้งยอด / ใบเสร็จรับเงินสำหรับประชาชน -->
  <div id="citizen-bill-modal" class="modal">
    <div class="modal-dialog" style="max-width: 600px;">
      <div class="modal-header" style="background: #0284c7; color: #fff;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 20px;">🧾</span>
          <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #fff;">ใบแจ้งยอดค่าน้ำประปา / ใบเสร็จรับเงิน</h4>
        </div>
        <button type="button" class="modal-close" id="btn-close-bill-modal" style="color: #fff;">&times;</button>
      </div>
      <div class="modal-body" id="citizen-bill-modal-content" style="padding: 24px; max-height: 80vh; overflow-y: auto;">
        <!-- Dynamic Content -->
      </div>
      <div style="padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
        <button type="button" class="btn btn-outline" id="btn-cancel-bill-modal" style="padding: 8px 16px; font-size: 13px; font-family: inherit; cursor: pointer; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff;">ปิดหน้าต่าง</button>
        <button type="button" class="btn btn-primary" id="btn-print-bill-modal" style="padding: 8px 18px; font-size: 13px; font-family: inherit; cursor: pointer; background: #0284c7; color: #fff; border: none; border-radius: 6px; font-weight: 600;">🖨️ สั่งพิมพ์เอกสาร</button>
      </div>
    </div>
  </div>

  <!-- Scripts: Citizen Search & Modals -->
  <script>
    // Check URL parameters to auto-open login modal if needed
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('login_required') || urlParams.has('open_login') || urlParams.has('switch_role')) {
      if (typeof openLoginModal === 'function') openLoginModal();
    }

    // 3. Citizen Search Bar & Printable Bill Popup
    let currentMatchedReading = null;

    document.getElementById('citizen-input')?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') {
        document.getElementById('btn-citizen-search')?.click();
      }
    });

    document.getElementById('btn-citizen-search').addEventListener('click', async () => {
      const kw = document.getElementById('citizen-input').value.trim().toLowerCase();
      const resArea = document.getElementById('citizen-result-area');
      if (!kw) {
        alert('กรุณากรอกรหัสผู้ใช้น้ำหรือบ้านเลขที่');
        return;
      }

      try {
        const res = await fetch(`api/readings.php?cycle=<?php echo $stats['current_cycle']; ?>`);
        if (res.ok) {
          const data = await res.json();
          const match = data.readings.find(r => 
            r.customer_code.toLowerCase().includes(kw) ||
            r.house_no.toLowerCase().includes(kw) ||
            r.first_name.toLowerCase().includes(kw) ||
            r.last_name.toLowerCase().includes(kw)
          );

          if (match) {
            currentMatchedReading = match;
            resArea.style.display = 'block';
            resArea.innerHTML = `
              <div style="background: #f8fafc; border: 2px solid #0284c7; border-radius: 10px; padding: 22px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #cbd5e1; padding-bottom: 12px; margin-bottom: 14px;">
                  <div>
                    <strong style="font-size: 18px; color: #0f172a;">${match.first_name} ${match.last_name}</strong>
                    <span style="font-size: 13px; color: #64748b; margin-left: 8px;">(รหัส: <strong>${match.customer_code}</strong> | บ้านเลขที่: <strong>${match.house_no}</strong>)</span>
                  </div>
                  <span class="badge ${match.payment_status === 'PAID' ? 'badge-paid' : 'badge-unpaid'}" style="font-size: 13px; padding: 5px 14px; border-radius: 9999px;">
                    ${match.payment_status === 'PAID' ? '✅ ชำระเงินเรียบร้อยแล้ว' : '⏳ ยังไม่ได้ชำระเงิน'}
                  </span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; font-size: 14px; background: #fff; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                  <div><span style="color: #64748b; font-size: 13px; display: block;">เลขมิเตอร์เดือนก่อน:</span> <strong style="font-size: 16px;">${parseFloat(match.previous_reading).toFixed(1)}</strong></div>
                  <div><span style="color: #64748b; font-size: 13px; display: block;">เลขมิเตอร์เดือนนี้:</span> <strong style="font-size: 16px;">${parseFloat(match.current_reading).toFixed(1)}</strong></div>
                  <div><span style="color: #64748b; font-size: 13px; display: block;">ปริมาณการใช้น้ำ:</span> <strong style="font-size: 16px; color: #0284c7;">${parseFloat(match.units_used).toFixed(1)} หน่วย</strong></div>
                  <div><span style="color: #64748b; font-size: 13px; display: block;">ยอดเงินที่ต้องชำระ:</span> <strong style="font-size: 18px; color: #b91c1c;">${parseFloat(match.grand_total).toFixed(2)} บาท</strong></div>
                </div>
                <div style="margin-top: 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                  <span style="font-size: 13px; color: #64748b;">* รวมค่าน้ำตามหน่วยจริง + ค่าบำรุงรักษามิเตอร์ 10.00 บาท${parseFloat(match.previous_arrears) > 0 ? ' + ยอดค้างเก่า ' + parseFloat(match.previous_arrears).toFixed(2) + ' บ.' : ''}</span>
                  <button type="button" id="btn-view-citizen-receipt" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #0284c7; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 13.5px; font-weight: 600;">
                    🖨️ ดูและพิมพ์ใบแจ้งยอด / ใบเสร็จ
                  </button>
                </div>
              </div>
            `;

            document.getElementById('btn-view-citizen-receipt')?.addEventListener('click', () => {
              showCitizenReceiptModal(match);
            });
          } else {
            currentMatchedReading = null;
            resArea.style.display = 'block';
            resArea.innerHTML = `<div style="text-align: center; color: #dc2626; padding: 16px; background: #fee2e2; border-radius: 6px; font-size: 14px;">❌ ไม่พบข้อมูลผู้ใช้น้ำที่ตรงกับคำค้นหา "${kw}" ในงวดนี้</div>`;
          }
        }
      } catch (err) {
        console.error(err);
      }
    });

    // 4. Citizen Receipt Modal Logic
    function showCitizenReceiptModal(reading) {
      const modal = document.getElementById('citizen-bill-modal');
      const content = document.getElementById('citizen-bill-modal-content');
      
      const receiptNo = reading.receipt_no || `WY-${reading.customer_code}`;
      const isPaid = reading.payment_status === 'PAID';

      content.innerHTML = `
        <div style="border: 2px solid #0f172a; border-radius: 8px; padding: 20px; font-family: 'Sarabun', sans-serif;">
          <div style="text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 14px;">
            <h3 style="font-size: 18px; font-weight: 700; margin: 0; color: #0f172a;">กองทุนระบบน้ำประปาหมู่บ้านวังยาง</h3>
            <p style="font-size: 13px; color: #475569; margin: 2px 0 0 0;">หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</p>
            <div style="display: inline-block; background: #0284c7; color: #fff; font-size: 13.5px; font-weight: 600; padding: 3px 16px; border-radius: 4px; margin-top: 6px;">
              ${isPaid ? 'ใบเสร็จรับเงินค่าน้ำประปา' : 'ใบแจ้งหนี้ค่าน้ำประปา'}
            </div>
          </div>

          <div style="display: flex; justify-content: space-between; font-size: 13.5px; margin-bottom: 12px; background: #f8fafc; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px;">
            <div><strong>เลขที่เอกสาร:</strong> ${receiptNo}</div>
            <div><strong>ประจำงวดเดือน:</strong> <?php echo $stats['current_cycle']; ?></div>
            <div><strong>สถานะ:</strong> <span style="font-weight: 700; color: ${isPaid ? '#16a34a' : '#dc2626'};">${isPaid ? 'ชำระแล้ว' : 'ค้างชำระ'}</span></div>
          </div>

          <div style="font-size: 13.5px; margin-bottom: 14px; line-height: 1.6;">
            <div><strong>ชื่อผู้ใช้น้ำ:</strong> ${reading.first_name} ${reading.last_name} (รหัส: ${reading.customer_code})</div>
            <div><strong>ที่อยู่:</strong> บ้านเลขที่ ${reading.house_no} ตำบลวังยาง</div>
          </div>

          <table style="width: 100%; border-collapse: collapse; font-size: 13.5px; margin-bottom: 14px;">
            <thead>
              <tr style="background: #e2e8f0; border: 1px solid #0f172a;">
                <th style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: left;">รายการ</th>
                <th style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right;">เลขครั้งก่อน</th>
                <th style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right;">เลขครั้งหลัง</th>
                <th style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right;">หน่วยที่ใช้</th>
                <th style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right;">จำนวนเงิน (บาท)</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td style="padding: 6px 8px; border: 1px solid #cbd5e1;">ค่าน้ำประปาประจำงวด (@ ${parseFloat(reading.rate_per_unit || 7).toFixed(2)} บ.)</td>
                <td style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right;">${parseFloat(reading.previous_reading).toFixed(1)}</td>
                <td style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right;">${parseFloat(reading.current_reading).toFixed(1)}</td>
                <td style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right; font-weight: 600;">${parseFloat(reading.units_used).toFixed(1)}</td>
                <td style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right;">${parseFloat(reading.water_charge || 0).toFixed(2)}</td>
              </tr>
              <tr>
                <td colspan="4" style="padding: 6px 8px; border: 1px solid #cbd5e1;">ค่าบำรุงรักษามิเตอร์ประจำเดือน</td>
                <td style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right;">10.00</td>
              </tr>
              ${parseFloat(reading.previous_arrears) > 0 ? `
              <tr style="color: #b91c1c;">
                <td colspan="4" style="padding: 6px 8px; border: 1px solid #cbd5e1;">ยอดค้างชำระยกยอดมา</td>
                <td style="padding: 6px 8px; border: 1px solid #cbd5e1; text-align: right;">${parseFloat(reading.previous_arrears).toFixed(2)}</td>
              </tr>` : ''}
              <tr style="background: #e0f2fe; font-weight: 700; border: 2px solid #0284c7;">
                <td colspan="4" style="padding: 8px; font-size: 14px;">ยอดรวมสุทธิที่ต้องชำระทั้งสิ้น</td>
                <td style="padding: 8px; text-align: right; font-size: 16px; color: #b91c1c;">${parseFloat(reading.grand_total).toFixed(2)} บาท</td>
              </tr>
            </tbody>
          </table>

          <!-- PromptPay QR Code for Citizen Bill -->
          <div style="background: #f0fdf4; border: 1px dashed #22c55e; border-radius: 8px; padding: 12px 16px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 12px;">
              <img src="https://promptpay.io/0812345678/${parseFloat(reading.grand_total).toFixed(2)}.png" style="width: 85px; height: 85px; border-radius: 6px; border: 1px solid #bbf7d0; background: #fff; padding: 2px;" alt="PromptPay QR">
              <div style="font-size: 13px; color: #166534; line-height: 1.5;">
                <div style="font-weight: 700; font-size: 13.5px; margin-bottom: 2px;">📱 สแกนชำระเงินผ่านพร้อมเพย์ (PromptPay)</div>
                <div>บัญชี: กองทุนระบบประปาหมู่บ้านวังยาง (ธ.ก.ส.)</div>
                <div>หมายเลข: 081-234-5678 | ยอดชำระ: <strong style="color: #b91c1c; font-size: 14px;">${parseFloat(reading.grand_total).toFixed(2)} บาท</strong></div>
              </div>
            </div>
            <div style="text-align: right; font-size: 13px; color: #15803d; font-weight: 600;">
              ${isPaid ? '✅ ชำระเรียบร้อยแล้ว' : '⚡ สแกนจ่ายได้ทันที'}
            </div>
          </div>

          <div style="font-size: 13px; color: #64748b; margin-top: 10px; display: flex; justify-content: space-between;">
            <div>* กำหนดชำระภายในวันที่ 10 ของทุกเดือน</div>
            <div>ผู้รับเงิน: คณะกรรมการการประปาหมู่บ้านวังยาง</div>
          </div>
        </div>
      `;

      modal.classList.add('show');
    }

    document.getElementById('btn-close-bill-modal')?.addEventListener('click', () => {
      document.getElementById('citizen-bill-modal').classList.remove('show');
    });
    document.getElementById('btn-cancel-bill-modal')?.addEventListener('click', () => {
      document.getElementById('citizen-bill-modal').classList.remove('show');
    });
    document.getElementById('btn-print-bill-modal')?.addEventListener('click', printCitizenReceipt);

    function printCitizenReceipt() {
      const modalContent = document.getElementById('citizen-bill-modal-content').innerHTML;
      const printWin = window.open('', '', 'width=800,height=600');
      printWin.document.write(`
        <html>
        <head>
          <title>พิมพ์ใบแจ้งยอดค่าน้ำ - การประปาหมู่บ้านวังยาง</title>
          <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
          <style>
            body { font-family: 'Sarabun', sans-serif; padding: 20px; }
          </style>
        </head>
        <body>
          ${modalContent}
          <script>
            window.onload = function() { window.print(); window.close(); }
          <\/script>
        </body>
        </html>
      `);
      printWin.document.close();
    }
  </script>
</body>
</html>

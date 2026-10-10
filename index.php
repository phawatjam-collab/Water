<?php
/**
 * หน้าหลัก (Portal / Landing Page): ระบบบริหารจัดการการประปาหมู่บ้านวังยาง
 * โครงการพัฒนาระบบสารสนเทศและฐานข้อมูล (งานกลุ่ม)
 */
require_once __DIR__ . '/sidebar.php';
require_once __DIR__ . '/api/db.php';
$currentUser = getCurrentUser();

$stats = [
    'total_customers' => 580,
    'total_zones' => 2,
    'current_rate' => 7.00,
    'maintenance_fee' => 10.00,
    'current_cycle' => '8-2567',
    'total_units' => 0,
    'accumulated_balance' => 145200.00
];

try {
    // ดึงจำนวนผู้ใช้น้ำทั้งหมด
    $cStmt = $pdo->query("SELECT COUNT(*) as cnt FROM customers WHERE status = 'ACTIVE'");
    $cnt = (int)($cStmt->fetch()['cnt'] ?? 0);
    if ($cnt > 0) {
        $stats['total_customers'] = $cnt;
    }

    // ดึงจำนวนโซน
    $zStmt = $pdo->query("SELECT COUNT(DISTINCT zone) as cnt FROM customers");
    $zCnt = (int)($zStmt->fetch()['cnt'] ?? 0);
    if ($zCnt > 0) {
        $stats['total_zones'] = $zCnt;
    }

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
      width: 100%;
      max-width: 100%;
      margin: 0 0 40px 0;
      padding: 0;
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

    /* Citizen Bill Search Widget & Smooth Dropdown */
    .citizen-box {
      background: #fff;
      border-radius: 14px;
      padding: 32px 28px;
      box-shadow: 0 10px 25px -3px rgba(0,0,0,0.08);
      border: 2px solid #0284c7;
      margin-bottom: 40px;
    }
    .search-input-wrapper {
      position: relative !important;
      flex: 1 !important;
      display: flex !important;
      align-items: center !important;
    }
    .hero-search-box {
      max-width: 720px !important;
      margin: 22px auto 8px auto !important;
      position: relative !important;
      text-align: left !important;
    }
    .hero-search-bar {
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      background: #ffffff !important;
      padding: 6px !important;
      border-radius: 12px !important;
      box-shadow: 0 12px 28px -4px rgba(0, 0, 0, 0.28), 0 4px 12px rgba(0, 0, 0, 0.15) !important;
      border: 2px solid rgba(255, 255, 255, 0.85) !important;
    }
    .hero-search-bar .search-input-wrapper {
      flex: 1 !important;
      position: relative !important;
      display: flex !important;
    }
    .hero-search-input {
      width: 100% !important;
      border: none !important;
      outline: none !important;
      font-size: 15px !important;
      font-family: inherit !important;
      padding: 12px 18px !important;
      color: #0f172a !important;
      background: transparent !important;
      box-shadow: none !important;
    }
    .hero-search-btn {
      background: #0284c7 !important;
      color: #ffffff !important;
      border: none !important;
      border-radius: 8px !important;
      padding: 0 24px !important;
      height: 46px !important;
      font-size: 14.5px !important;
      font-weight: 600 !important;
      font-family: inherit !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 6px !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      transition: all 0.15s ease !important;
    }
    .hero-search-btn:hover {
      background: #0369a1 !important;
      box-shadow: 0 4px 10px rgba(2, 132, 199, 0.35) !important;
      transform: translateY(-1px) !important;
    }
    .citizen-search-bar {
      display: flex !important;
      gap: 12px !important;
      margin-top: 16px !important;
      max-width: 650px !important;
      margin-left: auto !important;
      margin-right: auto !important;
      position: relative !important;
      align-items: center !important;
    }
    .citizen-search-bar .search-input-wrapper {
      flex: 1 !important;
      position: relative !important;
    }
    .citizen-search-input {
      width: 100% !important;
      box-sizing: border-box !important;
      padding: 14px 18px !important;
      border: 2px solid #cbd5e1 !important;
      border-radius: 8px !important;
      font-size: 15px !important;
      font-family: 'Sarabun', sans-serif !important;
      outline: none !important;
      height: 50px !important;
      background: #ffffff !important;
      color: #0f172a !important;
      transition: border-color 0.2s ease !important;
    }
    .citizen-search-input:focus {
      border-color: #0284c7 !important;
      box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
    }
    .btn-search-bill {
      background: #0284c7 !important;
      color: #ffffff !important;
      font-size: 15px !important;
      font-family: 'Prompt', sans-serif !important;
      font-weight: 600 !important;
      border: none !important;
      border-radius: 8px !important;
      padding: 0 24px !important;
      height: 50px !important;
      cursor: pointer !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 6px !important;
      white-space: nowrap !important;
      flex-shrink: 0 !important;
      transition: background 0.15s ease !important;
    }
    .btn-search-bill:hover {
      background: #0369a1 !important;
    }
    @media (max-width: 900px) {
      .kpi-grid {
        grid-template-columns: repeat(2, 1fr) !important;
        gap: 14px !important;
      }
    }
    @media (max-width: 560px) {
      .kpi-grid {
        grid-template-columns: 1fr !important;
        gap: 10px !important;
      }
      .kpi-card {
        padding: 16px !important;
      }
      .hero-search-bar {
        flex-direction: column !important;
        padding: 8px !important;
      }
      .hero-search-bar .search-input-wrapper {
        width: 100% !important;
      }
      .hero-search-btn {
        width: 100% !important;
        justify-content: center !important;
        height: 48px !important;
      }
      .citizen-search-bar {
        flex-direction: column !important;
      }
      .btn-search-bill {
        width: 100% !important;
        justify-content: center !important;
        height: 48px !important;
      }
      .portal-hero {
        padding: 28px 16px 32px 16px !important;
      }
      .portal-hero h1 {
        font-size: 22px !important;
      }
      .portal-hero p {
        font-size: 14px !important;
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
      letter-spacing: 0.3px !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    .search-dropdown-footer {
      display: flex !important;
      align-items: center !important;
      justify-content: space-between !important;
      padding: 8px 14px !important;
      background: #f8fafc !important;
      border-top: 1px solid #e2e8f0 !important;
      border-radius: 0 0 10px 10px !important;
      font-size: 11.5px !important;
      color: #64748b !important;
      margin-top: 4px !important;
      width: 100% !important;
      box-sizing: border-box !important;
    }
    .search-dropdown-item {
      display: flex !important;
      flex-direction: row !important;
      align-items: center !important;
      justify-content: space-between !important;
      width: 100% !important;
      box-sizing: border-box !important;
      padding: 12px 14px !important;
      border-radius: 10px !important;
      cursor: pointer !important;
      transition: all 0.15s ease !important;
      border: 1px solid transparent !important;
      border-bottom: 1px solid #f8fafc !important;
      gap: 14px !important;
      text-align: left !important;
      text-decoration: none !important;
      color: #0f172a !important;
      background: #ffffff !important;
    }
    .search-dropdown-item:last-of-type {
      border-bottom: 1px solid transparent !important;
    }
    .search-dropdown-item:hover,
    .search-dropdown-item.active {
      background: #f0f9ff !important;
      border-color: #bae6fd !important;
      transform: translateX(3px) !important;
      box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08) !important;
    }
    .search-dropdown-item .item-main {
      display: flex !important;
      flex-direction: column !important;
      gap: 4px !important;
      min-width: 0 !important;
      flex: 1 !important;
    }
    .search-dropdown-item .item-title {
      font-size: 14.5px !important;
      font-weight: 700 !important;
      color: #0f172a !important;
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      flex-wrap: wrap !important;
    }
    .item-phone-badge {
      font-size: 14px !important;
      font-weight: 700 !important;
      color: #0369a1 !important;
      background: #e0f2fe !important;
      padding: 3px 9px !important;
      border-radius: 6px !important;
      letter-spacing: 0.5px !important;
      display: inline-flex !important;
      align-items: center !important;
      gap: 4px !important;
      border: 1px solid #bae6fd !important;
    }
    .search-dropdown-item .item-name {
      font-size: 14.5px !important;
      font-weight: 600 !important;
      color: #1e293b !important;
    }
    .search-dropdown-item .item-code-badge {
      font-size: 11.5px !important;
      background: #f1f5f9 !important;
      color: #475569 !important;
      padding: 2px 7px !important;
      border-radius: 4px !important;
      font-weight: 600 !important;
      letter-spacing: 0.3px !important;
    }
    .search-dropdown-item .item-sub {
      font-size: 12.5px !important;
      color: #64748b !important;
      display: flex !important;
      align-items: center !important;
      gap: 8px !important;
      flex-wrap: wrap !important;
    }
    .search-dropdown-item .item-meta {
      text-align: right !important;
      flex-shrink: 0 !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: flex-end !important;
      gap: 4px !important;
    }
    .search-dropdown-item .item-amount {
      font-size: 15px !important;
      font-weight: 700 !important;
      color: #0f172a !important;
    }
    .search-dropdown-item .item-action-pill {
      display: inline-flex !important;
      align-items: center !important;
      gap: 4px !important;
      font-size: 11.5px !important;
      font-weight: 600 !important;
      color: #0284c7 !important;
      background: #e0f2fe !important;
      padding: 3px 10px !important;
      border-radius: 9999px !important;
      border: 1px solid #bae6fd !important;
      transition: all 0.15s ease !important;
      white-space: nowrap !important;
    }
    .search-dropdown-item:hover .item-action-pill,
    .search-dropdown-item.active .item-action-pill {
      background: #0284c7 !important;
      color: #ffffff !important;
      border-color: #0284c7 !important;
      box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3) !important;
    }
    .search-highlight {
      background-color: #fef08a !important;
      color: #854d0e !important;
      font-weight: 700 !important;
      padding: 0 2px !important;
      border-radius: 2px !important;
    }
    .search-dropdown-empty {
      padding: 24px 14px !important;
      text-align: center !important;
      color: #64748b !important;
      font-size: 13.5px !important;
      display: flex !important;
      flex-direction: column !important;
      align-items: center !important;
      gap: 6px !important;
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
      <section class="portal-hero" id="about-section" style="padding: 38px 28px 42px 28px; background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0284c7 100%); color: #fff; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.2); border: 1px solid rgba(255, 255, 255, 0.1);">
        <div class="hero-badge" style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.25); border-radius: 9999px; padding: 4px 14px; font-size: 13px; font-weight: 600;">🏛️ กองทุนน้ำประปาหมู่บ้านวังยาง หมู่ที่ 3</div>
        <h1 style="font-size: 28px; margin: 12px 0 8px 0; font-family: 'Prompt', sans-serif; font-weight: 700; letter-spacing: -0.3px;">ระบบบริการและบริหารจัดการน้ำประปาชุมชน</h1>
        <p style="font-size: 15px; margin: 0 auto; max-width: 650px; opacity: 0.9; line-height: 1.5;">
          ตรวจสอบยอดค่าน้ำ ชำระเงินผ่าน PromptPay และติดตามข้อมูลบริหารงานประปาหมู่บ้านวังยางแบบครบวงจร
        </p>
        <!-- Hero Water Bill Search Widget -->
        <div class="hero-search-box">
          <div class="hero-search-bar">
            <div class="search-input-wrapper">
              <input type="text" id="hero-citizen-input" class="hero-search-input" inputmode="search" autocomplete="off" placeholder="พิมพ์เบอร์โทรศัพท์ (เช่น 081-234-5678) หรือรหัสผู้ใช้น้ำ...">
              <div id="hero-search-dropdown" class="search-autocomplete-dropdown" style="display: none;"></div>
            </div>
            <button type="button" id="btn-hero-citizen-search" class="hero-search-btn">
              <span>🔎</span> ค้นหาบิล
            </button>
          </div>
        </div>


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

    <!-- Community Water Supply Zones Section -->
    <div style="margin-bottom: 32px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
        <div>
          <h3 style="font-family: 'Prompt', sans-serif; font-size: 19px; font-weight: 700; margin: 0; color: #0f172a;">
            🗺️ โซนพื้นที่ให้บริการน้ำประปาชุมชน (2 โซนหลัก)
          </h3>
        </div>
        <span style="font-size: 12.5px; background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 6px; font-weight: 600;">
          ครอบคลุมผู้ใช้น้ำประมาณ 580 หลังคาเรือน
        </span>
      </div>

      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
        <!-- Zone 1: โซนทุ่งสามัคคี -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 4px solid #0284c7;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0;">
            <div>
              <span style="font-size: 12px; font-weight: 700; color: #0284c7; text-transform: uppercase;">รหัสโซน 01</span>
              <h4 style="font-family: 'Prompt', sans-serif; font-size: 17px; font-weight: 700; margin: 2px 0; color: #0f172a;">โซนทุ่งสามัคคี</h4>
            </div>
            <span style="background: #ecfdf5; color: #059669; font-size: 12px; font-weight: 600; padding: 3px 8px; border-radius: 9999px;">
              จ่ายน้ำปกติ ✅
            </span>
          </div>
        </div>

        <!-- Zone 2: โซนโค้งขี้เหล็ก -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); border-left: 4px solid #0ea5e9;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0;">
            <div>
              <span style="font-size: 12px; font-weight: 700; color: #0ea5e9; text-transform: uppercase;">รหัสโซน 02</span>
              <h4 style="font-family: 'Prompt', sans-serif; font-size: 17px; font-weight: 700; margin: 2px 0; color: #0f172a;">โซนโค้งขี้เหล็ก</h4>
            </div>
            <span style="background: #ecfdf5; color: #059669; font-size: 12px; font-weight: 600; padding: 3px 8px; border-radius: 9999px;">
              จ่ายน้ำปกติ ✅
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Community Operating Rules & Technical Guidance -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px; margin-bottom: 32px;">
      
      <!-- Calendar & Collection Workflow -->
      <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <h4 style="font-family: 'Prompt', sans-serif; font-size: 16px; font-weight: 700; margin: 0; color: #0f172a; display: flex; align-items: center; gap: 8px;">
          <span>📅</span> ปฏิทินและขั้นตอนบริการผู้ใช้น้ำ
        </h4>
      </div>

      <!-- Technical Warning: Pressure Collision / Dual Systems -->
      <div style="background: linear-gradient(180deg, #fffaf5 0%, #ffffff 100%); border: 1px solid #fed7aa; border-radius: 12px; padding: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <h4 style="font-family: 'Prompt', sans-serif; font-size: 15.5px; font-weight: 700; margin: 0; color: #9a3412; display: flex; align-items: center; gap: 8px;">
          <span>⚠️</span> ระวังแรงดันน้ำตีกลับสำหรับบ้านที่มีบ่อบาดาลส่วนตัว
        </h4>
      </div>

    </div>

    <!-- Contact & Committee Office Information -->
    <div style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 12px; padding: 20px 24px; margin-bottom: 32px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
      <div>
        <h4 style="font-family: 'Prompt', sans-serif; font-size: 15px; font-weight: 700; margin: 0 0 4px 0; color: #0f172a;">
          🏛️ ที่ทำการกองทุนระบบน้ำประปาหมู่บ้านวังยาง หมู่ที่ 3
        </h4>
        <p style="font-size: 13px; color: #475569; margin: 0;">
          ศาลาประชาคมหมู่บ้านวังยาง ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม 48130
        </p>
      </div>
      <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <a href="tel:0891234567" style="background: #fff; border: 1px solid #cbd5e1; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; color: #0369a1; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
          📞 ผู้ใหญ่บ้าน: 089-123-4567
        </a>
        <a href="portal_citizen.php#citizen-services" style="background: #0284c7; color: #fff; padding: 8px 14px; border-radius: 6px; text-decoration: none; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
          🔧 แจ้งท่อแตกรั่วออนไลน์ &rarr;
        </a>
      </div>
    </div>

    </div> <!-- /.portal-container -->

      <!-- Footer -->
      <footer class="portal-footer" style="padding: 24px 32px; background: #0f172a; color: #94a3b8; font-size: 13.5px; text-align: center;">
        <p><strong>กองทุนระบบน้ำประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</strong></p>
        <p style="margin-top: 4px;">ระบบบริการและบริหารจัดการน้ำประปาชุมชน © 2567 เพื่อการบริหารงานที่โปร่งใสและตรวจสอบได้</p>
      </footer>
    </main>
  </div> <!-- /.app-layout -->

  <!-- Scripts: Citizen Search & Modals -->
  <script>
    // Check URL parameters to auto-open login modal if needed
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('login_required') || urlParams.has('open_login') || urlParams.has('switch_role')) {
      if (typeof openLoginModal === 'function') openLoginModal();
    }

    // 3. Citizen Water Bill Search & Autocomplete
    let currentMatchedReading = null;
    let cachedIndexReadings = null;

    async function getIndexReadings() {
      if (cachedIndexReadings) return cachedIndexReadings;
      try {
        const res = await fetch(`api/readings.php?cycle=<?php echo $stats['current_cycle']; ?>`);
        if (!res.ok) throw new Error('API Error');
        const data = await res.json();
        cachedIndexReadings = data.readings || [];
        return cachedIndexReadings;
      } catch (err) {
        console.error('Failed to load readings cache:', err);
        return [];
      }
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

    function formatPhone(p) {
      if (!p) return '-';
      const d = String(p).replace(/\D/g, '');
      if (d.length === 10) return `${d.slice(0, 3)}-${d.slice(3, 6)}-${d.slice(6)}`;
      return p;
    }

    function setupSearchAutocomplete({ inputId, dropdownId, onSelect, onSubmit }) {
      const input = document.getElementById(inputId);
      const dropdown = document.getElementById(dropdownId);
      if (!input || !dropdown) return null;
      let activeIdx = -1;

      function closeDropdown() {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        activeIdx = -1;
      }

      function updateActiveItem(items) {
        items.forEach((item, idx) => {
          if (idx === activeIdx) {
            item.classList.add('active');
            item.scrollIntoView({ block: 'nearest' });
          } else {
            item.classList.remove('active');
          }
        });
      }

      async function handleInput() {
        const kwRaw = input.value.trim();
        if (!kwRaw) {
          closeDropdown();
          return;
        }

        const kwLower = kwRaw.toLowerCase();
        const kwDigits = kwRaw.replace(/\D/g, '');

        const readings = await getIndexReadings();

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

        if (matches.length === 0) {
          dropdown.innerHTML = `
            <div class="search-dropdown-empty">
              <span style="font-size: 26px;">🔍</span>
              <div style="font-weight: 600; color: #475569;">ไม่พบข้อมูลที่ตรงกับ "<strong>${escapeHtml(kwRaw)}</strong>"</div>
              <small style="color: #94a3b8;">ลองค้นหาด้วยเบอร์โทรศัพท์ (เช่น 081-234-5678), รหัสผู้ใช้น้ำ หรือบ้านเลขที่</small>
            </div>
          `;
          dropdown.style.display = 'flex';
          activeIdx = -1;
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
            <a href="${targetUrl}" class="search-dropdown-item" data-code="${escapeHtml(r.customer_code)}" data-phone="${escapeHtml(r.phone)}" data-index="${idx}">
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
            <span>⚡ ข้อมูลประจำงวด <?php echo htmlspecialchars($stats['current_cycle']); ?></span>
          </div>
        `;

        dropdown.innerHTML = headerHtml + itemsHtml + footerHtml;
        dropdown.style.display = 'flex';
        activeIdx = -1;

        dropdown.querySelectorAll('.search-dropdown-item').forEach(item => {
          item.addEventListener('click', (e) => {
            const code = item.getAttribute('data-code');
            const phone = item.getAttribute('data-phone');
            if (onSelect) {
              onSelect(code, phone, item, e);
            }
          });
        });
      }

      input.addEventListener('input', handleInput);
      input.addEventListener('focus', () => {
        if (input.value.trim().length > 0) handleInput();
      });

      input.addEventListener('keydown', (e) => {
        const items = dropdown.querySelectorAll('.search-dropdown-item');
        if (dropdown.style.display !== 'none' && items.length > 0) {
          if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = (activeIdx + 1) % items.length;
            updateActiveItem(items);
            return;
          } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = (activeIdx - 1 + items.length) % items.length;
            updateActiveItem(items);
            return;
          } else if (e.key === 'Enter') {
            if (activeIdx >= 0 && items[activeIdx]) {
              e.preventDefault();
              items[activeIdx].click();
              return;
            }
          } else if (e.key === 'Escape') {
            closeDropdown();
            return;
          }
        }

        if (e.key === 'Enter') {
          closeDropdown();
          if (onSubmit) onSubmit(input.value.trim());
        }
      });

      document.addEventListener('click', (e) => {
        if (!e.target.closest(`#${inputId}`) && !e.target.closest(`#${dropdownId}`)) {
          closeDropdown();
        }
      });

      return { closeDropdown, handleInput };
    }

    // Initialize Hero Search Bar (Homepage Banner)
    const heroSearch = setupSearchAutocomplete({
      inputId: 'hero-citizen-input',
      dropdownId: 'hero-search-dropdown',
      onSelect: (code, phone, item, e) => {
        const target = phone || code;
        window.location.href = `portal_citizen.php?phone=${encodeURIComponent(target)}&customer=${encodeURIComponent(code)}`;
      },
      onSubmit: (kw) => {
        submitHeroSearch();
      }
    });

    async function submitHeroSearch() {
      const heroInput = document.getElementById('hero-citizen-input');
      const kw = heroInput ? heroInput.value.trim() : '';
      if (!kw) {
        alert('กรุณากรอกเบอร์โทรศัพท์ หรือรหัสผู้ใช้น้ำเพื่อค้นหา');
        heroInput?.focus();
        return;
      }
      const kwDigits = kw.replace(/\D/g, '');
      const kwLower = kw.toLowerCase();
      const readings = await getIndexReadings();
      const match = readings.find(r => {
        const pDigits = (r.phone || '').replace(/\D/g, '');
        if (kwDigits && pDigits === kwDigits) return true;
        if (kwDigits && kwDigits.length >= 4 && pDigits.includes(kwDigits)) return true;
        if (r.customer_code.toLowerCase() === kwLower) return true;
        if (r.house_no.toLowerCase() === kwLower) return true;
        if ((r.first_name + ' ' + r.last_name).toLowerCase().includes(kwLower)) return true;
        return false;
      });

      const target = match ? (match.phone || match.customer_code) : kw;
      const code = match ? match.customer_code : kw;
      window.location.href = `portal_citizen.php?phone=${encodeURIComponent(target)}&customer=${encodeURIComponent(code)}`;
    }

    document.getElementById('btn-hero-citizen-search')?.addEventListener('click', () => {
      submitHeroSearch();
    });
  </script>
</body>
</html>

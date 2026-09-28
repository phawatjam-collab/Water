<?php
/**
 * ศูนย์ทดสอบ Prototype และสเปกการออกแบบ (UI/UX Design & Prototype Presentation Suite)
 * พัฒนาสำหรับนำเสนออาจารย์และคณะกรรมการตรวจประเมินโครงการ
 */
require_once __DIR__ . '/auth.php';
$currentUser = getCurrentUser();
$currentRole = $currentUser['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ศูนย์ทดสอบ Prototype & สเปกการออกแบบ | การประปาหมู่บ้านวังยาง</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&family=Fira+Code:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    :root {
      --suite-bg: #090d16;
      --suite-bar: #0f172a;
      --suite-border: #1e293b;
      --suite-accent: #0284c7;
      --suite-text: #f8fafc;
      --suite-muted: #94a3b8;
    }

    body {
      margin: 0;
      padding: 0;
      background: var(--suite-bg);
      color: var(--suite-text);
      font-family: 'Sarabun', sans-serif;
      overflow-x: hidden;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Top Suite Header */
    .suite-header {
      background: var(--suite-bar);
      border-bottom: 1px solid var(--suite-border);
      padding: 12px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 4px 12px rgba(0,0,0,0.4);
    }

    .suite-brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .suite-badge {
      background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 4px;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    .suite-title {
      font-family: 'Prompt', sans-serif;
      font-size: 16px;
      font-weight: 700;
      margin: 0;
      color: #38bdf8;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .suite-subtitle {
      font-size: 12.5px;
      color: var(--suite-muted);
      margin: 0;
    }

    /* Tab Controls */
    .suite-tabs {
      display: flex;
      gap: 6px;
      background: rgba(30, 41, 59, 0.7);
      padding: 4px;
      border-radius: 8px;
      border: 1px solid var(--suite-border);
    }

    .suite-tab-btn {
      background: transparent;
      border: none;
      color: #94a3b8;
      font-family: 'Prompt', sans-serif;
      font-size: 13.5px;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 6px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
    }

    .suite-tab-btn:hover {
      color: #fff;
      background: rgba(255, 255, 255, 0.05);
    }

    .suite-tab-btn.active {
      background: var(--suite-accent);
      color: #fff;
      box-shadow: 0 2px 6px rgba(2, 132, 199, 0.4);
    }

    /* Sub Toolbar: Persona & Device Controls */
    .suite-toolbar {
      background: #0b1120;
      border-bottom: 1px solid var(--suite-border);
      padding: 10px 24px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 14px;
    }

    .persona-selector {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .persona-label {
      font-size: 13px;
      color: var(--suite-muted);
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .persona-chip {
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 13px;
      font-weight: 600;
      border: 1px solid rgba(255,255,255,0.1);
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(15, 23, 42, 0.8);
      color: #cbd5e1;
      text-decoration: none;
      transition: all 0.15s ease;
    }

    .persona-chip:hover {
      border-color: #38bdf8;
      color: #fff;
      transform: translateY(-1px);
    }

    .persona-chip.active {
      border-color: #38bdf8;
      background: rgba(2, 132, 199, 0.2);
      color: #38bdf8;
      box-shadow: 0 0 10px rgba(56, 189, 248, 0.25);
    }

    .viewport-controls {
      display: flex;
      align-items: center;
      gap: 6px;
      background: rgba(15, 23, 42, 0.8);
      padding: 4px;
      border-radius: 6px;
      border: 1px solid var(--suite-border);
    }

    .viewport-btn {
      background: none;
      border: none;
      color: #94a3b8;
      padding: 6px 10px;
      font-size: 12.5px;
      font-family: inherit;
      border-radius: 4px;
      cursor: pointer;
      transition: all 0.15s;
    }

    .viewport-btn:hover {
      color: #fff;
    }

    .viewport-btn.active {
      background: #1e293b;
      color: #38bdf8;
      font-weight: 600;
    }

    /* Main View Area */
    .suite-viewport-wrapper {
      flex: 1;
      padding: 20px;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      background: #060911;
      min-height: calc(100vh - 125px);
    }

    .prototype-frame-container {
      width: 100%;
      max-width: 100%;
      height: 840px;
      background: #fff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.7);
      border: 1px solid #334155;
      transition: max-width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .prototype-frame-container.viewport-laptop {
      max-width: 1280px;
    }

    .prototype-frame-container.viewport-tablet {
      max-width: 768px;
    }

    .prototype-frame-container.viewport-mobile {
      max-width: 390px;
    }

    .prototype-iframe {
      width: 100%;
      height: 100%;
      border: none;
      display: block;
    }

    /* Spec & Documentation Panels */
    .spec-pane {
      display: none;
      max-width: 1300px;
      width: 100%;
      margin: 0 auto;
      background: #0f172a;
      border: 1px solid #1e293b;
      border-radius: 12px;
      padding: 32px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.5);
    }

    .spec-pane.active {
      display: block;
      animation: fadeIn 0.2s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .spec-header {
      border-bottom: 1px solid #334155;
      padding-bottom: 20px;
      margin-bottom: 24px;
    }

    .spec-header h2 {
      font-family: 'Prompt', sans-serif;
      font-size: 24px;
      color: #38bdf8;
      margin: 0 0 8px 0;
    }

    .spec-header p {
      color: #94a3b8;
      font-size: 14.5px;
      margin: 0;
    }

    .spec-grid-2 {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
      margin-bottom: 30px;
    }

    .spec-grid-3 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-bottom: 30px;
    }

    .spec-card {
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 8px;
      padding: 20px;
    }

    .spec-card h3 {
      font-family: 'Prompt', sans-serif;
      font-size: 16px;
      color: #f1f5f9;
      margin: 0 0 12px 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .color-swatch {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 10px;
      padding: 8px 12px;
      background: #0f172a;
      border-radius: 6px;
      border: 1px solid #334155;
    }

    .color-box {
      width: 28px;
      height: 28px;
      border-radius: 6px;
      border: 1px solid rgba(255,255,255,0.2);
    }

    .code-block {
      background: #090d16;
      border: 1px solid #334155;
      border-radius: 6px;
      padding: 14px;
      font-family: 'Fira Code', monospace;
      font-size: 13px;
      color: #38bdf8;
      overflow-x: auto;
      white-space: pre-wrap;
    }

    .spec-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13.5px;
      margin-top: 10px;
    }

    .spec-table th, .spec-table td {
      padding: 10px 14px;
      border: 1px solid #334155;
      text-align: left;
    }

    .spec-table th {
      background: #0f172a;
      color: #38bdf8;
      font-family: 'Prompt', sans-serif;
      font-weight: 600;
    }

    .spec-table td {
      background: #1e293b;
      color: #cbd5e1;
    }

    .checklist-item {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 14px;
      background: #0f172a;
      border: 1px solid #334155;
      border-radius: 8px;
      margin-bottom: 12px;
      cursor: pointer;
      transition: border-color 0.15s;
    }

    .checklist-item:hover {
      border-color: #0284c7;
    }

    .checklist-item input[type="checkbox"] {
      width: 18px;
      height: 18px;
      margin-top: 3px;
      accent-color: #0284c7;
      cursor: pointer;
    }

    .checklist-content strong {
      display: block;
      font-family: 'Prompt', sans-serif;
      font-size: 14.5px;
      color: #f1f5f9;
      margin-bottom: 4px;
    }

    .checklist-content p {
      margin: 0;
      font-size: 13px;
      color: #94a3b8;
      line-height: 1.5;
    }

    .btn-action-test {
      background: #0284c7;
      color: #fff;
      border: none;
      padding: 6px 14px;
      border-radius: 6px;
      font-size: 12.5px;
      font-family: 'Prompt', sans-serif;
      font-weight: 600;
      cursor: pointer;
      margin-top: 8px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
    }

    .btn-action-test:hover {
      background: #0369a1;
    }
  </style>
</head>
<body>

  <!-- Top Suite Header -->
  <header class="suite-header">
    <div class="suite-brand">
      <div style="font-size: 26px; line-height: 1;">💧</div>
      <div>
        <div style="display: flex; align-items: center; gap: 8px;">
          <h1 class="suite-title">การประปาหมู่บ้านวังยาง</h1>
          <span class="suite-badge">UI/UX & Prototype Suite</span>
        </div>
        <p class="suite-subtitle">ศูนย์ทดสอบ Prototype และสเปกการออกแบบระบบ (สำหรับอาจารย์และกรรมการตรวจประเมิน)</p>
      </div>
    </div>

    <!-- Main Navigation Tabs -->
    <div class="suite-tabs">
      <button class="suite-tab-btn active" onclick="switchSuiteTab('live-prototype')">
        <span>🖥️</span> ตัวทดสอบ Prototype (Interactive)
      </button>
      <button class="suite-tab-btn" onclick="switchSuiteTab('design-specs')">
        <span>📐</span> สเปกการออกแบบ (UI/UX Specs)
      </button>
      <button class="suite-tab-btn" onclick="switchSuiteTab('axure-guide')">
        <span>🛠️</span> ข้อมูล Axure RP 11 & Repeater
      </button>
      <button class="suite-tab-btn" onclick="switchSuiteTab('scenarios-checklist')">
        <span>📋</span> รายการทดสอบ (Test Scenarios)
      </button>
    </div>
  </header>

  <!-- Sub Toolbar (Active during Live Prototype) -->
  <div class="suite-toolbar" id="prototype-toolbar">
    <!-- Persona Switcher -->
    <div class="persona-selector">
      <span class="persona-label"><span>🎭</span> เลือกระดับผู้ใช้งาน (Role Simulator):</span>
      
      <a href="javascript:void(0)" onclick="loadPersona('guest', 'index.php')" class="persona-chip <?php echo ($currentRole === 'guest') ? 'active' : ''; ?>" id="chip-guest">
        <span>👥</span> ผู้ใช้ทั่วไป / ประชาชน
      </a>
      <a href="javascript:void(0)" onclick="loadPersona('member', 'portal_citizen.php?customer=WY-001')" class="persona-chip <?php echo ($currentRole === 'member') ? 'active' : ''; ?>" id="chip-member">
        <span>👤</span> สมาชิกผู้ใช้น้ำ (นายสมชาย)
      </a>
      <a href="javascript:void(0)" onclick="loadPersona('reader', 'meter_reading.php')" class="persona-chip <?php echo ($currentRole === 'reader') ? 'active' : ''; ?>" id="chip-reader">
        <span>🚶‍♂️</span> จนท.จดมิเตอร์ (ป.17)
      </a>
      <a href="javascript:void(0)" onclick="loadPersona('finance', 'finance_billing.php')" class="persona-chip <?php echo ($currentRole === 'finance') ? 'active' : ''; ?>" id="chip-finance">
        <span>💼</span> ฝ่ายการเงินและเหรัญญิก
      </a>
      <a href="javascript:void(0)" onclick="loadPersona('admin', 'executive_reports.php')" class="persona-chip <?php echo ($currentRole === 'admin') ? 'active' : ''; ?>" id="chip-admin">
        <span>🏛️</span> คณะกรรมการ/ผู้บริหาร (กค.3)
      </a>
    </div>

    <!-- Viewport Devices -->
    <div style="display: flex; align-items: center; gap: 10px;">
      <span class="persona-label"><span>📱</span> ขนาดหน้าจอ:</span>
      <div class="viewport-controls">
        <button class="viewport-btn active" onclick="setViewport('desktop')">💻 Desktop (100%)</button>
        <button class="viewport-btn" onclick="setViewport('laptop')">🖥️ Laptop (1280px)</button>
        <button class="viewport-btn" onclick="setViewport('tablet')">📱 Tablet (768px)</button>
        <button class="viewport-btn" onclick="setViewport('mobile')">📲 Mobile (390px)</button>
      </div>
      <a href="index.php" target="_blank" class="persona-chip" title="เปิดหน้าเว็บจริงในแท็บใหม่">
        <span>↗️</span> เปิดแท็บใหม่
      </a>
    </div>
  </div>

  <!-- Content Container -->
  <main class="suite-viewport-wrapper">
    
    <!-- Tab 1: Live Interactive Prototype -->
    <div id="pane-live-prototype" style="width: 100%; display: flex; justify-content: center;">
      <div class="prototype-frame-container" id="frame-container">
        <iframe id="prototype-iframe" class="prototype-iframe" src="<?php 
          if ($currentRole === 'reader') echo 'meter_reading.php';
          elseif ($currentRole === 'finance') echo 'finance_billing.php';
          elseif ($currentRole === 'admin') echo 'executive_reports.php';
          elseif ($currentRole === 'member') echo 'portal_citizen.php?customer=' . urlencode($currentUser['customer_code'] ?? 'WY-001');
          else echo 'index.php';
        ?>"></iframe>
      </div>
    </div>

    <!-- Tab 2: Design Specs & Architecture -->
    <div id="pane-design-specs" class="spec-pane">
      <div class="spec-header">
        <h2>📐 สเปกและมาตรฐานการออกแบบ UI/UX (Design Specifications)</h2>
        <p>การจัดวางโครงสร้างสารสนเทศ (Information Architecture), ชุดสี, การจัดพิกัดกริด และระบบสิทธิ์การเข้าถึง (RBAC)</p>
      </div>

      <div class="spec-grid-2">
        <!-- Palette -->
        <div class="spec-card">
          <h3>🎨 ระบบชุดสีหลัก (Color Palette Tokens)</h3>
          <div class="color-swatch">
            <div class="color-box" style="background: #0284c7;"></div>
            <div>
              <strong>Primary Sky: #0284c7</strong><br>
              <span style="font-size: 12.5px; color: #94a3b8;">สีหลักด้านน้ำประปา ปุ่มดำเนินการ และ Header ประชาชน</span>
            </div>
          </div>
          <div class="color-swatch">
            <div class="color-box" style="background: #0f172a;"></div>
            <div>
              <strong>Brand & Dark Slate: #0f172a</strong><br>
              <span style="font-size: 12.5px; color: #94a3b8;">สีพื้นหลัง Sidebar นำทาง และหัวเอกสารราชการ</span>
            </div>
          </div>
          <div class="color-swatch">
            <div class="color-box" style="background: #d97706;"></div>
            <div>
              <strong>Field Amber: #d97706</strong><br>
              <span style="font-size: 12.5px; color: #94a3b8;">สีโมดูลจดมิเตอร์ภาคสนาม (แจ้งเตือนความผิดปกติ)</span>
            </div>
          </div>
          <div class="color-swatch">
            <div class="color-box" style="background: #4338ca;"></div>
            <div>
              <strong>Finance Indigo: #4338ca</strong><br>
              <span style="font-size: 12.5px; color: #94a3b8;">สีโมดูลการเงิน ออกใบเสร็จ และฎีกาเบิกจ่าย 10%</span>
            </div>
          </div>
          <div class="color-swatch">
            <div class="color-box" style="background: #7c3aed;"></div>
            <div>
              <strong>Executive Violet: #7c3aed</strong><br>
              <span style="font-size: 12.5px; color: #94a3b8;">สีโมดูลกำกับนโยบายกองทุน และงบการเงิน กค.3</span>
            </div>
          </div>
        </div>

        <!-- Typography & Layout -->
        <div class="spec-card">
          <h3>✍️ ระบบตัวอักษรและการจัดหน้า (Typography & Layout Grid)</h3>
          <div style="margin-bottom: 14px;">
            <strong style="color: #38bdf8;">Heading Font:</strong> Prompt (Google Fonts)<br>
            <span style="font-size: 13px; color: #94a3b8;">น้ำหนัก 600, 700 สำเร็จรูป คมชัด อ่านง่าย ให้ความรู้สึกทางการและทันสมัย</span>
          </div>
          <div style="margin-bottom: 14px;">
            <strong style="color: #38bdf8;">Body Font:</strong> Sarabun (Google Fonts)<br>
            <span style="font-size: 13px; color: #94a3b8;">ฟอนต์มาตรฐานเอกสารราชการไทย สำหรับตาราง, ใบเสร็จ, และแบบฟอร์ม</span>
          </div>
          <div style="margin-bottom: 14px;">
            <strong style="color: #38bdf8;">Grid System:</strong> 12-Column Responsive Grid<br>
            <span style="font-size: 13px; color: #94a3b8;">Desktop Max 1400px, Sidebar 260px Fixed Left, Main View Dynamic Fill</span>
          </div>
          <div>
            <strong style="color: #38bdf8;">Print Optimization:</strong> @media print<br>
            <span style="font-size: 13px; color: #94a3b8;">ซ่อน Sidebar และปุ่มควบคุมทั้งหมด สั่งพิมพ์ใบเสร็จและฎีกาลงกระดาษ A4/A5 ได้ทันที</span>
          </div>
        </div>
      </div>

      <!-- RBAC Mapping Table -->
      <div class="spec-card" style="margin-bottom: 30px;">
        <h3>🛡️ ผังการแบ่งสิทธิ์การเข้าถึง (Role-Based Access Control Matrix)</h3>
        <table class="spec-table">
          <thead>
            <tr>
              <th>ระดับผู้ใช้งาน (Persona)</th>
              <th>ประเภทการยืนยันตัวตน</th>
              <th>เมนูบนแถบ Sidebar</th>
              <th>ขอบเขตงานและสิทธิ์ในระบบ</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>👥 ผู้ใช้ทั่วไป / ประชาชน</strong></td>
              <td>ไม่ต้องล็อกอิน (Public Guest)</td>
              <td>หน้าแรก, ตรวจสอบค่าน้ำออนไลน์, แจ้งท่อแตก/คำร้อง</td>
              <td>ค้นหายอดค่าน้ำของครัวเรือนตนเอง สแกน QR PromptPay จ่ายเงิน และส่งคำร้องท่อแตก</td>
            </tr>
            <tr>
              <td><strong>👤 สมาชิกผู้ใช้น้ำ</strong></td>
              <td>รหัสผู้ใช้น้ำ (เช่น <code>WY-001</code>) หรือเบอร์โทร</td>
              <td>หน้าแรก, ข้อมูล & บิลของฉัน, แจ้งท่อแตก/ติดตาม</td>
              <td>โหลดบิลและสถิติการใช้น้ำของตนเองอัตโนมัติ ดูเลขมิเตอร์ย้อนหลัง และพิมพ์ใบแจ้งหนี้</td>
            </tr>
            <tr>
              <td><strong>🚶‍♂️ เจ้าหน้าที่จดมิเตอร์</strong></td>
              <td>Username: <code>reader</code> / <code>123456</code></td>
              <td>หน้าแรก, สมุดจดมิเตอร์ (ป.17)</td>
              <td>บันทึกเลขมิเตอร์ภาคสนาม ตรวจจับยอดใช้น้ำพุ่งสูงผิดปกติ และคำนวณเงินทันที</td>
            </tr>
            <tr>
              <td><strong>💼 ฝ่ายการเงินและเหรัญญิก</strong></td>
              <td>Username: <code>finance</code> / <code>123456</code></td>
              <td>หน้าแรก, ตัดรับชำระเงิน, ทะเบียนหนี้, ฎีกาเบิกจ่าย, ทะเบียนสมาชิก</td>
              <td>ออกใบเสร็จรับเงินมาตรฐานพร้อมตัวหนังสือไทย ทำฎีกาเบิกจ่าย 10% และคุมหนี้ค้างชำระ</td>
            </tr>
            <tr>
              <td><strong>🏛️ คณะกรรมการ/แอดมิน</strong></td>
              <td>Username: <code>admin</code> / <code>123456</code></td>
              <td>เข้าถึงได้ครบทุกโมดูล (6 รายการ)</td>
              <td>กำกับนโยบายอัตราค่าน้ำ ตรวจสอบงบการเงิน กค.3 วิเคราะห์น้ำสูญเสีย NRW และคุมระบบรวม</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Tab 3: Axure RP 11 Guide -->
    <div id="pane-axure-guide" class="spec-pane">
      <div class="spec-header">
        <h2>🛠️ คู่มือและข้อมูลสำหรับสร้างใน Axure RP 11 (Axure Implementation Guide)</h2>
        <p>โครงสร้าง Component, ตัวแปรส่วนกลาง (Global Variables), ชุดข้อมูล Repeater และสูตรคำนวณ</p>
      </div>

      <div class="spec-grid-2">
        <!-- Axure Variables -->
        <div class="spec-card">
          <h3>⚙️ ตัวแปรส่วนกลาง (Global Variables)</h3>
          <table class="spec-table">
            <thead>
              <tr>
                <th>ชื่อตัวแปร (Variable)</th>
                <th>ค่าเริ่มต้น</th>
                <th>คำอธิบายหน้าที่</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><code>varRole</code></td>
                <td><code>"guest"</code></td>
                <td>เก็บระดับสิทธิ์: <code>guest</code>, <code>member</code>, <code>reader</code>, <code>finance</code>, <code>admin</code></td>
              </tr>
              <tr>
                <td><code>varCustomerCode</code></td>
                <td><code>"WY-001"</code></td>
                <td>รหัสผู้ใช้น้ำปัจจุบันที่กำลังค้นหาหรือล็อกอิน</td>
              </tr>
              <tr>
                <td><code>varRatePerUnit</code></td>
                <td><code>7.00</code></td>
                <td>อัตราค่าน้ำประปาต่อหน่วย (บาท/ลบ.ม.)</td>
              </tr>
              <tr>
                <td><code>varMaintFee</code></td>
                <td><code>10.00</code></td>
                <td>ค่าบำรุงรักษามิเตอร์ประจำเดือน (บาท)</td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Dynamic Panels -->
        <div class="spec-card">
          <h3>📦 โครงสร้าง Dynamic Panels</h3>
          <ul style="font-size: 13.5px; line-height: 1.8; color: #cbd5e1; padding-left: 20px;">
            <li><strong>dp_Sidebar:</strong> มี 5 State แยกตามสิทธิ์ (<code>State_Guest</code>, <code>State_Member</code>, <code>State_Reader</code>, <code>State_Finance</code>, <code>State_Admin</code>)</li>
            <li><strong>dp_LoginModal:</strong> ป๊อปอัปเข้าสู่ระบบ มี 2 State (<code>State_MemberLogin</code>, <code>State_StaffLogin</code>)</li>
            <li><strong>dp_ExecutiveTabs:</strong> สลับหน้ารายงานผู้บริหาร 4 State (<code>Tab_Report</code>, <code>Tab_Loss</code>, <code>Tab_Members</code>, <code>Tab_Tariff</code>)</li>
            <li><strong>dp_ReceiptModal:</strong> ป๊อปอัปแสดงใบเสร็จรับเงินที่ดึงข้อมูลจากตารางมาจัดหน้าพร้อมสั่งพิมพ์</li>
          </ul>
        </div>
      </div>

      <!-- Repeater Dataset Spec -->
      <div class="spec-card">
        <h3>📋 ชุดข้อมูลสำหรับ Repeater Widget ใน Axure RP (Meter Readings Dataset)</h3>
        <p style="font-size: 13px; color: #94a3b8;">สามารถคัดลอกตารางนี้ไปวางในคอลัมน์ของ Repeater ใน Axure ได้ทันที:</p>
        <div class="code-block">customer_code,first_name,last_name,house_no,previous_reading,current_reading,units_used,previous_arrears,payment_status
WY-001,สมชาย,ใจดี,12 หมู่ 3,145.0,163.5,18.5,0.00,PAID
WY-002,สมศรี,มีสุข,14/1 หมู่ 3,98.2,110.0,11.8,0.00,UNPAID
WY-003,ประเสริฐ,วงษ์ทอง,19 หมู่ 3,220.5,234.0,13.5,130.00,UNPAID
WY-004,บุญส่ง,เกษมสุข,25 หมู่ 3,310.0,325.2,15.2,0.00,PAID
WY-005,วันชัย,ทองแท้,38/2 หมู่ 3,85.0,94.0,9.0,0.00,UNPAID
WY-006,อำนวย,คงทน,41 หมู่ 3,178.4,196.0,17.6,0.00,PAID
WY-007,ภัคจิรา,ศรีสวัสดิ์,52/1 หมู่ 3,64.0,78.5,14.5,0.00,PAID
WY-008,ชูเกียรติ,รุ่งเรือง,60 หมู่ 3,212.0,229.0,17.0,0.00,PAID</div>

        <h4 style="margin-top: 16px; color: #38bdf8; font-family: 'Prompt', sans-serif;">สูตร Interaction ใน Axure สำหรับ Repeater Item Loaded:</h4>
        <div class="code-block">// คำนวณค่าน้ำรวมในแต่ละแถว:
[[ (Item.units_used * varRatePerUnit) + varMaintFee + Item.previous_arrears ]]

// คำนวณค่าตอบแทนเจ้าหน้าที่เก็บค่าน้ำ (10%):
[[ (TotalCollected * 0.10) ]]</div>
      </div>
    </div>

    <!-- Tab 4: Scenarios Checklist -->
    <div id="pane-scenarios-checklist" class="spec-pane">
      <div class="spec-header">
        <h2>📋 แผนการทดสอบระบบสำหรับกรรมการและอาจารย์ (Test Scenarios Checklist)</h2>
        <p>คลิกเพื่อทดสอบแต่ละ Scenario ตามเกณฑ์การประเมินโครงการ</p>
      </div>

      <!-- Scenario 1 -->
      <div class="checklist-item">
        <input type="checkbox" id="chk-s1" checked>
        <div class="checklist-content">
          <strong>สถานการณ์ที่ 1: ประชาชนทั่วไปค้นหายอดค่าน้ำและชำระผ่าน PromptPay QR Code</strong>
          <p>
            1. สลับสิทธิ์เป็น "ผู้ใช้ทั่วไป / ประชาชน"<br>
            2. พิมพ์รหัสผู้ใช้น้ำ <code>WY-001</code> หรือคลิกตัวอย่างค้นหาด่วน<br>
            3. ระบบจะแสดงยอดค่าน้ำ หน่วยที่ใช้ และปุ่มพิมพ์ใบแจ้งหนี้พร้อม QR Code พร้อมเพย์ตามยอดเงินจริง
          </p>
          <button class="btn-action-test" onclick="loadPersona('guest', 'portal_citizen.php?customer=WY-001')">
            ▶️ รันสถานการณ์ที่ 1
          </button>
        </div>
      </div>

      <!-- Scenario 2 -->
      <div class="checklist-item">
        <input type="checkbox" id="chk-s2" checked>
        <div class="checklist-content">
          <strong>สถานการณ์ที่ 2: สมาชิกผู้ใช้น้ำเข้าสู่ระบบเพื่อดูบิลและติดตามคำร้องส่วนตัว</strong>
          <p>
            1. สลับสิทธิ์เป็น "สมาชิกผู้ใช้น้ำ"<br>
            2. แถบ Topbar แสดงชิป <code>👤 สมาชิก: สมชาย ใจดี (รหัส: WY-001)</code><br>
            3. หน้าเว็บโหลดประวัติการใช้น้ำ กราฟสถิติ 6 เดือนย้อนหลัง และข้อมูลมิเตอร์ให้อัตโนมัติทันที
          </p>
          <button class="btn-action-test" onclick="loadPersona('member', 'portal_citizen.php?customer=WY-001')">
            ▶️ รันสถานการณ์ที่ 2
          </button>
        </div>
      </div>

      <!-- Scenario 3 -->
      <div class="checklist-item">
        <input type="checkbox" id="chk-s3" checked>
        <div class="checklist-content">
          <strong>สถานการณ์ที่ 3: พนักงานจดมิเตอร์ภาคสนามกรอกเลขมาตรวัดน้ำ (แบบ ป.17)</strong>
          <p>
            1. สลับสิทธิ์เป็น "จนท.จดมิเตอร์"<br>
            2. แถบ Sidebar จะแสดงเฉพาะ "สมุดจดมิเตอร์ (ป.17)"<br>
            3. กรอกเลขมิเตอร์ครั้งหลัง ระบบคำนวณหน่วยใช้และยอดเงินทันที พร้อมระบบเตือนตัวเลขผิดปกติหากค่าน้ำสูงกว่าค่าเฉลี่ย
          </p>
          <button class="btn-action-test" onclick="loadPersona('reader', 'meter_reading.php')">
            ▶️ รันสถานการณ์ที่ 3
          </button>
        </div>
      </div>

      <!-- Scenario 4 -->
      <div class="checklist-item">
        <input type="checkbox" id="chk-s4" checked>
        <div class="checklist-content">
          <strong>สถานการณ์ที่ 4: ฝ่ายการเงินตัดรับชำระเงิน ออกใบเสร็จมาตรฐาน และทำฎีกาเบิกจ่าย 10%</strong>
          <p>
            1. สลับสิทธิ์เป็น "ฝ่ายการเงินและเหรัญญิก"<br>
            2. กดตัดรับชำระเงิน สั่งพิมพ์ใบเสร็จรับเงินมาตรฐานพร้อมตัวหนังสือภาษาไทย เช่น "หนึ่งร้อยห้าสิบบาทถ้วน"<br>
            3. ตรวจสอบทะเบียนหนี้ค้างชำระ และเปิดดูฎีกาเบิกจ่ายค่าตอบแทนคนเก็บค่าน้ำ (10% จากยอดเก็บจริง)
          </p>
          <button class="btn-action-test" onclick="loadPersona('finance', 'finance_billing.php')">
            ▶️ รันสถานการณ์ที่ 4
          </button>
        </div>
      </div>

      <!-- Scenario 5 -->
      <div class="checklist-item">
        <input type="checkbox" id="chk-s5" checked>
        <div class="checklist-content">
          <strong>สถานการณ์ที่ 5: คณะกรรมการบริหารตรวจงบดุล กค.3 และวิเคราะห์น้ำสูญเสีย (NRW)</strong>
          <p>
            1. สลับสิทธิ์เป็น "คณะกรรมการ/ผู้บริหาร"<br>
            2. ตรวจสอบรายงานสรุปรายรับ-รายจ่าย แยกกระเป๋าเงินสดในมือ (5,000 บาท) และเงินฝากธนาคาร<br>
            3. ตรวจสอบกราฟวิเคราะห์น้ำสูญเสียในระบบส่งน้ำ (Non-Revenue Water)
          </p>
          <button class="btn-action-test" onclick="loadPersona('admin', 'executive_reports.php')">
            ▶️ รันสถานการณ์ที่ 5
          </button>
        </div>
      </div>
    </div>

  </main>

  <!-- Interactive Control Script -->
  <script>
    // Tab Switching
    function switchSuiteTab(tabId) {
      document.querySelectorAll('.suite-tab-btn').forEach(btn => btn.classList.remove('active'));
      document.querySelectorAll('.spec-pane').forEach(pane => pane.classList.remove('active'));
      
      const livePane = document.getElementById('pane-live-prototype');
      const toolbar = document.getElementById('prototype-toolbar');

      if (tabId === 'live-prototype') {
        livePane.style.display = 'flex';
        toolbar.style.display = 'flex';
      } else {
        livePane.style.display = 'none';
        toolbar.style.display = 'none';
        const targetPane = document.getElementById(`pane-${tabId}`);
        if (targetPane) targetPane.classList.add('active');
      }

      // Mark tab button as active
      event.currentTarget.classList.add('active');
    }

    // Viewport Resizing
    function setViewport(size) {
      const container = document.getElementById('frame-container');
      document.querySelectorAll('.viewport-btn').forEach(b => b.classList.remove('active'));
      event.currentTarget.classList.add('active');

      container.className = 'prototype-frame-container';
      if (size !== 'desktop') {
        container.classList.add(`viewport-${size}`);
      }
    }

    // Persona Loading with Seamless API Auth
    async function loadPersona(role, targetUrl) {
      document.querySelectorAll('.persona-chip').forEach(c => c.classList.remove('active'));
      const activeChip = document.getElementById(`chip-${role}`);
      if (activeChip) activeChip.classList.add('active');

      // Switch to live prototype tab if currently on spec
      const liveBtn = document.querySelector('.suite-tab-btn');
      if (!liveBtn.classList.contains('active')) {
        liveBtn.click();
      }

      try {
        if (role === 'guest') {
          await fetch('api/auth.php?action=logout');
        } else if (role === 'member') {
          const fd = new FormData();
          fd.append('action', 'login');
          fd.append('login_type', 'member');
          fd.append('customer_code', 'WY-001');
          await fetch('api/auth.php', { method: 'POST', body: fd });
        } else {
          const fd = new FormData();
          fd.append('action', 'login');
          fd.append('login_type', 'staff');
          fd.append('username', role);
          fd.append('password', '123456');
          await fetch('api/auth.php', { method: 'POST', body: fd });
        }

        // Reload iframe with target page
        const iframe = document.getElementById('prototype-iframe');
        iframe.src = targetUrl;
      } catch (err) {
        console.error('Error switching persona:', err);
      }
    }
  </script>
</body>
</html>

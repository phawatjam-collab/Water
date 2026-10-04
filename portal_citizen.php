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
    }
    .citizen-search-row input {
      flex: 1;
      padding: 14px 18px;
      border: 2px solid #cbd5e1;
      border-radius: 8px;
      font-size: 16px;
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
      border-radius: 8px;
      font-size: 15px;
      font-family: 'Prompt', sans-serif;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .btn-search:hover {
      background: #0369a1;
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
        <input type="text" id="citizen-search-input" placeholder="พิมพ์รหัสผู้ใช้น้ำ (เช่น WY-001) หรือบ้านเลขที่ (เช่น 12) หรือชื่อ-สกุล...">
        <button type="button" id="btn-search-bill" class="btn-search">
          <span>🔍</span> ค้นหาบิล
        </button>
      </div>
      <div class="quick-examples">
        ตัวอย่างคลิกค้นหาด่วน: 
        <a onclick="quickSearch('WY-001')">WY-001 (นายสมชาย)</a> | 
        <a onclick="quickSearch('WY-003')">WY-003 (มีค้างชำระ)</a> | 
        <a onclick="quickSearch('12')">บ้านเลขที่ 12</a>
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

    async function quickSearch(kw) {
      document.getElementById('citizen-search-input').value = kw;
      await performCitizenSearch();
    }

    document.getElementById('btn-search-bill')?.addEventListener('click', performCitizenSearch);
    document.getElementById('citizen-search-input')?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') performCitizenSearch();
    });

    async function performCitizenSearch() {
      const kw = document.getElementById('citizen-search-input').value.trim().toLowerCase();
      const container = document.getElementById('bill-result-container');
      if (!kw) {
        alert('กรุณากรอกรหัสผู้ใช้น้ำ, บ้านเลขที่ หรือชื่อ-สกุล');
        return;
      }

      container.style.display = 'block';
      container.innerHTML = '<div style="text-align: center; padding: 30px; color: #64748b;">⏳ กำลังค้นหาข้อมูลบิลค่าน้ำ...</div>';

      try {
        const res = await fetch(`api/readings.php?cycle=${currentCycleCode}`);
        if (!res.ok) throw new Error('API Error');
        const data = await res.json();
        const match = data.readings.find(r => 
          r.customer_code.toLowerCase().includes(kw) ||
          r.house_no.toLowerCase().includes(kw) ||
          r.first_name.toLowerCase().includes(kw) ||
          r.last_name.toLowerCase().includes(kw)
        );

        if (!match) {
          container.innerHTML = `
            <div class="bill-result-card" style="text-align: center; border-color: #fee2e2; background: #fff5f5;">
              <span style="font-size: 36px;">❌</span>
              <h4 style="color: #dc2626; margin-top: 8px;">ไม่พบข้อมูลบิลค่าน้ำที่ตรงกับ "${kw}"</h4>
              <p style="color: #64748b; font-size: 13.5px;">กรุณาตรวจสอบรหัสผู้ใช้น้ำหรือบ้านเลขที่ของท่านอีกครั้ง หรือติดต่อคณะกรรมการประปาหมู่บ้าน</p>
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
                <span style="background: #e0f2fe; color: #0284c7; font-size: 13px; font-weight: 700; padding: 3px 10px; border-radius: 4px;">
                  รหัสผู้ใช้น้ำ: ${match.customer_code}
                </span>
                <h2 style="font-family: 'Prompt', sans-serif; font-size: 22px; color: #0f172a; margin: 6px 0 2px 0;">
                  ${match.first_name} ${match.last_name}
                </h2>
                <div style="font-size: 14px; color: #64748b;">
                  🏠 บ้านเลขที่ ${match.house_no} | โซน: <strong>${match.zone}</strong> | 📞 เบอร์โทรศัพท์: <strong style="color: #0284c7;">${match.phone || '-'}</strong> | มาตรเลขที่: <strong>${match.meter_serial || '-'}</strong>
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

    // Auto-search if customer param is present in URL or logged-in member
    window.addEventListener('DOMContentLoaded', () => {
      const urlParams = new URLSearchParams(window.location.search);
      const custParam = urlParams.get('customer') || '<?php echo ($currentUser && ($currentUser['role'] ?? '') === 'member') ? addslashes($currentUser['customer_code'] ?? '') : ''; ?>';
      if (custParam) {
        const input = document.getElementById('citizen-search-input');
        if (input) {
          input.value = custParam;
          performCitizenSearch();
        }
      }
    });
  </script>
</body>
</html>

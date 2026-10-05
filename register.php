<?php
/**
 * หน้าลงทะเบียนเข้าใช้งาน (Member & Staff Registration)
 * การประปาหมู่บ้านวังยาง หมู่ที่ 3 ตำบลวังยาง
 */
require_once __DIR__ . '/sidebar.php';
$currentUser = getCurrentUser();

// ดึงข้อมูลโซนและขนาดมิเตอร์จากฐานข้อมูล
$db = getAuthDbConnection();
$zones = [];
$installTypes = [];
if ($db) {
    try {
        $zones = $db->query("SELECT * FROM tb_zone ORDER BY zone_id ASC")->fetchAll();
        $installTypes = $db->query("SELECT * FROM tb_installation ORDER BY install_id ASC")->fetchAll();
    } catch (Exception $e) {}
}

if (empty($zones)) {
    $zones = [
        ['zone_id' => '01', 'zone_name' => 'โซน 1 วังยางเหนือ'],
        ['zone_id' => '02', 'zone_name' => 'โซน 2 วังยางกลาง'],
        ['zone_id' => '03', 'zone_name' => 'โซน 3 วังยางใต้']
    ];
}
if (empty($installTypes)) {
    $installTypes = [
        ['install_id' => 1, 'install_name' => 'มิเตอร์ขนาด 5/8 นิ้ว'],
        ['install_id' => 2, 'install_name' => 'มิเตอร์ขนาด 1 นิ้ว']
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ลงทะเบียนเข้าใช้งาน - การประปาหมู่บ้านวังยาง</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <?php renderPwaHead(); ?>

  <style>
    body {
      background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 30px 16px;
      font-family: 'Sarabun', 'Prompt', sans-serif;
    }
    .register-card {
      background: #ffffff;
      border-radius: 14px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
      max-width: 600px;
      width: 100%;
      overflow: hidden;
    }
    .register-header {
      background: #0f172a;
      color: #fff;
      padding: 24px 30px;
      text-align: center;
      position: relative;
    }
    .register-header h1 {
      font-family: 'Prompt', sans-serif;
      font-size: 20px;
      font-weight: 700;
      color: #38bdf8;
      margin: 8px 0 2px 0;
    }
    .register-header p {
      font-size: 13.5px;
      color: #94a3b8;
      margin: 0;
    }
    .register-tabs {
      display: flex;
      background: #f1f5f9;
      padding: 6px;
      margin: 20px 24px 0 24px;
      border-radius: 10px;
      gap: 6px;
    }
    .reg-tab-btn {
      flex: 1;
      padding: 10px 14px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      font-family: inherit;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      color: #64748b;
      background: transparent;
    }
    .reg-tab-btn.active {
      background: #0284c7;
      color: #fff;
      box-shadow: 0 2px 6px rgba(2, 132, 199, 0.3);
    }
    .register-body {
      padding: 24px 28px 30px 28px;
    }
    .form-group {
      margin-bottom: 16px;
    }
    .form-group label {
      display: block;
      font-size: 13.5px;
      font-weight: 600;
      color: #334155;
      margin-bottom: 6px;
    }
    .form-row {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
    }
    @media (max-width: 540px) {
      .form-row {
        grid-template-columns: 1fr;
      }
    }
    .helper-text {
      font-size: 12px;
      color: #64748b;
      margin-top: 4px;
      display: block;
    }
    .btn-submit-reg {
      width: 100%;
      padding: 12px;
      font-size: 15px;
      font-weight: 700;
      border-radius: 8px;
      border: none;
      background: #0284c7;
      color: #fff;
      cursor: pointer;
      font-family: inherit;
      transition: all 0.2s ease;
      box-shadow: 0 4px 10px rgba(2, 132, 199, 0.25);
      margin-top: 10px;
    }
    .btn-submit-reg:hover {
      background: #0369a1;
      transform: translateY(-1px);
    }
  </style>
</head>
<body>

  <div class="register-card">
    <!-- Header -->
    <div class="register-header">
      <div style="font-size: 32px; display: inline-block;">💧</div>
      <h1>การประปาหมู่บ้านวังยาง</h1>
      <p>ระบบลงทะเบียนผู้ใช้งานออนไลน์ (Registration Portal)</p>
    </div>

    <!-- Segmented Tab Switcher -->
    <div class="register-tabs">
      <button type="button" class="reg-tab-btn active" id="tab-btn-member" onclick="switchRegTab('member')">
        <span>👤</span> สมาชิกผู้ใช้น้ำ (Member)
      </button>
      <button type="button" class="reg-tab-btn" id="tab-btn-staff" onclick="switchRegTab('staff')">
        <span>💼</span> เจ้าหน้าที่ประปา (Staff)
      </button>
    </div>

    <div class="register-body">
      <!-- Error / Success Alert Box -->
      <div id="reg-alert" style="display: none; padding: 12px 16px; border-radius: 8px; font-size: 13.5px; margin-bottom: 18px; font-weight: 500;"></div>

      <!-- =========================================================
           FORM 1: ลงทะเบียนสมาชิกผู้ใช้น้ำ (Member)
           ========================================================= -->
      <form id="form-register-member" onsubmit="handleRegistration(event, 'member')">
        <input type="hidden" name="role" value="member">

        <div class="form-row">
          <div class="form-group">
            <label>ชื่อจริง: <span style="color: #dc2626;">*</span></label>
            <input type="text" name="first_name" class="form-input" required placeholder="เช่น สมหมาย" style="width: 100%;">
          </div>
          <div class="form-group">
            <label>นามสกุล: <span style="color: #dc2626;">*</span></label>
            <input type="text" name="last_name" class="form-input" required placeholder="เช่น ใจสะอาด" style="width: 100%;">
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>บ้านเลขที่: <span style="color: #dc2626;">*</span></label>
            <input type="text" name="house_no" class="form-input" required placeholder="เช่น 12/3 ม.1" style="width: 100%;">
            <span class="helper-text">* หากมีในระบบแล้ว ระบบจะเชื่อมโยงข้อมูลให้อัตโนมัติ</span>
          </div>
          <div class="form-group">
            <label>คุ้ม / โซนสายน้ำ: <span style="color: #dc2626;">*</span></label>
            <select name="zone_id" class="form-select" style="width: 100%;">
              <?php foreach ($zones as $z): ?>
                <option value="<?php echo htmlspecialchars($z['zone_id']); ?>">
                  <?php echo htmlspecialchars($z['zone_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>เบอร์โทรศัพท์: <span style="color: #dc2626;">*</span></label>
            <input type="tel" name="phone" class="form-input" required placeholder="08xxxxxxxx" maxlength="10" style="width: 100%;">
          </div>
          <div class="form-group">
            <label>ขนาดมาตรวัดน้ำ:</label>
            <select name="install_type_id" class="form-select" style="width: 100%;">
              <?php foreach ($installTypes as $inst): ?>
                <option value="<?php echo htmlspecialchars($inst['install_id']); ?>">
                  <?php echo htmlspecialchars($inst['install_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="border-top: 1px dashed #e2e8f0; margin: 16px 0; padding-top: 14px;">
          <span style="font-size: 13px; font-weight: 700; color: #0284c7; display: block; margin-bottom: 12px;">
            🔐 ข้อมูลสำหรับเข้าสู่ระบบ (Login Credentials)
          </span>

          <div class="form-group">
            <label>ชื่อผู้ใช้งาน (Username): <span style="color: #dc2626;">*</span></label>
            <input type="text" name="username" class="form-input" required placeholder="เช่น sommai หรือ wy010" minlength="3" style="width: 100%;">
            <span class="helper-text">* ภาษาอังกฤษหรือตัวเลขอย่างน้อย 3 ตัวอักษร</span>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>รหัสผ่าน (Password): <span style="color: #dc2626;">*</span></label>
              <input type="password" name="password" class="form-input" required placeholder="••••••••" minlength="4" style="width: 100%;">
            </div>
            <div class="form-group">
              <label>ยืนยันรหัสผ่าน: <span style="color: #dc2626;">*</span></label>
              <input type="password" name="confirm_password" class="form-input" required placeholder="••••••••" minlength="4" style="width: 100%;">
            </div>
          </div>
        </div>

        <button type="submit" class="btn-submit-reg" id="btn-submit-member">
          🚀 ยืนยันการสมัครสมาชิกผู้ใช้น้ำ
        </button>
      </form>

      <!-- =========================================================
           FORM 2: ลงทะเบียนเจ้าหน้าที่การประปา (Staff)
           ========================================================= -->
      <form id="form-register-staff" onsubmit="handleRegistration(event, 'staff')" style="display: none;">
        <input type="hidden" name="role" value="staff">

        <div class="form-group">
          <label>ชื่อ - นามสกุล เจ้าหน้าที่: <span style="color: #dc2626;">*</span></label>
          <input type="text" name="fullname" class="form-input" required placeholder="เช่น นายสมาน ช่างน้ำ" style="width: 100%;">
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>ตำแหน่ง / ความรับผิดชอบ:</label>
            <select name="position" class="form-select" style="width: 100%;">
              <option value="เจ้าหน้าที่จดมาตรวัดน้ำภาคสนาม">เจ้าหน้าที่จดมาตรวัดน้ำภาคสนาม (Field Reader)</option>
              <option value="เจ้าหน้าที่การเงินและบัญชี">เจ้าหน้าที่การเงินและบัญชี (Finance & Billing)</option>
              <option value="ช่างซ่อมบำรุงระบบประปา">ช่างซ่อมบำรุงระบบประปา (Technician)</option>
              <option value="เจ้าหน้าที่บริการประชาชน">เจ้าหน้าที่บริการประชาชน (Citizen Support)</option>
            </select>
          </div>
          <div class="form-group">
            <label>เบอร์โทรศัพท์เจ้าหน้าที่:</label>
            <input type="tel" name="phone" class="form-input" placeholder="08xxxxxxxx" maxlength="10" style="width: 100%;">
          </div>
        </div>

        <div style="border-top: 1px dashed #e2e8f0; margin: 16px 0; padding-top: 14px;">
          <span style="font-size: 13px; font-weight: 700; color: #0284c7; display: block; margin-bottom: 12px;">
            🔐 ข้อมูลเข้าสู่ระบบและรหัสความปลอดภัย (Staff Verification)
          </span>

          <div class="form-group">
            <label>ชื่อผู้ใช้งาน (Username): <span style="color: #dc2626;">*</span></label>
            <input type="text" name="username" class="form-input" required placeholder="เช่น saman_staff" minlength="3" style="width: 100%;">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>รหัสผ่าน (Password): <span style="color: #dc2626;">*</span></label>
              <input type="password" name="password" class="form-input" required placeholder="••••••••" minlength="4" style="width: 100%;">
            </div>
            <div class="form-group">
              <label>ยืนยันรหัสผ่าน: <span style="color: #dc2626;">*</span></label>
              <input type="password" name="confirm_password" class="form-input" required placeholder="••••••••" minlength="4" style="width: 100%;">
            </div>
          </div>

          <div class="form-group" style="background: #fffbeb; border: 1px solid #fef3c7; padding: 12px; border-radius: 8px;">
            <label style="color: #92400e; font-weight: 700;">
              🛡️ รหัสยืนยันเจ้าหน้าที่ (Staff Security Key): <span style="color: #dc2626;">*</span>
            </label>
            <input type="text" name="staff_key" class="form-input" required placeholder="กรอกรหัสความปลอดภัยเพื่อรับสิทธิ์ staff" style="width: 100%; border-color: #fcd34d;">
            <span class="helper-text" style="color: #b45309;">
              * ป้องกันบุคคลภายนอกสมัครสิทธิ์เจ้าหน้าที่ (ติดต่อผู้ดูแลระบบประปา หรือทดสอบด้วยรหัส: <code>STAFF-WY2567</code>)
            </span>
          </div>
        </div>

        <button type="submit" class="btn-submit-reg" id="btn-submit-staff">
          🚀 ยืนยันการลงทะเบียนเจ้าหน้าที่การประปา
        </button>
      </form>

      <!-- Back to Login / Home Footer -->
      <div style="text-align: center; margin-top: 20px; padding-top: 16px; border-top: 1px solid #f1f5f9; font-size: 13.5px; color: #64748b;">
        มีบัญชีผู้ใช้อยู่แล้ว? 
        <a href="index.php?open_login=1" style="color: #0284c7; text-decoration: none; font-weight: 700;">
          🔐 เข้าสู่ระบบที่นี่ &rarr;
        </a>
        <br>
        <a href="index.php" style="color: #94a3b8; text-decoration: none; font-size: 12.5px; margin-top: 8px; display: inline-block;">
          &larr; กลับหน้าหลักการประปา
        </a>
      </div>
    </div>
  </div>

  <!-- Toast Notification Container -->
  <div class="toast-container"></div>

  <script>
    function switchRegTab(tab) {
      const memberForm = document.getElementById('form-register-member');
      const staffForm = document.getElementById('form-register-staff');
      const memberBtn = document.getElementById('tab-btn-member');
      const staffBtn = document.getElementById('tab-btn-staff');
      const alertBox = document.getElementById('reg-alert');
      if (alertBox) alertBox.style.display = 'none';

      if (tab === 'member') {
        memberForm.style.display = 'block';
        staffForm.style.display = 'none';
        memberBtn.classList.add('active');
        staffBtn.classList.remove('active');
      } else {
        memberForm.style.display = 'none';
        staffForm.style.display = 'block';
        staffBtn.classList.add('active');
        memberBtn.classList.remove('active');
      }
    }

    async function handleRegistration(e, role) {
      e.preventDefault();
      const form = e.target;
      const submitBtn = document.getElementById(`btn-submit-${role}`);
      const alertBox = document.getElementById('reg-alert');

      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      if (data.password !== data.confirm_password) {
        showAlert('⚠️ รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน กรุณาตรวจสอบอีกครั้ง', 'error');
        return;
      }

      submitBtn.disabled = true;
      submitBtn.textContent = '⏳ กำลังบันทึกข้อมูล...';
      alertBox.style.display = 'none';

      try {
        const res = await fetch('api/register.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });

        const resData = await res.json();

        if (res.ok && resData.success) {
          showAlert(`🎉 ${resData.message}! กำลังนำท่านเข้าสู่ระบบ...`, 'success');
          setTimeout(() => {
            window.location.href = resData.redirect || 'index.php';
          }, 1500);
        } else {
          showAlert(`❌ ${resData.error || 'เกิดข้อผิดพลาดในการลงทะเบียน'}`, 'error');
          submitBtn.disabled = false;
          submitBtn.textContent = role === 'member' ? '🚀 ยืนยันการสมัครสมาชิกผู้ใช้น้ำ' : '🚀 ยืนยันการลงทะเบียนเจ้าหน้าที่การประปา';
        }
      } catch (err) {
        console.error(err);
        showAlert('❌ ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้ กรุณาลองใหม่อีกครั้ง', 'error');
        submitBtn.disabled = false;
        submitBtn.textContent = role === 'member' ? '🚀 ยืนยันการสมัครสมาชิกผู้ใช้น้ำ' : '🚀 ยืนยันการลงทะเบียนเจ้าหน้าที่การประปา';
      }
    }

    function showAlert(msg, type) {
      const alertBox = document.getElementById('reg-alert');
      if (!alertBox) return;
      alertBox.style.display = 'block';
      if (type === 'success') {
        alertBox.style.background = '#f0fdf4';
        alertBox.style.border = '1px solid #bbf7d0';
        alertBox.style.color = '#166534';
      } else {
        alertBox.style.background = '#fee2e2';
        alertBox.style.border = '1px solid #fca5a5';
        alertBox.style.color = '#b91c1c';
      }
      alertBox.innerHTML = msg;
    }

    // Auto switch tab if requested via URL query string (?tab=staff or ?tab=member)
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const tabParam = urlParams.get('tab');
      if (tabParam === 'staff') {
        switchRegTab('staff');
      } else if (tabParam === 'member') {
        switchRegTab('member');
      }
    });
  </script>
</body>
</html>

<?php
/**
 * แถบเมนูด้านข้างหลัก (Global App Sidebar & Route Manager)
 * จัดการ Routing ลิงก์ไปยังหน้าบริการแยกย่อย แบ่งหมวดหมู่ฟังก์ชันงานตามสิทธิ์ (RBAC)
 */
require_once __DIR__ . '/auth.php';

function renderPwaHead() {
    // PWA functionality disabled
}


function renderAppSidebar($activeRoute = 'home') {
    $currentUser = getCurrentUser();
    $currentRole = $currentUser['role'] ?? 'guest';

    // กรองและกำหนดหมวดหมู่เมนูตามระดับสิทธิ์ของผู้ใช้งานจริง (Role-Based Access Control)
    $sections = [];

    if (!$currentUser || $currentRole === 'guest') {
        // 1. ระดับประชาชนทั่วไป / ผู้เยี่ยมชม (Public Citizen / Guest)
        $sections = [
            [
                'title' => 'บริการประชาชนทั่วไป (Public)',
                'icon' => '👥',
                'items' => [
                    [
                        'id' => 'home',
                        'title' => 'หน้าแรก / ตรวจสอบค่าน้ำ',
                        'icon' => '🏠',
                        'url' => 'index.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'citizen_services',
                        'title' => 'แจ้งท่อแตก / ยื่นคำร้อง',
                        'icon' => '🔧',
                        'url' => 'index.php#citizen-services',
                        'is_sub' => true
                    ],
                    [
                        'id' => 'register',
                        'title' => 'ลงทะเบียนสมาชิก / เจ้าหน้าที่',
                        'icon' => '📝',
                        'url' => 'register.php',
                        'is_sub' => false
                    ]
                ]
            ]
        ];
    } elseif ($currentRole === 'member') {
        // 2. ระดับสมาชิกผู้ใช้น้ำประจำหมู่บ้าน (Member Portal)
        $memberCustomerCode = $currentUser['customer_code'] ?? '';
        $billUrl = 'index.php' . (!empty($memberCustomerCode) ? '?customer=' . urlencode($memberCustomerCode) : '');
        $sections = [
            [
                'title' => 'บริการสมาชิกผู้ใช้น้ำ (Member)',
                'icon' => '👤',
                'items' => [
                    [
                        'id' => 'home',
                        'title' => 'หน้าแรก',
                        'icon' => '🏠',
                        'url' => 'index.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'member_bill',
                        'title' => 'ข้อมูล & บิลค่าน้ำของฉัน',
                        'icon' => '🧾',
                        'url' => $billUrl,
                        'is_sub' => false
                    ],
                    [
                        'id' => 'citizen_services',
                        'title' => 'แจ้งท่อแตก / ติดตามคำร้อง',
                        'icon' => '🔧',
                        'url' => 'index.php#citizen-services',
                        'is_sub' => true
                    ]
                ]
            ]
        ];
    } elseif ($currentRole === 'staff' || $currentRole === 'reader' || $currentRole === 'finance') {
        // 3. ระดับเจ้าหน้าที่การประปา (Staff - รวมงานปฏิบัติการภาคสนามและการเงิน)
        $sections = [
            [
                'title' => 'บริการทั่วไป',
                'icon' => '🌐',
                'items' => [
                    [
                        'id' => 'home',
                        'title' => 'หน้าแรก',
                        'icon' => '🏠',
                        'url' => 'index.php',
                        'is_sub' => false
                    ],
                    
                ]
            ],
            [
                'title' => 'งานปฏิบัติการเจ้าหน้าที่ (Staff)',
                'icon' => '💼',
                'items' => [
                    [
                        'id' => 'field',
                        'title' => 'จดมิเตอร์น้ำประปา',
                        'icon' => '📝',
                        'url' => 'meter_reading.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'add_customer',
                        'title' => 'เพิ่มข้อมูลผู้ใช้น้ำใหม่',
                        'icon' => '➕',
                        'url' => 'meter_reading.php?action=add_customer',
                        'is_sub' => true
                    ],
                    [
                        'id' => 'finance',
                        'title' => 'รับชำระเงิน & ออกใบเสร็จ',
                        'icon' => '🧾',
                        'url' => 'finance_billing.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'arrears',
                        'title' => 'ติดตามยอดค้างชำระ & พิมพ์ใบเตือน',
                        'icon' => '⚠️',
                        'url' => 'finance_billing.php#pane-arrears',
                        'is_sub' => true
                    ],
                    [
                        'id' => 'vouchers',
                        'title' => 'สรุปค่าใช้จ่าย & ค่าตอบแทนคนจด (10%)',
                        'icon' => '📜',
                        'url' => 'finance_billing.php#pane-vouchers',
                        'is_sub' => true
                    ],
                    [
                        'id' => 'members',
                        'title' => 'ทะเบียนสมาชิกผู้ใช้น้ำ',
                        'icon' => '👥',
                        'url' => 'executive_reports.php#pane-members',
                        'is_sub' => true
                    ],
                    [
                        'id' => 'qr_labels',
                        'title' => 'พิมพ์ QR สติกเกอร์มิเตอร์',
                        'icon' => '🏷️',
                        'url' => 'print_qr_labels.php',
                        'is_sub' => true
                    ]
                ]
            ]
        ];
    } elseif ($currentRole === 'admin') {
        // 4. ระดับผู้ดูแลระบบ / คณะกรรมการบริหาร (Admin / Executive - ครบทุกโมดูล)
        $sections = [
            [
                'title' => 'ภาพรวม & บริหารระบบ (Admin)',
                'icon' => '🏛️',
                'items' => [
                    [
                        'id' => 'home',
                        'title' => 'หน้าแรก',
                        'icon' => '🏠',
                        'url' => 'index.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'dashboard',
                        'title' => 'แดชบอร์ดสรุปผลรวม',
                        'icon' => '📈',
                        'url' => 'dashboard.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'executive',
                        'title' => 'งบการเงิน & รายงาน',
                        'icon' => '📊',
                        'url' => 'executive_reports.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'members',
                        'title' => 'ทะเบียนสมาชิกผู้ใช้น้ำ',
                        'icon' => '👥',
                        'url' => 'executive_reports.php#pane-members',
                        'is_sub' => true
                    ],
                    [
                        'id' => 'policy',
                        'title' => 'ตั้งค่าอัตราค่าน้ำ & นโยบาย',
                        'icon' => '⚙️',
                        'url' => 'executive_reports.php#pane-policy',
                        'is_sub' => true
                    ]
                ]
            ],
            [
                'title' => 'งานปฏิบัติการ (Staff Operations)',
                'icon' => '🛠️',
                'items' => [
                    [
                        'id' => 'field',
                        'title' => 'จดมิเตอร์น้ำประปา',
                        'icon' => '📝',
                        'url' => 'meter_reading.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'add_customer',
                        'title' => 'เพิ่มข้อมูลผู้ใช้น้ำใหม่',
                        'icon' => '➕',
                        'url' => 'meter_reading.php?action=add_customer',
                        'is_sub' => true
                    ],
                    [
                        'id' => 'finance',
                        'title' => 'รับชำระเงิน & ออกใบเสร็จ',
                        'icon' => '🧾',
                        'url' => 'finance_billing.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'arrears',
                        'title' => 'ติดตามยอดค้างชำระ & พิมพ์ใบเตือน',
                        'icon' => '⚠️',
                        'url' => 'finance_billing.php#pane-arrears',
                        'is_sub' => true
                    ],
                    [
                        'id' => 'vouchers',
                        'title' => 'สรุปค่าใช้จ่าย & ค่าตอบแทนคนจด (10%)',
                        'icon' => '📜',
                        'url' => 'finance_billing.php#pane-vouchers',
                        'is_sub' => true
                    ],
                    
                    [
                        'id' => 'qr_labels',
                        'title' => 'พิมพ์ QR สติกเกอร์มิเตอร์',
                        'icon' => '🏷️',
                        'url' => 'print_qr_labels.php',
                        'is_sub' => true
                    ]
                ]
            ]
        ];
    }
    ?>
    <!-- Sidebar Navigation Component -->
    <aside class="sidebar no-print">
      <!-- Brand Header -->
      <div class="brand">
        <div class="brand-icon">💧</div>
        <div class="brand-title">
          <a href="index.php" style="text-decoration: none;">
            <h2>การประปาหมู่บ้านวังยาง</h2>
            <span>ระบบจัดการน้ำประปา</span>
          </a>
        </div>
        <button type="button" class="btn-sidebar-close no-print" onclick="toggleAppSidebar(false)" aria-label="ปิดเมนู" title="ปิดเมนู">✕</button>
      </div>

      <!-- Navigation Menu (Strictly Filtered by Role) -->
      <nav class="nav-menu">
        <?php foreach ($sections as $sec): ?>
          <div class="sidebar-section-header">
            <span class="sec-icon"><?php echo $sec['icon']; ?></span>
            <span><?php echo htmlspecialchars($sec['title']); ?></span>
          </div>

          <?php foreach ($sec['items'] as $item): ?>
            <?php
              $isActive = ($activeRoute === $item['id']);
              $subClass = !empty($item['is_sub']) ? 'nav-item-sub' : '';
            ?>
            <a href="<?php echo htmlspecialchars($item['url']); ?>" <?php echo !empty($item['target']) ? 'target="' . htmlspecialchars($item['target']) . '" rel="noopener noreferrer"' : ''; ?> class="nav-item <?php echo $subClass; ?> <?php echo $isActive ? 'active' : ''; ?>">
              <span class="icon"><?php echo $item['icon']; ?></span>
              <span style="flex: 1;"><?php echo htmlspecialchars($item['title']); ?></span>
            </a>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </nav>
    </aside>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop no-print" onclick="toggleAppSidebar(false)"></div>
    <?php
}

function renderAppTopBar($title, $subtitle) {
    $currentUser = getCurrentUser();
    ?>
    <header class="top-bar no-print">
      <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
        <button type="button" class="btn-sidebar-toggle no-print" onclick="toggleAppSidebar(true)" aria-label="เปิดเมนู" title="เปิดเมนู">
          <span>☰</span>
        </button>
        <?php if (!empty($title)): ?>
        <div class="top-bar-title">
          <h1><?php echo htmlspecialchars($title); ?></h1>
          <span><?php echo htmlspecialchars($subtitle); ?></span>
        </div>
        <?php endif; ?>
      </div>
      <div class="top-bar-actions">
        <?php if ($currentUser): ?>
          <div class="user-profile-chip" style="display: flex; align-items: center; gap: 8px; background: <?php echo $currentUser['badge_bg'] ?? '#f1f5f9'; ?>; padding: 6px 14px; border-radius: 9999px; font-size: 13px; color: <?php echo $currentUser['badge_color'] ?? '#0f172a'; ?>; border: 1px solid rgba(0,0,0,0.08);">
            <span>👤</span>
            <?php if (($currentUser['role'] ?? '') === 'member'): ?>
              <strong>สมาชิก: <?php echo htmlspecialchars($currentUser['name']); ?> (รหัส: <?php echo htmlspecialchars($currentUser['customer_code'] ?? ''); ?>)</strong>
            <?php else: ?>
              <strong><?php echo htmlspecialchars($currentUser['name']); ?> (<?php echo htmlspecialchars($currentUser['role_title']); ?>)</strong>
            <?php endif; ?>
          </div>
          <button type="button" class="btn btn-outline btn-open-login" onclick="openLoginModal()" style="font-size: 12.5px; padding: 6px 10px; border-radius: 6px; cursor: pointer; color: #475569; border: 1px solid #cbd5e1; background: #fff;" title="สลับบทบาท">
            🔄 สลับบทบาท
          </button>
          <a href="api/auth.php?action=logout" style="color: #dc2626; font-size: 13px; font-weight: 600; text-decoration: none; padding: 6px 12px; background: #fee2e2; border-radius: 6px; border: 1px solid #fecaca; display: inline-flex; align-items: center; gap: 4px;">
            <span>🚪</span> ออกจากระบบ
          </a>
        <?php else: ?>
          <span style="font-size: 13.5px; color: #64748b; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
            <span>🌐</span> ประชาชนทั่วไป (Public / Guest)
          </span>
          <button type="button" class="btn btn-primary btn-open-login" onclick="openLoginModal()" style="font-size: 13.5px; padding: 8px 16px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-family: inherit;">
            🔐 เข้าสู่ระบบ (Admin / Staff / Member)
          </button>
        <?php endif; ?>
      </div>
    </header>

    <!-- Unified Login Modal for 3 Core Roles (Admin, Staff, Member) -->
    <div id="login-modal" class="modal" style="display: none;">
      <div class="modal-dialog" style="max-width: 480px;">
        <div class="modal-header" style="background: #0f172a; color: #fff; padding: 16px 20px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="background: #0284c7; font-size: 20px; width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">🔐</div>
            <div>
              <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #38bdf8;">เข้าสู่ระบบจัดการน้ำประปา</h4>
              <span style="font-size: 13px; color: #94a3b8;">3 บทบาทหลัก: Admin, Staff, Member</span>
            </div>
          </div>
          <button type="button" class="modal-close" onclick="closeLoginModal()" style="color: #94a3b8; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
        </div>

        <div class="modal-body" style="padding: 20px 24px;">
          <?php if (isset($_GET['error'])): ?>
            <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 10px 14px; border-radius: 6px; font-size: 13.5px; margin-bottom: 14px; text-align: center;">
              ⚠️ ข้อมูลเข้าสู่ระบบไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง
            </div>
          <?php endif; ?>

          <!-- Quick 1-Click Role Switcher -->
          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 12px; margin-bottom: 16px;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b; display: block; margin-bottom: 6px;">⚡ เข้าสู่ระบบด่วน 3 บทบาทหลัก (1-Click Test Access):</span>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;">
              <button type="button" onclick="quickLoginRole('admin')" style="background: #f3e8ff; color: #7c3aed; border: 1px solid #d8b4fe; border-radius: 6px; padding: 7px 4px; font-size: 12.5px; font-weight: 600; cursor: pointer;">👑 Admin</button>
              <button type="button" onclick="quickLoginRole('staff')" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; border-radius: 6px; padding: 7px 4px; font-size: 12.5px; font-weight: 600; cursor: pointer;">💼 Staff</button>
              <button type="button" onclick="quickLoginRole('member')" style="background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; border-radius: 6px; padding: 7px 4px; font-size: 12.5px; font-weight: 600; cursor: pointer;">👤 Member</button>
            </div>
          </div>

          <!-- Login Type Switch Tabs -->
          <div style="display: flex; gap: 8px; margin-bottom: 20px; background: #f1f5f9; padding: 4px; border-radius: 8px;">
            <button type="button" id="tab-btn-member" class="login-tab-btn active" onclick="switchLoginTab('member')" style="flex: 1; padding: 8px 12px; border: none; border-radius: 6px; font-size: 13.5px; font-weight: 600; cursor: pointer; background: #0284c7; color: #fff; font-family: inherit; transition: all 0.15s;">
              👤 สมาชิกผู้ใช้น้ำ (Member)
            </button>
            <button type="button" id="tab-btn-staff" class="login-tab-btn" onclick="switchLoginTab('staff')" style="flex: 1; padding: 8px 12px; border: none; border-radius: 6px; font-size: 13.5px; font-weight: 600; cursor: pointer; background: transparent; color: #64748b; font-family: inherit; transition: all 0.15s;">
              💼 เจ้าหน้าที่ / แอดมิน (Staff / Admin)
            </button>
          </div>

          <!-- Form 1: Member Login -->
          <form id="member-login-form" action="api/auth.php" method="POST" style="display: block;">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="login_type" value="member">
            
            <div style="margin-bottom: 14px;">
              <label style="font-weight: 600; font-size: 13.5px; color: #334155; display: block; margin-bottom: 6px;">📞 เบอร์โทรศัพท์ (Key หลัก) หรือรหัสผู้ใช้น้ำ:</label>
              <input type="text" name="customer_code" class="form-input" required placeholder="เช่น 081-234-5678 หรือ WY-001 หรือ 12/3" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
              <span style="font-size: 12.5px; color: #64748b; margin-top: 4px; display: block;">* สมาชิกผู้ใช้น้ำใช้เบอร์โทรศัพท์มือถือ (Key หลัก) หรือรหัส WY-001</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; font-size: 14.5px; font-weight: 600; border-radius: 6px; background: #0284c7; color: #fff; border: none; cursor: pointer; font-family: inherit; margin-top: 8px;">
              🚀 เข้าสู่ระบบสมาชิกผู้ใช้น้ำ (Member)
            </button>

            <div style="text-align: center; margin-top: 14px; padding-top: 12px; border-top: 1px dashed #e2e8f0; display: flex; flex-direction: column; gap: 8px;">
              <!-- Registration link removed as per request -->
              <a href="index.php" style="font-size: 12.5px; color: #64748b; text-decoration: none; font-weight: 500; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                <span>🌐</span> ประชาชนทั่วไป ค้นหาค่าน้ำหรือแจ้งซ่อมโดยไม่ต้องล็อกอิน &rarr;
              </a>
            </div>
          </form>

          <!-- Form 2: Staff / Admin Login -->
          <form id="staff-login-form" action="api/auth.php" method="POST" style="display: none;">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="login_type" value="staff">

            <div style="margin-bottom: 14px;">
              <label style="font-weight: 600; font-size: 13.5px; color: #334155; display: block; margin-bottom: 6px;">ชื่อผู้ใช้งาน (Username):</label>
              <input type="text" name="username" class="form-input" required placeholder="เช่น staff หรือ admin" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 16px;">
              <label style="font-weight: 600; font-size: 13.5px; color: #334155; display: block; margin-bottom: 6px;">รหัสผ่าน (Password):</label>
              <input type="password" name="password" class="form-input" required placeholder="•••••••• (ค่าเริ่มต้น: 123456)" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; font-size: 14.5px; font-weight: 600; border-radius: 6px; background: #0284c7; color: #fff; border: none; cursor: pointer; font-family: inherit;">
              🚀 เข้าสู่ระบบเจ้าหน้าที่ / แอดมิน
            </button>

            <div style="text-align: center; margin-top: 14px; padding-top: 12px; border-top: 1px dashed #e2e8f0;">
              <a href="register.php?tab=staff" style="font-size: 13px; color: #0284c7; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                <span>📝</span> เจ้าหน้าที่ใหม่? ลงทะเบียนบัญชีเจ้าหน้าที่การประปา &rarr;
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
      function openLoginModal() {
        const modal = document.getElementById('login-modal');
        if (modal) {
          modal.style.display = 'flex';
          modal.classList.add('show');
        }
      }

      function closeLoginModal() {
        const modal = document.getElementById('login-modal');
        if (modal) {
          modal.style.display = 'none';
          modal.classList.remove('show');
        }
      }

      function switchLoginTab(type) {
        const memberForm = document.getElementById('member-login-form');
        const staffForm = document.getElementById('staff-login-form');
        const memberTabBtn = document.getElementById('tab-btn-member');
        const staffTabBtn = document.getElementById('tab-btn-staff');

        if (type === 'member') {
          if (memberForm) memberForm.style.display = 'block';
          if (staffForm) staffForm.style.display = 'none';
          if (memberTabBtn) {
            memberTabBtn.style.background = '#0284c7';
            memberTabBtn.style.color = '#fff';
          }
          if (staffTabBtn) {
            staffTabBtn.style.background = 'transparent';
            staffTabBtn.style.color = '#64748b';
          }
        } else {
          if (memberForm) memberForm.style.display = 'none';
          if (staffForm) staffForm.style.display = 'block';
          if (staffTabBtn) {
            staffTabBtn.style.background = '#0284c7';
            staffTabBtn.style.color = '#fff';
          }
          if (memberTabBtn) {
            memberTabBtn.style.background = 'transparent';
            memberTabBtn.style.color = '#64748b';
          }
        }
      }

      function quickLoginRole(role) {
        if (role === 'admin') {
          switchLoginTab('staff');
          const uInput = document.querySelector('#staff-login-form input[name="username"]');
          const pInput = document.querySelector('#staff-login-form input[name="password"]');
          if (uInput) uInput.value = 'admin';
          if (pInput) pInput.value = '123456';
          document.getElementById('staff-login-form')?.submit();
        } else if (role === 'staff') {
          switchLoginTab('staff');
          const uInput = document.querySelector('#staff-login-form input[name="username"]');
          const pInput = document.querySelector('#staff-login-form input[name="password"]');
          if (uInput) uInput.value = 'staff';
          if (pInput) pInput.value = '123456';
          document.getElementById('staff-login-form')?.submit();
        } else if (role === 'member') {
          switchLoginTab('member');
          const cInput = document.querySelector('#member-login-form input[name="customer_code"]');
          if (cInput) cInput.value = 'WY-001';
          document.getElementById('member-login-form')?.submit();
        }
      }

      window.addEventListener('click', function(e) {
        const modal = document.getElementById('login-modal');
        if (e.target === modal) {
          closeLoginModal();
        }
      });

      window.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('login_required') || urlParams.has('open_login') || urlParams.has('error')) {
          openLoginModal();
        }

      });

      // Ensure any legacy Service Workers or PWA caches are completely unregistered
      if ('serviceWorker' in navigator) {
        navigator.serviceWorker.getRegistrations().then(function(registrations) {
          for (let reg of registrations) {
            reg.unregister();
          }
        });
      }
      if (window.caches) {
        caches.keys().then(function(names) {
          for (let name of names) {
            caches.delete(name);
          }
        });
      }

      // Mobile Sidebar Drawer Toggle Controller
      window.toggleAppSidebar = function(forceState) {
        const sidebar = document.querySelector('.sidebar');
        const backdrop = document.querySelector('.sidebar-backdrop');
        if (!sidebar) return;
        const isOpen = (typeof forceState === 'boolean') ? forceState : !sidebar.classList.contains('sidebar-active');
        sidebar.classList.toggle('sidebar-active', isOpen);
        if (backdrop) backdrop.classList.toggle('show', isOpen);
        document.body.classList.toggle('sidebar-open', isOpen);
      };

      document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.sidebar .nav-item').forEach(link => {
          link.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
              window.toggleAppSidebar(false);
            }
          });
        });

        window.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') {
            window.toggleAppSidebar(false);
          }
        });
      });
    </script>
    <?php
}


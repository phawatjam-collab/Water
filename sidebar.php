<?php
/**
 * แถบเมนูด้านข้างหลัก (Global App Sidebar & Route Manager)
 * จัดการ Routing ลิงก์ไปยังหน้าบริการแยกย่อย แบ่งหมวดหมู่ฟังก์ชันงานตามสิทธิ์ (RBAC)
 */
require_once __DIR__ . '/auth.php';

function renderAppSidebar($activeRoute = 'home') {
    $currentUser = getCurrentUser();
    $currentRole = $currentUser['role'] ?? 'guest';

    // กรองและกำหนดหมวดหมู่เมนูตามระดับสิทธิ์ของผู้ใช้งานจริง (Role-Based Access Control)
    $sections = [];

    if (!$currentUser || $currentRole === 'guest') {
        // 1. ระดับประชาชนทั่วไป / ผู้ใช้ทั่วไป (Public Citizen Portal - ยังไม่เข้าสู่ระบบ)
        $sections = [
            [
                'title' => 'บริการประชาชนทั่วไป',
                'icon' => '👥',
                'items' => [
                    [
                        'id' => 'home',
                        'title' => 'หน้าแรก',
                        'icon' => '🏠',
                        'url' => 'index.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'citizen',
                        'title' => 'ตรวจสอบค่าน้ำออนไลน์',
                        'icon' => '🔍',
                        'url' => 'portal_citizen.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'citizen_services',
                        'title' => 'แจ้งท่อแตก / ยื่นคำร้อง',
                        'icon' => '🔧',
                        'url' => 'portal_citizen.php#citizen-services',
                        'is_sub' => true
                    ]
                ]
            ]
        ];
    } elseif ($currentRole === 'member') {
        // 2. ระดับสมาชิกผู้ใช้น้ำประจำหมู่บ้าน (Member Portal)
        $memberCustomerCode = $currentUser['customer_code'] ?? '';
        $billUrl = 'portal_citizen.php' . (!empty($memberCustomerCode) ? '?customer=' . urlencode($memberCustomerCode) : '');
        $sections = [
            [
                'title' => 'บริการสมาชิกผู้ใช้น้ำ',
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
                        'url' => 'portal_citizen.php#citizen-services',
                        'is_sub' => true
                    ]
                ]
            ]
        ];
    } elseif ($currentRole === 'reader') {
        // 3. ระดับเจ้าหน้าที่จดมิเตอร์ภาคสนาม (Reader)
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
                    ]
                ]
            ],
            [
                'title' => 'งานภาคสนาม',
                'icon' => '🚶‍♂️',
                'items' => [
                    [
                        'id' => 'field',
                        'title' => 'สมุดจดมิเตอร์ (ป.17)',
                        'icon' => '📝',
                        'url' => 'meter_reading.php',
                        'is_sub' => false
                    ]
                ]
            ]
        ];
    } elseif ($currentRole === 'finance') {
        // 4. ระดับฝ่ายการเงินและเหรัญญิก (Finance)
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
                    ]
                ]
            ],
            [
                'title' => 'งานการเงินและบัญชี',
                'icon' => '💼',
                'items' => [
                    [
                        'id' => 'finance',
                        'title' => 'ตัดรับชำระเงินค่าน้ำ',
                        'icon' => '🧾',
                        'url' => 'finance_billing.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'arrears',
                        'title' => 'ทะเบียนหนี้ค้างชำระ',
                        'icon' => '⚠️',
                        'url' => 'finance_billing.php#pane-arrears',
                        'is_sub' => true
                    ],
                    [
                        'id' => 'vouchers',
                        'title' => 'ฎีกาเบิกจ่ายเงินกองทุน',
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
                    ]
                ]
            ]
        ];
    } elseif ($currentRole === 'admin') {
        // 5. ระดับคณะกรรมการบริหาร / แอดมิน (Admin / Executive)
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
                    ]
                ]
            ],
            [
                'title' => 'บริหารและนโยบายกองทุน',
                'icon' => '🏛️',
                'items' => [
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
                    ],
                    [
                        'id' => 'dashboard',
                        'title' => 'แดชบอร์ดสรุปผลรวม',
                        'icon' => '📈',
                        'url' => 'dashboard.php',
                        'is_sub' => false
                    ]
                ]
            ],
            [
                'title' => 'ระบบปฏิบัติการ',
                'icon' => '🛠️',
                'items' => [
                    [
                        'id' => 'field',
                        'title' => 'สมุดจดมิเตอร์ (ภาคสนาม)',
                        'icon' => '📝',
                        'url' => 'meter_reading.php',
                        'is_sub' => false
                    ],
                    [
                        'id' => 'finance',
                        'title' => 'งานการเงิน & ฎีกาเบิกจ่าย',
                        'icon' => '🧾',
                        'url' => 'finance_billing.php',
                        'is_sub' => false
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
            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="nav-item <?php echo $subClass; ?> <?php echo $isActive ? 'active' : ''; ?>">
              <span class="icon"><?php echo $item['icon']; ?></span>
              <span style="flex: 1;"><?php echo htmlspecialchars($item['title']); ?></span>
            </a>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </nav>
    </aside>
    <?php
}

function renderAppTopBar($title, $subtitle) {
    $currentUser = getCurrentUser();
    ?>
    <header class="top-bar no-print">
      <div class="top-bar-title">
        <h1><?php echo htmlspecialchars($title); ?></h1>
        <span style="font-size: 13.5px;"><?php echo htmlspecialchars($subtitle); ?></span>
      </div>
      <div class="top-bar-actions" style="display: flex; align-items: center; gap: 10px;">
        <?php if ($currentUser): ?>
          <div class="user-profile-chip" style="display: flex; align-items: center; gap: 8px; background: <?php echo $currentUser['badge_bg'] ?? '#f1f5f9'; ?>; padding: 6px 14px; border-radius: 6px; font-size: 13.5px; color: <?php echo $currentUser['badge_color'] ?? '#0f172a'; ?>; border: 1px solid rgba(0,0,0,0.08);">
            <span>👤</span>
            <?php if (($currentUser['role'] ?? '') === 'member'): ?>
              <strong>สมาชิก: <?php echo htmlspecialchars($currentUser['name']); ?> (รหัส: <?php echo htmlspecialchars($currentUser['customer_code'] ?? ''); ?>)</strong>
            <?php else: ?>
              <strong><?php echo htmlspecialchars($currentUser['name']); ?> (<?php echo htmlspecialchars($currentUser['role_title']); ?>)</strong>
            <?php endif; ?>
          </div>
          <a href="api/auth.php?action=logout" style="color: #dc2626; font-size: 13px; font-weight: 600; text-decoration: none; padding: 6px 12px; background: #fee2e2; border-radius: 6px; border: 1px solid #fecaca; display: inline-flex; align-items: center; gap: 4px;">
            <span>🚪</span> ออกจากระบบ
          </a>
        <?php else: ?>
          <span style="font-size: 13.5px; color: #64748b; font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
            <span>👤</span> ผู้ใช้ทั่วไป / สมาชิก
          </span>
          <button type="button" class="btn btn-primary btn-open-login" onclick="openLoginModal()" style="font-size: 13.5px; padding: 8px 16px; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-family: inherit;">
            🔐 เข้าสู่ระบบ
          </button>
        <?php endif; ?>
      </div>
    </header>

    <!-- Unified Login Modal for Members and Staff -->
    <div id="login-modal" class="modal" style="display: none;">
      <div class="modal-dialog" style="max-width: 460px;">
        <div class="modal-header" style="background: #0f172a; color: #fff; padding: 16px 20px;">
          <div style="display: flex; align-items: center; gap: 10px;">
            <div style="background: #0284c7; font-size: 20px; width: 38px; height: 38px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">🔐</div>
            <div>
              <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #38bdf8;">เข้าสู่ระบบจัดการน้ำประปา</h4>
              <span style="font-size: 13px; color: #94a3b8;">การประปาหมู่บ้านวังยาง หมู่ที่ 3</span>
            </div>
          </div>
          <button type="button" class="modal-close" onclick="closeLoginModal()" style="color: #94a3b8; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
        </div>

        <div class="modal-body" style="padding: 20px 24px;">
          <?php if (isset($_GET['error'])): ?>
            <div style="background: #fee2e2; border: 1px solid #fca5a5; color: #b91c1c; padding: 10px 14px; border-radius: 6px; font-size: 13.5px; margin-bottom: 14px; text-align: center;">
              ⚠️ ข้อมูลเข้าสู่ระบบไม่ถูกต้อง กรุณาตรวจสอบรหัสสมาชิก หรือชื่อผู้ใช้งานและรหัสผ่าน
            </div>
          <?php endif; ?>

          <!-- Login Type Switch Tabs -->
          <div style="display: flex; gap: 8px; margin-bottom: 20px; background: #f1f5f9; padding: 4px; border-radius: 8px;">
            <button type="button" id="tab-btn-member" class="login-tab-btn active" onclick="switchLoginTab('member')" style="flex: 1; padding: 8px 12px; border: none; border-radius: 6px; font-size: 13.5px; font-weight: 600; cursor: pointer; background: #0284c7; color: #fff; font-family: inherit; transition: all 0.15s;">
              👤 สมาชิกผู้ใช้น้ำ
            </button>
            <button type="button" id="tab-btn-staff" class="login-tab-btn" onclick="switchLoginTab('staff')" style="flex: 1; padding: 8px 12px; border: none; border-radius: 6px; font-size: 13.5px; font-weight: 600; cursor: pointer; background: transparent; color: #64748b; font-family: inherit; transition: all 0.15s;">
              💼 เจ้าหน้าที่ / กรรมการ
            </button>
          </div>

          <!-- Form 1: Member Login -->
          <form id="member-login-form" action="api/auth.php" method="POST" style="display: block;">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="login_type" value="member">
            
            <div style="margin-bottom: 14px;">
              <label style="font-weight: 600; font-size: 13.5px; color: #334155; display: block; margin-bottom: 6px;">รหัสสมาชิกผู้ใช้น้ำ หรือบ้านเลขที่:</label>
              <input type="text" name="customer_code" class="form-input" required placeholder="เช่น WY-001 หรือ 12 หรือเบอร์โทรศัพท์" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
              <span style="font-size: 12.5px; color: #64748b; margin-top: 4px; display: block;">* สมาชิกสามารถใช้รหัสผู้ใช้น้ำ (เช่น WY-001) หรือเบอร์โทรที่ลงทะเบียนไว้</span>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; font-size: 14.5px; font-weight: 600; border-radius: 6px; background: #0284c7; color: #fff; border: none; cursor: pointer; font-family: inherit; margin-top: 8px;">
              🚀 เข้าสู่ระบบสมาชิก
            </button>
          </form>

          <!-- Form 2: Staff Login -->
          <form id="staff-login-form" action="api/auth.php" method="POST" style="display: none;">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="login_type" value="staff">

            <div style="margin-bottom: 14px;">
              <label style="font-weight: 600; font-size: 13.5px; color: #334155; display: block; margin-bottom: 6px;">ชื่อผู้ใช้งาน (Username):</label>
              <input type="text" name="username" class="form-input" required placeholder="เช่น admin, finance, reader" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
            </div>

            <div style="margin-bottom: 16px;">
              <label style="font-weight: 600; font-size: 13.5px; color: #334155; display: block; margin-bottom: 6px;">รหัสผ่าน (Password):</label>
              <input type="password" name="password" class="form-input" required placeholder="••••••••" style="width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; font-family: inherit; box-sizing: border-box;">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 11px; font-size: 14.5px; font-weight: 600; border-radius: 6px; background: #0284c7; color: #fff; border: none; cursor: pointer; font-family: inherit;">
              🚀 เข้าสู่ระบบเจ้าหน้าที่
            </button>
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
    </script>
    <?php
}

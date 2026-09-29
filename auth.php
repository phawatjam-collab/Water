<?php
/**
 * ระบบจัดการสิทธิ์และการเข้าถึง (Role-Based Access Control - RBAC)
 * โครงการระบบบริหารจัดการการประปาหมู่บ้านวังยาง (XAMPP Edition)
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// รายชื่อบัญชีผู้ใช้ระบบ (4 บทบาทหลัก: admin, staff, member, user)
$VALID_USERS = [
    'admin' => [
        'username' => 'admin',
        'role' => 'admin',
        'role_title' => 'ผู้ดูแลระบบ (Admin)',
        'name' => 'นายประธาน บริหารกิจการ',
        'position' => 'ประธานกรรมการ / ผู้ดูแลระบบการประปา',
        'badge_color' => '#7c3aed',
        'badge_bg' => '#f3e8ff',
        'default_page' => 'dashboard.php'
    ],
    'staff' => [
        'username' => 'staff',
        'role' => 'staff',
        'role_title' => 'เจ้าหน้าที่การประปา (Staff)',
        'name' => 'นายสมาน ปฏิบัติงานดี',
        'position' => 'เจ้าหน้าที่การประปา (จดมิเตอร์ / การเงิน / บริการประชาชน)',
        'badge_color' => '#0284c7',
        'badge_bg' => '#e0f2fe',
        'default_page' => 'meter_reading.php'
    ],
    'member' => [
        'username' => 'member',
        'role' => 'member',
        'role_title' => 'สมาชิกผู้ใช้น้ำ (Member)',
        'name' => 'นายสมชาย ไชยรัก',
        'position' => 'สมาชิกผู้ใช้น้ำ (บ้านเลขที่ 12/3 ม.1)',
        'customer_code' => 'WY-001',
        'house_no' => '12/3',
        'zone' => 'โซน 1 (วังยางเหนือ)',
        'phone' => '081-234-5678',
        'meter_serial' => 'MTR-1001',
        'badge_color' => '#0284c7',
        'badge_bg' => '#e0f2fe',
        'default_page' => 'portal_citizen.php?customer=WY-001'
    ],
    'user' => [
        'username' => 'user',
        'role' => 'user',
        'role_title' => 'ผู้ใช้ทั่วไป / ประชาชน (User)',
        'name' => 'ประชาชนทั่วไป',
        'position' => 'ผู้ใช้บริการทั่วไป (Public Citizen)',
        'badge_color' => '#059669',
        'badge_bg' => '#d1fae5',
        'default_page' => 'index.php'
    ],
    // บัญชีเดิมเพื่อความเข้ากันได้ (Backward Compatibility -> Map to Staff)
    'finance' => [
        'username' => 'finance',
        'role' => 'staff',
        'role_title' => 'เจ้าหน้าที่การประปา (Staff)',
        'name' => 'นางจำเนียร ตรวจบัญชี',
        'position' => 'เจ้าหน้าที่การเงินและบัญชี',
        'badge_color' => '#0284c7',
        'badge_bg' => '#e0f2fe',
        'default_page' => 'finance_billing.php'
    ],
    'reader' => [
        'username' => 'reader',
        'role' => 'staff',
        'role_title' => 'เจ้าหน้าที่การประปา (Staff)',
        'name' => 'นายสมาน เก็บเงินดี',
        'position' => 'เจ้าหน้าที่จดมาตรวัดน้ำ',
        'badge_color' => '#0284c7',
        'badge_bg' => '#e0f2fe',
        'default_page' => 'meter_reading.php'
    ]
];

/**
 * ดึงข้อมูลผู้ใช้ที่กำลังล็อกอินอยู่
 */
function getCurrentUser() {
    return $_SESSION['water_user'] ?? null;
}

function getAuthDbConnection() {
    global $pdo;
    if (isset($pdo) && $pdo instanceof PDO) {
        return $pdo;
    }
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=db_wangyang_water;charset=utf8mb4", 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        return $pdo;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * ล็อกอินสำหรับสมาชิกผู้ใช้น้ำ (ค้นหาจากรหัสผู้ใช้น้ำ เช่น WY-001 หรือเบอร์โทรศัพท์ หรือบ้านเลขที่)
 */
function loginMember($identifier) {
    global $VALID_USERS;
    $identifier = trim($identifier);
    if (empty($identifier)) return false;

    // ตรวจสอบกรณีล็อกอินด้วยคำว่า 'member'
    if (strtolower($identifier) === 'member' && isset($VALID_USERS['member'])) {
        $_SESSION['water_user'] = $VALID_USERS['member'];
        return true;
    }

    $db = getAuthDbConnection();
    if ($db) {
        try {
            $stmt = $db->prepare("
                SELECT * FROM customers 
                WHERE customer_code = ? OR phone = ? OR house_no = ?
                LIMIT 1
            ");
            $stmt->execute([$identifier, $identifier, $identifier]);
            $cust = $stmt->fetch();

            if ($cust) {
                $_SESSION['water_user'] = [
                    'username' => $cust['customer_code'],
                    'role' => 'member',
                    'role_title' => 'สมาชิกผู้ใช้น้ำ (Member)',
                    'name' => $cust['first_name'] . ' ' . $cust['last_name'],
                    'position' => 'สมาชิกผู้ใช้น้ำ (บ้านเลขที่ ' . $cust['house_no'] . ')',
                    'customer_code' => $cust['customer_code'],
                    'house_no' => $cust['house_no'],
                    'zone' => $cust['zone'],
                    'phone' => $cust['phone'],
                    'meter_serial' => $cust['meter_serial'],
                    'badge_color' => '#0284c7',
                    'badge_bg' => '#e0f2fe',
                    'default_page' => 'portal_citizen.php?customer=' . urlencode($cust['customer_code'])
                ];
                return true;
            }
        } catch (Exception $e) {
            // Fallback for demo if DB error
        }
    }

    // กรณีพิมพ์รหัส WY- หรือตัวเลข แล้วไม่พบในฐานข้อมูล ให้ใช้เดโมสมาชิก
    if (preg_match('/^WY-/i', $identifier) || is_numeric($identifier)) {
        if (isset($VALID_USERS['member'])) {
            $demoMember = $VALID_USERS['member'];
            $demoMember['customer_code'] = strtoupper($identifier);
            $_SESSION['water_user'] = $demoMember;
            return true;
        }
    }

    return false;
}

/**
 * ล็อกอินสำหรับเจ้าหน้าที่ประจำตำแหน่ง
 */
function loginStaff($username, $password = '') {
    global $VALID_USERS;
    $u = strtolower(trim($username));

    // เช็คกรณีใส่ชื่อ role หรือ username ตรงกัน
    if (isset($VALID_USERS[$u])) {
        // ตรวจสอบรหัสผ่าน (รหัสเริ่มต้น 123456 หรือ [username]123 หรือเว้นว่าง)
        if ($password !== '' && $password !== '123456' && $password !== ($u . '123')) {
            return false;
        }
        $_SESSION['water_user'] = $VALID_USERS[$u];
        return true;
    }
    return false;
}

/**
 * ล็อกอินเข้าสู่ระบบตาม Role (Compatibility)
 */
function loginUser($role) {
    global $VALID_USERS;
    $r = strtolower(trim($role));
    if (isset($VALID_USERS[$r])) {
        $_SESSION['water_user'] = $VALID_USERS[$r];
        return true;
    }
    return false;
}

/**
 * ออกจากระบบ
 */
function logoutUser() {
    unset($_SESSION['water_user']);
}

/**
 * ตรวจสอบสิทธิ์การเข้าใช้งาน (RBAC Gatekeeper)
 * 4 บทบาทหลัก: admin, staff, member, user
 * @param array $allowedRoles รายการ role ที่อนุญาต เช่น ['admin', 'staff']
 */
function requireRole($allowedRoles = []) {
    $user = getCurrentUser();

    // 1. ยังไม่ได้เข้าสู่ระบบ (Guest Session)
    if (!$user) {
        // หากหน้านั้นอนุญาต user หรือไม่ระบุ role แสดงว่าเปิดสาธารณะ
        if (empty($allowedRoles) || in_array('user', $allowedRoles)) {
            return null;
        }
        renderAccessDeniedPage(null, $allowedRoles);
        exit;
    }

    // 2. ตรวจสอบสิทธิ์การเข้าถึง
    $userRole = $user['role'] ?? 'user';
    if (in_array($userRole, ['finance', 'reader'])) {
        $userRole = 'staff';
    }

    $hasPermission = false;
    if ($userRole === 'admin') {
        $hasPermission = true; // Admin เข้าถึงได้ทุกโมดูล
    } elseif (in_array($userRole, $allowedRoles)) {
        $hasPermission = true;
    } elseif ($userRole === 'staff' && (in_array('staff', $allowedRoles) || in_array('finance', $allowedRoles) || in_array('reader', $allowedRoles))) {
        $hasPermission = true;
    }

    if (!empty($allowedRoles) && !$hasPermission) {
        renderAccessDeniedPage($user, $allowedRoles);
        exit;
    }

    return $user;
}

/**
 * แสดงหน้าแจ้งเตือนไม่มีสิทธิ์เข้าถึง (403 Forbidden)
 */
function renderAccessDeniedPage($user, $allowedRoles) {
    http_response_code(403);
    $rolesText = [
        'admin'  => 'ผู้ดูแลระบบ (Admin)',
        'staff'  => 'เจ้าหน้าที่การประปา (Staff)',
        'member' => 'สมาชิกผู้ใช้น้ำ (Member)',
        'user'   => 'ผู้ใช้ทั่วไป / ประชาชน (User)'
    ];
    $neededRoles = array_map(function($r) use ($rolesText) {
        return $rolesText[$r] ?? $r;
    }, $allowedRoles);
    $neededStr = implode(' หรือ ', $neededRoles);

    $userName = $user ? htmlspecialchars($user['name']) : 'ผู้ใช้ทั่วไป / สมาชิก';
    $userRole = $user ? htmlspecialchars($user['role_title']) : 'เซสชันสาธารณะ (ยังไม่ได้เข้าสู่ระบบ)';

    echo <<<HTML
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 สงวนสิทธิ์เข้าใช้งาน - การประปาหมู่บ้านวังยาง</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;600;700&family=Sarabun:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: #f8fafc;
            color: #1e293b;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .denied-card {
            background: #fff;
            max-width: 560px;
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            border: 1px solid #fee2e2;
            overflow: hidden;
            text-align: center;
        }
        .denied-header {
            background: #0284c7;
            color: #fff;
            padding: 24px;
        }
        .denied-header h1 {
            font-family: 'Prompt', sans-serif;
            font-size: 22px;
            margin: 0;
        }
        .denied-body {
            padding: 28px 24px;
        }
        .user-chip {
            display: inline-block;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            padding: 6px 14px;
            border-radius: 9999px;
            font-size: 13px;
            margin-bottom: 16px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            font-family: 'Prompt', sans-serif;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            margin: 6px;
            font-size: 14px;
        }
        .btn-primary { background: #0284c7; color: #fff; border: none; }
        .btn-primary:hover { background: #0369a1; }
        .btn-outline { background: #fff; color: #475569; border: 1px solid #cbd5e1; }
        .btn-outline:hover { background: #f8fafc; }
    </style>
</head>
<body>
    <div class="denied-card">
        <div class="denied-header">
            <div style="font-size: 40px; margin-bottom: 8px;">🔐</div>
            <h1>สงวนสิทธิ์เฉพาะผู้ปฏิบัติงานประจำตำแหน่ง</h1>
            <p style="margin: 4px 0 0 0; opacity: 0.9; font-size: 13.5px;">ระบบควบคุมความปลอดภัยตามระดับหน้าที่ (Role-Based Access Control)</p>
        </div>
        <div class="denied-body">
            <div class="user-chip">
                สถานะปัจจุบัน: <strong>{$userName}</strong> ({$userRole})
            </div>
            <p style="font-size: 15px; color: #475569; line-height: 1.6; margin-bottom: 20px;">
                หน้านี้สงวนสิทธิ์เฉพาะผู้ปฏิบัติงานในระดับ:<br>
                <strong style="color: #0284c7; font-size: 16px;">{$neededStr}</strong><br>
                กรุณาเข้าสู่ระบบด้วยบัญชีเจ้าหน้าที่ที่มีสิทธิ์เพื่อเข้าใช้งาน
            </p>

            <div style="margin: 24px 0; display: flex; justify-content: center; gap: 10px; flex-wrap: wrap;">
                <a href="index.php?open_login=1" class="btn btn-primary">🔐 เข้าสู่ระบบเจ้าหน้าที่</a>
                <a href="index.php" class="btn btn-outline">🏠 กลับสู่หน้าแรก</a>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
}

/**
 * เรนเดอร์ Header Navigation Bar ด้านบนสำหรับหน้าย่อยแต่ละโมดูล
 */
function renderModuleHeader($pageTitle, $pageSubtitle, $currentModule, $user = null) {
    $userHtml = '';
    if ($user) {
        $userHtml = <<<HTML
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="background: {$user['badge_bg']}; color: {$user['badge_color']}; border: 1px solid rgba(0,0,0,0.1); padding: 5px 12px; border-radius: 9999px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                <span>👤</span> {$user['name']} <span style="opacity: 0.8; font-size: 13px;">({$user['role_title']})</span>
            </div>
            <a href="api/auth.php?action=logout" style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                🚪 ออกจากระบบ
            </a>
        </div>
HTML;
    } else {
        $userHtml = <<<HTML
        <div>
            <a href="index.php?open_login=1" style="background: #0284c7; color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 6px; font-size: 13.5px; font-weight: 600;">
                🔐 เข้าสู่ระบบเจ้าหน้าที่
            </a>
        </div>
HTML;
    }

    $navLinks = [
        ['url' => 'index.php', 'title' => '🏠 หน้าหลัก (Portal)', 'id' => 'portal'],
        ['url' => 'portal_citizen.php', 'title' => '👥 ตรวจสอบค่าน้ำ (ประชาชน)', 'id' => 'citizen'],
        ['url' => 'meter_reading.php', 'title' => '🚶‍♂️ งานจดมิเตอร์ภาคสนาม', 'id' => 'field'],
        ['url' => 'finance_billing.php', 'title' => '💼 งานการเงินและฎีกาเบิกจ่าย', 'id' => 'finance'],
        ['url' => 'executive_reports.php', 'title' => '🏛️ นโยบาย & งบการเงิน', 'id' => 'executive']
    ];

    $linksHtml = '';
    foreach ($navLinks as $nl) {
        $isActive = ($currentModule === $nl['id']);
        $style = $isActive 
            ? 'background: #0284c7; color: #fff; font-weight: 600;' 
            : 'color: #334155; text-decoration: none;';
        $linksHtml .= "<a href=\"{$nl['url']}\" style=\"padding: 6px 12px; border-radius: 6px; font-size: 13.5px; transition: all 0.2s; {$style}\">{$nl['title']}</a> ";
    }

    echo <<<HTML
    <header style="background: #ffffff; border-bottom: 1px solid #e2e8f0; position: sticky; top: 0; z-index: 100; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="max-width: 1400px; margin: 0 auto; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <a href="index.php" style="text-decoration: none; display: flex; align-items: center; gap: 10px;">
                    <div style="background: #0284c7; color: #fff; font-size: 20px; width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center;">💧</div>
                    <div>
                        <strong style="font-size: 16px; color: #0f172a; display: block; font-family: 'Prompt', sans-serif;">การประปาหมู่บ้านวังยาง</strong>
                        <span style="font-size: 13px; color: #64748b;">หมู่ที่ 3 ตำบลวังยาง อำเภอวังยาง จังหวัดนครพนม</span>
                    </div>
                </a>
            </div>
            <div class="no-print">
                {$userHtml}
            </div>
        </div>
        <nav class="no-print" style="background: #f8fafc; border-top: 1px solid #f1f5f9; padding: 6px 24px;">
            <div style="max-width: 1400px; margin: 0 auto; display: flex; gap: 6px; overflow-x: auto;">
                {$linksHtml}
            </div>
        </nav>
    </header>
HTML;
}

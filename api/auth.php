<?php
/**
 * API สำหรับจัดการ Authentication & Session Login/Logout
 */
require_once __DIR__ . '/../auth.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$isJsonRequest = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
              || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false && empty($_POST));

$rawInput = [];
if ($isJsonRequest) {
    $rawInput = json_decode(file_get_contents('php://input'), true) ?: [];
    if (empty($action)) {
        $action = $rawInput['action'] ?? 'login';
    }
}

if ($action === 'login') {
    $loginType = $_POST['login_type'] ?? ($rawInput['login_type'] ?? ($_GET['login_type'] ?? ''));
    $username  = trim($_POST['username'] ?? ($rawInput['username'] ?? ($_GET['username'] ?? '')));
    $password  = trim($_POST['password'] ?? ($rawInput['password'] ?? ($_GET['password'] ?? '')));
    $role      = trim($_POST['role'] ?? ($rawInput['role'] ?? ($_GET['role'] ?? '')));
    $customerCode = trim($_POST['customer_code'] ?? ($rawInput['customer_code'] ?? ($_GET['customer_code'] ?? '')));
    $redirect  = $_GET['redirect'] ?? ($_POST['redirect'] ?? ($rawInput['redirect'] ?? ''));

    $loginSuccess = false;

    // 1. เข้าสู่ระบบแบบผู้ใช้ทั่วไป / ประชาชน (User Login)
    if ($loginType === 'user' || $username === 'user') {
        $loginSuccess = loginUser('user');
    }
    // 2. เข้าสู่ระบบแบบสมาชิกผู้ใช้น้ำ (Member Login)
    elseif ($loginType === 'member' || !empty($customerCode)) {
        $codeToFind = !empty($customerCode) ? $customerCode : $username;
        $loginSuccess = loginMember($codeToFind);
    } 
    // 3. ถ้ากรอก username ที่ขึ้นต้นด้วย WY- หรือเป็นตัวเลข ให้ลองตรวจสอบสมาชิกก่อน
    elseif (!empty($username) && (preg_match('/^WY-/i', $username) || is_numeric($username))) {
        $loginSuccess = loginMember($username);
    }
    // 4. เข้าสู่ระบบแบบเจ้าหน้าที่ / แอดมิน (Staff / Admin Login ด้วย Username & Password)
    elseif (!empty($username)) {
        $loginSuccess = loginStaff($username, $password);
    }
    // 5. เข้าสู่ระบบด้วย role (Compatibility fallback)
    elseif (!empty($role)) {
        $loginSuccess = loginUser($role);
    }

    if ($loginSuccess) {
        $user = getCurrentUser();

        if ($isJsonRequest) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'user' => $user,
                'redirect' => !empty($redirect) ? $redirect : $user['default_page']
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (!empty($redirect)) {
            if (!preg_match('#^https?://#i', $redirect) && !str_starts_with($redirect, '/')) {
                $redirect = '../' . ltrim($redirect, './');
            }
            header("Location: " . $redirect);
            exit;
        }

        $defaultPage = $user ? $user['default_page'] : 'index.php';
        header("Location: ../" . $defaultPage);
        exit;
    } else {
        if ($isJsonRequest) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'ข้อมูลเข้าสู่ระบบไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header("Location: ../index.php?error=invalid_credentials&open_login=1");
        exit;
    }
}

if ($action === 'logout') {
    logoutUser();
    header("Location: ../index.php");
    exit;
}

if ($action === 'status') {
    header('Content-Type: application/json; charset=utf-8');
    $user = getCurrentUser();
    echo json_encode([
        'authenticated' => ($user !== null),
        'user' => $user
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

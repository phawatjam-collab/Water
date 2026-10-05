<?php
/**
 * API สำหรับลงทะเบียนบัญชีผู้ใช้ใหม่ (Member & Staff Registration)
 * รองรับทั้ง JSON Request และ Form POST
 */
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

$isJsonRequest = (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
              || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false && empty($_POST));

$input = $_POST;
if ($isJsonRequest) {
    $raw = json_decode(file_get_contents('php://input'), true);
    if (is_array($raw)) {
        $input = array_merge($input, $raw);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$role = strtolower(trim($input['role'] ?? ($input['type'] ?? 'member')));
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if (!empty($confirmPassword) && $password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'รหัสผ่านและการยืนยันรหัสผ่านไม่ตรงกัน'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = null;
if ($role === 'staff') {
    $result = registerStaff($input);
} else {
    // Default to member registration
    $result = registerMember($input);
}

if ($result['success']) {
    http_response_code(200);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} else {
    http_response_code(400);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
}
exit;

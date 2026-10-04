<?php
/**
 * API ระบบแจ้งเรื่องร้องเรียน / ท่อแตก / คำร้องบริการ (Service Tickets API)
 * รองรับการยื่นคำร้องจากประชาชน พร้อมระบบแนบรูปถ่าย และแจ้งเตือนอัตโนมัติเข้ากลุ่ม LINE
 * และการบริหารจัดการสถานะงานซ่อมบำรุงของเจ้าหน้าที่
 */
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

/**
 * ฟังก์ชันส่งการแจ้งเตือนเข้า LINE Notify
 */
function sendLineNotify($message, $fullImagePath = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'line_notify_token'");
        $stmt->execute();
        $token = trim($stmt->fetchColumn() ?: '');

        $enStmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'line_notify_enabled'");
        $enStmt->execute();
        $enabled = (int)($enStmt->fetchColumn() ?: 1);

        if (!$enabled || empty($token)) {
            return ['success' => false, 'reason' => 'LINE Notify is disabled or token is empty'];
        }

        $url = 'https://notify-api.line.me/api/notify';
        $headers = [
            'Authorization: Bearer ' . $token
        ];

        $postData = ['message' => $message];

        // หากมีการแนบรูปภาพ ให้ส่งรูปไปด้วย
        if (!empty($fullImagePath) && file_exists($fullImagePath)) {
            $postData['imageFile'] = new CURLFile($fullImagePath);
            $headers[] = 'Content-Type: multipart/form-data';
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 4); // หน่วงเวลาสูงสุด 4 วินาที ไม่ให้กระทบการใช้งานของผู้ใช้
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => ($httpCode === 200),
            'code' => $httpCode,
            'response' => $response
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// 0. จัดการการตั้งค่า LINE Notify (Settings Management)
if ($action === 'get_line_settings') {
    $tokenStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'line_notify_token'");
    $token = $tokenStmt->fetchColumn() ?: '';
    
    $enStmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'line_notify_enabled'");
    $enabled = (int)($enStmt->fetchColumn() ?: 1);

    send_json([
        'token' => $token ? (substr($token, 0, 6) . '...' . substr($token, -4)) : '',
        'has_token' => !empty($token),
        'enabled' => $enabled
    ]);
}

if ($action === 'save_line_settings') {
    require_once __DIR__ . '/../auth.php';
    requireRole(['admin']);

    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $newToken = trim($input['token'] ?? '');
    $enabled = isset($input['enabled']) ? ((int)$input['enabled'] ? 1 : 0) : 1;

    if (!empty($newToken) && $newToken !== 'KEEP_CURRENT') {
        $upStmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value, description) VALUES ('line_notify_token', ?, 'LINE Notify Token')");
        $upStmt->execute([$newToken]);
    }
    
    $upEnStmt = $pdo->prepare("REPLACE INTO system_settings (setting_key, setting_value, description) VALUES ('line_notify_enabled', ?, 'LINE Enabled')");
    $upEnStmt->execute([(string)$enabled]);

    // ทดสอบส่งข้อความยืนยันหากมีการตั้ง Token
    $testResult = null;
    if (!empty($newToken) && $newToken !== 'KEEP_CURRENT') {
        $testResult = sendLineNotify("\n🔔 ยืนยันการเชื่อมต่อระบบแจ้งเตือนน้ำประปาหมู่บ้านวังยาง สำเร็จเรียบร้อย!");
    }

    send_json([
        'success' => true,
        'message' => 'บันทึกการตั้งค่า LINE Notify เรียบร้อยแล้ว',
        'test_result' => $testResult
    ]);
}

// 1. GET: ดึงรายการคำร้องแจ้งซ่อม
if ($method === 'GET') {
    $status = trim($_GET['status'] ?? '');
    $search = trim($_GET['search'] ?? '');
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM service_tickets WHERE id = ?");
        $stmt->execute([$id]);
        $ticket = $stmt->fetch();
        if (!$ticket) send_json(['error' => 'ไม่พบคำร้อง'], 404);
        send_json($ticket);
    }

    $sql = "SELECT * FROM service_tickets WHERE 1=1";
    $params = [];

    if (!empty($status) && $status !== 'ALL') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    if (!empty($search)) {
        $sql .= " AND (ticket_no LIKE ? OR reporter_name LIKE ? OR phone LIKE ? OR house_no LIKE ? OR issue_type LIKE ?)";
        $kw = "%{$search}%";
        $params = array_merge($params, [$kw, $kw, $kw, $kw, $kw]);
    }

    $sql .= " ORDER BY created_at DESC, id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();

    // นับสถิติคำร้องแยกตามสถานะ
    $countStmt = $pdo->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
            SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved
        FROM service_tickets
    ");
    $stats = $countStmt->fetch() ?: ['total' => 0, 'pending' => 0, 'in_progress' => 0, 'resolved' => 0];

    send_json([
        'stats' => [
            'total' => (int)$stats['total'],
            'pending' => (int)$stats['pending'],
            'inProgress' => (int)$stats['in_progress'],
            'resolved' => (int)$stats['resolved']
        ],
        'tickets' => $tickets
    ]);
}

// 2. POST: ยื่นคำร้องใหม่ (พร้อมรองรับการแนบรูปถ่าย & แจ้งเตือนเข้า LINE)
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) {
        $body = $_POST;
    }

    $reporterName = trim($body['reporter_name'] ?? ($body['reporterName'] ?? ($body['name'] ?? '')));
    $phone        = trim($body['phone'] ?? '');
    $houseNo      = trim($body['house_no'] ?? ($body['houseNo'] ?? ($body['location'] ?? '')));
    $zone         = trim($body['zone'] ?? 'โซน 1 วังยางเหนือ');
    $issueType    = trim($body['issue_type'] ?? ($body['issueType'] ?? ($body['topic'] ?? 'แจ้งท่อแตก/รั่ว')));
    $description  = trim($body['description'] ?? ($body['details'] ?? ''));

    if (empty($reporterName) || empty($phone)) {
        send_json(['error' => 'กรุณาระบุชื่อผู้แจ้งและเบอร์โทรศัพท์ติดต่อ'], 400);
    }

    // 2.1 จัดการอัปโหลดรูปภาพหลักฐานจุดเกิดเหตุ (ถ้ามี)
    $photoUrl = null;
    $uploadedFullPath = null;

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['photo'];
        $maxSize = 5 * 1024 * 1024; // 5 MB
        
        if ($file['size'] > $maxSize) {
            send_json(['error' => 'ไฟล์รูปภาพต้องมีขนาดไม่เกิน 5 MB'], 400);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

        if (!in_array($ext, $allowedExts)) {
            send_json(['error' => 'อนุญาตเฉพาะไฟล์รูปภาพ (JPG, PNG, WEBP, GIF) เท่านั้น'], 400);
        }

        // ตรวจสอบ MIME type จริง
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowedMimes)) {
            send_json(['error' => 'ประเภทไฟล์รูปภาพไม่ถูกต้อง'], 400);
        }

        $uploadDir = __DIR__ . '/../uploads/tickets/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $newFileName = 'ticket_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $targetPath = $uploadDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            $photoUrl = 'uploads/tickets/' . $newFileName;
            $uploadedFullPath = $targetPath;
        }
    }

    // 2.2 สร้างหมายเลขคำร้องอัตโนมัติ เช่น TK-2567-0804
    $yearBe = (int)date('Y') + 543;
    $month = (int)date('m');
    $prefix = sprintf("TK-%d-%02d", $yearBe, $month);

    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM service_tickets WHERE ticket_no LIKE ?");
    $cntStmt->execute(["{$prefix}%"]);
    $seq = (int)$cntStmt->fetchColumn() + 1;
    $ticketNo = sprintf("%s%02d", $prefix, $seq);

    // ตรวจสอบความซ้ำซ้อนของ ticket_no
    $chkStmt = $pdo->prepare("SELECT id FROM service_tickets WHERE ticket_no = ?");
    $chkStmt->execute([$ticketNo]);
    if ($chkStmt->fetch()) {
        $ticketNo .= '-' . substr(uniqid(), -3);
    }

    // 2.3 บันทึกลงฐานข้อมูล MySQL
    $insertStmt = $pdo->prepare("
        INSERT INTO service_tickets 
        (ticket_no, reporter_name, phone, house_no, zone, issue_type, description, photo_url, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW())
    ");
    $insertStmt->execute([
        $ticketNo, $reporterName, $phone, $houseNo ?: 'ไม่ระบุ', $zone, $issueType, $description, $photoUrl
    ]);
    $newId = (int)$pdo->lastInsertId();

    // 2.4 ยิงการแจ้งเตือนเข้า LINE Notify ไปยังกลุ่มเจ้าหน้าที่
    $lineMsg = "\n🚨 แจ้งคำร้องบริการ/ท่อแตกใหม่!\n"
             . "📌 เลขที่คำร้อง: " . $ticketNo . "\n"
             . "👤 ผู้แจ้ง: " . $reporterName . "\n"
             . "📞 โทร: " . $phone . "\n"
             . "📍 จุดเกิดเหตุ: " . $houseNo . " (" . $zone . ")\n"
             . "⚠️ ประเภท: " . $issueType . "\n"
             . "📝 รายละเอียด: " . ($description ?: '-') . "\n"
             . "📸 มีรูปภาพแนบ: " . ($photoUrl ? 'มีรูปภาพ' : 'ไม่มี') . "\n"
             . "⏱️ เวลา: " . date('d/m/Y H:i น.');

    $lineResult = sendLineNotify($lineMsg, $uploadedFullPath);

    send_json([
        'success' => true,
        'id' => $newId,
        'ticket_no' => $ticketNo,
        'photo_url' => $photoUrl,
        'line_notified' => $lineResult['success'] ?? false,
        'message' => "บันทึกคำร้องสำเร็จ! รหัสอ้างอิงของคุณคือ {$ticketNo} เจ้าหน้าที่จะประสานงานเข้าตรวจสอบโดยเร็ว"
    ], 201);
}

// 3. PUT: อัปเดตสถานะคำร้องและบันทึกการซ่อมบำรุง
if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($body['id'] ?? 0);

    if ($id <= 0 || !$body) {
        send_json(['error' => 'Invalid ticket ID or payload'], 400);
    }

    $status      = trim($body['status'] ?? 'IN_PROGRESS');
    $repairNotes = trim($body['repair_notes'] ?? ($body['repairNotes'] ?? ''));
    $repairCost  = (float)($body['repair_cost'] ?? ($body['repairCost'] ?? 0.0));

    $validStatuses = ['PENDING', 'IN_PROGRESS', 'RESOLVED'];
    if (!in_array($status, $validStatuses)) {
        $status = 'IN_PROGRESS';
    }

    $updateStmt = $pdo->prepare("
        UPDATE service_tickets
        SET status = ?, 
            repair_notes = ?, 
            repair_cost = ?, 
            resolved_at = CASE WHEN ? = 'RESOLVED' THEN NOW() ELSE resolved_at END
        WHERE id = ?
    ");
    $updateStmt->execute([$status, $repairNotes, $repairCost, $status, $id]);

    send_json([
        'success' => true,
        'message' => "อัปเดตสถานะคำร้องเป็น [{$status}] เรียบร้อยแล้ว"
    ]);
}

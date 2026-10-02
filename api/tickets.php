<?php
/**
 * API ระบบแจ้งเรื่องร้องเรียน / ท่อแตก / คำร้องบริการ (Service Tickets API)
 * รองรับการยื่นคำร้องจากประชาชน และการบริหารจัดการงานซ่อมบำรุงของเจ้าหน้าที่
 */
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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

// 2. POST: ยื่นคำร้องใหม่ (บันทึกลง service_tickets)
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

    // สร้างหมายเลขคำร้องอัตโนมัติ เช่น TK-2567-0804
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

    $insertStmt = $pdo->prepare("
        INSERT INTO service_tickets 
        (ticket_no, reporter_name, phone, house_no, zone, issue_type, description, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW())
    ");
    $insertStmt->execute([
        $ticketNo, $reporterName, $phone, $houseNo ?: 'ไม่ระบุ', $zone, $issueType, $description
    ]);
    $newId = (int)$pdo->lastInsertId();

    send_json([
        'success' => true,
        'id' => $newId,
        'ticket_no' => $ticketNo,
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

    $resolvedAt = ($status === 'RESOLVED') ? date('Y-m-d H:i:s') : null;

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

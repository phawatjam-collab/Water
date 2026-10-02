<?php
/**
 * API จัดการข้อมูลสมาชิกผู้ใช้น้ำ (Customers API)
 * เชื่อมต่อโดยตรงกับตาราง tb_customers, tb_zone, tb_installation ในฐานข้อมูล db_city_water_supply
 */
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

// ฟังก์ชันแปลงชื่อโซนเป็น zone_id (1, 2, 3)
function resolveZoneId($zone, $zoneId = null) {
    if (!empty($zoneId) && is_numeric($zoneId) && $zoneId >= 1 && $zoneId <= 3) {
        return (int)$zoneId;
    }
    if (empty($zone)) return 1;
    if (strpos($zone, '3') !== false || mb_strpos($zone, 'ใต้') !== false) return 3;
    if (strpos($zone, '2') !== false || mb_strpos($zone, 'กลาง') !== false) return 2;
    return 1;
}

// ฟังก์ชันแปลงขนาดมิเตอร์เป็น install_type_id (1: 5/8", 2: 1", 3: 1.5")
function resolveInstallTypeId($meterSize, $typeId = null) {
    if (!empty($typeId) && is_numeric($typeId) && $typeId >= 1 && $typeId <= 3) {
        return (int)$typeId;
    }
    if (empty($meterSize)) return 1;
    if (strpos($meterSize, '1.5') !== false) return 3;
    if (strpos($meterSize, '1') !== false) return 2;
    return 1;
}

// 1. GET: ดึงรายการลูกค้าทั้งหมด หรือค้นหา
if ($method === 'GET') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sql = "SELECT * FROM customers";
    $params = [];

    if ($search !== '') {
        $sql .= " WHERE first_name LIKE ? OR last_name LIKE ? OR house_no LIKE ? OR customer_code LIKE ? OR phone LIKE ?";
        $kw = "%{$search}%";
        $params = [$kw, $kw, $kw, $kw, $kw];
    }
    $sql .= " ORDER BY seq_no ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    send_json($stmt->fetchAll());
}

// 2. POST: เพิ่มข้อมูลลูกค้าใหม่ลงใน tb_customers
if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) send_json(['error' => 'Invalid JSON payload'], 400);

    $firstName = trim($body['firstName'] ?? '');
    $lastName  = trim($body['lastName'] ?? '-');
    $houseNo   = trim($body['houseNo'] ?? '');
    $phone     = trim($body['phone'] ?? '');
    $zoneInput = $body['zone'] ?? '';
    $zoneId    = resolveZoneId($zoneInput, $body['zoneId'] ?? null);
    $meterSize = $body['meterSize'] ?? '';
    $installId = resolveInstallTypeId($meterSize, $body['installTypeId'] ?? null);

    if (empty($firstName)) {
        send_json(['error' => 'กรุณาระบุชื่อลูกค้า'], 400);
    }

    $stmt = $pdo->prepare("
        INSERT INTO tb_customers (cus_firstname, cus_surname, install_type_id, house_id, cus_tel, zone_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$firstName, $lastName ?: '-', $installId, $houseNo ?: 'ไม่ระบุ', $phone ?: '-', $zoneId]);
    $newId = (int)$pdo->lastInsertId();

    // สร้างบันทึกมิเตอร์เริ่มต้นในรอบบิลที่เปิดอยู่ทันที เพื่อให้แสดงในสมุดจดมิเตอร์
    try {
        $openCycles = $pdo->query("SELECT id, tariff_rate_id FROM billing_cycles WHERE status = 'OPEN'")->fetchAll();
        $insReading = $pdo->prepare("
            INSERT IGNORE INTO meter_readings 
            (billing_cycle_id, customer_id, previous_reading, current_reading, units_used, rate_per_unit, maintenance_fee, current_total, previous_arrears, grand_total, payment_status, reading_date)
            VALUES (?, ?, 0.00, 0.00, 0.00, 7.00, 10.00, 10.00, 0.00, 10.00, 'UNPAID', CURDATE())
        ");
        foreach ($openCycles as $oc) {
            $insReading->execute([(int)$oc['id'], $newId]);
        }
    } catch (Exception $e) {
        // Ignored if table not ready
    }

    send_json([
        'success' => true,
        'id' => $newId,
        'customer_code' => sprintf('WY-%03d', $newId),
        'message' => 'บันทึกข้อมูลลูกค้าใหม่ลงใน tb_customers สำเร็จ'
    ]);
}

// 3. PUT: แก้ไขข้อมูลลูกค้าใน tb_customers
if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0 || !$body) send_json(['error' => 'Invalid ID or payload'], 400);

    $firstName = trim($body['firstName'] ?? '');
    $lastName  = trim($body['lastName'] ?? '-');
    $houseNo   = trim($body['houseNo'] ?? '');
    $phone     = trim($body['phone'] ?? '');
    $zoneInput = $body['zone'] ?? '';
    $zoneId    = resolveZoneId($zoneInput, $body['zoneId'] ?? null);
    $meterSize = $body['meterSize'] ?? '';
    $installId = resolveInstallTypeId($meterSize, $body['installTypeId'] ?? null);

    $stmt = $pdo->prepare("
        UPDATE tb_customers 
        SET cus_firstname = ?, cus_surname = ?, install_type_id = ?, house_id = ?, cus_tel = ?, zone_id = ?
        WHERE cus_id = ?
    ");
    $stmt->execute([
        $firstName,
        $lastName ?: '-',
        $installId,
        $houseNo ?: 'ไม่ระบุ',
        $phone ?: '-',
        $zoneId,
        $id
    ]);

    send_json([
        'success' => true,
        'id' => $id,
        'message' => 'แก้ไขข้อมูลลูกค้าใน tb_customers สำเร็จ'
    ]);
}

// 4. DELETE: ลบข้อมูลลูกค้าออกจาก tb_customers
if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) send_json(['error' => 'Invalid ID'], 400);

    $stmt = $pdo->prepare("DELETE FROM tb_customers WHERE cus_id = ?");
    $stmt->execute([$id]);

    send_json([
        'success' => true,
        'id' => $id,
        'message' => 'ลบข้อมูลลูกค้าจาก tb_customers สำเร็จ'
    ]);
}

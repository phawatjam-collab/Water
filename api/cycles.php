<?php
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $pdo->query("SELECT * FROM billing_cycles ORDER BY id DESC");
    send_json($stmt->fetchAll());
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) send_json(['error' => 'Invalid JSON'], 400);

    $cycle_code = $body['cycleCode'] ?? '';
    $month = (int)($body['month'] ?? 1);
    $year_be = (int)($body['yearBe'] ?? 2567);
    $prev_cycle_code = $body['prevCycleCode'] ?? '';

    // ดึงอัตราค่าน้ำปัจจุบัน
    $tariffStmt = $pdo->query("SELECT * FROM tariff_rates WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $tariff = $tariffStmt->fetch();
    $tariff_id = $tariff ? (int)$tariff['id'] : 1;
    $rate_per_unit = $tariff ? (float)$tariff['rate_per_unit'] : 7.00;
    $maintenance_fee = $tariff ? (float)$tariff['maintenance_fee'] : 10.00;

    // ตรวจสอบว่ามีรอบบิลนี้หรือยัง
    $checkStmt = $pdo->prepare("SELECT id FROM billing_cycles WHERE cycle_code = ?");
    $checkStmt->execute([$cycle_code]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        $cycle_id = (int)$existing['id'];
    } else {
        $insertCycle = $pdo->prepare("
            INSERT INTO billing_cycles (cycle_code, month, year_be, reading_start_date, reading_end_date, due_date, tariff_rate_id, status)
            VALUES (?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), ?, 'OPEN')
        ");
        $insertCycle->execute([$cycle_code, $month, $year_be, $tariff_id]);
        $cycle_id = (int)$pdo->lastInsertId();
    }

    // ดึงข้อมูลมิเตอร์จากรอบก่อนเพื่อนำมาเป็น Previous Reading และทบยอดค้างชำระ
    $prevMap = [];
    if ($prev_cycle_code !== '') {
        $prevStmt = $pdo->prepare("
            SELECT r.*, c.id as cust_id 
            FROM customers c
            JOIN billing_cycles bc ON bc.cycle_code = ?
            JOIN meter_readings r ON r.billing_cycle_id = bc.id AND r.customer_id = c.id
        ");
        $prevStmt->execute([$prev_cycle_code]);
        while ($row = $prevStmt->fetch()) {
            $prevMap[(int)$row['cust_id']] = [
                'lastReading' => (float)$row['current_reading'],
                'unpaidArrears' => $row['payment_status'] !== 'PAID' ? (float)$row['grand_total'] : 0.0
            ];
        }
    }

    // สร้างบันทึกมิเตอร์ให้ลูกค้าทุกคนในรอบใหม่
    $custStmt = $pdo->query("SELECT id, seq_no FROM customers WHERE status = 'ACTIVE' ORDER BY seq_no ASC");
    $customers = $custStmt->fetchAll();

    $checkReading = $pdo->prepare("SELECT id FROM meter_readings WHERE billing_cycle_id = ? AND customer_id = ?");
    $insertReading = $pdo->prepare("
        INSERT INTO meter_readings 
        (billing_cycle_id, customer_id, previous_reading, current_reading, units_used, rate_per_unit, water_charge, maintenance_fee, current_total, previous_arrears, grand_total, payment_status, reading_date)
        VALUES (?, ?, ?, ?, 0, ?, 0, ?, ?, ?, ?, 'UNPAID', CURDATE())
    ");

    foreach ($customers as $c) {
        $custId = (int)$c['id'];
        $checkReading->execute([$cycle_id, $custId]);
        if (!$checkReading->fetch()) {
            $info = $prevMap[$custId] ?? ['lastReading' => 0.0, 'unpaidArrears' => 0.0];
            $prevReading = $info['lastReading'];
            $arrears = $info['unpaidArrears'];
            $currentTotal = $maintenance_fee;
            $grandTotal = $currentTotal + $arrears;

            $insertReading->execute([
                $cycle_id, $custId, $prevReading, $prevReading, $rate_per_unit, $maintenance_fee, $currentTotal, $arrears, $grandTotal
            ]);
        }
    }

    send_json(['success' => true, 'cycleId' => $cycle_id, 'cycleCode' => $cycle_code]);
}

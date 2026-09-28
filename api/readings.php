<?php
require_once __DIR__ . '/db.php';

$cycle_code = $_GET['cycle'] ?? '8-2567';
$action = $_GET['action'] ?? '';

// ดึงรอบบิล
$cycleStmt = $pdo->prepare("SELECT * FROM billing_cycles WHERE cycle_code = ?");
$cycleStmt->execute([$cycle_code]);
$cycle = $cycleStmt->fetch();

if (!$cycle) {
    send_json(['error' => 'ไม่พบงวดประจำเดือน ' . htmlspecialchars($cycle_code)], 404);
}

$cycle_id = (int)$cycle['id'];

// 1. GET: รายการมิเตอร์ของงวด
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT r.*, c.customer_code, c.seq_no, c.first_name, c.last_name, c.house_no, c.zone, c.phone, c.meter_serial
        FROM meter_readings r
        JOIN customers c ON r.customer_id = c.id
        WHERE r.billing_cycle_id = ?
        ORDER BY c.seq_no ASC
    ");
    $stmt->execute([$cycle_id]);
    $readings = $stmt->fetchAll();

    send_json(['cycle' => $cycle, 'readings' => $readings]);
}

// 2. POST: บันทึกเลขมิเตอร์แบบชุด (Batch Save)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'save') {
    $body = json_decode(file_get_contents('php://input'), true);
    $items = $body['items'] ?? [];

    $tariffStmt = $pdo->prepare("SELECT rate_per_unit, maintenance_fee FROM tariff_rates WHERE id = ?");
    $tariffStmt->execute([(int)$cycle['tariff_rate_id']]);
    $tariff = $tariffStmt->fetch() ?: ['rate_per_unit' => 7.0, 'maintenance_fee' => 10.0];

    $rate = (float)$tariff['rate_per_unit'];
    $fee = (float)$tariff['maintenance_fee'];

    $updateStmt = $pdo->prepare("
        UPDATE meter_readings
        SET previous_reading = ?, current_reading = ?, units_used = ?, rate_per_unit = ?, water_charge = ?, maintenance_fee = ?, current_total = ?, previous_arrears = ?, grand_total = ?, reading_date = CURDATE()
        WHERE billing_cycle_id = ? AND customer_id = ?
    ");

    foreach ($items as $item) {
        $prev = (float)$item['previousReading'];
        $curr = (float)$item['currentReading'];
        $arrears = (float)($item['previousArrears'] ?? 0);
        $custId = (int)$item['customerId'];

        $units = $curr >= $prev ? ($curr - $prev) : ((10000 - $prev) + $curr);
        $waterCharge = round($units * $rate, 2);
        $currentTotal = round($waterCharge + $fee, 2);
        $grandTotal = round($currentTotal + $arrears, 2);

        $updateStmt->execute([
            $prev, $curr, $units, $rate, $waterCharge, $fee, $currentTotal, $arrears, $grandTotal, $cycle_id, $custId
        ]);
    }

    send_json(['success' => true, 'message' => 'บันทึกเลขมิเตอร์และคำนวณเงินเรียบร้อยแล้ว']);
}

// 3. POST: สลับสถานะชำระเงิน (Toggle Paid)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle-paid') {
    $cust_id = (int)($_GET['customerId'] ?? 0);
    if ($cust_id <= 0) send_json(['error' => 'Invalid customer ID'], 400);

    $findStmt = $pdo->prepare("SELECT * FROM meter_readings WHERE billing_cycle_id = ? AND customer_id = ?");
    $findStmt->execute([$cycle_id, $cust_id]);
    $reading = $findStmt->fetch();

    if (!$reading) send_json(['error' => 'Reading not found'], 404);

    $newStatus = $reading['payment_status'] === 'PAID' ? 'UNPAID' : 'PAID';
    $receiptNo = $reading['receipt_no'];

    if ($newStatus === 'PAID' && empty($receiptNo)) {
        // สร้างเลขที่ใบเสร็จมาตรฐาน เช่น 8-2567/501
        $countStmt = $pdo->prepare("SELECT count(*) as cnt FROM meter_readings WHERE billing_cycle_id = ? AND receipt_no IS NOT NULL");
        $countStmt->execute([$cycle_id]);
        $cntRow = $countStmt->fetch();
        $seq = 500 + ((int)$cntRow['cnt'] + 1);
        $receiptNo = sprintf("%d-%d/%03d", (int)$cycle['month'], (int)$cycle['year_be'], $seq);
    }

    $updateStatus = $pdo->prepare("
        UPDATE meter_readings 
        SET payment_status = ?, receipt_no = ?, payment_date = " . ($newStatus === 'PAID' ? "CURDATE()" : "NULL") . "
        WHERE id = ?
    ");
    $updateStatus->execute([$newStatus, $newStatus === 'PAID' ? $receiptNo : null, (int)$reading['id']]);

    send_json(['success' => true, 'paymentStatus' => $newStatus, 'receiptNo' => $receiptNo]);
}

<?php
/**
 * API จัดการเอกสารเบิกจ่ายเงินและใบสำคัญรับเงินกองทุนประปาหมู่บ้านวังยาง
 * รองรับ: ดึงข้อมูล, บันทึกรายการใหม่ (Custom Voucher), ลบรายการ, ซิงค์ค่าตอบแทน 10%
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bahtText.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Thai date formatter helper
function format_thai_date($date_str = null) {
    $thai_months = [
        1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
        5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
        9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
    ];
    $ts = $date_str ? strtotime($date_str) : time();
    $d = date('j', $ts);
    $m = (int)date('n', $ts);
    $y = (int)date('Y', $ts) + 543;
    return $d . ' ' . ($thai_months[$m] ?? '') . ' ' . $y;
}

// 1. DELETE ACTION
if ($method === 'POST' && $action === 'delete') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    $id = (int)($_GET['id'] ?? $data['id'] ?? 0);
    if ($id <= 0) {
        send_json(['error' => 'รหัสเอกสารไม่ถูกต้อง'], 400);
    }
    $delStmt = $pdo->prepare("DELETE FROM payment_vouchers WHERE id = ?");
    $delStmt->execute([$id]);
    send_json(['success' => true, 'message' => 'ลบรายการเบิกจ่ายเรียบร้อยแล้ว']);
}

// 2. CREATE NEW VOUCHER ACTION
if ($method === 'POST' && ($action === 'create' || empty($action))) {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?? $_POST;

    $cycle_code = trim($data['cycleCode'] ?? $data['cycle_code'] ?? $_GET['cycle'] ?? $_GET['cycleCode'] ?? '');
    if (empty($cycle_code)) {
        send_json(['error' => 'กรุณาระบุงวดประจำเดือน'], 400);
    }

    $cycleStmt = $pdo->prepare("SELECT * FROM billing_cycles WHERE cycle_code = ?");
    $cycleStmt->execute([$cycle_code]);
    $cycle = $cycleStmt->fetch();
    if (!$cycle) {
        send_json(['error' => 'ไม่พบงวดประจำเดือน ' . $cycle_code], 404);
    }

    $recipientName = trim($data['recipientName'] ?? $data['recipient_name'] ?? '');
    $recipientPosition = trim($data['recipientPosition'] ?? $data['recipient_position'] ?? 'ผู้รับเงิน');
    $voucherType = trim($data['voucherType'] ?? $data['voucher_type'] ?? 'OTHER');
    $amount = (float)($data['amount'] ?? 0);
    $calculationBasis = trim($data['calculationBasis'] ?? $data['calculation_basis'] ?? '');
    $description = trim($data['description'] ?? '');
    $approvedBy = trim($data['approvedBy'] ?? $data['approved_by'] ?? 'ประธานคณะกรรมการประปาหมู่บ้านวังยาง');
    $voucherDate = !empty($data['voucherDate']) ? date('Y-m-d', strtotime($data['voucherDate'])) : date('Y-m-d');

    if (empty($recipientName)) {
        send_json(['error' => 'กรุณาระบุชื่อผู้รับเงิน'], 400);
    }
    if ($amount <= 0) {
        send_json(['error' => 'จำนวนเงินต้องมากกว่า 0 บาท'], 400);
    }

    // Generate unique voucher_no e.g. บจ-8-2567/01
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM payment_vouchers WHERE billing_cycle_id = ?");
    $countStmt->execute([(int)$cycle['id']]);
    $existingCount = (int)$countStmt->fetchColumn();

    $idx = $existingCount + 1;
    $voucherNo = sprintf("บจ-%s/%02d", $cycle_code, $idx);
    // Double check uniqueness
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM payment_vouchers WHERE voucher_no = ?");
    $checkStmt->execute([$voucherNo]);
    if ((int)$checkStmt->fetchColumn() > 0) {
        $voucherNo = sprintf("บจ-%s/%02d-%s", $cycle_code, $idx, substr(uniqid(), -3));
    }

    $amountTextTh = baht_text($amount);

    $insStmt = $pdo->prepare("
        INSERT INTO payment_vouchers (
            voucher_no, billing_cycle_id, voucher_date, recipient_name, recipient_position,
            voucher_type, amount, amount_text_th, calculation_basis, description, approved_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insStmt->execute([
        $voucherNo, (int)$cycle['id'], $voucherDate, $recipientName, $recipientPosition,
        $voucherType, $amount, $amountTextTh, $calculationBasis, $description, $approvedBy
    ]);

    $newId = (int)$pdo->lastInsertId();

    send_json([
        'success' => true,
        'message' => 'บันทึกรายการเบิกจ่ายสำเร็จ',
        'voucher' => [
            'id' => $newId,
            'voucherNo' => $voucherNo,
            'cycleCode' => $cycle_code,
            'date' => format_thai_date($voucherDate),
            'voucherDate' => $voucherDate,
            'recipientName' => $recipientName,
            'recipientPosition' => $recipientPosition,
            'voucherType' => $voucherType,
            'amount' => $amount,
            'amountTextTh' => $amountTextTh,
            'calculationBasis' => $calculationBasis,
            'description' => $description,
            'approvedBy' => $approvedBy
        ]
    ]);
}

// 3. SYNC / UPDATE 10% COMMISSION
if ($method === 'POST' && $action === 'sync_commission') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true) ?? $_POST;
    $cycle_code = trim($data['cycleCode'] ?? $_GET['cycle'] ?? '');
    
    $cycleStmt = $pdo->prepare("SELECT * FROM billing_cycles WHERE cycle_code = ?");
    $cycleStmt->execute([$cycle_code]);
    $cycle = $cycleStmt->fetch();
    if (!$cycle) send_json(['error' => 'ไม่พบงวดประจำเดือน'], 404);

    $paidStmt = $pdo->prepare("
        SELECT SUM(grand_total) as total_paid
        FROM meter_readings 
        WHERE billing_cycle_id = ? AND payment_status = 'PAID'
    ");
    $paidStmt->execute([(int)$cycle['id']]);
    $paidRow = $paidStmt->fetch();
    $collectedRevenue = $paidRow && $paidRow['total_paid'] ? (float)$paidRow['total_paid'] : 0.0;
    $commission = round($collectedRevenue * 0.10, 2);
    $amountTextTh = baht_text($commission);
    $basis = "คิด 10% จากยอดจัดเก็บจริง " . number_format($collectedRevenue, 2) . " บาท (อัปเดตอัตโนมัติ)";

    // Find if 10% voucher exists
    $vStmt = $pdo->prepare("SELECT id FROM payment_vouchers WHERE billing_cycle_id = ? AND (voucher_type = 'COMMISSION_10' OR voucher_type = 'COLLECTOR') LIMIT 1");
    $vStmt->execute([(int)$cycle['id']]);
    $existing = $vStmt->fetch();

    if ($existing) {
        $upStmt = $pdo->prepare("UPDATE payment_vouchers SET amount = ?, amount_text_th = ?, calculation_basis = ? WHERE id = ?");
        $upStmt->execute([$commission, $amountTextTh, $basis, (int)$existing['id']]);
    } else {
        $vNo = sprintf("บจ-%s/01", $cycle_code);
        $insStmt = $pdo->prepare("
            INSERT INTO payment_vouchers (
                voucher_no, billing_cycle_id, voucher_date, recipient_name, recipient_position,
                voucher_type, amount, amount_text_th, calculation_basis, approved_by
            ) VALUES (?, ?, CURDATE(), 'นายสมาน เก็บเงินดี', 'เจ้าหน้าที่จัดเก็บค่าน้ำประปา', 'COMMISSION_10', ?, ?, ?, 'ประธานคณะกรรมการประปาหมู่บ้านวังยาง')
        ");
        $insStmt->execute([$vNo, (int)$cycle['id'], $commission, $amountTextTh, $basis]);
    }

    send_json([
        'success' => true,
        'message' => 'อัปเดตยอดค่าตอบแทน 10% เรียบร้อยแล้ว',
        'collectedRevenue' => $collectedRevenue,
        'commission' => $commission
    ]);
}

// 4. GET VOUCHERS LIST
$cycle_code = $_GET['cycle'] ?? '8-2567';

$cycleStmt = $pdo->prepare("SELECT * FROM billing_cycles WHERE cycle_code = ?");
$cycleStmt->execute([$cycle_code]);
$cycle = $cycleStmt->fetch();

if (!$cycle) send_json(['error' => 'ไม่พบงวดประจำเดือน'], 404);

// คำนวณยอดจัดเก็บจริง
$paidStmt = $pdo->prepare("
    SELECT SUM(grand_total) as total_paid
    FROM meter_readings 
    WHERE billing_cycle_id = ? AND payment_status = 'PAID'
");
$paidStmt->execute([(int)$cycle['id']]);
$paidRow = $paidStmt->fetch();
$collectedRevenue = $paidRow && $paidRow['total_paid'] ? (float)$paidRow['total_paid'] : 0.0;
$collectorCommission = round($collectedRevenue * 0.10, 2);

// Check vouchers in DB
$vStmt = $pdo->prepare("SELECT * FROM payment_vouchers WHERE billing_cycle_id = ? ORDER BY id ASC");
$vStmt->execute([(int)$cycle['id']]);
$dbVouchers = $vStmt->fetchAll();

// If empty, seed default realistic templates
if (count($dbVouchers) === 0) {
    $defaults = [
        [
            'name' => 'นายสมาน เก็บเงินดี',
            'position' => 'เจ้าหน้าที่จัดเก็บค่าน้ำประปา',
            'type' => 'COMMISSION_10',
            'amount' => $collectorCommission,
            'basis' => "คิด 10% จากยอดจัดเก็บจริง " . number_format($collectedRevenue, 2) . " บาท"
        ],
        [
            'name' => 'นายประสิทธิ์ ดูแลดี',
            'position' => 'ผู้ดูแลรักษาระบบประปาและบ่อบาดาล',
            'type' => 'CARETAKER',
            'amount' => 3000.00,
            'basis' => "ค่าตอบแทนประจำเดือนในการเปิด-ปิดและดูแลความสะอาดระบบประปา"
        ],
        [
            'name' => 'นายประธาน บริหารกิจการ',
            'position' => 'ประธานกรรมการการประปาหมู่บ้านวังยาง',
            'type' => 'COMMITTEE',
            'amount' => 2500.00,
            'basis' => "ค่าตอบแทนและเบี้ยประชุมคณะกรรมการบริหารกิจการประปา"
        ],
        [
            'name' => 'ร้านวังยางการช่าง & อุปกรณ์',
            'position' => 'ผู้จัดจำหน่ายวัสดุอุปกรณ์',
            'type' => 'MAINTENANCE',
            'amount' => 1450.00,
            'basis' => "ค่าท่อ PVC ข้อต่อ กาวประสานท่อ และอุปกรณ์ซ่อมแซมจุดรั่วไหลซอย 2"
        ]
    ];

    $insStmt = $pdo->prepare("
        INSERT INTO payment_vouchers (
            voucher_no, billing_cycle_id, voucher_date, recipient_name, recipient_position,
            voucher_type, amount, amount_text_th, calculation_basis, approved_by
        ) VALUES (?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, 'ประธานคณะกรรมการประปาหมู่บ้านวังยาง')
    ");

    foreach ($defaults as $idx => $d) {
        $vNo = sprintf("บจ-%s/%02d", $cycle_code, $idx + 1);
        $insStmt->execute([
            $vNo,
            (int)$cycle['id'],
            $d['name'],
            $d['position'],
            $d['type'],
            $d['amount'],
            baht_text($d['amount']),
            $d['basis']
        ]);
    }

    $vStmt->execute([(int)$cycle['id']]);
    $dbVouchers = $vStmt->fetchAll();
}

$vouchers = [];
$totalDisbursed = 0.0;

foreach ($dbVouchers as $row) {
    $amt = (float)$row['amount'];
    $totalDisbursed += $amt;
    $vouchers[] = [
        'id' => (int)$row['id'],
        'voucherNo' => $row['voucher_no'],
        'cycleCode' => $cycle_code,
        'date' => format_thai_date($row['voucher_date']),
        'voucherDate' => $row['voucher_date'],
        'recipientName' => $row['recipient_name'],
        'recipientPosition' => $row['recipient_position'],
        'voucherType' => $row['voucher_type'],
        'amount' => $amt,
        'amountTextTh' => $row['amount_text_th'],
        'calculationBasis' => $row['calculation_basis'] ?? '',
        'description' => $row['description'] ?? '',
        'approvedBy' => $row['approved_by'] ?? 'ประธานคณะกรรมการประปาหมู่บ้านวังยาง'
    ];
}

send_json([
    'cycleCode' => $cycle_code,
    'collectedRevenue' => $collectedRevenue,
    'collectorCommission' => $collectorCommission,
    'totalDisbursed' => $totalDisbursed,
    'netBalance' => $collectedRevenue - $totalDisbursed,
    'vouchers' => $vouchers
]);

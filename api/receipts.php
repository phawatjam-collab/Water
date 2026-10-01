<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bahtText.php';

$cycle_code = $_GET['cycle'] ?? '8-2567';
$customer_id = (int)($_GET['customerId'] ?? 1);

// ดึงรอบบิล
$cycleStmt = $pdo->prepare("SELECT * FROM billing_cycles WHERE cycle_code = ?");
$cycleStmt->execute([$cycle_code]);
$cycle = $cycleStmt->fetch();

// ดึงลูกค้า
$custStmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$custStmt->execute([$customer_id]);
$cust = $custStmt->fetch();

if (!$cycle || !$cust) {
    send_json(['error' => 'ไม่พบข้อมูลงวดหรือผู้ใช้น้ำ'], 404);
}

// ดึงบันทึกมิเตอร์
$readStmt = $pdo->prepare("SELECT * FROM meter_readings WHERE billing_cycle_id = ? AND customer_id = ?");
$readStmt->execute([(int)$cycle['id'], $customer_id]);
$reading = $readStmt->fetch();

if (!$reading) {
    send_json(['error' => 'ไม่พบบันทึกมิเตอร์ของผู้ใช้น้ำรายนี้'], 404);
}

$grand_total = (float)$reading['grand_total'];
$receipt_no = $reading['receipt_no'] ?: sprintf("%d-%d/%03d", (int)$cycle['month'], (int)$cycle['year_be'], 500 + (int)$cust['seq_no']);

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];

$month_num = (int)$cycle['month'];
$month_name = $thai_months[$month_num] ?? "เดือน {$month_num}";

$receipt = [
    'organizationName' => 'การประปาหมู่บ้านวังยาง หมู่ที่ 3',
    'receiptNo' => $receipt_no,
    'billingCycleText' => "งวดประจำเดือน {$month_name} {$cycle['year_be']}",
    'issueDateText' => date('j') . ' ' . ($thai_months[(int)date('n')] ?? '') . ' ' . (date('Y') + 543),
    'customer' => [
        'code' => $cust['customer_code'],
        'seq' => (int)$cust['seq_no'],
        'name' => $cust['first_name'] . ' ' . $cust['last_name'],
        'houseNo' => $cust['house_no'],
        'zone' => $cust['zone'],
        'phone' => $cust['phone'] ?: '-',
        'meterSerial' => $cust['meter_serial'] ?: '-'
    ],
    'meter' => [
        'previous' => (float)$reading['previous_reading'],
        'current' => (float)$reading['current_reading'],
        'unitsUsed' => (float)$reading['units_used'],
        'ratePerUnit' => (float)$reading['rate_per_unit']
    ],
    'breakdown' => [
        'waterCharge' => (float)$reading['water_charge'],
        'maintenanceFee' => (float)$reading['maintenance_fee'],
        'currentTotal' => (float)$reading['current_total'],
        'previousArrears' => (float)$reading['previous_arrears'],
        'grandTotal' => $grand_total
    ],
    'totalAmountTextTh' => baht_text($grand_total),
    'collectorName' => 'นายสมาน เก็บเงินดี',
    'villageCommitteeHeader' => 'คณะกรรมการบริหารกิจการประปาหมู่บ้านวังยาง'
];

send_json($receipt);

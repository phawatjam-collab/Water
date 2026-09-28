<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bahtText.php';

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

// คำนวณค่าตอบแทนคนเก็บค่าน้ำ 10%
$collectorCommission = round($collectedRevenue * 0.10, 2);

$personnel = [
    [
        'name' => 'นายสมาน เก็บเงินดี',
        'position' => 'เจ้าหน้าที่จัดเก็บค่าน้ำประปา',
        'type' => 'COLLECTOR',
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

$thai_months = [
    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
    5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
    9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
];
$date_th = date('j') . ' ' . ($thai_months[(int)date('n')] ?? '') . ' ' . (date('Y') + 543);

$vouchers = [];
foreach ($personnel as $idx => $p) {
    $voucherNo = sprintf("ฎีกา-%s/%02d", $cycle_code, $idx + 1);
    $vouchers[] = [
        'voucherNo' => $voucherNo,
        'cycleCode' => $cycle_code,
        'date' => $date_th,
        'recipientName' => $p['name'],
        'recipientPosition' => $p['position'],
        'voucherType' => $p['type'],
        'amount' => $p['amount'],
        'amountTextTh' => baht_text($p['amount']),
        'calculationBasis' => $p['basis'],
        'approvedBy' => 'ประธานคณะกรรมการประปาหมู่บ้านวังยาง'
    ];
}

send_json([
    'cycleCode' => $cycle_code,
    'collectedRevenue' => $collectedRevenue,
    'vouchers' => $vouchers
]);

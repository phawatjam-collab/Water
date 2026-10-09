<?php
/**
 * API ทะเบียนติดตามลูกหนี้ค่าน้ำประปาค้างชำระ
 * จำแนกตามอายุหนี้ (Debt Aging Analysis) และจัดการหนังสือเตือนระงับการจ่ายน้ำ
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/bahtText.php';

$cycle_code = $_GET['cycle'] ?? '8-2567';

// 1. ดึงรอบบิลปัจจุบัน
$cycleStmt = $pdo->prepare("SELECT * FROM billing_cycles WHERE cycle_code = ?");
$cycleStmt->execute([$cycle_code]);
$currentCycle = $cycleStmt->fetch();

if (!$currentCycle) {
    send_json(['error' => 'ไม่พบข้อมูลรอบบิล'], 404);
}

$cycleId = (int)$currentCycle['id'];

// 2. ดึงรายการลูกหนี้ค้างชำระทั้งหมดในระบบสำหรับงวดนี้
// เงื่อนไข: สถานะเป็น UNPAID หรือมียอด previous_arrears > 0
$stmt = $pdo->prepare("
    SELECT 
        r.id as reading_id,
        r.billing_cycle_id,
        r.customer_id,
        r.previous_reading,
        r.current_reading,
        r.units_used,
        r.rate_per_unit,
        r.water_charge,
        r.maintenance_fee,
        r.current_total,
        r.previous_arrears,
        r.grand_total,
        r.payment_status,
        c.customer_code,
        c.seq_no,
        c.first_name,
        c.last_name,
        c.house_no,
        c.zone,
        c.phone,
        c.meter_serial,
        c.status as customer_status
    FROM meter_readings r
    JOIN customers c ON r.customer_id = c.id
    WHERE r.billing_cycle_id = ?
    ORDER BY r.grand_total DESC, c.seq_no ASC
");
$stmt->execute([$cycleId]);
$rows = $stmt->fetchAll();

$debtors = [];
$totalDebt = 0;
$count1M = 0; $amount1M = 0;
$count2M = 0; $amount2M = 0;
$count3M = 0; $amount3M = 0;

foreach ($rows as $row) {
    $isUnpaid = ($row['payment_status'] === 'UNPAID');
    $prevArrears = (float)$row['previous_arrears'];
    $currentTotal = (float)$row['current_total'];
    $grandTotal = (float)$row['grand_total'];

    // หากชำระแล้ว และไม่มีหนี้ค้างเก่า ให้ข้าม
    if (!$isUnpaid && $prevArrears <= 0) {
        continue;
    }

    $debtAmount = $isUnpaid ? $grandTotal : $prevArrears;
    if ($debtAmount <= 0) continue;

    // คำนวณอายุหนี้ (Debt Aging) ตามจำนวนงวดค้างชำระ
    $months = 1;
    if ($prevArrears > 0) {
        // ประเมินงวดจากอัตราค่าน้ำเฉลี่ย หรือสัดส่วนยอดค้าง
        $avgMonthly = max(100.0, $currentTotal);
        $estimatedPrevMonths = ceil($prevArrears / $avgMonthly);
        $months = ($isUnpaid ? 1 : 0) + $estimatedPrevMonths;
    }

    if ($months == 1) {
        $urgency = 'WARNING_1';
        $urgencyText = 'เตือนรอบที่ 1';
        $action = 'ระบุยอดค้างในใบแจ้งหนี้รอบใหม่';
        $badgeClass = 'badge-aging-1';
        $count1M++;
        $amount1M += $debtAmount;
    } elseif ($months == 2) {
        $urgency = 'WARNING_2';
        $urgencyText = 'เตือนรอบที่ 2';
        $action = 'ออกหนังสือเตือนฉบับที่ 2 (ให้เวลา 7 วัน)';
        $badgeClass = 'badge-aging-2';
        $count2M++;
        $amount2M += $debtAmount;
    } else {
        $urgency = 'CRITICAL';
        $urgencyText = 'วิกฤต (ค้างเกิน 3 เดือน)';
        $action = 'เสนอระงับการจ่ายน้ำ / ปลดมิเตอร์ชั่วคราว';
        $badgeClass = 'badge-aging-3';
        $count3M++;
        $amount3M += $debtAmount;
    }

    $totalDebt += $debtAmount;

    $debtors[] = [
        'customerId' => (int)$row['customer_id'],
        'readingId' => (int)$row['reading_id'],
        'customerCode' => $row['customer_code'],
        'seqNo' => (int)$row['seq_no'],
        'name' => "{$row['first_name']} {$row['last_name']}",
        'houseNo' => $row['house_no'],
        'zone' => $row['zone'],
        'phone' => $row['phone'] ?: '-',
        'meterSerial' => $row['meter_serial'] ?: '-',
        'monthsOverdue' => $months,
        'currentBill' => $currentTotal,
        'previousArrears' => $prevArrears,
        'totalDebt' => $debtAmount,
        'totalDebtTextTh' => baht_text($debtAmount),
        'paymentStatus' => $row['payment_status'],
        'urgency' => $urgency,
        'urgencyText' => $urgencyText,
        'actionText' => $action,
        'badgeClass' => $badgeClass
    ];
}

send_json([
    'cycle' => [
        'code' => $currentCycle['cycle_code'],
        'month' => (int)$currentCycle['month'],
        'yearBe' => (int)$currentCycle['year_be']
    ],
    'summary' => [
        'totalDebtors' => count($debtors),
        'totalDebt' => round($totalDebt, 2),
        'totalDebtTextTh' => baht_text($totalDebt),
        'count1M' => $count1M,
        'amount1M' => round($amount1M, 2),
        'count2M' => $count2M,
        'amount2M' => round($amount2M, 2),
        'count3M' => $count3M,
        'amount3M' => round($amount3M, 2)
    ],
    'debtors' => $debtors
]);

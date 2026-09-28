<?php
require_once __DIR__ . '/db.php';

$cycle_code = $_GET['cycle'] ?? '8-2567';

$cycleStmt = $pdo->prepare("SELECT * FROM billing_cycles WHERE cycle_code = ?");
$cycleStmt->execute([$cycle_code]);
$cycle = $cycleStmt->fetch();

if (!$cycle) {
    die("ไม่พบงวดประจำเดือน {$cycle_code}");
}

$readingsStmt = $pdo->prepare("
    SELECT 
        c.seq_no as seq,
        c.customer_code as code,
        CONCAT(c.first_name, ' ', c.last_name) as fullname,
        c.house_no as house,
        c.zone as zone,
        r.previous_reading as prev_reading,
        r.current_reading as curr_reading,
        r.units_used as units,
        r.water_charge as charge,
        r.maintenance_fee as fee,
        r.current_total as current_total,
        r.previous_arrears as arrears,
        r.grand_total as grand_total,
        r.payment_status as status,
        r.receipt_no as receipt
    FROM meter_readings r
    JOIN customers c ON r.customer_id = c.id
    WHERE r.billing_cycle_id = ?
    ORDER BY c.seq_no ASC
");
$readingsStmt->execute([(int)$cycle['id']]);
$rows = $readingsStmt->fetchAll();

// ส่งออกเป็นไฟล์ CSV ที่มี UTF-8 BOM เพื่อให้ Excel ใน Windows เปิดภาษาไทยได้โดยไม่เพี้ยน
$filename = "Water_Billing_{$cycle_code}.csv";

header('Content-Type: text/csv; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

// Write UTF-8 BOM for Microsoft Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');

// Headers
fputcsv($output, [
    'ลำดับ', 'รหัสผู้ใช้น้ำ', 'ชื่อ-สกุล', 'บ้านเลขที่', 'โซน/กลุ่ม',
    'เลขมิเตอร์ครั้งก่อน', 'เลขมิเตอร์ครั้งหลัง', 'หน่วยที่ใช้',
    'ค่าน้ำ (บาท)', 'ค่าบำรุงรักษา (บาท)', 'ยอดงวดนี้ (บาท)',
    'ยอดค้างเก่า (บาท)', 'ยอดรวมสุทธิ (บาท)', 'สถานะการชำระ', 'เลขที่ใบเสร็จ'
]);

foreach ($rows as $r) {
    fputcsv($output, [
        $r['seq'],
        $r['code'],
        $r['fullname'],
        $r['house'],
        $r['zone'],
        number_format($r['prev_reading'], 2, '.', ''),
        number_format($r['curr_reading'], 2, '.', ''),
        number_format($r['units'], 2, '.', ''),
        number_format($r['charge'], 2, '.', ''),
        number_format($r['fee'], 2, '.', ''),
        number_format($r['current_total'], 2, '.', ''),
        number_format($r['arrears'], 2, '.', ''),
        number_format($r['grand_total'], 2, '.', ''),
        $r['status'] === 'PAID' ? 'ชำระแล้ว' : 'ค้างชำระ',
        $r['receipt'] ?: '-'
    ]);
}

fclose($output);
exit;

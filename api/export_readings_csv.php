<?php
/**
 * API: ส่งออกไฟล์ CSV สมุดจดมิเตอร์สำหรับนำไปกรอกใน Excel
 * รองรับภาษาไทย 100% ด้วย UTF-8 BOM
 */
require_once __DIR__ . '/../auth.php';
requireRole(['staff', 'admin']);
require_once __DIR__ . '/db.php';

$cycle_code = $_GET['cycle'] ?? '8-2567';

// ดึงรอบบิล
$cycleStmt = $pdo->prepare("SELECT * FROM billing_cycles WHERE cycle_code = ?");
$cycleStmt->execute([$cycle_code]);
$cycle = $cycleStmt->fetch();

if (!$cycle) {
    die("ไม่พบงวดประจำเดือน " . htmlspecialchars($cycle_code));
}

$cycle_id = (int)$cycle['id'];

// ดึงรายการมิเตอร์และลูกบ้าน
$stmt = $pdo->prepare("
    SELECT c.seq_no, c.customer_code, c.first_name, c.last_name, c.house_no, c.zone, c.phone, c.meter_serial,
           COALESCE(r.previous_reading, 0) as previous_reading,
           COALESCE(r.current_reading, 0) as current_reading,
           COALESCE(r.units_used, 0) as units_used,
           COALESCE(r.grand_total, 0) as grand_total
    FROM customers c
    LEFT JOIN meter_readings r ON r.customer_id = c.id AND r.billing_cycle_id = ?
    ORDER BY c.seq_no ASC
");
$stmt->execute([$cycle_id]);
$rows = $stmt->fetchAll();

// ตั้งชื่อไฟล์
$filename = "meter_readings_" . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $cycle_code) . ".csv";

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// ส่งออก UTF-8 BOM สำหรับ Microsoft Excel ภาษาไทย
echo "\xEF\xBB\xBF";

$fp = fopen('php://output', 'w');

// Header Columns
fputcsv($fp, [
    'ลำดับ',
    'รหัสผู้ใช้',
    'ชื่อ',
    'นามสกุล',
    'บ้านเลขที่',
    'คุ้ม_โซน',
    'เบอร์โทร',
    'เลขซีเรียลมิเตอร์',
    'เลขครั้งก่อน',
    'เลขครั้งหลัง_กรอกที่นี่'
]);

foreach ($rows as $r) {
    fputcsv($fp, [
        $r['seq_no'],
        $r['customer_code'],
        $r['first_name'],
        $r['last_name'],
        $r['house_no'],
        $r['zone'],
        $r['phone'],
        $r['meter_serial'],
        (float)$r['previous_reading'],
        ((float)$r['current_reading'] > 0) ? (float)$r['current_reading'] : ''
    ]);
}

fclose($fp);
exit;

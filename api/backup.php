<?php
/**
 * API สำรองฐานข้อมูล 1-Click Backup (SQL Dump Exporter)
 * ดึงโครงสร้างตาราง ข้อมูลทั้งหมด และมุมมองความสัมพันธ์ (Views & FK) ออกมาเป็นไฟล์ .sql
 */
require_once __DIR__ . '/../auth.php';
$currentUser = requireRole(['admin']);

require_once __DIR__ . '/db.php';

$dbname = 'db_city_water_supply';
$filename = "{$dbname}_backup_" . date('Y-m-d_His') . ".sql";

header('Content-Type: application/sql; charset=utf-8');
header("Content-Disposition: attachment; filename=\"{$filename}\"");

// UTF-8 BOM
echo "\xEF\xBB\xBF";

echo "-- ========================================================\n";
echo "-- ระบบบริหารจัดการ การประปาหมู่บ้านวังยาง\n";
echo "-- สำรองข้อมูลฐานข้อมูลอัตโนมัติ (1-Click SQL Backup)\n";
echo "-- ฐานข้อมูล: {$dbname}\n";
echo "-- วันที่สำรองข้อมูล: " . date('Y-m-d H:i:s') . "\n";
echo "-- ผู้ดำเนินการ: " . ($currentUser['name'] ?? 'Admin') . "\n";
echo "-- ========================================================\n\n";

echo "SET NAMES utf8mb4;\n";
echo "SET FOREIGN_KEY_CHECKS = 0;\n\n";

// 1. ดึงรายชื่อตารางทั้งหมด
$tablesStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
$tables = $tablesStmt->fetchAll(PDO::FETCH_NUM);

foreach ($tables as $tRow) {
    $table = $tRow[0];
    echo "-- --------------------------------------------------------\n";
    echo "-- โครงสร้างตาราง: `{$table}`\n";
    echo "-- --------------------------------------------------------\n";
    echo "DROP TABLE IF EXISTS `{$table}`;\n";
    
    $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_NUM);
    echo $createStmt[1] . ";\n\n";

    // ดึงข้อมูลในตาราง
    $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        echo "-- ข้อมูลในตาราง `{$table}` (" . count($rows) . " รายการ)\n";
        $columns = array_keys($rows[0]);
        $colList = implode('`, `', $columns);
        
        foreach ($rows as $row) {
            $valList = [];
            foreach ($row as $v) {
                if ($v === null) {
                    $valList[] = "NULL";
                } else {
                    $valList[] = $pdo->quote($v);
                }
            }
            echo "INSERT INTO `{$table}` (`{$colList}`) VALUES (" . implode(', ', $valList) . ");\n";
        }
        echo "\n";
    }
}

// 2. ดึง Views
$viewsStmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
$views = $viewsStmt->fetchAll(PDO::FETCH_NUM);
foreach ($views as $vRow) {
    $view = $vRow[0];
    echo "-- --------------------------------------------------------\n";
    echo "-- มุมมองตาราง (VIEW): `{$view}`\n";
    echo "-- --------------------------------------------------------\n";
    $createViewStmt = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch(PDO::FETCH_NUM);
    echo "DROP VIEW IF EXISTS `{$view}`;\n";
    echo $createViewStmt[1] . ";\n\n";
}

echo "SET FOREIGN_KEY_CHECKS = 1;\n";
echo "-- จบไฟล์สำรองข้อมูล (End of SQL Dump)\n";
exit;

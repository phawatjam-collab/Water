<?php
/**
 * การเชื่อมต่อฐานข้อมูล MySQL (db_wangyang_water) ผ่าน PDO
 * ระบบบริหารจัดการการประปาหมู่บ้านวังยาง (งานกลุ่ม)
 */

$host = 'localhost';
$db_name = 'db_wangyang_water';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'success' => false,
        'error' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล db_wangyang_water ได้: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function send_json($data, $statusCode = 200) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

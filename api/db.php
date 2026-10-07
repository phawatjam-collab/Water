<?php
/**
 * การเชื่อมต่อฐานข้อมูล MySQL (db_city_water_supply) ผ่าน PDO
 * ระบบบริหารจัดการการประปาหมู่บ้านวังยาง (XAMPP Edition)
 */

$host = 'localhost';
$db_name = 'db_city_water_supply';
$username = 'root';
$password = '';

date_default_timezone_set('Asia/Bangkok');

try {
    $pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+07:00'"
    ]);
} catch (PDOException $e) {
    // ระบบอำนวยความสะดวก: หากยังไม่ได้นำเข้าฐานข้อมูลใน phpMyAdmin (Error 1049: Unknown database)
    // ให้สร้างฐานข้อมูล db_city_water_supply และนำเข้า database.sql ให้อัตโนมัติทันที
    if ($e->getCode() == 1049 || stripos($e->getMessage(), 'Unknown database') !== false) {
        try {
            $rootPdo = new PDO("mysql:host={$host};charset=utf8mb4", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;");

            $pdo = new PDO("mysql:host={$host};dbname={$db_name};charset=utf8mb4", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4, time_zone = '+07:00'"
            ]);

            $sqlFile = dirname(__DIR__) . '/database.sql';
            if (file_exists($sqlFile)) {
                $sqlContent = file_get_contents($sqlFile);
                if (!empty($sqlContent)) {
                    $pdo->exec($sqlContent);
                }
            }
        } catch (Exception $autoEx) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'success' => false,
                'error' => 'ไม่สามารถเชื่อมต่อหรือสร้างฐานข้อมูลอัตโนมัติได้: ' . $autoEx->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    } else {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'error' => 'ไม่สามารถเชื่อมต่อฐานข้อมูล db_city_water_supply ได้: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

function send_json($data, $statusCode = 200) {
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

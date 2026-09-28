<?php
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sql = "SELECT * FROM customers";
    $params = [];

    if ($search !== '') {
        $sql .= " WHERE first_name LIKE ? OR last_name LIKE ? OR house_no LIKE ? OR customer_code LIKE ?";
        $kw = "%{$search}%";
        $params = [$kw, $kw, $kw, $kw];
    }
    $sql .= " ORDER BY seq_no ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    send_json($stmt->fetchAll());
}

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true);
    if (!$body) send_json(['error' => 'Invalid JSON'], 400);

    $customer_code = $body['customerCode'] ?? '';
    $seq_no = (int)($body['seqNo'] ?? 99);
    $first_name = $body['firstName'] ?? '';
    $last_name = $body['lastName'] ?? '';
    $house_no = $body['houseNo'] ?? '';
    $zone = $body['zone'] ?? '';
    $phone = $body['phone'] ?? '';
    $meter_serial = $body['meterSerial'] ?? '';

    $stmt = $pdo->prepare("
        INSERT INTO customers (customer_code, seq_no, first_name, last_name, house_no, zone, phone, meter_serial)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$customer_code, $seq_no, $first_name, $last_name, $house_no, $zone, $phone, $meter_serial]);
    send_json(['success' => true, 'id' => $pdo->lastInsertId()]);
}

if ($method === 'PUT') {
    $body = json_decode(file_get_contents('php://input'), true);
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0 || !$body) send_json(['error' => 'Invalid ID or data'], 400);

    $stmt = $pdo->prepare("
        UPDATE customers 
        SET customer_code = ?, seq_no = ?, first_name = ?, last_name = ?, house_no = ?, zone = ?, phone = ?, meter_serial = ?, status = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $body['customerCode'],
        (int)$body['seqNo'],
        $body['firstName'],
        $body['lastName'],
        $body['houseNo'],
        $body['zone'],
        $body['phone'] ?? '',
        $body['meterSerial'] ?? '',
        $body['status'] ?? 'ACTIVE',
        $id
    ]);
    send_json(['success' => true]);
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) send_json(['error' => 'Invalid ID'], 400);

    $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
    $stmt->execute([$id]);
    send_json(['success' => true]);
}

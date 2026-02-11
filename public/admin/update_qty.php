<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) {
    http_response_code(403);
    exit('Not logged in');
}

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$qty = (int)($_POST['qty'] ?? 0);

if ($id <= 0) {
    http_response_code(400);
    exit('Invalid item ID');
}

try {
    if ($action === 'add') {
        // Decrease stock by 1 if available
        $stmt = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty - 1 WHERE id = ? AND stock_qty > 0");
        $stmt->execute([$id]);
    } elseif ($action === 'minus') {
        // Increase stock back by 1
        $stmt = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty + 1 WHERE id = ?");
        $stmt->execute([$id]);
    }

    echo 'OK';
} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}

<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$user = current_user();

$customer = trim($_POST['customer_name'] ?? '');
$orderType = $_POST['order_type'] ?? 'Dine-in';
$total = floatval($_POST['total'] ?? 0);
$payment = floatval($_POST['payment'] ?? 0);
$change = floatval($_POST['change'] ?? 0);
$qtys = json_decode($_POST['qty'] ?? '[]', true);

try {
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($qtys), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, stock_qty FROM inventory WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute(array_keys($qtys));
    $rows = $stmt->fetchAll(PDO::FETCH_UNIQUE);

    // Insert order
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, customer_name, total_amount, status, order_type, payment, change_amount)
                           VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$user['id'], $customer, $total, 'completed', $orderType, $payment, $change]);
    $orderId = $pdo->lastInsertId();

    // Insert items
    $stmtInsert = $pdo->prepare("INSERT INTO order_items (order_id, inventory_id, qty, unit_price, line_total) VALUES (?,?,?,?,?)");
    $stmtUpdateStock = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty - ? WHERE id = ?");

    foreach ($qtys as $id => $q) {
        $unit = $rows[$id]['price'];
        $line = $unit * $q;
        $stmtInsert->execute([$orderId, $id, $q, $unit, $line]);
        $stmtUpdateStock->execute([$q, $id]);
    }

    $pdo->commit();

    header("Location: receipt.php?id=$orderId");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . htmlspecialchars($e->getMessage());
}

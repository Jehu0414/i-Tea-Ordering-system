<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$user = current_user();

$orderType = $_GET['type'] ?? 'Dine-in';
$customer = $_GET['customer'] ?? '';
$paid = (float)($_GET['paid'] ?? 0);
$itemsOrdered = json_decode($_GET['items'] ?? '[]', true);

if (empty($itemsOrdered)) {
    echo "Invalid order data.";
    exit;
}

try {
    $pdo->beginTransaction();

    $placeholders = implode(',', array_fill(0, count($itemsOrdered), '?'));
    $ids = array_keys($itemsOrdered);
    $stmt = $pdo->prepare("SELECT id, name, price, stock_qty FROM inventory WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll(PDO::FETCH_UNIQUE);

    $total = 0;
    $takeoutFee = 0;

    foreach ($itemsOrdered as $id => $q) {
        if (!isset($rows[$id])) throw new Exception("Item not found (ID: $id)");
        if ($rows[$id]['stock_qty'] < $q)
            throw new Exception("Not enough stock for " . $rows[$id]['name']);
        $line = $rows[$id]['price'] * $q;
        $total += $line;
        if ($orderType === 'Take-out') $takeoutFee += 5 * $q;
    }

    $grandTotal = $total + $takeoutFee;
    $change = $paid - $grandTotal;

    if ($change < 0) throw new Exception("Insufficient payment.");

    // Save order
    $stmt = $pdo->prepare("INSERT INTO orders 
        (user_id, customer_name, order_type, takeout_fee, total_amount, amount_paid, change_due, status)
        VALUES (?,?,?,?,?,?,?, 'completed')");
    $stmt->execute([$user['id'], $customer, $orderType, $takeoutFee, $grandTotal, $paid, $change]);
    $orderId = $pdo->lastInsertId();

    $stmtInsert = $pdo->prepare("INSERT INTO order_items (order_id, inventory_id, qty, unit_price, line_total) VALUES (?,?,?,?,?)");
    $stmtUpdate = $pdo->prepare("UPDATE inventory SET stock_qty = stock_qty - ? WHERE id = ?");

    foreach ($itemsOrdered as $id => $q) {
        $unit = $rows[$id]['price'];
        $line = $unit * $q;
        $stmtInsert->execute([$orderId, $id, $q, $unit, $line]);
        $stmtUpdate->execute([$q, $id]);
    }

    $pdo->commit();

    echo "<!DOCTYPE html>
    <html>
    <head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head>
    <body>
    <script>
    Swal.fire({
      title: 'Payment Successful!',
      html: 'Total: ₱" . number_format($grandTotal,2) . "<br>Change: ₱" . number_format($change,2) . "',
      icon: 'success',
      confirmButtonText: 'View Receipt'
    }).then(() => {
      window.location.href = 'receipt.php?id=$orderId';
    });
    </script>
    </body>
    </html>";
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>

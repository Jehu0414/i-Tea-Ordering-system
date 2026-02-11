<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$user = current_user();

$orderType = $_GET['type'] ?? 'Dine-in';
$customer = $_GET['customer'] ?? '';
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

    // Ask for payment
    echo "<!DOCTYPE html>
    <html>
    <head>
      <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    </head>
    <body>
      <script>
        Swal.fire({
          title: 'Total: ₱" . number_format($grandTotal,2) . "',
          text: 'Enter payment amount:',
          input: 'number',
          inputAttributes: { min: " . ceil($grandTotal) . ", step: 1 },
          confirmButtonText: 'Pay',
          showCancelButton: false
        }).then((result) => {
          if (result.value) {
            window.location.href = 'process-order-final.php?type=" . urlencode($orderType) . "&customer=" . urlencode($customer) . "&paid=' + result.value + '&items=" . urlencode(json_encode($itemsOrdered)) . "';
          }
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

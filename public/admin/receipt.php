<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (!is_logged_in()) header('Location: /public/login.php');
refresh_user_session($pdo);
$u = current_user();

// Restrict access
if (!in_array($u['role'], ['admin', 'staff'])) {
  echo "Access denied.";
  exit;
}

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) { echo "Invalid order ID."; exit; }

// Fetch order
$stmt = $pdo->prepare("
  SELECT o.*, u.full_name AS staff_name, u.role AS staff_role
  FROM orders o
  LEFT JOIN users u ON u.id = o.user_id
  WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) { echo "Order not found."; exit; }

// Fetch ordered items
$stmt = $pdo->prepare("
  SELECT oi.*, i.name
  FROM order_items oi
  JOIN inventory i ON i.id = oi.inventory_id
  WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

// Safe print function
if (!function_exists('e')) {
  function e($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

// Date formatting
$datetime = date('Y-m-d h:i A', strtotime($order['created_at']));

// Take-out fee per item
$takeoutFeePerBox = 5;
$isTakeout = (($order['order_type'] ?? '') === 'Take-out');
$total = 0;
$totalTakeoutBoxes = 0;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Receipt #<?= e($orderId) ?> - iTea Ordering</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @media print { .no-print { display:none } }
    body { background: #f8f9fa; }
    .receipt {
      max-width: 380px;
      margin: 30px auto;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      padding: 20px;
      font-family: Arial, sans-serif;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .header-row {
      display: flex;
      justify-content: space-between;
      font-size: 14px;
      margin-bottom: 5px;
    }
    .receipt h5 { font-weight: bold; margin-bottom: 10px; text-align: center; }
    .receipt table th, .receipt table td { padding: 6px; font-size: 14px; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="receipt">

    <!-- Header -->
    <div class="header-row">
      <div><strong>Date:</strong> <?= e($datetime) ?></div>
      <div><strong>#</strong> <?= e($orderId) ?></div>
    </div>

    <h5>🍵 i - Tea Ordering</h5>
    <hr>

  
    <div><strong>Customer:</strong> <?= e($order['customer_name']) ?></div>
    <div><strong>Order Type:</strong> <?= e($order['order_type'] ?? 'Dine-in') ?></div>

    <hr>

    <table class="table table-sm">
      <thead class="table-success">
        <tr>
          <th>Item</th>
          <th class="text-center">Qty</th>
          <th class="text-end">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($items as $it): ?>
          <?php
            $lineTotal = $it['unit_price'] * $it['qty'];
            $total += $lineTotal;
            if ($isTakeout) {
              $totalTakeoutBoxes += $it['qty'];
            }
          ?>
          <tr>
            <td><?= e($it['name']) ?></td>
            <td class="text-center"><?= e($it['qty']) ?></td>
            <td class="text-end">₱<?= number_format($lineTotal, 2) ?></td>
          </tr>
        <?php endforeach; ?>

        <!-- Add Take-Out Boxes if applicable -->
        <?php if ($isTakeout && $totalTakeoutBoxes > 0): ?>
          <?php $takeoutCost = $totalTakeoutBoxes * $takeoutFeePerBox; ?>
          <tr>
            <td><em>Take-Out Box</em></td>
            <td class="text-center"><?= e($totalTakeoutBoxes) ?></td>
            <td class="text-end">₱<?= number_format($takeoutCost, 2) ?></td>
          </tr>
          <?php $total += $takeoutCost; ?>
        <?php endif; ?>
      </tbody>
    </table>

    <hr>
    <p class="text-end fw-bold">Total: ₱<?= number_format($total, 2) ?></p>

    <?php if (!empty($order['amount_paid'])): ?>
      <p class="text-end"><strong>Amount Paid:</strong> ₱<?= number_format($order['amount_paid'], 2) ?></p>
      <p class="text-end"><strong>Change:</strong> ₱<?= number_format($order['change_due'], 2) ?></p>
    <?php endif; ?>

    <hr>
    <p class="text-center mt-2">Thank you for your purchase!</p>

    <div class="no-print text-center mt-4">
      <button class="btn btn-primary btn-sm" onclick="window.print()">🖨 Print Receipt</button>
      <a href="order.php" class="btn btn-secondary btn-sm">⬅ Back to Orders</a>
    </div>

  </div>
</div>
</body>
</html>
